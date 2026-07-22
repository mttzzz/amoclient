<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnexpectedResponseException;
use mttzzz\AmoClient\Traits\CrudTrait;

class Catalog extends AbstractModel
{
    use CrudTrait;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'catalogs';
        $this->deleter = $deleter;
    }

    public function entity(?int $id = null): Entities\Catalog
    {
        return new Entities\Catalog(['id' => $id], $this->http, $this->deleter);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\Catalog
    {
        return new Entities\Catalog($this->findEntity($id), $this->http, $this->deleter);
    }

    /**
     * Удаление каталогов приватным ajax — публичного механизма нет. Уносит
     * элементы каталога каскадом. Ярус semver третий: «ломается громко и
     * чинится patch-ом».
     *
     * @param  int|list<int>  $ids
     * @return bool false — каталога уже нет
     *
     * @throws AmoCustomException
     * @throws AmoUnexpectedResponseException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->catalogs($ids);
    }
}
