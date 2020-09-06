<?php


namespace mttzzz\AmoClient\Traits;


use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

trait CrudEntityTrait
{
    public function update()
    {
        try {
            return $this->http->patch($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function create()
    {
        try {
            return $this->http->post($this->entity, [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function delete()
    {
        try {
            $this->http->delete($this->entity, ['id' => $this->id])->throw()->json();
            return null;
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
