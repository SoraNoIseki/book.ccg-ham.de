<?php

namespace Soranoiseki\BookGroup;

use Illuminate\Support\ServiceProvider;

class BookGroupServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        // $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__.'/Views', 'book-group');

        // register configs
        $this->mergeConfigFrom(
            __DIR__ . '/Config/disks.php',
            'filesystems.disks'
        );

        $this->commands([]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Soranoiseki\BookGroup\Controllers\PowerpointController');
    }
}
