# IoT Gateway

Ein PHP-basiertes Gateway für IoT-Geräte mit MQTT-Integration, speziell entwickelt für ESP32-Mikrocontroller.

## Features

- Echtzeit-Überwachung von ESP32-Geräten
- MQTT-Kommunikation für Live-Updates
- Sensor-Datenerfassung (DS18B20, BMP180)
- Relais-Steuerung (4 Kanäle)
- Status-Kontakte Überwachung (4 Kanäle)
- Benutzer-Management mit Admin-Interface
- Responsive Web-Interface mit Bootstrap

## Systemanforderungen

- PHP 7.4 oder höher
- MariaDB/MySQL Datenbank
- MQTT Broker (z.B. Mosquitto)
- Webserver (Apache/Nginx)

## Installation

1. Repository klonen:
```bash
git clone https://github.com/yourusername/DB-Gateway.git
```

2. Konfigurationsdatei erstellen:
```bash
cp config.example.php config.php
```

3. Konfiguration in `config.php` anpassen:
- Datenbank-Zugangsdaten
- MQTT-Broker-Einstellungen
- Weitere Systemeinstellungen

4. Datenbank-Struktur importieren:
```sql
mysql -u username -p database_name < database.sql
```

5. MQTT-Handler als Service einrichten:
```bash
nohup /usr/bin/php /path/to/mqtt_listener.php > /dev/null 2>&1 &
```

## ESP32 Konfiguration

### Pin-Belegung

- **Relais**:
  - Relais 1: GPIO26
  - Relais 2: GPIO27
  - Relais 3: GPIO2
  - Relais 4: GPIO12

- **Kontakte** (INPUT_PULLUP):
  - Kontakt 1: GPIO34
  - Kontakt 2: GPIO35
  - Kontakt 3: GPIO36
  - Kontakt 4: GPIO39

- **Sensoren**:
  - DS18B20: Standard OneWire Bus
  - BMP180: I2C (SDA/SCL)

### MQTT-Topics

- Status: `device/{device_id}/status`
- Temperatur: `device/{device_id}/temperature`
- Relais: `device/{device_id}/relay`

## API-Endpunkte

- `/api/get_sensor_data.php`: Aktuelle Sensordaten
- `/api/get_contacts.php`: Status der Kontakte
- `/api/get_relays.php`: Status der Relais
- `/api/toggle_relay.php`: Relais schalten

## Lizenz

MIT License
