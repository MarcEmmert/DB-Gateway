<?php
session_start();
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/User.php';

// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/user_debug.log');

// Überprüfen, ob der Benutzer eingeloggt und Admin ist
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Überprüfen, ob eine Benutzer-ID übergeben wurde
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

try {
    $userId = intval($_GET['id']);
    $user = new User();
    $userData = $user->getById($userId);

    // Wenn der Benutzer nicht gefunden wurde
    if (!$userData) {
        throw new Exception("Benutzer nicht gefunden");
    }
} catch (Exception $e) {
    error_log("Error in user_edit.php: " . $e->getMessage());
    header('Location: users.php?error=' . urlencode($e->getMessage()));
    exit;
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Benutzer bearbeiten</h1>
        </div>
    </div>

    <div class="alert alert-danger" id="errorAlert" style="display: none;">
    </div>

    <div class="alert alert-success" id="successAlert" style="display: none;">
        Benutzer erfolgreich gespeichert
    </div>

    <div class="card">
        <div class="card-body">
            <form id="userForm" onsubmit="return saveUser(event);">
                <?php
                error_log("User ID in form: " . $userId);
                ?>
                <input type="hidden" id="user_id" name="id" value="<?= htmlspecialchars($userId) ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($userData['username']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($userData['email']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort (leer lassen für keine Änderung)</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           minlength="8" autocomplete="new-password">
                    <div class="form-text">Mindestens 8 Zeichen</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" 
                           <?= $userData['is_admin'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_admin">Administrator</label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="users.php" class="btn btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn btn-primary" id="saveButton">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Base URL für API-Aufrufe - verwende die aktuelle URL als Basis
const baseUrl = window.location.origin;
console.log('Base URL:', baseUrl); // Debug-Ausgabe

async function saveUser(event) {
    event.preventDefault();
    
    const form = document.getElementById('userForm');
    const errorAlert = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');
    const saveButton = document.getElementById('saveButton');
    const userId = document.getElementById('user_id').value;
    
    // Debug
    console.log('Base URL:', baseUrl);
    console.log('User ID from form:', userId);
    
    // Alerts ausblenden
    errorAlert.style.display = 'none';
    successAlert.style.display = 'none';
    
    // Button deaktivieren
    saveButton.disabled = true;
    saveButton.innerHTML = 'Speichere...';
    
    try {
        // Formulardaten sammeln
        const data = {
            id: parseInt(userId),
            username: document.getElementById('username').value.trim(),
            email: document.getElementById('email').value.trim(),
            is_admin: document.getElementById('is_admin').checked ? 1 : 0
        };
        
        // Validierung
        if (!data.id || isNaN(data.id)) {
            throw new Error('Ungültige Benutzer-ID');
        }
        if (!data.username) {
            throw new Error('Benutzername ist erforderlich');
        }
        if (!data.email) {
            throw new Error('E-Mail ist erforderlich');
        }
        
        // Passwort nur hinzufügen wenn es nicht leer ist
        const password = document.getElementById('password').value;
        if (password) {
            if (password.length < 8) {
                throw new Error('Passwort muss mindestens 8 Zeichen lang sein');
            }
            data.password = password;
        }
        
        console.log('Sending data:', data);
        
        // API-Aufruf mit korrekter Base-URL
        const response = await fetch(baseUrl + '/api/save_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        console.log('Response status:', response.status); // Debug-Ausgabe
        const result = await response.json();
        console.log('Server response:', result);
        
        if (!response.ok) {
            throw new Error(result.message || 'Ein Fehler ist aufgetreten');
        }
        
        // Erfolg anzeigen
        successAlert.style.display = 'block';
        setTimeout(() => {
            window.location.href = 'users.php?message=user_updated';
        }, 1000);
        
    } catch (error) {
        // Fehler anzeigen
        errorAlert.textContent = error.message;
        errorAlert.style.display = 'block';
        
        // Button wieder aktivieren
        saveButton.disabled = false;
        saveButton.innerHTML = 'Speichern';
        
        console.error('Error:', error);
    }
    
    return false;
}
</script>

<?php include 'templates/footer.php'; ?>
