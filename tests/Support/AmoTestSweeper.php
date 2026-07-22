<?php

namespace mttzzz\AmoClient\Tests\Support;

use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Models\AbstractModel;
use Throwable;

/**
 * Последняя сетка от хвостов: находит в аккаунте сущности, помеченные
 * маркером тестов, и сносит их через Deleter — единственное место в либе,
 * где живёт таблица «тип → механизм удаления» (docs/research/amo-delete-mechanisms.md §7.6).
 *
 * Свип — не замена teardown'у, а страховка на случай, когда teardown не
 * отработал: фатал в PHP, убитый по Ctrl-C прогон, оборванный на сети
 * процесс. Поэтому он ищет по маркеру в самом amo, а не по реестру
 * созданного в текущем процессе — реестра к этому моменту уже нет.
 */
final class AmoTestSweeper
{
    /*
     * Маркер тестовых сущностей — ЕДИНСТВЕННОЕ место, где живёт эта строка.
     * Тесты берут её отсюда через BaseAmoClient::marked(), а не хардкодят:
     * три разошедшиеся на букву копии маркера означали бы свип, находящий две
     * трети хвостов и молчащий об остальных.
     *
     * Почему именно такой маркер, а не «test» / «Test lead» / «sweep-probe»:
     * аккаунт 16117840 — БОЕВОЙ, в нём живут настоящие клиентские сделки,
     * контакты и компании. Любой человеко-читаемый маркер — обычное слово,
     * которое реальные данные содержат сплошь и рядом: сделка «Тест кухни»,
     * компания «Test Drive», контакт «Тестов Иван». Такой маркер уменьшает
     * вероятность коллизии, но не убирает саму возможность — а цена одной
     * коллизии здесь равна безвозвратно снесённым клиентским данным.
     *
     * Хвост `7c3f9a2e5b41` — 48-битный случайный нонс. Он не является словом
     * ни в одном языке, его нельзя набрать по случайности в поле CRM, и до
     * коммита, вводящего эту константу, он не существовал нигде. Значит любая
     * строка в аккаунте, его содержащая, физически не могла появиться иначе,
     * чем будучи записанной нашим тест-кодом. Коллизия невозможна не «почти»,
     * а по происхождению строки.
     *
     * Человеко-читаемый префикс `amoclient-sweep-` оставлен намеренно: если
     * такая сущность всё-таки попадётся владельцу аккаунта на глаза, по ней
     * сразу видно, чей это мусор и чем он убирается.
     *
     * Менять константу нельзя в отрыве от тестов: сущности, созданные со
     * старым маркером, после смены станут невидимы для свипа навсегда.
     */
    public const TEST_MARKER = 'amoclient-sweep-7c3f9a2e5b41';

    /* Насовсем: физическое стирание подтверждено эмпирикой, а не ответом amo. */
    public const SEMANTIC_PURGED = 'purged';

    /* В корзину: запись остаётся с is_deleted=true, purge недоступен нигде. */
    public const SEMANTIC_TRASHED = 'trashed';

    /* amo подтвердил удаление; доказательство добываемо, но не снято. */
    public const SEMANTIC_UNVERIFIED = 'unverified';

    /* amo подтвердил удаление; доказывать нечем — у типа нет адресного запроса. */
    public const SEMANTIC_UNPROVABLE = 'unprovable';

