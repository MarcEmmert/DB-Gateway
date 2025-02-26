<?php
session_start();
require_once '../includes/Database.php';
require_once '../includes/User.php';

// Nur Administratoren haben Zugriff
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$user = new User($db);

// Einstellungen speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'impressum' => $_POST['impressum'] ?? '',
        'privacy' => $_POST['privacy'] ?? '',
        'site_title' => $_POST['site_title'] ?? '',
        'contact_email' => $_POST['contact_email'] ?? ''
    ];
    
    // Speichere in der Datenbank
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                            VALUES (:key, :value) 
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
    
    $success = "Einstellungen wurden gespeichert.";
}

// Aktuelle Einstellungen laden
$settings = [];
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include '../templates/header.php';
?>

<div class="container mt-4">
    <h1>Systemeinstellungen</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="site_title" class="form-label">Seitentitel</label>
                    <input type="text" class="form-control" id="site_title" name="site_title" 
                           value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="contact_email" class="form-label">Kontakt E-Mail</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                           value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="impressum" class="form-label">Impressum</label>
                    <textarea class="form-control" id="impressum" name="impressum" rows="10"><?= htmlspecialchars($settings['impressum'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="privacy" class="form-label">Datenschutzerklärung</label>
                    <textarea class="form-control" id="privacy" name="privacy" rows="10"><?= htmlspecialchars($settings['privacy'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
