import os

class ConfigManager:
    _instance = None  # Aquí guardaremos la única instancia

    def __new__(cls):
        # Lógica del Singleton: Si no existe, la creamos. Si existe, devolvemos la misma.
        if cls._instance is None:
            print("🔋 [Singleton] Creando NUEVA instancia de ConfigManager...", flush=True)
            cls._instance = super(ConfigManager, cls).__new__(cls)
            cls._instance._initialize()
        else:
            print("♻️  [Singleton] Usando instancia EXISTENTE de ConfigManager...", flush=True)
        return cls._instance

    def _initialize(self):
        """Carga las variables de entorno solo una vez."""
        # Configuración de Base de Datos
        self.db_host = os.getenv('DB_HOST', 'db-master')
        self.db_user = os.getenv('DB_USER', 'user_docker')
        self.db_password = os.getenv('DB_PASSWORD', 'password_segura')
        self.db_name = os.getenv('DB_NAME', 'viajego_db')
        
        # Configuración de la App (Ejemplo de uso extra)
        self.secret_key = os.getenv('SECRET_KEY', 'mi_secreto_super_seguro')

    def get_db_config(self):
        """Devuelve un diccionario limpio con la config de DB."""
        return {
            'host': self.db_host,
            'user': self.db_user,
            'password': self.db_password,
            'database': self.db_name
        }