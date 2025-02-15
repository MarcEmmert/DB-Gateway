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

## Projektstruktur

/var/www/html/iotgateway/
├── api/                    # API-Endpunkte für Mobile App
├── assets/                 # CSS, JavaScript, Bilder
├── database/              # Datenbank-Schema und Migrations
├── includes/              # PHP-Klassen und Funktionen
├── logs/                  # Log-Dateien
├── templates/             # HTML-Templates
└── vendor/                # Composer-Abhängigkeiten

## Installation

Siehe [INSTALL.md](INSTALL.md) für detaillierte Installationsanweisungen.

## ESP32-Integration

Siehe [ESP32.md](ESP32.md) für die ESP32-Firmware und Einrichtung.

## Mobile App

Die mobile App ist als separates Repository verfügbar.

## Lizenz

MIT License
