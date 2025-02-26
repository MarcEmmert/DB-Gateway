<?php
require_once __DIR__ . "/includes/Database.php";
require_once __DIR__ . "/includes/Relay.php";
require_once __DIR__ . "/includes/StatusContact.php";

$relay = new Relay();
$status = new StatusContact();

// Initialisiere Geräte
$device_ids = [2, 3];  // IDs für beide Geräte

foreach ($device_ids as $device_id) {
    echo "Initialisiere Gerät ID $device_id...\n";
    
    try {
        $relay->initializeForDevice($device_id);
        $status->initializeForDevice($device_id);
        echo "✓ Erfolgreich initialisiert\n";
    } catch (Exception $e) {
        echo "✗ Fehler: " . $e->getMessage() . "\n";
    }
}

echo "\nInitialisierung abgeschlossen\n";
