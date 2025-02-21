<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Device.php';

header('Content-Type: application/json');

if (!isset($_GET['device_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device ID is required']);
    exit;
}

$db = Database::getInstance();
$device = new Device($db);
$temps = $device->getTemperatures($_GET['device_id'], 4);

echo json_encode([
    'success' => true,
    'data' => $temps
]);
