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

    public function orderByCompleteDesc(): self
    {
        return $this->orderBy('complete_till', 'desc');
    }

    public function orderByCompleteAsc(): self
    {
        return $this->orderBy('complete_till', 'asc');
    }

    /**
     * Сортировка по свежести — через id, а НЕ через updated_at.
     *
     * Нужна не для красоты: дискавери свипа идёт окном с потолком в 10 страниц
     * по 150, порядок выдачи амо возрастающий, а созданное тестами всегда самое
     * свежее — на нагруженном аккаунте наш собственный мусор систематически
     * ложится в отрезаемый хвост.
     *
     * Почему id, а не updated_at: роут задач `order[updated_at]` ИГНОРИРУЕТ
     * целиком — `asc`, `desc` и заведомый мусор дают побайтово одну и ту же
     * выдачу, и все три при HTTP 200 (§8.8). Метода по updated_at здесь нет
     * намеренно: он был бы тихим no-op, а отсутствие возможности честнее её
     * видимости. У примечаний тот же параметр работает (§8.7) — поддержка
     * сортировки у амо не единообразна между роутами.
     *
     * id монотонны, поэтому для «самые свежие первыми» этого достаточно.
     * `created_at` даёт тот же порядок и ничего не добавляет; `complete_till`
     * даёт другой и к свежести отношения не имеет.
     */
    public function orderByIdDesc(): self
    {
        return $this->orderBy('id', 'desc');
    }

    /**
     * См. orderByIdDesc(): сортировка по свежести у задач идёт через id.
     */
    public function orderByIdAsc(): self
    {
        return $this->orderBy('id', 'asc');
    }
}
