<?php

namespace mttzzz\AmoClient;

use Illuminate\Http\Client\PendingRequest;

class Ajax
{
    private $http;

    public function __construct($account, PendingRequest $http)
    {
        $this->http = clone $http;
        $this->http = $this->http->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->baseUrl("https://{$account->subdomain}.amocrm.{$account->domain}");
    }

    public function get(string $url, $query = [])
    {
        return $this->http->get($url, $query)->throw()->json();
    }

    public function postJson(string $url, $data = [])
    {
        return $this->http->asJson()->post($url, $data)->throw()->json();
    }

    public function postForm(string $url, $data = [])
    {
        return $this->http->asForm()->post($url, $data)->throw()->json();
    }

    public function patch(string $url, $data = [])
    {
        return $this->http->patch($url, $data)->throw()->json();
    }

    public function delete(string $url, $data = [])
    {
        return $this->http->delete($url, $data)->throw()->json();
    }
}
