<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Source extends AbstractModel
{
    use Traits\CrudTrait;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'sources';
        $this->deleter = $deleter;
    }

    public function entity(?int $id = null): Entities\Source
    {
        return new Entities\Source(['id' => $id], $this->http, $this->deleter);
    }

    public function find(int $id): Entities\Source
    {
        return new Entities\Source($this->findEntity($id), $this->http, $this->deleter);
    }

    /**
     * Удаление источников публичным v4. Пачкой и без похода за сущностью —
     * в отличие от `find($id)->delete()`, который был здесь единственной
     * формой и остался ради обратной совместимости.
     *
     * @param  int|list<int>  $ids
     * @return bool false — источника уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->sources($ids);
    }
}
