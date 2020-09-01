<?php


namespace mttzzz\AmoClient\Entities;


use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class Task extends AbstractEntity
{

    use Traits\CrudEntityTrait;

    protected $entity = 'tasks';
    public $id, $responsible_user_id, $entity_id, $entity_type, $duration,
        $is_completed, $task_type_id, $text, $result, $complete_till;

    public function __construct($data, PendingRequest $http, $entityType = null, $entityId = null)
    {
        $this->entity_type = $entityType;
        $this->entity_id = $entityId;
        parent::__construct($data, $http);
    }

    public function add($text, $responsible_user_id = null, $completeTill = null, $duration = null, $type = 2)
    {
        $this->text = $text;
        $this->responsible_user_id = $responsible_user_id;
        $this->complete_till = $completeTill ?? time();
        $this->duration = $duration;
        $this->task_type_id = $type;
        return $this->create();
    }
}
