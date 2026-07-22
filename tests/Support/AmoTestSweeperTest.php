<?php

namespace mttzzz\AmoClient\Tests\Support;

use Illuminate\Http\Client\PendingRequest;
use LogicException;
use mttzzz\AmoClient\Ajax;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Тесты гарда свипа. Сети, БД и amo здесь нет вовсе — это чистая логика,
 * поэтому сьют гоняется в CI (`composer test:offline`).
 *
 * Почему они обязаны существовать: гард — единственное, что отделяет уборку
 * тестового мусора от сноса клиентских данных в боевом аккаунте 16117840.
 * Сломать его правкой на одну строку легко, а обнаружить поломку без этих
 * тестов можно будет только по факту — на боевых данных.
 */
class AmoTestSweeperTest extends TestCase
{
    /**
     * Живой Deleter — источник истины по типам. Собирается без приложения:
     * ни Ajax, ни PendingRequest контейнера не требуют, а запросов конструктор
     * не делает.
     */
    private static function deleter(): Deleter
    {
        $account = new OctaneAccount;
        $account->subdomain = 'test';
        $account->domain = 'ru';

        $http = new PendingRequest;

        return new Deleter(new Ajax($account, $http), $http);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, bool}>
     */
    public static function markerCases(): iterable
    {
        $marker = AmoTestSweeper::TEST_MARKER;

        yield 'маркер в name' => ['leads', ['id' => 1, 'name' => "lead {$marker}"], true];
        yield 'чужой лид' => ['leads', ['id' => 1, 'name' => 'Реальная сделка клиента'], false];

        /* Ровно тот случай, ради которого маркер сделан нонсом: человеческое
         * «тест» живёт в настоящих клиентских данных и маркером быть не может. */
        yield 'человеческое «тест» — не маркер' => ['leads', ['id' => 1, 'name' => 'Тест кухни'], false];

        /* Гард смотрит в поле-носитель, а не по всему payload: помеченное
         * примечание на чужой сделке не делает саму сделку нашей. */
        yield 'маркер в окружении, а не в поле-носителе' => [
            'leads',
            ['id' => 1, 'name' => 'Сделка клиента', '_embedded' => ['notes' => [['text' => $marker]]]],
            false,
        ];

        yield 'регистр важен' => ['leads', ['id' => 1, 'name' => strtoupper($marker)], false];
        yield 'у задачи носитель text' => ['tasks', ['id' => 2, 'text' => "todo {$marker}"], true];
        yield 'у задачи носитель не name' => ['tasks', ['id' => 2, 'name' => $marker], false];
        yield 'примечание помечено params.text' => ['notes', ['id' => 3, 'params' => ['text' => $marker]], true];
        yield 'чужое примечание' => ['notes', ['id' => 3, 'params' => ['text' => 'звонил клиент']], false];

        /* Звонок — примечание особого типа, носитель тот же params. */
        yield 'звонок помечен params.source' => [
            'calls',
            ['id' => 4, 'params' => ['source' => $marker, 'phone' => '375296117699']],
            true,
        ];

        yield 'вебхук помечен destination' => ['webhooks', ['id' => 5, 'destination' => "https://example.com/{$marker}"], true];
        yield 'чужой вебхук' => ['webhooks', ['id' => 5, 'destination' => 'https://client.example/hook'], false];
        yield 'воронка помечена name' => ['pipelines', ['id' => 6, 'name' => "воронка {$marker}"], true];
        yield 'боевая воронка' => ['pipelines', ['id' => 6, 'name' => 'Продажи'], false];

        /* Тип без поля-носителя пометить нечем — значит и сносить нечего. */
        yield 'тип вне таблицы' => ['shortLinks', ['id' => 7, 'name' => $marker], false];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('markerCases')]
    public function test_marker_is_recognised_only_in_the_carrier_field(string $type, array $payload, bool $expected): void
    {
        $this->assertSame($expected, SweepTarget::isMarked($type, $payload));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, int|string|null}>
     */
    public static function addressingCases(): iterable
    {
        $marker = AmoTestSweeper::TEST_MARKER;

        yield 'помеченный лид адресуется id' => ['leads', ['id' => 42, 'name' => $marker], 42];
        yield 'непомеченное цели не даёт' => ['leads', ['id' => 42, 'name' => 'чужой'], null];
        yield 'помечено, но без id' => ['leads', ['name' => $marker], null];
        yield 'id=0 не адрес' => ['leads', ['id' => 0, 'name' => $marker], null];

        /* Вебхук — единственный тип, который роут удаления адресует не id. */
        yield 'вебхук адресуется destination' => [
            'webhooks',
            ['id' => 5, 'destination' => "https://example.com/{$marker}"],
            "https://example.com/{$marker}",
        ];
        yield 'вебхук с пустым destination неадресуем' => ['webhooks', ['id' => 5, 'destination' => ''], null];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('addressingCases')]
    public function test_target_carries_the_handle_deleter_expects(string $type, array $payload, int|string|null $expected): void
    {
        $target = SweepTarget::fromMarked($type, $payload);

        $this->assertSame($expected, $target?->handle);
    }

