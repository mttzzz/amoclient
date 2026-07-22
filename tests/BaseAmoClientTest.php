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

        /* track() отдаёт id обратно (int|string — из-за строково-адресуемых
         * вебхуков), но здесь он заведомо int: createGetId() иначе не умеет.
         * Держим переменную отдельно, чтобы тип не расширялся на весь тест и
         * #[Depends]-потребители получали int. */
        $leadId = $lead->createGetId();
        $this->track('leads', $leadId);

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
         * успешного tearDownAfterClass. По контракту Deleter повторный снос
         * уже удалённого — это false («известная „уже нет“-причина»), а не
         * исключение, и teardown обязан считать цель достигнутой и вычеркнуть
         * запись. Иначе каждый прогон копил бы ложные хвосты и топил в них
         * настоящие. Формулировки амо разбирает Deleter — тест проверяет
         * поведение уборки, а не тексты. */
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
         * стал «чинить» teardown до обещания, которого amo не выполняет.
         *
         * Проверяем СТРОГО id и is_deleted и ничего больше: по §8.5 ресёрча из
         * корзины возвращается почти пустая запись — живут только id,
         * is_deleted, updated_at и account_id, а name, price и status_id
         * приходят null. Ассерт на имя или на маркер здесь упал бы не потому,
         * что уборка сломалась, а потому что amo не хранит их для удалённого. */
        /* array_values(): get() отдаёт список сущностей, но объявлен как
         * array<string, mixed> — по этому типу обращение к [0] непроверяемо. */
        $deleted = array_values($this->amoClient->leads->withOnlyDeleted()->filterId($leadId)->get());

        $this->assertNotSame(
            [],
            $deleted,
            "лид $leadId не виден и в корзине — семантика удаления в amo изменилась, ресёрч §7.3 устарел"
        );

        $trashed = $deleted[0] ?? null;

        if (! is_array($trashed)) {
            $this->fail("корзина отдала не сущность по фильтру id=$leadId: ".get_debug_type($trashed));
        }

        $this->assertSame(
            $leadId,
            $trashed['id'] ?? null,
            "в корзине по фильтру id=$leadId лежит не тот лид"
        );
        $this->assertSame(
            true,
            $trashed['is_deleted'] ?? null,
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
     * Пустой ответ на удалённый лид — подтверждённая форма (§8.5 ресёрча:
     * `filterId(<удалённый>)->get()` → `[]`), поэтому пустота и есть критерий
     * «не активен». Ретраи — не про форму ответа, а про догоняющий индекс
     * списков amo: сразу после принятого сноса лид может успеть прийти ещё
     * раз. В счастливом пути ожидания нет — цикл выходит на первой итерации.
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
