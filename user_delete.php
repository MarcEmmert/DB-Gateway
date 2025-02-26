<?php
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

require_once 'includes/Database.php';
require_once 'includes/User.php';
require_once 'config.php';

$db = Database::getInstance($config['db']);
$user = new User($db);

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: users.php');
    exit;
}

// Verhindere, dass ein Admin sich selbst löscht
if ($id == $_SESSION['user_id']) {
    header('Location: users.php?error=' . urlencode('Sie können sich nicht selbst löschen.'));
    exit;
}

// Prüfe ob der Benutzer existiert
$user_data = $user->getById($id);
if (!$user_data) {
    header('Location: users.php?error=' . urlencode('Benutzer nicht gefunden.'));
    exit;
}

// Lösche den Benutzer
if ($user->delete($id)) {
    header('Location: users.php?success=' . urlencode('Benutzer wurde erfolgreich gelöscht.'));
} else {
    header('Location: users.php?error=' . urlencode('Fehler beim Löschen des Benutzers.'));
}
exit;
