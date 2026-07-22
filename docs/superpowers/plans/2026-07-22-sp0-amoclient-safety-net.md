# SP0 — amoclient Safety Net Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Поставить `mttzzz/amoclient` на прочный фундамент до рефакторинга error-handling: phpstan **max** с 0 ошибок и реальный тест-сьют с **гарантированной уборкой** созданных в боевом amo сущностей.

**Architecture:** Тесты — интеграционные против реального аккаунта amo (`aId=16117840`); токен и `account_amo_user` берутся из локальной Postgres-копии `octane_pushka_biz`. Уборка хвостов — через `TestEntityRegistry` (трекает созданные ID) + teardown-удаление + `register_shutdown_function` на фаталы + composer-скрипт финального свипа. phpstan вводится впервые (сейчас `phpstan.neon` есть, но larastan/phpstan не установлены).

**Tech Stack:** PHP 8.4, PHPUnit 11.5, Orchestra Testbench 11, Larastan/phpstan, Guzzle, Illuminate\Http; локальный Postgres `mttzzzz@localhost:5432/octane_pushka_biz`.

## Global Constraints

- Целевая версия либы после всей эпопеи — `4.0`; **SP0 публичный API НЕ меняет** (только тесты + типы + tooling).
- Таксономия ошибок, классификаторы, circuit breaker, изменения retry-политики — **вне scope SP0** (это SP1/SP2).
- Многострочные комментарии — блочным `/* ... */`, не цепочкой `//`.
- Коммиты — через `bash ~/.claude/scripts/commit-files.sh "<msg>" <files...>` с явным списком файлов; перед коммитом прогнать `vendor/bin/pint <files>` на изменённых.
- **Уборка-инвариант:** после любого прогона тестов в боевом amo не остаётся созданных тестами сущностей.
- Реальный аккаунт под тесты: `aId=16117840`, `clientId=00a140c1-7c52-4563-8b36-03f23754d255`.
- Локальная БД для тестов: `pgsql`, host `127.0.0.1`, port `5432`, database `octane_pushka_biz`, username `mttzzzz`, без пароля.

---

## File Structure

| Файл | Ответственность | Действие |
|---|---|---|
| `composer.json` | dev-deps (larastan) + скрипты `stan`/`test`/`test:sweep` | Modify |
| `phpstan.neon` | уровень max | Modify |
| `tests/BaseAmoClient.php` | базовый тест: реальный клиент + Postgres-конфиг + интеграция registry/teardown | Modify |
| `tests/Support/TestEntityRegistry.php` | учёт созданных в amo сущностей и их удаление | Create |
| `tests/Support/TestEntityRegistryTest.php` | unit-тесты чистой логики registry | Create |
| `tests/Support/AmoTestSweeper.php` | финальный свип: удалить всё tracked + по тест-тегу | Create |
| `bin/amo-test-sweep.php` | CLI-энтрипоинт свипа (composer `test:sweep`) | Create |

---

## Task 1: Починить тестовую БД (без неё ничего не запускается)

Сейчас `BaseAmoClient::getEnvironmentSetUp` задаёт connection `octane` = MySQL `root@localhost:3306` (мёртвый). Но `AmoClientOctane` и `CrudEntityTrait::setResponsibleUser()` реально ходят в `DB::connection('octane')` за токеном аккаунта и `account_amo_user`. Перенаправляем на локальную Postgres-копию прода.

**Files:**
- Modify: `tests/BaseAmoClient.php:67-79` (метод `getEnvironmentSetUp`)

**Interfaces:**
- Produces: рабочий `DB::connection('octane')` (pgsql, `octane_pushka_biz`) для всех тестов, наследующих `BaseAmoClient`.

- [ ] **Step 1: Убедиться, что локальная копия прод-БД свежая**

Run: `psql "postgresql://mttzzzz@localhost:5432/octane_pushka_biz" -c "select id, subdomain from accounts where id = 16117840;"`
Expected: одна строка с аккаунтом `16117840`. Если пусто — сначала `cd ~/projects/octane.pushka.biz && pgsync sync` (ждать завершения), затем повторить.

