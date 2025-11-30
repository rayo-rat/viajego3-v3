ALTER DATABASE viajego_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE viajego_db;

-- 1. TABLA DE USUARIOS
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'usuario', -- 'usuario', 'agencia', 'admin'
    
    -- Campos opcionales (ya no se piden en registro público)
    nombre VARCHAR(100) NULL,
    apellido VARCHAR(100) NULL,
    nombre_comercial VARCHAR(150) NULL, -- Solo para agencias
    
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --- USUARIOS PRE-CODIFICADOS ---

-- 1. EL SUPER ADMINISTRADOR (Dueño del sistema)
INSERT INTO usuarios (email, password_hash, rol, nombre) 
VALUES ('admin@viajego.com', 'admin123', 'admin', 'Super Admin');

-- 2. UNA AGENCIA DE PRUEBA (Creada por el admin)
INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) 
VALUES ('contacto@aeromex.com', '123456', 'agencia', 'Aerovía México');

-- 3. UN USUARIO NORMAL DE PRUEBA
INSERT INTO usuarios (email, password_hash, rol) 
VALUES ('cliente@gmail.com', '123456', 'usuario');


-- 2. TABLA DE HOTELES
DROP TABLE IF EXISTS hoteles; 
CREATE TABLE hoteles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    estrellas INT NOT NULL,
    precio_noche DECIMAL(10, 2) NOT NULL,
    servicios VARCHAR(255),
    tipo_habitacion VARCHAR(50) DEFAULT 'Estándar',
    capacidad_max INT DEFAULT 4,
    imagen_url VARCHAR(255) NULL, 
    user_id INT NOT NULL, 
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, user_id) VALUES
('Grand Oasis Cancún', 'Cancún', 5, 3500.00, 'Alberca, Wifi, Buffet', 2); -- Creado por Aerovía (ID 2)


-- 3. TABLA DE VUELOS
DROP TABLE IF EXISTS vuelos; 
CREATE TABLE vuelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_vuelo VARCHAR(10) NOT NULL,
    origen_iata VARCHAR(100) NOT NULL, 
    destino_iata VARCHAR(100) NOT NULL,
    aerolinea VARCHAR(100) NOT NULL,
    clase_base VARCHAR(50) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    asientos_disponibles INT NOT NULL DEFAULT 40,
    fecha_salida DATETIME NOT NULL,
    fecha_llegada DATETIME NOT NULL,
    fecha_regreso_salida DATETIME NOT NULL,
    fecha_regreso_llegada DATETIME NOT NULL,
    imagen_url VARCHAR(255) NULL, 
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) VALUES
('GO101', 'CDMX', 'CUN', 'Aerovía', 'Económica', 2500.50, '2026-03-10 08:00:00', '2026-03-10 11:00:00', '2026-03-15 18:00:00', '2026-03-15 21:00:00', 2);


-- 4. TABLA DE AUTOBUSES
DROP TABLE IF EXISTS rutas_autobus;
CREATE TABLE rutas_autobus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    linea_autobus VARCHAR(100),
    tipo_asiento VARCHAR(50),
    precio DECIMAL(10, 2) NOT NULL,
    asientos_disponibles INT DEFAULT 40,
    fecha_salida DATETIME NOT NULL,
    fecha_llegada DATETIME NOT NULL,
    fecha_regreso_salida DATETIME NOT NULL,
    fecha_regreso_llegada DATETIME NOT NULL,
    imagen_url VARCHAR(255) NULL, 
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) VALUES 
('CDMX', 'Acapulco', 'Estrella Oro', 'Ejecutivo', 850.00, '2025-12-01 08:00:00', '2025-12-01 13:00:00', '2025-12-03 10:00:00', '2025-12-03 15:00:00', 2);


-- 5. TABLA DE RESERVAS
DROP TABLE IF EXISTS reservas;
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reservation_code VARCHAR(50) NOT NULL UNIQUE,
    service_type VARCHAR(20) NOT NULL, 
    item_name VARCHAR(255) NOT NULL,
    date_start DATE NOT NULL,
    date_end DATE NOT NULL,
    num_guests INT DEFAULT 1,
    details_json JSON, 
    total_price DECIMAL(10, 2) NOT NULL,
    refund_amount DECIMAL(10, 2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'Confirmado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
);