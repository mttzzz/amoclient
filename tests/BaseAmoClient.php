<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Facades\Config;
use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Tests\Support\AmoTestSweeper;
use mttzzz\AmoClient\Tests\Support\TestEntityRegistry;
use Orchestra\Testbench\TestCase;
use Throwable;

abstract class BaseAmoClient extends TestCase
{
    protected AmoClientOctane $amoClient;

    /* Боевой аккаунт под тесты — один на всю команду. Константами, а не
     * литералами в setUp: тем же аккаунтом строится клиент для сноса, когда
     * инстанса теста уже нет (shutdown-хук), и разъехавшиеся значения означали
     * бы уборку не в том аккаунте. */
    protected const ACCOUNT_ID = 16117840;

    protected const CLIENT_ID = '00a140c1-7c52-4563-8b36-03f23754d255';

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
     * Знание «какой ответ amo означает „уже нет“» здесь НЕ живёт и заводиться
     * не должно: оно принадлежит операции удаления (src/Deleter.php). Teardown
     * не разбирает ни текстов, ни кодов — он читает bool контракта
     * deleter->byType() (true — подтверждено, false — известная «уже нет»-причина,
     * исключение — реальная ошибка). Список формулировок амо, продублированный
     * здесь, обязан был бы оставаться синхронным с Deleter — и разъехался бы на
     * первой же смене текста в амо, после чего уборка молча считала бы
     * неудалённое удалённым.
     */

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

    /* Клиент для сноса строится ЛЕНИВО и один раз на прогон (постройка = запрос
     * в octane за токеном, платить им на каждый класс незачем). Лениво, а не из
     * $this->amoClient: tearDownAfterClass() и shutdown-хук статические, живого
     * инстанса теста в них нет. */
    private static ?AmoClientOctane $sweepClient = null;

    private static bool $shutdownHookRegistered = false;

    /**
     * Счётчик попыток сноса на сущность: реестр общий на прогон, значит
     * неудавшийся снос доживёт до следующего класса и будет повторён. Без
     * счётчика каждый повтор читался бы как новый провал, а их число — как
     * число хвостов.
     *
     * @var array<string, int>
     */
    private static array $sweepAttempts = [];

    /**
     * @param  array<mixed>  $response
     */
    protected function assertCustomerDeleteAccepted(array $response): void
    {
        $errors = $this->amoResponsePath($response, ['response', 'customers', 'delete', 'errors']);

        foreach ($errors as $error) {
            if (! is_array($error)) {
                $this->fail('ответ amo: элемент errors[] ожидался массивом, пришло '.get_debug_type($error));
            }

            $this->assertSame(404, $error['code'] ?? null);
            $this->assertSame('Error 282.', $error['message'] ?? null);
        }
    }

    /**
     * Спуск по вложенному ключу сырого ответа amo с проваливанием теста на
     * первом же уровне, которого нет или который не массив.
     *
     * Нужен потому, что ответ amo для анализатора — `mixed` на каждом шаге, а
     * `assertArrayHasKey()` тип не сужает: расширения `phpstan/phpstan-phpunit`
     * в проекте нет. Гард здесь не косметика — он превращает «amo прислал
     * другую форму» из непроверяемого обращения к ключу в понятный провал с
     * названным уровнем.
     *
     * @param  array<mixed>  $payload
     * @param  list<string>  $path
     * @return array<mixed>
     */
    private function amoResponsePath(array $payload, array $path): array
    {
        $cursor = $payload;

        foreach ($path as $key) {
            $this->assertArrayHasKey($key, $cursor);

            $next = $cursor[$key];

            if (! is_array($next)) {
                /* Скобки обязательны: «»» после $key — байт ≥ 0x80, PHP
                 * считает его частью имени переменной. */
                $this->fail("ответ amo: по ключу «{$key}» ожидался массив, пришло ".get_debug_type($next));
            }

            $cursor = $next;
        }

        return $cursor;
    }

