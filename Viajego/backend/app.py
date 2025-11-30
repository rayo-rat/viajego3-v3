from flask import Flask, jsonify, request
from flask_cors import CORS
from database import get_db_connection
from werkzeug.utils import secure_filename
import socket
import json
import random
import os

app = Flask(__name__)
CORS(app)

# CONFIGURACIÓN DE ARCHIVOS (SIMULADA)
UPLOAD_FOLDER = '/tmp/uploads'
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def handle_file_upload(req):
    if 'imagen' in req.files:
        file = req.files['imagen']
        if file.filename != '' and allowed_file(file.filename):
            filename = secure_filename(file.filename)
            # Simulación: En un entorno real, guardarías el archivo aquí:
            # file.save(os.path.join(UPLOAD_FOLDER, filename))
            return f"/images/{filename}" # Retorna una URL simulada
    return None

# --- HELPER PARA UPDATE (MODIFICADO para aceptar request.form) ---
def generic_update(table, id, data):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        # Prepara la data para SQL (convirtiendo valores a str)
        set_clause = ", ".join([f"{key} = %s" for key in data.keys()])
        values = list(data.values())
        values.append(id)
        sql = f"UPDATE {table} SET {set_clause} WHERE id = %s"
        cursor.execute(sql, values)
        conn.commit()
        return jsonify({"success": True, "message": "Actualizado"})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 400
    finally:
        conn.close()

# --- HELPER PARA MANEJAR POST DE SERVICIOS ---
def handle_service_post(table_name, required_fields, insert_sql, values_formatter):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    d = request.form
    
    # Manejar subida de archivo
    imagen_url = handle_file_upload(request)
    
    # Preparar datos (usando .get para evitar KeyError si el campo no viene en el formulario)
    data = dict(d)
    if imagen_url:
        data['imagen_url'] = imagen_url
    
    try:
        # Requerido: Convierte a tipos correctos y llama al formatter de valores
        values = values_formatter(data)
        cursor.execute(insert_sql, values)
        conn.commit()
        return jsonify({"success": True, "imagen_url": imagen_url}), 201
    except Exception as e:
        return jsonify({"success": False, "msg": str(e)}), 400
    finally:
        conn.close()

# --- HELPER PARA MANEJAR PUT DE SERVICIOS ---
def handle_service_put(table_name, id):
    # PUT ahora también espera multipart/form-data
    data = dict(request.form)
    
    # Manejar subida de archivo
    imagen_url = handle_file_upload(request)
    if imagen_url:
        data['imagen_url'] = imagen_url
    
    return generic_update(table_name, id, data)


# ==================== AGENCIAS ====================
@app.route('/api/agencias', methods=['GET', 'POST'])
def agencias():
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    if request.method == 'POST':
        d = request.form # Usa request.form
        try:
            cursor.execute("INSERT INTO agencias (nombre, tipo_servicio, contacto) VALUES (%s, %s, %s)", (d['nombre'], d['tipo'], d['contacto']))
            conn.commit(); return jsonify({"success": True}), 201
        except Exception as e: return jsonify({"success": False, "msg": str(e)}), 400
        finally: conn.close()
    
    cursor.execute("SELECT * FROM agencias")
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

@app.route('/api/agencias/<int:id>', methods=['PUT', 'DELETE'])
def agencia_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM agencias WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    elif request.method == 'PUT':
        return handle_service_put('agencias', id)

# ==================== VUELOS ====================
@app.route('/api/vuelos', methods=['GET', 'POST'])
def vuelos():
    insert_sql = """INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, agencia_id, imagen_url) 
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
    def formatter(d):
        return (d['codigo'], d['origen'], d['destino'], d['aerolinea'], d['clase'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['agencia_id']), d.get('imagen_url'))

    if request.method == 'POST':
        return handle_service_post('vuelos', ['codigo', 'precio'], insert_sql, formatter)

    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT v.*, a.nombre as nombre_agencia FROM vuelos v LEFT JOIN agencias a ON v.agencia_id = a.id")
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

@app.route('/api/vuelos/<int:id>', methods=['PUT', 'DELETE'])
def vuelo_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM vuelos WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    return handle_service_put('vuelos', id)

# ==================== HOTELES ====================
@app.route('/api/hoteles', methods=['GET', 'POST'])
def hoteles():
    insert_sql = "INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, tipo_habitacion, capacidad_max, agencia_id, imagen_url) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
    def formatter(d):
        return (d['nombre'], d['ciudad'], int(d['estrellas']), float(d['precio']), d.get('servicios', ''), d.get('tipo_habitacion', 'Estándar'), int(d.get('capacidad_max', 4)), int(d['agencia_id']), d.get('imagen_url'))

    if request.method == 'POST':
        return handle_service_post('hoteles', ['nombre', 'precio'], insert_sql, formatter)

    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT h.*, a.nombre as nombre_agencia FROM hoteles h LEFT JOIN agencias a ON h.agencia_id = a.id")
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

@app.route('/api/hoteles/<int:id>', methods=['PUT', 'DELETE'])
def hotel_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM hoteles WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    return handle_service_put('hoteles', id)

# ==================== AUTOBUSES ====================
@app.route('/api/autobuses', methods=['GET', 'POST'])
def autobuses():
    insert_sql = """INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, agencia_id, imagen_url) 
                     VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
    def formatter(d):
        return (d['origen'], d['destino'], d['linea'], d['tipo_asiento'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['agencia_id']), d.get('imagen_url'))

    if request.method == 'POST':
        return handle_service_post('rutas_autobus', ['origen', 'precio'], insert_sql, formatter)

    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT r.*, a.nombre as nombre_agencia FROM rutas_autobus r LEFT JOIN agencias a ON r.agencia_id = a.id")
    res = cursor.fetchall(); conn.close()
    return jsonify(res)

