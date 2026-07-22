# tests-core — трекинг созданных в amo сущностей

Зона: 13 файлов из промпта лида. Ниже — по каждому файлу таблица «тест → тип → где создаётся → затрекан/нет/почему».
Контракт `BaseAmoClient::track(string $type, int $id): int` — реализует `teardown-core` параллельно; я его не трогал, только вызываю.

## Второй проход (маркер в payload, `BaseAmoClient::marked(string $value): string`, `teardown-core-2`)

Задача: каждая сущность должна нести маркер не только в реестре (in-memory), но и в своих
данных — иначе после фатала до `tearDownAfterClass()` она пропадёт из реестра вместе с процессом,
а свип по маркеру её уже не найдёт. Прошёл те же 5 файлов, где что-то реально создаётся:

| Файл | Что промаркировано |
|---|---|
| `AbstractModelTest.php` | `$name = $this->marked(uniqid('name_', true))` в `test_all_items`/`test_each` — единственная переменная, используется и в `entityData`, и в `filterName`, и в ассерте, конфликтов нет |
| `CallTest.php` | `$source = $this->marked('asterisk')` — поле `source` звонка (в этой либе у `Call` нет вложенного `params`, `source`/`link` — верхнеуровневые свойства; выбрал `source`, т.к. `link` семантически URL и суффикс мог бы выглядеть как порча ссылки, хотя технически либа шлёт его как строку без валидации формата) |
| `CatalogTest.php` | `setUp()`: `'name' => $this->marked('Test Catalog')`; `test_catalog_update`: `$newName = $this->marked('Test Catalog2')`; `test_catalog_element`: оба элемента (`'test element'`, `'TestElement entityData'` — второй завёл в переменную `$elementName2`, т.к. был буквально продублирован в ассерте) + переименование `'test 3'` при `update()` элемента |
| `CompanyTest.php` | `setUp()`: `'name' => $this->marked('Test Company')`; `test_company_update`: `$newName = $this->marked('Test Company 2')` |
| `ContactTest.php` | симметрично Company: `setUp()` + `test_contact_update` |

