from flask import Flask, jsonify, request
from flask_cors import CORS
from database import get_db_connection
# [CAMBIO 1] Importamos el Singleton
from config_loader import ConfigManager
from werkzeug.utils import secure_filename
import json
import random
import os

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False 
CORS(app)

# [CAMBIO 2] Instanciamos el Singleton al iniciar la app
app_config = ConfigManager()

# [CAMBIO 3] IMPRIMIMOS EL ID DE MEMORIA PARA TU DEFENSA
# Este ID debe ser IGUAL al que aparece en los logs de database.py
print(f"🚀 DEBUG [app.py]: ConfigManager ID de memoria al iniciar: {id(app_config)}", flush=True)

# [CAMBIO 4] Usamos la configuración centralizada en lugar de os.getenv sueltos
app.secret_key = app_config.secret_key

# --- FUNCIÓN PARA ARREGLAR CARACTERES (UTF-8) ---
def fix_encoding(data):
    """
    Recorre recursivamente los datos y arregla los strings rotos (Mojibake).
    """
    if isinstance(data, str):
        try: return data.encode('latin-1').decode('utf-8')
        except: return data
    elif isinstance(data, list): return [fix_encoding(i) for i in data]
    elif isinstance(data, dict): return {k: fix_encoding(v) for k, v in data.items()}
    return data

# --- UPDATE GENÉRICO ---
def generic_update(table, id, data):
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        valid_data = {k: v for k, v in data.items() if k not in ['id', 'user_id', 'fecha_registro']}
        set_clause = ", ".join([f"{key} = %s" for key in valid_data.keys()])
        values = list(valid_data.values()); values.append(id)
        
        sql = f"UPDATE {table} SET {set_clause} WHERE id = %s"
        cursor.execute(sql, values)
        conn.commit()
        return jsonify({"success": True, "message": "Actualizado correctamente"})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 400
    finally:
        conn.close()

# --- LÓGICA CORE: OBTENER SERVICIOS ---
def get_services(table_name, agency_filter=None):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    
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
    return jsonify(fix_encoding(res))

