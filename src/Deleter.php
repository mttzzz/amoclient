<?php

namespace mttzzz\AmoClient;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnexpectedResponseException;

/**
 * Единственное место, где живёт таблица «тип сущности → механизм удаления»
 * (docs/research/amo-delete-mechanisms.md, §7.6).
 *
 * Модельные `$amo->{коллекция}->delete()` и entity-level
 * `Entities\Source::delete()` / `Entities\Webhook::unSubscribe()` — делегации
 * сюда, а не собственные реализации. Причина не в эстетике: две реализации
 * одной логики, обязанные оставаться синхронными, — это ровно та форма дрейфа,
 * которая уже развела транспортный retry-колбэк и `RetriesTransientAmoErrors`
 * по охвату (решение по архитектуре 4.0, п. 9).
 *
 * ЯРУС SEMVER. Часть операций ниже ходит в приватные роуты амо
 * (`/private/notes/edit2.php`, `/ajax/**`) — недокументированные и способные
 * поменяться без предупреждения. По ним библиотека обещает НЕ «работает
 * всегда», а «ломается громко и чинится patch-ом»: изменившийся ответ даёт
 * AmoUnexpectedResponseException с сырым телом, а не тихий неверный результат.
 * Публичный v4 (sources, pipelines, webhooks) живёт по обычным правилам.
 *
 * СЕМАНТИКА ВОЗВРАТА, единая для всех методов:
 * - `true`  — амо подтвердил удаление;
 * - `false` — амо явно отказал по известной причине «сущности уже нет»
 *             (детали у конкретного метода). Это делает повторный снос
 *             идемпотентным: второй прогон teardown не падает;
 * - бросок  — HTTP-ошибка (AmoCustomException) либо нераспознанный ответ
 *             (AmoUnexpectedResponseException). Молчаливого «ну наверное
 *             удалилось» нет ни в одной ветке.
 *
 * Пустой список id — `true` без запроса: удалить ничего успешно можно.
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
     * §7.4: амо отдаёт этот текст И на реальный отказ по правам, И на попытку
     * удалить то, что уже лежит в корзине. Проверено на трёх лидах под
     * админ-сессией владельца: удаление свежеудалённого даёт именно его.
     * Различить два случая по ответу НЕЛЬЗЯ — амо здесь дезинформирует.
     * Поэтому текст трактуется как «уже нет» ТОЛЬКО внутри операции удаления
     * (решение по архитектуре 4.0, п. 2: глобальное правило по тексту
     * запрещено — оно маскировало бы реальный пермишн-фейл).
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
     * Диспетчер для свипа: у реестра на руках пара (тип, id), а не готовая
     * коллекция. Родитель примечания и каталог элемента здесь не нужны —
     * приватные роуты резолвят их сами по id сущности (§6).
     *
     * @param  int|string|array<mixed>  $ids
     *
     * @throws InvalidArgumentException неизвестный тип — громко, не тихий no-op
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
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
     * пропадает из обычных выборок, но остаётся в аккаунте с `is_deleted=true`
     * и находится через `leads()->withOnlyDeleted()`. Purge недоступен ни в UI,
     * ни в публичном v4, ни в приватном ajax — обещать hard delete было бы
     * враньём. Модалка амо, обещающая «восстановить невозможно», врёт (§7.3).
     *
     * Одним запросом на всю пачку.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил «Недостаточно прав для удаления…»,
     *              что по §7.4 неотличимо от «уже в корзине»
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    public function leads(int|array $ids): bool
    {
        return $this->deleteViaMultiple('leads', $ids);
    }

    /**
     * Удаление в корзину, семантика и возврат — как у leads().
     *
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    public function contacts(int|array $ids): bool
    {
        return $this->deleteViaMultiple('contacts', $ids);
    }

    /**
     * Удаление в корзину, семантика и возврат — как у leads().
     *
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
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
     * @throws AmoUnexpectedResponseException
     */
    public function customers(int|array $ids): bool
    {
        return $this->deleteViaSet(
            'customers',
            '/ajax/v1/customers/set/',
            array_values($this->ids($ids, 'delete customers')),
            true,
            'delete customers'
        );
    }

    /**
     * Каскадом уносит элементы каталога (§2).
     *
     * По одному запросу на каталог: скалярная форма `request[catalogs][delete]`
     * — единственная эмпирически проверенная. Списочную не отправляем, пока она
     * не снята с живого аккаунта: выдуманная форма даёт «Код ошибки 222» и
     * молча ничего не удаляет.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо вернул `code=404` («каталога уже нет»)
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
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
     * @throws AmoUnexpectedResponseException
     */
    public function catalogElements(int|array $ids): bool
    {
        return $this->deleteViaSet(
            'catalog_elements',
            '/ajax/v1/catalog_elements/set/',
            array_values($this->ids($ids, 'delete catalogElements')),
            false,
            'delete catalogElements'
        );
    }

    /**
     * Публичного удаления задач у амо нет: v4 `DELETE tasks/{id}` отдаёт 403
     * «Invalid scope», ajax-аналог `multiple/delete` — 404 (§2). Работает
     * только приватный роут — ярус semver третий, см. шапку класса.
     *
     * По одному запросу на задачу: роут принимает скалярный `ID`.
     *
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    public function tasks(int|array $ids): bool
    {
        return $this->deleteViaPrivateNotes($this->ids($ids, 'delete tasks'), 'TASK_DELETE', 'delete tasks');
    }

    /**
     * Публичного удаления примечаний нет: v4 `DELETE {entity}/{id}/notes/{id}`
     * отдаёт 405 (§2). Работает только приватный роут — ярус semver третий.
     *
     * Родителя указывать не нужно, амо резолвит его по id примечания (§6).
     * Практическое следствие: `$amo->leads->notes->delete($id)` снесёт и
     * примечание контакта — путь, которым получена коллекция, на удаление не
     * влияет. Это свойство роута, а не недосмотр.
     *
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
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
     * @param  int|list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
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
     * @param  int|list<int>  $ids
     * @return bool false — воронки уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function pipelines(int|array $ids): bool
    {
        $confirmed = true;

        foreach ($this->ids($ids, 'delete pipelines') as $id) {
            $confirmed = $this->deleteViaApi("leads/pipelines/{$id}") && $confirmed;
        }

        return $confirmed;
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
     * есть, но роут удаления его не принимает.
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
     * @throws AmoUnexpectedResponseException
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

        throw new AmoUnexpectedResponseException(
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
     * @throws AmoUnexpectedResponseException
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
            throw new AmoUnexpectedResponseException(
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

        throw new AmoUnexpectedResponseException($operation, 'амо вернул ошибки удаления', $response);
    }

    /**
     * Приватный `/private/notes/edit2.php`. Минимальный контракт снят с живого
     * аккаунта: ID + ACTION, родитель не нужен (§6). Ответ `{"status":"ok","id":N}`.
     *
     * Роут скалярный, поэтому пачка — это N запросов. Если бросок случился на
     * i-м id, предыдущие уже удалены: откатывать нечего и не нужно, но
     * рассчитывать на «всё или ничего» нельзя.
     *
     * @param  list<int>  $ids
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    private function deleteViaPrivateNotes(array $ids, string $action, string $operation): bool
    {
        foreach ($ids as $id) {
            $response = $this->post(self::PRIVATE_NOTES_ENDPOINT, ['ID' => $id, 'ACTION' => $action], false);

            if (($response['status'] ?? null) !== 'ok') {
                throw new AmoUnexpectedResponseException($operation, "ожидался status ok на {$action} id={$id}", $response);
            }

            /*
             * Round-trip: амо эхом возвращает id удалённого. Сверяем — это
             * единственная здесь защита от семантического дрейфа «ответ
             * распарсился, удалено не то». Отсутствие поля не считаем ошибкой:
             * контракт держится на status, эхо — приятное дополнение.
             */
            $echo = $response['id'] ?? null;

            if (is_numeric($echo) && (int) $echo !== $id) {
                throw new AmoUnexpectedResponseException($operation, "амо подтвердил удаление другого id (просили {$id})", $response);
            }
        }

        return true;
    }

    /**
     * Публичный v4 DELETE. 404 — не ошибка, а «сущности уже нет»: повторный
     * снос обязан быть идемпотентным, иначе второй прогон teardown ложно падает.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AmoCustomException
     */
    private function deleteViaApi(string $path, array $data = []): bool
    {
        try {
            $this->http->delete($path, $data)->throw();

            return true;
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return false;
            }

            throw new AmoCustomException($e);
        } catch (ConnectionException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
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
