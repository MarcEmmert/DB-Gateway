<?php
session_start();
require_once 'includes/User.php';
require_once 'includes/Device.php';

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Prüfen ob eine Geräte-ID übergeben wurde
if (!isset($_GET['id'])) {
    header('Location: devices.php');
    exit;
}

$device = new Device();
$deviceData = $device->getById($_GET['id']);

// Prüfen ob das Gerät existiert und dem Benutzer gehört
if (!$deviceData || $deviceData['user_id'] != $_SESSION['user_id']) {
    header('Location: devices.php');
    exit;
}

// Hole die letzten 24 Temperaturmessungen
$temperatures = $device->getTemperatures($deviceData['id']);

// Hole Relais und Status-Kontakte
$relays = $device->getRelays($deviceData['id']);
$statusContacts = $device->getStatusContacts($deviceData['id']);

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= htmlspecialchars($deviceData['name']) ?></h2>
        <div>
            <a href="device_edit.php?id=<?= $deviceData['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Bearbeiten
            </a>
            <a href="devices.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Geräteinformationen</h5>
                </div>
                <div class="card-body">
                    <p><strong>Beschreibung:</strong><br>
                    <?= htmlspecialchars($deviceData['description'] ?? 'Keine Beschreibung') ?></p>
                    
                    <p><strong>MQTT Topic:</strong><br>
                    <?= htmlspecialchars($deviceData['mqtt_topic']) ?></p>
                    
                    <p><strong>Status:</strong><br>
                    <span class="badge <?= time() - strtotime($deviceData['last_seen'] ?? '') < 300 ? 'bg-success' : 'bg-danger' ?>">
                        <?= time() - strtotime($deviceData['last_seen'] ?? '') < 300 ? 'Online' : 'Offline' ?>
                    </span>
                    </p>
                    
                    <?php if (isset($deviceData['last_seen'])): ?>
                        <p><strong>Zuletzt gesehen:</strong><br>
                        <?= date('d.m.Y H:i:s', strtotime($deviceData['last_seen'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            

<?php if (!empty($temperatures)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Temperaturverlauf</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="temperatureChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($relays)): ?>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Relais</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($relays as $relay): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span><?= htmlspecialchars($relay['name']) ?></span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           onchange="toggleRelay(<?= $deviceData['id'] ?>, <?= $relay['relay_number'] ?>, this.checked)"
                                           <?= $relay['state'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($statusContacts)): ?>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Status-Kontakte</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($statusContacts as $contact): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span><?= htmlspecialchars($contact['name']) ?></span>
                                <span class="badge <?= $contact['state'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $contact['state'] ? 'Geschlossen' : 'Offen' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php if (!empty($temperatures)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('temperatureChart').getContext('2d');
    const tempData = <?= json_encode(array_reverse($temperatures)) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: tempData.map(t => new Date(t.timestamp).toLocaleString()),
            datasets: [{
                label: 'Temperatur °C',
                data: tempData.map(t => t.value),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<!-- Immer einbinden, unabhängig von Temperaturen -->
<script src="js/device.js"></script>



<?php include 'templates/footer.php'; ?>
