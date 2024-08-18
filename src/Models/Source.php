<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Source extends AbstractModel
{
    use Traits\CrudTrait;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'sources';
    }

    public function entity(?int $id = null): Entities\Source
    {
        return new Entities\Source(['id' => $id], $this->http);
    }

    public function find(int $id): Entities\Source
    {
        return new Entities\Source($this->findEntity($id), $this->http);
    }
}
