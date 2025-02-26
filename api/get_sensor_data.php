<?php
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Keine HTML-Fehler ausgeben
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/iotgateway/logs/api_debug.log');

// Immer JSON ausgeben
header('Content-Type: application/json');

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Debug-Info sammeln
$debug = [
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'working_dir' => getcwd(),
    'include_path' => get_include_path()
];

try {
    // Prüfe device_id Parameter
    if (!isset($_GET['device_id'])) {
        throw new Exception('device_id parameter is required');
    }
    $device_id = intval($_GET['device_id']);
    
    // Prüfe ob Database.php existiert
    $dbPath = __DIR__ . '/../includes/Database.php';
    if (!file_exists($dbPath)) {
        error_log("Database.php not found at: " . $dbPath);
        sendJsonResponse([
            'error' => true,
            'message' => 'Database configuration not found',
            'debug' => $debug
        ], 500);
    }

    // Lade Datenbankklasse
    require_once $dbPath;
    
    // Erstelle Datenbankverbindung
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Hole Sensordaten
    $stmt = $pdo->prepare("
        SELECT s1.sensor_type, s1.value, s1.timestamp
        FROM sensor_data s1
        INNER JOIN (
            SELECT sensor_type, MAX(timestamp) as max_timestamp
            FROM sensor_data
            WHERE device_id = ?
            GROUP BY sensor_type
        ) s2 ON s1.sensor_type = s2.sensor_type AND s1.timestamp = s2.max_timestamp
        WHERE s1.device_id = ?
        ORDER BY s1.sensor_type
    ");
    
    $stmt->execute([$device_id, $device_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        sendJsonResponse([
            'success' => true,
            'message' => 'No sensor data found for device',
            'data' => [],
            'debug' => $debug
        ]);
    }
    
    // Erfolgreiche Antwort
    sendJsonResponse([
        'success' => true,
        'data' => $data,
        'debug' => $debug
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => $debug
    ], 500);

} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'error' => true,
        'message' => 'General error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => $debug
    ], 500);
}
