<?php


namespace mttzzz\AmoClient\Models;

use mttzzz\AmoClient\Entities;

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
}
