<?php

namespace mttzzz\AmoClient;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;

/**
 * Единственное место, где живёт таблица «тип сущности → механизм удаления»
 * (docs/research/amo-delete-mechanisms.md, §7.6 и §8).
 *
 * Модельные `$amo->{коллекция}->delete()` и entity-level
 * `Entities\Source::delete()` / `Entities\Webhook::unSubscribe()` — делегации
 * сюда, а не собственные реализации. Причина не в эстетике: две реализации
 * одной логики, обязанные оставаться синхронными, — это ровно та форма дрейфа,
 * которая уже развела транспортный retry-колбэк и `RetriesTransientAmoErrors`
 * по охвату (решение по архитектуре 4.0, п. 9).
 *
 * ШЕСТЬ ЛИЦ ОДНОГО ФАКТА «СУЩНОСТИ УЖЕ НЕТ» — главная причина, по которой это
 * знание обязано лежать в одном классе, а не расползаться по вызывающим. Амо
 * сообщает об одном и том же шестью несовместимыми способами:
 *
 * | Канал | Как выглядит «уже нет» |
 * |---|---|
 * | `/private/notes/edit2.php`, ЗАДАЧА | HTTP **400**, `{"status":"fail","id":N}`, текста нет вовсе |
 * | `/private/notes/edit2.php`, ПРИМЕЧАНИЕ и ЗВОНОК | HTTP **200**, `{"status":"no note","id":N}` |
 * | `/ajax/{тип}/multiple/delete/` | HTTP 200, `{"status":"fail","errors":["Недостаточно прав для удаления …"]}` |
 * | `/ajax/v1/{раздел}/set/` | HTTP 200, `errors[].code === 404` |
 * | публичный v4, ВОРОНКИ | HTTP **400**, `validation-errors[].errors[].code === "NotSupportedChoice"`, `path === "id"` |
 * | публичный v4, остальные | HTTP 404 |
 *
 * У воронок при этом единственный во всём наборе случай, когда амо ОТЛИЧАЕТ
 * «уже нет» от «нельзя удалить»: непустая воронка отвечает 422 и остаётся
 * громкой ошибкой. Везде остальном эти два исхода слиты в один ответ, и `false`
 * означает «не подтверждено», а не «объекта нет».
 *
 * (В §9.9 ресёрча их насчитано пять — там обычный 404 не выделен отдельной
 * строкой как «безусловный базовый случай». Здесь строк шесть, потому что
 * таблица перечисляет то, что распознаёт классификатор, а 404 он распознаёт
 * наравне с остальными. Расхождение только в счёте, не в фактах.)
 *
 * Первые две строки — один роут и один глагол на соседних типах, и они
 * противоположны по знаку: у задачи «уже нет» это ошибка, у примечания —
 * успех. Последние две — один и тот же публичный v4, где воронки отвечают не
 * как все. Отсюда правило, стоившее нам двух отдельных находок: классификатор,
 * знающий пять представлений из шести, ломает идемпотентность на шестом МОЛЧА
 * — лишний бросок неотличим от честной ошибки, а teardown уходит в повторы
 * (на воронках это дало девять попыток подряд, ни одной успешной).
 *
 * Ни teardown, ни свип, ни потребитель не должны знать ни одного из шести —
 * они читают `bool`.
 *
 * ЯРУС SEMVER. Часть операций ниже ходит в приватные роуты амо
 * (`/private/notes/edit2.php`, `/ajax/**`) — недокументированные и способные
 * поменяться без предупреждения. По ним библиотека обещает НЕ «работает
 * всегда», а «ломается громко и чинится patch-ом»: изменившийся ответ даёт
 * AmoUnknownException с сырым телом, а не тихий неверный результат.
 * Публичный v4 (sources, pipelines, webhooks) живёт по обычным правилам.
 *
 * СЕМАНТИКА ВОЗВРАТА, единая для всех методов:
 * - `true`  — амо подтвердил удаление;
 * - `false` — амо отказал причиной, которую в его исполнении невозможно
 *             отличить от «сущности уже нет». Это НЕ доказательство того, что
 *             её нет: у `multiple/delete` тем же ответом выражается и реальный
 *             отказ по правам (§7.4). Значение выбрано так, чтобы повторный
 *             снос был идемпотентным и второй прогон teardown не падал ложно,
 *             но неоднозначность обязана быть видна вызывающему;
 * - бросок  — HTTP-ошибка (AmoCustomException) либо нераспознанный ответ
 *             (AmoUnknownException). Молчаливого «ну наверное удалилось» нет
 *             ни в одной ветке.
 *
 * Пустой список id — `true` без запроса: удалить ничего успешно можно.
 *
 * Зависит только от `Ajax` и `PendingRequest`, то есть работает от голого
 * клиента `AmoClientOctane` — снос зовётся не только из тестов, но и из
 * `register_shutdown_function` и из CLI-свипа, где ни тест-кейса, ни живого
 * DI-контейнера уже нет.
 */
