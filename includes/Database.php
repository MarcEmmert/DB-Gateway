<?php
class Database {
    private static $instance = null;
    private $conn;
    private $config;
    
    private function __construct($forceNew = false) {
        if ($forceNew) {
            self::$instance = null;
        }
        
        $this->config = require __DIR__ . '/../config.php';
        error_log("Database config loaded: " . print_r($this->config, true));
        
        if (!isset($this->config['db']) || !is_array($this->config['db'])) {
            throw new Exception("Invalid database configuration format");
        }

        $dbConfig = $this->config['db'];
        $required = ['host', 'name', 'user', 'password'];
        foreach ($required as $field) {
            if (!isset($dbConfig[$field])) {
                throw new Exception("Missing required database config field: {$field}");
            }
        }
        
        $this->connect();
    }
    
    private function connect() {
        try {
            $dbConfig = $this->config['db'];
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
            if ($dbConfig['host'] === 'localhost') {
                // Force TCP connection instead of socket
                $dsn = "mysql:host=127.0.0.1;dbname={$dbConfig['name']};charset=utf8mb4";
            }
            
            error_log("Attempting database connection with DSN: " . preg_replace('/password=([^;]*)/', 'password=***', $dsn));
            error_log("Using database user: " . $dbConfig['user']);
            
            $this->conn = new PDO(
                $dsn,
                $dbConfig['user'],
                $dbConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4; SET time_zone = '+01:00';"
                ]
            );
            
            // Test the connection
            $this->conn->query("SELECT 1");
            error_log("Database connection established successfully");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            error_log("Connection trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function isConnected() {
        if (!$this->conn) {
            return false;
        }
        
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database connection check failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getInstance($forceNew = false) {
        if (self::$instance === null || $forceNew) {
            self::$instance = new self($forceNew);
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if (!$this->isConnected()) {
            error_log("Reconnecting to database...");
            $this->connect();
        }
        return $this->conn;
    }
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
