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
        $this->filter['note_type'][] = 'call_in';

        return $this;
    }

    public function filterCallOut(): self
    {
        $this->filter['note_type'][] = 'call_out';

        return $this;
    }

    public function filterEmail(): self
    {
        $this->filter['note_type'][] = 'amomail_message';

        return $this;
    }

    public function filterCommon(): self
    {
        $this->filter['note_type'][] = 'common';

        return $this;
    }

    public function filterUpdatedAt(int $from, int $to): self
    {
        $this->filter['updated_at'] = compact('from', 'to');

        return $this;
    }

    public function orderUpdatedAtAsc(): self
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderUpdatedAtDesc(): self
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderIdAsc(): self
    {
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderIdDesc(): self
    {
        $this->order['id'] = 'desc';

        return $this;
    }
}
