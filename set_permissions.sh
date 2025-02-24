#!/bin/bash

# Webserver-Benutzer (typischerweise www-data)
WEB_USER="www-data"
WEB_GROUP="www-data"

# Basis-Verzeichnis
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Verzeichnisberechtigungen setzen
find "$BASE_DIR" -type d -exec chmod 755 {} \;

# Dateiberechtigungen setzen
find "$BASE_DIR" -type f -exec chmod 644 {} \;

# Spezielle Verzeichnisse für Schreibzugriff
chmod 775 "$BASE_DIR/logs"
chmod 775 "$BASE_DIR/tmp"

# Sensitive Dateien schützen
chmod 640 "$BASE_DIR/includes/db.php"
chmod 640 "$BASE_DIR/includes/auth.php"

# Besitzer setzen
chown -R $WEB_USER:$WEB_GROUP "$BASE_DIR"

echo "Berechtigungen gesetzt!"
