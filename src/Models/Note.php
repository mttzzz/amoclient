<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class Note extends AbstractModel
{
    protected ?int $entityId;

    public function __construct(PendingRequest $http, string $entity, ?int $entityId)
    {
        $this->entity = $entity.'/notes';
        $this->entityId = $entityId;
        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\Note
    {
        return new Entities\Note(['id' => $id], $this->http, $this->entity, $this->entityId);
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

    public function orderUpdatedAtAsc(): self
    {
        $this->order = []; // обнуляем сортировку, потому как может быть только 1
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderUpdatedAtDesc(): self
    {
        $this->order = []; // обнуляем сортировку, потому как может быть только 1
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderIdAsc(): self
    {
        $this->order = []; // обнуляем сортировку, потому как может быть только 1
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderIdDesc(): self
    {
        $this->order = []; // обнуляем сортировку, потому как может быть только 1
        $this->order['id'] = 'desc';

        return $this;
    }
}
