# amoclient — Error Taxonomy & Resilience Redesign

**Date:** 2026-07-22
**Status:** Design (approved in brainstorming; sub-project specs follow)
**Repos touched:** `mttzzz/amoclient` (primary), `octane.pushka.biz`, `masterm.pushka.biz`
**Target library version:** `4.0` (breaking; no backward compatibility with `3.x`)

---

## 1. Problem

Классификация ошибок AmoCRM сейчас **размазана** между библиотекой и проектами, и делается по ненадёжным сигналам.

- Библиотека знает ровно один тип — `AmoCustomException`. Её конструктор лишь сохраняет HTTP-код в `code`, тело в `message`, оригинал в `previous`. Никакой семантики.
- Каждый проект **заново выводит** «транзиентно / оплата / авторизация / глушить ли»:
  - octane: `AbstractBaseJob::isTransient()` (коды `502/503/504` + свежий хак «400 + текст "повторите попытку"»), `Handler::isTransientExternalApiError()` (`402/404` + строковый матч `Unknown error (ConnectionException)`), `AccountUpdateJob::failed()` (свой набор suppress-кодов).
  - masterm: своя копия аналогичной логики (инцидент MASTERM-PUSHKA-BIZ-35).
- **Коды AmoCRM врут даже против их же документации.** Подтверждённые случаи:
  - `400` с телом `{"detail":"Возникла системная ошибка. Пожалуйста, повторите попытку."}` — фактически транзиентный сбой их бэкенда, сам просит повторить (Sentry OCTANE-PUSHKA-BIZ-9Q, 21.07.2026).
  - `500` с телом = HTML-страница ошибки гейтвея (не JSON) — их бэкенд лёг (Sentry OCTANE-PUSHKA-BIZ-9R/9S/7B/68).
  - `402` флапает по реально оплаченным аккаунтам в ночные окна (биллинг-пересчёт ~9 мин).
- **Внутренняя непоследовательность самой библиотеки:** `Models/AbstractModel::get()` (public API v4) оборачивает ошибку в `AmoCustomException`, а `Ajax::get()` отдаёт **сырой** `RequestException`. Одна и та же цепочка сбоя всплывает в двух разных типах.
- **Усиление нагрузки на лежащий upstream:** HTTP-retry в `AmoClientOctane` ротирует прокси на `status >= 500`. Но 5xx = лёг **бэкенд AmoCRM**, а не прокси — смена прокси бьёт в тот же труп ×N. Ротация оправдана только для `ConnectionException`.
- **Стампеда по аккаунтам:** плановый `accounts:update all` раздаёт по джобе на каждый payed-аккаунт; во время простоя AmoCRM сотни джоб независимо долбят мёртвый API, нет общего стоп-сигнала.
- **Шум:** `AmoCustomException`-5xx обходят per-host rate-limiter октана (не `instanceof RequestException`) → каждое событие в Sentry+Telegram без троттла.

### Почему нельзя опираться на документацию
AmoCRM отвечает не по контракту (см. кейсы выше), а **ajax — приватное недокументированное API**: вызовы воспроизводятся из браузера, мимикрируя под клиента; коды и тела ошибок там свои, никак не согласованы с публичным API v4. Единственный надёжный источник истины — **реальные ответы** реального аккаунта и захваченные реальные payload'ы ошибок.

---

## 2. North star — целевая архитектура

**Принцип границы:** библиотека **классифицирует семантику** ошибки и владеет **transport-resilience**; проект владеет **бизнес-политикой** (сколько ретраить в очереди, флипать ли `payed=false`, слать ли Telegram, деактивировать ли виджет).

```
┌────────────────────────── amoclient 4.0 (эталон) ──────────────────────────┐
│  Transport (Http)                                                           │
│    • retry: proxy-ротация ТОЛЬКО на ConnectionException, не на 5xx           │
│    • honor Retry-After для 429                                               │
│    • opt-in CircuitBreaker(PSR-16 cache) — per-client-instance              │
│                                                                             │
│  Error classification (у каждого канала — свой диалект)                      │
│    • PublicApiErrorClassifier   (API v4: RFC-7807-подобные тела)            │
│    • AjaxErrorClassifier        (приватное API: свои коды/тела)             │
│         (status, body, headers, previous) ─► typed AmoException             │
│                                                                             │
│  Exception taxonomy (контракт для потребителей)                             │
│    AmoException (base)                                                       │
│      getStatus():?int  getAmoDetail():?string  context():array              │
│      isRetryable():bool  isSilenceable():bool  retryAfter():?int            │
│      + маркер-интерфейсы AmoRetryable / AmoSilenceable                      │
└─────────────────────────────────────────────────────────────────────────────┘
         ▲                                            ▲
         │ isRetryable() → queue release              │ isSilenceable() → skip Sentry
┌────────┴─────────┐                        ┌─────────┴──────────┐
│  octane          │                        │  masterm           │
│  бизнес-политика  │                        │  бизнес-политика    │
└──────────────────┘                        └────────────────────┘
```

