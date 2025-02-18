# IoT Gateway Installation

## System-Voraussetzungen
- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Apache2 mit mod_rewrite
- Git (optional)

## 1. Datenbank Setup

### 1.1 Datenbank erstellen
```sql
CREATE DATABASE iotgateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'iotgateway'@'localhost' IDENTIFIED BY 'IhrPasswort';
GRANT ALL PRIVILEGES ON iotgateway.* TO 'iotgateway'@'localhost';
FLUSH PRIVILEGES;
```

### 1.2 Tabellen erstellen
Führen Sie die SQL-Dateien in dieser Reihenfolge aus:

1. Basis-Tabellen:
```bash
mysql -u root -p iotgateway < database/create_schema.sql
```

2. Konfigurationstabellen:
```bash
mysql -u root -p iotgateway < database/create_config_tables.sql
```

## 2. Webserver Setup

### 2.1 Dateien installieren
```bash
# Option 1: Via Git
git clone https://github.com/yourusername/iotgateway.git /var/www/html/iotgateway

# Option 2: Manuell
# Kopieren Sie alle Dateien nach /var/www/html/iotgateway
```

### 2.2 Berechtigungen setzen
```bash
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo chmod -R 755 /var/www/html/iotgateway
```

### 2.3 Konfiguration anpassen
Kopieren Sie die Beispielkonfiguration:
```bash
cp config.example.php config.php
```

Passen Sie die Datenbankverbindung in `config.php` an:
```php
$config['db'] = [
    'host' => 'localhost',
    'dbname' => 'iotgateway',
    'username' => 'iotgateway',
    'password' => 'IhrPasswort'
];
```

## 3. Erste Schritte

### 3.1 Admin-Benutzer erstellen
```sql
INSERT INTO users (username, password, is_admin) 
VALUES ('admin', SHA2('IhrPasswort', 256), 1);
```

### 3.2 Testen
Öffnen Sie die Anwendung im Browser und melden Sie sich mit den Admin-Zugangsdaten an.

## 4. Sensor-Konfiguration

### 4.1 Unterstützte Sensoren
Die Anwendung unterstützt folgende Sensoren:
- DS18B20 (Temperatur)
  - Typ: DS18B20_1, DS18B20_2
  - Einheit: °C
- BMP180 (Temperatur und Luftdruck)
  - Typ: BMP180_TEMP (°C)
  - Typ: BMP180_PRESSURE (hPa)

### 4.2 Sensorbeschreibungen anpassen
1. Öffnen Sie die Gerätekonfiguration (device_config.php?id=X)
2. Geben Sie benutzerdefinierte Namen für:
   - Sensoren
   - Relais
   - Status-Kontakte

### 4.3 Standard-Beschreibungen
Falls keine benutzerdefinierten Namen gesetzt sind:
- DS18B20_1: "Dallas 1"
- DS18B20_2: "Dallas 2"
- BMP180_TEMP: "BMP180 Temp"
- BMP180_PRESSURE: "Luftdruck"

## 5. Updates

### 5.1 Code aktualisieren
```bash
# Via Git
git pull

# Manuell
# Neue Dateien kopieren
```

### 5.2 Datenbank aktualisieren
Bei Updates immer prüfen, ob neue SQL-Dateien ausgeführt werden müssen:
```bash
mysql -u root -p iotgateway < database/update_schema.sql
```

## 6. Fehlerbehebung

### 6.1 Berechtigungen
Bei Problemen mit Schreibzugriffen:
```bash
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo chmod -R 755 /var/www/html/iotgateway
```

### 6.2 Datenbank-Verbindung
Verbindung testen:
```bash
mysql -u iotgateway -p iotgateway
```

### 6.3 Logs
Apache-Logs prüfen:
```bash
tail -f /var/log/apache2/error.log
```
