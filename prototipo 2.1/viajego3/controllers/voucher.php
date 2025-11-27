<?php
// controllers/voucher.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

if (!isLoggedIn()) die("Acceso denegado.");
$id = intval($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, 
            p.name AS pkg_name, h.name AS htl_name, 
            f.airline_name, f.flight_number, f.departure_time,
            fd1.name as f_orig, fd2.name as f_dest,
            b.company_name, b.bus_type, b.departure_time as b_time,
            bd1.name as b_orig, bd2.name as b_dest
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN packages p ON r.package_id = p.id
        LEFT JOIN hotels h ON r.hotel_id = h.id
        LEFT JOIN flight_services f ON r.flight_id = f.id
        LEFT JOIN bus_services b ON r.bus_id = b.id
        LEFT JOIN destinations fd1 ON f.origin_id = fd1.id
        LEFT JOIN destinations fd2 ON f.destination_id = fd2.id
        LEFT JOIN destinations bd1 ON b.origin_id = bd1.id
        LEFT JOIN destinations bd2 ON b.destination_id = bd2.id
        WHERE r.id = :id AND r.user_id = :uid
    ");
    $stmt->execute(['id'=>$id, 'uid'=>$_SESSION['user_id']]);
    $res = $stmt->fetch();
    if (!$res) die("Voucher no encontrado.");
} catch (PDOException $e) { die("Error DB"); }

class PDF extends FPDF {
    function Header() {
        $this->SetFillColor(52, 152, 219); $this->Rect(0, 0, 210, 35, 'F'); 
        $this->SetTextColor(255); $this->SetFont('Arial', 'B', 24);
        $this->SetXY(10, 10); $this->Cell(0, 10, "ViajeGO", 0, 1);
        $this->SetFont('Arial', '', 10); $this->SetXY(10, 20); $this->Cell(0, 10, "COMPROBANTE DE VIAJE", 0, 1);
        $this->Ln(15);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetMargins(15, 20, 15);
$pdf->SetY(45);

// Cliente
$pdf->SetFont('Arial', 'B', 12); $pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, "DATOS DEL PASAJERO", 0, 1);
$pdf->Line(15, 55, 195, 55);
$pdf->SetFont('Arial', '', 10); $pdf->Ln(2);
$pdf->Cell(20, 8, "Nombre:", 0, 0); $pdf->Cell(60, 8, utf8_decode($res['username']), 0, 1);
$pdf->Cell(20, 8, "Folio:", 0, 0); $pdf->Cell(0, 8, "#" . $id, 0, 1);
$pdf->Ln(5);

// Detalles
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "DETALLE DE SERVICIOS", 0, 1);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(5);

$pdf->SetFillColor(236, 240, 241);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(90, 8, "Concepto", 1, 0, 'L', true);
$pdf->Cell(60, 8, "Detalle", 1, 0, 'C', true);
$pdf->Cell(30, 8, "Info", 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);
$rows = [];

if ($res['flight_id']) {
    $rows[] = ["Vuelo: " . $res['airline_name'], date('d/m H:i', strtotime($res['departure_time'])), "Pax: " . ($res['adults']+$res['children'])];
    $rows[] = ["Ruta: " . $res['f_orig'] . " > " . $res['f_dest'], "Clase: " . $res['selected_class'], "Maletas: " . $res['extra_baggage']];
    $rows[] = ["Preferencias:", "Asiento: " . $res['seat_preference'], "-"];
} elseif ($res['package_id']) {
    $rows[] = ["Paquete: " . $res['pkg_name'], date('d/m', strtotime($res['start_date'])) . " - " . date('d/m', strtotime($res['end_date'])), "Pax: " . ($res['adults']+$res['children'])];
    $rows[] = ["Hotel: " . $res['htl_name'], "Todo Incluido", "-"];
}

foreach ($rows as $r) {
    $pdf->Cell(90, 8, utf8_decode($r[0]), 1, 0);
    $pdf->Cell(60, 8, utf8_decode($r[1]), 1, 0, 'C');
    $pdf->Cell(30, 8, utf8_decode($r[2]), 1, 1, 'C');
}

$pdf->Ln(10);
$pdf->SetFillColor(46, 204, 113); $pdf->SetTextColor(255); $pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(120, 10, "", 0, 0);
$pdf->Cell(30, 10, "TOTAL:", 1, 0, 'R', true);
$pdf->Cell(30, 10, "$" . number_format($res['total_price'], 2), 1, 1, 'C', true);

$pdf->Output('D', "Voucher.pdf");
?>