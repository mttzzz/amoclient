<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;

class Unsorted extends AbstractEntity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http)
    {
        parent::__construct($data, $http);
    }
}
