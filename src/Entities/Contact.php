<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;
use Illuminate\Support\Arr;

class Contact extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\PhoneTrait, Traits\EmailTrait, Traits\CrudEntityTrait;

    protected $entity = 'contacts';

    public $id, $first_name, $last_name, $name, $responsible_user_id;
    public $custom_fields_values = [];
    public $_embedded = []; 
    public $notes, $tasks, $links;

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
        return count($leadIds) ? Arr::pluck($leadIds,'id') : [];
    }
}
