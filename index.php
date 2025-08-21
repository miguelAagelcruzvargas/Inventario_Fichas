<?php
// index.php
// Este es tu dashboard principal, ubicado en la raíz del proyecto.

// HABILITAR VISUALIZACIÓN DE ERRORES PARA DEPURACIÓN (Solo para desarrollo, deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar la sesión de PHP si aún no está activa. Es crucial que esto esté al principio.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- VERIFICACIÓN DE SEGURIDAD: SÓLO ADMINISTRADORES PUEDEN ACCEDER ---
// Verificar si el usuario está logueado Y si su rol es 'admin'.
// Si alguna de estas condiciones no se cumple, el acceso es denegado.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Establecer un mensaje de error en la sesión.
    $_SESSION['error_message'] = "Acceso denegado. Debes ser un administrador para ver esta página.";
    // Redirigir al usuario a la página de login de administradores.
    // La ruta es relativa desde la raíz del proyecto hacia la carpeta 'login/login.php'.
    header('Location: login/login.php');
    exit(); // Terminar la ejecución del script para evitar que se muestre contenido no autorizado.
}

// Si el usuario es un administrador y está logueado, recuperamos su nombre de usuario.
// IMPORTANTE: Ahora recuperamos 'username' de la sesión, que fue guardado como 'nombre_usuario'
$username = $_SESSION['username'];

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Inicio - Gestión Liconsa"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; 
$baseUrl = $protocol . $host . $project_folder; 
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

// Incluir el header principal
include_once __DIR__ . "/layout/header.php"; 

// Incluir las clases necesarias
// ¡ASEGÚRATE DE QUE ESTAS LÍNEAS ESTÉN DESCOMENTADAS Y LAS RUTAS SEAN CORRECTAS!
// Asegúrate de que los archivos PHP de tus clases (Inventario.php, Cliente.php)
// estén en las rutas correctas relativas a index.php.
// Por ejemplo, si Inventario.php está en tu_proyecto/inventarios/Inventario.php
// y Cliente.php está en tu_proyecto/clientes/Cliente.php
// entonces estas rutas deberían ser correctas.
include_once __DIR__ . "/inventarios/Inventario.php"; 
include_once __DIR__ . "/clientes/Cliente.php"; 

$inventario_obj = new Inventario();
$hay_inventario_activo = $inventario_obj->obtenerInventarioActivo(); 
// Si hay inventario activo, $inventario_obj ahora tiene sus datos cargados.

$cliente_handler = new Cliente(); 
$total_clientes = $cliente_handler->contar(); 

?>

