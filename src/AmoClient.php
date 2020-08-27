<?php

namespace mttzzz\AmoClient;

use App\Models\Account;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;

class AmoClient
{
    public $leads, $contacts, $companies, $catalogs, $account,$users;
    //public $tasks, $notes;

    public function __construct($key)
    {
        $account = Account::where('key', $key)->first();
        $http = Http::withToken($account->access_token)->baseUrl("https://{$account->subdomain}.amocrm.ru/api/v4");
        $this->account = new Models\Account($http);
        $this->leads = new Models\Lead($http);
        $this->contacts = new Models\Contact($http);
        $this->companies = new Models\Company($http);
        $this->catalogs = new Models\Catalog($http);
        $this->users = new Models\User($http);
        //$this->tasks = new Models\Task($http);
        //$this->notes = new Models\Note($http);
    }
}
