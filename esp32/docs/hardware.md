 # IoT Gateway Hardware Dokumentation

## Komponenten

### Mikrocontroller
- ESP32 WROOM32

### Sensoren
- BMP180 (Temperatur und Luftdruck)
- Dallas DS18B20 (Temperatur)

### Aktoren
- 4x Relais
- 4x Kontakteingänge
- A7670E GSM Modul
- Nextion Display

## Pin-Belegung

### I2C
- SDA: GPIO33
- SCL: GPIO32

### OneWire (Dallas)
- Data: GPIO25

### Relais
- Relais 1: GPIO26
- Relais 2: GPIO27
- Relais 3: GPIO14
- Relais 4: GPIO12

### Kontakte
- Kontakt 1: GPIO34
- Kontakt 2: GPIO35
- Kontakt 3: GPIO36
- Kontakt 4: GPIO39

### GSM Modul
- TX: GPIO14
- RX: GPIO13

### Nextion Display
- RX: GPIO16
- TX: GPIO17

## Bibliotheken
- WiFi.h
- HTTPClient.h
- Wire.h
- Adafruit_BMP085.h
- OneWire.h
- DallasTemperature.h
- ArduinoJson.h
- Nextion.h
- TinyGSM.h

## Installation

1. Installiere die Arduino IDE
2. Installiere ESP32 Board Support
3. Installiere alle benötigten Bibliotheken über den Library Manager
4. Wähle das richtige Board (ESP32 Dev Module)
5. Kompiliere und flashe den Code

## Konfiguration

Die Konfiguration erfolgt über das Nextion Display:

### Hauptseite
- Temperatur (BMP180)
- Luftdruck
- Dallas Temperatur
- Relais Status und Steuerung
- Kontakt Status
- Verbindungsstatus

### Konfigurationsseite
- WLAN/GSM Umschaltung
- WLAN Einstellungen (SSID, Passwort)
- Server URL
- GSM Einstellungen (APN, User, Pass)

## Verbindung

Das Gerät kann über zwei Wege kommunizieren:
1. WLAN
2. GSM (Mobilfunk)

Die Umschaltung erfolgt über das Display. Alle Einstellungen werden im EEPROM gespeichert.
