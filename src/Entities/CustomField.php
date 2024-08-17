<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CustomField extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected $entity;

    public $type;

    public $name;

    public $code;

    public $sort;

    public $group_id;

    public $is_api_only;

    public $required_statuses;

    public $remind;

    public $enums;

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
