<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/config.php';

// Datenbankverbindung
$db = Database::getInstance($config['db']);

// Alle Geräte auf "online" setzen
$stmt = $db->getConnection()->prepare("UPDATE devices SET last_seen = NOW()");
$stmt->execute();

echo "Alle Geräte wurden auf online gesetzt.";
