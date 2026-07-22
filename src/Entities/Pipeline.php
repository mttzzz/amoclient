<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\StatusTrait;

    protected string $entity = 'leads/pipelines';

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $_embedded = [];

    public string $name;

    public int $sort = 5000;

    public bool $is_main = false;

    public bool $is_archive = false;

    public bool $is_unsorted_on = true;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function statuses(): Collection
    {

        if (! isset($this->_embedded['statuses'])) {
            /** @var Collection<int, array<string, mixed>> $empty */
            $empty = collect();

            return $empty;
        }

        /** @var array<int, array<string, mixed>> $statusesArray */
        $statusesArray = $this->_embedded['statuses'];

        /** @var Collection<int, array<string, mixed>> $statuses */
        $statuses = collect($statusesArray);

        return $statuses;
    }

    /**
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function update(): array
    {
        try {
            $result = $this->http->patch("$this->entity/$this->id", $this->toArray())->throw()->json();

            return is_array($result) ? $result : [];
        } catch (ConnectionException|RequestException $e) {
            throw new AmoCustomException($e);
        }
    }
}
