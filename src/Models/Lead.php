<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Entities;

class Lead
{
    //use Traits\QueryTrait;

    //protected $entity = 'leads';
    //protected $class = 'Lead';
    protected $http;
    protected $with = [], $page = 0, $limit = 50, $query, $filter = [], $order = [];

    public function __construct($http)
    {
        $this->http = $http;
    }

    public function find($id)
    {
        return $data = $this->http->get('leads/' . $id, [
                'with' => implode(',', $this->with),
                'page' => $this->page,
                'limit' => $this->limit,
                'query' => $this->query,
                'filter' => $this->filter,
                'order' => $this->order,
            ])->throw()->json() ?? [];
    }

    public function create(array $entities)
    {
        try {
            return $this->http->post('leads', $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function update(array $entities)
    {
        try {
            return $this->http->patch('leads', $this->prepareEntities($entities))->throw()->json() ?? [];
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
    }

    public function prepareEntities($entities)
    {
        foreach ($entities as $key => $entity) {
            $entities[$key] = $entity->toArray();
        }
        return $entities;
    }

    public function entity($id = null)
    {
        return new Entities\Lead(['id' => $id], $this->http);
    }

    public function get()
    {
        return $this->http->get('leads', [
                'with' => implode(',', $this->with),
                'page' => $this->page,
                'limit' => $this->limit,
                'query' => $this->query,
                'filter' => $this->filter,
                'order' => $this->order,
            ])->throw()->json()['_embedded']['leads'] ?? [];
    }

    public function page(int $page)
    {
        $this->page = $page;
        return $this;
    }

    public function limit(int $limit)
    {
        $limit = $limit > 250 ? 250 : $limit;
        $this->limit = $limit;
        return $this;
    }

    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    public function orderByCreatedAtAsc()
    {
        $this->order['created_at'] = 'asc';
        return $this;
    }

    public function orderByCreatedAtDesc()
    {
        $this->order['created_at'] = 'desc';
        return $this;
    }

    public function orderByUpdatedAtAsc()
    {
        $this->order['updated_at'] = 'asc';
        return $this;
    }

    public function orderByUpdatedAtDesc()
    {
        $this->order['updated_at'] = 'desc';
        return $this;
    }

    public function orderByIdAsc()
    {
        $this->order['id'] = 'asc';
        return $this;
    }

    public function orderByIdDesc()
    {
        $this->order['id'] = 'desc';
        return $this;
    }

    public function withCatalogElements()
    {
        $this->with[] = 'catalog_elements';
        return $this;
    }

    public function withIsPriceModifiedByRobot()
    {
        $this->with[] = 'is_price_modified_by_robot';
        return $this;
    }

    public function withLossReason()
    {
        $this->with[] = 'loss_reason';
        return $this;
    }

    public function withContacts()
    {
        $this->with[] = 'contacts';
        return $this;
    }

    public function withOnlyDeleted()
    {
        $this->with[] = 'only_deleted';
        return $this;
    }

    /*
    public function find($id): Entities\Lead
    {
        return parent::find($id);
    }

    public function entity()
    {
        return new Entities\Lead([], $this->http);
    }

    public function note()
    {
        return new Note($this->http, $this->entity);
    }
    */
}
