<?php

namespace mttzzz\AmoClient\Tests;

class sourceTest extends BaseAmoClient
{
    public function testSource()
    {
        $sourceEntity = $this->amoClient->sources->entity();
        $sourceEntity->name = 'test';
        $sourceEntity->external_id = '111111';
        $createdId = $sourceEntity->createGetId();
        $found = $this->amoClient->sources->find($createdId);
        $this->assertEquals($createdId, $found->id);
        $deleted = $found->delete();
        $this->assertNull($deleted);

    }
}
