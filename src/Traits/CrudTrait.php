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

    public function create(array $entities)
    {
        try {
            return $this->http->post($this->entity, $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function update(array $entities)
    {
        try {
            return $this->http->patch($this->entity, $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }
}
