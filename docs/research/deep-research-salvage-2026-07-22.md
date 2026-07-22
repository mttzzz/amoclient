# Deep-research SALVAGE — 22.07.2026 (синтез не доехал, факты спасены)

Воркфлоу `wf_98243cd6-5b7` (запуск 09:15) отработал фазы **search → извлечение утверждений → адверсариальная проверка**
и умер вместе с сессией в 09:31 до фазы синтеза. Отчёта нет; ниже — восстановленный из журнала выхлоп.
Ничего не резюмировано и не дописано вручную — это сырьё для архитектуры новой либы, дословно из журнала.

Правило харнесса: утверждение убивается, если его опровергли **≥2 из 3** голосующих скептиков.

**Итог голосований:** 19 утверждений проверено — устояло 8, убито 11.


## Устояли под адверсариальной проверкой

### 1. Repeated rate-limit violations escalate from HTTP 429 to HTTP 403 with account blocking — meaning naive/aggressive retry-on-429 is actively dangerous and the client's backoff must be conservative, with 403 classified as a permanent (non-retryable) failure distinct from transient 429.

*Источник:* https://www.amocrm.ru/developers/content/api/recommendations (primary)
*Цитата:* "При превышении возвращается HTTP 429 ... При многократном нарушении — HTTP 403, аккаунт блокируется"
*Голоса скептиков:* опровергли 0 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  Verified against the primary source and cross-checked; the claim survives adversarial probing.
  
  1) PRIMARY SOURCE CONFIRMS THE FACTUAL HALF (verbatim, fetched 2026-07-22 from https://www.amocrm.ru/developers/content/api/recommendations):
  - limits: «не более 7 запросов в секунду на одну интеграцию» и «до 50 запросов в секунду на весь аккаунт»
  - 429: «В случае превышения количества запросов – будет возвращаться HTTP код 429»
  - escalation: «при многократном нарушении ограничений, аккаунт блокируется и на любой запрос в API будет возвращаться HTTP код 403»
  - plus: «В случае подозрительной активности через API или сбоя интеграции, доступ к API аккаунта может быть заблокирован»
  The claim's supporting quote is a condensed paraphrase, not a misquote — the real wording is in fact STRONGER than the quote used ("на любой запрос в API" = the whole account's API surface dies, not just the offending endpoint).
  
  2) SECOND OFFICIAL SOURCE (https://www.amocrm.ru/developers/content/crm_platform/error-codes) independently lists 429 = «Превышено допустимое количество запросов в секунду» and 403 = account blocked for repeated rate-limit violations (plus code 113 «Доступ к данному аккаунту запрещён с Вашего IP адреса» for IP-whitelist). Both 403 variants are non-self-healing → the "permanent, non-retryable" classification holds for 403 generally, not only for the block case.
  
  3) THE INFERENCE ("naive retry-on-429 is actively dangerous") IS NOT AN OVERREACH — it is what the doc's causal chain says: retrying without backoff keeps you above 7 rps, which is precisely «многократное нарушение ограничений». Corroborated by a real incident in the official library's own tracker: amocrm/amocrm-api-php issue #318 (opened 2021-05-22) — a public widget generated excessive API calls, the account got suspended, and the library did not even handle the resulting response (an HTML error page rather than JSON). That is direct evidence that (a) the escalation happens in production and (b) the official SDK does not defend against it — relevant to the design question at hand.
  
  4) NON-RETRYABILITY OF 403 CORROBORATED BY INDEPENDENT THIRD PARTIES: sensei.plus KB («Если Вы поняли, ч
  *Контр-источник:* No contradicting source found. Nearest thing to a counterweight: amocrm/amocrm-api-php issue #318 shows amoCRM staff never answered the request for a dedicated rate-limit exception type, so there is no official statement on recommended retry intervals — meaning the "conservative backoff" prescription rests on inference from the docs plus third-party support KBs (sensei.plus, docs.itgrix.ru, mango-
- **Голос 2 — не опроверг** (уверенность: high):
  VERIFIED against the primary source, both halves of the claim hold.
  
  1) Escalation 429 → 403 + block — confirmed verbatim on https://www.amocrm.ru/developers/content/api/recommendations (fetched 2026-07-22): limits "не более 7 запросов в секунду на одну интеграцию", "до 50 запросов в секунду на весь аккаунт"; "В случае превышения количества запросов – будет возвращаться HTTP код 429"; "при многократном нарушении ограничений, аккаунт блокируется и на любой запрос в API будет возвращаться HTTP код 403". The claim's supporting quote is a compressed paraphrase, not a misquote — semantically faithful to the verbatim text.
  
  2) "Naive retry is actively dangerous" is NOT an overreach — the same page names the exact behaviour a dumb retry loop produces as a blocking trigger: "В случае подозрительной активности через API или сбоя интеграции, доступ к API аккаунта может быть заблокирован. Например при выборке одних и тех же раз за короткий промежуток времени, бесконтрольный перебор всех данных." Immediate retry of the same request after 429 = "выборка одних и тех же [данных] раз за короткий промежуток времени". So the inference is supported by the primary source directly, not merely deduced.
  
  3) "Client must supply its own conservative backoff" — confirmed by absence: the recommendations page documents NO Retry-After header and gives NO retry guidance for 429 (it stops at "будет возвращаться HTTP код 429"). By contrast it DOES give explicit retry guidance for 401 ("обновить Access Token с помощью Refresh Token и повторить запрос"). The asymmetry is the point: amo tells you to retry 401, and deliberately does not tell you to retry 429.
  
  4) 403 as permanent/non-retryable — corroborated. Official error-codes page (https://www.amocrm.ru/developers/content/crm_platform/error-codes) lists 403 as: code 113 IP-whitelist denial + account blocked for excessive rps. Recovery per amo support ecosystem is a support ticket, not a retry (support.sensei.plus/knowledge_base/item/269853: "обратитесь в техническую поддержку amoCRM"; admin gets an email notice). Even the softest third-party account ("blocking is usually temporary, system unblocks after some time") puts recover
  *Контр-источник:* No credible counter-source found. The only softening signal is third-party ("блокировка обычно временная, система разблокирует через некоторое время" — reon.pro / makerock.ru / alarmcrm.ru, SEO consultancy blogs, no timeframe, no primary backing), which does not survive against amoCRM's own docs and the support-ticket unblock path, and does not change the client-side non-retryable classification.
- **Голос 3 — не опроверг** (уверенность: high):
  PRIMARY VERIFIED LIVE (fetched 2026-07-22, https://www.amocrm.ru/developers/content/api/recommendations): documents limits "не более 7 запросов в секунду на одну интеграцию" and "до 50 запросов в секунду на весь аккаунт"; on exceeding — "HTTP код 429"; and on repeated violation — "аккаунт блокируется и на любой запрос в API будет возвращаться HTTP код 403". The supporting quote is an accurate paraphrase of the page, not a misread; the 429→403+block escalation is amo's own documented behavior.
  
  SECOND PRIMARY (https://www.amocrm.ru/developers/content/crm_platform/error-codes) corroborates the taxonomy: 429 = "Превышено допустимое количество запросов в секунду" (per-second, i.e. transient by construction); 403 = "Доступ к данному аккаунту запрещён / Аккаунт заблокирован". Notably, NEITHER amo page documents a Retry-After header, a retry policy, a quantified threshold for "многократное нарушение", nor any auto-expiry of the 403 block.
  
  INDEPENDENT CORROBORATION (non-amo, integrator KBs): docs.itgrix.ru, support.sensei.plus, cmdf5.ru and mango-office.ru all state 403 = account blocked for repeated rps-limit excess and that unblocking requires contacting amoCRM support (i.e. not self-healing → non-retryable from the client's perspective). Real-world instance: amocrm/amocrm-api-php issue #318 (2021) reports API access fully blocked and replaced by an HTML error page, requiring support intervention — plus the OP asking exactly for a distinct rate-limit exception type so work can be queued rather than retried.
  
  ADVERSARIAL CHECK THAT FAILED TO REFUTE: search surfaced an apparently strong qualifier — a 403 body `{error:{statusCode:403, message:"Forbidden. Try again later", expireDate:"2022-12-13T10:02:13.953Z"}}` implying blocks are *temporary* and auto-lift, which would downgrade "403 = permanent". I traced it and it is NOT amoCRM: WebFetch of https://developer.livesklad.com/ confirms that exact JSON belongs to LiveSklad's API docs. Search-engine cross-contamination; the qualifier does not apply. No amo-sourced evidence of an auto-expiring 403 was found.
  
  SCOPE OF THE INFERENTIAL HALF: "naive retry-on-429 is dangerous / backoff must be conservative" is t
  *Контр-источник:* Candidate counter-evidence examined and dismissed: developer.livesklad.com ("Общие правила и ограничения") — 403 with `expireDate` / "Forbidden. Try again later" indicating a temporary, auto-expiring block. Verified via WebFetch that this belongs to the LiveSklad API, not amoCRM; search results had conflated the two. No amoCRM-sourced counter-evidence found.

### 2. Batch limits are asymmetric and lower than the documented ceiling: reads return at most 500 entities (most methods 250), writes accept at most 250 entities, with amoCRM explicitly recommending no more than 50 per write request — so the client's batching layer should chunk writes at ~50, not at the 250 maximum.

*Источник:* https://www.amocrm.ru/developers/content/api/recommendations (primary)
*Цитата:* "не более 500" сущностей за один запрос (обычно не более 250) ... При создании/изменении: макимум 250 сущностей, рекомендуется не более 50"
*Голоса скептиков:* опровергли 0 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  Verified directly against the cited primary source (two independent fetches of https://www.amocrm.ru/developers/content/api/recommendations returned identical verbatim text):
  
  1. Reads: "Максимальное кол-во возвращаемых сущностей (сделок/контактов/компаний/покупателей) не более 500, большинство методов возвращает не более 250 сущностей" — matches the claim's "at most 500 (most methods 250)" word for word, including the hedge.
  2. Writes: "Максимальное кол-во создаваемых/изменяемых сущностей не более 250, для более оптимальной работы интеграции и избежания ошибок, рекомендуется не более 50" — the 250 ceiling and the ≤50 recommendation are in ONE sentence explicitly scoped to create/update, so the claim's attribution of the "50" to writes is correct, not an overreach.
  3. Same page: "В случае получения 504 ошибки рекомендуется уменьшить количество передаваемых сущностей в запросе и повторить запрос" — independently supports the engineering conclusion (chunk below the ceiling) rather than contradicting it.
  
  Corroboration from a second official source (amocrm/amocrm-api-php README, master): "Для оптимальной работы с данными лучше всего создавать/изменять за раз не более 50 сущностей в методах, где есть пакетная обработка." A third data point: https://www.amocrm.ru/developers/content/crm_platform/leads-api caps GET limit at "Количество возвращаемых сущностей за один запрос (Максимум – 250)" and hard-caps POST /api/v4/leads/complex at "За один запрос можно передать не более 50 сделок." — i.e. amo itself enforces 50 on at least one write endpoint.
  
  Attempted refutations that failed: (a) a WebSearch summary garbled the numbers ("максимум 500 создаваемых, рекомендуется 250"), but no URL in the result set actually contains that text and two verbatim fetches of the primary page contradict it — search-model paraphrase noise, not a credible counter-source; (b) targeted searches for anyone disputing/raising the batch limits found only rate-limit changes (7 rps/integration, 50 rps/account, "расширенные лимиты" tiers), which concern request frequency, not batch size, so they do not touch the claim.
  
  Two genuine (non-fatal) qualifications for the consumer of th
  *Контр-источник:* No credible counter-source found. The only apparent contradiction was an unsourced WebSearch paraphrase inverting 250/500 for writes; it is not backed by any retrievable page and is contradicted by two verbatim fetches of the primary doc and by the official amocrm/amocrm-api-php README.
- **Голос 2 — не опроверг** (уверенность: high):
  Primary source verified directly (two independent fetches of https://www.amocrm.ru/developers/content/api/recommendations, consistent wording both times). Verbatim RU text on the page:
  - reads: «Максимальное кол-во возвращаемых сущностей (сделок/контактов/компаний/покупателей) не более 500, большинство методов возвращает не более 250 сущностей»
  - writes: «Максимальное кол-во создаваемых/изменяемых сущностей не более 250» + «для более оптимальной работы интеграции и избежания ошибок, рекомендуется не более 50»
  - same page: 7 rps per integration / 50 rps per account, 429 on overage, and 504 guidance to reduce entity count per request.
  
  Cross-check against per-entity docs corroborates rather than contradicts: https://www.amocrm.ru/developers/content/crm_platform/leads-api documents «Количество возвращаемых сущностей за один запрос (Максимум – 250)» for the GET `limit` param, and the complex add method («сделка с контактом и компанией») has a hard cap «За один запрос можно передать не более 50 сделок». contacts-api likewise documents limit max 250 and states no higher write cap. So nothing in the entity docs exceeds the ceilings in the claim.
  
  Claim-vs-quote fidelity: the claim reproduces the three numbers exactly and does not upgrade the 50 from "recommended" to "hard limit" — it says "explicitly recommending", which is what the page says. The asymmetry (read ceiling 500/typical 250 vs write ceiling 250) is stated on the page, not inferred. The normative tail ("chunk writes at ~50, not at the 250 maximum") is an engineering inference, but it is the vendor's own explicit recommendation plus the page's own 504-remediation advice, not an overreach.
  
  Refutation attempts that failed: WebSearch for contradicting/qualifying sources returned only third-party wrappers (andrey-tech/amocrm-api-php, bzdvdn/amocrm-api-wrapper, Blackgard/amocrm-api) and amocrm/amocrm-api-php issue #318 on rate limits — none dispute the numbers; the only divergent statement was a search-summarizer garble ("max 500 create/modify, recommend 250"), which is an LLM paraphrase artifact contradicted by the primary page fetched twice, not a credible counter-source.
  
  Caveats (do not
  *Контр-источник:* No credible counter-source found. Nearest divergence was a WebSearch auto-summary claiming "max 500 create/modify, recommended 250" — traced to search-snippet paraphrase, contradicted by two direct fetches of the primary amocrm.ru recommendations page.
- **Голос 3 — не опроверг** (уверенность: high):
  Tried hard to refute; the claim survives on primary-source evidence, with one caveat that does not touch its actionable conclusion.
  
  VERBATIM PRIMARY (https://www.amocrm.ru/developers/content/api/recommendations, fetched 2026-07-22, two separate fetches with different prompts returned identical wording):
  - reads: "Максимальное кол-во возвращаемых сущностей (сделок/контактов/компаний/покупателей) не более 500, большинство методов возвращает не более 250 сущностей"
  - writes: "Максимальное кол-во создаваемых/изменяем сущностей не более 250, для более оптимальной работы интеграции и избежания ошибок, рекомендуется не более 50"
  - rate: "не более 7 запросов в секунду на одну интеграцию", "до 50 запросов в секунду на весь аккаунт"; 429 on excess, 403 on repeat violation.
  
  1) Quote-vs-claim: exact match, not an overreach. "500 reads / most 250", "250 writes max", "recommended ≤50" are all literal restatements. The design inference ("chunk writes at ~50") is the vendor's own explicit recommendation, not the claimant's extrapolation.
  
  2) Independent cross-check on the vendor's international doc site (amocrm.com → kommo.com → https://developers.kommo.com/docs/limitations): "The maximum number of entities added/updated is no more than 250", "we recommend to add/update no more than 50", "not more than 7 requests per second", plus an extra corroborating rule the claim didn't cite: "If you receive a 504 HTTP code, we recommend to reduce the number of entities added/updated in the request and repeat the request." That 504 rule independently validates chunking below the 250 max and argues for adaptive shrink-on-504 in the batching layer.
  
  3) Per-endpoint docs reinforce, not contradict: https://www.amocrm.ru/developers/content/crm_platform/leads-api gives GET limit "Количество возвращаемых сущностей за один запрос (Максимум – 250)", and the complex-lead creation endpoint states "За один запрос можно передать не более 50 сделок." — i.e. at least one write endpoint hard-caps at exactly 50, so a client batching at 250 would 400 there.
  
  CAVEAT (qualifies, does not refute): the "500" read figure appears only in the RU recommendations page's prose; the EN/Komm
  *Контр-источник:* Partial tension only: https://developers.kommo.com/docs/limitations states "The maximum number of returned entities (leads / contacts / companies) is not more than 250" (vs the RU page's 500), and https://www.amocrm.ru/developers/content/crm_platform/leads-api caps GET limit at 250 — so the claim's "500" read ceiling is doc-prose that no per-endpoint spec backs. Same source also adds "За один запр

### 3. Обработка 429 в библиотеке фактически сломана, а ретраев/бэкоффа нет вообще. `AmoCRMApiTooManyRequestsException` бросается единственный раз — внутри ветки «тело ответа не распарсилось как JSON» (`!$decodedBody && !empty($bodyContents)`); если amoCRM отдаст 429 с валидным JSON-телом, исключение будет не TooManyRequests, а generic `AmoCRMApiException("Invalid http status")`. Grep по всему request-слою (785 строк `AmoCRMApiRequest.php`) не находит ни одного вхождения retry/backoff/sleep/usleep/Retry-After; README также не содержит ни слова про ретраи, rate limiting и 429.

*Источник:* https://github.com/amocrm/amocrm-api-php (primary)
*Цитата:* "if (
*Голоса скептиков:* опровергли 0 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  Verified directly against the primary source: downloaded amocrm/amocrm-api-php master (codeload zip, fetched 2026-07-22), package `amocrm/amocrm-api-library`, `LIBRARY_VERSION = '1.17.0'`.
  
  CONFIRMED, mechanically, line-by-line:
  1) `wc -l src/AmoCRM/Client/AmoCRMApiRequest.php` = **785** — exactly the number in the claim.
  2) `grep -rn "TOO_MANY_REQUESTS\|TooManyRequests" src/` → only 4 hits: the exception class file, the `use` import (l.11), and the single throw site at l.654-655. There is exactly ONE throw of `AmoCRMApiTooManyRequestsException` in the whole library.
  3) That throw sits inside `parseResponse()` (l.642-672), nested in the non-JSON branch verbatim:
  ```php
  if (
      $response->getStatusCode() !== StatusCodeInterface::STATUS_ACCEPTED
      && !$decodedBody
      && !empty($bodyContents)
  ) {
      if ($response->getStatusCode() === StatusCodeInterface::STATUS_TOO_MANY_REQUESTS) { throw new AmoCRMApiTooManyRequestsException(...); }
      throw new AmoCRMApiException("Response body is not json", ...);
  }
  $this->checkHttpStatus($response, $decodedBody);
  ```
  4) The fall-through is exactly as claimed: `checkHttpStatus()` (l.580-632) branches only on 401 → `AmoCRMoAuthApiException`, 204 → `AmoCRMApiNoContentException`, 400+`validation-errors` → `AmoCRMApiErrorResponseException`; everything else not in `SUCCESS_STATUSES` (`[200,201,202,204]`, l.42-48 — 429 is not there) → generic `new AmoCRMApiException("Invalid http status", ...)` at l.610. So a 429 carrying a valid JSON body yields "Invalid http status", not TooManyRequests. The claim is if anything UNDERSTATED: a 429 with an *empty* body also misses the branch (`!empty($bodyContents)` false) and likewise degrades to the generic exception.
  5) Repo-wide `grep -rniE "retry|backoff|usleep|Retry-After" --exclude-dir=.git .` returns **zero hits** — not just in the request layer, in the entire repository (src, examples, README). No retry loop, no exponential backoff, no Retry-After parsing anywhere.
  
  TWO QUALIFICATIONS (do not overturn the claim, but the wording should be tightened before use):
  (a) The last sentence "README не содержит ни слова про ... rate limiting" is inaccurate
  *Контр-источник:* README.md l.196-197 of amocrm/amocrm-api-php (mentions API limits + 50-entity batch guidance, contradicting the "ни слова про rate limiting" sub-clause); AmoCRMApiRequest.php l.151/584-590 `setCheckHttpStatusCallback` (extension point the claim omits)
