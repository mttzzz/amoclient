<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class CustomField extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity;

    public string $type;

    public string $name;

    public string $code;

    public int $sort;

    public int $group_id;

    public bool $is_api_only;

    /**
     * @var array<array{status_id: int, pipeline_id: int}>
     */
    public array $required_statuses;

    public ?string $remind;

    /**
     * @var array<mixed>
     */
    public array $enums;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $entity)
    {
        $this->entity = $entity;
        parent::__construct($data, $http);
    }

    /**
     * @param  array<mixed>  $data
     */
    public function setData(array $data): void
    {
        parent::setData($data);
    }
}
