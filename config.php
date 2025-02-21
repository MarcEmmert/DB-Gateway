<?php
$config = [
    'db' => [
        'host' => 'localhost',
        'name' => 'iotgateway',
        'user' => 'iotuser',
        'password' => '01937736e'
    ],
    'mqtt' => [
        'host' => 'rdp.emmert.biz',
        'port' => 1883,
        'user' => 'mqtt_user',     // Geändert von iotuser zu mqtt_user
        'password' => '01937736e'
    ]
];

return $config;
