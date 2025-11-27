<?php
// controllers/process_reservation.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';
require_once __DIR__ . '/../models/includes/reservation_logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('views/index.php');
if (!isLoggedIn()) redirect('views/index.php#reservation');

// Datos Base
$uid = $_SESSION['user_id'];
$type = $_POST['type'];
$total = floatval($_POST['final_price']); // Precio calculado por JS (o validado en backend idealmente)

// Datos de IDs
$pid = intval($_POST['package_id'] ?? 0);
$hid = intval($_POST['hotel_id'] ?? 0);
$fid = intval($_POST['flight_id'] ?? 0);
$bid = intval($_POST['bus_id'] ?? 0);

// DATOS DE PERSONALIZACIÓN (NUEVOS)
$room_type = $_POST['room_type'] ?? NULL;
$board_basis = $_POST['board_basis'] ?? NULL;
$travel_class = $_POST['travel_class'] ?? NULL;

// Manejo de Extras (Array a String)
$extras_arr = $_POST['extras'] ?? [];
$extras_str = is_array($extras_arr) ? implode(", ", array_filter($extras_arr)) : $extras_arr;

if ($total <= 0 || empty($_POST['card_number'])) {
    $_SESSION['reservation_error'] = "Error en el pago o datos inválidos.";
    redirect('views/index.php');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (user_id, package_id, hotel_id, flight_id, bus_id, start_date, end_date, adults, children, total_price, status, 
         room_type, board_basis, travel_class, extras, created_at) 
        VALUES 
        (:uid, :pid, :hid, :fid, :bid, :start, :end, :ad, :ch, :total, 'paid', 
         :room, :board, :class, :extras, NOW())
    ");

    $stmt->execute([
        'uid' => $uid,
        'pid' => ($pid > 0 ? $pid : null),
        'hid' => ($hid > 0 ? $hid : null),
        'fid' => ($fid > 0 ? $fid : null),
        'bid' => ($bid > 0 ? $bid : null),
        'start' => $_POST['start_date'],
        'end' => $_POST['end_date'],
        'ad' => $_POST['adults'],
        'ch' => $_POST['children'],
        'total' => $total,
        // Nuevos Campos
        'room' => $room_type,
        'board' => $board_basis,
        'class' => $travel_class,
        'extras' => $extras_str
    ]);

    $id = $pdo->lastInsertId();
    $_SESSION['reservation_success'] = "¡Pago Exitoso! <a href='".BASE_URL."controllers/voucher.php?id=$id' target='_blank' class='btn-primary'>Descargar Voucher</a>";
    redirect('views/index.php#reservation');

} catch (PDOException $e) {
    $_SESSION['reservation_error'] = "Error DB: " . $e->getMessage();
    redirect('views/index.php');
}