- **Голос 2 — не опроверг** (уверенность: high):
  Verified directly against the primary source: `git clone --depth 1 https://github.com/amocrm/amocrm-api-php` → HEAD = a0072fec = tag **1.17.0**, dated **2026-07-01** (3 weeks old — claim is current, not stale).
  
  CONFIRMED (load-bearing parts):
  1. `src/AmoCRM/Client/AmoCRMApiRequest.php` = **exactly 785 lines** (`wc -l`), as claimed.
  2. `parseResponse()` lines 649–667: the 429 branch is nested inside `if ($response->getStatusCode() !== STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents))` — i.e. reachable ONLY when the body fails `json_decode` (or decodes falsy) AND is non-empty. Verbatim as quoted.
  3. `grep -nE "TooManyRequests|429|STATUS_TOO_MANY"` repo-wide → 5 hits total, only ONE throw site (line 655). No other 429 path anywhere.
  4. 429-with-valid-JSON path verified: `checkHttpStatus()` (line 585+) special-cases only 401 (`AmoCRMoAuthApiException`) and 400+`validation-errors` (`AmoCRMApiErrorResponseException`); `SUCCESS_STATUSES` (line 42) = [200, 201, 202, 204], so 429 falls to line 609–615 `new AmoCRMApiException("Invalid http status", …)`. Exactly as claimed. (Bonus, claim understates it: a 429 with an EMPTY body also misses the branch via `!empty($bodyContents)` and lands on the same generic exception.)
  5. `grep -rniE "retry|backoff|usleep|sleep\(|retry-after|rate.?limit"` over **all** *.php/*.md/*.json in the repo → **zero hits**. No retry, no backoff, no Retry-After, repo-wide (not just the request layer).
  6. Independent upstream corroboration: issue **#526 "Поддержка Retry-After" is still OPEN since 2023-09-04**, and #318 "Как работать с ограничениями кол-ва запросов на аккаунт" OPEN since 2021-05-22. PR/issue #517 (merged 2023-07-10) is what introduced the exception in that spot.
  
  TWO QUALIFICATIONS the synthesizer must fix in wording (neither kills the claim):
  (a) The README sub-claim is **factually wrong as written**: README.md line 196–197 does address limits — «При работе с библиотекой необходимо не забывать о лимитах API amoCRM. Для оптимальной работы… не более 50 сущностей в методах, где есть пакетная обработка.» Correct phrasing: the README mentions amo's limits and a 50-per-batch guideline, but says nothing about 
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/issues/526 (open «Поддержка Retry-After», 2023-09-04, notes amo returns 429 as HTML → the existing branch does fire in practice); https://github.com/amocrm/amocrm-api-php/blob/master/README.md#L196 (README does mention amo API limits + ≤50 per batch, contradicting the «ни слова про rate limiting» part); AmoCRMApiRequest.php ~line 562 (one-shot re-request af
- **Голос 3 — не опроверг** (уверенность: high):
  VERIFIED against primary source: cloned amocrm/amocrm-api-php at HEAD a0072fec (2026-07-01, current — 3 weeks old), file src/AmoCRM/Client/AmoCRMApiRequest.php.
  
  (1) SINGLE THROW SITE CONFIRMED. `grep -rn 'AmoCRMApiTooManyRequestsException' src/` yields exactly one throw, line 655, nested inside the guard at lines 649-653: `$response->getStatusCode() !== StatusCodeInterface::STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents)`. Verbatim match to the supporting quote — no overreach.
  
  (2) FALL-THROUGH PROVEN EMPIRICALLY, not inferred. checkHttpStatus() (line 580) branches only on 401 (line 592) and 204 (line 601), then catch-all `new AmoCRMApiException("Invalid http status", ...)` at lines 610-611. SUCCESS_STATUSES (line 42) = {200,201,202,204}, so 429 hits the catch-all. I ran composer install and invoked the real private parseResponse via reflection with PHP 8.4.21 (script: .../scratchpad/probe429.php). Actual output:
    429 + valid JSON body           => AmoCRMApiException | msg=Invalid http status
    429 + NON-json body (html)      => AmoCRMApiTooManyRequestsException | msg=Too many requests
    429 + empty body                => AmoCRMApiException | msg=Invalid http status
  Exactly the inversion the claim describes (empty-body case is an extra hole the claim understates).
  
  (3) 785 LINES — exact match (`wc -l` = 785).
  
  (4) NO RETRY/BACKOFF ANYWHERE. `grep -niE 'retry|backoff|sleep|usleep|Retry-After|rate.?limit|429'` on AmoCRMApiRequest.php → exit=1, zero matches. Same grep across ENTIRE src/ → zero matches. No HandlerStack/Middleware in src/ (raw GuzzleHttp\ClientInterface only); OAuth layer grep count = 0. Sole extension point `customCheckHttpStatusCallback` (line 585) merely SUPPRESSES a status check — it cannot retry. Retry-After header is never read.
  
  MINOR OVERREACH (non-fatal, noted for precision): claim says README has "ни слова про ретраи, rate limiting и 429". README (1106 lines) has zero mentions of retries/429/TooManyRequests, but line 196 does say "При работе с библиотекой необходимо не забывать о лимитах API amoCRM" + advice to batch <=50 entities. That is a caveat delegating rate-limit responsibility to the CALLER an
  *Контр-источник:* No credible contradicting source found. Only qualification is internal: README.md line 196 mentions amoCRM API limits exist (caller-side caveat, no library mechanism, no retry/429 wording), slightly softening the claim's absolute "ни слова про rate limiting" sub-clause.

### 4. amoCRM's maintainer publicly committed (May 2021) to adding rate-limit error handling to the official PHP library — both a retry capability and a dedicated exception type — but the issue is still OPEN as of 2026-07-22 (last activity 2021-06-11) and only the exception half shipped: `AmoCRM\Exceptions\AmoCRMApiTooManyRequestsException` exists, while a GitHub code search over the repo returns 0 hits for `retry`, 0 for `Retry-After` and 0 for `middleware`. The official library therefore has NO retry/backoff/middleware layer at all — throttling, backoff and Retry-After handling are entirely the caller's problem, which is a hard requirement for any replacement client.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/318 (primary)
*Цитата:* "В библиотеке в ближайшее время добавим обработку ошибок, которые идут при превышении, скорее всего это будет возможность повторения запроса и отдельный тип Exception"
*Голоса скептиков:* опровергли 0 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  Verified against primary sources (GitHub API + full source checkout), not just the web page. Every component of the claim holds; attempts to refute it failed.
  
  1. ISSUE STATE — CONFIRMED EXACTLY. `gh api repos/amocrm/amocrm-api-php/issues/318` returns: `"state":"open"`, `"closed_at":null`, `"created_at":"2021-05-22T08:31:26Z"`, `"updated_at":"2021-06-11T10:15:21Z"`, `"comments":11`. Open, last activity 2021-06-11 — exactly as claimed.
  
  2. QUOTE FIDELITY — CONFIRMED VERBATIM, and maintainer identity independently corroborated. The quote is comment 3 by user `bessudnov`, 2021-05-23T10:41:29Z, point 3 of a numbered list. "Maintainer" is not an overreach: the repo's own composer.json lists `"authors": [{"name":"Nikita Bessudnov","email":"nbessudnov@team.amocrm.com","homepage":"https://github.com/bessudnov"}]` — the commenter is the library's author on an @team.amocrm.com address.
  
  3. ZERO-HITS CLAIM — CONFIRMED, and verified more strongly than claimed. Cloned HEAD `a0072fec` (2026-07-01). `grep -ric "retry\|middleware" -r --exclude-dir=.git .` over the ENTIRE repo, all file types (not just GitHub's code-search index): zero matching files. Separately zero for `retry.after`. So no retry, no backoff, no Retry-After, no middleware layer anywhere in the shipped library.
  
  4. EXCEPTION HALF — CONFIRMED. `src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php` exists, docblock "Выбрасывается в случае ответа от сервера с кодом 429". Thrown at exactly one site: AmoCRMApiRequest.php:655.
  
  5. NOT OUTDATED / NOT A DEAD-REPO ARTIFACT (my main refutation attempt — failed). The repo is actively maintained: `pushed_at` 2026-07-01, release v1.17.0 published 2026-07-01 (three weeks before the claim's as-of date), 126 open issues. The absence of retry is a live architectural fact after 5 years of continued development, not stale-repo noise. This strengthens rather than weakens the claim.
  
  6. "BUT GUZZLE HAS MIDDLEWARE UNDERNEATH" (my strongest refutation attempt — failed). The lib hard-depends on `guzzlehttp/guzzle: 6.*||7.*` (not PSR-18), so a HandlerStack exists underneath. But there is NO injection point: `AmoCRMApiClient::__construct(?string $clientId, 
- **Голос 2 — не опроверг** (уверенность: high):
  CLAIM SURVIVES. Verified against primary sources (GitHub API + local clone of the current release), not rendered pages.
  
  1. ISSUE STATE — CONFIRMED EXACTLY. `gh api repos/amocrm/amocrm-api-php/issues/318` returns: `{"state":"open","closed_at":null,"created_at":"2021-05-22T08:31:26Z","updated_at":"2021-06-11T10:15:21Z","comments":11}`. Open, opened May 2021, last activity 2021-06-11 — all three match the claim to the day.
  
  2. QUOTE — VERBATIM AND CORRECTLY ATTRIBUTED. Comment by `bessudnov`, 2021-05-23T10:41:29Z, point 3: "В библиотеке в ближайшее время добавим обработку ошибок, которые идут при превышении, скорее всего это будет возможность повторения запроса и отдельный тип Exception". `author_association` = **COLLABORATOR**, so "maintainer" is accurate. A user (`Maximryzhkov`, 2021-06-10) explicitly chased it — "как скоро это реализуете?" — and got no reply; that unanswered ping is the second-to-last comment on the thread.
  
  3. CODE SEARCH — REPRODUCED. `gh api search/code?q=retry+repo:amocrm/amocrm-api-php` → `0`; `"Retry-After"` → `0`; `middleware` → `0`. Identical to the claim.
  
  4. INDEPENDENT LOCAL GREP (stronger than code search, which can be indexing-flaky) — CONFIRMS. Cloned HEAD `a0072fec` = tag **1.17.0**, dated 2026-07-01 (repo is actively maintained, not abandoned — 126 open issues, pushed 2026-07-01). Over the working tree: `grep -rin "retry" --include="*.php" .` → **0**; `middleware|HandlerStack` → **0**; `retry-after|retryAfter` → **0**; `sleep(|usleep|backoff|throttl|rate.?limit` in `src/` → **0**. `AmoCRM\Exceptions\AmoCRMApiTooManyRequestsException` does exist at `src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php`. So: exception half shipped, retry half never did — exactly as claimed, and still true 5 years later.
  
  5. THE ONLY OVERREACH IS COSMETIC. "publicly committed ... to both a retry capability and a dedicated exception type" slightly overstates: the maintainer definitely committed to error handling ("добавим обработку ошибок"), but hedged the specifics with "скорее всего" (most likely). This does not change the claim's substance or its architectural conclusion.
  
  6. THE CLAIM ACTUALLY UNDERSTATES THE PROBL
  *Контр-источник:* No contradicting source found. WebSearch for retry/429/backoff in the official library surfaced only third-party libs (ufee/amoapi) that do handle 429 — explicitly NOT the official one — and generic 429 best-practice articles. No PR, release note, or issue shows retry/backoff ever landing in amocrm/amocrm-api-php. Only qualification found is semantic, not factual (see evidence #5).
- **Голос 3 — не опроверг** (уверенность: high):
  VERIFIED AGAINST PRIMARY SOURCES — every factual component holds; I tried hard to break it and instead found the claim understates the problem.
  
  1) ISSUE STATE (gh api repos/amocrm/amocrm-api-php/issues/318, fetched 2026-07-22):
  {"number":318,"state":"open","created_at":"2021-05-22T08:31:26Z","updated_at":"2021-06-11T10:15:21Z","closed_at":null,"comments":11}
  Exactly as claimed: open, created May 2021, last activity 2021-06-11. Title: "Как работать с ограничениями кол-ва запросов на аккаунт".
  
  2) QUOTE IS VERBATIM AND CORRECTLY ATTRIBUTED. Comment by `bessudnov` (author_association=COLLABORATOR), 2021-05-23T10:41:29Z, point 3 of 3: "В библиотеке в ближайшее время добавим обработку ошибок, которые идут при превышении, скорее всего это будет возможность повторения запроса и отдельный тип Exception". `bessudnov` is not merely a collaborator — composer.json lists him as the sole author: {"name":"Nikita Bessudnov","email":"nbessudnov@team.amocrm.com","homepage":"https://github.com/bessudnov"}. So "maintainer publicly committed" is accurate on attribution.
  
  3) CODE STATE — I did not rely on GitHub code search; I cloned the repo and grepped the working tree at HEAD a0072fec (2026-07-01, "Merge pull request #631"):
    grep -rniE 'retry' . --exclude-dir=.git  → count=0 (whole repo: src/, tests/, examples/, README.md, composer.json)
    grep -rniE 'middleware|handlerstack' .   → count=0
    grep -rniE 'retry-after|retryAfter' src/ → 0
    grep -rniE 'usleep|sleep\(|ratelimit|throttl|backoff' src/ → 0
    `AmoCRM\Exceptions\AmoCRMApiTooManyRequestsException` DOES exist (src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php), an empty class extending AmoCRMApiHttpClientException. Its git history: single commit be1eb2c, 2023-07-10 — i.e. it shipped ~2 years AFTER the promise, confirming "only the exception half shipped".
  
  4) I ALSO CHECKED THE COMPANION PACKAGE (the only other PHP repo in the amocrm org): github.com/amocrm/amocrm-oauth-client → retry=0, middleware=0, 429=0. No official retry/middleware package exists anywhere in the org (org repos: javascript, amocrm-oauth-client, amocrm-api-php, gtm-template).
  
  5) THE LIBRARY ITSELF SAYS THROT
  *Контр-источник:* No credible contradicting source found. Nearest thing to a counter-consideration: the repo is actively maintained (HEAD 2026-07-01), so one might expect retry to have landed silently outside issue #318 — a full-tree grep at HEAD disproves that (0 hits for retry/middleware/Retry-After). Second nearest: Guzzle 6/7 is a hard dependency (composer.json) and ships Middleware::retry, so a reader might as

### 5. The library exposes amoCRM's undocumented private browser AJAX channel through the SAME generic entry point as the public REST API — `AmoAPI::request(string $query, string $type = 'GET', array $params = [], string $subdomain = null): ?array` where `$type` may be `'AJAX'` (example given for `/ajax/contacts/list`). There is no separate namespace, client class, version pin or feature flag isolating the unstable channel from stable v4 — the only marker is a magic string argument.

*Источник:* https://github.com/andrey-tech/amocrm-api-php (primary)
*Цитата:* "Позволяет выполнить RAW запрос к API amoCRM"
*Голоса скептиков:* опровергли 1 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  VERIFIED against primary source (repo master = latest release 3.1.0, pushed 2026-03-17, so current, not stale).
  
  1) Signature — exact match. README master line 741: `static request(string $query, string $type = 'GET', array $params = [], string $subdomain = null) :?array` / "Позволяет выполнить RAW запрос к API amoCRM." Line 743: "`$type` - метод запроса 'GET', 'POST' или 'AJAX'". Quote is not an overreach; it is the literal doc line for that method.
  
  2) The AJAX example is real. README line 1653-1681, section "Поддержка AJAX-запросов к frontend-методам": "Метод `\AmoCRM\AmoAPI::request()` позволяет выполнять AJAX-запросы к frontend-методам" with `$data = AmoAPI::request('/ajax/contacts/list', 'AJAX', $params);`.
  
  3) "No separate namespace/class/flag" — confirmed at source level, not just docs. Full src tree (github API git/trees master recursive) = 22 flat blobs under `src/AmoCRM/` + `src/AmoCRM/TokenStorage/`; there is NO Ajax/Frontend/Internal class or namespace. In `src/AmoCRM/AmoAPIRequest.php` (v3.1.0) `'AJAX'` is literally `case 'AJAX':` (line 314) inside the same `switch ($type)` as `'GET'`/`'POST'` (lines 289-324) in the single `public static function request()` (line 252). Same URL builder for all three (`$url = 'https://' . self::getAmoDomain($subdomain) . $query;`, line 284), same throttling, same `AmoAPIException`. The only behavioural difference: body via `http_build_query()` and header `X-Requested-With: XMLHttpRequest` instead of `Content-Type: application/json` (`buildHTTPHeaders(..., bool $isAjax = false, ...)`, lines 557-577). No feature flag, no config toggle, no version pin, no separate transport. The channel-selector is exactly one magic string argument, and an unknown value only yields `throw new AmoAPIException("Недопустимый метод запроса {$type}")` (line 324).
  
  CAVEATS (do not overturn the claim, but should be corrected in the report):
  (a) "isolating the unstable channel from stable v4" mis-states this library's public channel. README line 13: "обертка ... для работы с REST API amoCRM **v2 (версии 2)**"; line 25 explicitly says v4 is the current version — i.e. this wrapper targets the deprecated v2, not v4. Correct phra
  *Контр-источник:* No credible counter-source exists. Searched "andrey-tech amocrm-api-php AmoAPI::request AJAX raw запрос" — results (github README, packagist/php-download mirrors, releases page) all corroborate. Attempted refutation via source inspection (full src tree + AmoAPIRequest.php v3.1.0) also failed to find any isolating class/flag. Nearest thing to a counter is internal: the library's own README calls it
- **Голос 2 — опроверг** (уверенность: high):
  CORE MECHANISM VERIFIED, BUT THE CLAIM AS WRITTEN CONTAINS A FALSE LOAD-BEARING ELEMENT AND IS NOT SUPPORTED BY ITS OWN QUOTE.
  
  What checks out (primary source, https://raw.githubusercontent.com/andrey-tech/amocrm-api-php/master/src/AmoCRM/AmoAPIRequest.php):
  - AJAX is indeed a `switch ($type)` branch inside the SAME `request()` method as GET/POST — no separate class, namespace or flag: `case 'AJAX': $body = http_build_query($params); $headers = self::buildHTTPHeaders($subdomain, true, true, $headers); ... 'POST (AJAX): '` (third arg to buildHTTPHeaders is `$isAjax`, which swaps `Content-Type: application/json` for `X-Requested-With: XMLHttpRequest` and form-encodes the body; HTTP verb stays POST).
  - README does document `AmoAPI::request('/ajax/contacts/list', 'AJAX', $params)` under the section "Поддержка AJAX-запросов к frontend-методам", with no instability/undocumented warning.
  - URL building is `$url = 'https://' . self::getAmoDomain($subdomain) . $query;` — raw string concat, so no version pin whatsoever on either channel. This *strengthens* the "no isolation" observation.
  
  Grounds for refutation:
  1. FACTUALLY CONTRADICTED on "stable v4". The library has NO v4 support at all. Repo description: "Простая обертка на PHP7+ для работы с API amoCRM **v2**…"; README: "для работы с REST API amoCRM v2 (версии 2)", explicitly noting v4 is now current. Release notes for 3.0.0 (2026-03-05) and 3.1.0 (2026-03-17) mention only PHP 8.3, symfony/http-client, symfony/lock, custom headers, PHPUnit 10 — zero v4 work. So "no … version pin or feature flag isolating the unstable channel from stable v4" misdescribes the artifact: the AJAX channel sits next to **v2**, in a v2-only legacy wrapper. As a data point for designing a 2026 **v4** client this is a misattribution.
  2. UNSUPPORTED BY THE CITED QUOTE. The supporting quote "Позволяет выполнить RAW запрос к API amoCRM" says nothing about AJAX, about the private browser channel, or about absence of isolation. Every load-bearing part of the claim had to be established from other text/source, not from the quote given.
  3. SIGNATURE IS STALE. Claim states `request(string $query, string $type = 'GET', array $para
  *Контр-источник:* https://github.com/andrey-tech/amocrm-api-php (repo description + README: "REST API amoCRM v2 (версии 2)", no v4 support); https://api.github.com/repos/andrey-tech/amocrm-api-php/releases/tags/3.0.0 (changelog: custom HTTP headers added → 5th param, no v4); https://raw.githubusercontent.com/andrey-tech/amocrm-api-php/master/src/AmoCRM/AmoAPIRequest.php (actual signature with `array $headers = []`;
- **Голос 3 — не опроверг** (уверенность: high):
  Verified line-by-line against primary source (master branch, gh API), not just the README blurb.
  
  1) README.md line 740-743 (verbatim): "`static request(string $query, string $type = 'GET', array $params = [], string $subdomain = null) :?array` / Позволяет выполнить RAW запрос к API amoCRM. / `$type` - метод запроса 'GET', 'POST' или 'AJAX';" — signature in the claim is reproduced exactly.
  
  2) README line 1653-1681: section "### Поддержка AJAX-запросов к frontend-методам" with example `$data = AmoAPI::request('/ajax/contacts/list', 'AJAX', $params);` — the `/ajax/contacts/list` example is real, and the section carries NO stability/undocumented disclaimer.
  
  3) Source `src/AmoCRM/AmoAPIRequest.php` confirms the implementation: `public static function request(string $query, string $type = 'GET', array $params = [], ?string $subdomain = null, array $headers = []): ?array` (l.252-258); `switch ($type)` with `case 'GET' / case 'POST' / case 'AJAX'` (l.289-325) — the private channel is literally a third `case` in the same switch, sharing throttling, logging, error handling, `sendRequest()` and URL building. `$url = 'https://' . self::getAmoDomain($subdomain) . $query;` (l.285) — NO version prefix at all; the caller supplies the whole path, so `/api/v2/leads` and `/ajax/...` are indistinguishable to the client. `buildHTTPHeaders(..., $isAjax)` (l.557-580) sends the SAME `Authorization: Bearer <oauth token>` for both, only swapping `Content-Type: application/json` for `X-Requested-With: XMLHttpRequest`.
  
  4) The "no isolation" part is confirmed negatively too: grep -i "ajax" across `AmoAPI.php`, `AmoAPIAuth.php`, `AmoAPIOAuth2.php`, `AmoObject.php` returns ZERO hits — no AJAX class, no namespace, no `enableAjax`/feature flag anywhere. Full tree has one namespace `AmoCRM` and no ajax-specific file.
  
  CORRECTIONS the synthesis must apply (they do not invalidate the architectural finding, but the wording is wrong):
  - "stable v4" is factually wrong for this library. README l.13: "Простая обертка на PHP для работы с REST API amoCRM **v2 (версии 2)**"; l.25: "В настоящее время актуальной версией является REST API amoCRM **v4**". andrey-tech/amocrm-api-php n
  *Контр-источник:* https://github.com/andrey-tech/amocrm-api-php/blob/master/src/AmoCRM/AmoAPIRequest.php (l.252-330, l.557-581) and README.md l.13, l.25, l.740-743, l.1653-1681 — the only contradiction found is "v4": README l.13 pins the library to REST API v2, so the phrase "stable v4" in the claim is unsupported.

### 6. Error handling collapses to a single flat exception class `\AmoCRM\AmoAPIException` (plus a separate `TokenStorage\TokenStorageException`) with `getErrors(): array` / `getItems(): array` accessors — no exception hierarchy per HTTP status or error family, no transient-vs-permanent classification, and no Unknown-error trap, so a new/unrecognized amoCRM error type is indistinguishable from a known one at the catch site.

*Источник:* https://github.com/andrey-tech/amocrm-api-php (primary)
*Цитата:* "При возникновении ошибок выбрасывается исключение с объектом класса `\AmoCRM\AmoAPIException`"
*Голоса скептиков:* опровергли 0 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  Verified against the PRIMARY source (actual code, not just README), so the weak supporting quote is not the only basis.
  
  1) Full repo tree (GitHub API, `git/trees/master?recursive=1`, branch master, last push 2026-03-17, archived=false) contains exactly TWO exception files in the entire library: `src/AmoCRM/AmoAPIException.php` and `src/AmoCRM/TokenStorage/TokenStorageException.php`. No other `*Exception*` file exists.
  
  2) Both classes extend `\Exception` DIRECTLY — no common base, no interface, no per-status/per-family subclasses:
  - `class AmoAPIException extends Exception` with `protected $errors = []` (from `$response['_embedded']['errors']`), `protected $items = []` (from `$response['_embedded']['items']`), and `setErrors()/getErrors()/setItems()/getItems()`. Confirms the `getErrors(): array` / `getItems(): array` accessor part verbatim.
  - `class TokenStorageException extends Exception` (separate namespace `AmoCRM\TokenStorage`, no relation to AmoAPIException).
  
  3) No transient-vs-permanent classification — `src/AmoCRM/AmoAPIRequest.php:357-362` funnels EVERY non-2xx into one class regardless of family:
  `if (!in_array($code, self::$successStatusCodes, true)) { throw new AmoAPIException(self::getErrorMessage($code) . ..., $code); }`
  500/502/503 (all present in the `$errorCodes` map at lines 141-143) and 400 are the same type; only the HTTP code lands in `Exception::getCode()`. Transport failures also collapse into the same class and additionally DROP the cause chain: line 331-333 `catch (TransportExceptionInterface $e) { throw new AmoAPIException('Ошибка HTTP клиента: ' . $e->getMessage() . $requestInfo); }` — no `$previous` passed. The only status-specific behavior is 401 → re-auth retry (lines 344-354), which is control flow, not an exception taxonomy.
  
  4) Unknown-error handling: there IS a textual fallback — `AmoAPIRequest.php:436-442` throws `AmoAPIException("Ошибка: Неизвестная ошибка ...")` and line 669 `$message = self::$errorCodes[$code] ?? 'Неизвестная ошибка';` — but it is the SAME class with the same code, so at the catch site an unrecognized error is type-indistinguishable from a known one; discriminating requires string-ma
  *Контр-источник:* No credible counter-source found. Searched for disputes/qualifications; only forks and package mirrors of the same README appeared (github.com/aishmurodov/amocrm-api-php-1, php-download.com/package/andrey-tech/amocrm-api-php, packagist.org/packages/andrey-tech/amocrm-api-php), all reproducing the identical two-exception design. The only partial counterweight is internal to the source: AmoAPIReques
- **Голос 2 — не опроверг** (уверенность: high):
  Verified directly against primary source (repo HEAD, master, pushed 2026-03-17, not a fork, 38 stars — current, not outdated).
  
  1) Exhaustive file listing via GitHub trees API (`/repos/andrey-tech/amocrm-api-php/git/trees/master?recursive=1`) returns exactly TWO exception classes in the entire `src/` tree: `src/AmoCRM/AmoAPIException.php` and `src/AmoCRM/TokenStorage/TokenStorageException.php`. No `Exceptions/` namespace, no per-status/per-family subclasses.
  
  2) `AmoAPIException.php` source: `class AmoAPIException extends Exception` — extends SPL `\Exception` directly, adds only `protected $errors = []` / `protected $items = []` with `setErrors/getErrors/setItems/getItems`. Confirms the accessors. (Minor imprecision in the claim's notation: the real signatures are untyped — `public function getErrors()` / `public function getItems()`, array-ness only in PHPDoc. This makes the library weaker, not stronger, than claimed.)
  
  3) `TokenStorageException.php`: `class TokenStorageException extends Exception` — extends `\Exception`, NOT `AmoAPIException`. So the two are unrelated by inheritance: a caller cannot catch one base type. Confirms "separate".
  
  4) No transient-vs-permanent classification: grep over `AmoAPIRequest.php` (32KB, the whole transport) for `retry|429|backoff|transient` returns ZERO hits. The only sleeps are a fixed-rate throttle (`public static float $throttle = 7;` → `usleep`) and `$lockEntityAttempts`/`sleep($lockEntityTimeout)` for entity-lock contention. HTTP failure path is a single blanket throw: `if (!in_array($code, self::$successStatusCodes, true)) { throw new AmoAPIException(self::getErrorMessage($code) . ..., $code); }` — 500/502/503 and 400/403/404 all produce the same class.
  
  5) "No Unknown-error trap" — the one point needing a caveat, and it survives: there IS a *textual* fallback, `self::$errorCodes[$code] ?? 'Неизвестная ошибка'` in `getErrorMessage()` and a final catch-all branch `new AmoAPIException("Ошибка: Неизвестная ошибка {$requestInfo} ...")`. But it is not a distinct type, carries no marker property, and emits no signal — at the catch site it is byte-for-byte the same `AmoAPIException` as a known 404, disting
  *Контр-источник:* None found. Nearest tension is scope, not truth: official amocrm/amocrm-api-php (https://github.com/amocrm/amocrm-api-php/tree/master/src/AmoCRM/Exceptions) has ~19 exception classes, so the claim would be false if misapplied to it.
- **Голос 3 — не опроверг** (уверенность: high):
  VERIFIED AGAINST PRIMARY SOURCE CODE (not just README), master @ commit pushed 2026-03-17, fetched 2026-07-22.
  
  1) Exception inventory — exhaustive, from the GitHub git-tree API (`/repos/andrey-tech/amocrm-api-php/git/trees/master?recursive=1`): the entire `src/` contains exactly two exception files — `src/AmoCRM/AmoAPIException.php` and `src/AmoCRM/TokenStorage/TokenStorageException.php`. Both extend `\Exception` directly and are unrelated to each other (`class AmoAPIException extends Exception`, `class TokenStorageException extends Exception`). No `Exceptions/` namespace, no subclasses. Claim's structural assertion confirmed at code level.
  
  2) Flat throw sites — grepped every source file: `AmoAPIRequest.php` throws `new AmoAPIException(...)` at lines 263, 269, 302, 324, 332, 358, 372, 393, 426, 436, 709; `AmoAPI.php` (125, 162, 171), `AmoObject.php` (186, 191, 349), `AmoAPIOAuth2.php` (95, 107), `AmoAPIIncomingLeads.php` (113), `AmoAPIAuth.php` (77) — 100% the same class. HTTP status is stuffed into `Exception::$code` (`throw new AmoAPIException(self::getErrorMessage($code) . ..., $code)`, line 358), i.e. status is a scalar, not a type. Zero exception hierarchy per status/family: confirmed.
  
  3) Accessors — `AmoAPIException` has `setErrors/getErrors/setItems/getItems` reading `$response['_embedded']['errors']` / `['items']`. MINOR IMPRECISION in the claim's notation: the signatures are `public function getErrors()` / `public function getItems()` with NO declared `: array` return type and untyped `protected $errors = []` properties (PHPDoc-only). This makes the real code *less* typed than the claim implies — it strengthens, not weakens, the architectural point.
  
  4) "No Unknown-error trap" — NUANCE the parent must preserve. An unknown branch does literally exist: `AmoAPIRequest.php:436` throws `new AmoAPIException("Ошибка: Неизвестная ошибка {$requestInfo} (Response: {$response})", $code)`, and `getErrorMessage()` (line 661-673) maps unrecognized codes via `self::$errorCodes[$code] ?? 'Неизвестная ошибка'`. But both produce the SAME `AmoAPIException` class with the unknown code embedded only in a Russian message string — no distinct type, no repor
  *Контр-источник:* https://raw.githubusercontent.com/andrey-tech/amocrm-api-php/master/src/AmoCRM/AmoAPIRequest.php (lines 141-143, 263-442, 524-540, 661-673); https://raw.githubusercontent.com/andrey-tech/amocrm-api-php/master/src/AmoCRM/AmoAPIException.php; https://raw.githubusercontent.com/andrey-tech/amocrm-api-php/master/src/AmoCRM/TokenStorage/TokenStorageException.php; https://api.github.com/repos/andrey-tech

### 7. amoCRM declares the package's scope as an API client covering only the "main entities" plus OAuth 2.0 authorization — i.e. the vendor itself does not claim complete API surface coverage, so gaps in entity support are by design rather than bugs, and a from-scratch client is not obliged to mirror its (partial) entity model.

*Источник:* https://www.amocrm.ru/developers/content/crm_platform/api-php-library (primary)
*Цитата:* "В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM."
*Голоса скептиков:* опровергли 1 из 3

- **Голос 1 — не опроверг** (уверенность: high):
  CORE CLAIM VERIFIED against two independent primary surfaces, both current.
  
  1) Official docs page https://www.amocrm.ru/developers/content/crm_platform/api-php-library — quote confirmed verbatim: "В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM." The page makes no assertion of full/complete API coverage anywhere, enumerates no exhaustive entity list, and contains no deprecation/maintenance statement.
  
  2) Official README (raw.githubusercontent.com/amocrm/amocrm-api-php/master/README.md) — same sentence verbatim, first paragraph. Two additional vendor statements reinforce partial coverage rather than contradict it: "Библиотека поддерживает большое количество методов API" (a quantity claim, not a completeness claim) and "Не все методы доступны во всех сервисах" (explicitly: not every method exists on every service; calling an unsupported one throws). The services table lists ~40 services (notes, tags, tasks, leads, contacts, companies, catalogs, catalogElements, customFields, customFieldGroups, account, roles, users, customersSegments, events, eventTypes, webhooks, unsorted, pipelines, statuses, widgets, lossReason, transactions, customers, customersStatuses, customersBonusPoints, calls, products, links, shortLinks, talks, sources, chatTemplates, entitySubscriptions, files, entityFiles, websiteButtons, currencies, getOAuthClient, getRequest) — broad, but bounded, and notably excludes e.g. the Amojo/chats host and salesbot surfaces.
  
  NOT OUTDATED: latest release 1.17.0, 2026-07-01 (90 releases total, 117 open issues) — actively maintained, so the scoping sentence is the vendor's current framing, not stale copy.
  
  NOT MARKETING/SECONDARY: the wording is the vendor's own scoping sentence on its own developer docs and its own repo — exactly the source strength the claim needs. Searches for contradicting material (issues/forks alleging the vendor promises full coverage) surfaced nothing disputing partial scope; third-party wrappers (andrey-tech/amocrm-api-php, ufee/amoapi, dedomorozoff/amocrm-api-php-v4) exist precisely because the official client is not exhaustive, which is consonant,
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/releases (90 releases, latest 1.17.0 of 2026-07-01, incrementally adding entity services) — undercuts only the "by design" gloss, not the scope claim itself; README also states "Не все методы доступны во всех сервисах".
- **Голос 2 — не опроверг** (уверенность: high):
  VERIFIED against two primary vendor sources, both fetched today (2026-07-22).
  
  1) Verbatim quote confirmed on the primary URL https://www.amocrm.ru/developers/content/crm_platform/api-php-library — opening line: «В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM.» The same sentence is the first line of the README on master (https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/README.md), so it is the vendor's current, not archived, self-description. Neither page contains any claim of full/complete API-surface coverage.
  
  2) Stronger corroboration than the quote alone — the README carries an explicit limiting statement: «Не все методы доступны во всех сервисах. В случае их вызова будет выброшены Exception.» And that failure mode is a first-class, shipped design element, not an oversight: src/AmoCRM/Exceptions/ contains a dedicated `NotAvailableForActionException` («Метод не доступен для вызова»), one of 19 exception classes, thrown when a service does not implement a given method (real-world hit: issue #409, AttachmentNote). A typed exception whose entire purpose is "this service does not support this operation" is direct evidence that partial coverage is an acknowledged contract, i.e. the "by design rather than bugs" half of the claim is not an overreach.
  
  3) Not outdated: package is actively maintained — v1.17.0 released 2026-07-01, ~90 releases, and the scoping sentence is unchanged on master. Not marketing: it is a self-*limiting* statement in vendor docs (the most reliable class of vendor self-description), not a press release or benchmark. Source strength matches claim strength: the claim is purely about what the vendor declares, and the source IS the vendor's primary documentation. Independent search surfaced no source disputing it.
  
  Two nuances that qualify but do not refute:
  (a) "only" is the claim's word, not the source's — the docs say «с поддержкой основных сущностей», not «только основных». The restrictive reading is nonetheless carried by «основных» plus the explicit «Не все методы доступны…», so this is a fair paraphrase, not a misread.
  (b) "by design r
  *Контр-источник:* No contradicting source found. Closest tension is internal, not contradictory: continued feature-adding releases (v1.17.0, 2026-07-01) and user issues requesting missing capabilities (github.com/amocrm/amocrm-api-php/issues/409, AttachmentNote → NotAvailableForActionException) suggest some gaps are backlog rather than deliberate exclusion — which qualifies "by design" at the per-method level but l
- **Голос 3 — опроверг** (уверенность: medium):
  VERIFIED PART (survives): the quote is genuine and current. The sentence "В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM." appears verbatim on the primary vendor page (amocrm.ru/developers/content/crm_platform/api-php-library) AND as the opening line of the GitHub README (raw.githubusercontent.com/amocrm/amocrm-api-php/master/README.md), and Packagist's short description for amocrm/amocrm-api-library repeats it. So "vendor describes scope as core entities + OAuth 2.0, and never asserts complete API-surface coverage" is well-sourced.
  
  OVERREACH (why refuted): the claim's load-bearing second half — "gaps in entity support are BY DESIGN rather than bugs" — is nowhere in the source and is contradicted by the vendor's own behaviour:
  1. Both fetches of the official page confirm it contains NO statements about limitations, unsupported entities, completeness, or which entities are in/out of scope. "Основных сущностей" is a bare adjective in a one-line blurb, not a declared scope boundary. Inferring intent ("by design") from it is reading a spec into marketing copy.
  2. The release history shows missing surface being FILLED incrementally, i.e. treated as backlog, not as an intentional boundary: 1.17.0 adds the "get talks" endpoint + user activate/deactivate + price-in-minor-units; 1.15.0 adds note pinning; 1.14.0 adds FilesFilter + lead source param; 1.13.2 adds user phone numbers; 1.12.0 adds chat-to-contact binding and multi-value CF filtering. Endpoints arrive when someone needs them — that is "not yet implemented", which is the opposite of "by design".
  3. The blurb is stale boilerplate that has not tracked the library: the README's own "Поддерживаемые методы и сервисы" section now lists ~35 services including distinctly non-"основные" ones (websiteButtons, customersBonusPoints, shortLinks, chatTemplates, entitySubscriptions, customerSegments). A sentence that stopped describing the artifact years ago cannot be used as evidence of deliberate scoping in 2026.
  4. The final clause — "a from-scratch client is not obliged to mirror its (partial) entity model" — is a normative conclusion with 
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/releases (1.17.0 adds talks/user-activation; 1.15.0 note pinning; 1.14.0 FilesFilter; 1.13.2 user phones — missing endpoints added as backlog, not scoped out by design); https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/README.md ("Поддерживаемые методы и сервисы" lists ~35 services incl. websiteButtons, customersBonusPoints, shortLinks, contra

### 8. amoCRM автоматически отключает вебхук, если за последние 2 часа получено более 100 невалидных откликов и последний отклик на момент проверки тоже невалиден. Это прямой аргумент за «громкий» сигнал в Sentry на деградацию приёмника вебхуков: тихая серия 5xx приводит не к деградации, а к полному молчаливому отключению канала.

*Источник:* https://www.amocrm.ru/developers/content/crm_platform/webhooks-format (primary)
*Цитата:* "Ваш хук может быть отключен при следующем условии: За последние 2 часа было получено более 100 невалидных откликов и последний хук на момент проверки так же является невалидным"
*Голоса скептиков:* опровергли 0 из 2

- **Голос 1 — не опроверг** (уверенность: high):
  CORE FACT: CONFIRMED verbatim on the cited primary source, fetched today (2026-07-22). https://www.amocrm.ru/developers/content/crm_platform/webhooks-format states: «Ваш хук может быть отключен при следующем условии: За последние 2 часа было получено более 100 невалидных откликов и последний хук на момент проверки так же является невалидным». Same page defines valid = «код от 100 до 299» and «Если мы не получаем ответ за указанное время или код ответа не успешный (HTTP код не от 100 до 299), мы считаем хук не доставленным» (response window 2 s). So a silent 5xx series — and even a merely slow receiver — feeds the invalid counter, exactly as the claim describes.
  
  INDEPENDENT CORROBORATION (not the same page): Kommo (amoCRM's international docs, developers.kommo.com/docs/webhooks-general) repeats the identical rule in English — "If more than 100 invalid responses were received in the last 2 hours, and the last webhook at the time of the check is also invalid" — plus admin notification and re-enable-via-Save. Two official domains agree; not forum speculation, not marketing, not outdated.
  
  ATTEMPTED REFUTATIONS, all failed:
  (a) Distinct-mechanism confusion: developers.kommo.com/docs/webhooks-dp documents a DIFFERENT rule — ">100 invalid responses from that address within the last 5 minutes" → retries suspended 5 min, auto-resuming. This is per-address retry throttling, not hook disabling; it does not contradict the 2h/100 disable rule. Also documented on amocrm.ru: retry ladder attempt 2/3 at 5/15 min (codes 0-99, 300+), attempts 4/5 at 15 min / 1 h (499, 500-599) — meaning a 5xx burst inflates the invalid count through retries, reinforcing rather than weakening the claim.
  (b) Currency: pages live and current as of fetch date; no deprecation notice.
  
  TWO WORDING CORRECTIONS the synthesis must carry (they qualify but do not kill the claim):
  1. «автоматически отключает» overstates «может быть отключен» — the docs use permissive phrasing, so treat it as a documented possibility under a stated condition, not a guaranteed deterministic trigger.
  2. «полному молчаливому» is imprecise: amocrm.ru explicitly says «администраторы аккаунта получат уведомлени
  *Контр-источник:* https://developers.kommo.com/docs/webhooks-dp — documents a different threshold (>100 invalid responses in the last 5 minutes → retries suspended 5 minutes, auto-resume). Superficially looks like a contradiction of the 2h/100 figure but is a separate per-address retry-throttle mechanism; the disable rule itself is restated identically at https://developers.kommo.com/docs/webhooks-general. Partial 
- **Голос 2 — не опроверг** (уверенность: high):
  VERIFIED against the live primary source (raw curl of https://www.amocrm.ru/developers/content/crm_platform/webhooks-format, fetched 2026-07-22; text extracted from the HTML, not just a summarizer). Section «Обработка ответа от хука» reads verbatim:
  
  «Наш сервис ожидает ответ от хука не более 2 секунд. Если мы не получаем ответ за указанное время или код ответа не успешный (HTTP код не от 100 до 299), мы считаем хук не доставленным, а отклик невалидным. Ваш хук может быть отключен при следующем условии: За последние 2 часа было получено более 100 невалидных откликов и последний хук на момент проверки так же является невалидным. В случае блокировки хук будет отключен в целях безопасности. Вы сможете его активировать повторно в настройках нажав кнопку "Включить", затем сохранить. Также администраторы аккаунта получат уведомление в центр уведомлений об отключении хука.»
  
  Checklist pass:
  1) Quote support — exact, not paraphrase. The claim's added inference «тихая серия 5xx» is directly licensed by the doc: valid = HTTP 100–299 only, so any 5xx qualifies as невалидный отклик; moreover the 2-second timeout means a merely SLOW receiver (not only a failing one) also accrues invalid responses — this strengthens rather than weakens the claim. Manual re-enable («нажав кнопку "Включить", затем сохранить») confirms the failure mode is a hard off-switch requiring human action, not automatic recovery — i.e. «не деградация, а полное отключение канала» is accurate.
  2) Contradicting evidence — none found. Searches surfaced only third-party reproductions of the same rule. The one adjacent-looking artifact is Kommo (rebranded international docs) describing a *retry-suspension* mechanism (>100 invalid responses in 5 min → retries paused 5 min); that is a different, complementary throttle, on a different doc set, and does not dispute the amocrm.ru disable rule that the project actually targets.
  3) Source quality — primary vendor documentation for a vendor-specific behavioral rule; appropriate strength.
  4) Currency — page fetched today, live; wording current.
  5) Not marketing/forum/benchmark.
  
  ONE PRECISION CAVEAT (does not refute, but should be reflected in the 
  *Контр-источник:* https://developers.kommo.com/docs/webhooks (Kommo/international docs describe a separate 5-min retry-suspension throttle, not the 2h/100 disable rule — adjacent mechanism, not a contradiction); no source disputing the amocrm.ru rule was found


## Убиты (≥2 из 3 опровержений) — важны как «во что НЕ верить»

### 1. amoCRM enforces a hard rate limit of 7 requests/sec per integration and up to 50 requests/sec per account — so a reference PHP client must implement client-side rate limiting (token bucket / leaky bucket) keyed per integration, not just per-account, rather than relying on retry-after-429.

*Источник:* https://www.amocrm.ru/developers/content/api/recommendations (primary)
*Цитата:* "не более 7 запросов в секунду на одну интеграцию" и "до 50 запросов в секунду на весь аккаунт"
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: medium):
  VERIFIED HALF (numbers): fetched the primary source today (2026-07-22). https://www.amocrm.ru/developers/content/api/recommendations says verbatim: «не более 7 запросов в секунду на одну интеграцию и до 50 запросов в секунду на весь аккаунт», плюс «В случае превышения количества запросов – будет возвращаться HTTP код 429, при многократном нарушении ограничений, аккаунт блокируется и на любой запрос в API будет возвращаться HTTP код 403». Confirmed independently by https://www.amocrm.ru/developers/content/crm_platform/error-codes (429 = «Превышено допустимое количество запросов в секунду»; 403 = «Аккаунт заблокирован, за неоднократное превышение количества запросов в секунду»). So the quoted figures and the escalation are real, current, primary-sourced.
  
  REFUTED HALF (the operative prescription, which is what a reference architecture would actually implement):
  
  1) "hard rate limit" is wrong — 7/50 are DEFAULTS, purchasable/upgradable and historically mutable. amoCRM sells extended API packages: «до 100 запросов/сек на весь аккаунт, до 15 запросов/сек на каждую интеграцию», 4 tiers up to 100–200+ rps, valid only while the tariff is active (https://sensei.plus/extended-api-limits). And the limit regime has changed over time — amocrm/amocrm-api-php issue #318 records the transition «с 1 июня amoCRM переходит на лимит 50 запросов в секунду на аккаунт». A client that hardcodes a 7 rps bucket as a "hard limit" under-utilizes accounts with an extended package and would need a code change on the next limit revision; the correct design is a per-account CONFIGURABLE rps budget, not a constant 7.
  
  2) "keyed per integration, not just per-account" misreads the scoping. Both documented limits are scoped INSIDE one account — the integration quota is a sub-quota of the account ceiling, as the extended-package wording makes explicit («на весь аккаунт» … «на каждую интеграцию»). For a PHP client holding one integration and OAuth tokens for N accounts (exactly this project's case), the bucket key must be the account (subdomain) with a 7 rps budget each; a globally "per-integration" bucket would throttle all N accounts to 7 rps combined — strictly wrong. Nothing in the s
  *Контр-источник:* https://sensei.plus/extended-api-limits (extended API packages: 100–200+ rps/account, 15 rps/integration — limits are defaults, not hard); https://github.com/amocrm/amocrm-api-php/issues/318 (limit regime changed 1 June; account quota shared with third-party integrations → client-side limiting alone cannot prevent 429); https://www.amocrm.ru/developers/content/crm_platform/error-codes (429/403 sem
- **Голос 2 — опроверг** (уверенность: high):
  The two NUMBERS check out verbatim against the primary source, but the claim's operative architectural conclusion is an overreach on three independent counts.
  
  1) "HARD limit" is false. The 7/50 figures are DEFAULTS, not hard ceilings. amoCRM sells extended API-limit packages that raise both numbers in tiers: 100 req/s account-wide + 15 req/s per integration; 150 + 30; 200 + 50; and >200 negotiated individually (https://sensei.plus/extended-api-limits, corroborated by multiple RU partner sources describing "дополнительные пакеты API-запросов ... до 100-200 запросов в секунду и более"). A client that hardcodes a 7 rps token bucket as a "hard limit" would throttle a customer who paid for the 200/50 tier down to 1/7th of purchased capacity. The correct design conclusion is the opposite of the claim: limits MUST be injectable/configurable per account, not baked in as constants.
  
  2) The keying prescription inverts the documented scoping. The primary page reads "не более 7 запросов в секунду на одну интеграцию" and "до 50 запросов в секунду на весь аккаунт" — the account is the CONTAINING scope, and the 50/s is the aggregate of all integrations on that one account. So 7/s is per-integration-WITHIN-an-account, i.e. the bucket key is the tuple (account/subdomain, integration/client_id) — which for a single-client_id consumer collapses to per-ACCOUNT keying. The claim's "keyed per integration, not just per-account" would, for a consumer holding one client_id across N amo accounts (exactly the octane/masterm case — many accounts, one integration), impose a single global 7 rps bucket across all N tenants. That is architecturally wrong and would be a self-inflicted N-fold throughput cut. Corroborating: the June 2021 change is universally described as "50 req/s PER ACCOUNT", each account having its own pool.
  
  3) "rather than relying on retry-after-429" is unsourced. The quoted page says nothing about Retry-After, X-RateLimit-*, or the adequacy of reactive backoff; the fetched text mentions only "В случае превышения ... будет возвращаться HTTP код 429, при многократном нарушении ограничений, аккаунт блокируется и ... HTTP код 403". The 403-on-repeat-violation clau
  *Контр-источник:* https://sensei.plus/extended-api-limits (extended tiers 100/15, 150/30, 200/50 req/s — refutes "hard"); https://github.com/amocrm/amocrm-api-php/issues/318 (unanswered, no vendor confirmation of keying, no limiter in official lib); primary doc's own scoping wording "на одну интеграцию" vs "на весь аккаунт" at https://www.amocrm.ru/developers/content/api/recommendations
- **Голос 3 — опроверг** (уверенность: high):
  The NUMBERS check out; the three load-bearing claims built on them do not. Verbatim primary text: "не более 7 запросов в секунду на одну интеграцию и до 50 запросов в секунду на весь аккаунт." Refutations:
  
  (1) "HARD limit" is false — the claim truncates the source mid-paragraph. The very next sentence reads: "Лимиты на количество запросов для аккаунта можно увеличить, подключив один из дополнительных пакетов." amo publishes four paid tiers raising it to 15/integration+100/account, 30/150, 50/200, and custom above 200. So 7 is a DEFAULT tier value, tenant-configurable — up to 7x wrong for any account on an extended package. A limiter hardcoded to 7 r/s would silently throttle a paying customer to 1/7th of purchased capacity. The architectural implication is the inverse of the claim: the constant must be injected/configurable, never baked in.
  
  (2) "Keyed per integration, not just per-account" misreads the quota topology. Both quotas are scoped WITHIN one account — the 7/s is an integration's sub-quota of that account's 50/s ceiling ("на весь аккаунт" = summed across all integrations on that account). The correct key is the TUPLE (client_id × subdomain), with both limiters enforced hierarchically — not per-integration INSTEAD of / in priority over per-account. Keying globally per client_id across N accounts over-throttles by ~N× (an integration serving 20 accounts would get 7 r/s total instead of 7 r/s per account). Official repo issue #318 documents the opposite emphasis to the claim: the ACCOUNT dimension is the hazardous one — co-tenant integrations share the 50/s pool, one integration's overuse starves the others and escalates to a 403 account block. Additionally, for private/internal integrations (exactly this context — two of the user's own Laravel projects), client_id is issued per-account, so per-integration vs per-account collapses into the same key and the claimed distinction is moot.
  
  (3) "Must implement client-side token/leaky bucket rather than relying on retry-after-429" is contradicted by the very reference SDKs the research question designates as gold standard. AWS SDK primary docs: default mode is "standard" = NO client-side rate limit
  *Контр-источник:* https://docs.aws.amazon.com/sdkref/latest/guide/feature-retry-behavior.html (AWS SDK retry behavior, primary); https://www.amocrm.ru/developers/content/api/recommendations (same primary source, sentence the claim truncates); https://sensei.plus/extended-api-limits (published amo extended-limit tiers); https://github.com/amocrm/amocrm-api-php/issues/318 (official repo, quota topology); https://www.

### 2. Транспорт в официальной библиотеке жёстко прибит к Guzzle 6/7: `guzzlehttp/guzzle` — прямая runtime-зависимость, а `psr/http-client` (PSR-18), `psr/http-factory` (PSR-17) и `php-http/httplug` в require отсутствуют. Подменить HTTP-клиент (на Symfony HttpClient, на mock/record-replay-транспорт для тестов, на клиент с middleware-ретраями) без хака невозможно — это прямая противоположность паттерну Stripe/Saloon/Sentry/KnpLabs с transport-абстракцией.

*Источник:* https://github.com/amocrm/amocrm-api-php (primary)
*Цитата:* ""require": { "php": ">=7.1 || >=8.0", "ext-fileinfo": "*", "ext-json": "*", "amocrm/oauth2-amocrm": "^3.0", "fig/http-message-util": "1.*", "guzzlehttp/guzzle": "6.* || 7.*", "lcobucci/clock": "1.1.0 ||^2.0.0", "lcobucci/jwt": "^3.4.6 || ^4.0.4 || ^5.0", "nesbot/carbon": "^2.72.6 || ^3.8.4", "ramsey/uuid": "^3 || ^4", "symfony/dotenv": "3.* || 4.* || 5.* || 6.* || 7.*" }"
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  Claim splits into a verifiable half and an overreaching half. The verifiable half CHECKS OUT; the load-bearing conclusion is CONTRADICTED by the same primary source.
  
  CONFIRMED (from primary composer.json at github.com/amocrm/amocrm-api-php/blob/master/composer.json): require = {php, ext-fileinfo, ext-json, amocrm/oauth2-amocrm ^3.0, fig/http-message-util 1.*, guzzlehttp/guzzle "6.* || 7.*", lcobucci/clock, lcobucci/jwt, nesbot/carbon, ramsey/uuid, symfony/dotenv}. No psr/http-client, no psr/http-factory, no php-http/httplug. Guzzle is a direct runtime dep. Code confirms Guzzle coupling: src/AmoCRM/Client/AmoCRMApiRequest.php has `use GuzzleHttp\ClientInterface;`, `use GuzzleHttp\RequestOptions;`, stores `private $httpClient` typed as Guzzle's ClientInterface, and calls the Guzzle-specific `$this->httpClient->request($method, $url, $requestOptions)` (not PSR-18 `sendRequest(RequestInterface)`).
  
  REFUTED — "подменить HTTP-клиент ... без хака невозможно" is false for 2 of the 3 named scenarios. There is a fully public, non-reflective injection seam:
  1. `AmoCRMApiClient::getOAuthClient(): AmoCRMOAuth` — public.
  2. `AmoCRMOAuth::getOAuthProvider(): AmoCRM` — public (src/AmoCRM/OAuth/AmoCRMOAuth.php); also exposes `getHttpClient(): ClientInterface`.
  3. `League\OAuth2\Client\Provider\AbstractProvider` (parent of amocrm/oauth2-amocrm's provider) declares verbatim `use GuzzleHttp\ClientInterface as HttpClientInterface;` and `public function setHttpClient(HttpClientInterface $client) { $this->httpClient = $client; return $this; }`.
  4. `AmoCRMApiClient::buildRequest()` creates a NEW `AmoCRMApiRequest` per call and reads `$oAuthClient->getHttpClient()` in its constructor — so a client injected once takes effect for every subsequent request.
  Therefore `$apiClient->getOAuthClient()->getOAuthProvider()->setHttpClient(new GuzzleHttp\Client(['handler' => $stackWithMockHandlerOrMiddlewareRetry]))` gives, through documented public API and zero hacks: (a) mock/record-replay transport (Guzzle MockHandler + history middleware — the canonical Guzzle testing pattern, docs.guzzlephp.org/en/stable/testing.html), and (b) middleware retries with exponential backoff (`Guz
  *Контр-источник:* https://github.com/thephpleague/oauth2-client/blob/master/src/Provider/AbstractProvider.php (public setHttpClient(GuzzleHttp\ClientInterface)); https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/OAuth/AmoCRMOAuth.php (public getOAuthProvider()/getHttpClient()); https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiClient.php (public getOAuthClient(), per-call
- **Голос 2 — опроверг** (уверенность: high):
  Composer facts are correct, but the two conclusions drawn from them fail verification.
  
  (A) VERIFIED PART. https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/composer.json matches the quote verbatim: direct `"guzzlehttp/guzzle": "6.* || 7.*"`, no `psr/http-client`, no `psr/http-factory`, no `php-http/httplug`. src/AmoCRM/Client/AmoCRMApiRequest.php type-hints `GuzzleHttp\ClientInterface`, calls `$this->httpClient->request()` (Guzzle API, not PSR-18 `sendRequest()`), imports `GuzzleHttp\Exception\{ConnectException,GuzzleException,TooManyRedirectsException}` and `GuzzleHttp\RequestOptions`. So "not PSR-18-based, Guzzle-coupled" is true.
  
  (B) REFUTATION 1 — "подменить HTTP-клиент без хака невозможно" is a false absolute; a fully public escape hatch exists. Chain verified in source: `AmoCRMOAuth::getOAuthProvider(): AmoCRM` is public; `League\OAuth2\Client\Provider\AbstractProvider::setHttpClient(HttpClientInterface $client)` is public with `use GuzzleHttp\ClientInterface as HttpClientInterface;`; `AmoCRMOAuth::getHttpClient()` is a pure delegate — body is `return $this->oauthProvider->getHttpClient();`; and `AmoCRMApiClient::getRequest()` builds the request LAZILY (`return $this->buildRequest();`), where `AmoCRMApiRequest::__construct` does `$this->httpClient = $oAuthClient->getHttpClient();`. Therefore `$api->getOAuthClient()->getOAuthProvider()->setHttpClient(new GuzzleHttp\Client(['handler' => $stack]))` works through 100% public API and propagates to every subsequent request. That defeats 2 of the 3 cases the claim enumerates as impossible: mock/record-replay transport (Guzzle `MockHandler`; php-vcr's Guzzle handler) and "клиент с middleware-ретраями" (`HandlerStack` + `Middleware::retry()`) are native Guzzle capabilities. Only the Symfony HttpClient case genuinely fails (Symfony `Psr18Client` does not implement `GuzzleHttp\ClientInterface`). Correct formulation: transport is swappable only *within the Guzzle family*, via a league/oauth2-client setter that amoCRM never documents — a real design smell, but not "impossible without a hack".
  
  (C) REFUTATION 2 — the comparative "это прямая противоположность паттерну Stripe/Saloon/Sentry/KnpLa
  *Контр-источник:* https://raw.githubusercontent.com/thephpleague/oauth2-client/master/src/Provider/AbstractProvider.php (public setHttpClient); https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/src/AmoCRM/OAuth/AmoCRMOAuth.php (public getOAuthProvider, getHttpClient delegates); https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/src/AmoCRM/Client/AmoCRMApiClient.php (lazy buildRequest); http
- **Голос 3 — опроверг** (уверенность: high):
  Factual half VERIFIED, load-bearing conclusion REFUTED empirically + comparative framing REFUTED.
  
  (1) The quote is accurate. `composer require amocrm/amocrm-api-library` (installed v1.17.0, lock verified) shows require = php, ext-fileinfo, ext-json, amocrm/oauth2-amocrm ^3.0, fig/http-message-util, guzzlehttp/guzzle "6.*||7.*", lcobucci/clock, lcobucci/jwt, nesbot/carbon, ramsey/uuid, symfony/dotenv. No psr/http-client, no psr/http-factory, no php-http/httplug. (Note: psr/http-client and psr/http-factory ARE in vendor/ transitively via guzzle 7.15.1, so PSR-18 is available at runtime — the library just never types against it.)
  
  (2) "Подменить HTTP-клиент ... без хака невозможно" is FALSE — disproved by executing code. A public-API-only seam exists: `AmoCRMOAuth::getOAuthProvider(): AmoCRM\OAuth2\Client\Provider\AmoCRM`, which `extends League\OAuth2\Client\Provider\AbstractProvider` (verified: vendor/amocrm/oauth2-amocrm/src/AmoCRM.php:10), and AbstractProvider has `public function setHttpClient(GuzzleHttp\ClientInterface $client)`. `AmoCRMOAuth::getHttpClient()` (line 228-230) delegates: `return $this->oauthProvider->getHttpClient();`. I ran a probe (/tmp/claude-1000/-home-mttzzzz-projects-octane-pushka-biz/8f833150-aac0-4f40-8de6-2e51685b2051/scratchpad/amoprobe/probe.php) doing `$apiClient->getOAuthClient()->getOAuthProvider()->setHttpClient(new GuzzleClient(['handler' => $stackWithMockHandlerAndRetryMiddleware]))` — no reflection, no subclassing, no private access. Output: `transport identity swapped: true` / `leads()->get() served from MOCK: MOCKED (id=777)` / `retry middleware fired: 1 time(s)`. So 2 of the 3 use cases the claim enumerates as impossible — mock/record-replay transport for tests, and a client with middleware retries — work through public API. Only the Symfony-HttpClient case is genuinely blocked: the param type is `GuzzleHttp\ClientInterface`, and `is_a(GuzzleHttp\ClientInterface, Psr\Http\Client\ClientInterface, true) === false`, so you need a ~40-line adapter — an adapter, not a "hack". The defensible version of the claim is narrower: the seam is *accidental* (inherited from league/oauth2-client, not exposed on AmoCRMOAuth itself, 
  *Контр-источник:* Executed probe against installed amocrm/amocrm-api-library 1.17.0 (public-API transport swap + Guzzle retry middleware succeeded); vendor/amocrm/oauth2-amocrm/src/AmoCRM.php:10 (`class AmoCRM extends AbstractProvider`); https://raw.githubusercontent.com/thephpleague/oauth2-client/master/src/Provider/AbstractProvider.php (`public function setHttpClient(HttpClientInterface $client)`); https://raw.gi

### 3. Таксономия ошибок построена ровно наоборот тому, что требует «громкий неизвестный тип»: выделенные классы получают только 401 (AmoCRMoAuthApiException), 204 (AmoCRMApiNoContentException) и 400 с непустым `validation-errors` (AmoCRMApiErrorResponseException). ВСЁ остальное — 402, 403, 404, 409, 429, 500, 502, 503 и любой будущий код — схлопывается в **базовый** класс `AmoCRMApiException` с сообщением "Invalid http status". Т.е. catch-all — это корень иерархии, а не отдельный Unknown-тип: `catch (AmoCRMApiException)` ловит одновременно и известное, и неизвестное, различить их можно только руками по `getErrorCode()`. Никакой классификации transient vs permanent в библиотеке нет.

*Источник:* https://github.com/amocrm/amocrm-api-php (primary)
*Цитата:* "if (!in_array((int)$response->getStatusCode(), self::SUCCESS_STATUSES, true)) {
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  Verified against the primary source at current HEAD (cloned amocrm/amocrm-api-php @ a0072fec, 2026-07-01, LIBRARY_VERSION = '1.17.0'). The claim is ~80% correct but contains a materially false enumerated item, and the supporting quote does not cover the code path that contradicts it.
  
  WHAT HOLDS (confirmed verbatim):
  1. src/AmoCRM/Client/AmoCRMApiRequest.php:580-632 `checkHttpStatus()` — exactly three status-keyed branches: 401 -> AmoCRMoAuthApiException (l.592), 204 -> AmoCRMApiNoContentException (l.601), and `!in_array(status, SUCCESS_STATUSES)` -> `new AmoCRMApiException("Invalid http status", ...)` (l.609-615), upgraded to AmoCRMApiErrorResponseException only when `status === 400 && !empty($decodedBody['validation-errors'])` (l.617-628). SUCCESS_STATUSES = 200/201/202/204 (l.43-46).
  2. Guzzle `http_errors => false` on every verb (l.211, 299, 386, 460, 539), so 402/403/404/409/5xx never become GuzzleException — they really do land on the base-class branch.
  3. Catch-all IS the root: `class AmoCRMApiException extends Exception` (Exceptions/AmoCRMApiException.php:13) and ALL 18 other exception classes extend it (ErrorResponse, oAuth, NoContent, HttpClient, ConnectException, TooManyRedirects, PageNotAvailable, BadType, InvalidArgument, NotAvailableForAction, ...). So `catch (AmoCRMApiException)` catches known and unknown alike; discrimination is manual via `getErrorCode(): int` (AmoCRMApiException.php:62).
  4. "No transient vs permanent classification" — confirmed: `grep -rni "middleware|retry|backoff|exponential" src/` returns ZERO hits across the whole library. Only implicit exception: 401 triggers one token-refresh-and-replay (l.242-260 etc.), and OAuth 404/401 on refresh triggers a domain re-resolve retry (OAuth/AmoCRMOAuth.php:172-200) — auth-specific, not a taxonomy.
  
  WHAT IS CONTRADICTED (the refutation):
  The claim explicitly lists 429 among the codes that "схлопывается в базовый класс AmoCRMApiException". False: the library ships a dedicated `AmoCRMApiTooManyRequestsException` (src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php, docblock: "Выбрасывается в случае ответа от сервера с кодом 429", extends AmoCRMApiHttpClientException
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiRequest.php#L649-L667 (parseResponse: 429 -> AmoCRMApiTooManyRequestsException) and https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php
- **Голос 2 — опроверг** (уверенность: high):
  Проверено по первоисточнику: shallow-clone amocrm/amocrm-api-php@master (pushed 2026-07-01), файлы src/AmoCRM/Client/AmoCRMApiRequest.php и src/AmoCRM/Exceptions/*.
  
  ЧТО ПОДТВЕРЖДАЕТСЯ (ядро claim'а верно):
  - checkHttpStatus() (строки 580-632) действительно спец-классифицирует ровно три случая: 401 -> AmoCRMoAuthApiException, 204 -> AmoCRMApiNoContentException, 400 && !empty($decodedBody['validation-errors']) -> AmoCRMApiErrorResponseException; всё прочее -> new AmoCRMApiException("Invalid http status", $response->getStatusCode(), ...).
  - Иерархия действительно «корень = catch-all»: AmoCRMApiException extends Exception, а AmoCRMoAuthApiException / AmoCRMApiNoContentException / AmoCRMApiHttpClientException / AmoCRMApiConnectExceptionException / AmoCRMApiPageNotAvailableException — все extends AmoCRMApiException. Различать код можно только вручную через getErrorCode().
  - http_errors => false выставлен во всех методах (post/put/patch/delete/get, RequestOptions::HTTP_ERRORS), т.е. Guzzle не бросает на 4xx/5xx и все статусы реально доезжают до checkHttpStatus — обходного пути нет.
  - grep по src на retry|backoff|sleep|usleep: 0 совпадений. Общей классификации transient/permanent и ретраев нет (единственное исключение — one-shot refresh токена: catch (AmoCRMoAuthApiException) -> refreshAccessToken() -> повтор запроса, AmoCRMApiRequest.php:244-252, т.е. 401 де-факто трактуется как recoverable).
  
  ЧТО ОПРОВЕРГАЕТСЯ (claim как сформулирован — фактически неверен):
  1) 429 НЕ схлопывается в базовый класс. В библиотеке есть выделенный класс src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php (extends AmoCRMApiHttpClientException), и он бросается в parseResponse() (AmoCRMApiRequest.php:649-660):
     if ($response->getStatusCode() !== STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents)) { if ($response->getStatusCode() === STATUS_TOO_MANY_REQUESTS) { throw new AmoCRMApiTooManyRequestsException("Too many requests", ...); } throw new AmoCRMApiException("Response body is not json", ...); }
     Именно этот путь и срабатывает на реальном rate-limit амо (nginx отдаёт не-JSON тело) — т.е. на практике rate-limit даёт типизированное исключение, а не base
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiRequest.php (parseResponse, L642-L672) и https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php (+ листинг src/AmoCRM/Exceptions/ — 19 классов; PageMethodsTrait.php L32,L55)
- **Голос 3 — опроверг** (уверенность: high):
  Проверял по первоисточнику: clone amocrm/amocrm-api-php @ a0072fec (tag 1.17.0, 2026-07-01), файл src/AmoCRM/Client/AmoCRMApiRequest.php и весь src/AmoCRM/Exceptions/.
  
  ЧТО В КЛЕЙМЕ ВЕРНО (подтверждено primary source):
  1. `checkHttpStatus()` (строки 580-632) действительно выделяет только 401 → AmoCRMoAuthApiException (592-599), 204 → AmoCRMApiNoContentException (601-607), 400+непустой `validation-errors` → AmoCRMApiErrorResponseException (617-628); всё остальное — `new AmoCRMApiException("Invalid http status", ...)` (609-615).
  2. `AmoCRMApiException extends Exception` — это КОРЕНЬ иерархии, все спец-классы наследуются от него (AmoCRMoAuthApiException, AmoCRMApiNoContentException, AmoCRMApiHttpClientException — все `extends AmoCRMApiException`). Т.е. catch-all и база — один класс, отдельного Unknown-типа нет. Тезис верен.
  3. Transient/permanent классификации нет: `grep -rni "retry|backoff|Retry-After|transient" src/` по всей библиотеке — 0 совпадений. Нет ни ретраев, ни разбора Retry-After.
  
  ЧТО В КЛЕЙМЕ ФАКТИЧЕСКИ НЕВЕРНО (за это refuted):
  1. **429 прямо перечислен как схлопывающийся в базовый класс — это опровергается кодом.** В `parseResponse()`, AmoCRMApiRequest.php:654-660, до вызова checkHttpStatus: `if ($response->getStatusCode() === StatusCodeInterface::STATUS_TOO_MANY_REQUESTS) { throw new AmoCRMApiTooManyRequestsException("Too many requests", ...); }`. Класс существует: src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php — `class AmoCRMApiTooManyRequestsException extends AmoCRMApiHttpClientException` с докблоком «Выбрасывается в случае ответа от сервера с кодом 429». Нюанс (в пользу клейма, но клейм его не делает): ветка срабатывает только при `!$decodedBody && !empty($bodyContents)` — т.е. когда тело 429 не-JSON (типичный nginx-HTML). При пустом теле или JSON-теле 429 действительно упадёт в базовый класс. Но утверждение «429 схлопывается в базовый» как факт — ложно.
  2. **«Выделенные классы получают только 401/204/400» — неполно.** В transport-слое есть ещё три выделенных типа, бросаемых из каждого метода запроса (строки 228-232, 316-320, 391-395, 465-469, 544-548): ConnectException → AmoCRMApiConnectExceptionException, TooMany
  *Контр-источник:* Primary source, clone amocrm/amocrm-api-php @ a0072fec (v1.17.0): src/AmoCRM/Client/AmoCRMApiRequest.php:649-667 (429 → AmoCRMApiTooManyRequestsException), :228-232/:316-320/:391-395/:465-469/:544-548 (Connect/TooManyRedirects/HttpClient exceptions), :584-590 (customCheckHttpStatusCallback), src/AmoCRM/Exceptions/AmoCRMApiTooManyRequestsException.php, src/AmoCRM/EntitiesServices/Traits/PageMethods

### 4. amoCRM enforces two independent, simultaneous rate limits: 7 requests/sec bound to the pair (IP address + account subdomain), and 50 requests/sec per account shared across ALL integrations on that account. Because the 50 rps budget is consumed by third-party integrations too, a client-side token bucket can never guarantee compliance — the client must be designed to absorb 429 as a normal, expected outcome rather than an exceptional one.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/318 (primary)
*Цитата:* "Ограничение 7 запросов на IP адрес сохраняется, но вводится лимит 50 запросов в секунду на все интеграции"
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  The supporting quote is verbatim real, but the claim overreaches on scoping, universality, and its design conclusion — and its key scoping detail is contradicted by amoCRM's CURRENT official docs.
  
  1) SCOPING CONTRADICTED BY CURRENT PRIMARY SOURCE. I fetched https://www.amocrm.ru/developers/content/api/recommendations live (2026-07-22). Verbatim: «При работе с API действуют ограничения на частоту запросов: не более 7 запросов в секунду НА ОДНУ ИНТЕГРАЦИЮ и до 50 запросов в секунду на весь аккаунт». Official doc today says the 7 rps is scoped PER INTEGRATION — not to the (IP + subdomain) pair the claim asserts. This matters materially: a per-integration limit IS fully enforceable by a client-side token bucket, which undercuts the claim's core inference.
  
  2) SOURCE IS 5 YEARS OLD AND DESCRIBES AN UNSHIPPED ROLLOUT. `gh api repos/amocrm/amocrm-api-php/issues/318` — issue opened 2021-05-22, still state=open. The "ip + субдомен" scoping comes from ONE comment by @bessudnov dated 2021-05-30: «да, 7 запросов на связь ip + субдомен аккаунта». In the same thread he qualifies it as forward-looking and plan-gated: «Общий лимит ... будет действовать только на новых тарифах, которые будут доступны с 1 июня 2021 года» and «1 июня точно никто с этими ограничениями не столкнется». A 2021 pre-rollout forum comment is a weak basis for a confident 2026 architectural constraint.
  
  3) THE 50 rps FIGURE IS NOT ABSOLUTE. Same live page: «Лимиты на количество запросов для аккаунта можно увеличить, подключив один из дополнительных пакетов» — 50 is a default tier value, purchasable upward. The claim states it as a fixed universal budget. Also @bessudnov (2021-05-30): «менее 5% аккаунтов превышают в моменте лимит 50 запросов в секунду» — for ~95% of accounts the shared-budget contention premise does not bind.
  
  4) THE "THIRD PARTIES EAT THE BUDGET" PREMISE IS COMMUNITY SPECULATION, NOT VENDOR CONFIRMATION. In #318 that framing is a QUESTION from @eugene-borovov («То есть это означает, что даже сторонние интеграции будут "выедать" лимит?») echoed by @makeroi («Звучит именно так»). amo staff never confirmed it; bessudnov deflected to «эти ограничения затронут большие аккаунты,
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (live 2026-07-22: «не более 7 запросов в секунду на одну интеграцию и до 50 запросов в секунду на весь аккаунт»; «при многократном нарушении ограничений, аккаунт блокируется ... HTTP код 403»; «Лимиты ... можно увеличить, подключив один из дополнительных пакетов»); https://web.archive.org/web/2022id_/https://www.amocrm.ru/developers/cont
- **Голос 2 — опроверг** (уверенность: high):
  The quote is GENUINE but the claim built on it is OUTDATED and CONTRADICTED by the current primary source on its central mechanism.
  
  1) QUOTE VERIFIED (gh api repos/amocrm/amocrm-api-php/issues/318/comments): user `bessudnov` (amoCRM staff), 2021-05-23T10:41:29Z: "Ограничение 7 запросов на IP адрес сохраняется, но вводится лимит 50 запросов в секунду на все интеграции". Even stronger, 2021-05-30T20:48:07Z, answering whether the 7 rps is per-account: "да, 7 запросов на связь ip + субдомен аккаунта". So the (IP + subdomain) keying was literally stated. Issue opened 2021-05-22, still OPEN in 2026.
  
  2) CONTRADICTED BY CURRENT OFFICIAL DOCS. Raw fetch of https://www.amocrm.ru/developers/content/api/recommendations today (2026-07-22), verbatim: "При работе с API действуют ограничения на частоту запросов: не более 7 запросов в секунду **на одну интеграцию** и до 50 запросов в секунду на весь аккаунт." Per INTEGRATION — not per (IP + subdomain). These are architecturally opposite for a client design: per-integration means each integration has its own 7 rps and scaling across pods/IPs does NOT multiply budget; IP-keyed means it does. A library's rate-limiter key depends entirely on which is true.
  
  3) CORROBORATION THAT PER-INTEGRATION IS CURRENT: amoCRM's paid extended-limit packages are sold as per-integration tiers (sensei.plus/extended-api-limits): "До 7 запросов в секунду для одной интеграции / до 50 на весь аккаунт", packages 100/15, 150/30, 200/50 (account rps / per-integration rps). An IP-keyed limit could not be sold as a per-integration tier. No current source mentions IP keying at all.
  
  4) THE 2021 STATEMENT WAS EXPLICITLY IN-FLUX. Same thread, bessudnov 2021-05-30: "Общий лимит ... будет действовать только на новых тарифах"; "Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется". Also, the docs quoted IN that thread at the time said only bare "не более 7 запросов в секунду" — the doc wording has since been rewritten to "на одну интеграцию", i.e. amo restated the mechanism.
  
  5) THE PRESCRIPTIVE CONCLUSION OVERREACHES. (a) "never guarantee compliance" — under the currently documented per-integr
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (official, fetched 2026-07-22, verbatim: "не более 7 запросов в секунду на одну интеграцию и до 50 запросов в секунду на весь аккаунт"); corroborated by https://sensei.plus/extended-api-limits (per-integration package tiers 15/30/50 rps). Original quote confirmed at https://github.com/amocrm/amocrm-api-php/issues/318 comments by bessudno
- **Голос 3 — опроверг** (уверенность: high):
  REFUTED on the factual topology, though a weaker version survives.
  
  1) CONTRADICTED BY THE VENDOR'S CURRENT DOCS — on the exact page the research brief itself cites. I bypassed the summarizer and pulled raw HTML from https://www.amocrm.ru/developers/content/api/recommendations. Verbatim: «При работе с API действуют ограничения на частоту запросов: не более 7 запросов в секунду НА ОДНУ ИНТЕГРАЦИЮ и до 50 запросов в секунду на весь аккаунт.» The 7 rps limit is documented as PER INTEGRATION, not "bound to the pair (IP address + account subdomain)". The claim's central architectural assertion is stated nowhere in current amoCRM documentation; the word "IP" does not appear in the Ограничения section at all (it appears only in the 403-blocking consequence elsewhere). The English Kommo docs (developers.kommo.com/docs/limitations, updated ~March 2026) likewise state only "not more than 7 requests per second" and do not scope it to IP.
  
  2) THE LOAD-BEARING SOURCE IS 5 YEARS OLD, PRE-ROLLOUT, AND NEVER RATIFIED. I pulled issue #318 via `gh api`. The quote is NOT in the issue body (body is by user `makeroi`, 2021-05-22, and says only «С 1 июня амо переходит на ограничение по апи в 50 запросов в секунду на аккаунт»). The supporting quote is a mid-thread comment by amoCRM staffer `bessudnov`, 2021-05-23. The "ip + subdomain" wording is in yet a THIRD comment (bessudnov, 2021-05-30): «да, 7 запросов на связь ip + субдомен аккаунта». That same commenter framed it as an unshipped, gated rollout: «Общий лимит ... будет действовать только на новых тарифах» and «Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется». The issue is STILL OPEN (state: open) and was never converted into documentation. Treating a 2021 pre-launch forum answer as current architecture, against 2026 docs that say otherwise, is exactly the outdated-source failure mode.
  
  3) "TWO INDEPENDENT LIMITS" OF 7 AND 50 ARE NOT INVARIANTS — THEY ARE PURCHASABLE DEFAULTS. Official docs: «Лимиты на количество запросов для аккаунта можно увеличить, подключив один из дополнительных пакетов.» The package tiers are: 100 rps account / 15 rps per integration; 150/30;
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (current official amoCRM docs, fetched raw HTML 2026-07-22) + https://sensei.plus/extended-api-limits (paid limit packages) + https://developers.kommo.com/docs/limitations (English docs, updated ~4 months ago) + full comment thread of https://github.com/amocrm/amocrm-api-php/issues/318

### 5. When an account's API gets blocked for excessive requests, amoCRM stops returning JSON and serves an HTML page in place of an API response (unblockable only via support), and the official library does not handle this case at all — a reporter who is himself a repo CONTRIBUTOR states it explicitly. This is direct evidence that the official client's error taxonomy has an unhandled non-JSON branch, i.e. exactly the 'unknown error type gets swallowed / crashes the parser' failure mode a new client must trap loudly.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/318 (primary)
*Цитата:* "Со временем апи аккаунта вообще блокировалось, вместо него отдавалась html страница (кстати, библиотека это вообще не обрабатывает), с возможностью разблокировать только через поддержку."
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  The quote is REAL but the inference drawn from it is contradicted by primary code evidence, and it is 5 years stale.
  
  WHAT CHECKS OUT: Issue #318 (created 2021-05-22, still open, labels "question" + "API check needed") was opened by @makeroi, who IS in the repo's contributor list (GitHub contributors API for amocrm/amocrm-api-php lists "makeroi", 15 contributions). The Russian sentence is verbatim from the issue body.
  
  WHAT IS REFUTED — the claim's operative inference ("unhandled non-JSON branch = unknown error gets swallowed / crashes the parser"):
  1. Current master `src/AmoCRM/Client/AmoCRMApiRequest.php`, `parseResponse()` lines 642-667, is EXACTLY a non-JSON branch that throws loudly:
     `$decodedBody = json_decode($bodyContents, true);` then `if ($response->getStatusCode() !== STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents)) { if (statusCode === STATUS_TOO_MANY_REQUESTS) throw new AmoCRMApiTooManyRequestsException(...); throw new AmoCRMApiException("Response body is not json", $response->getStatusCode(), $this->getLastRequestInfo()); }`
     Nothing is swallowed and the parser does not crash — an HTML body produces a typed exception carrying status code + request info. The 429-with-HTML-body case (precisely "blocked for excessive requests") even has its own exception class.
  2. This handling PREDATES the report: issue #96 (created 2020-07-30, ten months before #318) is titled "На многие запросы Error: Response body is not json" and its debug output shows a 404 with HTML documentation content instead of JSON — i.e. the library was already throwing on an HTML response when makeroi wrote "библиотека это вообще не обрабатывает". His phrase means "no meaningful/dedicated handling of the blocked-account case", not "unhandled branch".
  3. Outdated + already actioned: in the same thread amoCRM maintainer @bessudnov (2021-05-23) replied "В библиотеке в ближайшее время добавим обработку ошибок, которые идут при превышении, скорее всего это будет возможность повторения запроса и отдельный тип Exception" — and `AmoCRMApiTooManyRequestsException` now exists in master. The claim states a 2021 gap as present-tense fact (today 2026-07).
  4. Source 
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiRequest.php (parseResponse lines 642-667 throw AmoCRMApiException "Response body is not json" / AmoCRMApiTooManyRequestsException on non-JSON bodies; checkHttpStatus lines 580-631); https://github.com/amocrm/amocrm-api-php/issues/96 (2020-07-30 — library already threw "Response body is not json" on an HTML response, 10
- **Голос 2 — опроверг** (уверенность: high):
  REFUTED on its load-bearing assertion. The quote is verbatim-accurate and the author's status checks out (makeroi, author_association=CONTRIBUTOR, 15 real commits, listed in /contributors) — but the substantive claim "the official library does not handle this case at all" is FALSE, and was already false when he wrote it.
  
  1) CONTRADICTED BY THE PRIMARY ARTIFACT. I cloned the repo and read `AmoCRMApiRequest::parseResponse()`. It has an explicit non-JSON branch:
     `$decodedBody = json_decode($bodyContents, true);`
     `if ($response->getStatusCode() !== STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents)) { ... throw new AmoCRMApiException("Response body is not json", $response->getStatusCode(), ...); }`
     An HTML page served in place of JSON hits exactly this branch and throws a typed exception. It is neither swallowed nor a parser crash.
  
  2) THE HANDLING PREDATES THE ISSUE BY 13 MONTHS. `git log -S'Response body is not json'` → first introduced b2cb811, 2020-04-29, shipped in tag 0.1. I checked out 652e4cc (2021-05-14), the master commit in force when issue #318 was filed on 2021-05-22, and the throw is already there at lines 493-502. So the reporter's parenthetical "(кстати, библиотека это вообще не обрабатывает)" was factually wrong at the moment of writing. He was recounting a past incident ("Мы сталкивались… Со временем апи аккаунта вообще блокировалось") with no version, no repro, no stack trace, plausibly from amoCRM's older v2 API or his own HTTP code rather than this library.
  
  3) ZERO MAINTAINER CORROBORATION. Maintainer @bessudnov (COLLABORATOR) posted three times in the thread and answered the rate-limit questions in detail (7 rps per ip+subdomain, 50 rps per account, promise of a dedicated exception + retry). He never acknowledged the HTML assertion. Nobody in the thread confirms it. It is an unverified offhand aside, not a finding.
  
  4) OUTDATED (5 years). Since then the taxonomy grew to 20 exception classes, and `AmoCRMApiTooManyRequestsException` (added 2023-07-10) was inserted *inside* that very non-JSON branch specifically to type 429-with-non-JSON-body. Using a 2021 aside to characterize the 2026 library is anachronis
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/blob/master/src/AmoCRM/Client/AmoCRMApiRequest.php (parseResponse, non-JSON branch introduced in commit b2cb811, 2020-04-29, present in tag 0.1); state of the file at commit 652e4cc, 2021-05-14 — i.e. master on the day issue #318 was filed; issue thread https://github.com/amocrm/amocrm-api-php/issues/318 comments by maintainer @bessudnov (COLLABORATOR); htt
- **Голос 3 — опроверг** (уверенность: high):
  The quote is verbatim-authentic, but the claim's load-bearing assertion ("the official library does not handle this case at all... unhandled non-JSON branch... swallowed/crashes the parser") is DIRECTLY CONTRADICTED BY THE LIBRARY'S SOURCE CODE, both today and at the moment the quote was written.
  
  1) CODE AT HEAD REFUTES IT. Cloned amocrm/amocrm-api-php at a0072fec (tag 1.17.0, 2026-07-01). src/AmoCRM/Client/AmoCRMApiRequest.php::parseResponse() lines 642-672 contains an explicit non-JSON branch:
      $decodedBody = json_decode($bodyContents, true);
      if ($response->getStatusCode() !== STATUS_ACCEPTED && !$decodedBody && !empty($bodyContents)) {
          if ($response->getStatusCode() === STATUS_TOO_MANY_REQUESTS) { throw new AmoCRMApiTooManyRequestsException(...); }
          throw new AmoCRMApiException("Response body is not json", $response->getStatusCode(), $this->getLastRequestInfo());
      }
  An HTML block page never json_decodes, so it hits this branch and throws with the status code and full request info preserved. It is neither swallowed nor a parser crash.
  
  2) IT WAS ALREADY HANDLED WHEN THE QUOTE WAS WRITTEN — the claim was wrong on day one, not merely outdated. `git log -S "Response body is not json"` returns exactly ONE commit: b2cb811, 2020-04-29, "correct exceptions for api requester fixes" — added once, never removed. Checking out 652e4cc (2021-05-14, the last commit before the issue was filed on 2021-05-22) shows the identical `throw new AmoCRMApiException("Response body is not json", ...)` at line 498. The reporter's parenthetical "библиотека это вообще не обрабатывает" was factually incorrect at the time of posting.
  
  3) THE APPEAL TO AUTHORITY IS AN API ARTIFACT. `author_association: CONTRIBUTOR` on the GitHub API is computed at read time, not post time. makeroi's earliest commit in the repo is 2021-08-24 (0a6a3821) — three months AFTER the 2021-05-22 issue. He was NOT a contributor when he wrote the line; the badge is retroactive. His claim is an offhand parenthetical inside an issue whose actual subject is rate limits, unverified against code.
  
  4) NO MAINTAINER EVER CORROBORATED IT. Maintainer bessudnov (COLLABORATOR)
  *Контр-источник:* Primary source code: amocrm/amocrm-api-php @ a0072fec (v1.17.0, 2026-07-01), src/AmoCRM/Client/AmoCRMApiRequest.php lines 642-672 (parseResponse) and 580-632 (checkHttpStatus); git log -S "Response body is not json" -> b2cb811 (2020-04-29, sole introducing commit); snapshot 652e4cc (2021-05-14) line 498 showing the branch already present 8 days before issue #318 was filed; GitHub API repos/amocrm/

### 6. andrey-tech/amocrm-api-php implements client-side rate limiting as a hard-coded sleep-based throttle of 7 requests/second (static `AmoAPI::$throttle`, default 7), pausing the process until 1/throttle seconds have elapsed since the previous request — a purely in-process limiter with no distributed/cross-process coordination, which breaks down under Laravel queue workers running in parallel.

*Источник:* https://github.com/andrey-tech/amocrm-api-php (primary)
*Цитата:* "не более 7 запросов в секунду"
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  Read the primary source directly (git clone master, HEAD f3b2a23 dated 2026-03-17, i.e. the v3.1.0 line; composer.json requires php>=8.3, symfony/lock>=6.4, symfony/http-client>=6.4).
  
  WHAT CHECKS OUT:
  - `src/AmoCRM/AmoAPIRequest.php:47` — `public static float $throttle = 7;` (declared in trait `AmoAPIRequest`, consumed by class `AmoAPI`, so `AmoAPI::$throttle` is the correct accessor). Default 7: confirmed.
  - `AmoAPIRequest.php:524-544` — sleep-based: `$usleep = (int)(1E6 * ($lastTime + 1.0/self::$throttle - microtime(true))); if ($usleep > 0) { … usleep($usleep); }`, then `self::$lastRequestTime[$domain] = microtime(true);`. "Pause until 1/throttle seconds since the previous request": confirmed.
  
  WHAT IS WRONG — two material errors:
  
  (1) "hard-coded" is false. `$throttle` is a public static float, documented in the README config table and settable at runtime (`AmoAPI::$throttle = …`); the code even has an explicit disable path at line 527: `if (self::$throttle <= 0.0) { … return; }` ("Если троттлинг отключён (throttle <= 0), пропускаем без деления на ноль"). Nothing is hard-coded — 7 is a default.
  
  (2) "purely in-process limiter with no distributed/cross-process coordination" is contradicted by current code. Every API call goes through `sendRequest()` (AmoAPIRequest.php:458-520), which wraps the request in a Symfony Lock with BLOCKING acquire:
    `$domainLock = self::getLockFactory()->createLock('domain.' . $domain, ttl: null, autoRelease: true); $domainLock->acquire(true);` … `finally { $domainLock->release(); }`
    The lock store is pluggable: `public static ?PersistingStoreInterface $lockStore = null;` (line 98), default `FlockStore` (line 599 — file locks, already cross-PROCESS on one host), and the README §"Блокировка одновременного обновления одной сущности" (lines 764-805) explicitly documents `RedisStore` / `MemcachedStore` / `PdoStore` / `ZookeeperStore` for the case "где несколько серверов обращаются к одному аккаунту amoCRM, файловые блокировки не обеспечивают [координацию]". The v3.1.0 release (2026-03-17) is precisely "support for custom lock storage". So distributed/cross-process coordination exists and is a shipped, documen
  *Контр-источник:* Primary source, andrey-tech/amocrm-api-php master @ f3b2a23 (2026-03-17): src/AmoCRM/AmoAPIRequest.php lines 47, 98, 204, 458-520 (`createLock('domain.'.$domain)` + `acquire(true)`), 524-544 (usleep throttle, `<=0` disable), 591-604 (`getLockFactory()`, FlockStore/custom store); README.md lines 425-426 (config table for `$lockStore`), 751-805 (RedisStore/MemcachedStore/PdoStore multi-server sectio
- **Голос 2 — опроверг** (уверенность: high):
  Verified against the PRIMARY source (fetched current master via `gh api repos/andrey-tech/amocrm-api-php`, default branch `master`, last push 2026-03-17, not a fork). Two material parts of the claim are false for the library as it exists today.
  
  (1) "hard-coded" is wrong. `src/AmoCRM/AmoAPIRequest.php:47` — `public static float $throttle = 7;` is a public, documented, user-settable knob. README config table line 415: "`$throttle` | 7 | **Устанавливает** максимальное число запросов к API amoCRM в секунду". The code even has an explicit disable path: `if (self::$throttle <= 0.0) { self::$lastRequestTime[$domain] = microtime(true); return; }`. A configurable public static with a default of 7 is not a "hard-coded throttle of 7 requests/second".
  
  (2) "purely in-process limiter with no distributed/cross-process coordination" is contradicted by current master. Every API call now runs inside a per-domain symfony/lock critical section, acquired BLOCKING, with the throttle nested inside it (AmoAPIRequest.php ~465-477):
      $domainLock = self::getLockFactory()->createLock('domain.' . $domain, ttl: null, autoRelease: true);
      $domainLock->acquire(true); // блокирующее ожидание
      try { self::throttle($domain); ...
  `getLockFactory()` (l.591-605) defaults to `new FlockStore($dir)` — file locks, i.e. genuinely cross-PROCESS on one host — and is pluggable: `public static ?PersistingStoreInterface $lockStore = null;` with the doc-comment "Позволяет подключить любой совместимый стор: RedisStore, MemcachedStore и др. Пример: AmoAPI::$lockStore = new RedisStore($redis);" (README line 426 documents the same). `composer.json` requires `"symfony/lock": ">=6.4"`, `"php": ">=8.3"`. The `$lockStore` support landed 2026-03-15 (commit "Добавить поддержку пользовательского хранилища блокировок ($lockStore)"). So under Laravel queue workers the library does NOT simply "break down" — it serializes per-amo-domain across workers, and with a RedisStore across hosts.
  
  (3) The cited supporting quote — "не более 7 запросов в секунду" — is merely the anchor text of a link to amoCRM's own recommendations page. It supports none of the architectural assertions (hard-coded / sleep
  *Контр-источник:* Primary source, current master of the claimed repo itself: https://github.com/andrey-tech/amocrm-api-php/blob/master/src/AmoCRM/AmoAPIRequest.php (lines 47, 86-98, 204, 465-477, 524-545, 587-605), https://github.com/andrey-tech/amocrm-api-php/blob/master/composer.json ("symfony/lock": ">=6.4", "php": ">=8.3"), README.md config table lines 415/425-428, and commit "Добавить поддержку пользовательско
- **Голос 3 — опроверг** (уверенность: high):
  MIXED — core mechanism CONFIRMED, but two load-bearing clauses are FALSE against the cited primary source (repo master = v3.1.0, 2026-03-17).
  
  WHAT IS CONFIRMED (src/AmoCRM/AmoAPIRequest.php @ master):
  - L47: `public static float $throttle = 7;` with docblock "Максимальное число запросов к amoCRM API в секунду / Не более 7 запросов в секунду!!!"
  - L524-544 `private static function throttle(string $domain)`: `$usleep = (int)(1E6 * ($lastTime + 1.0 / self::$throttle - microtime(true))); if ($usleep > 0) { ... usleep($usleep); } self::$lastRequestTime[$domain] = microtime(true);` — exactly "sleep-based, pause until 1/throttle sec since previous request".
  - `AmoAPI::$throttle` is valid: AmoAPIRequest is a **trait** (`trait AmoAPIRequest`, L31) consumed by `class AmoAPI` (`use AmoAPIRequest;`).
  - Pacing state `protected static array $lastRequestTime = []` (L204) is per-PHP-process memory, and README L811 itself scopes throttling to "**в рамках одного процесса или потока исполнения**".
  
  REFUTATION 1 — "hard-coded" is false. `$throttle` is a public, runtime-configurable static documented in the README parameter table (L415: "| `$throttle` | 7 | Устанавливает максимальное число запросов к API amoCRM в секунду |"), and throttling is explicitly disableable: L527 `if (self::$throttle <= 0.0) { self::$lastRequestTime[$domain] = microtime(true); return; }`. Default 7 ≠ hard-coded 7.
  
  REFUTATION 2 — "no distributed/cross-process coordination" is contradicted at the very same code site. `sendRequest()` (L465-473, 517) wraps the throttle + HTTP call in a Symfony Lock:
    `$domainLock = self::getLockFactory()->createLock('domain.' . $domain, ttl: null, autoRelease: true); $domainLock->acquire(true); try { self::throttle($domain); ...request... } finally { $domainLock->release(); }`
  with comment "Захватываем блокировку домена — только один запрос к домену одновременно". The store is pluggable: `public static ?PersistingStoreInterface $lockStore = null` (L98), defaulting to **FlockStore** (cross-process on one host, getLockFactory L591-607); README L766-799 explicitly documents RedisStore/MemcachedStore/PdoStore for the multi-server case ("где несколько серв
  *Контр-источник:* https://github.com/andrey-tech/amocrm-api-php/blob/master/src/AmoCRM/AmoAPIRequest.php (L47, L98, L204, L465-473, L517, L524-544, L591-607); https://github.com/andrey-tech/amocrm-api-php/blob/master/README.md (L415, L426, L751-811); https://github.com/andrey-tech/amocrm-api-php/blob/2.18.6/src/AmoCRM/AmoAPIRequest.php (L72, L222, L580-594); release history v3.0.0 2026-03-05 / v3.1.0 2026-03-17 via

### 7. The rate limits apply to every amoCRM API version, explicitly including v2 and the undocumented v1 browser/private API — the maintainer answers this in response to a direct question 'ограничение только на v4 апи? Как обстоят дела с v2 и с недокументируемым апи (v1)?'. Consequence for the design: a library that hosts both the public v4 channel and the undocumented ajax channel must share ONE rate-limit/throttle budget per account across both channels, not per-channel limiters.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/318 (primary)
*Цитата:* "Лимиты будут действовать на все версии API на новых тарифах. Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется."
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  The quote is GENUINE and the author is authoritative, but the claim overstates it and the design consequence is wrong.
  
  VERIFIED (via `gh api repos/amocrm/amocrm-api-php/issues/318`):
  - makeroi (2021-05-25T21:15:19Z) did ask verbatim: "И еще вопрос - ограничение только на v4 апи? Как обстоят дела с v2 и с недокументируемым апи (v1)?"
  - bessudnov (2021-05-30T20:47:36Z) replied quoting that comment: "Лимиты будут действовать на все версии API на новых тарифах. Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется."
  - bessudnov is legitimate: `gh api users/bessudnov` → bio "CTO (Past: Senior PHP, Teamlead and DevOps) at amoCRM", company amoCRM; author_association = COLLABORATOR.
  
  WHY REFUTED:
  
  (1) OVERREACH ON "EXPLICITLY". The CTO said only a blanket "все версии API" — he never enumerated v2, never named v1, never confirmed the private browser ajax channel. The claim also silently DROPS his qualifier "на новых тарифах" and the hedge "будем открывать постепенно... 1 июня точно никто не столкнется" — a forward-looking rollout plan, not an observed fact.
  
  (2) THE DECISIVE FOLLOW-UP WAS NEVER ANSWERED. In the same thread makeroi (2021-06-11T10:15:20Z) asked precisely the crux the claim depends on: "Я правильно понимаю, что есть затронут все версии апи, то одновременно не смогут в секунду делать запросы 50 десктопных клиентов? Или как работает это ограничение? То есть притворяться клиентом нет смысла?" — i.e. does the budget really cover the browser/desktop-client channel. amoCRM never replied. `gh api .../issues/318` → {state: "open", comments: 11, closed_at: null, updated_at: "2021-06-11T10:15:21Z"}: zero maintainer activity for 5 years. The source explicitly leaves the browser-channel case unresolved.
  
  (3) CONFLATION. The claim maps the questioner's "недокументируемое апи (v1)" onto "the undocumented v1 browser/private API" / private ajax channel. Unsupported — amoCRM's legacy public API was /private/api/v2/json/… (v2), while the browser surface is a separate /ajax/… channel authenticated by session cookie, not OAuth. Nothing in the issue establishes these are the same bucket.
  
  (4) OUTDATE
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (current official: "не более 7 запросов в секунду на одну интеграцию", "до 50 запросов в секунду на весь аккаунт", no mention of API versions); https://www.kommo.com/developers/content/api/recommendations/ (same vendor, states 7 rps per IP address — conflicting bucketing key); https://github.com/amocrm/amocrm-api-php/issues/318#issuecomm
- **Голос 2 — опроверг** (уверенность: high):
  VERIFIED PARTS: The quote is authentic. Fetched via `gh api repos/amocrm/amocrm-api-php/issues/318/comments`. bessudnov (amoCRM side), 2021-05-30T20:47:36Z, replying with makeroi's comment quoted inline — that comment did end with "И еще вопрос - ограничение только на v4 апи? Как обстоят дела с v2 и с недокументируемым апи (v1)?" So attribution and "answers a direct question" framing are accurate.
  
  REFUTATION 1 — the load-bearing design consequence is unsupported. "Лимиты будут действовать на все версии API" says the limits APPLY TO all versions; it says nothing about counters being POOLED into one bucket across channels. The claim's inference ("must share ONE rate-limit/throttle budget per account across both channels, not per-channel limiters") is the researcher's, not the maintainer's. Worse, the same thread contradicts a single-bucket model: bessudnov 2021-05-23 — "Ограничение 7 запросов на IP адрес сохраняется, но вводится лимит 50 запросов в секунду на все интеграции", clarified 2021-05-30T20:48:07Z — "да, 7 запросов на связь ip + субдомен аккаунта". That is TWO orthogonal buckets (per IP+subdomain, and per-account-across-all-integrations), not one. And the 50 rps account bucket is consumed by third-party integrations the library cannot see (eugene-borovov, 2021-05-26: "даже сторонние интеграции будут «выедать» лимит?" — confirmed), so a library-local shared budget cannot enforce it at all; only 429/403 reaction can.
  
  REFUTATION 2 — "explicitly including … the undocumented v1 browser/private API" is an overread. The answer is a generic one-liner ("все версии API"); it never names v1/v2/ajax. Decisively, the exact follow-up the claim depends on was asked and NEVER answered: makeroi, 2021-06-11T10:15:20Z — "Я правильно понимаю, что есть затронут все версии апи, то одновременно не смогут в секунду делать запросы 50 десктопных клиентов? … То есть притворяться клиентом нет смысла?" No maintainer reply; issue is still state=open. The browser channel authenticates by session cookie, a different identity from the OAuth integration, so folding it into the integration's bucket is an untested assumption.
  
  REFUTATION 3 — dropped qualifiers + staleness. The
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/issues/318 (same thread: bessudnov 2021-05-23 & 2021-05-30T20:48:07Z two-dimension limit model; makeroi 2021-06-11 follow-up about the browser client left unanswered, issue still open) + https://www.amocrm.ru/developers/content/api/recommendations (current: 7 rps per integration + 50 rps per account, no mention of API versions) + https://github.com/andrey-t
- **Голос 3 — опроверг** (уверенность: high):
  ATTRIBUTION VERIFIED, CLAIM STRENGTH REFUTED. Pulled the full thread via `gh api repos/amocrm/amocrm-api-php/issues/318/comments` (11 comments, all retrieved; issue state=open, closed_at=null).
  
  WHAT IS TRUE: makeroi (author_association=CONTRIBUTOR, 2021-05-25) did ask verbatim "И еще вопрос - ограничение только на v4 апи? Как обстоят дела с v2 и с недокументируемым апи (v1)?". bessudnov (author_association=COLLABORATOR, i.e. amoCRM-side, 2021-05-30T20:47:36Z) replied quoting that comment: "Лимиты будут действовать на все версии API на новых тарифах. Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется." So the quote and its attribution are real.
  
  WHY THE CLAIM STILL FAILS (5 independent grounds):
  
  (1) OVERREACH BEYOND THE QUOTE. The reply says only "все версии API" — a blanket phrase. It never names v1, v2, /private/api/, /ajax/, or a browser channel. The claim upgrades this to "explicitly including v2 and the undocumented v1 browser/private API". "Explicitly" is false — it is a generic one-liner answering a compound comment that also contained two unrelated questions (average rps per account, UI visibility of request counts).
  
  (2) THE SAME THREAD RECORDS THE QUESTION AS UNRESOLVED AND UNANSWERED. makeroi immediately re-asked (2021-06-11T10:15:20Z): "Я правильно понимаю, что есть затронут все версии апи, то одновременно не смогут в секунду делать запросы 50 десктопных клиентов? Или как работает это ограничение? То есть притворяться клиентом нет смысла?" — i.e. the asker himself did NOT read "все версии API" as settling whether the browser/desktop-client channel is metered. That is comment 11 of 11, the LAST in the thread; no collaborator ever replied; issue still open 5 years later. The primary source therefore documents the opposite of "the maintainer answers this".
  
  (3) STRIPPED QUALIFIERS. The quote is doubly hedged — "на новых тарифах" (new tariffs only) and a phased rollout ("1 июня точно никто не столкнётся"). The claim drops both, presenting a conditional 2021 rollout note as a standing architectural fact.
  
  (4) OUTDATED (May 2021 → July 2026) AND SCOPE HAS DEMONSTRABLY DRIFTED. In t
  *Контр-источник:* Primary source self-refutation: github.com/amocrm/amocrm-api-php/issues/318 — makeroi's 2021-06-11 follow-up re-asking whether desktop/browser clients count toward the limit is the final (11th) comment; issue remains open with no maintainer answer. Current official docs: https://www.amocrm.ru/developers/content/api/recommendations — "не более 7 запросов в секунду на одну интеграцию", "до 50 запрос

### 8. The official library requires a concrete HTTP client implementation (guzzlehttp/guzzle 6.* || 7.*) as a hard runtime dependency rather than depending on the PSR-18/PSR-17 abstractions (psr/http-client, psr/http-factory) with a virtual provider, so consumers cannot swap transport and inherit Guzzle's own version/compatibility constraints. This is the exact coupling that Saloon/Sentry/KnpLabs-style SDKs avoid via HTTPlug/PSR-18 discovery.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/578 (primary)
*Цитата:* ""guzzlehttp/guzzle": "6.* || 7.*","
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  The claim has two halves. The factual half survives; the cited source and the prescriptive comparison both fail.
  
  (1) SOURCE MISATTRIBUTION — the quote is cherry-picked from an unrelated issue. github.com/amocrm/amocrm-api-php/issues/578 is titled "Совместимость с php 8.3" and is about a composer resolution failure caused by `lcobucci/clock` constraints (~8.1.0 || ~8.2.0) blocking install on PHP 8.3. The line `"guzzlehttp/guzzle": "6.* || 7.*"` appears only incidentally inside a pasted composer.json/error dump. The issue does not discuss PSR-18, PSR-17, HTTPlug, transport abstraction, or client swapping anywhere. The cited "primary source" does not support the thesis it is attached to.
  
  (2) The underlying fact IS independently verifiable — from the real primary source, raw.githubusercontent.com/amocrm/amocrm-api-php/master/composer.json: require = {php, ext-fileinfo, ext-json, amocrm/oauth2-amocrm ^3.0, fig/http-message-util 1.*, guzzlehttp/guzzle "6.* || 7.*", lcobucci/clock, lcobucci/jwt, nesbot/carbon, ramsey/uuid, symfony/dotenv}. No psr/http-client, no psr/http-factory, no virtual *-implementation packages, no php-http/discovery. I also read src/AmoCRM/Client/AmoCRMApiClient.php: there is no setHttpClient()/setTransport() — the only HTTP-ish hook is `public function setCheckHttpStatusCallback(callable $callable): self`, which handles status codes, not transport. So "cannot swap transport" holds.
  
  (3) THE COMPARATIVE CLAUSE IS FALSE FOR 2 OF 3 NAMED SDKs — this is the load-bearing, architecture-prescribing part, and it is contradicted by primary sources:
    • SALOON — has the SAME coupling the claim condemns. saloonphp/saloon v3 composer.json require = {php ^8.2, guzzlehttp/guzzle ^7.6, guzzlehttp/promises, guzzlehttp/psr7, psr/http-factory, psr/http-message}. No psr/http-client, no php-http/discovery. Saloon's own docs (docs.saloon.dev/conclusion/whats-new-in-v3) state "Saloon version three requires Guzzle ^7.6" and that dropping Guzzle / PSR-18-only sending is FUTURE work. Saloon is a hard-Guzzle-dependency library, so citing it as avoiding this coupling "via HTTPlug/PSR-18 discovery" is simply wrong.
    • SENTRY — outdated by ~2.5 years, and
  *Контр-источник:* https://raw.githubusercontent.com/saloonphp/saloon/v3/composer.json (Saloon v3 hard-requires guzzlehttp/guzzle ^7.6, no psr/http-client — same coupling the claim condemns); https://github.com/getsentry/sentry-php/blob/master/UPGRADE-4.0.md + https://raw.githubusercontent.com/getsentry/sentry-php/master/composer.json (sentry-php 4.0 removed HTTPlug/php-http, added ext-curl, ships own cURL client — 
- **Голос 2 — опроверг** (уверенность: high):
  Split verdict — the factual half checks out, but the citation is misattributed and the comparative half (the load-bearing prescriptive part) is contradicted by primary sources.
  
  1) SOURCE MISATTRIBUTION. The cited "primary" source, github.com/amocrm/amocrm-api-php/issues/578, is titled «Совместимость с php 8.3» and is entirely about a `lcobucci/clock` 2.3.0 constraint conflict on PHP 8.3. It contains no mention of guzzlehttp/guzzle version constraints, PSR-18, or HTTP-client swapping. The supporting quote `"guzzlehttp/guzzle": "6.* || 7.*"` does not come from that issue; it comes from composer.json. Ironically, the dependency-hell issue actually filed against the repo is about lcobucci/clock, not Guzzle — the claim borrowed an unrelated ticket as evidence.
  
  2) FACTUAL HALF IS TRUE (verified independently against the real primary source). https://raw.githubusercontent.com/amocrm/amocrm-api-php/master/composer.json requires: `guzzlehttp/guzzle: 6.* || 7.*`, `fig/http-message-util: 1.*`, `amocrm/oauth2-amocrm ^3.0`, `symfony/dotenv 3.*–7.*` — and does NOT require `psr/http-client`, `psr/http-factory`, `psr/http-client-implementation`, or `php-http/discovery`. Source-level coupling is worse than composer-level: src/AmoCRM/Client/AmoCRMApiRequest.php imports `use GuzzleHttp\ClientInterface;` (Guzzle's own interface, NOT `Psr\Http\Client\ClientInterface`), types `private $httpClient` as that interface, calls `$this->httpClient->request($method, $url, $options)` with `GuzzleHttp\RequestOptions` constants at 5 call sites, and catches `GuzzleHttp\Exception\GuzzleException`. A PSR-18 client cannot be substituted.
  
  3) COMPARATIVE HALF IS FALSE FOR 2 OF 3 NAMED EXEMPLARS.
   • Saloon v3 (raw.githubusercontent.com/saloonphp/saloon/v3/composer.json) hard-requires `guzzlehttp/guzzle: ^7.6`, `guzzlehttp/promises: ^1.5 || ^2.0`, `guzzlehttp/psr7: ^2.0` — the identical composer-level Guzzle coupling. Saloon does NOT use HTTPlug/PSR-18 discovery; it swaps transport via its own `Saloon\Contracts\Sender` interface (default `src/Http/Senders/GuzzleSender.php`), a runtime strategy pattern, not a virtual-package abstraction.
   • Sentry PHP does the opposite of what the cla
  *Контр-источник:* https://raw.githubusercontent.com/saloonphp/saloon/v3/composer.json (requires guzzlehttp/guzzle ^7.6 — same coupling); https://raw.githubusercontent.com/getsentry/sentry-php/master/composer.json + CHANGELOG 4.0.0 «The SDK now ships with its own HTTP client based on cURL» (no httplug/discovery/psr-18); https://github.com/amocrm/amocrm-api-php/issues/578 (actually about lcobucci/clock + PHP 8.3, not
- **Голос 3 — опроверг** (уверенность: high):
  SPLIT VERDICT — the factual core is confirmed, but the claim as written fails on (a) source attribution and (b) its comparative justification, which is false for 2 of the 3 named exemplars.
  
  === WHAT SURVIVES (verified, keep it) ===
  amocrm/amocrm-api-library composer.json (raw, master) require block contains, verbatim: "guzzlehttp/guzzle": "6.* || 7.*" — and NO psr/http-client, NO psr/http-factory, NO php-http/discovery, NO "provide"/"suggest" sections at all (confirmed absent). Verified independently at code level, which makes the coupling STRONGER than claimed: src/AmoCRM/Client/AmoCRMApiRequest.php line 15 imports GuzzleHttp\ClientInterface (Guzzle's own contract), NOT Psr\Http\Client\ClientInterface; line 136 `$this->httpClient = $oAuthClient->getHttpClient();`; lines 223/311/379/453/533 call `$this->httpClient->request(...)`. PSR-18's ONLY method is sendRequest(RequestInterface): ResponseInterface — Guzzle's `request($method,$uri,array $options)` does not exist in PSR-18. AmoCRMOAuth.php further imports/catches GuzzleHttp\Exception\{ConnectException, GuzzleException, TooManyRedirectsException} and declares `getHttpClient(): ClientInterface` (Guzzle's). So a PSR-18 client cannot be substituted — the "consumers cannot swap transport" half is correct.
  
  === REFUTATION 1: cited primary source does not support the claim ===
  The claim cites https://github.com/amocrm/amocrm-api-php/issues/578 as "primary". Issue #578 is titled "PHP 8.3 Compatibility" (2024-12-06) and is about a lcobucci/clock version conflict ("lcobucci/clock": "~2.0.0 || ^2.1.0" resolving to 2.3.0, which declares php "~8.1.0 || ~8.2.0", blocking install on PHP 8.3.14) cascading through lcobucci/jwt 4.3.0. It contains NO discussion of Guzzle, PSR-18, HTTPlug, or transport swapping. The supporting quote was lifted from composer.json, not from the cited issue. (Ironically #578 evidences a DIFFERENT hard-dep leak — lcobucci/clock — not the Guzzle one.)
  
  === REFUTATION 2: the comparison is factually wrong on Saloon ===
  Claim says Saloon avoids this coupling "via HTTPlug/PSR-18 discovery". Saloon v3 composer.json require: {"php":"^8.2","guzzlehttp/guzzle":"^7.6","guzzlehttp/promises":"
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/issues/578 (actual title "PHP 8.3 Compatibility", lcobucci/clock conflict — does not mention Guzzle/PSR-18); https://raw.githubusercontent.com/saloonphp/saloon/v3/composer.json (hard-requires guzzlehttp/guzzle ^7.6, no psr/http-client, no php-http/discovery); https://raw.githubusercontent.com/getsentry/sentry-php/master/composer.json + /CHANGELOG.md 4.0.0 (

### 9. The official amocrm/amocrm-api-php library hard-pins a transitive utility dependency (lcobucci/clock) so tightly in its own composer.json that a mere PHP minor-version upgrade (8.2 -> 8.3) made the package uninstallable: the pin "~2.0.0 || ^2.1.0" resolves at most to clock 2.3.0, which itself requires php ~8.1.0 || ~8.2.0. This is direct evidence that the official lib's dependency policy leaks its internal implementation choices (JWT/clock) into consumer runtime constraints — a concrete anti-pattern for a rewritten client, which should keep required deps minimal and unpinned to internals.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/578 (primary)
*Цитата:* "При установке текущей версии пакета (1.10.2) на версию php 8.3 возникает конфикт совместимостей с пакетом "lcobucci/clock". В composer.json стоит ограничение "~2.0.0 || ^2.1.0", последняя доступная версия под это ограничение 2.3.0. А эта версия в свою очередь ограничена php 8.2."
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  REFUTED on four independent grounds — the claim inherits the issue reporter's own misdiagnosis and states it in present tense.
  
  1) EMPIRICALLY FALSE that the package was uninstallable on PHP 8.3. I ran the real resolver locally: composer.json `{"require":{"amocrm/amocrm-api-library":"1.10.2"},"config":{"platform":{"php":"8.3.14"}}}` + `composer update --dry-run` resolves cleanly, locking `lcobucci/clock (2.2.0)` and `lcobucci/jwt (4.0.4)`. Packagist metadata explains why: clock 2.2.0 requires php `^8.0` (2.1.0 also `^8.0`, 2.0.0 `^7.4||^8.0`); only 2.3.0 narrowed to `~8.1.0||~8.2.0`. The claim's premise "the pin resolves at most to clock 2.3.0" confuses "highest matching version" with "only resolvable version" — Composer backtracks. Same test with the current release: `amocrm/amocrm-api-library (1.17.0)` resolves on platform php 8.4.10.
  
  2) THE QUOTE IS A LOCK-FILE ERROR, NOT A CONSTRAINT ERROR. The full issue body (fetched via `gh api repos/amocrm/amocrm-api-php/issues/578`) shows the actual composer output: "Your lock file does not contain a compatible set of packages. Please run composer update." / "- lcobucci/clock is locked to version 2.3.0 and an update of this package was not requested." That is the reporter's stale project lock (built on PHP 8.2, then the Docker base image bumped to 8.3.14 — note the `0.737` docker build timestamps), which Composer itself tells him to fix with `composer update`. It is not evidence that the library's constraint blocks installation.
  
  3) "HARD-PINS ... SO TIGHTLY" MISCHARACTERIZES THE CONSTRAINT. `~2.0.0 || ^2.1.0` unions to `>=2.0.0 <3.0.0`, i.e. exactly `^2.0.0` — a full major-version caret range, the opposite of a hard pin. The real (much weaker) defect is a stale upper bound: no `^3.0` branch. Also, `lcobucci/clock` is a DIRECT entry in amocrm's own `require`, not a "transitive utility dependency" — the claim's own wording ("in its own composer.json") contradicts its label.
  
  4) OUTDATED. Issue #578 was closed 2025-03-18 by maintainer bessudnov with "добавлена поддержка". Current master composer.json (fetched raw) has `"lcobucci/clock": "1.1.0 ||^2.0.0"` and `"lcobucci/jwt": "^3.4.6 || ^4.0.4 || ^5.0"` — 
  *Контр-источник:* Local Composer resolution test (amocrm/amocrm-api-library 1.10.2 @ platform php 8.3.14 → clock 2.2.0, jwt 4.0.4; 1.17.0 @ php 8.4.10 OK); https://repo.packagist.org/p2/lcobucci/clock.json (2.2.0 requires php ^8.0); https://repo.packagist.org/p2/lcobucci/jwt.json (4.3.0 requires php ^7.4||^8.0); full issue body + maintainer close comment via `gh api repos/amocrm/amocrm-api-php/issues/578` (closed 2
- **Голос 2 — опроверг** (уверенность: high):
  REFUTED on three independent grounds; the central factual assertion is empirically false.
  
  (1) "Made the package uninstallable" is FALSE — verified by direct experiment. I ran a fresh `composer update` for `amocrm/amocrm-api-library:1.10.2` (the exact version in the issue) with `config.platform.php` forced to `8.3.14` (the exact PHP version the reporter ran). It RESOLVED CLEANLY: "Locking lcobucci/clock (2.2.0)", "Locking lcobucci/jwt (4.0.4)", 29 packages, zero conflicts. Repeated on real PHP 8.4.21 for both 1.10.2 and current 1.17.0 — both resolve to clock 2.2.0, no error. The claim's arithmetic is wrong: `~2.0.0 || ^2.1.0` = `>=2.0.0 <3.0.0`, and Composer picks the highest version compatible with the *platform*, not the highest version absolutely. Packagist shows clock 2.2.0 requires only `php ^8.0`, which satisfies 8.3 and 8.4. So "resolves at most to clock 2.3.0" is a misunderstanding of the resolver.
  
  (2) The quoted error is a STALE-LOCKFILE report, not a constraint conflict — and the claim's own supporting quote says so verbatim: "Your lock file does not contain a compatible set of packages. Please run composer update." and "lcobucci/clock **is locked to version 2.3.0 and an update of this package was not requested**." That is `composer install` against a lock generated under PHP 8.2. WebSearch corroborates independently: the documented fix is simply to run `composer update`. Identical stale-lock patterns appear in Drupal.org #3442019 and Wikimedia T405031. The claim misreads a user-side lockfile artifact as a library dependency-policy defect.
  
  (3) "Transitive utility dependency ... leaks its internal implementation choices" is FALSE — clock is a DIRECT first-party dependency of amo's own code. `src/AmoCRM/OAuth/AmoCRMOAuth.php` imports and instantiates it: `use Lcobucci\Clock\FrozenClock;` / `use Lcobucci\Clock\SystemClock;` (lines 27-28) and selects between them at line 720-722. Explicitly requiring a package you directly `use` is *recommended* Composer practice (never rely on transitive deps), the exact opposite of the anti-pattern alleged.
  
  (4) "Hard-pins ... so tightly" is a mischaracterization: `~2.0.0 || ^2.1.0` is a full major-vers
  *Контр-источник:* Empirical Composer resolution (Composer 2.9.7, PHP 8.4.21) + packagist metadata https://repo.packagist.org/p2/lcobucci/clock.json + the issue's own error text at https://github.com/amocrm/amocrm-api-php/issues/578 + amo source at src/AmoCRM/OAuth/AmoCRMOAuth.php (lines 27-28, 720-722) + current composer.json at 1.17.0 (published 2026-07-01) + corroborating Drupal.org #3442019 / Wikimedia T405031 (
- **Голос 3 — опроверг** (уверенность: high):
  REFUTED on both load-bearing assertions ("hard-pins", "uninstallable"), plus a factual inversion ("transitive").
  
  1) "Made the package uninstallable" — FALSE, disproved empirically. `~2.0.0 || ^2.1.0` is effectively `^2.0`, so composer may pick any 2.x. Packagist metadata (repo.packagist.org/p2/lcobucci/clock.json): clock 2.3.0 = `php ~8.1.0 || ~8.2.0`, but 2.2.0 = `php ^8.0`, 2.1.0 = `^8.0`, 2.0.0 = `^7.4 || ^8.0`. I ran the real resolver with `config.platform.php` forced to 8.3.0 and again to 8.4.0, requiring `amocrm/amocrm-api-library:1.10.2`: both resolved cleanly — "Locking amocrm/amocrm-api-library (1.10.2) / lcobucci/clock (2.2.0) / lcobucci/jwt (4.0.4)", 29 packages, no conflict. The package installs fine on 8.3 and 8.4.
  
  2) The quote is misread. The reporter's own pasted output (full issue body via `gh api repos/amocrm/amocrm-api-php/issues/578`) is a LOCK-FILE error in the consumer's project, not a constraint deadlock: "Your lock file does not contain a compatible set of packages. Please run composer update." → "lcobucci/clock is locked to version 2.3.0 and an update of this package was not requested." Composer itself printed the one-line fix. That is a stale composer.lock carried from PHP 8.2, not amo's dependency policy making the package uninstallable.
  
  3) "Transitive utility dependency ... leaks internal implementation choices (JWT/clock)" — inverted. `lcobucci/clock` is a DIRECT, genuinely used dependency: `src/AmoCRM/OAuth/AmoCRMOAuth.php` at tag 1.10.2 has `use Lcobucci\Clock\FrozenClock;` (lines 27, 563, 610 — `new LooseValidAt(FrozenClock::fromUTC())`). Declaring a package you import is correct Composer practice, not an anti-pattern.
  
  4) "Hard-pins ... so tightly" — mischaracterization. A caret major-range is not a hard pin. The actually tight pin is upstream and not amo's: lcobucci's own policy of `~8.1.0 || ~8.2.0` PHP-minor pinning (clock 3.1.0 = `~8.2.0`, 3.4.0 = `~8.3.0 || ~8.4.0`, 3.6.0 = `~8.4.0 || ~8.5.0`).
  
  5) Stale as a live characterization: issue opened 2024-12-06, closed 2025-03-18 by maintainer bessudnov ("добавлена поддержка"), i.e. ~16 months before today (2026-07-22). Current master requires `"lcobucci/cloc
  *Контр-источник:* Empirical: `composer update --dry-run` with config.platform.php=8.3.0 and 8.4.0 on amocrm/amocrm-api-library:1.10.2 → resolves to lcobucci/clock 2.2.0, no conflict (run 2026-07-22, Composer 2.9.7). Packagist metadata https://repo.packagist.org/p2/lcobucci/clock.json (2.2.0 requires php ^8.0). Primary issue body via gh api repos/amocrm/amocrm-api-php/issues/578 ("Your lock file does not contain a c

### 10. Maintainer responsiveness on the official library is measured in months, not days: issue #578 was opened 2024-12-06 and received exactly one comment — the two-word "добавлена поддержка" from maintainer bessudnov — posted 2025-03-18 at the same second the issue was closed, i.e. 102 days of total silence on a blocker that prevented installation on a current PHP release. Depending on this library for a project that must track PHP 8.4 is therefore a supply-chain risk, which strengthens the case for an in-house client.

*Источник:* https://github.com/amocrm/amocrm-api-php/issues/578 (primary)
*Цитата:* "добавлена поддержка"
*Голоса скептиков:* опровергли 2 из 2

- **Голос 1 — опроверг** (уверенность: high):
  The literal facts check out, but all three inferences built on them fail.
  
  VERIFIED (not disputed): issue #578 "Совместимость с php 8.3" opened 2024-12-06T12:10:30Z by Scarboroid; comments totalCount=1; body exactly "добавлена поддержка" by bessudnov at 2025-03-18T01:28:43Z; closed_at 2025-03-18T01:28:42Z (1s apart, effectively simultaneous). Elapsed is 101.5 days; "102" is calendar-date arithmetic. Immaterial.
  
  REFUTATION 1 — the generalization is contradicted by the repo's own data. I pulled the last 60 issues with first-comment timestamps. Median time-to-first-comment = 3 days. 21/40 commented issues answered in ≤3 days, 26/40 in ≤14 days. Mean (67d) is dragged only by a long tail (#507=844d, #579=519d, #561=326d, #577=195d). #578 at 101 days is in the worst quartile. So "responsiveness is measured in months, not days" inverts the central tendency — the median IS days. Fast counter-examples exist adjacent to #578: #588 (2025-02-03 → 2 days, bessudnov), #590 (2025-02-11 → 1 day, bessudnov). A real weakness does exist — 20 of 60 issues never received any comment — but that is a different claim than the one made.
  
  REFUTATION 2 — "a blocker that prevented installation" is false, and the claim's own primary source disproves it. Packagist shows lcobucci/clock 2.1.0 and 2.2.0 both require php ^8.0, which satisfies 8.3 and 8.4. amocrm 1.10.2's constraint "~2.0.0 || ^2.1.0" therefore already permitted an 8.3-compatible clock. The reporter's own pasted output — which the claim quotes from — says "Your lock file does not contain a compatible set of packages. Please run composer update." and "lcobucci/clock is locked to version 2.3.0 and an update of this package was not requested." That is a stale composer.lock in the reporter's project, not a resolution failure in the library. A fresh composer require on PHP 8.3 resolves clock to 2.2.0 and installs. Separately, the constraint was in fact relaxed to "1.1.0 ||^2.0.0" in 1.11.0, released 2025-02-07 — day 63, i.e. 39 days BEFORE the comment — so "102 days of total silence on a blocker" conflates issue-tracker latency with fix latency.
  
  REFUTATION 3 — PHP 8.3 is silently swapped for PHP 8.4. The issue title 
  *Контр-источник:* GitHub API on amocrm/amocrm-api-php (issue #578 + last 60 issues via GraphQL; composer.json at tags 1.10.2–1.17.0) and Packagist metadata for lcobucci/clock (repo.packagist.org/p2/lcobucci/clock.json)
- **Голос 2 — опроверг** (уверенность: high):
  VERIFIED via GitHub API (primary): issue #578 created 2024-12-06T12:10:30Z, closed 2025-03-18T01:28:42Z, comments=1, sole comment "добавлена поддержка" by bessudnov at 2025-03-18T01:28:43Z (1 second AFTER close, not "the same second"). Elapsed = 101.6 days ≈ 102. bessudnov is the top contributor (440 commits). The install blocker is real (composer conflict quoted in issue body). So the anecdote itself is accurate.
  
  REFUTATION 1 — the headline generalization is contradicted by the repo's own data. I measured time-to-first-non-author reply across all 60 issues that ever got one: MEDIAN ≈ 11 days, not months. 25/60 (42%) were answered within 7 days. Maintainer bessudnov personally replied in 0.4d (#486), 0.9d (#424), 1.0d (#423), 1.3d (#590), 1.7d (#470), 2.2d (#588). #578's 101.6 days sits in the worst ~15% of the distribution. "Responsiveness is measured in months, not days" is an N=1 cherry-pick of the tail presented as the central tendency; the true pattern is erratic/bimodal (median ~11d, but a long tail: #491 515d, #563 379d, #561 327d, #507 844d, and 40/100 recent issues with zero replies ever).
  
  REFUTATION 2 — "102 days of TOTAL SILENCE on a blocker" is contradicted by commit/release history during that exact window. Commit d395e304/69f98c7a "fixing a php compatibility conflict" landed 2025-01-17 (day 42, branch hotfix/ISSUE-573), and release 1.11.0 (2025-02-07) changed the very constraint at issue from "lcobucci/clock": "~2.0.0 || ^2.1.0" to "1.1.0 || ^2.0.0"; 1.12.0 shipped 2025-03-10. The silence was on the ISSUE THREAD, not in the codebase — the claim conflates thread-neglect with maintainer inaction.
  
  MINOR: #578 is titled "Совместимость с php 8.3" — it is about PHP 8.3, while the claim pivots to "a project that must track PHP 8.4."
  
  IMPORTANT CAVEAT FOR THE PARENT — the CONCLUSION survives on stronger independent evidence, just not on this reasoning. Current release 1.17.0 (published 2026-07-01) STILL pins "lcobucci/clock": "1.1.0 ||^2.0.0". Packagist shows PHP 8.4 support in lcobucci/clock only from 3.3.1+ (3.4.0 = "~8.3.0 || ~8.4.0", 3.6.0 = "~8.4.0 || ~8.5.0") — all excluded by that constraint. Issue #600 "Add support for lcobucci/c
  *Контр-источник:* https://github.com/amocrm/amocrm-api-php/issues/600 (open 16 months, clock 3.x/PHP 8.4 request unanswered); GitHub API repos/amocrm/amocrm-api-php/issues + /comments (measured median first-reply ≈11 days over 60 issues; bessudnov replies at 0.4–2.2 days on #486/#424/#423/#590/#470/#588); commits d395e304 & 69f98c7a "fixing a php compatibility conflict" 2025-01-17 and release 1.11.0 2025-02-07 chan

### 11. The official amoCRM documentation page for its PHP library contains no technical content whatsoever — no PHP version requirements, dependencies, rate limits, error/exception taxonomy, token-storage guidance, or code examples — and explicitly delegates all documentation to the GitHub repository. For the rewrite this means the vendor publishes zero normative architectural guidance for the PHP client; github.com/amocrm/amocrm-api-php is the only authoritative source, and there is no official contract to stay compatible with at the docs level.

*Источник:* https://www.amocrm.ru/developers/content/crm_platform/api-php-library (primary)
*Цитата:* "Подробная информация и документация доступна на странице библиотеки на Github"
*Голоса скептиков:* опровергли 3 из 3

- **Голос 1 — опроверг** (уверенность: high):
  Two independent fetches of the primary source (https://www.amocrm.ru/developers/content/crm_platform/api-php-library) confirm the page's *kernel* — it is a thin landing page whose substantive line is "Подробная информация и документация доступна на странице библиотеки на Github" — but the claim as written overreaches on two verifiable points, and ≥1 of them is load-bearing for the rewrite.
  
  (1) "no technical content whatsoever ... or code examples" is false as stated. The page DOES carry a concrete install directive: `composer require amocrm/amocrm-api-library`, plus a stated feature framing (API client for the main entities, OAuth 2.0 support). That is not nothing, and it is load-bearing in a non-obvious way: the Packagist/Composer package name is **amocrm/amocrm-api-library**, which does NOT match the repo name amocrm/amocrm-api-php used everywhere in the claim. A rewrite that took "the docs page has zero technical content" at face value would have missed the single normative fact the vendor does publish there (the canonical package identifier). Confirmed independently by Packagist (packagist.org/packages/amocrm/amocrm-api-library) and the repo README/composer.json.
  
  (2) "the vendor publishes zero normative architectural guidance for the PHP client ... there is no official contract to stay compatible with at the docs level" is contradicted by the vendor's own docs site — the very page named in the same research question, https://www.amocrm.ru/developers/content/api/recommendations, which publishes hard, normative, machine-relevant constraints: "не более 7 запросов в секунду на одну интеграцию" and "до 50 запросов в секунду на весь аккаунт"; "Максимальное кол-во возвращаемых сущностей ... не более 500, большинство методов возвращает не более 250"; "Максимальное кол-во создаваемых/изменяемых сущностей не более 250" with a recommended batch of "не более 50"; and status-code semantics (429 = limit exceeded, 403 = block after repeated violation, 402 = expired subscription, 401 = invalid/expired Access Token → refresh). Those are precisely the inputs to the rate limiter, pagination/lazy-collection, batch-chunking and transient-vs-permanent error taxonomy the
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (vendor-published normative limits/status semantics) and the primary page itself, which shows `composer require amocrm/amocrm-api-library`; corroborated by https://packagist.org/packages/amocrm/amocrm-api-library and https://github.com/amocrm/amocrm-api-php/blob/master/composer.json
- **Голос 2 — опроверг** (уверенность: high):
  OVERREACH #1 — "no technical content whatsoever... or code examples" is factually false. I fetched the page twice (two independent renders agreed on verbatim text). The full page is six sentences, and it DOES contain a code block:
  
  "Для работы с API amoCRM предоставляется официальная библиотека для проектов на языке PHP. Пакет включает API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM. Установка через composer: `composer require amocrm/amocrm-api-library`. Подробная информация и документация доступны на странице библиотеки на Github. Вопросы по работе с библиотекой можно задавать в техническую поддержку или на Github в разделе Issue."
  
  That is (a) a literal code example, (b) a normative auth-protocol statement (OAuth 2.0), and (c) a load-bearing, non-derivable fact for this rewrite: the Composer package name is `amocrm/amocrm-api-library`, which does NOT match the repo name `amocrm/amocrm-api-php` used throughout the research question. Confirmed on Packagist: package `amocrm/amocrm-api-library` → source repo github.com/amocrm/amocrm-api-php, requires PHP ">=7.1 || >=8.0", deps guzzlehttp/guzzle 6|7, lcobucci/jwt, ramsey/uuid, nesbot/carbon, symfony/dotenv, amocrm/oauth2-amocrm. Anyone acting on "the page has zero technical content" would `composer require amocrm/amocrm-api-php` and fail.
  
  OVERREACH #2 (the load-bearing one) — the inference "the vendor publishes zero normative architectural guidance for the PHP client... there is no official contract to stay compatible with at the docs level" is directly contradicted by the SAME vendor docs site, on a page the research question itself cites as an official source. Live fetch of https://www.amocrm.ru/developers/content/api/recommendations yields exactly the normative constraints a PHP client must encode:
  - Rate limiting: "не более 7 запросов в секунду на одну интеграцию", "до 50 запросов в секунду на весь аккаунт"
  - Batching/pagination: read responses capped at "не более 500", most methods "не более 250 сущностей"; writes "не более 250" per request with "не более 50" recommended
  - Error taxonomy + transient/permanent split: 429 on limit exceeded, 403 on repe
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (same official vendor docs site — publishes 7 rps/integration, 50 rps/account, ≤250 write / ≤500 read batch caps, 429/403/402/401 semantics, TLS 1.2, and explicit secure token-storage guidance); https://packagist.org/packages/amocrm/amocrm-api-library (confirms package name ≠ repo name, PHP ">=7.1 || >=8.0", Guzzle 6|7 dependency)
- **Голос 3 — опроверг** (уверенность: high):
  Two defects: the literal description of the page is wrong, and the inference drawn from it is a non-sequitur contradicted by the vendor's own docs.
  
  (1) "No technical content whatsoever ... or code examples" is false on the primary source. Verbatim main content of https://www.amocrm.ru/developers/content/crm_platform/api-php-library (fetched, full body): «Библиотека на PHP для работы с API / Для работы с API amoCRM мы предоставляем официальную библиотеку для проектов написанных на языке PHP. / В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM. / Установить библиотеку можно с помощью composer: `composer require amocrm/amocrm-api-library` / Подробная информация и документация доступна на странице библиотеки на Github / Вопросы по работе с библиотекой можно задавать как в техническую поддержку, так и на Github в раздел Issue.» There IS a code block (the composer command), a dependency/package identifier (note: `amocrm/amocrm-api-library`, which differs from the repo name `amocrm-api-php` — the vendor's canonical Packagist name is normative and only stated here), and a normative scope statement (OAuth 2.0 as the auth protocol). "Zero technical content" overreaches; the defensible claim is "the page is a thin stub".
  
  (2) The load-bearing inference — "the vendor publishes zero normative architectural guidance for the PHP client; GitHub is the only authoritative source; there is no official contract to stay compatible with at the docs level" — is directly contradicted by the same docs site, one link away in the page's own sidebar ("API Reference → Ограничения и рекомендации"). Fetched https://www.amocrm.ru/developers/content/api/recommendations yields hard normative constraints that govern exactly the subsystems this rewrite must design: не более 7 запросов в секунду на одну интеграцию; до 50 запросов в секунду на весь аккаунт; 429 on limit breach, 403 on repeated violation (account block), 402 on subscription end, 401 on invalid/expired Access Token; максимальное кол-во возвращаемых сущностей не более 500 (большинство методов — не более 250); максимальное кол-во создаваемых/изменяемых сущносте
  *Контр-источник:* https://www.amocrm.ru/developers/content/api/recommendations (same vendor docs site, linked from the PHP-library page's own API Reference nav) — publishes 7 req/s per integration, 50 req/s per account, 429/403/402/401 semantics, max 250 created/modified per request (recommended ≤50), max 500/250 returned per page, TLS 1.2; plus the PHP-library page itself, whose verbatim body contains a `composer 


## Сырые извлечённые утверждения по источникам (до голосования)

### Источник #1 — качество: "primary"

- **[central]** The official amoCRM documentation page for its PHP library contains no technical content whatsoever — no PHP version requirements, dependencies, rate limits, error/exception taxonomy, token-storage guidance, or code examples — and explicitly delegates all documentation to the GitHub repository. For the rewrite this means the vendor publishes zero normative architectural guidance for the PHP client; github.com/amocrm/amocrm-api-php is the only authoritative source, and there is no official contract to stay compatible with at the docs level.
  > Подробная информация и документация доступна на странице библиотеки на Github

- **[central]** amoCRM declares the package's scope as an API client covering only the "main entities" plus OAuth 2.0 authorization — i.e. the vendor itself does not claim complete API surface coverage, so gaps in entity support are by design rather than bugs, and a from-scratch client is not obliged to mirror its (partial) entity model.
  > В данном пакете представлен API клиент с поддержкой основных сущностей и авторизацией по протоколу OAuth 2.0 в amoCRM.

- **[supporting]** amoCRM positions amocrm/amocrm-api-library as its officially provided (vendor-maintained) PHP library, not a community project — so its design decisions carry vendor endorsement and are a legitimate baseline/reference point even though the docs page supplies no rationale for them.
  > Для работы с API amoCRM мы предоставляем официальную библиотеку для проектов написанных на языке PHP.

- **[tangential]** The Composer package name differs from the GitHub repository name: the install command is `composer require amocrm/amocrm-api-library` while the repo is amocrm/amocrm-api-php — a verifiable naming inconsistency to be aware of when pinning/replacing the dependency in the two Laravel consumer projects.
  > composer require amocrm/amocrm-api-library

- **[supporting]** amoCRM offers no library-specific SLA or dedicated support channel: questions are routed either to generic technical support or to GitHub Issues, which means bug-fix and new-error-type turnaround for the official library is unbounded — an argument for owning the client (and its Sentry-visible unknown-error trap) in-house.
  > Вопросы по работе с библиотекой можно задавать как в техническую поддержку, так и на Github в раздел Issue.

### Источник #2 — качество: "primary"
Дата: Not stated on the page (no publish/update date shown); page content references SSLv3 support ending 16.11.2016 as the only dated item

- **[central]** amoCRM enforces a hard rate limit of 7 requests/sec per integration and up to 50 requests/sec per account — so a reference PHP client must implement client-side rate limiting (token bucket / leaky bucket) keyed per integration, not just per-account, rather than relying on retry-after-429.
  > не более 7 запросов в секунду на одну интеграцию" и "до 50 запросов в секунду на весь аккаунт

- **[central]** Repeated rate-limit violations escalate from HTTP 429 to HTTP 403 with account blocking — meaning naive/aggressive retry-on-429 is actively dangerous and the client's backoff must be conservative, with 403 classified as a permanent (non-retryable) failure distinct from transient 429.
  > При превышении возвращается HTTP 429 ... При многократном нарушении — HTTP 403, аккаунт блокируется

- **[central]** Batch limits are asymmetric and lower than the documented ceiling: reads return at most 500 entities (most methods 250), writes accept at most 250 entities, with amoCRM explicitly recommending no more than 50 per write request — so the client's batching layer should chunk writes at ~50, not at the 250 maximum.
  > не более 500" сущностей за один запрос (обычно не более 250) ... При создании/изменении: макимум 250 сущностей, рекомендуется не более 50

- **[supporting]** HTTP 402 means the account subscription expired, with write access blocked immediately and read access surviving for 30 days — a business/state error that is neither an auth error nor transient, so an error taxonomy that lumps 4xx together will misclassify it and retrying will never succeed.
  > 402 — доступ закрыт после окончания подписки (блокировка записи сразу, чтение на 30 дней)

- **[supporting]** HTTP 504 on batch operations is an amoCRM-side timeout whose documented remedy is to reduce the number of entities in the request and retry — i.e. a transient error whose correct handling is adaptive batch-size reduction, not plain exponential backoff on the same payload.
  > Ошибка 504 требует уменьшения объема в запросе

### Источник #3 — качество: "primary"
Дата: not stated (living documentation, saloon-docs v3 branch; accessed 2026-07-22)

- **[central]** Saloon v3 ships a two-branch, status-code-keyed exception tree rooted at SaloonException: transport/connection failures throw FatalRequestException, while HTTP-level failures throw RequestException, which splits into ServerException (5xx: InternalServerErrorException 500, ServiceUnavailableException 503, GatewayTimeoutException 504) and ClientException (4xx: UnauthorizedException 401, PaymentRequiredException 402, ForbiddenException 403, NotFoundException 404, MethodNotAllowedException 405, RequestTimeOutException 408, UnprocessableEntityException 422, TooManyRequestsException 429). This is a directly reusable taxonomy skeleton where the transient/permanent split maps onto the Fatal-vs-Request and Server-vs-Client branches.
  > SaloonException
├── FatalRequestException (Connection Errors)
└── RequestException (Request Errors)
    ├── ServerException (5xx)
    │   ├── InternalServerErrorException (500)
    │   ├── ServiceUnavailableException (503)
    │   └── GatewayTimeoutException (504)
    └── ClientException (4xx)
        ├── UnauthorizedException (401)
        ├── PaymentRequiredException (402)
        ├── ForbiddenException (403)
        ├── NotFoundException (404)
        ├── MethodNotAllowedException (405)
        ├── RequestTimeOutException (408)
        ├── UnprocessableEntityException (422)
        └── TooM

- **[central]** Saloon's failure classification is an overridable hook, not hard-wired: overriding hasRequestFailed(Response $response): ?bool on the connector or request lets a client treat an HTTP 200 carrying an error payload in the body as a failure — the exact shape amoCRM exhibits when it returns success status codes with error content.
  > You may integrate with an API which returns a 200 response status but with an error message in the response body. To handle this, you can extend the `hasRequestFailed` method on your connector or request.

- **[central]** Exception construction is a second, separate extension point: overriding getRequestException(Response $response, ?Throwable $senderException): ?Throwable returns a custom exception per response, and when both connector and request implement it the request-level override wins — extensibility by hook/override precedence rather than by subclassing the transport.
  > You may choose to return your own exception if a request has failed. Just extend the `getRequestException` method on either your connector or request. […] Priority is given to the request when you extend the `getRequestException` method on both your connector and request.

- **[supporting]** Throwing on HTTP failure is opt-in in Saloon, not the default: by default a failed HTTP response is returned as a Response object and only connection failures throw automatically; loudness must be turned on explicitly per-response with $response->throw() or globally with the AlwaysThrowOnErrors trait on the connector. A fail-loud-by-default client must therefore invert this default.
  > The only exception to this is if Saloon cannot connect to an API, which will throw a `FatalRequestException`. […] You can add the `AlwaysThrowOnErrors` trait on your connector, and then every request that fails will throw an exception. […] On a per-response basis, you may use the `throw` method after sending your response. This method will throw an exception if the response has a 'failed' HTTP status code like 4xx or 5xx.

- **[supporting]** Saloon's documented hierarchy enumerates only selected status codes and has no dedicated Unknown/unclassified exception class — unlisted codes (e.g. 400, 409, 502) fall back to the generic ClientException/ServerException parents, so an unrecognised error type is silently absorbed into a broad bucket rather than surfaced. A client that must signal new amoCRM error kinds needs an explicit Unknown leaf that Saloon does not provide out of the box.
  > No explicit statement about a default/fallback exception for unmatched status codes was found in the provided content.

### Источник #4 — качество: "primary"
Дата: Not stated on the page (PSR-18 was accepted in 2018; editor listed as Tobias Nyholm)

- **[central]** PSR-18 mandates that a conforming HTTP client MUST NOT throw an exception for HTTP error status codes — 4xx and 5xx responses are returned as ordinary PSR-7 responses. Consequence for the amoCRM client design: mapping amo's 4xx/5xx (400 validation, 401 rotation, 402 billing, 429 rate limit, 5xx transient) into a typed exception taxonomy is explicitly the SDK layer's responsibility, not the transport's, and no PSR-18 transport can be relied on to signal them.
  > HTTP clients never will throw exceptions for 4xx and 5xx responses.

- **[central]** PSR-18's exception interfaces are deliberately siblings, not a linear inheritance chain: NetworkExceptionInterface and RequestExceptionInterface do not extend one another because inheritance does not model the domain — the shared root is only the marker ClientExceptionInterface. This is direct precedent for designing the amoCRM error taxonomy as a shallow marker-interface hierarchy (transport vs request vs API-semantic branches) rather than a deep class tree.
  > The chosen approach is to not let them extend each other because inheritance does not make sense in the domain model.

- **[supporting]** The PSR-18 meta document explicitly sanctions extending the standard exception set with more granular subtypes (e.g. TimeOutException, HostNotFoundException under NetworkExceptionInterface), i.e. the standard is intended as a floor over which an SDK adds its own finer-grained, catchable error types — including a catch-all root for unrecognised failures.
  > One could be more granular when defining exceptions. For example, `TimeOutException` and `HostNotFoundException` could be subtypes of `NetworkExceptionInterface`.

- **[supporting]** PSR-18 intentionally defines no client configuration and takes no position on middleware, leaving behaviours such as following redirects (and by extension retries, backoff, rate limiting, idempotency keys) to layers wrapping the client rather than to the client interface itself — supporting a decorator/middleware-pipeline design above a plain PSR-18 ClientInterface.
  > It is temping to allow configuration or add middleware to an HTTP client so it could i.e. follow redirects or throw exceptions.

- **[central]** PSR-18's stated design goal is Liskov substitutability of transports: a consuming library must work against any compliant client with no implementation-specific code, which argues for the amoCRM SDK to typehint Psr\Http\Client\ClientInterface (+ PSR-17 factories) and never hard-depend on Guzzle or Symfony HttpClient internals.
  > A library should be able to use any compliant client without special code to handle client implementation details (Liskov substitution principle).

### Источник #5 — качество: "blog"
Дата: 2017-02-22

- **[central]** Stripe makes mutating POST operations safely retryable via a client-generated unique identifier sent in an `Idempotency-Key` header, which the server correlates with the stored state of the original request so side effects occur only once — the canonical pattern for any API client that must retry writes after ambiguous network failures.
  > When performing a request, a client generates a unique ID to identify just that operation and sends it up to the server along with the normal payload... the server receives the ID and correlates it with the state of the request on its end.

- **[central]** Retrying failed remote calls is framed as a client-side obligation, not an optional nicety: not retrying leaves data in an inconsistent state, so retry logic belongs in the API client library itself rather than in each consumer's application code.
  > Have clients retry operations against remote services. Not doing so could leave data in an inconsistent state.

- **[central]** The prescribed backoff schedule is exponential with delay proportional to 2^n where n is the failure count, rather than a fixed-interval retry loop.
  > wait proportionally to `2^n`, where _n_ is the number of failures

- **[central]** Exponential backoff alone is insufficient — random jitter must be added to each client's wait time to desynchronize retry waves from multiple clients and avoid re-overwhelming an already degraded server (thundering herd).
  > Adding some amount of random jitter to each client's wait time

- **[supporting]** Stripe ships this policy inside its official SDK rather than documenting it as advice: the Stripe Ruby library performs automatic retries with an idempotency key, increasing backoff and jitter — evidence that retry+idempotency is an SDK-layer concern in reference client libraries.
  > automatic retries on failure with an idempotency key using increasing backoff times and jitter

### Источник #6 — качество: "primary"
Дата: Not stated on the page (undated docs file on the saloon-docs `v3` branch; Saloon v3 line, docs current as of fetch 2026-07-22)

- **[central]** Saloon v3's transport seam is its own `Sender` abstraction, not PSR-18: the framework builds a PSR-7 request internally and obtains PSR-17 factories *from the sender* to construct the URI and request-body streams. The page documents PSR-7 and PSR-17 only and never mentions PSR-18 `ClientInterface` — so the swappable unit is a Saloon Sender that wraps an HTTP client, with PSR-7/17 as the shared message/factory vocabulary between core and transport.
  > When you send a request, Saloon will construct a [PSR-7](https://www.php-fig.org/psr/psr-7/) request behind the scenes and pass it to the HTTP client. Saloon also uses [PSR-17](https://www.php-fig.org/psr/psr-17/) factories defined by the sender to create the URI (the full URL) and streams for the request body.

- **[central]** Saloon states an explicit architectural rationale for standing on PSR-7/17: HTTP-client independence as future-proofing — Guzzle is only the default sender, and replacing it would not require rebuilding the library. This is the canonical justification for putting a PSR-message boundary between an SDK's core and its transport.
  > This design decision was made because the PSR-7 and 17 standards are very well-known and have been implemented by many popular HTTP clients. While Saloon uses Guzzle as the default way to send requests, if Guzzle suddenly becomes abandoned - Saloon can use one of many other HTTP clients without needing an entire rebuild from scratch. While Guzzle is amazing, and we'd never want it to go away - making Saloon independent of the HTTP client was a big step toward future-proofing the library.

- **[supporting]** Saloon exposes the exact PSR-7 request/response pair on its response object (`$response->getPsrRequest()` / `getPsrResponse()`), and the docs name debuggability as a first-class driver: the pre-v3 design (delegating request construction to Guzzle config options) made it impossible to know what was actually sent to the third party. For an SDK that must report unknown API errors to Sentry, this is the mechanism that lets an exception carry the literal wire request and response.
  > With previous versions, Saloon heavily relied on Guzzle's config options to build the PSR-7 request internally, which meant that debugging was difficult because you never really knew what was sent to the third party. With version three, you can see the PSR request right before it is sent.

- **[supporting]** Extensibility at the transport layer is a template-method hook, `handlePsrRequest(RequestInterface $request, PendingRequest $pendingRequest): RequestInterface`, declarable on either the connector (applies to every request through that connector) or an individual request — giving per-connector cross-cutting request mutation without a formal middleware stack and without subclassing the HTTP client.
  > If you use the method on the connector - the method will be run for every request sent through the connector. The method will give you access to both the PSR request and the Saloon `PendingRequest` if you need to use it for additional information, like the Saloon request class.

- **[supporting]** The docs explicitly discourage reaching for the low-level PSR-7 escape hatch, directing users to the higher-level `Connector`/`Request`/`PendingRequest` APIs instead — i.e. the raw-transport hook is deliberately positioned as a last resort rather than the normal extension point, enforcing layering discipline.
  > You may find it easier to modify the `Connector`,  `Request`, or `PendingRequest` classes instead of the underlying PSR-7 request. You should be cautious when using this hook as there are many nicer, higher-level APIs in Saloon to modify the request.

### Источник #7 — качество: "primary"
Дата: 2023-07 (July 2023)

- **[central]** RFC 9457 defines a two-level identification scheme for API errors: the `type` URI is the primary machine-readable identifier of a problem type, and when `type` is absent it defaults to `about:blank`, meaning the error carries no semantics beyond its HTTP status code. For a client's error taxonomy this yields a deterministic dispatch key (type URI first, HTTP status as fallback) and a well-defined 'nothing known about this' bucket — the natural anchor for an explicit Unknown branch rather than a silent catch-all.
  > Consumers MUST use the 'type' URI (after resolution, if necessary) as the problem type's primary identifier. ... When this member is not present, its value is assumed to be 'about:blank'. ... indicates that the problem has no additional semantics beyond that of the HTTP status code

- **[central]** RFC 9457 explicitly forbids programmatic decisions based on parsing the human-readable `detail` string, directing implementers to structured extension members instead. This makes string-sniffing of an error body (e.g. classifying a 400 as transient by matching a Russian phrase in `detail`) an anti-pattern in a client's transient-vs-permanent classifier; the classifier should key on structured fields, not prose.
  > Consumers SHOULD NOT parse the 'detail' member for information; extensions are more suitable and less error-prone ways to obtain such information.

- **[supporting]** The `status` member inside the problem body is only advisory and duplicates the real HTTP status code, which the generator is required to set identically on the response. A client should therefore treat the transport-level HTTP status as authoritative for retry/permanence classification and treat the body's `status` as redundant metadata, not a second source of truth.
  > The 'status' member, if present, is only advisory; it conveys the HTTP status code used for the convenience of the consumer. ... Generators MUST use the same status code in the actual HTTP response, to assure that generic HTTP software that does not understand this format still behaves correctly.

- **[supporting]** RFC 9457 requires clients to ignore unrecognized extension members, and permits problem types to define their own type-specific extension members. This scopes the 'loud unknown' rule precisely: an unknown *extension field* must be tolerated silently for forward compatibility, whereas an unknown *problem type / error identity* is the thing worth surfacing — the two must not be conflated in the exception hierarchy.
  > Clients consuming problem details MUST ignore any such extensions that they don't recognize; this allows problem types to evolve and include additional information in the future. ... Problem type definitions MAY extend the problem details object with additional members that are specific to that problem type.

- **[tangential]** RFC 9457's Security Considerations warn against exposing server implementation internals (e.g. stack dumps) through problem detail links, and frame problem details as a description of the HTTP interface rather than a debugging channel — relevant to deciding what raw upstream payload a client forwards into an error-reporting sink like Sentry versus what it redacts.
  > Generators providing links to occurrence information are encouraged to avoid making implementation details such as a stack dump available through the HTTP interface, since this can expose sensitive details of the server implementation, its data, and so on.

### Источник #8 — качество: "primary"
Дата: 2024-12-06 (opened); closed 2025-03-18

- **[central]** The official amocrm/amocrm-api-php library hard-pins a transitive utility dependency (lcobucci/clock) so tightly in its own composer.json that a mere PHP minor-version upgrade (8.2 -> 8.3) made the package uninstallable: the pin "~2.0.0 || ^2.1.0" resolves at most to clock 2.3.0, which itself requires php ~8.1.0 || ~8.2.0. This is direct evidence that the official lib's dependency policy leaks its internal implementation choices (JWT/clock) into consumer runtime constraints — a concrete anti-pattern for a rewritten client, which should keep required deps minimal and unpinned to internals.
  > При установке текущей версии пакета (1.10.2) на версию php 8.3 возникает конфикт совместимостей с пакетом "lcobucci/clock". В composer.json стоит ограничение "~2.0.0 || ^2.1.0", последняя доступная версия под это ограничение 2.3.0. А эта версия в свою очередь ограничена php 8.2.

- **[central]** Maintainer responsiveness on the official library is measured in months, not days: issue #578 was opened 2024-12-06 and received exactly one comment — the two-word "добавлена поддержка" from maintainer bessudnov — posted 2025-03-18 at the same second the issue was closed, i.e. 102 days of total silence on a blocker that prevented installation on a current PHP release. Depending on this library for a project that must track PHP 8.4 is therefore a supply-chain risk, which strengthens the case for an in-house client.
  > добавлена поддержка

- **[supporting]** The official library declares a malformed/no-op PHP version constraint: "php": ">=7.1 || >=8.0" in v1.10.2's composer.json. The union is logically equivalent to >=7.1 and encodes no upper bound and no real 8.x intent, so Composer cannot protect consumers from PHP-version breakage — the library never actually declares which PHP versions it supports. A reference-architecture rewrite should instead declare a precise floor (e.g. "php": "^8.4") and test against it in CI.
  > "php": ">=7.1 || >=8.0",

- **[central]** The official library requires a concrete HTTP client implementation (guzzlehttp/guzzle 6.* || 7.*) as a hard runtime dependency rather than depending on the PSR-18/PSR-17 abstractions (psr/http-client, psr/http-factory) with a virtual provider, so consumers cannot swap transport and inherit Guzzle's own version/compatibility constraints. This is the exact coupling that Saloon/Sentry/KnpLabs-style SDKs avoid via HTTPlug/PSR-18 discovery.
  > "guzzlehttp/guzzle": "6.* || 7.*",

- **[supporting]** The official library pulls application-level, non-client concerns into a supposedly reusable SDK's hard require block — symfony/dotenv (3.* through 7.*) for env loading and nesbot/carbon for dates — inflating the transitive dependency surface of every consuming app. A framework-agnostic core should take configuration/credentials via injected value objects (matching the constraint that amo OAuth tokens live in the host project's DB) and use native DateTimeImmutable rather than requiring dotenv/Carbon.
  > "symfony/dotenv": "3.* || 4.* || 5.* || 6.* || 7.*",
  "fig/http-message-util": "1.*",
  "ramsey/uuid": "^3 || ^4",
  "lcobucci/jwt": "^3.4.6 || ^4.0.4",
  "lcobucci/clock": "~2.0.0 || ^2.1.0", <----
  "nesbot/carbon": "^2.52 || ^3.0.0",

### Источник #9 — качество: "primary"
Дата: not stated (official Sentry docs page, no publish/last-updated date exposed in content)

- **[central]** Sentry groups events into issues purely by fingerprint, and the default fingerprint is derived from the event's stacktrace, exception and message — so an API client that throws every new/unknown error type from the same code path (e.g. a single `UnknownAmoException` construction site) will collapse all of them into ONE issue by default, defeating a 'loud unknown error' signal unless the fingerprint is customized.
  > All events have a fingerprint. Events with the same fingerprint are grouped together into an issue. ... based on information available within the event such as `stacktrace`, `exception`, and `message`.

- **[central]** The PHP SDK's supported mechanism for custom grouping is `$scope->setFingerprint([...])` inside a `\Sentry\withScope()` callback wrapping `\Sentry\captureException($e)` — i.e. a library can set grouping per-throw without touching global config or a `before_send` hook.
  > \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($e) {
    $scope->setFingerprint(['example-exception-group']);
    \Sentry\captureException($e);
});

- **[central]** For API/RPC clients whose exceptions share an identical stack trace but differ by request parameters, Sentry documents the pattern of including the literal `{{ default }}` token plus dynamic attributes (function name, status code) in the fingerprint array, which splits the default group further instead of replacing it — directly applicable to keying amoCRM errors by endpoint + HTTP status/error code.
  > $scope->setFingerprint([
    '{{ default }}',
    $e->getFunctionName(),
    $e->getStatusCode(),
]);

- **[supporting]** The special value `{{ default }}` stands for the entire normally-generated grouping hash, so including it extends default grouping while omitting it overrides grouping entirely and merges otherwise-diverse stack traces into a single issue (e.g. `setFingerprint(['database-connection-error'])`) — the deliberate lever for collapsing noisy transient errors into one aggregate issue.
  > the entire normally generated grouping hash

### Источник #10 — качество: "primary"
Дата: no publication or last-updated date shown on the page; content references Stripe CLI v1.43.3+ and API version 2022-08-01, and reflects the current stripe-php v1x StripeClient API (fetched 2026-07-22)

- **[central]** Stripe's official PHP SDK ships a flat, closed taxonomy of ~9 typed exception classes keyed to error categories (CardException, InvalidRequestException, ApiConnectionException, AuthenticationException, PermissionException, RateLimitException, IdempotencyException, SignatureVerificationException), where the base class ApiErrorException doubles as the catch-all branch — meaning a newly introduced or unrecognized Stripe error type is structurally absorbed into the generic branch rather than raising a distinct 'unknown' signal.
  > } catch (\Stripe\Exception\ApiErrorException $e) {
    // All other Stripe API errors
    error_log("Status: " . $e->getHttpStatus() . ", Code: " . $e->getStripeCode() . ", Message: " . $e->getMessage() . ", Request ID: " . $e->getRequestId());

- **[central]** Stripe classifies transient vs permanent at the exception-type level and prescribes exponential backoff specifically for rate-limit errors, not for the whole error surface: RateLimitException is the one class whose documented remediation is a retry with exponentially increasing delay, while CardException / InvalidRequestException / AuthenticationException / PermissionException / IdempotencyException require a caller-side fix before any retry.
  > To handle rate-limiting automatically, retry the API call after a delay, and increase the delay exponentially if the error continues.

- **[central]** For connection errors and generic API errors, Stripe explicitly instructs clients to treat the call outcome as indeterminate rather than failed — i.e. the retry decision must be decoupled from the assumption that the write did not land, which is what makes idempotency keys (not blind retries) the correct mechanism.
  > Treat the result of the API call as indeterminate. That is, don't assume that it succeeded or that it failed.

- **[supporting]** Stripe's prescribed safe-retry protocol for mutating calls is: attach an idempotency key (max 255 characters) to every create/update, and on connection error repeat the request with the same key until a definitive success or failure is received; Stripe also offers an automatic-retry mode that generates the keys and repeats requests on the client's behalf.
  > When creating or updating an object, use an idempotency key. Then, if a connection error occurs, you can safely repeat the request without risk of creating a second object or performing the update twice. Repeat the request with the same idempotency key until you receive a clear success or failure.

- **[supporting]** Every Stripe error carries a structured, machine-readable payload accessible via $e->getError() — code, message, param, type, doc_url, request_log_url — plus a request ID retrievable via $e->getRequestId() (a string beginning with 'req'), and Stripe instructs clients to include that request ID in all error logs so an error can be correlated to a specific upstream request.
  > A unique identifier for the originating request that errored. The error response header includes this value (string beginning with `req`), but you can specify a print in your request, as shown in the code samples in this guide.

### Источник #11 — качество: "primary"
Дата: No explicit publish date — living repository (master branch, 3.x line, MIT, ~2.2k stars, actively maintained); content fetched 2026-07-22

- **[central]** KnpLabs/php-github-api deliberately has no hard dependency on a concrete HTTP client: transport is abstracted behind HTTPlug + PSR-18 (client) and PSR-17 (message factories), discovered at runtime, so the consumer picks Guzzle, Symfony HttpClient/Nyholm, or anything else. This is the reference pattern for a framework-agnostic amoCRM client core.
  > "We are decoupled from any HTTP messaging client with help by HTTPlug." — Requirements: "PHP >= 7.2", "PSR-17 HTTP factory implementation", "PSR-18 HTTP client implementation"; install is split into quick (`composer require knplabs/github-api:^3.0 guzzlehttp/guzzle:^7.0.1`) and advanced (`Client::createWithHttpClient(new HttplugClient())`).

- **[central]** All cross-cutting behaviour (error throwing, redirects, host injection, default headers, request history) is implemented as HTTPlug plugins pushed onto a middleware pipeline in the Client constructor, and users extend the client by writing a class that `implements Plugin` and calling `$httpBuilder->addPlugin(...)` — i.e. extensibility by composition, not by subclassing the client.
  > Constructor stack: "$builder->addPlugin(new GithubExceptionThrower());", "$builder->addPlugin(new Plugin\HistoryPlugin($this->responseHistory));", "$builder->addPlugin(new Plugin\RedirectPlugin());", "$builder->addPlugin(new Plugin\AddHostPlugin(Psr17FactoryDiscovery::findUriFactory()->createUri('https://api.github.com')));", "$builder->addPlugin(new Plugin\HeaderDefaultsPlugin([...]))". Customization doc: "You need to create a Plugin that modifies the request." / "class CustomUserAgentPlugin implements Plugin" / "$httpBuilder->addPlugin(new CustomUserAgentPlugin());"

- **[central]** The entire error taxonomy is centralized in one middleware plugin (GithubExceptionThrower) that maps HTTP status + response headers/payload to specific exception classes — but every unmapped error falls through to a bare generic `RuntimeException` carrying only the API message. That fallback is exactly the anti-pattern the amoCRM rewrite must avoid: a NEW/unknown error type is indistinguishable from an already-classified one, so it cannot be reported as 'unknown' to Sentry.
  > Mapping: 429 + exhausted remaining → "throw new ApiLimitExceedException($limit, $reset);"; 401 + `X-GitHub-OTP` → "throw new TwoFactorAuthenticationRequiredException($type);"; 403 + `X-GitHub-SSO` → "throw new SsoRequiredException($url);"; 400 → "throw new ErrorException(...)"; 422 + errors → "throw new ValidationFailedException(...)"; 502 → "throw new RuntimeException(implode(', ', $errors), 502);". Default branch: "throw new RuntimeException(isset($content['message']) ? $content['message'] : $content...);"

- **[central]** Pagination is a separate collaborator object (ResultPager), not baked into resource methods, and offers both an eager array API and a lazy Generator API; however payloads stay untyped associative arrays — the signatures return `array`/`Generator`, never typed DTOs, which is the concrete weakness a type-safety-first amoCRM client should reverse.
  > "public function fetch(AbstractApi $api, string $method, array $parameters = []): array", "public function fetchAll(AbstractApi $api, string $method, array $parameters = []): array", "public function fetchAllLazy(AbstractApi $api, string $method, array $parameters = []): Generator", plus cursor controls "hasNext()", "fetchNext()", "hasPrevious()", "fetchPrevious()"; usage is "new Github\ResultPager($client)" with "Parameters are passed to the API method via call_user_func_array".

- **[supporting]** HTTP caching is an opt-in, injectable PSR-6 pool (addCache/removeCache) implemented as another pipeline plugin, and it is explicitly framed as a rate-limit mitigation: conditional requests served from cache do not consume the API quota.
  > "public function addCache(CacheItemPoolInterface $cachePool, array $config = []): void" / "public function removeCache(): void"; docs: "the client will get cached responses if resources haven't changed since last time, **without** reaching the `X-Rate-Limit`."

### Источник #12 — качество: "primary"
Дата: 2026-07-21 (page footer: \"Page Last Generated: Jul 21, 2026\"; the guidelines are a continuously-updated living document, no original publication date given)

- **[central]** The Azure cross-language SDK canon makes transport abstraction a hard rule: the client's public surface must not expose the underlying HTTP/protocol implementation, which is the same motivation behind PSR-18/PSR-17 + HTTPlug decoupling in PHP SDKs (the caller injects a transport; the library never types against a concrete Guzzle client).
  > DO NOT leak the underlying protocol transport implementation details to the consumer.

- **[central]** Paginated collections must be exposed as lazy language-canonical iterators driven by continuation tokens, and the SDK is explicitly forbidden from offering a convenience method that materializes a whole paged collection into an array — i.e. a PHP client should return a Generator/lazy collection over amoCRM's page cursor, not an eagerly-fetched array, and should expose non-paginated list endpoints through the identical iterator API.
  > DO NOT expose an API to get a paginated collection into an array.

- **[central]** Idempotency for retried non-idempotent writes is specified as an explicit wire-level contract rather than left to the caller: the client generates a UUID repeatability request id plus a first-sent timestamp header before sending, so that a retry after a network failure is deduplicated server-side — the pattern PHP SDKs implement as Stripe-style `Idempotency-Key` in a retry middleware.
  > DO add the `Repeatability-Request-ID` (a uuid) and `Repeatability-First-Sent` (IMF fixdate) request headers before sending the HTTP request.

- **[supporting]** Service API version must be a first-class, consumer-selectable, enumerated client-construction parameter (a `ServiceVersion` enum whose values match the service's own version strings), and a stable library release may only target generally-available service versions — which maps directly onto keeping amoCRM's stable v4 surface pinned/documented while any preview or undocumented channel stays out of the stable default.
  > DO allow the consumer to explicitly select a supported service API version when instantiating the client.

- **[supporting]** This design page prescribes only narrow, status-code-specific error rules (a distinct error type for 412/409, and no error thrown on 304) and contains no general exception hierarchy, no unknown-error trap, and no logging/telemetry taxonomy — so it is not a usable source for the error-taxonomy portion of the design; the only transferable primitive it gives is the requirement that consumers can reach the complete raw response (status line, headers, body), which is the prerequisite for reporting an unrecognized error faithfully.
  > YOU SHOULD throw a distinct error when a `412 Precondition Failed` response or a `409 Conflict` response is received.

### Источник #13 — качество: "primary"
Дата: Latest release v3.1.0 dated 2026-03-17; README continuously maintained (requires PHP >= 8.3)

- **[central]** andrey-tech/amocrm-api-php implements client-side rate limiting as a hard-coded sleep-based throttle of 7 requests/second (static `AmoAPI::$throttle`, default 7), pausing the process until 1/throttle seconds have elapsed since the previous request — a purely in-process limiter with no distributed/cross-process coordination, which breaks down under Laravel queue workers running in parallel.
  > не более 7 запросов в секунду

- **[central]** The library exposes amoCRM's undocumented private browser AJAX channel through the SAME generic entry point as the public REST API — `AmoAPI::request(string $query, string $type = 'GET', array $params = [], string $subdomain = null): ?array` where `$type` may be `'AJAX'` (example given for `/ajax/contacts/list`). There is no separate namespace, client class, version pin or feature flag isolating the unstable channel from stable v4 — the only marker is a magic string argument.
  > Позволяет выполнить RAW запрос к API amoCRM

- **[central]** Error handling collapses to a single flat exception class `\AmoCRM\AmoAPIException` (plus a separate `TokenStorage\TokenStorageException`) with `getErrors(): array` / `getItems(): array` accessors — no exception hierarchy per HTTP status or error family, no transient-vs-permanent classification, and no Unknown-error trap, so a new/unrecognized amoCRM error type is indistinguishable from a known one at the catch site.
  > При возникновении ошибок выбрасывается исключение с объектом класса `\AmoCRM\AmoAPIException`

- **[supporting]** The library ships an amoCRM-specific optimistic-concurrency guard: per-entity-ID locking (`$lockEntityAttempts` default 10, `$lockEntityTimeout` default 1s, `$lockEntityDir` default 'lock/'), pluggable via any `symfony/lock` store (Redis/Memcached/PDO) and defaulting to FlockStore, specifically to avoid amoCRM rejecting concurrent updates of the same entity; the author explicitly documents that the default file-based mechanism only works within a single server. Transport underneath is raw cURL configured through mutable global static properties (timeouts, SSL cert path), with no PSR-18/PSR-17 abstraction.
  > Last modified date is older than in database

- **[supporting]** Pagination is lazy by design: bulk read methods (`getAllEntities()` / `getAllContacts()` etc.) return a `\Generator` rather than a materialized array, while the offset-based `getEntities()` uses `limit_rows` (max 500, recommended <= 250) and `limit_offset`; batch write methods cap at 500 entities per request with `*WithLimit()` variants to constrain batch size.
  > Возвращает объект типа \Generator для последующей выборки параметров сущностей

### Источник #14 — качество: "primary"
Дата: not stated (living documentation; retrieved 2026-07-22)

- **[central]** Saloon's fixture mechanism implements record-once/replay-many: the first test run performs a REAL API call, serializes the response to a JSON file on disk, and every subsequent run replays that file instead of hitting the network — refreshing a fixture is done by deleting the JSON file and re-running. This is the concrete pattern to copy for testing an amoCRM client against the live API without generating garbage on every run.
  > Saloon will make a real API call for the first time, and then store the API call inside of a file in your application, so that when you run the test again, the response will be used and not make a real API call.

- **[central]** Saloon ships explicit guards against unintended live traffic: `Config::preventStrayRequests()` throws an exception when a request is not mocked, and `MockConfig::throwOnMissingFixtures()` makes CI fail instead of silently recording a new fixture — i.e. the library treats 'accidental real request' as a loud error rather than a silent fallback.
  > To prevent accidental real requests, use `Config::preventStrayRequests()` (throws exceptions on unmocked calls). For CI environments, `MockConfig::throwOnMissingFixtures()` prevents unexpected fixture recording.

- **[supporting]** Recorded fixtures support declarative redaction of secrets by subclassing Saloon's `Fixture` class with `defineSensitiveHeaders()`, `defineSensitiveJsonParameters()` and `defineSensitiveRegexPatterns()`, BUT the very first recording is written un-redacted to disk — redaction only applies on replay. This is a concrete gotcha when recording against a real amoCRM account with OAuth tokens and customer PII.
  > The first time the request is made and the fixture is stored, the original response won't be redacted. Only future requests made with the fixture will use the redacted recording.

- **[supporting]** Saloon centralizes test doubles in a single `MockClient` that can be scoped locally to one request or installed globally, so an API call buried deep in application code can be intercepted without dependency-injection plumbing at every call site — the transport seam is the mock seam.
  > Saloon's testing starts with the **MockClient** class. This class can be instantiated locally for testing individual requests or used globally to test an API call nested deep in your application.

- **[supporting]** Saloon's documented testing philosophy explicitly scopes out contract-testing the vendor's API and scopes in failure-path testing of the consumer: you should not assert the upstream endpoint returns the right data, but you must assert how your code behaves when the API is down or returns 500 — an argument for investing in error-taxonomy tests over golden-response tests.
  > You should assume that the API you are integrating with has tests that ensure their API always returns what they tell you, so you don't have to worry about testing that specific endpoint returns the right data. You should, however, always test how your application handles the API response that comes back. What happens if the API is down or it returns a 500 error?

### Источник #15 — качество: "primary"
Дата: 2021-05-22 (opened; last comment 2021-06-11; issue still OPEN and unresolved as of 2026-07-22)

- **[central]** amoCRM's maintainer publicly committed (May 2021) to adding rate-limit error handling to the official PHP library — both a retry capability and a dedicated exception type — but the issue is still OPEN as of 2026-07-22 (last activity 2021-06-11) and only the exception half shipped: `AmoCRM\Exceptions\AmoCRMApiTooManyRequestsException` exists, while a GitHub code search over the repo returns 0 hits for `retry`, 0 for `Retry-After` and 0 for `middleware`. The official library therefore has NO retry/backoff/middleware layer at all — throttling, backoff and Retry-After handling are entirely the caller's problem, which is a hard requirement for any replacement client.
  > В библиотеке в ближайшее время добавим обработку ошибок, которые идут при превышении, скорее всего это будет возможность повторения запроса и отдельный тип Exception

- **[central]** amoCRM enforces two independent, simultaneous rate limits: 7 requests/sec bound to the pair (IP address + account subdomain), and 50 requests/sec per account shared across ALL integrations on that account. Because the 50 rps budget is consumed by third-party integrations too, a client-side token bucket can never guarantee compliance — the client must be designed to absorb 429 as a normal, expected outcome rather than an exceptional one.
  > Ограничение 7 запросов на IP адрес сохраняется, но вводится лимит 50 запросов в секунду на все интеграции

- **[central]** When an account's API gets blocked for excessive requests, amoCRM stops returning JSON and serves an HTML page in place of an API response (unblockable only via support), and the official library does not handle this case at all — a reporter who is himself a repo CONTRIBUTOR states it explicitly. This is direct evidence that the official client's error taxonomy has an unhandled non-JSON branch, i.e. exactly the 'unknown error type gets swallowed / crashes the parser' failure mode a new client must trap loudly.
  > Со временем апи аккаунта вообще блокировалось, вместо него отдавалась html страница (кстати, библиотека это вообще не обрабатывает), с возможностью разблокировать только через поддержку.

- **[central]** The rate limits apply to every amoCRM API version, explicitly including v2 and the undocumented v1 browser/private API — the maintainer answers this in response to a direct question 'ограничение только на v4 апи? Как обстоят дела с v2 и с недокументируемым апи (v1)?'. Consequence for the design: a library that hosts both the public v4 channel and the undocumented ajax channel must share ONE rate-limit/throttle budget per account across both channels, not per-channel limiters.
  > Лимиты будут действовать на все версии API на новых тарифах. Лимиты эти будем открывать постепенно, поэтому 1 июня точно никто с этими ограничениями не столкнется.

- **[supporting]** The limit documented on amoCRM's own recommendations page (https://www.amocrm.ru/developers/content/api/recommendations) at the time was 'no more than 7 requests per second' plus per-method caps on returned record counts — i.e. the public documentation lags/conflicts with the actual multi-tier enforcement (7 rps per IP+subdomain AND 50 rps per account on new tariffs) discussed by staff in this thread. Documented amo limits cannot be treated as authoritative by the client.
  > Предусмотрены механизмы ограничения активности работы с API – **не более 7 запросов в секунду**, а также в некоторых методах предусмотрены ограничения на количества возвращаемых за один запрос данных

### Источник #16 — качество: "primary"
Дата: No explicit publish date on the page; it is the living Laravel 13.x documentation (site footer shows \"© 2026 Laravel\"), retrieved 2026-07-22

- **[central]** Laravel officially recognizes the split between stand-alone (framework-agnostic) packages and Laravel-specific packages; a stand-alone package works with any PHP framework and is consumed in Laravel merely by requiring it in composer.json, validating a design where the amoCRM client core is framework-agnostic and only the bridge is Laravel-aware.
  > There are different types of packages. Some packages are stand-alone, meaning they work with any PHP framework. Carbon and Pest are examples of stand-alone packages. Any of these packages may be used with Laravel by requiring them in your `composer.json` file.

- **[central]** Laravel auto-registers a package's service providers and facades via the `extra.laravel.providers` / `extra.laravel.aliases` keys in the package's composer.json (package discovery), and consumers can opt out per-package with `dont-discover` (or `"*"` for all) — so the Laravel integration needs zero manual wiring in bootstrap/providers.php.
  > instead of requiring users to manually add your service provider to the list, you may define the provider in the `extra` section of your package's `composer.json` file so that it is automatically loaded by Laravel. In addition to service providers, you may also list any facades you would like to be registered

- **[supporting]** Extending `Illuminate\Support\ServiceProvider` forces the package to declare `illuminate/support` as its own Composer dependency — a concrete argument for keeping the ServiceProvider/facade in a separate (or optional/suggested) package so the framework-agnostic core carries no Laravel dependency.
  > A service provider extends the `Illuminate\Support\ServiceProvider` class and contains two methods: `register` and `boot`. The base `ServiceProvider` class is located in the `illuminate/support` Composer package, which you should add to your own package's dependencies.

- **[supporting]** `mergeConfigFrom` (called in `register`) merges only the first level of the configuration array, so nested config sections (e.g. `retry.backoff`, `rate_limit.buckets`, per-channel amo settings) will silently lose defaults if a user partially overrides them — nested defaults must be resolved in code, not relied upon from the merge.
  > This method only merges the first level of the configuration array. If your users partially define a multi-dimensional configuration array, the missing options will not be merged.

- **[supporting]** Package configuration files must not contain closures, because they cannot be serialized when the consuming app runs `config:cache` — meaning extension points like middleware, retry predicates or error classifiers must be configured as class-strings/DI bindings rather than callables in config.
  > You should not define closures in your configuration files. They cannot be serialized correctly when users execute the `config:cache` Artisan command.

### Источник #17 — качество: "primary"
Дата: not stated (docs.php-http.org \"latest\" branch; page is undated)

- **[central]** php-http/vcr-plugin implements record/replay as ordinary HTTPlug/PSR-18 middleware plugins (RecordPlugin + ReplayPlugin) inserted into the client's plugin chain, so fixtures are captured and served at the transport layer of the same client stack under test — no separate mocking framework and no manual response definition (contrast: php-http mock-client requires responses to be declared by hand).
  > record & replay HTTP responses ... Unlike the mock-client requiring manual response definition, this plugin "automatically" generates responses from previously recorded ones.

- **[central]** The plugin supports a hybrid live/fixture mode: ReplayPlugin by default throws when no recording matches a request, but passing `false` as its third constructor argument makes unmatched requests fall through to the real server — which, combined with RecordPlugin, gives the 'first run hits the live API and records, subsequent runs replay' workflow without changing test code.
  > By default, ReplayPlugin throws exceptions when requests cannot be replayed; set the third constructor argument to `false` to allow fallthrough to actual servers.

- **[supporting]** Plugin ordering in the chain is load-bearing and constrained: when both VCR plugins are used, ReplayPlugin must be registered before RecordPlugin, otherwise the behaviour is wrong — a concrete example that a middleware-pipeline SDK must document/enforce plugin order rather than treat plugins as commutative.
  > The `ReplayPlugin` **must always** come first

- **[supporting]** Fixture identity is computed by a pluggable NamingStrategyInterface; the default PathNamingStrategy turns a request into a deterministic filesystem-safe name (e.g. GET https://example.org/my-path → `example.org_GET_my-path`) and is configured via `hash_headers` (headers such as Authorization that make a request unique, hashed into the signature) and `hash_body_methods` (methods whose body affects identity, defaulting to PUT, POST, PATCH).
  > hash_headers: Headers making requests unique (e.g. 'Authorization'), with names and content hashed for signatures; hash_body_methods: Request methods where body content creates distinction (default: PUT, POST, PATCH)

- **[supporting]** Two recorder backends ship with the plugin and differ in persistence lifetime: FilesystemRecorder writes fixtures to disk (Symfony Filesystem + Guzzle PSR-7 serialization), while InMemoryRecorder keeps them only for the current process; custom backends require implementing both RecorderInterface and PlayerInterface. The package is installed as a dev-only dependency.
  > InMemoryRecorder: Stores responses in memory; "Response will be lost at the end of the running process" ... composer require --dev php-http/vcr-plugin

### Источник #18 — качество: "primary"
Дата: not specified (living official docs; version references up to Infection 0.34.0 — `--only-covered` removed in 0.31.0, `--filter` deprecated since 0.34.0)

- **[supporting]** Infection exposes two CI quality gates for mutation testing — `--min-msi` (Mutation Score Indicator threshold, %) and `--min-covered-msi` (Covered Code MSI threshold, %) — explicitly intended for automated test-quality enforcement on a CI server, so a library's CI can fail the build when mutation score drops below a chosen bar.
  > --min-msi: "This is a minimum threshold of Mutation Score Indicator (MSI) in percentage. Can be used with CI server to automatically control tests quality." — --min-covered-msi: "This is a minimum threshold of Covered Code Mutation Score Indicator (MSI) in percentage. Can be used with CI server to automatically control tests quality."

- **[supporting]** `--only-covered` no longer exists: it was removed in Infection 0.31.0 and replaced by the inverse flag `--with-uncovered` ("Allow mutation of code not covered by tests"), i.e. modern Infection mutates only covered code by default and any recommendation to pass `--only-covered` is stale.
  > --only-covered: "This option was removed in Infection 0.31.0, use `--with-uncovered` instead" … --with-uncovered: "Allow mutation of code not covered by tests."

- **[supporting]** Infection supports running mutants in parallel via `--threads`/`-j`, which the docs claim dramatically reduces mutation-run wall time — the standard lever for keeping mutation testing viable in a library's CI pipeline.
  > "If you want to run tests for mutated code in parallel, set this to something > 1. It will **dramatically speed up** mutation process."

- **[supporting]** Infection can restrict mutation to changed code only — `--git-diff-filter` filters mutated files via `git diff --diff-filter` (e.g. `AM` = added and modified), and `--git-diff-lines` mutates only touched lines by diffing the current branch against `master` by default — enabling per-PR incremental mutation gates instead of full-repo runs.
  > --git-diff-filter: "Allows filtering files to mutate by using `git diff` with `--diff-filter` option. Sensible values are: `AM` - added and modified files. `A` - only added files." — --git-diff-lines: "Allows mutating only touched **lines** of code. Under the hood, this option mutates only added and changed files, comparing your current branch with `master` branch by default."

- **[tangential]** CI runtime cost can be cut further by reusing an existing coverage report (`--coverage` + `--skip-initial-tests` skips re-running the whole suite) and by `--map-source-class-to-test`; `--ignore-msi-with-no-mutations` forces a zero exit code when no mutations are generated, and `--logger-github` emits GitHub Actions annotations for escaped mutants directly in the pull request.
  > --skip-initial-tests: "If you have already run the test suite to ensure it is passing, and you are providing coverage using `--coverage` then you can use this option to increase performance by not running the entire test suite again." — --ignore-msi-with-no-mutations: "Ignore MSI violations when no mutations are generated. This will force Infection to return a zero exit code even when the required MSI is not reached." — --logger-github: "Supposed to be used only with GitHub Actions. This logger prints GitHub Annotation warnings for escaped Mutants right in the Pull Request."

### Источник #19 — качество: "primary"
Дата: not stated on page

- **[supporting]** amoCRM ждёт ответ от webhook-обработчика не более 2 секунд; всё, что дольше, считается недоставленным. Для PHP-клиента/приёмника это жёсткий бюджет: обработку вебхука нельзя делать синхронно (обращения к amo API, БД, Sentry) — только быстрый ACK + очередь.
  > Наш сервис ожидает ответ от хука не более 2 секунд.

- **[supporting]** Успешной доставкой вебхука amoCRM считает ЛЮБОЙ HTTP-код ответа в диапазоне 100–299; всё остальное (0–99, 300+, 499, 5xx) — невалидный отклик, запускающий ретраи. Это значит, что редирект (3xx) на endpoint вебхука молча ломает доставку, а не «работает через Location».
  > При отправке запроса информация считается принятой, если в заголовке http ответа будет возвращён код от 100 до 299

- **[supporting]** У amoCRM своя фиксированная лестница ретраев вебхуков (до 5 попыток) с разными интервалами и разными триггерными кодами: 5 мин / 15 мин для кодов 0–99 и 300+, и 15 мин / 1 час для кодов 499 и 500–599. То есть backoff на стороне amo уже задан и не настраивается — клиентская сторона может влиять только тем, какой код вернуть.
  > The document provides a table with 5 attempts showing intervals (5 min, 15 min, 15 min, 1 hour) and trigger codes (0-99 and 300+, or 499 and 500-599).

- **[central]** amoCRM автоматически отключает вебхук, если за последние 2 часа получено более 100 невалидных откликов и последний отклик на момент проверки тоже невалиден. Это прямой аргумент за «громкий» сигнал в Sentry на деградацию приёмника вебхуков: тихая серия 5xx приводит не к деградации, а к полному молчаливому отключению канала.
  > Ваш хук может быть отключен при следующем условии: За последние 2 часа было получено более 100 невалидных откликов и последний хук на момент проверки так же является невалидным

- **[supporting]** Вебхуки доставляются как POST в формате x-www-form-urlencoded с JSON-телом вида {"entity":{"action":{"0":{...}}}} (для delete — {"entity":{"action":"id"}}), при этом страница не даёт никаких гарантий порядка или ровно-однократной доставки. Следовательно типизированный DTO-слой вебхуков в библиотеке должен парсить form-encoded payload и быть идемпотентным по entity id.
  > WebHooks are sent as x-www-form-urlencoded POST requests to third-party applications. ... No explicit guarantee or ordering assurance statement is present in the documentation.

### Источник #20 — качество: "primary"
Дата: No explicit publish date on the docs site; content is versioned by library release (pages fetched are the current v3/v4 docs, with v1/v2 archives in the sitemap and an \"Upgrading from v3 to v4\" guide present). Retrieved 2026-07-22.

- **[central]** Saloon ships a status-code-mapped exception hierarchy (SaloonException → FatalRequestException for connection errors, RequestException → ServerException/ClientException with per-status leaves such as UnauthorizedException 401, PaymentRequiredException 402, TooManyRequestsException 429, ServiceUnavailableException 503, GatewayTimeoutException 504), yet by default it does NOT throw on a failed synchronous request — throwing is opt-in via the AlwaysThrowOnErrors trait, and classification is overridable per-connector/per-request via hasRequestFailed() and getRequestException().
  > SaloonException ├── FatalRequestException (Connection Errors) └── RequestException (Request Errors) ├── ServerException (5xx) … └── ClientException (4xx) … / "By default, Saloon will not throw an exception if a synchronous request fails." / "Add the `AlwaysThrowOnErrors` trait on your connector, and then every request that fails will throw an exception." / "Priority is given to the request when you extend the `getRequestException` method on both your connector and request."

- **[central]** Saloon exposes retry policy as declarative properties on the connector/request rather than call-site code: $tries = 3, $retryInterval = 1000 (ms), $useExponentialBackoff = true (doubles the interval after each failed attempt), $throwOnMaxTries = false (return the last response instead of throwing), plus a handleRetry() hook for custom retry predicates; the older on-the-fly sendAndRetry() was deprecated in v3.6.4.
  > "public ?int $tries = 3;" … "public ?int $retryInterval = 1000;" … "public ?bool $useExponentialBackoff = true;" - Doubles the retry interval after each unsuccessful attempt. … "public ?bool $throwOnMaxTries = false;" … "You can overwrite the `handleRetry` method on the connector or request" … sendAndRetry: "This method has been deprecated since Saloon v3.6.4"

- **[central]** Saloon's built-in record/replay testing lets the first test run hit the real API and persists the response as a JSON fixture (default path tests/Fixtures/Saloon, overridable via MockConfig::setFixturePath) that is replayed on subsequent runs, with sensitive-data redaction hooks defineSensitiveHeaders(), defineSensitiveJsonParameters() and defineSensitiveRegexPatterns() on a custom fixture class.
  > "Saloon supports recording requests in your tests. This works by allowing the test to make a real API call the first time and then on subsequent API calls, the same response will be used." … "You can customise the fixture location by using the `setFixturePath` method on the `MockConfig` class."

- **[central]** Saloon's extensibility is composition-based rather than inheritance-based: plugins are traits with a convention-named boot method (trait HasLogging → public function bootHasLogging(PendingRequest $pendingRequest): void) invoked during the request lifecycle, layered on a middleware pipeline with middleware()->onRequest()/onResponse(), unique pipe names, a PipeOrder enum (FIRST/LAST) for ordering, global middleware via Config::globalMiddleware(), and short-circuiting by returning a FakeResponse from request middleware.
  > "Plugins are traits that can be added to either a request or a connector and have a special 'boot' method which is invoked during the request lifecycle." … "You can use the third argument to specify an order. This expects a `PipeOrder` enum and allows you to choose either `FIRST` or `LAST`." … "If this fake response is present before Saloon sends the request, it won't send the request to the sender, instead, Saloon will use the `FakeResponse`."

- **[supporting]** Saloon's paginators are lazy and hold only one page in memory at a time, supporting per-item iteration across pages via items(), a Laravel-compatible LazyCollection via collect(), and concurrent fetching via async()/pool(concurrency: 10); three built-in strategies are provided (paged, limit/offset, cursor).
  > "They are also memory efficient, so they only keep one page in memory at a time, this means you can use Saloon's paginators to iterate through **thousands of pages and millions of results without running out of memory**." … "This method will return a `LazyCollection` class. This class extends Laravel's base `Collection` class but works with custom iterators and keeps memory consumption low."

### Источник #21 — качество: "primary"
Дата: не указана на странице (официальная документация amoCRM без даты публикации/обновления)

- **[central]** amoCRM v4 GET-коллекции ограничены 250 сущностями на запрос (параметр `limit`), плюс параметр `page` — то есть клиентский пагинатор/ленивая коллекция обязан уметь чанковать выборку по 250 и не может тянуть весь список одним вызовом.
  > Количество возвращаемых сущностей за один запрос (Максимум – 250)

- **[central]** Ответы коллекций приходят в HAL-конверте: `_page`, `_embedded` (массив сущностей) и `_links` с ключами `self`, `next`, `first`, `prev` — значит корректная реализация Paginator должна идти по link-relation `next` из ответа, а не вычислять номера страниц арифметикой на клиенте.
  > "_links" with: `self`, `next`, `first`, `prev` properties documented in example responses; "_page": Present in response structure

- **[central]** Батч-эндпоинт POST /api/v4/leads/complex жёстко ограничен 50 сделками за запрос — библиотека обязана сама разбивать входной набор на чанки ≤50 и агрегировать результаты/ошибки по чанкам, иначе получит 400.
  > За один запрос можно передать не более 50 сделок.

- **[central]** Ошибки amoCRM v4 отдаются с `Content-Type: application/problem+json`, т.е. в формате RFC 7807/9457 Problem Details — таксономия исключений клиента может (и должна) типизироваться поверх полей problem-документа, а не поверх голого HTTP-кода.
  > Header specification: "Content-Type: application/problem+json"

- **[central]** Документированный набор статусов для leads-api узок и семантически нестандартен: 200 (успех), 204 («сделки с переданным ID не существует» — вместо 404), 400 (некорректные данные, детали в теле), 401 (не авторизован), 402 (аккаунт не оплачен). Это подтверждает необходимость Unknown-ловушки: любой код вне этого списка — новый/недокументированный класс ошибки, который нельзя молча маппить в generic-исключение.
  > 200 – Success; 204 – Deal with specified ID does not exist; 401 – User not authorized; 402 – Account not paid; 400 – Incorrect data transmitted / "Переданы некорректные данные. Подробности доступны в теле ответа"

### Источник #22 — качество: "primary"
Дата: No explicit publish date — living document on the Symfony docs "current" branch (retrieved 2026-07-22)

- **[central]** Symfony's BC promise explicitly carves out two escape hatches — code tagged @internal and code tagged @experimental are excluded from the guarantee — giving a documented, machine-greppable mechanism for shipping an unstable/undocumented surface (e.g. an amoCRM private ajax channel) inside the same package as a stable API without contaminating its BC guarantees.
  > Experimental Features and code marked with the `@internal` tags are excluded from our Backward Compatibility promise.

- **[central]** Symfony binds itself to Semantic Versioning at the branch level: only major releases may break BC, minors may add features but must not break the existing API, and replacement is done by keeping the old API working plus a deprecation rather than removal — the concrete BC/semver policy a library like the amoCRM client can adopt verbatim.
  > only major releases (such as 5.0, 6.0 etc.) are allowed to break backward compatibility. Minor releases (such as 5.1, 5.2 etc.) may introduce new features, but must do so without breaking the existing API of that release branch.

- **[supporting]** The @experimental tag is time-boxed, not an indefinite disclaimer: a feature may stay experimental for only one minor version (extendable by one more case-by-case), can never be introduced in an LTS release, and while experimental the CHANGELOG must document every BC break and its upgrade path — a falsifiable rule for how long an unstable channel may remain unstable.
  > A feature can stay experimental for only one minor version and can never be introduced in an LTS version (the core team may extend the experimental period by one more minor version on a case-by-case basis). While a feature is experimental, the `CHANGELOG` must explain any backward incompatible change and how to upgrade.

- **[supporting]** Symfony's per-use-case BC tables define extensibility asymmetrically: consumers get a guarantee for extending a class, overriding public/protected methods and reading protected properties, but explicitly get NO guarantee when adding a new method or new property to a subclass, nor when reaching private members via Reflection — evidence that inheritance-based extension points must be paired with explicit named-collision and reflection exclusions.
  > Add a new property | No … Add a new method | No … Call private method via Reflection | No … Access private property via Reflection | No

- **[supporting]** Symfony documents a concrete BC-safe recipe for evolving public method signatures inside a minor release: declare the new argument as a comment in the signature, document it in PHPDoc, read it via func_num_args()/func_get_arg(), trigger_deprecation() when the default changes behavior, and only uncomment the real parameter in the next major — a reusable pattern for growing a client's API without a major bump.
  > public function say(string $text, /* bool $stripWhitespace = true */): void … $stripWhitespace = 2 <= \func_num_args() ? func_get_arg(1) : false; … trigger_deprecation('symfony/COMPONENT', 'X.Y', 'Not passing the "bool $stripWhitespace" argument explicitly is deprecated...');

