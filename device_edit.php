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

$device = new Device();
$error = '';
$success = '';

// Wenn eine ID übergeben wurde, Gerät laden
if (isset($_GET['id'])) {
    $device_data = $device->getById($_GET['id']);
    
    // Überprüfen, ob das Gerät existiert und dem Benutzer gehört
    if (!$device_data || 
        (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'description' => $_POST['description']
    ];
    
    if (isset($_GET['id'])) {
        // Gerät aktualisieren
        if ($device->update($_GET['id'], $data)) {
            $success = 'Gerät wurde aktualisiert';
            $device_data = $device->getById($_GET['id']);
        } else {
            $error = 'Fehler beim Aktualisieren des Geräts';
        }
    } else {
        // Neues Gerät erstellen
        if ($device->create($_POST['name'], $_POST['description'], $_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Fehler beim Erstellen des Geräts';
        }
    }
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><?= isset($_GET['id']) ? 'Gerät bearbeiten' : 'Neues Gerät' ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($device_data['name'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3"><?= htmlspecialchars($device_data['description'] ?? '') ?></textarea>
                        </div>
                        
                        <?php if (isset($device_data)): ?>
                            <div class="mb-3">
                                <label class="form-label">MQTT Topic</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($device_data['mqtt_topic']) ?>"
                                       readonly>
                                <div class="form-text">
                                    Dieses Topic wird für die MQTT-Kommunikation verwendet.
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?= isset($_GET['id']) ? "device_detail.php?id={$_GET['id']}" : 'index.php' ?>"
                               class="btn btn-secondary">
                                Abbrechen
                            </a>
                            
                            <div>
                                <?php if (isset($_GET['id'])): ?>
                                    <button type="button" class="btn btn-danger me-2"
                                            onclick="deleteDevice(<?= $_GET['id'] ?>)">
                                        Löschen
                                    </button>
                                <?php endif; ?>
                                
                                <button type="submit" class="btn btn-primary">
                                    Speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteDevice(id) {
    if (confirm('Möchten Sie dieses Gerät wirklich löschen?')) {
        fetch(`/api/delete_device.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                alert('Fehler beim Löschen des Geräts');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Löschen des Geräts');
        });
    }
}
</script>

<?php include 'templates/footer.php'; ?>
