#include <HardwareSerial.h>
#include <Wire.h>
#include <Adafruit_BMP085.h>  // BMP180 Bibliothek
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>

// Serielle Schnittstellen
HardwareSerial SerialGSM(2);  // UART2 für GSM verwenden
#define SerialMon Serial      // USB Serial für Debug

// Pin-Definitionen
#define GSM_TX 14
#define GSM_RX 13
#define POWER_PIN 4  // Falls ein Power-Pin verwendet wird
#define DALLAS_PIN 25
#define I2C_SDA 33
#define I2C_SCL 32

// Konfiguration
const char* apn = "internet.telekom";  // APN für Telekom
const char* server = "rdp.emmert.biz";
const int port = 1883;

// MQTT Konfiguration
const char* mqttUsername = "mqtt_user";
const char* mqttPassword = "01937736e";
const char* mqttClientId = "ESP32_A7670E";
const char* mqttTopicStatus = "esp32/fd1118d93d957fb7/status";
const char* mqttTopicData = "esp32/fd1118d93d957fb7/data";

// Sensor Objekte
Adafruit_BMP085 bmp;  // BMP180 statt BMP280
OneWire oneWire(DALLAS_PIN);
DallasTemperature dallas(&oneWire);

// Sensor Werte
float bmpTemp, bmpPressure;
float dallas1Temp, dallas2Temp;
unsigned long lastSensorRead = 0;
const unsigned long sensorInterval = 10000;  // 10 Sekunden
float lastBmpTemp = 0, lastBmpPressure = 0;
float lastDallas1Temp = 0, lastDallas2Temp = 0;
const float tempDelta = 0.2;    // 0.2°C Änderung für Update
const float pressDelta = 0.5;   // 0.5hPa Änderung für Update

// Globale Flags
bool gsm_ready = false;
bool gprs_ready = false;
bool mqtt_ready = false;

void setup() {
    // Debug-Monitor
    SerialMon.begin(115200);
    SerialMon.println("\nA7670E Test Start");
    
    // I2C für BMP180
    Wire.begin(I2C_SDA, I2C_SCL);
    if (!bmp.begin()) {  // BMP180 hat keine Adresse nötig
        SerialMon.println("BMP180 nicht gefunden!");
    } else {
        SerialMon.println("BMP180 gefunden");
    }
    
    // Dallas Sensoren
    dallas.begin();
    if (dallas.getDeviceCount() == 0) {
        SerialMon.println("Keine Dallas Sensoren gefunden!");
    } else {
        SerialMon.print("Dallas Sensoren gefunden: ");
        SerialMon.println(dallas.getDeviceCount());
    }
    
    // GSM Serial auf UART2
    SerialGSM.begin(115200, SERIAL_8N1, GSM_RX, GSM_TX);
    
    // Power-Pin konfigurieren falls verwendet
    if (POWER_PIN > 0) {
        pinMode(POWER_PIN, OUTPUT);
        digitalWrite(POWER_PIN, HIGH);
    }
    
    // Warten bis Modul hochgefahren
    delay(3000);
    
    SerialMon.println("=== GSM Initialisierung ===");
    
    // Modul initialisieren
    if (!initGSM()) {
        SerialMon.println("GSM Initialisierung fehlgeschlagen!");
        return;
    }
    
    // GPRS Setup
    if (!setupGPRS()) {
        SerialMon.println("GPRS Setup fehlgeschlagen!");
        return;
    }
    
    // MQTT Setup
    if (!setupMQTT()) {
        SerialMon.println("MQTT Setup fehlgeschlagen!");
        return;
    }
    
    // Erste Sensor-Messung
    readSensors();
}

// AT Befehl senden und auf Antwort warten
bool sendAT(const char* command, const char* expected = "OK", int timeout = 2000) {
    if (strlen(command) > 0) {
        SerialMon.print("Sende: ");
        SerialMon.println(command);
    }
    
    SerialGSM.print(command);
    SerialGSM.print("\r\n");
    
    uint32_t start = millis();
    String response;
    
    while (millis() - start < timeout) {
        if (SerialGSM.available()) {
            char c = SerialGSM.read();
            response += c;
            SerialMon.write(c);  // Debug-Ausgabe
            
            if (response.indexOf(expected) >= 0) {
                return true;
            }
        }
    }
    
    return false;
}

