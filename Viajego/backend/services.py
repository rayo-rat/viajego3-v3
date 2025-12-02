from database import get_db_connection
from werkzeug.utils import secure_filename
import json
import random
import os

UPLOAD_FOLDER = '/tmp/uploads'
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

def fix_encoding(data):
    if isinstance(data, str):
        try: return data.encode('latin-1').decode('utf-8')
        except: return data
    elif isinstance(data, list): return [fix_encoding(i) for i in data]
    elif isinstance(data, dict): return {k: fix_encoding(v) for k, v in data.items()}
    return data

def handle_file_upload(req):
    if 'imagen' in req.files:
        file = req.files['imagen']
        if file.filename != '' and '.' in file.filename:
            return f"/images/{secure_filename(file.filename)}" 
    return None

def db_get_all(table, agency_filter=None):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    sql = f"SELECT t.*, u.nombre_comercial as nombre_agencia FROM {table} t LEFT JOIN usuarios u ON t.user_id = u.id"
    params = ()
    if agency_filter:
        sql += " WHERE t.user_id = %s"
        params = (agency_filter,)
    sql += " ORDER BY t.id DESC"
    cursor.execute(sql, params)
    res = cursor.fetchall(); conn.close()
    return fix_encoding(res)

def db_insert(sql, values):
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        cursor.execute(sql, values); conn.commit()
        return True, "Creado exitosamente"
    except Exception as e: return False, str(e)
    finally: conn.close()

def db_delete(table, id):
    conn = get_db_connection(); cursor = conn.cursor()
    cursor.execute(f"DELETE FROM {table} WHERE id=%s", (id,)); conn.commit(); conn.close()

def db_login(email, password):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, email, rol, nombre, nombre_comercial FROM usuarios WHERE email=%s AND password_hash=%s", (email, password))
    user = cursor.fetchone(); conn.close()
    return fix_encoding(user)