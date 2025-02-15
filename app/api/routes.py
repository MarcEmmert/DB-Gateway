from flask import jsonify, request
from flask_login import login_required, current_user
from app.api import bp
from app.models import Device, TemperatureReading, Relay
from app import db
from datetime import datetime

@bp.route('/api/temperature', methods=['POST'])
def record_temperature():
    data = request.get_json()
    if not data:
        return jsonify({'error': 'No data provided'}), 400
    
    device_id = data.get('device_id')
    temperature = data.get('temperature')
    
    if not device_id or temperature is None:
        return jsonify({'error': 'Missing required fields'}), 400
    
    device = Device.query.filter_by(device_id=device_id).first()
    if not device:
        return jsonify({'error': 'Device not found'}), 404
    
    reading = TemperatureReading(
        device_id=device.id,
        temperature=float(temperature)
    )
    device.last_seen = datetime.utcnow()
    
    db.session.add(reading)
    db.session.commit()
    
    return jsonify({'status': 'success'}), 201

@bp.route('/api/device/<device_id>/status', methods=['GET'])
def get_device_status(device_id):
    device = Device.query.filter_by(device_id=device_id).first()
    if not device:
        return jsonify({'error': 'Device not found'}), 404
    
    relays = [{
        'id': relay.id,
        'name': relay.name,
        'state': relay.state
    } for relay in device.relays]
    
    return jsonify({
        'device_id': device.device_id,
        'name': device.name,
        'relays': relays
    })

@bp.route('/relay/toggle', methods=['POST'])
@login_required
def toggle_relay():
    relay_id = request.form.get('relay_id', type=int)
    state = request.form.get('state', type=bool)
    
    if not relay_id:
        return jsonify({'status': 'error', 'message': 'Relay ID ist erforderlich'}), 400
        
    relay = Relay.query.get_or_404(relay_id)
    device = Device.query.get(relay.device_id)
    
    if not device:
        return jsonify({'status': 'error', 'message': 'Gerät nicht gefunden'}), 404
        
    if not current_user.is_admin and device.owner_id != current_user.id:
        return jsonify({'status': 'error', 'message': 'Keine Berechtigung'}), 403
    
    try:
        relay.state = state
        db.session.commit()
        return jsonify({
            'status': 'success',
            'relay_id': relay_id,
            'state': state
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({'status': 'error', 'message': str(e)}), 500
