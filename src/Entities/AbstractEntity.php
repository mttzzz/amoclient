<?php


namespace mttzzz\AmoClient\Entities;


use Exception;
use Illuminate\Http\Client\PendingRequest;

abstract class AbstractEntity
{
    protected $http;

    public function __construct($data = [], PendingRequest $http = null)
    {
        $this->http = $http;
        $this->setData($data);
    }

    protected function setData($data)
    {
        try {
            foreach ($data as $key => $item) {
                $this->{$key} = ($key === 'id' && $item) ? (int)$item : $item;
            }
        } catch (Exception $e) {
        }
    }

    public function toArray()
    {
        $item = [];
        $except = ['http', 'cf', 'entity', 'notes', '_links', 'closest_task_at', 'updated_by', 'created_by',
            'fieldPhoneId', 'fieldEmailId', 'tasks', 'links'];
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
