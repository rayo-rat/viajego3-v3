import mysql.connector
import time
# IMPORTANTE: Importamos nuestro Singleton
from config_loader import ConfigManager

def get_db_connection():
    # 1. Instanciamos el Singleton. 
    # NO importa cuántas veces llamemos a esto, siempre será el mismo objeto en memoria.
    conf = ConfigManager() 
    
    print(f"🔍 DEBUG [database.py]: ConfigManager ID de memoria: {id(conf)}", flush=True)

    # 3. Obtenemos la configuración del objeto Singleton
    db_data = conf.get_db_config()

    retries = 5
    while retries > 0:
        try:
            return mysql.connector.connect(
                host=db_data['host'],
                user=db_data['user'],
                password=db_data['password'],
                database=db_data['database'],
                charset='utf8mb4',
                collation='utf8mb4_unicode_ci'
            )
        except mysql.connector.Error as err:
            print(f"⚠️ Error conectando: {err}. Reintentando...", flush=True)
            time.sleep(2)
            retries -= 1
    raise Exception("Error conectando a la BD")