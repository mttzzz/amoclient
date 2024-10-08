<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Link extends AbstractEntity
{
    public int $to_entity_id;

    public string $to_entity_type;

    /**
     * @var array<mixed>
     */
    public array $metadata = [];

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }

    /**
     * @return array<mixed>
     */
    public function link(): array
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/link", [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function unlink(): null
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/unlink", [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
