<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Psr7\Request as GuzzlePsrRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\AmoClientOctane;
use PHPUnit\Framework\Attributes\DataProvider;

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

        /* источник цепочки маршрутов — amoclient.proxies (порядок = приоритет) */
        Config::set('amoclient.proxies', [self::PROXY_ONE, self::PROXY_TWO, null]);
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
        /* порядок из amoclient.proxies: PROXY_ONE → PROXY_TWO → null (напрямую) */
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO, null], $this->proxiesUsed());
    }

    public function test_5xx_no_longer_rotates_proxy(): void
    {
        $this->fakeAttempts([
            Http::response(['title' => 'Bad Gateway'], 502),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        try {
            (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');
            $this->fail('Ожидали RequestException: 502 больше не повторяется внутри запроса');
        } catch (RequestException $e) {
            $this->assertSame(502, $e->response->status());
        }

        /*
         * Дефект, зафиксированный этим сьютом до 4.0, ИСПРАВЛЕН. Раньше здесь было
         * [PROXY_ONE, PROXY_TWO]: 502 считался поводом сменить маршрут. Но у ответа
         * есть HTTP-статус, значит запрос дошёл до амо, и второй заход через другой
         * роутер уходит в тот же лежащий бэкенд — попытка сжигается впустую.
         * Failover обязан жить НИЖЕ уровня, на котором статус существует
         * (решение по архитектуре 4.0, п.5).
         *
         * Следствие, которое надо понимать: 5xx теперь вылетает из клиента с ПЕРВОЙ
         * попытки. Это не потеря надёжности, а перенос ответственности — повтор
         * делает очередь потребителя (Queue\RetriesTransientAmoErrors), где пауза
         * измеряется минутами и переживает многоминутный сбой, а не миллисекундной
         * задержкой внутри одного запроса.
         */
        $this->assertSame([self::PROXY_ONE], $this->proxiesUsed());
    }

    public function test_read_timeout_does_not_rotate_proxy(): void
    {
        /*
         * Read-таймаут: соединение УСТАНОВЛЕНО и запрос УШЁЛ (request_size > 0),
         * амо молчит. Самый дорогой случай ротации — каждый лишний заход стоит
         * полного `timeout`, поэтому пул прокси умножал простой на свой размер,
         * не давая ни одного шанса на успех: все три захода идут в один бэкенд.
         *
         * Числа в handler-контексте — не выдумка: замер curl 8.22.0, прокси открыл
         * туннель, TLS-сервер за ним принял запрос (50 байт) и молчит. На curl
         * 8.17.0 картина та же: request_size 50, pretransfer_time 0.0041.
         */
        $this->fakeAttempts([
            fn () => throw new ConnectionException(
                'cURL error 28: Operation timed out after 3500 milliseconds with 0 bytes received',
                0,
                new GuzzleConnectException(
                    'cURL error 28: Operation timed out after 3500 milliseconds with 0 bytes received',
                    new GuzzlePsrRequest('GET', 'https://example.amocrm.ru/api/v4/account'),
                    null,
                    [
                        'errno' => 28,
                        'connect_time' => 0.000103,
                        'appconnect_time' => 0.003262,
                        'pretransfer_time' => 0.003292,
                        'starttransfer_time' => 0.0,
                        'total_time' => 3.501031,
                        'request_size' => 50,
                    ],
                ),
            ),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        try {
            (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');
            $this->fail('Ожидали ConnectionException: read-таймаут больше не ротирует прокси');
        } catch (ConnectionException) {
            /* ожидаемо */
        }

        /* Одна попытка, маршрут не менялся. */
        $this->assertSame([self::PROXY_ONE], $this->proxiesUsed());
    }

    public function test_broken_proxy_tunnel_rotates_proxy(): void
    {
        /*
         * ИНЦИДЕНТ 14.08.2026, ~50% ошибок на всех веб-ручках, которые ходят в
         * амо синхронно (например POST /widgets/{id}/unidoc/v1/documents → 500
         * «Unknown error (ConnectionException)»).
         *
         * Прокси-микротик отвечал `HTTP/1.1 200 OK` на CONNECT, а в готовый
         * туннель на Client Hello присылал НЕ TLS → `cURL error 35: wrong
         * version number`. TCP-рукопожатие с прокси при этом состоялось, то есть
         * `connect_time > 0`, и прежний различитель (`connect_time`) считал это
         * «амо принял запрос и молчит» — маршрут не менялся, хотя следующий
         * маршрут отвечал нормально.
         *
         * Различитель обязан ключеваться на то, УШЁЛ ли запрос, — `request_size`:
         * сам CONNECT в счётчик не входит, поэтому здесь ноль. Маршрут непригоден,
         * и повтор по другому маршруту не только имеет шанс, но и безопасен для
         * не-идемпотентных методов. `pretransfer_time` в проде 14.08.2026 здесь
         * был 0.0, но с curl 8.21.0 на отказе он равен общему времени
         * (curl/curl#23325) — на нём 4.0.4 перестала менять маршрут и в этом случае.
         *
         * Числа — воспроизведение на curl 8.22.0: фейковый прокси отвечает `200`
         * на CONNECT и сразу шлёт в туннель не-TLS.
         */
        $this->fakeAttempts([
            fn () => throw new ConnectionException(
                'cURL error 35: TLS connect error: error:0A00010B:SSL routines::wrong version number',
                0,
                new GuzzleConnectException(
                    'cURL error 35: TLS connect error: error:0A00010B:SSL routines::wrong version number',
                    new GuzzlePsrRequest('GET', 'https://example.amocrm.ru/api/v4/account'),
                    null,
                    [
                        'errno' => 35,
                        'connect_time' => 0.000098,
                        'appconnect_time' => 0.0,
                        'pretransfer_time' => 0.000863,
                        'starttransfer_time' => 0.000863,
                        'total_time' => 0.000864,
                        'request_size' => 0,
                    ],
                ),
            ),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO], $this->proxiesUsed());
    }

    public function test_route_chain_ignores_app_proxy_config(): void
    {
        /*
         * app.proxy/app.secondProxy — общий конфиг потребителя для сервисов,
         * которым нужен конкретный exit-IP (в octane это BY-справочники). Амо в
         * таком exit-IP не нуждается, а чтение этих ключей либой означало, что
         * маршрут амо меняется вместе с чужой настройкой и мимо
         * `amoclient.proxies`. Именно так прод оказался целиком на микротике,
         * пока в `amoclient.proxies` стояло «сначала напрямую».
         */
        Config::set('amoclient.proxies', [null]);
        Config::set('app.proxy', self::PROXY_ONE);
        Config::set('app.secondProxy', self::PROXY_TWO);

        $this->fakeAttempts([Http::response(['id' => self::ACCOUNT_ID], 200)]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());
        $this->assertSame([null], $this->proxiesUsed());
    }

    public function test_unreachable_path_still_rotates_proxy(): void
    {
        /*
         * Позитивный контроль к двум тестам выше: различитель обязан РАЗЛИЧАТЬ.
         * Соединиться не удалось вовсе — запрос не ушёл (request_size = 0),
         * это ровно тот случай, ради которого пул прокси и существует, и здесь
         * ротация обязана работать. Без этого теста «не ротирует» проходило бы и
         * при полностью сломанном различителе, который не ротирует никогда.
         *
         * Так и случилось с curl 8.21+: контекст здесь был снят на старом curl
         * (pretransfer_time 0.0), тест оставался зелёным, а в проде смена
         * маршрута не срабатывала вовсе (MASTERM-PUSHKA-BIZ-4H, 24.11.2026).
         * Теперь числа — замер curl 8.22.0: прокси отказывает в соединении.
         */
        $this->fakeAttempts([
            fn () => throw new ConnectionException(
                'cURL error 7: Failed to connect to example.amocrm.ru:443 over proxy proxy-one after 0 ms: Could not connect to server',
                0,
                new GuzzleConnectException(
                    'cURL error 7: Failed to connect to example.amocrm.ru:443 over proxy proxy-one after 0 ms: Could not connect to server',
                    new GuzzlePsrRequest('GET', 'https://example.amocrm.ru/api/v4/account'),
                    null,
                    [
                        'errno' => 7,
                        'connect_time' => 0.0,
                        'appconnect_time' => 0.0,
                        'pretransfer_time' => 0.000101,
                        'starttransfer_time' => 0.000101,
                        'total_time' => 0.000102,
                        'request_size' => 0,
                    ],
                ),
            ),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());
        $this->assertSame([self::PROXY_ONE, self::PROXY_TWO], $this->proxiesUsed());
    }

    /**
     * Connect-таймаут, снятый на двух поколениях curl: SYN в чёрную дыру
     * (192.0.2.1), connect-таймаут 2 с на 8.17.0 и 10 с на 8.22.0.
     *
     * @return array<string, array{0: array<string, float|int>}>
     */
    public static function connectTimeoutContexts(): array
    {
        return [
            /* прежняя база прода (PHP 8.4.21): незаполненные таймеры — нули */
            'curl 8.17.0' => [[
                'errno' => 28,
                'connect_time' => 0.0,
                'appconnect_time' => 0.0,
                'pretransfer_time' => 0.0,
                'starttransfer_time' => 0.0,
                'total_time' => 2.001631,
                'request_size' => 0,
            ]],
            /* база PHP 8.4.27: curl дописал таймеры общим временем (curl/curl#23325) */
            'curl 8.22.0' => [[
                'errno' => 28,
                'connect_time' => 0.0,
                'appconnect_time' => 0.0,
                'pretransfer_time' => 10.00196,
                'starttransfer_time' => 10.00196,
                'total_time' => 10.001961,
                'request_size' => 0,
            ]],
        ];
    }

    /**
     * @param  array<string, float|int>  $context
     */
    #[DataProvider('connectTimeoutContexts')]
    public function test_connect_timeout_rotates_proxy_on_any_curl(array $context): void
    {
        /*
         * ИНЦИДЕНТ masterm 24.11.2026 (MASTERM-PUSHKA-BIZ-4H). Ночной sync задач:
         * прямой маршрут до kuhnimodul.amocrm.ru не соединился за 10 с, и ошибка
         * вылетела с ПЕРВОЙ попытки, хотя офисный прокси в те же секунды ходил
         * на те же адреса амо.
         *
         * Различитель 4.0.4 смотрел на pretransfer_time, а с curl 8.21.0 он на
         * неудачном запросе равен общему времени: connect-таймаут читался как
         * «запрос ушёл, амо молчит», и на curl 8.21+ маршрут не менялся ни на
         * одном сетевом отказе. request_size честен на обоих поколениях: ничего
         * не отправлено — ноль.
         *
         * Цепочка как у masterm: сначала напрямую, затем прокси.
         */
        Config::set('amoclient.proxies', [null, self::PROXY_ONE, self::PROXY_TWO]);
        $message = sprintf('cURL error 28: Connection timed out after %d milliseconds', (int) round($context['total_time'] * 1000));

        $this->fakeAttempts([
            fn () => throw new ConnectionException($message, 0, new GuzzleConnectException(
                $message,
                new GuzzlePsrRequest('GET', 'https://example.amocrm.ru/api/v4/account'),
                null,
                $context,
            )),
            Http::response(['id' => self::ACCOUNT_ID], 200),
        ]);

        $response = (new AmoClientOctane(self::ACCOUNT_ID))->http->get('account');

        $this->assertTrue($response->ok());
        $this->assertSame([null, self::PROXY_ONE], $this->proxiesUsed());
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
