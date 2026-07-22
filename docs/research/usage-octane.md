# Инвентарь использования `mttzzz/amoclient` в octane.pushka.biz

Дата: 2026-07-22. Версия либы в composer.json: `^3.0`. Библиотека читалась из `/home/mttzzzz/projects/amoclient/src`, инвентарь строился по вызовам в `/home/mttzzzz/projects/octane.pushka.biz`.

Метод: `grep -rn` по `app/` (Laravel-приложение, других директорий с бизнес-кодом нет). Facade `mttzzz\AmoClient\Facades\AmoClient` **нигде не используется** — во всём проекте клиент создаётся прямым `new AmoClientOctane(...)` (64 сайта, см. §5).

---

## 1. Сущности (entities) — используются / нет

| Сущность (свойство `AmoClientOctane`) | Используется | Мест (`->prop->method(`) | Комментарий |
|---|---|---|---|
| `leads` | ✅ | 85 | Самая нагруженная сущность, весь Sensei/HandlerForm-слой на ней |
| `contacts` | ✅ | 39 | |
| `companies` | ✅ | 29 | |
| `account` | ✅ | 2 факт. (не 81 — см. ниже) | `$amo->account->get()`, `->withAmojoId()->get()` |
| `catalogs` | ✅ | 9 (+ `->elements` под-цепочка) | `catalogElements` как отдельная сущность не существует — доступ только через `->catalogs->entity($id)->elements` |
| `unsorted` | ✅ | 7 | Asterisk call-flow (`CreateUnsortedJob`) |
| `tasks` | ✅ | 3 | `CloseAllTasksJob`, `CloseCalendlyTaskJob` |
| `events` | ✅ | 3 | Только `SetCallCountInLeadJob` (подсчёт звонков по lead/company/contact) |
| `sources` | ✅ | 4 | `kufarAccounts.php` (CRUD виджет-источника), `DisconnectChannelsJob` (это уже Eloquent-модель `AccountWidgetSource`, не либа) |
| `shortLinks` | ✅ | 3 | `TransformUrlToShortAmoUrlJob`, `WidgetRoutes/shortLinks.php` |
| `users` | ✅ | 4 | Только `->find($id)`, нигде `filter*`/`page` |
| `pipelines` | ✅ | 1 | Только `->each()` в `PipelinesStatusesHandler` (bulk-синхронизация статусов воронок) |
| `calls` | ✅ | 1 | `PushCallToCrmJob` |
| `webhooks` | ❌ | 0 | Не используется вовсе (подписки на вебхуки в octane не создаются через либу) |
| `customers` | ❌ | 0 | Не используется вовсе (нет ни одного упоминания `Models\Customer`/`Entities\Customer`) |
| `catalogElements` (как top-level) | — | — | В либе нет отдельного top-level accessor'а; всё идёт через `catalogs->entity()->elements` |

Важный нюанс по `->account->`: в коде 81 текстовое совпадение `->account->`, но подавляющее большинство — это Eloquent-отношение `AccountWidget::account` / `Account`-модель октейна (`$accountWidget->account->subdomain` и т.п.), **не** сущность либы. Реальных вызовов сущности amoclient `account` — 2: `app/Console/Commands/CheckAmoCRMAccounts.php:115` и `app/Jobs/WidgetOauthJob.php:199`.

---

## 2. Методы, вызываемые на сущностях

### 2.1 CRUD / чтение

