<?php
class StatusContact {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByDevice($device_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM status_contacts
            WHERE device_id = ?
            ORDER BY contact_number
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function updateState($device_id, $contact_number, $state) {
        if ($contact_number < 1 || $contact_number > 4) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE status_contacts
            SET state = ?,
                last_changed = CURRENT_TIMESTAMP
            WHERE device_id = ?
            AND contact_number = ?
        ");
        return $stmt->execute([$state ? 1 : 0, $device_id, $contact_number]);
    }
    
    public function initializeForDevice($device_id) {
        // Erstellt die 4 Status-Kontakte für ein neues Gerät
        $stmt = $this->db->prepare("
            INSERT INTO status_contacts (device_id, contact_number, name)
            VALUES 
            (?, 1, 'Kontakt 1'),
            (?, 2, 'Kontakt 2'),
            (?, 3, 'Kontakt 3'),
            (?, 4, 'Kontakt 4')
        ");
        return $stmt->execute([$device_id, $device_id, $device_id, $device_id]);
    }
    
    public function updateName($device_id, $contact_number, $name) {
        if ($contact_number < 1 || $contact_number > 4) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE status_contacts
            SET name = ?
            WHERE device_id = ?
            AND contact_number = ?
        ");
        return $stmt->execute([$name, $device_id, $contact_number]);
    }
}
