// assets/js/script.js
// La variable global BASE_URL_JS debe ser definida en views/index.php antes de cargar este script.

document.addEventListener('DOMContentLoaded', function() {
    // Definición de la URL de la API de cálculo usando la variable global PHP
    // ✅ CORREGIDO: Apunta directamente al controlador de la API para evitar la pérdida de datos POST durante la redirección.
    const API_URL = (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + 'controllers/pricing_api.php';

    const packageSelect = document.getElementById('package');
    const hotelSelect = document.getElementById('hotel');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const adultsInput = document.getElementById('adults');
    const childrenInput = document.getElementById('children');
    const reservationForm = document.getElementById('reservation-form');
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Establecer fecha mínima para ambos campos de fecha
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
    endDateInput.min = today;
    
    // Cuando cambia la fecha de inicio, actualizar la fecha mínima de fin
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        }
        calculateTotal();
    });
    
    const elements = [packageSelect, hotelSelect, startDateInput, endDateInput, adultsInput, childrenInput];
    
    elements.forEach(element => {
        if (element) {
            element.addEventListener('change', calculateTotal);
            element.addEventListener('input', calculateTotal);
        }
    });
    
    // Calcular automáticamente cuando la página carga
    setTimeout(calculateTotal, 1000);
    
    function calculateTotal() {
        const packageOption = packageSelect.options[packageSelect.selectedIndex];
        const hotelOption = hotelSelect.options[hotelSelect.selectedIndex];
        
        if (!packageOption || !packageOption.dataset.price || 
            !hotelOption || !hotelOption.dataset.price || 
            !startDateInput.value || !endDateInput.value) {
            return;
        }
        
        // Mostrar loading
        reservationForm.classList.add('loading');
        
        const formData = new FormData();
        formData.append('package_price', packageOption.dataset.price);
        formData.append('hotel_price', hotelOption.dataset.price);
        formData.append('start_date', startDateInput.value);
        formData.append('end_date', endDateInput.value);
        formData.append('adults', adultsInput.value || 1);
        formData.append('children', childrenInput.value || 0);
        
        // Uso de la URL de la API centralizada
        fetch(API_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                showCalculationError(data.error);
            } else {
                showCalculationResult(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCalculationError('Error al calcular el total');
        })
        .finally(() => {
            reservationForm.classList.remove('loading');
        });
    }
    
    function showCalculationResult(data) {
        let resultElement = document.getElementById('calculation-result');
        if (!resultElement) {
            resultElement = document.createElement('div');
            resultElement.id = 'calculation-result';
            reservationForm.insertBefore(resultElement, reservationForm.lastElementChild);
        }
        
        resultElement.className = 'calculation-success';
        resultElement.innerHTML = `
            <div style="text-align: center; margin-bottom: 1rem;">
                <i class="fas fa-calculator" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem;">Resumen de tu Reserva</h3>
            </div>
            
            <div class="calculation-details">
                <div class="calculation-item">
                    <span>Días de estancia:</span>
                    <strong>${data.days}</strong>
                </div>
                <div class="calculation-item">
                    <span>Paquete turístico:</span>
                    <strong>$${parseFloat(data.package).toLocaleString('es-MX', {minimumFractionDigits: 2})}</strong>
                </div>
                <div class="calculation-item">
                    <span>Hospedaje (${data.days} noches):</span>
                    <strong>$${parseFloat(data.hotel * data.days).toLocaleString('es-MX', {minimumFractionDigits: 2})}</strong>
                </div>
                <div class="calculation-item">
                    <span>Tarifas adultos:</span>
                    <strong>$${parseFloat(data.adults_fee).toLocaleString('es-MX', {minimumFractionDigits: 2})}</strong>
                </div>
                <div class="calculation-item">
                    <span>Tarifas niños:</span>
                    <strong>$${parseFloat(data.children_fee).toLocaleString('es-MX', {minimumFractionDigits: 2})}</strong>
                </div>
            </div>
            
            <div class="calculation-total">
                Total: $${parseFloat(data.total).toLocaleString('es-MX', {minimumFractionDigits: 2})} MXN
            </div>
        `;
    }
    
    function showCalculationError(message) {
        let resultElement = document.getElementById('calculation-result');
        if (!resultElement) {
            resultElement = document.createElement('div');
            resultElement.id = 'calculation-result';
            reservationForm.insertBefore(resultElement, reservationForm.lastElementChild);
        }
        
        resultElement.className = 'calculation-error';
        resultElement.innerHTML = `
            <div style="text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem;">Verifica tus datos</h3>
                <p>${message}</p>
            </div>
        `;
    }
    
    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});