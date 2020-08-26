<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractEntity
{
    use Traits\CustomFieldTrait;

    protected $entity;

    public $id, $name, $custom_fields_values = [];
    public function __construct($data, PendingRequest $http, $catalogId)
    {
        $this->entity = "catalogs/$catalogId/elements";
        parent::__construct($data, $http);
    }
}
