<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;


class Customer extends AbstractModel
{
    use Traits\CrudTrait;

    protected $entity = 'customers';
    private $cf, $enums;

    public function __construct(PendingRequest $http, $cf, $enums)
    {
        $this->cf = $cf;
        $this->enums = $cf;
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Customer(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    public function entityData($data)
    {
        return new Entities\Customer($data, $this->http, $this->cf, $this->enums);
    }

    public function customFields()
    {
        return new CustomField($this->http, $this->entity);
    }

    public function find($id)
    {
        return new Entities\Customer($this->findEntity($id), $this->http, $this->cf, $this->enums);
    }

    public function withCatalogElements()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withContacts()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCompanies()
    {
        return $this->addWith(__FUNCTION__);
    }
}
