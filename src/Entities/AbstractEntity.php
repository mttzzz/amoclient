<?php


namespace mttzzz\AmoClient\Entities;


use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

abstract class AbstractEntity
{
    private $http;

    public function __construct($data = [], PendingRequest $http = null)
    {
        $this->http = $http;
        $this->setData($data);

    }

    protected function setData($data)
    {
        try {
            foreach ($data as $key => $item) {
                $this->{$key} = $item;
            }
        } catch (Exception $e) {
        }
    }

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

    public function toArray()
    {
        $item = [];
        $except = ['http', 'entity', 'notes', '_links', 'closest_task_at', 'updated_by', 'created_by', 'fieldPhoneId', 'fieldEmailId'];
        foreach ($this as $key => $value) {
            if (!in_array($key, $except)) {
                $item[$key] = $value;
            }
            if (empty($item[$key]) && !in_array($key, ['is_main'])) {
                unset($item[$key]);
            }
        }
        return $item;
    }
}
