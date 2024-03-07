<?php

namespace mttzzz\AmoClient;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\ConnectException as GuzzleHttpConnectException;
use Illuminate\Http\Client\ConnectionException as HttpClientConnectionException;
use Illuminate\Http\Client\PendingRequest;


class AmoClientOctane
{
    public $leads, $contacts, $companies, $sources, $catalogs, $customers, $account, $users, $pipelines, $tasks, $events, $ajax,
        $unsorted, $calls, $webhooks, $shortLinks, $accountId;

    public function __construct($aId, $clientId = '00a140c1-7c52-4563-8b36-03f23754d255')
    {
        $account = DB::connection('octane')->table('accounts')
            ->select(['accounts.id', 'subdomain', 'domain', 'account_widget.access_token'])
            ->join('account_widget', 'accounts.id', '=', 'account_widget.account_id')
            ->join('widgets', 'widgets.id', '=', 'account_widget.widget_id')
            ->where('account_widget.active', true)
            ->where('accounts.id', $aId)
            ->where('widgets.client_id', $clientId)
            ->first();

        if (!$account) {
            $account = DB::connection('octane')->table('accounts')->where('id', $aId)->first();
            $widget = DB::connection('octane')->table('widgets')->where('client_id', $clientId)->first();
            throw new Exception("Account ($account->subdomain) doesnt active widget ($widget->name)");
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

        // Создание кастомного стека обработчиков для Guzzle
        $stack = HandlerStack::create();

        //Подлючаем спискок прокси из конфига, если конфига нет, то писваиваем массив с 1 элементом null.
        //Это приведет к тому, что запрос будет выполнен без прокси.
        $proxies = config('amoclient.proxies') ?? [null];

        //Остальные параметры из конфига, если они есть.
        $timeout = config('amoclient.timeout') ?? 60;
        $connectTimeout = config('amoclient.connectTimeout') ?? 10;
        $retries = config('amoclient.retries') ?? 2;
        $retryDelay = config('amoclient.retryDelay') ?? 1000;

        // Индекс текущего прокси
        $currentProxyIndex = 0;

        // Добавление middleware в стек
        $stack->push(Middleware::retry(function ($retry, RequestInterface $request, $response, $exception) use (&$proxies, &$currentProxyIndex) {
            dump("currentProxyIndex: " . $currentProxyIndex ."  currentProxy: " . json_encode($proxies[$currentProxyIndex]));
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
        ->retry($retries, $retryDelay,function (Exception $exception, PendingRequest $request) use (&$currentProxyIndex, )  {
            $currentProxyIndex = 0; // Обнуляем индекс, чтобы при каждом новом ретрае сначала пробовать без прокси и потом по очередности с указанными прокси если они есть

            //ретраить будем, только если HttpClientConnectionException, остальные ошибки ретраить не будем.
            return $exception instanceof HttpClientConnectionException;
        })
        ->withOptions([
            'handler' => $stack,
            'proxy' => $proxies[$currentProxyIndex]
            ])
            ->baseUrl($baseUrl);

        $this->accountId = $aId;
        $this->account = new Models\Account($http);
        $this->leads = new Models\Lead($http, $cf, $enums);
        $this->customers = new Models\Customer($http, $cf, $enums);
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