# ==================== RUTAS: VUELOS ====================
@app.route('/api/vuelos', methods=['GET', 'POST'])
def vuelos():
    if request.method == 'GET':
        return get_services('vuelos', request.args.get('agency_id'))

    d = request.form; conn = get_db_connection(); cursor = conn.cursor()
    try:
        sql = """INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        vals = (d['codigo_vuelo'], d['origen_iata'], d['destino_iata'], d.get('aerolinea', 'Agencia'), d['clase_base'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['user_id']))
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True, "message": "Vuelo creado"}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/vuelos/<int:id>', methods=['PUT', 'DELETE'])
def vuelo_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM vuelos WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True, "message": "Eliminado"})
    return generic_update('vuelos', id, request.json)

# ==================== RUTAS: HOTELES ====================
@app.route('/api/hoteles', methods=['GET', 'POST'])
def hoteles():
    if request.method == 'GET':
        return get_services('hoteles', request.args.get('agency_id'))

    d = request.form
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        sql = "INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, tipo_habitacion, capacidad_max, user_id) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
        vals = (d['nombre'], d['ciudad'], int(d['estrellas']), float(d['precio_noche']), d.get('servicios', ''), d.get('tipo_habitacion', 'Estándar'), int(d.get('capacidad_max', 4)), int(d['user_id']))
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True, "message": "Hotel creado"}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/hoteles/<int:id>', methods=['PUT', 'DELETE'])
def hotel_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM hoteles WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True, "message": "Eliminado"})
    return generic_update('hoteles', id, request.json)

# ==================== RUTAS: AUTOBUSES ====================
@app.route('/api/autobuses', methods=['GET', 'POST'])
def autobuses():
    if request.method == 'GET':
        return get_services('rutas_autobus', request.args.get('agency_id'))

    d = request.form
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        sql = """INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        vals = (d['origen'], d['destino'], d.get('linea_autobus', 'Bus'), d['tipo_asiento'], float(d['precio']), d['fecha_salida'], d['fecha_llegada'], d['fecha_regreso_salida'], d['fecha_regreso_llegada'], int(d['user_id']))
        cursor.execute(sql, vals); conn.commit()
        return jsonify({"success": True, "message": "Ruta creada"}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/autobuses/<int:id>', methods=['PUT', 'DELETE'])
def bus_item(id):
    if request.method == 'DELETE':
        conn = get_db_connection(); cursor = conn.cursor()
        cursor.execute("DELETE FROM rutas_autobus WHERE id=%s", (id,)); conn.commit(); conn.close()
        return jsonify({"success": True, "message": "Eliminado"})
    return generic_update('rutas_autobus', id, request.json)

# ==================== USUARIOS & ADMIN ====================

@app.route('/api/registro', methods=['POST'])
def handle_user_registration(): # Nombre de función corregido
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        rol = d.get('rol', 'usuario')
        comercial = d.get('nombre_comercial') if rol == 'agencia' else None
        
        sql = "INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, nombre_comercial) VALUES (%s, %s, %s, %s, %s, %s)"
        vals = (d.get('nombre'), d.get('apellido'), d['email'], d['password'], rol, comercial) 
        
        cursor.execute(sql, vals); conn.commit(); return jsonify({"success": True, "message": "Registro exitoso"}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/login', methods=['POST'])
def login():
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, nombre, apellido, email, rol, nombre_comercial FROM usuarios WHERE email=%s AND password_hash=%s", (d.get('email'), d.get('password')))
    user = cursor.fetchone(); conn.close()
    if user: return jsonify({"success": True, "user": fix_encoding(user)})
    else: return jsonify({"success": False, "message": "Credenciales inválidas"}), 401

@app.route('/api/admin/crear_agencia', methods=['POST'])
def crear_agencia():
    d = request.json
    conn = get_db_connection(); cursor = conn.cursor()
    try:
        cursor.execute("INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) VALUES (%s, %s, 'agencia', %s)", (d['email'], d['password'], d['nombre_comercial']))
        conn.commit(); return jsonify({"success": True, "message": "Agencia creada"}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/admin/agencias', methods=['GET'])
def listar_agencias():
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, email, nombre_comercial, fecha_registro FROM usuarios WHERE rol='agencia' ORDER BY id DESC")
    res = cursor.fetchall(); conn.close()
    return jsonify(fix_encoding(res))

@app.route('/api/admin/agencias/<int:id>', methods=['PUT', 'DELETE'])
def admin_agencia_item(id):
    conn = get_db_connection()
    cursor = conn.cursor()
    
    if request.method == 'DELETE':
        try:
            cursor.execute("DELETE FROM usuarios WHERE id=%s AND rol='agencia'", (id,))
            conn.commit()
            
            if cursor.rowcount == 0:
                return jsonify({"success": False, "message": "Agencia no encontrada o no es agencia."}), 404
            
            return jsonify({"success": True, "message": "Agencia eliminada correctamente"})
        except Exception as e:
            if "Foreign key constraint" in str(e):
                 return jsonify({"success": False, "message": "No se puede eliminar: La agencia tiene servicios activos (vuelos, hoteles o rutas)."}), 400
            
            return jsonify({"success": False, "message": f"Error del servidor: {str(e)}"}), 500
        finally:
            conn.close()
    
    # PUT (Editar)
    data = request.json
    if 'password' in data:
        data['password_hash'] = data.pop('password')
        
    return generic_update('usuarios', id, data)

# ==================== RESERVAS ====================
@app.route('/api/reservas', methods=['POST'])
def crear_reserva():
    d = request.json; conn = get_db_connection(); cursor = conn.cursor()
    try:
        code = f"RES-{random.randint(10000, 99999)}"; dets = json.dumps(d.get('detalles', {}))
        sql = "INSERT INTO reservas (user_id, reservation_code, service_type, item_name, date_start, date_end, num_guests, details_json, total_price) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
        vals = (d['user_id'], code, d['service_type'], d['item_name'], d['date_start'], d['date_end'], d['num_guests'], dets, d['total_price'])
        cursor.execute(sql, vals); conn.commit(); return jsonify({"success": True, "code": code}), 201
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

@app.route('/api/mis_reservas/<int:uid>', methods=['GET'])
def mis_reservas(uid):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM reservas WHERE user_id=%s ORDER BY created_at DESC", (uid,))
    res = cursor.fetchall(); conn.close()
    fixed_res = fix_encoding(res)
    for r in fixed_res:
        if r.get('details_json') and isinstance(r['details_json'], str):
            try: r['detalles'] = json.loads(r['details_json'])
            except: r['detalles'] = {}
        else: r['detalles'] = {}
    return jsonify(fixed_res)

@app.route('/api/reservas/cancelar/<int:id>', methods=['POST'])
def cancelar_reserva(id):
    conn = get_db_connection(); cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT total_price FROM reservas WHERE id=%s", (id,))
        row = cursor.fetchone()
        if not row: return jsonify({"success": False, "message": "Reserva no encontrada"}), 404
        refund = float(row['total_price']) * 0.30
        cursor.execute("UPDATE reservas SET status='Cancelado', refund_amount=%s WHERE id=%s", (refund, id))
        conn.commit(); return jsonify({"success": True, "reembolso": refund})
    except Exception as e: return jsonify({"success": False, "message": str(e)}), 400
    finally: conn.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)