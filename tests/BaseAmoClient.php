<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Facades\Config;
use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Tests\Support\TestEntityRegistry;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Throwable;

abstract class BaseAmoClient extends TestCase
{
    protected AmoClientOctane $amoClient;

    /*
     * Порядок сноса: дети раньше родителей.
     *
     * Задача/примечание/звонок живут на сделке-контакте-компании, элемент — на
     * каталоге. Снос родителя уносит детей с собой, и тогда попытка удалить
     * ребёнка приходит на уже несуществующий объект: amo отвечает на это не
     * «удалено» и не честной 404, а неоднозначным fail'ом (по §7.4 ресёрча
     * текст ошибки там прямо дезинформирует). Мы бы не смогли отличить
     * «ушёл каскадом» от «не удалился» — то есть потеряли бы сигнал ровно там,
     * ради которого весь teardown и делается. Поэтому детей сносим первыми,
     * пока их родители ещё живы и ответ однозначен.
     *
     * Внутри одного ранга порядок — как трекали (usort в PHP 8 стабилен).
     */
    private const TEARDOWN_ORDER = [
        /* дети сущностей */
        'tasks',
        'notes',
        'calls',
        'catalogElements',
        /* сами сущности */
        'leads',
        'contacts',
        'companies',
        'customers',
        /* контейнеры: каталог уносит элементы, воронка — сделки */
        'catalogs',
        'pipelines',
        /* не связаны ни с чем из перечисленного */
        'webhooks',
        'sources',
    ];

    /*
     * Ответы amo, означающие «сущности уже нет», а не «удалить не вышло».
     *
     * Текст про права здесь дезинформирует: по §7.4 ресёрча (проверено на
     * заведомо свежеудалённом лиде под admin-правами) amo отдаёт именно его на
     * попытку удалить сделку, УЖЕ лежащую в корзине. Трактовать это как ошибку
     * нельзя — иначе идемпотентный снос (shutdown-хук после tearDownAfterClass,
     * повторный прогон свипа) начнёт ложно шуметь и прятать настоящие хвосты.
     */
    private const ALREADY_GONE_NEEDLES = [
        'Недостаточно прав для удаления сделки',
    ];

    /*
     * Реестр и клиент — статические, и это не удобство, а требование двух
     * механик сразу:
     *
     * 1. #[Depends]. PHPUnit создаёт НОВЫЙ инстанс TestCase на каждый
     *    тест-метод, поэтому инстанс-свойство жило бы ровно один тест. А
     *    цепочки в сьютах тянут созданный id через несколько методов
     *    (LeadTest: create → update/custom_fields/query → delete). Снос после
     *    каждого теста убил бы сущность между звеньями — ровно ту, ради которой
     *    track() и возвращает $id. Реестр общий на прогон, снос — в
     *    tearDownAfterClass().
     * 2. Фаталы. register_shutdown_function отрабатывает, когда живого инстанса
     *    теста уже нет; сносить он может только через статику.
     */
    private static ?TestEntityRegistry $registry = null;

    private static ?AmoClientOctane $sweepClient = null;

    private static bool $shutdownHookRegistered = false;

    /**
     * @param  array<mixed>  $response
     */
    protected function assertCustomerDeleteAccepted(array $response): void
    {
        $this->assertIsArray($response);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('customers', $response['response']);
        $this->assertArrayHasKey('delete', $response['response']['customers']);
        $this->assertArrayHasKey('errors', $response['response']['customers']['delete']);

        foreach ($response['response']['customers']['delete']['errors'] as $error) {
            $this->assertSame(404, $error['code']);
            $this->assertSame('Error 282.', $error['message']);
        }
    }

