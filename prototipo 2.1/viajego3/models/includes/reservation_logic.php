<?php
// models/includes/reservation_logic.php
// Contiene la lógica de negocio central para calcular el precio total de una reserva.

// Asegura que las constantes de tarifas existan
if (!defined('ADULT_FEE')) {
    // Si no está definido, forzamos la carga de config.php
    require_once __DIR__ . '/config.php';
}

/**
 * Calcula el total de la reserva basado en paquetes, hotel y personas.
 * @param float $package_price Precio base del paquete.
 * @param float $hotel_price Precio por noche del hotel.
 * @param string $start_date Fecha de inicio (Y-m-d).
 * @param string $end_date Fecha de fin (Y-m-d).
 * @param int $adults Cantidad de adultos.
 * @param int $children Cantidad de niños.
 * @return array Contiene el total, días y error (si existe).
 */
function calculateReservationTotal(
    float $package_price, 
    float $hotel_price, 
    string $start_date, 
    string $end_date, 
    int $adults, 
    int $children
): array {
    // --- VALIDACIONES DE FECHAS ---
    if ($start_date === "" || $end_date === "") {
        return ["error" => "Fechas requeridas."];
    }
    
    $start = strtotime($start_date);
    $end   = strtotime($end_date);
    $today = strtotime(date('Y-m-d'));
    
    if ($start === false || $end === false) {
        return ["error" => "Fechas inválidas"];
    } elseif ($start < $today) {
        return ["error" => "La fecha de inicio no puede ser anterior al día actual"];
    } elseif ($end < $start) {
        return ["error" => "La fecha de fin no puede ser anterior a la de inicio"];
    }
    
    // Cálculo de Días
    $days = (($end - $start) / 86400) + 1;
    if ($days > 25) {
        return ["error" => "Máximo 25 días permitidos"];
    }

    // --- CÁLCULO DE PRECIO USANDO CONSTANTES ---
    $adults_fee = $adults * ADULT_FEE;
    $children_fee = $children * CHILD_FEE;
    $hotel_total = $hotel_price * $days;

    $total = $package_price + $hotel_total + $adults_fee + $children_fee;

    return [
        "days" => $days,
        "total" => number_format($total, 2, ".", ""),
        "package" => $package_price,
        "hotel_total" => $hotel_total,
        "hotel_price" => $hotel_price,
        "adults_fee" => $adults_fee,
        "children_fee" => $children_fee
    ];
}