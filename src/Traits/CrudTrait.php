<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities\AbstractEntity;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

trait CrudTrait
{
    /**
     * @return array<string, mixed>
     *
     * @throws AmoCustomException
     */
    protected function findEntity(int $id): array
    {
        try {
            $result = $this->http->get($this->entity.'/'.$id,
                ['with' => implode(',', $this->with)])
                ->throw()->json();

            if (! is_array($result)) {
                return [];
            }

            /* amo API отдаёт JSON-объект — ключи всегда строки, но json()
             * типизирован как mixed, is_array() даёт лишь array<mixed>. */
            /** @var array<string, mixed> $result */
            return $result;
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @param  list<AbstractEntity>  $entities
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function create(array $entities): array
    {
        try {
            if (! empty($entities)) {
                $result = $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();

                return is_array($result) ? $result : [];
            }

            return [];

        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @param  list<AbstractEntity>  $entities
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function update(array $entities): array
    {
        try {
            if (! empty($entities)) {
                $result = $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json();

                return is_array($result) ? $result : [];
            }

            return [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
