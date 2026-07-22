<?php

namespace mttzzz\AmoClient;

use Exception;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Exceptions\AmoPaymentRequiredException;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\Helpers\Widget;
use mttzzz\AmoClient\Models\Account;
use mttzzz\AmoClient\Models\Call;
use mttzzz\AmoClient\Models\Catalog;
use mttzzz\AmoClient\Models\Company;
use mttzzz\AmoClient\Models\Contact;
use mttzzz\AmoClient\Models\Customer;
use mttzzz\AmoClient\Models\Event;
use mttzzz\AmoClient\Models\Lead;
use mttzzz\AmoClient\Models\Pipeline;
use mttzzz\AmoClient\Models\ShortLink;
use mttzzz\AmoClient\Models\Source;
use mttzzz\AmoClient\Models\Task;
use mttzzz\AmoClient\Models\Unsorted;
use mttzzz\AmoClient\Models\User;
use mttzzz\AmoClient\Models\Webhook;
use stdClass;
use Throwable;

class AmoClientOctane
{
    public Lead $leads;

    public Contact $contacts;

    public Company $companies;

    public Source $sources;

    public Catalog $catalogs;

    public Customer $customers;

    public Account $account;

    public User $users;

    public Pipeline $pipelines;

    public Task $tasks;

    public Event $events;

    public Ajax $ajax;

    /**
     * Удаление сущностей: единственная реализация таблицы «тип → механизм».
     * Публична, потому что снос по паре (тип, id) — штатная нужда свипа,
     * у которого на руках реестр, а не готовая коллекция.
     */
    public Deleter $deleter;

    public Unsorted $unsorted;

    public Call $calls;

    public Webhook $webhooks;

    public ShortLink $shortLinks;

    public PendingRequest $http;

    public int $accountId;

    public string $clientId = '00a140c1-7c52-4563-8b36-03f23754d255';

