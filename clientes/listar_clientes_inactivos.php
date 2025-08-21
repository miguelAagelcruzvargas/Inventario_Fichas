<?php
// clientes/listar_clientes_inactivos.php

if (session_status() == PHP_SESSION_NONE) { 
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Clientes Inactivos - Gestión Liconsa"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; // Ajusta si es diferente
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); // Será 'listar_clientes_inactivos.php'
// --- Fin Definición de Variables ---

// Asegúrate que layout/header.php es la versión con menú Tailwind integrado (header_menu_integrado_tailwind)
include_once __DIR__ . "/../layout/header.php"; 
// No se incluye menu.php por separado, ya está en el header.
include_once __DIR__ . "/cliente.php";      

$cliente = new Cliente();

$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$registros_por_pagina = 10; // Puedes ajustar esto
$inicio_desde = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda_inactivos = isset($_GET['busqueda_inactivos']) ? trim($_GET['busqueda_inactivos']) : '';

$resultado_obj = null;
$total_items = 0;
$total_paginas = 0;
$mensaje_notificacion = '';
$tipo_notificacion = 'info';
$es_busqueda_inactivos_activa = !empty($busqueda_inactivos);
$titulo_dinamico = "Listado de Clientes Inactivos";

if ($es_busqueda_inactivos_activa) {
    $titulo_dinamico = "Búsqueda en Clientes Inactivos";
    $resultado_obj = $cliente->buscarInactivos($busqueda_inactivos); 
    if ($resultado_obj) {
        $total_items = $resultado_obj->rowCount();
    } else {
        $mensaje_notificacion = "Error al buscar clientes inactivos.";
        $tipo_notificacion = "danger";
    }
} else {
    $resultado_obj = $cliente->leerInactivos($inicio_desde, $registros_por_pagina);
    if ($resultado_obj) {
        $total_items = $cliente->contarInactivos();
        if ($total_items > 0) {
            $total_paginas = ceil($total_items / $registros_por_pagina);
        }
    } else {
        $mensaje_notificacion = "Error al cargar la lista de clientes inactivos.";
        $tipo_notificacion = "danger";
    }
}

if (isset($_SESSION['mensaje_accion'])) {
    $mensaje_notificacion = $_SESSION['mensaje_accion'];
    $tipo_notificacion = isset($_SESSION['tipo_mensaje_accion']) ? $_SESSION['tipo_mensaje_accion'] : 'info';
    unset($_SESSION['mensaje_accion']);
    unset($_SESSION['tipo_mensaje_accion']);
} elseif (isset($_GET['mensaje'])) { 
    $mensaje_notificacion = htmlspecialchars($_GET['mensaje']);
    $tipo_notificacion = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'info';
}

