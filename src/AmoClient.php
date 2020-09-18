<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;

class AmoClient
{
    public $leads, $contacts, $companies, $catalogs, $account, $users, $pipelines, $tasks, $events, $ajax, $unsorted, $calls;

    public function __construct($key)
    {
        $account = DB::connection('api.amocrm.pushka.biz')
            ->table('accounts')
            ->where('key', $key)
            ->first();

        $cf = DB::connection('api.amocrm.pushka.biz')
            ->table('custom_field_accounts')
            ->select('id', 'type')
            ->where('account_id', $account->id)
            ->get()->pluck('type', 'id')->toArray();

        $http = Http::withToken($account->access_token)
            ->baseUrl("https://{$account->subdomain}.amocrm.ru/api/v4");

        $this->account = new Models\Account($http);
        $this->leads = new Models\Lead($http, $cf);
        $this->contacts = new Models\Contact($http, $account, $cf);
        $this->companies = new Models\Company($http, $account, $cf);
        $this->catalogs = new Models\Catalog($http);
        $this->users = new Models\User($http);
        $this->pipelines = new Models\Pipeline($http);
        $this->tasks = new Models\Task($http);
        $this->events = new Models\Event($http);
        $this->ajax = new Ajax($account);
        $this->unsorted = new Models\Unsorted($http);
        $this->calls = new Models\Call($http);
    }
}
