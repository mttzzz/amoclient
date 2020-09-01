<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class Contact extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\PhoneTrait, Traits\EmailTrait, Traits\CrudEntityTrait;

    protected $entity = 'contacts', $notes, $tasks;

    public $id, $first_name, $last_name, $name, $responsible_user_id;
    public $custom_fields_values = [];
    public $_embedded = [];

    public function __construct($data = [], PendingRequest $http = null)
    {
        parent::__construct($data, $http);
        $this->notes = new Note([], $http, $this->entity, $this->id);
        $this->tasks = new Task([], $http, $this->entity, $this->id);
    }
}
