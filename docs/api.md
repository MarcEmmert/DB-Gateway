# API Dokumentation

## Relais-Steuerung

### Toggle Relay API Endpoint
- **Endpoint**: `/api/toggle_relay.php`
- **Methode**: POST
- **Content-Type**: application/json
- **Parameter**:
  - `device_id`: ID des Geräts (Integer)
  - `relay_number`: Nummer des Relais (1-4)
  - `state`: Status (0 oder 1)

### Wichtige Implementierungsdetails

#### 1. Datenbank-Struktur
Die Relais-Daten werden in der `relays`-Tabelle gespeichert:
\`\`\`sql
CREATE TABLE relays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    relay_number TINYINT NOT NULL,
    name VARCHAR(50) NOT NULL,
    state TINYINT(1) DEFAULT 0,
    last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
\`\`\`

#### 2. Frontend Implementation
In \`index.php\` und \`device_details.php\` muss der Toggle-Handler wie folgt implementiert sein:

\`\`\`html
<input class="form-check-input" type="checkbox" 
       onchange="toggleRelay(<?= $device['id'] ?>, <?= $relay['relay_number'] ?>, this.checked)"
       <?= $relay['state'] ? 'checked' : '' ?>>
\`\`\`

\`\`\`javascript
function toggleRelay(deviceId, relayNumber, state) {
    const checkbox = event.target;
    fetch('api/toggle_relay.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            device_id: deviceId,
            relay_number: relayNumber,
            state: state ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Fehler beim Schalten des Relais');
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Schalten des Relais');
        checkbox.checked = !checkbox.checked;
    });
}
\`\`\`

#### 3. Wichtige Hinweise
- Die \`relay_number\` muss verwendet werden, nicht die \`relay_id\`
- Das Event-Target (checkbox) muss gespeichert werden, um den Status zurückzusetzen
- Keine MQTT-Integration mehr nötig, direkte Datenbankaktualisierung