@app.route('/api/autobuses/<int:id>', methods=['PUT', 'DELETE'])
def bus_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM rutas_autobus WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
    return handle_service_put('rutas_autobus', id)

# ==================== PAQUETES ====================
@app.route('/api/paquetes', methods=['GET', 'POST'])
def paquetes():
    insert_sql = """INSERT INTO paquetes (titulo, destino, duracion, fecha_salida, fecha_regreso, precio_total, servicios_incluidos, transporte_json, hotel_json, agencia_id, imagen_url) 
                     VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
    def formatter(d):
        transporte = json.dumps(json.loads(d['transporte_data_json']))
        hotel = json.dumps(json.loads(d['hotel_data_json']))
        
        return (d['titulo'], d['destino'], d['duracion'], d['fecha_salida'], d['fecha_regreso'], float(d['precio']), d['servicios'], transporte, hotel, int(d['agencia_id']), d.get('imagen_url'))

    if request.method == 'POST':
        # Requerido: Convierte JSON strings a objetos antes de pasar al formatter
        data = dict(request.form)
        imagen_url = handle_file_upload(request)
        if imagen_url: data['imagen_url'] = imagen_url
        
        return handle_service_post('paquetes', ['titulo', 'precio'], insert_sql, formatter)

    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT p.*, a.nombre as nombre_agencia FROM paquetes p LEFT JOIN agencias a ON p.agencia_id = a.id")
    res = cursor.fetchall(); conn.close()
    
    for p in res:
        if p['transporte_json']: p['transporte_data'] = json.loads(p['transporte_json'])
        if p['hotel_json']: p['hotel_data'] = json.loads(p['hotel_json'])
        
    return jsonify(res)

@app.route('/api/paquetes/<int:id>', methods=['PUT', 'DELETE'])
def paquete_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM paquetes WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True})
        
    # PUT de paquetes maneja JSON strings anidados en form data
    data = dict(request.form)
    if 'transporte_data_json' in data:
        data['transporte_json'] = data.pop('transporte_data_json')
    if 'hotel_data_json' in data:
        data['hotel_json'] = data.pop('hotel_data_json')
        
    # Manejar subida de archivo
    imagen_url = handle_file_upload(request)
    if imagen_url: data['imagen_url'] = imagen_url
        
    return generic_update('paquetes', id, data)

# --- USUARIOS/RESERVAS (ORIGINAL) ---
@app.route('/api/registro', methods=['POST'])
def registro():
    d = request.json; conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    try:
        # Sin hashing de contraseña (original)
        cursor.execute("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol) VALUES (%s, %s, %s, %s, 'usuario')", (d['nombre'], d['apellido'], d['email'], d['password']))
        conn.commit(); return jsonify({"success": True}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/login', methods=['POST'])
def login():
    d = request.json; conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    # Comparación de contraseña en texto plano (original)
    cursor.execute("SELECT id, nombre, email, rol FROM usuarios WHERE email=%s AND password_hash=%s", (d.get('email'), d.get('password')))
    user = cursor.fetchone(); conn.close()
    if user: return jsonify({"success": True, "user": user})
    else: return jsonify({"success": False, "message": "Inválido"}), 401

@app.route('/api/reservas', methods=['POST'])
def crear_reserva():
    d = request.json; conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
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

@app.route('/api/reservas/cancelar/<int:id>', methods=['POST'])
def cancelar_reserva(id):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT total_price FROM reservas WHERE id=%s", (id,))
        if not (r := cursor.fetchone()): return jsonify({"success": False}), 404
        refund = float(r['total_price']) * 0.30
        cursor.execute("UPDATE reservas SET status='Cancelado', refund_amount=%s WHERE id=%s", (refund, id))
        conn.commit(); return jsonify({"success": True, "reembolso": refund})
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)