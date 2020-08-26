<?php


namespace mttzzz\AmoClient\Entities;


use Exception;
use Illuminate\Http\Client\PendingRequest;

abstract class AbstractEntity
{
    private $http;

    public function __construct($data = [], PendingRequest $http = null)
    {
        $this->http = $http;
        try {
            foreach ($data as $key => $item) {
                $this->{$key} = $item;
            }
        } catch (Exception $e) {
        }
    }

    public function update()
    {
        $this->checkID();
        $this->http->patch($this->entity, $this->data())->throw();
        return $this;
    }

    public function create(): int
    {
        $result = $this->http->post($this->entity, $this->data())->throw()->json();
        return $result['_embedded'][$this->entity][0]['id'];
    }

    public function delete()
    {
        $this->checkID();
        $this->http->delete($this->entity, ['id' => $this->id])->throw()->json();
        return null;
    }

    private function checkID()
    {
        if (!$this->id) throw new Exception('id is empty! Set id, please!');
    }

    public function toArray()
    {
        $item = [];
        $except = ['http', 'entity', '_links', 'closest_task_at', 'updated_by', 'created_by'];
        foreach ($this as $key => $value) {
            if (!in_array($key,$except)) {
                $item[$key] = $value;
            }
        }
        return $item;
    }

    protected function data()
    {
        return [$this->toArray()];
    }
}
