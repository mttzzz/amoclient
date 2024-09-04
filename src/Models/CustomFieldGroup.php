<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class CustomFieldGroup extends AbstractModel
{
    public function __construct(PendingRequest $http, string $parentEntity)
    {
        parent::__construct($http);
        $this->entity = "$parentEntity/groups";
    }

    public function entity(?int $id = null): Entities\CustomFieldGroup
    {
        return new Entities\CustomFieldGroup(['id' => $id], $this->http, $this->entity);
    }
}
