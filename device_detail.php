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

// Überprüfen, ob eine Device ID angegeben wurde
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$device = new Device();
$device_data = $device->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$temperatures = $device->getTemperatures($device_data['id'], 24);
$relays = $device->getRelays($device_data['id']);
$status_contacts = $device->getStatusContacts($device_data['id']);

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><?= htmlspecialchars($device_data['name']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($device_data['description']) ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="device_edit.php?id=<?= $device_data['id'] ?>" 
               class="btn btn-primary">
                <i class="fas fa-edit"></i> Bearbeiten
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Temperaturverlauf</h5>
                </div>
                <div class="card-body">
                    <canvas id="temperatureChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Letzte Aktivität:</strong><br>
                        <?php if ($device_data['last_seen']): ?>
                            <?= date('d.m.Y H:i:s', strtotime($device_data['last_seen'])) ?>
                        <?php else: ?>
                            Noch keine Aktivität
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($temperatures)): ?>
                        <p>
                            <strong>Aktuelle Temperatur:</strong><br>
                            <span class="temperature-display">
                                <?= number_format($temperatures[0]['value'], 1) ?>°C
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($relays)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Relais</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($relays as $relay): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= htmlspecialchars($relay['name']) ?></span>
                                <button class="btn btn-sm <?= $relay['state'] ? 'btn-success' : 'btn-secondary' ?>"
                                        onclick="toggleRelay(<?= $device_data['id'] ?>, <?= $relay['id'] ?>)"
                                        data-relay-id="<?= $relay['id'] ?>">
                                    <?= $relay['state'] ? 'An' : 'Aus' ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($status_contacts)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Status-Kontakte</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($status_contacts as $contact): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= htmlspecialchars($contact['name']) ?></span>
                                <span class="status-indicator <?= $contact['state'] ? 'status-online' : 'status-offline' ?>"
                                      data-contact-id="<?= $contact['id'] ?>"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Temperaturdaten für Chart vorbereiten
const temperatureData = <?= json_encode(array_map(function($t) {
    return [
        'time' => date('H:i', strtotime($t['timestamp'])),
        'value' => floatval($t['value'])
    ];
}, array_reverse($temperatures))) ?>;

// Chart erstellen
document.addEventListener('DOMContentLoaded', function() {
    createTemperatureChart('temperatureChart', temperatureData);
});
</script>

<?php include 'templates/footer.php'; ?>
