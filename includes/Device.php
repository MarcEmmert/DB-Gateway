<?php
class Device {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    private function getLatestSensorData($device_id, $sensor_type) {
        $stmt = $this->db->prepare("
            SELECT sd.value, sd.timestamp,
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
            $device['sensors'] = [];
            
            // DS18B20 Temperatursensoren
            $temp1 = $this->getLatestSensorData($device['id'], 'DS18B20_1');
            if ($temp1) {
                $device['sensors']['DS18B20_1'] = $temp1;
            }
            
            $temp2 = $this->getLatestSensorData($device['id'], 'DS18B20_2');
            if ($temp2) {
                $device['sensors']['DS18B20_2'] = $temp2;
            }
            
            // BMP180 Sensoren
            $bmp_temp = $this->getLatestSensorData($device['id'], 'BMP180_TEMP');
            if ($bmp_temp) {
                $device['sensors']['BMP180_TEMP'] = $bmp_temp;
            }
            
            $pressure = $this->getLatestSensorData($device['id'], 'BMP180_PRESSURE');
            if ($pressure) {
                $device['sensors']['BMP180_PRESSURE'] = $pressure;
            }
        }
        
        return $devices;
    }
    
    public function getAllForUser($user_id, $is_admin = false) {
        if ($is_admin) {
            return $this->getAll();
        }
        
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
            $device['sensors'] = [];
            
            // DS18B20 Temperatursensoren
            $temp1 = $this->getLatestSensorData($device['id'], 'DS18B20_1');
            if ($temp1) {
                $device['sensors']['DS18B20_1'] = $temp1;
            }
            
            $temp2 = $this->getLatestSensorData($device['id'], 'DS18B20_2');
            if ($temp2) {
                $device['sensors']['DS18B20_2'] = $temp2;
            }
            
            // BMP180 Sensoren
            $bmp_temp = $this->getLatestSensorData($device['id'], 'BMP180_TEMP');
            if ($bmp_temp) {
                $device['sensors']['BMP180_TEMP'] = $bmp_temp;
            }
            
            $pressure = $this->getLatestSensorData($device['id'], 'BMP180_PRESSURE');
            if ($pressure) {
                $device['sensors']['BMP180_PRESSURE'] = $pressure;
            }
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
        $mqtt_topic = 'device/' . uniqid();
        
        $stmt = $this->db->prepare("
            INSERT INTO devices (name, description, user_id, mqtt_topic)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $description, $user_id, $mqtt_topic]);
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
    
    public function getTemperatures($device_id, $limit = 24) {
        $query = "
            SELECT sd.value, sd.timestamp, sd.sensor_type,
                   COALESCE(sc.display_name,
                   CASE sd.sensor_type 
                       WHEN 'DS18B20_1' THEN 'Dallas 1'
                       WHEN 'DS18B20_2' THEN 'Dallas 2'
                       WHEN 'BMP180_TEMP' THEN 'BMP180'
                       WHEN 'BMP180_PRESSURE' THEN 'Luftdruck'
                       ELSE sd.sensor_type
                   END) as display_name,
                   CASE sd.sensor_type 
                       WHEN 'BMP180_PRESSURE' THEN 'hPa'
                       ELSE '°C'
                   END as unit
            FROM sensor_data sd
            LEFT JOIN sensor_config sc ON sd.device_id = sc.device_id AND sd.sensor_type = sc.sensor_type
            WHERE sd.device_id = ? 
            AND sd.sensor_type IN ('DS18B20_1', 'DS18B20_2', 'BMP180_TEMP', 'BMP180_PRESSURE')
            AND sd.timestamp >= NOW() - INTERVAL 1 HOUR
            ORDER BY sd.timestamp DESC, sd.sensor_type
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$device_id, $limit]);
        $results = $stmt->fetchAll();
        
        return $results;
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
}
