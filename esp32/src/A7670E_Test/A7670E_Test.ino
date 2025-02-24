#include <HardwareSerial.h>
#include <Wire.h>
#include <Adafruit_BMP280.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>

// Serielle Schnittstellen
HardwareSerial SerialGSM(2);  // UART2 für GSM verwenden
HardwareSerial& SerialMon = Serial;  // USB-Serial für Debug

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
const char* mqttTopicData = "device/67b63977e4fbf";

// Sensor Objekte
Adafruit_BMP280 bmp;
OneWire oneWire(DALLAS_PIN);
DallasTemperature dallas(&oneWire);

// Sensor Variablen
bool bmp_ready = false;
bool dallas_ready = false;
float bmpTemp = 0;
float bmpPressure = 0;
float dallas1Temp = DEVICE_DISCONNECTED_C;
float dallas2Temp = DEVICE_DISCONNECTED_C;

// GSM Status
bool gsm_ready = false;
bool gprs_ready = false;
bool mqtt_ready = false;

// Funktionsdeklarationen
bool sendAT(const char* command, const char* expected = "OK", int timeout = 2000);
String sendATWithResponse(const char* command, int timeout = 2000);
bool initGSM();
bool setupGPRS();
bool setupMQTT();
bool mqttPublish(const char* topic, const char* message);
bool shouldUpdateValues();
void updateLastValues();
void publishSensorData();

bool setupSensors() {
    // I2C für BMP280 initialisieren
    Wire.begin(I2C_SDA, I2C_SCL);
    
    // BMP280 initialisieren
    if (!bmp.begin(BMP280_ADDRESS_ALT, BMP280_CHIPID)) {
        SerialMon.println("BMP280: Fehler (Alt Adresse)");
        // Versuche Standard-Adresse
        if (!bmp.begin()) {
            SerialMon.println("BMP280: Fehler (Std Adresse)");
            bmp_ready = false;
        } else {
            SerialMon.println("BMP280: OK (Std Adresse)");
            bmp.setSampling(Adafruit_BMP280::MODE_NORMAL,     /* Operating Mode. */
                           Adafruit_BMP280::SAMPLING_X2,      /* Temp. oversampling */
                           Adafruit_BMP280::SAMPLING_X16,     /* Pressure oversampling */
                           Adafruit_BMP280::FILTER_X16,       /* Filtering. */
                           Adafruit_BMP280::STANDBY_MS_500);  /* Standby time. */
            bmp_ready = true;
        }
    } else {
        SerialMon.println("BMP280: OK (Alt Adresse)");
        bmp.setSampling(Adafruit_BMP280::MODE_NORMAL,     /* Operating Mode. */
                       Adafruit_BMP280::SAMPLING_X2,      /* Temp. oversampling */
                       Adafruit_BMP280::SAMPLING_X16,     /* Pressure oversampling */
                       Adafruit_BMP280::FILTER_X16,       /* Filtering. */
                       Adafruit_BMP280::STANDBY_MS_500);  /* Standby time. */
        bmp_ready = true;
    }
    
    // Dallas initialisieren
    dallas.begin();
    int deviceCount = dallas.getDeviceCount();
    if (deviceCount > 0) {
        SerialMon.print("Dallas: ");
        SerialMon.print(deviceCount);
        SerialMon.println(" Sensoren gefunden");
        dallas_ready = true;
    } else {
        SerialMon.println("Dallas: Keine Sensoren gefunden");
        dallas_ready = false;
    }
    
    return bmp_ready || dallas_ready;
}

void setup() {
    SerialMon.begin(115200);
    SerialMon.println("Start");
    
    // Initialisiere Sensorwerte
    bmpTemp = bmpPressure = dallas1Temp = dallas2Temp = 0;
    
    // Sensoren initialisieren
    if (!setupSensors()) {
        SerialMon.println("Keine Sensoren - Stop");
        while(1) delay(1000);
    }
    
    // GSM Setup
    SerialGSM.begin(115200, SERIAL_8N1, GSM_RX, GSM_TX);
    if (POWER_PIN > 0) {
        pinMode(POWER_PIN, OUTPUT);
        digitalWrite(POWER_PIN, HIGH);
    }
    delay(5000);  // Längere Wartezeit für GSM-Modul
    
    // Echo ausschalten und warten
    sendAT("ATE0");
    delay(1000);
    
    // Mehrere Versuche für die GSM-Initialisierung
    int retries = 0;
    while (!initGSM() && retries < 3) {
        SerialMon.println("GSM Init fehlgeschlagen, neuer Versuch...");
        delay(2000);
        retries++;
    }
    
    if (retries >= 3) {
        SerialMon.println("GSM: Fehler nach 3 Versuchen");
        return;
    }
    
    // GPRS Setup
    if (!setupGPRS()) {
        SerialMon.println("GPRS: Fehler");
        return;
    }
    
    // MQTT Setup
    if (!setupMQTT()) {
        SerialMon.println("MQTT: Fehler");
        return;
    }
    
    SerialMon.println("System bereit");
}