    /*
     * Семантика удаления по типам. Правило одно: в PURGED попадает только то,
     * для чего есть ЭМПИРИЧЕСКОЕ доказательство физического стирания —
     * повторный запрос сущности после удаления показал, что её нет. Ответ amo
     * «ок» таким доказательством не является: он говорит лишь, что вызов
     * принят.
     *
     * Сегодня доказательство есть ровно у одного типа: webhooks — после
     * unSubscribe() `find()` по destination отдаёт пусто (§1, §2).
     *
     * leads/contacts/companies — TRASHED, и это тоже доказано: §7.3/§8.5,
     * запись живёт с is_deleted=true и находится через withOnlyDeleted().
     *
     * Всё остальное — не PURGED, и это не осторожность ради осторожности:
     * §7.7 прямо оговаривает, что {"status":"ok"} и пропажа из выборок — не
     * доказательство стирания, аналога withOnlyDeleted() у этих типов нет.
     * §8.6 добавляет к tasks/notes/calls деталь: повтор удаления примечания
     * отдаёт {"status":"no note"} — «в хранилище примечаний такого нет», а не
     * «стёрто физически». Решение владельца №2 про корзину касалось
     * leads/contacts/companies и о задачах с примечаниями не говорит ничего,
     * ссылаться на него здесь нельзя.
     *
     * Недоказанное разведено на два разных случая, потому что это разные вещи
     * для читателя отчёта: «не проверили» — задача, которую можно поставить;
     * «проверить нечем» — свойство API, задачей не закрывается. Признак
     * объективный и проверяемый по коду: есть ли у типа адресный запрос
     * «отдай вот эту сущность». У tasks/catalogs/catalogElements/pipelines/
     * sources/customers есть find(), то есть перепроверку после удаления
     * можно поставить задачей — это UNVERIFIED. У notes и calls его нет
     * вовсе (Models\Note и Models\Call не имеют find()), а отсутствие в
     * выборке родителя не отличает стирание от скрытия — это UNPROVABLE.
     *
     * Отчёт свипа — единственное место, где мы обещаем результат уборки;
     * над-обещание именно здесь стоит дороже всего.
     *
     * Состав словаря обязан совпадать с Deleter::TYPES и типами
     * TestEntityRegistry: тип, известный контракту, но забытый здесь, не
     * ищется вовсе — и тогда «свип отработал, ноль находок» неотличимо от
     * «свип не искал».
     */
    private const SEMANTICS = [
        'webhooks' => self::SEMANTIC_PURGED,
        'leads' => self::SEMANTIC_TRASHED,
        'contacts' => self::SEMANTIC_TRASHED,
        'companies' => self::SEMANTIC_TRASHED,
        'tasks' => self::SEMANTIC_UNVERIFIED,
        'notes' => self::SEMANTIC_UNPROVABLE,
        'calls' => self::SEMANTIC_UNPROVABLE,
        'catalogs' => self::SEMANTIC_UNVERIFIED,
        'catalogElements' => self::SEMANTIC_UNVERIFIED,
        'pipelines' => self::SEMANTIC_UNVERIFIED,
        'sources' => self::SEMANTIC_UNVERIFIED,
        'customers' => self::SEMANTIC_UNVERIFIED,
    ];

    /*
     * Потолок удалений за один свип. Это не оптимизация, а ограничитель
     * радиуса поражения: если дискавери когда-нибудь начнёт возвращать лишнее
     * (сменился формат ответа amo, кто-то расширил выборку), свип остановится
     * на этом числе и напишет об этом в отчёт, вместо того чтобы методично
     * вычистить аккаунт. Нормальный прогон укладывается в единицы удалений.
     */
    private const DELETE_BUDGET = 200;

    /* Страниц по 150 на скан — потолок, чтобы свип не превращался в выкачивание аккаунта. */
    private const MAX_PAGES = 10;

    /*
     * Пауза между запросами на удаление. У amo лимит ~7 запросов в секунду на
     * аккаунт, а часть типов сносится строго по одному (каталоги, воронки,
     * задачи, примечания — списочной формы у их роутов нет), то есть свип
     * генерирует ровно N запросов подряд. Упереться в 429 → 403 уборкой
     * мусора и заблокировать боевой аккаунт целиком — недопустимая цена.
     */
    private const DELETE_THROTTLE_US = 150000;

    /**
     * @var array{
     *     account: int,
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     unprovable: array<string, int>,
     *     stale: list<array{type: string, ref: string}>,
     *     failed: list<array{type: string, ref: string, reason: string}>,
     *     scanned: array<string, int>,
     *     warnings: list<string>
     * }
     */
    private array $report;

    private int $deleted = 0;

