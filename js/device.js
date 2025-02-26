function toggleRelay(deviceId, relayNumber, state) {
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
            // Toggle zurück
            event.target.checked = !event.target.checked;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Schalten des Relais');
        // Toggle zurück
        event.target.checked = !event.target.checked;
    });
}
