<?php


namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;

class Contact extends AbstractModel
{
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;
    protected $entity = 'contacts';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Contact(['id' => $id], $this->http);
    }

    public function find($id)
    {
        return new Entities\Contact($this->findEntity($id), $this->http);
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
