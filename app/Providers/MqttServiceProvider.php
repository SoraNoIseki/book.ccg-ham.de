<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PhpMqtt\Client\MqttClient;

class MqttServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MqttClient::class, function ($app) {
            $config = $app['config']['mqtt'];
            return new MqttClient($config['host'], $config['port'], $config['client_id']);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}