### Источник #23 — качество: "primary"
Дата: 2026-07-01 (последний релиз v1.17.0; README — живой документ, репозиторий активен)

- **[central]** Транспорт в официальной библиотеке жёстко прибит к Guzzle 6/7: `guzzlehttp/guzzle` — прямая runtime-зависимость, а `psr/http-client` (PSR-18), `psr/http-factory` (PSR-17) и `php-http/httplug` в require отсутствуют. Подменить HTTP-клиент (на Symfony HttpClient, на mock/record-replay-транспорт для тестов, на клиент с middleware-ретраями) без хака невозможно — это прямая противоположность паттерну Stripe/Saloon/Sentry/KnpLabs с transport-абстракцией.
  > "require": { "php": ">=7.1 || >=8.0", "ext-fileinfo": "*", "ext-json": "*", "amocrm/oauth2-amocrm": "^3.0", "fig/http-message-util": "1.*", "guzzlehttp/guzzle": "6.* || 7.*", "lcobucci/clock": "1.1.0 ||^2.0.0", "lcobucci/jwt": "^3.4.6 || ^4.0.4 || ^5.0", "nesbot/carbon": "^2.72.6 || ^3.8.4", "ramsey/uuid": "^3 || ^4", "symfony/dotenv": "3.* || 4.* || 5.* || 6.* || 7.*" }

