<?php

namespace mttzzz\AmoClient\Entities;

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
}
