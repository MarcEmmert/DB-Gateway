<?php
class Relay {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByDevice($device_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM relays
            WHERE device_id = ?
            ORDER BY relay_number
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function toggle($device_id, $relay_number) {
        if ($relay_number < 1 || $relay_number > 4) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE relays
            SET state = NOT state,
                last_changed = CURRENT_TIMESTAMP
            WHERE device_id = ?
            AND relay_number = ?
        ");
        return $stmt->execute([$device_id, $relay_number]);
    }
    
    public function setState($device_id, $relay_number, $state) {
        if ($relay_number < 1 || $relay_number > 4) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE relays
            SET state = ?,
                last_changed = CURRENT_TIMESTAMP
            WHERE device_id = ?
            AND relay_number = ?
        ");
        return $stmt->execute([$state ? 1 : 0, $device_id, $relay_number]);
    }
    
    public function initializeForDevice($device_id) {
        // Erstellt die 4 Relais für ein neues Gerät
        $stmt = $this->db->prepare("
            INSERT INTO relays (device_id, relay_number, name)
            VALUES 
            (?, 1, 'Relais 1'),
            (?, 2, 'Relais 2'),
            (?, 3, 'Relais 3'),
            (?, 4, 'Relais 4')
        ");
        return $stmt->execute([$device_id, $device_id, $device_id, $device_id]);
    }
    
    public function updateName($device_id, $relay_number, $name) {
        if ($relay_number < 1 || $relay_number > 4) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE relays
            SET name = ?
            WHERE device_id = ?
            AND relay_number = ?
        ");
        return $stmt->execute([$name, $device_id, $relay_number]);
    }
}
