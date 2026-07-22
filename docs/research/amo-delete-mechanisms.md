# Task 4 spike: механизмы удаления сущностей amoCRM (эмпирика)

Аккаунт: `aId=16117840`, `clientId=00a140c1-7c52-4563-8b36-03f23754d255` (боевой). Все зонды — с префиксом `sp0-spike-`.

Статус: **ЗАВЕРШЕНО**. 10 зондов создано, все либо удалены/переведены в корзину, либо не требовали действия (см. §4). `git status --porcelain` в `amoclient` чист (временный `tests/Spike/DeleteProbeTest.php` удалён после последнего прогона).

## 1. Таблица «тип → механизм удаления»

| Тип | Механизм | Как проверено | Статус |
|---|---|---|---|
| leads | `ajax->postForm('/ajax/leads/multiple/delete/', ['ID'=>[$id]])` | известно из тестов, доп. проверка корзины в §3 | подтверждено (существующие тесты + доп. зонд) |
| contacts | `ajax->postForm('/ajax/contacts/multiple/delete/', ['ID'=>[$id]])` | известно из тестов, доп. проверка в §3 | подтверждено |
| companies | `ajax->postForm('/ajax/companies/multiple/delete/', ['ID'=>[$id]])` | известно из тестов, доп. проверка в §3 | подтверждено |
| customers | `ajax->postJson('/ajax/v1/customers/set/', ['request'=>['customers'=>['delete'=>[$id]]]])` | известно из тестов (не перепроверялось — не в скоупе спайка) | подтверждено (существующие тесты) |
| catalogs | `ajax->postForm('/ajax/v1/catalogs/set/', ['request'=>['catalogs'=>['delete'=>$id]]])` | известно из тестов; доп. подтверждено как cleanup в зонде catalogElements (catalog 6869) | подтверждено |
| sources | `$amo->sources->find($id)->delete()` (v4 `DELETE /api/v4/sources/{id}`) | известно из тестов (не перепроверялось) | подтверждено (существующие тесты) |
| **tasks** | **нет публичного удаления.** Единственный принимаемый write — `update()` с `is_completed=true` (косметика). Практический teardown: удалить родительский lead. | `test_probe_task_delete` | подтверждено (негативный результат) |
| **notes** | **нет публичного удаления.** Практический teardown: удалить родительский lead (notes уходят вместе с ним). | `test_probe_note_delete` | подтверждено (негативный результат) |
| **catalogElements** | **прямого удаления не нашли.** Практический teardown: удалить родительский catalog (каскад подтверждён). | `test_probe_catalog_element_delete` | подтверждено частично |
| **webhooks** | `entity->unSubscribe()` — `DELETE /api/v4/webhooks` с `{"destination": ...}` в теле. **Настоящий hard delete.** | `WebhookTest` (известно) + доп. `test_probe_webhook_delete` | подтверждено |
| **shortLinks** | **неудаляемо публично** (в ответе `create()` нет `id`). Ничего трекать не нужно — удаление parent-сущности достаточно. | `test_probe_short_link_delete` | подтверждено (негативный результат) |
| **unsorted** | **неудаляемо публично.** `decline()`/`accept()` — единственные переходы. `decline()` порождает настоящий lead, который надо трекать и удалять отдельно. | `test_probe_unsorted_delete` | подтверждено (негативный результат + побочный эффект) |
| **calls** | **неудаляемо публично.** Роута `DELETE /calls/{id}` не существует вообще. | `test_probe_call_delete` | подтверждено (негативный результат) |

Жирным — то, что устанавливал этот спайк. Остальное было известно заранее из существующих тестов (не перепроверялось, кроме доп. проверок, отмеченных в колонке «Как проверено»).

## 2. Сырые ответы по типам (копипастопригодные вызовы + суть ответа)

### tasks — нет публичного удаления

