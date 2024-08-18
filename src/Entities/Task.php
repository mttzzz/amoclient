<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Traits;

class Task extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    public int $entity_id;

    public string $entity_type;

    public int $duration;

    public bool $is_completed;

    public int $task_type_id;

    public string $text;

    /**
     * @var array<mixed>
     */
    public array $result;

    public int $complete_till;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, string $entityType = 'leads', ?int $entityId = null)
    {
        parent::__construct($data, $http);
        $this->entity = 'tasks';
        $this->entity_type = $entityType;
        $this->entity_id = $entityId;
    }

    /**
     * @return array<mixed>
     */
    public function add(
        string $text, ?int $responsible_user_id = null,
        ?int $completeTill = null, ?int $duration = null, ?int $type = 1
    ): array {
        $this->text = $text;
        if ($responsible_user_id) {
            $this->responsible_user_id = $responsible_user_id;
        }
        $this->complete_till = $completeTill ?? time();
        $this->duration = $duration;
        $this->task_type_id = $type;

        return $this->create();
    }

    public function setResultText(string $text): self
    {
        $this->result['text'] = $text;

        return $this;
    }
}
