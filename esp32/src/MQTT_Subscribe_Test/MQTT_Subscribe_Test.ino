#include <WiFi.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Adafruit_BMP280.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>

// Debug Serial
#define SerialMon Serial

// Pin-Definitionen
#define DALLAS_PIN 25
#define I2C_SDA 33
#define I2C_SCL 32
#define RELAY1_PIN 26  // GPIO26
#define RELAY2_PIN 27  // GPIO27
#define RELAY3_PIN 2   // GPIO2
#define RELAY4_PIN 12  // GPIO12
#define CONTACT1_PIN 34  // GPIO34 (INPUT)
#define CONTACT2_PIN 35  // GPIO35 (INPUT)
#define CONTACT3_PIN 36  // GPIO36 (INPUT)
#define CONTACT4_PIN 39  // GPIO39 (INPUT)

// WiFi Konfiguration
const char* ssid = "CSI2G";
const char* password = "tmz18bla";

// MQTT Konfiguration
const char* mqttServer = "rdp.emmert.biz";
const int mqttPort = 1883;
const char* mqttUsername = "mqtt_user";
const char* mqttPassword = "01937736e";
const char* mqttClientId = "ESP32_A7670E";
const char* mqttDeviceId = "67b63977e4fbf";
const char* mqttTopicStatus = "device/67b63977e4fbf/status";
const char* mqttTopicTemperature = "device/67b63977e4fbf/temperature";
const char* mqttTopicRelay = "device/67b63977e4fbf/relay";

// Sensor Objekte
Adafruit_BMP280 bmp;
OneWire oneWire(DALLAS_PIN);
DallasTemperature dallas(&oneWire);
WiFiClient espClient;
PubSubClient client(espClient);

// Sensor Status
bool bmp_ready = false;
bool dallas_ready = false;
float bmpTemp = 0;
float bmpPressure = 0;
float dallas1Temp = DEVICE_DISCONNECTED_C;
float dallas2Temp = DEVICE_DISCONNECTED_C;

// Relais und Kontakt Status
bool relayStates[4] = {false, false, false, false};
int contactStates[4] = {HIGH, HIGH, HIGH, HIGH};  // HIGH = offen (1), LOW = geschlossen (0)