    public function __construct(int $aId, ?string $clientId = null, ?string $proxy = null)
    {

        if ($clientId) {
            $this->clientId = $clientId;
        }

        // Один запрос: account + widget. account_custom_fields подгружаются
        // лениво через LazyCustomFields — только если caller дойдёт до
        // entity()/one()/find() у Lead/Customer/Contact/Company.
        $mainResult = DB::connection('octane')
            ->select('
                SELECT
                    a.id,
                    a.subdomain,
                    a.domain,
                    a.contact_phone_field_id,
                    a.contact_email_field_id,
                    aw.access_token,
                    w.name as widget_name
                FROM accounts a
                LEFT JOIN widgets w ON w.client_id = ?
                LEFT JOIN account_widget aw ON a.id = aw.account_id AND aw.active = true AND aw.widget_id = w.id
                WHERE a.id = ?
            ', [$this->clientId, $aId]);

        if (empty($mainResult)) {
            throw new Exception("Account ($aId) not found");
        }

        $accountData = $mainResult[0];

        if (! $accountData instanceof stdClass) {
            throw new Exception("Account ($aId) not found");
        }

        // Проверяем, что виджет установлен
        if (! $accountData->access_token) {
            /** @var Widget|null $widget */
            $widget = DB::connection('octane')->table('widgets')->where('client_id', $this->clientId)->first();
            if (! $widget) {
                throw new Exception("Widget ($this->clientId) not found");
            }
            // @codeCoverageIgnoreStart
            $subdomain = is_scalar($accountData->subdomain) ? (string) $accountData->subdomain : '';
            throw new Exception("Widget ($widget->name) doesn't installed in account ($subdomain)");
            // @codeCoverageIgnoreEnd
        }

        $octaneAccount = $this->convertToOctaneAccount($accountData);

        $lazyCf = new LazyCustomFields($aId);

        // Остальные параметры из конфига, если они есть.
        $timeout = self::configInt('amoclient.timeout', 60);
        $connectTimeout = self::configInt('amoclient.connectTimeout', 10);
        $retries = self::configInt('amoclient.retries', 3);
        $retryDelay = self::configInt('amoclient.retryDelay', 2000);
        $verify = Config::get('amoclient.verify');

        $baseUrl = $octaneAccount->domain === 'com'
            ? "https://{$octaneAccount->subdomain}.kommo.com/api/v4"
            : "https://{$octaneAccount->subdomain}.amocrm.{$octaneAccount->domain}/api/v4";

        // Собираем уникальные прокси в порядке приоритета
        /** @var array<int, string|null> $proxies */
        $proxies = [];
        if ($proxy) {
            $proxies[] = $proxy;
        }
        if (config('app.proxy') && ! in_array(config('app.proxy'), $proxies)) {
            $proxies[] = config('app.proxy');
        }
        if (config('app.secondProxy') && ! in_array(config('app.secondProxy'), $proxies)) {
            $proxies[] = config('app.secondProxy');
        }
        // Добавляем null как последний вариант (без прокси)
        $proxies[] = null;

        $proxyIndex = 0;
        $maxProxyAttempts = count($proxies);
        $currentToken = $octaneAccount->access_token;

        $http = Http::withToken($currentToken)
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->withOptions(['verify' => $verify])
            ->retry($retries * $maxProxyAttempts, $retryDelay, function (Throwable $exception, PendingRequest $request) use (&$proxyIndex, $proxies, $maxProxyAttempts, $aId, &$currentToken) {
                if ($exception instanceof RequestException) {
                    $status = $exception->response->status();

                    /*
                     * 402 Payment Required: не ретраим и не отдаём голый
                     * RequestException — бросаем типизированную ошибку со
                     * снапшотом octane accounts.payed, чтобы потребитель отличил
                     * реальную неоплату от ночного флапа амо (окно ротации
                     * токенов ~00:05). Ретраить внутри запроса бессмысленно:
                     * окна живут минуты — это забота очереди
                     * (Queue\RetriesTransientAmoErrors).
                     */
                    if ($status === 402) {
                        throw AmoPaymentRequiredException::fromRequestException($exception, $aId);
                    }

                    /*
                     * 401: гонка с ночной ротацией — запрос ушёл со старым
                     * токеном, октан только что записал новый (амо отзывает
                     * старый при refresh). Перечитываем токен из octane:
                     * изменился → повтор со свежим; не изменился → реальная
                     * auth-проблема, не ретраим.
                     */
                    if ($status === 401) {
                        $freshToken = DB::connection('octane')->table('account_widget')
                            ->join('widgets', 'widgets.id', '=', 'account_widget.widget_id')
                            ->where('widgets.client_id', $this->clientId)
                            ->where('account_widget.account_id', $aId)
                            ->where('account_widget.active', true)
                            ->value('access_token');

                        if (is_string($freshToken) && $freshToken !== '' && $freshToken !== $currentToken) {
                            $currentToken = $freshToken;
                            $request->withToken($freshToken);

                            return true;
                        }

                        return false;
                    }
                }

                /*
                 * Смена прокси лечит НЕДОСТИЖИМЫЙ СЕТЕВОЙ ПУТЬ и только его.
                 *
                 * Если соединение до амо установилось, отказ пришёл от самого амо —
                 * и повтор через другой роутер уходит в тот же бэкенд. Раньше сюда
                 * попадали два случая, в которых ротация не лечила ничего, а платили
                 * за неё временем:
                 *
                 *  - 5xx: у ответа есть HTTP-статус, значит запрос дошёл. Failover
                 *    обязан жить НИЖЕ уровня, на котором статус существует
                 *    (решение по архитектуре 4.0, п.5).
                 *  - read-таймаут: соединение открыто, амо принял запрос и молчит.
                 *    Это самый дорогой случай: каждый лишний заход стоит полного
                 *    `timeout`, то есть ротация умножала простой на размер пула,
                 *    ничего не выигрывая. У потребителя с очередью это съедало
                 *    таймаут воркера и убивало процесс раньше, чем джоба успевала
                 *    сделать release, — то есть лестница ретраев вырождалась в
                 *    фиксированный retry_after.
                 *
                 * Оба теперь не ротируют. Повторную попытку по ним делает очередь
                 * потребителя (Queue\RetriesTransientAmoErrors) — там пауза измеряется
                 * минутами, а не задержкой внутри одного запроса.
                 */
                $shouldRetry = $exception instanceof HttpClientConnectionException
                    && self::pathIsUnreachable($exception);

                if ($shouldRetry && $proxyIndex < $maxProxyAttempts - 1) {
                    $proxyIndex++;
                    $newProxy = $proxies[$proxyIndex] ?? null;
                    if ($newProxy) {
                        $request->withOptions(['proxy' => $newProxy]);
                    } else {
                        $request->withOptions(['proxy' => null]);
                    }

                    return true;
                }

                return false;
            })
            ->baseUrl($baseUrl);

        // Устанавливаем первую прокси (если есть)
        if ($proxies[0]) {
            $http = $http->withOptions(['proxy' => $proxies[0]]);
        }
        // @codeCoverageIgnoreEnd
        $this->accountId = $aId;
        $this->http = $http;
        /*
         * ajax и deleter собираются ДО моделей: приватные роуты удаления живут
         * на корне домена, а не на /api/v4, поэтому модели получают канал
         * готовым, а не конструируют его сами.
         */
        $this->ajax = new Ajax($octaneAccount, $http);
        $this->deleter = new Deleter($this->ajax, $http);
        $this->account = new Account($http, $aId);
        $this->leads = new Lead($http, $lazyCf, $this->deleter);
        $this->customers = new Customer($http, $lazyCf, $this->deleter);
        $this->contacts = new Contact($http, $octaneAccount, $lazyCf, $this->deleter);
        $this->companies = new Company($http, $octaneAccount, $lazyCf, $this->deleter);
        $this->catalogs = new Catalog($http, $this->deleter);
        $this->users = new User($http);
        $this->pipelines = new Pipeline($http, $this->deleter);
        $this->tasks = new Task($http, $this->deleter);
        $this->events = new Event($http);
        $this->unsorted = new Unsorted($http);
        $this->calls = new Call($http, $this->deleter);
        $this->webhooks = new Webhook($http, $this->deleter);
        $this->shortLinks = new ShortLink($http);
        $this->sources = new Source($http, $this->deleter);
    }

    private function convertToOctaneAccount(stdClass $data): OctaneAccount
    {
        $octaneAccount = new OctaneAccount;
        $octaneAccount->id = is_numeric($data->id) ? (int) $data->id : 0;
        $octaneAccount->subdomain = is_scalar($data->subdomain) ? (string) $data->subdomain : '';
        $octaneAccount->domain = is_scalar($data->domain) ? (string) $data->domain : '';
        $octaneAccount->access_token = is_scalar($data->access_token) ? (string) $data->access_token : '';
        $octaneAccount->contact_phone_field_id = is_numeric($data->contact_phone_field_id ?? null) ? (int) $data->contact_phone_field_id : 0;
        $octaneAccount->contact_email_field_id = is_numeric($data->contact_email_field_id ?? null) ? (int) $data->contact_email_field_id : 0;

        return $octaneAccount;
    }

    /**
     * Читает числовой конфиг amoclient.* с фолбэком на дефолт: значения из
     * Config::get() приходят как mixed, поэтому гардим is_numeric() перед
     * кастом (level: max запрещает cast mixed → int напрямую).
     */
    private static function configInt(string $key, int $default): int
    {
        $value = Config::get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Установить соединение не удалось вовсе — то есть сетевой путь недостижим.
     *
     * Guzzle кладёт в `ConnectException` и невозможность соединиться, и таймаут
     * ожидания ответа: обе фазы приходят одним типом, поэтому различаем по факту,
     * а не по классу. Различитель — `connect_time` из handler-контекста curl:
     * ноль означает, что рукопожатие не состоялось (мёртвый маршрут, DNS, отказ
     * в соединении) и другой прокси имеет шанс помочь; положительное значение
     * означает, что соединение было установлено и молчал уже сам амо — менять
     * маршрут бессмысленно.
     *
     * Когда контекста нет (например, стаб в тестах потребителя), считаем путь
     * недостижимым: это прежнее поведение, и ошибиться в эту сторону дешевле —
     * лишняя попытка против несделанной.
     */
    private static function pathIsUnreachable(HttpClientConnectionException $e): bool
    {
        $previous = $e->getPrevious();

        if (! $previous instanceof GuzzleConnectException) {
            return true;
        }

        $connectTime = $previous->getHandlerContext()['connect_time'] ?? null;

        return ! (is_numeric($connectTime) && $connectTime > 0);
    }
}
