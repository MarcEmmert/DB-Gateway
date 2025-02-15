# ESP32 Integration

Diese Anleitung beschreibt die Integration von ESP32-WROOM-32 Geräten mit dem IoT Gateway.

## Voraussetzungen

- Arduino IDE
- ESP32 Board Support Package
- Erforderliche Bibliotheken:
  - PubSubClient (MQTT)
  - ArduinoJson
  - WiFiManager

## Installation der Bibliotheken

1. Arduino IDE öffnen
2. Werkzeuge -> Board -> Boardverwalter -> "esp32" suchen und installieren
3. Sketch -> Bibliothek einbinden -> Bibliotheken verwalten:
   - "PubSubClient" installieren
   - "ArduinoJson" installieren
   - "WiFiManager" installieren

## Firmware-Code

```cpp
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <WiFiManager.h>

// MQTT-Konfiguration
const char* mqtt_server = "192.168.0.100";
const int mqtt_port = 1883;
const char* mqtt_user = "mqtt_user";
const char* mqtt_password = "IhrMQTTPasswort";
const char* client_id = "esp32_";  // wird mit MAC-Adresse ergänzt

// Pin-Konfiguration
const int TEMP_PIN = 34;        // Temperatur-Sensor
const int RELAY_1_PIN = 26;     // Relais 1
const int RELAY_2_PIN = 27;     // Relais 2
const int STATUS_1_PIN = 32;    // Status-Kontakt 1
const int STATUS_2_PIN = 33;    // Status-Kontakt 2

// MQTT Topics
String base_topic;              // wird mit MAC-Adresse erstellt
String temp_topic;
String relay_topic;
String status_topic;

// Globale Variablen
WiFiClient espClient;
PubSubClient client(espClient);
unsigned long lastMsg = 0;
char msg[50];

void setup_wifi() {
  WiFiManager wm;
  
  // WiFiManager Parameter
  WiFiManagerParameter custom_mqtt_server("server", "MQTT Server", mqtt_server, 40);
  wm.addParameter(&custom_mqtt_server);
  
  // Automatisches Verbinden mit gespeicherten Credentials
  if(!wm.autoConnect("ESP32-Setup")) {
    Serial.println("Verbindung fehlgeschlagen");
    ESP.restart();
  }
  
  // MAC-Adresse für eindeutige Identifikation
  String mac = WiFi.macAddress();
  mac.replace(":", "");
  base_topic = "esp32/" + mac;
  temp_topic = base_topic + "/temperature";
  relay_topic = base_topic + "/relay";
  status_topic = base_topic + "/status";
}

void callback(char* topic, byte* payload, unsigned int length) {
  String message;
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  
  // JSON parsen
  StaticJsonDocument<200> doc;
  DeserializationError error = deserializeJson(doc, message);
  
  if (!error) {
    // Relais steuern
    if (String(topic) == relay_topic + "/1") {
      digitalWrite(RELAY_1_PIN, doc["state"] ? HIGH : LOW);
    }
    else if (String(topic) == relay_topic + "/2") {
      digitalWrite(RELAY_2_PIN, doc["state"] ? HIGH : LOW);
    }
  }
}

void reconnect() {
  while (!client.connected()) {
    String clientId = client_id + String(random(0xffff), HEX);
    
    if (client.connect(clientId.c_str(), mqtt_user, mqtt_password)) {
      // Subscribe zu Relais-Topics
      client.subscribe((relay_topic + "/+").c_str());
    } else {
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  
  // Pins konfigurieren
  pinMode(RELAY_1_PIN, OUTPUT);
  pinMode(RELAY_2_PIN, OUTPUT);
  pinMode(STATUS_1_PIN, INPUT_PULLUP);
  pinMode(STATUS_2_PIN, INPUT_PULLUP);
  
  setup_wifi();
  
  client.setServer(mqtt_server, mqtt_port);
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
    
    // Temperatur lesen und senden
    float temperature = analogRead(TEMP_PIN) * 0.0805;  // Beispiel-Umrechnung
    
    StaticJsonDocument<200> doc;
    doc["temperature"] = temperature;
    doc["timestamp"] = now;
    
    String output;
    serializeJson(doc, output);
    client.publish(temp_topic.c_str(), output.c_str());
    
    // Status-Kontakte lesen und senden
    StaticJsonDocument<200> status_doc;
    status_doc["status1"] = !digitalRead(STATUS_1_PIN);
    status_doc["status2"] = !digitalRead(STATUS_2_PIN);
    
    String status_output;
    serializeJson(status_doc, status_output);
    client.publish(status_topic.c_str(), status_output.c_str());
  }
}
```

## Konfiguration

1. Arduino IDE öffnen
2. Board "ESP32 Dev Module" auswählen
3. COM-Port auswählen
4. Sketch hochladen

## Erste Einrichtung

1. Nach dem Hochladen startet der ESP32 im AP-Modus
2. Mit dem WLAN "ESP32-Setup" verbinden
3. Im Webportal:
   - WLAN-Zugangsdaten eingeben
   - MQTT-Server-IP eingeben (192.168.0.100)
4. ESP32 startet neu und verbindet sich

## MQTT-Topics

Der ESP32 verwendet folgende Topics:

- `esp32/[MAC]/temperature` - Temperaturwerte
- `esp32/[MAC]/relay/1` - Relais 1 steuern
- `esp32/[MAC]/relay/2` - Relais 2 steuern
- `esp32/[MAC]/status` - Status der Kontakte

## Fehlerbehebung

1. LED-Status:
   - Dauerhaft an: WLAN verbunden
   - Schnelles Blinken: WLAN-Verbindung wird aufgebaut
   - Langsames Blinken: MQTT-Verbindung wird aufgebaut

2. Serielle Ausgabe:
   - Baudrate: 115200
   - Zeigt Verbindungsstatus und Fehler

3. Häufige Probleme:
   - MQTT-Verbindung fehlgeschlagen: Credentials prüfen
   - WLAN-Verbindung instabil: Signalstärke prüfen
   - Sensoren nicht erkannt: Verkabelung prüfen
