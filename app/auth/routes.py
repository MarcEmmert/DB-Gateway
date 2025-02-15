from flask import render_template, flash, redirect, url_for, request, current_app
from flask_login import login_user, logout_user, current_user, login_required
from app import db
from app.auth import bp
from app.models import User
from app.auth.forms import LoginForm, RegistrationForm

@bp.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    
    form = LoginForm()
    current_app.logger.info('Login attempt')
    
    if form.validate_on_submit():
        current_app.logger.info(f'Form validated for user: {form.username.data}')
        user = User.query.filter_by(username=form.username.data).first()
        
        if user is None:
            current_app.logger.warning(f'User not found: {form.username.data}')
            flash('Ungültiger Benutzername oder Passwort')
            return redirect(url_for('auth.login'))
            
        if not user.check_password(form.password.data):
            current_app.logger.warning('Invalid password')
            flash('Ungültiger Benutzername oder Passwort')
            return redirect(url_for('auth.login'))
            
        login_user(user, remember=form.remember_me.data)
        current_app.logger.info(f'User {user.username} logged in successfully')
        
        next_page = request.args.get('next')
        if not next_page or not next_page.startswith('/'):
            next_page = url_for('main.index')
        return redirect(next_page)
    
    if form.errors:
        current_app.logger.error(f'Form errors: {form.errors}')
    
    return render_template('auth/login.html', title='Anmelden', form=form)

@bp.route('/logout')
def logout():
    logout_user()
    return redirect(url_for('main.index'))

@bp.route('/register', methods=['GET', 'POST'])
@login_required
def register():
    if not current_user.is_admin:
        flash('Nur Administratoren können neue Benutzer registrieren.')
        return redirect(url_for('main.index'))
    form = RegistrationForm()
    if form.validate_on_submit():
        user = User(username=form.username.data, email=form.email.data)
        user.set_password(form.password.data)
        user.is_admin = form.is_admin.data
        db.session.add(user)
        db.session.commit()
        flash(f'Benutzer {form.username.data} wurde erfolgreich registriert!')
        return redirect(url_for('admin.user_list'))
    return render_template('auth/register.html', title='Registrierung', form=form)
