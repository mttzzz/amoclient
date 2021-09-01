<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Task extends AbstractModel
{
    use Traits\CrudTrait;

    protected $entity = 'tasks';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Task(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Task($this->findEntity($id), $this->http);
    }

    public function filterId($id)
    {
        $this->filter['id'] = is_array($id) ? $id : (int)$id;
        return $this;
    }

    public function filterResponsibleUserId($id)
    {
        $this->filter['responsible_user_id'] = is_array($id) ? $id : (int)$id;
        return $this;
    }

    public function filterIsCompletedTrue()
    {
        $this->filter['is_completed'] = true;
        return $this;
    }

    public function filterIsCompletedFalse()
    {
        $this->filter['is_completed'] = false;
        return $this;
    }

    public function filterTaskType($type)
    {
        $this->filter['task_type'] = $type;
        return $this;
    }

    public function filterLead()
    {
        $this->filter['entity_type'] = 'leads';
        return $this;
    }

    public function filterContact()
    {
        $this->filter['entity_type'] = 'contacts';
        return $this;
    }

    public function filterCompany()
    {
        $this->filter['entity_type'] = 'companies';
        return $this;
    }

    public function filterCustomer()
    {
        $this->filter['entity_type'] = 'customers';
        return $this;
    }

    public function filterEntityId($id)
    {
        $this->filter['entity_id'] = is_array($id) ? $id : (int)$id;
        return $this;
    }

    public function filterUpdatedAt(int $from, int $to)
    {
        $this->filter['updated_at'] = ['from' => $from, 'to' => $to];
        return $this;
    }

    public function orderByCompleteDesc()
    {
        $this->order['complete_till'] = 'desc';
        return $this;
    }

    public function orderByCompleteAsc()
    {
        $this->order['complete_till'] = 'asc';
        return $this;
    }
}
