#!/bin/bash

# Konfiguration
BACKUP_DIR="/var/backups/iotgateway"
PROJECT_DIR="/var/www/html/iotgateway"
DB_NAME="iotgateway"
DB_USER="iotuser"
DB_PASS="01937736e"
NEXTCLOUD_URL="https://next.studio101.de/remote.php/dav/files/IoT/IoTGateway-Backups"
NEXTCLOUD_USER="IoT"
NEXTCLOUD_PASS="!01TMZ18bla"

# Datum für Backup-Namen
DATE=$(date +%Y%m%d_%H%M%S)

# Backup-Verzeichnis erstellen
mkdir -p "$BACKUP_DIR"

# MySQL-Dump erstellen
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_backup_$DATE.sql"

# Projekt-Dateien sichern
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" -C "$(dirname $PROJECT_DIR)" "$(basename $PROJECT_DIR)"

# Beide Backups in ein Archiv packen
tar -czf "$BACKUP_DIR/complete_backup_$DATE.tar.gz" -C "$BACKUP_DIR" "db_backup_$DATE.sql" "files_backup_$DATE.tar.gz"

# Zu Nextcloud hochladen
curl -u "$NEXTCLOUD_USER:$NEXTCLOUD_PASS" \
     -T "$BACKUP_DIR/complete_backup_$DATE.tar.gz" \
     "$NEXTCLOUD_URL/backup_$DATE.tar.gz"

# Alte Backups lokal aufräumen (älter als 7 Tage)
find "$BACKUP_DIR" -type f -mtime +7 -delete

# Log-Eintrag
echo "Backup completed at $DATE" >> "$BACKUP_DIR/backup.log"

# Fehlerbehandlung
if [ $? -eq 0 ]; then
    echo "Backup erfolgreich erstellt und hochgeladen"
else
    echo "Fehler beim Backup-Prozess"
    # Optional: E-Mail-Benachrichtigung bei Fehler
    # mail -s "Backup Error IoT Gateway" ihre@email.de < "$BACKUP_DIR/backup.log"
fi
