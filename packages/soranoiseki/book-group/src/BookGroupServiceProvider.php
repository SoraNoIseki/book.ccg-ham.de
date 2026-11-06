<?php

namespace Soranoiseki\BookGroup;

use Illuminate\Support\ServiceProvider;

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
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
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
        $this->mergeConfigFrom(
            __DIR__ . '/Config/groups.php',
            'book.groups'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/Config/website_api.php',
            'book.website_api'
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
        // $this->app->make('Soranoiseki\BookGroup\Http\Controllers\PowerpointController');
        // $this->app->make('Soranoiseki\BookGroup\Http\Controllers\LibraryController');
        // $this->app->make('Soranoiseki\BookGroup\Http\Controllers\CalendarController');
        // $this->app->make('Soranoiseki\BookGroup\Http\Controllers\SongController');
        // $this->app->make('Soranoiseki\BookGroup\Http\Controllers\TaskPlanController');

        $this->commands([
            \Soranoiseki\BookGroup\Console\Commands\SyncSongs::class,
            \Soranoiseki\BookGroup\Console\Commands\FetchDropboxPublicLinks::class,
            \Soranoiseki\BookGroup\Console\Commands\ImportOpenBiblio::class,
        ]);
    }


    protected function initDropboxApp() {
        Storage::extend('dropbox', function (Application $app, array $config) {
            dd($config['authorization_token']);
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
