<?php

namespace mttzzz\AmoClient\Queue;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoPaymentRequiredException;
use Throwable;

/**
 * Устойчивость webhook-джоб к транзиентным флапам amoCRM: горизонт доставки
 * 24 часа вместо потери вебхука в окно ротации токенов / биллинг-флапа.
 *
 * Контракт для джобы-потребителя:
 *  - `use RetriesTransientAmoErrors;` (+ штатный InteractsWithQueue);
 *  - убрать свойство $tries — горизонт задаёт retryUntil(), не счётчик попыток
 *    (Laravel: при заданном retryUntil maxTries игнорируется);
 *  - в catch handle(): isTransientAmoError($e) → releaseForTransientAmoError()
 *    БЕЗ report (тишина — ретрай сам доедет); иначе — обычный fail/report;
 *  - не глотать transient-ошибки внутренними catch'ами — пробрасывать наружу;
 *  - алерт о реальной потере — в failed() на MaxAttemptsExceededException
 *    (горизонт исчерпан).
 *
 * Транзиент: сетевые сбои, 5xx, 401 (гонка ротации токена),
 * 402 при payed=true в octane (ложь амо).
 */
/* @phpstan-ignore trait.unused (миксин для Job-классов consumer-проектов — используется в tests/Resilience, но phpstan.neon анализирует только src/, поэтому ни один класс в src/ на неё не ссылается) */
trait RetriesTransientAmoErrors
{
    /* Абсолютный горизонт ретраев; Laravel фиксирует его в payload при dispatch. */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    /* Пауза перед следующей попыткой (сек): 1м → 5м → 15м → 30м → далее каждый час. */
    public function transientAmoBackoff(int $attempt): int
    {
        return [60, 300, 900, 1800][$attempt - 1] ?? 3600;
    }

    public function releaseForTransientAmoError(): void
    {
        $this->release($this->transientAmoBackoff($this->attempts()));
    }

    public static function isTransientAmoError(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        /* 402: решает снапшот octane accounts.payed на момент ошибки */
        if ($e instanceof AmoPaymentRequiredException) {
            return $e->isSpurious();
        }

        $previous = $e instanceof AmoCustomException ? $e->getPrevious() : null;

        if ($previous instanceof ConnectionException) {
            return true;
        }

        $status = match (true) {
            $e instanceof RequestException => $e->response->status(),
            $previous instanceof RequestException => $previous->response->status(),
            default => null,
        };

        return $status === 401 || ($status !== null && $status >= 500);
    }
}
