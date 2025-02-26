<?php
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/iotgateway/logs/api_debug.log');

// Hilfsfunktion für JSON-Antworten
function sendResponse($data, $statusCode = 200) {
    if (php_sapi_name() === 'cli') {
        print_r($data);
        exit($statusCode === 200 ? 0 : 1);
    } else {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}

// Sammle Debug-Informationen
$debug = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
    'current_dir' => getcwd(),
    'include_path' => get_include_path(),
    'error_log_path' => ini_get('error_log')
];

// Logging-Funktion
function logDebug($message) {
    if (php_sapi_name() === 'cli') {
        echo $message . "\n";
    }
    error_log($message);
}

try {
    // 1. Prüfe ob Database.php existiert
    $dbPath = __DIR__ . '/../includes/Database.php';
    logDebug("Checking for Database.php at: $dbPath");
    if (!file_exists($dbPath)) {
        error_log("Database.php not found at: " . $dbPath);
        throw new Exception("Database configuration file not found at: $dbPath");
    }
    $debug['database_php_path'] = $dbPath;
    logDebug("Database.php found");
    
    // 2. Prüfe ob config.php existiert
    $configPath = __DIR__ . '/../config.php';
    logDebug("Checking for config.php at: $configPath");
    if (!file_exists($configPath)) {
        error_log("config.php not found at: " . $configPath);
        throw new Exception("Configuration file not found at: $configPath");
    }
    $debug['config_php_path'] = $configPath;
    logDebug("config.php found");
    
    // 3. Lade Konfiguration
    logDebug("Loading configuration...");
    $config = require $configPath;
    if (!isset($config['db']) || !is_array($config['db'])) {
        throw new Exception("Invalid database configuration in config.php");
    }
    $debug['config_loaded'] = true;
    logDebug("Configuration loaded successfully");
    
    // 4. Teste Datenbankverbindung
    logDebug("Testing database connection...");
    require_once $dbPath;
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $debug['database_connected'] = true;
    logDebug("Database connection successful");
    
    // 5. Teste Tabellenzugriff
    logDebug("Testing table access...");
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    logDebug("Found tables: " . implode(", ", $tables));
    
    if (!in_array('sensor_data', $tables)) {
        throw new Exception("sensor_data table not found in database");
    }
    
    // 6. Prüfe sensor_data Struktur
    logDebug("Checking sensor_data structure...");
    $stmt = $pdo->query("DESCRIBE sensor_data");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Erfolgreiche Antwort
    sendResponse([
        'success' => true,
        'message' => 'All tests passed successfully',
        'data' => [
            'tables' => $tables,
            'sensor_data_columns' => $columns,
            'mysql_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
        ],
        'debug' => $debug
    ]);

} catch (PDOException $e) {
    error_log("Database error in test_sensor.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (php_sapi_name() === 'cli') {
        echo "Database Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    sendResponse([
        'error' => true,
        'type' => 'database',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => $debug
    ], 500);

} catch (Exception $e) {
    error_log("General error in test_sensor.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    sendResponse([
        'error' => true,
        'type' => 'general',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => $debug
    ], 500);
}
