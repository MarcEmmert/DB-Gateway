# Backup-Konfiguration

## Installation

1. Backup-Skript auf dem Server installieren:
```bash
sudo mkdir -p /var/www/html/iotgateway/scripts
sudo cp backup.sh backup_config.env /var/www/html/iotgateway/scripts/
sudo chmod +x /var/www/html/iotgateway/scripts/backup.sh
```

2. Konfigurationsdatei anpassen:
```bash
sudo nano /var/www/html/iotgateway/scripts/backup_config.env
```
- Nextcloud-URL anpassen
- Benutzername und Passwort eintragen
- E-Mail-Einstellungen konfigurieren

3. Cron-Job einrichten:
```bash
sudo crontab -e
```
Fügen Sie diese Zeile hinzu:
```
0 2 * * * /var/www/html/iotgateway/scripts/backup.sh >> /var/log/iotgateway_backup.log 2>&1
```
Dies führt das Backup täglich um 2 Uhr nachts aus.

## Backup-Inhalt

Das Backup enthält:
1. Komplette MySQL-Datenbank
2. Alle Projektdateien
3. Konfigurationsdateien
4. Logs (der letzten 7 Tage)

## Backup-Speicherorte

1. Lokal: `/var/backups/iotgateway/`
2. Nextcloud: `IoTGateway-Backups/`

## Backup-Wiederherstellung

1. Backup von Nextcloud herunterladen:
```bash
curl -u "IhrBenutzer:IhrPasswort" \
     "https://ihre-nextcloud.de/remote.php/dav/files/IhrBenutzer/IoTGateway-Backups/backup_DATUM.tar.gz" \
     -o restore_backup.tar.gz
```

2. Backup entpacken:
```bash
tar -xzf restore_backup.tar.gz
```

3. Datenbank wiederherstellen:
```bash
mysql -u iotuser -p iotgateway < db_backup_DATUM.sql
```

4. Dateien wiederherstellen:
```bash
sudo tar -xzf files_backup_DATUM.tar.gz -C /var/www/html/
```

## Monitoring

- Backup-Logs: `/var/log/iotgateway_backup.log`
- E-Mail-Benachrichtigungen bei Fehlern
- Tägliche Statusüberprüfung

## Sicherheit

- Verschlüsselte Übertragung via HTTPS
- Sichere Aufbewahrung der Zugangsdaten
- Regelmäßige Überprüfung der Backup-Integrität
