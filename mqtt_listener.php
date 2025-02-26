<?php
require_once __DIR__ . '/vendor/autoload.php';

// Error reporting einschalten
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/mqtt_handler_error.log');

$pidFile = __DIR__ . '/mqtt_listener.pid';

// Check if already running
if (file_exists($pidFile)) {
    $pid = file_get_contents($pidFile);
    if (posix_kill($pid, 0)) {
        die("Process already running with PID: $pid\n");
    }
    // If process not running, remove stale pid file
    unlink($pidFile);
}

// Save current PID
file_put_contents($pidFile, getmypid());

// Remove PID file on shutdown
register_shutdown_function(function() use ($pidFile) {
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
});

require_once __DIR__ . '/includes/MQTTHandler.php';

try {
    echo "Starting MQTT Handler...\n";
    $handler = new MQTTHandler();
    
    // Der Handler läuft jetzt im Hintergrund und verarbeitet Nachrichten
    // Wir müssen nur die Loop-Methode des MQTT-Clients aufrufen
    while (true) {
        $handler->client->loop(true, true);
        // Kleine Pause um CPU-Last zu reduzieren
        usleep(100000); // 100ms Pause
    }
    
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo "Fatal error occurred. Check error log for details.\n";
    exit(1);
}
