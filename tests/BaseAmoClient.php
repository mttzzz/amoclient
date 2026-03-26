<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Support\Facades\Config;
use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Exceptions\AmoCustomException;
use Orchestra\Testbench\TestCase;

abstract class BaseAmoClient extends TestCase
{
    protected AmoClientOctane $amoClient;

    /**
     * @param  array<mixed>  $response
     */
    protected function assertCustomerDeleteAccepted(array $response): void
    {
        $this->assertIsArray($response);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('customers', $response['response']);
        $this->assertArrayHasKey('delete', $response['response']['customers']);
        $this->assertArrayHasKey('errors', $response['response']['customers']['delete']);

        foreach ($response['response']['customers']['delete']['errors'] as $error) {
            $this->assertSame(404, $error['code']);
            $this->assertSame('Error 282.', $error['message']);
        }
    }

    protected function skipIfCustomersUnavailable(AmoCustomException $e): void
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Customers disabled') || str_contains($message, 'Error 426.')) {
            $this->markTestSkipped('Customers API is unavailable for the current account configuration.');
        }
    }

    protected function skipIfUnsupportedAmoResponse(AmoCustomException $e, array $needles, string $reason): void
    {
        foreach ($needles as $needle) {
            if (str_contains($e->getMessage(), $needle)) {
                $this->markTestSkipped($reason);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Настроить конфигурацию
        Config::set('amoclient.proxies', [null]);
        Config::set('amoclient.verify', false);
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
