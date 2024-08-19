<?php

namespace mttzzz\AmoClient\Tests;

use mttzzz\AmoClient\AmoClientOctane;

class AmoClientOctaneTest extends BaseAmoClient
{
    public function testAmoClientOctane()
    {
        $this->assertInstanceOf(AmoClientOctane::class, $this->amoClient);
        $this->assertEquals(16117840, $this->amoClient->accountId);
    }
}