    /**
     * @param  int  $windowDays  Глубина скана для типов без полнотекстового поиска
     *                           (tasks/notes/calls ищутся фильтром по updated_at).
     */
    public function __construct(private readonly int $windowDays = 3) {}

    /**
     * @return array{
     *     account: int,
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     unprovable: array<string, int>,
     *     stale: list<array{type: string, ref: string}>,
     *     failed: list<array{type: string, ref: string, reason: string}>,
     *     scanned: array<string, int>,
     *     warnings: list<string>
     * }
     */
    public function sweep(AmoClientOctane $amo): array
    {
        $this->assertTablesAgree($amo->deleter);

        $to = time() + 3600; /* запас на расхождение часов с amo */
        $from = $to - ($this->windowDays * 86400);

        $this->deleted = 0;
        $this->report = [
            /* Инструмент удаляет в боевой CRM: из выхлопа должно быть видно, в какой именно. */
            'account' => $amo->accountId,
            'marker' => self::TEST_MARKER,
            'window' => ['days' => $this->windowDays, 'from' => $from, 'to' => $to],
            'purged' => [],
            'trashed' => [],
            'unverified' => [],
            'unprovable' => [],
            'stale' => [],
            'failed' => [],
            'scanned' => [],
            'warnings' => [],
        ];

        /*
         * Порядок несущий, а не косметический:
         *
         * 1) задачи/примечания/звонки — раньше своих родителей: они уходят из
         *    обычных выборок вместе с ушедшим в корзину лидом, и свип
         *    отчитался бы «удалено 0» там, где на самом деле не увидел;
         * 2) элементы списков — раньше самих списков (удаление каталога
         *    уносит элементы каскадом и тем прячет их от подсчёта);
         * 3) сделки/контакты/компании — до воронок: удаление воронки уносит
         *    лежащие в ней сделки, и они пропали бы из выборки непосчитанными;
         * 4) воронки — после сделок, на пустой воронке удаление предсказуемо;
         * 5) вебхуки/источники/покупатели ни от кого не зависят — в конце.
         */
        $this->sweepTasks($amo, $from, $to);
        $this->sweepNotesAndCalls($amo, $from, $to);
        $this->sweepCatalogs($amo);
        $this->sweepQueryable($amo, 'leads');
        $this->sweepQueryable($amo, 'contacts');
        $this->sweepQueryable($amo, 'companies');
        $this->sweepPipelines($amo);
        $this->sweepWebhooks($amo);
        $this->sweepSources($amo);
        $this->sweepCustomers($amo);

        return $this->report;
    }

    /**
     * Единственная дверь к удалению. Принимает SweepTarget, а SweepTarget
     * невозможно получить для непомеченной сущности (см. его приватный
     * конструктор): промах фильтра дискавери здесь не превращается в снос
     * чужих данных.
     */
    private function delete(AmoClientOctane $amo, SweepTarget $target): void
    {
        if ($this->deleted >= self::DELETE_BUDGET) {
            return;
        }

        $semantic = $this->semanticFor($target->type);

        try {
            /*
             * В teardown false («amo говорит, что сущности уже нет») штатен:
             * тест мог удалить её сам. В свипе — нет. Свип только что видел
             * эту сущность в ОБЫЧНОЙ выборке, а по §8.5 удалённая в неё не
             * приходит вовсе. Значит false сразу после дискавери означает не
             * «уже убрано», а что-то другое: реальный отказ по правам, гонку с
             * параллельным прогоном, неверно определённый тип. Считать это
             * успешной уборкой — записать неудалённое в удалённое, то есть
             * соврать ровно в том месте, ради которого свип и существует.
             *
             * Как выглядит каждая из четырёх форм «уже нет», свипу знать не
             * нужно: их различает Deleter, сюда доходит один бит.
             */
            $confirmed = $amo->deleter->byType($target->type, $target->handle);

            $this->deleted++;

            if ($confirmed) {
                $this->report[$semantic][$target->type] = ($this->report[$semantic][$target->type] ?? 0) + 1;
            } else {
                $this->report['stale'][] = ['type' => $target->type, 'ref' => $target->ref()];
            }
        } catch (Throwable $e) {
            $this->report['failed'][] = [
                'type' => $target->type,
                'ref' => $target->ref(),
                'reason' => $this->reason($e),
            ];
        }

        usleep(self::DELETE_THROTTLE_US);

        if ($this->deleted === self::DELETE_BUDGET) {
            $this->report['warnings'][] = 'бюджет удалений ('.self::DELETE_BUDGET.') исчерпан — свип остановлен. Это защита от разъехавшейся дискавери, а не штатный режим: разберитесь, почему помеченного столько';
        }
    }

