<?php

namespace werk365\jwtauthroles;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use werk365\jwtauthroles\Middlewares\RoleMiddleware;

class JwtAuthRolesServiceProvider extends ServiceProvider
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

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);

        if (function_exists('config_path')) { // function not available and 'publish' not relevant in Lumen
            $this->publishes([
                __DIR__ . '/../config/jwtauthroles.php' => config_path('jwtauthroles.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_jwtauth_tables.php.stub' => $this->getMigrationFileName($filesystem),
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
        $this->mergeConfigFrom(__DIR__ . '/../config/jwtauthroles.php', 'jwtauthroles');

        // Register the service the package provides.
        $this->app->singleton('JwtAuthRoles', function ($app) {
            return new JwtAuthRoles;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['JwtAuthRoles'];
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
            __DIR__ . '/../config/jwtauthroles.php' => config_path('jwtauthroles.php'),
        ], 'jwtauthroles.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/werk365'),
        ], 'jwtAuthRoles.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/werk365'),
        ], 'jwtAuthRoles.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/werk365'),
        ], 'jwtAuthRoles.views');*/

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
                return $filesystem->glob($path.'*_create_jwtauth_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_jwtauth_tables.php")
            ->first();
    }
}
