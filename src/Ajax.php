<?php

namespace mttzzz\AmoClient;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Helpers\OctaneAccount;

class Ajax
{
    private PendingRequest $http;

    public function __construct(OctaneAccount $account, PendingRequest $http)
    {
        $this->http = clone $http;
        $baseUrl = $account->domain === 'com'
            ? "https://{$account->subdomain}.kommo.com"
            : "https://{$account->subdomain}.amocrm.{$account->domain}";
        $this->http = $this->http->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->baseUrl($baseUrl);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $url, array $query = []): array
    {
        $result = $this->http->get($url, $query)->throw()->json();

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    public function postJson(string $url, array $data = []): array
    {
        $result = $this->http->asJson()->post($url, $data)->throw()->json();

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    public function postForm(string $url, array $data = []): array
    {
        $result = $this->http->asForm()->post($url, $data)->throw()->json();

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    public function patch(string $url, array $data = []): array
    {
        $result = $this->http->patch($url, $data)->throw()->json();

        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    public function delete(string $url, array $data = []): array
    {
        $result = $this->http->delete($url, $data)->throw()->json();

        return is_array($result) ? $result : [];
    }
}
