<?php

namespace mttzzz\AmoClient\Models;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use mttzzz\AmoClient\Entities\AbstractEntity;
use mttzzz\AmoClient\Exceptions\AmoCustomException;

abstract class AbstractModel
{
    protected PendingRequest $http;

    /** @var string[] */
    protected array $with = [];

    protected int $page = 1;

    protected int $limit = 150;

    protected string $query = '';

    protected string $entity = '';

    /** @var array<string, mixed> */
    protected array $order = [];

    /** @var array<string, mixed> */
    protected array $filter = [];

    public function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    /**
     * Форма ответа зависит от эндпойнта, и тип обязан это признавать.
     *
     * При наличии `_embedded` отдаётся первая вложенная коллекция — СПИСОК
     * сущностей с целочисленными ключами; без него — сам объект ответа, со
     * строковыми. Раньше здесь стояло `array<string, mixed>`, и это было
     * враньём ровно в первом, самом частом случае: inline-аннотация заставляла
     * phpstan верить в строковые ключи там, где их нет. Враньё успело стоить
     * одного обхода гардом в страховочной сетке — тип, который приходится
     * обходить, хуже отсутствующего.
     *
     * @return array<mixed>
     *
     * @throws AmoCustomException
     */
    public function get(): array
    {
        try {
            $query = [];
            foreach (['with', 'page', 'limit', 'query', 'filter', 'order'] as $param) {
                if (! empty($this->$param)) {
                    $query[$param] = $param === 'with' ? implode(',', $this->with) : $this->$param;
                }
            }
            $data = $this->http->get($this->entity, $query)->throw()->json();
            $data = is_array($data) ? $data : [];

            if (isset($data['_embedded']) && is_array($data['_embedded'])) {
                $embeddedData = Arr::first($data['_embedded']);
            } else {
                $embeddedData = $data;
            }

            if (! is_array($embeddedData)) {
                return [];
            }

            return $embeddedData;
            // @codeCoverageIgnoreStart
        } catch (RequestException $e) {
            throw new AmoCustomException($e);
            // @codeCoverageIgnoreEnd
        }
    }

    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function limit(int $limit): self
    {
        $limit = $limit > 150 ? 150 : $limit;
        $this->limit = $limit;

        return $this;
    }

    protected function addWith(string $with): static
    {
        $this->with[] = Str::snake(Str::after($with, 'with'));

        return $this;
    }

    /**
     * Единственная точка, где выставляется сортировка, и она ЗАМЕЩАЕТ прежнюю.
     *
     * Сортировка у амо ровно одна. Пока ключи накапливались, вызов двух методов
     * подряд отправлял два ключа и оставлял выбор амо — молча, без следа в
     * ответе. Теперь побеждает последний вызов: решение принимаем мы, и оно
     * воспроизводимо.
     *
     * Направление попадает сюда только из перечисленных поимённо методов, и это
     * защита, а не стиль: амо не валидирует `order` — на `order[updated_at]=
     * nonsense` отвечает 200 и отдаёт порядок по умолчанию. Опечатка в строке
     * не проявилась бы ни ошибкой, ни предупреждением, поэтому строке взяться
     * неоткуда.
     *
     * @param  'asc'|'desc'  $direction
     */
    protected function orderBy(string $field, string $direction): static
    {
        $this->order = [$field => $direction];

        return $this;
    }

    /**
     * @param  list<AbstractEntity>  $entities
     * @return list<array<string, mixed>>
     */
    protected function prepareEntities(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->toArray();
        }

        return $result;
    }

    public function each(callable $function, int $limit = 150): void
    {
        $page = 1;
        $this->limit = $limit;
        while (true) {
            $chunk = $this->page($page++)->get();
            if (empty($chunk)) {
                break;
            }
            $function($chunk);
            if (count($chunk) < $limit) {
                break;
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allItems(int $limit = 150): array
    {
        $result = [];
        $this->each(function (array $items) use (&$result) {
            $result = array_merge($result, $items);
        }, $limit);

        /* $result мутируется по ссылке внутри замыкания each() — phpstan
         * не протаскивает уточнённый тип через byref-захват. */
        /** @var array<int, array<string, mixed>> $result */
        return $result;
    }
}