class Deleter
{
    /**
     * Приватный роут-хаб амо. Задачи, примечания и звонки удаляются одним и тем
     * же скриптом — звонок в амо это примечание особого типа (`note_type`
     * call_in/call_out), поэтому у него глагол NOTE_DELETE, а не собственный.
     * Это не хак с нашей стороны, а модель данных амо (§7.6).
     */
    private const PRIVATE_NOTES_ENDPOINT = '/private/notes/edit2.php';

    /**
     * Ответ приватного роута на повторное удаление ПРИМЕЧАНИЯ или ЗВОНКА
     * (§8.6): HTTP 200 со статусом `no note`. Не ошибка — «нечего удалять».
     *
     * Задача в том же роуте отвечает на то же самое иначе — HTTP 400 со
     * статусом `fail`. Соседние типы, один глагол, два несовместимых
     * представления одного факта; см. isPrivateAlreadyGone().
     */
    private const PRIVATE_NO_NOTE_STATUS = 'no note';

    /**
     * §7.4: амо отдаёт этот текст И на реальный отказ по правам, И на попытку
     * удалить то, что уже лежит в корзине. Проверено на трёх лидах под
     * админ-сессией владельца: удаление свежеудалённого даёт именно его.
     * Различить два случая по ответу НЕЛЬЗЯ — амо здесь дезинформирует.
     * Поэтому текст трактуется как «уже нет» ТОЛЬКО внутри операции удаления
     * (решение по архитектуре 4.0, п. 2: глобальное правило по тексту
     * запрещено — оно маскировало бы реальный пермишн-фейл).
     *
     * Матчим строго по этому префиксу и никогда по полной строке: §8 показал,
     * что хвост сообщения врёт про тип сущности — удаление компании амо
     * комментирует как «…для удаления контакта», и в успехе тоже («Удаление
     * прошло успешно для 1 контакта»). Тип сущности из текста амо не выводить
     * ни при каких условиях.
     */
    private const ALREADY_GONE_MARKER = 'Недостаточно прав для удаления';

    /** Типы, которые умеет `byType()` — тот же словарь, что у TestEntityRegistry. */
    private const TYPES = [
        'leads', 'contacts', 'companies', 'customers', 'catalogs', 'catalogElements',
        'tasks', 'notes', 'calls', 'pipelines', 'sources', 'webhooks',
    ];

    private Ajax $ajax;

    private PendingRequest $http;

    public function __construct(Ajax $ajax, PendingRequest $http)
    {
        $this->ajax = $ajax;
        $this->http = $http;
    }

    /**
     * Словарь типов, которые умеет `byType()`.
     *
     * Отдаётся наружу не ради удобства, а чтобы рассинхрон таблиц стал
     * невозможным по построению. У свипа своя таблица (тип → где искать
     * хвосты), у реестра — свой список; сверить их с нашей нечем, пока она
     * приватна. А тип, потерянный в одной из таблиц, не ищется МОЛЧА: «удалено
     * 0» в отчёте неотличимо от «не искали вовсе». Это тот же класс дефекта,
     * что и тихий пустой массив, только этажом выше.
     *
     * @return list<string>
     */
    public function types(): array
    {
        return self::TYPES;
    }

