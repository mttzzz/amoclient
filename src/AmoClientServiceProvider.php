<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\ServiceProvider;

class AmoClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/amoclient.php', 'amoclient');

    }

    public function provides()
    {
        return ['amoclient'];
    }

    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/amoclient.php' => config_path('amoclient.php'),
        ], 'amoclient.config');

    }
}
