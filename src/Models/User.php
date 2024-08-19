<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;

class User extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
        $this->entity = 'users';
    }

    public function withRole(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withGroup(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withUuid(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withAmojoId(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    /**
     * @return array<mixed>
     */
    public function find(int $id): array
    {
        return $this->http->get("$this->entity/$id")->throw()->json();
    }
}
