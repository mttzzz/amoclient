<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Contact extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait, Traits\EmailTrait, Traits\PhoneTrait, Traits\TagTrait;

    public ?string $first_name;

    public ?string $last_name;

    public ?string $name;

    public int $created_by;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $custom_fields_values = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $_embedded = [];

    public Models\Note $notes;

    public Task $tasks;

    public Models\Link $links;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<mixed>  $cf
     * @param  array<mixed>  $enums
     */
    public function __construct(array $data, PendingRequest $http, array $cf, array $enums, Deleter $deleter)
    {
        parent::__construct($data, $http);
        $this->entity = 'contacts';
        $this->cf = $cf;
        $this->enums = $enums;
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id, $deleter);
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
    }

    /**
     * @return array<int>
     */
    public function getLeadIds(): array
    {
        $embedded = $this->toArray()['_embedded'] ?? [];
        $leads = is_array($embedded) ? ($embedded['leads'] ?? []) : [];

        if (! is_array($leads) || ! count($leads)) {
            return [];
        }

        return array_map(static fn ($id) => is_numeric($id) ? (int) $id : 0, Arr::pluck($leads, 'id'));
    }
}
