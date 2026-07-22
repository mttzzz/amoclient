<?php

namespace mttzzz\AmoClient\Tests\Support;

use PHPUnit\Framework\TestCase;

class TestEntityRegistryTest extends TestCase
{
    public function test_tracks_and_lists_in_insertion_order(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('tasks', 20);

        $this->assertSame(
            [['type' => 'leads', 'id' => 10], ['type' => 'tasks', 'id' => 20]],
            $r->all()
        );
    }

    public function test_dedupes_same_type_and_id(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('leads', 10);

        $this->assertCount(1, $r->all());
    }

    public function test_forget_removes_entry(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('leads', 11);
        $r->forget('leads', 10);

        $this->assertSame([['type' => 'leads', 'id' => 11]], $r->all());
    }

    public function test_clear_empties(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->clear();

        $this->assertSame([], $r->all());
    }

    /*
     * Строковый id — не экзотика, а вебхук: он адресуется destination-URL, и
     * числового id у него нет вообще. Пока реестр брал только int, вебхуки
     * физически не могли попасть в уборку, хотя удаляются они по-настоящему
     * (hard delete) — то есть терялся самый живучий вид хвоста.
     */
    public function test_tracks_string_id_for_url_addressed_entities(): void
    {
        $r = new TestEntityRegistry;
        $destination = 'https://webhook.site/a895608c-8b4a-453e-8359-4ed5d42bb454';
        $r->track('webhooks', $destination);

        $this->assertSame([['type' => 'webhooks', 'id' => $destination]], $r->all());
    }

    public function test_dedupes_and_forgets_by_string_id(): void
    {
        $r = new TestEntityRegistry;
        $destination = 'https://webhook.site/a895608c-8b4a-453e-8359-4ed5d42bb454';
        $r->track('webhooks', $destination);
        $r->track('webhooks', $destination);

        $this->assertCount(1, $r->all());

        $r->forget('webhooks', $destination);

        $this->assertSame([], $r->all());
    }

    /*
     * Разные типы с одинаковым id — две разные сущности; склей их ключ, и снос
     * одной вычеркнул бы из уборки другую. Молча.
     */
    public function test_same_id_under_different_types_are_separate_entries(): void
    {
        $r = new TestEntityRegistry;
        $r->track('leads', 10);
        $r->track('tasks', 10);
        $r->forget('leads', 10);

        $this->assertSame([['type' => 'tasks', 'id' => 10]], $r->all());
    }
}
