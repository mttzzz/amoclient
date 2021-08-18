<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Contact extends AbstractModel
{
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;
    use Filter\Common, Filter\PhoneEmail;

    protected $entity = 'contacts';
    private $cf, $enums;

    public function __construct(PendingRequest $http, $account, $cf, $enums)
    {
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->cf = $cf;
        $this->enums = $enums;

        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Contact(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    public function entityData($data)
    {
        return new Entities\Contact($data, $this->http, $this->cf, $this->enums);
    }

    public function find($id)
    {
        return new Entities\Contact($this->findEntity($id), $this->http, $this->cf, $this->enums);
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
