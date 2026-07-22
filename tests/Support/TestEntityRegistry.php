<?php

namespace mttzzz\AmoClient\Tests\Support;

/*
 * id — int|string, потому что не всё в amo адресуется числом: вебхук
 * адресуется своим destination-URL (числового id у Entities\Webhook нет вовсе,
 * unSubscribe() ходит по destination). Сужение до int оставило бы вне уборки
 * ровно тот тип, где удаление настоящее (hard delete), а хвост на боевом
 * аккаунте живёт вечно.
 *
 * Ключ дедупликации — конкатенация "$type:$id", строковый id она держит без
 * изменений.
 */
class TestEntityRegistry
{
    /** @var array<string, array{type: string, id: int|string}> */
    private array $entries = [];

    public function track(string $type, int|string $id): void
    {
        $this->entries[$type.':'.$id] = ['type' => $type, 'id' => $id];
    }

    public function forget(string $type, int|string $id): void
    {
        unset($this->entries[$type.':'.$id]);
    }

    /**
     * @return list<array{type: string, id: int|string}>
     */
    public function all(): array
    {
        return array_values($this->entries);
    }

    public function clear(): void
    {
        $this->entries = [];
    }
}