    /**
     * Диспетчер для свипа: у реестра на руках пара (тип, id), а не готовая
     * коллекция. Родитель примечания и каталог элемента здесь не нужны —
     * приватные роуты резолвят их сами по id сущности (§6).
     *
     * Идентификатор допускает строку: вебхук адресуется destination'ом, а не
     * числом, и реестр уборки расширен до `int|string` именно из-за него.
     *
     * @param  int|string|array<mixed>  $ids
     *
     * @throws InvalidArgumentException неизвестный тип — громко, не тихий no-op
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function byType(string $type, int|string|array $ids): bool
    {
        return match ($type) {
            'leads' => $this->leads($this->ids($ids, 'delete leads')),
            'contacts' => $this->contacts($this->ids($ids, 'delete contacts')),
            'companies' => $this->companies($this->ids($ids, 'delete companies')),
            'customers' => $this->customers($this->ids($ids, 'delete customers')),
            'catalogs' => $this->catalogs($this->ids($ids, 'delete catalogs')),
            'catalogElements' => $this->catalogElements($this->ids($ids, 'delete catalogElements')),
            'tasks' => $this->tasks($this->ids($ids, 'delete tasks')),
            'notes' => $this->notes($this->ids($ids, 'delete notes')),
            'calls' => $this->calls($this->ids($ids, 'delete calls')),
            'pipelines' => $this->pipelines($this->ids($ids, 'delete pipelines')),
            'sources' => $this->sources($this->ids($ids, 'delete sources')),
            'webhooks' => $this->webhooks($this->destinations($ids)),
            default => throw new InvalidArgumentException(sprintf(
                'Deleter: неизвестный тип «%s». Известные: %s.',
                $type,
                implode(', ', self::TYPES)
            )),
        };
    }

    /**
     * Удаление В КОРЗИНУ, не физическое (§3, §7.3). После вызова сущность
     * пропадает из обычных выборок, но остаётся в аккаунте и достаётся через
     * `withOnlyDeleted()`. Purge недоступен ни в UI, ни в публичном v4, ни в
     * приватном ajax — обещать hard delete было бы враньём. Модалка амо,
     * обещающая «восстановить невозможно», врёт (§7.3).
     *
     * ФЛАГ `is_deleted` ПРИЗНАКОМ УДАЛЕНИЯ НЕ СЛУЖИТ. У сделок он встаёт в
     * `true` (§8.5), а у контактов и компаний остаётся `false` — при том, что
     * запись точно так же скрыта из обычной выборки и достаётся только через
     * `only_deleted` (§9.6). Семантика корзины у всех трёх одинаковая, врёт
     * именно флаг. Достоверный признак — ОТСУТСТВИЕ в обычной выборке при
     * НАЛИЧИИ в `only_deleted`; проверку на `is_deleted` здесь строить нельзя,
     * она даст ложное «не удалено» для двух типов из трёх.
     *
     * Одним запросом на всю пачку.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил «Недостаточно прав для удаления…». Тем
     *              же ответом он отвечает и на повторное удаление лежащего в
     *              корзине, и на настоящий отказ по правам: какой из двух
     *              случаев произошёл, по ответу амо установить невозможно
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function leads(int|array $ids): bool
    {
        return $this->deleteViaMultiple('leads', $ids);
    }

    /**
     * Удаление в корзину, семантика и возврат — как у leads().
     *
     * @param  int|list<int>  $ids
     * @return bool false — «Недостаточно прав для удаления…»: неотличимо от
     *              «уже в корзине», см. leads()
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function contacts(int|array $ids): bool
    {
        return $this->deleteViaMultiple('contacts', $ids);
    }

    /**
     * Удаление в корзину, семантика и возврат — как у leads().
     *
     * @param  int|list<int>  $ids
     * @return bool false — «Недостаточно прав для удаления…»: неотличимо от
     *              «уже в корзине», см. leads()
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function companies(int|array $ids): bool
    {
        return $this->deleteViaMultiple('companies', $ids);
    }

    /**
     * Одним запросом на пачку. Ответ несёт собственный `errors[]` внутри
     * HTTP 200 — частичный успех тут выражается телом, а не кодом.
     *
     * @param  int|list<int>  $ids
     * @return bool false — все ошибки ответа с `code=404` («уже нет»);
     *              смешанный набор ошибок — бросок, а не тихий частичный успех
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function customers(int|array $ids): bool
    {
        return $this->deleteViaSet(
            'customers',
            '/ajax/v1/customers/set/',
            $this->ids($ids, 'delete customers'),
            true,
            'delete customers'
        );
    }

    /**
     * Каскадом уносит элементы каталога (§2).
     *
     * По одному запросу на каталог: скалярная форма `request[catalogs][delete]`
     * — единственная работающая. Списочная снята отдельным зондом (§8) и
     * роняет бэкенд амо: HTTP 500 с HTML-страницей вместо json. Пачку сюда
     * не собирать, сколько бы запросов это ни стоило.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо вернул `code=404` («каталога уже нет»)
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function catalogs(int|array $ids): bool
    {
        $confirmed = true;

        foreach ($this->ids($ids, 'delete catalogs') as $id) {
            $confirmed = $this->deleteViaSet(
                'catalogs',
                '/ajax/v1/catalogs/set/',
                $id,
                false,
                'delete catalogs'
            ) && $confirmed;
        }

        return $confirmed;
    }

    /**
     * Точечное удаление элемента списка — отдельный эндпойнт, а не вложенность
     * в `/ajax/v1/catalogs/set/` (§7.1). Каталог указывать не нужно, поэтому
     * элемент сносится по одному id, без похода за родителем.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо вернул `code=404` («элемента уже нет»)
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function catalogElements(int|array $ids): bool
    {
        return $this->deleteViaSet(
            'catalog_elements',
            '/ajax/v1/catalog_elements/set/',
            $this->ids($ids, 'delete catalogElements'),
            false,
            'delete catalogElements'
        );
    }

    /**
     * Публичного удаления задач у амо нет: v4 `DELETE tasks/{id}` отдаёт 403
     * «Invalid scope», ajax-аналог `multiple/delete` — 404 (§2). Работает
     * только приватный роут — ярус semver третий, см. шапку класса.
     *
     * Подтверждено официально (§9): справочник амо описывает у задач только
     * GET / POST / PATCH — метода DELETE там нет вовсе. Приватный роут здесь
     * не обходной манёвр в обход публичного пути, а единственный существующий.
     *
     * По одному запросу на задачу: роут принимает скалярный `ID`.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 400 `{"status":"fail","id":N}`,
     *              то есть задачи уже нет (§8)
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function tasks(int|array $ids): bool
    {
        return $this->deleteViaPrivateNotes($this->ids($ids, 'delete tasks'), 'TASK_DELETE', 'delete tasks');
    }

    /**
     * Публичного удаления примечаний нет: v4 `DELETE {entity}/{id}/notes/{id}`
     * отдаёт 405 (§2). Работает только приватный роут — ярус semver третий.
     *
     * Подтверждено официально (§9): справочник амо описывает у примечаний
     * только GET / POST / PATCH — метода DELETE там нет вовсе, как и у задач.
     *
     * Родителя указывать не нужно, амо резолвит его по id примечания (§6).
     * Практическое следствие: `$amo->leads->notes->delete($id)` снесёт и
     * примечание контакта — путь, которым получена коллекция, на удаление не
     * влияет. Это свойство роута, а не недосмотр.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 200 `{"status":"no note","id":N}`,
     *              то есть примечания уже нет (§8.6). Обратите внимание: это
     *              НЕ та форма, что у задач (там 400 + `status:fail`) —
     *              один роут отвечает на соседних типах по-разному
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function notes(int|array $ids): bool
    {
        return $this->deleteViaPrivateNotes($this->ids($ids, 'delete notes'), 'NOTE_DELETE', 'delete notes');
    }

    /**
     * Звонок в амо — примечание особого типа, поэтому глагол тот же
     * NOTE_DELETE, что и у примечаний (§7.6, зонд 316925779). Роута
     * `DELETE /calls/{id}` не существует вовсе (§2) — ярус semver третий.
     *
     * Удаляется ровно сама запись звонка. Сущность, к которой амо привязал
     * звонок по совпадению телефона (боевая компания в спайке), не трогается.
     *
     * Что звонок — именно примечание, теперь подтверждено эмпирикой, а не
     * документацией: на пути удаления он ведёт себя как примечание и в успехе,
     * и в повторе (`no note`), а не как самостоятельный тип (§8.6).
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 200 `{"status":"no note","id":N}`,
     *              то есть звонка уже нет (§8.6); у задач та же ситуация
     *              выглядит иначе — 400 + `status:fail`
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function calls(int|array $ids): bool
    {
        return $this->deleteViaPrivateNotes($this->ids($ids, 'delete calls'), 'NOTE_DELETE', 'delete calls');
    }

    /**
     * Единственный тип, где UI ходит в приватный ajax без нужды: публичный v4
     * умеет то же самое и отдаёт 204 (§6). Приватный канал подключаем только
     * там, где публичного механизма нет, иначе завязываемся на нестабильный
     * контракт задаром.
     *
     * ШЕСТОЕ ЛИЦО «УЖЕ НЕТ» — здесь оно не 404, а HTTP 400 с телом
     * `{"validation-errors":[{"errors":[{"code":"NotSupportedChoice",
     * "path":"id",…}]}],"detail":"Request validation failed"}`. Амо валидирует
     * `id` против списка существующих воронок, и отсутствующая не «не найдена»,
     * а «не входит в допустимые варианты». Пока это не распознавалось, teardown
     * получал исключение и ретраил снос девять раз подряд, ни разу не пройдя.
     *
     * Полный набор ответов роута (§9.10): **204** — удалено; **400
     * NotSupportedChoice** по `path: id` — объекта нет; **422** — объект есть,
     * но удалить нельзя (непустая воронка). Последний остаётся громким.
     *
     * @param  int|list<int>  $ids
     * @return bool false — воронки НЕТ: либо 404, либо 400 NotSupportedChoice.
     *              Единственный тип, где `false` означает именно отсутствие
     *              объекта, а не общее «амо не подтвердил»: запрет удаления амо
     *              сообщает отдельным кодом 422, и он сюда не попадает
     *
     * @throws AmoCustomException
     */
    public function pipelines(int|array $ids): bool
    {
        $confirmed = true;

        foreach ($this->ids($ids, 'delete pipelines') as $id) {
            $confirmed = $this->deleteViaApi(
                "leads/pipelines/{$id}",
                [],
                $this->pipelineIdIsNotAChoice(...)
            ) && $confirmed;
        }

        return $confirmed;
    }