| Метод | Где (примеры `file:line`) | Кол-во мест (по грепу) |
|---|---|---|
| `->entity($id)` / `->entity()` (билдер) | `app/Http/Controllers/Api/OtherController.php:21`, `app/Http/Controllers/Api/ChromeController.php:42` | leads 40, contacts 18, companies 10 |
| `->find($id)` (готовая сущность одним запросом) | `app/Services/Clients/Easy/EasyNotificationService.php:243` (`$amo->users->find($responsibleUserId)`), `app/Jobs/HandlerForm/HFVinseraSecondFormJob.php:34` | leads 10, contacts 7, companies 8, users 4 |
| `->entityData($array)` (гидрация из уже полученного `_embedded`-массива, без нового запроса) | `app/Jobs/Sensei/MergePDFJob.php:54` (`$amo->catalogs->entity()->elements->entityData($element)`) | leads 5, contacts 4, companies 3 |
| `->create()` / `->update()` | `app/Http/Controllers/Api/OtherController.php:21` (`...->setCF(...)->update()`), `app/Jobs/Sensei/TransformUrlToShortAmoUrlJob.php:34` (`->create()`) | повсюду вместе с `setCF`/`link` |
| `->query($string)` (телефон/email/произв. строка полнотекстового поиска) | `app/Jobs/HandlerForm/HFGeneralJob.php:377-394` (дедуп по телефону/email для contacts и companies), `app/Services/Asterisk/ClientInfoService.php:46,61`, `app/Services/Asterisk/PopupLinkBuilder.php:49,56` | contacts 5, companies 3 |
| `->withLeads()` | `app/Services/Asterisk/PopupLinkBuilder.php:49` | 13 (contacts+companies) |
| `->withContacts()` | `app/Jobs/HandlerForm/HFGeneralJob.php` (загрузка сделки вместе с контактами) | 12 |
| `->withCatalogElements()` | leads — подгрузка привязанных элементов каталога вместе со сделкой | 7 |
| `->each($callback, $chunkSize?)` (постраничный обход без ручного `page()`-цикла) | `app/Jobs/AccountUpdate/Handlers/PipelinesStatusesHandler.php:65`, `app/Services/WidgetRoutes/anotherLeads.php` (companies) | pipelines 1, companies 1 |
| `->allItems()` (весь результат без пагинации, целиком) | `app/Models/Account.php:261` (`catalogs->entity($catalogId)->elements->allItems()`), `app/Exports/AmoCatalogElementExport.php:22,53` | 3 |
| `->page($n)->limit($n)->get()` (ручная пагинация) | `app/Services/Clients/Mogo/LeadSyncService.php:55`, `app/Jobs/Sensei/AddCatalogElementsDPJob.php:46` | 2 |
| `->delete()` | `app/Services/WidgetRoutes/kufarAccounts.php:146` (`sources->entity($id)->delete()`) | 1 |

### 2.2 Фильтры (`->filter*`)

| Метод | Примеры | Кол-во |
|---|---|---|
| `->filterId($id\|array)` | `app/Services/WidgetRoutes/anotherLeads.php` (companies), `app/Jobs/Sensei/CalcElementQuantityJob.php:58` (catalog elements) | 18 |
| `->filter($closure\|array)` (сырой/произвольный фильтр) | разное | 8 |
| `->filterUpdatedAt(...)` | синхронизация по времени изменения (Mogo/EasyStandart sync jobs) | 4 |
| `->filterLeadFields(...)` | HandlerForm-слой | 3 |
| `->filterPipelines(...)` | синк-джобы по воронкам | 2 |
| `->filterIsCompletedFalse()` + `->filterEntityId()` | `app/Jobs/Sensei/CloseAllTasksJob.php:30` (открытые задачи по сделке) | 2 each |
| `->filterBy(...)` | разное | 2 |
| `->filterName(...)` | `app/Jobs/HandlerForm/HFGeneralJob.php:1159` (`companies->filterName(...)->get()`) | 1 |
| `->filterUid / filterStatuses / filterLead / filterCallOut / filterCallIn` | по одному месту каждый | 1 each |

### 2.3 Кастомные поля / теги / связи

