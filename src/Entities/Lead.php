<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
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

    public function __construct($data, PendingRequest $http, $cf)
    {
        $this->cf = $cf;
        parent::__construct($data, $http);
        $this->notes = new Note([], $http, $this->entity, $this->id);
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
    }

    public function getMainContactId()
    {
        if (!isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        foreach ($this->_embedded['contacts'] as $contact) {
            if ($contact['is_main']) {
                return $contact['id'];
            }
        }
        return null;
    }

    public function getCompanyId()
    {
        return $this->_embedded['companies'][0]['id'] ?? null;
    }

    public function getContactsIds()
    {
        if (!isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        $ids = [];
        foreach ($this->_embedded['contacts'] as $contact) {
            $ids[] = $contact['id'];
        }
        return $ids;
    }
}
