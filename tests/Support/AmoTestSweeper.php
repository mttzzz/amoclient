<?php

namespace mttzzz\AmoClient\Tests\Support;

use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Models\AbstractModel;
use Throwable;

/**
 * Последняя сетка от хвостов: находит в аккаунте сущности, помеченные
 * маркером тестов, и сносит их механизмами из docs/research/amo-delete-mechanisms.md §7.6.
 *
 * Свип — не замена teardown'у, а страховка на случай, когда teardown не
 * отработал: фатал в PHP, убитый по Ctrl-C прогон, упавший на сети процесс.
 * Поэтому он ищет по маркеру в самом amo, а не по реестру созданного в
 * текущем процессе (реестра к этому моменту уже нет).
 */
final class AmoTestSweeper
{
    /*
     * Маркер тестовых сущностей. Всё, что содержит эту строку в своём
     * «маркерном» поле (см. MARKER_FIELDS), считается созданным нашими
     * тестами и подлежит сносу; всё остальное свип не трогает.
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

    /* Насовсем: запись физически исчезает из аккаунта. */
    public const SEMANTIC_PURGED = 'purged';

    /* В корзину: запись остаётся с is_deleted=true, purge недоступен. */
    public const SEMANTIC_TRASHED = 'trashed';

    /* Механизм отвечает «ок», но жёсткость удаления эмпирически не снята. */
    public const SEMANTIC_UNVERIFIED = 'unverified';

    /*
     * Семантика удаления по типам — из §7.6 + решения владельца №2.
     * Таблица заодно работает allow-list'ом: тип, которого здесь нет, свипу
     * неизвестен и снести его нечем (см. semanticFor()).
     *
     * customers помечены UNVERIFIED честно: §7.6 приводит механизм «из прежних
     * тестов», но семантику (насовсем или в корзину) никто не снимал.
     * По tasks/notes/calls §7.7 оговаривает, что {"status":"ok"} и пропажа из
     * выборок — ещё не доказательство hard delete; владелец (решение №2) счёл
     * их удалением насовсем, здесь следуем этому решению, но оговорку не
     * прячем — она ровно тут, чтобы следующий читатель не считал вопрос
     * закрытым эмпирикой.
     */
    private const SEMANTICS = [
        'leads' => self::SEMANTIC_TRASHED,
        'contacts' => self::SEMANTIC_TRASHED,
        'companies' => self::SEMANTIC_TRASHED,
        'tasks' => self::SEMANTIC_PURGED,
        'notes' => self::SEMANTIC_PURGED,
        'calls' => self::SEMANTIC_PURGED,
        'catalogs' => self::SEMANTIC_PURGED,
        'catalogElements' => self::SEMANTIC_PURGED,
        'webhooks' => self::SEMANTIC_PURGED,
        'sources' => self::SEMANTIC_PURGED,
        'customers' => self::SEMANTIC_UNVERIFIED,
    ];

    /*
     * Поле, в котором тест ОБЯЗАН оставить маркер, — по одному на тип.
     *
     * Гард смотрит только сюда, а не по всему payload'у. Разница несущая:
     * поиск маркера в json_encode(payload) расширяет гард до «сущность
     * где-то упоминает маркер» — под это подпадает, например, реальная
     * клиентская сделка, к которой наш тест прицепил примечание с маркером.
     * Сама сделка при этом чужая, и сносить её нельзя. Поле-носитель делает
     * гард узким: помечен именно этот объект, а не его окружение.
     */
    private const MARKER_FIELDS = [
        'leads' => 'name',
        'contacts' => 'name',
        'companies' => 'name',
        'catalogs' => 'name',
        'catalogElements' => 'name',
        'customers' => 'name',
        'sources' => 'name',
        'tasks' => 'text',
        'notes' => 'params',
        'calls' => 'params',
        'webhooks' => 'destination',
    ];

    /*
     * Потолок удалений за один свип. Это не оптимизация, а ограничитель
     * радиуса поражения: если дискавери когда-нибудь начнёт возвращать лишнее
     * (сменился формат ответа amo, кто-то расширил фильтр), свип остановится
     * на этом числе и напишет об этом в отчёт, вместо того чтобы методично
     * вычистить аккаунт. Нормальный прогон укладывается в единицы удалений.
     */
    private const DELETE_BUDGET = 200;

    /* Страниц по 150 на тип — потолок скана, чтобы свип не превращался в выкачивание аккаунта. */
    private const MAX_PAGES = 10;

