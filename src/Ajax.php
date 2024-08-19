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
        $this->http = $this->http->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->baseUrl("https://{$account->subdomain}.amocrm.{$account->domain}");
    }

    /**
     * @param  array<mixed>  $query
     * @return array<mixed>
     */
    public function get(string $url, array $query = []): array
    {
        return $this->http->get($url, $query)->throw()->json();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function postJson(string $url, array $data = []): array
    {
        return $this->http->asJson()->post($url, $data)->throw()->json();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function postForm(string $url, array $data = []): array
    {
        return $this->http->asForm()->post($url, $data)->throw()->json();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function patch(string $url, array $data = []): array
    {
        return $this->http->patch($url, $data)->throw()->json();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function delete(string $url, array $data = []): array
    {
        return $this->http->delete($url, $data)->throw()->json();
    }
}