- [ ] **Step 2: Заменить MySQL-конфиг на Postgres**

В `tests/BaseAmoClient.php` заменить тело `getEnvironmentSetUp`:

```php
protected function getEnvironmentSetUp($app)
{
    /* Тесты интеграционные: токен аккамунта 16117840 и account_amo_user
     * читаются из локальной Postgres-копии прод-БД octane_pushka_biz
     * (pgsync sync). Прежний MySQL root@3306 был мёртвым хвостом. */
    $app['config']->set('database.default', 'octane');
    $app['config']->set('database.connections.octane', [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'octane_pushka_biz',
        'username' => 'mttzzzz',
        'password' => '',
        'charset' => 'utf8',
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ]);
}
```

- [ ] **Step 3: Проверить, что тесты поднимаются и ходят в amo**

Run: `cd ~/projects/amoclient && vendor/bin/phpunit --filter test_lead_create tests/TaskTest.php 2>&1 | tail -20`
Expected: тест проходит (создаёт реальный лид) ИЛИ падает на amo-ответе — но НЕ на «connection refused»/«unknown database». Если DB-ошибка — чинить конфиг прежде чем идти дальше. (Созданный лид уберём в Task 4/5.)

- [ ] **Step 4: Commit**

```bash
cd ~/projects/amoclient
vendor/bin/pint tests/BaseAmoClient.php
bash ~/.claude/scripts/commit-files.sh "test(infra): тестовая БД octane → локальный Postgres octane_pushka_biz (мёртвый MySQL root@3306 был несущим для DB-backed токена)" tests/BaseAmoClient.php
```

---

## Task 2: phpstan level max, 0 ошибок

phpstan/larastan не установлены (в `vendor/bin` их нет), хотя `phpstan.neon` уже включает larastan-extension и `level: 8`. Ставим deps, поднимаем до max, доводим до нуля.

**Files:**
- Modify: `composer.json` (require-dev + scripts)
- Modify: `phpstan.neon` (`level: 8` → `level: max`)
- Modify: файлы `src/**` по мере фиксов (состав неизвестен до первого прогона)

**Interfaces:**
- Produces: `composer stan` → exit 0; `composer test` как алиас phpunit.

- [ ] **Step 1: Установить larastan**

Run: `cd ~/projects/amoclient && composer require --dev "larastan/larastan:^3.0" 2>&1 | tail -15`
Expected: установлен `larastan/larastan`, появился `vendor/bin/phpstan`.

- [ ] **Step 2: Добавить composer-скрипты**

В `composer.json` добавить блок `scripts` (если его нет):

```json
    "scripts": {
        "stan": "phpstan analyse --no-progress --memory-limit=2G",
        "test": "phpunit",
        "test:sweep": "php bin/amo-test-sweep.php"
    },
```

- [ ] **Step 3: Зафиксировать baseline на level max**

В `phpstan.neon` заменить `level: 8` → `level: max`.
Run: `cd ~/projects/amoclient && composer stan 2>&1 | tee /tmp/claude-1000/-home-mttzzzz-projects-octane-pushka-biz/3c2a2db2-1985-4529-bf23-6d8ff3c9629a/scratchpad/stan-baseline.txt | tail -5`
Expected: `[ERROR] Found N errors` (N > 0 ожидаемо). Это baseline.

- [ ] **Step 4: Чинить ошибки по категориям до нуля**

Итеративно: `composer stan`, читать ошибки, чинить. Типичные категории для этой либы (ответы amo — сплошь `array`):
- `no value type specified in iterable type array` → добавить `@param array<string, mixed>` / `@return array<mixed>` в phpdoc.
- `Method ... has no return type specified` → добавить нативный return type или phpdoc.
- `Cannot access offset ... on mixed` → нарратить тип (`is_array()`-гард или `/** @var */`).
- `Access to an undefined property` на динамических entity-полях → `@property` в docblock класса или `@phpstan-ignore`.

