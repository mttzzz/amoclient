<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Entities;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Exceptions\AmoUnknownException;

class Call extends AbstractModel
{
    protected Deleter $deleter;

    public function __construct(PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($http);
        $this->entity = 'calls';
        $this->deleter = $deleter;
    }

    public function entity(?int $id = null): Entities\Call
    {
        return new Entities\Call(['id' => $id], $this->http);
    }

    /**
     * Удаление звонков. Роута `DELETE /calls/{id}` у амо не существует вовсе;
     * снимается приватным NOTE_DELETE, потому что звонок в амо — примечание
     * особого типа (детали и обоснование — в Deleter). Ярус semver третий:
     * обещается «ломается громко и чинится patch-ом».
     *
     * @param  int|list<int>  $ids
     * @return bool false — амо ответил HTTP 400 `{"status":"fail","id":N}`,
     *              то есть звонка уже нет. «Уже нет» приходит здесь ошибочным
     *              статусом, а не полем в теле двухсотки — разбирает это
     *              Deleter, вызывающему достаточно bool
     *
     * @throws AmoCustomException
     * @throws AmoUnknownException
     */
    public function delete(int|array $ids): bool
    {
        return $this->deleter->calls($ids);
    }
}
