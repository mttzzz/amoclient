<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

trait CrudTrait
{
    /**
     * @throws AmoCustomException
     */
    protected function findEntity(int $id): array
    {
        try {
            return $this->http->get($this->entity.'/'.$id,
                ['with' => implode(',', $this->with)])
                ->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @throws AmoCustomException
     */
    public function create(array $entities): array
    {
        try {
            if (!empty($entities)) {
                return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();
            }

            return [];

        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @throws AmoCustomException
     */
    public function update(array $entities): array
    {
        try {
            if (!empty($entities)) {
                return $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json();
            }

            return [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