| Операция | Примеры | Кол-во |
|---|---|---|
| `->setCF($fieldId, $value)` (запись значения кастом-поля в билдер перед `create()/update()`) | `app/Http/Controllers/Api/OtherController.php:21`, `app/Jobs/Sensei/SetCallCountInLeadJob.php:49`, `app/Jobs/Widgets/Trello/TrelloDPWebhookJob.php:91` | 33 |
| `->getCF($fieldId)` (чтение значения из уже загруженной сущности) | `app/Jobs/Sensei/SpreedSheetMultiSelectRelationJob.php:52` | 1 (единственное место!) |
| `->tag($array)` (простановка тегов при create/update) | `app/Jobs/HandlerForm/HFGeneralJob.php:501,567,594` (lead/contact/company) | 3, все в одном файле |
| `->links->link($array)` / `->unlink($array)` | `app/Jobs/Sensei/AddCatalogElementsJob.php:43`, `app/Jobs/Sensei/UnlinkCatalogElementJob.php:43,48`, `app/Jobs/HandlerForm/HFGeneralJob.php:663,666` (`links->contact(...)->link()`, `links->companies(...)->link()`) | 10 |
| `->customFields()` (метаданные полей сущности, не значения) | `app/Exports/AmoCatalogElementExport.php:22` (`catalogs->entity($id)->customFields()->allItems()`), plus `leads/contacts/companies->customFields()` по 1 разу | 4 |
| `notes->entity()->common($text)` / `->serviceMessage($text, $source)` | `app/Jobs/Document/DocumentCreateNoteJob.php:57`, `app/Jobs/HandlerForm/HFGeneralJob.php:805,997` | 8 |
| `->status($id)` (смена статуса звонка/сделки) | `app/Jobs/Asterisk/PushCallToCrmJob.php:68` | 1 |

**Важно:** `getCF` асимметричен `setCF` (33 записей против 1 чтения) — почти вся логика читает кастомные поля напрямую из сырого `_embedded`/`custom_fields_values` массива (`data_get(...)`), а не через метод сущности. Смотри §4.

---

## 3. Ajax-канал (`$amo->ajax->...`) — приватное браузерное API amo

Все 7 мест использования, сгруппированы по URL:

| URL | Метод | Payload / query | Где |
|---|---|---|---|
| `api/v4/users/{userId}` | `get` | — | `app/Jobs/Document/MainGenerateDocJob.php:452` |
| `/api/v4/account` | `get` | `['with' => 'datetime_settings']` | `app/Jobs/AccountUpdate/AccountUpdateJob.php:192` — читает `_embedded.datetime_settings.timezone` |
| `ajax/v4/sources/{source->id}` | `patch` | правка источника (виджет-канал) | `app/Services/WidgetRoutes/kufarAccounts.php:94` |
| `/api/v2/salesbot/run` | `postJson` | `[$data]` (запуск салesbot-сценария) | `app/Jobs/Widgets/Trello/TrelloGeneralJob.php:77` |
| `ajax/v2/multiple/leads/chat_send` | `postJson` | отправка сообщения в чат сделки | `app/Jobs/Sensei/ChatSendAmocrmJob.php:29` |
| `ajax/v1/links/set/` | `postForm` | `['request' => ['links' => ['link' => $links]]]` | `app/Jobs/Sensei/CalcElementQuantityJob.php:98` |
| `ajax/get_managers_with_group/` | `get` | — (парсит группы менеджеров, не покрыто `users`-сущностью) | `app/Jobs/AccountUpdate/Handlers/UsersHandler.php:143` |

Наблюдения:
- Ajax-канал вызывается только через `get`/`postJson`/`postForm`/`patch` — `delete()` из `Ajax.php` в octane не используется вовсе.
- Часть URL смешивает `api/v4/...` (официальный REST, но не описанный в публичных сущностях либы — `account`/`datetime_settings`, `users/{id}`) и настоящие `ajax/*`/`ajax/v*` приватные эндпоинты (`get_managers_with_group`, `links/set`, `multiple/leads/chat_send`, `sources/{id}` под префиксом `ajax/v4`). Т.е. канал реально используется как «эскейп-люк» на два разных случая: (а) официальный REST-метод, для которого в либе просто нет сущности/метода (`account.datetime_settings`, `users/{id}` детали, salesbot run), и (б) недокументированный браузерный ajax.
- `ajax/get_managers_with_group/` — единственный источник группировки менеджеров по группам; сущность `users` этого не отдаёт вовсе (`UsersHandler` ходит в ajax именно поэтому).

---

## 4. Точки боли (обходные пути вокруг либы)

