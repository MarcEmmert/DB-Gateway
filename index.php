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
$deviceManager = new Device($db);  // Umbenennung zu deviceManager für Klarheit
$current_user = $user->getById($_SESSION['user_id']);

// Geräte des Benutzers laden
if ($_SESSION['is_admin']) {
    $devices = $deviceManager->getAll();
} else {
    $devices = $deviceManager->getByUser($_SESSION['user_id']);
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
                        $temps = $deviceManager->getTemperatures($device['id'], 4);  // Hole 4 Werte
                        ?>
                        
                        <div id="device-data-<?= $device['id'] ?>">
                            <?php if (!empty($temps)): ?>
                                <div class="row g-2">
                                    <?php foreach ($temps as $temp): ?>
                                        <div class="col-3 text-center">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0"><?= number_format($temp['value'], 1) ?><?= $temp['unit'] ?></h5>
                                                <p class="text-muted small mb-0"><?= htmlspecialchars($temp['display_name']) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">Keine Temperaturdaten</p>
                            <?php endif; ?>
                        </div>

                        <script>
                        function updateDeviceData<?= $device['id'] ?>() {
                            fetch('api/get_device_data.php?device_id=<?= $device['id'] ?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.data.length > 0) {
                                        let html = '<div class="row g-2">';
                                        data.data.forEach(temp => {
                                            html += `
                                                <div class="col-3 text-center">
                                                    <div class="border rounded p-2">
                                                        <h5 class="mb-0">${parseFloat(temp.value).toFixed(1)}${temp.unit}</h5>
                                                        <p class="text-muted small mb-0">${temp.display_name}</p>
                                                    </div>
                                                </div>
                                            `;
                                        });
                                        html += '</div>';
                                        document.getElementById('device-data-<?= $device['id'] ?>').innerHTML = html;
                                    }
                                })
                                .catch(error => console.error('Error:', error));
                        }

                        // Aktualisiere alle 5 Sekunden
                        setInterval(updateDeviceData<?= $device['id'] ?>, 5000);
                        </script>
                        
                        <?php
                        $relays = $deviceManager->getRelays($device['id']);
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
            'Content-Type': 'application/json',
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