**Правило:** НЕ глушить массово `@phpstan-ignore` — чинить типами. `@phpstan-ignore` только там, где тип реально динамический (amo-ответ) и гард невозможен, с комментарием-причиной.

Run после каждого пакета фиксов: `cd ~/projects/amoclient && composer stan 2>&1 | tail -5`
Expected (в конце): `[OK] No errors`.

- [ ] **Step 5: Commit**

```bash
cd ~/projects/amoclient
vendor/bin/pint composer.json phpstan.neon src
bash ~/.claude/scripts/commit-files.sh "build(stan): phpstan level max, 0 ошибок; larastan в dev-deps + composer stan/test/test:sweep скрипты" composer.json composer.lock phpstan.neon src
```

(Если фиксов в `src` много — бить на несколько коммитов по подпапкам `src/Models`, `src/Entities`, `src/Traits`, каждый со своим `pint` + `commit-files.sh`.)

---

## Task 3: TestEntityRegistry — чистая логика учёта (unit, без amo)

Реестр созданных сущностей: что создали в amo (канал+тип+id), чтобы потом удалить. Чистая логика — тестируется unit'ом без сети.

**Files:**
- Create: `tests/Support/TestEntityRegistry.php`
- Create: `tests/Support/TestEntityRegistryTest.php`

**Interfaces:**
- Produces:
  - `TestEntityRegistry::track(string $type, int $id): void` — зарегистрировать (`$type` ∈ `leads|contacts|companies|customers|tasks|catalogs|catalogElements|...`).
  - `TestEntityRegistry::all(): array<int, array{type: string, id: int}>` — список уникальных (dedup по type+id), в порядке добавления.
  - `TestEntityRegistry::forget(string $type, int $id): void` — снять из реестра (после успешного удаления).
  - `TestEntityRegistry::clear(): void` — очистить.

- [ ] **Step 1: Failing unit-тест**

Create `tests/Support/TestEntityRegistryTest.php`:

```php
<?php

namespace mttzzz\AmoClient\Tests\Support;

use PHPUnit\Framework\TestCase;

class TestEntityRegistryTest extends TestCase
{
    public function test_tracks_and_lists_in_insertion_order(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('tasks', 20);

        $this->assertSame(
            [['type' => 'leads', 'id' => 10], ['type' => 'tasks', 'id' => 20]],
            $r->all()
        );
    }

    public function test_dedupes_same_type_and_id(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('leads', 10);

        $this->assertCount(1, $r->all());
    }

    public function test_forget_removes_entry(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('leads', 11);
        $r->forget('leads', 10);

        $this->assertSame([['type' => 'leads', 'id' => 11]], $r->all());
    }

    public function test_clear_empties(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->clear();

        $this->assertSame([], $r->all());
    }
}
```

- [ ] **Step 2: Прогнать — падает (нет класса)**

Run: `cd ~/projects/amoclient && vendor/bin/phpunit tests/Support/TestEntityRegistryTest.php 2>&1 | tail -8`
Expected: FAIL — `Class "...TestEntityRegistry" not found`.

- [ ] **Step 3: Реализация**

Create `tests/Support/TestEntityRegistry.php`:

```php
<?php

namespace mttzzz\AmoClient\Tests\Support;

class TestEntityRegistry
{
    /** @var array<string, array{type: string, id: int}> */
    private array $entries = [];

    public function track(string $type, int $id): void
    {
        $this->entries[$type.':'.$id] = ['type' => $type, 'id' => $id];
    }

    public function forget(string $type, int $id): void
    {
        unset($this->entries[$type.':'.$id]);
    }

    /**
     * @return array<int, array{type: string, id: int}>
     */
    public function all(): array
    {
        return array_values($this->entries);
    }

    public function clear(): void
    {
        $this->entries = [];
    }
}
```

- [ ] **Step 4: Прогнать — зелёный**

