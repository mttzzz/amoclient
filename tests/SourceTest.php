<?php

namespace mttzzz\AmoClient\Tests;

class SourceTest extends BaseAmoClient
{
    public function test_source()
    {
        $sourceEntity = $this->amoClient->sources->entity();
        $sourceEntity->name = $this->marked('test');
        $sourceEntity->external_id = '111111';
        $createdId = $sourceEntity->createGetId();
        $this->track('sources', $createdId);
        $found = $this->amoClient->sources->find($createdId);
        $this->assertEquals($createdId, $found->id);
        $deleted = $found->delete();
        /* delete() переведён с null на bool (lib-delete-2) — null был бы одинаково зелёным
         * и при реальном удалении, и при молчаливом no-op, bool этого различия не прячет. */
        $this->assertTrue($deleted);

    }
}
