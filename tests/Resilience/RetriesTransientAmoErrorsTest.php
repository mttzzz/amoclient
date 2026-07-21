<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
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
}

class DummyAmoJob
{
    use RetriesTransientAmoErrors;
}
