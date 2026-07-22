<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;
use mttzzz\AmoClient\Traits;

class CatalogElement extends AbstractModel
{
    use Traits\CrudTrait, Traits\Filter\Common, Traits\QueryTrait;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, int $catalogId, Deleter $deleter)
    {
        $this->entity = "catalogs/{$catalogId}/elements";
        $this->deleter = $deleter;
        parent::__construct($http);
    }

    public function entity(?int $id = null): Entities\CatalogElement
    {
        return new Entities\CatalogElement(['id' => $id], $this->http, $this->entity);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function entityData(array $data): Entities\CatalogElement
    {
        return new Entities\CatalogElement($data, $this->http, $this->entity);
    }

    /**
     * @throws AmoCustomException
     */
    public function find(int $id): Entities\CatalogElement
    {
        return new Entities\CatalogElement($this->findEntity($id), $this->http, $this->entity);
    }

    /**
     * Точечное удаление элементов списка — отдельный приватный эндпойнт, а не
     * вложенность в удаление каталога. Каталог в вызове не участвует: элемент
     * адресуется одним своим id, поэтому свипу не нужно знать родителя.
     * Ярус semver третий: «ломается громко и чинится patch-ом».
     *
     * @param  int|list<int>  $ids
     * @return bool false — элемента уже нет
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->catalogElements($ids);
    }
}