```php
// Candidate A: v4 raw DELETE
$this->amoClient->http->delete("tasks/{$taskId}");
// → 403 Forbidden: {"title":"Forbidden","type":"https://httpstatus.es/403","status":403,"detail":"Invalid scope"}

// Candidate B: ajax bulk-delete (по аналогии с leads/contacts/companies) — роута не существует
$this->amoClient->ajax->postForm('/ajax/tasks/multiple/delete/', ['ID' => [$taskId]]);
// → 404, HTML-страница "Здесь ничего нет, только бескрайний космос" (amo generic 404, не JSON)

// Candidate C (принят, но это не delete): пометка выполненной
$taskEntity = $this->amoClient->tasks->find($taskId);
$taskEntity->is_completed = true;
$taskEntity->result = ['text' => 'sp0-spike-closed'];
$taskEntity->update();
// → 200, задача помечена is_completed=true, но продолжает существовать и быть выбираемой по id
```

**Вывод:** 403 "Invalid scope" (не 404) означает, что сам эндпойнт, вероятно, существует в API, но текущий OAuth-виджет не имеет прав на него (или для задач delete-скоуп в принципе не выдаётся сторонним интеграциям) — в любом случае для этой библиотеки/аккаунта недоступен. Teardown полагается на удаление родительского lead.

### notes — нет публичного удаления

```php
$this->amoClient->http->delete("leads/{$leadId}/notes/{$noteId}");
// → 405 Method Not Allowed, пустое тело
```

405 (а не 403/404) — метод буквально не разрешён на этом роуте, это не вопрос scope. Teardown полагается на удаление родительского lead.

### catalogElements — прямого удаления нет, каскад через catalog есть

```php
// Candidate A: v4 raw DELETE
$this->amoClient->http->delete("catalogs/{$catalogId}/elements/{$elementId}");
// → 405 Method Not Allowed, пустое тело

// Candidate B: ajax set/-подобный вызов (по аналогии с catalogs delete)
$this->amoClient->ajax->postJson('/ajax/v1/catalogs/set/', [
    'request' => ['catalogs' => [$catalogId => ['elements' => ['delete' => [$elementId]]]]],
]);
// → 400: {"response":{"error":"Код ошибки 222. В случае повторного возникновения ошибки,
//    обращайтесь в нашу техническую поддержку - support@amocrm.ru","error_code":"222"}}
// (амо не распознало форму запроса — гипотеза про вложенность неверна, правильная форма не найдена)

// Работающий cleanup — удаление всего каталога (тот же вызов, что и для самих catalogs):
$this->amoClient->ajax->postForm('/ajax/v1/catalogs/set/', ['request' => ['catalogs' => ['delete' => $catalogId]]]);
// → 200: {"response":{"catalogs":{"delete":{"catalogs":{"6869":{"id":6869,"name":"sp0-spike-catalog"}},"errors":[]}}}}
// Element 1772361 внутри catalog 6869 ушёл вместе с ним (без отдельной ошибки).
```

### webhooks — подтверждённый hard delete

```php
$destination = 'https://example.com/sp0-spike-webhook-<uid>';
$entity = $this->amoClient->webhooks->entity($destination)->addLead();
$entity->subscribe();
// → 200: {"id":48162870,"destination":"...","settings":{"add_lead":1}, ...}

$this->amoClient->webhooks->find($destination);
// → найден: {"id":48162870, "settings":["add_lead"], ...}

$entity->unSubscribe();
// → null (204/200 без тела)

$this->amoClient->webhooks->find($destination);
// → [] (пусто) — подтверждён hard delete, не просто disable
```

### shortLinks — неудаляемо (нет id)

```php
$shortLink = $this->amoClient->shortLinks->entity()->url('https://ya.ru')->setContactId($contactId);
$shortLink->create();
// → {"_embedded":{"short_links":[{"url":"https://amo.sh/K/YNTCBS/YJMC10","account_id":16117840,
//     "metadata":{"entity_type":"contacts","entity_id":48892019}}]}}
// Ключей всего 3: url, account_id, metadata — id ОТСУТСТВУЕТ. Адресовать DELETE нечем.
```

### calls — неудаляемо (роута нет)

```php
$this->amoClient->http->delete("calls/{$callId}");
// → 404: {"title":"Not Found","type":"https://httpstatus.es/404","status":404,
//         "detail":"Cannot DELETE https://pushka.amocrm.ru/calls/316925483!"}
```

