<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class Call extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'calls';
    }

    public function entity(?int $id = null): Entities\Call
    {
        return new Entities\Call(['id' => $id], $this->http);
    }
}
