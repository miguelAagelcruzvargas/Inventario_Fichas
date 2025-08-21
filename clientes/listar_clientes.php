<?php
// clientes/listar_clientes.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Listado de Clientes Activos"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; 
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

include_once __DIR__ . "/../layout/header.php"; 
include_once __DIR__ . "/cliente.php";        

$cliente_handler = new Cliente(); // Renombrado para claridad

$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$registros_por_pagina = 12; // <-- CORRECCIÓN: Cambiado de 10 a 12
$inicio_desde = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda = isset($_GET['busqueda']) ? trim(htmlspecialchars($_GET['busqueda'])) : ''; // Sanitizar búsqueda

$resultado_obj = null; 
$clientes_para_tabla = []; // Inicializar como array vacío
$total_items = 0;        
$total_paginas = 0;
$mensaje_notificacion = ''; 
$tipo_notificacion = 'info';  
$es_busqueda_activa = !empty($busqueda); 
$titulo_dinamico = "Listado de Clientes"; 

if ($es_busqueda_activa) {
    $titulo_dinamico = "Resultados de Búsqueda de Clientes"; 
    $resultado_obj = $cliente_handler->buscar($busqueda); 
    if ($resultado_obj) {
        $clientes_para_tabla = $resultado_obj->fetchAll(PDO::FETCH_ASSOC);
        $total_items = count($clientes_para_tabla); 
        // No hay paginación para los resultados de búsqueda en esta implementación simple
        $total_paginas = ($total_items > 0) ? 1 : 0;
    } else {
        $mensaje_notificacion = "Error al realizar la búsqueda o no se encontraron resultados.";
        $tipo_notificacion = $resultado_obj === false ? "danger" : "warning"; // Distinguir error de no resultados
    }
} else {
    $titulo_dinamico = "Listado de Clientes Activos"; 
    $resultado_obj = $cliente_handler->leerPaginadoActivos($inicio_desde, $registros_por_pagina); 
    if ($resultado_obj) {
        $clientes_para_tabla = $resultado_obj->fetchAll(PDO::FETCH_ASSOC);
        $total_items = $cliente_handler->contarActivos(); 
        if ($total_items > 0) {
            $total_paginas = ceil($total_items / $registros_por_pagina);
        }
    } else {
        $mensaje_notificacion = "Error al cargar la lista de clientes activos.";
        $tipo_notificacion = "danger";
    }
}

if (isset($_SESSION['mensaje_accion'])) {
    $mensaje_notificacion = $_SESSION['mensaje_accion'];
    $tipo_notificacion = isset($_SESSION['tipo_mensaje_accion']) ? $_SESSION['tipo_mensaje_accion'] : 'info';
    unset($_SESSION['mensaje_accion']);
    unset($_SESSION['tipo_mensaje_accion']);
} elseif (isset($_GET['mensaje'])) { 
    $mensaje_notificacion = htmlspecialchars(urldecode($_GET['mensaje']));
    $tipo_notificacion = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'info';
}

$alert_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = null;
if(!empty($tipo_notificacion) && isset($alert_styles[$tipo_notificacion])){
    $current_alert_style = $alert_styles[$tipo_notificacion];
}

