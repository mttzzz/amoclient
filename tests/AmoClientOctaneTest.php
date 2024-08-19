<?php

namespace mttzzz\AmoClient\Tests;

use Exception;
use Illuminate\Support\Facades\DB;
use Mockery;
use mttzzz\AmoClient\AmoClientOctane;
use PHPUnit\Framework\Attributes\Depends;

class AmoClientOctaneTest extends BaseAmoClient
{
    public function testAmoClientOctane()
    {
        $this->assertInstanceOf(AmoClientOctane::class, $this->amoClient);
        $this->assertEquals(16117840, $this->amoClient->accountId);

        $aId = $this->amoClient->accountId;
        $clientId = $this->amoClient->clientId;

        // Проверка данных аккаунта
        $octaneAccountData = DB::connection('octane')->table('accounts')->where('id', $aId)->first();
        $this->assertNotNull($octaneAccountData, "Account ($aId) not found");

        // Проверка данных виджета
        $widget = DB::connection('octane')->table('widgets')->where('client_id', $clientId)->first();
        $this->assertNotNull($widget, "Widget ($clientId) not found");

        // Проверка активности виджета
        $this->assertTrue((bool) $widget->active,
            "Account ($octaneAccountData->subdomain) doesn't have an active widget ($widget->name)");
    }

    #[Depends('testAmoClientOctane')]
    public function testAccountNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Account (999999999999) not found');

        new AmoClientOctane(999999999999, '00a140c1-7c52-4563-8b36-03f23754d255');
    }

    #[Depends('testAccountNotFound')]
    public function testWidgetNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Widget (invalid-client-id) not found');

        new AmoClientOctane($this->amoClient->accountId, 'invalid-client-id');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
