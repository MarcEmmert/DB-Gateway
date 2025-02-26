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
    error_log("delete_user.php started");
    
    // Überprüfen, ob der Benutzer eingeloggt und Admin ist
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        throw new Exception("Nicht autorisiert");
    }

    // ID aus der URL holen
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    error_log("Attempting to delete user ID: " . $userId);
    
    if (!$userId) {
        throw new Exception("Benutzer-ID fehlt oder ungültig");
    }
    
    // Eigenen Account nicht löschen
    if ($userId === $_SESSION['user_id']) {
        throw new Exception("Sie können Ihren eigenen Account nicht löschen");
    }

    $user = new User();
    
    // Benutzer löschen
    if ($user->delete($userId)) {
        echo json_encode([
            'success' => true,
            'message' => 'Benutzer erfolgreich gelöscht'
        ]);
    } else {
        throw new Exception("Fehler beim Löschen des Benutzers");
    }

} catch (Exception $e) {
    error_log("Error in delete_user.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
