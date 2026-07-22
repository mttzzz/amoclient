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

    public int $created_by;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $custom_fields_values = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $_embedded = [];

    /**
     * @param  array<string, mixed>  $data
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
            $result = $this->http->post($this->entity.'/complex', [$this->toArray()])->throw()->json();

            return is_array($result) ? $result : [];
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
            if ($contact['is_main'] ?? false) {
                $id = $contact['id'] ?? null;

                return is_numeric($id) ? (int) $id : null;
            }
        }

        return null;
    }

    public function getCompanyId(): ?int
    {
        $companyId = $this->_embedded['companies'][0]['id'] ?? null;

        return is_numeric($companyId) ? (int) $companyId : null;
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
            $id = $contact['id'] ?? null;
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