- **[central]** Таксономия ошибок построена ровно наоборот тому, что требует «громкий неизвестный тип»: выделенные классы получают только 401 (AmoCRMoAuthApiException), 204 (AmoCRMApiNoContentException) и 400 с непустым `validation-errors` (AmoCRMApiErrorResponseException). ВСЁ остальное — 402, 403, 404, 409, 429, 500, 502, 503 и любой будущий код — схлопывается в **базовый** класс `AmoCRMApiException` с сообщением "Invalid http status". Т.е. catch-all — это корень иерархии, а не отдельный Unknown-тип: `catch (AmoCRMApiException)` ловит одновременно и известное, и неизвестное, различить их можно только руками по `getErrorCode()`. Никакой классификации transient vs permanent в библиотеке нет.
  > if (!in_array((int)$response->getStatusCode(), self::SUCCESS_STATUSES, true)) {
            $exception = new AmoCRMApiException(
                "Invalid http status",
                $response->getStatusCode(),
                $this->getLastRequestInfo(),
                isset($decodedBody['detail']) ? $decodedBody['detail'] : ''
            );

            if (
                $response->getStatusCode() === StatusCodeInterface::STATUS_BAD_REQUEST
                && !empty($decodedBody['validation-errors'])
            ) {
                $exception = new AmoCRMApiErrorResponseException(

- **[central]** Обработка 429 в библиотеке фактически сломана, а ретраев/бэкоффа нет вообще. `AmoCRMApiTooManyRequestsException` бросается единственный раз — внутри ветки «тело ответа не распарсилось как JSON» (`!$decodedBody && !empty($bodyContents)`); если amoCRM отдаст 429 с валидным JSON-телом, исключение будет не TooManyRequests, а generic `AmoCRMApiException("Invalid http status")`. Grep по всему request-слою (785 строк `AmoCRMApiRequest.php`) не находит ни одного вхождения retry/backoff/sleep/usleep/Retry-After; README также не содержит ни слова про ретраи, rate limiting и 429.
  > if (
            $response->getStatusCode() !== StatusCodeInterface::STATUS_ACCEPTED
            && !$decodedBody
            && !empty($bodyContents)
        ) {
            if ($response->getStatusCode() === StatusCodeInterface::STATUS_TOO_MANY_REQUESTS) {
                throw new AmoCRMApiTooManyRequestsException(
                    "Too many requests",
                    $response->getStatusCode(),
                    $this->getLastRequestInfo()
                );
            }

- **[supporting]** В библиотеке нет middleware-pipeline: `AmoCRMApiRequest.php` — god-класс на 785 строк, где для каждого HTTP-глагола (get/post/patch/delete/postFile/…) один и тот же блок из трёх catch'ей скопирован дословно — на строках 228-233, 316-321, 391-396, 465-470, 544-549. Любая сквозная политика (ретрай, rate-limit, идемпотентность, логирование, Sentry-репорт) требует правки пяти+ мест или наследования, а не одной точки расширения. Единственный официальный extension point — колбэк `setCheckHttpStatusCallback()` (с v1.9.0), который умеет только «проглотить» ошибку, вернув `true` из проверки статуса.
  > } catch (ConnectException $e) {
            throw new AmoCRMApiConnectExceptionException($e->getMessage(), $e->getCode(), $this->getLastRequestInfo());
        } catch (TooManyRedirectsException $e) {
            throw new AmoCRMApiTooManyRedirectsException($e->getMessage(), $e->getCode(), $this->getLastRequestInfo());
        } catch (GuzzleException $e) {
            throw new AmoCRMApiHttpClientException($e->getMessage(), $e->getCode(), $this->getLastRequestInfo());

- **[supporting]** Планка качества в проекте — нижняя: в `require-dev` нет ни phpstan, ни psalm, ни infection, ни PHPUnit-моков транспорта — только phpunit, phpcs и roave/security-advisories; `composer.json scripts` сводятся к style:check + test. Плюс библиотека до сих пор заявляет минимум PHP 7.1 (константа в README: «Для работы библиотеки требуется PHP версии не ниже 7.1»), причём констрейнт `">=7.1 || >=8.0"` избыточен/бессмыслен (`>=7.1` уже покрывает 8.x) — это и объясняет ассоциативные массивы вместо типизированных DTO, отсутствие enum'ов, readonly и property promotion. Актуальный релиз на момент проверки — 1.17.0 от 2026-07-01, т.е. библиотека живая, но архитектурно заморожена на PHP 7.1.
  > "require-dev": { "phpunit/phpunit": "7.* || 8.* || 9.*", "roave/security-advisories": "dev-latest", "squizlabs/php_codesniffer": "^3.5.2" }