void setup() {
    SerialMon.begin(115200);
    SerialMon.println("ESP32 MQTT Client");
    
    // Kontakt-Pins als Input mit Pull-up
    pinMode(CONTACT1_PIN, INPUT_PULLUP);
    pinMode(CONTACT2_PIN, INPUT_PULLUP);
    pinMode(CONTACT3_PIN, INPUT_PULLUP);
    pinMode(CONTACT4_PIN, INPUT_PULLUP);
    
    // Relay-Pins als Output
    pinMode(RELAY1_PIN, OUTPUT);
    pinMode(RELAY2_PIN, OUTPUT);
    pinMode(RELAY3_PIN, OUTPUT);
    pinMode(RELAY4_PIN, OUTPUT);
    
    // Initialize relays to OFF
    digitalWrite(RELAY1_PIN, LOW);
    digitalWrite(RELAY2_PIN, LOW);
    digitalWrite(RELAY3_PIN, LOW);
    digitalWrite(RELAY4_PIN, LOW);
    
    // Initial Kontakt-Status lesen
    contactStates[0] = digitalRead(CONTACT1_PIN);  // HIGH (1) = offen, LOW (0) = geschlossen
    contactStates[1] = digitalRead(CONTACT2_PIN);
    contactStates[2] = digitalRead(CONTACT3_PIN);
    contactStates[3] = digitalRead(CONTACT4_PIN);
    
    // Debug Ausgabe
    SerialMon.println("\n=== Initial Pin Setup ===");
    SerialMon.println("Kontakt Status (HIGH = offen, LOW = geschlossen):");
    for (int i = 0; i < 4; i++) {
        SerialMon.print("Kontakt "); SerialMon.print(i+1);
        SerialMon.print(": "); SerialMon.print(contactStates[i]);
        SerialMon.println(contactStates[i] == HIGH ? " (Offen)" : " (Geschlossen)");
    }
    
    // Connect to WiFi
    SerialMon.print("Connecting to ");
    SerialMon.println(ssid);
    WiFi.begin(ssid, password);
    
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        SerialMon.print(".");
    }
    SerialMon.println("WiFi connected");
    SerialMon.println("IP address: ");
    SerialMon.println(WiFi.localIP());
    
    // MQTT Setup
    client.setServer(mqttServer, mqttPort);
    client.setCallback(mqttCallback);
    
    // I2C und Sensoren initialisieren
    Wire.begin(I2C_SDA, I2C_SCL);
    delay(100);
    
    // BMP280 initialisieren
    if (!bmp.begin(0x76)) {
        SerialMon.println("BMP280 nicht gefunden! Prüfe Verkabelung.");
        bmp_ready = false;
    } else {
        bmp.setSampling(Adafruit_BMP280::MODE_NORMAL,
                       Adafruit_BMP280::SAMPLING_X2,
                       Adafruit_BMP280::SAMPLING_X16,
                       Adafruit_BMP280::FILTER_X16,
                       Adafruit_BMP280::STANDBY_MS_500);
        bmp_ready = true;
        SerialMon.println("BMP280 bereit");
    }
    
    // Dallas initialisieren
    dallas.begin();
    delay(100);
    int deviceCount = dallas.getDeviceCount();
    SerialMon.print("Dallas Sensoren gefunden: ");
    SerialMon.println(deviceCount);
    
    if (deviceCount > 0) {
        dallas_ready = true;
        dallas.requestTemperatures();
        delay(100);
        
        // Adressen ausgeben
        DeviceAddress tempDeviceAddress;
        for (int i = 0; i < deviceCount; i++) {
            if (dallas.getAddress(tempDeviceAddress, i)) {
                SerialMon.print("Sensor ");
                SerialMon.print(i);
                SerialMon.print(" Adresse: ");
                for (uint8_t j = 0; j < 8; j++) {
                    if (tempDeviceAddress[j] < 16) SerialMon.print("0");
                    SerialMon.print(tempDeviceAddress[j], HEX);
                }
                SerialMon.println();
            }
        }
    }
    
    SerialMon.println("Setup abgeschlossen");
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
    String message;
    for (int i = 0; i < length; i++) {
        message += (char)payload[i];
    }
    
    if (String(topic) == mqttTopicRelay) {
        StaticJsonDocument<200> doc;
        DeserializationError error = deserializeJson(doc, message);
        
        if (!error) {
            int relay_id = doc["relay_id"].as<int>();
            bool state = doc["state"].as<int>() == 1;
            
            // Relay IDs 13-16 zu Array Index 0-3 konvertieren
            if (relay_id >= 13 && relay_id <= 16) {
                int relay_index = relay_id - 13;
                relayStates[relay_index] = state;
                updateRelays();
                publishRelayStatus();
                
                SerialMon.print("Relay ");
                SerialMon.print(relay_id);
                SerialMon.print(" -> ");
                SerialMon.println(state ? "ON" : "OFF");
            }
        }
    }
}

void connectMQTT() {
    client.setServer(mqttServer, mqttPort);
    client.setCallback(mqttCallback);
    client.setKeepAlive(120);  // Keepalive auf 120 Sekunden setzen
    
    int retries = 0;
    while (!client.connected() && retries < 3) {
        SerialMon.println("Verbinde mit MQTT...");
        
        if (client.connect(mqttClientId, mqttUsername, mqttPassword, mqttTopicStatus, 1, true, "")) {
            SerialMon.println("MQTT verbunden");
            client.subscribe(mqttTopicRelay);
            
            // Initial Status senden
            publishRelayStatus();
            publishSensorData();
        } else {
            SerialMon.print("Fehler, rc=");
            SerialMon.print(client.state());
            SerialMon.println(" - Neuer Versuch in 5 Sekunden");
            retries++;
            delay(5000);
        }
    }
    
    if (!client.connected()) {
        SerialMon.println("MQTT Verbindung fehlgeschlagen - ESP neu starten");
        ESP.restart();
    }
}

