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
        $result = $user->update($data['id'], [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => isset($data['is_admin']) ? (bool)$data['is_admin'] : false
        ]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Aktualisieren des Benutzers']);
        }
    } catch (Exception $e) {
        error_log("Fehler beim Aktualisieren des Benutzers: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Interner Serverfehler']);
    }
}
