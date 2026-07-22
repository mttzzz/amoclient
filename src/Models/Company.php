<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\Helpers\OctaneAccount;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;
use mttzzz\AmoClient\Traits\Order;

class Company extends AbstractModel
{
    use Filter\Common, Filter\PhoneEmail;

    /* Поля по замеру §9.4: у компаний работают id, updated_at, name.
     * created_at игнорируется — раньше поставлялся общим трейтом впустую. */
    use Order\ById, Order\ByName, Order\ByUpdatedAt;
    use Traits\CrudTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    protected Deleter $deleter;

    /**
     * Коллекция примечаний по всем компаниям (GET /companies/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, OctaneAccount $account, LazyCustomFields $lazyCf, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'companies';
        $this->fieldPhoneId = $account->contact_phone_field_id;
        $this->fieldEmailId = $account->contact_email_field_id;
        $this->lazyCf = $lazyCf;
        $this->deleter = $deleter;
        $this->notes = new Note($http, $this->entity, null, $deleter);
    }

    public function entity(?int $id = null): Entities\Company
    {
        return new Entities\Company(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Company
    {
        return new Entities\Company($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    public function find(int $id): Entities\Company
    {
        return new Entities\Company($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * Удаление компаний — в корзину, семантика как у сделок (тот же роут).
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо отказал сообщением «Недостаточно прав для
     *              удаления…». Тем же ответом он отвечает и на повторное
     *              удаление лежащего в корзине, и на настоящий отказ по правам:
     *              какой из двух случаев произошёл, по ответу амо установить
     *              невозможно. Значение выбрано так, чтобы повторный снос был
     *              идемпотентным, но неоднозначность видна вызывающему
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->companies($ids);
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

    public function withContacts(): self
    {
        return $this->addWith(__FUNCTION__);
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
