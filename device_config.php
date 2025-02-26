<?php
session_start();

// Konfiguration und Klassen laden
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Device.php';

// Nur eingeloggte Benutzer
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Datenbankverbindung
$db = Database::getInstance($config['db']);
$device = new Device($db);

// Gerät laden
$device_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$device_data = $device->getById($device_id);

if (!$device_data) {
    header('Location: index.php');
    exit;
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sensor-Konfiguration speichern
    if (isset($_POST['sensors'])) {
        foreach ($_POST['sensors'] as $sensor_type => $display_name) {
            if (!empty($display_name)) {
                $device->updateSensorConfig($device_id, $sensor_type, $display_name);
            }
        }
    }
    
    // Relais-Konfiguration speichern
    if (isset($_POST['relays'])) {
        foreach ($_POST['relays'] as $relay_number => $display_name) {
            if (!empty($display_name)) {
                $device->updateRelayConfig($device_id, $relay_number, $display_name);
            }
        }
    }
    
    // Kontakt-Konfiguration speichern
    if (isset($_POST['contacts'])) {
        foreach ($_POST['contacts'] as $contact_number => $config) {
            if (!empty($config['name'])) {
                $device->updateContactConfig(
                    $device_id, 
                    $contact_number, 
                    $config['name'],
                    $config['color_open'] ?? '#dc3545',
                    $config['color_closed'] ?? '#28a745'
                );
            }
        }
    }
    
    header('Location: device_detail.php?id=' . $device_id);
    exit;
}

// Aktuelle Konfigurationen laden
$temps = $device->getTemperatures($device_id, 1);
$relays = $device->getRelays($device_id);
$contacts = $device->getStatusContacts($device_id);

// Standard-Sensoren
$default_sensors = [
    'DS18B20_1' => 'Dallas 1',
    'DS18B20_2' => 'Dallas 2',
    'BMP180_TEMP' => 'BMP180 Temp',
    'BMP180_PRESSURE' => 'Luftdruck'
];

// Aktuelle Konfigurationen laden
$sensor_config = $device->getSensorConfig($device_id);
$relay_config = $device->getRelayConfig($device_id);
$contact_config = $device->getContactConfig($device_id);

require 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Gerätekonfiguration für <?= htmlspecialchars($device_data['name'] ?? '') ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <!-- Sensoren -->
                        <h6 class="mb-3">Sensoren</h6>
                        <?php foreach ($default_sensors as $sensor_type => $default_name): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= htmlspecialchars($default_name) ?></label>
                                <input type="text" 
                                       class="form-control" 
                                       name="sensors[<?= $sensor_type ?>]" 
                                       value="<?= htmlspecialchars($sensor_config[$sensor_type] ?? '') ?>" 
                                       placeholder="<?= htmlspecialchars($default_name) ?>">
                            </div>
                        <?php endforeach; ?>

                        <!-- Relais -->
                        <?php if (!empty($relays)): ?>
                            <h6 class="mb-3 mt-4">Relais</h6>
                            <?php foreach ($relays as $relay): ?>
                                <div class="mb-3">
                                    <label class="form-label">Relais <?= $relay['relay_number'] ?></label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="relays[<?= $relay['relay_number'] ?>]" 
                                           value="<?= htmlspecialchars($relay_config[$relay['relay_number']] ?? '') ?>" 
                                           placeholder="Relais <?= $relay['relay_number'] ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Kontakte -->
                        <?php if (!empty($contacts)): ?>
                            <h6 class="mb-3 mt-4">Status-Kontakte</h6>
                            <?php foreach ($contacts as $contact): ?>
                                <div class="mb-3">
                                    <label class="form-label">Kontakt <?= $contact['contact_number'] ?></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="contacts[<?= $contact['contact_number'] ?>][name]" 
                                                   value="<?= htmlspecialchars($contact_config[$contact['contact_number']]['name'] ?? '') ?>" 
                                                   placeholder="Kontakt <?= $contact['contact_number'] ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Farbe (Offen)</label>
                                            <input type="color" 
                                                   class="form-control form-control-color" 
                                                   name="contacts[<?= $contact['contact_number'] ?>][color_open]" 
                                                   value="<?= htmlspecialchars($contact_config[$contact['contact_number']]['color_open'] ?? '#dc3545') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Farbe (Geschlossen)</label>
                                            <input type="color" 
                                                   class="form-control form-control-color" 
                                                   name="contacts[<?= $contact['contact_number'] ?>][color_closed]" 
                                                   value="<?= htmlspecialchars($contact_config[$contact['contact_number']]['color_closed'] ?? '#28a745') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="device_detail.php?id=<?= $device_id ?>" class="btn btn-secondary">Zurück</a>
                            <button type="submit" class="btn btn-primary">Speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'templates/footer.php'; ?>
