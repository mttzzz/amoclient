<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class Call extends AbstractModel
{
    protected $entity = 'calls';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Call(['id' => $id], $this->http);
    }
}
