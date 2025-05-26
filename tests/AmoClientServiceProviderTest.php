<?php

namespace mttzzz\AmoClient\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use mttzzz\AmoClient\AmoClientServiceProvider;
use PHPUnit\Framework\TestCase;

class AmoClientServiceProviderTest extends TestCase
{
    protected AmoClientServiceProvider $serviceProvider;

    protected Container $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container;

        // Устанавливаем фасадный корень
        Facade::setFacadeApplication($this->app);

        // Добавляем конфигурацию в контейнер
        $this->app->singleton('config', function () {
            return new ConfigRepository;
        });

        // Устанавливаем конфигурацию
        Config::set('amoclient', require __DIR__.'/../config/amoclient.php');

        $this->serviceProvider = new AmoClientServiceProvider($this->app);
    }

    public function test_register()
    {
        $this->serviceProvider->register();

        $config = $this->app['config']->get('amoclient');
        $this->assertNotNull($config); // Проверка, что конфигурация была загружена
    }

    public function test_provides()
    {
        $provides = $this->serviceProvider->provides();

        $this->assertIsArray($provides);
        $this->assertContains('amoclient', $provides); // Проверка, что 'amoclient' содержится в массиве provides
    }
}