void readSensors() {
    // BMP280 auslesen
    if (bmp_ready) {
        float newTemp = bmp.readTemperature();
        float newPressure = bmp.readPressure() / 100.0F;  // hPa
        
        // Nur aktualisieren wenn Werte plausibel
        if (!isnan(newTemp) && !isnan(newPressure) && 
            newTemp > -40 && newTemp < 85 && 
            newPressure > 300 && newPressure < 1100) {
            
            bmpTemp = newTemp;
            bmpPressure = newPressure;
            
            SerialMon.print("BMP280: ");
            SerialMon.print(bmpTemp);
            SerialMon.print("°C, ");
            SerialMon.print(bmpPressure);
            SerialMon.println("hPa");
        } else {
            SerialMon.println("BMP280: Ungültige Werte");
            // Bei ungültigen Werten neu initialisieren
            bmp_ready = false;
            setupSensors();
        }
    }
    
    // Dallas auslesen
    if (dallas_ready) {
        dallas.requestTemperatures();
        float temp1 = dallas.getTempCByIndex(0);
        float temp2 = dallas.getTempCByIndex(1);
        
        if (temp1 != DEVICE_DISCONNECTED_C || temp2 != DEVICE_DISCONNECTED_C) {
            dallas1Temp = temp1;
            dallas2Temp = temp2;
            
            SerialMon.print("Dallas: ");
            SerialMon.print(dallas1Temp);
            SerialMon.print("°C, ");
            SerialMon.print(dallas2Temp);
            SerialMon.println("°C");
        } else {
            SerialMon.println("Dallas: Keine Daten");
        }
    }
}

bool sendAT(const char* command, const char* expected, int timeout) {
    if (strlen(command) > 0) {
        SerialGSM.println(command);
    }
    
    if (strlen(expected) == 0) {
        return true;
    }
    
    unsigned long start = millis();
    String response;
    
    while (millis() - start < timeout) {
        if (SerialGSM.available()) {
            response = SerialGSM.readStringUntil('\n');
            response.trim();
            
            if (response.length() > 0) {
                if (response.indexOf(expected) >= 0) {
                    return true;
                }
            }
        }
        delay(10);
    }
    
    return false;
}

String sendATWithResponse(const char* command, int timeout) {
    String response = "";
    
    if (strlen(command) > 0) {
        SerialGSM.println(command);
    }
    
    unsigned long start = millis();
    
    while (millis() - start < timeout) {
        if (SerialGSM.available()) {
            String line = SerialGSM.readStringUntil('\n');
            line.trim();
            
            if (line.length() > 0 && line != command) {
                response = line;
                break;
            }
        }
        delay(10);
    }
    
    return response;
}

bool initGSM() {
    // Modul testen mit mehreren Versuchen
    for (int i = 0; i < 5; i++) {
        if (sendAT("AT", "OK", 1000)) {
            break;
        }
        delay(1000);
        if (i == 4) {
            SerialMon.println("Modul antwortet nicht!");
            return false;
        }
    }
    
    // SIM-Status prüfen
    if (!sendAT("AT+CPIN?", "READY", 5000)) {
        SerialMon.println("SIM nicht bereit!");
        return false;
    }
    SerialMon.println("SIM bereit");
    
    // Auf Netzwerk registrieren mit Timeout
    unsigned long start = millis();
    while (millis() - start < 60000) {  // 60 Sekunden Timeout
        if (sendAT("AT+CREG?", "0,1")) {
            SerialMon.println("GSM Netzwerk registriert");
            break;
        }
        delay(2000);
        if (millis() - start >= 60000) {
            SerialMon.println("Keine Netzwerk-Registrierung!");
            return false;
        }
    }
    
    // GPRS Status prüfen
    if (!sendAT("AT+CGREG?", "0,1", 10000)) {
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
    
    SerialMon.println("MQTT: Setup");
    
    // Prüfen ob MQTT läuft und ggf. sauber beenden
    String response = sendATWithResponse("AT+CMQTTSTART?");
    if (response.indexOf("+CMQTTSTART: 1") >= 0) {
        sendAT("AT+CMQTTDISC=0,120");
        delay(1000);
        sendAT("AT+CMQTTREL=0");
        delay(1000);
        sendAT("AT+CMQTTSTOP");
        delay(2000);
    }
    
    // MQTT Version auf 3.1.1 setzen
    sendAT("AT+CMQTTCFG=\"version\",0,4");
    delay(1000);
    
    // Keep Alive auf 120 Sekunden setzen
    sendAT("AT+CMQTTCFG=\"keepalive\",0,120");
    delay(1000);
    
    // Clean Session deaktivieren für persistente Verbindung
    sendAT("AT+CMQTTCFG=\"session\",0,0");
    delay(1000);
    
    // MQTT Verbindung konfigurieren
    if (!sendAT("AT+CMQTTSTART", "OK", 10000)) {
        SerialMon.println("MQTT: Start Fehler");
        return false;
    }
    delay(2000);
    
    // Client ID setzen
    char atCommand[128];
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTACCQ=0,\"%s\"", mqttClientId);
    if (!sendAT(atCommand, "OK", 5000)) {
        SerialMon.println("MQTT: Client Fehler");
        return false;
    }
    delay(1000);
    
    // Mit Broker verbinden - Clean Session deaktiviert
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTCONNECT=0,\"tcp://%s:%d\",120,0,\"%s\",\"%s\"", 
             server, port, mqttUsername, mqttPassword);
    
    SerialMon.print("Connect: ");
    SerialMon.println(atCommand);
    
    if (!sendAT(atCommand, "+CMQTTCONNECT: 0,0", 20000)) {
        SerialMon.println("MQTT: Connect Fehler");
        return false;
    }
    
    mqtt_ready = true;
    SerialMon.println("MQTT: Bereit");
    return true;
}

