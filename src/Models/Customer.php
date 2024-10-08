<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Customer extends AbstractModel
{
    use Traits\CrudTrait;

    /**
     * @var array<mixed>
     */
    private array $cf;

    /**
     * @param  array<mixed>  $cf
     */
    public function __construct(PendingRequest $http, array $cf)
    {
        parent::__construct($http);
        $this->entity = 'customers';
        $this->cf = $cf;
    }

    public function entity(?int $id = null): Entities\Customer
    {
        return new Entities\Customer(['id' => $id], $this->http, $this->cf);
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\Customer
    {
        return new Entities\Customer($data, $this->http, $this->cf);
    }

    public function customFields(): CustomField
    {
        return new CustomField($this->http, $this->entity);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\Customer
    {
        return new Entities\Customer($this->findEntity($id), $this->http, $this->cf);
    }

    public function withCatalogElements(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withContacts(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCompanies(): self
    {
        return $this->addWith(__FUNCTION__);
    }
}