    /**
     * Свип держится на трёх таблицах типов: SEMANTICS здесь,
     * SweepTarget::MARKER_FIELDS рядом и Deleter::types() в самой либе.
     * Разъехавшись, они дают не падение, а тихую дыру в покрытии — тип просто
     * перестаёт искаться, а отчёт по-прежнему выглядит успешным (так однажды
     * выпали pipelines).
     *
     * Сверяются все три и до единого запроса в amo: пока источником истины
     * был продублированный руками список, дыра ловилась внимательностью, а
     * теперь — построением.
     */
    private function assertTablesAgree(Deleter $deleter): void
    {
        $contract = $deleter->types();
        $withSemantics = self::coveredTypes();
        $withMarkerField = SweepTarget::knownTypes();

        sort($contract);
        sort($withSemantics);
        sort($withMarkerField);

        if ($contract === $withSemantics && $contract === $withMarkerField) {
            return;
        }

        throw new \LogicException(sprintf(
            'Таблицы типов разъехались с контрактом Deleter: свип не ищет [%s]; ищет неизвестное либе [%s]; без поля-носителя [%s]',
            implode(', ', array_diff($contract, $withSemantics)),
            implode(', ', array_diff($withSemantics, $contract)),
            implode(', ', array_diff($withSemantics, $withMarkerField))
        ));
    }

    /**
     * Типы, за которые свип берётся: у каждого есть семантика удаления.
     *
     * Публичный не ради удобства, а ради проверяемости: дыра в покрытии
     * (тип контракта, которого свип не ищет) не роняет ничего и не видна в
     * отчёте — её ловит только сверка списков, а сверить приватную константу
     * тест не может.
     *
     * @return list<string>
     */
    public static function coveredTypes(): array
    {
        return array_keys(self::SEMANTICS);
    }

    /**
     * @return self::SEMANTIC_* ровно одна из трёх категорий отчёта, а не любая
     *                          строка: счётчик в delete() пишет по этому ключу,
     *                          и произвольная строка завела бы в отчёте
     *                          четвёртую категорию, которую никто не печатает
     */
    private function semanticFor(string $type): string
    {
        $semantic = self::SEMANTICS[$type] ?? null;

        if ($semantic === null) {
            /*
             * Тип вне таблицы §7.6 — механизма удаления для него не
             * установлено. Молчаливое «ну попробуем» здесь означало бы
             * стрелять непроверенным вызовом по боевому аккаунту.
             */
            throw new \LogicException("Свип не знает типа '{$type}': нет строки в SEMANTICS");
        }

        return $semantic;
    }

    /* ---------------------------------------------------------------- */
    /* Дискавери по типам                                               */
    /* ---------------------------------------------------------------- */

    /**
     * leads/contacts/companies ищутся полнотекстовым query — маркер длиннее
     * трёхсимвольного минимума amo, так что выборка приходит уже узкой. Но
     * query у amo нечёткий (ищет по многим полям и подстрокам), поэтому
     * авторитетом остаётся гард SweepTarget, а не сервер.
     */
    private function sweepQueryable(AmoClientOctane $amo, string $type): void
    {
        $model = match ($type) {
            'leads' => $amo->leads->query(self::TEST_MARKER),
            'contacts' => $amo->contacts->query(self::TEST_MARKER),
            'companies' => $amo->companies->query(self::TEST_MARKER),
            default => throw new \LogicException("sweepQueryable не умеет '{$type}'"),
        };

        foreach ($this->targets($type, $type, $model) as $target) {
            $this->delete($amo, $target);
        }
    }

