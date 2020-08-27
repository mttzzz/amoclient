<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits\CrudTrait;

class Catalog extends AbstractModel
{
    use CrudTrait;
    protected $entity = 'catalogs';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Catalog(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Catalog($this->findEntity($id), $this->http);
    }
}
