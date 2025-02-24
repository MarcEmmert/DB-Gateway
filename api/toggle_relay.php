<?php
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Stelle sicher, dass wir JSON zurückgeben
header('Content-Type: application/json');

// Error Handler für PHP Fehler
function errorHandler($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'details' => [
            'file' => $errfile,
            'line' => $errline,
            'type' => $errno
        ]
    ]);
    exit;
}
set_error_handler("errorHandler");

// Exception Handler
function exceptionHandler($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit;
}
set_exception_handler("exceptionHandler");

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';
require_once __DIR__ . '/../includes/MQTTHandler.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

try {
    // JSON-Daten empfangen
    $json = file_get_contents('php://input');
    if (!$json) {
        throw new Exception('Keine Daten empfangen');
    }
    
    $data = json_decode($json, true);
    if ($data === null) {
        throw new Exception('Ungültige JSON-Daten: ' . json_last_error_msg());
    }

    if (!isset($data['device_id']) || !isset($data['relay_id']) || !isset($data['state'])) {
        throw new Exception('Fehlende Parameter');
    }

    $device_id = $data['device_id'];
    $relay_id = $data['relay_id'];
    $state = (int)$data['state'];

    $db = Database::getInstance();
    $device = new Device($db);
    $mqtt = new MQTTHandler();
    
    // Überprüfen, ob das Gerät dem Benutzer gehört
    $device_data = $device->getById($device_id);
    if (!$device_data || (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
        throw new Exception('Keine Berechtigung für dieses Gerät');
    }
    
    // Hole das Relay aus der Datenbank um die relay_number zu bekommen
    $stmt = $db->getConnection()->prepare("SELECT relay_number FROM relays WHERE id = ? AND device_id = ?");
    $stmt->execute([$relay_id, $device_id]);
    $relay = $stmt->fetch();
    
    if (!$relay) {
        throw new Exception('Relay nicht gefunden');
    }
    
    // Relay über MQTT schalten
    if ($mqtt->toggleRelay($device_id, $relay['relay_number'], $state)) {
        // Aktualisiere auch den Status in der Datenbank
        $device->toggleRelay($relay_id, $state);
        echo json_encode([
            'success' => true,
            'details' => [
                'device_id' => $device_id,
                'relay_id' => $relay_id,
                'relay_number' => $relay['relay_number'],
                'new_state' => $state
            ]
        ]);
    } else {
        // Hole den Inhalt des MQTT-Logs
        $mqtt_log = '';
        $logFile = __DIR__ . '/../logs/mqtt.log';
        if (file_exists($logFile)) {
            $mqtt_log = shell_exec('tail -n 20 ' . escapeshellarg($logFile));
        }
        
        throw new Exception('MQTT-Fehler beim Schalten des Relais');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'mqtt_log' => isset($mqtt_log) ? $mqtt_log : null
        ]
    ]);
}
