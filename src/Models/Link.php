<?php

namespace mttzzz\AmoClient\Models;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

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
        $entity->metadata = [];

        return $entity;
    }

    public function customers(int $customerId): Entities\Link
    {
        if (! in_array(Str::before($this->entity, '/'), ['contacts', 'companies'])) {
            throw new Exception('Customer can be linked only to contact or company');
        }

        $entity = new Entities\Link(['id' => null], $this->http, $this->entity);
        $entity->to_entity_id = (int) $customerId;
        $entity->to_entity_type = 'customers';
        $entity->metadata = [];

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
            return $this->http->post("$str/link", $this->prepareEntities($entities))->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    /**
     * @param  array<mixed>  $entities
     */
    public function unlink(array $entities): null
    {
        $str = Str::beforeLast($this->entity, '/');
        try {
            return $this->http->post("$str/unlink", $this->prepareEntities($entities))->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