    /**
     * `NotSupportedChoice` по пути `id` в ответе 400 — форма, которой амо
     * сообщает, что воронки с таким id среди существующих нет (§9.9).
     *
     * ДОКАЗАТЕЛЬСТВО, что это «объекта нет», а не «удалить нельзя»: контрольный
     * запрос на заведомо несуществующий `id=999999999` даёт побайтово тот же
     * ответ, а обеих воронок из хвоста прогона в аккаунте не было — список
     * `/leads/pipelines` их не содержал.
     *
     * ЛОВУШКА, из-за которой матчим по связке ВСЕХ ТРЁХ признаков. По форме это
     * обычная ошибка валидации — тот же конверт, в котором приходит настоящий
     * некорректный запрос, а такие мы условились считать честной ошибкой.
     * Различает их контекст операции: при удалении единственный параметр — сам
     * идентификатор, поэтому «недопустимый выбор» для `path: id` не может
     * значить ничего, кроме отсутствия объекта. Тот же код по другому пути или
     * без пути — не наш случай и остаётся громким.
     *
     * Что это необходимость, а не паранойя, показал второй случай: тот же
     * `NotSupportedChoice` амо отдаёт при СОЗДАНИИ ЛИДА со `status_id`, не
     * принадлежащим воронке (§9.10). Код универсальный и означает «значение вне
     * допустимого набора» — что угодно, только не «объекта нет». Распознавать
     * по нему в отрыве от операции и пути нельзя ни при каких обстоятельствах;
     * ровно поэтому распознаватель передаётся параметром в `deleteViaApi`, а не
     * зашит внутрь него.
     *
     * ВОРОНКА, КОТОРУЮ УДАЛИТЬ НЕЛЬЗЯ, ОТВЕЧАЕТ ИНАЧЕ — HTTP 422 («Only
     * pipelines without leads…», §9.10). Сюда она не попадает и остаётся
     * громкой ошибкой, как и должна. Это единственный за всё исследование
     * случай, когда амо РАЗЛИЧАЕТ «уже нет» и «нельзя удалить», а не сливает их
     * в один ответ, как у сделок с «Недостаточно прав». Поэтому здесь `false`
     * означает именно «объекта нет», а не общее «не подтверждено», — и 422 в
     * этот распознаватель добавлять нельзя, сколь бы похожим он ни казался.
     */
    private function pipelineIdIsNotAChoice(RequestException $e): bool
    {
        if ($e->response->status() !== 400) {
            return false;
        }

        $body = $e->response->json();

        if (! is_array($body)) {
            return false;
        }

        foreach ($this->validationErrors($body) as $error) {
            if (! is_array($error)) {
                continue;
            }

            if (($error['code'] ?? null) === 'NotSupportedChoice' && ($error['path'] ?? null) === 'id') {
                return true;
            }
        }

        return false;
    }

