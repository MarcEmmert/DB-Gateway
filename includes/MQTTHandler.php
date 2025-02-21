<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/mqtt_handler_error.log');

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
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";
        error_log($logMessage);
        echo $logMessage . "\n";  // Ausgabe auch auf der Konsole
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
            
            // Initialize MQTT client
            try {
                $this->log("Creating MQTT client...");
                $this->client = new MqttClient(
                    $this->config['mqtt']['host'],
                    $this->config['mqtt']['port'],
                    'php-mqtt-client-' . uniqid()
                );
                
                $connectionSettings = (new ConnectionSettings)
                    ->setKeepAliveInterval(60)
                    ->setReconnectAutomatically(true)
                    ->setConnectTimeout(60)
                    ->setUsername($this->config['mqtt']['user'])
                    ->setPassword($this->config['mqtt']['password']);
                
                $this->log("Attempting MQTT connection with user: " . $this->config['mqtt']['user']);
                $this->client->connect($connectionSettings);
                $this->log("Connected to MQTT broker");
                
                // Subscribe to all topics
                $this->client->subscribe('#', function($topic, $message) {
                    $this->handleMessage($topic, $message);
                }, 1);
                $this->log("Subscribed to all topics");
                
                $this->connected = true;
                
            } catch (Exception $e) {
                throw new Exception("MQTT initialization failed: " . $e->getMessage());
            }
            
            $this->log("=== MQTTHandler Initialization Complete ===\n");
            
        } catch (Exception $e) {
            $this->log("FATAL: MQTTHandler initialization failed: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    private function handleMessage($topic, $payload) {
        $this->log("\n=== Received MQTT Message ===");
        $this->log("Topic: " . $topic);
        $this->log("Payload: " . $payload);
        
        try {
            // Parse topic
            $topic_parts = explode('/', $topic);
            $this->log("Topic parts: " . print_r($topic_parts, true));
            
            if (count($topic_parts) !== 3) {
                $this->log("Invalid topic format - expected 3 parts, got " . count($topic_parts));
                return;
            }
            
            $device_id_from_topic = $topic_parts[1];
            $message_type = $topic_parts[2];
            $this->log("Device ID from topic: " . $device_id_from_topic);
            $this->log("Message type: " . $message_type);
            
            // Get device from database
            try {
                $stmt = $this->db->getConnection()->prepare(
                    "SELECT id FROM devices WHERE mqtt_topic LIKE ?"
                );
                $pattern = "device/{$device_id_from_topic}%";
                $this->log("SQL Pattern: " . $pattern);
                
                $stmt->execute([$pattern]);
                $device = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->log("Database query result: " . ($device ? "Found device ID " . $device['id'] : "No device found"));
                
                if (!$device) {
                    $this->log("No device found for topic: " . $topic);
                    return;
                }
            } catch (PDOException $e) {
                $this->log("Database error finding device: " . $e->getMessage());
                return;
            }
            
            $device_id = $device['id'];
            
            // Parse JSON payload
            $json_data = json_decode($payload, true);
            if (!$json_data) {
                $this->log("Failed to parse JSON payload: " . json_last_error_msg());
                return;
            }
            $this->log("Parsed JSON data: " . print_r($json_data, true));
            
            // Process message based on type
            if ($message_type === 'temperature') {
                $this->log("Processing temperature data");
                $this->log("Device ID: " . $device_id);
                
                $sensors = [
                    'dallas1_temp' => 'DS18B20_1',
                    'dallas2_temp' => 'DS18B20_2',
                    'bmp_temp' => 'BMP180_TEMP',
                    'pressure' => 'BMP180_PRESSURE'
                ];
                
                foreach ($sensors as $json_key => $sensor_type) {
                    if (!isset($json_data[$json_key])) {
                        $this->log("Skipping {$sensor_type} - no data available");
                        continue;
                    }
                    
                    try {
                        $value = round((float)$json_data[$json_key], 2);
                        $this->log("Processing {$sensor_type} = {$value}");
                        
                        $sql = "INSERT INTO sensor_data (device_id, sensor_type, value, timestamp) VALUES (?, ?, ?, NOW())";
                        $this->log("SQL: " . $sql);
                        $this->log("Parameters: [{$device_id}, {$sensor_type}, {$value}]");
                        
                        $stmt = $this->db->getConnection()->prepare($sql);
                        if (!$stmt) {
                            $this->log("Failed to prepare statement");
                            continue;
                        }
                        
                        $result = $stmt->execute([$device_id, $sensor_type, $value]);
                        if ($result) {
                            $this->log("Successfully saved {$sensor_type} = {$value}");
                        } else {
                            $error = $stmt->errorInfo();
                            $this->log("Failed to save {$sensor_type}. Error: " . print_r($error, true));
                        }
                    } catch (PDOException $e) {
                        $this->log("Database error saving {$sensor_type}: " . $e->getMessage());
                    }
                }
                $this->log("Temperature processing complete");
            }
            // Status processing
            elseif ($message_type === 'status') {
                $this->log("Processing status message");
                $this->log("Device ID: " . $device_id);
                
                try {
                    // Update device status
                    $sql = "UPDATE devices SET last_seen = NOW(), status = ? WHERE id = ?";
                    $stmt = $this->db->getConnection()->prepare($sql);
                    
                    $status = isset($json_data['status']) ? $json_data['status'] : 'online';
                    $result = $stmt->execute([$status, $device_id]);
                    
                    if ($result) {
                        $this->log("Successfully updated device status to: " . $status);
                    } else {
                        $error = $stmt->errorInfo();
                        $this->log("Failed to update device status. Error: " . print_r($error, true));
                    }
                } catch (PDOException $e) {
                    $this->log("Database error updating device status: " . $e->getMessage());
                }
            }
            else {
                $this->log("Unknown message type: " . $message_type);
            }
            
        } catch (Exception $e) {
            $this->log("General error: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
        }
        
        $this->log("=== Message Processing Complete ===\n");
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
