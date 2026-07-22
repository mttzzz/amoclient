<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\Traits\Order;

class Note extends AbstractModel
{
    /* Поля по замеру §9.4: у примечаний работают id и updated_at.
     * created_at игнорируется, поэтому трейта ByCreatedAt здесь нет. */
    use Order\ById, Order\ByUpdatedAt;

    protected ?int $entityId;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, string $entity, ?int $entityId, Deleter $deleter)
    {
        $this->entity = $entity.'/notes';
        $this->entityId = $entityId;
        $this->deleter = $deleter;
        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\Note
    {
        return new Entities\Note(['id' => $id], $this->http, $this->entity, $this->entityId);
    }

    /**
     * Удаление примечаний. Публичного механизма нет (v4 отдаёт 405) — работает
     * только приватный роут, ярус semver третий: «ломается громко и чинится
     * patch-ом».
     *
     * ВАЖНО: родитель в этом вызове не участвует, амо резолвит его по id
     * примечания. То есть `$amo->leads->notes->delete($id)` удалит и примечание
     * контакта — путь, которым получена коллекция, на удаление не влияет.
     * Это свойство роута амо, а не недосмотр библиотеки.
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 200 `{"status":"no note","id":N}`,
     *              то есть примечания уже нет. У задач тот же факт выглядит иначе
     *              (400 + `status:fail`) — один приватный роут отвечает на
     *              соседних типах по-разному, разбирает это Deleter,
     *              вызывающему достаточно bool
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->notes($ids);
    }

    public function filterId(int $id): self
    {
        $this->filter['id'] = $id;

        return $this;
    }

    public function filterCallIn(): self
    {
        $this->addFilterNoteType('call_in');

        return $this;
    }

    public function filterCallOut(): self
    {
        $this->addFilterNoteType('call_out');

        return $this;
    }

    public function filterEmail(): self
    {
        $this->addFilterNoteType('amomail_message');

        return $this;
    }

    public function filterCommon(): self
    {
        $this->addFilterNoteType('common');

        return $this;
    }

    /**
     * $this->filter — array<string, mixed>, значение по 'note_type' для
     * phpstan mixed, поэтому не аппендим напрямую (offsetAccess на mixed),
     * а гардим is_array() перед [].
     */
    private function addFilterNoteType(string $type): void
    {
        $types = $this->filter['note_type'] ?? [];
        $types = is_array($types) ? $types : [];
        $types[] = $type;
        $this->filter['note_type'] = $types;
    }

    public function filterUpdatedAt(int $from, int $to): self
    {
        $this->filter['updated_at'] = compact('from', 'to');

        return $this;
    }

}
