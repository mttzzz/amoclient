<?php

namespace mttzzz\AmoClient\Tests;

use Exception;
use Illuminate\Support\Facades\DB;
use Mockery;
use mttzzz\AmoClient\AmoClientOctane;

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
        // dd($widget);
        $this->assertTrue((bool) $widget->active,
            "Account ($octaneAccountData->subdomain) doesn't have an active widget ($widget->name)");
    }

    public function testAccountNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Account (99999999) not found');

        new AmoClientOctane(99999999, '00a140c1-7c52-4563-8b36-03f23754d255');
    }

    public function testWidgetNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Widget (invalid-client-id) not found');

        new AmoClientOctane(16117840, 'invalid-client-id');
    }

    public function testInactiveWidget()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Account (subdomain) doesn't active widget (widget-name)");

        // Мокирование данных
        $accountData = (object) [
            'id' => 16117841,
            'subdomain' => 'subdomain',
            'domain' => 'domain',
        ];

        $widgetData = (object) [
            'id' => 1,
            'client_id' => '00a140c1-7c52-4563-8b36-03f23754d255',
            'name' => 'widget-name',
            'active' => false,
        ];

        // Мокирование вызовов к базе данных
        DB::shouldReceive('connection->table->select->join->join->where->where->where->first')
            ->andReturn(null);

        DB::shouldReceive('connection->table->where->first')
            ->with('client_id', '00a140c1-7c52-4563-8b36-03f23754d255')
            ->andReturn($widgetData);

        new AmoClientOctane(16117841, '00a140c1-7c52-4563-8b36-03f23754d255');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
