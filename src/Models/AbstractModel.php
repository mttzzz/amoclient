<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

abstract class AbstractModel
{
    protected $http;

    protected $with = [];

    protected $page;

    protected $limit;

    protected $query;

    protected $entity;

    protected $order = [];

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
                if (! empty($this->$param)) {
                    $query[$param] = $param === 'with' ? implode(',', $this->with) : $this->$param;
                }
            }
            $data = $this->http->get($this->entity, $query)->throw()->json();
            $data = is_null($data) ? [] : $data;
            if (! $this->page) {
                $this->filter = [];
            }

            return isset($data['_embedded']) ? Arr::first($data['_embedded']) : $data;
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function page(int $page)
    {
        $this->page = $page;

        return $this;
    }

    public function limit(int $limit)
    {
        $limit = $limit > 150 ? 150 : $limit;
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

    public function each($function, $limit = 150)
    {
        $page = 1;
        $this->limit = $limit;
        while (true) {
            $chunk = $this->page($page++)->get();
            $function($chunk);
            if (count($chunk) < $limit) {
                break;
            }
        }
    }

    public function allItems($limit = 150)
    {
        $result = [];
        $this->each(function ($items) use (&$result) {
            $result = array_merge($result, $items);
        }, $limit);

        return $result;
    }
}
