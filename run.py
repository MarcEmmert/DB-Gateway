from flask import Flask
from flask_migrate import Migrate
from app import create_app, db
from app.models import User, Device, TemperatureReading, Relay, StatusContact

app = create_app()
migrate = Migrate(app, db)

@app.shell_context_processor
def make_shell_context():
    return {
        'db': db,
        'User': User,
        'Device': Device,
        'TemperatureReading': TemperatureReading,
        'Relay': Relay,
        'StatusContact': StatusContact
    }

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=3000)
