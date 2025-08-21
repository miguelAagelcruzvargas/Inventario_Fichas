<?php
// inventarios/crear_inventario.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Crear Nuevo Inventario - Gestión Liconsa"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; // Ajusta si tu carpeta de proyecto es diferente
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

// Asegúrate que layout/header.php es la versión con menú Tailwind integrado
include_once __DIR__ . "/../layout/header.php"; 
// La línea para incluir menu.php ya fue eliminada.
include_once __DIR__ . "/Inventario.php";

$inventario = new Inventario(); // Usado para la creación
$mensaje = '';
$tipo_mensaje = ''; 
$inventario_creado_exitosamente = false;

// Verificar si ya existe un inventario activo
$inventario_activo_obj = new Inventario(); 
$inventario_activo = false;
$inventario_activo_id_para_cerrar = null;
$nombre_mes_inventario_activo = '';
$anio_inventario_activo = '';

if($inventario_activo_obj->obtenerInventarioActivo()) { 
    $inventario_activo = true;
    $inventario_activo_id_para_cerrar = $inventario_activo_obj->id;
    $nombre_mes_inventario_activo = $inventario_activo_obj->obtenerNombreMes();
    $anio_inventario_activo = $inventario_activo_obj->anio;

    $mensaje = "Ya existe un inventario abierto para " . htmlspecialchars($nombre_mes_inventario_activo) . " " . htmlspecialchars($anio_inventario_activo) . ". Debe cerrarlo antes de crear uno nuevo.";
    $tipo_mensaje = "warning";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$inventario_activo) {
    if (
        !empty($_POST['mes']) &&
        !empty($_POST['anio']) &&
        isset($_POST['cajas_ingresadas']) && $_POST['cajas_ingresadas'] !== '' &&
        isset($_POST['sobres_por_caja']) && $_POST['sobres_por_caja'] !== '' &&
        isset($_POST['precio_sobre']) && $_POST['precio_sobre'] !== ''
    ) {
        $inventario->mes = $_POST['mes'];
        $inventario->anio = $_POST['anio'];
        $inventario->cajas_ingresadas = (int)$_POST['cajas_ingresadas'];
        $inventario->sobres_por_caja = (int)$_POST['sobres_por_caja'];
        $precio_sobre_input = str_replace(',', '.', $_POST['precio_sobre']);
        $inventario->precio_sobre = (float)$precio_sobre_input;

        if ($inventario->cajas_ingresadas < 0) {
            $mensaje = "El número de cajas ingresadas no puede ser negativo.";
            $tipo_mensaje = "warning";
        } elseif ($inventario->sobres_por_caja < 1) {
            $mensaje = "El número de sobres por caja debe ser al menos 1.";
            $tipo_mensaje = "warning";
        } elseif ($inventario->precio_sobre <= 0) {
            $mensaje = "El precio por sobre debe ser mayor que cero.";
            $tipo_mensaje = "warning";
        } else {
            $inventario_id = $inventario->crear();
            if ($inventario_id) {
                $mensaje = "Inventario para " . htmlspecialchars($inventario->obtenerNombreMes()) . " " . htmlspecialchars($inventario->anio) . " creado correctamente. Redirigiendo al dashboard...";
                $tipo_mensaje = "success";
                $inventario_creado_exitosamente = true;
                
                echo "<script>
                        setTimeout(function() {
                            window.location.href = '../resumen/dashboard.php?inventario_id=" . $inventario_id . "';
                        }, 2000);
                      </script>";
            } else {
                $mensaje = "No se pudo crear el inventario. Es probable que ya exista un inventario para ". htmlspecialchars(Inventario::nombreMesStatic((int)$_POST['mes'])) ." " . htmlspecialchars($_POST['anio']) .".";
                $tipo_mensaje = "danger";
            }
        }
    } else {
        $mensaje = "Por favor, complete todos los campos requeridos.";
        $tipo_mensaje = "warning";
    }
}

$mes_actual = date('n');
$anio_actual = date('Y');

