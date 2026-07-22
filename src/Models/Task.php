<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\Traits;

class Task extends AbstractModel
{
    use Traits\CrudTrait;

    /* Поля по замеру §9.4: у задач работают id, created_at, complete_till.
     * updated_at игнорируется — `asc` и `desc` дают одну и ту же страницу при
     * HTTP 200, поэтому трейта ByUpdatedAt здесь нет и быть не должно. */
    use Traits\Order\ByCompleteTill, Traits\Order\ByCreatedAt, Traits\Order\ById;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'tasks';
        $this->deleter = $deleter;
    }

    /**
     * Удаление задач. Публичного механизма у амо нет — работает только
     * приватный роут, поэтому ярус semver третий: обещается не «работает
     * всегда», а «ломается громко и чинится patch-ом» (детали — в Deleter).
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 400 `{"status":"fail","id":N}`,
     *              то есть задачи уже нет. «Уже нет» приходит здесь ошибочным
     *              статусом, а не полем в теле двухсотки — разбирает это
     *              Deleter, вызывающему достаточно bool
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->tasks($ids);
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
}
