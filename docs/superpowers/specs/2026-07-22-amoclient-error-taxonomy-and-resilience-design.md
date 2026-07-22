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

**Дискриминаторы 400** (подтверждены кодом офф. SDK, см. 2.5, и нашим production, см. Appendix): настоящий bad request несёт массив **`validation-errors`** → `AmoValidationException`; 400 **без** `validation-errors`, но с `detail` ≈ «повторите попытку/системная ошибка» → `AmoSystemRetryException` (код врёт). Отдельное измерение — **внутренний `errors[0].code`**: `226` = «лид удалён/недоступен», амо шлёт 400 вместо 404 → benign `AmoNotFoundException`-подобный. Повод всегда берём из поля **`detail`**. Классификатор смотрит связку **`(status, detail, validation-errors, errors[].code, Error NNN.-текст)`**, а не только HTTP-статус.

### 2.2 Два классификатора
`PublicApiErrorClassifier` и `AjaxErrorClassifier` реализуют общий интерфейс `classify(status, body, headers, previous): AmoException`. Публичный работает с RFC-7807-подобными телами (`title/type/status/detail`, `validation-errors`); ajax — со своим диалектом (коды вида `Error 282.`/`Error 426.`, HTML, нестандартные обёртки). Классификаторы намеренно **раздельны** — семантика каналов не пересекается. Точка входа выбирает классификатор по каналу вызова.

### 2.3 `AmoUnknownException` как триггер-проволока
Всё, что не попало в известное правило → `AmoUnknownException`: `isRetryable=false`, `isSilenceable=false` → **всегда** долетает до Sentry с полным `context()`. Это by-design незакрытое ведро: увидели новый тип в Sentry → добавили правило в классификатор + фикстуру в тесты. Классификатор эволюционирует; тесты растут по мере появления реальных ошибок.

### 2.4 Transport resilience
- **Retry:** проксирование-ротация только на `ConnectionException` (недоступность прокси); на 5xx — ограниченный ретрай без прокси-чурна; на 429 — уважать `Retry-After`.
- **Circuit breaker (opt-in):** реализуется в либе, работает через инжектируемый PSR-16 cache. Включается **пер-инстанс клиента** (batch-синк AccountUpdate — вкл; real-time виджет-вызовы вроде lead-lookup/client-info — выкл, юзер ждёт ответа). Первый вызов, получивший «Amo down»-сигнал (`AmoServerException`/`AmoConnectionException`), открывает брейкер на короткий TTL; последующие вызовы при открытом брейкере быстро отклоняются (типизированной ошибкой) без похода в сеть. Half-open: одна проба после TTL.

### 2.5 Baseline из официальной библиотеки `amocrm/amocrm-api-php`
Их публичный SDK даёт **грубый** baseline для `PublicApiErrorClassifier`. Приватного ajax там нет by design — его правила растим только из реальных захватов (браузер/Sentry).

| Их обработка (публичное v4) | Наш тип |
|---|---|
| `ConnectException` (Guzzle) | `AmoConnectionException` |
| 429 → `AmoCRMApiTooManyRequestsException` | `AmoRateLimitException` |
| 401 → `AmoCRMoAuthApiException` (несёт `detail`) | `AmoAuthException` |
| 204 → `AmoCRMApiNoContentException` | успех/пусто — не ошибка |
| 400 **с** `validation-errors` → `AmoCRMApiErrorResponseException` | `AmoValidationException` |
| тело не JSON (HTML) → `AmoCRMApiException("Response body is not json")` | `AmoServerException` (если 5xx) |
| прочее non-success → generic `AmoCRMApiException` | **наша доработка** (см. ниже) |

Подтверждённые их кодом дискриминаторы: канонический повод в поле **`detail`**; **наличие `validation-errors`** = настоящий 400; **не-JSON тело** на ошибке = типичный HTML-гейтвей 5xx.

Чего офф. либа **НЕ** различает (наша production-доработка на основе живых инцидентов): `402/403/404`, `5xx`-как-класс, «код-врёт» 400 с retry-текстом, и весь **ajax**-диалект. Контракта `isRetryable/isSilenceable/retryAfter` у них тоже нет — это наш слой.

Ref: `https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiRequest.php` (`checkHttpStatus`, `parseResponse`).

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

---

## Appendix — Каталог известных ошибок амо (production seed для фикстур/правил)

Собрано из кода octane/masterm/amoclient, тестов и Sentry-истории (90д). Стартовый набор правил классификатора + фикстур; растёт через `AmoUnknownException`. **Не полагаться на документацию** — только реальные сигналы.

