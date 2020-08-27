<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;

trait CrudTrait
{
    protected function findEntity($id)
    {
        return $data = $this->http->get($this->entity . '/' . $id, ['with' => implode(',', $this->with)])
                ->throw()->json() ?? [];
    }

    protected function createEntity(array $entities)
    {
        try {
            return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    protected function updateEntity(array $entities)
    {
        try {
            return $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    protected function prepareEntities($entities)
    {
        foreach ($entities as $key => $entity) {
            $entities[$key] = $entity->toArray();
        }
        return $entities;
    }
}
