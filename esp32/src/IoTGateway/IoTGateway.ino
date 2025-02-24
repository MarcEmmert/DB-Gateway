#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_BMP085.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>
#include <Nextion.h>
#include <TinyGSM.h>

// Pin Definitionen
#define DALLAS_PIN 25
#define RELAIS_1 26
#define RELAIS_2 27
#define RELAIS_3 14
#define RELAIS_4 12
#define CONTACT_1 34
#define CONTACT_2 35
#define CONTACT_3 36
#define CONTACT_4 39
#define GSM_TX 14
#define GSM_RX 13
#define NEXTION_RX 16
#define NEXTION_TX 17
#define I2C_SDA 33
#define I2C_SCL 32

// Objekte für Sensoren und Kommunikation
Adafruit_BMP085 bmp;
OneWire oneWire(DALLAS_PIN);
DallasTemperature dallas(&oneWire);
NexHMI nextion(Serial2);
TinyGsm modem(Serial1);

// Konfigurationsvariablen
struct Config {
  bool useGSM;
  char ssid[32];
  char password[32];
  char serverUrl[100];
  char apn[32];
  char gprsUser[32];
  char gprsPass[32];
} config;

// Globale Variablen für Sensor- und Statuswerte
float bmpTemp, pressure;
float dallasTemp;
bool relayStates[4] = {false, false, false, false};
bool contactStates[4] = {false, false, false, false};
bool isOnline = false;
String connectionType = "None";

void setup() {
  Serial.begin(115200);
  
  // I2C Setup für BMP180
  Wire.begin(I2C_SDA, I2C_SCL);
  if (!bmp.begin()) {
    Serial.println("BMP180 nicht gefunden!");
  }

  // Dallas Setup
  dallas.begin();
  
  // Relais Pins
  pinMode(RELAIS_1, OUTPUT);
  pinMode(RELAIS_2, OUTPUT);
  pinMode(RELAIS_3, OUTPUT);
  pinMode(RELAIS_4, OUTPUT);
  
  // Kontakt Pins
  pinMode(CONTACT_1, INPUT);
  pinMode(CONTACT_2, INPUT);
  pinMode(CONTACT_3, INPUT);
  pinMode(CONTACT_4, INPUT);
  
  // GSM Setup
  Serial1.begin(115200, SERIAL_8N1, GSM_RX, GSM_TX);
  
  // Nextion Setup
  Serial2.begin(9600, SERIAL_8N1, NEXTION_RX, NEXTION_TX);
  nextion.begin(9600);
  
  // Lade Konfiguration
  loadConfig();
  
  // Initialisiere Verbindung
  setupConnection();
}

void loop() {
  // Lese Sensoren
  readSensors();
  
  // Aktualisiere Display
  updateDisplay();
  
  // Prüfe auf Nextion Eingaben
  handleNextionInput();
  
  // Sende Daten zum Server
  if (isOnline) {
    sendDataToServer();
  }
  
  // Prüfe auf Server-Befehle
  checkServerCommands();
  
  delay(1000);
}

void loadConfig() {
  // TODO: Implementiere EEPROM Konfiguration
}

void setupConnection() {
  if (config.useGSM) {
    setupGSM();
  } else {
    setupWiFi();
  }
}

void setupGSM() {
  Serial.println("Initialisiere GSM...");
  if (!modem.restart()) {
    Serial.println("GSM Modem Reset fehlgeschlagen");
    return;
  }
  
  if (!modem.gprsConnect(config.apn, config.gprsUser, config.gprsPass)) {
    Serial.println("GPRS Verbindung fehlgeschlagen");
    return;
  }
  
  isOnline = true;
  connectionType = "GSM";
}

void setupWiFi() {
  Serial.println("Verbinde mit WLAN...");
  WiFi.begin(config.ssid, config.password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    isOnline = true;
    connectionType = "WiFi";
  }
}

void readSensors() {
  // BMP180
  bmpTemp = bmp.readTemperature();
  pressure = bmp.readPressure() / 100.0; // hPa
  
  // Dallas
  dallas.requestTemperatures();
  dallasTemp = dallas.getTempCByIndex(0);
  
  // Kontakte
  contactStates[0] = digitalRead(CONTACT_1);
  contactStates[1] = digitalRead(CONTACT_2);
  contactStates[2] = digitalRead(CONTACT_3);
  contactStates[3] = digitalRead(CONTACT_4);
}

