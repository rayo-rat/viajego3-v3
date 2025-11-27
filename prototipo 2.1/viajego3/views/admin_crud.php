<?php
// views/admin_crud.php
require_once __DIR__ . '/../models/includes/config.php';
require_once __DIR__ . '/../models/includes/auth.php';

// Asegurar que solo el administrador pueda acceder
requireAdmin();

// Obtener la lista de Agencias para los selects (necesaria para crear hoteles/servicios)
try {
    $stmt = $pdo->query("SELECT id, name FROM travel_agencies ORDER BY name");
    $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $agencies = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>CRUD de Servicios | Admin - ViajeGO</title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos específicos para el CRUD Admin */
        .crud-container { max-width: 1200px; margin: 80px auto 20px; padding: 20px; }
        .tab-menu { display: flex; border-bottom: 2px solid #ccc; margin-bottom: 20px; }
        .tab-menu button {
            padding: 10px 15px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        .tab-menu button:hover { background-color: #f0f0f0; }
        .tab-menu button.active { border-bottom: 2px solid #0b7dda; color: #0b7dda; }
        
        .content-section { display: none; }
        .content-section.active { display: block; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #f2f2f2; }
        
        .btn-action { padding: 5px 10px; margin-right: 5px; cursor: pointer; border: none; border-radius: 4px; }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        
        .modal {
            display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; 
            overflow: auto; background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; 
            width: 90%; max-width: 600px; border-radius: 8px; position: relative;
            max-height: 90vh; overflow-y: auto;
        }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; }

        /* Estilo para previsualización de imagen */
        #package-image-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            display: none;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <h1 class="logo">Viaje<span>GO</span> CRUD</h1>
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>views/index.php">Volver al Sitio</a></li>
                    <li><a href="<?= BASE_URL ?>controllers/admin.php">Reservas Pendientes</a></li>
                    <li><a href="<?= BASE_URL ?>controllers/logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="crud-container">
        <h2>Administración de Servicios y Agencias</h2>

        <div class="tab-menu" id="crud-tabs">
            <button data-target="packages-section" onclick="loadPackages()">Paquetes</button>
            <button data-target="agencies-section" class="active" onclick="loadAgencies()">Agencias</button>
            <button data-target="hotels-section" onclick="loadHotels()">Hoteles</button>
            <button data-target="buses-section" onclick="loadBuses()">Autobuses</button>
            <button data-target="flights-section" onclick="loadFlights()">Vuelos</button>
        </div>

        <div id="packages-section" class="content-section active">
            <h3>Gestión de Paquetes Turísticos</h3>
            <button class="btn-primary" onclick="openPackageModal()"><i class="fas fa-plus"></i> Nuevo Paquete</button>
            <div id="packages-list" style="margin-top: 15px;">
                <p>Cargando paquetes...</p>
            </div>
        </div>

        <div id="agencies-section" class="content-section">
            <h3>Gestión de Agencias</h3>
            <button class="btn-primary" onclick="openAgencyModal()"><i class="fas fa-plus"></i> Nueva Agencia</button>
            <div id="agencies-list" style="margin-top: 15px;">
                <p>Cargando agencias...</p>
            </div>
        </div>
        
        <div id="hotels-section" class="content-section">
            <h3>Gestión de Hoteles</h3>
            <button class="btn-primary" onclick="openHotelModal()"><i class="fas fa-plus"></i> Nuevo Hotel</button>
            <div id="hotels-list" style="margin-top: 15px;">
                <p>Cargando hoteles...</p>
            </div>
        </div>

        <div id="buses-section" class="content-section">
            <h3>Gestión de Servicios de Autobús</h3>
            <button class="btn-primary" onclick="openBusModal()"><i class="fas fa-plus"></i> Nuevo Servicio</button>
            <div id="buses-list" style="margin-top: 15px;">
                <p>Cargando autobuses...</p>
            </div>
        </div>

        <div id="flights-section" class="content-section">
            <h3>Gestión de Vuelos</h3>
            <button class="btn-primary" onclick="openFlightModal()"><i class="fas fa-plus"></i> Nuevo Vuelo</button>
            <div id="flights-list" style="margin-top: 15px;">
                <p>Cargando vuelos...</p>
            </div>
        </div>
    </main>
    
    <div id="packageModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('packageModal')">&times;</span>
            <h4><span id="package-modal-title">Agregar</span> Paquete</h4>
            <form id="package-form" onsubmit="handlePackageSubmit(event)"> 
                <input type="hidden" name="id" id="package-id">
                
                <div class="form-group">
                    <label>Nombre Paquete:</label>
                    <input type="text" id="package-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Precio Base:</label>
                    <input type="number" step="0.01" id="package-price" name="base_price" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea id="package-description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Imagen (JPG, PNG, GIF):</label>
                    <input type="file" id="package-image" name="image" accept="image/*">
                    <input type="hidden" name="current_image_url" id="package-current-image"> 
                    <img id="package-image-preview" src="" alt="Previsualización">
                </div>
                
                <button type="submit" class="btn-primary">Guardar Paquete</button>
            </form>
        </div>
    </div>

    <div id="agencyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('agencyModal')">&times;</span>
            <h4><span id="modal-title">Agregar</span> Agencia</h4>
            <form id="agency-form" onsubmit="handleAgencySubmit(event)">
                <input type="hidden" name="id" id="agency-id">
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" id="agency-name" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="agency-email" name="contact_email">
                </div>
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" id="agency-phone" name="contact_phone">
                </div>
                <div class="form-group">
                    <label>Dirección:</label>
                    <textarea id="agency-address" name="address"></textarea>
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
            </form>
            <div id="api-key-display" style="margin-top: 15px; display: none; background: #f0f0f0; padding: 10px;">
                <strong>API Key:</strong> <span id="api-key-value"></span>
            </div>
        </div>
    </div>
    
    <div id="hotelModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('hotelModal')">&times;</span>
            <h4><span id="hotel-modal-title">Agregar</span> Hotel</h4>
            <form id="hotel-form" onsubmit="handleHotelSubmit(event)">
                <input type="hidden" name="id" id="hotel-id">
                
                <div class="form-group">
                    <label>Agencia:</label>
                    <select id="hotel-agency-id" name="agency_id" required>
                        <option value="">Selecciona una agencia</option>
                        <?php foreach ($agencies as $agency): ?>
                            <option value="<?= e($agency['id']) ?>"><?= e($agency['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre Hotel:</label>
                    <input type="text" id="hotel-name" name="name" required>
                </div>
                <div class="form-group">
                    <label>Ubicación:</label>
                    <input type="text" id="hotel-location" name="location" required>
                </div>
                <div class="form-group">
                    <label>Precio/Noche:</label>
                    <input type="number" step="0.01" id="hotel-price" name="price_per_night" required>
                </div>
                <div class="form-group">
                    <label>Estrellas (1-5):</label>
                    <input type="number" min="1" max="5" id="hotel-stars" name="stars" value="3" required>
                </div>
                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea id="hotel-description" name="description"></textarea>
                </div>
                <button type="submit" class="btn-primary">Guardar Hotel</button>
            </form>
        </div>
    </div>
    
    <div id="busModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('busModal')">&times;</span>
            <h4><span id="bus-modal-title">Agregar</span> Servicio Autobús</h4>
            <form id="bus-form" onsubmit="handleBusSubmit(event)">
                <input type="hidden" name="id" id="bus-id">
                
                <div class="form-group">
                    <label>Agencia:</label>
                    <select id="bus-agency-id" name="agency_id" required>
                        <option value="">Selecciona una agencia</option>
                        <?php foreach ($agencies as $agency): ?>
                            <option value="<?= e($agency['id']) ?>"><?= e($agency['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Compañía:</label>
                    <input type="text" id="bus-company" name="company_name" required>
                </div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <input type="text" id="bus-type" name="bus_type" placeholder="Ej: Primera Clase" required>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Origen:</label>
                        <input type="text" id="bus-origin" name="route_origin" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Destino:</label>
                        <input type="text" id="bus-destination" name="route_destination" required>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Salida:</label>
                        <input type="datetime-local" id="bus-departure" name="departure_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Llegada:</label>
                        <input type="datetime-local" id="bus-arrival" name="arrival_time" required>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Precio:</label>
                        <input type="number" step="0.01" id="bus-price" name="price_per_seat" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Asientos:</label>
                        <input type="number" id="bus-seats" name="total_seats" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Guardar Servicio</button>
            </form>
        </div>
    </div>
    
    <div id="flightModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('flightModal')">&times;</span>
            <h4><span id="flight-modal-title">Agregar</span> Vuelo</h4>
            <form id="flight-form" onsubmit="handleFlightSubmit(event)">
                <input type="hidden" name="id" id="flight-id">

                <div class="form-group">
                    <label>Agencia:</label>
                    <select id="flight-agency-id" name="agency_id" required>
                        <option value="">Selecciona una agencia</option>
                        <?php foreach ($agencies as $agency): ?>
                            <option value="<?= e($agency['id']) ?>"><?= e($agency['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Aerolínea:</label>
                    <input type="text" id="flight-airline" name="airline_name" required>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Nº Vuelo:</label>
                        <input type="text" id="flight-number" name="flight_number" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Clase:</label>
                        <select id="flight-class" name="flight_class">
                            <option value="Economy">Económica</option>
                            <option value="Business">Ejecutiva</option>
                            <option value="First">Primera</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Origen (IATA):</label>
                        <input type="text" id="flight-origin" name="origin_airport" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Destino (IATA):</label>
                        <input type="text" id="flight-destination" name="destination_airport" required>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Salida:</label>
                        <input type="datetime-local" id="flight-departure" name="departure_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Llegada:</label>
                        <input type="datetime-local" id="flight-arrival" name="arrival_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Precio:</label>
                    <input type="number" step="0.01" id="flight-price" name="price_per_ticket" required>
                </div>
                <button type="submit" class="btn-primary">Guardar Vuelo</button>
            </form>
        </div>
    </div>
    
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const API_URL = BASE_URL + 'api/admin_api.php?action=';

    document.addEventListener('DOMContentLoaded', function() {
        loadPackages(); // Cargar Paquetes por defecto (ahora es la primera pestaña)

        // Tabs Logic
        document.getElementById('crud-tabs').addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON') {
                // UI Update
                document.querySelectorAll('.tab-menu button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
                e.target.classList.add('active');
                document.getElementById(e.target.getAttribute('data-target')).classList.add('active');
                
                // Recargar el contenido de la pestaña activa si es necesario
                const target = e.target.getAttribute('data-target');
                if (target === 'agencies-section') loadAgencies();
                if (target === 'hotels-section') loadHotels();
                if (target === 'buses-section') loadBuses();
                if (target === 'flights-section') loadFlights();
                if (target === 'packages-section') loadPackages();
            }
        });
        
        // Previsualización de Imagen
        document.getElementById('package-image').addEventListener('change', function(e) {
            const preview = document.getElementById('package-image-preview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        });
    });
    
    // --- API FETCH HELPER (Modificado para soportar FormData) ---
    async function apiFetch(action, options = {}, isFormData = false) {
        try {
            let res;
            if (isFormData) {
                // Para FormData, no configuramos Content-Type
                res = await fetch(API_URL + action, options);
            } else {
                // Para JSON (Hoteles, Buses, Vuelos, Agencies - Create/Update)
                res = await fetch(API_URL + action, {
                    ...options,
                    headers: { 'Content-Type': 'application/json' },
                    body: options.body
                });
            }
            
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                // Útil para depurar si el PHP devuelve HTML o texto simple en lugar de JSON
                console.error("Error al parsear JSON:", text);
                throw new Error("Respuesta inválida del servidor: " + text);
            }
        } catch (err) {
            alert(err.message);
            return { success: false };
        }
    }
    
    // --- 1. PACKAGES (NUEVAS FUNCIONES) ---
    function loadPackages() {
        apiFetch('get_packages').then(data => {
            const list = document.getElementById('packages-list');
            if (data.success && data.data.length > 0) {
                let html = '<table class="data-table"><thead><tr><th>ID</th><th>Paquete</th><th>Precio Base</th><th>Imagen</th><th>Acciones</th></tr></thead><tbody>';
                data.data.forEach(p => {
                    const img_url = p.image_url ? BASE_URL + 'assets/images/' + p.image_url : 'N/A';
                    html += `<tr>
                        <td>${p.id}</td>
                        <td>${p.name}</td>
                        <td>$${p.base_price}</td>
                        <td>${p.image_url ? '<img src="' + img_url + '" style="width: 50px; height: 50px; object-fit: cover;">' : 'No Asignada'}</td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openPackageModal(${JSON.stringify(p).replace(/"/g, "&quot;")})"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteItem('delete_package', ${p.id}, loadPackages)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html + '</tbody></table>';
            } else {
                list.innerHTML = '<div class="alert info">No hay paquetes.</div>';
            }
        });
    }

    function openPackageModal(p = null) {
        document.getElementById('package-form').reset();
        document.getElementById('package-modal-title').textContent = p ? 'Editar' : 'Agregar';
        document.getElementById('package-id').value = p ? p.id : '';
        const preview = document.getElementById('package-image-preview');
        
        if (p) {
            document.getElementById('package-name').value = p.name;
            document.getElementById('package-price').value = p.base_price;
            document.getElementById('package-description').value = p.description;
            document.getElementById('package-current-image').value = p.image_url; // Guarda el nombre actual de la imagen
            
            if(p.image_url) {
                preview.src = BASE_URL + 'assets/images/' + p.image_url;
                preview.style.display = 'block';
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        } else {
            document.getElementById('package-current-image').value = '';
            preview.src = '';
            preview.style.display = 'none';
        }
        
        // La imagen solo es obligatoria en creación
        document.getElementById('package-image').required = !p; 
        
        document.getElementById('packageModal').style.display = 'block';
    }

    function handlePackageSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const id = form['id'].value;
        const action = id ? 'update_package' : 'create_package';
        
        // CRUCIAL: Usamos FormData para enviar archivos
        const formData = new FormData(form); 
        
        apiFetch(action, {
            method: 'POST', // Siempre POST para enviar FormData, incluso para 'update'
            body: formData
        }, true).then(data => { // El 'true' indica que es FormData
            if(data.success) { 
                closeModal('packageModal'); 
                loadPackages(); 
            } else if (data.message) {
                 alert(data.message);
            }
        });
    }

    // --- 2. AGENCIES ---
    function loadAgencies() {
        apiFetch('get_agencies').then(data => {
            const list = document.getElementById('agencies-list');
            if (data.success && data.data.length > 0) {
                let html = '<table class="data-table"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody>';
                data.data.forEach(a => {
                    html += `<tr>
                        <td>${a.id}</td>
                        <td>${a.name}</td>
                        <td>${a.contact_email || '-'}</td>
                        <td>${a.contact_phone || '-'}</td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openAgencyModal(${JSON.stringify(a).replace(/"/g, "&quot;")})"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteItem('delete_agency', ${a.id}, loadAgencies)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html + '</tbody></table>';
            } else {
                list.innerHTML = '<div class="alert info">No hay agencias.</div>';
            }
        });
    }

    function openAgencyModal(a = null) {
        document.getElementById('agency-form').reset();
        document.getElementById('modal-title').textContent = a ? 'Editar' : 'Agregar';
        document.getElementById('agency-id').value = a ? a.id : '';
        if(a) {
            document.getElementById('agency-name').value = a.name;
            document.getElementById('agency-email').value = a.contact_email;
            document.getElementById('agency-phone').value = a.contact_phone;
            document.getElementById('agency-address').value = a.address;
        }
        document.getElementById('api-key-display').style.display = 'none';
        document.getElementById('agencyModal').style.display = 'block';
    }

    function handleAgencySubmit(e) {
        e.preventDefault();
        const form = e.target;
        const id = form['id'].value;
        const action = id ? 'update_agency' : 'create_agency';
        
        apiFetch(action, {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify({
                id: id,
                name: form['name'].value,
                contact_email: form['contact_email'].value,
                contact_phone: form['contact_phone'].value,
                address: form['address'].value
            })
        }).then(data => {
            if(data.success) {
                if(data.api_key) {
                    document.getElementById('api-key-value').textContent = data.api_key;
                    document.getElementById('api-key-display').style.display = 'block';
                } else {
                    closeModal('agencyModal');
                }
                loadAgencies();
            }
        });
    }

    // --- 3. HOTELS ---
    function loadHotels() {
        apiFetch('get_hotels').then(data => {
            const list = document.getElementById('hotels-list');
            if (data.success && data.data.length > 0) {
                let html = '<table class="data-table"><thead><tr><th>ID</th><th>Hotel</th><th>Agencia</th><th>Ubicación</th><th>Precio</th><th>Acciones</th></tr></thead><tbody>';
                data.data.forEach(h => {
                    html += `<tr>
                        <td>${h.id}</td>
                        <td>${h.name}</td>
                        <td>${h.agency_name || '-'}</td>
                        <td>${h.location || '-'}</td>
                        <td>$${h.price_per_night}</td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openHotelModal(${JSON.stringify(h).replace(/"/g, "&quot;")})"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteItem('delete_hotel', ${h.id}, loadHotels)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html + '</tbody></table>';
            } else {
                list.innerHTML = '<div class="alert info">No hay hoteles.</div>';
            }
        });
    }

    function openHotelModal(h = null) {
        document.getElementById('hotel-form').reset();
        document.getElementById('hotel-modal-title').textContent = h ? 'Editar' : 'Agregar';
        document.getElementById('hotel-id').value = h ? h.id : '';
        if(h) {
            document.getElementById('hotel-agency-id').value = h.agency_id;
            document.getElementById('hotel-name').value = h.name;
            document.getElementById('hotel-location').value = h.location;
            document.getElementById('hotel-price').value = h.price_per_night;
            document.getElementById('hotel-stars').value = h.stars;
            document.getElementById('hotel-description').value = h.description;
        }
        document.getElementById('hotelModal').style.display = 'block';
    }

    function handleHotelSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const id = form['id'].value;
        apiFetch(id ? 'update_hotel' : 'create_hotel', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify({
                id: id,
                agency_id: form['agency_id'].value,
                name: form['name'].value,
                location: form['location'].value,
                price_per_night: form['price_per_night'].value,
                stars: form['stars'].value,
                description: form['description'].value
            })
        }).then(data => {
            if(data.success) { closeModal('hotelModal'); loadHotels(); }
        });
    }

    // --- 4. BUSES ---
    function loadBuses() {
        apiFetch('get_buses').then(data => {
            const list = document.getElementById('buses-list');
            if (data.success && data.data.length > 0) {
                let html = '<table class="data-table"><thead><tr><th>ID</th><th>Cía</th><th>Ruta</th><th>Salida</th><th>Precio</th><th>Acciones</th></tr></thead><tbody>';
                data.data.forEach(b => {
                    html += `<tr>
                        <td>${b.id}</td>
                        <td>${b.company_name}</td>
                        <td>${b.route_origin} -> ${b.route_destination}</td>
                        <td>${b.departure_time}</td>
                        <td>$${b.price_per_seat}</td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openBusModal(${JSON.stringify(b).replace(/"/g, "&quot;")})"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteItem('delete_bus', ${b.id}, loadBuses)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html + '</tbody></table>';
            } else { list.innerHTML = '<div class="alert info">No hay autobuses.</div>'; }
        });
    }

    function openBusModal(b = null) {
        document.getElementById('bus-form').reset();
        document.getElementById('bus-modal-title').textContent = b ? 'Editar' : 'Agregar';
        document.getElementById('bus-id').value = b ? b.id : '';
        if(b) {
            document.getElementById('bus-agency-id').value = b.agency_id;
            document.getElementById('bus-company').value = b.company_name;
            document.getElementById('bus-type').value = b.bus_type;
            document.getElementById('bus-origin').value = b.route_origin;
            document.getElementById('bus-destination').value = b.route_destination;
            document.getElementById('bus-departure').value = b.departure_time.replace(' ', 'T');
            document.getElementById('bus-arrival').value = b.arrival_time.replace(' ', 'T');
            document.getElementById('bus-price').value = b.price_per_seat;
            document.getElementById('bus-seats').value = b.total_seats;
        }
        document.getElementById('busModal').style.display = 'block';
    }

    function handleBusSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const id = form['id'].value;
        apiFetch(id ? 'update_bus' : 'create_bus', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify({
                id: id,
                agency_id: form['agency_id'].value,
                company_name: form['company_name'].value,
                bus_type: form['bus_type'].value,
                route_origin: form['route_origin'].value,
                route_destination: form['route_destination'].value,
                departure_time: form['departure_time'].value.replace('T', ' '),
                arrival_time: form['arrival_time'].value.replace('T', ' '),
                price_per_seat: form['price_per_seat'].value,
                total_seats: form['total_seats'].value
            })
        }).then(data => { if(data.success) { closeModal('busModal'); loadBuses(); } });
    }

    // --- 5. FLIGHTS ---
    function loadFlights() {
        apiFetch('get_flights').then(data => {
            const list = document.getElementById('flights-list');
            if (data.success && data.data.length > 0) {
                let html = '<table class="data-table"><thead><tr><th>ID</th><th>Aerolínea</th><th>Vuelo</th><th>Ruta</th><th>Salida</th><th>Precio</th><th>Acciones</th></tr></thead><tbody>';
                data.data.forEach(f => {
                    html += `<tr>
                        <td>${f.id}</td>
                        <td>${f.airline_name}</td>
                        <td>${f.flight_number}</td>
                        <td>${f.origin_airport} -> ${f.destination_airport}</td>
                        <td>${f.departure_time}</td>
                        <td>$${f.price_per_ticket}</td>
                        <td>
                            <button class="btn-action btn-edit" onclick="openFlightModal(${JSON.stringify(f).replace(/"/g, "&quot;")})"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteItem('delete_flight', ${f.id}, loadFlights)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html + '</tbody></table>';
            } else { list.innerHTML = '<div class="alert info">No hay vuelos.</div>'; }
        });
    }

    function openFlightModal(f = null) {
        document.getElementById('flight-form').reset();
        document.getElementById('flight-modal-title').textContent = f ? 'Editar' : 'Agregar';
        document.getElementById('flight-id').value = f ? f.id : '';
        if(f) {
            document.getElementById('flight-agency-id').value = f.agency_id;
            document.getElementById('flight-airline').value = f.airline_name;
            document.getElementById('flight-number').value = f.flight_number;
            document.getElementById('flight-class').value = f.flight_class;
            document.getElementById('flight-origin').value = f.origin_airport;
            document.getElementById('flight-destination').value = f.destination_airport;
            document.getElementById('flight-departure').value = f.departure_time.replace(' ', 'T');
            document.getElementById('flight-arrival').value = f.arrival_time.replace(' ', 'T');
            document.getElementById('flight-price').value = f.price_per_ticket;
        }
        document.getElementById('flightModal').style.display = 'block';
    }

    function handleFlightSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const id = form['id'].value;
        apiFetch(id ? 'update_flight' : 'create_flight', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify({
                id: id,
                agency_id: form['agency_id'].value,
                airline_name: form['airline_name'].value,
                flight_number: form['flight_number'].value,
                flight_class: form['flight_class'].value,
                origin_airport: form['origin_airport'].value,
                destination_airport: form['destination_airport'].value,
                departure_time: form['departure_time'].value.replace('T', ' '),
                arrival_time: form['arrival_time'].value.replace('T', ' '),
                price_per_ticket: form['price_per_ticket'].value,
                total_seats: 100 // Default
            })
        }).then(data => { if(data.success) { closeModal('flightModal'); loadFlights(); } });
    }
    
    // --- UTILS ---
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function deleteItem(action, id, reloadCallback) {
        if(confirm('¿Estás seguro de eliminar este elemento?')) {
            // El método de borrado debe ser DELETE para Agencias y PUT/POST para otros (el helper lo convierte)
            const method = action === 'delete_agency' ? 'DELETE' : 'DELETE';

            apiFetch(action + '&id=' + id, { method: method })
                .then(data => { if(data.success) { reloadCallback(); } });
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

</body>
</html>