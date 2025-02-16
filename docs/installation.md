# IoT Gateway - Installations- und Konfigurationsanleitung

## 1. Systemvoraussetzungen
- Ubuntu Server (getestet mit 22.04 LTS)
- Apache2
- PHP 8.1 oder höher
- MariaDB 10.6 oder höher
- MQTT Broker (Mosquitto)

## 2. Installation der Abhängigkeiten

```bash
# System-Updates
sudo apt update
sudo apt upgrade -y

# Apache, PHP und MariaDB
sudo apt install -y apache2 php php-mysql php-mbstring php-json php-pdo mariadb-server mosquitto mosquitto-clients

# PHP-Erweiterungen
sudo apt install -y php-curl php-xml php-zip
```

## 3. Datenbank-Konfiguration

```bash
# MariaDB sichern
sudo mysql_secure_installation

# Datenbank und Benutzer erstellen
sudo mysql -u root -p
```

```sql
CREATE DATABASE iotgateway;
CREATE USER 'iotuser'@'localhost' IDENTIFIED BY '01937736e';
GRANT ALL PRIVILEGES ON iotgateway.* TO 'iotuser'@'localhost';
FLUSH PRIVILEGES;

USE iotgateway;

-- Benutzer-Tabelle
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Geräte-Tabelle
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    mqtt_topic VARCHAR(100) UNIQUE NOT NULL,
    location VARCHAR(100),
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Temperatur-Tabelle
CREATE TABLE temperatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    value DECIMAL(5,2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id)
);

-- Relais-Tabelle
CREATE TABLE relays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    state TINYINT(1) DEFAULT 0,
    last_changed TIMESTAMP NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id)
);

-- Status-Kontakte-Tabelle
CREATE TABLE status_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    state TINYINT(1) DEFAULT 0,
    last_changed TIMESTAMP NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id)
);

-- API-Tokens-Tabelle
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 4. Apache-Konfiguration

```bash
# Webserver-Verzeichnis erstellen
sudo mkdir -p /var/www/html/iotgateway
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo chmod -R 755 /var/www/html/iotgateway

# Apache-Konfiguration
sudo nano /etc/apache2/sites-available/iotgateway.conf
```

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
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

```bash
# Apache-Konfiguration aktivieren
sudo a2ensite iotgateway.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 5. MQTT-Broker Konfiguration

```bash
sudo nano /etc/mosquitto/conf.d/default.conf
```

```
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd
```

```bash
# MQTT-Benutzer erstellen
sudo mosquitto_passwd -c /etc/mosquitto/passwd iotgateway

# Mosquitto neustarten
sudo systemctl restart mosquitto
```

## 6. Admin-Benutzer erstellen

```sql
-- In MariaDB
USE iotgateway;
INSERT INTO users (username, password, email, is_admin) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin@example.com', 1);
```

## 7. Wichtige Hinweise

### Berechtigungen
- Stellen Sie sicher, dass alle PHP-Dateien die richtigen Berechtigungen haben:
```bash
sudo chown -R www-data:www-data /var/www/html/iotgateway
sudo find /var/www/html/iotgateway -type f -exec chmod 644 {} \;
sudo find /var/www/html/iotgateway -type d -exec chmod 755 {} \;
```

### Fehlerprotokollierung
- Überprüfen Sie die Fehlerprotokolle bei Problemen:
```bash
sudo tail -f /var/log/apache2/iotgateway_error.log
```

### Sicherheit
- Ändern Sie alle Standard-Passwörter
- Aktivieren Sie HTTPS
- Halten Sie das System aktuell
