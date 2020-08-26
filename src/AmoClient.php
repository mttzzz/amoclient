<?php

namespace mttzzz\AmoClient;

use App\Models\Account;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;

class AmoClient
{
    public $leads;
    public $contacts;
    public $companies;
    public $tasks;
    public $notes;
    public $catalogs;
    public $catalogElements;

    public function __construct($key)
    {
        //$http = Http::retry(1)->baseUrl("https://api.amocrm.pushka.biz/api/v4/$key");
        $account = Account::where('key', $key)->first();
        $http = Http::withToken($account->access_token)->baseUrl("https://{$account->subdomain}.amocrm.ru/api/v4");
        $this->leads = new Models\Lead($http);
        $this->contacts = new Models\Contact($http);
        $this->companies = new Models\Company($http);
        $this->catalogElements = new Models\CatalogElement();
        $this->catalogs = new Models\Catalog($http);
        $this->tasks = new Models\Task($http);
    }
}
