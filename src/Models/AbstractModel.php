<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

abstract class AbstractModel
{
    protected PendingRequest $http;

    /** @var string[] */
    protected array $with = [];

    protected int $page;

    protected int $limit;

    /** @var array<string, mixed> */
    protected $query;

    protected string $entity;

    /** @var array<string, mixed> */
    protected array $order = [];

    /** @var array<string, mixed> */
    protected array $filter = [];

    public function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AmoCustomException
     */
    public function get(): array
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

            if (isset($data['_embedded']) && is_array($data['_embedded'])) {
                $embeddedData = Arr::first($data['_embedded']);
            } else {
                $embeddedData = $data;
            }

            return $embeddedData;
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function limit(int $limit): self
    {
        $limit = $limit > 150 ? 150 : $limit;
        $this->limit = $limit;

        return $this;
    }

    protected function addWith(string $with): static
    {
        $this->with[] = Str::snake(Str::after($with, 'with'));

        return $this;
    }

    /**
     * @param  array<int, object>  $entities
     * @return array<int, array<string, mixed>>
     */
    protected function prepareEntities(array $entities): array
    {
        foreach ($entities as $key => $entity) {
            $entities[$key] = $entity->toArray();
        }

        return $entities;
    }

    public function each(callable $function, int $limit = 150): void
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allItems(int $limit = 150): array
    {
        $result = [];
        $this->each(function ($items) use (&$result) {
            $result = array_merge($result, $items);
        }, $limit);

        return $result;
    }
}
