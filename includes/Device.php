<?php
class Device {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    private function getLatestSensorData($device_id, $sensor_type) {
        $stmt = $this->db->prepare("
            SELECT sd.value, sd.timestamp, sd.sensor_type,
                   COALESCE(sc.display_name,
                   CASE sd.sensor_type 
                       WHEN 'DS18B20_1' THEN 'Dallas 1'
                       WHEN 'DS18B20_2' THEN 'Dallas 2'
                       WHEN 'BMP180_TEMP' THEN 'BMP180 Temp'
                       WHEN 'BMP180_PRESSURE' THEN 'Luftdruck'
                       ELSE sd.sensor_type
                   END) as display_name,
                   CASE sd.sensor_type 
                       WHEN 'BMP180_PRESSURE' THEN 'hPa'
                       ELSE '°C'
                   END as unit
            FROM sensor_data sd
            LEFT JOIN sensor_config sc ON sd.device_id = sc.device_id AND sd.sensor_type = sc.sensor_type
            WHERE sd.device_id = ? AND sd.sensor_type = ? 
            ORDER BY sd.timestamp DESC 
            LIMIT 1
        ");
        $stmt->execute([$device_id, $sensor_type]);
        return $stmt->fetch();
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT d.*, u.username as owner_name,
                   (SELECT COUNT(*) FROM relays r WHERE r.device_id = d.id) as relay_count
            FROM devices d
            JOIN users u ON d.user_id = u.id
            ORDER BY d.name
        ");
        $devices = $stmt->fetchAll();
        
        // Sensordaten für jedes Gerät laden
        foreach ($devices as &$device) {
            $device['sensors'] = $this->getTemperatures($device['id']);
        }
        
        return $devices;
    }
    
    public function getByUser($user_id) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.username as owner_name,
                   (SELECT COUNT(*) FROM relays r WHERE r.device_id = d.id) as relay_count
            FROM devices d
            JOIN users u ON d.user_id = u.id
            WHERE d.user_id = ?
            ORDER BY d.name
        ");
        $stmt->execute([$user_id]);
        $devices = $stmt->fetchAll();
        
        // Sensordaten für jedes Gerät laden
        foreach ($devices as &$device) {
            $device['sensors'] = $this->getTemperatures($device['id']);
        }
        
        return $devices;
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.username as owner_name
            FROM devices d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($name, $description, $user_id) {
        try {
            $this->db->beginTransaction();
            
            $mqtt_topic = 'device/' . uniqid();
            
            // Erstelle das Gerät
            $stmt = $this->db->prepare("
                INSERT INTO devices (name, description, user_id, mqtt_topic)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $user_id, $mqtt_topic]);
            $device_id = $this->db->lastInsertId();
            
            // Erstelle 4 Standard-Relais
            for ($i = 1; $i <= 4; $i++) {
                $relayName = "Relais " . $i;
                $stmt = $this->db->prepare("
                    INSERT INTO relays (device_id, relay_number, name, state)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$device_id, $i, $relayName]);
                
                // Füge Relais-Konfiguration hinzu
                $stmt = $this->db->prepare("
                    INSERT INTO relay_config (device_id, relay_number, display_name)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$device_id, $i, $relayName]);
            }
            
            // Erstelle 4 Standard-Kontakte
            for ($i = 1; $i <= 4; $i++) {
                $contactName = "Kontakt " . $i;
                $stmt = $this->db->prepare("
                    INSERT INTO status_contacts (device_id, contact_number, name, state)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$device_id, $i, $contactName]);
                
