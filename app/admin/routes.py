from flask import render_template, flash, redirect, url_for, request
from flask_login import login_required, current_user
from app.admin import bp
from app.models import User, Device, Relay, StatusContact
from app import db
from app.admin.forms import UserForm, DeviceForm
from functools import wraps

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or not current_user.is_admin:
            flash('Sie benötigen Administratorrechte für diese Seite.')
            return redirect(url_for('main.index'))
        return f(*args, **kwargs)
    return decorated_function

@bp.route('/users')
@login_required
@admin_required
def list_users():
    users = User.query.all()
    return render_template('admin/user_list.html', title='Benutzerverwaltung', users=users)

@bp.route('/devices')
@login_required
@admin_required
def list_devices():
    devices = Device.query.all()
    return render_template('admin/device_list.html', title='Geräteverwaltung', devices=devices)

@bp.route('/device/add', methods=['GET', 'POST'])
@login_required
@admin_required
def add_device():
    form = DeviceForm()
    form.owner_id.choices = [(u.id, u.username) for u in User.query.order_by(User.username).all()]
    
    if form.validate_on_submit():
        device = Device(
            name=form.name.data,
            description=form.description.data,
            owner_id=form.owner_id.data
        )
        db.session.add(device)
        db.session.commit()
        flash(f'Gerät {device.name} wurde erstellt.')
        return redirect(url_for('admin.list_devices'))
        
    return render_template('admin/device_form.html', title='Neues Gerät', form=form)

@bp.route('/device/<int:id>/edit', methods=['GET', 'POST'])
@login_required
@admin_required
def edit_device(id):
    device = Device.query.get_or_404(id)
    form = DeviceForm(obj=device)
    form.owner_id.choices = [(u.id, u.username) for u in User.query.order_by(User.username).all()]
    
    if form.validate_on_submit():
        device.name = form.name.data
        device.description = form.description.data
        device.owner_id = form.owner_id.data
        db.session.commit()
        flash(f'Gerät {device.name} wurde aktualisiert.')
        return redirect(url_for('admin.list_devices'))
        
    return render_template('admin/device_form.html', title='Gerät bearbeiten', form=form)

@bp.route('/device/<int:id>/delete', methods=['POST'])
@login_required
@admin_required
def delete_device(id):
    device = Device.query.get_or_404(id)
    name = device.name
    db.session.delete(device)
    db.session.commit()
    flash(f'Gerät {name} wurde gelöscht.')
    return redirect(url_for('admin.list_devices'))

@bp.route('/user/<int:id>', methods=['GET', 'POST'])
@login_required
@admin_required
def edit_user(id):
    user = User.query.get_or_404(id)
    form = UserForm(obj=user)
    if form.validate_on_submit():
        user.username = form.username.data
        user.email = form.email.data
        user.is_admin = form.is_admin.data
        db.session.commit()
        flash('Benutzer wurde aktualisiert.')
        return redirect(url_for('admin.list_users'))
    return render_template('admin/edit_user.html', title='Benutzer bearbeiten', form=form, user=user)