### 4.1 Классификация ошибок целиком через `getCode()` + ручной парсинг message/detail

Единая точка входа — `app/Jobs/AbstractBaseJob.php:60-89`. `isTransient()`:
- `ConnectionException` → транзиент;
- `getCode() in [502,503,504]` → транзиент;
- **особый случай**: `AmoCustomException` с `getCode() === 400`, где реальный HTTP-статус — не то, что нужно смотреть; код читает `getPrevious()` (сырой `RequestException`), достаёт JSON `detail` и матчит `str_contains(mb_strtolower($detail), 'повторите попытку')` — amoCRM отдаёт транзиентный сбой своего бэкенда как обычный 400 с телом-подсказкой (see commit `1bc68a79`, `OCTANE-PUSHKA-BIZ-9Q`). Библиотека не даёт структурного способа отличить «настоящий 400-валидатор» от «400, который на самом деле retry-directive» — потребитель вынужден руками лезть в `getPrevious()->response->json()`.

Похожие ad-hoc парсеры `message`/JSON-тела `AmoCustomException` (сообщение — это либо JSON, либо сырая строка, единого контракта нет, приходится `json_decode` и гадать формат):
- `app/Jobs/Document/DocumentCreateNoteJob.php:71` `isIgnorableAmoError()` — `json_decode($e->getMessage(), true)`, ищет `errors.0.code === 226` (лид удалён, amoCRM вернул 400 вместо 404) плюс отдельно код 402.
- `app/Jobs/Sensei/CopyValueFieldJob.php:71` `isLeadNotFound()` — то же самое: 404 напрямую, либо 400 + `errors.{id}` = "Lead not found" для bulk-PATCH.
- `app/Exceptions/Handler.php:145-160` `isTransientExternalApiError()` — центральный классификатор для Sentry-фильтра: матчит **фиксированную строку** `'Unknown error (ConnectionException)'` (это buildin-текст из конструктора `AmoCustomException` для случая, когда исходное исключение — `ConnectionException` без нормального ответа), плюс коды 402/404 как «состояние клиента, не баг».
- `app/Services/WidgetRoutes/anotherLeads.php:42,132` — код 404 → «сущность удалена, тихо возвращаем пусто», без ретрая.
- `app/Services/WidgetRoutes/shortLinks.php:71` — то же самое (404 → not found, не шумим).

Итог: **пять независимых копий** логики «прочитать HTTP-код/строку из `AmoCustomException` и решить транзиентно оно или нет» в разных файлах, вместо одного метода в либе (`isTransient()`/`isNotFound()`/`isSpuriousPaymentRequired()` и т.п. на самом исключении).

### 4.2 `AmoPaymentRequiredException::accountPayed` / `isSpurious()` — спроектированы, но не используются

В либе `src/Exceptions/AmoPaymentRequiredException.php` есть специально заведённый механизм: конструктор сам делает DB-запрос `accounts.payed` в момент ошибки и кладёт снапшот в `$exception->accountPayed`, плюс метод `isSpurious()` для «402 — ложь амо, а не реальная неоплата».

**В octane это API не вызывается нигде** (`grep isSpurious|accountPayed` — 0 попаданий в `app/`). Вместо этого `app/Jobs/AccountUpdate/AccountUpdateJob.php` реализует ту же самую идею полностью заново, только на уровне джобы:
- `isTransient()` (строка 44-58) — не верит первым двум 402 по количеству *попыток джобы* (`$this->attempts() < $this->tries`), а не по факту оплаты;
- `handlePaymentRequiredError()` (строка 303-312) — только на последней попытке сам пишет `$this->account->update(['payed' => false])`;
- обоснование в комментариях — эмпирическое измерение живого биллинг-флапа amoCRM (~9 минут, инцидент `MASTERM-PUSHKA-BIZ-35`), никак не связанное с механизмом библиотеки.

Т.е. `AmoPaymentRequiredException` фактически используется только как **обычный `AmoCustomException` с кодом 402** (`match ($th->getCode())`), сама подкласс-специфика (accountPayed snapshot at throw-time) octane не нужна — у него своя, более сложная модель ретраев на уровне очереди.

