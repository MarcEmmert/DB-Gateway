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

$user = new User();
$device = new Device();
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
                        
                        <?php
                        $temps = $device->getTemperatures($device['id'], 1);
                        $latest_temp = $temps[0] ?? null;
                        ?>
                        
                        <?php if ($latest_temp): ?>
                            <h3 class="text-center"><?= number_format($latest_temp['value'], 1) ?>°C</h3>
                            <p class="text-muted text-center">
                                Letzte Messung: <?= date('H:i:s', strtotime($latest_temp['timestamp'])) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-center text-muted">Keine Temperaturdaten</p>
                        <?php endif; ?>
                        
                        <?php
                        $relays = $device->getRelays($device['id']);
                        if (!empty($relays)):
                        ?>
                            <hr>
                            <h6>Relais</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($relays as $relay): ?>
                                    <button class="btn btn-sm <?= $relay['state'] ? 'btn-success' : 'btn-secondary' ?>"
                                            onclick="toggleRelay(<?= $device['id'] ?>, <?= $relay['id'] ?>)">
                                        <?= htmlspecialchars($relay['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="device_detail.php?id=<?= $device['id'] ?>" 
                               class="btn btn-primary btn-sm w-100">
                                Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleRelay(deviceId, relayId) {
    fetch('api/toggle_relay.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            device_id: deviceId,
            relay_id: relayId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler beim Schalten des Relais');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Schalten des Relais');
    });
}
</script>

<?php include 'templates/footer.php'; ?>
