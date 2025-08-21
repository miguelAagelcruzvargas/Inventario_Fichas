<?php
require_once '../config/conexion.php'; // Asegúrate que $pdo se inicialice aquí
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Resumen de Inventario"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; 
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

// Validar inventario_id
if (!isset($_GET['inventario_id']) || !is_numeric($_GET['inventario_id'])) {
    $_SESSION['mensaje_accion'] = 'ID de inventario no válido o no proporcionado.';
    $_SESSION['tipo_mensaje_accion'] = 'danger';
    header("Location: " . htmlspecialchars($baseUrl) . "/inventarios/historial_inventarios.php");
    exit;
}
$inventario_id = (int) $_GET['inventario_id'];

// Cargar información del inventario
if (!isset($pdo)) { // Asumiendo que $pdo se define en conexion.php
    $_SESSION['mensaje_accion'] = 'Error de conexión a la base de datos.';
    $_SESSION['tipo_mensaje_accion'] = 'danger';
    header("Location: " . htmlspecialchars($baseUrl) . "/inventarios/historial_inventarios.php");
    exit;
}

$stmt_inv = $pdo->prepare("SELECT * FROM inventarios WHERE id = ?");
$stmt_inv->execute([$inventario_id]);
$inventario_data = $stmt_inv->fetch(PDO::FETCH_ASSOC);

if (!$inventario_data) {
    $_SESSION['mensaje_accion'] = 'Inventario con ID ' . $inventario_id . ' no encontrado.';
    $_SESSION['tipo_mensaje_accion'] = 'warning';
    header("Location: " . htmlspecialchars($baseUrl) . "/inventarios/historial_inventarios.php");
    exit;
}

// Incluir la clase Inventario para usar nombreMesStatic
$nombre_mes_inventario = "Inventario"; // Valor por defecto
if (file_exists(__DIR__ . "/../inventarios/Inventario.php")) {
    include_once __DIR__ . "/../inventarios/Inventario.php";
    if (isset($inventario_data['mes']) && isset($inventario_data['anio'])) {
        $nombre_mes_inventario = Inventario::nombreMesStatic((int)$inventario_data['mes']) . " " . htmlspecialchars($inventario_data['anio']);
        $page_title = "Resumen: " . $nombre_mes_inventario;
    } elseif (isset($inventario_data['mes_anio'])) { // Campo combinado como fallback
        $nombre_mes_inventario = htmlspecialchars($inventario_data['mes_anio']);
        $page_title = "Resumen: " . $nombre_mes_inventario;
    } else {
        $page_title = "Resumen de Inventario ID: " . $inventario_id;
        $nombre_mes_inventario = 'ID: ' . $inventario_id;
    }
} elseif (isset($inventario_data['mes_anio'])) {
     $nombre_mes_inventario = htmlspecialchars($inventario_data['mes_anio']);
     $page_title = "Resumen: " . $nombre_mes_inventario;
}


