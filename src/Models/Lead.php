<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;
use mttzzz\AmoClient\Traits\Filter;
use mttzzz\AmoClient\Traits\Order;

class Lead extends AbstractModel
{
    use Filter\Common, Filter\Lead;
    /* Поля по замеру §9.4: у сделок работают id, updated_at, created_at. */
    use Order\ByCreatedAt, Order\ById, Order\ByUpdatedAt;

    use Traits\CrudTrait, Traits\QueryTrait;

    private LazyCustomFields $lazyCf;

    protected Deleter $deleter;

    /**
     * Коллекция примечаний по всем сделкам (GET /leads/notes)
     */
    public Note $notes;

    public function __construct(PendingRequest $http, LazyCustomFields $lazyCf, Deleter $deleter)
    {
        $this->lazyCf = $lazyCf;
        $this->deleter = $deleter;
        parent::__construct($http);
        $this->entity = 'leads';
        $this->notes = new Note($http, $this->entity, null, $deleter);
    }

    public function entity(?int $id = null): Entities\Lead
    {
        return new Entities\Lead(['id' => $id], $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Lead
    {
        return new Entities\Lead($data, $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
    }

    /**
     * Удаление сделок — В КОРЗИНУ, не физическое: сущность пропадает из
     * обычных выборок, но остаётся в аккаунте с `is_deleted=true` и находится
     * через `withOnlyDeleted()`. Hard delete амо не даёт нигде — ни в UI, ни в
     * публичном v4, ни в приватном ajax, поэтому обещать его было бы враньём.
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
        return $this->deleter->leads($ids);
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
        return new Entities\Lead($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->lazyCf->enums(), $this->deleter);
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
