<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;


trait CrudTrait
{
    public function create($entities)
    {
        try {
            return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function update($entities)
    {
        try {
            return $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function delete(array $ids)
    {
        try {
            return $this->http->delete($this->entity, ['id' => $ids])->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    private function prepareEntities($entities)
    {
        foreach ($entities as &$entity) {
            $entity = is_object($entity) ? $entity->toArray() : $entity;
        }
        return $entities;
    }
}
