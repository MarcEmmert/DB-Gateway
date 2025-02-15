from flask import render_template, flash, redirect, url_for, request
from flask_login import login_required, current_user
from app.main import bp
from app.models import Device, TemperatureReading, Relay, StatusContact
from app import db

@bp.route('/')
@bp.route('/index')
@login_required
def index():
    if current_user.is_admin:
        devices = Device.query.all()
    else:
        devices = Device.query.filter_by(owner_id=current_user.id).all()
    return render_template('main/index.html', title='Dashboard', devices=devices)

@bp.route('/devices')
@login_required
def devices():
    if current_user.is_admin:
        devices = Device.query.all()
    else:
        devices = Device.query.filter_by(owner_id=current_user.id).all()
    return render_template('main/devices.html', title='Meine Geräte', 
                         devices=devices, TemperatureReading=TemperatureReading)

@bp.route('/device/<int:device_id>')
@login_required
def device_detail(device_id):
    device = Device.query.get_or_404(device_id)
    if not current_user.is_admin and device.owner_id != current_user.id:
        flash('Sie haben keine Berechtigung, dieses Gerät anzuzeigen.')
        return redirect(url_for('main.devices'))
    return render_template('main/device_detail.html', title=device.name, device=device)

@bp.route('/device/add', methods=['GET', 'POST'])
@login_required
def add_device():
    # TODO: Implementiere Gerätehinzufügung
    flash('Diese Funktion wird in Kürze verfügbar sein.')
    return redirect(url_for('main.devices'))

@bp.route('/device/<int:device_id>/edit', methods=['GET', 'POST'])
@login_required
def edit_device(device_id):
    # TODO: Implementiere Gerätebearbeitung
    flash('Diese Funktion wird in Kürze verfügbar sein.')
    return redirect(url_for('main.devices'))

@bp.route('/device/<int:id>/toggle_relay/<int:relay_id>', methods=['POST'])
@login_required
def toggle_relay(id, relay_id):
    device = Device.query.get_or_404(id)
    if not current_user.is_admin and device.owner_id != current_user.id:
        return jsonify({'error': 'Zugriff verweigert'}), 403
    
    relay = Relay.query.get_or_404(relay_id)
    if relay.device_id != id:
        return jsonify({'error': 'Ungültiges Relais'}), 400
    
    relay.state = not relay.state
    db.session.commit()
    
    return jsonify({
        'id': relay.id,
        'state': relay.state
    })