    /**
     * Несущее свойство гарда: цель нельзя собрать в обход фабрики. Если
     * конструктор когда-нибудь откроют, «снести лишнее» снова станет обычной
     * ошибкой в коде, а не невозможной операцией.
     */
    public function test_target_cannot_be_constructed_around_the_factory(): void
    {
        $constructor = (new ReflectionClass(SweepTarget::class))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Дыра в покрытии не роняет свип и не видна в отчёте: тип просто перестаёт
     * искаться, а «удалено 0» выглядит как успех. Так однажды выпали pipelines.
     */
    public function test_sweep_covers_every_type_of_the_delete_contract(): void
    {
        $contract = self::deleter()->types();
        $covered = AmoTestSweeper::coveredTypes();
        $withMarkerField = SweepTarget::knownTypes();

        sort($contract);
        sort($covered);
        sort($withMarkerField);

        /* Источник истины — сама либа, а не список, переписанный в тест:
         * иначе сверка сравнивала бы копию с копией. */
        $this->assertSame($contract, $covered, 'свип не ищет тип, который либа умеет удалять');
        $this->assertSame($contract, $withMarkerField, 'у типа контракта нет поля-носителя маркера');
    }

    public function test_semantics_and_marker_fields_agree(): void
    {
        $withSemantics = AmoTestSweeper::coveredTypes();
        $withMarkerField = SweepTarget::knownTypes();

        sort($withSemantics);
        sort($withMarkerField);

        $this->assertSame($withSemantics, $withMarkerField);
    }

    /**
     * Неизвестный тип обязан быть громким: молчаливое «ну попробуем» означало
     * бы непроверенный вызов удаления по боевому аккаунту.
     */
    public function test_unknown_type_is_refused_loudly(): void
    {
        $semanticFor = new ReflectionMethod(AmoTestSweeper::class, 'semanticFor');

        $this->expectException(LogicException::class);

        $semanticFor->invoke(new AmoTestSweeper, 'shortLinks');
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, bool|null}>
     */
    public static function orderCases(): iterable
    {
        yield 'выдача от свежего к старому' => [
            [['id' => 2, 'updated_at' => 200], ['id' => 1, 'updated_at' => 100]],
            true,
        ];
        yield 'выдача по возрастанию — сортировка не применилась' => [
            [['id' => 1, 'updated_at' => 100], ['id' => 2, 'updated_at' => 200]],
            false,
        ];
        yield 'одинаковые метки — не возрастание' => [
            [['id' => 1, 'updated_at' => 100], ['id' => 2, 'updated_at' => 100]],
            true,
        ];
        yield 'одна строка — судить не по чему' => [[['id' => 1, 'updated_at' => 100]], null];
        yield 'пустая страница' => [[], null];
        yield 'нет updated_at' => [[['id' => 1], ['id' => 2]], null];
    }

    /**
     * amo отвечает 200 на любое направление сортировки и молча сортирует по
     * умолчанию (§8.7). Значит проверить, что desc применился, можно только по
     * данным: если первая запись страницы старше последней, выдача идёт по
     * возрастанию — и наши свежие хвосты лежат за потолком страниц. Гард обязан
     * стоять там, где последствие, а не там, где формируется запрос.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    #[DataProvider('orderCases')]
    public function test_ascending_order_is_detected_from_the_data(array $rows, ?bool $expected): void
    {
        $looksDescending = new ReflectionMethod(AmoTestSweeper::class, 'looksDescending');

        $this->assertSame($expected, $looksDescending->invoke(null, $rows));
    }

    /**
     * amo отдаёт на 500 не JSON, а полноценную HTML-страницу, и она приезжает
     * внутрь сообщения исключения. Без чистки отчёт об уборке превращается в
     * вывалившуюся вёрстку, за которой не видно ни одной настоящей причины.
     */
    public function test_html_error_page_is_collapsed_into_one_readable_line(): void
    {
        $reason = new ReflectionMethod(AmoTestSweeper::class, 'reason');

        $result = $reason->invoke(new AmoTestSweeper, new RuntimeException(
            "HTTP request returned status code 500:\n<html><head><title>500</title></head>\n<body>\n  <h1>Внутренняя ошибка</h1>\n</body></html>"
        ));

        if (! is_string($result)) {
            $this->fail('reason() обязан возвращать строку');
        }

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringContainsString('Внутренняя ошибка', $result);
        $this->assertLessThanOrEqual(300, mb_strlen($result));
    }

    /**
     * Корзина и удаление насовсем — разные вещи, и отчёт обязан называть их
     * разными словами: для leads/contacts/companies purge недоступен нигде,
     * и «удалено» про них было бы враньём (решение владельца №2).
     */
    public function test_report_names_trash_and_deletion_differently(): void
    {
        $rendered = AmoTestSweeper::render([
            'account' => 16117840,
            'marker' => AmoTestSweeper::TEST_MARKER,
            'window' => ['days' => 3, 'from' => 0, 'to' => 0],
            'purged' => ['webhooks' => 2],
            'trashed' => ['leads' => 4],
            'unverified' => ['tasks' => 3],
            'stale' => [],
            'failed' => [],
            'scanned' => ['tasks' => 120, 'leads' => 4],
            'warnings' => [],
        ]);

        $this->assertStringContainsString('Удалено насовсем (стирание подтверждено эмпирикой):', $rendered);
        $this->assertStringContainsString('Отправлено в корзину', $rendered);
        $this->assertStringContainsString('purge недоступен нигде', $rendered);
        $this->assertStringContainsString('физическое стирание не доказано', $rendered);

        /* Инструмент удаляет в боевой CRM — из выхлопа должно быть видно, в какой. */
        $this->assertStringContainsString('аккаунт 16117840', $rendered);
    }

    /**
     * «Удалено 0» без числа просмотренного неотличимо от «дискавери мертва»;
     * и то и другое печатается как успешная уборка.
     */
    public function test_empty_report_does_not_read_as_a_clean_account(): void
    {
        $rendered = AmoTestSweeper::render([
            'account' => 16117840,
            'marker' => AmoTestSweeper::TEST_MARKER,
            'window' => ['days' => 3, 'from' => 0, 'to' => 0],
            'purged' => [],
            'trashed' => [],
            'unverified' => [],
            'stale' => [],
            'failed' => [],
            'scanned' => [],
            'warnings' => [],
        ]);

        $this->assertStringContainsString('Просмотрено: ничего (дискавери не отработала)', $rendered);

        /* Типы, которые amo не даёт снести в принципе, обязаны быть названы —
         * иначе пустой отчёт читается как доказательство чистоты аккаунта. */
        $this->assertStringContainsString('shortLinks', $rendered);
        $this->assertStringContainsString('unsorted', $rendered);
        $this->assertStringContainsString('примечания на покупателях', $rendered);
    }

    /**
     * В teardown ответ «уже нет» штатен — сущность мог удалить сам тест. В
     * свипе нет: он видел её в обычной выборке секунду назад, а туда удалённая
     * не приходит вовсе (§8.5). Записать такое в «удалено» — соврать об уборке.
     */
    public function test_stale_answer_is_reported_separately_from_deleted(): void
    {
        $rendered = AmoTestSweeper::render([
            'account' => 16117840,
            'marker' => AmoTestSweeper::TEST_MARKER,
            'window' => ['days' => 3, 'from' => 0, 'to' => 0],
            'purged' => [],
            'trashed' => [],
            'unverified' => [],
            'stale' => [['type' => 'leads', 'ref' => '33286485']],
            'failed' => [['type' => 'webhooks', 'ref' => 'https://example.com/hook', 'reason' => 'HTTP 500']],
            'scanned' => ['leads' => 1],
            'warnings' => [],
        ]);

        $this->assertStringContainsString('которую свип видел живой', $rendered);
        $this->assertStringContainsString('leads 33286485', $rendered);

        /* Вебхук адресуется destination'ом: «webhooks 0 — причина» не даёт
         * оператору того, чем добивать руками. */
        $this->assertStringContainsString('webhooks https://example.com/hook — HTTP 500', $rendered);
    }

    /**
     * «Насовсем» разрешено писать только там, где физическое стирание
     * доказано повторной выборкой (сегодня — один тип, вебхуки). Ответ amo
     * «ок» доказательством не является: §7.7 про tasks/notes, §8.6 про
     * «no note» у примечаний. Отчёт об уборке — единственное место, где мы
     * обещаем результат, и над-обещание здесь стоит дороже всего.
     */
    public function test_only_empirically_proven_deletion_is_called_permanent(): void
    {
        $semanticFor = new ReflectionMethod(AmoTestSweeper::class, 'semanticFor');
        $sweeper = new AmoTestSweeper;

        $this->assertSame(AmoTestSweeper::SEMANTIC_PURGED, $semanticFor->invoke($sweeper, 'webhooks'));

        foreach (['leads', 'contacts', 'companies'] as $type) {
            $this->assertSame(AmoTestSweeper::SEMANTIC_TRASHED, $semanticFor->invoke($sweeper, $type));
        }

        foreach (['tasks', 'notes', 'calls', 'catalogs', 'catalogElements', 'pipelines', 'sources', 'customers'] as $type) {
            $this->assertSame(
                AmoTestSweeper::SEMANTIC_UNVERIFIED,
                $semanticFor->invoke($sweeper, $type),
                "{$type}: физическое стирание не доказано, «насовсем» про него писать нельзя"
            );
        }
    }
}
