<?php

namespace mttzzz\AmoClient;

use Exception;
use GuzzleHttp\Exception\ConnectException as GuzzleHttpConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Http\Client\PendingRequest;
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
use Psr\Http\Message\RequestInterface;

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

    public int $accountId;

    public function __construct(int $aId, string $clientId = '00a140c1-7c52-4563-8b36-03f23754d255')
    {
        /** @var OctaneAccount|null $account */
        $account = DB::connection('octane')->table('accounts')
            ->select(['accounts.id', 'subdomain', 'domain', 'account_widget.access_token'])
            ->join('account_widget', 'accounts.id', '=', 'account_widget.account_id')
            ->join('widgets', 'widgets.id', '=', 'account_widget.widget_id')
            ->where('account_widget.active', true)
            ->where('accounts.id', $aId)
            ->where('widgets.client_id', $clientId)
            ->first();

        if (! $account) {
            /** @var OctaneAccount|null $account */
            $account = DB::connection('octane')->table('accounts')->where('id', $aId)->first();
            if (! $account) {
                throw new Exception("Account ($aId) not found");
            }
            /** @var Widget|null $widget */
            $widget = DB::connection('octane')->table('widgets')->where('client_id', $clientId)->first();
            if (! $widget) {
                throw new Exception("Widget ($clientId) not found");
            }
            throw new Exception("Account ($account->subdomain) doesn't active widget ($widget->name)");
        }

        $account->contact_phone_field_id = DB::connection('octane')->table('account_custom_fields')
            ->where('account_id', $aId)->where('code', 'PHONE')->first()->id ?? null;
        $account->contact_email_field_id = DB::connection('octane')->table('account_custom_fields')
            ->where('account_id', $aId)->where('code', 'EMAIL')->first()->id ?? null;

        $fields = DB::connection('octane')
            ->table('account_custom_fields')
            ->where('account_id', $aId)
            ->get();

        $cf = $fields->pluck('type', 'id')->toArray();
        $enums = $fields->pluck('enums', 'id')->toArray();

        // Создание стека обработчиков для Guzzle
        $stack = HandlerStack::create();

        //Подключаем список прокси из конфига, если конфига нет, то присваиванием массив с 1 элементом null.
        //Это приведет к тому, что запрос будет выполнен без прокси.
        $proxies = Config::get('amoclient.proxies') ?? [null];

        //Остальные параметры из конфига, если они есть.
        $timeout = Config::get('amoclient.timeout') ?? 60;
        $connectTimeout = Config::get('amoclient.connectTimeout') ?? 10;
        $retries = Config::get('amoclient.retries') ?? 2;
        $retryDelay = Config::get('amoclient.retryDelay') ?? 1000;

        // Индекс текущего прокси
        $currentProxyIndex = 0;

        // Добавление middleware в стек
        $stack->push(Middleware::retry(function ($retry, RequestInterface $request, $response, $exception) use (&$proxies, &$currentProxyIndex) {
            // Проверка на наличие GuzzleHttpConnectException и наличие доступных прокси для переключения
            if ($exception instanceof GuzzleHttpConnectException && isset($proxies[$currentProxyIndex + 1])) {
                // Переход к следующему прокси
                $currentProxyIndex++;

                // Если следующий прокси не null, устанавливаем его
                if ($proxies[$currentProxyIndex] !== null) {
                    $request->withUri($request->getUri()->withHost($proxies[$currentProxyIndex]));
                }

                return true; // Повторить попытку
            }

            return false; // Не повторять попытку для других типов ошибок или если прокси закончились
        }, function () {
            // Логика для определения задержки между попытками
            return 1000; // Задержка в миллисекундах
        }));

        $baseUrl = "https://{$account->subdomain}.amocrm.{$account->domain}/api/v4";
        $http = Http::withToken($account->access_token)
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry($retries, $retryDelay, function (Exception $exception, PendingRequest $request) use (&$currentProxyIndex) {
                $currentProxyIndex = 0; // Обнуляем индекс, чтобы при каждом новом ретрае сначала пробовать без прокси и потом по очередности с указанными прокси если они есть

                //ретраить будем, только если HttpClientConnectionException, остальные ошибки ретраить не будем.
                return $exception instanceof HttpClientConnectionException;
            })
            ->withOptions([
                'handler' => $stack,
                'proxy' => $proxies[$currentProxyIndex],
            ])
            ->baseUrl($baseUrl);

        $this->accountId = $aId;
        $this->account = new Models\Account($http);
        $this->leads = new Models\Lead($http, $cf, $enums);
        $this->customers = new Models\Customer($http, $cf);
        $this->contacts = new Models\Contact($http, $account, $cf, $enums);
        $this->companies = new Models\Company($http, $account, $cf, $enums);
        $this->catalogs = new Models\Catalog($http);
        $this->users = new Models\User($http);
        $this->pipelines = new Models\Pipeline($http);
        $this->tasks = new Models\Task($http);
        $this->events = new Models\Event($http);
        $this->ajax = new Ajax($account, $http);
        $this->unsorted = new Models\Unsorted($http);
        $this->calls = new Models\Call($http);
        $this->webhooks = new Models\Webhook($http);
        $this->shortLinks = new Models\ShortLink($http);
        $this->sources = new Models\Source($http);
    }
}
