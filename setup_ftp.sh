#!/bin/bash

echo "=== FTP Server Setup ==="

# Installiere vsftpd falls nicht vorhanden
if ! command -v vsftpd &> /dev/null; then
    sudo apt-get update
    sudo apt-get install -y vsftpd
fi

# Erstelle FTP Benutzer falls nicht vorhanden
if ! id -u esp32 &> /dev/null; then
    sudo useradd -m -s /bin/bash esp32
    echo "esp32:esp32" | sudo chpasswd
fi

# Konfiguriere vsftpd
cat << 'EOF' | sudo tee /etc/vsftpd.conf
# Grundeinstellungen
listen=YES
listen_ipv6=NO
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
use_localtime=YES
xferlog_enable=YES
chroot_local_user=YES
allow_writeable_chroot=YES
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd

# Port Konfiguration
listen_port=2121

# Passive Mode Konfiguration
pasv_enable=YES
pasv_min_port=40000
pasv_max_port=40100
pasv_address=149.172.191.145
pasv_addr_resolve=NO
port_enable=NO

# Wichtig für externe Verbindungen
pasv_promiscuous=YES
seccomp_sandbox=NO

# Logging
xferlog_file=/var/log/vsftpd.log
log_ftp_protocol=YES

# Verzeichnis
local_root=/home/esp32/ftp
EOF

# Debug: Zeige Konfiguration
echo "=== vsftpd Konfiguration ==="
cat /etc/vsftpd.conf
echo ""

# Erstelle Verzeichnisse
echo "=== Erstelle Verzeichnisse ==="
sudo mkdir -p /var/run/vsftpd/empty
sudo mkdir -p /home/esp32/ftp
sudo chown -R esp32:esp32 /home/esp32/ftp
sudo chmod 755 /home/esp32/ftp
ls -la /home/esp32/ftp
echo ""

# Erstelle Status-Datei
echo "relay1=0,relay2=0,relay3=0,relay4=0" | sudo tee /home/esp32/ftp/relay_status.txt
sudo chown esp32:esp32 /home/esp32/ftp/relay_status.txt
sudo chmod 644 /home/esp32/ftp/relay_status.txt

# Aktiviere und starte ufw
echo "=== Aktiviere Firewall ==="
sudo ufw --force enable
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 2121/tcp comment 'FTP Control'
sudo ufw allow 40000:40100/tcp comment 'FTP Passive Data'
sudo ufw status verbose
echo ""

# Starte FTP-Server neu
echo "=== Starte FTP-Server ==="
sudo systemctl restart vsftpd
sleep 2

# Prüfe Status
echo "=== FTP Server Status ==="
sudo systemctl status vsftpd
echo ""

# Debug: Prüfe Logs
echo "=== vsftpd Logs ==="
sudo tail -n 20 /var/log/vsftpd.log
echo ""

echo "=== Netzwerk Status ==="
sudo netstat -tuln | grep -E "2121|40000"
echo ""

echo "=== Setup abgeschlossen ==="
echo "FTP Server ist nun konfiguriert:"
echo "- Benutzer: esp32"
echo "- Passwort: esp32"
echo "- Verzeichnis: /home/esp32/ftp"
echo "- Port: 2121"
echo ""
echo "Teste die lokale Verbindung mit:"
echo "ftp -p esp32@localhost 2121"
echo ""
echo "Teste die externe Verbindung mit:"
echo "ftp -p esp32@rdp.emmert.biz 2121"
