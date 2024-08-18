<?php

namespace mttzzz\AmoClient\Entities;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Models;
use mttzzz\AmoClient\Traits;

class Customer extends AbstractEntity
{
    use Traits\CrudEntityTrait, Traits\CustomFieldTrait, Traits\TagTrait;

    public string $name;

    public Models\Note $notes;

    public Task $tasks;

    public Models\Link $links;

    public int $periodicity;

    public int $next_price;

    public int $next_date;

    /**
     * @var array<mixed>
     */
    public array $custom_fields_values = [];

    /**
     * @var array<mixed>
     */
    public array $_embedded = [];

    /**
     * @param  array<mixed>  $data
     * @param  array<mixed>  $cf
     */
    public function __construct(array $data, PendingRequest $http, array $cf)
    {
        parent::__construct($data, $http);
        $this->entity = 'customers';
        $this->cf = $cf;
        $this->tasks = new Task(['responsible_user_id' => $this->responsible_user_id], $http, $this->entity, $this->id);
        $this->links = new Models\Link($http, "{$this->entity}/{$this->id}");
        $this->notes = new Models\Note($http, "{$this->entity}/{$this->id}", $this->id);
    }

    /**
     * @return array<mixed>
     */
    public function complex(): array
    {
        try {
            return $this->http->post($this->entity.'/complex', [$this->toArray()])->throw()->json();
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
        }
    }

    public function setContact(Contact $contact): void
    {
        $this->_embedded['contacts'][] = $contact->toArray();
    }

    public function setCompany(Company $company): void
    {
        $this->_embedded['companies'][] = $company->toArray();
    }

    public function getMainContactId(): ?int
    {
        if (! isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        foreach ($this->_embedded['contacts'] as $contact) {
            if ($contact['is_main']) {
                return $contact['id'];
            }
        }

        return null;
    }

    public function getCompanyId(): ?int
    {
        return $this->_embedded['companies'][0]['id'] ?? null;
    }

    /**
     * @return array<int>
     */
    public function getContactsIds(): array
    {
        if (! isset($this->_embedded['contacts'])) {
            throw new Exception('add withContacts() before call this function');
        }
        $ids = [];
        foreach ($this->_embedded['contacts'] as $contact) {
            $ids[] = $contact['id'];
        }

        return $ids;
    }
}
