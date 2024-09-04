<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait;

    //TODO обязательно протестировать
    public string $name;

    /**
     * @var array<mixed>
     */
    public array $custom_fields_values = [];

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