$alert_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = $alert_styles[$tipo_mensaje] ?? $alert_styles['info'];
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-2xl mx-auto">

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

        <div class="bg-white shadow-2xl rounded-xl border border-gray-200">
            <div class="p-6 sm:p-8 md:p-10">
                <div class="text-center mb-8">
                    <i class="fas fa-calendar-plus fa-3x text-liconsa-blue mb-4"></i>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Crear Nuevo Inventario</h2>
                    <p class="text-gray-500 mt-2 text-sm sm:text-base">Defina los parámetros para el nuevo periodo de inventario.</p>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                            </div>
                            <div class="ml-3 flex-grow">
                                <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_mensaje)); ?></p>
                                <p class="text-xs md:text-sm"><?php echo $mensaje; ?></p>
                                <?php if ($inventario_activo && !$inventario_creado_exitosamente && $inventario_activo_id_para_cerrar): ?>
                                    <div class="mt-2">
                                        <a href="cerrar_inventario.php?id=<?php echo $inventario_activo_id_para_cerrar; ?>" class="font-medium underline hover:text-opacity-80 <?php echo $current_alert_style['text']; ?>">Cerrar inventario actual</a> o 
                                        <a href="../resumen/dashboard.php" class="font-medium underline hover:text-opacity-80 <?php echo $current_alert_style['text']; ?>">Ir al Dashboard</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!$inventario_creado_exitosamente): ?>
                            <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').style.display='none';" aria-label="Close">
                                <span class="sr-only">Cerrar</span>
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$inventario_activo || $inventario_creado_exitosamente): ?>
                    <?php if (!$inventario_creado_exitosamente): ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="mes" class="block text-sm font-medium text-gray-700 mb-1">Mes <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select id="mes" name="mes" required 
                                            class="w-full py-2.5 pl-3 pr-10 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm appearance-none">
                                        <option value="">Seleccione un mes</option>
                                        <?php 
                                        $mes_seleccionado_form = isset($_POST['mes']) ? $_POST['mes'] : $mes_actual;
                                        for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo ($m == $mes_seleccionado_form) ? 'selected' : ''; ?>>
                                                <?php echo Inventario::nombreMesStatic($m); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="anio" class="block text-sm font-medium text-gray-700 mb-1">Año <span class="text-red-500">*</span></label>
                                 <div class="relative">
                                    <select id="anio" name="anio" required 
                                            class="w-full py-2.5 pl-3 pr-10 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm appearance-none">
                                        <option value="">Seleccione un año</option>
                                        <?php 
                                        $anio_seleccionado_form = isset($_POST['anio']) ? $_POST['anio'] : $anio_actual;
                                        for($i = $anio_actual - 1; $i <= $anio_actual + 2; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($i == $anio_seleccionado_form) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                            
                        <div>
                            <label for="cajas_ingresadas" class="block text-sm font-medium text-gray-700 mb-1">Número de Cajas Ingresadas <span class="text-red-500">*</span></label>
                            <input type="number" id="cajas_ingresadas" name="cajas_ingresadas" min="0" required 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm" 
                                   value="<?php echo isset($_POST['cajas_ingresadas']) ? htmlspecialchars($_POST['cajas_ingresadas']) : ''; ?>"
                                   placeholder="Ej: 100">
                        </div>
                        
                        <div>
                            <label for="sobres_por_caja" class="block text-sm font-medium text-gray-700 mb-1">Sobres por Caja <span class="text-red-500">*</span></label>
                            <input type="number" id="sobres_por_caja" name="sobres_por_caja" 
                                   value="<?php echo isset($_POST['sobres_por_caja']) ? htmlspecialchars($_POST['sobres_por_caja']) : '36'; ?>" 
                                   min="1" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm">
                            <p class="mt-1 text-xs text-gray-500">Cantidad de sobres por cada caja. Por defecto: 36.</p>
                        </div>
                        
                        <div>
                            <label for="precio_sobre" class="block text-sm font-medium text-gray-700 mb-1">Precio por Sobre <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="text" inputmode="decimal" id="precio_sobre" name="precio_sobre" 
                                       value="<?php echo isset($_POST['precio_sobre']) ? htmlspecialchars(str_replace('.', ',', $_POST['precio_sobre'])) : '13.00'; ?>" 
                                       pattern="^\d+([,\.]\d{1,2})?$" title="Ej: 13.00 o 13,00"
                                       required class="w-full pl-7 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm">
                            </div>
                             <p class="mt-1 text-xs text-gray-500">Precio de cada sobre en pesos. Use punto o coma para decimales. Por defecto: $13.00.</p>
                        </div>
                        
                        <hr class="my-4 md:my-6 border-gray-200">

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 gap-3">
                            <a href="<?php echo $baseUrl; ?>/inventarios/historial_inventarios.php" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-6 rounded-lg shadow-sm hover:shadow-md flex items-center justify-center text-sm transition duration-150 ease-in-out">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" class="w-full sm:w-auto bg-liconsa-blue hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                                <i class="fas fa-plus-circle mr-2"></i>Crear Inventario
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                     <div class="text-center">
                        <p class="text-lg text-gray-700 mt-4">Para crear un nuevo inventario, primero debe gestionar el inventario activo.</p>
                        <div class="mt-4">
                             <a href="../resumen/dashboard.php<?php if ($inventario_activo_id_para_cerrar) echo '?inventario_id=' . $inventario_activo_id_para_cerrar; ?>" class="bg-liconsa-blue hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg inline-flex items-center justify-center text-sm transition duration-150 ease-in-out">
                                <i class="fas fa-tachometer-alt mr-2"></i>Ir al Dashboard del Inventario Activo
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php 
include_once __DIR__ . "/../layout/footer.php"; 
?>