    protected function skipIfCustomersUnavailable(AmoCustomException $e): void
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Customers disabled') || str_contains($message, 'Error 426.')) {
            $this->markTestSkipped('Customers API is unavailable for the current account configuration.');
        }
    }

    protected function skipIfUnsupportedAmoResponse(AmoCustomException $e, array $needles, string $reason): void
    {
        foreach ($needles as $needle) {
            if (str_contains($e->getMessage(), $needle)) {
                $this->markTestSkipped($reason);
            }
        }
    }

    /**
     * Регистрирует созданную в amo сущность на авто-снос и ВОЗВРАЩАЕТ её id.
     *
     * Возврат — не косметика: он позволяет отдать id дальше по цепочке
     * #[Depends] одной строкой (`return $this->track('leads', $created['id']);`),
     * не заводя временную переменную и не оставляя ветку, где сущность создана,
     * но не затрекана.
     */
    protected function track(string $type, int $id): int
    {
        self::registry()->track($type, $id);

        return $id;
    }

    protected static function registry(): TestEntityRegistry
    {
        return self::$registry ??= new TestEntityRegistry;
    }

    /**
     * Единственная точка сноса: её зовут tearDownAfterClass(), shutdown-хук на
     * фаталах и тест-доказательство в BaseAmoClientTest.
     *
     * Ошибка удаления не валит тест — иначе один нештатный ответ amo обрушил бы
     * весь сьют, — но обязана быть громкой в STDERR с типом и id: молча
     * потерянный хвост в боевом аккаунте хуже шумного прогона. Неудалённое
     * намеренно ОСТАЁТСЯ в реестре: следующий tearDownAfterClass и shutdown-хук
     * дадут ему ещё попытку, а если не выйдет и там — id уже напечатан, по нему
     * добивают руками или свипом.
     */
    protected static function sweepTrackedEntities(): void
    {
        $registry = self::$registry;
        $client = self::$sweepClient;

        if ($registry === null || $client === null) {
            return;
        }

        foreach (self::inTeardownOrder($registry->all()) as $entry) {
            try {
                self::deleteTrackedEntity($client, $entry['type'], $entry['id']);
                $registry->forget($entry['type'], $entry['id']);
            } catch (Throwable $e) {
                if (self::isAlreadyGone($e)) {
                    $registry->forget($entry['type'], $entry['id']);

                    continue;
                }

                fwrite(STDERR, sprintf(
                    "\n[teardown] ХВОСТ В AMO: %s id=%d НЕ УДАЛЁН — %s: %s\n",
                    $entry['type'],
                    $entry['id'],
                    $e::class,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Снос одной сущности МЕТОДАМИ БИБЛИОТЕКИ (решение владельца №1: удаление —
     * штатная поверхность либы, а не сырой ajax в тестах; две реализации одной
     * логики неизбежно разъезжаются).
     *
     * TODO(teardown-core): тело ждёт контракт удаления от тиммейта lib-delete —
     * это единственная контракт-зависимая точка файла. До подключения снос
     * честно кричит в STDERR по каждой затреканной сущности, а не делает вид,
     * что прибрал.
     */
    private static function deleteTrackedEntity(AmoClientOctane $client, string $type, int $id): void
    {
        throw new RuntimeException(
            "контракт удаления lib-delete ещё не подключён — тип '$type' не снесён"
        );
    }

    /**
     * @param  array<int, array{type: string, id: int}>  $entries
     * @return array<int, array{type: string, id: int}>
     */
    private static function inTeardownOrder(array $entries): array
    {
        usort(
            $entries,
            static fn (array $a, array $b): int => self::teardownRank($a['type']) <=> self::teardownRank($b['type'])
        );

        return $entries;
    }

    private static function teardownRank(string $type): int
    {
        $rank = array_search($type, self::TEARDOWN_ORDER, true);

        /* Неизвестный тип — в самый хвост: он либо новый (тогда его место
         * после всего известного — консервативно, как у родителя), либо
         * опечатка в вызове track() — и тогда провалившаяся попытка удаления
         * сама назовёт его в STDERR. */
        return $rank === false ? count(self::TEARDOWN_ORDER) : $rank;
    }

    private static function isAlreadyGone(Throwable $e): bool
    {
        /* AmoCustomException кладёт HTTP-статус в code: 404 — сущности нет,
         * значит цель уборки достигнута, а не провалена. */
        if ((int) $e->getCode() === 404) {
            return true;
        }

        foreach (self::ALREADY_GONE_NEEDLES as $needle) {
            if (str_contains($e->getMessage(), $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Настроить конфигурацию
        Config::set('amoclient.proxies', [null]);
        Config::set('amoclient.verify', false);
        Config::set('amoclient.timeout', 60);
        Config::set('amoclient.connectTimeout', 10);
        Config::set('amoclient.retries', 2);
        Config::set('amoclient.retryDelay', 1000);

        // Создать экземпляр AmoClientOctane
        $aId = 16117840;
        $clientId = '00a140c1-7c52-4563-8b36-03f23754d255';
        $this->amoClient = new AmoClientOctane($aId, $clientId);

        self::registry();
        /* Клиент переживает инстанс теста: им сносит и shutdown-хук. */
        self::$sweepClient = $this->amoClient;

        if (! self::$shutdownHookRegistered) {
            self::$shutdownHookRegistered = true;

            /* Фатал (OOM, segfault, exit) обходит и tearDown, и
             * tearDownAfterClass — shutdown-функция единственная, кто ещё
             * выполнится. Идемпотентность даёт forget() при успехе и
             * isAlreadyGone() при повторе, так что двойной снос не выглядит
             * провалом.
             *
             * Известная граница: после НОРМАЛЬНОГО прогона хук отрабатывает уже
             * по разобранному приложению Testbench, и запрос через фасады может
             * упасть сам. Это не потеря: в норме реестр к этому моменту пуст
             * (снёс tearDownAfterClass), а если в нём что-то осталось — падение
             * поймано и id напечатан в STDERR. */
            register_shutdown_function(static function (): void {
                self::sweepTrackedEntities();
            });
        }
    }

    public static function tearDownAfterClass(): void
    {
        /* Снос на границе класса, а не теста: см. комментарий к $registry —
         * per-test снос ломал бы #[Depends]-цепочки. */
        self::sweepTrackedEntities();

        parent::tearDownAfterClass();
    }

    protected function getEnvironmentSetUp($app)
    {
        /* Тесты интеграционные: токен аккамунта 16117840 и account_amo_user
         * читаются из локальной Postgres-копии прод-БД octane_pushka_biz
         * (pgsync sync). Прежний MySQL root@3306 был мёртвым хвостом. */
        $app['config']->set('database.default', 'octane');
        $app['config']->set('database.connections.octane', [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'octane_pushka_biz',
            'username' => 'mttzzzz',
            'password' => '',
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);
    }
}
