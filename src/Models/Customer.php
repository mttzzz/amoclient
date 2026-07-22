<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\LazyCustomFields;
use mttzzz\AmoClient\Traits;

class Customer extends AbstractModel
{
    use Traits\CrudTrait;

    private LazyCustomFields $lazyCf;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, LazyCustomFields $lazyCf, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'customers';
        $this->lazyCf = $lazyCf;
        $this->deleter = $deleter;
    }

    public function entity(?int $id = null): Entities\Customer
    {
        return new Entities\Customer(['id' => $id], $this->http, $this->lazyCf->cf(), $this->deleter);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\Customer
    {
        return new Entities\Customer($data, $this->http, $this->lazyCf->cf(), $this->deleter);
    }

    /**
     * Удаление покупателей приватным ajax — публичного механизма нет. Ответ
     * несёт собственный `errors[]` внутри HTTP 200. Ярус semver третий:
     * «ломается громко и чинится patch-ом».
     *
     * @param  int|list<int>  $ids
     * @return bool false — все ошибки ответа с code=404 («покупателя уже нет»)
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->customers($ids);
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
        return new Entities\Customer($this->findEntity($id), $this->http, $this->lazyCf->cf(), $this->deleter);
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
