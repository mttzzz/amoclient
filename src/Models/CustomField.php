<?php


namespace mttzzz\AmoClient\Models;


class CustomField extends AbstractModel
{
    protected $entity;

    public function __construct($http, $parentEntity)
    {
        $this->entity = "{$parentEntity}/custom_fields";
        parent::__construct($http);
    }
}
