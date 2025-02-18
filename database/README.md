# Datenbank-Dokumentation

## Tabellen-Übersicht

### 1. Kern-Tabellen

#### devices
- Speichert Grundinformationen zu Geräten
- Felder: id, name, description, user_id, mqtt_topic, created_at

#### users
- Benutzer-Verwaltung
- Felder: id, username, password (SHA2), is_admin, created_at

#### sensor_data
- Speichert Sensormesswerte
- Felder: id, device_id, sensor_type, value, timestamp

### 2. Konfigurationstabellen

#### sensor_config
- Benutzerdefinierte Namen für Sensoren
- Felder: id, device_id, sensor_type, display_name
- Unterstützte sensor_types:
  * DS18B20_1 (Standard: "Dallas 1")
  * DS18B20_2 (Standard: "Dallas 2")
  * BMP180_TEMP (Standard: "BMP180 Temp")
  * BMP180_PRESSURE (Standard: "Luftdruck")

#### relay_config
- Benutzerdefinierte Namen für Relais
- Felder: id, device_id, relay_number, display_name
- Standard: "Relais X" (X = Nummer)

#### contact_config
- Benutzerdefinierte Namen für Status-Kontakte
- Felder: id, device_id, contact_number, display_name
- Standard: "Kontakt X" (X = Nummer)

## SQL-Dateien

### create_schema.sql
- Erstellt die Basis-Tabellen
- Muss als erstes ausgeführt werden

### create_config_tables.sql
- Erstellt die Konfigurations-Tabellen
- Nach create_schema.sql ausführen

### update_schema.sql
- Enthält Datenbankaktualisierungen
- Bei Updates prüfen und ausführen

## Datenbank-Updates

### Version 1.0 zu 1.1
- Neue Konfigurationstabellen
- Verbesserte Sensorbeschreibungen
- Einheitliche Namensgebung

### Version 1.1 zu 1.2
- Sensor-Typen standardisiert
- Einheiten festgelegt (°C, hPa)
- Verbesserte Datenbankstruktur

## Backup & Restore

### Backup erstellen
```bash
mysqldump -u root -p iotgateway > backup.sql
```

### Backup einspielen
```bash
mysql -u root -p iotgateway < backup.sql
```

## Wichtige Hinweise

### Sensordaten
- Timestamps in UTC speichern
- Werte als FLOAT mit 2 Dezimalstellen
- Regelmäßige Bereinigung alter Daten empfohlen

### Konfiguration
- Benutzerdefinierte Namen haben Vorrang
- Standard-Namen als Fallback
- UTF-8 für Sonderzeichen unterstützt

### Performance
- Indizes auf timestamp und device_id
- Partitionierung für große Datenmengen empfohlen
- Regelmäßige Wartung der Tabellen
