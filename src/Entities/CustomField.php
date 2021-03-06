<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CustomField extends AbstractEntity
{
    use Traits\CrudEntityTrait;
    protected $entity;
    public $type, $name, $code, $sort, $group_id, $is_api_only, $required_statuses, $remind, $enums;

    public function __construct($data, PendingRequest $http, $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }

    public function setData($data)
    {
        parent::setData($data);
    }
}
