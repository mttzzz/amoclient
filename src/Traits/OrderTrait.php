<?php

namespace mttzzz\AmoClient\Traits;

trait OrderTrait
{
    public function orderByCreatedAtAsc(): self
    {
        $this->order['created_at'] = 'asc';

        return $this;
    }

    public function orderByCreatedAtDesc(): self
    {
        $this->order['created_at'] = 'desc';

        return $this;
    }

    public function orderByUpdatedAtAsc(): self
    {
        $this->order['updated_at'] = 'asc';

        return $this;
    }

    public function orderByUpdatedAtDesc(): self
    {
        $this->order['updated_at'] = 'desc';

        return $this;
    }

    public function orderByIdAsc(): self
    {
        $this->order['id'] = 'asc';

        return $this;
    }

    public function orderByIdDesc(): self
    {
        $this->order['id'] = 'desc';

        return $this;
    }
}
