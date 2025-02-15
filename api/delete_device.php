<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Überprüfen, ob eine ID angegeben wurde
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine Device ID angegeben']);
    exit;
}

$device = new Device();
$device_data = $device->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Zugriff verweigert']);
    exit;
}

// Gerät löschen
if ($device->delete($_GET['id'])) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Löschen des Geräts']);
}
