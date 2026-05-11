<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Contact extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    /**
     * Коллекция примечаний по всем контактам (GET /contacts/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, OctaneAccount $account, LazyCustomFields $lazyCf)
    {
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->lazyCf = $lazyCf;
        $this->entity = 'contacts';
        $this->notes = new Note($http, $this->entity, null);

        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\Contact
    {
        return new Entities\Contact(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    /**
     * @param  array<mixed>  $data
     */
    public function entityData(array $data): Entities\Contact
    {
        return new Entities\Contact($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    public function find(int $id): ?Entities\Contact
    {
        return new Entities\Contact($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums());
    }

    public function customFields(): CustomField
    {
        return new CustomField($this->http, $this->entity);
    }

    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function withCatalogElements(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withLeads(): self
    {
        return $this->addWith(__FUNCTION__);
    }

    public function withCustomers(): self
    {
        return $this->addWith(__FUNCTION__);
    }
}