bool initGSM() {
    // Echo ausschalten
    sendAT("ATE0");
    delay(100);
    
    // Modul testen
    if (!sendAT("AT", "OK")) {
        SerialMon.println("Modul antwortet nicht!");
        return false;
    }
    
    // SIM-Status prüfen
    if (!sendAT("AT+CPIN?", "READY")) {
        SerialMon.println("SIM nicht bereit!");
        return false;
    }
    SerialMon.println("SIM bereit");
    
    // Auf Netzwerk registrieren
    if (!sendAT("AT+CREG?", "0,1")) {
        SerialMon.println("Keine Netzwerk-Registrierung!");
        return false;
    }
    SerialMon.println("GSM Netzwerk registriert");
    
    // GPRS Status prüfen
    if (!sendAT("AT+CGREG?", "0,1")) {
        SerialMon.println("Keine GPRS-Registrierung!");
        return false;
    }
    SerialMon.println("GPRS Netzwerk registriert");
    
    // Signalqualität prüfen
    sendAT("AT+CSQ");
    SerialMon.println("Signal OK");
    
    gsm_ready = true;
    SerialMon.println("=== GSM bereit ===\n");
    return true;
}

bool setupGPRS() {
    if (!gsm_ready) return false;
    
    SerialMon.println("=== GPRS Setup ===");
    
    // Alles sauber schließen
    sendAT("AT+NETCLOSE");
    sendAT("AT+CGACT=0,1");
    delay(2000);
    
    // GPRS Kontext konfigurieren
    char atCommand[128];
    snprintf(atCommand, sizeof(atCommand), "AT+CGDCONT=1,\"IP\",\"%s\"", apn);
    if (!sendAT(atCommand)) {
        SerialMon.println("GPRS Kontext-Konfiguration fehlgeschlagen!");
        return false;
    }
    
    // GPRS aktivieren
    if (!sendAT("AT+CGACT=1,1", "OK", 10000)) {
        SerialMon.println("GPRS Aktivierung fehlgeschlagen!");
        return false;
    }
    
    // Netzwerkverbindung öffnen
    if (!sendAT("AT+NETOPEN", "+NETOPEN: 0", 10000)) {
        SerialMon.println("Netzwerkverbindung fehlgeschlagen!");
        return false;
    }
    
    // IP-Adresse anzeigen
    sendAT("AT+IPADDR");
    
    gprs_ready = true;
    SerialMon.println("=== GPRS bereit ===\n");
    return true;
}

bool setupMQTT() {
    if (!gprs_ready) return false;
    
    SerialMon.println("=== MQTT Setup ===");
    
    // Alle MQTT Verbindungen trennen
    sendAT("AT+CMQTTDISC=0,120");
    delay(1000);
    sendAT("AT+CMQTTREL=0");
    delay(1000);
    sendAT("AT+CMQTTSTOP");
    delay(2000);
    
    // MQTT Debug aktivieren
    sendAT("AT+CMQTTDEBUG=1");
    delay(1000);
    
    // MQTT Version auf 3.1.1 setzen
    sendAT("AT+CMQTTCFG=\"version\",0,4");
    delay(1000);
    
    // SSL/TLS deaktivieren
    sendAT("AT+CMQTTCFG=\"ssl\",0,0");
    delay(1000);
    
    // Keep Alive auf 60 Sekunden setzen
    sendAT("AT+CMQTTCFG=\"keepalive\",0,60");
    delay(1000);
    
    // MQTT Verbindung konfigurieren
    SerialMon.println("Starte MQTT...");
    if (!sendAT("AT+CMQTTSTART", "OK", 10000)) {
        SerialMon.println("MQTT Start Fehler");
        return false;
    }
    delay(2000);
    
    // Client ID setzen
    SerialMon.println("Setze Client ID...");
    char atCommand[128];
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTACCQ=0,\"%s\"", mqttClientId);
    if (!sendAT(atCommand, "OK", 5000)) {
        SerialMon.println("MQTT Client ID Fehler");
        return false;
    }
    delay(1000);
    
    // Mit Broker verbinden
    SerialMon.println("Verbinde mit Broker...");
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTCONNECT=0,\"tcp://%s:%d\",60,1,\"%s\",\"%s\"", 
             server, port, mqttUsername, mqttPassword);
    
    SerialMon.print("MQTT Connect Befehl: ");
    SerialMon.println(atCommand);
    
    if (!sendAT(atCommand, "+CMQTTCONNECT: 0,0", 20000)) {
        SerialMon.println("MQTT Connect Fehler - Prüfe Fehlercode:");
        // Warte auf Fehlercode
        delay(1000);
        // Zeige detaillierten Verbindungsstatus
        sendAT("AT+CMQTTCONNECT?");
        return false;
    }
    
    mqtt_ready = true;
    SerialMon.println("=== MQTT bereit ===\n");
    
    // Zeige MQTT Status
    sendAT("AT+CMQTTCONNECT?");
    return true;
}

