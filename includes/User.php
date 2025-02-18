<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            return true;
        }
        return false;
    }
    
    public function create($username, $password, $email, $is_admin = false) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, email, is_admin)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$username, $hash, $email, $is_admin]);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY username");
        return $stmt->fetchAll();
    }
    
    public function update($id, $data) {
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key === 'password' && !empty($value)) {
                $sets[] = "$key = ?";
                $params[] = password_hash($value, PASSWORD_DEFAULT);
            } elseif ($key !== 'password') {
                $sets[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($sets)) return false;
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
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
