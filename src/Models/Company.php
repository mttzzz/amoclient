<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Company extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;


    private array $cf;

    private array $enums;

    public function __construct(PendingRequest $http, object $account, array $cf, array $enums)
    {
        $this->entity = 'companies';
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->cf = $cf;
        $this->enums = $enums;
        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\Company
    {
        return new Entities\Company(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    public function entityData(array $data): Entities\Company
    {
        return new Entities\Company($data, $this->http, $this->cf, $this->enums);
    }

    public function find(int $id)
    {
        return new Entities\Company($this->findEntity($id), $this->http, $this->cf, $this->enums);
    }

    public function customFields(): CustomField
    {
        return new CustomField($this->http, $this->entity);
    }

    public function query(string|array $query): self
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
