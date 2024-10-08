<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractModel
{
    use Traits\CrudTrait, Traits\Filter\Common, Traits\QueryTrait;

    public function __construct(PendingRequest $http, int $catalogId)
    {
        $this->entity = "catalogs/{$catalogId}/elements";
        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\CatalogElement
    {
        return new Entities\CatalogElement(['id' => $id], $this->http, $this->entity);
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\CatalogElement
    {
        return new Entities\CatalogElement($data, $this->http, $this->entity);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\CatalogElement
    {
        return new Entities\CatalogElement($this->findEntity($id), $this->http, $this->entity);
    }
}