<main class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <!-- Header Principal con mejor diseño -->
    <div class="bg-white shadow-sm border-b border-gray-100">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-6 shadow-lg">
                    <i class="fas fa-glass-whiskey text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl md:text-4xl xl:text-5xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-4">
                    Sistema de Gestión Liconsa
                </h1>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto mb-6">
                    Administre eficientemente beneficiarios, inventarios y retiros de leche del programa social
                </p>
                
                <!-- Barra de usuario mejorada -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 bg-gray-50 rounded-2xl px-6 py-4 max-w-md mx-auto">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <span class="text-gray-700 font-semibold"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/login/logout.php"
                       class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-medium text-sm rounded-xl px-4 py-2.5 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales mejoradas -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto mb-16">
            <!-- Card de Beneficiarios -->
            <div class="group bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-200">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-users text-2xl text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Beneficiarios</h3>
                                <p class="text-gray-500 text-sm">Registrados en el sistema</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                <?php echo htmlspecialchars($total_clientes); ?>
                            </div>
                            <div class="text-sm text-gray-500">Total</div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-gray-100">
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/listar_clientes.php"
                            class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-list"></i>
                            <span>Ver Listado Completo</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card de Inventario -->
            <div class="group bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 <?php echo $hay_inventario_activo ? 'hover:border-green-200' : 'hover:border-yellow-200'; ?>">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 <?php echo $hay_inventario_activo ? 'bg-gradient-to-br from-green-500 to-green-600' : 'bg-gradient-to-br from-yellow-500 to-yellow-600'; ?> rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-boxes-stacked text-2xl text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Inventario</h3>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full <?php echo $hay_inventario_activo ? 'bg-green-500' : 'bg-yellow-500'; ?>"></div>
                                    <p class="text-sm font-medium <?php echo $hay_inventario_activo ? 'text-green-600' : 'text-yellow-600'; ?>">
                                        <?php echo $hay_inventario_activo ? 'Activo' : 'Sin inventario'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($hay_inventario_activo && $inventario_obj->id): ?>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-800">
                                    <?php echo htmlspecialchars($inventario_obj->obtenerNombreMes()); ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inventario_obj->anio); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pt-6 border-t border-gray-100">
                        <?php if ($hay_inventario_activo && $inventario_obj->id): ?>
                            <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/dashboard.php?inventario_id=<?php echo htmlspecialchars($inventario_obj->id); ?>"
                                class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Ver Dashboard</span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($baseUrl); ?>/inventarios/crear_inventario.php"
                                class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                <i class="fas fa-plus-circle"></i>
                                <span>Crear Inventario</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Accesos Rápidos mejorada -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Accesos Rápidos</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Accede directamente a las funciones principales del sistema
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 max-w-7xl mx-auto">
            <!-- Card Clientes -->
            <div class="group bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-200 hover:-translate-y-1">
                <div class="h-2 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-users text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Clientes</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Administre los beneficiarios del programa. Registre nuevos o consulte los existentes.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/agregar_cliente.php"
                            class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-user-plus"></i>
                            <span>Agregar Cliente</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/listar_clientes.php"
                            class="w-full inline-flex items-center justify-center gap-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-list"></i>
                            <span>Ver Todos</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Inventarios -->
            <div class="group bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-green-200 hover:-translate-y-1">
                <div class="h-2 bg-gradient-to-r from-green-500 to-green-600"></div>
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-boxes-stacked text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Inventarios</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Gestione los inventarios mensuales de leche y consulte el historial completo.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (!$hay_inventario_activo): ?>
                            <a href="<?php echo htmlspecialchars($baseUrl); ?>/inventarios/crear_inventario.php"
                                class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                <i class="fas fa-plus-circle"></i>
                                <span>Crear Nuevo</span>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>/inventarios/historial_inventarios.php"
                            class="w-full inline-flex items-center justify-center gap-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-history"></i>
                            <span>Ver Historial</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Dashboard y Retiros -->
            <div class="group bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-yellow-200 hover:-translate-y-1 md:col-span-2 xl:col-span-1">
                <div class="h-2 bg-gradient-to-r from-yellow-500 to-orange-500"></div>
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-chart-line text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Dashboard</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Visualice resúmenes del inventario actual y registre las entregas de leche.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/dashboard.php<?php echo $hay_inventario_activo && $inventario_obj->id ? '?inventario_id='.htmlspecialchars($inventario_obj->id) : ''; ?>"
                            class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Ver Dashboard</span>
                        </a>
                        <?php if ($hay_inventario_activo && $inventario_obj->id): ?>
                            <a href="<?php echo htmlspecialchars($baseUrl); ?>/resumen/dashboard.php?inventario_id=<?php echo htmlspecialchars($inventario_obj->id); ?>#lista-clientes" 
                                class="w-full inline-flex items-center justify-center gap-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-semibold py-3.5 px-6 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                <i class="fas fa-hand-holding-heart"></i>
                                <span>Registrar Retiro</span>
                            </a>
                        <?php else: ?>
                            <div class="w-full inline-flex items-center justify-center gap-3 bg-gray-300 text-gray-500 font-semibold py-3.5 px-6 rounded-2xl cursor-not-allowed">
                                <i class="fas fa-hand-holding-heart"></i>
                                <span>Requiere Inventario</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . "/layout/footer.php"; 
?>
