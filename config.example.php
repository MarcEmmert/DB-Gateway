<?php
return [
    // Datenbank-Konfiguration
    'db' => [
        'host' => 'localhost',
        'name' => 'iotgateway',
        'user' => 'iotuser',
        'password' => 'IhrSicheresPasswort'
    ],

    // MQTT-Broker-Konfiguration
    'mqtt' => [
        'host' => 'localhost',
        'port' => 1883,
        'user' => 'mqtt_user',
        'password' => 'IhrMQTTPasswort',
        'client_id' => 'iotgateway_' . rand(1000, 9999)
    ],

    // Anwendungs-Konfiguration
    'app' => [
        'debug' => false,
        'secret' => 'IhrGeheimesAppSecret',
        'session_lifetime' => 3600,
        'timezone' => 'Europe/Berlin'
    ],

    // Logging-Konfiguration
    'log' => [
        'file' => '/var/log/iot-gateway/app.log',
        'level' => 'warning' // debug, info, warning, error
    ],

    // API-Konfiguration für Mobile App
    'api' => [
        'token_lifetime' => 30 * 24 * 3600, // 30 Tage in Sekunden
        'rate_limit' => 100 // Anfragen pro Minute
    ]
];
