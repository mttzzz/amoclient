<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Traits;

abstract class AbstractModel
{
    use Traits\CrudTrait;

    protected $http;

    public function __construct($http = null)
    {
        $this->http = $http;
    }

    public function find($id)
    {
        $data = $this->entity === 'notes' ? ['id' => $id, 'parentEntity' => $this->parentEntity] : ['id' => $id];
        try {
            $entities = $this->http->get($this->entity, $data)->throw()->json();
            if ($entity = $entities['_embedded'][$this->entity][0] ?? null) {
                $class = "\mttzzz\AmoClient\Entities\\{$this->class}";
                $entity = $this->entity === 'notes' ? new $class($entity, $this->http, $this->parentEntity)
                    : new $class($entity, $this->http);
            }
            return $entity;
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }
}
