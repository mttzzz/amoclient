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
     * Сортировка по updated_at. Нужна не для красоты: дискавери свипа идёт
     * окном по updated_at с потолком в 10 страниц, порядок выдачи амо
     * возрастающий, а созданное тестами всегда самое свежее — то есть на
     * нагруженном аккаунте наш собственный мусор систематически ложится в
     * отрезаемый хвост.
     *
     * ⚠️ Для роута задач направление НЕ снято зондом (§8.7 проверял
     * `/leads/notes`). Амо на неподдерживаемую сортировку отвечает 200 и молча
     * отдаёт порядок по умолчанию, поэтому если `order[updated_at]` тут не
     * поддерживается, метод окажется тихим no-op, а не ошибкой. До зонда
     * считать это непроверенным.
     */
    public function orderByUpdatedAtDesc(): self
    {
        return $this->orderBy('updated_at', 'desc');
    }

    /**
     * См. предупреждение у orderByUpdatedAtDesc(): направление для роута задач
     * зондом не подтверждено.
     */
    public function orderByUpdatedAtAsc(): self
    {
        return $this->orderBy('updated_at', 'asc');
    }

    /**
     * Сортировка у амо ровно одна, поэтому каждый вызов ЗАМЕЩАЕТ предыдущий,
     * а не добавляется к нему. Раньше `orderByCompleteDesc()
     * ->orderByUpdatedAtDesc()` отправил бы два ключа, и какой из них амо
     * учтёт — оставалось бы на его усмотрение, молча. Теперь побеждает
     * последний вызов, и это решение принимаем мы.
     *
     * Направление не параметр публичного API: методы перечислены поимённо
     * именно потому, что амо не валидирует `order` — на `order[updated_at]=
     * nonsense` он отвечает 200 и сортирует по умолчанию. Опечатка в строке
     * не проявилась бы ни ошибкой, ни предупреждением, только тихой
     * деградацией уборки, поэтому строке взяться неоткуда.
     *
     * @param  'asc'|'desc'  $direction
     */
    private function orderBy(string $field, string $direction): self
    {
        $this->order = [$field => $direction];

        return $this;
    }
}
