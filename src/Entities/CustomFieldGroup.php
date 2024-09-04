<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CustomFieldGroup extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity;

    public string $name;

    public int $sort;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }
}
