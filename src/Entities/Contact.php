<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Contact extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait, Traits\EmailTrait, Traits\PhoneTrait, Traits\TagTrait;

    protected string $entity = 'contacts';

    public $id;

    public $first_name;

    public $last_name;

    public $name;

    public $responsible_user_id;

    public $custom_fields_values = [];

    public $_embedded = [];

    public $notes;

    public $tasks;

    public $links;

    public function __construct($data, PendingRequest $http, $cf, $enums)
    {
        $this->cf = $cf;
        $this->enums = $enums;
        parent::__construct($data, $http);
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
    }

    public function getLeadIds()
    {
        $leadIds = $this->toArray()['_embedded']['leads'] ?? [];

        return count($leadIds) ? Arr::pluck($leadIds, 'id') : [];
    }
}