### A.1 Public API v4 — по HTTP-статусу
| Статус | Тело/сигнал | Наш тип | Источник |
|---|---|---|---|
| 401 | `detail` | `AmoAuthException` | AccountUpdateJob, AmoChatJob |
| 402 | «Амо не оплачен» / «Аккаунт стал не оплаченным» (флапает ночью ~9 мин) | `AmoPaymentRequiredException` | MASTERM-35, Handler, DocumentCreateNoteJob |
| 403 | | `AmoForbiddenException` | AccountUpdateJob |
| 404 | сущность удалена/недоступна | `AmoNotFoundException` (benign) | shortLinks, anotherLeads, CopyValueFieldJob, documentPdf |
| 429 | | `AmoRateLimitException` (+`retryAfter`) | офф. SDK |
| 5xx (500/502/503/504) | часто **HTML**-тело | `AmoServerException` | 9R/9S/7B/68/97 |
| 400 + `validation-errors` | массив ошибок полей | `AmoValidationException` | HFGeneralJob:720, офф. SDK |
| 400 + `detail`≈«повторите попытку/системная ошибка» | нет `validation-errors` | `AmoSystemRetryException` (код врёт) | 9Q (21.07.2026) |
| 400 + `errors[0].code === 226` | | benign (амо шлёт 400 вместо 404, «лид удалён») | DocumentCreateNoteJob:68/79 |

### A.2 Внутренние коды `Error NNN.` (в message; ajax + часть v4)
| Сигнал | Смысл | Наш тип | Источник |
|---|---|---|---|
| `Error 282.` (часто с HTTP 404) | сущность/задача уже удалена | `AmoNotFoundException` (benign) | masterm ChangeTaskJob, amoclient CustomerTest/BaseAmoClient |
| `Error 426.` / «Customers disabled» | Customers API выключен для конфигурации аккаунта | feature-disabled (benign, silenceable) | amoclient EventTest/CustomerTest |

### A.3 Chat (amojo) — отдельный диалект, **кандидат в 3-й классификатор**
| Сигнал | Смысл | Реакция (текущая, octane AmoChatJob) |
|---|---|---|
| 403 + `message === 'sender blocked by receiver'` | клиент заблокировал | benign — НЕ fail |
| 404 | чат удалён | fail |
| 401 | некорректный токен | fail |

Chat сейчас обрабатывается в octane (`AmoChatJob`), не через amoclient. В SP1 решить: заводить ли `ChatApiErrorClassifier` в либе или оставить проекту. Диалект amojo ≠ public-v4 ≠ ajax.

### A.4 Источники, которые продолжаем майнить
- **Sentry** (org `pushka-biz`, все проекты) — реальные тела ошибок; каждый новый `AmoUnknownException` → новая фикстура + правило.
- **Офф. SDK** `amocrm-api-php` — только public-v4 baseline (см. 2.5).
- **amoclient/tests** — уже содержат реальные `Error 282./426.`, «Customers disabled».
- **git-история** октана/masterm — инцидент-рефы (OCTANE-*, MASTERM-35) с причинной моделью в коммитах.
- **Офф. docs** кодов ошибок (см. A.5).

### A.5 Документированный каталог внутренних `errors[].code` (офф. docs — seed, НЕ авторитетно)
Ref: `https://www.amocrm.ru/developers/content/crm_platform/error-codes`

Офф. docs перечисляют внутренние `errors[].code` — богаче, чем их SDK (он их не разбирает вовсе). Но каталог **неполон и не авторитетен**: код **226** («лид удалён», реально ловим в prod как 400) в списке **отсутствует** (225→231). Используем как seed; истина — реальные ответы + `AmoUnknownException`.

| `errors[].code` (docs) | значение | наш тип |
|---|---|---|
| 101 | запрос к несуществующему аккаунту/субдомену | `AmoAuthException` (account-gone) |
| 110, 111, 112, 113 | логин/капча/юзер отключён/IP запрещён | `AmoAuthException` (113 → Forbidden) |
| 202, 244, 288 | нет прав | `AmoForbiddenException` |
| **203** | **«системная ошибка» доп. полей** | кандидат в `AmoSystemRetryException` (транзиент — проверить на реальном теле) |
| 204*, 225, 231, 233, 234, 236, 237, 282 | сущность/поле/события/задачи не найдены | `AmoNotFoundException` (benign) |
| 205, 212, 219 | контакт не создан/обновлён, ошибка поиска | контекстное |
| 330 | слишком много привязок | `AmoValidationException` |
| 402 | подписка закончилась | `AmoPaymentRequiredException` |
| 405 | HTTP-метод не поддерживается | `AmoValidationException` (наш баг) |
| 425, 426 | функционал недоступен / выключен | feature-disabled (benign, silenceable) |
| 429 | rate limit | `AmoRateLimitException` |
| 2002 | ничего не найдено (HTTP 204) | пусто, не ошибка |
| **226** | **(в docs НЕТ)** «лид удалён» — 400 вместо 404 | benign not-found (только из prod) |

**Layer-нюанс:** внутренний `errors[].code` численно пересекается с HTTP-статусами, но значит другое. Внутр. `204` = «поле не найдено» ≠ HTTP 204 No Content; внутр. `402`/`429` по смыслу совпадают с HTTP. Классификатор обязан различать **слой**: HTTP status line vs `errors[].code` внутри тела. `203` («системная ошибка») — сигнал, что «код-врёт»-семантика живёт и на уровне внутренних кодов, не только в тексте `detail`.