    /**
     * Плоский список `validation-errors[*].errors[*]` из ответа амо.
     *
     * @param  array<mixed>  $body
     * @return list<mixed>
     */
    private function validationErrors(array $body): array
    {
        $groups = $body['validation-errors'] ?? null;

        if (! is_array($groups)) {
            return [];
        }

        $result = [];

        foreach ($groups as $group) {
            $errors = is_array($group) ? ($group['errors'] ?? null) : null;

            if (! is_array($errors)) {
                continue;
            }

            foreach ($errors as $error) {
                $result[] = $error;
            }
        }

        return $result;
    }

    /**
     * Публичный v4 `DELETE sources/{id}`.
     *
     * @param  int|list<int>  $ids
     * @return bool false — источника уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function sources(int|array $ids): bool
    {
        $confirmed = true;

        foreach ($this->ids($ids, 'delete sources') as $id) {
            $confirmed = $this->deleteViaApi("sources/{$id}") && $confirmed;
        }

        return $confirmed;
    }

    /**
     * Публичный v4 `DELETE webhooks` с destination в теле — настоящий hard
     * delete, а не отключение (проверено: после вызова `find()` пуст, §2).
     *
     * Вебхук адресуется destination'ом, а не id: id в ответе `subscribe()`
     * есть, но роут удаления его не принимает. Отсюда единственная в этом
     * классе строковая сигнатура — она обязана такой остаться.
     *
     * @param  string|list<string>  $destinations
     * @return bool false — подписки уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function webhooks(string|array $destinations): bool
    {
        $confirmed = true;

        foreach ($this->destinations($destinations) as $destination) {
            $confirmed = $this->deleteViaApi('webhooks', ['destination' => $destination]) && $confirmed;
        }

        return $confirmed;
    }

    /**
     * `/ajax/{тип}/multiple/delete/` — то же, что шлёт кнопка «Удалить» в
     * карточке амо (§7.2).
     *
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    private function deleteViaMultiple(string $entity, int|array $ids): bool
    {
        $operation = "delete {$entity}";
        $list = $this->ids($ids, $operation);

        if ($list === []) {
            return true;
        }

        $response = $this->post("/ajax/{$entity}/multiple/delete/", ['ID' => $list], false);

        $status = $response['status'] ?? null;

        if ($status === 'success') {
            return true;
        }

        $errors = $response['errors'] ?? null;

        if ($status === 'fail' && is_array($errors) && $this->allErrorsAreAlreadyGone($errors)) {
            return false;
        }

        throw new AmoUnknownException(
            $operation,
            'ожидался status success либо отказ «'.self::ALREADY_GONE_MARKER.'…»',
            $response
        );
    }

    /**
     * Семейство `/ajax/v1/{раздел}/set/`: успех и частичный успех живут внутри
     * HTTP 200, в `response.{раздел}.delete.errors[]`.
     *
     * Непустой `errors[]` по умолчанию громкий (решение по архитектуре, п. 12).
     * Единственное исключение — набор, целиком состоящий из `code=404`: это
     * «сущности уже нет», эмпирика зафиксирована в тестах (`Error 282.`,
     * tests/BaseAmoClient.php).
     *
     * @param  int|list<int>  $deletePayload  форма зависит от раздела: скаляр у catalogs, список у остальных
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    private function deleteViaSet(string $section, string $url, int|array $deletePayload, bool $asJson, string $operation): bool
    {
        if ($deletePayload === []) {
            return true;
        }

        $response = $this->post($url, ['request' => [$section => ['delete' => $deletePayload]]], $asJson);

        $delete = $this->nested($response, 'response', $section, 'delete');
        $errors = is_array($delete) ? ($delete['errors'] ?? null) : null;

        if (! is_array($errors)) {
            throw new AmoUnknownException(
                $operation,
                "в ответе нет response.{$section}.delete.errors",
                $response
            );
        }

        if ($errors === []) {
            return true;
        }

        if ($this->allErrorsAreNotFound($errors)) {
            return false;
        }

        throw new AmoUnknownException($operation, 'амо вернул ошибки удаления', $response);
    }

    /**
     * Приватный `/private/notes/edit2.php`. Минимальный контракт снят с живого
     * аккаунта: ID + ACTION, родитель не нужен (§6). Успех — HTTP 200
     * `{"status":"ok","id":N}`.
     *
     * «Уже нет» в этом канале имеет ДВА вида, и оба обязаны быть здесь: задача
     * отвечает HTTP 400 + `status:fail`, примечание и звонок — HTTP 200 +
     * `status:"no note"` (§8.6). Знать одно из двух — значит громко падать на
     * идемпотентном повторе половины типов.
     *
     * Роут скалярный, поэтому пачка — это N запросов. Если бросок случился на
     * i-м id, предыдущие уже удалены: откатывать нечего и не нужно, но
     * рассчитывать на «всё или ничего» нельзя.
     *
     * @param  list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    private function deleteViaPrivateNotes(array $ids, string $action, string $operation): bool
    {
        $confirmed = true;

        foreach ($ids as $id) {
            try {
                $response = $this->ajax->postForm(self::PRIVATE_NOTES_ENDPOINT, ['ID' => $id, 'ACTION' => $action]);
            } catch (RequestException $e) {
                /*
                 * «Уже нет» приходит в этом канале ОШИБОЧНЫМ СТАТУСОМ, а не
                 * полем в теле двухсотки (§8), поэтому распознаём до того, как
                 * обернём исключение: после AmoCustomException тело уже не
                 * разобрать структурно. Текста в ответе нет вовсе — только эхо
                 * id, так что матч по строке здесь физически невозможен.
                 */
                if ($this->isPrivateAlreadyGone($e, $id)) {
                    $confirmed = false;

                    continue;
                }

