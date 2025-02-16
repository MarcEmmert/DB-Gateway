import os
from dotenv import load_dotenv

basedir = os.path.abspath(os.path.dirname(__file__))
load_dotenv(os.path.join(basedir, '.env'))

class Config:
    # Secret Key
    SECRET_KEY = '01937736e'
    
    # Datenbank-Konfiguration
    SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL') or \
        'mysql://iotuser:01937736e@localhost/iotgateway'
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # Produktivmodus aktivieren
    DEBUG = False
    
    # MQTT Konfiguration
    MQTT_BROKER_URL = 'localhost'
    MQTT_BROKER_PORT = 1883
    MQTT_USERNAME = 'mqtt_user'
    MQTT_PASSWORD = '01937736e'  # Das Passwort, das Sie bei Mosquitto gesetzt haben
    MQTT_KEEPALIVE = 60
    MQTT_TLS_ENABLED = False
    
    # Session und CSRF Sicherheit
    WTF_CSRF_ENABLED = True
    WTF_CSRF_SECRET_KEY = '01937736e'  # Generiere einen eigenen CSRF Key
    
    # Logging
    LOG_TO_STDOUT = os.environ.get('LOG_TO_STDOUT')
    
    # Zeitzone
    TIMEZONE = 'Europe/Berlin'
