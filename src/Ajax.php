<?php


namespace mttzzz\AmoClient;


use Illuminate\Support\Facades\Http;

class Ajax
{
    private $http;

    public function __construct($account)
    {
        $this->http = Http::withToken($account->access_token)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->baseUrl("https://{$account->subdomain}.amocrm.ru");
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
