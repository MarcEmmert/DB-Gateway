<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';

// POST-Daten prüfen
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Anmeldedaten']);
    exit;
}

$user = new User();
if ($user->authenticate($input['username'], $input['password'])) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Ungültige Anmeldedaten']);
}
