<?php
// controllers/home.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

// ------------------------------------------------------
//  MINI API INTERNA PARA CALCULAR PRECIOS EN TIEMPO REAL
// ------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'calc') {
    header("Content-Type: application/json");
    
    // Validar que todos los campos necesarios estén presentes
    $required = ['package_price', 'hotel_price', 'start_date', 'end_date'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            echo json_encode(["error" => "Faltan campos requeridos"]);
            exit;
        }
    }
    
    $package_price = floatval($_POST['package_price'] ?? 0);
    $hotel_price   = floatval($_POST['hotel_price'] ?? 0);
    $adults        = intval($_POST['adults'] ?? 1);
    $children      = intval($_POST['children'] ?? 0);
    $start_date    = $_POST['start_date'] ?? "";
    $end_date      = $_POST['end_date'] ?? "";

    $days = 0;
    $error = "";

    if ($start_date !== "" && $end_date !== "") {
        $start = strtotime($start_date);
        $end   = strtotime($end_date);
        $today = strtotime(date('Y-m-d'));
        
        if ($start === false || $end === false) {
            $error = "Fechas inválidas";
        } elseif ($start < $today) {
            $error = "La fecha de inicio no puede ser anterior al día actual";
        } elseif ($end < $start) {
            $error = "La fecha de fin no puede ser anterior a la de inicio";
        } else {
            $days = (($end - $start) / 86400) + 1;
            if ($days > 25) {
                $error = "Máximo 25 días permitidos";
                $days = 0;
            }
        }
    }

    if ($error) {
        echo json_encode(["error" => $error]);
        exit;
    }

    $total = 
        $package_price +
        ($hotel_price * $days) +
        ($adults * 500) +
        ($children * 300);

    echo json_encode([
        "days" => $days,
        "total" => number_format($total, 2, ".", ""),
        "package" => $package_price,
        "hotel" => $hotel_price,
        "adults_fee" => ($adults * 500),
        "children_fee" => ($children * 300)
    ]);
    exit;
}

// Obtener paquetes y hoteles de la base de datos
$packages = [];
$hotels = [];

try {
    $stmt = $pdo->query("SELECT * FROM packages");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages_error = "Error al cargar paquetes.";
}

try {
    $stmt = $pdo->query("SELECT * FROM hotels");
    $hotels = $stmt->fetchAll();
} catch (PDOException $e) {
    $hotels_error = "Error al cargar hoteles.";
}

// Cargar la vista
require_once __DIR__ . '/../views/home/index_view.php';