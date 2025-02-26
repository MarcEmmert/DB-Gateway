<?php
session_start();
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/User.php';

// Überprüfen, ob der Benutzer eingeloggt und Admin ist
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

$user = new User();
$users = $user->getAll();

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Benutzerverwaltung</h1>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                    data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Neuer Benutzer
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Benutzername</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if ($u['is_admin']): ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Benutzer</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="user_edit.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteUser(<?= $u['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal für neuen Benutzer -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neuer Benutzer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                            <label class="form-check-label" for="is_admin">
                                Administrator
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Benutzer bearbeiten -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Benutzer bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">E-Mail</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Neues Passwort (optional)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_admin" name="is_admin">
                            <label class="form-check-label" for="edit_is_admin">
                                Administrator
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-primary" onclick="updateUser()">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Base URL für API-Aufrufe - verwende die aktuelle URL als Basis
const baseUrl = window.location.origin;
console.log('Base URL:', baseUrl); // Debug-Ausgabe

function saveUser() {
    const formData = new FormData(document.getElementById('addUserForm'));
    const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        is_admin: formData.get('is_admin') === 'on'
    };
    
    console.log('Sending data:', data); // Debug-Ausgabe
    
    fetch(baseUrl + '/api/save_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug-Ausgabe
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // Debug-Ausgabe
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Speichern des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Speichern des Benutzers: ' + error.message);
    });
    
    return false; // Prevent form submission
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_is_admin').checked = user.is_admin;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function updateUser() {
    const formData = new FormData(document.getElementById('editUserForm'));
    const data = {
        id: document.getElementById('edit_user_id').value,
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        is_admin: formData.get('is_admin') === 'on'
    };
    
    console.log('Updating user with data:', data); // Debug-Ausgabe
    
    fetch(baseUrl + '/api/save_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug-Ausgabe
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // Debug-Ausgabe
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Fehler beim Aktualisieren des Benutzers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Aktualisieren des Benutzers: ' + error.message);
    });
    
    return false; // Prevent form submission
}

function deleteUser(id) {
    if (confirm('Möchten Sie diesen Benutzer wirklich löschen?')) {
        fetch(baseUrl + `/api/delete_user.php?id=${id}`, {
            method: 'GET'
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug-Ausgabe
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug-Ausgabe
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Fehler beim Löschen des Benutzers');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Löschen des Benutzers: ' + error.message);
        });
    }
}
</script>

<?php include 'templates/footer.php'; ?>