Run: `cd ~/projects/amoclient && vendor/bin/phpunit tests/Support/TestEntityRegistryTest.php 2>&1 | tail -5`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
cd ~/projects/amoclient
vendor/bin/pint tests/Support/TestEntityRegistry.php tests/Support/TestEntityRegistryTest.php
bash ~/.claude/scripts/commit-files.sh "test(infra): TestEntityRegistry — учёт созданных в amo сущностей (dedup, forget, clear)" tests/Support/TestEntityRegistry.php tests/Support/TestEntityRegistryTest.php
```

---

## Task 4: Discovery + удаление сущностей (teardown против реального amo)

**Почему discovery-шаг:** amo API v4 не даёт единого `delete()` для всех сущностей (в `CrudEntityTrait` его нет вовсе). У части сущностей удаление — через отдельный endpoint/ajax, у части (leads/contacts/companies) публичного hard-delete нет. Точный механизм на тип надо установить эмпирически (доке не верим). Спайк даёт таблицу «тип → как удалить», которую реализуем.

**Files:**
- Modify: `tests/BaseAmoClient.php` (интеграция registry + teardown + shutdown-hook)
- Возможно Create/Modify: `src/Models/*`/`src/Entities/*` — если для удаления не хватает метода (согласовать: это правка публичного API — если требуется, вынести в отдельный микро-коммит и отметить как исключение из «SP0 API не меняет»).

**Interfaces:**
- Consumes: `TestEntityRegistry` (Task 3), рабочая БД (Task 1).
- Produces: `BaseAmoClient::track(string $type, int $id): int` (возвращает `$id` для чейнинга в `#[Depends]`), автоматический teardown-снос.

- [ ] **Step 1: Спайк — установить механизм удаления по типам**

Для каждого типа, создаваемого в тестах (`leads`, `contacts`, `companies`, `customers`, `tasks`, `catalogs`, `catalogElements`, `notes`), эмпирически проверить удаление через tinker-подобный скрипт против amo `16117840`. Пример для лида:

Run:
```bash
cd ~/projects/amoclient && vendor/bin/testbench tinker 2>/dev/null <<'PHP'
$amo = new \mttzzz\AmoClient\AmoClientOctane(16117840, '00a140c1-7c52-4563-8b36-03f23754d255');
$id = $amo->leads->entity()->tap(fn($l) => [$l->name = 'sweep-probe', $l->price = 1, $l->status_id = 142])->create()['_embedded']['leads'][0]['id'];
echo "created $id\n";
/* пробуем удалить — фиксируем, какой вызов принимается amo */
PHP
```
Задокументировать в самом плане-исполнении (или в комментарии `AmoTestSweeper`) таблицу: тип → метод удаления (endpoint DELETE / ajax / смена статуса на «удалено» / не удаляется публично).
Expected: заполненная таблица «тип → механизм». Типы без публичного удаления помечаются «только через тег/воронку + ручной sweep».

- [ ] **Step 2: Реализовать удаление в BaseAmoClient teardown**

По таблице из Step 1 реализовать в `tests/BaseAmoClient.php`:
- свойство `protected TestEntityRegistry $registry;` (инициализация в `setUp` до `parent::setUp`-логики);
- метод `track(string $type, int $id): int` — `$this->registry->track($type, $id); return $id;`;
- `tearDown(): void` — пройти `$this->registry->all()`, удалить каждую (через установленный в Step 1 механизм), на успех `forget`; ошибки удаления — не валить тест, писать в `fwrite(STDERR, ...)`;
- `register_shutdown_function` в `setUp` — на фатал добить остатки registry (idempotent).

Точный код teardown зависит от Step 1 и пишется по его результатам (механизмы удаления разнятся по типам).

- [ ] **Step 3: Проверить teardown на лиде**

Написать temp-тест, который создаёт лид через `track('leads', $id)` и в `tearDown` он должен удалиться; после — проверить, что лида в amo нет.
Run: `cd ~/projects/amoclient && vendor/bin/phpunit --filter test_teardown_deletes_lead tests/BaseAmoClientTest.php 2>&1 | tail -8`
Expected: PASS; повторный запрос лида в amo → 404/пусто.

- [ ] **Step 4: Commit**

```bash
cd ~/projects/amoclient
vendor/bin/pint tests/BaseAmoClient.php
bash ~/.claude/scripts/commit-files.sh "test(infra): авто-уборка созданных в amo сущностей — teardown по TestEntityRegistry + register_shutdown на фаталы" tests/BaseAmoClient.php
```

---

## Task 5: Развести существующие тесты на registry + свип-сетка

**Files:**
- Modify: `tests/*Test.php` (создающие сущности — оборачивать create в `track()`)
- Create: `tests/Support/AmoTestSweeper.php`, `bin/amo-test-sweep.php`

**Interfaces:**
- Consumes: `TestEntityRegistry`, механизмы удаления (Task 4).
- Produces: `composer test:sweep` — снести все тест-теггнутые/остаточные сущности старше порога.

- [ ] **Step 1: Обернуть создание сущностей в track() в существующих тестах**

В каждом `*Test.php`, где сущность создаётся (`->create()` / `createGetId()`), после получения `id` вызвать `$this->track('<type>', $id)`. Пример в `TaskTest::test_lead_create`:

```php
$created = $response['_embedded']['leads'][0];
$this->lead->id = $created['id'];

return $this->track('leads', $created['id']);
```

- [ ] **Step 2: Прогнать весь сьют, затем проверить отсутствие хвостов**

Run: `cd ~/projects/amoclient && vendor/bin/phpunit 2>&1 | tail -12`
Expected: сьют зелёный (или skip'ы на недоступных фичах), без фаталов.
Затем вручную проверить в amo (через tinker-запрос списка недавних лидов/контактов с тест-именами) — созданных тестами сущностей быть не должно.

- [ ] **Step 3: Свип-сетка `AmoTestSweeper` + CLI**

Create `tests/Support/AmoTestSweeper.php` — класс с методом `sweep(AmoClientOctane $amo): array` (возвращает список удалённого): найти сущности по тест-маркеру (тег/префикс имени `Test *`/`sweep-probe`) и удалить механизмами из Task 4.
Create `bin/amo-test-sweep.php` — бутстрап testbench + `new AmoClientOctane(16117840, ...)` + `(new AmoTestSweeper)->sweep($amo)`, печать результата.

**Гард безопасности:** свип удаляет ТОЛЬКО сущности с тест-маркером; никогда не трогает реальные клиентские данные аккаунта. Маркер задаётся явной константой (`AmoTestSweeper::TEST_MARKER`).

- [ ] **Step 4: Проверить свип**

Run: `cd ~/projects/amoclient && composer test:sweep 2>&1 | tail -10`
Expected: печатает «удалено N» (0, если сьют уже прибрал за собой) без ошибок.

- [ ] **Step 5: Commit**

```bash
cd ~/projects/amoclient
vendor/bin/pint tests bin
bash ~/.claude/scripts/commit-files.sh "test(infra): все тесты трекают созданные сущности; AmoTestSweeper + composer test:sweep как финальная сетка от хвостов" tests bin
```

---

## Definition of Done (SP0)

- [ ] `composer stan` → `[OK] No errors` на `level: max`.
- [ ] `composer test` — сьют зелёный против реального amo.
- [ ] После полного прогона в amo `16117840` не остаётся созданных тестами сущностей (teardown + shutdown-hook + `composer test:sweep`).
- [ ] Мёртвый MySQL-конфиг заменён рабочим Postgres.
- [ ] Публичный API либы не изменён (кроме, возможно, точечного delete-метода в Task 4 — если понадобился, зафиксирован отдельным коммитом и отмечен).
- [ ] Всё запушено (после явного разрешения пользователя).

## Self-review notes
- Спайк в Task 4 (механизм удаления) — необходим: amo не даёт единого delete, доке не верим; deliverable спайка = таблица «тип → механизм», не placeholder.
- Task 1 первый намеренно: без рабочей БД (DB-backed токен) не запускается ни один интеграционный тест.
- phpstan-фиксы (Task 2 Step 4) не пере­числены пофайлово — состав неизвестен до первого прогона на max; дан процесс + категории + правило против массового `@phpstan-ignore`.
