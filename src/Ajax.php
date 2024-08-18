<?php

namespace mttzzz\AmoClient;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use mttzzz\AmoClient\Helpers\OctaneAccount;

class Ajax
{
    private PendingRequest $http;

    public function __construct(OctaneAccount $account, PendingRequest $http)
    {
        $this->http = clone $http;
        $this->http = $this->http->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->baseUrl("https://{$account->subdomain}.amocrm.{$account->domain}");
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $url, array $query = []): Response
    {
        return $this->http->get($url, $query)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function postJson(string $url, array $data = []): Response
    {
        return $this->http->asJson()->post($url, $data)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function postForm(string $url, array $data = []): Response
    {
        return $this->http->asForm()->post($url, $data)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function patch(string $url, array $data = []): Response
    {
        return $this->http->patch($url, $data)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function delete(string $url, array $data = []): Response
    {
        return $this->http->delete($url, $data)->throw()->json();
    }
}
