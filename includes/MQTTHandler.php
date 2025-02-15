<?php
class MQTTHandler {
    private $mqtt;
    private $db;
    private $device;
    
    public function __construct() {
        $config = require __DIR__ . '/../config.php';
        $this->db = Database::getInstance()->getConnection();
        $this->device = new Device();
        
        $this->mqtt = new Mosquitto\Client();
        $this->mqtt->setCredentials($config['mqtt']['user'], $config['mqtt']['password']);
        $this->mqtt->connect($config['mqtt']['host'], $config['mqtt']['port'], 60);
    }
    
    public function subscribe($device_id) {
        $device = $this->device->getById($device_id);
        if ($device) {
            $this->mqtt->subscribe($device['mqtt_topic'] . '/#', 0);
        }
    }
    
    public function publish($topic, $message) {
        return $this->mqtt->publish($topic, json_encode($message), 0, false);
    }
    
    public function handleMessage($message) {
        $topic_parts = explode('/', $message->topic);
        $device_id = $topic_parts[1];
        $type = $topic_parts[2];
        
        $data = json_decode($message->payload, true);
        
        switch ($type) {
            case 'temperature':
                $this->device->addTemperature($device_id, $data['temperature']);
                break;
                
            case 'status':
                foreach ($data as $contact_id => $state) {
                    $this->device->updateStatusContact($contact_id, $state);
                }
                break;
        }
    }
    
    public function toggleRelay($device_id, $relay_id, $state) {
        $device = $this->device->getById($device_id);
        if ($device) {
            $topic = $device['mqtt_topic'] . '/relay/' . $relay_id;
            $message = ['state' => $state];
            return $this->publish($topic, $message);
        }
        return false;
    }
}
