<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Lead extends AbstractModel
{
    use Filter\Common, Filter\Lead;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    /** @var array<mixed> */
    private array $cf;

    /** @var array<mixed> */
    private array $enums;

    /**
     * @param  array<mixed>  $cf
     * @param  array<mixed>  $enums
     */
    public function __construct(PendingRequest $http, array $cf, array $enums)
    {
        $this->cf = $cf;
        $this->enums = $enums;
        parent::__construct($http);
        $this->entity = 'leads';
    }

    public function entity(?int $id = null): Entities\Lead
    {
        return new Entities\Lead(['id' => $id], $this->http, $this->cf, $this->enums);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Lead
    {
        return new Entities\Lead($data, $this->http, $this->cf, $this->enums);
    }

    public function customFields(): CustomField
    {
        return new CustomField($this->http, $this->entity);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\Lead
    {
        return new Entities\Lead($this->findEntity($id), $this->http, $this->cf, $this->enums);
    }

    public function withCatalogElements(): static
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withIsPriceModifiedByRobot(): static
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withLossReason(): static
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withContacts(): static
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withOnlyDeleted(): static
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withSourceId(): static
    {
        return $this->addWith(__FUNCTION__);
    }
}
