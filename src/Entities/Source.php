<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Source extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'sources';

    public string $name;

    public int $pipeline_id;

    public string $external_id;

    public bool $default;

    public ?string $origin_code;

    /**
     * @var array<mixed>
     */
    public array $services;

    /**
     * @throws AmoCustomException
     */
    public function delete(): null
    {
        try {
            return $this->http->delete($this->entity.'/'.$this->id)->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
