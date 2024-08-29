<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Task extends AbstractModel
{
    use Traits\CrudTrait;

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'tasks';
    }

    public function entity(?int $id = null): Entities\Task
    {
        return new Entities\Task(['id' => $id], $this->http);
    }

    public function find(int $id): Entities\Task
    {
        return new Entities\Task($this->findEntity($id), $this->http);
    }

    /**
     * @param  int|array<int>  $id
     */
    public function filterId(int|array $id): self
    {
        $this->filter['id'] = is_array($id) ? $id : (int) $id;

        return $this;
    }

    /**
     * @param  int|array<int>  $id
     */
    public function filterResponsibleUserId(int|array $id): self
    {
        $this->filter['responsible_user_id'] = is_array($id) ? $id : (int) $id;

        return $this;
    }

    public function filterIsCompletedTrue(): self
    {
        $this->filter['is_completed'] = true;

        return $this;
    }

    public function filterIsCompletedFalse(): self
    {
        $this->filter['is_completed'] = false;

        return $this;
    }

    /**
     * @param  int|array<int>  $type
     */
    public function filterTaskType(int|array $type): self
    {
        $this->filter['task_type'] = is_array($type) ? $type : (int) $type;

        return $this;
    }

    public function filterLead(): self
    {
        $this->filter['entity_type'] = 'leads';

        return $this;
    }

    public function filterContact(): self
    {
        $this->filter['entity_type'] = 'contacts';

        return $this;
    }

    public function filterCompany(): self
    {
        $this->filter['entity_type'] = 'companies';

        return $this;
    }

    public function filterCustomer(): self
    {
        $this->filter['entity_type'] = 'customers';

        return $this;
    }

    /**
     * @param  int|array<int>  $id
     */
    public function filterEntityId(int|array $id): self
    {
        $this->filter['entity_id'] = is_array($id) ? $id : (int) $id;

        return $this;
    }

    public function filterUpdatedAt(int $from, int $to): self
    {
        $this->filter['updated_at'] = ['from' => $from, 'to' => $to];

        return $this;
    }

    public function orderByCompleteDesc(): self
    {
        $this->order['complete_till'] = 'desc';

        return $this;
    }

    public function orderByCompleteAsc(): self
    {
        $this->order['complete_till'] = 'asc';

        return $this;
    }
}