    protected function skipIfCustomersUnavailable(AmoCustomException $e): void
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Customers disabled') || str_contains($message, 'Error 426.')) {
            $this->markTestSkipped('Customers API is unavailable for the current account configuration.');
        }
    }

    /**
     * @param  list<string>  $needles
     */
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
    protected function track(string $type, int|string $id): int|string
    {
        self::registry()->track($type, $id);

        return $id;
    }

    protected static function registry(): TestEntityRegistry
    {
        return self::$registry ??= new TestEntityRegistry;
    }

    /**
     * Дописывает к значению маркер, по которому финальный свип опознаёт наш
     * мусор в боевом аккаунте.
     *
     * Маркер обязан физически лежать в данных сущности (name / text /
     * params.text / destination) — свип не имеет права трогать то, в чьём
     * payload его нет. Хардкодить строку по тест-файлам нельзя: разойдётся на
     * символ — и свип начнёт находить две трети хвостов МОЛЧА, потому что
     * «ничего не найдено» неотличимо от «всё чисто».
     *
     * Дописывает, а не заменяет: тесты сравнивают отправленное имя с
     * полученным, суффикс такие ассерты переживает. Идемпотентно — повторный
     * вызов не клеит маркер дважды.
     */
    protected function marked(string $value): string
    {
        if (str_contains($value, AmoTestSweeper::TEST_MARKER)) {
            return $value;
        }

        if ($value === '') {
            return AmoTestSweeper::TEST_MARKER;
        }

        /* URL (destination вебхука) обязан остаться валидным URL — пробел с
         * суффиксом его ломает, amo такую подписку не примет. Вешаем маркер
         * query-параметром: строка в payload есть, адрес цел. */
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value.(str_contains($value, '?') ? '&' : '?').AmoTestSweeper::TEST_MARKER;
        }

        return $value.' '.AmoTestSweeper::TEST_MARKER;
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

        if ($registry === null || $registry->all() === []) {
            return;
        }

        $client = self::sweepClient();

        if ($client === null) {
            return;
        }

        foreach (self::groupedInTeardownOrder($registry->all()) as $group) {
            $type = $group['type'];

            try {
                /* Пакетом на тип: боевой аккаунт один на всю команду, и лишние
                 * запросы — прямая дорога к 429 → 403. Раскладку на пакетные и
                 * поштучные эндпойнты делает Deleter, это его знание. */
                $confirmed = $client->deleter->byType($type, $group['ids']);

                foreach ($group['ids'] as $id) {
                    $registry->forget($type, $id);
                    self::reportSwept($type, $id, $confirmed);
                }
            } catch (Throwable $e) {
                self::sweepOneByOne($client, $registry, $type, $group['ids'], $e);
            }
        }
    }

    /**
     * Разбор пакета, упавшего целиком.
     *
     * Пакетный ответ не говорит, какие id прошли, а какие нет, — а требование
     * ровно обратное: хвост должен быть назван поимённо. Переспрашиваем
     * поштучно; повторный снос безопасен, потому что уже удалённое по контракту
     * Deleter даёт false, а не исключение.
     *
     * @param  list<int|string>  $ids
     */
    private static function sweepOneByOne(
        AmoClientOctane $client,
        TestEntityRegistry $registry,
        string $type,
        array $ids,
        Throwable $batchError
    ): void {
        if (count($ids) === 1) {
            /* Пакет из одного — переспрашивать нечего, это был тот же вызов. */
            self::reportFailure($type, $ids[0], $batchError);

            return;
        }

        foreach ($ids as $id) {
            try {
                $confirmed = $client->deleter->byType($type, $id);
                $registry->forget($type, $id);
                self::reportSwept($type, $id, $confirmed);
            } catch (Throwable $e) {
                self::reportFailure($type, $id, $e);
            }
        }
    }

    /**
     * Успешный снос молчит — кроме двух случаев, когда молчание врёт.
     *
     * $confirmed === false значит: amo не подтвердил удаление, но назвал
     * известную «уже нет»-причину (её распознаёт Deleter, не мы). Цель уборки
     * достигнута, запись забывается — но неоднозначность видна, иначе разница
     * между «снесли» и «его и так не было» пропадёт бесследно.
     */
    private static function reportSwept(string $type, int|string $id, bool $confirmed): void
    {
        $key = $type.':'.$id;
        $attempts = self::$sweepAttempts[$key] ?? 0;
        unset(self::$sweepAttempts[$key]);

        if (! $confirmed) {
            fwrite(STDERR, sprintf(
                "\n[teardown] %s id=%s — снос неоднозначен: amo не подтвердил удаление, но отказал по известной «уже нет»-причине\n",
                $type,
                (string) $id
            ));

            return;
        }

        if ($attempts > 0) {
            fwrite(STDERR, sprintf(
                "\n[teardown] %s id=%s снесён с попытки %d\n",
                $type,
                (string) $id,
                $attempts + 1
            ));
        }
    }

    /**
     * Запись НЕ забывается: следующий tearDownAfterClass и shutdown-хук дадут
     * ей ещё попытку. Повтор помечен явно — реестр общий на прогон, и та же
     * сущность будет пробоваться в каждом следующем классе; без пометки десять
     * строк про один хвост читались бы как десять хвостов.
     */
    private static function reportFailure(string $type, int|string $id, Throwable $e): void
    {
        $key = $type.':'.$id;
        $attempt = self::$sweepAttempts[$key] = (self::$sweepAttempts[$key] ?? 0) + 1;

        fwrite(STDERR, sprintf(
            "\n[teardown] %s: %s id=%s НЕ УДАЛЁН (попытка %d) — %s: %s\n",
            $attempt === 1 ? 'ХВОСТ В AMO' : 'ПОВТОР',
            $type,
            (string) $id,
            $attempt,
            $e::class,
            $e->getMessage()
        ));
    }

    /**
     * Клиент для сноса: лениво, один раз на прогон.
     *
     * Постройка может упасть сама (например, shutdown-хук после нормального
     * прогона работает уже по разобранному приложению Testbench — фасады DB и
     * Config мертвы). Это не повод молчать: реестр в этот момент непуст, значит
     * в боевом аккаунте лежит хвост, и его id надо назвать.
     */
    private static function sweepClient(): ?AmoClientOctane
    {
        if (self::$sweepClient instanceof AmoClientOctane) {
            return self::$sweepClient;
        }

        try {
            return self::$sweepClient = new AmoClientOctane(self::ACCOUNT_ID, self::CLIENT_ID);
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf(
                "\n[teardown] КЛИЕНТ НЕ СОБРАН, уборка не выполнена — %s: %s\n",
                $e::class,
                $e->getMessage()
            ));

            foreach (self::registry()->all() as $entry) {
                fwrite(STDERR, sprintf(
                    "[teardown] ХВОСТ В AMO: %s id=%s\n",
                    $entry['type'],
                    (string) $entry['id']
                ));
            }

            return null;
        }
    }

    /**
     * Сгруппированные по типу id в порядке сноса: один вызов deleter на тип.
     *
     * @param  array<int, array{type: string, id: int|string}>  $entries
     * @return list<array{type: string, ids: list<int|string>}>
     */
    private static function groupedInTeardownOrder(array $entries): array
    {
        $grouped = [];

        foreach (self::inTeardownOrder($entries) as $entry) {
            $grouped[$entry['type']][] = $entry['id'];
        }

        $groups = [];

        foreach ($grouped as $type => $ids) {
            $groups[] = ['type' => (string) $type, 'ids' => $ids];
        }

        return $groups;
    }

    /**
     * @param  array<int, array{type: string, id: int|string}>  $entries
     * @return array<int, array{type: string, id: int|string}>
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
        $this->amoClient = new AmoClientOctane(self::ACCOUNT_ID, self::CLIENT_ID);

        if (! self::$shutdownHookRegistered) {
            self::$shutdownHookRegistered = true;

            /* Фатал (OOM, segfault, exit) обходит и tearDown, и
             * tearDownAfterClass — shutdown-функция единственная, кто ещё
             * выполнится. Идемпотентность держится на forget() при успехе и на
             * контракте Deleter (уже удалённое даёт false, а не исключение),
             * так что двойной снос не выглядит провалом.
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
