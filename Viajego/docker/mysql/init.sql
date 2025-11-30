USE viajego_db;

-- 1. TABLA DE AGENCIAS (6 entradas)
DROP TABLE IF EXISTS agencias;
CREATE TABLE agencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo_servicio VARCHAR(50) NOT NULL, -- 'Hoteles', 'Vuelos', 'Transporte', 'Mixto'
    contacto VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO agencias (id, nombre, tipo_servicio, contacto) VALUES 
(1, 'ViajeGO Official', 'Mixto', 'contacto@viajego.com'),
(2, 'Aerovía México', 'Vuelos', 'ventas@aerovia.com'),
(3, 'Sol Caribe Hotels', 'Hoteles', 'reservas@solcaribe.com'),
(4, 'ETN Select', 'Transporte', 'contacto@etn.com'),
(5, 'Mundo Aventura', 'Mixto', 'info@mundoaventura.com'),
(6, 'Hoteles del Sur', 'Hoteles', 'info@hotelessur.com');

-- 2. TABLA DE USUARIOS
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (nombre, apellido, email, password_hash, rol) 
VALUES ('Administrador', 'General', 'admin@viajego.com', '123456', 'agencia');


-- 3. TABLA DE HOTELES (6 entradas)
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
    agencia_id INT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id) ON DELETE SET NULL
);

INSERT INTO hoteles (nombre, ciudad, estrellas, precio_noche, servicios, tipo_habitacion, capacidad_max, agencia_id) VALUES
('Grand Oasis Cancún', 'Cancún', 5, 3500.00, 'Alberca, Wifi, Buffet', 'Estándar', 4, 3),
('Hotel Riu Guadalajara', 'Guadalajara', 4, 1800.50, 'Wifi, Gimnasio', 'Doble', 6, 3),
('Hotel Playa del Carmen', 'Playa del Carmen', 4, 2500.00, 'Alberca, Playa Privada', 'Suite', 10, 6),
('Posada Colonial Oaxaca', 'Oaxaca', 3, 950.00, 'Desayuno Incluido, Terraza', 'Estándar', 4, 6),
('Las Brisas Acapulco', 'Acapulco', 5, 4200.00, 'Vista al Mar, Club de Playa', 'Doble', 6, 3),
('Hotel Ejecutivo Monterrey', 'Monterrey', 4, 1500.00, 'Sala de Conferencias, Wifi', 'Estándar', 4, 5);


-- 4. TABLA DE VUELOS (6 entradas)
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
    agencia_id INT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id) ON DELETE SET NULL
);

INSERT INTO vuelos (codigo_vuelo, origen_iata, destino_iata, aerolinea, clase_base, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, agencia_id) VALUES
('GO101', 'Ciudad de México', 'Cancún', 'Aerovía México', 'Económica', 2500.50, '2026-03-10 08:00:00', '2026-03-10 11:00:00', '2026-03-15 18:00:00', '2026-03-15 21:00:00', 2),
('GO102', 'Monterrey', 'Cancún', 'Aerovía México', 'Económica', 2800.00, '2026-04-01 10:00:00', '2026-04-05 13:30:00', '2026-04-10 15:00:00', '2026-04-10 18:30:00', 2),
('VM300', 'Guadalajara', 'Los Ángeles', 'VivaMex', 'Ejecutiva', 6500.00, '2026-05-20 23:00:00', '2026-05-21 05:00:00', '2026-05-27 10:00:00', '2026-05-27 15:00:00', 5),
('A850', 'Cancún', 'Ciudad de México', 'Aerovía México', 'Económica', 1850.00, '2026-03-25 18:00:00', '2026-03-25 21:00:00', '2026-03-28 08:00:00', '2026-03-28 11:00:00', 2),
('MA200', 'Tijuana', 'Cancún', 'Mundo Aviación', 'Primera', 7000.00, '2026-06-15 12:00:00', '2026-06-15 17:00:00', '2026-06-20 18:00:00', '2026-06-20 23:00:00', 5),
('OAX10', 'Ciudad de México', 'Oaxaca', 'Aerovía México', 'Económica', 1200.00, '2026-07-01 14:00:00', '2026-07-01 15:30:00', '2026-07-04 17:00:00', '2026-07-04 18:30:00', 2);


