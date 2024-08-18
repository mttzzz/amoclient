<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Support\Collection;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\StatusTrait;

    protected string $entity = 'leads/pipelines';

    /**
     * @var array<array<string, mixed>>
     */
    public array $_embedded = ['statuses' => []];

    public string $name;

    public int $sort = 1;

    public bool $is_main = false;

    public bool $is_unsorted_on = true;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function statuses(): Collection
    {
        /** @var Collection<int, array<string, mixed>> $statuses */
        $statuses = collect($this->_embedded['statuses']);

        return $statuses;
    }
}
