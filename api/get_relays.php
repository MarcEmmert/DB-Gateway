<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

// GET-Parameter prüfen
if (!isset($_GET['device_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fehlende Parameter']);
    exit;
}

$device_id = $_GET['device_id'];

try {
    $db = Database::getInstance();
    $device = new Device($db);
    
    // Überprüfen, ob das Gerät dem Benutzer gehört
    $device_data = $device->getById($device_id);
    if (!$device_data || (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
        exit;
    }
    
    // Relais abrufen
    $relays = $device->getRelays($device_id);
    echo json_encode(['success' => true, 'relays' => $relays]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
