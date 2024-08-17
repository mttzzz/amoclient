<?php

namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;

class CustomFieldGroup extends AbstractModel
{
    protected $entity;

    public function __construct($http, $parentEntity)
    {
        $this->entity = "{$parentEntity}/groups";
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\CustomFieldGroup(['id' => $id], $this->http, $this->entity);
    }
}
