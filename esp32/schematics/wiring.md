# IoT Gateway Schaltplan

## Stromversorgung
- ESP32 VIN: 5V DC
- ESP32 GND: GND

## I2C Bus (BMP180)
- ESP32 GPIO33 (SDA) → BMP180 SDA
- ESP32 GPIO32 (SCL) → BMP180 SCL
- BMP180 VIN → 3.3V
- BMP180 GND → GND

## OneWire (Dallas DS18B20)
- ESP32 GPIO25 → DS18B20 DATA
- DS18B20 VDD → 3.3V
- DS18B20 GND → GND
- 4.7kΩ Pull-up Widerstand zwischen DATA und 3.3V

## Relais Module (4 Kanäle)
- ESP32 GPIO26 → IN1
- ESP32 GPIO27 → IN2
- ESP32 GPIO14 → IN3
- ESP32 GPIO12 → IN4
- Relais VCC → 5V
- Relais GND → GND

## Kontakteingänge
- ESP32 GPIO34 → Kontakt 1 (mit Pull-down 10kΩ)
- ESP32 GPIO35 → Kontakt 2 (mit Pull-down 10kΩ)
- ESP32 GPIO36 → Kontakt 3 (mit Pull-down 10kΩ)
- ESP32 GPIO39 → Kontakt 4 (mit Pull-down 10kΩ)

## A7670E GSM Modul
- ESP32 GPIO14 (TX) → A7670E RX
- ESP32 GPIO13 (RX) → A7670E TX
- A7670E VCC → 4V DC (separates Netzteil empfohlen)
- A7670E GND → GND

## Nextion Display
- ESP32 GPIO16 (RX2) → Nextion TX
- ESP32 GPIO17 (TX2) → Nextion RX
- Nextion VCC → 5V
- Nextion GND → GND

## Wichtige Hinweise

### Stromversorgung
1. Separate 5V Stromversorgung für Relais-Modul empfohlen
2. Separate 4V Stromversorgung für GSM Modul (min. 2A)
3. Hauptstromversorgung 5V/2A für ESP32 und Display

### Pull-up/Pull-down Widerstände
1. 4.7kΩ Pull-up für DS18B20
2. 10kΩ Pull-down für alle Kontakteingänge
3. I2C Pull-ups (4.7kΩ) sind meist im BMP180 Modul integriert

### Schutzmaßnahmen
1. Entstörkondensatoren (100nF) an allen Spannungsversorgungen
2. TVS-Dioden an Kontakteingängen empfohlen
3. Optokoppler für Kontakteingänge bei industrieller Umgebung

## Empfohlene Komponenten

### Widerstände
- 4x 10kΩ (Kontakt Pull-downs)
- 1x 4.7kΩ (OneWire Pull-up)

### Kondensatoren
- 6x 100nF (Entstörung)
- 2x 470µF/16V (Pufferung GSM)

### Schutzkomponenten
- 4x TVS-Dioden für Kontakteingänge
- Optional: 4x Optokoppler für Kontakteingänge

### Steckverbinder
- 4x 2-polige Schraubklemmen (Kontakte)
- 4x 3-polige Schraubklemmen (Relais)
- 1x DC-Buchse für Hauptstromversorgung
- 1x DC-Buchse für GSM-Stromversorgung

## Leiterplattendesign-Empfehlungen

### Zonenaufteilung
1. Digitalbereich (ESP32, Sensoren)
2. Stromversorgung
3. Relais (potentialgetrennt)
4. GSM (separate Masse)

### EMV-Maßnahmen
1. Massefläche unter GSM-Modul
2. Entstörkondensatoren nah an ICs
3. Relais räumlich getrennt von Sensoren
4. Separate Masseführung für GSM
