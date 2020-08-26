<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;

trait QueryTrait
{
    public function query($query)
    {
        try {
            $entities = $this->http->get($this->entity, ['query' => $query])->throw()->json();
            return $entities['_embedded'][$this->entity] ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }
}
