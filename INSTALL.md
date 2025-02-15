# IoT Gateway Installation Guide

## Systemvoraussetzungen
- Ubuntu Server 24.04 LTS
- MariaDB 10.6 oder höher
- PHP 8.3 oder höher
- Apache2
- Mosquitto MQTT Broker

## 1. System-Pakete aktualisieren

```bash
sudo apt update
sudo apt upgrade -y
```

## 2. PHP und Apache Installation

Zuerst fügen wir das Ondřej Surý's PPA Repository hinzu, das die aktuellen PHP-Versionen enthält:

```bash
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
```

Dann installieren wir PHP und die benötigten Erweiterungen:

```bash
sudo apt install -y apache2 php8.3 php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl libapache2-mod-php8.3
```

## 3. MariaDB Installation

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
```

## 4. Mosquitto MQTT Broker Installation

```bash
sudo apt install -y mosquitto mosquitto-clients
```

Mosquitto konfigurieren:

```bash
sudo nano /etc/mosquitto/conf.d/default.conf
```

Fügen Sie folgende Konfiguration hinzu:
```
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd
```

Benutzer für MQTT erstellen:
```bash
sudo mosquitto_passwd -c /etc/mosquitto/passwd mqtt_user
```

Mosquitto neustarten:
```bash
sudo systemctl restart mosquitto
```

## 5. Datenbank einrichten

```bash
sudo mysql -u root
```

In der MariaDB-Konsole:
```sql
CREATE DATABASE iotgateway;
CREATE USER 'iotuser'@'localhost' IDENTIFIED BY 'IhrSicheresPasswort';
GRANT ALL PRIVILEGES ON iotgateway.* TO 'iotuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 6. Anwendung installieren

Anwendungsverzeichnis erstellen:
```bash
sudo mkdir -p /var/www/html/iotgateway
```

Wählen Sie eine der folgenden Methoden, um die Anwendung zu installieren:

### Option A: Via Git (empfohlen)
```bash
cd /var/www/html/iotgateway
sudo git clone https://github.com/IhrUsername/iot-gateway.git .
```

### Option B: Via SFTP/SCP
1. Auf Ihrem lokalen Computer:
```bash
scp -r /pfad/zu/iot-gateway/* benutzer@server:/tmp/iotgateway/
```

2. Auf dem Server:
```bash
sudo mv /tmp/iotgateway/* /var/www/html/iotgateway/
```

### Option C: Manuell
1. Laden Sie das neueste Release von der Projekt-Website herunter
2. Entpacken Sie es in `/var/www/html/iotgateway`

Nach der Installation:
```bash
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo cp /var/www/html/iotgateway/config.example.php /var/www/html/iotgateway/config.php
sudo nano /var/www/html/iotgateway/config.php
```

Datenbank initialisieren:
```bash
sudo mysql iotgateway < /var/www/html/iotgateway/database/schema.sql
```

## 7. Apache konfigurieren

Virtual Host erstellen:
```bash
sudo nano /etc/apache2/sites-available/iotgateway.conf
```

Fügen Sie folgende Konfiguration hinzu:
```apache
<VirtualHost *:80>
    ServerName iotgateway.local
    DocumentRoot /var/www/html/iotgateway
    
    <Directory /var/www/html/iotgateway>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/iotgateway_error.log
    CustomLog ${APACHE_LOG_DIR}/iotgateway_access.log combined
</VirtualHost>
```

Apache-Konfiguration aktivieren:
```bash
sudo a2ensite iotgateway
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 8. Berechtigungen setzen

```bash
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo chmod -R 755 /var/www/html/iotgateway
sudo chmod -R 777 /var/www/html/iotgateway/logs
```

## 9. Admin-Benutzer erstellen

Verbinden Sie sich mit der Datenbank:
```bash
sudo mysql iotgateway
```

Admin-Benutzer erstellen (ersetzen Sie 'IhrAdminPasswort' durch ein sicheres Passwort):
```sql
INSERT INTO users (username, password, email, is_admin) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin@example.com', TRUE);
```

## 10. Firewall konfigurieren

```bash
sudo ufw allow 80/tcp
sudo ufw allow 1883/tcp
sudo ufw enable
```

## 11. System neustarten

```bash
sudo reboot
```

## Erste Anmeldung

1. Öffnen Sie http://ihre-server-ip im Browser
2. Melden Sie sich mit folgenden Zugangsdaten an:
   - Benutzername: admin
   - Passwort: IhrAdminPasswort

## Fehlerbehebung

1. Apache-Logs prüfen:
```bash
sudo tail -f /var/log/apache2/iotgateway_error.log
```

2. MQTT-Logs prüfen:
```bash
sudo tail -f /var/log/mosquitto/mosquitto.log
```

3. PHP-Logs prüfen:
```bash
sudo tail -f /var/log/php8.3-fpm.log
```

## Sicherheitshinweise

1. Ändern Sie alle Standard-Passwörter
2. Halten Sie das System aktuell
3. Überwachen Sie die Log-Dateien
4. Erstellen Sie regelmäßige Backups