    /*
     * Пауза между запросами на удаление. У amo лимит ~7 запросов в секунду на
     * аккаунт, а свип бежит по живому аккаунту клиента: упереться в 429 (и
     * дальше в 403 с блокировкой) уборкой мусора — недопустимая цена.
     */
    private const DELETE_THROTTLE_US = 150000;

    /**
     * @var array{
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     failed: list<array{type: string, id: int, reason: string}>,
     *     refused: list<array{type: string, id: int}>,
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
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     failed: list<array{type: string, id: int, reason: string}>,
     *     refused: list<array{type: string, id: int}>,
     *     warnings: list<string>
     * }
     */
    public function sweep(AmoClientOctane $amo): array
    {
        $to = time() + 3600; /* запас на расхождение часов с amo */
        $from = $to - ($this->windowDays * 86400);

        $this->deleted = 0;
        $this->report = [
            'marker' => self::TEST_MARKER,
            'window' => ['days' => $this->windowDays, 'from' => $from, 'to' => $to],
            'purged' => [],
            'trashed' => [],
            'unverified' => [],
            'failed' => [],
            'refused' => [],
            'warnings' => [],
        ];

        /*
         * Порядок намеренный: сначала дети (задачи/примечания/звонки/элементы),
         * потом родители. Обратный порядок терял бы детей из выборок — они
         * уходят из обычных ответов вместе с ушедшим в корзину родителем, и
         * свип отчитался бы «удалено 0» там, где на самом деле не увидел.
         */
        $this->sweepTasks($amo, $from, $to);
        $this->sweepNotesAndCalls($amo, $from, $to);
        $this->sweepCatalogs($amo);
        $this->sweepWebhooks($amo);
        $this->sweepSources($amo);
        $this->sweepCustomers($amo);
        $this->sweepQueryable($amo, 'leads');
        $this->sweepQueryable($amo, 'contacts');
        $this->sweepQueryable($amo, 'companies');

        return $this->report;
    }

