<?php
class Device {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($name, $description, $user_id) {
        $mqtt_topic = 'esp32/' . bin2hex(random_bytes(8));
        
        $stmt = $this->db->prepare("
            INSERT INTO devices (name, description, user_id, mqtt_topic)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$name, $description, $user_id, $mqtt_topic]);
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
    
    public function getByUser($user_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM devices
            WHERE user_id = ?
            ORDER BY name
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT d.*, u.username as owner_name
            FROM devices d
            JOIN users u ON d.user_id = u.id
            ORDER BY d.name
        ");
        return $stmt->fetchAll();
    }
    
    public function update($id, $data) {
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $sql = "UPDATE devices SET " . implode(', ', $sets) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM devices WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function getTemperatures($device_id, $limit = 24) {
        $stmt = $this->db->prepare("
            SELECT * FROM temperatures
            WHERE device_id = ?
            ORDER BY timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$device_id, $limit]);
        return $stmt->fetchAll();
    }
    
    public function addTemperature($device_id, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO temperatures (device_id, value)
            VALUES (?, ?)
        ");
        return $stmt->execute([$device_id, $value]);
    }
    
    public function getRelays($device_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM relays
            WHERE device_id = ?
            ORDER BY name
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function toggleRelay($relay_id, $state) {
        $stmt = $this->db->prepare("
            UPDATE relays
            SET state = ?, last_changed = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$state, $relay_id]);
    }
    
    public function getStatusContacts($device_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM status_contacts
            WHERE device_id = ?
            ORDER BY name
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
}
