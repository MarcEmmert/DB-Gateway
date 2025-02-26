<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';
require_once __DIR__ . '/../includes/SensorData.php';
require_once __DIR__ . '/../includes/Relay.php';
require_once __DIR__ . '/../includes/StatusContact.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Device ID prüfen
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine Device ID angegeben']);
    exit;
}

$device = new Device();
$device_data = $device->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Zugriff verweigert']);
    exit;
}

// Sensordaten laden
$sensorData = new SensorData();
$latest_readings = $sensorData->getLatestReadings($device_data['id']);

// Relais-Status laden
$relay = new Relay();
$relays = $relay->getByDevice($device_data['id']);

// Status-Kontakte laden
$statusContact = new StatusContact();
$status_contacts = $statusContact->getByDevice($device_data['id']);

// Antwort zusammenstellen
$response = [
    'id' => $device_data['id'],
    'name' => $device_data['name'],
    'last_seen' => $device_data['last_seen'],
    'sensors' => [
        'DS18B20_1' => null,
        'DS18B20_2' => null,
        'BMP180_TEMP' => null,
        'BMP180_PRESSURE' => null
    ],
    'relays' => $relays,
    'status_contacts' => $status_contacts
];

// Sensordaten einfügen
foreach ($latest_readings as $reading) {
    $response['sensors'][$reading['sensor_type']] = [
        'value' => floatval($reading['value']),
        'timestamp' => $reading['timestamp']
    ];
}

echo json_encode($response);