    /**
     * Свежесть задач выражается через id, а не updated_at: роут задач
     * order[updated_at] ИГНОРИРУЕТ целиком — три противоположных значения
     * дают побайтово одну страницу, и всё это при HTTP 200 (§8.8). id
     * монотонны, поэтому наши записи при desc идут первыми и не попадают в
     * хвост, отрезаемый потолком страниц.
     *
     * Поддержка сортировки у amo не единообразна между роутами, а «200 в
     * ответ» не значит «параметр применён» — поэтому и порядок выдачи
     * проверяется по данным, и проверяется именно по id.
     */
    private function sweepTasks(AmoClientOctane $amo, int $from, int $to): void
    {
        $windowed = $amo->tasks->filterUpdatedAt($from, $to)->orderByIdDesc();

        foreach ($this->targets('tasks', 'tasks', $windowed, 'id') as $target) {
            $this->delete($amo, $target);
        }
    }

    /**
     * Звонок в amo — примечание особого типа (§6), поэтому и находится, и
     * удаляется вместе с примечаниями; в отчёте разводим их по note_type,
     * чтобы «удалили 3 примечания» не скрывало снесённые звонки.
     *
     * Сортировка `desc` обязательна, а не для красоты: по умолчанию amo отдаёт
     * примечания по возрастанию (§8.7), то есть наши — самые свежие — лежат в
     * хвосте, который отрезает потолок страниц. На пустом аккаунте это не
     * видно, на нагруженном свип перестанет находить именно свой мусор.
     * Направление берётся методом либы, а не строкой: неверное направление amo
     * не отвергает — отвечает 200 и сортирует по умолчанию (§8.7), так что
     * опечатка в литерале не проявилась бы ничем.
     */
    private function sweepNotesAndCalls(AmoClientOctane $amo, int $from, int $to): void
    {
        $collections = [
            'leads' => $amo->leads->notes,
            'contacts' => $amo->contacts->notes,
            'companies' => $amo->companies->notes,
        ];

        foreach ($collections as $parent => $notes) {
            /* ИМЯ ВРЕМЕННОЕ. Models\Note сейчас живёт со старой формой
             * orderUpdatedAtDesc(); трейтовая orderByUpdatedAtDesc() заказана
             * lib-delete и уже стоит в вызовах tests/NoteTest.php. Как модель
             * переименуют — правится эта строка (и падает стан, если забыть). */
            $windowed = $notes->filterUpdatedAt($from, $to)->orderUpdatedAtDesc();

            foreach ($this->scanAll("notes:{$parent}", $windowed, 'updated_at') as $payload) {
                $noteType = is_string($payload['note_type'] ?? null) ? $payload['note_type'] : '';
                $type = in_array($noteType, ['call_in', 'call_out'], true) ? 'calls' : 'notes';

                /* Маркерное поле у примечания и звонка одно (params), тип уточняется здесь. */
                $target = $this->target($type, $payload);

                if ($target !== null) {
                    $this->delete($amo, $target);
                }
            }
        }
    }

    /**
     * Каталог сносится целиком и уносит свои элементы каскадом (§2), поэтому
     * внутрь непомеченных каталогов лезем отдельно: тест мог создать элемент
     * в чужом, настоящем каталоге — сам каталог при этом трогать нельзя.
     *
     * Помеченные каталоги идут после элементов: сначала выносим то, что можно
     * увидеть и посчитать, потом сам каталог с его каскадом.
     */
    private function sweepCatalogs(AmoClientOctane $amo): void
    {
        $markedCatalogs = [];

        foreach ($this->scanAll('catalogs', $amo->catalogs) as $payload) {
            if (SweepTarget::isMarked('catalogs', $payload)) {
                $markedCatalogs[] = $payload;

                continue;
            }

            $catalogId = is_numeric($payload['id'] ?? null) ? (int) $payload['id'] : 0;

            if ($catalogId <= 0) {
                continue;
            }

            $elements = $amo->catalogs->entity($catalogId)->elements->query(self::TEST_MARKER);

            foreach ($this->targets("catalogElements:{$catalogId}", 'catalogElements', $elements) as $target) {
                $this->delete($amo, $target);
            }
        }

        foreach ($markedCatalogs as $payload) {
            $target = $this->target('catalogs', $payload);

            if ($target !== null) {
                $this->delete($amo, $target);
            }
        }
    }

