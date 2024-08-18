<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Support\Collection;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\StatusTrait;

    /**
     * @var array<string, mixed>
     */
    public $_embedded = [];

    public ?string $name = null;

    public ?int $sort = null;

    public ?bool $is_main = null;

    public ?bool $is_unsorted_on = null;

    protected string $entity = 'leads/pipelines';

    /**
     * @return Collection<int, Status>
     */
    public function statuses(): Collection
    {
        /**
         * @var array<int, array<string, mixed>> $statusesArray
         */
        $statusesArray = $this->_embedded['statuses'] ?? [];

        return collect($statusesArray)->map(function ($status) {
            return new Status(
                $status['id'],
                $status['name'],
                $status['sort'],
                $status['is_editable'],
                $status['pipeline_id'],
                $status['color'],
                $status['type'],
                $status['account_id'],
                $status['_links'],
                $status['descriptions']
            );
        });
    }
}

class Status
{
    public int $id;

    public string $name;

    public int $sort;

    public bool $is_editable;

    public int $pipeline_id;

    public string $color;

    public int $type;

    public int $account_id;

    /**
     * @var array<string, array<string, string>>
     */
    public array $_links;

    /**
     * @var array<int, string>
     */
    public array $descriptions;

    /**
     * @param  array<string, array<string, string>>  $_links
     * @param  array<int, string>  $descriptions
     */
    public function __construct(
        int $id,
        string $name,
        int $sort,
        bool $is_editable,
        int $pipeline_id,
        string $color,
        int $type,
        int $account_id,
        array $_links,
        array $descriptions
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->sort = $sort;
        $this->is_editable = $is_editable;
        $this->pipeline_id = $pipeline_id;
        $this->color = $color;
        $this->type = $type;
        $this->account_id = $account_id;
        $this->_links = $_links;
        $this->descriptions = $descriptions;
    }
}
