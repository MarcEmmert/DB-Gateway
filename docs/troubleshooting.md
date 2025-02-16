# Fehlerbehebung (Troubleshooting)

## Häufige Probleme und Lösungen

### 1. Verbindungsprobleme

#### Web-Interface nicht erreichbar
1. Apache-Status prüfen:
```bash
sudo systemctl status apache2
```

2. Logs prüfen:
```bash
sudo tail -f /var/log/apache2/iotgateway_error.log
```

3. Lösungen:
- Apache neustarten: `sudo systemctl restart apache2`
- Ports prüfen: `netstat -tulpn | grep apache2`
- Firewall-Regeln prüfen: `sudo ufw status`

#### MQTT-Verbindung fehlgeschlagen
1. Broker-Status:
```bash
sudo systemctl status mosquitto
```

2. Verbindung testen:
```bash
mosquitto_sub -v -h localhost -u iotgateway -P IhrPasswort -t "#"
```

3. Lösungen:
- Mosquitto neustarten
- Zugangsdaten prüfen
- Firewall-Ports öffnen (1883, 8883)

### 2. Datenbank-Probleme

#### Verbindungsfehler
1. MariaDB-Status:
```bash
sudo systemctl status mariadb
```

2. Verbindung testen:
```bash
mysql -u iotuser -p'01937736e' iotgateway -e "SELECT 1"
```

3. Lösungen:
- MariaDB neustarten
- Benutzerrechte prüfen
- Passwort zurücksetzen

#### Datenbank-Fehler
```sql
-- Tabellen prüfen
SHOW TABLES;
-- Struktur prüfen
DESCRIBE users;
DESCRIBE devices;
-- Berechtigungen prüfen
SHOW GRANTS FOR 'iotuser'@'localhost';
```

### 3. Sensor-Probleme

#### ESP32 verbindet nicht
1. Überprüfen Sie:
- WLAN-Zugangsdaten
- MQTT-Zugangsdaten
- IP-Adressen
- Firewall-Regeln

2. Debug-Ausgabe aktivieren:
```cpp
#define MQTT_DEBUG
#define WIFI_DEBUG
```

3. Serial Monitor prüfen:
```
115200 Baud
Debugging-Ausgaben analysieren
```

#### Sensordaten fehlerhaft
1. Hardware prüfen:
- Verkabelung
- Stromversorgung
- Pin-Belegung

2. Messungen validieren:
- Zweiten Sensor verwenden
- Manuelle Messung
- Kalibrierung prüfen

### 4. Web-Interface Probleme

#### PHP-Fehler
1. Logs prüfen:
```bash
sudo tail -f /var/log/apache2/php_error.log
```

2. PHP-Info anzeigen:
```php
<?php phpinfo(); ?>
```

3. Lösungen:
- PHP-Module installieren
- Berechtigungen korrigieren
- PHP-Version prüfen

#### JavaScript-Fehler
1. Browser-Konsole öffnen (F12)
2. Fehler analysieren
3. Cache leeren
4. JavaScript-Dateien prüfen

### 5. System-Probleme

#### Speicherplatz
```bash
# Speicherplatz prüfen
df -h

# Große Dateien finden
sudo find /var/www/html/iotgateway -type f -size +10M

# Logs bereinigen
sudo truncate -s 0 /var/log/apache2/iotgateway_error.log
```

#### Performance
```bash
# System-Last
top

# Apache-Prozesse
ps aux | grep apache

# MariaDB-Performance
mysqltuner
```

## FAQ

### Q: Wie setze ich ein vergessenes Admin-Passwort zurück?
A: Führen Sie folgende SQL-Befehle aus:
```sql
USE iotgateway;
UPDATE users SET password = '$2y$10$newHashedPassword' WHERE username = 'admin';
```

### Q: Warum werden keine Sensordaten empfangen?
A: Prüfen Sie:
1. MQTT-Verbindung
2. Topic-Struktur
3. Sensor-Verkabelung
4. Debug-Logs

### Q: Wie sichere ich die Datenbank?
A: Verwenden Sie:
```bash
mysqldump -u root -p iotgateway > backup.sql
```

### Q: Wie aktualisiere ich das System?
A: Führen Sie aus:
```bash
sudo apt update
sudo apt upgrade
sudo systemctl restart apache2 mariadb mosquitto
```

## Log-Analyse

### Apache-Logs
```bash
# Error-Log
sudo tail -f /var/log/apache2/iotgateway_error.log

# Access-Log
sudo tail -f /var/log/apache2/iotgateway_access.log
```

### MQTT-Logs
```bash
sudo tail -f /var/log/mosquitto/mosquitto.log
```

### PHP-Logs
```bash
sudo tail -f /var/log/apache2/php_error.log
```

## Debugging-Tools

### Network
```bash
# Netzwerk-Verbindungen
netstat -tulpn

# DNS
nslookup iotgateway.local

# Ports
nmap localhost
```

### Database
```bash
# MariaDB-Status
mysqladmin -u root -p status

# Prozesse
mysqladmin -u root -p processlist
```

### MQTT
```bash
# Topics überwachen
mosquitto_sub -v -h localhost -u iotgateway -P IhrPasswort -t "#"

# Publish Test
mosquitto_pub -h localhost -u iotgateway -P IhrPasswort -t "test" -m "test"
```

## Wartung

### Regelmäßige Wartung
1. Logs rotieren
2. Datenbank optimieren
3. Updates installieren
4. Backups erstellen

### Monitoring
1. System-Ressourcen überwachen
2. Fehlerprotokolle prüfen
3. Sensordaten validieren
4. Benutzeraktivität überprüfen
