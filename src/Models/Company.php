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


    private $cf;

    private $enums;

    public function __construct(PendingRequest $http, $account, $cf, $enums)
    {
        $this->entity = 'companies';
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->cf = $cf;
        $this->enums = $enums;
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Company(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    public function entityData($data)
    {
        return new Entities\Company($data, $this->http, $this->cf, $this->enums);
    }

    public function find($id)
    {
        return new Entities\Company($this->findEntity($id), $this->http, $this->cf, $this->enums);
    }

    public function customFields()
    {
        return new CustomField($this->http, $this->entity);
    }

    public function query($query)
    {
        $this->query = $query;

        return $this;
    }

    public function withContacts()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCatalogElements()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withLeads()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCustomers()
    {
        return $this->addWith(__FUNCTION__);
    }
}
