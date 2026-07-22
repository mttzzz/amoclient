<?php

namespace mttzzz\AmoClient\Tests;

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
    /* Имя-маркер: по нему созданное этим гейтом добирает финальный свип, если
     * снос почему-то не прошёл. Маркер свипа держит sweeper-ci — разойдётся,
     * меняется здесь одной строкой. */
    private const PROBE_NAME = 'sweep-probe teardown-gate';

    public function test_sweep_deletes_tracked_lead_and_drains_registry(): int
    {
        $lead = $this->amoClient->leads->entity();
        $lead->name = self::PROBE_NAME;

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
