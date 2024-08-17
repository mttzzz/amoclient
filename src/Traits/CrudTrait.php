<?php

namespace mttzzz\AmoClient\Traits;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

trait CrudTrait
{
    protected function findEntity($id)
    {
        try {
            return $this->http->get($this->entity.'/'.$id,
                ['with' => implode(',', $this->with)])
                ->throw()->json() ?? [];
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function create(array $entities)
    {
        try {
            if (! empty($entities)) {
                return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();
            }

            return [];

        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function update(array $entities)
    {
        try {
            if (! empty($entities)) {
                return $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json();
            }

            return [];
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