if ($es_busqueda_activa) {
    $page_title = 'Búsqueda: "' . htmlspecialchars($busqueda) . '" - Clientes';
}
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-7xl mx-auto">

        <nav class="mb-6 text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex flex-wrap space-x-1 sm:space-x-2">
                <li class="flex items-center">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Inicio</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($page_title); ?></span>
                </li>
            </ol>
        </nav>

        <?php if (!empty($mensaje_notificacion) && $current_alert_style): ?>
            <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_notificacion)); ?></p>
                        <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_notificacion); ?></p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200/80 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').remove();" aria-label="Close">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    
        <header class="mb-6 md:mb-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 md:gap-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 text-center md:text-left leading-tight">
                    <?php if ($es_busqueda_activa): ?>
                        <i class="fas fa-search text-gray-500 mr-2"></i> <?php echo htmlspecialchars($titulo_dinamico); ?> para "<?php echo htmlspecialchars($busqueda); ?>"
                    <?php else: ?>
                        <i class="fas fa-user-check text-liconsa-green mr-2"></i> <?php echo htmlspecialchars($titulo_dinamico); ?>
                    <?php endif; ?>
                </h1>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <a href="invitaciones_whatsapp.php" class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-medium py-2.5 px-5 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fab fa-whatsapp mr-2"></i>Invitar a Grupo
                    </a>
                    <a href="agregar_cliente.php" class="w-full sm:w-auto bg-liconsa-blue hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-plus mr-2"></i> Agregar Cliente
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Búsqueda Inteligente de Clientes -->
        <div class="mb-8 bg-gradient-to-r from-white to-blue-50 p-6 rounded-xl shadow-lg border border-blue-100">
            <div class="mb-5 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-3">
                    <i class="fas fa-search text-blue-600 text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Buscar Clientes</h2>
                <p class="text-sm text-gray-600">Búsqueda instantánea por nombre, número de tarjeta o teléfono</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 relative">
                <div class="relative flex-grow w-full">
                    <!-- Indicador de búsqueda activa -->
                    <div id="search_indicator" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i id="search_icon" class="fas fa-search text-gray-400 transition-colors duration-200"></i>
                    </div>
                    
                    <input type="text" name="busqueda" id="busqueda_cliente_input" 
                           class="w-full pl-12 pr-12 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all duration-200 bg-white hover:border-gray-300" 
                           placeholder="Escribe aquí para buscar..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           autocomplete="off">
                    
                    <!-- Botón para limpiar -->
                    <button type="button" id="clear_search" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-200" style="display:none;" title="Limpiar búsqueda">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    
                    <!-- Contador de caracteres -->
                    <div id="char_counter" class="absolute -bottom-6 right-0 text-xs text-gray-400" style="display:none;"></div>
                    
                    <!-- Contenedor de sugerencias mejorado -->
                    <div id="sugerencias_busqueda_container" 
                         class="absolute z-40 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl max-h-96 overflow-y-auto transition-all duration-200 transform opacity-0 scale-95" 
                         style="display:none;">
                    </div> 
                </div>
                
                <?php if ($es_busqueda_activa): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                       class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white font-medium py-4 px-6 rounded-xl shadow-lg hover:shadow-xl text-sm flex items-center justify-center transition-all duration-200 transform hover:scale-105" 
                       title="Limpiar búsqueda y ver todos los clientes">
                        <i class="fas fa-eraser mr-2"></i>
                        <span>Limpiar Búsqueda</span>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Tips de búsqueda y estadísticas -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex flex-wrap justify-center gap-4 text-xs">
                    <div class="flex items-center text-gray-600 bg-white/80 px-3 py-2 rounded-full border border-gray-200 hover:shadow-sm transition-shadow">
                        <i class="fas fa-user text-blue-500 mr-2"></i>
                        <span>Nombres completos</span>
                    </div>
                    <div class="flex items-center text-gray-600 bg-white/80 px-3 py-2 rounded-full border border-gray-200 hover:shadow-sm transition-shadow">
                        <i class="fas fa-credit-card text-green-500 mr-2"></i>
                        <span>Números de tarjeta</span>
                    </div>
                    <div class="flex items-center text-gray-600 bg-white/80 px-3 py-2 rounded-full border border-gray-200 hover:shadow-sm transition-shadow">
                        <i class="fas fa-phone text-purple-500 mr-2"></i>
                        <span>Números de teléfono</span>
                    </div>
                    <div class="flex items-center text-gray-600 bg-white/80 px-3 py-2 rounded-full border border-gray-200 hover:shadow-sm transition-shadow">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        <span>Búsqueda instantánea</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($clientes_para_tabla)): ?>
            <div class="bg-white shadow-xl rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                        <h3 class="text-sm sm:text-base font-medium text-gray-700">
                            <?php if ($es_busqueda_activa): ?>
                                <span class="px-3 py-1 text-xs sm:text-sm font-semibold text-blue-800 bg-blue-100 rounded-full"><?php echo $total_items; ?> clientes encontrados</span>
                            <?php elseif ($total_paginas > 0): ?>
                                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> <span class="hidden sm:inline">(Clientes Activos)</span>
                            <?php else: ?>
                                Clientes Activos
                            <?php endif; ?>
                        </h3>
                        <span class="px-3 py-1 text-xs sm:text-sm font-semibold text-white bg-liconsa-blue rounded-full">
                            <?php echo $es_busqueda_activa ? 'Total Encontrados: ' : 'Total Activos: '; ?>
                            <?php echo $total_items; ?>
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider">Tarjeta</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider">Nombre Completo</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-200 uppercase tracking-wider hidden md:table-cell">Dotación</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider hidden sm:table-cell">Teléfono</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-200 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-200 uppercase tracking-wider min-w-[140px] sm:min-w-[160px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($clientes_para_tabla as $row): ?>
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($row['numero_tarjeta']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-center hidden md:table-cell"><?php echo htmlspecialchars($row['dotacion_maxima']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 hidden sm:table-cell">
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
                                                <i class="fab fa-whatsapp mr-1"></i> <span class="hidden md:inline"><?php echo htmlspecialchars($row['telefono']); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic text-xs">No reg.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <?php if ($row['estado'] == 'activo'): ?>
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center space-x-1 sm:space-x-2">
                                            <a href="editar_cliente.php?id=<?php echo $row['id']; ?><?php echo $es_busqueda_activa ? '&origen_busqueda=1&termino_busqueda='.urlencode($busqueda) : ''; ?><?php echo !$es_busqueda_activa && $pagina_actual > 1 ? '&pagina='.$pagina_actual : ''; ?>" 
                                               class="text-indigo-600 hover:text-indigo-800 p-1.5 rounded-md hover:bg-indigo-50 transition duration-150 ease-in-out" title="Editar">
                                                <i class="fas fa-edit fa-fw"></i> <span class="hidden lg:inline text-xs">Editar</span>
                                            </a>
                                            <?php 
                                            $url_params = ['id' => $row['id']];
                                            if ($es_busqueda_activa) {
                                                $url_params['origen_busqueda'] = 1;
                                                $url_params['termino_busqueda'] = $busqueda;
                                            } elseif ($pagina_actual > 1) {
                                                $url_params['pagina'] = $pagina_actual;
                                            }
                                            
                                            $clase_boton_estado = ""; $icono_boton_estado = ""; $texto_boton_estado = "";
                                            if ($row['estado'] == 'activo') {
                                                $texto_confirmacion = "¿Está seguro de dar de baja a este cliente?";
                                                $clase_boton_estado = "text-red-600 hover:text-red-800 hover:bg-red-50";
                                                $icono_boton_estado = "fa-user-times";
                                                $texto_boton_estado = "Baja";
                                            } else { 
                                                $url_params['activar'] = 1; 
                                                $texto_confirmacion = "¿Está seguro de reactivar a este cliente?";
                                                $clase_boton_estado = "text-green-600 hover:text-green-800 hover:bg-green-50";
                                                $icono_boton_estado = "fa-user-check";
                                                $texto_boton_estado = "Activar";
                                            }
                                            $url_cambio_estado = "eliminar_cliente.php?" . http_build_query($url_params);
                                            ?>
                                            <a href="<?php echo htmlspecialchars($url_cambio_estado); ?>" class="<?php echo $clase_boton_estado; ?> p-1.5 rounded-md transition duration-150 ease-in-out" title="<?php echo $row['estado'] == 'activo' ? 'Dar de Baja' : 'Reactivar'; ?>" onclick="return confirm('<?php echo addslashes($texto_confirmacion); ?>')">
                                                <i class="fas <?php echo $icono_boton_estado; ?> fa-fw"></i> <span class="hidden lg:inline text-xs"><?php echo $texto_boton_estado; ?></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (!$es_busqueda_activa && $total_paginas > 1): ?>
            <nav class="mt-6 flex justify-center" aria-label="Paginación">
                <ul class="inline-flex items-center -space-x-px">
                    <?php
                    $link_clases_base = "px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white";
                    $link_clases_actual = "z-10 px-3 py-2 leading-tight text-liconsa-blue border border-blue-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white";

                    if ($pagina_actual > 1): ?>
                        <li>
                            <a href="?pagina=1<?php if($es_busqueda_activa) echo '&busqueda='.urlencode($busqueda); ?>" class="<?php echo $link_clases_base; ?> rounded-l-lg"><i class="fas fa-angle-double-left text-xs"></i></a>
                        </li>
                        <li>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php if($es_busqueda_activa) echo '&busqueda='.urlencode($busqueda); ?>" class="<?php echo $link_clases_base; ?>"><i class="fas fa-angle-left text-xs"></i><span class="hidden sm:inline"> Anterior</span></a>
                        </li>
                    <?php endif; ?>
                    <?php
                    $rango_display = 1; 
                    $inicio_loop = max(1, $pagina_actual - $rango_display);
                    $fin_loop = min($total_paginas, $pagina_actual + $rango_display);

                    if ($inicio_loop > 1) {
                         if ($inicio_loop > 2) { echo '<li><a href="?pagina=1'.($es_busqueda_activa ? '&busqueda='.urlencode($busqueda) : '').'" class="'.$link_clases_base.' hidden md:inline-flex">1</a></li>'; }
                         if ($inicio_loop > 2) { echo '<li><span class="'.$link_clases_base.' hidden md:inline-flex">...</span></li>'; }
                    }

                    for ($i = $inicio_loop; $i <= $fin_loop; $i++):?>
                        <li>
                            <a href="?pagina=<?php echo $i; ?><?php if($es_busqueda_activa) echo '&busqueda='.urlencode($busqueda); ?>" class="<?php echo ($i == $pagina_actual) ? $link_clases_actual : $link_clases_base; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;
                    
                    if ($fin_loop < $total_paginas):
                        if ($fin_loop < $total_paginas - 1) { echo '<li><span class="'.$link_clases_base.' hidden md:inline-flex">...</span></li>'; }
                        echo '<li><a href="?pagina='.$total_paginas.($es_busqueda_activa ? '&busqueda='.urlencode($busqueda) : '').'" class="'.$link_clases_base.' hidden md:inline-flex">'.$total_paginas.'</a></li>';
                    endif; ?>
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php if($es_busqueda_activa) echo '&busqueda='.urlencode($busqueda); ?>" class="<?php echo $link_clases_base; ?>"><span class="hidden sm:inline">Siguiente </span><i class="fas fa-angle-right text-xs"></i></a>
                        </li>
                        <li>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php if($es_busqueda_activa) echo '&busqueda='.urlencode($busqueda); ?>" class="<?php echo $link_clases_base; ?> rounded-r-lg"><i class="fas fa-angle-double-right text-xs"></i></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            </div>
        <?php endif; ?>
        
    <?php elseif($es_busqueda_activa): ?>
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-500 fa-lg mr-3 mt-1"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">
                        No se encontraron clientes que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
         <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-6 rounded-md shadow-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 fa-2x mr-3"></i>
                </div>
                <div class="ml-3">
                     <h3 class="text-lg font-medium text-blue-800">No hay clientes activos</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Actualmente no hay clientes activos registrados en el sistema.</p>
                    </div>
                    <div class="mt-4">
                        <a href="agregar_cliente.php" class="font-semibold underline text-blue-700 hover:text-blue-600">
                            <i class="fas fa-plus-circle mr-1"></i>¡Agrega el primero!
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-10 mb-6 p-6 bg-gray-50 rounded-xl shadow-lg border border-gray-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center">
            <div class="flex-shrink-0 mb-3 sm:mb-0 sm:mr-4">
                 <i class="fab fa-whatsapp fa-3x text-green-500"></i>
            </div>
            <div class="ml-0 sm:ml-4">
                <h5 class="text-md sm:text-lg font-semibold text-green-700">Integración con WhatsApp</h5>
                <div class="text-xs sm:text-sm text-gray-600 mt-1">
                    <ol class="list-decimal pl-5 space-y-1">
                        <li>Cree un grupo nuevo en WhatsApp (móvil o escritorio).</li>
                        <li>Desde esta lista, haga clic en el <i class="fab fa-whatsapp text-green-500"></i> de un cliente para abrir la conversación.</li>
                        <li>Una vez en WhatsApp, podrá invitarlo al grupo creado.</li>
                        <li>Use el botón "<a href="invitaciones_whatsapp.php" class="font-medium text-green-600 hover:underline">Invitar a Grupo</a>" para mensajes con enlace predefinido.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<style>
/* Estilos personalizados para el scroll de sugerencias */
#sugerencias_busqueda_container::-webkit-scrollbar {
    width: 8px;
}

#sugerencias_busqueda_container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 6px;
}

