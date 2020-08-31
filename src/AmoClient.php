<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;

class AmoClient
{
    public $leads, $contacts, $companies, $catalogs, $account, $users, $pipelines;

    //public $tasks, $notes;

    public function __construct($key)
    {
        $account = DB::connection('api.amocrm.pushka.biz')
            ->table('accounts')
            ->where('key', $key)
            ->first();
        $http = Http::withToken($account->access_token)
            ->baseUrl("https://{$account->subdomain}.amocrm.ru/api/v4");
        $this->account = new Models\Account($http);
        $this->leads = new Models\Lead($http);
        $this->contacts = new Models\Contact($http, $account->contact_phone_field_id, $account->contact_email_field_id);
        $this->companies = new Models\Company($http, $account->contact_phone_field_id, $account->contact_email_field_id);
        $this->catalogs = new Models\Catalog($http);
        $this->users = new Models\User($http);
        $this->pipelines = new Models\Pipeline($http);
        //$this->tasks = new Models\Task($http);
        //$this->notes = new Models\Note($http);
    }
}
