<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits\CrudTrait;

class Catalog extends AbstractModel
{
    use CrudTrait;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'catalogs';
    }

    public function entity(int|null $id = null): Entities\Catalog
    {
        return new Entities\Catalog(['id' => $id], $this->http);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\Catalog
    {
        return new Entities\Catalog($this->findEntity($id), $this->http);
    }
}
