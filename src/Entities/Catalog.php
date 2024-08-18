<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Models\CatalogElement;
use mttzzz\AmoClient\Traits;

class Catalog extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    public string $name;

    public string $type = 'regular';

    public ?int $sort = null;

    public CatalogElement $elements;

    public bool $can_add_elements;

    public bool $can_link_multiple;

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
        $this->entity = 'catalogs';
        if ($this->id !== null) {
            $this->elements = new Models\CatalogElement($http, $this->id);
        }
    }

    public function customFields(): Models\CustomField
    {
        return new Models\CustomField($this->http, $this->entity.'/'.$this->id);
    }
}
