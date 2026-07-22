<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Models\CatalogElement;
use mttzzz\AmoClient\Traits;

class Catalog extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    public string $name;

    public string $type;

    public ?int $sort;

    public CatalogElement $elements;

    public bool $can_add_elements;

    public string $test;

    public function __construct($data, PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($data, $http);
        $this->entity = 'catalogs';
        if ($this->id !== null) {
            $this->elements = new CatalogElement($http, $this->id, $deleter);
        }
    }

    public function customFields(): Models\CustomField
    {
        return new Models\CustomField($this->http, $this->entity.'/'.$this->id);
    }
}
