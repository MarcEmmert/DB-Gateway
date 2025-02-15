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

// Device ID prüfen
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

// Temperaturdaten laden
$temperatures = $device->getTemperatures($device_data['id'], 1);
$latest_temp = $temperatures[0] ?? null;

// Relais-Status laden
$relays = $device->getRelays($device_data['id']);

// Status-Kontakte laden
$status_contacts = $device->getStatusContacts($device_data['id']);

// Antwort zusammenstellen
$response = [
    'id' => $device_data['id'],
    'name' => $device_data['name'],
    'last_seen' => $device_data['last_seen'],
    'temperature' => $latest_temp ? floatval($latest_temp['value']) : null,
    'temperature_timestamp' => $latest_temp ? $latest_temp['timestamp'] : null,
    'relays' => $relays,
    'status_contacts' => $status_contacts
];

echo json_encode($response);
