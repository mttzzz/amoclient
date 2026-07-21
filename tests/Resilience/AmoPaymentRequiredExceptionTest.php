<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use mttzzz\AmoClient\Exceptions\AmoPaymentRequiredException;

class AmoPaymentRequiredExceptionTest extends ResilienceTestCase
{
    public function test_spurious_when_account_payed(): void
    {
        $requestException = $this->make402();

        $e = AmoPaymentRequiredException::fromRequestException($requestException, self::ACCOUNT_ID);

        /* BC: message/code/previous как у AmoCustomException 402-ветки */
        $this->assertSame('Амо не оплачен', $e->getMessage());
        $this->assertSame(402, $e->getCode());
        $this->assertSame($requestException, $e->getPrevious());
        $this->assertTrue($e->accountPayed);
        $this->assertTrue($e->isSpurious());
    }

    public function test_real_unpaid_when_account_not_payed(): void
    {
        DB::connection('octane')->table('accounts')
            ->where('id', self::ACCOUNT_ID)->update(['payed' => false]);

        $e = AmoPaymentRequiredException::fromRequestException($this->make402(), self::ACCOUNT_ID);

        $this->assertFalse($e->accountPayed);
        $this->assertFalse($e->isSpurious());
    }

    public function test_unknown_account_is_not_spurious(): void
    {
        /* Нет строки в octane — безопасный дефолт: НЕ транзиент, алертим громко */
        $e = AmoPaymentRequiredException::fromRequestException($this->make402(), 999999);

        $this->assertFalse($e->isSpurious());
    }

    private function make402(): RequestException
    {
        return new RequestException(
            new Response(new Psr7Response(402, [], '{"title":"Payment Required"}'))
        );
    }
}
