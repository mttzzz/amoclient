<?php

namespace mttzzz\AmoClient\Tests\Support;

class TestEntityRegistry
{
    /* @var array<string, array{type: string, id: int}> */
    private array $entries = [];

    public function track(string $type, int $id): void
    {
        $this->entries[$type.':'.$id] = ['type' => $type, 'id' => $id];
    }

    public function forget(string $type, int $id): void
    {
        unset($this->entries[$type.':'.$id]);
    }

    /**
     * @return array<int, array{type: string, id: int}>
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
