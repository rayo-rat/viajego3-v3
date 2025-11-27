<?php
// api/admin_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

// Verificar que sea admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// =================== LÓGICA DE SUBIDA DE ARCHIVOS ===================
function handleFileUpload(string $fileKey): ?string
{
    // Verifica si el archivo fue subido y no tiene errores
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileKey];
        // ROOT_PATH es la ruta absoluta al directorio superior (viajego3/)
        $target_dir = ROOT_PATH . 'assets/images/';
        
        // Generar un nombre de archivo único para evitar colisiones
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('pkg_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Validar tipo MIME (seguridad básica)
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowed_mime)) {
            // Eliminar archivo temporal si es inválido
            @unlink($file['tmp_name']); 
            throw new Exception("Tipo de archivo no permitido: solo JPG, PNG, GIF.");
        }
        
        // Validar tamaño (máx 5MB)
        if ($file['size'] > 5000000) {
            throw new Exception("El archivo es demasiado grande (Máx 5MB).");
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $new_filename; // Retornar solo el nombre para guardar en BD
        } else {
            throw new Exception("Error al mover el archivo subido. Permisos de escritura insuficientes.");
        }
    }
    return null; // No hay archivo subido
}
// =============================================================

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // ==================== AGENCIAS ====================
        case 'get_agencies':
            $stmt = $pdo->query("SELECT * FROM travel_agencies ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_agency':
            $data = json_decode(file_get_contents('php://input'), true);
            $api_key = bin2hex(random_bytes(32));
            
            $stmt = $pdo->prepare("
                INSERT INTO travel_agencies (name, description, contact_email, contact_phone, address, api_key, created_at)
                VALUES (:name, :description, :email, :phone, :address, :api_key, NOW())
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'email' => $data['contact_email'] ?? null,
                'phone' => $data['contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'api_key' => $api_key
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Agencia creada exitosamente',
                'id' => $pdo->lastInsertId(),
                'api_key' => $api_key
            ]);
            break;

        case 'update_agency':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE travel_agencies 
                SET name = :name, description = :description, contact_email = :email,
                    contact_phone = :phone, address = :address, is_active = :is_active
                WHERE id = :id
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'email' => $data['contact_email'] ?? null,
                'phone' => $data['contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'id' => $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Agencia actualizada']);
            break;

        case 'delete_agency':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM travel_agencies WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Agencia eliminada']);
            break;

        
        // ==================== PAQUETES (CRUD CON FILE UPLOAD) ====================
        case 'get_packages':
            $stmt = $pdo->query("SELECT * FROM packages ORDER BY id DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_package':
            // Recibimos de $_POST (ya que viene con el archivo multipart/form-data)
            $data = $_POST;
            $new_image = handleFileUpload('image');
            
            if (!$new_image) {
                // Devolvemos 400 si la imagen es obligatoria en creación y falta
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'La imagen del paquete es obligatoria.']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO packages (name, description, image_url, base_price)
                VALUES (:name, :description, :image_url, :base_price)
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $new_image,
                'base_price' => $data['base_price']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Paquete creado exitosamente']);
            break;
            
        case 'update_package':
            // Recibimos de $_POST (ya que viene con el archivo multipart/form-data)
            $data = $_POST;
            $id = intval($data['id']);

            // 1. Manejar subida de archivo (puede ser null)
            $new_image = handleFileUpload('image');
            
            // 2. Si NO se subió un archivo nuevo, usamos el valor del campo oculto (current_image_url)
            if (!$new_image) {
                $new_image = $data['current_image_url'] ?? null;
            }

            $stmt = $pdo->prepare("
                UPDATE packages 
                SET name = :name, description = :description, image_url = :image_url, base_price = :base_price
                WHERE id = :id
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $new_image,
                'base_price' => $data['base_price'],
                'id' => $id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Paquete actualizado exitosamente']);
            break;

        case 'delete_package':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM packages WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Paquete eliminado']);
            break;
        
        
        // ==================== AUTOBUSES ====================
        case 'get_buses':
            $agency_id = $_GET['agency_id'] ?? null;
            $sql = "SELECT b.*, a.name as agency_name FROM bus_services b 
                    LEFT JOIN travel_agencies a ON b.agency_id = a.id";
            if ($agency_id) {
                $sql .= " WHERE b.agency_id = :agency_id";
            }
            $sql .= " ORDER BY b.created_at DESC"; 
            
            $stmt = $pdo->prepare($sql);
            if ($agency_id) {
                $stmt->execute(['agency_id' => $agency_id]);
            } else {
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_bus':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                INSERT INTO bus_services (
                    agency_id, company_name, bus_type, route_origin, route_destination,
                    departure_time, arrival_time, price_per_seat, total_seats, amenities, created_at
                ) VALUES (
                    :agency_id, :company_name, :bus_type, :origin, :destination,
                    :departure, :arrival, :price, :seats, :amenities, NOW()
                )
            ");
            
            $stmt->execute([
                'agency_id' => $data['agency_id'],
                'company_name' => $data['company_name'],
                'bus_type' => $data['bus_type'],
                'origin' => $data['route_origin'],
                'destination' => $data['route_destination'],
                'departure' => $data['departure_time'],
                'arrival' => $data['arrival_time'],
                'price' => $data['price_per_seat'],
                'seats' => $data['total_seats'],
                'amenities' => json_encode($data['amenities'] ?? [])
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Servicio de autobús creado',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'update_bus':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE bus_services SET
                    company_name = :company_name, bus_type = :bus_type,
                    route_origin = :origin, route_destination = :destination,
                    departure_time = :departure, arrival_time = :arrival,
                    price_per_seat = :price, total_seats = :seats,
                    amenities = :amenities, is_active = :is_active
                WHERE id = :id
            ");
            
            $stmt->execute([
                'company_name' => $data['company_name'],
                'bus_type' => $data['bus_type'],
                'origin' => $data['route_origin'],
                'destination' => $data['route_destination'],
                'departure' => $data['departure_time'],
                'arrival' => $data['arrival_time'],
                'price' => $data['price_per_seat'],
                'seats' => $data['total_seats'],
                'amenities' => json_encode($data['amenities'] ?? []),
                'is_active' => $data['is_active'] ?? 1,
                'id' => $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Autobús actualizado']);
            break;

        case 'delete_bus':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM bus_services WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Autobús eliminado']);
            break;

        // ==================== VUELOS ====================
        case 'get_flights':
            $agency_id = $_GET['agency_id'] ?? null;
            $sql = "SELECT f.*, a.name as agency_name FROM flight_services f 
                    LEFT JOIN travel_agencies a ON f.agency_id = a.id";
            if ($agency_id) {
                $sql .= " WHERE f.agency_id = :agency_id";
            }
            $sql .= " ORDER BY f.departure_time DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($agency_id) {
                $stmt->execute(['agency_id' => $agency_id]);
            } else {
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_flight':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                INSERT INTO flight_services (
                    agency_id, airline_name, flight_number, aircraft_type, flight_class,
                    origin_airport, destination_airport, departure_time, arrival_time,
                    price_per_ticket, total_seats, baggage_allowance, created_at
                ) VALUES (
                    :agency_id, :airline, :flight_number, :aircraft, :class,
                    :origin, :destination, :departure, :arrival,
                    :price, :seats, :baggage, NOW()
                )
            ");
            
            $stmt->execute([
                'agency_id' => $data['agency_id'],
                'airline' => $data['airline_name'],
                'flight_number' => $data['flight_number'],
                'aircraft' => $data['aircraft_type'] ?? null,
                'class' => $data['flight_class'],
                'origin' => $data['origin_airport'],
                'destination' => $data['destination_airport'],
                'departure' => $data['departure_time'],
                'arrival' => $data['arrival_time'],
                'price' => $data['price_per_ticket'],
                'seats' => $data['total_seats'],
                'baggage' => $data['baggage_allowance'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vuelo creado exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'update_flight':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE flight_services SET
                    airline_name = :airline, flight_number = :flight_number,
                    aircraft_type = :aircraft, flight_class = :class,
                    origin_airport = :origin, destination_airport = :destination,
                    departure_time = :departure, arrival_time = :arrival,
                    price_per_ticket = :price, total_seats = :seats,
                    baggage_allowance = :baggage, is_active = :is_active
                WHERE id = :id
            ");
            
            $stmt->execute([
                'airline' => $data['airline_name'],
                'flight_number' => $data['flight_number'],
                'aircraft' => $data['aircraft_type'] ?? null,
                'class' => $data['flight_class'],
                'origin' => $data['origin_airport'],
                'destination' => $data['destination_airport'],
                'departure' => $data['departure_time'],
                'arrival' => $data['arrival_time'],
                'price' => $data['price_per_ticket'],
                'seats' => $data['total_seats'],
                'baggage' => $data['baggage_allowance'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'id' => $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Vuelo actualizado']);
            break;

        case 'delete_flight':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM flight_services WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Vuelo eliminado']);
            break;

        // ==================== HOTELES ====================
        case 'get_hotels':
            $agency_id = $_GET['agency_id'] ?? null;
            $sql = "SELECT h.*, a.name as agency_name FROM hotels h 
                    LEFT JOIN travel_agencies a ON h.agency_id = a.id";
            if ($agency_id) {
                $sql .= " WHERE h.agency_id = :agency_id";
            }
            $sql .= " ORDER BY h.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            if ($agency_id) {
                $stmt->execute(['agency_id' => $agency_id]);
            } else {
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_hotel':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                INSERT INTO hotels (name, location, description, price_per_night, stars, agency_id, created_at)
                VALUES (:name, :location, :description, :price, :stars, :agency_id, NOW())
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'location' => $data['location'],
                'description' => $data['description'] ?? null,
                'price' => $data['price_per_night'],
                'stars' => $data['stars'] ?? 3,
                'agency_id' => $data['agency_id'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Hotel creado exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'update_hotel':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE hotels SET
                    name = :name, location = :location, description = :description,
                    price_per_night = :price, stars = :stars, agency_id = :agency_id
                WHERE id = :id
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'location' => $data['location'],
                'description' => $data['description'] ?? null,
                'price' => $data['price_per_night'],
                'stars' => $data['stars'] ?? 3,
                'agency_id' => $data['agency_id'] ?? null,
                'id' => $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Hotel actualizado']);
            break;

        case 'delete_hotel':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Hotel eliminado']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}