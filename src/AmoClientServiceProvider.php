<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\ServiceProvider;

class AmoClientServiceProvider extends ServiceProvider
{
    /**
     * @codeCoverageIgnoreStart
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * @codeCoverageIgnoreEnd
     */
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

    /**
     * @codeCoverageIgnoreStart
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/amoclient.php' => config_path('amoclient.php'),
        ], 'amoclient.config');

    }

    /**
     * @codeCoverageIgnoreEnd
     */
}
