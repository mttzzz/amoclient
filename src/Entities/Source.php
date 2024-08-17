<?php

namespace mttzzz\AmoClient\Entities;

use mttzzz\AmoClient\Traits;

class Source extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'sources';

    public $name;

    public $pipeline_id;

    public $external_id;

    public $default;

    public $origin_code;

    public $services;
}
