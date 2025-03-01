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

    public function getStatusContacts($device_id) {
        error_log("getStatusContacts aufgerufen für device_id: " . $device_id);
        
        $stmt = $this->db->prepare("
            SELECT sc.*, COALESCE(cc.display_name, sc.name) as display_name
            FROM status_contacts sc
            LEFT JOIN contact_config cc ON sc.device_id = cc.device_id AND sc.contact_number = cc.contact_number
            WHERE sc.device_id = ?
            ORDER BY sc.contact_number
        ");
        $stmt->execute([$device_id]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Gefundene Kontakte:");
        error_log(print_r($contacts, true));
        
        return $contacts;
    }

    public function toggleRelay($relay_id, $state) {
        $stmt = $this->db->prepare("
            UPDATE relays
            SET state = ?
            WHERE id = ?
        ");
        return $stmt->execute([$state, $relay_id]);
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
            SELECT contact_number, display_name as name, color_open, color_closed 
            FROM contact_config 
            WHERE device_id = ?
        ");
        $stmt->execute([$device_id]);
        
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['contact_number']] = [
                'name' => $row['name'],
                'color_open' => $row['color_open'],
                'color_closed' => $row['color_closed']
            ];
        }
        return $config;
    }

    public function updateContactConfig($device_id, $contact_number, $display_name, $color_open = '#dc3545', $color_closed = '#28a745') {
        $stmt = $this->db->prepare("
            INSERT INTO contact_config (device_id, contact_number, display_name, color_open, color_closed) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                display_name = VALUES(display_name),
                color_open = VALUES(color_open),
                color_closed = VALUES(color_closed)
        ");
        return $stmt->execute([$device_id, $contact_number, $display_name, $color_open, $color_closed]);
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
                '1h' => 60,    // Ein Punkt pro Minute
                '8h' => 96,    // Ein Punkt alle 5 Minuten
                '24h' => 144,  // Ein Punkt alle 10 Minuten
                '7d' => 168,   // Ein Punkt pro Stunde
                '30d' => 180,  // Ein Punkt alle 4 Stunden
                default => 144
            };

            // SQL für die Abfrage der Temperaturdaten
            $query = "
                SELECT timestamp, value, ? as sensor_type,
                       CASE ? WHEN 'BMP180_PRESSURE' THEN 'hPa' ELSE '°C' END as unit
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

            return $data;
        } catch (Exception $e) {
            error_log("Error in getTemperatureHistory: " . $e->getMessage());
            return false;
        }
    }
}
