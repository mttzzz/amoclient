<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

abstract class AbstractModel
{
    protected $http, $entity;
    protected $with = [], $page, $limit, $query, $filter = [], $order = [];

    public function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    public function get()
    {
        $query = [];
        foreach (['with', 'page', 'limit', 'query', 'filter', 'order'] as $param) {
            if (!empty($this->$param)) {
                $query[$param] = $param === 'with' ? implode(',', $this->with) : $this->$param;
            }
        }
        return $this->http->get($this->entity, $query)->throw()->json()['_embedded'][$this->entity] ?? [];
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

    protected function addWith($with)
    {
        $this->with[] = Str::snake(Str::after($with, 'with'));
        return $this;
    }
}
