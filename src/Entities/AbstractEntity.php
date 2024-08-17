<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;

abstract class AbstractEntity
{
    protected $http;

    // Define class properties here
    public $custom_fields_values = [];

    public $group_id;

    public ?int $updated_by;

    public ?int $closest_task_at;

    public $is_deleted;

    public $is_unsorted;

    public $_links;

    public $loss_reason_id;

    public ?int $closed_at;

    public $score;

    public $labor_cost;

    public $catalog_id;

    public $_embedded = [];

    public $metadata;

    public function __construct($data = [], ?PendingRequest $http = null)
    {
        $this->http = $http;
        $this->setData($data);
    }

    protected function setData($data)
    {
        try {
            $intFields = ['id', 'price', 'status_id', 'responsible_user_id', 'duration'];

            $data['custom_fields_values'] = empty($data['custom_fields_values']) ? [] : $data['custom_fields_values'];

            foreach ($data as $key => $item) {
                $this->{$key} = (in_array($key, $intFields) && $item) ? (int) $item : $item;
            }
        } catch (Exception $e) {
        }
    }

    public function toArray()
    {
        $item = [];
        $except = ['http', 'cf', 'entity', 'notes', '_links', 'closest_task_at', 'updated_by',
            'fieldPhoneId', 'fieldEmailId', 'tasks', 'links', 'enums'];
        foreach ($this as $key => $value) {
            if (! in_array($key, $except)) {
                $item[$key] = $value;
            }

            if (get_class($this) === CustomField::class && $key === 'enums') {
                $item[$key] = $value;
            }

            if (empty($item[$key]) && ! in_array($key, ['is_main', 'duration', 'disabled'])) {
                unset($item[$key]);
            }

            if ($key === 'disabled' && is_null($item[$key])) {
                unset($item[$key]);
            }

        }

        return $item;
    }
}
