<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Facades\Facade;
use mttzzz\AmoClient\Facades\AmoClient;
use PHPUnit\Framework\TestCase;

class AmoClientFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Устанавливаем фасад для тестирования
        Facade::setFacadeApplication([
            'amoclient' => new \stdClass, // Здесь можно заменить на реальную реализацию клиента
        ]);
    }

    public function test_facade_resolves()
    {
        $resolvedInstance = AmoClient::getFacadeRoot();
        $this->assertInstanceOf(\stdClass::class, $resolvedInstance);
    }
}
