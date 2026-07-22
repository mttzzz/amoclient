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
     * НИ ПОЛЕ, НИ НАПРАВЛЕНИЕ НЕ ЯВЛЯЮТСЯ ПАРАМЕТРАМИ ПУБЛИЧНОГО API — оба
     * выражены именем метода, и это защита, а не стиль. Амо не валидирует
     * `order` вовсе: и неподдерживаемое поле, и мусорное направление получают
     * HTTP 200 и выдачу в порядке по умолчанию. Промах не проявляется ни
     * ошибкой, ни предупреждением — только тем, что уборка не находит
     * собственный мусор. Поэтому строке с полем или направлением взяться
     * неоткуда: недопустимое непредставимо.
     *
     * Здесь мы намеренно расходимся с эталонной реализацией вендора.
     * Официальный SDK делает `Filters/Traits/OrderTrait::setOrder(string
     * $field, string $direction)` без какой-либо валидации, причём
     * `TasksFilter` подключает трейт так же, как `NotesFilter` — то есть
     * позволяет попросить у задач сортировку по `updated_at`, которую задачи
     * игнорируют. Промаха не замечает никто: ни SDK, ни API, ни разработчик.
     * «Упрощение до setOrder(string, string), как у вендора» вернёт ровно этот
     * дефект.
     *
     * ПОДДЕРЖКА ПОЛЕЙ НЕ ЕДИНООБРАЗНА МЕЖДУ РОУТАМИ, поэтому набор методов
     * собирается по замеру, а не по сущности, и каждое поле живёт отдельным
     * трейтом в `Traits\Order\*`. Матрица (§9.4; способ замера — запросить
     * страницу дважды, `asc` и `desc`: совпали, значит поле игнорируется):
     *
     * | Роут | id | updated_at | created_at | complete_till | name |
     * |---|---|---|---|---|---|
     * | leads | ✅ | ✅ | ✅ | ✕ | ✕ |
     * | contacts | ✅ | ✅ | ✕ | ✕ | ✅ |
     * | companies | ✅ | ✅ | ✕ | ✕ | ✅ |
     * | tasks | ✅ | ✕ | ✅ | ✅ | ✕ |
     * | leads/notes | ✅ | ✅ | ✕ | ✕ | ✕ |
     *
     * Карта пришпилена оффлайн-тестом `tests/Support/OrderCapabilityMapTest`,
     * который проверяет и ОТСУТСТВИЕ методов по игнорируемым полям: попытка
     * «добавить недостающий метод для симметрии» упрётся в красный тест и
     * потребует замера, а не рассуждения.
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
