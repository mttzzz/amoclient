<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Company extends AbstractModel
{
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    protected $entity = 'companies';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Company(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Company($this->findEntity($id), $this->http);
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
