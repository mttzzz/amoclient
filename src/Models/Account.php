<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

class Account extends AbstractModel
{
    public int $id;

    public function __construct(PendingRequest $http, int $id)
    {
        parent::__construct($http);

        $this->entity = 'account';
        $this->id = $id;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AmoCustomException
     */
    public function get(): array
    {
        try {
            $query = [];

            if (! empty($this->with)) {
                $query['with'] = implode(',', $this->with);
            }
            $data = $this->http->get($this->entity, $query)->throw()->json();
            $data = is_null($data) ? [] : $data;

            return $data;
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
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
