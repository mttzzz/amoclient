<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;

class Customer extends AbstractModel
{
    use Traits\CrudTrait;

    private LazyCustomFields $lazyCf;

    public function __construct(PendingRequest $http, LazyCustomFields $lazyCf)
    {
        parent::__construct($http);
        $this->entity = 'customers';
        $this->lazyCf = $lazyCf;
    }

    public function entity(?int $id = null): Entities\Customer
    {
        return new Entities\Customer(['id' => $id], $this->http, $this->lazyCf->cf());
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\Customer
    {
        return new Entities\Customer($data, $this->http, $this->lazyCf->cf());
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
        return new Entities\Customer($this->findEntity($id), $this->http, $this->lazyCf->cf());
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
