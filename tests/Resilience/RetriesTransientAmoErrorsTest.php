<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoPaymentRequiredException;
use mttzzz\AmoClient\Queue\RetriesTransientAmoErrors;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/*
 * Чистый unit: трейту не нужны ни БД, ни контейнер (isSpurious читает
 * заранее выставленное свойство, retryUntil — только Carbon).
 */
class RetriesTransientAmoErrorsTest extends TestCase
{
    public function test_classifier_matrix(): void
    {
        /* сетевые сбои — транзиент, голые и обёрнутые */
        $conn = new ConnectionException('cURL error 28: timeout');
        $this->assertTrue(DummyAmoJob::isTransientAmoError($conn));
        $this->assertTrue(DummyAmoJob::isTransientAmoError(new AmoCustomException($conn)));

        /* 5xx и 401 — транзиент; 400 и посторонние — нет */
        $this->assertTrue(DummyAmoJob::isTransientAmoError($this->requestException(500)));
        $this->assertTrue(DummyAmoJob::isTransientAmoError($this->requestException(503)));
        $this->assertTrue(DummyAmoJob::isTransientAmoError($this->requestException(401)));
        $this->assertFalse(DummyAmoJob::isTransientAmoError($this->requestException(400)));
        $this->assertFalse(DummyAmoJob::isTransientAmoError($this->requestException(404))); /* JSON-404 — постоянная */
        $this->assertFalse(DummyAmoJob::isTransientAmoError(new RuntimeException('boom')));

        /* обёрнутый 5xx (AmoCustomException поверх RequestException) — транзиент */
        $this->assertTrue(DummyAmoJob::isTransientAmoError(
            new AmoCustomException($this->requestException(502))
        ));

        /* 402: решает снапшот payed */
        $spurious = new AmoPaymentRequiredException($this->requestException(402));
        $spurious->accountPayed = true;
        $this->assertTrue(DummyAmoJob::isTransientAmoError($spurious));

        $real = new AmoPaymentRequiredException($this->requestException(402));
        $real->accountPayed = false;
        $this->assertFalse(DummyAmoJob::isTransientAmoError($real));
    }

    public function test_html_404_from_amo_web_router_is_transient(): void
    {
        /* штамп инцидента masterm 2026-08-06: web-роутер амо отдал свою
           HTML-страницу на ajax/todo/calendar — контроллер недостижим, транзиент */
        $htmlPage = "<!doctype html>\n<!--[if IE 7]>     <html class=\"ie lte8\"> <![endif]-->\n<html><body>404</body></html>";

        $this->assertTrue(DummyAmoJob::isTransientAmoError(
            $this->requestExceptionWith(404, $htmlPage, ['Content-Type' => 'text/html; charset=utf-8'])
        ));

        /* Content-Type достаточен и без разметки в теле */
        $this->assertTrue(DummyAmoJob::isTransientAmoError(
            $this->requestExceptionWith(404, 'Not Found', ['Content-Type' => 'text/html'])
        ));

        /* тело-разметка достаточна без Content-Type (+ ведущие пробелы); обёртка не мешает */
        $this->assertTrue(DummyAmoJob::isTransientAmoError(
            new AmoCustomException($this->requestExceptionWith(404, "  \n<html><body>x</body></html>", []))
        ));

        /* JSON-404 от API — сущность реально удалена, НЕ транзиент */
        $this->assertFalse(DummyAmoJob::isTransientAmoError(
            $this->requestExceptionWith(404, '{"title":"Not Found","status":404}', ['Content-Type' => 'application/problem+json'])
        ));

        /* пустое тело без Content-Type — постоянная */
        $this->assertFalse(DummyAmoJob::isTransientAmoError(
            $this->requestExceptionWith(404, '', [])
        ));

        /* HTML на прочих 4xx семантику не меняет */
        $this->assertFalse(DummyAmoJob::isTransientAmoError(
            $this->requestExceptionWith(403, $htmlPage, ['Content-Type' => 'text/html'])
        ));
    }

    public function test_backoff_progression(): void
    {
        $job = new DummyAmoJob;

        $this->assertSame(60, $job->transientAmoBackoff(1));
        $this->assertSame(300, $job->transientAmoBackoff(2));
        $this->assertSame(900, $job->transientAmoBackoff(3));
        $this->assertSame(1800, $job->transientAmoBackoff(4));
        $this->assertSame(3600, $job->transientAmoBackoff(5));
        $this->assertSame(3600, $job->transientAmoBackoff(42));
    }

    public function test_retry_until_is_24h_horizon(): void
    {
        $job = new DummyAmoJob;

        $this->assertEqualsWithDelta(
            Carbon::now()->addDay()->getTimestamp(),
            $job->retryUntil()->getTimestamp(),
            5
        );
    }

    private function requestException(int $status): RequestException
    {
        return new RequestException(
            new Response(new Psr7Response($status, [], '{"error":"x"}'))
        );
    }

    /** @param  array<string, string>  $headers */
    private function requestExceptionWith(int $status, string $body, array $headers): RequestException
    {
        return new RequestException(
            new Response(new Psr7Response($status, $headers, $body))
        );
    }
}

/*
 * Трейт зовёт `attempts()` и `release()` — их даёт ларавеловский
 * InteractsWithQueue, а не он сам. Подключаем оба, чтобы заглушка была честной
 * моделью настоящей джобы: без InteractsWithQueue трейт молча разваливается в
 * рантайме на первой же транзиентной ошибке, и обнаружилось бы это в проде.
 * Требование трейта к хосту стоит объявить явно в его phpdoc (хвост в SP1).
 */
class DummyAmoJob
{
    use InteractsWithQueue;
    use RetriesTransientAmoErrors;
}
