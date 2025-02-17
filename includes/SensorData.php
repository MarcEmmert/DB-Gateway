<?php
class SensorData {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function addReading($device_id, $sensor_type, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO sensor_data (device_id, sensor_type, value)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$device_id, $sensor_type, $value]);
    }
    
    public function getLatestReadings($device_id) {
        $stmt = $this->db->prepare("
            SELECT sensor_type, value, timestamp
            FROM sensor_data
            WHERE device_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY timestamp DESC
            LIMIT 4
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll();
    }
    
    public function getReadingHistory($device_id, $sensor_type, $hours = 24) {
        $stmt = $this->db->prepare("
            SELECT value, timestamp
            FROM sensor_data
            WHERE device_id = ?
            AND sensor_type = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY timestamp DESC
        ");
        $stmt->execute([$device_id, $sensor_type, $hours]);
        return $stmt->fetchAll();
    }
}
