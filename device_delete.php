<?php
// Fehler anzeigen für Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/Database.php';
require_once 'includes/Device.php';
require_once 'config.php';

// Logging aktivieren
error_log("Device Delete aufgerufen");

try {
    $device_id = $_GET['id'] ?? null;
    error_log("Versuche Gerät zu löschen: " . $device_id);
    
    if (!$device_id) {
        throw new Exception('Keine Geräte-ID angegeben');
    }
    
    $db = Database::getInstance($config['db']);
    $device = new Device($db);
    
    // Gerät abrufen
    $device_data = $device->getById($device_id);
    error_log("Gerätedaten: " . print_r($device_data, true));
    
    if (!$device_data) {
        throw new Exception('Gerät nicht gefunden');
    }
    
    // Überprüfen, ob der Benutzer das Gerät löschen darf
    error_log("User ID: " . $_SESSION['user_id'] . ", Is Admin: " . ($_SESSION['is_admin'] ? 'yes' : 'no') . ", Device User ID: " . $device_data['user_id']);
    
    if (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id']) {
        throw new Exception('Keine Berechtigung zum Löschen des Geräts');
    }
    
    // Zuerst abhängige Daten löschen
    $db->getConnection()->beginTransaction();
    error_log("Transaktion gestartet");
    
    try {
        // Sensordaten löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM sensor_data WHERE device_id = ?");
        $stmt->execute([$device_id]);
        error_log("Sensordaten gelöscht: " . $stmt->rowCount() . " Zeilen");
        
        // Sensor-Konfiguration löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM sensor_config WHERE device_id = ?");
        $stmt->execute([$device_id]);
        error_log("Sensor-Konfiguration gelöscht: " . $stmt->rowCount() . " Zeilen");
        
        // Relay-Konfiguration löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM relay_config WHERE device_id = ?");
        $stmt->execute([$device_id]);
        error_log("Relay-Konfiguration gelöscht: " . $stmt->rowCount() . " Zeilen");
        
        // Relais löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM relays WHERE device_id = ?");
        $stmt->execute([$device_id]);
        error_log("Relais gelöscht: " . $stmt->rowCount() . " Zeilen");
        
        // Gerät löschen
        $stmt = $db->getConnection()->prepare("DELETE FROM devices WHERE id = ?");
        if (!$stmt->execute([$device_id])) {
            throw new Exception('Fehler beim Löschen des Geräts: ' . implode(', ', $stmt->errorInfo()));
        }
        error_log("Gerät erfolgreich gelöscht");
        
        $db->getConnection()->commit();
        error_log("Transaktion erfolgreich abgeschlossen");
        
        header('Location: index.php?success=1');
        exit;
        
    } catch (Exception $e) {
        error_log("Fehler während der Transaktion: " . $e->getMessage());
        $db->getConnection()->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Hauptfehler: " . $e->getMessage());
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
        error_log("Transaktion zurückgerollt");
    }
    die('Fehler: ' . $e->getMessage());
}
