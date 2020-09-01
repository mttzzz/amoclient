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

    public function catalogElement($to_entity_id, $catalog_id, $quantity = null)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = $to_entity_id;
        $entity->to_entity_type = 'catalog_elements';
        $entity->metadata['catalog_id'] = $catalog_id;
        if ($quantity) {
            $entity->metadata['quantity'] = $quantity;
        }
        return $entity;
    }

    public function contact($contactId, $main_contact = false)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = $contactId;
        $entity->to_entity_type = 'contacts';
        $entity->metadata['is_main'] = $main_contact;
        return $entity;
    }

    public function companies($companyId)
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = $companyId;
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