### 4.3 `RetriesTransientAmoErrors` (trait в либе, `src/Queue/RetriesTransientAmoErrors.php`) — не используется вовсе

`grep -rl RetriesTransientAmoErrors app` → пусто. Вместо трейта из либы octane написал свой `App\Jobs\AbstractBaseJob` (общий базовый класс для *всех* джоб проекта, не только amo-джоб) с собственными `isTransient()`/`backoff()`/`canBeReleased()` — то есть весь retry-слой библиотеки, ради которого трейт существует, продублирован на уровне приложения и вдобавок расширен под неamo-специфику (`canBeReleased()` проверяет `SyncJob`, что вообще не про amo).

### 4.4 Ручной парсинг сырого ответа вместо сущностей

- Кастомные поля читаются в основном как сырой массив: `data_get($response, '_embedded...')`/`custom_fields_values` напрямую, а не через `getCF()` (см. §2.3 — 33 `setCF` против 1 `getCF`).
- `Ajax`-канал (§3) — весь ответ приходится разбирать вручную (`data_get($data, '_embedded.datetime_settings.timezone')` и т.п.), т.к. `Ajax::get/postJson/...` возвращают голый `array`, а не типизированную сущность.
- `app/Jobs/AccountUpdate/Handlers/UsersHandler.php:143` — `ajax/get_managers_with_group/` вызывается именно потому, что сущность `users` не даёт группировки; результат парсится вручную.

### 4.5 Собственные ретраи поверх встроенного retry в `AmoClientOctane`

Библиотека уже делает ретраи внутри `Http::retry(...)` конструктора (`AmoClientOctane.php:156-234`, включая переключение прокси, 401-race и 402-типизацию). Тем не менее очередной слой ретраев живёт в `AbstractBaseJob`/`AccountUpdateJob` (release + backoff по попыткам джобы). Это не дублирование одного и того же уровня (HTTP-запрос vs джоба целиком), но означает, что у потребителя нет единого места «сколько раз и как ретраится конкретный сбой» — решение размазано между конструктором клиента, `AbstractBaseJob` и оверрайдами (`AccountUpdateJob::isTransient()`).

---

## 5. Как создаётся клиент

- **Единственный способ** — `new AmoClientOctane($accountId, ?$clientId, ?$proxy)`. Facade `mttzzz\AmoClient\Facades\AmoClient` в проекте не используется вовсе (0 вызовов), DI через контейнер тоже не настроен под интерфейс — только конкретный класс инжектится вручную в конструкторы джоб/хендлеров (`AbstractUpdateHandler.php:13`, `UsersHandler`).
- **64 сайта прямого инстанцирования** по всему `app/` (полный список собран через `grep -rn "new AmoClientOctane("`), включая:
  - Jobs (большинство — по одному разу за `execute()`, аккаунт передаётся из данных джобы);
  - Controllers (`ChromeController`, `VinceraController`, `OtherController`, `UnsortedInformerController`) — создают клиент прямо в экшене на основе `$r->integer('accountId')` или захардкоженного ID (`VinceraController.php:33,107` и `HFVinseraSecondFormJob.php:33,34` — literal `7349356`, это единственный жёстко закодированный account id для Vincera-интеграции; аналогично `EasyNotificationService::AMOCRM_ACCOUNT_ID` для Easy);
  - **Мемоизация на уровне Eloquent-моделей**: `App\Models\Account::amoClient($clientId = null)` (`app/Models/Account.php:157-167`) и `App\Models\AccountWidget::amo()` (`app/Models/AccountWidget.php:138-144`) — оба кэшируют инстанс `AmoClientOctane` в protected-поле модели (`$amoClientCache`), чтобы не пересоздавать клиент (= не бить лишний раз в БД за токеном) при нескольких обращениях в рамках одного request/job lifecycle. Это ключевой паттерн повторного использования — `$account->amoClient()` / `$accountWidget->amo()` встречается в большинстве Http/Service слоя вместо `new AmoClientOctane`.
