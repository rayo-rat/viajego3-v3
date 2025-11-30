import mysql.connector
import os

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv('DB_HOST', 'db-master'),
        user=os.getenv('DB_USER', 'user_docker'),
        password=os.getenv('DB_PASSWORD', 'password_segura'),
        database=os.getenv('DB_NAME', 'viajego_db')
    )