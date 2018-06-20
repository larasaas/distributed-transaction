<?php

namespace Larasaas\DistributedTransaction;

use Illuminate\Support\ServiceProvider;

/**
 * This is the owen auditing service provider class.
 */
class DtsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__.'/../config/dts.php' => config_path('dts.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadTranslationsFrom(__DIR__.'/../translations','dts');



    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->singleton(Connection::class, function ($app) {
//            return new Connection($app['config']['dts']);
//        });
        $this->mergeConfigFrom(__DIR__.'/../config/dts.php', 'dts');

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
