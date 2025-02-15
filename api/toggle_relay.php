<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';
require_once __DIR__ . '/../includes/MQTTHandler.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// POST-Daten prüfen
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['device_id']) || !isset($input['relay_id']) || !isset($input['state'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Parameter']);
    exit;
}

$device = new Device();
$device_data = $device->getById($input['device_id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Zugriff verweigert']);
    exit;
}

try {
    // MQTT-Nachricht senden
    $mqtt = new MQTTHandler();
    $mqtt->toggleRelay($input['device_id'], $input['relay_id'], $input['state']);
    
    // Datenbank aktualisieren
    $device->toggleRelay($input['relay_id'], $input['state']);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Schalten des Relais: ' . $e->getMessage()]);
}
