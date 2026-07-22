<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\Tests\Support\AmoTestSweeper;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Доказательство, что авто-уборка BaseAmoClient действительно сносит созданное
 * в боевом amo, и регрессия-гейт на будущее.
 *
 * Тесты зовут sweepTrackedEntities() явно, а не полагаются на tearDownAfterClass:
 * лид создаётся и проверяется в одном тесте, ассерты идут по тому же коду, что
 * выполняет teardown, и провал показывает конкретную сущность, а не «после
 * прогона что-то осталось».
 *
 * За весь класс в amo создаётся РОВНО ОДИН лид: остальные тесты переиспользуют
 * его id через #[Depends] — боевой аккаунт один на всю команду.
 */
class BaseAmoClientTest extends BaseAmoClient
{
    private const PROBE_NAME = 'sweep-probe teardown-gate';

    public function test_sweep_deletes_tracked_lead_and_drains_registry(): int
    {
        $lead = $this->amoClient->leads->entity();
        /* Через marked(): если снос вдруг не пройдёт, зонд заберёт финальный
         * свип — гейт уборки не имеет права сам оставлять хвост. */
        $lead->name = $this->marked(self::PROBE_NAME);

        $leadId = $this->track('leads', $lead->createGetId());

        $this->assertContains(
            ['type' => 'leads', 'id' => $leadId],
            self::registry()->all(),
            'track() обязан зарегистрировать созданный лид — иначе уборка о нём не узнает'
        );

        self::sweepTrackedEntities();

        $this->assertNotContains(
            ['type' => 'leads', 'id' => $leadId],
            self::registry()->all(),
            "лид $leadId остался в реестре: amo не принял удаление (см. [teardown] в STDERR)"
        );

        $this->assertSame(
            [],
            $this->activeLead($leadId),
            "лид $leadId после уборки всё ещё активен в amo 16117840 — хвост в боевом аккаунте"
        );

        return $leadId;
    }

    #[Depends('test_sweep_deletes_tracked_lead_and_drains_registry')]
    public function test_sweep_is_idempotent_on_already_deleted_lead(int $leadId): void
    {
        /* Ровно то, что делает shutdown-хук, если фатал случился уже после
         * успешного tearDownAfterClass. На повторное удаление лежащего в
         * корзине amo отвечает «Недостаточно прав для удаления сделки» (§7.4
         * ресёрча — это про «уже удалено», а не про права). Принять этот ответ
         * за ошибку значит получить ложный шум на каждом прогоне и утопить в
         * нём настоящие хвосты. */
        $this->track('leads', $leadId);

        self::sweepTrackedEntities();

        $this->assertNotContains(
            ['type' => 'leads', 'id' => $leadId],
            self::registry()->all(),
            "повторный снос лида $leadId посчитан провалом — идемпотентность уборки сломана"
        );
    }

    #[Depends('test_sweep_deletes_tracked_lead_and_drains_registry')]
    public function test_swept_lead_lands_in_trash_because_purge_is_unavailable(int $leadId): void
    {
        /* Инвариант уборки сформулирован честно: «не остаётся АКТИВНЫХ
         * созданных тестами сущностей». Hard delete для leads/contacts/companies
         * не выставлен нигде — ни в UI (модалка врёт), ни в публичном v4, ни в
         * приватном ajax; purge корзины недоступен вообще (§7.3, решение
         * владельца №2). Тест пришпиливает это, чтобы следующий читатель не
         * стал «чинить» teardown до обещания, которого amo не выполняет. */
        $deleted = $this->amoClient->leads->withOnlyDeleted()->filterId($leadId)->get();

        $this->assertNotSame(
            [],
            $deleted,
            "лид $leadId не виден и в корзине — семантика удаления в amo изменилась, ресёрч §7.3 устарел"
        );
        $this->assertTrue(
            (bool) ($deleted[0]['is_deleted'] ?? false),
            "лид $leadId найден среди удалённых, но без is_deleted=true"
        );
    }

    /*
     * Ниже — чистая строковая логика marked(), в amo не ходит.
     * Помечает ВСЁ, что тесты создают в боевом аккаунте: свип не имеет права
     * трогать сущность, в чьём payload маркера нет, поэтому дырка в marked() —
     * это ровно те хвосты, которые свип потом не увидит.
     */

    public function test_marked_appends_sweep_marker(): void
    {
        $marked = $this->marked('Test Lead');

        $this->assertStringContainsString(
            AmoTestSweeper::TEST_MARKER,
            $marked,
            'без маркера в payload финальный свип не имеет права трогать сущность'
        );
        $this->assertStringStartsWith(
            'Test Lead',
            $marked,
            'маркер дописывается, а не заменяет: тесты сравнивают отправленное имя с полученным'
        );
    }

    public function test_marked_is_idempotent(): void
    {
        $once = $this->marked('Test Lead');

        $this->assertSame(
            $once,
            $this->marked($once),
            'повторная пометка клеит маркер дважды — значение разъезжается с тем, что ассертит тест'
        );
    }

    public function test_marked_keeps_url_valid_for_webhook_destination(): void
    {
        /* destination вебхука — настоящий URL, amo его валидирует. Пробел с
         * суффиксом сделал бы подписку неотправляемой, а вебхук как раз тот
         * тип, где хвост живёт вечно: у него hard delete и нет числового id. */
        $destination = 'https://webhook.site/a895608c-8b4a-453e-8359-4ed5d42bb454';

        $marked = $this->marked($destination);

        $this->assertStringContainsString(AmoTestSweeper::TEST_MARKER, $marked);
        $this->assertStringNotContainsString(' ', $marked, 'URL с пробелом amo не примет');
        $this->assertNotFalse(filter_var($marked, FILTER_VALIDATE_URL), "помеченный destination перестал быть URL: $marked");
        $this->assertSame($marked, $this->marked($marked), 'повторная пометка URL обязана быть no-op');
    }

    /**
     * Активная (не в корзине) выборка по id с ретраями.
     *
     * Индекс списков amo догоняет удаление не мгновенно — сразу после принятого
     * сноса лид может ещё раз прийти в выборке. Без окна ожидания гейт флапал бы
     * не по делу и его бы отключили, а с ним — и защиту от хвостов.
     *
     * @return array<string, mixed>
     */
    private function activeLead(int $leadId): array
    {
        $found = [];

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $found = $this->amoClient->leads->filterId($leadId)->get();

            if ($found === []) {
                return [];
            }

            sleep(2);
        }

        return $found;
    }
}
