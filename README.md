# IoT Gateway

Ein PHP-basiertes Gateway für IoT-Geräte mit MQTT-Integration.

## Funktionen

- Benutzer-Verwaltung mit Login-System
- Mehrere ESP32-Clients pro Benutzer
- Temperatur-Aufzeichnung und Visualisierung
- Relais-Steuerung
- Status-Kontakt-Überwachung
- Mobile App-Unterstützung via API
- MQTT-Integration

## Schnellstart

```bash
# Repository klonen
cd /var/www/html
sudo git clone https://github.com/MarcEmmert/DB-Gateway.git iotgateway

# Konfiguration erstellen
cd iotgateway
sudo cp config.example.php config.php
sudo nano config.php

# Berechtigungen setzen
sudo chown -R www-data:www-data .
```

Detaillierte Installationsanweisungen finden Sie in der [INSTALL.md](INSTALL.md).

## Projektstruktur

/var/www/html/iotgateway/
├── api/                    # API-Endpunkte für Mobile App
├── assets/                 # CSS, JavaScript, Bilder
├── database/              # Datenbank-Schema und Migrations
├── includes/              # PHP-Klassen und Funktionen
├── logs/                  # Log-Dateien
├── templates/             # HTML-Templates
└── vendor/                # Composer-Abhängigkeiten

## ESP32-Integration

Die ESP32-Firmware und Einrichtungsanleitung finden Sie in der [ESP32.md](ESP32.md).

## Systemanforderungen

- Ubuntu Server 24.04 LTS
- PHP 8.3 oder höher
- MariaDB 10.6 oder höher
- Apache2
- Mosquitto MQTT Broker

## Entwicklung

1. Repository klonen:
```bash
git clone https://github.com/MarcEmmert/DB-Gateway.git
cd DB-Gateway
```

2. Entwicklungsumgebung einrichten:
```bash
cp config.example.php config.php
# config.php anpassen
```

## Updates

```bash
cd /var/www/html/iotgateway
sudo git pull
sudo chown -R www-data:www-data .
```

## Lizenz

MIT License
