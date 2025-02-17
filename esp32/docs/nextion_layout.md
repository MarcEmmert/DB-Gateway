# Nextion Display Layout

## Display Konfiguration
- Modell: Nextion NX3224T024
- Auflösung: 480x272 Pixel
- Orientierung: Landscape
- Hintergrundfarbe: 65535 (weiß)

## Globale Einstellungen
- Standardschriftart: Arial
- Standardschriftgröße: 32px
- Standardtextfarbe: 0 (schwarz)
- Aktive Farbe: 2016 (grün)
- Inaktive Farbe: 63488 (rot)

## Seite 0: Hauptseite (page0)

### Header Bereich (y: 0-40)
```
// Titel
text title
{
    x: 10
    y: 5
    w: 460
    h: 30
    font: Arial
    fontsize: 24
    text: "IoT Gateway"
    align: center
}
```

### Temperatur & Druck Bereich (y: 45-120)
```
// BMP180 Temperatur
text tTempLabel
{
    x: 10
    y: 45
    w: 120
    h: 25
    font: Arial
    fontsize: 20
    text: "BMP Temp:"
}

text tTemp
{
    x: 135
    y: 45
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    align: left
}

// BMP180 Druck
text tPressureLabel
{
    x: 10
    y: 75
    w: 120
    h: 25
    font: Arial
    fontsize: 20
    text: "Druck:"
}

text tPressure
{
    x: 135
    y: 75
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    align: left
}

// Dallas Temperatur
text tDallasTempLabel
{
    x: 250
    y: 45
    w: 120
    h: 25
    font: Arial
    fontsize: 20
    text: "Dallas:"
}

text tDallasTemp
{
    x: 375
    y: 45
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    align: left
}
```

### Relais Bereich (y: 125-170)
```
// Relais Buttons
button btnRelay1
{
    x: 10
    y: 125
    w: 100
    h: 40
    text: "Relais 1"
}

button btnRelay2
{
    x: 130
    y: 125
    w: 100
    h: 40
    text: "Relais 2"
}

button btnRelay3
{
    x: 250
    y: 125
    w: 100
    h: 40
    text: "Relais 3"
}

button btnRelay4
{
    x: 370
    y: 125
    w: 100
    h: 40
    text: "Relais 4"
}
```

### Kontakte Bereich (y: 175-220)
```
// Kontakt Indikatoren
text indContact1
{
    x: 10
    y: 175
    w: 100
    h: 40
    text: "Kontakt 1"
}

text indContact2
{
    x: 130
    y: 175
    w: 100
    h: 40
    text: "Kontakt 2"
}

text indContact3
{
    x: 250
    y: 175
    w: 100
    h: 40
    text: "Kontakt 3"
}

text indContact4
{
    x: 370
    y: 175
    w: 100
    h: 40
    text: "Kontakt 4"
}
```

### Status Bereich (y: 225-272)
```
// Status und Verbindungstyp
text tStatus
{
    x: 10
    y: 225
    w: 150
    h: 40
    font: Arial
    fontsize: 20
    align: center
}

text tConnType
{
    x: 170
    y: 225
    w: 100
    h: 40
    font: Arial
    fontsize: 20
    align: center
}

button btnConfig
{
    x: 370
    y: 225
    w: 100
    h: 40
    text: "Konfig"
    bco: 31 // Blauer Hintergrund
    pco: 65535 // Weißer Text
}
```

## Seite 1: Konfiguration (page1)

### Header Bereich
```
// Titel
text title1
{
    x: 10
    y: 5
    w: 460
    h: 30
    font: Arial
    fontsize: 24
    text: "Konfiguration"
    align: center
}
```

### WLAN Einstellungen (y: 40-120)
```
// WLAN SSID
text tSSIDLabel
{
    x: 10
    y: 40
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    text: "WLAN:"
}

text tSSID
{
    x: 120
    y: 40
    w: 250
    h: 25
    font: Arial
    fontsize: 20
    align: left
}

// WLAN Passwort
text tPassLabel
{
    x: 10
    y: 75
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    text: "Passwort:"
}

text tPass
{
    x: 120
    y: 75
    w: 250
    h: 25
    font: Arial
    fontsize: 20
    align: left
}
```

### Server Einstellungen (y: 120-170)
```
// Server URL
text tURLLabel
{
    x: 10
    y: 120
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    text: "Server:"
}

text tURL
{
    x: 120
    y: 120
    w: 350
    h: 25
    font: Arial
    fontsize: 20
    align: left
}
```

### GSM Einstellungen (y: 170-220)
```
// APN
text tAPNLabel
{
    x: 10
    y: 170
    w: 100
    h: 25
    font: Arial
    fontsize: 20
    text: "APN:"
}

text tAPN
{
    x: 120
    y: 170
    w: 250
    h: 25
    font: Arial
    fontsize: 20
    align: left
}
```

