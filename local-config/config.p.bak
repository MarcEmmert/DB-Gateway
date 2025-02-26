import os
from dotenv import load_dotenv

basedir = os.path.abspath(os.path.dirname(__file__))
load_dotenv(os.path.join(basedir, '.env'))

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'you-will-never-guess'
    SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL') or \
        'mysql://iot_user:iot_password@localhost/iot_gateway'
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # Debug-Modus aktivieren
    DEBUG = True
    
    # MQTT Config
    MQTT_BROKER_URL = 'localhost'
    MQTT_BROKER_PORT = 1883
    MQTT_USERNAME = ''
    MQTT_PASSWORD = ''
    MQTT_KEEPALIVE = 60
    MQTT_TLS_ENABLED = False
    
    # Session und CSRF
    WTF_CSRF_ENABLED = True
    WTF_CSRF_SECRET_KEY = 'a-very-secret-key'
