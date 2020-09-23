<?php


namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Company extends AbstractEntity
{
    use Traits\CustomFieldTrait, Traits\TagTrait, Traits\PhoneTrait, Traits\EmailTrait, Traits\CrudEntityTrait;

    protected $entity = 'companies';

    public $id, $name, $responsible_user_id;
    public $custom_fields_values = [];
    public $_embedded = [], $notes, $tasks, $links;

    public function __construct($data, PendingRequest $http, $cf)
    {
        $this->cf = $cf;
        parent::__construct($data, $http);
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
    }
}
