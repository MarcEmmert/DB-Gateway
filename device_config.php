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

$db = Database::getInstance();
$device = new Device($db);
$device_data = $device->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$temperatures = $device->getTemperatures($device_data['id']);
$relays = $device->getRelays($device_data['id']);
$status_contacts = $device->getStatusContacts($device_data['id']);

// Wenn das Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sensor-Namen aktualisieren
    if (isset($_POST['sensor_names']) && is_array($_POST['sensor_names'])) {
        foreach ($_POST['sensor_names'] as $id => $name) {
            $device->updateSensorName($id, $name);
        }
    }
    
    // Relais-Namen aktualisieren
    if (isset($_POST['relay_names']) && is_array($_POST['relay_names'])) {
        foreach ($_POST['relay_names'] as $id => $name) {
            $device->updateRelayName($id, $name);
        }
    }
    
    // Kontakt-Namen aktualisieren
    if (isset($_POST['contact_names']) && is_array($_POST['contact_names'])) {
        foreach ($_POST['contact_names'] as $id => $name) {
            $device->updateContactName($id, $name);
        }
    }
    
    // Zurück zur Detailseite
    header('Location: device_detail.php?id=' . $device_data['id']);
    exit;
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Gerätekonfiguration</h1>
            <p class="text-muted"><?= htmlspecialchars($device_data['name']) ?></p>
        </div>
    </div>

    <form method="post">
        <!-- Sensoren -->
        <?php if (!empty($temperatures)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Sensoren</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($temperatures as $sensor): ?>
                    <div class="col-md-4 mb-3">
                        <div class="form-group">
                            <label>Sensor (<?= htmlspecialchars($sensor['unit']) ?>)</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="sensor_names[<?= $sensor['id'] ?>]" 
                                   value="<?= htmlspecialchars($sensor['display_name']) ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Relais -->
        <?php if (!empty($relays)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Relais</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($relays as $relay): ?>
                    <div class="col-md-3 mb-3">
                        <div class="form-group">
                            <label>Relais <?= $relay['number'] ?></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="relay_names[<?= $relay['id'] ?>]" 
                                   value="<?= htmlspecialchars($relay['display_name']) ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status-Kontakte -->
        <?php if (!empty($status_contacts)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Status-Kontakte</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($status_contacts as $contact): ?>
                    <div class="col-md-3 mb-3">
                        <div class="form-group">
                            <label>Kontakt <?= $contact['number'] ?></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="contact_names[<?= $contact['id'] ?>]" 
                                   value="<?= htmlspecialchars($contact['display_name']) ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
                <a href="device_detail.php?id=<?= $device_data['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Abbrechen
                </a>
            </div>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
