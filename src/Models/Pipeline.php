<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Pipeline extends AbstractModel
{
    use Traits\CrudTrait;

    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'leads/pipelines';
        $this->deleter = $deleter;
    }

    public function entity(?int $id = null): Entities\Pipeline
    {
        return new Entities\Pipeline(['id' => $id], $this->http);
    }

    public function find(int $id): Entities\Pipeline
    {
        return new Entities\Pipeline($this->findEntity($id), $this->http);
    }

    /**
     * Удаление воронок публичным v4 (204). UI амо ходит сюда приватным ajax,
     * но повторять это незачем: приватный канал подключаем только там, где
     * публичного механизма нет. Ярус semver — обычный.
     *
     * @param  int|list<int>  $ids
     * @return bool false — воронки уже нет (404)
     *
     * @throws AmoCustomException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->pipelines($ids);
    }
}
