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
        $this->assertNull($deleted);

    }
}
