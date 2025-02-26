<?php
session_start();
require_once 'includes/Database.php';
require_once 'includes/User.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Lade die Einstellungen
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'privacy'");
$stmt->execute();
$privacy = $stmt->fetchColumn();

include 'templates/header.php';
?>

<div class="container mt-4">
    <h1>Datenschutzerklärung</h1>
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="admin/settings.php" class="btn btn-primary mb-3">
                    <i class="fas fa-edit"></i> Datenschutzerklärung bearbeiten
                </a>
            <?php endif; ?>
            
            <div class="content">
                <?= nl2br(htmlspecialchars($privacy ?? 'Datenschutzerklärung wird vom Administrator eingerichtet.')) ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
