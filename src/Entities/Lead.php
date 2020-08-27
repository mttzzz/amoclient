<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits\CustomFieldTrait;
use mttzzz\AmoClient\Traits\TagTrait;

class Lead extends AbstractEntity
{
    use CustomFieldTrait, TagTrait;

    protected $entity = 'leads';

    public $name, $notes, $tasks;
    public $id, $price, $status_id, $responsible_user_id;
    public $custom_fields_values = [], $_embedded = [];

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
        $this->notes = new Note([], $http, $this->entity, $this->id);
        $this->tasks = new Task([], $http, $this->entity, $this->id);
    }
}