// Calcular totales de retiros
$stmt_retiros = $pdo->prepare("
    SELECT 
      SUM(sobres_retirados) AS total_sobres_retirados,
      SUM(monto_pagado) AS total_monto_recaudado 
    FROM retiros
    WHERE inventario_id = ? AND retiro = 1 
");
$stmt_retiros->execute([$inventario_id]);
$resultados_retiros = $stmt_retiros->fetch(PDO::FETCH_ASSOC);

$cajas_ingresadas_val = (int) ($inventario_data['cajas_ingresadas'] ?? 0);
$sobres_por_caja_val = (int) ($inventario_data['sobres_por_caja'] ?? 0);
$precio_sobre_val = (float) ($inventario_data['precio_sobre'] ?? 0.00); // Asegurarse que el nombre del campo sea 'precio_sobre'

$sobres_totales_val = $cajas_ingresadas_val * $sobres_por_caja_val;
$sobres_retirados_val = (int) ($resultados_retiros['total_sobres_retirados'] ?? 0);
$sobres_restantes_val = $sobres_totales_val - $sobres_retirados_val;

$cajas_restantes_val = ($sobres_por_caja_val > 0) ? intdiv($sobres_restantes_val, $sobres_por_caja_val) : 0;
$sobres_sueltos_val = ($sobres_por_caja_val > 0) ? ($sobres_restantes_val % $sobres_por_caja_val) : $sobres_restantes_val;

$monto_recaudado_val = (float) ($resultados_retiros['total_monto_recaudado'] ?? 0.00);
$valor_total_inventario_val = $sobres_totales_val * $precio_sobre_val;

// Manejo de mensajes de notificación con Tailwind
$mensaje_notificacion_get = '';
$tipo_notificacion_get = '';
if (isset($_SESSION['mensaje_accion'])) {
    $mensaje_notificacion_get = $_SESSION['mensaje_accion'];
    $tipo_notificacion_get = $_SESSION['tipo_mensaje_accion'] ?? 'info';
    unset($_SESSION['mensaje_accion']);
    unset($_SESSION['tipo_mensaje_accion']);
}

$alert_tailwind_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = null;
if (!empty($tipo_notificacion_get) && isset($alert_tailwind_styles[$tipo_notificacion_get])) {
    $current_alert_style = $alert_tailwind_styles[$tipo_notificacion_get];
}

include_once __DIR__ . "/../layout/header.php"; 
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-5xl mx-auto">

        <nav class="mb-6 text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex flex-wrap space-x-1 sm:space-x-2">
                <li class="flex items-center">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Inicio</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/inventarios/historial_inventarios.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Historial</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-700 font-medium">
                        <?php echo $nombre_mes_inventario; ?>
                    </span>
                </li>
            </ol>
        </nav>

        <?php if (!empty($mensaje_notificacion_get) && $current_alert_style): ?>
            <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_notificacion_get)); ?></p>
                        <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_notificacion_get); ?></p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').style.display='none';" aria-label="Close">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-xl rounded-xl border border-gray-200 p-6 sm:p-8 md:p-10">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-file-invoice-dollar text-liconsa-blue mr-3 text-3xl"></i>
                        Resumen del Inventario
                    </h1>
                    <p class="text-gray-500 mt-1">
                        <?php echo $nombre_mes_inventario; ?>
                    </p>
                </div>
                <span class="mt-3 sm:mt-0 px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo ($inventario_data['estado'] == 'abierto') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'; ?>">
                    <i class="fas <?php echo ($inventario_data['estado'] == 'abierto') ? 'fa-lock-open' : 'fa-lock'; ?> mr-1.5 opacity-75"></i>
                    <?php echo ucfirst(htmlspecialchars($inventario_data['estado'])); ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <h2 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-300 flex items-center">
                        <i class="fas fa-cogs mr-2 text-slate-500"></i>Configuración Inicial
                    </h2>
                    <div class="space-y-2.5 text-sm text-gray-700">
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Cajas Ingresadas:</strong> <?php echo $cajas_ingresadas_val; ?></p>
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Sobres por Caja:</strong> <?php echo $sobres_por_caja_val; ?></p>
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Precio por Sobre:</strong> $<?php echo number_format($precio_sobre_val, 2); ?></p>
                        <hr class="my-3 border-slate-200">
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Total de Sobres:</strong> <span class="font-bold text-slate-600"><?php echo number_format($sobres_totales_val); ?></span></p>
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Valor Total Inicial:</strong> <span class="font-bold text-slate-600">$<?php echo number_format($valor_total_inventario_val, 2); ?></span></p>
                    </div>
                </div>

                <div class="bg-lime-50 border border-lime-200 p-5 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <h2 class="text-lg font-semibold text-lime-700 mb-4 pb-2 border-b border-lime-300 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-lime-500"></i>Resultados del Periodo
                    </h2>
                    <div class="space-y-2.5 text-sm text-gray-700">
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Sobres Retirados:</strong> <?php echo number_format($sobres_retirados_val); ?></p>
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Sobres Restantes:</strong> <span class="font-bold text-lime-600"><?php echo number_format($sobres_restantes_val); ?></span></p>
                        <p class="ml-4 text-xs text-gray-500">(Equivalente a: <?php echo $cajas_restantes_val; ?> cajas y <?php echo $sobres_sueltos_val; ?> sobres)</p>
                        <hr class="my-3 border-lime-200">
                        <p><strong class="font-medium text-gray-800 w-40 inline-block">Monto Recaudado:</strong> <span class="font-bold text-lime-600">$<?php echo number_format($monto_recaudado_val, 2); ?></span></p>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-center sm:justify-between items-center gap-4">
                <a href="<?php echo htmlspecialchars($baseUrl); ?>/inventarios/historial_inventarios.php" 
                   class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition duration-150 ease-in-out">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Historial
                </a>
                <?php if ($inventario_data['estado'] == 'abierto'): ?>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/dashboard.php?inventario_id=<?php echo $inventario_id; ?>" 
                   class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-liconsa-blue hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition duration-150 ease-in-out">
                    <i class="fas fa-tasks mr-2"></i>Gestionar Retiros de este Inventario
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . "/../layout/footer.php"; ?>
