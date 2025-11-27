<?php
// views/index.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';
require_once __DIR__ . '/../models/includes/reservation_logic.php';

if (isset($_GET['action']) && $_GET['action'] === 'calc') {
    header("Location: " . BASE_URL . "controllers/pricing_api.php");
    exit;
}

$search_query = trim($_GET['q'] ?? '');
$sort_option = $_GET['sort'] ?? 'recommended';
$active_tab = $_GET['tab'] ?? 'packages'; 

$limit_clause = " LIMIT 6";
$search_term = '';
$bindings_needed = 0;

if (!empty($search_query)) {
    $limit_clause = "";
    $search_term = '%' . $search_query . '%';
    $bindings_needed = 1;
}

// --- 1. PAQUETES ---
$packages_sql = "SELECT p.id, p.name, p.description, p.image_url, p.base_price, p.destination_id, d.name as destination_name, a.name as agency_name FROM packages p JOIN destinations d ON p.destination_id = d.id JOIN travel_agencies a ON p.agency_id = a.id";
$packages_params = [];
if ($bindings_needed > 0) {
    $packages_sql .= " WHERE p.name LIKE :pkg_search1 OR d.name LIKE :pkg_search2";
    $packages_params['pkg_search1'] = $search_term;
    $packages_params['pkg_search2'] = $search_term;
}
switch ($sort_option) {
    case 'price_asc': $packages_sql .= " ORDER BY p.base_price ASC"; break;
    case 'price_desc': $packages_sql .= " ORDER BY p.base_price DESC"; break;
    default: $packages_sql .= " ORDER BY p.id DESC"; break;
}
$packages_sql .= $limit_clause;
try {
    $stmt = $pdo->prepare($packages_sql);
    $stmt->execute($packages_params);
    $packages = $stmt->fetchAll();
} catch (PDOException $e) { $packages = []; }

// --- 2. VUELOS ---
$flights_sql = "SELECT f.id, f.airline_name, f.flight_number, D1.name as origin_airport, D2.name as destination_airport, f.departure_time, f.arrival_time, f.price_per_ticket FROM flight_services f JOIN destinations D1 ON f.origin_id = D1.id JOIN destinations D2 ON f.destination_id = D2.id";
$flights_params = [];
if ($bindings_needed > 0) {
    $flights_sql .= " WHERE D2.name LIKE :flt_search1 OR f.airline_name LIKE :flt_search2";
    $flights_params['flt_search1'] = $search_term;
    $flights_params['flt_search2'] = $search_term;
} 
$flights_sql .= " ORDER BY f.departure_time ASC" . $limit_clause;
try {
    $stmt = $pdo->prepare($flights_sql);
    $stmt->execute($flights_params);
    $flights = $stmt->fetchAll();
} catch (PDOException $e) { $flights = []; }

// --- 3. AUTOBUSES ---
$buses_sql = "SELECT b.id, b.company_name, b.bus_type, D1.name as route_origin, D2.name as route_destination, b.departure_time, b.arrival_time, b.price_per_seat FROM bus_services b JOIN destinations D1 ON b.origin_id = D1.id JOIN destinations D2 ON b.destination_id = D2.id";
$buses_params = [];
if ($bindings_needed > 0) {
    $buses_sql .= " WHERE D2.name LIKE :bus_search1 OR b.company_name LIKE :bus_search2";
    $buses_params['bus_search1'] = $search_term;
    $buses_params['bus_search2'] = $search_term;
}
$buses_sql .= " ORDER BY b.departure_time ASC" . $limit_clause;
try {
    $stmt = $pdo->prepare($buses_sql);
    $stmt->execute($buses_params);
    $buses = $stmt->fetchAll();
} catch (PDOException $e) { $buses = []; }

