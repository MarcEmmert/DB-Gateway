# MQTT-Konfiguration

## Broker-Setup

### Installation
```bash
sudo apt install -y mosquitto mosquitto-clients
```

### Grundkonfiguration
Erstellen Sie die Konfigurationsdatei:
```bash
sudo nano /etc/mosquitto/conf.d/default.conf
```

Fügen Sie diese Konfiguration ein:
```
# Basis-Einstellungen
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd

# Persistenz
persistence true
persistence_location /var/lib/mosquitto/

# Logging
log_dest file /var/log/mosquitto/mosquitto.log
log_type error
log_type warning
log_type notice
log_type information
```

### Benutzer erstellen
```bash
sudo mosquitto_passwd -c /etc/mosquitto/passwd iotgateway
```

### Dienst neustarten
```bash
sudo systemctl restart mosquitto
```

## Topic-Struktur

### Geräte-Topics
- `esp32/{device_token}/status` - Gerätestatus
- `esp32/{device_token}/data` - Sensordaten
- `esp32/{device_token}/control` - Steuerungsbefehle

### Datenformat
```json
{
    "temperature": 21.5,
    "humidity": 45.2,
    "contact1": true,
    "contact2": false,
    "relay1": true,
    "relay2": false,
    "timestamp": "2025-02-16T20:35:00Z"
}
```

### Steuerungsbefehle
```json
{
    "relay": 1,
    "state": true
}
```

## Sicherheit

### SSL/TLS Konfiguration
1. Zertifikate erstellen:
```bash
sudo mkdir /etc/mosquitto/certs
cd /etc/mosquitto/certs
sudo openssl req -new -x509 -days 365 -nodes -out mqtt.crt -keyout mqtt.key
```

2. Berechtigungen setzen:
```bash
sudo chown mosquitto: /etc/mosquitto/certs/mqtt.key
sudo chmod 600 /etc/mosquitto/certs/mqtt.key
```

3. SSL/TLS in Mosquitto aktivieren:
```
# In /etc/mosquitto/conf.d/default.conf hinzufügen:
listener 8883
cafile /etc/mosquitto/certs/mqtt.crt
keyfile /etc/mosquitto/certs/mqtt.key
tls_version tlsv1.2
```

### Zugriffskontrolle
- Verwenden Sie eindeutige Benutzer pro Gerät
- Beschränken Sie Zugriff auf spezifische Topics
- Aktivieren Sie ACLs (Access Control Lists)

## Monitoring

### Verbindungen überwachen
```bash
# Alle Messages
mosquitto_sub -v -h localhost -u iotgateway -P IhrPasswort -t "#"

# Spezifisches Gerät
mosquitto_sub -v -h localhost -u iotgateway -P IhrPasswort -t "esp32/IhrGeräteToken/#"
```

### Logs analysieren
```bash
sudo tail -f /var/log/mosquitto/mosquitto.log
```

## Fehlersuche

### Verbindungsprobleme
1. Broker-Status prüfen:
```bash
sudo systemctl status mosquitto
```

2. Ports prüfen:
```bash
sudo netstat -tulpn | grep mosquitto
```

3. Firewall-Regeln:
```bash
sudo ufw status
sudo ufw allow 1883
sudo ufw allow 8883  # Für SSL/TLS
```

### Authentifizierung
1. Passwort zurücksetzen:
```bash
sudo mosquitto_passwd -b /etc/mosquitto/passwd iotgateway NeuesPasswort
```

2. Berechtigungen prüfen:
```bash
ls -la /etc/mosquitto/passwd
```

## Best Practices

1. **Sicherheit**
   - Verwenden Sie starke Passwörter
   - Aktivieren Sie SSL/TLS
   - Implementieren Sie ACLs
   - Regelmäßige Updates

2. **Performance**
   - Optimieren Sie QoS-Level
   - Begrenzen Sie Message-Größe
   - Überwachen Sie Ressourcenverbrauch

3. **Wartung**
   - Regelmäßige Backups
   - Log-Rotation einrichten
   - Monitoring implementieren
