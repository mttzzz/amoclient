<?php

namespace mttzzz\AmoClient;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use mttzzz\AmoClient\Models;

class AmoClient
{
    public $leads, $contacts, $companies, $catalogs, $account, $users, $pipelines, $tasks, $events, $ajax,
        $unsorted, $calls, $webhooks, $shortLinks;

    public function __construct($key, $slug = null)
    {
        $account = \DB::connection('api.amocrm.pushka.biz')
            ->table('accounts')
            ->select(['accounts.id', 'subdomain', 'account_widget.access_token', 'contact_phone_field_id', 'contact_email_field_id'])
            ->join('account_widget', 'accounts.id', '=', 'account_widget.account_id')
            ->join('widgets', 'widgets.id', '=', 'account_widget.widget_id')
            ->where('account_widget.is_token_revoked', false)
            ->where('key', $key);

        $account = $slug ? $account->where('widgets.slug', $slug)->first() : $account->first();
        if (!$account) throw new Exception('Account doesnt active widgets');

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
        $this->webhooks = new Models\Webhook($http);
        $this->shortLinks = new Models\ShortLink($http);
    }
}
