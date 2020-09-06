<?php

namespace mttzzz\AmoClient\Models;
//sadsd
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class AbstractModel
{
    protected $http, $entity;
    protected $with = [], $page, $limit, $query, $order = [];
    protected array $filter = [];

    public function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    public function get()
    {
        try {
            $query = [];
            foreach (['with', 'page', 'limit', 'query', 'filter', 'order'] as $param) {
                if (!empty($this->$param)) {
                    $query[$param] = $param === 'with' ? implode(',', $this->with) : $this->$param;
                }
            }
            $data = $this->http->get($this->entity, $query)->throw()->json();
            $data = is_null($data) ? [] : $data;
            return isset($data['_embedded']) ? Arr::first($data['_embedded']) : $data;
        } catch (RequestException $e) {
            return json_decode($e->response->body(), 1) ?? [];
        }
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

    protected function prepareEntities($entities)
    {
        foreach ($entities as $key => $entity) {
            $entities[$key] = $entity->toArray();
        }
        return $entities;
    }

    public function each($function, $limit = 50)
    {
        $page = 1;
        $this->limit = $limit;
        while (true) {
            $chunk = $this->page($page++)->get();
            $function($chunk);
            if (count($chunk) < $limit) break;
        }
    }
}
