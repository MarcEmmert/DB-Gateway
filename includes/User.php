<?php
// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/user_debug.log');

class User {
    private $db;
    
    public function __construct($database = null) {
        try {
            if ($database === null) {
                $database = Database::getInstance();
            }
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception("Keine Datenbankverbindung verfügbar");
            }
            
            // Test-Query ausführen
            $stmt = $this->db->query("SELECT 1");
            if (!$stmt) {
                throw new Exception("Datenbankverbindung fehlgeschlagen");
            }
            
            error_log("User class initialized with database connection");
            
        } catch (Exception $e) {
            error_log("Error in User constructor: " . $e->getMessage());
            throw new Exception("Datenbankfehler: " . $e->getMessage());
        }
    }
    
    public function authenticate($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            error_log("Login attempt for user: " . $username);
            
            if ($user && password_verify($password, $user['password'])) {
                error_log("Login successful for user: " . $username);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
            
            error_log("Login failed for user: " . $username);
            return false;
        } catch (Exception $e) {
            error_log("Database error during login: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($data) {
        try {
            error_log("Starting user creation");
            error_log("Create data: " . print_r($data, true));
            
            // Validierung
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                throw new Exception("Benutzername, E-Mail und Passwort sind erforderlich");
            }
            
            // Prüfe ob Username bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Fehler beim Vorbereiten der Benutzernamenprüfung");
            }
            if (!$stmt->execute([$data['username']])) {
                throw new Exception("Fehler beim Prüfen des Benutzernamens");
            }
            if ($stmt->fetch()) {
                throw new Exception("Dieser Benutzername ist bereits vergeben");
            }
            
            // Prüfe ob Email bereits existiert
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Fehler beim Vorbereiten der E-Mail-Prüfung");
            }
            if (!$stmt->execute([$data['email']])) {
                throw new Exception("Fehler beim Prüfen der E-Mail");
            }
            if ($stmt->fetch()) {
                throw new Exception("Diese E-Mail-Adresse ist bereits vergeben");
            }
            
            // Benutzer erstellen
            $sql = "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)";
            error_log("Insert SQL: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $error = $this->db->errorInfo();
                error_log("Prepare failed: " . print_r($error, true));
                throw new Exception("Fehler beim Vorbereiten der Datenbankabfrage");
            }
            
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $values = [
                $data['username'],
                $data['email'],
                $hash,
                isset($data['is_admin']) ? 1 : 0
            ];
            error_log("Insert values: " . print_r($values, true));
            
            if (!$stmt->execute($values)) {
                $error = $stmt->errorInfo();
                error_log("Execute failed: " . print_r($error, true));
                throw new Exception("Fehler beim Ausführen der Datenbankabfrage");
            }
            
            $newId = $this->db->lastInsertId();
            error_log("Created new user with ID: " . $newId);
            
            return $newId;
            
        } catch (PDOException $e) {
            error_log("PDO Error in create: " . $e->getMessage());
            throw new Exception("Datenbankfehler: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in User::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        try {
            error_log("Starting user update for ID: " . $id);
            error_log("Update data: " . print_r($data, true));
            
            // Validiere die Daten
            if (empty($data['username']) || empty($data['email'])) {
                throw new Exception("Benutzername und E-Mail sind erforderlich");
            }
            
            try {
                // Prüfe ob der Benutzer existiert
                $checkStmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
                if (!$checkStmt) {
                    throw new Exception("Fehler beim Vorbereiten der Benutzerprüfung");
                }
                if (!$checkStmt->execute([$id])) {
                    throw new Exception("Fehler beim Prüfen des Benutzers");
                }
                if (!$checkStmt->fetch()) {
                    throw new Exception("Benutzer nicht gefunden");
                }

                // Prüfe ob Username bereits existiert
                $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                if (!$stmt) {
                    throw new Exception("Fehler beim Vorbereiten der Benutzernamenprüfung");
                }
                if (!$stmt->execute([$data['username'], $id])) {
                    throw new Exception("Fehler beim Prüfen des Benutzernamens");
                }
                if ($stmt->fetch()) {
                    throw new Exception("Dieser Benutzername ist bereits vergeben");
                }
                
                // Prüfe ob Email bereits existiert
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                if (!$stmt) {
                    throw new Exception("Fehler beim Vorbereiten der E-Mail-Prüfung");
                }
                if (!$stmt->execute([$data['email'], $id])) {
                    throw new Exception("Fehler beim Prüfen der E-Mail");
                }
                if ($stmt->fetch()) {
                    throw new Exception("Diese E-Mail-Adresse ist bereits vergeben");
                }
                
                // Baue Update-Query
                $updates = [];
                $values = [];
                
                // Füge Felder einzeln hinzu
                $updates[] = "username = ?";
                $values[] = $data['username'];
                
                $updates[] = "email = ?";
                $values[] = $data['email'];
                
                if (isset($data['password'])) {
                    $updates[] = "password = ?";
                    $values[] = $data['password'];
                }
                
                $updates[] = "is_admin = ?";
                $values[] = isset($data['is_admin']) ? 1 : 0;
                
                // Füge ID für WHERE-Klausel hinzu
                $values[] = $id;
                
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                error_log("Update SQL: " . $sql);
                error_log("Update values: " . print_r($values, true));
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    $error = $this->db->errorInfo();
                    error_log("Prepare failed: " . print_r($error, true));
                    throw new Exception("Fehler beim Vorbereiten der Datenbankabfrage");
                }
                
                if (!$stmt->execute($values)) {
                    $error = $stmt->errorInfo();
                    error_log("Execute failed: " . print_r($error, true));
                    throw new Exception("Fehler beim Ausführen der Datenbankabfrage");
                }
                
                error_log("Update successful for user ID: " . $id);
                return true;
                
            } catch (PDOException $e) {
                error_log("PDO Error: " . $e->getMessage());
                throw new Exception("Datenbankfehler: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Error in User::update: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY username");
        return $stmt->fetchAll();
    }
    
    public function delete($id) {
        try {
            error_log("Starting user deletion for ID: " . $id);
            
            // Prüfe ob der Benutzer existiert
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            if (!$checkStmt) {
                throw new Exception("Fehler beim Vorbereiten der Benutzerprüfung");
            }
            if (!$checkStmt->execute([$id])) {
                throw new Exception("Fehler beim Prüfen des Benutzers");
            }
            if (!$checkStmt->fetch()) {
                throw new Exception("Benutzer nicht gefunden");
            }
            
            // Benutzer löschen
            $sql = "DELETE FROM users WHERE id = ?";
            error_log("Delete SQL: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $error = $this->db->errorInfo();
                error_log("Prepare failed: " . print_r($error, true));
                throw new Exception("Fehler beim Vorbereiten der Datenbankabfrage");
            }
            
            if (!$stmt->execute([$id])) {
                $error = $stmt->errorInfo();
                error_log("Execute failed: " . print_r($error, true));
                throw new Exception("Fehler beim Ausführen der Datenbankabfrage");
            }
            
            error_log("Delete successful for user ID: " . $id);
            return true;
            
        } catch (PDOException $e) {
            error_log("PDO Error in delete: " . $e->getMessage());
            throw new Exception("Datenbankfehler: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in User::delete: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function generateApiToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $this->db->prepare("
            INSERT INTO api_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$user_id, $token, $expires])) {
            return $token;
        }
        return false;
    }
}
