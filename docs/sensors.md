# IoT Gateway - Sensor-HowTo

## 1. Hardware-Voraussetzungen

### ESP32-Board
- ESP32 Development Board (z.B. ESP32-WROOM-32)
- Micro-USB Kabel für Programmierung
- 5V Stromversorgung

### Sensoren und Aktoren
- DHT22/DHT11 Temperatursensor
- Relais-Module (optional)
- Status-Kontakte (z.B. Reed-Kontakte, optional)
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
- DHT sensor library (von Adafruit)
- Adafruit Unified Sensor (von Adafruit)

## 3. ESP32-Code

```cpp
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

// WiFi-Konfiguration
const char* ssid = "IhrWLAN";
const char* password = "IhrWLANPasswort";

// MQTT-Konfiguration
const char* mqtt_server = "192.168.0.100";  // IP des IoT Gateways
const char* mqtt_user = "iotgateway";
const char* mqtt_password = "IhrMQTTPasswort";
const char* mqtt_topic = "esp32/IhrGeräteToken";  // Aus der Geräteverwaltung

// Sensor-Konfiguration
#define DHTPIN 4        // GPIO-Pin für DHT22
#define DHTTYPE DHT22   // DHT22 (AM2302)
DHT dht(DHTPIN, DHTTYPE);

// Relais-Konfiguration
#define RELAY1_PIN 16   // GPIO-Pin für Relais 1
#define RELAY2_PIN 17   // GPIO-Pin für Relais 2

// Status-Kontakt-Konfiguration
#define CONTACT1_PIN 18 // GPIO-Pin für Kontakt 1
#define CONTACT2_PIN 19 // GPIO-Pin für Kontakt 2

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
  pinMode(CONTACT1_PIN, INPUT_PULLUP);
  pinMode(CONTACT2_PIN, INPUT_PULLUP);
  
  // DHT22 starten
  dht.begin();
  
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
    float temp = dht.readTemperature();
    float hum = dht.readHumidity();
    
    if (!isnan(temp) && !isnan(hum)) {
      doc["temperature"] = temp;
      doc["humidity"] = hum;
    }
    
    // Status-Kontakte lesen
    doc["contact1"] = !digitalRead(CONTACT1_PIN);  // Invertiert für normale Logik
    doc["contact2"] = !digitalRead(CONTACT2_PIN);
    
    // Relais-Status
    doc["relay1"] = digitalRead(RELAY1_PIN);
    doc["relay2"] = digitalRead(RELAY2_PIN);
    
    // JSON serialisieren und senden
    char jsonBuffer[512];
    serializeJson(doc, jsonBuffer);
    client.publish(mqtt_topic, jsonBuffer);
  }
}
```

## 4. Verkabelung

### DHT22 Temperatursensor
- VCC → 3.3V oder 5V
- DATA → GPIO4
- GND → GND

### Relais-Module
- Relais 1:
  - VCC → 5V
  - GND → GND
  - IN → GPIO16
- Relais 2:
  - VCC → 5V
  - GND → GND
  - IN → GPIO17

### Status-Kontakte
- Kontakt 1:
  - Ein Ende → GPIO18
  - Anderes Ende → GND
- Kontakt 2:
  - Ein Ende → GPIO19
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
