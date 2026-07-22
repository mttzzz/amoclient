<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\AmoClientOctane;

/*
 * CHARACTERIZATION-сьют транспорта: пришпиливает поведение, которое либа имеет
 * СЕГОДНЯ, включая известный дефект (ротация прокси на 5xx). Смысл не в том, что
 * это поведение правильное, а в том, что переписывание 4.0 обязано менять его
 * ОСОЗНАННО: тест краснеет — значит поведение изменилось, и изменение надо
 * объяснить, а не обнаружить в проде.
 *
 * Ключ к наблюдаемости (решение по архитектуре 4.0, п.15): фейк объявляется
 * ЗАМЫКАНИЕМ — только этой форме Laravel передаёт guzzle-опции ($options), где
 * лежит выбранный на попытке proxy. Массив-форма + Http::assertSent() опций не
 * видит (рекордер пишет только пару Request/Response), поэтому существующие
 * тесты ротацию проверить не могли в принципе.
 */
class ProxyRotationCharacterizationTest extends ResilienceTestCase
{
    private const PROXY_ONE = 'http://proxy-one:8080';

    private const PROXY_TWO = 'http://proxy-two:8080';

    /** @var list<array{proxy: string|null, auth: string|null}> */
    private array $attempts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->attempts = [];

        /* реальный источник прокси — app.proxy/app.secondProxy, а НЕ amoclient.proxies */
        Config::set('app.proxy', self::PROXY_ONE);
        Config::set('app.secondProxy', self::PROXY_TWO);
    }

    /**
     * Харнесс наблюдаемости: пишет (proxy, Authorization) каждой попытки.
     *
     * @param  list<mixed>  $responses  ответ на попытку N; последний повторяется
     */
    private function fakeAttempts(array $responses): void
    {
        $index = 0;

        Http::fake(function (Request $request, array $options) use (&$index, $responses) {
            $proxy = $options['proxy'] ?? null;
            $auth = $request->header('Authorization')[0] ?? null;

            /*
             * Оба значения приходят из недр guzzle нетипизированными. Сужаем явно,
             * а не обещаем тип на слово: харнесс существует ради утверждений о том,
             * ЧТО именно ушло на попытке, и молча принятый не-string тут означал бы
             * ассерт, сравнивающий null с null и всегда зелёный.
             */
            $this->attempts[] = [
                'proxy' => is_string($proxy) ? $proxy : null,
                'auth' => is_string($auth) ? $auth : null,
            ];

            $response = $responses[$index] ?? $responses[count($responses) - 1];
            $index++;

            return $response;
        });
    }

    /**
     * @return list<string|null>
     */
    private function proxiesUsed(): array
    {
        return array_map(fn (array $a) => $a['proxy'], $this->attempts);
    }

    public function test_connection_error_rotates_proxy_in_priority_order(): void
    {
        $this->fakeAttempts([
            Http::failedConnection(),
            Http::failedConnection(),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());
        /* app.proxy → app.secondProxy → null (прямое соединение последним) */
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO, null], $this->proxiesUsed());
    }

    public function test_5xx_also_rotates_proxy_today(): void
    {
        $this->fakeAttempts([
            Http::response(['title' => 'Bad Gateway'], 502),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());

        /*
         * ЗАФИКСИРОВАННЫЙ ДЕФЕКТ: 502 — это лежащий бэкенд амо, а не битый прокси;
         * смена прокси бьёт в тот же труп и сжигает попытку. В 4.0 ожидается
         * [PROXY_ONE, PROXY_ONE] — когда тест покраснеет здесь, это ПЛАНОВОЕ
         * изменение (решение по архитектуре 4.0, п.5: failover ниже уровня,
         * на котором существует HTTP-статус).
         */
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO], $this->proxiesUsed());
    }

    public function test_401_retries_with_fresh_token_without_rotating_proxy(): void
    {
        $this->fakeAttempts([
            Http::response(['detail' => 'Unauthorized'], 401),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $amo = new AmoClientOctane(self::ACCOUNT_ID);

        /* гонка ночной ротации: октан записал новый токен после конструирования клиента */
        DB::connection('octane')->table('account_widget')
            ->where('account_id', self::ACCOUNT_ID)
            ->update(['access_token' => 'new-token']);

        $response = $amo->http->get('account');

        $this->assertTrue($response->ok());
        $this->assertSame(['Bearer old-token', 'Bearer new-token'], array_map(
            fn (array $a) => $a['auth'],
            $this->attempts
        ));
        /* auth-проблема прокси не касается — ротации быть не должно */
        $this->assertSame([self::PROXY_ONE, self::PROXY_ONE], $this->proxiesUsed());
    }

    public function test_429_is_not_retried_today(): void
    {
        $this->fakeAttempts([Http::response(['title' => 'Too Many Requests'], 429)]);

        try {
            (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');
            $this->fail('Ожидали RequestException 429');
        } catch (RequestException $e) {
            $this->assertSame(429, $e->response->status());
        }

        /*
         * ЗАФИКСИРОВАННЫЙ ПРОБЕЛ: 429 не распознаётся вовсе — ни ретрая,
         * ни уважения Retry-After. В 4.0 ожидается консервативный бэкофф
         * БЕЗ ротации прокси (эскалация 429→403 блокирует весь аккаунт).
         */
        $this->assertCount(1, $this->attempts);
    }

    public function test_declared_retry_budget_is_dead_effective_attempts_equal_proxy_count(): void
    {
        $this->fakeAttempts([Http::failedConnection()]);

        try {
            (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');
            $this->fail('Ожидали ConnectionException после исчерпания попыток');
        } catch (ConnectionException) {
            /* ожидаемо */
        }

        /*
         * ЗАФИКСИРОВАННЫЙ ДЕФЕКТ (найден этим сьютом, в инвентарях его нет):
         * Laravel'у объявлен бюджет retries(2) × proxies(3) = 6 попыток, но
         * фактических попыток ТРИ. Причина: ретрай и ротация — одно и то же
         * решение колбэка. Как только пул прокси исчерпан
         * (`$proxyIndex < $maxProxyAttempts - 1` перестаёт выполняться,
         * AmoClientOctane.php:216), колбэк возвращает false, и повторов больше
         * нет вообще. То есть `amoclient.retries` за пределами размера пула
         * не влияет ни на что: транзиентный сетевой сбой получает ровно по
         * одной попытке на прокси, а не по две.
         *
         * Для 4.0 это означает: бюджет попыток и выбор маршрута обязаны быть
         * РАЗНЫМИ решениями (решение по архитектуре 4.0, п.5 и п.6) — иначе
         * конфиг обещает то, чего не делает.
         */
        $this->assertCount(3, $this->attempts);
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO, null], $this->proxiesUsed());
    }
}
