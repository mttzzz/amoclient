<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CustomFieldGroup extends AbstractEntity
{
    use Traits\CrudEntityTrait;
    protected $entity;
    public $name, $sort;

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
