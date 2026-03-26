<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;

abstract class AbstractEntity
{
    protected PendingRequest $http;

    /**
     * @var array<string, mixed>
     **/
    protected array $attributes = [];

    protected string $entity;

    public ?int $id = null;

    public int $responsible_user_id = 0;

    /**
     * @var array<mixed>
     **/
    public array $custom_fields_values = [];

    public int $group_id;

    public ?int $updated_by;

    public ?int $closest_task_at;

    public ?bool $is_deleted;

    public bool $is_unsorted;

    /**
     * @var array<string>
     **/
    public array $_links;

    public ?int $loss_reason_id;

    public ?int $closed_at;

    public ?int $score;

    public ?int $labor_cost;

    public int $catalog_id;

    /**
     * @var array<mixed>
     **/
    public array $_embedded = [];

    /**
     * @var array<mixed>
     **/
    public array $metadata = [];

    /**
     * @param  array<mixed>  $data
     **/
    public function __construct(array $data, PendingRequest $http)
    {
        $this->http = $http;
        $this->setData($data);
    }

    /**
     * @param  array<mixed>  $data
     **/
    protected function setData(array $data): void
    {
        try {
            $intFields = ['id', 'price', 'status_id', 'responsible_user_id', 'duration'];

            $data['custom_fields_values'] = empty($data['custom_fields_values']) ? [] : $data['custom_fields_values'];

            foreach ($data as $key => $item) {
                $value = (in_array($key, $intFields, true) && $item) ? (int) $item : $item;

                if (property_exists($this, $key)) {
                    $this->{$key} = $value;

                    continue;
                }

                $this->attributes[$key] = $value;
            }
        } catch (Exception $e) {
        }
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __unset(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $item = [];

        $except = ['http', 'cf', 'entity', 'notes', '_links', 'closest_task_at', 'updated_by',
            'fieldPhoneId', 'fieldEmailId', 'tasks', 'links', 'enums', 'attributes'];

        foreach (get_object_vars($this) as $key => $value) {
            if (! in_array($key, $except)) {
                $item[$key] = $value;
            }

            if (get_class($this) === CustomField::class && $key === 'enums') {
                $item[$key] = $value;
            }

            if (empty($item[$key]) && ! in_array($key, ['duration', 'disabled', 'can_link_multiple', 'is_main'])) {
                unset($item[$key]);
            }

            if ($key === 'disabled' && is_null($item[$key])) {
                unset($item[$key]);
            }
        }

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $except, true)) {
                continue;
            }

            $item[$key] = $value;

            if (empty($item[$key]) && ! in_array($key, ['duration', 'disabled', 'can_link_multiple', 'is_main'], true)) {
                unset($item[$key]);
            }

            if ($key === 'disabled' && is_null($item[$key])) {
                unset($item[$key]);
            }
        }

        return $item;
    }
}
