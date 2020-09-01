<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;


class Lead extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\CrudEntityTrait;

    protected $entity = 'leads';

    public $name, $notes, $tasks, $links;
    public $id, $price, $status_id, $responsible_user_id;
    public $custom_fields_values = [], $_embedded = [];

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
        $this->notes = new Note([], $http, $this->entity, $this->id);
        $this->tasks = new Task([], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
    }
}
