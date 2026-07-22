<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnexpectedResponseException;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;

class Contact extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;
    use Traits\CrudTrait, Traits\OrderTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    protected Deleter $deleter;

    /**
     * Коллекция примечаний по всем контактам (GET /contacts/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, OctaneAccount $account, LazyCustomFields $lazyCf, Deleter $deleter)
    {
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->lazyCf = $lazyCf;
        $this->deleter = $deleter;
        $this->entity = 'contacts';
        $this->notes = new Note($http, $this->entity, null, $deleter);

        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\Contact
    {
        return new Entities\Contact(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Contact
    {
        return new Entities\Contact($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    public function find(int $id): ?Entities\Contact
    {
        return new Entities\Contact($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * Удаление контактов — в корзину, семантика как у сделок (тот же роут).
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо отказал сообщением «Недостаточно прав для
     *              удаления…», которое неотличимо от «уже в корзине»
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->contacts($ids);
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
