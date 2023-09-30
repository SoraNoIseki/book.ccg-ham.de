<?php

namespace Soranoiseki\BookGroup;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Soranoiseki\BookGroup\View\Components\Tabs\Container;
use Soranoiseki\BookGroup\View\Components\Tabs\Label;
use Soranoiseki\BookGroup\View\Components\Tabs\Tab;
use Soranoiseki\BookGroup\View\Components\Alert;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;


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

        // $this->initDropboxApp();

        // register configs
        $this->mergeConfigFrom(
            __DIR__ . '/Config/disks.php',
            'filesystems.disks'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/Config/connections.php',
            'database.connections'
        );

        // register view components
        Blade::component('tabs-container', Container::class);
        Blade::component('tabs-label', Label::class);
        Blade::component('tabs-tab', Tab::class);
        Blade::component('alert', Alert::class);

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
        $this->app->make('Soranoiseki\BookGroup\Controllers\LibraryController');
    }


    protected function initDropboxApp() {
        Storage::extend('dropbox', function (Application $app, array $config) {
            $adapter = new DropboxAdapter(new DropboxClient(
                $config['authorization_token']
            ));
   
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
