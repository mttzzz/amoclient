<?php

namespace mttzzz\AmoClient\Entities;

use Illuminate\Http\Client\PendingRequest;
use LogicException;
use mttzzz\AmoClient\Deleter;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use mttzzz\AmoClient\Traits;

class Source extends AbstractEntity
{
    use Traits\CrudEntityTrait;

    protected string $entity = 'sources';

    public string $name;

    public int $pipeline_id;

    public string $external_id;

    public bool $default;

    public ?string $origin_code;

    /**
     * @var array<mixed>
     */
    public array $services;

    private Deleter $deleter;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data, PendingRequest $http, Deleter $deleter)
    {
        parent::__construct($data, $http);
        $this->deleter = $deleter;
    }

    /**
     * Удаление источника.
     *
     * Возврат сменился с `null` на `bool` осознанно: одна операция на двух
     * уровнях (здесь и `$amo->sources->delete()`) обязана отвечать одинаково,
     * иначе по этому шву поведение и разойдётся. Прежний `null` вдобавок
     * нечего было проверять — `assertNull()` на удалении одинаково зелен и
     * когда удалилось, и когда метод молча ничего не сделал.
     *
     * @return bool false — источника уже нет (404)
     *
     * @throws LogicException у сущности нет id — адресовать удаление нечем
     * @throws AmoCustomException
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            throw new LogicException('Entities\Source::delete(): у сущности нет id — сначала find() или create().');
        }

        return $this->deleter->sources($this->id);
    }
}
