<?php

namespace mttzzz\AmoClient\Entities;

use mttzzz\AmoClient\Traits;

class Task extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'tasks';

    public ?int $id = null;

    public ?int $responsible_user_id = null;

    public ?int $entity_id = null;

    public ?string $entity_type = null;

    public ?int $duration = null;

    public ?bool $is_completed = null;

    public ?int $task_type_id = null;

    public ?string $text = null;

    public ?string $result = null;

    public ?int $complete_till = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data, int $entityId, string $entityType)
    {
        parent::__construct($data);
        $this->entity_id = $entityId;
        $this->entity_type = $entityType;
    }

    public function add(int $completeTill, int $duration, int $responsible_user_id, string $text, int $type): void
    {
        $this->complete_till = $completeTill;
        $this->duration = $duration;
        $this->responsible_user_id = $responsible_user_id;
        $this->text = $text;
        $this->task_type_id = $type;
    }

    public function setResultText(string $text): void
    {
        $this->result = $text;
    }
}
