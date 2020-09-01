<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\CrudEntityTrait;

    protected $entity;

    public $id, $name, $custom_fields_values = [];

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
