// assets/js/scripts.js

// Esperar a que el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    
    // Configuración de tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configuración de popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        var alertList = document.querySelectorAll('.alert.alert-success, .alert.alert-info');
        alertList.forEach(function(alert) {
            // Crear un nuevo elemento button para cerrar
            var closeButton = document.createElement('button');
            closeButton.classList.add('btn-close');
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            closeButton.setAttribute('aria-label', 'Close');
            
            // Verificar si el alerta ya tiene un botón de cierre
            if (!alert.querySelector('.btn-close')) {
                alert.classList.add('alert-dismissible', 'fade', 'show');
                alert.appendChild(closeButton);
                
                // Crear un nuevo objeto bootstrap para el alerta
                var bsAlert = new bootstrap.Alert(alert);
                
                // Cerrar automáticamente después de 5 segundos
                setTimeout(function() {
                    bsAlert.close();
                }, 5000);
            }
        });
    }, 500);
    
    // Mejorar la experiencia de usuario con tablas
    var tables = document.querySelectorAll('.table');
    tables.forEach(function(table) {
        // Añadir clases para tablas responsivas si no las tienen
        if (!table.parentElement.classList.contains('table-responsive')) {
            var wrapper = document.createElement('div');
            wrapper.classList.add('table-responsive');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Validación personalizada de formularios
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Función para formatear números como moneda
    window.formatCurrency = function(number) {
        return '$' + parseFloat(number).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };
    
    // Función para confirmar acciones importantes
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
    
    // Función para búsqueda en tiempo real (para inputs con clase 'search-input')
    var searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            var searchTerm = this.value.toLowerCase();
            var tableRows = document.querySelectorAll(this.dataset.target + ' tbody tr');
            
            tableRows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                if(text.indexOf(searchTerm) === -1) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
    });
    
    // Funcionalidad para ordenar tablas (para th con clase 'sortable')
    var sortableHeaders = document.querySelectorAll('th.sortable');
    sortableHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            var table = this.closest('table');
            var index = Array.from(this.parentNode.children).indexOf(this);
            var rows = Array.from(table.querySelectorAll('tbody tr'));
            var isAscending = this.classList.contains('asc');
            
            // Quitar clases de ordenamiento de todos los encabezados
            table.querySelectorAll('th.sortable').forEach(function(th) {
                th.classList.remove('asc', 'desc');
            });
            
            // Aplicar clase de ordenamiento a este encabezado
            this.classList.add(isAscending ? 'desc' : 'asc');
            
            // Ordenar filas
            rows.sort(function(a, b) {
                var aValue = a.children[index].textContent.trim();
                var bValue = b.children[index].textContent.trim();
                
                // Intentar convertir a número si es posible
                var aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                var bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? bNum - aNum : aNum - bNum;
                } else {
                    return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
                }
            });
            
            // Reordenar filas en la tabla
            var tbody = table.querySelector('tbody');
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
        });
    });
});

// Función para imprimir un elemento específico
function printElement(elementId) {
    var element = document.getElementById(elementId);
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = element.innerHTML;
    window.print();
    document.body.innerHTML = originalContents;
}