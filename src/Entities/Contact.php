<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class Contact extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\PhoneTrait, Traits\EmailTrait;

    protected $entity = 'contacts';

    public $id, $first_name, $last_name, $name, $responsible_user_id;
    public $custom_fields_values = [];
    public $_embedded = [];

    public function __construct($data = [], PendingRequest $http = null)
    {
        parent::__construct($data, $http);
    }

    public function toArray()
    {
        $item = parent::toArray();
        foreach (['id', 'name', 'last_name', 'first_name'] as $key) {
            if (empty($item->$key)) {
                unset($item->$key);
            }
        }
        return $item;
    }
}
