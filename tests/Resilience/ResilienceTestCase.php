<?php

namespace mttzzz\AmoClient\Tests\Resilience;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

/*
 * Самодостаточная база для resilience-тестов: sqlite in-memory вместо
 * реальной octane-БД и Http::fake вместо живого amo (legacy-сьюта пакета
 * бьёт в реальный аккаунт — для этих тестов это неприемлемо).
 */
abstract class ResilienceTestCase extends TestCase
{
    protected const ACCOUNT_ID = 42;

    protected const CLIENT_ID = '00a140c1-7c52-4563-8b36-03f23754d255';

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'octane');
        $app['config']->set('database.connections.octane', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('amoclient.proxies', [null]);
        Config::set('amoclient.verify', false);
        Config::set('amoclient.timeout', 5);
        Config::set('amoclient.connectTimeout', 2);
        Config::set('amoclient.retries', 2);
        Config::set('amoclient.retryDelay', 0);

        Schema::connection('octane')->create('accounts', function ($table) {
            $table->integer('id')->primary();
            $table->string('subdomain');
            $table->string('domain');
            $table->boolean('payed')->default(true);
            $table->integer('contact_phone_field_id')->nullable();
            $table->integer('contact_email_field_id')->nullable();
        });
        Schema::connection('octane')->create('widgets', function ($table) {
            $table->increments('id');
            $table->string('client_id');
            $table->string('name');
        });
        Schema::connection('octane')->create('account_widget', function ($table) {
            $table->integer('account_id');
            $table->integer('widget_id');
            $table->boolean('active')->default(true);
            $table->string('access_token')->nullable();
        });

        DB::connection('octane')->table('accounts')->insert([
            'id' => self::ACCOUNT_ID,
            'subdomain' => 'test',
            'domain' => 'ru',
            'payed' => true,
        ]);
        $widgetId = DB::connection('octane')->table('widgets')->insertGetId([
            'client_id' => self::CLIENT_ID,
            'name' => 'pushka',
        ]);
        DB::connection('octane')->table('account_widget')->insert([
            'account_id' => self::ACCOUNT_ID,
            'widget_id' => $widgetId,
            'active' => true,
            'access_token' => 'old-token',
        ]);
    }
}
