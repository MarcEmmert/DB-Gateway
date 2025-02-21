#!/bin/bash

# Pfad zum Projekt-Verzeichnis
PROJECT_DIR="/Users/Marc/Desktop/DB-Gateway"

# Log-Datei
LOG_FILE="$PROJECT_DIR/scripts/git-sync.log"

# Datum für Log
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Ins Projekt-Verzeichnis wechseln
cd "$PROJECT_DIR"

# Status in Log schreiben
echo "[$DATE] Starting git sync" >> "$LOG_FILE"

# Änderungen holen
git fetch origin

# Prüfen ob Updates verfügbar
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ $LOCAL != $REMOTE ]; then
    echo "[$DATE] Updates found, pulling changes..." >> "$LOG_FILE"
    
    # Lokale Änderungen sichern
    if [ -n "$(git status --porcelain)" ]; then
        echo "[$DATE] Stashing local changes" >> "$LOG_FILE"
        git stash
    fi
    
    # Änderungen ziehen
    git pull origin main
    
    # Lokale Änderungen wiederherstellen
    if [ -n "$(git stash list)" ]; then
        echo "[$DATE] Restoring local changes" >> "$LOG_FILE"
        git stash pop
    fi
    
    echo "[$DATE] Sync completed successfully" >> "$LOG_FILE"
else
    echo "[$DATE] No updates found" >> "$LOG_FILE"
fi
