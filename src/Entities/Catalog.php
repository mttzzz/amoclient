<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Catalog extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'catalogs';

    public $id;

    public $name;

    public $type = 'regular';

    public $sort = null;

    public $elements;

    public bool $can_add_elements;

    public bool $can_link_multiple;

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
        $this->elements = new Models\CatalogElement($http, $this->id);
    }

    public function customFields()
    {
        return new Models\CustomField($this->http, $this->entity.'/'.$this->id);
    }
}
