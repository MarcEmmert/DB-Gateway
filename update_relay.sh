#!/bin/bash

# Skript zum Aktualisieren der Relay-Status
# Verwendung: ./update_relay.sh <relay_nummer> <status>
# Beispiel: ./update_relay.sh 1 1    # Schaltet Relay 1 ein
#          ./update_relay.sh 2 0    # Schaltet Relay 2 aus

RELAY_FILE="/home/esp32/ftp/relay_status.txt"

if [ $# -ne 2 ]; then
    echo "Verwendung: $0 <relay_nummer> <status>"
    echo "Beispiel: $0 1 1    # Schaltet Relay 1 ein"
    echo "         $0 2 0    # Schaltet Relay 2 aus"
    exit 1
fi

RELAY_NUM=$1
STATUS=$2

# Prüfe Eingaben
if ! [[ "$RELAY_NUM" =~ ^[1-4]$ ]]; then
    echo "Fehler: Relay Nummer muss zwischen 1 und 4 sein"
    exit 1
fi

if ! [[ "$STATUS" =~ ^[0-1]$ ]]; then
    echo "Fehler: Status muss 0 oder 1 sein"
    exit 1
fi

# Lese aktuelle Status
if [ ! -f "$RELAY_FILE" ]; then
    echo "relay1=0,relay2=0,relay3=0,relay4=0" > "$RELAY_FILE"
fi

CURRENT_STATUS=$(cat "$RELAY_FILE")

# Update den Status für das gewählte Relay
for i in {1..4}; do
    if [ $i -eq $RELAY_NUM ]; then
        NEW_STATUS="relay$i=$STATUS"
    else
        NEW_STATUS="relay$i=$(echo $CURRENT_STATUS | grep -o "relay$i=[0-1]" | cut -d= -f2)"
    fi
    if [ $i -eq 1 ]; then
        FINAL_STATUS="$NEW_STATUS"
    else
        FINAL_STATUS="$FINAL_STATUS,$NEW_STATUS"
    fi
done

# Schreibe neue Status
echo "$FINAL_STATUS" > "$RELAY_FILE"
echo "Relay Status aktualisiert:"
echo "$FINAL_STATUS"
