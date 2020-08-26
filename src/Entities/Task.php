<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;

class Task extends AbstractEntity
{
    protected $entity = 'tasks';
    public $id, $responsible_user_id, $entity_id, $entity_type, $duration,
        $is_completed, $task_type_id, $text, $result, $complete_till;

    public function __construct($data, PendingRequest $http)
    {
        parent::__construct($data, $http);
    }

    public function toArray()
    {
        $item = parent::toArray();
        foreach (['id', 'entity_id', 'entity_type'] as $key) {
            if (empty($item->$key)) {
                unset($item->$key);
            }
        }
        return $item;
    }
}
