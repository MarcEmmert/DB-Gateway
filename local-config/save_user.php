<?php
session_start();
require_once '../includes/User.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    die('Nicht autorisiert');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    try {
        $user = new User();
        
        // Stelle sicher, dass is_admin ein Integer ist
        $isAdmin = isset($data['is_admin']) ? 
                  ($data['is_admin'] === 'on' || $data['is_admin'] === '1' || $data['is_admin'] === true ? 1 : 0) : 
                  0;
        
        $result = $user->create(
            $data['username'],
            $data['password'],
            $data['email'],
            $isAdmin
        );
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Speichern des Benutzers']);
        }
    } catch (Exception $e) {
        error_log("Fehler beim Erstellen des Benutzers: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Interner Serverfehler: ' . $e->getMessage()]);
    }
}