                throw new AmoCustomException($e);
            } catch (ConnectionException $e) {
                throw new AmoCustomException($e);
            }

            $status = $response['status'] ?? null;
            $echo = $response['id'] ?? null;

            /*
             * ВТОРОЕ лицо «уже нет» в этом же канале, снято зондом (§8.6):
             * примечание и звонок на повторное удаление отвечают УСПЕШНЫМ
             * статусом со словом no note, а задача — ошибочным 400. Один роут,
             * один глагол, соседние типы, два несовместимых представления.
             * Классификатор, знающий одно из двух, ломает идемпотентность на
             * другом и делает это молча: бросок выглядит как честная ошибка.
             *
             * Эхо id требуем так же строго, как на 400: «no note» с чужим или
             * отсутствующим id проваливается ниже, в громкий бросок.
             */
            if ($status === self::PRIVATE_NO_NOTE_STATUS && $this->echoMatches($echo, $id)) {
                $confirmed = false;

                continue;
            }

            if ($status !== 'ok') {
                throw new AmoUnknownException(
                    $operation,
                    'ожидался status ok либо «'.self::PRIVATE_NO_NOTE_STATUS."» на {$action} id={$id}",
                    $response
                );
            }

            /*
             * Round-trip: амо эхом возвращает id удалённого. Сверяем — это
             * единственная здесь защита от семантического дрейфа «ответ
             * распарсился, удалено не то». Отсутствие поля не считаем ошибкой:
             * контракт держится на status, эхо — приятное дополнение.
             */
            if (is_numeric($echo) && (int) $echo !== $id) {
                throw new AmoUnknownException($operation, "амо подтвердил удаление другого id (просили {$id})", $response);
            }
        }

        return $confirmed;
    }

    /**
     * Эхо id из ответа совпадает с запрошенным.
     *
     * Требуется строго там, где по эху решается «уже нет»: лучше громко упасть
     * на незнакомом ответе, чем принять чужую ошибку за идемпотентный повтор.
     */
    private function echoMatches(mixed $echo, int $id): bool
    {
        return is_numeric($echo) && (int) $echo === $id;
    }

    /**
     * ПЕРВОЕ лицо «уже нет» у приватного роута: связка (400, status=fail, эхо
     * id совпадает с запрошенным), §8.1. Так отвечает удаление ЗАДАЧИ; у
     * примечания и звонка тот же факт выглядит иначе — см. второе лицо в
     * deleteViaPrivateNotes().
     *
     * Эхо требуем строго. 400 без него — не наш случай: лучше громко упасть на
     * незнакомой четырёхсотке, чем принять за идемпотентный повтор чужую
     * ошибку запроса. Не-json тело (амо отдаёт HTML на своих пятисотках) сюда
     * просто не проходит — json() вернёт не массив.
     */
    private function isPrivateAlreadyGone(RequestException $e, int $id): bool
    {
        if ($e->response->status() !== 400) {
            return false;
        }

        $body = $e->response->json();

        if (! is_array($body) || ($body['status'] ?? null) !== 'fail') {
            return false;
        }

        return $this->echoMatches($body['id'] ?? null, $id);
    }

    /**
     * Публичный v4 DELETE. 404 — не ошибка, а «сущности уже нет»: повторный
     * снос обязан быть идемпотентным, иначе второй прогон teardown ложно падает.
     *
     * `$alsoAlreadyGone` — дополнительный распознаватель «уже нет» для тех
     * типов, у которых амо отвечает не 404: у воронок это 400 с
     * `NotSupportedChoice`. Передаётся точечно, а не зашит здесь, чтобы чужая
     * четырёхсотка у sources и webhooks осталась громкой.
     *
     * @param  array<string, mixed>  $data
     * @param  (callable(RequestException): bool)|null  $alsoAlreadyGone
     *
     * @throws AmoCustomException
     */
    private function deleteViaApi(string $path, array $data = [], ?callable $alsoAlreadyGone = null): bool
    {
        try {
            $this->http->delete($path, $data)->throw();

            return true;
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return false;
            }

            if ($alsoAlreadyGone !== null && $alsoAlreadyGone($e)) {
                return false;
            }

            throw new AmoCustomException($e);
        } catch (ConnectionException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * Не-json тело (амо отдаёт HTML-страницу на своих пятисотках, §8)
     * переживается здесь бесплатно: до разбора дело не доходит, `->throw()`
     * бросает раньше, а AmoCustomException при невалидном json падбэчится на
     * getMessage() вместо того, чтобы уронить парсер поверх настоящей ошибки.
     *
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    private function post(string $url, array $data, bool $asJson): array
    {
        try {
            return $asJson ? $this->ajax->postJson($url, $data) : $this->ajax->postForm($url, $data);
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @param  array<mixed>  $errors
     */
    private function allErrorsAreAlreadyGone(array $errors): bool
    {
        if ($errors === []) {
            return false;
        }

        foreach ($errors as $error) {
            if (! is_string($error) || ! str_contains($error, self::ALREADY_GONE_MARKER)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<mixed>  $errors
     */
    private function allErrorsAreNotFound(array $errors): bool
    {
        if ($errors === []) {
            return false;
        }

        foreach ($errors as $error) {
            $code = is_array($error) ? ($error['code'] ?? null) : null;

            if (! is_numeric($code) || (int) $code !== 404) {
                return false;
            }
        }

        return true;
    }

    /**
     * Навигация по вложенному ответу амо: json() типизирован как mixed, поэтому
     * каждый уровень гардим is_array вместо каста.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>|null
     */
    private function nested(array $data, string ...$path): ?array
    {
        $current = $data;

        foreach ($path as $key) {
            if (! is_array($current) || ! array_key_exists($key, $current)) {
                return null;
            }

            $current = $current[$key];
        }

        return is_array($current) ? $current : null;
    }

    /**
     * Нечисловой id — ошибка вызывающего, а не амо: падаем сразу, не отправив
     * запрос, чтобы не удалить «0» вместо реального идентификатора.
     *
     * @param  int|string|array<mixed>  $ids
     * @return list<int>
     */
    private function ids(int|string|array $ids, string $operation): array
    {
        $result = [];

        foreach (is_array($ids) ? $ids : [$ids] as $id) {
            if (! is_numeric($id)) {
                throw new InvalidArgumentException(sprintf(
                    '%s: ожидался числовой id, получен %s.',
                    $operation,
                    get_debug_type($id)
                ));
            }

            $result[] = (int) $id;
        }

        return $result;
    }

    /**
     * @param  int|string|array<mixed>  $destinations
     * @return list<string>
     */
    private function destinations(int|string|array $destinations): array
    {
        $result = [];

        foreach (is_array($destinations) ? $destinations : [$destinations] as $destination) {
            if (! is_string($destination) || $destination === '') {
                throw new InvalidArgumentException(sprintf(
                    'delete webhooks: вебхук адресуется непустым destination, получен %s.',
                    get_debug_type($destination)
                ));
            }

            $result[] = $destination;
        }

        return $result;
    }
}
