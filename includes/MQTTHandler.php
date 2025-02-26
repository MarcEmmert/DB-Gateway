<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MQTTHandler {
    public $client;
    private $db;
    private $config;
    private $connected = false;
    
    private function log($message) {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mqtt.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] $message\n";
        error_log($formattedMessage, 3, $logFile);
    }
    
    public function __construct() {
        $this->log("\n=== Starting MQTTHandler Initialization ===");
        
        try {
            // Load configuration
            $this->config = require __DIR__ . '/../config.php';
            if (!isset($this->config['mqtt'])) {
                throw new Exception("MQTT configuration not found in config.php");
            }
            $this->log("Configuration loaded successfully");
            $this->log("MQTT Config: " . print_r($this->config['mqtt'], true));
            
            // Initialize database
            $this->db = Database::getInstance();
            if (!$this->db) {
                throw new Exception("Failed to get database instance");
            }
            $this->log("Database initialized successfully");
            
            // Set timezone
            try {
                $this->db->getConnection()->exec("SET time_zone = '+01:00'");
                $this->log("Database timezone set to +01:00");
            } catch (PDOException $e) {
                $this->log("Warning: Failed to set database timezone: " . $e->getMessage());
            }
            
            // Connect to MQTT broker
            $mqtt_config = $this->config['mqtt'];
            $client_id = 'php-mqtt-' . uniqid();
            
            $this->log("Connecting to MQTT broker:");
            $this->log("Host: " . $mqtt_config['host']);
            $this->log("Port: " . $mqtt_config['port']);
            $this->log("Client ID: " . $client_id);
            
            $connectionSettings = (new ConnectionSettings)
                ->setUsername($mqtt_config['user'])
                ->setPassword($mqtt_config['password'])
                ->setKeepAliveInterval(60)
                ->setConnectTimeout(5);
            
            $this->client = new MqttClient($mqtt_config['host'], $mqtt_config['port'], $client_id);
            $this->client->connect($connectionSettings, true);
            $this->connected = true;
            
            $this->log("Successfully connected to MQTT broker");
            
            // Subscribe to all topics
            $this->client->subscribe('#', function($topic, $message) {
                $this->processMqttMessage($topic, $message);
            }, 1);
            $this->log("Subscribed to all topics");
            
        } catch (Exception $e) {
            $this->log("Error in constructor: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function processMqttMessage($topic, $message) {
        try {
            $this->log("Received MQTT message:");
            $this->log("Topic: " . $topic);
            $this->log("Message: " . $message);
            
            $parts = explode('/', $topic);
            if (count($parts) < 3) {
                $this->log("Invalid topic format");
                return false;
            }
            
            $device_id = $parts[1];
            $data = json_decode($message, true);
            
            if ($data === null) {
                $this->log("Invalid JSON message");
                return false;
            }
            
            // Status Update (device/DEVICE_ID/status)
            if (count($parts) >= 3 && $parts[2] === 'status' && isset($data['data'])) {
                $this->handleStatusMessage($parts, $message);
            }
            // Temperature Update (device/DEVICE_ID/temperature)
            else if (count($parts) >= 3 && $parts[2] === 'temperature') {
                $this->handleTemperatureMessage($parts, $message);
            }
            
            // Update device last_seen
            $stmt = $this->db->getConnection()->prepare(
                "UPDATE devices SET last_seen = CURRENT_TIMESTAMP WHERE mqtt_topic LIKE ?"
            );
            $stmt->execute(['%' . $device_id . '%']);
            
            return true;
        } catch (Exception $e) {
            $this->log("Error processing MQTT message: " . $e->getMessage());
            return false;
        }
    }
    
    private function handleStatusMessage($topic_parts, $payload) {
        try {
            $this->log("\n=== Handling Status Message ===");
            $this->log("Topic: " . implode('/', $topic_parts));
            $this->log("Payload: " . $payload);
            
            // Get device ID from topic
            $device_mqtt_id = $topic_parts[1];
            $this->log("Device MQTT ID: " . $device_mqtt_id);
            
            // Get device ID from database
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM devices 
                WHERE mqtt_topic LIKE ?
            ");
            $stmt->execute(["%$device_mqtt_id%"]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                $this->log("Error: Device not found for MQTT ID: " . $device_mqtt_id);
                return;
            }
            
            $device_id = $device['id'];
            $this->log("Found device ID: " . $device_id);
            
            // Parse JSON payload
            $data = json_decode($payload, true);
            if (!$data || !isset($data['data'])) {
                $this->log("Error: Invalid JSON payload or missing 'data' field");
                $this->log("Raw payload: " . $payload);
                return;
            }
            
            // Update device status
            $stmt = $this->db->getConnection()->prepare("
                UPDATE devices 
                SET last_seen = NOW(), status = 'online'
                WHERE id = ?
            ");
            $stmt->execute([$device_id]);
            $this->log("Successfully updated device status to: online");
            
            // Kontakte aktualisieren
            // MQTT: 0 = offen, 1 = geschlossen
            // DB: 0 = offen, 1 = geschlossen
            // Also direkt übernehmen
            for ($i = 1; $i <= 4; $i++) {
                if (isset($data['data']["contact$i"])) {
                    $state = (int)$data['data']["contact$i"];
                    $this->log("\nProcessing Contact $i:");
                    $this->log("Raw MQTT Value: " . $data['data']["contact$i"] . " (" . ($state == 0 ? "Offen" : "Geschlossen") . ")");
                    $this->log("DB State: " . $state . " (" . ($state == 0 ? "Offen" : "Geschlossen") . ")");
                    $this->updateContactState($device_id, $i, $state);
                }
            }
            
            // Relais aktualisieren
            for ($i = 1; $i <= 4; $i++) {
                if (isset($data['data']["relay$i"])) {
                    $state = (int)$data['data']["relay$i"];
                    $this->log("\nProcessing Relay $i:");
                    $this->log("Raw MQTT Value: " . $data['data']["relay$i"]);
                    $this->log("Converted State: " . $state);
                    $this->updateRelayState($device_id, $i, $state);
                }
            }
        } catch (Exception $e) {
            $this->log("Error handling status message: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
        }
    }
    
    private function handleTemperatureMessage($topic_parts, $payload) {
        try {
            $this->log("\n=== Handling Temperature Message ===");
            $this->log("Topic: " . implode('/', $topic_parts));
            $this->log("Payload: " . $payload);
            
            // Get device ID from topic
            $device_mqtt_id = $topic_parts[1];
            $this->log("Device MQTT ID: " . $device_mqtt_id);
            
            // Get device ID from database
            $stmt = $this->db->getConnection()->prepare("
                SELECT id FROM devices 
                WHERE mqtt_topic LIKE ?
            ");
            $stmt->execute(["%$device_mqtt_id%"]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                $this->log("Error: Device not found for MQTT ID: " . $device_mqtt_id);
                return;
            }
            
            $device_id = $device['id'];
            $this->log("Found device ID: " . $device_id);
            
            // Parse JSON payload
            $data = json_decode($payload, true);
            if (!$data) {
                $this->log("Error: Invalid JSON payload");
                $this->log("Raw payload: " . $payload);
                return;
            }
            
            // Prepare insert statement
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO sensor_data 
                (device_id, sensor_type, value, timestamp) 
                VALUES (?, ?, ?, NOW())
            ");
            
            // Insert BMP180 temperature
            if (isset($data['bmp_temp'])) {
                $stmt->execute([$device_id, 'BMP180_TEMP', $data['bmp_temp']]);
                $this->log("Stored BMP180 temperature: " . $data['bmp_temp']);
            }
            
            // Insert Dallas1 temperature
            if (isset($data['dallas1_temp'])) {
                $stmt->execute([$device_id, 'DS18B20_1', $data['dallas1_temp']]);
                $this->log("Stored Dallas1 temperature: " . $data['dallas1_temp']);
            }
            
            // Insert Dallas2 temperature
            if (isset($data['dallas2_temp'])) {
                $stmt->execute([$device_id, 'DS18B20_2', $data['dallas2_temp']]);
                $this->log("Stored Dallas2 temperature: " . $data['dallas2_temp']);
            }
            
            // Insert BMP180 pressure
            if (isset($data['pressure'])) {
                $stmt->execute([$device_id, 'BMP180_PRESSURE', $data['pressure']]);
                $this->log("Stored BMP180 pressure: " . $data['pressure']);
            }
            
            $this->log("Successfully stored all sensor data");
            
        } catch (Exception $e) {
            $this->log("Error handling temperature message: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
        }
    }
    
    private function updateRelayState($device_id, $relay_number, $state) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE relays 
            SET state = ? 
            WHERE device_id = ? AND relay_number = ?
        ");
        $stmt->execute([$state, $device_id, $relay_number]);
    }
    
    private function updateContactState($device_id, $contact_number, $state) {
        $this->log("\n=== Update Contact State ===");
        $this->log("Device ID: " . $device_id);
        $this->log("Contact Number: " . $contact_number);
        $this->log("State to set: " . $state);
        
        // Prüfe ob der Kontakt existiert
        $stmt = $this->db->getConnection()->prepare("
            SELECT id, state FROM status_contacts 
            WHERE device_id = ? AND contact_number = ?
        ");
        $stmt->execute([$device_id, $contact_number]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contact) {
            $this->log("Found existing contact (ID: {$contact['id']}, Current State: {$contact['state']})");
            // Aktualisiere bestehenden Kontakt
            $stmt = $this->db->getConnection()->prepare("
                UPDATE status_contacts 
                SET state = ?, last_changed = CURRENT_TIMESTAMP
                WHERE device_id = ? AND contact_number = ?
            ");
            $result = $stmt->execute([$state, $device_id, $contact_number]);
            $this->log("Update result: " . ($result ? "Success" : "Failed"));
            
            if ($result) {
                // Prüfe den neuen Status
                $stmt = $this->db->getConnection()->prepare("
                    SELECT state, last_changed FROM status_contacts 
                    WHERE device_id = ? AND contact_number = ?
                ");
                $stmt->execute([$device_id, $contact_number]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->log("New state in DB: " . $updated['state']);
                $this->log("Last changed: " . $updated['last_changed']);
            }
        } else {
            $this->log("Creating new contact");
            // Erstelle neuen Kontakt
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO status_contacts (device_id, contact_number, state, name, last_changed)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $result = $stmt->execute([$device_id, $contact_number, $state, "Kontakt " . $contact_number]);
            $this->log("Insert result: " . ($result ? "Success" : "Failed"));
        }
    }
    
    public function toggleRelay($device_id, $relay_number, $state) {
        $this->log("\n=== Toggling Relay ===");
        $this->log("Device ID: " . $device_id);
        $this->log("Relay Number: " . $relay_number);
        $this->log("State: " . $state);
        
        try {
            if (!$this->connected) {
                $this->log("Error: MQTT client is not connected");
                throw new Exception("MQTT client is not connected");
            }

            // Get device MQTT topic base
            $stmt = $this->db->getConnection()->prepare(
                "SELECT mqtt_topic FROM devices WHERE id = ?"
            );
            $stmt->execute([$device_id]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                $this->log("Error: Device not found");
                throw new Exception("Device not found");
            }
            
            // Get base topic (remove trailing # if present)
            $topic_base = preg_replace('/#$/', '', $device['mqtt_topic']);
            $topic_base = rtrim($topic_base, '/');
            $this->log("Device topic base: " . $topic_base);
            
            // Erstelle das MQTT Topic
            $topic = $topic_base . "/relay";
            
            // Konvertiere relay_number (1-4) zu relay_id (13-16)
            $relay_id = $relay_number + 12;
            
            // Erstelle die MQTT Nachricht im korrekten Format
            $message = json_encode([
                'relay_id' => $relay_id,
                'state' => (int)$state
            ]);
            
            $this->log("Publishing to topic: " . $topic);
            $this->log("Message: " . $message);
            
            // Sende MQTT Nachricht
            $this->client->publish($topic, $message, 0, false);
            $this->log("Message published successfully");
            
            return true;
        } catch (Exception $e) {
            $this->log("Error toggling relay: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function __destruct() {
        if ($this->connected && $this->client) {
            try {
                $this->client->disconnect();
                $this->log("Disconnected from MQTT broker");
            } catch (Exception $e) {
                $this->log("Error disconnecting from MQTT broker: " . $e->getMessage());
            }
        }
    }
}