// --- 4. HOTELES ---
$hotels_sql = "SELECT h.id, h.name, h.price_per_night, h.stars, h.regimen, d.name as city FROM hotels h JOIN destinations d ON h.destination_id = d.id";
$hotels_params = [];
if ($bindings_needed > 0) {
    $hotels_sql .= " WHERE h.name LIKE :htl_search1 OR d.name LIKE :htl_search2";
    $hotels_params['htl_search1'] = $search_term;
    $hotels_params['htl_search2'] = $search_term;
}
$hotels_sql .= " ORDER BY h.price_per_night ASC" . $limit_clause;
try {
    $stmt = $pdo->prepare($hotels_sql);
    $stmt->execute($hotels_params);
    $hotels_list = $stmt->fetchAll();
} catch (PDOException $e) { $hotels_list = []; }

// Filtros Formulario
$pkgs_select = $pdo->query("SELECT id, name, base_price, destination_id FROM packages ORDER BY name ASC")->fetchAll();
$hotels_select = $pdo->query("SELECT h.id, h.name, h.price_per_night, h.destination_id, d.name as city, h.regimen FROM hotels h JOIN destinations d ON h.destination_id = d.id ORDER BY h.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ViajeGO - Agencia de Viajes Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <style>
        .tabs-menu { display: flex; justify-content: center; border-bottom: 2px solid #e2e8f0; margin-bottom: 3rem; gap: 1.5rem; flex-wrap: wrap; }
        .tab-button { background: none; border: none; padding: 10px 20px; font-size: 1.2rem; font-weight: 600; cursor: pointer; color: var(--gray); border-bottom: 3px solid transparent; transition: all 0.3s ease; }
        .tab-button.active { color: var(--primary); border-bottom-color: var(--primary); }
        .service-card { background: white; border-radius: var(--radius); overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); padding: 1.5rem; transition: transform 0.3s ease; text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .service-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }
        .service-icon { font-size: 3rem; color: var(--secondary); margin-bottom: 1rem; }
        .service-detail { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem; text-align: left; padding: 0 5px; }
        .service-price { font-size: 1.5rem; font-weight: 700; color: var(--success); text-align: center; margin-top: 1rem; }
        .service-card h3 { font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--dark); }
        .hotel-hidden { display: none; }
        .stars { color: #f59e0b; margin-bottom: 5px; }
        .agency-badge { font-size: 0.8rem; background: #eee; padding: 2px 8px; border-radius: 10px; color: #666; display: inline-block; margin-bottom: 5px; }
    </style>
</head>
<body>
<header>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo">Viaje<span>GO <span class="server-tag">V3.0</span></span></h1>
            <ul class="nav-links">
                <li><a href="<?= BASE_URL ?>views/index.php#home">Inicio</a></li>
                <li><a href="<?= BASE_URL ?>views/index.php#packages">Servicios</a></li>
                <li><a href="<?= BASE_URL ?>views/index.php#reservation">Reservar</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?= BASE_URL ?>controllers/admin.php"><i class="fas fa-cog"></i> Admin</a></li>
                        <li><a href="<?= BASE_URL ?>views/admin_crud.php"><i class="fas fa-list-alt"></i> CRUD</a></li>
                    <?php endif; ?>
                    <li><a href="<?= BASE_URL ?>controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>controllers/login.php">Ingresar</a></li>
                    <li><a href="<?= BASE_URL ?>controllers/register.php">Registro</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>

<main>
    <section id="home" class="hero">
        <div class="container">
            <h1>Explora México con ViajeGO</h1>
            <h2>Playas, Ciudades Coloniales y Aventura</h2>
            <div class="hero-buttons">
                <a href="#reservation" class="btn-primary"><i class="fas fa-calendar-check"></i> Armar Viaje</a>
                <a href="#packages" class="btn-secondary">Ver Ofertas</a>
            </div>
        </div>
    </section>

    <section id="packages" class="packages">
        <div class="container">
            <h2>Catálogo Nacional</h2>
            <p style="text-align: center; color: var(--gray); margin-bottom: 3rem;">Reserva paquetes completos o servicios individuales.</p>
            
            <form method="GET" action="#packages" style="display: flex; gap: 1rem; margin-bottom: 3rem; max-width: 900px; margin: 0 auto 3rem auto;">
                <input type="hidden" name="tab" id="active-tab-input" value="<?= e($active_tab) ?>">
                <input type="text" name="q" placeholder="Buscar destino (Ej: Cancún)..." value="<?= e($search_query) ?>" style="flex-grow: 1; padding: 12px; border-radius: 8px;">
                <button type="submit" class="btn-primary">Buscar</button>
            </form>
            
            <div class="tabs-menu">
                <button class="tab-button <?= $active_tab === 'packages' ? 'active' : '' ?>" data-tab="packages" onclick="changeTab('packages')">🏨 Paquetes</button>
                <button class="tab-button <?= $active_tab === 'hotels' ? 'active' : '' ?>" data-tab="hotels" onclick="changeTab('hotels')">🛏️ Hoteles</button>
                <button class="tab-button <?= $active_tab === 'flights' ? 'active' : '' ?>" data-tab="flights" onclick="changeTab('flights')">✈️ Vuelos</button>
                <button class="tab-button <?= $active_tab === 'buses' ? 'active' : '' ?>" data-tab="buses" onclick="changeTab('buses')">🚌 Autobuses</button>
            </div>

            <div id="tab-packages" class="tab-content" style="display: <?= $active_tab === 'packages' ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($packages as $p): ?>
                    <div class='package-card service-card'>
                        <div class='package-image' style="height: 200px; overflow: hidden; border-radius: 8px;">
                            <img src='<?= BASE_URL ?>assets/images/<?= e($p['image_url']) ?>' alt='<?= e($p['name']) ?>' style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div style="padding-top: 15px;">
                            <span class="agency-badge"><i class="fas fa-building"></i> <?= e($p['agency_name']) ?></span>
                            <h3><?= e($p['name']) ?></h3>
                            <p style="font-size: 0.9rem; color: #666;"><?= e($p['description']) ?></p>
                            <div class='service-price'>$<?= number_format($p['base_price'], 2) ?> MXN</div>
                        </div>
                        <button class="btn-primary" onclick="selectPackage(<?= e($p['id']) ?>)" style="width:100%; margin-top: 10px;">Reservar</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="tab-hotels" class="tab-content" style="display: <?= $active_tab === 'hotels' ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($hotels_list as $h): ?>
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-bed"></i></div>
                        <h3><?= e($h['name']) ?></h3>
                        <div class="stars"><?php for($i=0; $i<$h['stars']; $i++) echo '<i class="fas fa-star"></i>'; ?></div>
                        <p><strong><?= e($h['city']) ?></strong></p>
                        <div class="service-detail"><span>Régimen:</span> <strong><?= e($h['regimen']) ?></strong></div>
                        <div class="service-price">$<?= number_format($h['price_per_night'], 2) ?> <span style="font-size:0.8rem; color:#666;">/ noche</span></div>
                        <a href="<?= BASE_URL ?>views/checkout.php?type=hotel&id=<?= e($h['id']) ?>" class="btn-primary" style="text-decoration:none; margin-top:10px; display:block;">Reservar Hotel</a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="tab-flights" class="tab-content" style="display: <?= $active_tab === 'flights' ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($flights as $f): ?>
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-plane"></i></div>
                        <h3><?= e($f['airline_name']) ?> (<?= e($f['flight_number']) ?>)</h3>
                        <p><strong><?= e($f['origin_airport']) ?> ➝ <?= e($f['destination_airport']) ?></strong></p>
                        <div class="service-detail"><span>Salida:</span> <strong><?= date('d/m H:i', strtotime($f['departure_time'])) ?></strong></div>
                        <div class="service-price">$<?= number_format($f['price_per_ticket'], 2) ?></div>
                        <a href="<?= BASE_URL ?>views/checkout.php?type=flight&id=<?= e($f['id']) ?>" class="btn-primary" style="text-decoration:none; margin-top:10px; display:block;">Comprar Vuelo</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="tab-buses" class="tab-content" style="display: <?= $active_tab === 'buses' ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($buses as $b): ?>
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-bus"></i></div>
                        <h3><?= e($b['company_name']) ?></h3>
                        <p><strong><?= e($b['route_origin']) ?> ➝ <?= e($b['route_destination']) ?></strong></p>
                        <div class="service-detail"><span>Salida:</span> <strong><?= date('d/m H:i', strtotime($b['departure_time'])) ?></strong></div>
                        <div class="service-price">$<?= number_format($b['price_per_seat'], 2) ?></div>
                        <a href="<?= BASE_URL ?>views/checkout.php?type=bus&id=<?= e($b['id']) ?>" class="btn-primary" style="text-decoration:none; margin-top:10px; display:block;">Comprar Boleto</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="reservation" class="reservation">
        <div class="container">
            <h2>Arma tu Paquete (Hotel + Experiencia)</h2>
            <?php if (isset($_SESSION['reservation_error'])): ?>
                <div class="alert error"><?= e($_SESSION['reservation_error']) ?></div>
                <?php unset($_SESSION['reservation_error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['reservation_success'])): ?>
                <div class="alert success"><?= $_SESSION['reservation_success'] ?></div>
                <?php unset($_SESSION['reservation_success']); ?>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="<?= BASE_URL ?>views/checkout.php" id="reservation-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>1. Selecciona tu Paquete Base:</label>
                            <select id="package" name="package" required onchange="filterHotels()">
                                <option value="">-- Elige un destino --</option>
                                <?php foreach ($pkgs_select as $p): ?>
                                    <option value="<?= e($p['id']) ?>" data-dest="<?= e($p['destination_id']) ?>">
                                        <?= e($p['name']) ?> - $<?= number_format($p['base_price'], 0) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>2. Elige tu Hotel en el destino:</label>
                            <select id="hotel" name="hotel" required disabled>
                                <option value="">-- Primero elige un paquete --</option>
                                <?php foreach ($hotels_select as $h): ?>
                                    <option value="<?= e($h['id']) ?>" data-dest="<?= e($h['destination_id']) ?>" class="hotel-option">
                                        <?= e($h['name']) ?> (<?= e($h['city']) ?>) - Plan: <?= e($h['regimen']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group"><label>Fecha Inicio:</label><input type="date" name="start_date" required min="<?= date('Y-m-d') ?>"></div>
                        <div class="form-group"><label>Fecha Fin:</label><input type="date" name="end_date" required min="<?= date('Y-m-d') ?>"></div>
                        <div class="form-group"><label>Adultos:</label><input type="number" name="adults" value="2" min="1"></div>
                        <div class="form-group"><label>Niños:</label><input type="number" name="children" value="0" min="0"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:20px;">Ir a Pagar</button>
                </form>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container"><p>&copy; <?= date('Y') ?> ViajeGO V3.0 - Arquitectura Distribuida.</p></div>
</footer>

<script>
    const BASE_URL_JS = '<?= BASE_URL ?>';

    function changeTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(d => d.style.display = 'none');
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tabName).style.display = 'grid';
        document.getElementById('tab-' + tabName).style.gridTemplateColumns = 'repeat(auto-fit, minmax(300px, 1fr))';
        document.querySelector(`.tab-button[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById('active-tab-input').value = tabName;
    }

    function filterHotels() {
        const pkgSelect = document.getElementById('package');
        const hotelSelect = document.getElementById('hotel');
        const selectedOption = pkgSelect.options[pkgSelect.selectedIndex];
        const targetDest = selectedOption.getAttribute('data-dest');

        if (!targetDest) {
            hotelSelect.disabled = true;
            hotelSelect.value = "";
            return;
        }
        hotelSelect.disabled = false;
        hotelSelect.value = ""; 
        let count = 0;
        Array.from(hotelSelect.options).forEach(opt => {
            if (opt.value === "") return;
            const hotelDest = opt.getAttribute('data-dest');
            if (hotelDest === targetDest) {
                opt.style.display = 'block';
                count++;
            } else {
                opt.style.display = 'none';
            }
        });
        if (count === 0) alert("No hay hoteles disponibles para este destino.");
    }

    function selectPackage(id) {
        document.getElementById('package').value = id;
        filterHotels();
        window.location.href = '#reservation';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const initialTab = document.getElementById('active-tab-input').value || 'packages';
        changeTab(initialTab);
        filterHotels(); 
    });
</script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>