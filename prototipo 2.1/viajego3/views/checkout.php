<?php
// views/checkout.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';
require_once __DIR__ . '/../models/includes/reservation_logic.php';

if (!isLoggedIn()) {
    redirect('views/index.php');
}

// 1. Determinar origen de datos
$booking_type = $_GET['type'] ?? (isset($_POST['package']) ? 'package' : null);
$booking_id = intval($_GET['id'] ?? 0);

$total_price = 0.00;
$base_price_js = 0.00;
$item_details = [];
$view_title = "Resumen de tu Viaje";
$view_desc = "Personaliza y confirma tu reserva.";
$view_agency = "ViajeGO";
$view_includes = [];

// Valores por defecto
$adults = isset($_REQUEST['adults']) ? intval($_REQUEST['adults']) : 1;
$children = isset($_REQUEST['children']) ? intval($_REQUEST['children']) : 0;
$total_pax = $adults + $children;

// Fechas iniciales (hoy y regreso sugerido en 1 semana)
$start_date = $_REQUEST['start_date'] ?? date('Y-m-d');
$end_date = $_REQUEST['end_date'] ?? date('Y-m-d', strtotime('+7 day'));

// IDs para el form
$package_id = 0; $hotel_id = 0; $flight_id = 0; $bus_id = 0;

