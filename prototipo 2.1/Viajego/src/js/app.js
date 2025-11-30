let currentItem = null;
let currentType = '';
let modalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    checkLogin();
    // Carga Paquetes primero por defecto, o Vuelos si Paquetes no existe
    if(document.getElementById('paquetes-container')) cargarServicio('paquetes');
    else if(document.getElementById('vuelos-container')) cargarServicio('vuelos');
    
    // --- VALIDACIONES DE TARJETA ---
    const cardExp = document.getElementById('cardExp');
    if(cardExp){
        cardExp.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length >= 3) val = val.slice(0, 2) + '/' + val.slice(2, 4);
            e.target.value = val;
        });
    }

    const cardCvv = document.getElementById('cardCvv');
    if(cardCvv){
        cardCvv.value = ""; 
        cardCvv.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    }
});

// --- SESIÓN ---
function checkLogin() {
    const userStr = localStorage.getItem('user');
    const authContainer = document.getElementById('auth-buttons');
    if (!authContainer) return; 
    
    if (userStr) {
        const user = JSON.parse(userStr);
        let html = `<span class="text-dark me-3">Hola, <b>${user.nombre}</b></span>`;
        if(user.rol === 'agencia') html += `<a href="admin.html" class="btn btn-sm btn-warning me-2">Panel</a>`;
        html += `<a href="mis_viajes.html" class="btn btn-sm btn-primary me-2">Mis Viajes</a>`;
        html += `<button onclick="logout()" class="btn btn-sm btn-outline-danger">Salir</button>`;
        authContainer.innerHTML = html;
    }
}

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// --- CARGA DE SERVICIOS ---
async function cargarServicio(tipo) {
    const contenedor = document.getElementById(`${tipo}-container`);
    if(!contenedor) return;
    
    contenedor.innerHTML = '<div class="text-center py-5"><div class="spinner-border"></div></div>';
    
    try {
        const res = await fetch(`/api/${tipo}`);
        const datos = await res.json();
        
        if (!datos.length) { contenedor.innerHTML = '<h4 class="text-center">No disponible</h4>'; return; }

        let html = '';
        datos.forEach(item => {
            let nombre, precio, desc, extraInfo = '';
            
            // Lógica unificada
            if (tipo === 'paquetes') {
                nombre = item.titulo;
                precio = item.precio_total;
                desc = `${item.duracion} | ${item.destino}`;
                const hName = item.hotel_data ? item.hotel_data.h_nombre : 'Hotel Incluido';
                const tName = item.transporte_data ? item.transporte_data.t_empresa : 'Vuelo/Bus Incluido';
                extraInfo = `<small class="d-block text-info mb-1"><i class="fas fa-hotel"></i> ${hName}</small>
                             <small class="d-block text-secondary mb-2"><i class="fas fa-plane"></i> ${tName}</small>`;
            } else {
                nombre = item.nombre || item.linea_autobus || item.aerolinea + ' ' + item.codigo_vuelo;
                precio = item.precio || item.precio_noche;
                desc = item.ciudad || item.origen_iata + ' -> ' + item.destino_iata;
            }

            const itemString = encodeURIComponent(JSON.stringify(item));
            
            html += `
            <div class="col">
                <div class="card h-100 bg-secondary text-white border-0 shadow-sm">
                    <div class="card-body">
                        ${tipo === 'paquetes' ? '<span class="badge bg-warning text-dark mb-2">Paquete Todo Incluido</span>' : ''}
                        <h5 class="card-title">${nombre}</h5>
                        <p class="card-text small">${desc}</p>
                        ${extraInfo}
                        <h4 class="fw-bold">$${precio}</h4>
                    </div>
                    <div class="card-footer bg-transparent border-top border-secondary">
                        <button class="btn btn-light w-100" onclick="abrirModal('${tipo}', '${itemString}')">Reservar</button>
                    </div>
                </div>
            </div>`;
        });
        contenedor.innerHTML = html;
    } catch (e) { console.error(e); }
}


