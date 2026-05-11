<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Company extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    /**
     * Коллекция примечаний по всем компаниям (GET /companies/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, OctaneAccount $account, LazyCustomFields $lazyCf)
    {
        parent::__construct($http);
        $this->entity = 'companies';
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->lazyCf = $lazyCf;
        $this->notes = new Note($http, $this->entity, null);
    }

    public function entity(?int $id = null): Entities\Company
    {
        return new Entities\Company(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\Company
    {
        return new Entities\Company($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    public function find(int $id): Entities\Company
    {
        return new Entities\Company($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    public function customFields(): CustomField
    {
        return new CustomField($this->http, $this->entity);
    }

    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function withContacts(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCatalogElements(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withLeads(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCustomers(): self
    {
        return $this->addWith(__FUNCTION__);
    }
}