void updateRelays() {
    digitalWrite(RELAY1_PIN, relayStates[0] ? HIGH : LOW);
    digitalWrite(RELAY2_PIN, relayStates[1] ? HIGH : LOW);
    digitalWrite(RELAY3_PIN, relayStates[2] ? HIGH : LOW);
    digitalWrite(RELAY4_PIN, relayStates[3] ? HIGH : LOW);
}

void publishRelayStatus() {
    StaticJsonDocument<256> doc;
    JsonObject data = doc.createNestedObject("data");
    
    // Relay Status
    data["relay1"] = relayStates[0] ? 1 : 0;
    data["relay2"] = relayStates[1] ? 1 : 0;
    data["relay3"] = relayStates[2] ? 1 : 0;
    data["relay4"] = relayStates[3] ? 1 : 0;
    
    // Kontakt Status
    // ESP32: HIGH (1) = offen, LOW (0) = geschlossen
    // MQTT/DB: 0 = offen, 1 = geschlossen
    data["contact1"] = contactStates[0] == HIGH ? 0 : 1;  // HIGH -> 0 (offen), LOW -> 1 (geschlossen)
    data["contact2"] = contactStates[1] == HIGH ? 0 : 1;
    data["contact3"] = contactStates[2] == HIGH ? 0 : 1;
    data["contact4"] = contactStates[3] == HIGH ? 0 : 1;
    
    char jsonBuffer[256];
    serializeJson(doc, jsonBuffer);
    SerialMon.println("\n=== Publishing Status ===");
    SerialMon.print("JSON: "); SerialMon.println(jsonBuffer);
    
    if (client.publish(mqttTopicStatus, jsonBuffer, true)) {
        SerialMon.println("Status erfolgreich publiziert");
        SerialMon.println("Kontakt Status:");
        for (int i = 0; i < 4; i++) {
            SerialMon.print("Kontakt "); SerialMon.print(i+1); 
            SerialMon.print(": ESP32="); SerialMon.print(contactStates[i]);
            SerialMon.print(" ("); SerialMon.print(contactStates[i] == HIGH ? "Offen" : "Geschlossen"); SerialMon.print(")");
            SerialMon.print(" -> MQTT="); SerialMon.print(contactStates[i] == HIGH ? 0 : 1);
            SerialMon.print(" ("); SerialMon.print(contactStates[i] == HIGH ? "Offen" : "Geschlossen"); SerialMon.println(")");
        }
    } else {
        SerialMon.println("Fehler beim Publizieren des Status");
    }
}

void publishSensorData() {
    StaticJsonDocument<256> doc;
    
    // Sensordaten aktualisieren
    if (bmp_ready) {
        bmpTemp = bmp.readTemperature();
        bmpPressure = bmp.readPressure() / 100.0F;
        
        // Nur gültige Werte senden
        if (!isnan(bmpTemp) && bmpTemp != 0) {
            doc["bmp_temp"] = bmpTemp;
        }
        if (!isnan(bmpPressure) && bmpPressure != 0) {
            doc["pressure"] = bmpPressure;
        }
        
        SerialMon.print("BMP280: ");
        SerialMon.print(bmpTemp);
        SerialMon.print("°C, ");
        SerialMon.print(bmpPressure);
        SerialMon.println("hPa");
    }
    
    if (dallas_ready) {
        dallas.requestTemperatures();
        delay(100);  // Kurz warten auf Messung
        
        dallas1Temp = dallas.getTempCByIndex(0);
        dallas2Temp = dallas.getTempCByIndex(1);
        
        if (dallas1Temp != DEVICE_DISCONNECTED_C && dallas1Temp != -127.0) {
            doc["dallas1_temp"] = dallas1Temp;
        }
        if (dallas2Temp != DEVICE_DISCONNECTED_C && dallas2Temp != -127.0) {
            doc["dallas2_temp"] = dallas2Temp;
        }
        
        SerialMon.print("Dallas 1: ");
        SerialMon.print(dallas1Temp);
        SerialMon.print("°C, Dallas 2: ");
        SerialMon.print(dallas2Temp);
        SerialMon.println("°C");
    }
    
    char jsonBuffer[256];
    serializeJson(doc, jsonBuffer);
    SerialMon.print("Sende Temperaturdaten: ");
    SerialMon.println(jsonBuffer);
    
    if (client.publish(mqttTopicTemperature, jsonBuffer)) {
        SerialMon.println("Temperaturdaten erfolgreich publiziert");
    } else {
        SerialMon.println("Fehler beim Publizieren der Temperaturdaten");
    }
}

