<?php

namespace mttzzz\AmoClient\Traits;

trait OrderTrait
{
    public function orderByCreatedAtAsc()
    {
        $this->order['created_at'] = 'asc';

        return $this;
    }

    public function orderByCreatedAtDesc()
    {
        $this->order['created_at'] = 'desc';

        return $this;
    }

    public function orderByUpdatedAtAsc()
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderByUpdatedAtDesc()
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderByIdAsc()
    {
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderByIdDesc()
    {
        $this->order['id'] = 'desc';

        return $this;
    }
}