**Осознанно НЕ тронул** (важно, чтобы не сломать автозаменой): `test_company_query('Test Company')`
и `test_contact_query('Test Contact')` — литерал там не сравнивается в ассерте с промаркированной
строкой, а используется как full-text `query()` поиск amo (substring/prefix-match) уже
переименованной к моменту выполнения (порядок #[Depends]: `update` идёт раньше `query`) сущности.
Короткий немаркированный литерал `'Test Company'` остаётся substring-совпадением независимо от
того, добавляет ли `marked()` суффикс или префикс к полному имени (`'Test Company 2 <маркер>'`
или `'<маркер> Test Company 2'` — оба содержат `'Test Company'` как подстроку). Если бы я
пробросил туда `$this->data['name']` (= `marked('Test Company')`), а `marked()` добавляет суффикс,
после `test_company_update` строка поиска `'Test Company <маркер>'` перестала бы быть подстрокой
фактического имени `'Test Company 2 <маркер>'` (переставился порядок токенов) — тест сломался бы
именно тем самым автозаменочным рефлексом, от которого лид предостерёг.

`marked()` в `BaseAmoClient` ещё не существует на момент написания (её добавляет `teardown-core-2`
параллельно) — вызовы написаны по контракту из сообщения лида, компиляция ждёт их коммита, как
раньше было с `track()`.

## tests/AbstractModelTest.php

| Тест | Тип | Где создаётся | Статус |
|---|---|---|---|
| `test_all_items` | leads | `entityData(...)->createGetId()` | затрекан |
| `test_each` | leads (×2) | `entityData(...)->createGetId()` дважды | затрекан оба |
| `test_limit` | — | только чтение (`->limit(10)->get()`) | не создаёт, не трогал |

Обе `test_all_items`/`test_each` сами удаляют лиды в конце теста через `/ajax/leads/multiple/delete/` —
трекинг всё равно добавлен: если ассерт между create и delete упадёт, delete не выполнится и лид
останется в аккаунте без registry.

## tests/AccountTest.php

Все тесты — либо реальный `account->get()` (чтение), либо моки `PendingRequest`/`Account` через
`createMock`, ответы не бьют в реальный amo. Ничего не создаётся. Файл не трогал.

## tests/AjaxTest.php

Все тесты бьют в `jsonplaceholder.typicode.com` (внешний mock API для проверки HTTP-методов `Ajax`),
не в amo. Ничего не создаётся в боевом аккаунте. Файл не трогал.

## tests/AmoClientFacadeTest.php

Чистый unit фасада с `stdClass`-заглушкой вместо клиента, `extends TestCase` (не `BaseAmoClient`,
`track()` недоступен). Ничего не создаётся. Файл не трогал.

## tests/AmoClientOctaneTest.php

Только чтение из `DB::connection('octane')` (accounts/widgets) + конструирование `AmoClientOctane`
(включая two negative-тесты на исключение). Ни одного HTTP-вызова к amo API, тем более create.
Файл не трогал.

## tests/AmoClientServiceProviderTest.php

Чистый unit service provider (`register()`/`provides()`), `extends TestCase`, своя минимальная
DI-контейнер-обвязка. Ничего не создаётся. Файл не трогал.

## tests/AmoCustomExceptionTest.php

Все тесты конструируют `AmoCustomException` из мокнутых `ConnectionException`/`RequestException`
с ручным `GuzzleResponse`, `extends TestCase`. HTTP не бьёт никуда. Ничего не создаётся. Файл не трогал.

## tests/CallTest.php

| Тест | Тип | Где создаётся | Статус |
|---|---|---|---|
| `test_call_create` | calls | `$call->...->create()`, id из `_embedded.calls[0].id` | затрекан |
| `test_call_filter_by_result` | — | `$call->create()`, но ожидается `AmoCustomException` (`expectException`) — до создания не доходит | не создаёт, не трогал |
| `test_call_create_exception` | — | то же самое, дубль предыдущего теста по факту | не создаёт, не трогал |

## tests/CatalogTest.php

| Тест | Тип | Где создаётся | Статус |
|---|---|---|---|
| `test_catalog_create` | catalogs | `$this->catalog->create()`, id из `_embedded.catalogs[0].id`, `return` — чейнится в `#[Depends]` | затрекан, `return $this->track(...)` |
| `test_catalog_update` | — | только update существующего (id из `#[Depends]`) | не создаёт, passthrough `return $catalogId` не трогал |
| `test_catalog_element` | catalogElements (×2) | `$elementEntity->create()` (было без захвата ответа — захватил) и `$elementEntity2->create()` (entityData-вариант) | затрекан оба |
| `test_catalog_delete` | — | удаляет уже затрекан. catalog raw ajax'ом (Task 4 lib-delete ещё не подменил на `->delete()`) | не создаёт, не трогал |
| `test_catalog_create_get_id` | catalogs | `$this->catalog->createGetId()` | затрекан |
| `test_catalog_not_found` | — | только `find()` несуществующего id | не создаёт, не трогал |
| `test_catalog_create_exception` / `test_catalog_update_exception` | — | ожидают `AmoCustomException` на `create()`/`update()` без данных — до создания не доходит | не создаёт, не трогал |

Примечание про `catalogElements`: тип не удаляется явно в тесте (`test_catalog_delete` сносит
только сам каталог через `/ajax/v1/catalogs/set/`, элементы предположительно уходят каскадом).
Трекаю по букве контракта (тип в списке лида) — реши `teardown-core`/лид, нужен ли для него
отдельный механизм удаления или он no-op после каскада.

## tests/CompanyTest.php

| Тест | Тип | Где создаётся | Статус |
|---|---|---|---|
| `test_company_create` | companies | `$this->company->create()`, id из `_embedded.companies[0].id`, `return` — чейнится | затрекан, `return $this->track(...)` |
| `test_company_update` / `test_company_get_lead_ids` / `test_company_custom_fields` / `test_company_query` | — | update/чтение существующего id из `#[Depends]`, ничего нового не создают | не создают, passthrough `return $companyId` не трогал |
| `test_company_delete` | — | удаляет уже затрекан. company через ajax | не создаёт, не трогал |
| `test_company_create_get_id` | companies | `$this->company->createGetId()` | затрекан |
| `test_company_not_found` / `test_company_set_responsible_user` / `test_company_get_responsible_name` | — | локальная модель, DB-чтение, ничего в amo | не создают, не трогал |

## tests/ContactTest.php

Структурно зеркало `CompanyTest.php`.

| Тест | Тип | Где создаётся | Статус |
|---|---|---|---|
| `test_contact_create` | contacts | `$this->contact->create()`, id из `_embedded.contacts[0].id`, `return` — чейнится | затрекан, `return $this->track(...)` |
| `test_contact_update` / `test_contact_get_lead_ids` / `test_contact_custom_fields` / `test_contact_query` | — | update/чтение существующего id | не создают, passthrough не трогал |
| `test_contact_delete` | — | удаляет уже затрекан. contact через ajax | не создаёт, не трогал |
| `test_contact_create_get_id` | contacts | `$this->contact->createGetId()` | затрекан |
| `test_contact_not_found` / `test_contact_set_responsible_user` / `test_contact_get_responsible_name` | — | локальная модель, DB-чтение | не создают, не трогал |

## tests/CustomFieldGroupTest.php

`test_custom_field_group_entity` — `$this->customFieldGroup->entity(123)` конструирует локальный
объект-обёртку над существующим id=123, HTTP-вызова нет (не `find`/`create`). Ничего не создаётся.
Файл не трогал.

## tests/CustomFieldTest.php

Несмотря на имя `test_custom_field_create`, тест **не создаёт** custom field — только
`$this->amoClient->leads->customFields()->get()` (чтение списка существующих полей аккаунта) и
возвращает id первого для `#[Depends]`-цепочки update/find. `test_custom_field_update` правит уже
существующее поле (не создаёт новое). `test_custom_field_find` — чтение. `test_custom_field_find_exception`
— мок `PendingRequest`, не бьёт в реальный amo. Ни одного create во всём файле. Файл не трогал.

## Итог

- Затрекано: `AbstractModelTest` (leads ×3 сайта), `CallTest` (calls ×1), `CatalogTest` (catalogs ×2,
  catalogElements ×2), `CompanyTest` (companies ×2), `ContactTest` (contacts ×2).
- 8 из 13 файлов не тронуты вовсе — не создают ничего в боевом amo (моки, чтение, unit-и без сети).
- Расхождений/багов в существующих тестах не нашёл — не пишу лиду отдельно.
- Открытый вопрос лиду/`teardown-core`: `catalogElements` из `test_catalog_element` — нужен ли им
  отдельный механизм удаления, или это no-op после каскадного удаления самого каталога в
  `test_catalog_delete`.
