<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class Company extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\PhoneTrait, Traits\EmailTrait, Traits\CrudEntityTrait;

    protected $entity = 'companies';

    public $id, $name, $responsible_user_id;
    public $custom_fields_values = [];
    public $_embedded = [];

    public function __construct($data = [], PendingRequest $http = null)
    {
        parent::__construct($data, $http);
    }
}