bool mqttPublish(const char* topic, const char* message) {
    if (!mqtt_ready) return false;
    
    // Debug: AT Befehle anzeigen
    SerialMon.println("\nMQTT Publish:");
    
    // Topic setzen
    char atCommand[512];
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTTOPIC=0,%d", strlen(topic));
    SerialMon.println(atCommand);
    
    if (!sendAT(atCommand, ">", 2000)) {
        SerialMon.println("MQTT: Topic Fehler");
        return false;
    }
    SerialGSM.print(topic);
    delay(100);
    if (!sendAT("", "OK", 2000)) {
        return false;
    }
    delay(100);
    
    // Payload setzen
    snprintf(atCommand, sizeof(atCommand), "AT+CMQTTPAYLOAD=0,%d", strlen(message));
    SerialMon.println(atCommand);
    
    if (!sendAT(atCommand, ">", 2000)) {
        SerialMon.println("MQTT: Payload Fehler");
        return false;
    }
    SerialGSM.print(message);
    delay(100);
    if (!sendAT("", "OK", 2000)) {
        return false;
    }
    delay(100);
    
    // Nachricht veröffentlichen mit QoS=1 und Retain=1
    SerialMon.println("AT+CMQTTPUB=0,1,60,1");
    if (!sendAT("AT+CMQTTPUB=0,1,60,1", "+CMQTTPUB: 0,0", 5000)) {
        SerialMon.println("MQTT: Publish Fehler");
        return false;
    }
    
    SerialMon.println("MQTT: OK");
    return true;
}

bool shouldUpdateValues() {
    // Mindestens ein Sensor muss funktionieren
    if (!bmp_ready && !dallas_ready) {
        return false;
    }
    
    // Immer true zurückgeben - Werte bei jedem Durchlauf senden
    return true;
}

void updateLastValues() {
    // Keine Änderung
}

void publishSensorData() {
    if (!mqtt_ready) return;
    
    StaticJsonDocument<200> doc;
    char jsonBuffer[200];
    char topic[50];
    
    // Temperaturdaten senden
    doc.clear();
    if (dallas_ready) {
        if (dallas1Temp != DEVICE_DISCONNECTED_C) {
            doc["dallas1_temp"] = dallas1Temp;
        }
        if (dallas2Temp != DEVICE_DISCONNECTED_C) {
            doc["dallas2_temp"] = dallas2Temp;
        }
    }
    
    if (bmp_ready && !isnan(bmpTemp) && bmpTemp != 0) {
        doc["bmp_temp"] = bmpTemp;
        doc["pressure"] = bmpPressure;
    }
    
    serializeJson(doc, jsonBuffer);
    snprintf(topic, sizeof(topic), "%s/temperature", mqttTopicData);
    
    SerialMon.print("Temperature JSON: ");
    SerialMon.println(jsonBuffer);
    if (mqttPublish(topic, jsonBuffer)) {
        SerialMon.print("MQTT OK - Topic: ");
        SerialMon.println(topic);
    }
    
    // Status senden
    doc.clear();
    if (dallas_ready) {
        doc["dallas1"] = (dallas1Temp != DEVICE_DISCONNECTED_C);
        doc["dallas2"] = (dallas2Temp != DEVICE_DISCONNECTED_C);
    }
    if (bmp_ready && !isnan(bmpTemp) && bmpTemp != 0) {
        doc["bmp"] = true;
    }
    
    serializeJson(doc, jsonBuffer);
    snprintf(topic, sizeof(topic), "%s/status", mqttTopicData);
    
    SerialMon.print("Status JSON: ");
    SerialMon.println(jsonBuffer);
    if (mqttPublish(topic, jsonBuffer)) {
        SerialMon.print("MQTT OK - Topic: ");
        SerialMon.println(topic);
    }
}

void loop() {
    static unsigned long lastUpdate = 0;
    const unsigned long UPDATE_INTERVAL = 2000;  // 2 Sekunden statt 10 Sekunden
    
    if (millis() - lastUpdate >= UPDATE_INTERVAL) {
        lastUpdate = millis();
        
        // Sensoren auslesen
        readSensors();
        
        // Daten senden wenn sich Werte geändert haben
        if (shouldUpdateValues()) {
            publishSensorData();
        }
    }
    
    // Kurze Pause
    delay(100);
}