- **Токены**: сам конструктор клиента достаёт `access_token` из `octane`-БД (`accounts`/`account_widget`/`widgets` join) внутри себя (`AmoClientOctane.php:83-97`) — потребитель никогда не передаёт токен явно, только `accountId`(+`clientId` виджета, если их несколько на один аккаунт).
- **Прокси**: конфигурируется в 3 источника с приоритетом: явный `$proxy`-аргумент конструктора → `config('app.proxy')` → `config('app.secondProxy')` → без прокси. `config/amoclient.php` держит закомментированные IP реальных прокси (РБ/РФ датацентры) как справочную информацию, но сам файл конфига **не читает** `proxies` в рантайме конструктора — массив прокси там просто не используется кодом библиотеки (constructor строит свой `$proxies` заново из `app.proxy`/`app.secondProxy`, `config('amoclient.proxies')` нигде не читается — мёртвый конфиг-ключ).
- **Таймауты/ретраи**: читаются из `config/amoclient.php` через `Config::get('amoclient.timeout' | 'connectTimeout' | 'retries' | 'retryDelay')`, с дефолтами `60/10/3/2000` в самом коде конструктора (не совпадают с дефолтами, записанными в `config/amoclient.php`: там `60/20/2/2000` — то есть закомментированный файл конфига реально переопределяет `connectTimeout` 20 вместо кодового дефолта 10, и `retries` 2 вместо 3).
- **Верификация TLS**: `Config::get('amoclient.verify')` — в `config/amoclient.php` такого ключа вообще нет (не задокументирован), значит везде `null` → Laravel HTTP client дефолтит на `true`.

---

## 6. Очереди/джобы

- **Все** amo-вызовы, кроме нескольких HTTP-контроллеров (`ChromeController`, `OtherController`, `VinceraController`, `UnsortedInformerController`), идут из `ShouldQueue`-джоб, унаследованных от `App\Jobs\AbstractBaseJob` (`app/Jobs/AbstractBaseJob.php`) — общий базовый класс для *всех* джоб проекта (не специфичный для amo), с единой `handle()` → `execute()` (абстрактный) → `catch` → `isTransient()`-развилка release/fail.
- `RetriesTransientAmoErrors` (трейт из либы) **не используется** — см. §4.3. Вместо него вся логика в `AbstractBaseJob` + точечные оверрайды:
  - `AccountUpdateJob::isTransient()` (§4.2) — расширяет базовую транзиентность под свою 402-семантику;
  - остальные джобы **не** переопределяют `isTransient()`, полагаются на базовую (ConnectionException / 502-504 / amo-400-с-detail-«повторите попытку»).
- `tries = 3` в production, `1` в остальных окружениях (`AbstractBaseJob.php:29`); `backoff() = [1, 5, 10]` по умолчанию, `AccountUpdateJob` переопределяет на `[600, 600]` под измеренный ~9-минутный биллинг-флап (§4.2).
- Джобы, которые явно и осознанно матчят конкретные HTTP-коды amo (за пределами общей `isTransient()`): `AccountUpdateJob` (401/402/403/404 — разные ветки восстановления, §4.2), `DocumentCreateNoteJob` (402/226), `CopyValueFieldJob` (404 / bulk-400), `WidgetRoutes/anotherLeads.php` и `WidgetRoutes/shortLinks.php` (404 → тихий пропуск).

---

## 7. Минимально необходимая поверхность новой библиотеки

Обязано быть, иначе ломается реальный код:

