<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Company extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    /**
     * @var array<mixed>
     */
    private array $cf;

    /**
     * @var array<mixed>
     */
    private array $enums;

    
    /**
     * Коллекция примечаний по всем компаниям (GET /companies/notes)
     */
    public Note $notes;

    /**
     * @param  array<mixed>  $cf
     * @param  array<mixed>  $enums
     */
    public function __construct(PendingRequest $http, OctaneAccount $account, array $cf, array $enums)
    {
        parent::__construct($http);
        $this->entity = 'companies';
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->cf = $cf;
        $this->enums = $enums;
    }

    public function entity(?int $id = null): Entities\Company
    {
        return new Entities\Company(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\Company
    {
        return new Entities\Company($data, $this->http, $this->cf, $this->enums);
    }

    public function find(int $id): Entities\Company
    {
        return new Entities\Company($this->findEntity($id), $this->http, $this->cf, $this->enums);
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
