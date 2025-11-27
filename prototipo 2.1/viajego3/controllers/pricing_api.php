<?php
// controllers/pricing_api.php
// API para calcular el costo total de la reserva dinámicamente.

// Cargar dependencias necesarias
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/reservation_logic.php';

// Esta API debe ejecutarse solo cuando es llamada por un cliente (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

header("Content-Type: application/json");

$package_price = floatval($_POST['package_price'] ?? 0);
$hotel_price   = floatval($_POST['hotel_price'] ?? 0);
$adults        = intval($_POST['adults'] ?? 1);
$children      = intval($_POST['children'] ?? 0);
$start_date    = $_POST['start_date'] ?? "";
$end_date      = $_POST['end_date'] ?? "";

// USAR LA LÓGICA CENTRALIZADA
$result = calculateReservationTotal(
    $package_price, 
    $hotel_price, 
    $start_date, 
    $end_date, 
    $adults, 
    $children
);

if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode(["error" => $result['error']]);
    exit;
}

// Adaptar la salida para el JS existente
echo json_encode([
    "days" => $result['days'],
    "total" => $result['total'],
    "package" => $result['package'],
    "hotel" => $result['hotel_price'],
    "adults_fee" => $result['adults_fee'],
    "children_fee" => $result['children_fee']
]);
exit;