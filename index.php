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

$db = Database::getInstance();
$user = new User($db);
$device_manager = new Device($db);
$current_user = $user->getById($_SESSION['user_id']);

// Geräte des Benutzers laden
if ($_SESSION['is_admin']) {
    $devices = $device_manager->getAll();
} else {
    $devices = $device_manager->getByUser($_SESSION['user_id']);
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Dashboard</h1>
        </div>
        <?php if ($_SESSION['is_admin']): ?>
            <div class="col-md-6 text-end">
                <a href="device_add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Neues Gerät
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php foreach ($devices as $device): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($device['name']) ?></h5>
                            <?php if (strtotime($device['last_seen']) > strtotime('-5 minutes')): ?>
                                <span class="badge bg-success">Online</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Offline</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3"><?= htmlspecialchars($device['description']) ?></p>
                        
                        <!-- Sensordaten -->
                        <?php if (!empty($device['sensors'])): 
                            // Gruppiere Sensoren nach Einheit
                            $temperature_sensors = [];
                            $pressure_sensors = [];
                            
                            foreach ($device['sensors'] as $sensor) {
                                if ($sensor['unit'] === 'hPa') {
                                    $pressure_sensors[] = $sensor;
                                } else {
                                    $temperature_sensors[] = $sensor;
                                }
                            }
                        ?>
                            <!-- Temperaturen -->
                            <?php if (!empty($temperature_sensors)): ?>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($temperature_sensors as $sensor): ?>
                                        <div class="col-4">
                                            <div class="border rounded p-2 text-center">
                                                <div class="h4 mb-0"><?= number_format($sensor['value'], 1) ?><?= $sensor['unit'] ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($sensor['display_name']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Luftdruck -->
                            <?php if (!empty($pressure_sensors)): ?>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($pressure_sensors as $sensor): ?>
                                        <div class="col-6">
                                            <div class="border rounded p-2 text-center">
                                                <div class="h4 mb-0"><?= number_format($sensor['value'], 1) ?><?= $sensor['unit'] ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($sensor['display_name']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Relais -->
                        <?php 
                        $relays = $device_manager->getRelays($device['id']);
                        if (!empty($relays)): ?>
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2 mb-2">Relais</h6>
                                <div class="row g-2">
                                    <?php foreach ($relays as $relay): ?>
                                        <div class="col-6">
                                            <button class="btn btn-sm w-100 <?= $relay['state'] ? 'btn-success' : 'btn-secondary' ?>"
                                                    onclick="toggleRelay(<?= $device['id'] ?>, <?= $relay['id'] ?>, <?= $relay['state'] ? 0 : 1 ?>)"
                                                    data-relay-id="<?= $relay['id'] ?>">
                                                <?= htmlspecialchars($relay['display_name']) ?>
                                                <br>
                                                <small><?= $relay['state'] ? 'An' : 'Aus' ?></small>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Kontakte -->
                        <?php 
                        $contacts = $device_manager->getStatusContacts($device['id']);
                        $contact_config = $device_manager->getContactConfig($device['id']);
                        if (!empty($contacts)): ?>
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2 mb-2">Status-Kontakte</h6>
                                <div class="row g-2">
                                    <?php foreach ($contacts as $contact): 
                                        $config = $contact_config[$contact['contact_number']] ?? [];
                                        $isOpen = $contact['state'] == 0;
                                        $colorOpen = $config['color_open'] ?? '#dc3545';
                                        $colorClosed = $config['color_closed'] ?? '#28a745';
                                    ?>
                                        <div class="col-6">
                                            <div class="border rounded p-2 text-center" data-contact-id="<?= $contact['id'] ?>">
                                                <div class="small mb-1"><?= htmlspecialchars($contact['display_name'] ?? 'Kontakt ' . $contact['contact_number']) ?></div>
                                                <span class="badge" 
                                                      style="background-color: <?= $isOpen ? $colorOpen : $colorClosed ?>"
                                                      data-color-open="<?= $colorOpen ?>"
                                                      data-color-closed="<?= $colorClosed ?>">
                                                    <?= $isOpen ? 'Offen' : 'Geschlossen' ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="device_detail.php?id=<?= $device['id'] ?>" class="btn btn-primary btn-sm w-100">
                                Details anzeigen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// Base URL für API-Aufrufe
const baseUrl = window.location.origin;
console.log('Base URL:', baseUrl); // Debug-Ausgabe

async function toggleRelay(deviceId, relayId, newState) {
    try {
        const response = await fetch(baseUrl + '/api/toggle_relay.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                device_id: deviceId,
                relay_id: relayId,
                state: newState
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            // Button Status aktualisieren
            const button = document.querySelector(`button[data-relay-id="${relayId}"]`);
            if (button) {
                button.innerHTML = `${button.innerHTML.split('<br>')[0]}<br><small>${newState ? 'An' : 'Aus'}</small>`;
                button.classList.remove(newState ? 'btn-secondary' : 'btn-success');
                button.classList.add(newState ? 'btn-success' : 'btn-secondary');
                button.onclick = () => toggleRelay(deviceId, relayId, newState ? 0 : 1);
            }
        } else {
            alert('Fehler beim Schalten des Relais: ' + (data.message || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Schalten des Relais');
    }
}

// Funktion zum Aktualisieren der Kontakte
function updateContacts(deviceId) {
    fetch(`api/get_contacts.php?device_id=${deviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.contacts) {
                data.contacts.forEach(contact => {
                    const contactDiv = document.querySelector(`[data-contact-id="${contact.id}"]`);
                    if (contactDiv) {
                        const badge = contactDiv.querySelector('.badge');
                        if (badge) {
                            // Hole die gespeicherten Farben aus data-Attributen
                            const colorOpen = badge.getAttribute('data-color-open') || '#dc3545';
                            const colorClosed = badge.getAttribute('data-color-closed') || '#28a745';
                            
                            const isOpen = contact.state == 0;
                            badge.style.backgroundColor = isOpen ? colorOpen : colorClosed;
                            badge.textContent = isOpen ? 'Offen' : 'Geschlossen';
                        }
                    }
                });
            }
        });
}

// Automatische Aktualisierung der Status alle 5 Sekunden
setInterval(async () => {
    const deviceCards = document.querySelectorAll('.card');
    
    for (const card of deviceCards) {
        const deviceId = card.querySelector('a[href^="device_detail.php?id="]')?.href.split('=')[1];
        if (!deviceId) continue;
        
        // Relais-Status aktualisieren
        try {
            const relayResponse = await fetch(baseUrl + `/api/get_relays.php?device_id=${deviceId}`);
            const relayData = await relayResponse.json();
            
            if (relayData.success) {
                relayData.relays.forEach(relay => {
                    const button = card.querySelector(`button[data-relay-id="${relay.id}"]`);
                    if (button) {
                        const displayName = button.innerHTML.split('<br>')[0];
                        button.innerHTML = `${displayName}<br><small>${relay.state ? 'An' : 'Aus'}</small>`;
                        button.classList.remove(relay.state ? 'btn-secondary' : 'btn-success');
                        button.classList.add(relay.state ? 'btn-success' : 'btn-secondary');
                        button.onclick = () => toggleRelay(deviceId, relay.id, relay.state ? 0 : 1);
                    }
                });
            }
        } catch (error) {
            console.error('Fehler beim Aktualisieren der Relais:', error);
        }
        
        // Kontakt-Status aktualisieren
        updateContacts(deviceId);
    }
}, 5000);
</script>