### 2.1 Таксономия исключений

Базовый `AmoException` (никаких сырых `RequestException` наружу):

| Метод | Смысл |
|---|---|
| `getStatus(): ?int` | HTTP-статус; `null` для connection-ошибок |
| `getAmoDetail(): ?string` | распарсенный человекочитаемый повод из тела |
| `isRetryable(): bool` | повтор того же запроса имеет шанс на успех (транзиент upstream) |
| `isSilenceable(): bool` | ожидаемое состояние, не баг → не алертить (транзиент ИЛИ benign client-state) |
| `retryAfter(): ?int` | секунды (из `Retry-After`) или `null` |
| `context(): array` | структурный контекст для Sentry (status, detail, url, account, channel) |
| `getPrevious()` | оригинальный `RequestException`/`ConnectionException` |

`isRetryable` и `isSilenceable` — **две независимые оси** (пример: 404 не retryable, но silenceable).

Подтипы и их дефолтная семантика:

| Тип | retryable | silenceable | примечание |
|---|---|---|---|
| `AmoConnectionException` | ✅ | ✅ | нет HTTP-ответа (cURL 28 и т.п.) |
| `AmoRateLimitException` | ✅ | ✅ | 429; `retryAfter` из заголовка |
| `AmoServerException` | ✅ | ✅ | 5xx (вкл. HTML-гейтвей) |
| `AmoSystemRetryException` | ✅ | ✅ | код врёт: 4xx, но тело = «повторите попытку»/«системная ошибка» |
| `AmoAuthException` | ❌ | ❌ | 401 — токен/ротация; политику решает проект |
| `AmoPaymentRequiredException` | ❌ | ❌ | 402; флап-политику (не верить первому) решает проект |
| `AmoForbiddenException` | ❌ | ❌ | 403 |
| `AmoNotFoundException` | ❌ | ✅ | 404 — benign |
| `AmoValidationException` | ❌ | ❌ | настоящий bad request (есть `validation-errors`) — наш баг, алертить |
| `AmoUnknownException` | ❌ | ❌ | **не классифицировано — громкая тревога (см. 2.3)** |

### 2.2 Два классификатора
`PublicApiErrorClassifier` и `AjaxErrorClassifier` реализуют общий интерфейс `classify(status, body, headers, previous): AmoException`. Публичный работает с RFC-7807-подобными телами (`title/type/status/detail`, `validation-errors`); ajax — со своим диалектом (коды вида `Error 282.`/`Error 426.`, HTML, нестандартные обёртки). Классификаторы намеренно **раздельны** — семантика каналов не пересекается. Точка входа выбирает классификатор по каналу вызова.

### 2.3 `AmoUnknownException` как триггер-проволока
Всё, что не попало в известное правило → `AmoUnknownException`: `isRetryable=false`, `isSilenceable=false` → **всегда** долетает до Sentry с полным `context()`. Это by-design незакрытое ведро: увидели новый тип в Sentry → добавили правило в классификатор + фикстуру в тесты. Классификатор эволюционирует; тесты растут по мере появления реальных ошибок.

### 2.4 Transport resilience
- **Retry:** проксирование-ротация только на `ConnectionException` (недоступность прокси); на 5xx — ограниченный ретрай без прокси-чурна; на 429 — уважать `Retry-After`.
- **Circuit breaker (opt-in):** реализуется в либе, работает через инжектируемый PSR-16 cache. Включается **пер-инстанс клиента** (batch-синк AccountUpdate — вкл; real-time виджет-вызовы вроде lead-lookup/client-info — выкл, юзер ждёт ответа). Первый вызов, получивший «Amo down»-сигнал (`AmoServerException`/`AmoConnectionException`), открывает брейкер на короткий TTL; последующие вызовы при открытом брейкере быстро отклоняются (типизированной ошибкой) без похода в сеть. Half-open: одна проба после TTL.

---

## 3. Декомпозиция (roadmap)

Каждый под-проект — свой спек → план → реализация. Всё зависит от SP0.

| # | Под-проект | Репа | Зависит |
|---|---|---|---|
| **SP0** | **Safety net библиотеки** — phpstan max + реальное тест-покрытие с уборкой хвостов | amoclient | — |
| SP1 | Таксономия + два классификатора + контракт `AmoException` | amoclient 4.0 | SP0 |
| SP2 | Transport resilience (retry-политика + circuit breaker) | amoclient 4.0 | SP1 |
| SP3 | Rewrite octane на контракт (выкинуть `isTransient`/`Handler`/`failed`-эвристики; breaker на AccountUpdate) | octane | SP1, SP2 |
| SP4 | Rewrite masterm на контракт | masterm | SP1, SP2 |