### Steuerung (y: 225-272)
```
// Verbindungstyp umschalten
button btnToggleConn
{
    x: 10
    y: 225
    w: 140
    h: 40
    text: "WLAN/GSM"
    bco: 33840 // Grauer Hintergrund
    pco: 65535 // Weißer Text
}

// Speichern Button
button btnSave
{
    x: 160
    y: 225
    w: 140
    h: 40
    text: "Speichern"
    bco: 2016 // Grüner Hintergrund
    pco: 65535 // Weißer Text
}

// Zurück Button
button btnBack
{
    x: 310
    y: 225
    w: 140
    h: 40
    text: "Zurück"
    bco: 63488 // Roter Hintergrund
    pco: 65535 // Weißer Text
}
```

## Touch Events und Kommunikation

### Hauptseite (page0) Events
```
// Relais Steuerung
touch btnRelay1
{
    event release
    {
        printh 65 01 00 01      // Relais 1, Component ID 1
    }
}

touch btnRelay2
{
    event release
    {
        printh 65 02 00 02      // Relais 2, Component ID 2
    }
}

touch btnRelay3
{
    event release
    {
        printh 65 03 00 03      // Relais 3, Component ID 3
    }
}

touch btnRelay4
{
    event release
    {
        printh 65 04 00 04      // Relais 4, Component ID 4
    }
}

// Konfigurationsseite öffnen
touch btnConfig
{
    event release
    {
        page page1        // Wechsel zur Konfigurationsseite
    }
}
```

### Konfigurationsseite (page1) Events
```
// WLAN SSID Eingabe
touch tSSID
{
    event release
    {
        // Öffne Tastatur für SSID Eingabe
        vis kbInput,1     // Tastatur anzeigen
        kbInput.type=0    // Normaler Text
    }
}

// WLAN Passwort Eingabe
touch tPass
{
    event release
    {
        // Öffne Tastatur für Passwort Eingabe
        vis kbInput,1     // Tastatur anzeigen
        kbInput.type=1    // Passwort (versteckt)
    }
}

// Server URL Eingabe
touch tURL
{
    event release
    {
        // Öffne Tastatur für Server URL
        vis kbInput,1     // Tastatur anzeigen
        kbInput.type=0    // Normaler Text
    }
}

// Verbindungstyp umschalten
touch btnToggleConn
{
    event release
    {
        // Wechsel zwischen WLAN und GSM
        printh 66 00      // Toggle Verbindungstyp
    }
}

// Konfiguration speichern
touch btnSave
{
    event release
    {
        // Konfiguration speichern und neu verbinden
        printh 67 00      // Speichern und Neustart
        page page0        // Zurück zur Hauptseite
    }
}

// Zurück zur Hauptseite
touch btnBack
{
    event release
    {
        page page0        // Ohne Speichern zurück
    }
}
```

### ESP32 Kommunikationsprotokolle

1. **Relais Steuerung (0x65)**
   - Byte 1: 0x65 (Kommando für Relais)
   - Byte 2: Relais Nummer (0x01-0x04)
   - Byte 3: Status (0x00 = Toggle)
   - Byte 4: Component ID (0x01-0x04)

2. **Verbindungstyp (0x66)**
   - Byte 1: 0x66 (Kommando für Verbindungswechsel)
   - Byte 2: 0x00 (Toggle zwischen WLAN/GSM)
   
3. **Konfiguration (0x67)**
   - Byte 1: 0x67 (Kommando für Konfiguration)
   - Byte 2: 0x00 (Speichern und Neustart)

### Hinweise zur Implementation

1. **APN-Konfiguration**
   - Die APN wird automatisch vom Modem ermittelt
   - Keine manuelle Eingabe erforderlich
   - Das GSM-Modul erkennt die SIM-Karte und wählt die passende APN

2. **Tastatur-Handling**
   - Nach Eingabeabschluss Tastatur ausblenden: `vis kbInput,0`
   - Eingabe in entsprechendes Textfeld übernehmen
   - Bei Passwort-Eingabe Zeichen maskieren

3. **Status-Updates**
   - ESP32 sendet regelmäßig Updates an das Display
   - Format: `printh [Kommando] [Wert1] [Wert2]`
   - Beispiel Temperatur: `printh 68 23 50` (23.5°C)

## Tastatur Konfiguration
```
keyboard kbInput
{
    x: 40
    y: 40
    w: 400
    h: 180
    bco: 33840 // Grauer Hintergrund
    pco: 0 // Schwarzer Text
}
```

## Farbdefinitionen
COLOR_BLACK = 0          // Schwarz
COLOR_WHITE = 65535      // Weiß
COLOR_RED = 63488        // Rot
COLOR_GREEN = 2016       // Grün
COLOR_BLUE = 31         // Blau
COLOR_GRAY = 33840      // Grau
