<?php

namespace mttzzz\AmoClient\Exceptions;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;

/**
 * HTTP 402 от amoCRM. Наследует AmoCustomException (message «Амо не оплачен»,
 * code 402, previous = оригинальный RequestException) — BC для потребителей,
 * ловящих AmoCustomException или матчащих message по str_contains.
 *
 * accountPayed — снапшот octane accounts.payed на момент ошибки:
 * 402 при payed=true — ложь амо (окно ночной ротации токенов ~00:05 /
 * биллинг-флап), можно ретраить; при payed=false — реальная неоплата.
 */
class AmoPaymentRequiredException extends AmoCustomException
{
    public bool $accountPayed = false;

    public int $accountId = 0;

    public static function fromRequestException(RequestException $e, int $accountId): self
    {
        $exception = new self($e);
        $exception->accountId = $accountId;
        $exception->accountPayed = (bool) DB::connection('octane')
            ->table('accounts')->where('id', $accountId)->value('payed');

        return $exception;
    }

    /** 402 при оплаченном (по octane) аккаунте — транзиентная ложь амо. */
    public function isSpurious(): bool
    {
        return $this->accountPayed;
    }
}
