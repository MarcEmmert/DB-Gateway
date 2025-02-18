#include "GSM.h"

// Serielle Verbindung für das GSM Modul
#define SerialGSM Serial2
#define GSM_BAUD 115200

// AT Befehle für das A7670E
const char* AT_OK = "OK\r\n";
const char* AT_ERROR = "ERROR\r\n";
const char* AT_TEST = "AT\r\n";
const char* AT_ECHO_OFF = "ATE0\r\n";
const char* AT_SMS_TEXT_MODE = "AT+CMGF=1\r\n";
const char* AT_SIGNAL_QUALITY = "AT+CSQ\r\n";
const char* AT_NETWORK_REG = "AT+CREG?\r\n";
const char* AT_GPRS_ATTACH = "AT+CGATT=1\r\n";
const char* AT_PDP_CONTEXT = "AT+CGDCONT=1,\"IP\",\"%s\"\r\n";
const char* AT_PDP_ACT = "AT+CGACT=1,1\r\n";

GSMClass::GSMClass() {
    _isConnected = false;
    _lastError = "";
}

bool GSMClass::begin(int rx_pin, int tx_pin) {
    // Serielle Verbindung initialisieren
    SerialGSM.begin(GSM_BAUD, SERIAL_8N1, rx_pin, tx_pin);
    
    // Warten bis das Modul bereit ist
    delay(3000);
    
    // Echo ausschalten
    if (!sendATCommand(AT_ECHO_OFF)) {
        _lastError = "Echo off failed";
        return false;
    }
    
    // Modul testen
    if (!sendATCommand(AT_TEST)) {
        _lastError = "Module not responding";
        return false;
    }
    
    return true;
}

bool GSMClass::connect(const char* apn, const char* user, const char* pass) {
    // Signal-Qualität prüfen
    if (!checkSignal()) {
        _lastError = "Poor signal quality";
        return false;
    }
    
    // Netzwerk-Registrierung prüfen
    if (!checkNetworkReg()) {
        _lastError = "Network registration failed";
        return false;
    }
    
    // GPRS aktivieren
    if (!sendATCommand(AT_GPRS_ATTACH)) {
        _lastError = "GPRS attach failed";
        return false;
    }
    
    // PDP Kontext konfigurieren
    char pdp_cmd[128];
    sprintf(pdp_cmd, AT_PDP_CONTEXT, apn);
    if (!sendATCommand(pdp_cmd)) {
        _lastError = "PDP context setup failed";
        return false;
    }
    
    // PDP Kontext aktivieren
    if (!sendATCommand(AT_PDP_ACT)) {
        _lastError = "PDP context activation failed";
        return false;
    }
    
    _isConnected = true;
    return true;
}

bool GSMClass::isConnected() {
    return _isConnected;
}

String GSMClass::getLastError() {
    return _lastError;
}

bool GSMClass::checkSignal() {
    String response = sendATCommandWithResponse(AT_SIGNAL_QUALITY);
    if (response.indexOf("+CSQ:") >= 0) {
        int signal = response.substring(response.indexOf("+CSQ: ") + 6).toInt();
        return signal > 10; // Mindestens 10 für gute Verbindung
    }
    return false;
}

bool GSMClass::checkNetworkReg() {
    String response = sendATCommandWithResponse(AT_NETWORK_REG);
    if (response.indexOf("+CREG: ") >= 0) {
        // +CREG: 0,1 oder +CREG: 0,5 bedeutet registriert
        return (response.indexOf(",1") > 0 || response.indexOf(",5") > 0);
    }
    return false;
}

bool GSMClass::sendATCommand(const char* command) {
    SerialGSM.print(command);
    String response = readResponse();
    return response.indexOf(AT_OK) >= 0;
}

String GSMClass::sendATCommandWithResponse(const char* command) {
    SerialGSM.print(command);
    return readResponse();
}

String GSMClass::readResponse() {
    String response = "";
    unsigned long start = millis();
    
    while (millis() - start < 10000) { // 10 Sekunden Timeout
        if (SerialGSM.available()) {
            char c = SerialGSM.read();
            response += c;
            
            // Prüfen ob Antwort komplett
            if (response.endsWith(AT_OK) || response.endsWith(AT_ERROR)) {
                break;
            }
        }
    }
    
    return response;
}

// Globale Instanz
GSMClass GSM;
