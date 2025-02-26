<?php
session_start();
require_once 'includes/User.php';

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User();
$userData = $user->getById($_SESSION['user_id']);

$error = '';
$success = '';

// Verarbeite Formular-Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => $_POST['email']
    ];
    
    // Passwort nur aktualisieren wenn eines eingegeben wurde
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $data['password'] = $_POST['new_password'];
        } else {
            $error = 'Die Passwörter stimmen nicht überein';
        }
    }
    
    if (empty($error)) {
        if ($user->update($_SESSION['user_id'], $data)) {
            $success = 'Profil erfolgreich aktualisiert';
            $userData = $user->getById($_SESSION['user_id']); // Daten neu laden
        } else {
            $error = 'Fehler beim Aktualisieren des Profils';
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
                    <h3>Profil bearbeiten</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?= htmlspecialchars($userData['username']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($userData['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Neues Passwort</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" minlength="8">
                            <div class="form-text">Leer lassen, wenn das Passwort nicht geändert werden soll</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password">
                        </div>

                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
