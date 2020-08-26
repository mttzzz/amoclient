<?php

namespace mttzzz\AmoClient;

use Illuminate\Support\ServiceProvider;

class AmoClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'mttzzz');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'mttzzz');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/amoclient.php', 'amoclient');

        // Register the service the package provides.
        $this->app->singleton('amoclient', function ($app) {
            return new AmoClient;
        });
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

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/mttzzz'),
        ], 'amoclient.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/mttzzz'),
        ], 'amoclient.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/mttzzz'),
        ], 'amoclient.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
