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

    public function __construct(int $aId, ?string $clientId = null)
    {

        if ($clientId) {
            $this->clientId = $clientId;
        }

        // Оптимизированный запрос: объединяем все данные в один запрос с LEFT JOIN
        $result = DB::connection('octane')
            ->select("
                SELECT 
                    a.id,
                    a.subdomain,
                    a.domain,
                    a.contact_phone_field_id,
                    a.contact_email_field_id,
                    aw.access_token,
                    w.name as widget_name,
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', acf.id,
                            'type', acf.type,
                            'enums', acf.enums
                        )
                    ) as custom_fields
                FROM accounts a
                LEFT JOIN account_widget aw ON a.id = aw.account_id AND aw.active = 1
                LEFT JOIN widgets w ON w.id = aw.widget_id AND w.client_id = ?
                LEFT JOIN account_custom_fields acf ON a.id = acf.account_id
                WHERE a.id = ?
                GROUP BY a.id, a.subdomain, a.domain, a.contact_phone_field_id, a.contact_email_field_id, aw.access_token, w.name
            ", [$this->clientId, $aId]);

        if (empty($result)) {
            throw new Exception("Account ($aId) not found");
        }

        $accountData = $result[0];

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

        // Парсим custom fields из JSON
        $rawCustomFields = $accountData->custom_fields;

        // Если custom_fields пришли как JSON строка, парсим их
        if (is_string($rawCustomFields)) {
            /** @var array<int, array{id: int|null, type: string|null, enums: string|null}> $customFields */
            $customFields = json_decode($rawCustomFields, true);
        } else {
            /** @var array<int, array{id: int|null, type: string|null, enums: string|null}> $customFields */
            $customFields = $rawCustomFields;
        }

        if (! is_array($customFields)) {
            $customFields = [];
        }

        $fields = collect($customFields)->filter(function (array $field): bool {
            return ! is_null($field['id']); // Фильтруем null значения от LEFT JOIN
        });

        $cf = $fields->pluck('type', 'id')->toArray();

        // Обрабатываем enums: если это уже массив, конвертируем в JSON строку для совместимости с трейтом
        $enums = $fields->pluck('enums', 'id')->map(function ($enum) {
            if (is_array($enum)) {
                return json_encode($enum);
            }

            return $enum;
        })->toArray();

        // Остальные параметры из конфига, если они есть.
        $timeout = Config::get('amoclient.timeout') ?? 60;
        $connectTimeout = Config::get('amoclient.connectTimeout') ?? 10;
        $retries = Config::get('amoclient.retries') ?? 3;
        $retryDelay = Config::get('amoclient.retryDelay') ?? 1000;

        $baseUrl = "https://{$octaneAccount->subdomain}.amocrm.{$octaneAccount->domain}/api/v4";
        $http = Http::withToken($octaneAccount->access_token)
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry($retries, $retryDelay, function (Throwable $exception) {
                // Ретраим при проблемах с подключением
                if ($exception instanceof HttpClientConnectionException) {
                    return true;
                }

                // Ретраим при серверных ошибках (5xx статус коды)
                if ($exception instanceof RequestException) {
                    if ($exception->response->status() >= 500) {
                        return true;
                    }
                }

                // Остальные ошибки не ретраим
                return false;
            })
            ->baseUrl($baseUrl);
        // @codeCoverageIgnoreEnd
        $this->accountId = $aId;
        $this->http = $http;
        $this->account = new Models\Account($http, $aId);
        $this->leads = new Models\Lead($http, $cf, $enums);
        $this->customers = new Models\Customer($http, $cf);
        $this->contacts = new Models\Contact($http, $octaneAccount, $cf, $enums);
        $this->companies = new Models\Company($http, $octaneAccount, $cf, $enums);
        $this->catalogs = new Models\Catalog($http);
        $this->users = new Models\User($http);
        $this->pipelines = new Models\Pipeline($http);
        $this->tasks = new Models\Task($http);
        $this->events = new Models\Event($http);
        $this->ajax = new Ajax($octaneAccount, $http);
        $this->unsorted = new Models\Unsorted($http);
        $this->calls = new Models\Call($http);
        $this->webhooks = new Models\Webhook($http);
        $this->shortLinks = new Models\ShortLink($http);
        $this->sources = new Models\Source($http);
    }

    private function convertToOctaneAccount(stdClass $data): OctaneAccount
    {
        $octaneAccount = new OctaneAccount;
        $octaneAccount->id = $data->id;
        $octaneAccount->subdomain = $data->subdomain;
        $octaneAccount->domain = $data->domain;
        $octaneAccount->access_token = $data->access_token;
        $octaneAccount->contact_phone_field_id = $data->contact_phone_field_id;
        $octaneAccount->contact_email_field_id = $data->contact_email_field_id;

        return $octaneAccount;
    }
}
