<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class CustomField extends AbstractModel
{
    public CustomFieldGroup $groups;

    public function __construct(PendingRequest $http, string $parentEntity)
    {
        parent::__construct($http);
        $this->entity = "$parentEntity/custom_fields";
        $this->groups = new CustomFieldGroup($http, $this->entity);
    }

    public function entity(?int $id = null): Entities\CustomField
    {
        return new Entities\CustomField(['id' => $id], $this->http, $this->entity);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): ?Entities\CustomField
    {
        try {
            return $this->http->get($this->entity.'/'.$id)
                ->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
