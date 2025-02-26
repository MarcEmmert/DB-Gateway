<?php
session_start();
require_once 'includes/User.php';
require_once 'includes/Device.php';

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Verarbeite Formular-Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device = new Device();
    
    try {
        $result = $device->create([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'location' => $_POST['location'],
            'owner_id' => $_SESSION['user_id']
        ]);
        
        if ($result) {
            header('Location: devices.php?success=1');
            exit;
        } else {
            $error = 'Fehler beim Erstellen des Geräts';
        }
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Neues Gerät hinzufügen</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   required maxlength="50">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <textarea class="form-control" id="description" name="description" 
                                    rows="3" maxlength="255"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Standort</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   maxlength="100">
                        </div>

                        <button type="submit" class="btn btn-primary">Gerät hinzufügen</button>
                        <a href="devices.php" class="btn btn-secondary">Abbrechen</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
