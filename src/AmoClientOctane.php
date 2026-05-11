<?php

namespace mttzzz\AmoClient;

use Exception;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        // Проверяем, что виджет установлен
        if (! $accountData->access_token) {
            /** @var Widget|null $widget */
            $widget = DB::connection('octane')->table('widgets')->where('client_id', $this->clientId)->first();
            if (! $widget) {
                throw new Exception("Widget ($this->clientId) not found");
            }
            // @codeCoverageIgnoreStart
            throw new Exception("Widget ($widget->name) doesn't installed in account ($accountData->subdomain)");
            // @codeCoverageIgnoreEnd
        }

        $octaneAccount = $this->convertToOctaneAccount($accountData);

        $lazyCf = new LazyCustomFields($aId);

        // Остальные параметры из конфига, если они есть.
        $timeout = Config::get('amoclient.timeout') ?? 60;
        $connectTimeout = Config::get('amoclient.connectTimeout') ?? 10;
        $retries = Config::get('amoclient.retries') ?? 3;
        $retryDelay = Config::get('amoclient.retryDelay') ?? 2000;
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

        $http = Http::withToken($octaneAccount->access_token)
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->withOptions(['verify' => $verify])
            ->retry($retries * $maxProxyAttempts, $retryDelay, function (Throwable $exception, PendingRequest $request) use (&$proxyIndex, $proxies, $maxProxyAttempts) {
                // Проверяем, нужно ли переключить прокси
                $shouldRetry = false;

                if ($exception instanceof HttpClientConnectionException) {
                    $shouldRetry = true;
                }

                if ($exception instanceof RequestException) {
                    if ($exception->response->status() >= 500) {
                        $shouldRetry = true;
                    }
                }

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
        $this->account = new Account($http, $aId);
        $this->leads = new Lead($http, $lazyCf);
        $this->customers = new Customer($http, $lazyCf);
        $this->contacts = new Contact($http, $octaneAccount, $lazyCf);
        $this->companies = new Company($http, $octaneAccount, $lazyCf);
        $this->catalogs = new Catalog($http);
        $this->users = new User($http);
        $this->pipelines = new Pipeline($http);
        $this->tasks = new Task($http);
        $this->events = new Event($http);
        $this->ajax = new Ajax($octaneAccount, $http);
        $this->unsorted = new Unsorted($http);
        $this->calls = new Call($http);
        $this->webhooks = new Webhook($http);
        $this->shortLinks = new ShortLink($http);
        $this->sources = new Source($http);
    }

    private function convertToOctaneAccount(stdClass $data): OctaneAccount
    {
        $octaneAccount = new OctaneAccount;
        $octaneAccount->id = $data->id;
        $octaneAccount->subdomain = $data->subdomain;
        $octaneAccount->domain = $data->domain;
        $octaneAccount->access_token = $data->access_token;
        $octaneAccount->contact_phone_field_id = $data->contact_phone_field_id ?? 0;
        $octaneAccount->contact_email_field_id = $data->contact_email_field_id ?? 0;

        return $octaneAccount;
    }
}
