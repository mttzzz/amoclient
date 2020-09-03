<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractModel
{
    use Traits\CrudTrait, Traits\QueryTrait;

    protected $entity;

    public function __construct(PendingRequest $http, $catalogId)
    {
        $this->entity = "catalogs/{$catalogId}/elements";
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\CatalogElement(['id' => $id], $this->http, $this->entity);
    }

    public function find($id)
    {
        return new Entities\CatalogElement($this->findEntity($id), $this->http, $this->entity);
    }

    public function filterId($id)
    {
        $this->filter['id'] = $id;
        return $this;
    }
}
