<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;

class Link extends AbstractEntity
{
    protected string $entity;

    public $to_entity_id;

    public $to_entity_type;

    public $metadata = [];

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }

    public function link()
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/link", [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), true) ?? [];
        }
    }

    public function unlink()
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/unlink", [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), true) ?? [];
        }
    }
}
