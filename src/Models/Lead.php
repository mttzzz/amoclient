<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;


class Lead extends AbstractModel
{
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;
    use Filter\Common, Filter\Lead;

    protected $entity = 'leads';

    public function __construct(PendingRequest $http)
    {
        parent::__construct($http);
    }

    public function entity($id = null)
    {
        return new Entities\Lead(['id' => $id], $this->http);
    }

    public function customFields()
    {
        return new CustomField($this->http, $this->entity);
    }

    public function find($id)
    {
        return new Entities\Lead($this->findEntity($id), $this->http);
    }

    public function withCatalogElements()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withIsPriceModifiedByRobot()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withLossReason()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withContacts()
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withOnlyDeleted()
    {
        return $this->addWith(__FUNCTION__);
    }
}
