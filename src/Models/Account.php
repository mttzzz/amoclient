<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;

class Account extends AbstractModel
{
    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);

        $this->entity = 'account';
    }

    public function withAmojoId(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withAmojoRights(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withUsersGroups(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withTaskTypes(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withVersion(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withEntityNames(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withDatetimeSettings(): self
    {
        return $this->addWith(__FUNCTION__);
    }
}
