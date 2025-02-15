<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $config = require_once __DIR__ . '/../config.php';
        
        try {
            $this->conn = new PDO(
                "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
                $config['db']['user'],
                $config['db']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Datenbankfehler: " . $e->getMessage());
            die("Verbindung zur Datenbank fehlgeschlagen");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