// --- LÓGICA DEL MODAL DE RESERVA ---
function abrirModal(tipo, itemString) {
    if (!localStorage.getItem('user')) { alert("Inicia sesión primero"); window.location.href = 'login.html'; return; }
    
    currentItem = JSON.parse(decodeURIComponent(itemString));
    currentType = tipo;
    
    const fieldsDiv = document.getElementById('dynamicFields');
    let titulo = currentItem.nombre || currentItem.aerolinea || currentItem.linea_autobus || currentItem.titulo;
    document.getElementById('modalTitle').innerText = `Reservar: ${titulo}`;
    
    let html = '';
    const today = new Date().toISOString().split('T')[0];

    // 🎁 CASO 1: PAQUETES 
    if (tipo === 'paquetes') {
        const t = currentItem.transporte_data || {};
        const h = currentItem.hotel_data || {};
        const fSalidaRaw = currentItem.fecha_salida ? new Date(currentItem.fecha_salida).toISOString().split('T')[0] : today;

        html = `
        <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Paquete completo con fechas fijas.</div>
        <div class="row g-3">
            <div class="col-12 bg-dark p-3 rounded border border-secondary">
                <h5 class="text-info">${currentItem.duracion}</h5>
                <p><b>Salida:</b> ${new Date(currentItem.fecha_salida).toLocaleString()}</p>
                <p><b>Regreso:</b> ${new Date(currentItem.fecha_regreso).toLocaleString()}</p>
                <hr>
                <h6 class="text-white"><i class="fas fa-plane"></i> Transporte</h6>
                <p class="small text-muted mb-2">${t.t_empresa || ''} (${t.t_codigo || ''}) - ${t.t_clase || ''}</p>
                <h6 class="text-white"><i class="fas fa-hotel"></i> Alojamiento</h6>
                <p class="small text-muted mb-0">${h.h_nombre || ''} - ${h.h_habitacion || ''}</p>
                <p class="small text-success mb-0">${h.h_servicios || ''}</p>
            </div>
            <div class="col-12"><label>Pasajeros</label><input type="number" id="guests" class="form-control" value="1" min="1" max="10" onchange="calcTotal()"></div>
            <input type="hidden" id="dateStart" value="${fSalidaRaw}">
        </div>`;
    }
    // 🏨 CASO 2: HOTELES
    else if (tipo === 'hoteles') {
        html = `
        <div class="row g-3">
            <div class="col-6"><label>Entrada</label><input type="date" id="dateStart" class="form-control" min="${today}" onchange="calcTotal()"></div>
            <div class="col-6"><label>Salida (Máx 25 días)</label><input type="date" id="dateEnd" class="form-control" min="${today}" onchange="calcTotal()"></div>
            <div class="col-6"><label>Habitación</label>
                <select id="roomType" class="form-select" onchange="validarHuespedes(); calcTotal()">
                    <option value="estandar" data-price="1">Estándar (max 4)</option>
                    <option value="doble" data-price="1.5">Doble (4-6)</option>
                    <option value="suite" data-price="2.5">Suite (max 10)</option>
                </select>
            </div>
            <div class="col-6"><label>Comida</label>
                <select id="mealPlan" class="form-select" onchange="calcTotal()">
                    <option value="none" data-cost="0">Sin alimentos</option>
                    <option value="desayuno" data-cost="200">Solo Desayuno (+$200)</option>
                    <option value="all" data-cost="800">Todo Incluido (+$800)</option>
                </select>
            </div>
            <div class="col-12"><label>Huéspedes</label><input type="number" id="guests" class="form-control" value="2" min="1" max="4" onchange="calcTotal()"></div>
            <div class="col-12 text-light small mt-2">
                <i class="fas fa-check text-success"></i> Wi-Fi | 
                <i class="fas fa-check text-success"></i> Alberca | 
                <i class="fas fa-check text-success"></i> Estacionamiento
            </div>
        </div>`;
    } 
    // ✈️ CASO 3: VUELOS Y BUSES (FECHAS AHORA SON FIJAS Y READ-ONLY)
    else {
        // Aseguramos que las fechas existan o usamos una cadena vacía para evitar errores
        const fSalida = currentItem.fecha_salida ? new Date(currentItem.fecha_salida).toLocaleString() : 'N/A';
        const fLlegada = currentItem.fecha_llegada ? new Date(currentItem.fecha_llegada).toLocaleString() : 'N/A';
        const fRegSalida = currentItem.fecha_regreso_salida ? new Date(currentItem.fecha_regreso_salida).toLocaleString() : 'N/A';
        const fRegLlegada = currentItem.fecha_regreso_llegada ? new Date(currentItem.fecha_regreso_llegada).toLocaleString() : 'N/A';
        
        const rawStart = currentItem.fecha_salida ? new Date(currentItem.fecha_salida).toISOString().split('T')[0] : today;
        const rawEnd = currentItem.fecha_regreso_salida ? new Date(currentItem.fecha_regreso_salida).toISOString().split('T')[0] : today;

        html = `
        <div class="alert alert-info"><i class="fas fa-clock"></i> Este itinerario tiene fechas y horarios programados (Fijos).</div>
        <div class="row g-3">
            <div class="col-12 form-check form-switch">
                <input class="form-check-input" type="checkbox" id="roundTrip" checked disabled>
                <label class="form-check-label text-white">Viaje Redondo (Incluido)</label>
            </div>
            
            <div class="col-6 bg-dark border border-secondary p-2 rounded">
                <label class="text-info fw-bold">IDA</label>
                <div class="small text-white mt-1">Salida: ${fSalida}</div>
                <div class="small text-muted">Llegada: ${fLlegada}</div>
            </div>
            
            <div class="col-6 bg-dark border border-secondary p-2 rounded">
                <label class="text-info fw-bold">REGRESO</label>
                <div class="small text-white mt-1">Salida: ${fRegSalida}</div>
                <div class="small text-muted">Llegada: ${fRegLlegada}</div>
            </div>

            <div class="col-6 mt-3"><label>Clase</label><select id="travelClass" class="form-select" onchange="calcTotal()"><option value="estandar" data-mult="1">Estándar</option><option value="ejecutiva" data-mult="1.5">Ejecutiva</option></select></div>
             <div class="col-12"><label>Pasajeros</label><input type="number" id="guests" class="form-control" value="1" min="1" max="10" onchange="calcTotal()"></div>
            
            <input type="hidden" id="dateStart" value="${rawStart}">
            <input type="hidden" id="dateEnd" value="${rawEnd}">
        </div>`;
    }

    fieldsDiv.innerHTML = html;
    
    if(document.getElementById('cardCvv')) document.getElementById('cardCvv').value = '';

    calcTotal(); 
    modalInstance = new bootstrap.Modal(document.getElementById('bookingModal'));
    modalInstance.show();
}

