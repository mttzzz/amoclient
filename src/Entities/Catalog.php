<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Catalog extends AbstractEntity
{
    use Traits\ElementTrait;

    protected $entity = 'catalogs';
    protected $http;
    public $id, $name;

    public function __construct($data, PendingRequest $http)
    {
        $this->http = $http;
        parent::__construct($data, $http);
    }

    public function entityElement($id = null)
    {
        return new Entities\CatalogElement(['id' => $id], $this->http, $this->id);
    }

    public function findElement($id)
    {
        $entities = $this->http->get("catalogs/{$this->id}/elements", ['id' => $id])->json();
        if ($entity = $entities['_embedded']['elements'][0] ?? null) {
            return new Entities\CatalogElement($entity, $this->http, $this->id);
        }
    }
}
