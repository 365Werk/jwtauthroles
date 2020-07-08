<?php

namespace werk365\jwtfusionauth;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class jwtfusionauthServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'werk365');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'werk365');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (function_exists('config_path')) { // function not available and 'publish' not relevant in Lumen
            $this->publishes([
                __DIR__.'/../config/jwtfusionauth.php' => config_path('jwtfusionauth.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_jwks_table.php.stub' => $this->getMigrationFileName($filesystem),
            ], 'migrations');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/jwtfusionauth.php', 'jwtfusionauth');

        // Register the service the package provides.
        $this->app->singleton('jwtfusionauth', function ($app) {
            return new jwtfusionauth;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['jwtfusionauth'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/jwtfusionauth.php' => config_path('jwtfusionauth.php'),
        ], 'jwtfusionauth.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/werk365'),
        ], 'jwtfusionauth.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/werk365'),
        ], 'jwtfusionauth.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/werk365'),
        ], 'jwtfusionauth.views');*/

        // Registering package commands.
        // $this->commands([]);
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_jwks_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_permission_tables.php")
            ->first();
    }
}