-- 5. TABLA DE AUTOBUSES (6 entradas)
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
    agencia_id INT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id) ON DELETE SET NULL
);

INSERT INTO rutas_autobus (origen, destino, linea_autobus, tipo_asiento, precio, fecha_salida, fecha_llegada, fecha_regreso_salida, fecha_regreso_llegada, agencia_id) VALUES 
('CDMX', 'Acapulco', 'Estrella de Oro', 'Ejecutivo', 850.00, '2025-12-01 08:00:00', '2025-12-01 13:00:00', '2025-12-03 10:00:00', '2025-12-03 15:00:00', 1),
('Guadalajara', 'Puerto Vallarta', 'ETN Select', 'Lujo', 950.00, '2025-12-10 09:00:00', '2025-12-10 16:00:00', '2025-12-14 17:00:00', '2025-12-14 00:00:00', 4),
('Monterrey', 'San Luis Potosí', 'ETN Select', 'Estándar', 500.00, '2026-01-05 14:00:00', '2026-01-05 19:30:00', '2026-01-08 07:00:00', '2026-01-08 12:30:00', 4),
('Oaxaca', 'Puerto Escondido', 'ViajeGO Official', 'Ejecutivo', 600.00, '2026-02-15 06:00:00', '2026-02-15 13:00:00', '2026-02-19 14:00:00', '2026-02-19 21:00:00', 1),
('CDMX', 'Monterrey', 'ETN Select', 'Lujo', 1200.00, '2026-03-01 22:00:00', '2026-03-02 08:00:00', '2026-03-05 15:00:00', '2026-03-06 01:00:00', 4),
('Guadalajara', 'CDMX', 'ViajeGO Official', 'Estándar', 750.00, '2026-04-10 11:00:00', '2026-04-10 18:00:00', '2026-04-14 19:00:00', '2026-04-15 02:00:00', 1);


-- 6. TABLA DE PAQUETES (6 entradas - Con datos JSON de ejemplo)
DROP TABLE IF EXISTS paquetes;
CREATE TABLE paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    duracion VARCHAR(100) NOT NULL,
    fecha_salida DATETIME NOT NULL,
    fecha_regreso DATETIME NOT NULL,
    
    transporte_json JSON, 
    hotel_json JSON,     
    
    precio_total DECIMAL(10, 2) NOT NULL,
    servicios_incluidos TEXT,
    imagen_url VARCHAR(255) NULL, 
    agencia_id INT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id) ON DELETE SET NULL
);

