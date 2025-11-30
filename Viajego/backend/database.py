import mysql.connector
import os
import time

def get_db_connection():
    retries = 5
    while retries > 0:
        try:
            return mysql.connector.connect(
                host=os.getenv('DB_HOST', 'db-master'),
                user=os.getenv('DB_USER', 'user_docker'),
                password=os.getenv('DB_PASSWORD', 'password_segura'),
                database=os.getenv('DB_NAME', 'viajego_db'),
                charset='utf8mb4',      # <--- CLAVE 1: Forzar charset
                collation='utf8mb4_unicode_ci' # <--- CLAVE 2: Forzar collation
            )
        except mysql.connector.Error:
            print("Esperando a la base de datos...")
            time.sleep(3)
            retries -= 1
    raise Exception("No se pudo conectar a la BD")