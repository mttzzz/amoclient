<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;

class CustomFieldGroup extends AbstractEntity
{
    protected $entity;
    public $name, $sort;

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
