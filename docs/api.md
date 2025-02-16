# API-Dokumentation

## REST API

### Authentifizierung
Alle API-Anfragen benötigen einen API-Token im Header:
```
Authorization: Bearer IhrAPIToken
```

Token generieren:
```php
POST /api/token
{
    "username": "IhrBenutzername",
    "password": "IhrPasswort"
}
```

### Geräte-Endpunkte

#### Geräteliste abrufen
```
GET /api/devices
```
Response:
```json
{
    "devices": [
        {
            "id": 1,
            "name": "Sensor 1",
            "description": "Temperatursensor Wohnzimmer",
            "mqtt_topic": "esp32/abc123",
            "last_seen": "2025-02-16T20:35:00Z"
        }
    ]
}
```

#### Gerät erstellen
```
POST /api/devices
{
    "name": "Neuer Sensor",
    "description": "Beschreibung",
    "location": "Standort"
}
```

#### Gerät aktualisieren
```
PUT /api/devices/{id}
{
    "name": "Geänderter Name",
    "description": "Neue Beschreibung"
}
```

#### Gerät löschen
```
DELETE /api/devices/{id}
```

### Sensor-Daten

#### Temperaturdaten abrufen
```
GET /api/devices/{id}/temperatures
```
Response:
```json
{
    "temperatures": [
        {
            "value": 21.5,
            "timestamp": "2025-02-16T20:35:00Z"
        }
    ]
}
```

#### Relais steuern
```
POST /api/devices/{id}/relay
{
    "relay": 1,
    "state": true
}
```

#### Status-Kontakte abrufen
```
GET /api/devices/{id}/contacts
```

## MQTT API

### Topic-Struktur

#### Gerätestatus
Topic: `esp32/{device_token}/status`
```json
{
    "online": true,
    "ip": "192.168.0.100",
    "rssi": -67,
    "uptime": 3600
}
```

#### Sensordaten
Topic: `esp32/{device_token}/data`
```json
{
    "temperature": 21.5,
    "humidity": 45.2,
    "contact1": true,
    "contact2": false,
    "relay1": true,
    "relay2": false
}
```

#### Steuerungsbefehle
Topic: `esp32/{device_token}/control`
```json
{
    "relay": 1,
    "state": true
}
```

### Beispiele

#### Python-Client
```python
import paho.mqtt.client as mqtt
import json

def on_connect(client, userdata, flags, rc):
    print(f"Connected with result code {rc}")
    client.subscribe("esp32/+/data")

def on_message(client, userdata, msg):
    data = json.loads(msg.payload)
    print(f"Topic: {msg.topic}, Data: {data}")

client = mqtt.Client()
client.username_pw_set("iotgateway", "IhrPasswort")
client.on_connect = on_connect
client.on_message = on_message

client.connect("192.168.0.100", 1883, 60)
client.loop_forever()
```

#### ESP32-Client
```cpp
#include <PubSubClient.h>
#include <ArduinoJson.h>

void publishData() {
    StaticJsonDocument<200> doc;
    doc["temperature"] = 21.5;
    doc["humidity"] = 45.2;
    
    char buffer[512];
    serializeJson(doc, buffer);
    client.publish("esp32/deviceToken/data", buffer);
}
```

## Webhooks

### Webhook registrieren
```
POST /api/webhooks
{
    "url": "https://ihre-domain.com/callback",
    "events": ["temperature", "contact", "relay"],
    "secret": "IhrWebhookSecret"
}
```

### Webhook-Format
```json
{
    "event": "temperature",
    "device_id": 1,
    "data": {
        "value": 21.5,
        "timestamp": "2025-02-16T20:35:00Z"
    },
    "signature": "sha256=..."
}
```

## Fehlerbehandlung

### HTTP-Statuscodes
- 200: Erfolg
- 201: Erstellt
- 400: Ungültige Anfrage
- 401: Nicht authentifiziert
- 403: Nicht autorisiert
- 404: Nicht gefunden
- 500: Server-Fehler

### Fehlerformat
```json
{
    "error": {
        "code": "invalid_request",
        "message": "Ungültige Parameter",
        "details": {
            "field": "name",
            "reason": "required"
        }
    }
}
```

## Ratenlimits

- 1000 Anfragen pro Stunde pro API-Token
- 100 Webhook-Aufrufe pro Minute
- Header für verbleibende Anfragen:
  - X-RateLimit-Limit
  - X-RateLimit-Remaining
  - X-RateLimit-Reset

## Sicherheit

### Token-Sicherheit
- Tokens sind 64 Zeichen lang
- Gültigkeit: 30 Tage
- Automatische Rotation möglich
- Sofortige Deaktivierung bei Kompromittierung

### HTTPS
- TLS 1.2 oder höher erforderlich
- Starke Cipher-Suites
- HSTS aktiviert
- Zertifikat-Pinning empfohlen

### Best Practices
1. Tokens sicher speichern
2. HTTPS verwenden
3. Webhook-Secrets validieren
4. Ratenlimits beachten
5. Fehlerbehandlung implementieren
