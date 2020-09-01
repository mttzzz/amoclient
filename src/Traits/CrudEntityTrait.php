<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;

trait CrudEntityTrait
{
    public function update()
    {
        try {
            return $this->http->patch($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function create()
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function delete()
    {
        $this->http->delete($this->entity, ['id' => $this->id])->throw()->json();
        return null;
    }
}
