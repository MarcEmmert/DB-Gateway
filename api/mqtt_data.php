<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';
require_once __DIR__ . '/../includes/SensorData.php';
require_once __DIR__ . '/../includes/StatusContact.php';

// POST-Daten empfangen
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['mqtt_topic']) || !isset($data['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Parameter']);
    exit;
}

$device = new Device();
$device_data = $device->getByMqttTopic($data['mqtt_topic']);

if (!$device_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Gerät nicht gefunden']);
    exit;
}

try {
    $sensorData = new SensorData();
    $statusContact = new StatusContact();
    
    // Temperaturen speichern
    if (isset($data['data']['temp1'])) {
        $sensorData->addReading($device_data['id'], 'DS18B20_1', $data['data']['temp1']);
    }
    if (isset($data['data']['temp2'])) {
        $sensorData->addReading($device_data['id'], 'DS18B20_2', $data['data']['temp2']);
    }
    if (isset($data['data']['temp3'])) {
        $sensorData->addReading($device_data['id'], 'BMP180_TEMP', $data['data']['temp3']);
    }
    
    // Luftdruck speichern
    if (isset($data['data']['pressure'])) {
        $sensorData->addReading($device_data['id'], 'BMP180_PRESSURE', $data['data']['pressure']);
    }
    
    // Status-Kontakte aktualisieren
    for ($i = 1; $i <= 4; $i++) {
        if (isset($data['data']['contact' . $i])) {
            $statusContact->updateState($device_data['id'], $i, $data['data']['contact' . $i]);
        }
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern der Daten: ' . $e->getMessage()]);
}