**⚠️ Важное побочное наблюдение:** созданный тестовый звонок (`phone('375296117699')`, тот же номер, что используется во всех `CallTest`-подобных тестах) amo автоматически привязал не к новой, а к **уже существующей** компании (`entity_id: 45389979, entity_type: "company"`) по совпадению телефона — это существующая сущность аккаунта, НЕ созданная этим спайком, и я её не трогал. Для будущего teardown-registry это значит: звонки с "общими" тестовыми телефонами могут молча приаттачиться к чужим (не только что созданным) сущностям — сам звонок это не создаёт новых удаляемых сущностей, но эвристика "удалить всё, что вернул create()" должна игнорировать `_embedded.entity`, если его id не совпадает с тем, что тест создавал сам.

### unsorted — неудаляемо, но decline() создаёт настоящий lead

```php
$sip = $this->amoClient->unsorted->sip();
$sip->source_name = 'sp0-spike-unsorted';
$sip->source_uid = 'sp0-spike-<uid>';
$sip->addMetadata(...);
$created = $sip->create();
$uid = $created['_embedded']['unsorted'][0]['uid'];

$this->amoClient->http->delete("unsorted/{$uid}");
// → 404: {"title":"Not Found", ..., "detail":"Cannot DELETE https://pushka.amocrm.ru/unsorted/{$uid}!"}

$this->amoClient->unsorted->decline($uid, 0);
// → 200: {"uid":"...", "pipeline_id":742990, "category":"sip",
//         "_embedded":{"leads":[{"id":33286105, ...}], "contacts":[]}}
// decline() породил РЕАЛЬНЫЙ lead (33286105) — не no-op! Нужно track('leads', 33286105)
// и удалить его тем же ajax-механизмом, что и обычные leads.
```

### leads/contacts/companies — корзина, не hard delete (см. §3)

```php
$id = $this->amoClient->leads->entity()->createGetId(); // sp0-spike-lead-trash → id=33286097
$this->amoClient->ajax->postForm('/ajax/leads/multiple/delete/', ['ID' => [$id]]);
// → {"status":"success","message":"Удаление прошло успешно для 1 сделки"}

$this->amoClient->leads->find($id)->toArray();
// → [] (пусто — обычный find не видит удалённые)

$this->amoClient->leads->withOnlyDeleted()->filterId($id)->get();
// → [{"id":33286097, "is_deleted":true, "updated_by":288614, "updated_at":..., "account_id":16117840, ...}]
// Сущность физически осталась в аккаунте, помечена is_deleted=true — это КОРЗИНА, не hard delete.
```

## 3. Корзина vs hard delete

**Вывод: ajax-`multiple/delete/` для leads/contacts/companies — soft delete (корзина), не физическое удаление.**

Проверено эмпирически на lead 33286097 (полный цикл: create → ajax-delete → find → withOnlyDeleted):
- Обычный `find($id)` после удаления возвращает `[]` — сущность не видна в штатных запросах.
- `leads->withOnlyDeleted()->filterId($id)->get()` **находит** запись с `is_deleted: true`, `account_id`, `updated_at` и полным `_links.self` — то есть запись физически жива в базе amo, просто скрыта из обычных выборок.

На contacts (48892015) и companies (48892017) проверено то же самое частично: `find()` после ajax-delete тоже возвращает `[]` (согласуется с lead-паттерном); `withOnlyDeleted()`-аналог для contacts/companies не проверялся отдельно (модели `Contact`/`Company` могли не иметь этого метода — не проверял, см. открытые вопросы), но нет оснований полагать, что механизм отличается от lead.

**Практическое следствие для teardown (Task 5):** teardown, реализованный через существующий ajax `multiple/delete/`, не оставляет "видимого" мусора в обычном интерфейсе/API-выборках amo (что и требовалось — не мешать реальной работе с аккаунтом), но НЕ уменьшает физически число записей в аккаунте — они уходят в 30-дневную (по документации amo, не проверялось эмпирически) корзину. Если для teardown критична именно **физическая** очистка (а не просто "не мешает"), этого публичный API не даёт — сущность нельзя пробить дальше корзины ни одним найденным вызовом.

