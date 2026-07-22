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
}