                // Füge Kontakt-Konfiguration hinzu
                $stmt = $this->db->prepare("
                    INSERT INTO contact_config (device_id, contact_number, display_name)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$device_id, $i, $contactName]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Fehler beim Erstellen des Geräts: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateDevice($id, $name, $description, $mqtt_topic) {
        $stmt = $this->db->prepare("
            UPDATE devices 
            SET name = ?, description = ?, mqtt_topic = ?
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $mqtt_topic, $id]);
    }
    
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Lösche zugehörige Sensordaten
            $stmt = $this->db->prepare("DELETE FROM sensor_data WHERE device_id = ?");
            $stmt->execute([$id]);
            
            // Lösche zugehörige Relais
            $stmt = $this->db->prepare("DELETE FROM relays WHERE device_id = ?");
            $stmt->execute([$id]);
            
            // Lösche zugehörige Status-Kontakte
            $stmt = $this->db->prepare("DELETE FROM status_contacts WHERE device_id = ?");
            $stmt->execute([$id]);
            
            // Lösche das Gerät
            $stmt = $this->db->prepare("DELETE FROM devices WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getTemperatures($device_id, $limit = 4) {
        $sensors = ['DS18B20_1', 'DS18B20_2', 'BMP180_TEMP', 'BMP180_PRESSURE'];
        $result = [];
        
        foreach ($sensors as $sensor_type) {
            $data = $this->getLatestSensorData($device_id, $sensor_type);
            if ($data) {
                $result[] = $data;
            }
        }
        
        return $result;
    }
    
    public function getRelays($device_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, COALESCE(rc.display_name, CONCAT('Relais ', r.relay_number)) as display_name
            FROM relays r
            LEFT JOIN relay_config rc ON r.device_id = rc.device_id AND r.relay_number = rc.relay_number
            WHERE r.device_id = ?
            ORDER BY r.relay_number
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function toggleRelay($relay_id, $state) {
        $stmt = $this->db->prepare("
            UPDATE relays
            SET state = ?
            WHERE id = ?
        ");
        return $stmt->execute([$state, $relay_id]);
    }
    
    public function getStatusContacts($device_id) {
        $stmt = $this->db->prepare("
            SELECT sc.*, COALESCE(cc.display_name, CONCAT('Kontakt ', sc.contact_number)) as display_name
            FROM status_contacts sc
            LEFT JOIN contact_config cc ON sc.device_id = cc.device_id AND sc.contact_number = cc.contact_number
            WHERE sc.device_id = ?
            ORDER BY sc.contact_number
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function updateStatusContact($contact_id, $state) {
        $stmt = $this->db->prepare("
            UPDATE status_contacts
            SET state = ?, last_changed = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$state, $contact_id]);
    }
    
    public function getSensorConfig($device_id) {
        $stmt = $this->db->prepare("
            SELECT sensor_type, display_name 
            FROM sensor_config 
            WHERE device_id = ?
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function updateSensorConfig($device_id, $sensor_type, $display_name) {
        $stmt = $this->db->prepare("
            INSERT INTO sensor_config (device_id, sensor_type, display_name)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)
        ");
        return $stmt->execute([$device_id, $sensor_type, $display_name]);
    }

    public function getRelayConfig($device_id) {
        $stmt = $this->db->prepare("
            SELECT relay_number, display_name 
            FROM relay_config 
            WHERE device_id = ?
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function updateRelayConfig($device_id, $relay_number, $display_name) {
        $stmt = $this->db->prepare("
            INSERT INTO relay_config (device_id, relay_number, display_name)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)
        ");
        return $stmt->execute([$device_id, $relay_number, $display_name]);
    }

    public function getContactConfig($device_id) {
        $stmt = $this->db->prepare("
            SELECT contact_number, display_name 
            FROM contact_config 
            WHERE device_id = ?
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function updateContactConfig($device_id, $contact_number, $display_name) {
        $stmt = $this->db->prepare("
            INSERT INTO contact_config (device_id, contact_number, display_name)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)
        ");
        return $stmt->execute([$device_id, $contact_number, $display_name]);
    }

    public function getTemperatureHistory($device_id, $sensor_type, $timespan = '24h') {
        try {
            // Bestimme das Zeitintervall basierend auf dem Timespan
            $interval = match($timespan) {
                '1h' => 'INTERVAL 1 HOUR',
                '8h' => 'INTERVAL 8 HOUR',
                '24h' => 'INTERVAL 24 HOUR',
                '7d' => 'INTERVAL 7 DAY',
                '30d' => 'INTERVAL 30 DAY',
                default => 'INTERVAL 24 HOUR'
            };
            
            // Bestimme die Anzahl der Datenpunkte
            $points = match($timespan) {
                '1h' => 60,  // Ein Punkt pro Minute
                '8h' => 96,  // Ein Punkt alle 5 Minuten
                '24h' => 144, // Ein Punkt alle 10 Minuten
                '7d' => 168,  // Ein Punkt pro Stunde
                '30d' => 180, // Ein Punkt alle 4 Stunden
                default => 144
            };
            
            // SQL für die Abfrage der Temperaturdaten
            $query = "
                SELECT 
                    timestamp,
                    value,
                    ? as sensor_type,
                    CASE ? 
                        WHEN 'BMP180_PRESSURE' THEN 'hPa'
                        ELSE '°C'
                    END as unit
                FROM sensor_data 
                WHERE device_id = ? 
                    AND sensor_type = ?
                    AND timestamp >= NOW() - $interval
                ORDER BY timestamp ASC
                LIMIT $points
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sensor_type, $sensor_type, $device_id, $sensor_type]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug-Logging
            error_log("Temperature History Query for device $device_id, sensor $sensor_type, timespan $timespan");
            error_log("Found " . count($data) . " data points");
            
            return $data;
        } catch (Exception $e) {
            error_log("Error in getTemperatureHistory: " . $e->getMessage());
            return false;
        }
    }
}
