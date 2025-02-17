# IoT Gateway - Sensor-HowTo

## 1. Hardware-Voraussetzungen

### ESP32-Board
- ESP32 Development Board (z.B. ESP32-WROOM-32)
- Micro-USB Kabel für Programmierung
- 5V Stromversorgung

### Sensoren und Aktoren
- 2x DS18B20 (Dallas) Temperatursensoren
- BMP180 Druck- und Temperatursensor
- 4x Relais-Module
- 4x Digitale Eingänge für Status-Kontakte
- Verbindungskabel/Jumper-Wires

## 2. Software-Voraussetzungen

### Entwicklungsumgebung
1. Arduino IDE installieren (https://www.arduino.cc/en/software)
2. ESP32 Board Support Package installieren
   - Fügen Sie `https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json` zu den Board-URLs hinzu
   - Installieren Sie "ESP32" über den Boardmanager

### Benötigte Bibliotheken
Installieren Sie über den Arduino Library Manager:
- PubSubClient (von Nick O'Leary)
- ArduinoJson (von Benoit Blanchon)
- OneWire (von Paul Stoffregen)
- DallasTemperature (von Miles Burton)
- Wire (von Arduino)
- Adafruit_BMP085 (von Adafruit)

## 3. ESP32-Code

```cpp
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <Wire.h>
#include <Adafruit_BMP085.h>

// WiFi-Konfiguration
const char* ssid = "IhrWLAN";
const char* password = "IhrWLANPasswort";

// MQTT-Konfiguration
const char* mqtt_server = "192.168.0.100";  // IP des IoT Gateways
const char* mqtt_user = "iotgateway";
const char* mqtt_password = "IhrMQTTPasswort";
const char* mqtt_topic = "esp32/IhrGeräteToken";  // Aus der Geräteverwaltung

// Sensor-Konfiguration
#define ONEWIRE_PIN 4    // DS18B20
#define RELAY1_PIN 26    // Relais 1
#define RELAY2_PIN 27    // Relais 2
#define RELAY3_PIN 32    // Relais 3
#define RELAY4_PIN 33    // Relais 4
#define CONTACT1_PIN 13  // Kontakt 1
#define CONTACT2_PIN 14  // Kontakt 2
#define CONTACT3_PIN 15  // Kontakt 3
#define CONTACT4_PIN 16  // Kontakt 4

// I2C Pins (BMP180)
#define I2C_SDA 21
#define I2C_SCL 22

WiFiClient espClient;
PubSubClient client(espClient);
unsigned long lastMsg = 0;
char msg[50];

void setup_wifi() {
  delay(10);
  Serial.println("Verbinde mit WiFi");
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("WiFi verbunden");
  Serial.println("IP Adresse: ");
  Serial.println(WiFi.localIP());
}

void callback(char* topic, byte* payload, unsigned int length) {
  StaticJsonDocument<200> doc;
  deserializeJson(doc, payload, length);
  
  if (doc.containsKey("relay")) {
    int relay = doc["relay"].as<int>();
    bool state = doc["state"].as<bool>();
    
    if (relay == 1) {
      digitalWrite(RELAY1_PIN, state ? HIGH : LOW);
    } else if (relay == 2) {
      digitalWrite(RELAY2_PIN, state ? HIGH : LOW);
    } else if (relay == 3) {
      digitalWrite(RELAY3_PIN, state ? HIGH : LOW);
    } else if (relay == 4) {
      digitalWrite(RELAY4_PIN, state ? HIGH : LOW);
    }
  }
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Verbinde mit MQTT...");
    if (client.connect("ESP32Client", mqtt_user, mqtt_password)) {
      Serial.println("verbunden");
      client.subscribe(mqtt_topic);
    } else {
      Serial.print("Fehler, rc=");
      Serial.print(client.state());
      Serial.println(" Versuche erneut in 5 Sekunden");
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  
  // GPIO-Pins konfigurieren
  pinMode(RELAY1_PIN, OUTPUT);
  pinMode(RELAY2_PIN, OUTPUT);
  pinMode(RELAY3_PIN, OUTPUT);
  pinMode(RELAY4_PIN, OUTPUT);
  pinMode(CONTACT1_PIN, INPUT_PULLUP);
  pinMode(CONTACT2_PIN, INPUT_PULLUP);
  pinMode(CONTACT3_PIN, INPUT_PULLUP);
  pinMode(CONTACT4_PIN, INPUT_PULLUP);
  
  // I2C initialisieren
  Wire.begin(I2C_SDA, I2C_SCL);
  
  setup_wifi();
  client.setServer(mqtt_server, 1883);
  client.setCallback(callback);
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  unsigned long now = millis();
  if (now - lastMsg > 10000) {  // Alle 10 Sekunden
    lastMsg = now;
    
    // JSON-Dokument erstellen
    StaticJsonDocument<200> doc;
    
    // Temperatur und Luftfeuchtigkeit lesen
    OneWire oneWire(ONEWIRE_PIN);
    DallasTemperature sensors(&oneWire);
    sensors.requestTemperatures();
    float temp1 = sensors.getTempCByIndex(0);
    float temp2 = sensors.getTempCByIndex(1);
    
    // BMP180 lesen
    Adafruit_BMP085 bmp;
    if (!bmp.begin()) {
      Serial.println("BMP180 nicht gefunden!");
    } else {
      float pressure = bmp.readPressure();
      float temp3 = bmp.readTemperature();
      
      doc["pressure"] = pressure;
      doc["temp3"] = temp3;
    }
    
    // Status-Kontakte lesen
    doc["contact1"] = !digitalRead(CONTACT1_PIN);  // Invertiert für normale Logik
    doc["contact2"] = !digitalRead(CONTACT2_PIN);
    doc["contact3"] = !digitalRead(CONTACT3_PIN);
    doc["contact4"] = !digitalRead(CONTACT4_PIN);
    
    // Relais-Status
    doc["relay1"] = digitalRead(RELAY1_PIN);
    doc["relay2"] = digitalRead(RELAY2_PIN);
    doc["relay3"] = digitalRead(RELAY3_PIN);
    doc["relay4"] = digitalRead(RELAY4_PIN);
    
    // JSON serialisieren und senden
    char jsonBuffer[512];
    serializeJson(doc, jsonBuffer);
    client.publish(mqtt_topic, jsonBuffer);
  }
}
```

## 4. Verkabelung

### DS18B20 Temperatursensoren
- VCC → 3.3V
- DATA → GPIO4
- GND → GND
- 4.7kΩ Pull-up zwischen 3.3V und DATA

### BMP180 Druck- und Temperatursensor
- VCC → 3.3V
- GND → GND
- SCL → GPIO22
- SDA → GPIO21

### Relais-Module
- Relais 1:
  - VCC → 5V
  - GND → GND
  - IN → GPIO26
- Relais 2:
  - VCC → 5V
  - GND → GND
  - IN → GPIO27
- Relais 3:
  - VCC → 5V
  - GND → GND
  - IN → GPIO32
- Relais 4:
  - VCC → 5V
  - GND → GND
  - IN → GPIO33

### Status-Kontakte
- Kontakt 1:
  - Ein Ende → GPIO13
  - Anderes Ende → GND
- Kontakt 2:
  - Ein Ende → GPIO14
  - Anderes Ende → GND
- Kontakt 3:
  - Ein Ende → GPIO15
  - Anderes Ende → GND
- Kontakt 4:
  - Ein Ende → GPIO16
  - Anderes Ende → GND

## 5. Konfiguration und Upload

1. Öffnen Sie den Code in der Arduino IDE
2. Ändern Sie die Konfigurationswerte:
   - WLAN-Zugangsdaten
   - MQTT-Server IP
   - MQTT-Zugangsdaten
   - MQTT-Topic (aus der Geräteverwaltung)
3. Wählen Sie das richtige Board im Arduino IDE Menü
4. Verbinden Sie das ESP32-Board via USB
5. Wählen Sie den korrekten COM-Port
6. Klicken Sie auf "Upload"

## 6. Fehlersuche

### WiFi-Verbindungsprobleme
- Überprüfen Sie SSID und Passwort
- Testen Sie die WLAN-Signalstärke
- Prüfen Sie die Serial-Monitor-Ausgabe

### MQTT-Verbindungsprobleme
- Verifizieren Sie die Server-IP
- Prüfen Sie Benutzername und Passwort
- Kontrollieren Sie die Firewall-Einstellungen
- Testen Sie die MQTT-Verbindung mit mosquitto_sub

### Sensor-Probleme
- Überprüfen Sie die Verkabelung
- Testen Sie die Spannungsversorgung
- Prüfen Sie die GPIO-Pins im Code

## 7. Tipps und Tricks

### Energieeffizienz
- Aktivieren Sie den Deep Sleep Mode für batteriebetriebene Geräte
- Reduzieren Sie die Sendefrequenz
- Optimieren Sie die WiFi-Verbindung

### Zuverlässigkeit
- Implementieren Sie Watchdog Timer
- Fügen Sie Fehlerprüfungen hinzu
- Speichern Sie wichtige Daten im EEPROM

### Sicherheit
- Verwenden Sie sichere Passwörter
- Aktivieren Sie SSL/TLS wenn möglich
- Aktualisieren Sie die Firmware regelmäßig
