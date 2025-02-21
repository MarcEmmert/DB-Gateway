<?php
session_start();
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Device.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$user = new User($db);
$device = new Device($db);
$current_user = $user->getById($_SESSION['user_id']);

// Geräte des Benutzers laden
if ($_SESSION['is_admin']) {
    $devices = $device->getAll();
} else {
    $devices = $device->getByUser($_SESSION['user_id']);
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Dashboard</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="device_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neues Gerät
            </a>
        </div>
    </div>

    <div class="row">
        <?php foreach ($devices as $device): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($device['name']) ?></h5>
                            <?php if (strtotime($device['last_seen']) > strtotime('-5 minutes')): ?>
                                <span class="badge bg-success">Online</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Offline</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?= htmlspecialchars($device['description']) ?></p>
                        
                        <!-- Temperaturtabelle -->
                        <?php if (!empty($device['sensors'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sensor</th>
                                            <th>Wert</th>
                                            <th>Zeit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($device['sensors'] as $sensor): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($sensor['display_name']) ?></td>
                                                <td><?= number_format($sensor['value'], 1) ?><?= $sensor['unit'] ?></td>
                                                <td><?= date('H:i', strtotime($sensor['timestamp'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="device_detail.php?id=<?= $device['id'] ?>" class="btn btn-primary btn-sm">
                                Details anzeigen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
