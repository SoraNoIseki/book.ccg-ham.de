<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    protected $client;

    public function __construct(MqttClient $mqtt)
    {
        $this->client = $mqtt;

        $settings = (new ConnectionSettings)
            ->setUsername(env('MQTT_USERNAME'))
            ->setPassword(env('MQTT_PASSWORD'))
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('book/last_will')
            ->setLastWillMessage('Client disconnected unexpectedly')
            ->setLastWillQualityOfService(1);

        $this->client->connect($settings);
    }

    public function publish($topic, $message, $qos = 0, $retain = false)
    {
        $this->client->publish($topic, $message, $qos, $retain);
        $this->disconnect();
    }

    public function subscribe($topic, callable $callback, $qos = 0)
    {
        $this->client->subscribe($topic, $callback, $qos);
    }

    public function loop($duration = 1)
    {
        $this->client->loop($duration);
    }

    public function disconnect()
    {
        $this->client->disconnect();
    }
}