function validarHuespedes() {
    const tipo = document.getElementById('roomType').value;
    const input = document.getElementById('guests');
    const hint = document.getElementById('guestHint');
    
    let min=1, max=4;
    if(tipo === 'doble') { min=4; max=6; }
    if(tipo === 'suite') { min=1; max=10; }
    
    input.min = min;
    input.max = max;
    if(input.value < min) input.value = min;
    if(input.value > max) input.value = max;
    
    hint.innerText = `Permitido: ${min} a ${max} personas`;
}

function calcTotal() {
    let total = 0;
    const guests = parseInt(document.getElementById('guests').value) || 1;
    
    if (currentType === 'paquetes') {
        total = parseFloat(currentItem.precio_total) * guests;
    }
    else if (currentType === 'hoteles') {
        const basePrice = parseFloat(currentItem.precio_noche);
        const start = new Date(document.getElementById('dateStart').value);
        const end = new Date(document.getElementById('dateEnd').value);
        
        if (start && end && end > start) {
            const diffTime = Math.abs(end - start);
            const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            
            if(days > 25) { alert("Máximo 25 días"); document.getElementById('dateEnd').value = ''; return; }
            
            const roomMult = parseFloat(document.getElementById('roomType').selectedOptions[0].dataset.price);
            const mealCost = parseFloat(document.getElementById('mealPlan').selectedOptions[0].dataset.cost);
            
            total = (basePrice * roomMult * days) + (mealCost * guests * days);
        }
    } 
    else {
        const basePrice = parseFloat(currentItem.precio);
        const classMult = parseFloat(document.getElementById('travelClass').selectedOptions[0].dataset.mult);
        let extra = 0;
        if(currentType === 'vuelos') {
            const luggageElement = document.getElementById('luggage');
            if(luggageElement) extra = parseFloat(luggageElement.selectedOptions[0].dataset.cost);
        }
        
        total = (basePrice * classMult * guests) + (extra * guests);
    }
    
    document.getElementById('totalPriceDisplay').innerText = `$${total.toFixed(2)}`;
    return total;
}

