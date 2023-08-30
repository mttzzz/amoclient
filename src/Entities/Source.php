<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Source extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected $entity = 'sources';
    public $name, $pipeline_id, $extenal_id, $default, $origin_code, $services;
}
