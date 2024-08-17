<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;

class Note extends AbstractModel
{
    protected $entityId;

    protected $entity;

    public function __construct(PendingRequest $http, $entity, $entityId)
    {
        $this->entity = $entity.'/notes';
        $this->entityId = $entityId;
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Note(['id' => $id], $this->http, $this->entity, $this->entityId);
    }

    public function filterId($id)
    {
        $this->filter['id'] = $id;

        return $this;
    }

    public function filterCallIn()
    {
        $this->filter['note_type'][] = 'call_in';

        return $this;
    }

    public function filterCallOut()
    {
        $this->filter['note_type'][] = 'call_out';

        return $this;
    }

    public function filterEmail()
    {
        $this->filter['note_type'][] = 'amomail_message';

        return $this;
    }

    public function filterCommon()
    {
        $this->filter['note_type'][] = 'common';

        return $this;
    }

    public function filterUpdatedAt(int $from, int $to)
    {
        $this->filter['updated_at'] = compact('from', 'to');

        return $this;
    }

    public function orderUpdatedAtAsc()
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderUpdatedAtDesc()
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderIdAsc()
    {
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderIdDesc()
    {
        $this->order['id'] = 'desc';

        return $this;
    }
}
