<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Facades\Config;
use mttzzz\AmoClient\AmoClientOctane;
use Orchestra\Testbench\TestCase;

abstract class BaseAmoClient extends TestCase
{
    protected $amoClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Настроить конфигурацию
        Config::set('amoclient.proxies', [null]);
        Config::set('amoclient.timeout', 60);
        Config::set('amoclient.connectTimeout', 10);
        Config::set('amoclient.retries', 2);
        Config::set('amoclient.retryDelay', 1000);

        // Создать экземпляр AmoClientOctane
        $aId = 16117840;
        $clientId = '00a140c1-7c52-4563-8b36-03f23754d255';
        $this->amoClient = new AmoClientOctane($aId, $clientId);
    }

    protected function getEnvironmentSetUp($app)
    {
        // Настроить тестовую базу данных
        $app['config']->set('database.default', 'octane');
        $app['config']->set('database.connections.octane', [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'octane_pushka_biz',
            'username' => 'root',
            'password' => 'root',
        ]);
    }
}
