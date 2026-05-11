<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Lead extends AbstractModel
{
    use Filter\Common, Filter\Lead;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    /**
     * Коллекция примечаний по всем сделкам (GET /leads/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, LazyCustomFields $lazyCf)
    {
        $this->lazyCf = $lazyCf;
        parent::__construct($http);
        $this->entity = 'leads';
        $this->notes = new Note($http, $this->entity, null);
    }

    public function entity(?int $id = null): Entities\Lead
    {
        return new Entities\Lead(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Lead
    {
        return new Entities\Lead($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
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
        return new Entities\Lead($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
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