$alert_styles = [
    'info'    => ['bg' => 'bg-blue-50', 'border' => 'border-blue-400', 'text' => 'text-blue-700', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-400', 'text' => 'text-green-700', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-400', 'text' => 'text-yellow-700', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-50', 'border' => 'border-red-400', 'text' => 'text-red-700', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = $alert_styles[$tipo_notificacion] ?? $alert_styles['info'];
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <?php if (!empty($mensaje_notificacion)): ?>
        <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-md" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                </div>
                <div class="ml-3 flex-grow">
                    <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_notificacion)); ?></p>
                    <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_notificacion); ?></p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').style.display='none';" aria-label="Close">
                    <span class="sr-only">Cerrar</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <header class="mb-6 md:mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 md:gap-6">
            <div class="text-center md:text-left">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-gray-800 leading-tight">
                    <i class="fas fa-user-slash text-red-600 mr-2"></i> 
                    <?php echo htmlspecialchars($titulo_dinamico); ?>
                    <?php if ($es_busqueda_inactivos_activa): ?>
                        para "<?php echo htmlspecialchars($busqueda_inactivos); ?>"
                    <?php endif; ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Clientes que han sido dados de baja. Pueden ser reactivados.</p>
            </div>
            <div class="flex-shrink-0">
                <a href="listar_clientes.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-5 rounded-lg shadow-sm hover:shadow-md flex items-center justify-center text-sm transition duration-150 ease-in-out">
                    <i class="fas fa-user-check mr-2"></i>Ver Clientes Activos
                </a>
            </div>
        </div>
    </header>
    
    <div class="mb-6 md:mb-8 bg-white p-4 sm:p-6 rounded-xl shadow-xl border border-gray-200">
        <form action="listar_clientes_inactivos.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3 relative" id="formBusquedaInactivos">
            <div class="relative flex-grow w-full">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="busqueda_inactivos" id="busqueda_cliente_inactivo_input" class="w-full pl-12 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm" placeholder="Buscar en clientes inactivos..." value="<?php echo htmlspecialchars($busqueda_inactivos); ?>" autocomplete="off">
                </div>
            <button type="submit" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-6 rounded-lg shadow-md hover:shadow-lg text-sm transition duration-150 ease-in-out">Buscar Inactivos</button>
            <?php if ($es_busqueda_inactivos_activa): ?>
                <a href="listar_clientes_inactivos.php" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg shadow-sm hover:shadow-md text-sm flex items-center justify-center transition duration-150 ease-in-out" title="Limpiar búsqueda">
                    <i class="fas fa-times mr-1 sm:mr-2"></i> <span class="hidden sm:inline">Limpiar</span><span class="sm:hidden">Limpiar</span>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if ($resultado_obj && $total_items > 0): ?>
        <div class="bg-white shadow-xl rounded-xl border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                    <h3 class="text-sm sm:text-base font-medium text-gray-700">
                        <?php if ($es_busqueda_inactivos_activa): ?>
                            <span class="px-3 py-1 text-xs sm:text-sm font-semibold text-gray-800 bg-gray-200 rounded-full"><?php echo $total_items; ?> clientes inactivos encontrados</span>
                        <?php elseif ($total_paginas > 0): ?>
                            Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> <span class="hidden sm:inline">(Clientes Inactivos)</span>
                        <?php else: ?>
                            Clientes Inactivos
                        <?php endif; ?>
                    </h3>
                    <span class="px-3 py-1 text-xs sm:text-sm font-semibold text-white bg-red-600 rounded-full">
                        Total Inactivos: <?php echo $total_items; ?>
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-100 uppercase tracking-wider">Tarjeta</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-100 uppercase tracking-wider">Nombre Completo</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-100 uppercase tracking-wider">Dotación</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-100 uppercase tracking-wider">Teléfono</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-100 uppercase tracking-wider min-w-[140px] sm:min-w-[160px]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($row = $resultado_obj->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($row['numero_tarjeta']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-center"><?php echo htmlspecialchars($row['dotacion_maxima']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    <?php if (!empty($row['telefono'])): 
                                        $telefono_limpio = preg_replace('/\D/', '', $row['telefono']);
                                        $numero_whatsapp = $telefono_limpio; 
                                        if (!empty($telefono_limpio)) {
                                            if (strlen($telefono_limpio) === 10 && substr($telefono_limpio, 0, 2) !== '52') { $numero_whatsapp = '52' . $telefono_limpio; }
                                            elseif (substr($telefono_limpio, 0, 2) === '52' && strlen($telefono_limpio) === 12) { $numero_whatsapp = $telefono_limpio; }
                                            elseif (strpos($telefono_limpio, '+') === 0) { $numero_whatsapp = $telefono_limpio; }
                                            else if (strlen($telefono_limpio) > 0 && strlen($telefono_limpio) < 12 && substr($telefono_limpio, 0, 2) !== '52' && strpos($telefono_limpio, '+') !== 0){ $numero_whatsapp = '52' . $telefono_limpio; }
                                        }
                                    ?>
                                        <a href="https://wa.me/<?php echo $numero_whatsapp; ?>" target="_blank" class="text-green-600 hover:text-green-700 hover:underline inline-flex items-center text-xs sm:text-sm" title="Contactar por WhatsApp">
                                            <i class="fab fa-whatsapp mr-1"></i> <span class="hidden sm:inline"><?php echo htmlspecialchars($row['telefono']); ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic text-xs">No registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                    <div class="flex items-center justify-center space-x-1 sm:space-x-2">
                                        <a href="editar_cliente.php?id=<?php echo $row['id']; ?>&origen=inactivos<?php echo $es_busqueda_inactivos_activa ? '&termino_busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" 
                                           class="text-gray-500 hover:text-gray-700 p-1.5 rounded-md hover:bg-gray-100 transition duration-150 ease-in-out" title="Editar Datos">
                                            <i class="fas fa-edit fa-fw"></i> <span class="hidden lg:inline text-xs">Editar</span>
                                        </a>
                                        <?php
                                        $url_reactivar = "eliminar_cliente.php?id=" . $row['id'] . "&activar=1&origen=inactivos";
                                        if ($es_busqueda_inactivos_activa) {
                                            $url_reactivar .= "&termino_busqueda_inactivos=".urlencode($busqueda_inactivos);
                                        }
                                        ?>
                                        <a href="<?php echo $url_reactivar; ?>" class="text-green-600 hover:text-green-800 p-1.5 rounded-md hover:bg-green-50 transition duration-150 ease-in-out" title="Reactivar Cliente" onclick="return confirm('¿Está seguro de reactivar a este cliente? Volverá a la lista de activos.')">
                                            <i class="fas fa-user-check fa-fw"></i> <span class="hidden lg:inline text-xs">Reactivar</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if (!$es_busqueda_inactivos_activa && $total_paginas > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px" aria-label="Paginación">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=1<?php echo $es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                            <span class="sr-only">Primera</span><i class="fas fa-angle-double-left h-5 w-5"></i>
                        </a>
                        <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                            <span class="sr-only">Anterior</span><i class="fas fa-angle-left h-5 w-5"></i>
                        </a>
                    <?php endif; ?>
                    <?php
                    $rango_display = 2; 
                    $inicio_loop = max(1, $pagina_actual - $rango_display);
                    $fin_loop = min($total_paginas, $pagina_actual + $rango_display);
                    if ($inicio_loop > 1) {
                        if ($inicio_loop > 2) { echo '<a href="?pagina=1'.($es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : '').'" class="relative hidden md:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">1</a>'; }
                        if ($inicio_loop > 2) { echo '<span class="relative hidden md:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>'; }
                    }
                    for ($i = $inicio_loop; $i <= $fin_loop; $i++):?>
                        <a href="?pagina=<?php echo $i; ?><?php echo $es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $pagina_actual) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100'; ?> transition duration-150 ease-in-out">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;
                    if ($fin_loop < $total_paginas):
                        if ($fin_loop < $total_paginas - 1) { echo '<span class="relative hidden md:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>'; }
                        echo '<a href="?pagina='.$total_paginas.($es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : '').'" class="relative hidden md:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">'.$total_paginas.'</a>';
                    endif; ?>
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                            <span class="sr-only">Siguiente</span><i class="fas fa-angle-right h-5 w-5"></i>
                        </a>
                        <a href="?pagina=<?php echo $total_paginas; ?><?php echo $es_busqueda_inactivos_activa ? '&busqueda_inactivos='.urlencode($busqueda_inactivos) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                            <span class="sr-only">Última</span><i class="fas fa-angle-double-right h-5 w-5"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
        
    <?php elseif($es_busqueda_inactivos_activa): ?>
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-500 fa-lg mr-3 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">
                        No se encontraron clientes inactivos que coincidan con "<strong><?php echo htmlspecialchars($busqueda_inactivos); ?></strong>".
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
         <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 fa-lg mr-3 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-800">
                        No hay clientes inactivos registrados.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('busqueda_cliente_inactivo_input'); // Cambiado ID
    const suggestionsContainer = document.getElementById('sugerencias_busqueda_container'); // Mismo contenedor, si quieres uno diferente, cambia el ID
    const searchForm = document.getElementById('formBusquedaInactivos'); // Cambiado ID
    let debounceTimer;

    if(searchInput && suggestionsContainer && searchForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 1) { 
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
                return;
            }

            // Asumimos que autocomplete_sugerencias.php puede filtrar por inactivos si se le pasa un parámetro,
            // o necesitas un endpoint diferente para sugerencias de inactivos.
            // Por ahora, usa el mismo y el backend debería manejar el filtrado si es necesario.
            debounceTimer = setTimeout(() => {
                fetch(`autocomplete_sugerencias.php?term=${encodeURIComponent(query)}&estado=inactivo`) // Ejemplo: añadir parámetro de estado
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red al obtener sugerencias: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        suggestionsContainer.innerHTML = ''; 
                        if (data.length > 0) {
                            suggestionsContainer.style.display = 'block';
                            data.forEach(item => {
                                const a = document.createElement('a');
                                a.classList.add('block', 'px-4', 'py-2.5', 'text-sm', 'text-gray-700', 'hover:bg-gray-100', 'cursor-pointer', 'border-b', 'border-gray-200', 'last:border-b-0');
                                
                                let displayText = `<span class="font-semibold text-gray-800">${item.nombre_completo || 'N/A'}</span>`;
                                if (item.numero_tarjeta) {
                                    displayText += ` <span class="text-xs text-gray-500">(Tjt: ${item.numero_tarjeta})</span>`;
                                }
                                if (item.telefono) {
                                    displayText += ` <span class="text-xs text-gray-500">(Tel: ${item.telefono})</span>`;
                                }
                                a.innerHTML = displayText;

                                a.addEventListener('click', function(e) {
                                    e.preventDefault(); 
                                    searchInput.value = item.nombre_completo; 
                                    suggestionsContainer.innerHTML = ''; 
                                    suggestionsContainer.style.display = 'none';
                                    searchForm.submit(); 
                                });
                                suggestionsContainer.appendChild(a);
                            });
                        } else {
                            suggestionsContainer.style.display = 'none'; 
                        }
                    })
                    .catch(error => {
                        console.error('Error en fetch de sugerencias:', error);
                        suggestionsContainer.innerHTML = '<div class="px-4 py-2 text-sm text-red-700 bg-red-50 rounded-b-md">Error al cargar sugerencias.</div>';
                        suggestionsContainer.style.display = 'block';
                    });
            }, 300); 
        });

        document.addEventListener('click', function(event) {
            if (suggestionsContainer && searchInput && !searchInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
            }
        });
    }
});
</script>

<?php 
// Asegúrate que layout/footer.php sea la versión SIN Bootstrap (footer_sin_bootstrap)
include_once __DIR__ . "/../layout/footer.php"; 
ob_end_flush();
?>
