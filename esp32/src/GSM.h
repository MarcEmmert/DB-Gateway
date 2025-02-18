#ifndef GSM_H
#define GSM_H

#include <Arduino.h>

class GSMClass {
public:
    GSMClass();
    
    // Initialisierung
    bool begin(int rx_pin, int tx_pin);
    
    // Verbindungsaufbau
    bool connect(const char* apn, const char* user, const char* pass);
    
    // Status
    bool isConnected();
    String getLastError();
    
private:
    bool _isConnected;
    String _lastError;
    
    // Hilfsfunktionen
    bool checkSignal();
    bool checkNetworkReg();
    bool sendATCommand(const char* command);
    String sendATCommandWithResponse(const char* command);
    String readResponse();
};

extern GSMClass GSM;

#endif
