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

// Überprüfen, ob eine Geräte-ID übergeben wurde
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$user = new User($db);
$device_manager = new Device($db);
$current_user = $user->getById($_SESSION['user_id']);

// Gerätedaten laden
$device_data = $device_manager->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Sensordaten laden
$sensors = $device_manager->getTemperatures($device_data['id']);

// Relais laden
$relays = $device_manager->getRelays($device_data['id']);

// Status-Kontakte laden
$status_contacts = $device_manager->getStatusContacts($device_data['id']);

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($device_data['name']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center">
                <h1><?= htmlspecialchars($device_data['name']) ?></h1>
                <?php if (strtotime($device_data['last_seen']) > strtotime('-5 minutes')): ?>
                    <span class="badge bg-success">Online</span>
                <?php else: ?>
                    <span class="badge bg-danger">Offline</span>
                <?php endif; ?>
            </div>
            <p class="text-muted"><?= htmlspecialchars($device_data['description']) ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="device_config.php?id=<?= $device_data['id'] ?>" class="btn btn-primary me-2">
                <i class="fas fa-cog"></i> Konfigurieren
            </a>
            <a href="device_edit.php?id=<?= $device_data['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Bearbeiten
            </a>
        </div>
    </div>

    <!-- Sensordaten -->
    <?php if (!empty($sensors)): 
        // Gruppiere Sensoren nach Einheit
        $temperature_sensors = [];
        $pressure_sensors = [];
        
        foreach ($sensors as $sensor) {
            if ($sensor['unit'] === 'hPa') {
                $pressure_sensors[] = $sensor;
            } else {
                $temperature_sensors[] = $sensor;
            }
        }
    ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Sensoren</h5>
            </div>
            <div class="card-body">
                <!-- Temperaturen -->
                <?php if (!empty($temperature_sensors)): ?>
                    <div class="row g-3 mb-4">
                        <?php foreach ($temperature_sensors as $sensor): ?>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <div class="h3 mb-0"><?= number_format($sensor['value'], 1) ?><?= $sensor['unit'] ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($sensor['display_name']) ?></div>
                                    <div class="small text-muted mt-2">
                                        Aktualisiert: <?= date('d.m.Y H:i:s', strtotime($sensor['timestamp'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Luftdruck -->
                <?php if (!empty($pressure_sensors)): ?>
                    <div class="row g-3">
                        <?php foreach ($pressure_sensors as $sensor): ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="h3 mb-0"><?= number_format($sensor['value'], 1) ?><?= $sensor['unit'] ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($sensor['display_name']) ?></div>
                                    <div class="small text-muted mt-2">
                                        Aktualisiert: <?= date('d.m.Y H:i:s', strtotime($sensor['timestamp'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Relais -->
    <?php if (!empty($relays)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Relais</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($relays as $relay): ?>
                        <div class="col-md-3">
                            <button class="btn btn-lg w-100 <?= $relay['state'] ? 'btn-success' : 'btn-secondary' ?>"
                                    onclick="toggleRelay(<?= $device_data['id'] ?>, <?= $relay['id'] ?>, <?= $relay['state'] ? 0 : 1 ?>)"
                                    data-relay-id="<?= $relay['id'] ?>">
                                <?= htmlspecialchars($relay['display_name']) ?>
                                <br>
                                <small><?= $relay['state'] ? 'An' : 'Aus' ?></small>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status-Kontakte -->
    <?php 
    $status_contacts = $device_manager->getStatusContacts($device_data['id']);
    error_log("Status Contacts für Device " . $device_data['id'] . ":");
    error_log(print_r($status_contacts, true));
    ?>
    
    <?php if (!empty($status_contacts)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status-Kontakte</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($status_contacts as $contact): ?>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center" data-contact-id="<?= $contact['id'] ?>">
                                <div class="mb-2"><?= htmlspecialchars($contact['display_name']) ?></div>
                                <?php 
                                    // state=1 bedeutet geschlossen (grün), state=0 bedeutet offen (rot)
                                    $isOpen = $contact['state'] == 0;
                                ?>
                                <span class="badge <?= $isOpen ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $isOpen ? 'Offen' : 'Geschlossen' ?>
                                </span>
                                <div class="small text-muted mt-2">
                                    Zuletzt geändert: <?= date('d.m.Y H:i:s', strtotime($contact['last_changed'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>

<script>
async function toggleRelay(deviceId, relayId, newState) {
    try {
        const response = await fetch('api/toggle_relay.php', {
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
            showErrorDialog(data.message, data.details, {
                deviceId,
                relayId,
                newState
            });
        }
    } catch (error) {
        console.error('Fehler:', error);
        showErrorDialog('Technischer Fehler', null, {
            error: error.message,
            deviceId,
            relayId,
            newState
        });
    }
}

function showErrorDialog(message, details, context) {
    // Create error text
    let errorText = "=== Fehler beim Schalten des Relais ===\n\n";
    errorText += `Fehlermeldung: ${message}\n\n`;
    
    // Add context
    errorText += "=== Kontext ===\n";
    for (const [key, value] of Object.entries(context)) {
        errorText += `${key}: ${value}\n`;
    }
    
    // Add details if available
    if (details) {
        errorText += "\n=== Details ===\n";
        if (details.mqtt_log) {
            errorText += "\nMQTT Log:\n" + details.mqtt_log + "\n";
        }
        for (const [key, value] of Object.entries(details)) {
            if (key !== 'mqtt_log') {
                errorText += `${key}: ${value}\n`;
            }
        }
    }
    
    // Create dialog elements
    const dialogContainer = document.createElement('div');
    Object.assign(dialogContainer.style, {
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        backgroundColor: 'white',
        padding: '20px',
        borderRadius: '5px',
        boxShadow: '0 0 10px rgba(0,0,0,0.5)',
        zIndex: '1000',
        maxWidth: '80%',
        maxHeight: '80%',
        overflow: 'auto'
    });
    
    // Add title
    const title = document.createElement('h4');
    title.textContent = 'Fehler beim Schalten des Relais';
    title.style.marginBottom = '20px';
    dialogContainer.appendChild(title);
    
    // Add textarea
    const textarea = document.createElement('textarea');
    Object.assign(textarea.style, {
        width: '100%',
        height: '300px',
        padding: '10px',
        marginBottom: '10px',
        fontFamily: 'monospace'
    });
    textarea.value = errorText;
    textarea.readOnly = true;
    dialogContainer.appendChild(textarea);
    
    // Add buttons
    const buttonContainer = document.createElement('div');
    buttonContainer.style.textAlign = 'right';
    
    const copyButton = document.createElement('button');
    copyButton.textContent = 'Kopieren';
    copyButton.className = 'btn btn-secondary me-2';
    copyButton.onclick = () => {
        textarea.select();
        document.execCommand('copy');
        copyButton.textContent = 'Kopiert!';
        setTimeout(() => copyButton.textContent = 'Kopieren', 2000);
    };
    buttonContainer.appendChild(copyButton);
    
    const closeButton = document.createElement('button');
    closeButton.textContent = 'Schließen';
    closeButton.className = 'btn btn-primary';
    closeButton.onclick = () => document.body.removeChild(dialogContainer);
    buttonContainer.appendChild(closeButton);
    
    dialogContainer.appendChild(buttonContainer);
    
    // Add overlay
    const overlay = document.createElement('div');
    Object.assign(overlay.style, {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0,0,0,0.5)',
        zIndex: 999
    });
    overlay.onclick = () => {
        document.body.removeChild(overlay);
        document.body.removeChild(dialogContainer);
    };
    
    // Show dialog
    document.body.appendChild(overlay);
    document.body.appendChild(dialogContainer);
}

// Automatische Aktualisierung der Status alle 2 Sekunden
setInterval(async () => {
    // Device ID aus der URL holen
    const urlParams = new URLSearchParams(window.location.search);
    const deviceId = urlParams.get('id');  
    if (!deviceId) return;
    
    // Relais-Status aktualisieren
    try {
        const relayResponse = await fetch(`api/get_relays.php?device_id=${deviceId}`);
        const relayData = await relayResponse.json();
        
        if (relayData.success) {
            relayData.relays.forEach(relay => {
                const button = document.querySelector(`button[data-relay-id="${relay.id}"]`);
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
    try {
        const contactResponse = await fetch(`api/get_contacts.php?device_id=${deviceId}`);
        const contactData = await contactResponse.json();
        console.log('Contact API Response:', contactData);
        
        if (contactData.success && contactData.contacts) {
            contactData.contacts.forEach(contact => {
                const container = document.querySelector(`div[data-contact-id="${contact.id}"]`);
                console.log('Updating contact:', contact);
                console.log('Found container:', container);
                
                if (container) {
                    const badge = container.querySelector('.badge');
                    if (badge) {
                        // state=1 bedeutet geschlossen (grün), state=0 bedeutet offen (rot)
                        const isOpen = contact.state == 0;
                        console.log(`Contact ${contact.id} state: ${contact.state} -> isOpen: ${isOpen}`);
                        
                        badge.textContent = isOpen ? 'Offen' : 'Geschlossen';
                        badge.classList.remove(isOpen ? 'bg-success' : 'bg-danger');
                        badge.classList.add(isOpen ? 'bg-danger' : 'bg-success');
                    }
                    
                    const timeElement = container.querySelector('.text-muted');
                    if (timeElement) {
                        const date = new Date(contact.last_changed);
                        timeElement.textContent = `Zuletzt geändert: ${date.toLocaleString('de-DE')}`;
                    }
                }
            });
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren der Kontakte:', error);
    }
}, 2000);
</script>
