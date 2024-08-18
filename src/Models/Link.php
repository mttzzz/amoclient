<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Entities;

class Link extends AbstractModel
{
    public function __construct(PendingRequest $http, string $entity)
    {
        parent::__construct($http);
        $this->entity = $entity.'/links';
    }

    public function entity(?int $id = null): Entities\Link
    {
        return new Entities\Link(['id' => $id], $this->http, $this->entity);
    }

    public function catalogElement(int $catalogElementId, int $catalogId, ?int $quantity = null): Entities\Link
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int) $catalogElementId;
        $entity->to_entity_type = 'catalog_elements';
        $entity->metadata['catalog_id'] = (int) $catalogId;
        if ($quantity) {
            $entity->metadata['quantity'] = (int) $quantity;
        }

        return $entity;
    }

    public function contact(int $contactId, bool $mainContact = false): Entities\Link
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int) $contactId;
        $entity->to_entity_type = 'contacts';
        $entity->metadata['is_main'] = (bool) $mainContact;

        return $entity;
    }

    public function companies(int $companyId): Entities\Link
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int) $companyId;
        $entity->to_entity_type = 'companies';
        $entity->metadata = null;

        return $entity;
    }

    public function customers(int $customerId): Entities\Link
    {
        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int) $customerId;
        $entity->to_entity_type = 'customers';
        $entity->metadata = null;

        return $entity;
    }

    /**
     * @param  array<mixed>  $entities
     * @return array<mixed>
     */
    public function link(array $entities): array
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/link", $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), true) ?? [];
        }
    }

    /**
     * @param  array<mixed>  $entities
     * @return array<mixed>
     */
    public function unlink(array $entities): array
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/unlink", $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), true) ?? [];
        }
    }
}
