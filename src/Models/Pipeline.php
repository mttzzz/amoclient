<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractModel
{
    use Traits\CrudTrait;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'leads/pipelines';
    }

    public function entity(?int $id = null): Entities\Pipeline
    {
        return new Entities\Pipeline(['id' => $id], $this->http);
    }

    public function find(int $id): Entities\Pipeline
    {
        return new Entities\Pipeline($this->findEntity($id), $this->http);
    }
}