    /**
     * Воронки — публичный v4 DELETE (§7.6), настоящее удаление, не корзина.
     * Полнотекстового query у этого эндпойнта нет, но список воронок аккаунта
     * короткий, поэтому обычного скана хватает.
     */
    private function sweepPipelines(AmoClientOctane $amo): void
    {
        foreach ($this->targets('pipelines', 'pipelines', $amo->pipelines) as $target) {
            $this->delete($amo, $target);
        }
    }

    private function sweepWebhooks(AmoClientOctane $amo): void
    {
        foreach ($this->targets('webhooks', 'webhooks', $amo->webhooks) as $target) {
            $this->delete($amo, $target);
        }
    }

    private function sweepSources(AmoClientOctane $amo): void
    {
        foreach ($this->targets('sources', 'sources', $amo->sources) as $target) {
            $this->delete($amo, $target);
        }
    }

    /**
     * У модели Customer нет ни полнотекстового query, ни фильтров, поэтому
     * это единственный тип, который приходится сканировать подряд. Если в
     * аккаунте покупатели отключены (частый случай — тесты на этом
     * скипаются), скан отвалится ошибкой amo: она уходит в warnings, свип
     * продолжается — падать на необязательном типе, оставив хвосты по
     * остальным, было бы хуже.
     */
    private function sweepCustomers(AmoClientOctane $amo): void
    {
        foreach ($this->targets('customers', 'customers', $amo->customers) as $target) {
            $this->delete($amo, $target);
        }
    }

    /* ---------------------------------------------------------------- */
    /* Общее                                                            */
    /* ---------------------------------------------------------------- */

