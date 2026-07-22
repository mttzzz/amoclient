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
     * Удаление источника. Возврат `null` сохранён как есть: метод отгружен в
     * этой форме, менять его сигнатуру ради косметики значит ломать
     * потребителей на ровном месте. Информативная форма — `$amo->sources
     * ->delete($ids): bool`, обе ходят через один Deleter, разойтись им негде.
     *
     * @throws LogicException у сущности нет id — адресовать удаление нечем
     * @throws AmoCustomException
     */
    public function delete(): null
    {
        if ($this->id === null) {
            throw new LogicException('Entities\Source::delete(): у сущности нет id — сначала find() или create().');
        }

        $this->deleter->sources($this->id);

        return null;
    }
}
