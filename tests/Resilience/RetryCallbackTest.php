<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Exceptions\AmoPaymentRequiredException;

class RetryCallbackTest extends ResilienceTestCase
{
    public function test_401_with_rotated_token_retries_with_fresh_token(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['detail' => 'Unauthorized'], 401)
                ->push(['id' => self::ACCOUNT_ID], 200),
        ]);

        $amo = new AmoClientOctane(self::ACCOUNT_ID);

        /* Гонка ротации: октан записал новый токен ПОСЛЕ конструирования клиента */
        DB::connection('octane')->table('account_widget')
            ->where('account_id', self::ACCOUNT_ID)
            ->update(['access_token' => 'new-token']);

        $response = $amo->http->get('account');

        $this->assertTrue($response->ok());
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer new-token'));
    }

    public function test_401_without_rotation_does_not_retry(): void
    {
        Http::fake(['*' => Http::response(['detail' => 'Unauthorized'], 401)]);

        $amo = new AmoClientOctane(self::ACCOUNT_ID);

        try {
            $amo->http->get('account');
            $this->fail('Ожидали RequestException 401');
        } catch (RequestException $e) {
            $this->assertSame(401, $e->response->status());
        }

        /* токен в БД не менялся → реальная auth-проблема, повторов нет */
        Http::assertSentCount(1);
    }

    public function test_402_throws_typed_payment_exception_immediately(): void
    {
        Http::fake(['*' => Http::response(['title' => 'Payment Required'], 402)]);

        $amo = new AmoClientOctane(self::ACCOUNT_ID);

        try {
            $amo->http->get('account');
            $this->fail('Ожидали AmoPaymentRequiredException');
        } catch (AmoPaymentRequiredException $e) {
            $this->assertSame(402, $e->getCode());
            $this->assertSame(self::ACCOUNT_ID, $e->accountId);
            $this->assertTrue($e->isSpurious()); /* payed=true в фикстуре */
        }

        /* 402 не ретраим внутри запроса — окна живут минуты, это забота очереди */
        Http::assertSentCount(1);
    }
}