bool mqttPublish(const char* topic, const char* message) {
    if (!mqtt_ready) return false;
    
    char atCommand[128];
    
    // Topic setzen
    SerialMon.print("Publiziere an Topic: ");
    SerialMon.println(topic);
    
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTTOPIC=0,%d", strlen(topic));
    if (!sendAT(atCommand, ">")) {
        return false;
    }
    
    SerialGSM.print(topic);
    if (!sendAT("", "OK", 5000)) {
        return false;
    }
    
    // Payload setzen
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTPAYLOAD=0,%d", strlen(message));
    if (!sendAT(atCommand, ">")) {
        return false;
    }
    
    SerialGSM.print(message);
    if (!sendAT("", "OK", 5000)) {
        return false;
    }
    
    // Nachricht veröffentlichen
    if (!sendAT("AT+CMQTTPUB=0,1,60", "+CMQTTPUB: 0,0", 5000)) {
        SerialMon.println("MQTT Publish fehlgeschlagen");
        return false;
    }
    
    SerialMon.println("MQTT Publish erfolgreich");
    return true;
}

void readSensors() {
    // BMP180
    bmpTemp = bmp.readTemperature();
    bmpPressure = bmp.readPressure() / 100.0; // hPa
    
    // Dallas Sensoren
    dallas.requestTemperatures();
    dallas1Temp = dallas.getTempCByIndex(0);
    dallas2Temp = dallas.getTempCByIndex(1);
    
    SerialMon.println("\n=== Sensor Werte ===");
    SerialMon.print("BMP180 Temp: "); SerialMon.print(bmpTemp); SerialMon.println("°C");
    SerialMon.print("BMP180 Druck: "); SerialMon.print(bmpPressure); SerialMon.println("hPa");
    SerialMon.print("Dallas1: "); SerialMon.print(dallas1Temp); SerialMon.println("°C");
    SerialMon.print("Dallas2: "); SerialMon.print(dallas2Temp); SerialMon.println("°C");
}

bool shouldUpdateValues() {
    return abs(bmpTemp - lastBmpTemp) >= tempDelta ||
           abs(bmpPressure - lastBmpPressure) >= pressDelta ||
           abs(dallas1Temp - lastDallas1Temp) >= tempDelta ||
           abs(dallas2Temp - lastDallas2Temp) >= tempDelta;
}

void updateLastValues() {
    lastBmpTemp = bmpTemp;
    lastBmpPressure = bmpPressure;
    lastDallas1Temp = dallas1Temp;
    lastDallas2Temp = dallas2Temp;
}

void publishSensorData() {
    if (!mqtt_ready) return;
    
    StaticJsonDocument<200> doc;
    doc["bmp_temp"] = bmpTemp;
    doc["bmp_pressure"] = bmpPressure;
    doc["dallas1_temp"] = dallas1Temp;
    doc["dallas2_temp"] = dallas2Temp;
    
    char jsonBuffer[200];
    serializeJson(doc, jsonBuffer);
    
    mqttPublish(mqttTopicData, jsonBuffer);
}

void loop() {
    static uint32_t lastCheck = 0;
    const uint32_t checkInterval = 30000;  // 30 Sekunden
    
    // Sensor-Messung alle 10 Sekunden
    if (millis() - lastSensorRead >= sensorInterval) {
        readSensors();
        
        // Nur senden wenn sich Werte signifikant geändert haben
        if (shouldUpdateValues()) {
            publishSensorData();
            updateLastValues();
        }
        
        lastSensorRead = millis();
    }
    
    // Periodisch Status prüfen
    if (millis() - lastCheck >= checkInterval) {
        SerialMon.println("\n=== Status Check ===");
        
        // GSM Status
        sendAT("AT+CSQ");          // Signalstärke
        sendAT("AT+CREG?");        // Netzwerkregistrierung
        sendAT("AT+CGACT?");       // GPRS Status
        sendAT("AT+IPADDR");       // IP-Adresse
        
        lastCheck = millis();
    }
    
    // Auf serielle Eingaben reagieren
    if (SerialMon.available()) {
        String command = SerialMon.readStringUntil('\n');
        command.trim();
        
        if (command.length() > 0) {
            SerialMon.print("\nManueller Befehl: ");
            SerialMon.println(command);
            sendAT(command.c_str());
        }
    }
    
    delay(100);  // Kurze Pause
}
