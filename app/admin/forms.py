from flask_wtf import FlaskForm
from wtforms import StringField, BooleanField, SelectField, TextAreaField, PasswordField, SubmitField
from wtforms.validators import DataRequired, Email, Length, Optional
from app.models import User

class UserForm(FlaskForm):
    username = StringField('Benutzername', validators=[DataRequired(), Length(min=2, max=64)])
    email = StringField('E-Mail', validators=[DataRequired(), Email(), Length(max=120)])
    password = PasswordField('Passwort', validators=[Optional(), Length(min=6)])
    is_admin = BooleanField('Administrator')
    submit = SubmitField('Speichern')

    def __init__(self, original_username=None, *args, **kwargs):
        super(UserForm, self).__init__(*args, **kwargs)
        self.original_username = original_username

    def validate_username(self, username):
        if username.data != self.original_username:
            user = User.query.filter_by(username=username.data).first()
            if user is not None:
                raise ValidationError('Bitte wählen Sie einen anderen Benutzernamen.')

class DeviceForm(FlaskForm):
    name = StringField('Name', validators=[DataRequired(), Length(min=2, max=64)])
    description = TextAreaField('Beschreibung', validators=[Optional(), Length(max=256)])
    owner_id = SelectField('Besitzer', coerce=int, validators=[DataRequired()])
    submit = SubmitField('Speichern')

    def __init__(self, *args, **kwargs):
        super(DeviceForm, self).__init__(*args, **kwargs)
        self.owner_id.choices = [(u.id, u.username) for u in User.query.all()]