## 4. Созданные зонды

| Тип | ID/UID | Статус |
|---|---|---|
| lead | 33286097 | ajax-удалён → в корзине (`is_deleted=true`), эмпирически НЕ hard-delete |
| contact | 48892015 | ajax-удалён → `find()` пуст (та же trash-семантика) |
| company | 48892017 | ajax-удалён → `find()` пуст (та же trash-семантика) |
| lead | 33286099 (сироту создал упавший до фикса зонд task-delete) | ajax-удалён отдельным cleanup-прогоном |
| task | 46711269 (дочерний к 33286099) | нет прямого delete; ушёл вместе с trashed-родителем |
| lead | 33286101 | ajax-удалён в конце `test_probe_task_delete` |
| task | 46711283 (дочерний к 33286101) | помечен `is_completed=true`; ушёл вместе с trashed-родителем |
| lead | 33286103 | ajax-удалён в конце `test_probe_note_delete` |
| note | 316925459 (дочерняя к 33286103) | нет прямого delete; ушла вместе с trashed-родителем |
| catalog | 6869 | ajax-удалён (`/ajax/v1/catalogs/set/`), 0 errors |
| catalogElement | 1772361 (дочерний к 6869) | нет прямого delete; каскадно ушёл вместе с catalog |
| webhook | id=48162870, destination=`https://example.com/sp0-spike-webhook-*` | `unSubscribe()` — **hard delete подтверждён** (`find()` пуст) |
| contact | 48892019 (для shortLink) | ajax-удалён в конце `test_probe_short_link_delete` |
| shortLink | без id (`https://amo.sh/K/YNTCBS/YJMC10`) | нет механизма адресации; ушёл вместе с trashed-контактом |
| call | 316925483 | не удалён (роута нет); permanent record, действие не требуется |
| unsorted | uid=`56dc452bbca450ebff92efbf4031851e18af12c530fbcea420cdd99503f6` | не удалён напрямую (роута нет); переведён в decline |
| lead | 33286105 (побочный эффект `decline()` выше) | ajax-удалён отдельным cleanup-прогоном |

**Итого: все ID/UID обработаны. 0 зондов остались явно "видимыми" в обычных выборках amo** (все либо в корзине через подтверждённый ajax-механизм, либо физически не создавали отдельно удаляемой сущности — call/shortLink/task/note/catalogElement ушли вместе с trashed родителем). Ни одна существующая (не созданная этим спайком) сущность аккаунта не тронута — в частности, компания `45389979`, к которой автопривязался тестовый звонок по совпадению телефона, не изменялась и не удалялась.

## 5. Открытые вопросы

1. **Точный TTL корзины (soft-delete) не проверялся** — не выяснено эмпирически, через сколько дней amo физически purge-ит `is_deleted=true`-записи (документация заявляет ~30 дней, но задача явно просила не доверять доке; без повторного захода через месяц это не подтвердить в рамках спайка).
2. **`withOnlyDeleted()`-эквивалент для Contact/Company не проверялся отдельно** — не установлено, есть ли у моделей `Contact`/`Company` такой же метод, как `Lead::withOnlyDeleted()`; полагаюсь на аналогию с lead (та же ajax-семантика delete), но не подтверждено напрямую списком.
3. **catalogElements: правильная форма ajax-запроса для точечного удаления элемента не найдена** — перепробован один осмысленный вариант (400, "Код ошибки 222"), дальше не итерировал ради экономии зондов на боевом аккаунте. Если Task 5 обязательно нужно точечное удаление элемента (без сноса всего каталога) — потребуется ещё один заход перебора форм запроса.
4. **tasks: 403 "Invalid scope" не отличает "эндпойнта нет" от "нет прав у виджета"** — не выяснено, есть ли способ (другой OAuth-скоуп/права виджета) включить `DELETE /tasks/{id}`, либо он в принципе не публикуется amo для сторонних интеграций.