// --- PROCESAR PAGO (CORREGIDO) ---
async function procesarPago() {
    // 1. Validaciones
    const card = document.getElementById('cardNum').value.replace(/\s/g, '');
    const cv = document.getElementById('cardExp').value;
    const cvv = document.getElementById('cardCvv').value;
    
    if(card.length !== 16 || isNaN(card)) { alert("Tarjeta inválida (16 dígitos)"); return; }
    if(!cv.includes('/')) { alert("Fecha incorrecta (MM/AA)"); return; }
    if(cvv.length !== 3 || isNaN(cvv)) { alert("CVV inválido (3 dígitos)"); return; }

    const user = JSON.parse(localStorage.getItem('user'));
    const total = calcTotal();
    
    // 2. Preparar Detalles
    let detalles = {};
    
    if(currentType === 'hoteles') {
        detalles = {
            habitacion: document.getElementById('roomType').value,
            comida: document.getElementById('mealPlan').value
        };
    } else if (currentType === 'paquetes') {
        detalles = {
            info: "Paquete Turístico Completo",
            duracion: currentItem.duracion
        };
    } else {
        detalles = {
            clase: document.getElementById('travelClass').value,
            tipo_viaje: 'Redondo (Fijo)'
        };
        if(currentType === 'vuelos') {
            const luggageSelect = document.getElementById('luggage');
            detalles.equipaje = luggageSelect ? luggageSelect.options[luggageSelect.selectedIndex].text : 'No incluido';
        }
    }

    const payload = {
        user_id: user.id,
        service_type: currentType,
        item_name: currentItem.nombre || currentItem.aerolinea || currentItem.linea_autobus || currentItem.titulo,
        date_start: document.getElementById('dateStart').value,
        date_end: document.getElementById('dateEnd').value || document.getElementById('dateStart').value, 
        num_guests: document.getElementById('guests').value,
        details: detalles,
        total_price: total
    };

    if(!payload.date_start) { alert("Error: No se encontró la fecha de salida."); return; }

    try {
        const res = await fetch('/api/reservas', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if(data.success) {
            alert("¡Pago exitoso! Redirigiendo a Mis Viajes...");
            modalInstance.hide();
            window.location.href = 'mis_viajes.html';
        } else {
            alert("Error: " + data.message);
        }
    } catch(e) { console.error(e); alert("Error de conexión"); }
}

async function cargarMisViajes() {
    const userStr = localStorage.getItem('user');
    if(!userStr) return;
    const user = JSON.parse(userStr);
    
    const container = document.getElementById('lista-reservas');
    if(!container) return; 
    
    try {
        const res = await fetch(`/api/mis_reservas/${user.id}`);
        const reservas = await res.json();
        
        if(reservas.length === 0) {
            container.innerHTML = '<div class="alert alert-warning">No tienes viajes registrados.</div>';
            return;
        }
        
        let html = '';
        reservas.forEach(r => {
            const esCancelado = r.status === 'Cancelado';
            const colorStatus = esCancelado ? 'danger' : 'success';
            
            let detHtml = '';
            for (const [key, value] of Object.entries(r.detalles)) {
                detHtml += `<li class="small text-capitalize"><b>${key}:</b> ${value}</li>`;
            }

            html += `
            <div class="col-md-6">
                <div class="card shadow-sm border-${colorStatus} mb-3">
                    <div class="card-header bg-${colorStatus} text-white d-flex justify-content-between">
                        <span>${r.service_type.toUpperCase()}</span>
                        <span>${r.status}</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">${r.item_name}</h5>
                        <p class="text-muted small">Cód: ${r.reservation_code}</p>
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1"><b>Fecha:</b> ${new Date(r.date_start).toLocaleDateString()}</p>
                                <p class="mb-1"><b>Pasajeros:</b> ${r.num_guests}</p>
                            </div>
                            <div class="col-6 text-end">
                                <h4 class="text-${colorStatus}">$${r.total_price}</h4>
                                ${esCancelado ? `<small class="text-danger">Reembolso: $${r.refund_amount}</small>` : ''}
                            </div>
                        </div>
                        <hr>
                        <ul class="list-unstyled mb-3">${detHtml}</ul>
                        
                        ${!esCancelado ? `
                        <button class="btn btn-outline-danger w-100 btn-sm" onclick="cancelarReserva(${r.id})">
                            Cancelar Reserva (Reembolso 30%)
                        </button>` : ''}
                    </div>
                </div>
            </div>`;
        });
        container.innerHTML = html;
        
    } catch(e) { console.error(e); }
}

async function cancelarReserva(id) {
    if(!confirm("¿Estás seguro de cancelar? Solo se reembolsará el 30% del total.")) return;
    
    try {
        const res = await fetch(`/api/reservas/cancelar/${id}`, { method: 'POST' });
        const data = await res.json();
        if(data.success) {
            alert(`Cancelación exitosa. Se ha reembolsado $${data.reembolso}`);
            location.reload();
        } else {
            alert("Error al cancelar");
        }
    } catch(e) { alert("Error de conexión"); }
}