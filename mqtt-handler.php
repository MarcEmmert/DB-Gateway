<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Device.php';
require_once __DIR__ . '/includes/SensorData.php';

class MQTTHandler {
    private $db;
    private $process;
    private $pipes;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
            error_log("Database connection established successfully");
            
            $config = require __DIR__ . '/config.php';
            
            if (!isset($config['mqtt'])) {
                throw new Exception("MQTT configuration not found");
            }
            
            $this->startMQTTClient($config['mqtt']);
        } catch (Exception $e) {
            error_log("Error in constructor: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function startMQTTClient($mqttConfig) {
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );
        
        // Baue den Befehl
        $cmd = "mosquitto_sub -h " . escapeshellarg($mqttConfig['host']);
        $cmd .= " -p " . intval($mqttConfig['port']);
        if (!empty($mqttConfig['user'])) {
            $cmd .= " -u " . escapeshellarg($mqttConfig['user']);
            $cmd .= " -P " . escapeshellarg($mqttConfig['password']);
        }
        $cmd .= " -t 'device/#' -v";  // -v gibt Topic und Nachricht aus
        
        $this->process = proc_open($cmd, $descriptorspec, $this->pipes);
        
        if (!is_resource($this->process)) {
            throw new Exception("Failed to start MQTT client");
        }
        
        // Nicht-blockierend machen
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);
    }
    
    public function handleMessage($topic, $payload) {
        error_log("Processing MQTT message - Topic: {$topic}, Payload: {$payload}");

        $parts = explode('/', $topic);
        if (count($parts) !== 3) {
            error_log("Invalid topic format: {$topic}");
            return;
        }

        $device_topic = "device/" . $parts[1] . "/#";
        $message_type = $parts[2];

        try {
            // Überprüfe die Datenbankverbindung
            if (!$this->db->isConnected()) {
                error_log("Database connection lost - attempting to reconnect");
                $this->db = Database::getInstance(true);
            }

            // Hole die Device ID aus der Datenbank basierend auf dem MQTT Topic
            $stmt = $this->db->getConnection()->prepare(
                "SELECT id FROM devices WHERE mqtt_topic = ?"
            );
            $stmt->execute([$device_topic]);
            $device = $stmt->fetch();

            if (!$device) {
                error_log("Unknown device for topic: {$device_topic}");
                return;
            }

            $device_id = $device['id'];
            $json_data = json_decode($payload, true);
            if (!$json_data) {
                error_log("Invalid JSON payload: {$payload}");
                return;
            }

            switch ($message_type) {
                case 'temperature':
                    if (isset($json_data['temperature'])) {
                        // Speichere Temperatur für DS18B20_1 und DS18B20_2
                        $stmt = $this->db->getConnection()->prepare(
                            "INSERT INTO sensor_data (device_id, sensor_type, value, timestamp) VALUES 
                            (?, 'DS18B20_1', ?, NOW()),
                            (?, 'DS18B20_2', ?, NOW())"
                        );
                        
                        // Speichere die Werte für beide Dallas-Sensoren
                        $stmt->execute([
                            $device_id, $json_data['temperature'],
                            $device_id, $json_data['temperature']
                        ]);
                        error_log("Temperature data saved for device {$device_id}: {$json_data['temperature']} (DS18B20_1 and DS18B20_2)");

                        // Wenn BMP180 aktiv ist, speichere auch diese Werte
                        if (isset($json_data['bmp_temp'])) {
                            $stmt = $this->db->getConnection()->prepare(
                                "INSERT INTO sensor_data (device_id, sensor_type, value, timestamp) VALUES 
                                (?, 'BMP180_TEMP', ?, NOW())"
                            );
                            $stmt->execute([$device_id, $json_data['bmp_temp']]);
                            error_log("BMP temperature data saved for device {$device_id}: {$json_data['bmp_temp']}");
                        }

                        // Wenn Luftdruck vorhanden ist
                        if (isset($json_data['pressure'])) {
                            $stmt = $this->db->getConnection()->prepare(
                                "INSERT INTO sensor_data (device_id, sensor_type, value, timestamp) 
                                VALUES (?, 'BMP180_PRESSURE', ?, NOW())"
                            );
                            $stmt->execute([$device_id, $json_data['pressure']]);
                            error_log("Pressure data saved for device {$device_id}: {$json_data['pressure']}");
                        }
                    }
                    break;

                case 'status':
                    // Aktualisiere den Status in der devices Tabelle
                    $stmt = $this->db->getConnection()->prepare(
                        "UPDATE devices SET last_seen = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$device_id]);
                    
                    // Log sensor status
                    if (isset($json_data['dallas1'])) {
                        error_log("Dallas1 sensor status for device {$device_id}: " . ($json_data['dallas1'] ? 'active' : 'inactive'));
                    }
                    if (isset($json_data['dallas2'])) {
                        error_log("Dallas2 sensor status for device {$device_id}: " . ($json_data['dallas2'] ? 'active' : 'inactive'));
                    }
                    if (isset($json_data['bmp'])) {
                        error_log("BMP sensor status for device {$device_id}: " . ($json_data['bmp'] ? 'active' : 'inactive'));
                    }
                    break;

                default:
                    error_log("Unknown message type: {$message_type}");
                    break;
            }
        } catch (PDOException $e) {
            error_log("Database error in handleMessage: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        } catch (Exception $e) {
            error_log("General error in handleMessage: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }
    
    public function processMessages() {
        while (true) {
            $line = fgets($this->pipes[1]);
            if ($line !== false) {
                // mosquitto_sub -v gibt Ausgabe im Format "topic payload" aus
                $parts = explode(" ", $line, 2);
                if (count($parts) === 2) {
                    $topic = trim($parts[0]);
                    $payload = trim($parts[1]);
                    $this->handleMessage($topic, $payload);
                }
            }
            
            // Prüfe auf Fehler
            $error = fgets($this->pipes[2]);
            if ($error !== false) {
                error_log("MQTT Error: " . trim($error));
            }
            
            // Kleine Pause um CPU-Last zu reduzieren
            usleep(100000); // 100ms
        }
    }
    
    public function __destruct() {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }
}

// MQTT Handler starten
try {
    $handler = new MQTTHandler();
    $handler->processMessages();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
