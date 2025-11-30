from flask import Flask, jsonify, request
from flask_cors import CORS
from database import get_db_connection
from werkzeug.utils import secure_filename
import json
import random
import os

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False 
CORS(app)

UPLOAD_FOLDER = '/tmp/uploads'
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def handle_file_upload(req):
    if 'imagen' in req.files:
        file = req.files['imagen']
        if file.filename != '' and allowed_file(file.filename):
            filename = secure_filename(file.filename)
            return f"/images/{filename}" 
    return None

def generic_update(table, id, data):
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        set_clause = ", ".join([f"{key} = %s" for key in data.keys()])
        values = list(data.values()); values.append(id)
        sql = f"UPDATE {table} SET {set_clause} WHERE id = %s"
        cursor.execute(sql, values); conn.commit()
        return jsonify({"success": True, "message": "Actualizado"})
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

# --- LÓGICA CORE: OBTENER SERVICIOS (CON FILTRO DE AGENCIA) ---
def get_services(table_name, agency_filter=None):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    
    # JOIN para traer el nombre comercial de la agencia (usuario)
    sql = f"""
        SELECT t.*, u.nombre_comercial as nombre_agencia 
        FROM {table_name} t 
        LEFT JOIN usuarios u ON t.user_id = u.id
    """
    
    params = ()
    if agency_filter:
        sql += " WHERE t.user_id = %s"
        params = (agency_filter,)
    
    sql += " ORDER BY t.id DESC"
    
    cursor.execute(sql, params)
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

# ==================== VUELOS ====================
@app.route('/api/vuelos', methods=['GET', 'POST'])
def vuelos():
    if request.method == 'GET':
        agency_id = request.args.get('agency_id') # Filtro opcional
        return get_services('vuelos', agency_id)

    # POST
    d = request.form; conn = get_db_connection(); cursor = conn.cursor()
    img = handle_file_upload(request)
    try:
        sql = """INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id, imagen_url) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        vals = (d['codigo'], d['origen'], d['destino'], d['aerolinea'], d['clase'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['user_id']), img)
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True}), 201
    except Exception as e: return jsonify({"success": False, "msg": str(e)}), 400
    finally: conn.close()

@app.route('/api/vuelos/<int:id>', methods=['PUT', 'DELETE'])
def vuelo_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM vuelos WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    d = dict(request.form); img = handle_file_upload(request)
    if img: d['imagen_url'] = img
    return generic_update('vuelos', id, d)

# ==================== HOTELES ====================
@app.route('/api/hoteles', methods=['GET', 'POST'])
def hoteles():
    if request.method == 'GET':
        return get_services('hoteles', request.args.get('agency_id'))

    d = request.form; conn = get_db_connection(); cursor = conn.cursor()
    img = handle_file_upload(request)
    try:
        sql = "INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, tipo_habitacion, capacidad_max, user_id, imagen_url) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
        vals = (d['nombre'], d['ciudad'], int(d['estrellas']), float(d['precio']), d.get('servicios', ''), d.get('tipo_habitacion', 'Estándar'), int(d.get('capacidad_max', 4)), int(d['user_id']), img)
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True}), 201
    except Exception as e: return jsonify({"success": False, "msg": str(e)}), 400
    finally: conn.close()

@app.route('/api/hoteles/<int:id>', methods=['PUT', 'DELETE'])
def hotel_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM hoteles WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    d = dict(request.form); img = handle_file_upload(request)
    if img: d['imagen_url'] = img
    return generic_update('hoteles', id, d)

# ==================== AUTOBUSES ====================
@app.route('/api/autobuses', methods=['GET', 'POST'])
def autobuses():
    if request.method == 'GET':
        return get_services('rutas_autobus', request.args.get('agency_id'))

    d = request.form; conn = get_db_connection(); cursor = conn.cursor()
    img = handle_file_upload(request)
    try:
        sql = """INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id, imagen_url) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        vals = (d['origen'], d['destino'], d['linea'], d['tipo_asiento'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['user_id']), img)
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True}), 201
    except Exception as e: return jsonify({"success": False, "msg": str(e)}), 400
    finally: conn.close()

@app.route('/api/autobuses/<int:id>', methods=['PUT', 'DELETE'])
def bus_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM rutas_autobus WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    d = dict(request.form); img = handle_file_upload(request)
    if img: d['imagen_url'] = img
    return generic_update('rutas_autobus', id, d)

# ==================== USUARIOS & AUTH (MODIFICADO) ====================

# 1. REGISTRO PÚBLICO (Solo Turistas)
@app.route('/api/registro', methods=['POST'])
def registro():
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        # Solo pedimos Email y Password. Rol forzado a 'usuario'.
        # Nombre y Apellido quedan NULL por ahora.
        cursor.execute("INSERT INTO usuarios (email, password_hash, rol) VALUES (%s, %s, 'usuario')", 
                       (d['email'], d['password']))
        conn.commit()
        return jsonify({"success": True, "message": "¡Bienvenido a ViajeGO!"}), 201
    except Exception as e: 
        return jsonify({"success": False, "message": "El email ya está registrado"}), 400
    finally: conn.close()

# 2. CREAR AGENCIA (Solo Admin)
@app.route('/api/admin/crear_agencia', methods=['POST'])
def crear_agencia():
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        cursor.execute("INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) VALUES (%s, %s, 'agencia', %s)", 
                       (d['email'], d['password'], d['nombre_comercial']))
        conn.commit()
        return jsonify({"success": True, "message": "Agencia creada correctamente"}), 201
    except Exception as e: 
        return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

# 3. LOGIN (Redirección inteligente)
@app.route('/api/login', methods=['POST'])
def login():
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT id, email, rol, nombre, nombre_comercial FROM usuarios WHERE email=%s AND password_hash=%s", 
                   (d.get('email'), d.get('password')))
    user = cursor.fetchone()
    conn.close()
    
    if user:
        # Preparamos el objeto usuario para el frontend
        return jsonify({"success": True, "user": user})
    else:
        return jsonify({"success": False, "message": "Credenciales inválidas"}), 401

# 4. LISTAR AGENCIAS (Para el panel de Admin)
@app.route('/api/admin/agencias', methods=['GET'])
def listar_agencias():
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, email, nombre_comercial, fecha_registro FROM usuarios WHERE rol='agencia' ORDER BY id DESC")
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

@app.route('/api/reservas', methods=['POST'])
def crear_reserva():
    d = request.json; conn = get_db_connection(); cursor = conn.cursor()
    try:
        code = f"RES-{random.randint(10000, 99999)}"; dets = json.dumps(d.get('detalles', {}))
        cursor.execute("INSERT INTO reservas (user_id, reservation_code, service_type, item_name, date_start, date_end, num_guests, details_json, total_price) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)", 
                       (d['user_id'], code, d['service_type'], d['item_name'], d['date_start'], d['date_end'], d['num_guests'], dets, d['total_price']))
        conn.commit(); return jsonify({"success": True, "code": code}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/mis_reservas/<int:uid>', methods=['GET'])
def mis_reservas(uid):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM reservas WHERE user_id=%s ORDER BY created_at DESC", (uid,))
    res = cursor.fetchall(); conn.close()
    for r in res: r['detalles'] = json.loads(r['details_json']) if isinstance(r['details_json'], str) else r['detalles']
    return jsonify(res)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)