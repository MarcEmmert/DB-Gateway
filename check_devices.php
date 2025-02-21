<?php
require_once __DIR__ . '/includes/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->getConnection()->query("SELECT id, name, mqtt_topic FROM devices");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Devices in Database ===\n";
    foreach ($devices as $device) {
        echo "ID: {$device['id']}\n";
        echo "Name: {$device['name']}\n";
        echo "MQTT Topic: {$device['mqtt_topic']}\n";
        echo "------------------------\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
