<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\ServiceProvider;

class AmoClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/amoclient.php', 'amoclient');

    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['amoclient'];
    }

    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/amoclient.php' => config_path('amoclient.php'),
        ], 'amoclient.config');

    }
}
