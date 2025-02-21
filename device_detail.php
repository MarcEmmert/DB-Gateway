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

// Überprüfen, ob eine Device ID angegeben wurde
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$device = new Device($db);
$device_data = $device->getById($_GET['id']);

// Überprüfen, ob das Gerät existiert und dem Benutzer gehört
if (!$device_data || 
    (!$_SESSION['is_admin'] && $device_data['user_id'] !== $_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$temperatures = $device->getTemperatures($device_data['id']);
$relays = $device->getRelays($device_data['id']);
$status_contacts = $device->getStatusContacts($device_data['id']);

include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><?= htmlspecialchars($device_data['name']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($device_data['description']) ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="device_edit.php?id=<?= $device_data['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Bearbeiten
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($temperatures)): 
                // Gruppiere Sensoren nach Einheit
                $temperature_sensors = [];
                $pressure_sensors = [];
                
                foreach ($temperatures as $sensor) {
                    if ($sensor['unit'] === 'hPa') {
                        $pressure_sensors[] = $sensor;
                    } else {
                        $temperature_sensors[] = $sensor;
                    }
                }
                
                if (!empty($temperature_sensors)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Aktuelle Temperaturen</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($temperature_sensors as $temp): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3 text-center">
                                            <h3 class="mb-0"><?= number_format($temp['value'], 1) ?><?= $temp['unit'] ?></h3>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($temp['display_name']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pressure_sensors)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Aktueller Luftdruck</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($pressure_sensors as $pressure): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3 text-center">
                                            <h3 class="mb-0"><?= number_format($pressure['value'], 1) ?><?= $pressure['unit'] ?></h3>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($pressure['display_name']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Verlauf</h5>
                            <select class="form-select form-select-sm w-auto" id="timespan-select">
                                <option value="1h">Letzte Stunde</option>
                                <option value="8h">8 Stunden</option>
                                <option value="24h" selected>24 Stunden</option>
                                <option value="7d">7 Tage</option>
                                <option value="30d">30 Tage</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($temperature_sensors)): ?>
                            <div class="mb-4">
                                <h6>Temperaturverlauf</h6>
                                <canvas id="temperatureChart" height="300"></canvas>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($pressure_sensors)): ?>
                            <div>
                                <h6>Luftdruckverlauf</h6>
                                <canvas id="pressureChart" height="300"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($relays)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Relais</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($relays as $relay): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= htmlspecialchars($relay['display_name']) ?></span>
                                <button class="btn btn-sm <?= $relay['state'] ? 'btn-success' : 'btn-secondary' ?>"
                                        onclick="toggleRelay(<?= $relay['id'] ?>, <?= !$relay['state'] ?>)"
                                        data-relay-id="<?= $relay['id'] ?>">
                                    <?= $relay['state'] ? 'An' : 'Aus' ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($status_contacts)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Status-Kontakte</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($status_contacts as $contact): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?= htmlspecialchars($contact['display_name']) ?></span>
                                <span class="badge <?= $contact['state'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $contact['state'] ? 'Geschlossen' : 'Offen' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Geräteinformationen</h5>
                </div>
                <div class="card-body">
                    <p><strong>MQTT Topic:</strong><br><?= htmlspecialchars($device_data['mqtt_topic']) ?></p>
                    <p><strong>Letzte Aktivität:</strong><br>
                        <?php if (strtotime($device_data['last_seen']) > strtotime('-5 minutes')): ?>
                            <span class="text-success">Online</span>
                        <?php else: ?>
                            <span class="text-danger">Offline</span>
                            <br>
                            <small class="text-muted">
                                Zuletzt gesehen: <?= date('d.m.Y H:i:s', strtotime($device_data['last_seen'])) ?>
                            </small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let temperatureChart = null;
let pressureChart = null;

// Debug-Funktion
function debugLog(message) {
    console.log(message);
}

// Funktion zum Laden der Temperaturdaten
async function loadTemperatureData(timespan) {
    debugLog('Loading temperature data for timespan: ' + timespan);
    const temperatureSensors = ['DS18B20_1', 'DS18B20_2', 'BMP180_TEMP'];
    const pressureSensors = ['BMP180_PRESSURE'];
    const colors = ['#FF6384', '#36A2EB', '#FFCE56'];
    
    try {
        // Hole die aktuellen Sensordaten für die Anzeigenamen
        const sensorInfo = <?= json_encode($temperatures) ?>;
        debugLog('Sensor Info:', sensorInfo);
        
        // Lade Temperaturdaten
        const tempDatasets = await Promise.all(temperatureSensors.map(async (sensor, index) => {
            const response = await fetch(`api/get_temperature_history.php?device_id=<?= $device_data['id'] ?>&sensor_type=${sensor}&timespan=${timespan}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(`Fehler beim Laden der Daten für ${sensor}: ${data.error}`);
            }
            
            // Finde den passenden Sensor basierend auf dem Display-Namen
            let displayName;
            switch(sensor) {
                case 'DS18B20_1':
                    displayName = sensorInfo.find(s => s.display_name === 'DS1')?.display_name || 'DS1';
                    break;
                case 'DS18B20_2':
                    displayName = sensorInfo.find(s => s.display_name === 'DS2')?.display_name || 'DS2';
                    break;
                case 'BMP180_TEMP':
                    displayName = sensorInfo.find(s => s.display_name === 'BMP280')?.display_name || 'BMP280';
                    break;
                default:
                    displayName = sensor;
            }
            
            debugLog(`Sensor ${sensor} display name: ${displayName}`);
            
            return {
                label: displayName,
                data: data.data.map(point => ({
                    x: new Date(point.timestamp),
                    y: parseFloat(point.value)
                })),
                borderColor: colors[index],
                tension: 0.4
            };
        }));

        // Lade Luftdruckdaten
        const pressureDatasets = await Promise.all(pressureSensors.map(async (sensor) => {
            const response = await fetch(`api/get_temperature_history.php?device_id=<?= $device_data['id'] ?>&sensor_type=${sensor}&timespan=${timespan}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(`Fehler beim Laden der Daten für ${sensor}: ${data.error}`);
            }
            
            // Finde den Luftdrucksensor
            const displayName = sensorInfo.find(s => s.unit === 'hPa')?.display_name || 'Luftdruck';
            
            debugLog(`Sensor ${sensor} display name: ${displayName}`);
            
            return {
                label: displayName,
                data: data.data.map(point => ({
                    x: new Date(point.timestamp),
                    y: parseFloat(point.value)
                })),
                borderColor: '#4BC0C0',
                tension: 0.4
            };
        }));

        // Aktualisiere Temperaturdiagramm
        if (document.getElementById('temperatureChart')) {
            if (temperatureChart) {
                temperatureChart.destroy();
            }
            const tempCtx = document.getElementById('temperatureChart').getContext('2d');
            temperatureChart = new Chart(tempCtx, {
                type: 'line',
                data: { datasets: tempDatasets },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: timespan.includes('d') ? 'day' : 'hour',
                                displayFormats: {
                                    hour: 'HH:mm',
                                    day: 'DD.MM'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Zeit'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Temperatur (°C)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + 
                                           context.parsed.y.toFixed(1) + ' °C';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Aktualisiere Luftdruckdiagramm
        if (document.getElementById('pressureChart')) {
            if (pressureChart) {
                pressureChart.destroy();
            }
            const pressureCtx = document.getElementById('pressureChart').getContext('2d');
            pressureChart = new Chart(pressureCtx, {
                type: 'line',
                data: { datasets: pressureDatasets },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: timespan.includes('d') ? 'day' : 'hour',
                                displayFormats: {
                                    hour: 'HH:mm',
                                    day: 'DD.MM'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Zeit'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Luftdruck (hPa)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + 
                                           context.parsed.y.toFixed(1) + ' hPa';
                                }
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Fehler beim Laden der Daten:', error);
        debugLog('Error loading data: ' + error.message);
    }
}

// Event-Listener für Zeitspannenauswahl
document.getElementById('timespan-select').addEventListener('change', function() {
    loadTemperatureData(this.value);
});

// Funktion zum Umschalten der Relais
function toggleRelay(relayId, newState) {
    fetch('api/toggle_relay.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            relay_id: relayId,
            state: newState ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualisiere Button-Status
            const button = document.querySelector(`button[data-relay-id="${relayId}"]`);
            if (button) {
                button.classList.remove(newState ? 'btn-secondary' : 'btn-success');
                button.classList.add(newState ? 'btn-success' : 'btn-secondary');
                button.textContent = newState ? 'An' : 'Aus';
                button.onclick = () => toggleRelay(relayId, !newState);
            }
        } else {
            alert('Fehler beim Schalten des Relais');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Schalten des Relais');
    });
}

// Debug: Log initial state
debugLog('Script loaded, initializing...');

// Initialisiere Chart mit 24h Zeitspanne
loadTemperatureData('24h');

// Aktualisiere die Seite alle 5 Minuten
setInterval(() => {
    loadTemperatureData(document.getElementById('timespan-select').value);
}, 300000);
</script>

<?php include 'templates/footer.php'; ?>