    /**
     * Скан + гард: наружу выходят только цели, которые разрешено сносить.
     *
     * @return list<SweepTarget>
     */
    private function targets(string $label, string $type, AbstractModel $model, ?string $freshnessKey = null): array
    {
        $targets = [];

        foreach ($this->scanAll($label, $model, $freshnessKey) as $payload) {
            $target = $this->target($type, $payload);

            if ($target !== null) {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function target(string $type, array $payload): ?SweepTarget
    {
        $target = SweepTarget::fromMarked($type, $payload);

        if ($target === null && SweepTarget::isMarked($type, $payload)) {
            /*
             * Помечено нашим маркером, но адресовать нечем (нет id, у вебхука
             * пустой destination). Тихо пропустить — значит оставить свой же
             * хвост в боевом аккаунте и отчитаться «чисто».
             */
            $this->report['warnings'][] = "{$type}: помеченная сущность без пригодного адреса — не снесена, разберитесь руками";
        }

        return $target;
    }

    /**
     * Постраничный скан с потолком. Ошибка amo не роняет свип: тип уходит в
     * warnings, остальные всё равно должны быть убраны — иначе один
     * отключённый в аккаунте раздел оставлял бы хвосты по всем остальным.
     *
     * @param  string|null  $freshnessKey  поле, по убыванию которого обязана идти
     *                                      выдача оконной дискавери: `updated_at` у
     *                                      примечаний, `id` у задач (роут задач
     *                                      order[updated_at] игнорирует целиком, §8.8).
     *                                      null — выборка не оконная, проверять нечего
     * @return list<array<string, mixed>>
     */
    private function scanAll(string $label, AbstractModel $model, ?string $freshnessKey = null): array
    {
        $rows = [];

        try {
            for ($page = 1; $page <= self::MAX_PAGES; $page++) {
                $chunk = $model->page($page)->limit(150)->get();

                if ($chunk === []) {
                    break;
                }

                foreach ($chunk as $row) {
                    if (is_array($row)) {
                        /** @var array<string, mixed> $row */
                        $rows[] = $row;
                    }
                }

                /*
                 * Самопроверка сортировки — по данным, а не по форме запроса.
                 * Переход оконной дискавери на desc непроверяем изнутри: amo
                 * отвечает 200 на любое направление и молча сортирует по
                 * умолчанию (§8.7). Если ключ или направление однажды
                 * разъедутся, свип не упадёт и не предупредит — он просто
                 * снова начнёт отрезать свежий хвост, то есть терять ровно
                 * свой мусор. Данные первой страницы уже в руках, лишнего
                 * запроса это не стоит.
                 */
                if ($freshnessKey !== null && $page === 1 && self::looksDescending($rows, $freshnessKey) === false) {
                    $this->report['warnings'][] = "{$label}: выдача идёт по возрастанию {$freshnessKey} — сортировка desc не применилась, свежие хвосты могут быть за потолком страниц; сузьте окно (--days)";
                }

                if (count($chunk) < 150) {
                    break;
                }

                if ($page === self::MAX_PAGES) {
                    $advice = $freshnessKey !== null
                        ? 'сузьте окно (--days), повторный прогон вернёт те же страницы'
                        : 'прогоните свип повторно';
                    $this->report['warnings'][] = "{$label}: скан упёрся в потолок ".self::MAX_PAGES." страниц — хвосты могли остаться, {$advice}";
                }
            }
        } catch (Throwable $e) {
            $this->report['warnings'][] = "{$label}: скан не выполнен — ".$this->reason($e);
        }

        $type = strtok($label, ':');
        $this->report['scanned'][$type === false ? $label : $type] = ($this->report['scanned'][$type === false ? $label : $type] ?? 0) + count($rows);

        return $rows;
    }

    /**
     * Идёт ли выдача от свежего к старому.
     *
     * Поле сравнения задаётся вызывающим, потому что «свежесть» на разных
     * роутах выражена по-разному: у примечаний это updated_at, у задач — id
     * (их роут order[updated_at] игнорирует целиком, §8.8). Проверять задачи
     * по updated_at значило бы ложно срабатывать каждый прогон, а варнинг,
     * который всегда горит, перестают читать.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return bool|null null — судить не по чему: меньше двух строк или поля в них нет
     */
    private static function looksDescending(array $rows, string $key): ?bool
    {
        if (count($rows) < 2) {
            return null;
        }

        $first = $rows[0][$key] ?? null;
        $last = $rows[count($rows) - 1][$key] ?? null;

        if (! is_numeric($first) || ! is_numeric($last)) {
            return null;
        }

        return (int) $first >= (int) $last;
    }

    /**
     * Причина отказа в одну строку.
     *
     * strip_tags здесь несущий: на 500 amo отдаёт не JSON, а полноценную
     * HTML-страницу, и она приезжает внутрь сообщения исключения. Без чистки
     * отчёт об уборке превращается в вывалившуюся вёрстку, за которой не
     * видно ни одной настоящей причины.
     */
    private function reason(Throwable $e): string
    {
        $message = strip_tags($e->getMessage());
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;
        $message = trim($message);

        if ($message === '') {
            $message = get_class($e);
        }

        return mb_substr($message, 0, 300);
    }

    /**
     * @param  array{
     *     account: int,
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     unprovable: array<string, int>,
     *     stale: list<array{type: string, ref: string}>,
     *     failed: list<array{type: string, ref: string, reason: string}>,
     *     scanned: array<string, int>,
     *     warnings: list<string>
     * }  $report
     */
    public static function render(array $report): string
    {
        $out = [];
        $out[] = 'Свип amo — аккаунт '.$report['account'].', маркер '.$report['marker'].', окно '.$report['window']['days'].' сут.';
        $out[] = '';

        /*
         * «Насовсем» пишется только там, где физическое стирание доказано
         * повторной выборкой. Сегодня это один тип — вебхуки.
         */
        $out[] = 'Удалено насовсем (стирание подтверждено эмпирикой):';
        $out[] = self::renderBucket($report['purged']);

        /*
         * Корзину печатаем отдельной категорией и отдельным словом. Для
         * leads/contacts/companies purge недоступен нигде — ни в UI, ни в
         * публичном v4, ни в приватном ajax (§7.3), — и назвать их
         * «удалёнными» значило бы соврать в отчёте об уборке.
         */
        $out[] = 'Отправлено в корзину (is_deleted=true, purge недоступен нигде):';
        $out[] = self::renderBucket($report['trashed']);

        /*
         * Два разных «не доказано». Первое — задача, которую можно поставить:
         * у типа есть адресный запрос, и перепроверка после удаления делается.
         * Второе — свойство API: проверять нечем, задачей это не закрывается.
         * Печатать их одной строкой значит либо завести вечно висящий долг,
         * либо тихо согласиться его игнорировать.
         */
        $out[] = 'amo подтвердил удаление, стирание не проверено (проверка возможна — есть адресный запрос):';
        $out[] = self::renderBucket($report['unverified']);

        $out[] = 'amo подтвердил удаление, проверить нечем (адресного запроса у типа нет):';
        $out[] = self::renderBucket($report['unprovable']);

        /*
         * Сущность, которую свип видел в обычной выборке, но amo на удаление
         * ответил «её уже нет». По §8.5 удалённая в обычную выборку не
         * приходит, значит это не идемпотентный повтор, а расхождение:
         * права, гонка с параллельным прогоном или неверный тип. Печатаем
         * отдельно — в буфер «удалено» такое класть нельзя.
         */
        if ($report['stale'] !== []) {
            $out[] = 'amo ответил «уже нет» о сущности, которую свип видел живой — проверьте:';
            foreach ($report['stale'] as $entry) {
                $out[] = '  '.$entry['type'].' '.$entry['ref'];
            }
            $out[] = '';
        }

        if ($report['failed'] !== []) {
            $out[] = 'Не удалось снести:';
            foreach ($report['failed'] as $failure) {
                $out[] = '  '.$failure['type'].' '.$failure['ref'].' — '.$failure['reason'];
            }
            $out[] = '';
        }

        if ($report['warnings'] !== []) {
            $out[] = 'Предупреждения:';
            foreach ($report['warnings'] as $warning) {
                $out[] = '  - '.$warning;
            }
            $out[] = '';
        }

        /*
         * Сколько сущностей свип вообще посмотрел. Без этой строки «удалено 0»
         * не отличить от «не искал»: пустой результат по типу — это либо
         * чистый аккаунт, либо мёртвая дискавери, и цифра просмотренного
         * разводит эти два случая.
         */
        $out[] = 'Просмотрено: '.self::renderScanned($report['scanned']);
        $out[] = '';

        /*
         * Без этой строки отчёт «удалено 0» читается как «в аккаунте чисто»,
         * что неверно: часть типов не сметается по устройству самого API.
         */
        $out[] = 'Не сметается (§7.5, §7.6):';
        $out[] = '  shortLinks       — в ответе create() нет id, адресовать нечем';
        $out[] = '  unsorted         — decline()/accept() единственные переходы, порождённый лид сразу в корзине';
        $out[] = '  примечания на покупателях — коллекции customers/notes в либе нет, дискавери им не располагает';
        $out[] = '                     (активного хвоста нет: тесты их не создают)';

        return implode(PHP_EOL, $out).PHP_EOL;
    }

    /**
     * @param  array<string, int>  $bucket
     */
    private static function renderBucket(array $bucket): string
    {
        if ($bucket === []) {
            return '  —'.PHP_EOL;
        }

        $lines = [];
        ksort($bucket);

        foreach ($bucket as $type => $count) {
            $lines[] = '  '.str_pad($type, 18).$count;
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * @param  array<string, int>  $scanned
     */
    private static function renderScanned(array $scanned): string
    {
        if ($scanned === []) {
            return 'ничего (дискавери не отработала)';
        }

        ksort($scanned);
        $parts = [];

        foreach ($scanned as $type => $count) {
            $parts[] = $type.' '.$count;
        }

        return implode(', ', $parts);
    }
}
