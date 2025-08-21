// Funciones auxiliares para la búsqueda mejorada
function showResults(data, query, suggestionsContainer, searchInput, hideSuggestions, saveToHistory) {
    console.log('📋 Mostrando', data.length, 'resultados');
    
    let html = `<div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-600 font-medium">
        <i class="fas fa-users mr-1"></i>${data.length} cliente${data.length === 1 ? '' : 's'} encontrado${data.length === 1 ? '' : 's'}
    </div>`;
    
    data.forEach((item) => {
        html += `<div class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors duration-150" data-cliente="${item.nombre_completo}">
            <div class="flex items-center justify-between">
                <div class="flex-grow">
                    <div class="font-medium text-gray-900 mb-1">${item.nombre_completo || 'N/A'}</div>
                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                        ${item.numero_tarjeta ? `<span><i class="fas fa-credit-card mr-1 text-yellow-600"></i>${item.numero_tarjeta}</span>` : ''}
                        ${item.telefono ? `<span><i class="fas fa-phone mr-1 text-purple-600"></i>${item.telefono}</span>` : ''}
                    </div>
                </div>
                <div class="flex-shrink-0 ml-3">
                    ${item.estado && item.estado !== 'activo' ? 
                        `<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">${item.estado}</span>` :
                        `<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Activo</span>`
                    }
                </div>
            </div>
        </div>`;
    });
    
    suggestionsContainer.innerHTML = html;
    suggestionsContainer.style.display = 'block';
    setTimeout(() => {
        suggestionsContainer.classList.remove('opacity-0', 'scale-95');
        suggestionsContainer.classList.add('opacity-100', 'scale-100');
    }, 10);
    
    // Eventos de clic
    suggestionsContainer.querySelectorAll('[data-cliente]').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const clienteName = this.dataset.cliente;
            searchInput.value = clienteName;
            hideSuggestions();
            saveToHistory(clienteName);
            
            const searchUrl = `${window.location.pathname}?busqueda=${encodeURIComponent(clienteName)}`;
            window.location.href = searchUrl;
        });
    });
}

// Script de búsqueda mejorado
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando búsqueda mejorada...');
    
    const searchInput = document.getElementById('busqueda_cliente_input');
    const suggestionsContainer = document.getElementById('sugerencias_busqueda_container');
    const searchIcon = document.getElementById('search_icon');
    const clearButton = document.getElementById('clear_search');
    const charCounter = document.getElementById('char_counter');
    let debounceTimer;
    
    if (!searchInput || !suggestionsContainer) {
        console.error('❌ Elementos no encontrados');
        return;
    }
    
    // Funciones auxiliares
    function showSuggestions() {
        suggestionsContainer.style.display = 'block';
        setTimeout(() => {
            suggestionsContainer.classList.remove('opacity-0', 'scale-95');
            suggestionsContainer.classList.add('opacity-100', 'scale-100');
        }, 10);
    }
    
    function hideSuggestions() {
        suggestionsContainer.classList.remove('opacity-100', 'scale-100');
        suggestionsContainer.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            suggestionsContainer.style.display = 'none';
            suggestionsContainer.innerHTML = '';
        }, 200);
    }
    
    function updateSearchIcon(isSearching = false) {
        if (searchIcon) {
            if (isSearching) {
                searchIcon.className = 'fas fa-spinner fa-spin text-blue-500';
            } else {
                searchIcon.className = 'fas fa-search text-gray-400';
            }
        }
    }
    
    function updateCharCounter(length) {
        if (charCounter) {
            if (length > 0) {
                charCounter.style.display = 'block';
                charCounter.textContent = `${length} caracteres`;
            } else {
                charCounter.style.display = 'none';
            }
        }
    }
    
    function toggleClearButton(show) {
        if (clearButton) {
            clearButton.style.display = show ? 'flex' : 'none';
        }
    }
    
    function saveToHistory(term) {
        let searchHistory = JSON.parse(localStorage.getItem('search_history') || '[]');
        if (!searchHistory.includes(term)) {
            searchHistory.unshift(term);
            searchHistory = searchHistory.slice(0, 5);
            localStorage.setItem('search_history', JSON.stringify(searchHistory));
        }
    }
    
    function showNoResults() {
        suggestionsContainer.innerHTML = `<div class="px-6 py-8 text-center">
            <i class="fas fa-search-minus text-3xl text-gray-300 mb-3"></i>
            <div class="text-sm font-medium text-gray-600 mb-1">Sin resultados</div>
            <div class="text-xs text-gray-500">Intenta con otros términos</div>
        </div>`;
        showSuggestions();
        setTimeout(hideSuggestions, 3000);
    }
    
    function showError(message) {
        suggestionsContainer.innerHTML = `<div class="px-6 py-4 text-center">
            <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
            <div class="text-sm text-red-600">${message}</div>
        </div>`;
        showSuggestions();
        setTimeout(hideSuggestions, 3000);
    }
    
    // Eventos principales
    searchInput.addEventListener('focus', function() {
        this.classList.add('ring-4', 'ring-blue-100');
        if (this.value.length > 0) {
            toggleClearButton(true);
        }
    });

    searchInput.addEventListener('blur', function() {
        this.classList.remove('ring-4', 'ring-blue-100');
        setTimeout(() => hideSuggestions(), 150);
    });
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        updateCharCounter(query.length);
        toggleClearButton(query.length > 0);
        
        if (query.length < 1) {
            hideSuggestions();
            updateSearchIcon(false);
            return;
        }
        
        updateSearchIcon(true);
        suggestionsContainer.innerHTML = `
            <div class="px-6 py-4 text-center">
                <div class="flex items-center justify-center space-x-2">
                    <div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full"></div>
                    <div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full" style="animation-delay: 0.1s"></div>
                    <div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full" style="animation-delay: 0.2s"></div>
                    <span class="ml-2 text-sm text-gray-600">Buscando...</span>
                </div>
            </div>
        `;
        showSuggestions();

        debounceTimer = setTimeout(() => {
            const url = `autocomplete_sugerencias.php?term=${encodeURIComponent(query)}&solo_activos=0`;
            
            fetch(url)
                .then(response => {
                    updateSearchIcon(false);
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    if (!text.trim()) {
                        showNoResults();
                        return;
                    }
                    
                    try {
                        const data = JSON.parse(text);
                        if (data.error) {
                            showError(data.error);
                        } else if (Array.isArray(data) && data.length > 0) {
                            showResults(data, query, suggestionsContainer, searchInput, hideSuggestions, saveToHistory);
                        } else {
                            showNoResults();
                        }
                    } catch (jsonError) {
                        showError('Error al procesar respuesta');
                    }
                })
                .catch(error => {
                    updateSearchIcon(false);
                    showError('Error de conexión');
                });
        }, 300);
    });
    
    // Eventos adicionales
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            toggleClearButton(false);
            updateCharCounter(0);
            hideSuggestions();
        });
    }
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideSuggestions();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });
    
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
            hideSuggestions();
        }
    });
    
    console.log('✅ Búsqueda mejorada iniciada');
});
