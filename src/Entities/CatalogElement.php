<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait;

    protected $entity;

    public $id;

    public $name;

    public $custom_fields_values = [];

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
