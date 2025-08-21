<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Historial de Inventarios - Gestión Liconsa"; 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web';
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 

include_once __DIR__ . "/../layout/header.php";
include_once __DIR__ . "/Inventario.php";

$inventario_obj = new Inventario();
$stmt_inventarios = $inventario_obj->leer();

$mensaje_get = isset($_GET['mensaje']) ? urldecode($_GET['mensaje']) : '';
$tipo_mensaje_get = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$alert_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style_get = null;
if (!empty($tipo_mensaje_get) && isset($alert_styles[$tipo_mensaje_get])) {
    $current_alert_style_get = $alert_styles[$tipo_mensaje_get];
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

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">
                <i class="fas fa-history text-liconsa-blue mr-2"></i>
                Historial de Inventarios
            </h1>
            <a href="crear_inventario.php" class="w-full sm:w-auto bg-liconsa-blue hover:bg-blue-700 text-white font-semibold py-2 px-4 sm:py-2.5 sm:px-6 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                <i class="fas fa-plus-circle mr-2"></i>Crear Nuevo
            </a>
        </div>
        
        <?php if (!empty($mensaje_get) && $current_alert_style_get): ?>
            <div class="<?php echo $current_alert_style_get['bg']; ?> border-l-4 <?php echo $current_alert_style_get['border']; ?> <?php echo $current_alert_style_get['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="<?php echo $current_alert_style_get['icon']; ?> fa-lg <?php echo $current_alert_style_get['icon_text']; ?> mr-3 mt-1"></i>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_mensaje_get)); ?></p>
                        <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_get); ?></p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style_get['text']; ?>" onclick="this.closest('[role=alert]').style.display='none';" aria-label="Close">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($stmt_inventarios && $stmt_inventarios->rowCount() > 0): ?>
            <div class="bg-white shadow-xl rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mes/Año</th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Cajas</th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden md:table-cell">Sob/Caja</th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sob. Tot.</th>
                                <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden lg:table-cell">Creación</th>
                                <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $stmt_inventarios->fetch(PDO::FETCH_ASSOC)): 
                                $inv_temp = new Inventario();
                                $inv_temp->cajas_ingresadas = $row['cajas_ingresadas'];
                                $inv_temp->sobres_por_caja = $row['sobres_por_caja'];
                                $sobres_totales = $inv_temp->calcularSobresTotales();
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo Inventario::nombreMesStatic((int)$row['mes']) . ' ' . htmlspecialchars($row['anio']); ?>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell"><?php echo htmlspecialchars($row['cajas_ingresadas']); ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell"><?php echo htmlspecialchars($row['sobres_por_caja']); ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($sobres_totales); ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                        <?php if ($row['estado'] == 'abierto'): ?>
                                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-lock-open mr-1 opacity-75"></i>Abierto
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">
                                                <i class="fas fa-lock mr-1 opacity-75"></i>Cerrado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 hidden lg:table-cell">
                                        <?php echo $row['fecha_creacion'] ? date('d/m/y H:i', strtotime($row['fecha_creacion'])) : '-'; ?>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php if ($row['estado'] == 'abierto'): ?>
                                                <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/dashboard.php?inventario_id=<?php echo $row['id']; ?>" class="text-sky-600 hover:text-sky-800 transition-colors duration-150 p-1 rounded-md hover:bg-sky-100" title="Continuar Gestión">
                                                    <i class="fas fa-play-circle fa-lg"></i>
                                                </a>
                                                <a href="cerrar_inventario.php?id=<?php echo $row['id']; ?>&origen=historial_inventarios.php" class="text-amber-600 hover:text-amber-800 transition-colors duration-150 p-1 rounded-md hover:bg-amber-100" title="Cerrar Inventario" 
                                                   onclick="return confirm('¿Está seguro de cerrar el inventario para <?php echo Inventario::nombreMesStatic((int)$row['mes']) . ' ' . htmlspecialchars($row['anio']); ?>?\n\nEsta acción no se puede deshacer.')">
                                                    <i class="fas fa-archive fa-lg"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/resumen_inventario.php?inventario_id=<?php echo $row['id']; ?>" class="text-gray-500 hover:text-gray-700 transition-colors duration-150 p-1 rounded-md hover:bg-gray-100" title="Ver Detalles">
                                                    <i class="fas fa-eye fa-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="<?php echo $alert_styles['info']['bg']; ?> border-l-4 <?php echo $alert_styles['info']['border']; ?> <?php echo $alert_styles['info']['text']; ?> p-6 rounded-md shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                         <i class="<?php echo $alert_styles['info']['icon']; ?> fa-2x <?php echo $alert_styles['info']['icon_text']; ?> mr-3"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium <?php echo $alert_styles['info']['text']; ?>">No hay inventarios</h3>
                        <div class="mt-2 text-sm <?php echo $alert_styles['info']['text']; ?>">
                            <p>Actualmente no hay inventarios registrados en el sistema.</p>
                        </div>
                        <div class="mt-4">
                            <a href="crear_inventario.php" class="font-semibold underline <?php echo $alert_styles['info']['text']; ?> hover:<?php echo $alert_styles['info']['text']; ?> hover:opacity-80">
                                <i class="fas fa-plus-circle mr-1"></i>Crear el primer inventario
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once __DIR__ . "/../layout/footer.php"; ?>
