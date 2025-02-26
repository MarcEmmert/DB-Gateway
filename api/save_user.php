<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';

// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/user_debug.log');

header('Content-Type: application/json');

try {
    error_log("save_user.php started");
    error_log("Raw input: " . file_get_contents('php://input'));
    
    // Überprüfen, ob der Benutzer eingeloggt und Admin ist
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        throw new Exception("Nicht autorisiert");
    }

    // POST-Daten validieren
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Decoded data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception("Ungültige Anfrage-Daten: " . json_last_error_msg());
    }

    $user = new User();
    
    // Wenn eine ID vorhanden ist, aktualisieren wir einen bestehenden Benutzer
    if (isset($data['id']) && $data['id'] > 0) {
        $userId = intval($data['id']);
        error_log("Updating existing user with ID: " . $userId);
        
        $result = $user->update($userId, [
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'is_admin' => isset($data['is_admin']) ? 1 : 0,
            'password' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null
        ]);
        
        $message = "Benutzer erfolgreich aktualisiert";
    }
    // Sonst erstellen wir einen neuen Benutzer
    else {
        error_log("Creating new user");
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("Benutzername, E-Mail und Passwort sind erforderlich");
        }
        
        $result = $user->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'is_admin' => isset($data['is_admin']) ? 1 : 0,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
        
        $message = "Benutzer erfolgreich erstellt";
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    error_log("Error in save_user.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