    /**
     * Единственная дверь к удалению. Принимает не «id», а ровно тот payload,
     * который вернуло amo, и сама достаёт из него и id, и маркерное поле.
     * Поэтому снести непомеченное нельзя, ошибившись в фильтре дискавери:
     * для этого пришлось бы подделать ответ amo.
     *
     * @param  array<string, mixed>  $payload  как вернул amo, без правок
     * @param  callable(int, array<string, mixed>): void  $delete  получает уже провалидированный id
     */
    private function deleteMarked(string $type, array $payload, callable $delete): void
    {
        $semantic = $this->semanticFor($type);
        $id = is_numeric($payload['id'] ?? null) ? (int) $payload['id'] : 0;

        if (! $this->isMarked($type, $payload)) {
            /*
             * Сюда попадать не должно: дискавери обязана отдавать только
             * помеченное. Непустой refused в отчёте — сигнал, что выборка
             * шире маркера, и её надо чинить, а не «оно и так не удалилось».
             */
            $this->report['refused'][] = ['type' => $type, 'id' => $id];

            return;
        }

        if ($id <= 0) {
            $this->report['warnings'][] = "{$type}: помеченная сущность без пригодного id — пропущена";

            return;
        }

        if ($this->deleted >= self::DELETE_BUDGET) {
            return;
        }

        try {
            $delete($id, $payload);
            $this->deleted++;
            $this->report[$semantic][$type] = ($this->report[$semantic][$type] ?? 0) + 1;
        } catch (Throwable $e) {
            $this->report['failed'][] = ['type' => $type, 'id' => $id, 'reason' => $this->reason($e)];
        }

        usleep(self::DELETE_THROTTLE_US);

        if ($this->deleted === self::DELETE_BUDGET) {
            $this->report['warnings'][] = 'бюджет удалений ('.self::DELETE_BUDGET.') исчерпан — свип остановлен. Это защита от разъехавшейся дискавери, а не штатный режим: разберитесь, почему помеченного столько';
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isMarked(string $type, array $payload): bool
    {
        $field = self::MARKER_FIELDS[$type] ?? null;

        if ($field === null) {
            return false;
        }

        $value = $payload[$field] ?? null;

        /* params у примечаний/звонков — подмассив (text/source/link/uniq). */
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        /* Сравнение регистрозависимое и по точной подстроке: маркер машинный, «почти совпало» здесь не бывает. */
        return is_string($value) && str_contains($value, self::TEST_MARKER);
    }

    private function semanticFor(string $type): string
    {
        $semantic = self::SEMANTICS[$type] ?? null;

        if ($semantic === null) {
            /*
             * Тип вне таблицы §7.6 — механизма удаления для него не
             * установлено. Молча «ну попробуем» здесь означало бы стрелять
             * непроверенным вызовом по боевому аккаунту.
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
     * query у amo нечёткий (ищет по многим полям и по подстрокам), поэтому
     * авторитетом остаётся гард, а не сервер.
     */
    private function sweepQueryable(AmoClientOctane $amo, string $type): void
    {
        $model = match ($type) {
            'leads' => $amo->leads->query(self::TEST_MARKER),
            'contacts' => $amo->contacts->query(self::TEST_MARKER),
            'companies' => $amo->companies->query(self::TEST_MARKER),
            default => throw new \LogicException("sweepQueryable не умеет '{$type}'"),
        };

        foreach ($this->scanMarked($type, $type, $model) as $payload) {
            $this->deleteMarked($type, $payload, function (int $id) use ($amo, $type): void {
                $amo->ajax->postForm("/ajax/{$type}/multiple/delete/", ['ID' => [$id]]);
            });
        }
    }

    private function sweepTasks(AmoClientOctane $amo, int $from, int $to): void
    {
        $model = $amo->tasks->filterUpdatedAt($from, $to);

        foreach ($this->scanMarked('tasks', 'tasks', $model) as $payload) {
            $this->deleteMarked('tasks', $payload, function (int $id) use ($amo): void {
                $amo->ajax->postForm('/private/notes/edit2.php', ['ID' => $id, 'ACTION' => 'TASK_DELETE']);
            });
        }
    }

    /**
     * Звонок в amo — примечание особого типа (§6), поэтому и находится, и
     * удаляется вместе с примечаниями; в отчёте разводим их по note_type,
     * чтобы «удалили 3 примечания» не скрывало снесённые звонки.
     */
    private function sweepNotesAndCalls(AmoClientOctane $amo, int $from, int $to): void
    {
        $collections = [
            'leads' => $amo->leads->notes,
            'contacts' => $amo->contacts->notes,
            'companies' => $amo->companies->notes,
        ];

        foreach ($collections as $parent => $notes) {
            /* Маркерное поле у примечания и звонка одно (params), поэтому
             * дискавери общая, а тип уточняется по note_type уже здесь. */
            foreach ($this->scanMarked("notes:{$parent}", 'notes', $notes->filterUpdatedAt($from, $to)) as $payload) {
                $noteType = is_string($payload['note_type'] ?? null) ? $payload['note_type'] : '';
                $type = in_array($noteType, ['call_in', 'call_out'], true) ? 'calls' : 'notes';

                $this->deleteMarked($type, $payload, function (int $id) use ($amo): void {
                    $amo->ajax->postForm('/private/notes/edit2.php', ['ID' => $id, 'ACTION' => 'NOTE_DELETE']);
                });
            }
        }
    }

    /**
     * Каталог сносится целиком и уносит свои элементы каскадом (§2), поэтому
     * помеченные каталоги обрабатываем первыми, а внутрь непомеченных лезем
     * отдельно: тест мог создать элемент в чужом, настоящем каталоге — сам
     * каталог при этом трогать нельзя.
     */
    private function sweepCatalogs(AmoClientOctane $amo): void
    {
        foreach ($this->scanAll('catalogs', $amo->catalogs) as $payload) {
            if ($this->isMarked('catalogs', $payload)) {
                $this->deleteMarked('catalogs', $payload, function (int $id) use ($amo): void {
                    $amo->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $id]]]);
                });

                continue;
            }

            $catalogId = is_numeric($payload['id'] ?? null) ? (int) $payload['id'] : 0;

            if ($catalogId <= 0) {
                continue;
            }

            $elements = $amo->catalogs->entity($catalogId)->elements->query(self::TEST_MARKER);

            foreach ($this->scanMarked("catalogElements:{$catalogId}", 'catalogElements', $elements) as $element) {
                $this->deleteMarked('catalogElements', $element, function (int $id) use ($amo): void {
                    $amo->ajax->postForm('/ajax/v1/catalog_elements/set/', [
                        'request' => ['catalog_elements' => ['delete' => [$id]]],
                    ]);
                });
            }
        }
    }

    private function sweepWebhooks(AmoClientOctane $amo): void
    {
        foreach ($this->scanMarked('webhooks', 'webhooks', $amo->webhooks) as $payload) {
            /* Вебхук адресуется destination'ом, а не id: v4 DELETE /webhooks
             * принимает адрес в теле. id из гарда нужен только отчёту. */
            $this->deleteMarked('webhooks', $payload, function (int $id, array $row) use ($amo): void {
                $destination = is_string($row['destination'] ?? null) ? $row['destination'] : '';
                $amo->webhooks->entity($destination)->unSubscribe();
            });
        }
    }