INSERT INTO paquetes (titulo, destino, duracion, fecha_salida, fecha_regreso, precio_total, servicios_incluidos, transporte_json, hotel_json, agencia_id) VALUES
(
    'Cancún Lujo 5 Días', 'Cancún', '5 Días / 4 Noches', '2026-03-10 08:00:00', '2026-03-15 21:00:00', 16000.00, 
    'Vuelo redondo, Traslados, Desayuno buffet',
    '{
        "t_type": "vuelo",
        "t_empresa": "Aerovía México",
        "t_codigo": "GO101",
        "t_origen": "Ciudad de México",
        "t_destino": "Cancún",
        "t_ida": "2026-03-10 08:00",
        "t_regreso": "2026-03-15 18:00",
        "t_clase": "Ejecutiva",
        "t_equipaje": "Documentado 25kg"
    }',
    '{
        "h_nombre": "Grand Oasis Cancún",
        "h_ciudad": "Cancún",
        "h_habitacion": "Doble",
        "h_comidas": "Solo Desayuno",
        "h_servicios": "Alberca, Wifi, Buffet"
    }',
    5
),
(
    'GDL + LAX Aventura', 'Guadalajara - LAX', '8 Días / 7 Noches', '2026-05-20 23:00:00', '2026-05-27 15:00:00', 21500.00, 
    'Vuelo, 7 Noches de Alojamiento',
    '{
        "t_type": "vuelo",
        "t_empresa": "VivaMex",
        "t_codigo": "VM300",
        "t_origen": "Guadalajara",
        "t_destino": "Los Ángeles",
        "t_ida": "2026-05-20 23:00",
        "t_regreso": "2026-05-27 10:00",
        "t_clase": "Ejecutiva",
        "t_equipaje": "Solo mano"
    }',
    '{
        "h_nombre": "Hotel Riu Guadalajara",
        "h_ciudad": "Guadalajara",
        "h_habitacion": "Estándar",
        "h_comidas": "Sin alimentos",
        "h_servicios": "Wifi, Gimnasio"
    }',
    1
),
(
    'Oaxaca Cultural', 'Oaxaca', '4 Días / 3 Noches', '2026-02-15 06:00:00', '2026-02-19 21:00:00', 4800.00, 
    'Autobús de Lujo, Hospedaje, Tour Mezcal',
    '{
        "t_type": "bus",
        "t_empresa": "ViajeGO Official",
        "t_codigo": "Ruta 4",
        "t_origen": "Oaxaca",
        "t_destino": "Puerto Escondido",
        "t_ida": "2026-02-15 06:00",
        "t_regreso": "2026-02-19 14:00",
        "t_clase": "Ejecutivo"
    }',
    '{
        "h_nombre": "Posada Colonial Oaxaca",
        "h_ciudad": "Oaxaca",
        "h_habitacion": "Estándar",
        "h_comidas": "Desayuno Incluido",
        "h_servicios": "Terraza, Wifi"
    }',
    1
),
(
    'Playa Aventura PV', 'Puerto Vallarta', '5 Días / 4 Noches', '2025-12-10 09:00:00', '2025-12-14 00:00:00', 8500.00, 
    'Transporte Terrestre, Playa y Alberca',
    '{
        "t_type": "bus",
        "t_empresa": "ETN Select",
        "t_codigo": "Ruta 2",
        "t_origen": "Guadalajara",
        "t_destino": "Puerto Vallarta",
        "t_ida": "2025-12-10 09:00",
        "t_regreso": "2025-12-14 17:00",
        "t_clase": "Lujo"
    }',
    '{
        "h_nombre": "Hotel Playa del Carmen",
        "h_ciudad": "Playa del Carmen",
        "h_habitacion": "Suite",
        "h_comidas": "Todo Incluido",
        "h_servicios": "Playa Privada, Alberca"
    }',
    5
),
(
    'Noche Regia Mty', 'Monterrey', '3 Días / 2 Noches', '2026-06-15 12:00:00', '2026-06-20 23:00:00', 12000.00, 
    'Vuelo, Hotel, Coche Renta (no incluido en precio)',
    '{
        "t_type": "vuelo",
        "t_empresa": "Mundo Aviación",
        "t_codigo": "MA200",
        "t_origen": "Tijuana",
        "t_destino": "Cancún",
        "t_ida": "2026-06-15 12:00",
        "t_regreso": "2026-06-20 18:00",
        "t_clase": "Primera"
    }',
    '{
        "h_nombre": "Hotel Ejecutivo Monterrey",
        "h_ciudad": "Monterrey",
        "h_habitacion": "Estándar",
        "h_comidas": "Sin alimentos",
        "h_servicios": "Sala de Conferencias, Wifi"
    }',
    1
),
(
    'Acapulco Romántico', 'Acapulco', '4 Días / 3 Noches', '2026-03-10 08:00:00', '2026-03-15 21:00:00', 10500.00, 
    'Vuelo, Suite con Vista al Mar, Cena Romántica',
    '{
        "t_type": "vuelo",
        "t_empresa": "Aerovía México",
        "t_codigo": "GO101",
        "t_origen": "Ciudad de México",
        "t_destino": "Cancún",
        "t_ida": "2026-03-10 08:00",
        "t_regreso": "2026-03-15 18:00",
        "t_clase": "Ejecutiva",
        "t_equipaje": "Solo mano"
    }',
    '{
        "h_nombre": "Las Brisas Acapulco",
        "h_ciudad": "Acapulco",
        "h_habitacion": "Suite",
        "h_comidas": "Todo Incluido",
        "h_servicios": "Vista al Mar, Club de Playa"
    }',
    5
);

-- 7. TABLA DE RESERVAS
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