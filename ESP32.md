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
const int RELAY_1_PIN = 26;     // Relais 1
const int RELAY_2_PIN = 27;     // Relais 2
const int RELAY_3_PIN = 2;      // Relais 3
const int RELAY_4_PIN = 12;     // Relais 4
const int STATUS_1_PIN = 34;    // Status-Kontakt 1
const int STATUS_2_PIN = 35;    // Status-Kontakt 2
const int STATUS_3_PIN = 36;    // Status-Kontakt 3
const int STATUS_4_PIN = 39;    // Status-Kontakt 4

// MQTT Topics
String base_topic;              // wird mit MAC-Adresse erstellt
String status_topic;
String relay_topic;
String temperature_topic;

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
  base_topic = "device/" + mac;
  status_topic = base_topic + "/status";
  relay_topic = base_topic + "/relay";
  temperature_topic = base_topic + "/temperature";
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
    else if (String(topic) == relay_topic + "/3") {
      digitalWrite(RELAY_3_PIN, doc["state"] ? HIGH : LOW);
    }
    else if (String(topic) == relay_topic + "/4") {
      digitalWrite(RELAY_4_PIN, doc["state"] ? HIGH : LOW);
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
  pinMode(RELAY_3_PIN, OUTPUT);
  pinMode(RELAY_4_PIN, OUTPUT);
  pinMode(STATUS_1_PIN, INPUT_PULLUP);
  pinMode(STATUS_2_PIN, INPUT_PULLUP);
  pinMode(STATUS_3_PIN, INPUT_PULLUP);
  pinMode(STATUS_4_PIN, INPUT_PULLUP);
  
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
  if (now - lastMsg > 60000) {  // Alle 60 Sekunden
    lastMsg = now;
    
    // Status-Kontakte lesen und senden
    StaticJsonDocument<200> status_doc;
    status_doc["data"]["relay1"] = digitalRead(RELAY_1_PIN);
    status_doc["data"]["relay2"] = digitalRead(RELAY_2_PIN);
    status_doc["data"]["relay3"] = digitalRead(RELAY_3_PIN);
    status_doc["data"]["relay4"] = digitalRead(RELAY_4_PIN);
    status_doc["data"]["contact1"] = !digitalRead(STATUS_1_PIN);
    status_doc["data"]["contact2"] = !digitalRead(STATUS_2_PIN);
    status_doc["data"]["contact3"] = !digitalRead(STATUS_3_PIN);
    status_doc["data"]["contact4"] = !digitalRead(STATUS_4_PIN);
    
    String status_output;
    serializeJson(status_doc, status_output);
    client.publish(status_topic.c_str(), status_output.c_str());
    
    // Temperatur lesen und senden
    StaticJsonDocument<200> temperature_doc;
    temperature_doc["data"]["DS18B20_1"] = 22.5;  // Beispiel-Wert
    temperature_doc["data"]["DS18B20_2"] = 23.1;  // Beispiel-Wert
    temperature_doc["data"]["BMP180_TEMP"] = 22.8;  // Beispiel-Wert
    temperature_doc["data"]["BMP180_PRESSURE"] = 1013.25;  // Beispiel-Wert
    
    String temperature_output;
    serializeJson(temperature_doc, temperature_output);
    client.publish(temperature_topic.c_str(), temperature_output.c_str());
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

- `device/[MAC]/status` - Status der Kontakte
- `device/[MAC]/relay/1` - Relais 1 steuern
- `device/[MAC]/relay/2` - Relais 2 steuern
- `device/[MAC]/relay/3` - Relais 3 steuern
- `device/[MAC]/relay/4` - Relais 4 steuern
- `device/[MAC]/temperature` - Temperaturwerte

## Fehlerbehebung

### Häufige Probleme

1. Keine MQTT-Verbindung:
   - WLAN-Verbindung prüfen
   - MQTT-Broker-Adresse prüfen
   - Netzwerk-Firewall prüfen

2. Falsche Sensor-Werte:
   - Verkabelung prüfen
   - Pullup-Widerstände prüfen
   - I2C-Adresse prüfen

3. Kontakte zeigen falschen Status:
   - INPUT_PULLUP aktiviert?
   - Verkabelung auf Wackelkontakte prüfen
   - Entprellzeit anpassen wenn nötig