#sugerencias_busqueda_container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
}

#sugerencias_busqueda_container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Estilos para Firefox */
#sugerencias_busqueda_container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Indicador visual de scroll */
.scroll-indicator {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(255,255,255,0.9), transparent);
    height: 20px;
    pointer-events: none;
    display: none;
}

.has-scroll .scroll-indicator {
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Inicializando sistema de búsqueda...');
    
    const searchInput = document.getElementById('busqueda_cliente_input');
    const suggestionsContainer = document.getElementById('sugerencias_busqueda_container');
    const searchIcon = document.getElementById('search_icon');
    const clearButton = document.getElementById('clear_search');
    const charCounter = document.getElementById('char_counter');
    let debounceTimer;

    // Verificar elementos esenciales
    if (!searchInput || !suggestionsContainer) {
        console.error('❌ Elementos de búsqueda no encontrados');
        return;
    }

    // Funciones de utilidad
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
            // Resetear estilos de scroll
            suggestionsContainer.style.maxHeight = '';
            suggestionsContainer.classList.remove('has-scroll');
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
                charCounter.textContent = length + ' caracteres';
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

    function showResults(data, query) {
        suggestionsContainer.innerHTML = '';
        
        // Determinar altura basada en número de resultados
        const maxVisibleItems = 7;
        const itemHeight = 68; // Altura aproximada de cada item en píxeles
        const hasScroll = data.length > maxVisibleItems;
        
        // Ajustar altura dinámicamente
        if (hasScroll) {
            suggestionsContainer.style.maxHeight = (maxVisibleItems * itemHeight) + 'px';
            suggestionsContainer.classList.add('has-scroll');
        } else {
            suggestionsContainer.style.maxHeight = 'none';
            suggestionsContainer.classList.remove('has-scroll');
        }
        
        data.forEach((item, index) => {
            const a = document.createElement('a');
            a.className = 'block px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors duration-150';
            
            let displayText = '<div class="flex items-center justify-between">';
            displayText += '<div>';
            displayText += '<div class="font-medium text-gray-900">' + (item.nombre_completo || 'N/A') + '</div>';
            displayText += '<div class="text-xs text-gray-500 mt-1">';
            
            if (item.numero_tarjeta) {
                displayText += '<span class="mr-3"><i class="fas fa-credit-card mr-1"></i>' + item.numero_tarjeta + '</span>';
            }
            if (item.telefono) {
                displayText += '<span><i class="fas fa-phone mr-1"></i>' + item.telefono + '</span>';
            }
            
            displayText += '</div></div>';
            
            if (item.estado && item.estado !== 'activo') {
                displayText += '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">' + item.estado + '</span>';
            } else {
                displayText += '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Activo</span>';
            }
            
            displayText += '</div>';
            a.innerHTML = displayText;

            a.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('👆 Sugerencia seleccionada:', item.nombre_completo);
                searchInput.value = item.nombre_completo; 
                hideSuggestions();
                
                // Navegar a la página con los resultados de búsqueda
                const searchUrl = window.location.pathname + '?busqueda=' + encodeURIComponent(item.nombre_completo);
                console.log('🔍 Navegando a:', searchUrl);
                window.location.href = searchUrl;
            });
            
            suggestionsContainer.appendChild(a);
        });
        
        // Agregar indicador de scroll si es necesario
        if (hasScroll) {
            const scrollIndicator = document.createElement('div');
            scrollIndicator.className = 'scroll-indicator';
            scrollIndicator.innerHTML = '<div class="text-center py-1 text-xs text-gray-500"><i class="fas fa-chevron-down"></i> Más resultados abajo</div>';
            suggestionsContainer.appendChild(scrollIndicator);
            
            // Mostrar/ocultar indicador basado en scroll
            suggestionsContainer.addEventListener('scroll', function() {
                const isAtBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 5;
                scrollIndicator.style.display = isAtBottom ? 'none' : 'block';
            });
        }
        
        showSuggestions();
        console.log('📊 Mostrando ' + data.length + ' resultados' + (hasScroll ? ' (con scroll)' : ''));
    }

    function showNoResults() {
        suggestionsContainer.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500 text-center">No se encontraron clientes</div>';
        showSuggestions();
        setTimeout(() => hideSuggestions(), 2000);
    }

    function showError(message) {
        suggestionsContainer.innerHTML = '<div class="px-4 py-3 text-sm text-red-600 text-center">' + message + '</div>';
        showSuggestions();
        setTimeout(() => hideSuggestions(), 3000);
    }

    // Eventos del input de búsqueda
    searchInput.addEventListener('focus', function() {
        console.log('🎯 Campo enfocado');
        this.classList.add('ring-4', 'ring-blue-100');
        if (this.value.length > 0) {
            toggleClearButton(true);
        }
    });

    searchInput.addEventListener('blur', function() {
        this.classList.remove('ring-4', 'ring-blue-100');
        setTimeout(() => hideSuggestions(), 150);
    });
        
    // Evento input principal
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        console.log('📝 Búsqueda:', query, 'Length:', query.length);
        
        // Actualizar contador y botón limpiar
        updateCharCounter(query.length);
        toggleClearButton(query.length > 0);
        
        if (query.length < 1) {
            hideSuggestions();
            updateSearchIcon(false);
            return;
        }
        
        // Mostrar indicador de búsqueda
        updateSearchIcon(true);
        suggestionsContainer.innerHTML = '<div class="px-6 py-4 text-center"><div class="flex items-center justify-center space-x-2"><div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full"></div><div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full" style="animation-delay: 0.1s"></div><div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full" style="animation-delay: 0.2s"></div><span class="ml-2 text-sm text-gray-600">Buscando clientes...</span></div></div>';
        showSuggestions();

        debounceTimer = setTimeout(() => {
            console.log('🔍 Ejecutando búsqueda para:', query);
            const url = 'autocomplete_sugerencias.php?term=' + encodeURIComponent(query) + '&solo_activos=0';
            
            fetch(url)
                .then(response => {
                    updateSearchIcon(false);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('📡 Respuesta recibida');
                    
                    if (!text.trim()) {
                        showNoResults();
                        return;
                    }
                    
                    try {
                        const data = JSON.parse(text);
                        
                        if (data.error) {
                            showError(data.error);
                            return;
                        }
                        
                        if (Array.isArray(data) && data.length > 0) {
                            showResults(data, query);
                        } else {
                            showNoResults();
                        }
                        
                    } catch (jsonError) {
                        console.error('❌ JSON Error:', jsonError);
                        showError('Error al procesar respuesta del servidor');
                    }
                })
                .catch(error => {
                    console.error('🚨 Fetch Error:', error);
                    updateSearchIcon(false);
                    showError('Error de conexión. Intenta de nuevo.');
                });
        }, 300);
    });

    // Botón limpiar
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            hideSuggestions();
            toggleClearButton(false);
            updateCharCounter(0);
        });
    }

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(event) {
        if (suggestionsContainer && searchInput && 
            !searchInput.contains(event.target) && 
            !suggestionsContainer.contains(event.target)) {
            console.log('🖱️ Click fuera, cerrando sugerencias');
            hideSuggestions();
        }
    });
    
    // Manejar teclas de navegación
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            console.log('⌨️ Escape presionado, cerrando sugerencias');
            hideSuggestions();
        } else if (e.key === 'Enter') {
            console.log('⌨️ Enter presionado, previniendo envío de formulario');
            e.preventDefault();
            return false;
        }
    });
    
    console.log('🎉 Sistema de búsqueda inicializado correctamente');
});
</script>

<?php 
include_once __DIR__ . "/../layout/footer.php"; 
?>
