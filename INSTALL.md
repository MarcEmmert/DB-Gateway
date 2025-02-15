# IoT Gateway - Installationsanleitung

## Inhaltsverzeichnis
1. [Systemvoraussetzungen](#1-systemvoraussetzungen)
2. [Grundinstallation](#2-grundinstallation)
3. [MySQL Einrichtung](#3-mysql-einrichtung)
4. [Anwendungsinstallation](#4-anwendungsinstallation)
5. [Konfiguration](#5-konfiguration)
6. [Dienste einrichten](#6-dienste-einrichten)
7. [Sicherheit](#7-sicherheit)
8. [Erste Anmeldung](#8-erste-anmeldung)
9. [Fehlerbehebung](#9-fehlerbehebung)

## 1. Systemvoraussetzungen

### Hardware-Anforderungen
* Mindestens 2 CPU-Kerne
* Mindestens 2 GB RAM
* Mindestens 20 GB Festplattenspeicher

### Software-Anforderungen
* Ubuntu Server 24.04 LTS
* Python 3.12
* MySQL 8.0
* MQTT Broker (Mosquitto)
* Nginx
* Git

## 2. Grundinstallation

### 2.1 System aktualisieren

    # System auf den neuesten Stand bringen
    $ sudo apt update
    $ sudo apt upgrade -y

    # Zeitzone einstellen
    $ sudo timedatectl set-timezone Europe/Berlin

### 2.2 Benötigte Pakete installieren

    # Grundlegende Pakete
    $ sudo apt install -y python3 python3-venv python3-dev python3-pip \
        mysql-server mosquitto mosquitto-clients \
        git nginx supervisor \
        build-essential libssl-dev libffi-dev

    # Prüfen der Installationen
    $ python3 --version
    $ mysql --version
    $ nginx -v
    $ supervisord -v

## 3. MySQL Einrichtung

### 3.1 MySQL absichern

    # MySQL secure installation durchführen
    $ sudo mysql_secure_installation

Antworten Sie wie folgt:
* Set up VALIDATE PASSWORD component? → YES
* Password validation policy level → 2 (STRONG)
* New root password → [Ihr sicheres Passwort]
* Remove anonymous users? → YES
* Disallow root login remotely? → YES
* Remove test database? → YES
* Reload privilege tables now? → YES

### 3.2 Datenbank und Benutzer erstellen

    # MySQL als Root starten
    $ sudo mysql

Im MySQL-Terminal:

    mysql> CREATE DATABASE iot_gateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    mysql> CREATE USER 'iot_user'@'localhost' IDENTIFIED BY 'IhrSicheresPasswort';
    mysql> GRANT ALL PRIVILEGES ON iot_gateway.* TO 'iot_user'@'localhost';
    mysql> FLUSH PRIVILEGES;
    mysql> EXIT;

Prüfen Sie den Zugriff:

    $ mysql -u iot_user -p'IhrSicheresPasswort' -e "SHOW DATABASES;"

## 4. Anwendungsinstallation

### 4.1 Anwendung herunterladen

    # Git Repository klonen
    $ cd /home/administrator
    $ git clone https://github.com/IhrBenutzername/DB-Gateway.git iot-gateway
    $ cd iot-gateway

    # Erstellen Sie die requirements.txt
    $ nano requirements.txt

Fügen Sie folgende Abhängigkeiten ein:

    Flask==3.0.0
    Flask-SQLAlchemy==3.1.1
    Flask-Login==0.6.3
    Flask-WTF==1.2.1
    mysql-connector-python==8.2.0
    python-dotenv==1.0.0
    Flask-MQTT==1.1.1
    Flask-SocketIO==5.3.6
    PyJWT==2.8.0
    bcrypt==4.1.2
    Flask-Migrate==4.0.5

### 4.2 Python-Umgebung einrichten

    # Virtuelle Umgebung erstellen und aktivieren
    $ python3 -m venv venv
    $ source venv/bin/activate

    # Pip aktualisieren und Abhängigkeiten installieren
    (venv)$ pip install --upgrade pip
    (venv)$ pip install -r requirements.txt
    (venv)$ pip install gunicorn

    # Gunicorn-Installation prüfen
    (venv)$ which gunicorn
    # Sollte /home/administrator/iot-gateway/venv/bin/gunicorn anzeigen

## 5. Konfiguration

### 5.1 Umgebungsvariablen einrichten

    # .env Datei erstellen
    $ nano .env

Fügen Sie folgendes ein:

    # Flask Konfiguration
    FLASK_APP=run.py
    FLASK_ENV=production
    SECRET_KEY=IhrSichererSchlüssel

    # Datenbank
    DATABASE_URL=mysql://iot_user:IhrSicheresPasswort@localhost/iot_gateway

    # MQTT Broker
    MQTT_BROKER_URL=localhost
    MQTT_BROKER_PORT=1883
    MQTT_USERNAME=mqtt_user
    MQTT_PASSWORD=IhrMQTTPasswort
    MQTT_KEEPALIVE=60

    # Logging
    LOG_LEVEL=INFO
    LOG_FILE=/var/log/iot-gateway/app.log

### 5.2 Verzeichnisse und Berechtigungen

    # Log-Verzeichnis erstellen
    $ sudo mkdir -p /var/log/iot-gateway
    $ sudo chown -R administrator:administrator /var/log/iot-gateway

    # Anwendungsberechtigungen setzen
    $ sudo chown -R administrator:administrator /home/administrator/iot-gateway
    $ sudo chmod -R 755 /home/administrator/iot-gateway

    # Berechtigungen prüfen
    $ ls -la /var/log/iot-gateway
    $ ls -la /home/administrator/iot-gateway

## 6. Dienste einrichten

### 6.1 Supervisor Konfiguration

    # Supervisor Konfigurationsdatei erstellen
    $ sudo nano /etc/supervisor/conf.d/iot-gateway.conf

Fügen Sie folgendes ein:

    [program:iot-gateway]
    directory=/home/administrator/iot-gateway
    command=/home/administrator/iot-gateway/venv/bin/gunicorn --workers 4 --bind unix:/tmp/iot-gateway.sock run:app
    user=administrator
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    stderr_logfile=/var/log/iot-gateway/supervisor.err.log
    stdout_logfile=/var/log/iot-gateway/supervisor.out.log
    environment=
        PYTHONPATH="/home/administrator/iot-gateway",
        PATH="/home/administrator/iot-gateway/venv/bin"

Supervisor neuladen:

    $ sudo supervisorctl reread
    $ sudo supervisorctl update
    $ sudo supervisorctl status iot-gateway
    # Sollte "RUNNING" anzeigen

### 6.2 Nginx Konfiguration

    # Nginx Konfigurationsdatei erstellen
    $ sudo nano /etc/nginx/sites-available/iot-gateway

Fügen Sie folgendes ein:

    server {
        listen 80;
        server_name _;

        access_log /var/log/nginx/iot-gateway.access.log;
        error_log /var/log/nginx/iot-gateway.error.log;

        location / {
            proxy_pass http://unix:/tmp/iot-gateway.sock;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }

        location /static {
            alias /home/administrator/iot-gateway/app/static;
            expires 30d;
        }
    }

Nginx aktivieren:

    # Symlink erstellen
    $ sudo ln -s /etc/nginx/sites-available/iot-gateway /etc/nginx/sites-enabled/
    
    # Default-Konfiguration entfernen
    $ sudo rm -f /etc/nginx/sites-enabled/default
    
    # Konfiguration testen
    $ sudo nginx -t
    
    # Nginx neustarten
    $ sudo systemctl restart nginx
    
    # Status prüfen
    $ sudo systemctl status nginx

## 7. Sicherheit

### 7.1 Firewall einrichten

    # UFW Regeln erstellen
    $ sudo ufw default deny incoming
    $ sudo ufw default allow outgoing
    $ sudo ufw allow ssh
    $ sudo ufw allow 'Nginx Full'
    $ sudo ufw allow 1883
    
    # Firewall aktivieren
    $ sudo ufw enable
    
    # Status prüfen
    $ sudo ufw status

## 8. Erste Anmeldung

Nach erfolgreicher Installation können Sie sich anmelden mit:
* Benutzername: admin
* Passwort: admin123

⚠️ WICHTIG: Ändern Sie das Admin-Passwort sofort nach der ersten Anmeldung!

## 9. Fehlerbehebung

### 9.1 Häufige Probleme

Problem: Supervisor zeigt FATAL
```
$ sudo supervisorctl status iot-gateway
→ Prüfen Sie die Pfade und Berechtigungen:
$ ls -l /home/administrator/iot-gateway/venv/bin/gunicorn
$ sudo chown -R administrator:administrator /home/administrator/iot-gateway
```

Problem: 502 Bad Gateway
```
$ tail -f /var/log/nginx/error.log
→ Prüfen Sie den Socket:
$ ls -l /tmp/iot-gateway.sock
→ Prüfen Sie die Supervisor-Logs:
$ tail -f /var/log/iot-gateway/supervisor.err.log
```

Problem: Datenbank-Verbindungsfehler
```
$ mysql -u iot_user -p
→ Prüfen Sie die Berechtigungen:
$ sudo mysql -e "SHOW GRANTS FOR 'iot_user'@'localhost';"
```
