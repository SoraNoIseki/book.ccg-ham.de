<?php

namespace Soranoiseki\BookGroup;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Soranoiseki\BookGroup\View\Components\Tabs\Container;
use Soranoiseki\BookGroup\View\Components\Tabs\Label;
use Soranoiseki\BookGroup\View\Components\Tabs\Tab;
use Soranoiseki\BookGroup\View\Components\Alert;
use Soranoiseki\BookGroup\View\Components\BibleSelector;


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

        // register view components
        Blade::component('tabs-container', Container::class);
        Blade::component('tabs-label', Label::class);
        Blade::component('tabs-tab', Tab::class);
        Blade::component('alert', Alert::class);
        Blade::component('bible-selector', BibleSelector::class);

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
