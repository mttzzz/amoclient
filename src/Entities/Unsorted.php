<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;

class Unsorted extends AbstractEntity
{
    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
    }
}