    private function sweepSources(AmoClientOctane $amo): void
    {
        foreach ($this->scanMarked('sources', 'sources', $amo->sources) as $payload) {
            $this->deleteMarked('sources', $payload, function (int $id) use ($amo): void {
                $amo->sources->entity($id)->delete();
            });
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
        foreach ($this->scanMarked('customers', 'customers', $amo->customers) as $payload) {
            $this->deleteMarked('customers', $payload, function (int $id) use ($amo): void {
                $amo->ajax->postJson('/ajax/v1/customers/set/', [
                    'request' => ['customers' => ['delete' => [$id]]],
                ]);
            });
        }
    }

    /* ---------------------------------------------------------------- */
    /* Общее                                                            */
    /* ---------------------------------------------------------------- */

    /**
     * Постраничный скан с потолком. Ошибка amo не роняет свип: тип уходит в
     * warnings, остальные типы всё равно должны быть убраны — иначе один
     * отключённый в аккаунте раздел оставлял бы хвосты по всем остальным.
     *
     * @return list<array<string, mixed>>
     */
    private function scanAll(string $label, AbstractModel $model): array
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

                if (count($chunk) < 150) {
                    break;
                }

                if ($page === self::MAX_PAGES) {
                    $this->report['warnings'][] = "{$label}: скан упёрся в потолок ".self::MAX_PAGES.' страниц — хвосты могли остаться, прогоните свип повторно';
                }
            }
        } catch (Throwable $e) {
            $this->report['warnings'][] = "{$label}: скан не выполнен — ".$this->reason($e);
        }

        return $rows;
    }

    /**
     * То же, но наружу выходит только помеченное. Гард в deleteMarked()
     * перепроверяет ещё раз — здесь фильтр стоит для того, чтобы чужие
     * сущности вообще не попадали в остальной код свипа.
     *
     * @return list<array<string, mixed>>
     */
    private function scanMarked(string $label, string $markerType, AbstractModel $model): array
    {
        $marked = [];

        foreach ($this->scanAll($label, $model) as $row) {
            if ($this->isMarked($markerType, $row)) {
                $marked[] = $row;
            }
        }

        return $marked;
    }

    private function reason(Throwable $e): string
    {
        $message = trim($e->getMessage());
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return mb_substr($message, 0, 300);
    }

    /**
     * @param  array{
     *     marker: string,
     *     window: array{days: int, from: int, to: int},
     *     purged: array<string, int>,
     *     trashed: array<string, int>,
     *     unverified: array<string, int>,
     *     failed: list<array{type: string, id: int, reason: string}>,
     *     refused: list<array{type: string, id: int}>,
     *     warnings: list<string>
     * }  $report
     */
    public static function render(array $report): string
    {
        $out = [];
        $out[] = 'Свип amo — маркер '.$report['marker'].', окно '.$report['window']['days'].' сут.';
        $out[] = '';

        $out[] = 'Удалено насовсем:';
        $out[] = self::renderBucket($report['purged']);

        /*
         * Корзину печатаем отдельной категорией и отдельным словом. Для
         * leads/contacts/companies purge недоступен нигде — ни в UI, ни в
         * публичном v4, ни в приватном ajax (§7.3), — и назвать их
         * «удалёнными» значило бы соврать в отчёте об уборке.
         */
        $out[] = 'Отправлено в корзину (is_deleted=true, purge недоступен нигде):';
        $out[] = self::renderBucket($report['trashed']);

        $out[] = 'Механизм отработал, жёсткость удаления не проверена:';
        $out[] = self::renderBucket($report['unverified']);

        if ($report['failed'] !== []) {
            $out[] = 'Не удалось снести:';
            foreach ($report['failed'] as $failure) {
                $out[] = '  '.$failure['type'].' '.$failure['id'].' — '.$failure['reason'];
            }
            $out[] = '';
        }

        if ($report['refused'] !== []) {
            $out[] = 'ГАРД ОТКЛОНИЛ (дискавери вернула непомеченное — чинить выборку):';
            foreach ($report['refused'] as $refusal) {
                $out[] = '  '.$refusal['type'].' '.$refusal['id'];
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
         * Без этой строки отчёт «удалено 0» читается как «в аккаунте чисто»,
         * что неверно: часть типов не сметается по устройству самого API.
         */
        $out[] = 'Не сметается по устройству API (§7.6): shortLinks — в ответе create() нет id, адресовать нечем;';
        $out[] = 'unsorted — decline()/accept() единственные переходы, порождённый лид сразу лежит в корзине (§7.5).';

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
}
