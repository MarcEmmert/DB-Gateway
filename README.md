# IoT Temperature Monitoring und Relay Control System

Ein webbasiertes System zur Überwachung von Temperaturdaten und Steuerung von Relais mittels ESP32-Geräten.

## Funktionen

- Temperaturüberwachung von mehreren ESP32-Geräten
- Relais-Fernsteuerung
- Statusüberwachung von Kontakten
- Benutzerverwaltung mit mehreren Clients pro Benutzer
- Responsive Weboberfläche
- REST-API für mobile Anwendungen

## Installation

1. Python 3.8+ installieren
2. Abhängigkeiten installieren:
   ```bash
   pip install -r requirements.txt
   ```
3. MySQL-Datenbank einrichten
4. Umgebungsvariablen in `.env` konfigurieren
5. Server starten:
   ```bash
   python run.py
   ```

## Systemanforderungen

- Ubuntu Server 24.04
- Python 3.8+
- MySQL Server
- ESP32 WROOM32 Geräte
