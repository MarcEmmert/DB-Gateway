<?php
session_start();

// CORS und Content-Type Header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

// Parameter prüfen
if (!isset($_GET['device_id']) || !isset($_GET['sensor_type'])) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    exit;
}

$device_id = intval($_GET['device_id']);
$sensor_type = $_GET['sensor_type'];
$timespan = isset($_GET['timespan']) ? $_GET['timespan'] : '24h';

// Überprüfen, ob der Timespan gültig ist
$valid_timespans = ['1h', '8h', '24h', '7d', '30d'];
if (!in_array($timespan, $valid_timespans)) {
    $timespan = '24h';
}

$db = Database::getInstance();
$device = new Device($db);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
$device_data = $device->getById($device_id);
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Zugriff verweigert']);
    exit;
}

// Daten abrufen
try {
    $data = $device->getTemperatureHistory($device_id, $sensor_type, $timespan);
    
    if ($data === false) {
        echo json_encode(['success' => false, 'error' => 'Fehler beim Abrufen der Daten']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
