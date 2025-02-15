from app import create_app, db
from app.models import User

def create_admin_user():
    app = create_app()
    with app.app_context():
        # Prüfe ob Admin bereits existiert
        admin = User.query.filter_by(username='admin').first()
        if admin:
            print("Admin-Benutzer existiert bereits!")
            return
        
        # Erstelle neuen Admin
        admin = User(
            username='admin',
            email='admin@example.com',
            is_admin=True
        )
        admin.set_password('admin123')
        
        try:
            db.session.add(admin)
            db.session.commit()
            print("Admin-Benutzer wurde erfolgreich erstellt!")
            print("Username: admin")
            print("Password: admin123")
        except Exception as e:
            db.session.rollback()
            print(f"Fehler beim Erstellen des Admin-Benutzers: {str(e)}")

if __name__ == '__main__':
    create_admin_user()
