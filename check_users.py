from app import create_app, db
from app.models import User

def check_users():
    app = create_app()
    with app.app_context():
        users = User.query.all()
        print("\nRegistrierte Benutzer:")
        print("-" * 50)
        for user in users:
            print(f"ID: {user.id}")
            print(f"Username: {user.username}")
            print(f"Email: {user.email}")
            print(f"Is Admin: {user.is_admin}")
            print("-" * 50)

if __name__ == '__main__':
    check_users()