try {
    // --- CASO 1: PAQUETE ---
    if ($booking_type === 'package') {
        $package_id = intval($_POST['package'] ?? 0);
        $hotel_id = intval($_POST['hotel'] ?? 0);
        
        if (!$package_id || !$hotel_id) throw new Exception("Faltan datos del paquete.");
        
        $stmt = $pdo->prepare("SELECT p.*, a.name as agency_name FROM packages p JOIN travel_agencies a ON p.agency_id = a.id WHERE p.id = :id");
        $stmt->execute(['id' => $package_id]);
        $package = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
        $stmt->execute(['id' => $hotel_id]);
        $hotel = $stmt->fetch();

        $res = calculateReservationTotal($package['base_price'], $hotel['price_per_night'], $start_date, $end_date, $adults, $children);
        $total_price = $res['total'];
        $base_price_js = $total_price;
        
        $view_title = $package['name'];
        $view_desc = $package['description'];
        $view_agency = $package['agency_name'];
        $view_includes = [
            ['icon' => 'plane', 'title' => 'Transporte', 'text' => $package['includes_transport']],
            ['icon' => 'hotel', 'title' => 'Alojamiento', 'text' => $package['includes_lodging']],
            ['icon' => 'star', 'title' => 'Extras', 'text' => $package['includes_extras']]
        ];

    // --- CASO 2: VUELO (PERSONALIZABLE) ---
    } elseif ($booking_type === 'flight' && $booking_id > 0) {
        $flight_id = $booking_id;
        
        $stmt = $pdo->prepare("
            SELECT f.*, d1.name as origin, d2.name as destination 
            FROM flight_services f
            JOIN destinations d1 ON f.origin_id = d1.id
            JOIN destinations d2 ON f.destination_id = d2.id
            WHERE f.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $flight_id]);
        $item = $stmt->fetch();

        if (!$item) throw new Exception("Vuelo no encontrado.");

        // Precio base unitario
        $ticket_base = floatval($item['price_per_ticket']);
        
        // Cálculo inicial
        $subtotal = $ticket_base * $total_pax;
        $fees = ($adults * ADULT_FEE) + ($children * CHILD_FEE);
        $total_price = $subtotal + $fees;
        $base_price_js = $total_price;

        $view_title = "Vuelo: " . $item['origin'] . " ➝ " . $item['destination'];
        $view_desc = "Operado por: " . $item['airline_name'];
        $view_agency = $item['airline_name']; 
        
        // No llenamos view_includes aquí, usaremos el configurador

    // --- CASO 3: HOTEL ---
    } elseif ($booking_type === 'hotel' && $booking_id > 0) {
        $hotel_id = $booking_id;
        $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
        $stmt->execute(['id' => $hotel_id]);
        $item = $stmt->fetch();
        
        $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
        if($days < 1) $days = 1;
        $total_price = floatval($item['price_per_night']) * $days;
        $base_price_js = $total_price;
        
        $view_title = "Hotel: " . $item['name'];
        $view_desc = $item['description'];
        $view_includes = [['icon' => 'bed', 'title' => 'Estancia', 'text' => "$days Noches"]];

    // --- CASO 4: BUS ---
    } elseif ($booking_type === 'bus' && $booking_id > 0) {
        $bus_id = $booking_id;
        $stmt = $pdo->prepare("SELECT b.*, d1.name as origin, d2.name as destination FROM bus_services b JOIN destinations d1 ON b.origin_id = d1.id JOIN destinations d2 ON b.destination_id = d2.id WHERE b.id = :id");
        $stmt->execute(['id' => $bus_id]);
        $item = $stmt->fetch();
        
        $price = floatval($item['price_per_seat']);
        $total_price = ($price * $total_pax) + ($adults * ADULT_FEE) + ($children * CHILD_FEE);
        $base_price_js = $total_price;
        
        $view_title = "Bus: " . $item['origin'] . " ➝ " . $item['destination'];
        $view_desc = $item['company_name'];
        $view_includes = [['icon' => 'clock', 'title' => 'Salida', 'text' => $item['departure_time']]];
    }

} catch (Exception $e) {
    $_SESSION['reservation_error'] = $e->getMessage();
    redirect('views/index.php#reservation');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Viaje - ViajeGO</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; max-width: 1100px; margin: 40px auto; padding: 20px; }
        .card-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; color: var(--primary); font-size: 1.2rem; }
        .price-total { display: flex; justify-content: space-between; padding: 15px 0; font-size: 1.4rem; font-weight: 800; color: var(--dark); border-top: 2px solid #eee; margin-top: 10px; }
        .provider-badge { background: #eef2ff; color: var(--primary); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
        /* Estilos para opciones de vuelo */
        .flight-options { margin-top: 20px; }
        .option-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .option-card { flex: 1; border: 1px solid #ddd; padding: 12px; border-radius: 8px; cursor: pointer; transition: 0.2s; position: relative; }
        .option-card:hover { border-color: var(--primary); background: #f8fafc; }
        .option-card input[type="radio"] { margin-right: 8px; }
        .option-price-tag { font-size: 0.8rem; color: var(--success); font-weight: bold; display: block; margin-top: 4px; }
        
        @media(max-width: 768px) { .checkout-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar" style="position: sticky;">
        <div class="container">
            <h1 class="logo">Viaje<span>GO</span> Checkout</h1>
            <a href="<?= BASE_URL ?>views/index.php" style="color: white; text-decoration: none;">&larr; Volver</a>
        </div>
    </nav>

    <form method="POST" action="<?= BASE_URL ?>controllers/process_reservation.php" id="payment-form">
        <!-- DATOS OCULTOS -->
        <input type="hidden" name="type" value="<?= e($booking_type) ?>">
        <input type="hidden" name="package_id" value="<?= e($package_id) ?>">
        <input type="hidden" name="hotel_id" value="<?= e($hotel_id) ?>">
        <input type="hidden" name="flight_id" value="<?= e($flight_id) ?>">
        <input type="hidden" name="bus_id" value="<?= e($bus_id) ?>">
        <input type="hidden" name="adults" value="<?= e($adults) ?>">
        <input type="hidden" name="children" value="<?= e($children) ?>">
        <input type="hidden" name="base_total_ref" value="<?= e($total_price) ?>"> 

        <div class="checkout-layout">
            <!-- IZQUIERDA: CONFIGURACIÓN -->
            <div class="details-col">
                <div class="card-box">
                    <div class="card-header">
                        <h3><i class="fas fa-sliders-h"></i> Configura tu Viaje</h3>
                        <span class="provider-badge"><?= e($view_agency) ?></span>
                    </div>
                    <h2 style="margin-top: 0;"><?= e($view_title) ?></h2>
                    <p style="color: #666;"><?= e($view_desc) ?></p>

                    <?php if($booking_type === 'flight'): ?>
                        <!-- ✈️ CONFIGURADOR DE VUELO (ESPAÑOL) -->
                        <div class="flight-options">
                            
                            <!-- FECHAS (IDA Y VUELTA) -->
                            <label><strong><i class="fas fa-calendar-alt"></i> Fechas de Vuelo:</strong></label>
                            <div class="option-row">
                                <div class="form-group" style="flex:1">
                                    <small>Salida</small>
                                    <input type="date" name="start_date" value="<?= e($start_date) ?>" min="<?= date('Y-m-d') ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                                </div>
                                <div class="form-group" style="flex:1">
                                    <small>Regreso</small>
                                    <input type="date" name="end_date" value="<?= e($end_date) ?>" min="<?= date('Y-m-d') ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom:15px;">
                                <label><strong><i class="fas fa-clock"></i> Horario Preferido:</strong></label>
                                <select name="flight_time" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                                    <option value="06:00">06:00 AM - Madrugada</option>
                                    <option value="09:30">09:30 AM - Mañana</option>
                                    <option value="14:00">02:00 PM - Tarde</option>
                                    <option value="19:45">07:45 PM - Noche</option>
                                </select>
                            </div>

                            <!-- TIPO DE VUELO -->
                            <label><strong><i class="fas fa-route"></i> Tipo de Vuelo:</strong></label>
                            <div class="option-row">
                                <label class="option-card">
                                    <input type="radio" name="flight_type" value="direct" checked onchange="updateTotal()">
                                    Directo <span class="option-price-tag">Más Rápido</span>
                                </label>
                                <label class="option-card">
                                    <input type="radio" name="flight_type" value="scale" onchange="updateTotal()">
                                    1 Escala <span class="option-price-tag" style="color: #e67e22;">Ahorras $200 p/p</span>
                                </label>
                            </div>

                            <!-- CLASE (EN ESPAÑOL) -->
                            <label><strong><i class="fas fa-crown"></i> Clase:</strong></label>
                            <div class="option-row">
                                <label class="option-card">
                                    <input type="radio" name="selected_class" value="Turista" checked onchange="updateTotal()">
                                    Turista <span class="option-price-tag">Incluida</span>
                                </label>
                                <label class="option-card">
                                    <input type="radio" name="selected_class" value="Ejecutiva" onchange="updateTotal()">
                                    Ejecutiva <span class="option-price-tag">+40% Tarifa</span>
                                </label>
                            </div>

                            <!-- PREFERENCIA DE ASIENTO -->
                            <label><strong><i class="fas fa-chair"></i> Asiento:</strong></label>
                            <div class="option-row">
                                <label class="option-card">
                                    <input type="radio" name="seat_preference" value="Ventana" checked> Ventana
                                </label>
                                <label class="option-card">
                                    <input type="radio" name="seat_preference" value="Pasillo"> Pasillo
                                </label>
                                <label class="option-card">
                                    <input type="radio" name="seat_preference" value="Medio"> Medio
                                </label>
                            </div>

                            <!-- EQUIPAJE -->
                            <label><strong><i class="fas fa-suitcase"></i> Equipaje Extra (25kg):</strong></label>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; display:flex; align-items:center; gap:15px;">
                                <input type="number" name="extra_baggage" id="extra_baggage" value="0" min="0" max="5" style="width:60px; padding:5px; font-size:1.2rem;" onchange="updateTotal()">
                                <span>maletas x <strong>$500 MXN</strong> cada una</span>
                            </div>

                        </div>
                    
                    <?php else: ?>
                        <!-- VISTA ESTÁNDAR PARA OTROS SERVICIOS -->
                        <ul class="inclusion-list" style="margin-top:20px; padding:0; list-style:none;">
                            <?php foreach($view_includes as $inc): ?>
                                <li style="padding:5px 0;"><i class="fas fa-<?= $inc['icon'] ?>" style="color: var(--success);"></i> <strong><?= e($inc['title']) ?>:</strong> <?= e($inc['text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden" name="start_date" value="<?= e($start_date) ?>">
                        <input type="hidden" name="end_date" value="<?= e($end_date) ?>">
                    <?php endif; ?>

                </div>
            </div>

            <!-- DERECHA: PAGO -->
            <div class="payment-col">
                <div class="card-box" style="position: sticky; top: 100px;">
                    <div class="card-header"><h3><i class="fas fa-receipt"></i> Desglose de Pago</h3></div>
                    
                    <div style="margin-bottom: 15px; color: #555; font-size: 0.9rem;">
                        <div>Pasajeros: <strong><?= $total_pax ?></strong></div>
                        <?php if($booking_type === 'flight'): ?>
                            <div id="summary-type">Tipo: Directo</div>
                            <div id="summary-class">Clase: Turista</div>
                            <div id="summary-bags">Equipaje Extra: 0</div>
                        <?php endif; ?>
                    </div>

                    <div class="price-total">
                        <span>Total:</span>
                        <span style="color: var(--primary);" id="display-total">$<?= number_format($total_price, 2) ?> MXN</span>
                    </div>
                    
                    <hr style="margin: 20px 0;">

                    <div class="form-group"><label>Titular</label><input type="text" name="card_name" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;"></div>
                    <div class="form-group"><label>Tarjeta</label><input type="text" name="card_number" id="cc-number" required placeholder="0000 0000 0000 0000" maxlength="19" inputmode="numeric" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;"></div>
                    <div style="display:flex; gap:10px;"><input type="text" name="expiry" placeholder="MM/AA" required style="flex:1; padding:10px;"><input type="text" name="cvv" placeholder="CVV" required style="flex:1; padding:10px;" inputmode="numeric"></div>
                    
                    <button type="submit" class="btn-primary" style="width:100%; margin-top:20px;">Pagar Ahora</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        const baseTotalOriginal = <?= $base_price_js ?>; 
        const flightTicketPrice = <?= isset($item['price_per_ticket']) ? $item['price_per_ticket'] : 0 ?>;
        const totalPax = <?= $total_pax ?>;
        const isFlight = <?= $booking_type === 'flight' ? 'true' : 'false' ?>;

        function updateTotal() {
            if (!isFlight) return;

            let currentTotal = baseTotalOriginal;

            // 1. Escalas
            const flightType = document.querySelector('input[name="flight_type"]:checked').value;
            if (flightType === 'scale') {
                currentTotal -= (200 * totalPax);
                document.getElementById('summary-type').innerText = "Tipo: 1 Escala (Ahorro)";
            } else {
                document.getElementById('summary-type').innerText = "Tipo: Directo";
            }

            // 2. Clase (ACTUALIZADO A ESPAÑOL: Ejecutiva)
            const classType = document.querySelector('input[name="selected_class"]:checked').value;
            if (classType === 'Ejecutiva') {
                currentTotal += (flightTicketPrice * 0.40) * totalPax;
                document.getElementById('summary-class').innerText = "Clase: Ejecutiva";
            } else {
                document.getElementById('summary-class').innerText = "Clase: Turista";
            }

            // 3. Equipaje
            const bags = parseInt(document.getElementById('extra_baggage').value) || 0;
            currentTotal += (bags * 500);
            document.getElementById('summary-bags').innerText = "Equipaje Extra: " + bags;

            document.getElementById('display-total').innerText = '$' + currentTotal.toLocaleString('es-MX', {minimumFractionDigits: 2}) + ' MXN';
        }

        // Formato Tarjeta
        document.getElementById('cc-number').addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, '').substring(0,16).match(/.{1,4}/g)?.join(' ') || e.target.value);
        document.getElementById('cc-exp').addEventListener('input', e => { 
            let v = e.target.value.replace(/\D/g,''); 
            if(v.length >= 2) v = v.substring(0,2)+'/'+v.substring(2,4); 
            e.target.value = v; 
        });
        document.getElementById('cc-cvv').addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, '').substring(0,4));
    </script>
</body>
</html>
```

---

### 3. `controllers/process_reservation.php` (Backend en Español y Recálculo)

Recalcula los precios correctamente usando los términos en español ("Ejecutiva") para evitar discrepancias.

```php
<?php
// controllers/process_reservation.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';
require_once __DIR__ . '/../models/includes/reservation_logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('views/index.php');
if (!isLoggedIn()) redirect('views/index.php#reservation');

$type = $_POST['type'];
$base_total = floatval($_POST['base_total_ref'] ?? 0);
$final_total = $base_total;

// Datos extras de vuelo
$clase = $_POST['selected_class'] ?? 'Turista';
$maletas = intval($_POST['extra_baggage'] ?? 0);
$tipo_vuelo = $_POST['flight_type'] ?? 'direct';
$asiento = $_POST['seat_preference'] ?? 'Cualquiera';
$horario = $_POST['flight_time'] ?? 'N/A';

// --- RECALCULAR PRECIO (Seguridad Backend) ---
if ($type === 'flight') {
    $fid = intval($_POST['flight_id']);
    $stmt = $pdo->prepare("SELECT price_per_ticket FROM flight_services WHERE id = :id");
    $stmt->execute(['id' => $fid]);
    $flight = $stmt->fetch();
    
    if ($flight) {
        $ticket_price = floatval($flight['price_per_ticket']);
        $adults = intval($_POST['adults']);
        $children = intval($_POST['children']);
        $pax = $adults + $children;
        
        // Reiniciar cálculo base
        $final_total = ($ticket_price * $pax) + ($adults * ADULT_FEE) + ($children * CHILD_FEE);
        
        // Validar "Ejecutiva" en lugar de "Business"
        if ($clase === 'Ejecutiva') $final_total += ($ticket_price * 0.40) * $pax;
        if ($tipo_vuelo === 'scale') $final_total -= (200 * $pax);
        $final_total += ($maletas * 500);
    }
} else {
    $final_total = floatval($_POST['base_total_ref']);
}

// Validación Pago
if (empty($_POST['card_number'])) {
    $_SESSION['reservation_error'] = "Datos de tarjeta inválidos.";
    redirect('views/index.php#reservation');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (user_id, package_id, hotel_id, flight_id, bus_id, start_date, end_date, adults, children, total_price, status, selected_class, extra_baggage, seat_preference, admin_note, created_at) 
        VALUES 
        (:uid, :pid, :hid, :fid, :bid, :start, :end, :adults, :children, :total, 'paid', :clase, :maletas, :asiento, :nota, NOW())
    ");

    $nota = "Horario preferido: $horario. Tipo: $tipo_vuelo.";

    $stmt->execute([
        'uid' => $_SESSION['user_id'],
        'pid' => ($_POST['package_id'] > 0 ? $_POST['package_id'] : null),
        'hid' => ($_POST['hotel_id'] > 0 ? $_POST['hotel_id'] : null),
        'fid' => ($_POST['flight_id'] > 0 ? $_POST['flight_id'] : null),
        'bid' => ($_POST['bus_id'] > 0 ? $_POST['bus_id'] : null),
        'start' => $_POST['start_date'],
        'end' => $_POST['end_date'],
        'adults' => $_POST['adults'],
        'children' => $_POST['children'],
        'total' => $final_total,
        'clase' => $clase,
        'maletas' => $maletas,
        'asiento' => $asiento,
        'nota' => $nota
    ]);

    $id = $pdo->lastInsertId();
    $_SESSION['reservation_success'] = "✅ ¡Reserva Confirmada! <a href='".BASE_URL."controllers/voucher.php?id=$id' target='_blank' class='btn-primary'>Descargar Voucher</a>";
    redirect('views/index.php#reservation');

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['reservation_error'] = "Error interno.";
    redirect('views/index.php#reservation');
}