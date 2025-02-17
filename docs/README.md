# IoT Gateway Dokumentation

## Übersicht
Willkommen zur Dokumentation des IoT Gateways. Dieses System ermöglicht die Verwaltung und Überwachung von IoT-Geräten über eine zentrale Weboberfläche.

## Dokumentationsstruktur

1. [Installation](installation.md)
   - Systemvoraussetzungen
   - Schritt-für-Schritt Installationsanleitung
   - Datenbank-Setup
   - Apache-Konfiguration

2. [Sensoren & Geräte](sensors.md)
   - Hardware-Anforderungen
   - ESP32 Programmierung
   - Verkabelungsanleitungen
   - Beispiel-Code

3. [MQTT-Konfiguration](mqtt.md)
   - Broker-Setup
   - Sicherheitseinstellungen
   - Topic-Struktur
   - Beispiel-Konfigurationen

4. [Benutzerhandbuch](user_manual.md)
   - Erste Schritte
   - Benutzer-Management
   - Geräte-Verwaltung
   - Dashboard-Nutzung

5. [Fehlerbehebung](troubleshooting.md)
   - Bekannte Probleme
   - Debugging-Tipps
   - Logfile-Analyse
   - FAQ

6. [API-Dokumentation](api.md)
   - REST-API Endpunkte
   - MQTT-API
   - Authentifizierung
   - Beispiele

## Server-Konfiguration

### Backup-System
Das System führt automatische tägliche Backups durch:
- Zeitpunkt: Täglich um 2 Uhr nachts
- Backup-Inhalt: Datenbank und Dateisystem
- Speicherort: Nextcloud (IoTGateway-Backups-Test)
- Aufbewahrung: 7 Tage

### Server-spezifische Dateien
Folgende Dateien sind server-spezifisch und werden nicht im Git-Repository gespeichert:
- `config.php` - Lokale Konfiguration
- `local-config/` - Server-spezifische Anpassungen
- Verschiedene PHP-Dateien für Benutzer- und Geräteverwaltung

## Quick Start

1. Folgen Sie der [Installationsanleitung](installation.md)
2. Konfigurieren Sie den [MQTT-Broker](mqtt.md)
3. Richten Sie Ihr erstes [IoT-Gerät](sensors.md) ein
4. Melden Sie sich am Web-Interface an
5. Fügen Sie Ihr Gerät hinzu und überwachen Sie die Daten

## Support

Bei Fragen oder Problemen:
1. Prüfen Sie die [Fehlerbehebung](troubleshooting.md)
2. Durchsuchen Sie die [FAQ](troubleshooting.md#faq)
3. Öffnen Sie ein Issue auf GitHub

## Lizenz
MIT License - Siehe [LICENSE](../LICENSE) für Details