void updateDisplay() {
    // Update temperature and pressure values
    nextion.setComponentText("tTemp", String(bmpTemp, 1) + "°C");
    nextion.setComponentText("tPressure", String(pressure, 0) + "hPa");
    nextion.setComponentText("tDallasTemp", String(dallasTemp, 1) + "°C");
    
    // Update relay states
    for (int i = 0; i < 4; i++) {
        String btnName = "btnRelay" + String(i + 1);
        nextion.setComponentBackgroundColor(btnName, relayStates[i] ? 0x00FF00 : 0xFF0000);
    }
    
    // Update contact states
    for (int i = 0; i < 4; i++) {
        String indName = "indContact" + String(i + 1);
        nextion.setComponentBackgroundColor(indName, contactStates[i] ? 0x00FF00 : 0xFF0000);
    }
    
    // Update connection status
    nextion.setComponentText("tStatus", isOnline ? "Online" : "Offline");
    nextion.setComponentBackgroundColor("tStatus", isOnline ? 0x00FF00 : 0xFF0000);
    
    // Update connection type
    nextion.setComponentText("tConnType", config.useGSM ? "GSM" : "WiFi");
}

void handleNextionInput() {
    NexTouch *touch = nextion.getCurrentTouch();
    if (touch) {
        String componentName = touch->getName();
        
        // Handle relay buttons
        if (componentName.startsWith("btnRelay")) {
            int relayNum = componentName.substring(8).toInt() - 1;
            if (relayNum >= 0 && relayNum < 4) {
                setRelay(relayNum, !relayStates[relayNum]);
            }
        }
        
        // Handle configuration page
        else if (componentName == "btnConfig") {
            nextion.setPage("page1");  // Switch to config page
        }
        else if (componentName == "btnSave") {
            // Save configuration
            String newSsid = nextion.getComponentText("tSSID");
            String newPass = nextion.getComponentText("tPass");
            String newUrl = nextion.getComponentText("tURL");
            String newApn = nextion.getComponentText("tAPN");
            
            strncpy(config.ssid, newSsid.c_str(), sizeof(config.ssid) - 1);
            strncpy(config.password, newPass.c_str(), sizeof(config.password) - 1);
            strncpy(config.serverUrl, newUrl.c_str(), sizeof(config.serverUrl) - 1);
            strncpy(config.apn, newApn.c_str(), sizeof(config.apn) - 1);
            
            // Save config and restart connection
            saveConfig();
            setupConnection();
            nextion.setPage("page0");  // Return to main page
        }
        else if (componentName == "btnBack") {
            nextion.setPage("page0");  // Return to main page
        }
        else if (componentName == "btnToggleConn") {
            config.useGSM = !config.useGSM;
            setupConnection();
        }
    }
}

void saveConfig() {
    // Save configuration to EEPROM or flash
    // Implementation depends on storage method
}

void sendDataToServer() {
  StaticJsonDocument<200> doc;
  
  doc["device_id"] = 1; // TODO: Konfigurierbar machen
  doc["bmp_temp"] = bmpTemp;
  doc["pressure"] = pressure;
  doc["dallas_temp"] = dallasTemp;
  
  JsonArray contacts = doc.createNestedArray("contacts");
  for (int i = 0; i < 4; i++) {
    contacts.add(contactStates[i]);
  }
  
  JsonArray relays = doc.createNestedArray("relays");
  for (int i = 0; i < 4; i++) {
    relays.add(relayStates[i]);
  }
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  HTTPClient http;
  http.begin(config.serverUrl);
  http.addHeader("Content-Type", "application/json");
  
  int httpCode = http.POST(jsonString);
  if (httpCode > 0) {
    String payload = http.getString();
    // TODO: Verarbeite Server-Antwort
  }
  
  http.end();
}

void checkServerCommands() {
  // TODO: Implementiere Server-Polling für Befehle
}

void setRelay(int relay, bool state) {
  if (relay >= 0 && relay < 4) {
    digitalWrite(relay == 0 ? RELAIS_1 :
                relay == 1 ? RELAIS_2 :
                relay == 2 ? RELAIS_3 : RELAIS_4, state);
    relayStates[relay] = state;
  }
}