- **Сущности**: `leads`, `contacts`, `companies`, `account`, `catalogs` (+ вложенные `elements`/`customFields()` под каталогом), `unsorted`, `tasks`, `events`, `sources`, `shortLinks`, `users` (только `find`), `pipelines` (только bulk `each`), `calls` (одно место, но нетривиальный `status()`-метод для смены статуса звонка).
- **Методы чтения**: `entity($id)`/`entity()` billder, `find($id)`, `entityData($rawArray)`, `query($string)` (поиск по телефону/email/произвольной строке), `withLeads()`, `withContacts()`, `withCatalogElements()`, `each($cb, $chunkSize)`, `allItems()`, ручная `page()/limit()/get()`.
- **Фильтры**: `filterId`, `filter` (сырой), `filterUpdatedAt`, `filterLeadFields`, `filterPipelines`, `filterIsCompletedFalse` + `filterEntityId`, `filterName`, `filterBy`; менее ходовые (`filterUid/filterStatuses/filterLead/filterCallOut/filterCallIn`) — по одному месту, но реальны.
- **Запись**: `create()`, `update()`, `delete()`, `setCF($id, $value)`, `tag($array)`, `links->link()/unlink()/contact()/companies()`, `notes->entity()->common()/serviceMessage()`.
- **Ajax-канал**: `get/postJson/postForm/patch` (без `delete` — не используется), причём должен покрывать и официальные `api/v4/*`-эндпоинты без выделенной сущности (`account?with=datetime_settings`, `users/{id}` details, `/api/v2/salesbot/run`), и настоящие приватные `ajax/*` (`get_managers_with_group`, `links/set`, `multiple/leads/chat_send`, `ajax/v4/sources/{id}`). Новая либа должна явно узаконить этот смешанный статус, а не просто продолжать «эскейп-люк».
- **Клиент**: конструктор по `accountId` (+опционально `clientId` виджета, `proxy`), сам резолвящий токен из БД; обязательна поддержка **мемоизации на вызывающей стороне** (паттерн `Account::amoClient()`/`AccountWidget::amo()` должен остаться дешёвым — т.е. конструктор не должен тяжелеть).
- **Ошибки**: структурная замена всему §4.1 — исключение должно само уметь ответить «это транзиент?» / «это not-found?» / «это retry-directive от самого amo (400 + detail)?» без ручного `getPrevious()->response->json()` и `str_contains` по русской строке. Это единственный пункт, где новая либа должна дать **больше**, чем старая: 5 независимых копий одной и той же классификации — прямое доказательство недостающей абстракции.
- **Ретраи внутри HTTP-слоя**: сохранить механику `AmoClientOctane` (прокси-failover, 401-race-refetch токена, 402 → типизированное исключение) — она используется и работает, просто не отражена структурно наружу (см. `AmoPaymentRequiredException`).

## 8. Кандидаты в выброс

- **`webhooks`** сущность — 0 использований в octane.
- **`customers`** сущность — 0 использований в octane.
- **`AmoPaymentRequiredException::accountPayed` / `isSpurious()`** — спроектированы, но ни разу не вызваны потребителем; вся логика «спурious ли 402» переехала в `AccountUpdateJob` на уровне джобы с собственной эмпирикой. Либо выкинуть, либо (лучше) синхронизировать: новая либа должна дать именно то API, которым сейчас реально пользуется `AccountUpdateJob` (доступ к номеру попытки/времени so it can decide, а не готовый DB-snapshot at throw-time, который никому не нужен).
- **`RetriesTransientAmoErrors` trait** (`src/Queue/`) — 0 использований; вся ретрай-логика на джобах написана заново в `AbstractBaseJob` приложения. Либо трейт не нужен вовсе, либо он спроектирован не под ту форму, в которой реально нужен потребителю (базовый класс на все джобы, а не trait только для amo-джоб).
- **`Ajax::delete()`** — не используется (только `get/postJson/postForm/patch`).
- **`getCF()`** — формально используется (1 место), но 33 места читают кастомные поля напрямую из сырого ответа; если новая либа хочет реально закрыть этот путь, `getCF()`/типизированный доступ к custom fields должен стать удобнее сырого `data_get`, иначе останется декоративным.
- **`config('amoclient.proxies')`** — ключ существует в `config/amoclient.php`, но конструктор `AmoClientOctane` его не читает (использует `app.proxy`/`app.secondProxy` напрямую). Мёртвый конфиг.
- **`config('amoclient.verify')`** — читается кодом, но в `config/amoclient.php` такого ключа нет — не задокументированная зависимость.