void checkContacts() {
    bool changed = false;
    static unsigned long lastDebugTime = 0;
    unsigned long currentTime = millis();
    
    // Debug-Ausgabe alle 2 Sekunden
    bool debugOutput = (currentTime - lastDebugTime) >= 2000;
    if (debugOutput) {
        SerialMon.println("\n=== Checking Contacts ===");
        lastDebugTime = currentTime;
    }
    
    // Kontakte prüfen (HIGH/1 = offen, LOW/0 = geschlossen)
    int pins[4] = {CONTACT1_PIN, CONTACT2_PIN, CONTACT3_PIN, CONTACT4_PIN};
    for (int i = 0; i < 4; i++) {
        int rawValue = digitalRead(pins[i]);
        if (debugOutput) {
            SerialMon.print("Kontakt "); SerialMon.print(i+1);
            SerialMon.print(" Raw Value: "); SerialMon.print(rawValue);
            SerialMon.println(rawValue == HIGH ? " (Offen)" : " (Geschlossen)");
        }
        
        if (contactStates[i] != rawValue) {
            contactStates[i] = rawValue;
            changed = true;
            SerialMon.print("Kontakt "); SerialMon.print(i+1);
            SerialMon.print(" Status geändert zu: "); SerialMon.print(rawValue);
            SerialMon.println(rawValue == HIGH ? " (Offen)" : " (Geschlossen)");
        }
    }
    
    // Status senden wenn sich was geändert hat
    if (changed) {
        publishRelayStatus();
    }
}

void loop() {
    static unsigned long lastUpdate = 0;
    static unsigned long lastContactCheck = 0;
    unsigned long now = millis();
    
    // WiFi Check
    if (WiFi.status() != WL_CONNECTED) {
        SerialMon.println("\nWiFi Verbindung verloren. Reconnecting...");
        WiFi.disconnect();
        WiFi.begin(ssid, password);
        
        // Warte bis zu 10 Sekunden auf WiFi
        unsigned long startAttempt = millis();
        while (WiFi.status() != WL_CONNECTED && millis() - startAttempt < 10000) {
            delay(100);
            SerialMon.print(".");
        }
        
        if (WiFi.status() == WL_CONNECTED) {
            SerialMon.println("\nWiFi wieder verbunden");
            SerialMon.print("IP: ");
            SerialMon.println(WiFi.localIP());
        } else {
            SerialMon.println("\nWiFi Reconnect fehlgeschlagen");
            return;  // Try again next loop
        }
    }
    
    // MQTT Check
    if (!client.connected()) {
        SerialMon.println("MQTT nicht verbunden. Reconnecting...");
        if (client.connect(mqttClientId, mqttUsername, mqttPassword)) {
            SerialMon.println("MQTT verbunden");
            client.subscribe(mqttTopicRelay);
            publishRelayStatus();  // Initial status senden
        } else {
            SerialMon.println("MQTT Reconnect fehlgeschlagen");
            return;  // Try again next loop
        }
    }
    client.loop();
    
    // Kontakte alle 100ms prüfen
    if (now - lastContactCheck >= 100) {
        checkContacts();
        lastContactCheck = now;
    }
    
    // Sensordaten alle 30 Sekunden senden
    if (now - lastUpdate >= 30000) {
        publishSensorData();
        lastUpdate = now;
    }
    
    // Kurze Pause
    delay(10);
}
