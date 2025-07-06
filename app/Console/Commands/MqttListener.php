<?php

namespace App\Console\Commands;

use App\Models\NpkData;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen to MQTT topic and save NPK data';

    public function handle()
    {
        $server = env('MQTT_BROKER', 'ec1101f6.ala.asia-southeast1.emqxsl.com');
        $port = env('MQTT_PORT', 8883);
        $clientId = 'laravel-subscriber-'.uniqid();
        $username = env('MQTT_USER', 'upi');
        $password = env('MQTT_PASSWORD', '123');
        $topic = env('MQTT_TOPIC_SUB', 'sensor/npk');

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60);

        $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1_1);
        $mqtt->connect($connectionSettings, true);

        $this->info("Listening to topic: {$topic}");

        $mqtt->subscribe($topic, function (string $topic, string $message) {
            $this->info("Message received: {$message}");
            $data = json_decode($message, true);

            NpkData::create([
                'device_id' => $data['device_id'],
                'nitrogen' => $data['nitrogen'],
                'phosphorus' => $data['phosphorus'],
                'potassium' => $data['potassium'],
            ]);

            $this->info("Data saved!");
        }, 0);

        $mqtt->loop(true);
    }
}
