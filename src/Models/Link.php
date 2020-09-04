<?php


namespace mttzzz\AmoClient\Models;


use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Entities;

class Link extends AbstractModel
{
    protected $entity;

    public function __construct(PendingRequest $http, $entity)
    {
        $this->entity = $entity . '/links';
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Link(['id' => $id], $this->http, $this->entity);
    }

    public function catalogElement($catalogElementId, $catalogId, $quantity = null)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int)$catalogElementId;
        $entity->to_entity_type = 'catalog_elements';
        $entity->metadata['catalog_id'] = (int)$catalogId;
        if ($quantity) {
            $entity->metadata['quantity'] = (int)$quantity;
        }
        return $entity;
    }

    public function contact($contactId, $mainContact = false)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int)$contactId;
        $entity->to_entity_type = 'contacts';
        $entity->metadata['is_main'] = (bool)$mainContact;
        return $entity;
    }

    public function companies($companyId)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int)$companyId;
        $entity->to_entity_type = 'companies';
        $entity->metadata = null;
        return $entity;
    }

    public function link(array $entities)
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/link", $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function unlink(array $entities)
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/unlink", $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }
}
