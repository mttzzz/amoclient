# Инвентарь: использование `mttzzz/amoclient` в masterm.pushka.biz + поверхность самой либы

Статус: завершено.

Контекст: вход для полного переписывания библиотеки с нуля. Цель — зафиксировать (А) что реально
использует потребитель masterm.pushka.biz, (Б) какая публичная поверхность есть в текущей `amoclient`.
Оба репо читались read-only, изменений не вносилось.

Версия либы в masterm: `mttzzz/amoclient: ^3.1.1` (composer.json).

---

## Часть А — потребитель masterm.pushka.biz

**Файлы, реально импортирующие `mttzzz\AmoClient*`** (`use mttzzz\AmoClient...`) — 10 файлов, не 11:

```
app/Console/Commands/SyncActiveTasksCommand.php
app/Jobs/AmoWebhookSubscribeJob.php
app/Jobs/AmoWebhookUnsubscribeJob.php
app/Jobs/ChangeDayJob.php
app/Jobs/ChangeTaskJob.php
app/Jobs/SyncAmoPipelinesJob.php
app/Jobs/SyncCustomerGroupJob.php
app/Jobs/SyncCustomerUserJob.php
app/Jobs/SyncTaskTypeJob.php
app/Services/GetAmoTasksService.php
```

Ещё 4 файла упомянуты в первом (широком) grep по строке `amocrm`/`AmoClient`, но **не импортируют библиотеку** — используются только доменные строки/URL-шаблоны (`app/Nova/Customer.php:41` — `.amocrm.ru` в заголовке ресурса; `app/Nova/Filters/CustomerFilter.php:63` — то же; `app/Rules/CheckInstallPushkaConnectorRule.php:22` — ссылка на OAuth-URL с захардкоженным `client_id=00a140c1-7c52-4563-8b36-03f23754d255`, тот же дефолт, что и в `AmoClientOctane.php:71`; `app/Services/ProxyHttpService.php` — см. ниже, отдельный кейс) и `app/Services/TransformAmoTasksToVueCal.php` (чистый трансформер payload'а ajax-эндпоинта, без прямого использования клиента, но зависит от структуры, которую отдаёт `Ajax`-канал через `GetAmoTasksService`).

### А.1 — Сущности/модели и где используются

| Модель/канал | Файлы (`file:line`) |
|---|---|
| `AmoClientOctane` (конструктор) | `SyncActiveTasksCommand.php:44`, `AmoWebhookSubscribeJob.php:40`, `AmoWebhookUnsubscribeJob.php:40`, `ChangeTaskJob.php:274` (внутри `setDefaultDuration()`), `SyncAmoPipelinesJob.php:42`, `SyncCustomerGroupJob.php:23`, `SyncCustomerUserJob.php:52`, `SyncTaskTypeJob.php:36`, `GetAmoTasksService.php:33` |
| `->tasks` (Model\Task) | `SyncActiveTasksCommand.php:44-45` (`filterTaskType()->filterIsCompletedFalse()->allItems()`), `ChangeTaskJob.php:275-277` (`entity((int)$id)` → мутирует `duration` → `update()`) |
| `->webhooks` (Model\Webhook) | `AmoWebhookSubscribeJob.php:42,53,56` (`get()`, `entity($url)->unSubscribe()`, `entity($url)->addTask()->updateTask()->deleteTask()->subscribe()`), `AmoWebhookUnsubscribeJob.php:40-43` (`find($url)` → `toArray()` пуст? → `unSubscribe()`) |
| `->pipelines` (Model\Pipeline) | `SyncAmoPipelinesJob.php:44` (`get()`, без фильтров — полный список) |
| `->account` (Model\Account) | `SyncCustomerGroupJob.php:25` (`withUsersGroups()->get()`), `SyncTaskTypeJob.php:38` (`withTaskTypes()->get()`) |
| `->ajax` (Ajax-канал) | `SyncCustomerUserJob.php:64` (`get('ajax/get_managers_with_group/')`), `GetAmoTasksService.php:33-46` (`postForm("ajax/todo/calendar/$period/", [...])`) |

Ни один из 10 файлов не использует `->leads/->contacts/->companies/->customers/->catalogs/->users/->events/->calls/->shortLinks/->sources` и не создаёт ни одной `Entities\*` вручную кроме `->tasks->entity()` (см. таблицу выше) — то есть **потребитель использует ~15% публичной поверхности библиотеки** (модели `Task`/`Webhook`/`Pipeline`/`Account` + ajax-канал; ни одной сущности `Lead/Contact/Company/Customer`, ни одного CRUD через `Entities\*::create()/update()`, ни `CustomFieldTrait`, ни `Phone/EmailTrait`, ни `LazyCustomFields`).

### А.2 — Характерные вызовы методов

- **`Task`**: `$amo->tasks->filterTaskType($activeTypes)->filterIsCompletedFalse()->allItems()` (`SyncActiveTasksCommand.php:44-45`) — полная выгрузка активных задач по типам через пагинированный `allItems()`; `$amo->tasks->entity((int)$task['id'])` затем прямая мутация публичного свойства `$amoTask->duration = $d*60;` и `$amoTask->update()` (`ChangeTaskJob.php:275-277`) — PATCH одной задачи.
- **`Webhook`**: `$amo->webhooks->get()` без фильтров — весь список, потребитель сам ищет нужный `destination` в цикле (`AmoWebhookSubscribeJob.php:42-49`) вместо `find()`; `entity($webhookUrl)->unSubscribe()` / `entity($webhookUrl)->addTask()->updateTask()->deleteTask()->subscribe()` (`:53,56`) — билдер настроек событий цепочкой; `AmoWebhookUnsubscribeJob.php` **вместо этого** использует `find($url)`, проверяя `!empty($webhook->toArray())` как способ узнать "хук не найден" (косвенный признак: `find()` в библиотеке никогда не возвращает `null`, только пустую сущность — потребитель обходит это через `toArray()`-эмптинес чек, что зависит от implementation detail `toArray()` про "falsy ⇒ unset", см. Б.5 п.8).
- **`Pipeline`**: `$amo->pipelines->get()` (`SyncAmoPipelinesJob.php:44`) — весь список, без фильтра/пагинации (в get() пагинация тоже не запрашивается явно — предположительно pipelines у amo укладывается в один page).
- **`Account`**: `$amo->account->withUsersGroups()->get()` и `$amo->account->withTaskTypes()->get()` (`SyncCustomerGroupJob.php:25`, `SyncTaskTypeJob.php:38`) — оба читают через `data_get($result, '_embedded.X', [])`, т.е. потребитель сам разворачивает `_embedded`, вместо использования метода библиотеки `AbstractModel::get()`, который для `Lead`/`Contact` и т.п. уже делает `Arr::first($data['_embedded'])` — но `Account::get()` **переопределён** (`Models/Account.php:26-47`) и **не** делает unwrap `_embedded` (возвращает данные как есть) — асимметрия между `Account::get()` и `AbstractModel::get()`, потребитель вынужден знать этот нюанс и разворачивать вручную через `data_get`.
- **`Ajax`**: `$amo->ajax->get('ajax/get_managers_with_group/')` (`SyncCustomerUserJob.php:64`, см. А.3 подробный контракт) и `$amo->ajax->postForm("ajax/todo/calendar/$period/", [...])` (`GetAmoTasksService.php:33-46`).

### А.3 — Использование ajax-канала — точные URL и payload

1. **`GET ajax/get_managers_with_group/`** (`SyncCustomerUserJob.php:64`) — без query-параметров. Ответ — `{ managers: [ {id, login, title?, option?, group?, active, is_admin, phone?}, ... ] }`, потребитель читает `data_get($response, 'managers', [])` (`:66`) и для каждого элемента вручную типизирует amo-строковые boolean-подобные значения (`active`/`is_admin` могут прийти как `bool|int|string`, потребитель матчит `strtoupper()` на `['Y','1','TRUE']`, `:70-72,93-96`) — задокументировано локальным `@phpstan-type AmoManager` (`:23`), контракт **не** формализован в библиотеке (это приватный недокументированный ajax-эндпоинт amo, вне `/api/v4`).
2. **`POST ajax/todo/calendar/{day|week}/`** (`GetAmoTasksService.php:34-46`, `asForm`-семантика через `Ajax::postForm()`) — payload:
   ```php
   [
       'filter_date_from' => 'd.m.Y',
       'filter_date_to' => 'd.m.Y',
       'useFilter' => 'y',
       'filter' => [
           'main_user' => $userIds,               // array<int|string|null>
           'status' => ['uncompl'],
           'pipe' => [$pipelineId => [$statusId, ...], ...],
           'task_type' => [$amoTaskTypeId, ...],
       ],
   ]
   ```
   Ответ читается как `data_get($response, 'response.items', [])` (`:49`), задокументирован локальным `@phpstan-type AmoTask` (`:18`, поля `id, date?, complete_till, duration, user{id,name}, params{type, main_user{id}, text}, linked?{element_type?, name?, uri?}`) — снова приватный контракт вне amo `/api/v4`, целиком выведен потребителем эмпирически, в библиотеке не формализован.

Оба ajax-контракта потребитель типизирует **сам** через `@phpstan-type` в userland-коде — библиотека даёт только транспорт (`Ajax::get/postForm`), ноль доменного знания об этих двух конкретных приватных эндпоинтах amo.

### А.4 — Точки боли

1. **Ручной retry/backoff в Job-классах, не через библиотечный `RetriesTransientAmoErrors`** — 5 из 9 джоб (`AmoWebhookSubscribeJob`, `AmoWebhookUnsubscribeJob`, `SyncAmoPipelinesJob`, `SyncCustomerUserJob`, `SyncTaskTypeJob`) катчат `Exception` и вручную решают ретраить ли: `if ($this->tries === $this->attempts()) { fail+report } elseif ($e->getCode() !== 402) { release($fixed) }` (пример `AmoWebhookSubscribeJob.php:58-65`, идентичный паттерн в `AmoWebhookUnsubscribeJob.php:46-53`) или совсем без 402-исключения (`SyncAmoPipelinesJob.php:46-56`, `SyncCustomerUserJob.php:180-187`, `SyncTaskTypeJob.php:40-50` — там **любая** ошибка получает фиксированный `release(10)`, включая настоящие бизнес-ошибки amo, которые впустую съедают `tries` попыток). Только **2 из 9** (`ChangeDayJob`, `ChangeTaskJob`) используют библиотечный `Queue\RetriesTransientAmoErrors` с честной классификацией транзиентности и 24-часовым горизонтом. Это прямое расхождение внутри одного проекта — часть кода уже мигрировала на библиотечный классификатор (git history показывает: `MASTERM-PUSHKA-BIZ-31/2E → 35`, см. `tests/Unit/Jobs/ChangeTaskJobTest.php:9-14`), часть — нет.
2. **Проверка `$e->getCode() !== 402` вместо `instanceof AmoPaymentRequiredException`/`isTransientAmoError()`** (`AmoWebhookSubscribeJob.php:62`, `AmoWebhookUnsubscribeJob.php:50`) — работает только потому что `AmoCustomException`/`AmoPaymentRequiredException` кладут `402` в `Exception::$code` (`Exceptions/AmoCustomException.php:19`), но это неявный контракт: любой сторонний `Exception` с случайно совпавшим кодом 402 (например, HTTP-код в чужом исключении) будет неверно классифицирован как "не ретраить".
3. **Ручной текстовый матчинг по телу ошибки** — `ChangeTaskJob::setDefaultDuration()` (`:284`): `if (str_contains($e->getMessage(), 'Error 282')) { return false; }` — распознавание конкретной бизнес-ошибки amo ("задачи уже нет") через **substring-поиск в JSON-pretty-printed message** (см. Б.4: `AmoCustomException` хранит только `message`-строку, не структурированный `errors[].code`). Это ровно тот сценарий, для которого в Б.4 отмечена нехватка структурированного тела ошибки в `AmoCustomException` — потребитель вынужден грепать текст вместо чтения `code`/`errors[0].code` из тела ответа amo.
4. **Дублирование прокси/retry-логики транспорта в userland** — `app/Services/ProxyHttpService.php` — отдельный класс с комментарием в самом файле "Логика аналогична AmoClientOctane" (`:16`), **буквально копирующий** алгоритм сбора прокси (`collectProxies()` `:77-99` ≈ `AmoClientOctane.php:137-153`) и retry-callback (`createRetryCallback()` `:268-297` ≈ `AmoClientOctane.php:203-228`, тот же паттерн "5xx/ConnectionException → ротация прокси"), но для **не-amo** HTTP-вызовов (сервис общего назначения). Это сильный сигнал для новой библиотеки: транспортный слой (прокси-ротация + ретраи) стоит выделить в переиспользуемый публичный компонент, а не хоронить внутри `AmoClientOctane`-конструктора.
5. **Асимметрия `Account::get()` vs остальных моделей** (см. А.2) — заставляет потребителя помнить, что `_embedded`-unwrap для `Account` нужно делать вручную через `data_get(..., '_embedded.X')`, тогда как для большинства других моделей `get()` уже это делает.
6. **`Webhook::find()` не бросает `AmoCustomException`** (см. Б.5 п.13) — но потребитель (`AmoWebhookUnsubscribeJob.php:40-44`) оборачивает это в общий `catch(Exception $e)` на уровне `handle()`, так что асимметрия транспарентна для конкретно этого кейса (просто ловится на уровень выше), но при рефакторинге легко потерять эту компенсацию.
7. **`ChangeTaskJob`/`ChangeDayJob` — единственные, где транзиентность обрабатывается корректно**, но обе имеют закомментированный/неиспользуемый код рядом (`ChangeTaskJob.php:143-145` — закомментированный `throw new Exception(...)` для несуществующего аккаунта; `SyncCustomerUserJob.php:174-178` — закомментированный блок удаления orphan-юзеров) — не имеет отношения к либе напрямую, но показывает общий паттерн "заготовка на будущее оставлена мёртвым кодом рядом с рабочим".
8. **Фасад `AmoClient` полностью не используется** — `grep -rl "AmoClient::" app tests` не находит ни одного вызова; ни один `ServiceProvider` в masterm не биндит `'amoclient'` в контейнер. Подтверждает находку из Б.5 п.1: фасад — мёртвый код и в потребителе тоже, весь доступ идёт через `new AmoClientOctane($accountId)` напрямую.

### А.5 — Как создаётся клиент и откуда берутся токены

- Единственный паттерн создания — `new AmoClientOctane($customer->amoAccountOrFail()->id)` (или `$amoAccount->id` напрямую), **везде** без второго (`clientId`) и третьего (`proxy`) аргумента — потребитель полностью полагается на дефолт `clientId` из библиотеки (`00a140c1-7c52-4563-8b36-03f23754d255`, тот же дефолт продублирован строкой в `CheckInstallPushkaConnectorRule.php:22` для OAuth-install-ссылки — двойное дублирование магической строки между либой и потребителем) и на `config('app.proxy')`/`config('app.secondProxy')` для прокси (те же ключи читает и локальный `ProxyHttpService`, см. А.4 п.4).
- `$customer->amoAccountOrFail()` (Eloquent-связь `Customer → AmoAccount`, определена в `app/Models/Customer.php`, не читалась подробно — вне периметра задачи, т.к. это модель masterm, не библиотеки) — резолвит `AmoAccount->id`, который **и есть** `accounts.id` в БД `octane` (первый аргумент конструктора `AmoClientOctane`). Токен как таковой в masterm нигде не хранится и не читается напрямую — вся ответственность за резолв `access_token` полностью делегирована библиотеке (SQL в `octane`-коннекшене внутри `AmoClientOctane::__construct`, см. Б.2/Б.3). Значит masterm обязан держать **два** working DB connections: свой основной + именованный `octane` (конфигурируется в `config/database.php` masterm, не проверялось подробно — не требовалось заданием).
- Ни один файл в masterm не создаёт `Helpers\OctaneAccount`/`Helpers\Widget`/`Helpers\Pipeline` вручную — это чисто внутренние DTO библиотеки, наружу в потребителя не просачиваются.

---

## Часть Б — поверхность библиотеки

### Б.1 — Карта публичного API

**Точки входа**

- `mttzzz\AmoClient\AmoClientOctane` (`src/AmoClientOctane.php:33`) — единственный конструируемый вручную класс. Конструктор `__construct(int $aId, ?string $clientId = null, ?string $proxy = null)` (`:73`) делает синхронный SQL-запрос в БД `octane` за токеном/доменом аккаунта и раздаёт готовые модели как public-свойства.
- `mttzzz\AmoClient\AmoClientServiceProvider` (`src/AmoClientServiceProvider.php:7`) — регистрирует **только конфиг** (`mergeConfigFrom` в `register()`, `:24`) и `publishes()` для консоли (`:39-45`). **НЕ биндит** `'amoclient'` в контейнер — ни `$this->app->bind('amoclient', …)`, ни `singleton`. `provides(): ['amoclient']` (`:31-34`) объявляет service, но фактически ничего не регистрирует под этим именем.
- `mttzzz\AmoClient\Facades\AmoClient` (`src/Facades/AmoClient.php:7`) — фасад с `getFacadeAccessor() = 'amoclient'` (`:9-11`). Поскольку контейнер ничего не биндит под этим ключом (см. выше), **фасад в текущем виде нерабочий** — `AmoClient::...` бросит `BindingResolutionException`, пока потребитель сам не забиндит `'amoclient'` в своём `AppServiceProvider`. Нужно проверить в Части А, как это разрешает masterm.
- Конфиг `config/amoclient.php` — ключи `proxies` (массив, `:4-8`), `verify` (`:9`), `timeout`/`connectTimeout`/`retries`/`retryDelay` (`:10-13`). **Важно**: `proxies` из конфига нигде не читается в `AmoClientOctane` — прокси реально берутся из `config('app.proxy')` / `config('app.secondProxy')` (см. Б.2). Мёртвый конфиг-ключ.

**Модели (`src/Models/*`, 20 файлов)** — прикреплены к `AmoClientOctane` как public-свойства (`:35-65`) и держат query-builder-подобное API (`page()`, `limit()`, `filter*()`, `order*()`, `get()`, `each()`, `allItems()`, `find()`, `entity()`, `create()`, `update()`):

| Модель | entity-путь | Особые методы |
|---|---|---|
| `Account` (`Models/Account.php`) | `account` | `withAmojoId/withAmojoRights/withUsersGroups/withTaskTypes/withVersion/withEntityNames/withDatetimeSettings` |
| `Lead` (`Models/Lead.php`) | `leads` | `entity/entityData/find/customFields`, `withCatalogElements/withIsPriceModifiedByRobot/withLossReason/withContacts/withOnlyDeleted/withSourceId`, вложенный `notes` |
| `Contact` (`Models/Contact.php`) | `contacts` | аналогично + `withLeads/withCustomers` |
| `Company` (`Models/Company.php`) | `companies` | аналогично + `withContacts/withLeads/withCustomers` |
| `Customer` (`Models/Customer.php`) | `customers` | `withCatalogElements/withContacts/withCompanies` |
| `Catalog` (`Models/Catalog.php`) | `catalogs` | CRUD, `entity/find` |
| `CatalogElement` (`Models/CatalogElement.php`) | `catalogs/{id}/elements` | конструктор требует `$catalogId` |
| `User` (`Models/User.php`) | `users` | `withRole/withGroup/withUuid/withAmojoId`, `find()` — **отдельная реализация**, не через `CrudTrait::findEntity` (нет `AmoCustomException`-обёртки, см. Б.5) |
| `Pipeline` (`Models/Pipeline.php`) | `leads/pipelines` | CRUD |
| `Task` (`Models/Task.php`) | `tasks` | богатый набор `filter*`/`orderByComplete*` |
| `Event` (`Models/Event.php`) | `events` | ~60 `type*()`/`valueAfter*()`/`valueBefore*()` методов-констант под конкретные типы событий amo — самый большой файл поверхности (629 строк) |
| `Ajax` (`src/Ajax.php`, не в Models/) | — | см. ниже отдельно |
| `Unsorted` (`Models/Unsorted.php`) | `leads/unsorted` | `sip()/form()` (фабрики `Entities\Unsorted\*`), `decline/accept`, `filterUid/filterCategory*/filterPipelineId`, `orderCreatedAt*` |
| `Call` (`Models/Call.php`) | `calls` | только `entity()` |
| `Webhook` (`Models/Webhook.php`) | `webhooks` | `entity/find` (find делает **сырую фильтрацию по `destination`**, без CRUD-трейта) |
| `ShortLink` (`Models/ShortLink.php`) | `short_links` | `entity()`, `create(array $entities)` (batch) |
| `Source` (`Models/Source.php`) | `sources` | CRUD |
| `CustomField` (`Models/CustomField.php`) | `{parent}/custom_fields` | вложенный `groups` (`CustomFieldGroup`) |
| `CustomFieldGroup` (`Models/CustomFieldGroup.php`) | `{parent}/groups` | только `entity()` |
| `Note` (`Models/Note.php`) | `{parent}/notes` | `filterCallIn/filterCallOut/filterEmail/filterCommon`, `orderUpdatedAt*/orderId*` |
| `Link` (`Models/Link.php`) | `{parent}/links` | фабрики `catalogElement()/contact()/companies()/customers()`, `link()/unlink()` batch |

`AbstractModel` (`src/Models/AbstractModel.php:12`) — общий фундамент: `get()` (`:43-74`, обрабатывает `_embedded` unwrap), `page/limit` (`:76-89`), `addWith()` через рефлексию имени метода (`Str::snake(Str::after($with,'with'))`, `:91-96`), `prepareEntities()` (`:102-110`, маппит `Entities\*::toArray()`), `each()`/`allItems()` (пагинация до `count($chunk) < $limit`, `:112-142`).

**Сущности (`src/Entities/*`, 19 файлов + `Unsorted/*`)** — DTO с "магической" схемой: `AbstractEntity` (`src/Entities/AbstractEntity.php:8`) хранит **известные** amo-поля как typed public properties (`id, responsible_user_id, custom_fields_values, group_id, updated_by, closest_task_at, is_deleted, is_unsorted, _links, loss_reason_id, closed_at, score, labor_cost, catalog_id, _embedded, metadata`, `:19-61`) и **всё остальное** — в `protected array $attributes` через `__get/__set/__isset/__unset` (`:97-115`, magic-properties). `setData()` (`:75-95`) молча глотает `Exception` пустым `catch(){}` (`:93-94`) — любая ошибка на маппинге входных данных теряется без трейса. `toArray()` (`:120-167`) — сериализация с ручным `$except`-списком служебных полей (`:125-126`) и неявным правилом "empty ⇒ unset" (`:141,157`) с точечными исключениями (`duration, disabled, can_link_multiple, is_main`) — то есть **нулевые/false/пустые значения полей не сериализуются в payload по умолчанию**, кроме явно перечисленных.

Каждая конкретная Entity (`Lead, Contact, Company, Customer, Task, Note, Link, Call, Source, Webhook, CustomField, CustomFieldGroup, Catalog, CatalogElement, Pipeline, ShortLink, Unsorted\{Form,Sip}`) — тонкая обёртка над `AbstractEntity`/`AbstractUnsorted`, которая: (а) переопределяет `$entity`-путь, (б) собирает вложенные модели (`notes`, `tasks`, `links` — `Entities\Lead::__construct` `src/Entities/Lead.php:88-90`), (в) добавляет предметные builder-методы (`Call::direction()/status*()`, `Webhook::responsibleLead()/addLead()/...`, `Note::callIn()/smsOut()/...`).

Замечена мёртвая/decorative вложенная `class OctanePipeline` внутри `src/Entities/Lead.php:13-34` — используется только как PHPDoc-тип для `DB::table('account_pipelines')->first()` в `getPipelineName()` (`:155-161`), но Eloquent/`DB::table()->first()` реально возвращает `stdClass`, не `OctanePipeline` — типовая аннотация лжёт (не проверяется `instanceof`, в отличие от `AmoClientOctane::convertToOctaneAccount` `:105` или `CrudEntityTrait::getResponsibleName` `:90`, которые честно гардят `instanceof stdClass`).

**Трейты (`src/Traits/*`, 12 файлов)**

- `CrudTrait` (`Traits/CrudTrait.php:10`) — миксин для *моделей* (`AbstractModel`-наследники): `findEntity()` (protected, GET `{entity}/{id}` с `with`), `create()`/`update()` (batch POST/PATCH массива `Entities\*`). Единая точка обёртки `RequestException`/`ConnectionException` → `AmoCustomException` (`:32-34, 54-56, 75-77`).
- `CrudEntityTrait` (`Traits/CrudEntityTrait.php:12`) — миксин для *сущностей* (`AbstractEntity`-наследники): `update()/create()` (single, POST/PATCH `[$this->toArray()]`), `createGetId()` (`:55-63`, достаёт `id` из `_embedded[entity][0]`), `setResponsibleUser()` (`:65-75`, **прямой SQL-запрос** в `octane.account_amo_user` — проверка что менеджер активен на аккаунте, иначе обнуляет `responsible_user_id`), `getCreatedAt()` (Carbon), `getResponsibleName()` (**ещё один прямой SQL** в `octane.amo_users`, `:88`).
- `CustomFieldTrait` (`Traits/CustomFieldTrait.php:11`) — самый сложный трейт: `setCF()`/`setCFByCode()` пишут в `custom_fields_values` с side-effect типизацией по `$this->cf[$id]` (карта `field_id → type` из `LazyCustomFields`) — `switch` на `textarea/multitext/url/text/numeric/date_time/date/checkbox/birthday` (`:62-106`), даты — `Carbon::parseFromLocale($value,'ru')` с **захардкоженной русской локалью** и `catch(Exception) → Telegram::log(...)` (импорт из внешнего пакета `mttzzz\LaravelTelegramLog\Telegram`, `:9`) вместо проброса/логирования через стандартный Laravel-логгер. `getCF/getCFByCode/getCFV/getCFVByCode/getCFE/getCFCLN/getCFVM` — чтение, причём `getCFCLN()` (`:174-198`) делает **синхронный HTTP-запрос** `GET catalogs/{id}/elements/{id}` внутри геттера значения поля (скрытый I/O за невинным именем).
- `EmailTrait`/`PhoneTrait` (`Traits/EmailTrait.php`, `Traits/PhoneTrait.php`) — почти побитово идентичные (email по `field_code==='EMAIL'`, phone по `'PHONE'`): `*List()/*Add()/*Set()/*Delete()`. `phoneGet()`/`emailGet()` — рекурсивный self-call с side-effect (создают пустой блок, если не найден, затем рекурсивно ищут снова, `EmailTrait.php:41-55`) — неочевидный контракт, легко зациклить при мутации в другом месте.
- `OrderTrait`/`QueryTrait`/`StatusTrait`/`TagTrait` — маленькие сеттеры (`order[...]`, `query`, `_embedded['statuses']`/`tags`).
- `Filter\Common`/`Filter\Lead`/`Filter\PhoneEmail` (`Traits/Filter/*`) — построители `$this->filter[...]`, `PhoneEmail` требует protected `$fieldPhoneId/$fieldEmailId`, которые задаются **только** конструктором `Contact`/`Company` (`Models/Contact.php:26-27`) — то есть трейт синтаксически применим к любой модели, но семантически рухнет (`0` id → бессмысленный фильтр) без этого конструктора.

Общий паттерн "мутирующий `$this->filter[key] ?? []` → `is_array()`-гард → присвоить обратно" повторяется **дословно** минимум в 5 местах (`Task::filterId`-семейство через `filter[...]=...` напрямую, но `Event::addFilterEntity/addFilterType/appendFilterValueItem/setFilterValueField` `Models/Event.php:591-627`, `Unsorted::addFilterCategory` `Models/Unsorted.php:118-124`, `Note::addFilterNoteType` `Models/Note.php:64-70`) — не вынесено в общий трейт/хелпер, хотя логика идентична.

**`LazyCustomFields`** (`src/LazyCustomFields.php:16`) — ленивый кэш `account_custom_fields` (id→type, id→enums-json) на инстанс `AmoClientOctane`. Первый вызов `cf()`/`enums()` триггерит `SELECT id, type, enums FROM account_custom_fields WHERE account_id=?` (`:48-51`); до этого — ноль лишних запросов, даже если сконструирован (комментарий `:8-15` ссылается на pg_stat-обоснование). Прокидывается только в `Lead/Contact/Company/Customer` (те, у кого есть `CustomFieldTrait`-сущности).

**`Ajax`** (`src/Ajax.php:8`) — отдельный HTTP-канал **на веб-домен amo** (не `/api/v4`, см. Б.2), клонирует `PendingRequest` от основного клиента (`:14`) и добавляет `X-Requested-With: XMLHttpRequest`. Методы: `get/postJson/postForm/patch/delete` (`:26-75`) — все делают `->throw()->json()`, никакой доменной логики сверху, чистый низкоуровневый proxy к внутренним (недокументированным) ajax-эндпоинтам amoCRM. Есть на `AmoClientOctane->ajax` (`:249`).

**`Helpers/*`** — три plain DTO без поведения: `OctaneAccount` (`Helpers/OctaneAccount.php:5`, снапшот строки `accounts`+`account_widget`), `Pipeline` (`Helpers/Pipeline.php:5`, `id/name/client_id` — не путать с `Entities\Pipeline`/`Models\Pipeline`, троекратное имя-коллизия в разных неймспейсах), `Widget` (`Helpers/Widget.php:5`).

### Б.2 — Механика транспорта (`src/AmoClientOctane.php`)

1. **Резолв аккаунта и токена** (`:83-121`) — единственный SQL-запрос джойнит `accounts ⋈ widgets ⋈ account_widget` по `client_id` (дефолт `'00a140c1-7c52-4563-8b36-03f23754d255'`, переопределяемый вторым конструкторным аргументом, `:71,76-78`) и `account_id`. Если строки нет — `throw new Exception("Account ($aId) not found")` (`:100`, **сырой `Exception`, не типизированный**). Если виджет не установлен (`access_token` пуст) — второй запрос за именем виджета и снова сырой `Exception` (`:110-119`).
2. **Базовый URL** (`:133-135`) — ветвится по `domain==='com'` → `kommo.com`, иначе `amocrm.{domain}` (обычно `.ru`).
3. **Прокси** (`:137-153, 232-235`) — список уникальных прокси собирается в порядке: явный `$proxy`-аргумент конструктора → `config('app.proxy')` → `config('app.secondProxy')` → `null` (без прокси) как гарантированный последний элемент. Первый прокси из списка ставится сразу (`:233-235`), дальнейшая ротация — только через retry-callback при ошибке (см. ниже). **Именно `config('app.*')`, не `config('amoclient.proxies')`** — конфиг-ключ библиотеки `amoclient.proxies` (`config/amoclient.php:4-8`) не используется нигде в коде.
4. **Таймауты/ретраи** — из `config('amoclient.timeout'|'connectTimeout'|'retries'|'retryDelay')` с фолбэками `60/10/3/2000` через приватный `configInt()` (`:275-280`, гардит `is_numeric` перед кастом). Итоговое число попыток HTTP-клиента — `$retries * $maxProxyAttempts` (`:160`, т.е. ретраи и прокси-ротация делят один и тот же бюджет попыток).
5. **Retry-callback** (`:160-229`, единственный на весь клиент, Laravel `PendingRequest::retry()` third-arg closure) — классифицирует по типу exception и статусу:
   - `RequestException` со статусом `402` → **не ретраит**, сразу бросает `AmoPaymentRequiredException::fromRequestException()` (`:174`) — специальный путь, читает `accounts.payed` из `octane` синхронно внутри исключения (см. Б.4).
   - статус `401` → перечитывает `account_widget.access_token` из `octane` (join на `widgets.client_id`+`account_id`+`active=true`, `:185-190`); если токен реально сменился — обновляет `$currentToken` (замыкание by-ref, `:154,193`) и вызывает `$request->withToken($freshToken)`, ретраит (`return true`); если токен тот же — не ретраит (`return false`, реальная auth-проблема).
   - `HttpClientConnectionException` (сетевая ошибка) ИЛИ `RequestException` со статусом `>=500` → триггерит **прокси-ротацию**: `$proxyIndex++`, следующий прокси из списка (или `null`) применяется через `$request->withOptions(['proxy' => ...])` (`:216-226`), ретраит пока не исчерпан `$maxProxyAttempts - 1`.
   - Любой другой случай (4xx кроме 401/402, исчерпанные попытки) → `return false`, ретрай прекращается, ошибка всплывает как есть (`RequestException`, не обёрнутая тут — обёртка в `AmoCustomException` происходит выше, в `Models`/`Traits`, не в транспорте).
6. **Конструирование зависимостей** (`:237-254`) — `$http` (готовый `PendingRequest` с baseUrl/токеном/ретраями) раздаётся во все модели; `Ajax` получает отдельный клон с другим baseUrl (веб-домен) и заголовком `X-Requested-With`.

Итог: **401 и 402 — единственные статусы с доменной логикой** (перечитка токена / снапшот payed) внутри самого транспортного слоя; всё остальное — либо голая прокси/connection-ретрай эвристика (5xx, сеть), либо falls through без обработки (4xx, кроме 401/402).

### Б.3 — Работа с токенами

- Источник — внешняя БД проекта `octane`, коннекшн `DB::connection('octane')` (используется в 5 разных местах: `AmoClientOctane` конструктор `:83-97` и retry-callback `:185-190`; `AmoPaymentRequiredException::fromRequestException` `:27-28`; `CrudEntityTrait::setResponsibleUser`/`getResponsibleName` `:67-72,88`; `LazyCustomFields::load()` `:48-51`; `Entities\Lead::getPipelineName()` `:156-159`).
- Таблицы/поля, которые библиотека читает напрямую (без ORM/моделей — сырой query builder на named connection):
  - `accounts` (`id, subdomain, domain, contact_phone_field_id, contact_email_field_id, payed`)
  - `widgets` (`id, name, client_id`)
  - `account_widget` (`account_id, widget_id, active, access_token`)
  - `account_custom_fields` (`id, type, enums`, per `account_id`)
  - `account_amo_user` (`amo_user_id, is_active, account_id`)
  - `amo_users` (`id, name`)
  - `account_pipelines` (`account_id, id, name`)
- **Протухание токена**: библиотека сама не рефрешит токен через OAuth (нет кода обращения к amo `/oauth2/access_token`) — она полагается на то, что **внешний процесс (Octane)** уже обновил `account_widget.access_token` в БД, и просто **перечитывает** его при 401 (см. Б.2 п.5). Если строка в БД не обновилась — 401 не ретраится, всплывает как обычная ошибка.
- Нет разделения "refresh_token" — модель предполагает, что валидный `access_token` уже лежит в БД; сама либа не участвует в OAuth-обмене.

### Б.4 — Обработка ошибок

**`Exceptions/AmoCustomException`** (`src/Exceptions/AmoCustomException.php:9`) — базовый враппер. Конструктор принимает `ConnectionException|RequestException` (`:16`), логика:
- если `$e->getCode() == 402` (loose `==`) → message `'Амо не оплачен'`, code 402 (`:18-19`) — **это ветвление сработает даже если `$e` — `ConnectionException` с кодом 402** (маловероятно, но не защищено типом);
- иначе если `RequestException` → декодит JSON тела ответа, если не парсится — берёт `$e->getMessage()`, иначе `json_encode($decodedBody, ...)` как message (`:21-27`);
- иначе (голый `ConnectionException`) → `'Unknown error (ConnectionException)'` (`:31`).
- **Оригинал всегда в `getPrevious()`** (комментарий `:11-15` явно фиксирует контракт: классификаторы должны смотреть `getPrevious()`, не парсить message строкой).

**`AmoPaymentRequiredException extends AmoCustomException`** (`src/Exceptions/AmoPaymentRequiredException.php:17`) — специализация под 402. `fromRequestException()` (`:23-31`) — фабрика, снапшотит `octane.accounts.payed` **синхронным SQL-запросом в момент броска исключения** и кладёт в `$accountPayed`. `isSpurious()` (`:34-37`) — семантика "402 при payed=true в octane ⇒ ложь амо (транзиент)".

**`Queue/RetriesTransientAmoErrors`** (`src/Queue/RetriesTransientAmoErrors.php:30`) — trait-миксин для консьюмерских Job-классов (**не используется внутри `src/` — только контракт для потребителей**, помечено `@phpstan-ignore trait.unused` `:29`). Даёт:
- `retryUntil()` (`:33-36`) — горизонт `now()->addDay()`, заменяет `$tries`-счётчик (Laravel игнорирует `maxTries` при заданном `retryUntil`).
- `transientAmoBackoff(int $attempt)` (`:39-42`) — фиксированная лесенка `[60,300,900,1800]`, затем плато `3600` (`??` фолбэк).
- `releaseForTransientAmoError()` (`:44-47`) — `$this->release($backoff)`.
- `isTransientAmoError(Throwable $e): bool` (static, `:49-73`) — **классификатор транзиентности**, единственное место, где формализована граница transient/non-transient:
  - `ConnectionException` (голый, до обёртки) → transient;
  - `AmoPaymentRequiredException` → `$e->isSpurious()` (снапшот payed решает);
  - `AmoCustomException` с `getPrevious() instanceof ConnectionException` → transient;
  - статус (из `RequestException` напрямую или из `getPrevious()`) `=== 401` или `>= 500` → transient;
  - всё остальное (в т.ч. голый `RequestException` без обёртки с 4xx≠401, JSON-decode ошибки, домейн-валидаторы amo с 400) → **не transient**.

  Обрати внимание: этот классификатор **дублирует** часть логики транспортного retry-callback (401→refresh, но здесь 401 воспринимается уже как "транзиент для джобы" — то есть если внутренний токен-refresh транспорта не помог за весь бюджет попыток и 401 всё равно всплыл, джоба-потребитель ещё раз считает его transient и повторяет через часы). Также — **эта же логика недавно чинилась в потребителе `octane.pushka.biz`** отдельным приложение-специфичным патчем (см. git log в начале сессии: `fix(jobs): транзиентный Amo-400 «системная ошибка, повторите попытку» теперь ретраится` — то есть 400 с retry-детейлом в теле пока НЕ покрыт этим классификатором библиотеки, октан держит свою копию поверх).

**Где обработка заведомо неполна**:
- 400 с "системная ошибка, повторите попытку" (amoCRM транзиентный сбой бэкенда, отдаваемый как HTTP 400) — **не классифицируется** ни retry-callback'ом транспорта (только 401/402/5xx/connection), ни `isTransientAmoError()` (только 401/5xx/connection/402-spurious). Статус 400 всегда попадает в default-ветку "не transient".
- Классификация опирается **только на HTTP-статус и присутствие `getPrevious()`-типа** — никогда на тело ответа (`errors[].code` из amo API contract) даже там, где `AmoCustomException`-конструктор уже распарсил JSON body (`:21-27`) и мог бы сохранить структурированную ошибку вместо строки message.
- `AmoCustomException` не хранит распарсенный `$decodedBody` как свойство — только как `message`-строку (JSON pretty-print). Любой потребитель, которому нужен `errors[0].code`, вынужден **заново парсить `$e->getMessage()`** как JSON (хрупко, теряет типизацию).

### Б.5 — Странности и долги

1. **Facade сломан "из коробки"** — `AmoClientServiceProvider::register()` не биндит `'amoclient'` в контейнер (`src/AmoClientServiceProvider.php:22-26`), а `Facades\AmoClient` рассчитан именно на этот ключ (`src/Facades/AmoClient.php:9-11`). Нужно проверить в Части А, компенсирует ли это masterm собственным биндингом, или фасад просто мёртвый код.
2. **Мёртвый конфиг-ключ** `amoclient.proxies` (`config/amoclient.php:4-8`) — никогда не читается; реальная прокси-ротация идёт через `config('app.proxy')`/`config('app.secondProxy')` (`AmoClientOctane.php:143-148`), которые не задокументированы в конфиге самой библиотеки — их нужно знать из кода.
3. **Тройная коллизия имени `Pipeline`**: `Helpers\Pipeline` (plain DTO), `Models\Pipeline` (CRUD-модель), `Entities\Pipeline` (CRUD-сущность) — плюс локальная `OctanePipeline` внутри `Entities/Lead.php:13-34`, которая ничем не связана с остальными тремя и используется только как (лживая) PHPDoc-аннотация для `stdClass`.
4. **Три вложенных класса определены не в своих файлах** — `OctanePipeline` живёт в `Entities/Lead.php` вместо отдельного файла; нарушает "один класс — один файл", затрудняет поиск при переписывании.
5. **Ассоциативные массивы вместо DTO повсеместно** — `custom_fields_values`, `_embedded`, `filter`, `order`, `metadata`, `settings` — везде `array<string,mixed>`/`array<mixed>` с ручными `is_array()`-гардами на каждом уровне доступа (десятки повторяющихся блоков вида `$x = $arr['key'] ?? null; $x = is_array($x) ? $x : [];`). Это прямое следствие phpstan-max кампании (см. `[[phpstan-campaign]]`), которая типизировала *обращения*, но не ввела структурные типы — итоговый код многословен и хрупок к опечаткам в строковых ключах amo API (`'field_code'`, `'value'`, `'enum_id'`, `'catalog_id'` и т.д. — нигде не константы).
6. **Магические строки/числа без единого источника истины**: `Call::status*()` захардкожены `1..7` без enum (`Entities/Call.php:171-204`); `Pipeline::changeSuccessStatus/changeFailStatus` жёстко `id: 142/143` (`Traits/StatusTrait.php:17,24`) — это ID успешного/неуспешного статуса **конкретного** пайплайна amo (не универсальны, но выглядят как метод общего назначения); `clientId` дефолт зашит строкой в `AmoClientOctane.php:71`.
7. **`AbstractEntity::setData()` глотает все исключения молча** (`Entities/AbstractEntity.php:93-94`, пустой `catch(Exception $e){}`) — если маппинг входных данных упадёт (например, из-за неожиданного типа в API-ответе), сущность тихо останется частично инициализированной без единого сигнала (не то что exception, даже лог не пишется).
8. **`toArray()` неявно превращает "falsy" в "отсутствует"** (`AbstractEntity.php:141,157`) — `0`, `false`, `''`, `[]` не попадают в payload, кроме explicit-исключений (`duration, disabled, can_link_multiple, is_main`). Значит **нельзя штатно отправить `price=0` или `is_price_computed=false`** через общий путь — придёть помнить добавлять поле в except-список при любом новом кейсе, где falsy-значение семантически валидно.
9. **`getCFCLN()` делает скрытый синхронный HTTP-запрos внутри геттера** (`Traits/CustomFieldTrait.php:174-198`, `GET catalogs/{id}/elements/{id}` per value) — неочевидный I/O-side-effect с именем, которое выглядит как чистая функция чтения.
10. **`CustomFieldTrait::setValue()` завязан на `Telegram::log()` из внешнего пакета** (`mttzzz\LaravelTelegramLog\Telegram`, `Traits/CustomFieldTrait.php:9,81,99`) вместо стандартного логирования Laravel — жёсткая зависимость поверх зависимости, обход через `catch(Exception) → Telegram::log() → return null/дефолт` вместо проброса наверх.
11. **`EmailTrait`/`PhoneTrait` — почти дублирующийся код** (идентичная структура `*List/*Get/*Add/*Set/*Delete`, разница только в `field_code` и regex для phone). Не вынесено в общий generic-трейт с параметром кода поля.
12. **`Note::orderX()` перезатирает весь `$order`** каждым вызовом (`Models/Note.php:81,89,97,105`, коммент "обнуляем сортировку, потому как может быть только 1") — молчаливое поведение, отличное от `OrderTrait`, где `order[key]=val` **аккумулируется**; несогласованный контракт между похожими trait/model.
13. **`Webhook::find()` не использует `CrudTrait`** (`Models/Webhook.php:21-38`) — своя раздельная реализация фильтрации по `destination` с ручным разбором `_embedded.webhooks[0]`, без обёртки исключений в `AmoCustomException` (RequestException всплывёт как есть, единственная асимметрия относительно остальных `find()`).
14. **`User::find()`** (`Models/User.php:38-43`) — тоже вне `CrudTrait`/`AmoCustomException`, прямой `$this->http->get(...)->throw()->json()` без try/catch — RequestException всплывает необёрнутым, в отличие от всех других `find()`.
15. **Транспорт и очередной классификатор дублируют, но не совпадают по охвату** — retry-callback транспорта (`AmoClientOctane.php`) знает про 401/402/5xx/connection; `RetriesTransientAmoErrors::isTransientAmoError()` знает про те же категории + 402-spurious, но **не про 400 с retry-детейлом** (см. Б.4) — граница транзиентности определена в двух местах с разным охватом и без единого источника истины (нет общего enum/классификатора ошибок amo, на который могли бы ссылаться оба слоя).
16. **Нет rate-limit (429) обработки** нигде в транспорте или классификаторе — amoCRM API имеет официальный rate-limit (7 req/sec), но retry-callback не выделяет 429 в отдельную ветку (упадёт в generic `>=500`-ветку и не сработает, т.к. 429 < 500, то есть **429 вообще не ретраится и не ротирует прокси** — falls through в `return false`).

---

## Сводка

### Что новая либа обязана уметь

1. **Резолв аккаунта/токена по `account_id` из внешней БД `octane`** (accounts + widgets + account_widget join, `AmoClientOctane.php:83-97`) — это ядро value proposition библиотеки, единственная причина, почему потребитель не пишет OAuth-обмен сам. Обязателен для сохранения.
2. **Прозрачная 401-перечитка токена внутри retry** (`:184-200`) — потребитель (masterm) никогда не думает о протухании токена; это должно остаться прозрачным поведением транспорта.
3. **402 как типизированное исключение со снапшотом `payed`** (`AmoPaymentRequiredException`) — используется классификатором транзиентности (`RetriesTransientAmoErrors::isTransientAmoError`) и обеими джобами masterm (пусть и грубо, через `$e->getCode() !== 402`). Контракт `getCode()===402` **должен остаться стабильным** даже при переписывании — либо задокументирован как явный API (`instanceof`), либо сохранён по коду ради обратной совместимости консьюмеров, которые уже на него полагаются.
4. **Прокси-ротация + ретраи как переиспользуемый транспортный слой** — судя по `ProxyHttpService.php` в masterm (буквальная копипаста той же логики для не-amo HTTP), это стоит вынести в отдельный публичный компонент новой либы (`Transport`/`HttpClientFactory`), а не хоронить внутри конструктора `AmoClientOctane`. Один источник истины для "прокси-каскад + 5xx/connection ретрай" закроет и текущий, и будущий дублирующий код в потребителях.
5. **Единый классификатор transient/non-transient ошибок**, доступный и на уровне транспорта (retry внутри запроса), и на уровне очереди (`RetriesTransientAmoErrors`-подобный trait/сервис) — но **с одним источником истины**, не двумя пересекающимися, но не идентичными реализациями (см. Б.5 п.15). Обязательно покрыть: connection errors, 401 (после исчерпания refresh), 402 (payed-снапшот), 5xx, **и 400 с retry-детейлом в body** (сейчас дыра — уже чинилась ad hoc в octane.pushka.biz, `[[octane]]`-проекте, отдельным патчем поверх этой же либы, ищи `git log` `AmoCustomException`/`isTransient` в octane).
6. **Структурированное тело ошибки amo API в исключении**, а не только `message`-строка — `errors[].code`/`title`/`detail` как typed-поля на `AmoCustomException` (или наследнике), чтобы убрать `str_contains($e->getMessage(), 'Error 282')`-паттерн (`masterm ChangeTaskJob.php:284`) и дать потребителям официальный способ различать конкретные бизнес-ошибки amo без грепа текста.
7. **CRUD по `Task`/`Webhook`/`Pipeline`/`Account`/`Ajax`** — это единственные точки поверхности, реально нагруженные production-трафиком masterm (Часть А). Приоритет для новой архитектуры: эти пять — must-have day-one, с полным сохранением семантики (`filter*/order*/with*`-билдеры для `Task`; billing-free CRUD для `Webhook`; unwrap-паттерн `_embedded`).
8. **`LazyCustomFields`-подобный ленивый кэш** — концепция (не триггерить лишний SQL, пока реально не нужны custom fields) стоит сохранить, даже если реализация поменяется, ради того же pg_stat-обоснования (`[[pg-perf-audit-followup]]`).
9. **Ajax-канал** как низкоуровневый транспорт без доменной логики (`get/postJson/postForm/patch/delete`) — masterm строит поверх него два собственных приватных контракта (`get_managers_with_group`, `todo/calendar`) целиком в userland через `@phpstan-type`. Новая либа не обязана типизировать эти конкретные приватные эндпоинты (они не публичный amo API), но обязана сохранить сам транспорт с той же гибкостью (произвольный `url`+`payload`, отдельный baseUrl на веб-домен, `X-Requested-With`-заголовок).
10. **`account_amo_user`/`amo_users` резолв ответственного менеджера** (`CrudEntityTrait::setResponsibleUser/getResponsibleName`) — не используется напрямую в исследованных 10 файлах masterm, но раз это часть текущего публичного контракта сущностей (`Lead/Contact/...`), и masterm потенциально может начать использовать `Entities\Lead`/`Contact` в будущем — сохранить как минимум концептуально; стоит уточнить у другого потребителя (`octane.pushka.biz`), нагружен ли этот путь там (вне периметра этой задачи).

### Что можно не переносить (с обоснованием)

1. **`Facades\AmoClient` в текущем виде** — фасад нигде не биндится в контейнере (`AmoClientServiceProvider::register()` не делает `bind('amoclient', ...)`) и **нигде не используется** ни в одном исследованном потребителе (`grep "AmoClient::"` — 0 результатов в masterm). Либо чинить биндинг и явно документировать DI-путь, либо выкинуть совсем — сейчас это чистый мёртвый код, вводящий в заблуждение.
2. **Конфиг-ключ `amoclient.proxies`** — никогда не читается кодом (реальные прокси идут через `config('app.proxy')`/`config('app.secondProxy')`, вне неймспейса `amoclient.*`). Либо унифицировать источник прокси под один конфиг-неймспейс новой либы, либо убрать ключ вовсе — сейчас он активно вводит в заблуждение (существует в publish-конфиге, но не работает).
3. **`Event`-модель с ~60 `type*()`/`valueAfter*()`/`valueBefore*()` методами** (`Models/Event.php`, 629 строк) — **ни разу не используется** ни в одном из 10 файлов masterm. Огромная площадь поверхности (самый большой файл в либе) под фичу, для которой нет ни одного подтверждённого потребителя в исследованном проекте. Кандидат на замену компактным builder'ом с параметрами вместо перечисления каждого типа события как отдельного метода (тот же функционал, на порядок меньше кода) — но нужно сверить с `octane.pushka.biz`, использует ли она events, прежде чем резать функционал.
4. **`Entities\Unsorted\{Form,Sip}` + `Models\Unsorted`** — ноль использования в masterm. Не значит "не нужно вообще" (это, вероятно, специфика другого потребителя — приёма заявок с сайта/звонков), но не приоритет для MVP новой либы, если она стартует с реплики покрытия masterm.
5. **`ShortLink`, `Source`, `Call`, `CatalogElement`, `Catalog`, `CustomField`, `CustomFieldGroup`, `User`, `Link`, `Note`, `Customer`, `Company`, `Contact`, `Lead`** — весь набор моделей/сущностей, кроме `Task`/`Webhook`/`Pipeline`/`Account`, не встречается в masterm ни разу. Прежде чем резать — сверить с реальным использованием в `octane.pushka.biz` (второй потребитель, вне периметра этой задачи, но упомянут в plan/spec `docs/superpowers/*` как второй consumer с "свои" паттерны использования, судя по CLAUDE.md-контексту и заголовку коммита `97c96f8`).
6. **Дублирующиеся `EmailTrait`/`PhoneTrait`** — можно свернуть в один generic-трейт, параметризованный `field_code` (`'EMAIL'`/`'PHONE'`) — экономия кода без потери функционала, публичные методы (`emailList/phoneList` и т.д.) можно сохранить как тонкие фасады над общей реализацией.
7. **`OctanePipeline`-класс внутри `Entities/Lead.php`** — мёртвая/лживая PHPDoc-декорация (см. Б.5 п.3), реальный рантайм-тип — `stdClass`. Не переносить как есть; если нужен типизированный результат `DB::table('account_pipelines')->first()` — сделать честный маппинг с `instanceof stdClass`-гардом (по образцу `AmoClientOctane::convertToOctaneAccount`).
8. **`Helpers\Pipeline`/`Helpers\Widget`** как отдельные безымянные plain-DTO классы, дублирующие часть полей `OctaneAccount` — не используются потребителями напрямую (внутренние для либы), можно объединить/убрать по факту того, что реально требуется новой архитектуре резолва аккаунта.
9. **Ручные ad hoc retry-паттерны 5 из 9 job-классов masterm** (см. А.4 п.1) — это не часть библиотеки, но релевантный сигнал: если новая либа даст **единый, простой, документированный** API для retry/backoff (замена `RetriesTransientAmoErrors`), у masterm появится стимул мигрировать оставшиеся 5 джоб на него, закрыв текущую несогласованность. Не задача переписывания либы напрямую, но её прямое следствие — стоит зафиксировать как ожидаемый бонус-эффект.

---

*Файл дописывался инкрементально в ходе исследования; Часть А и Часть Б читались параллельно двумя проходами по коду (без изменений в обоих репозиториях).*
