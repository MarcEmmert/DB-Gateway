<?php
session_start();
require_once 'includes/User.php';
require_once 'includes/Device.php';

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$device = new Device();
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Hole alle Geräte des Benutzers
$devices = $device->getByUser($_SESSION['user_id']);

include 'templates/header.php';
?>

<div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Gerät wurde erfolgreich <?= $success == 1 ? 'hinzugefügt' : 'aktualisiert' ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Meine Geräte</h2>
        <a href="device_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Neues Gerät
        </a>
    </div>

    <?php if (empty($devices)): ?>
        <div class="alert alert-info">
            Sie haben noch keine Geräte. Klicken Sie auf "Neues Gerät", um ein Gerät hinzuzufügen.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($devices as $dev): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($dev['name']) ?></h5>
                            <?php if ($dev['description']): ?>
                                <p class="card-text"><?= htmlspecialchars($dev['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="device_details.php?id=<?= $dev['id'] ?>" class="btn btn-primary btn-sm">
                                    Details
                                </a>
                                <a href="device_edit.php?id=<?= $dev['id'] ?>" class="btn btn-secondary btn-sm">
                                    Bearbeiten
                                </a>
                                <button class="btn btn-danger btn-sm" 
                                        onclick="deleteDevice(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name']) ?>')">
                                    Löschen
                                </button>
                            </div>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                MQTT Topic: <?= htmlspecialchars($dev['mqtt_topic']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteDevice(id, name) {
    if (confirm(`Möchten Sie das Gerät "${name}" wirklich löschen?`)) {
        window.location.href = `device_delete.php?id=${id}`;
    }
}
</script>

<?php include 'templates/footer.php'; ?>
