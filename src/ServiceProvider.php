<?php

namespace Vijayd28\LaravelSQS;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * ServiceProvider class
 */
class ServiceProvider extends IlluminateServiceProvider
{

    /**
     * Boot function
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->publishes([
            __DIR__ . '/../config/sqs.php' => config_path('sqs.php'),
        ]);
    }

    /**
     * register function
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sqs.php', 'sqs'
        );

        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app);
        });
    }
}
