// Temperatur-Chart erstellen
function createTemperatureChart(canvasId, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.time),
            datasets: [{
                label: 'Temperatur °C',
                data: data.map(d => d.value),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: value => `${value}°C`
                    }
                }
            }
        }
    });
}

// Relais-Status umschalten
function toggleRelay(deviceId, relayId) {
    const button = event.target;
    const currentState = button.classList.contains('btn-success');
    
    fetch('/api/toggle_relay.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            device_id: deviceId,
            relay_id: relayId,
            state: !currentState
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('btn-success');
            button.classList.toggle('btn-secondary');
        } else {
            alert('Fehler beim Schalten des Relais');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Schalten des Relais');
    });
}

// Automatische Aktualisierung der Daten
function updateDeviceData() {
    const deviceCards = document.querySelectorAll('[data-device-id]');
    
    deviceCards.forEach(card => {
        const deviceId = card.dataset.deviceId;
        
        fetch(`/api/device_data.php?id=${deviceId}`)
            .then(response => response.json())
            .then(data => {
                // Temperatur aktualisieren
                const tempDisplay = card.querySelector('.temperature-display');
                if (tempDisplay && data.temperature) {
                    tempDisplay.textContent = `${data.temperature.toFixed(1)}°C`;
                }
                
                // Online-Status aktualisieren
                const statusBadge = card.querySelector('.status-badge');
                if (statusBadge) {
                    const isOnline = new Date(data.last_seen) > new Date(Date.now() - 5 * 60 * 1000);
                    statusBadge.className = `badge ${isOnline ? 'bg-success' : 'bg-danger'}`;
                    statusBadge.textContent = isOnline ? 'Online' : 'Offline';
                }
                
                // Relais-Status aktualisieren
                data.relays?.forEach(relay => {
                    const relayButton = card.querySelector(`[data-relay-id="${relay.id}"]`);
                    if (relayButton) {
                        relayButton.className = `btn btn-sm ${relay.state ? 'btn-success' : 'btn-secondary'}`;
                    }
                });
                
                // Status-Kontakte aktualisieren
                data.status_contacts?.forEach(contact => {
                    const contactIndicator = card.querySelector(`[data-contact-id="${contact.id}"]`);
                    if (contactIndicator) {
                        contactIndicator.className = `status-indicator ${contact.state ? 'status-online' : 'status-offline'}`;
                    }
                });
            })
            .catch(error => console.error('Error:', error));
    });
}

// Alle 10 Sekunden aktualisieren
if (document.querySelector('[data-device-id]')) {
    setInterval(updateDeviceData, 10000);
}

// Dark Mode Toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Dark Mode beim Laden wiederherstellen
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

// Bootstrap Tooltips aktivieren
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
