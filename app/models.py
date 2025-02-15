from datetime import datetime
from werkzeug.security import generate_password_hash, check_password_hash
from flask_login import UserMixin
from app import db, login

class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(64), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(256))
    is_admin = db.Column(db.Boolean, default=False)
    devices = db.relationship('Device', backref='owner', lazy='dynamic')

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    def __repr__(self):
        return f'<User {self.username}>'

class Device(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(64), nullable=False)
    description = db.Column(db.String(256))
    owner_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    temperature_readings = db.relationship('TemperatureReading', backref='device', lazy='dynamic')
    relays = db.relationship('Relay', backref='device', lazy='dynamic')
    status_contacts = db.relationship('StatusContact', backref='device', lazy='dynamic')

class TemperatureReading(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    timestamp = db.Column(db.DateTime, index=True, default=datetime.utcnow)
    value = db.Column(db.Float, nullable=False)
    device_id = db.Column(db.Integer, db.ForeignKey('device.id'), nullable=False)

class Relay(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(64), nullable=False)
    state = db.Column(db.Boolean, default=False)
    device_id = db.Column(db.Integer, db.ForeignKey('device.id'), nullable=False)

class StatusContact(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(64), nullable=False)
    state = db.Column(db.Boolean, default=False)
    device_id = db.Column(db.Integer, db.ForeignKey('device.id'), nullable=False)

@login.user_loader
def load_user(id):
    return User.query.get(int(id))
