ALTER DATABASE viajego_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE viajego_db;

-- ==========================================
-- 1. TABLA DE USUARIOS
-- ==========================================
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'usuario', -- 'usuario', 'agencia', 'admin'
    nombre VARCHAR(100) NULL,
    apellido VARCHAR(100) NULL,
    nombre_comercial VARCHAR(150) NULL, -- Solo para agencias
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --- DATOS DE PRUEBA: USUARIOS Y AGENCIAS ---

-- ID 1: Super Admin
INSERT INTO usuarios (email, password_hash, rol, nombre) 
VALUES ('admin@viajego.com', 'admin123', 'admin', 'Super Admin');

-- ID 2: Agencia de Vuelos "Aerovía México"
INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) 
VALUES ('ventas@aerovia.com', '123456', 'agencia', 'Aerovía México');

-- ID 3: Agencia de Hoteles "Sol Caribe"
INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) 
VALUES ('reservas@solcaribe.com', '123456', 'agencia', 'Sol Caribe Hotels');

-- ID 4: Agencia de Autobuses "ETN Turistar"
INSERT INTO usuarios (email, password_hash, rol, nombre_comercial) 
VALUES ('contacto@etn.com', '123456', 'agencia', 'ETN Turistar');

-- ID 5: Usuario Turista
INSERT INTO usuarios (email, password_hash, rol, nombre, apellido) 
VALUES ('cliente@gmail.com', '123456', 'usuario', 'Juan', 'Pérez');


-- ==========================================
-- 2. TABLA DE HOTELES
-- ==========================================
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

-- Datos: Hoteles (Subidos por "Sol Caribe" - ID 3)
INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, tipo_habitacion, user_id) VALUES
('Grand Oasis Cancún', 'Cancún', 5, 3500.00, 'Alberca, Wifi, Buffet, Playa Privada', 'Doble', 3),
('Hotel Riu Plaza', 'Guadalajara', 4, 1800.50, 'Wifi, Gimnasio, Desayuno', 'Estándar', 3),
('Fiesta Americana', 'Ciudad de México', 5, 4200.00, 'Spa, Centro de Negocios, Gym', 'Suite', 3),
('Posada Real', 'Puerto Vallarta', 3, 1200.00, 'Wifi, Alberca', 'Estándar', 3),
('Hotel Misión', 'Monterrey', 4, 2100.00, 'Estacionamiento, Wifi, Restaurante', 'Doble', 3);


-- ==========================================
-- 3. TABLA DE VUELOS
-- ==========================================
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

-- Datos: Vuelos (Subidos por "Aerovía México" - ID 2)
INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) VALUES
('AM100', 'CDMX', 'CUN', 'Aerovía', 'Económica', 2500.50, '2026-03-10 08:00:00', '2026-03-10 11:00:00', '2026-03-15 18:00:00', '2026-03-15 21:00:00', 2),
('AM202', 'MTY', 'CUN', 'Aerovía', 'Económica', 2100.00, '2026-04-01 09:00:00', '2026-04-01 12:30:00', '2026-04-05 14:00:00', '2026-04-05 17:30:00', 2),
('AM505', 'GDL', 'TIJ', 'Aerovía', 'Ejecutiva', 3200.00, '2026-05-20 07:00:00', '2026-05-20 09:00:00', '2026-05-25 10:00:00', '2026-05-25 12:00:00', 2),
('AM800', 'CUN', 'CDMX', 'Aerovía', 'Primera', 4500.00, '2026-06-15 16:00:00', '2026-06-15 19:00:00', '2026-06-20 08:00:00', '2026-06-20 11:00:00', 2);


-- ==========================================
-- 4. TABLA DE AUTOBUSES
-- ==========================================
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

-- Datos: Autobuses (Subidos por "ETN Turistar" - ID 4)
INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, user_id) VALUES 
('CDMX', 'Acapulco', 'ETN', 'Lujo', 850.00, '2025-12-01 08:00:00', '2025-12-01 13:00:00', '2025-12-03 10:00:00', '2025-12-03 15:00:00', 4),
('Guadalajara', 'Puerto Vallarta', 'ETN', 'Ejecutivo', 950.00, '2025-12-10 09:00:00', '2025-12-10 14:00:00', '2025-12-15 11:00:00', '2025-12-15 16:00:00', 4),
('Monterrey', 'Mazatlán', 'ETN', 'Estándar', 1200.00, '2026-01-05 20:00:00', '2026-01-06 06:00:00', '2026-01-10 18:00:00', '2026-01-11 04:00:00', 4);


-- ==========================================
-- 5. TABLA DE RESERVAS
-- ==========================================
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