**Строим SP0 первым** — safety net (типы + реальные тесты) до рефакторинга error-handling.

---

## 4. SP0 — Safety net (этот цикл)

Цель: зафиксировать поведение и типы **до** введения таксономии, чтобы рефакторинг SP1+ шёл на страховке.

### 4.1 phpstan → max
`phpstan.neon`: `level: 8` → `level: max`. Установить/починить dev-deps (сейчас `vendor/bin/phpstan` не резолвится, `composer stan` не определён). Добавить `composer stan` скрипт. Довести до **0 ошибок** на `src/`.

### 4.2 Реальное тест-покрытие
**Философия:** реальные тесты > фейков, потому что доке верить нельзя, а ajax недокументирован. Единственная правда — реальные ответы реального аккаунта (`aId=16117840`).

- **Happy-path вызовы (entities, ajax, account):** живьём против реального amo. Проверяем, что вызовы реально работают.
- **Ошибки (для будущего классификатора SP1):**
  - Провоцируем живьём что детерминированно вызывается: битый запрос → `400/422`, отсутствующая сущность → `404`.
  - Что живьём надёжно не вызвать (`5xx`, `429`, off-contract «системная ошибка») — **фикстуры из реальных захваченных payload'ов** (Sentry-сэмплы, история). Реальные байты amo, не выдумка.
  - Всё непокрытое → `AmoUnknownException`, растим правила по мере появления в Sentry.

### 4.3 Уборка хвостов (гибрид + sweep-сетка)
Тесты создают сущности в **боевом** amo — обязана быть гарантия исчезновения.

- **`TestEntityRegistry`** — трекает каждый созданный ID (сущность + тип канала для удаления).
- **`tearDown()`** — удаляет всё из registry. `register_shutdown_function` — страховка на фатал (когда `tearDown` не отрабатывает).
- **Тест-воронка/тег** — сущности, которые скоупятся (leads/contacts/companies), кладём в известную тест-воронку/тег для балк-пуржа. Сущности, которые **не** скоупятся (custom fields, pipelines, users), — трекаются по ID и удаляются индивидуально.
- **`account:test-sweep`** — отдельная artisan-команда: сносит любые test-тегнутые остатки старше N (финальная сетка от крашей/утечек registry). Запускается вручную и/или в конце CI-прогона.

### 4.4 Чистка вестигиального
- Убрать протухший `getEnvironmentSetUp` MySQL-конфиг `octane_pushka_biz` root/root (октан давно на Postgres; конфиг мёртвый).
- Причесать `BaseAmoClient` (ad-hoc `skipIf*`-хелперы) под новую тест-инфраструктуру.

### 4.5 Вне scope SP0
Таксономия, классификаторы, circuit breaker, изменения retry-политики — это SP1/SP2. SP0 только: типы max + реальные тесты + уборка. Существующий публичный API библиотеки **не меняется** в SP0 (это фундамент, не рефакторинг контракта).

---

## 5. Тест-стратегия (сквозная)

| Слой | Как тестируем | Почему |
|---|---|---|
| Happy-path вызовы | Живьём против amo `16117840` + уборка | Только так знаем, что вызовы реально работают |
| Классификатор ошибок (SP1) | Живьём детерминированные 4xx + фикстуры реальных payload'ов из Sentry/истории | 5xx/429/off-contract живьём не вызвать; вход всё равно реальный |
| Новые/неизвестные ошибки | `AmoUnknownException` → Sentry → добавить правило+фикстуру | Доке верить нельзя; растим покрытие по факту |

Инвариант: **после любого прогона в боевом amo не остаётся созданных тестами хвостов** (registry teardown + shutdown-hook + sweep-команда).

---

## 6. Риски и открытые вопросы

- **Кросс-реповый rollout без back-compat.** amoclient 4.0 ломает оба проекта. Порядок: SP0→SP1→SP2 в либе (теги 4.0.x), затем SP3 (octane) и SP4 (masterm) поднимают `^4.0` и переписывают error-handling в одном PR каждый. До завершения SP3/SP4 проекты остаются на 3.x.
- **masterm `^3.1.1`** сейчас требует версию, которой нет тегом (последний тег 3.1.0) — проверить перед стартом SP1, не сломано ли что-то уже.
- **`AmoClientOctane`** — project-именованный класс в общей либе (smell). Не трогаем в SP0; пересмотр именования — кандидат в SP2.
- **Circuit breaker** требует общего Redis у потребителя; в SP2 определить дефолтный no-op cache, чтобы либа работала и без инжекта.
- **Sweep-команда** должна быть строго ограничена тест-тегом/воронкой — никогда не трогать реальные клиентские сущности аккаунта `16117840`.

---

## 7. Следующий шаг
Спек SP0 → детальный план реализации (skill `writing-plans`). SP1–SP4 получают свои спеки по мере продвижения.
