#define SerialGSM Serial2
#define SerialMon Serial

#define GSM_TX 14
#define GSM_RX 13
#define POWER_PIN 4  // Falls ein Power-Pin verwendet wird

void setup() {
    // Debug-Monitor
    SerialMon.begin(115200);
    SerialMon.println("A7670E Test Start");
    
    // GSM Serial
    SerialGSM.begin(115200, SERIAL_8N1, GSM_RX, GSM_TX);
    
    // Power-Pin konfigurieren falls verwendet
    if (POWER_PIN > 0) {
        pinMode(POWER_PIN, OUTPUT);
        digitalWrite(POWER_PIN, HIGH);
    }
    
    // Warten bis Modul hochgefahren
    delay(3000);
    
    SerialMon.println("Sende AT Befehle...");
}

// AT Befehl senden und Antwort ausgeben
void sendAT(const char* command) {
    SerialMon.print("Sende: ");
    SerialMon.println(command);
    
    SerialGSM.print(command);
    SerialGSM.print("\r\n");
    
    delay(500);
    
    while(SerialGSM.available()) {
        SerialMon.write(SerialGSM.read());
    }
    SerialMon.println();
}

void loop() {
    // Basis AT Befehle
    sendAT("AT");                // Modul-Test
    delay(1000);
    
    sendAT("AT+CGMM");          // Modell-Informationen
    delay(1000);
    
    sendAT("AT+CPIN?");         // SIM-Status
    delay(1000);
    
    sendAT("AT+CSQ");           // Signal-Qualität
    delay(1000);
    
    sendAT("AT+CREG?");         // Netzwerk-Registrierung
    delay(1000);
    
    sendAT("AT+COPS?");         // Aktueller Netzbetreiber
    delay(1000);
    
    // 30 Sekunden warten bis zum nächsten Test
    SerialMon.println("\nWarte 30 Sekunden...\n");
    delay(30000);
}
