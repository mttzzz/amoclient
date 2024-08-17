<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class CustomField extends AbstractModel
{
    protected $entity;

    public $groups;

    public function __construct($http, $parentEntity)
    {
        $this->entity = "{$parentEntity}/custom_fields";
        parent::__construct($http);
        $this->groups = new CustomFieldGroup($http, $this->entity);
    }

    public function entity($id = null)
    {
        return new Entities\CustomField(['id' => $id], $this->http, $this->entity);
    }

    public function find($id)
    {
        try {
            return $this->http->get($this->entity.'/'.$id)
                ->throw()->json() ?? [];
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
