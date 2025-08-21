<?php
// resumen/dashboard.php
// Este archivo está ubicado dentro de la carpeta 'resumen/'.

// --- INICIO DE SESIÓN Y VERIFICACIÓN DE SEGURIDAD (CRÍTICO: DEBE SER LO PRIMERO) ---
// Iniciar la sesión de PHP si aún no está activa. Esto es VITAL y debe ser lo primero.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado Y si su rol es 'admin'.
// Si alguna de estas condiciones no se cumple, el acceso es denegado.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Establecer un mensaje de error en la sesión.
    $_SESSION['error_message'] = "Acceso denegado. Debes ser un administrador para ver esta página.";
    // Redirigir al usuario a la página de login de administradores.
    // La ruta sube un nivel (de 'resumen/' a la raíz) y luego baja a 'login/login.php'.
    header('Location: ../login/login.php');
    exit(); // Terminar la ejecución del script para evitar que se muestre contenido no autorizado.
}

// Si la verificación pasa, podemos continuar con el resto del script.
// La variable $username se mantiene por si se usa en otro lugar, pero no se mostrará aquí.
$username = $_SESSION['username'];

ob_start(); // Iniciar el buffer de salida DESPUÉS de la verificación de seguridad.

// HABILITAR VISUALIZACIÓN DE ERRORES PARA DEPURACIÓN (Solo para desarrollo, deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Dashboard - Resumen de Retiros";

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web';
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']);

// Incluir el header principal (asegúrate que las rutas son correctas desde 'resumen/')
// NOTA: Asegúrate que este header NO incluya Bootstrap CSS/JS para un diseño independiente
include_once __DIR__ . "/../layout/header.php";


// Incluir las clases necesarias (rutas correctas desde 'resumen/')
include_once __DIR__ . "/../inventarios/Inventario.php";
include_once __DIR__ . "/../clientes/Cliente.php"; // Ya verificado que tiene la clase Cliente
include_once __DIR__ . "/../retiros/retiro.php";

$inventario = new Inventario();
$inventario_especifico = false;
$inventario_activo = false;
$inventario_id_actual = null; // Para mantener el ID del inventario que se está visualizando

if (isset($_GET['inventario_id']) && !empty($_GET['inventario_id'])) {
    $inventario->id = (int)$_GET['inventario_id'];
    if ($inventario->leerUno()) {
        $inventario_especifico = true;
        $inventario_activo = true;
        $inventario_id_actual = $inventario->id;
    }
}

if (!$inventario_especifico) {
    $inventario_temporal = new Inventario(); // Usar una instancia temporal para no sobrescribir $inventario si ya tiene un ID
    if ($inventario_temporal->obtenerInventarioActivo()) {
        // Si se encontró un inventario activo y no se especificó uno por GET, usamos ese.
        $inventario = $inventario_temporal; // Ahora $inventario es el activo
        $inventario_activo = true;
        $inventario_id_actual = $inventario->id;
    }
}


$cliente = new Cliente();
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$registros_por_pagina = 12; // <--- CAMBIO AQUÍ: Mostrar 12 clientes por página
$inicio_desde = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$es_busqueda_activa = !empty($busqueda);

$resultado_clientes = null;
$total_clientes_a_mostrar = 0;
$total_paginas = 0;
$clientes_data_temp = [];

if ($inventario_activo) { // La lógica de clientes solo si hay un inventario activo/seleccionado
    if ($es_busqueda_activa) {
        $resultado_clientes = $cliente->buscar($busqueda);
        if ($resultado_clientes) {
            $total_clientes_a_mostrar = $resultado_clientes->rowCount(); // rowCount para búsqueda directa
            $clientes_data_temp = $resultado_clientes->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $resultado_clientes_paginados = $cliente->leerPaginadoActivos($inicio_desde, $registros_por_pagina);
        if ($resultado_clientes_paginados) {
            $clientes_data_temp = $resultado_clientes_paginados->fetchAll(PDO::FETCH_ASSOC);
            $total_clientes_a_mostrar = $cliente->contarActivos(); // contarActivos para la paginación de clientes activos
            if ($total_clientes_a_mostrar > 0) {
                $total_paginas = ceil($total_clientes_a_mostrar / $registros_por_pagina);
            }
        }
    }
}


function calcular_estadisticas_inventario($inventario_obj) {
    if (!$inventario_obj || !isset($inventario_obj->id) || !$inventario_obj->id) {
        return null;
    }
    if(!$inventario_obj->leerUno()){
        return null;
    }
    
    $sobres_totales_calc = $inventario_obj->calcularSobresTotales();
    $sobres_retirados_calc = $inventario_obj->calcularSobresRetirados();
    $precio_sobre_num = (float)($inventario_obj->precio_sobre ?? 0);

    return [
        'sobres_totales' => $sobres_totales_calc,
        'sobres_retirados' => $sobres_retirados_calc,
        'sobres_restantes' => $sobres_totales_calc - $sobres_retirados_calc,
        'cajas_restantes' => $inventario_obj->sobres_por_caja > 0 ? floor(($sobres_totales_calc - $sobres_retirados_calc) / $inventario_obj->sobres_por_caja) : 0,
        'dinero_recaudado' => (float)($inventario_obj->calcularDineroRecaudado() ?? 0),
        'valor_total_inventario' => $sobres_totales_calc * $precio_sobre_num,
        'porcentaje_retirado' => ($sobres_totales_calc > 0) ? round(($sobres_retirados_calc / $sobres_totales_calc) * 100, 2) : 0,
        'cajas_ingresadas' => $inventario_obj->cajas_ingresadas ?? 0,
        'sobres_por_caja' => $inventario_obj->sobres_por_caja ?? 0,
        'precio_sobre' => $precio_sobre_num,
        'nombre_mes' => $inventario_obj->obtenerNombreMes(),
        'anio' => $inventario_obj->anio,
        'estado' => $inventario_obj->estado
    ];
}

$estadisticas_inventario_actuales = null;
$sobres_totales = 0; $sobres_retirados = 0; $sobres_restantes = 0;
$cajas_restantes = 0; $dinero_recaudado = 0.00; $valor_total = 0.00;
$porcentaje_retirado = 0; $cajas_ingresadas_display = 0; $sobres_por_caja_display = 0;
$precio_sobre_display = 0.00; $nombre_mes_display = 'N/A'; $anio_display = 'N/A'; $estado_inventario_display = 'N/A';


if ($inventario_activo && $inventario->id) {
    $estadisticas_inventario_actuales = calcular_estadisticas_inventario($inventario);
    if ($estadisticas_inventario_actuales) {
        $sobres_totales = $estadisticas_inventario_actuales['sobres_totales'];
        $sobres_retirados = $estadisticas_inventario_actuales['sobres_retirados'];
        $sobres_restantes = $estadisticas_inventario_actuales['sobres_restantes'];
        $cajas_restantes = $estadisticas_inventario_actuales['cajas_restantes'];
        $dinero_recaudado = $estadisticas_inventario_actuales['dinero_recaudado'];
        $valor_total = $estadisticas_inventario_actuales['valor_total_inventario'];
        $porcentaje_retirado = $estadisticas_inventario_actuales['porcentaje_retirado'];
        $cajas_ingresadas_display = $estadisticas_inventario_actuales['cajas_ingresadas'];
        $sobres_por_caja_display = $estadisticas_inventario_actuales['sobres_por_caja'];
        $precio_sobre_display = $estadisticas_inventario_actuales['precio_sobre'];
        $nombre_mes_display = $estadisticas_inventario_actuales['nombre_mes'];
        $anio_display = $estadisticas_inventario_actuales['anio'];
        $estado_inventario_display = $estadisticas_inventario_actuales['estado'];
    }
}


$mensaje_notificacion = '';
$tipo_notificacion = 'info';

// Definir estilos de alertas
$alert_tailwind_styles = [
    'info'    => ['icon' => 'fa-info-circle'],
    'success' => ['icon' => 'fa-check-circle'],
    'warning' => ['icon' => 'fa-exclamation-triangle'],
    'danger'  => ['icon' => 'fa-times-circle'],
];

if (isset($_SESSION['mensaje_accion'])) {
    $mensaje_notificacion = $_SESSION['mensaje_accion'];
    $tipo_notificacion = isset($_SESSION['tipo_mensaje_accion']) ? $_SESSION['tipo_mensaje_accion'] : 'info';
    unset($_SESSION['mensaje_accion']);
    unset($_SESSION['tipo_mensaje_accion']);
} elseif (isset($_GET['mensaje'])) {
    $mensaje_notificacion = htmlspecialchars($_GET['mensaje']);
    $tipo_notificacion = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'info';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $inventario_activo && isset($inventario->estado) && $inventario->estado === 'abierto') {
    if (isset($_POST['cliente_id']) && isset($_POST['dotacion_maxima_form'])) {
        
        $cliente_id_post = (int)$_POST['cliente_id'];
        $dotacion_maxima_post = (int)$_POST['dotacion_maxima_form'];

        $cliente_actualizar = new Cliente();
        $cliente_actualizar->id = $cliente_id_post;
        $dotacion_actualizada_info = false;
        
        if ($cliente_actualizar->leerUno()) {
            if ($cliente_actualizar->dotacion_maxima != $dotacion_maxima_post && $dotacion_maxima_post >=0) {
                if($cliente_actualizar->actualizarDotacionPorId($cliente_id_post, $dotacion_maxima_post)){
                    $dotacion_actualizada_info = true;
                }
            }
        }

        $retiro = new Retiro();
        $retiro->cliente_id = $cliente_id_post;
        $retiro->inventario_id = $inventario->id;
        $retiro_existente_antes_guardar = $retiro->leer();

        $retiro->retiro = isset($_POST['retiro_checkbox']) && $_POST['retiro_checkbox'] === 'si';

        if ($retiro->retiro) {
            $retiro->sobres_retirados = isset($_POST['sobres_retirados_form']) && (int)$_POST['sobres_retirados_form'] >= 0 ?
                                        (int)$_POST['sobres_retirados_form'] : $dotacion_maxima_post;
        } else {
            $retiro->sobres_retirados = 0;
        }
        
        $precio_sobre_actual = isset($inventario->precio_sobre) ? (float)$inventario->precio_sobre : 0;
        $retiro->monto_pagado = $retiro->sobres_retirados * $precio_sobre_actual;

        $json_response = [];

        if ($retiro->guardar()) {
            $accion = $retiro_existente_antes_guardar ? 'actualizado' : 'registrado';
            $json_response['status'] = 'success';
            $json_response['message'] = "Retiro {$accion} correctamente.";
            
            if ($dotacion_actualizada_info) {
                $json_response['message'] .= " Dotación máxima actualizada a {$dotacion_maxima_post}.";
            }

            $json_response['tipo_mensaje'] = "success";
            $json_response['retiro_data'] = [
                'sobres_retirados' => $retiro->sobres_retirados,
                'monto_pagado' => $retiro->monto_pagado,
                'retiro_realizado' => $retiro->retiro,
            ];
            $json_response['dotacion_maxima'] = $dotacion_maxima_post; 
            
            // Verificar si existe registro de retiro después de guardar
            $retiro_verificar = new Retiro();
            $retiro_verificar->cliente_id = $cliente_id_post;
            $retiro_verificar->inventario_id = $inventario->id;
            $retiro_existente_despues_guardar = $retiro_verificar->leer();
            
            $json_response['retiro_info_exists'] = $retiro_existente_despues_guardar ? true : false;
            $json_response['cliente_id'] = $cliente_id_post;
            
            // Log de depuración para el servidor
            error_log("RETIRO DEBUG - Cliente ID: {$cliente_id_post}, Retiro realizado: " . ($retiro->retiro ? 'SÍ' : 'NO') . ", Retiro existe: " . ($retiro_existente_despues_guardar ? 'SÍ' : 'NO'));
            
            // Recalcular estadísticas después de guardar el retiro
            $inventario_actualizado_para_stats = new Inventario();
            $inventario_actualizado_para_stats->id = $inventario->id;
            $json_response['global_stats'] = calcular_estadisticas_inventario($inventario_actualizado_para_stats);

        } else {
            $json_response['status'] = 'error';
            $json_response['message'] = "Error al procesar el retiro.";
            $json_response['tipo_mensaje'] = "danger";
            $json_response['cliente_id'] = $cliente_id_post;
        }

        if (ob_get_length() > 0) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        echo json_encode($json_response);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Incluir Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Importar fuente Inter */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #007bff;
            --primary-blue-dark: #0056b3;
            --secondary-gray: #6c757d;
            --success-green: #28a745;
            --warning-yellow: #ffc107;
            --danger-red: #dc3545;
            --info-cyan: #17a2b8;

            --bg-light: #f8f9fa;
            --bg-dark: #343a40;
            --text-color: #333;
            --text-light-color: #f8f9fa;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            --border-color: #e9ecef;
            --input-bg: #f5f8fa;
            --input-border: #ced4da;
            --input-focus-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
            color: var(--text-color);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            box-sizing: border-box;
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        /* Encabezados de sección */
        .section-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-blue);
            color: var(--primary-blue-dark);
        }
        .section-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .section-header p {
            font-size: 1.1em;
            color: var(--text-light);
        }

        /* Estilos para cards generales */
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color); /* Borde sutil */
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .card-header h3 {
            font-size: 1.5em;
            color: var(--primary-blue);
            margin: 0;
        }
        .card-header .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            color: #fff;
        }
        .badge.success { background-color: var(--success-green); }
        .badge.secondary { background-color: var(--secondary-gray); }
        .badge.warning { background-color: var(--warning-yellow); }
        .badge.danger { background-color: var(--danger-red); }

        /* Grid para las estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            background-color: var(--bg-light); /* Fondo más claro para los ítems de stats */
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .stat-item .icon {
            font-size: 1.8em;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        .stat-item .label {
            font-size: 0.8em;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 500;
        }
        .stat-item .value {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--text-dark);
        }
        .stat-item .value.currency::before {
            content: "$";
        }
        .stat-item.success .icon, .stat-item.success .value { color: var(--success-green); }
        .stat-item.warning .icon, .stat-item.warning .value { color: var(--warning-yellow); }
        .stat-item.info .icon, .stat-item.info .value { color: var(--info-cyan); }


        /* Barra de progreso personalizada */
        .custom-progress-bar {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 20px;
        }
        .custom-progress-bar-fill {
            height: 100%;
            background-color: var(--primary-blue);
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
        }
        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-size: 0.9em;
            font-weight: 600;
            color: var(--primary-blue-dark);
        }

        /* Botones generales */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95em;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
            box-sizing: border-box;
        }
        .btn i {
            margin-right: 8px;
        }
        .btn-primary { background-color: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); }
        .btn-danger { background-color: var(--danger-red); color: #fff; border-color: var(--danger-red); }
        .btn-danger:hover { background-color: #c82333; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); }
        .btn-success { background-color: var(--success-green); color: #fff; border-color: var(--success-green); }
        .btn-success:hover { background-color: #218838; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2); }
        .btn-success:disabled { background-color: #6c757d; border-color: #6c757d; cursor: not-allowed; transform: none; box-shadow: none; opacity: 0.8; }
        .btn-secondary { background-color: var(--secondary-gray); color: #fff; border-color: var(--secondary-gray); }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2); }
        .btn-outline-primary { background-color: transparent; color: var(--primary-blue); border-color: var(--primary-blue); }
        .btn-outline-primary:hover { background-color: var(--primary-blue); color: #fff; }
        .btn-outline-danger { background-color: transparent; color: var(--danger-red); border-color: var(--danger-red); }
        .btn-outline-danger:hover { background-color: var(--danger-red); color: #fff; }
        .btn-outline-success { background-color: transparent; color: var(--success-green); border-color: var(--success-green); }
        .btn-outline-success:hover { background-color: var(--success-green); color: #fff; }
        .btn-group-custom .btn { margin-right: 5px; } /* Espacio entre botones en grupos */


        /* Formulario de búsqueda */
        .search-container {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            position: relative; /* Contenedor para posicionar el wrapper de búsqueda */
        }
        .relative-search-wrapper { /* NUEVO: Contenedor para el input-group y las sugerencias */
            position: relative; /* Para posicionar las sugerencias ABSOLUTE dentro de él */
            display: flex; /* Para que la barra de búsqueda y el botón estén en línea */
            width: 100%;
        }
        .input-group-custom {
            display: flex;
            width: 100%;
            /* No position:relative aquí, el padre lo maneja */
        }
        .input-group-custom .input-icon {
            padding: 10px 15px;
            background-color: var(--bg-light);
            border: 1px solid var(--input-border);
            border-right: none;
            border-radius: 8px 0 0 8px;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }
        .search-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid var(--input-border);
            border-radius: 0 8px 8px 0;
            font-size: 1em;
            color: var(--text-dark);
            background-color: var(--input-bg);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .search-input:focus {
            border-color: var(--primary-blue);
            outline: none;
            box-shadow: var(--input-focus-shadow);
        }
        .search-input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .autocomplete-suggestions {
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            position: absolute; /* Posicionado absolutamente con respecto a .relative-search-wrapper */
            background-color: var(--card-bg);
            z-index: 1000; /* Alto z-index para que esté sobre otros elementos */
            max-height: 200px;
            overflow-y: auto;
            left: 0;
            top: 100%; /* Posiciona justo debajo del .relative-search-wrapper */
            width: 100%; /* Ocupa el 100% del ancho del .relative-search-wrapper */
            border-top: none;
            border-radius: 0 0 8px 8px;
            display: flex;
            flex-direction: column;
            padding: 0;
            list-style: none;
        }
        .autocomplete-suggestions .suggestion-item {
            display: block;
            padding: 12px 18px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
            font-size: 1em;
            color: var(--text-dark);
            transition: background-color 0.2s ease, color 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .autocomplete-suggestions .suggestion-item:last-child {
            border-bottom: none;
        }
        .autocomplete-suggestions .suggestion-item:hover {
            background-color: var(--primary-blue);
            color: #fff;
        }
        .autocomplete-suggestions .suggestion-item:hover strong {
            color: #fff;
        }
        .autocomplete-suggestions .suggestion-item:hover small {
            color: rgba(255, 255, 255, 0.8);
        }
        .autocomplete-suggestions .suggestion-item strong {
            color: var(--primary-blue-dark);
            font-weight: 700;
        }
        .autocomplete-suggestions .suggestion-item small {
            color: var(--text-light);
            font-size: 0.8em;
            margin-left: 8px;
        }


        /* Notificaciones dinámicas */
        #dynamic-notification-area {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            max-width: 90%;
        }
        .custom-alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 1em;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid;
            background-color: #fff;
        }
        .custom-alert.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #856404;
            border-color: #ffc107;
        }
        .custom-alert.info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            border-color: #17a2b8;
        }
        .custom-alert.success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-color: #28a745;
        }
        .custom-alert.danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-color: #dc3545;
        }
        .custom-alert .alert-icon {
            font-size: 1.4em;
            margin-right: 15px;
        }
        .custom-alert .close-btn {
            background: none;
            border: none;
            font-size: 1.5em;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.2s ease;
        }
        .custom-alert .close-btn:hover {
            color: var(--text-dark);
        }
        /* Definición de RGB para las notificaciones */
        :root {
            --info-cyan-rgb: 23, 162, 184;
            --success-green-rgb: 40, 167, 69;
            --warning-yellow-rgb: 255, 193, 7;
            --danger-red-rgb: 220, 53, 69;
        }

        /* Estilos adicionales para botones */
        .btn[disabled] {
            opacity: 0.65;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover:not([disabled]) {
            background-color: #e0a800;
            border-color: #d39e00;
        }


        /* Cards de cliente (móvil) */
        .client-card-mobile {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 6px solid var(--primary-blue);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .client-card-mobile.inactive {
            border-left-color: var(--danger-red);
            opacity: 0.8;
            background-color: #fcfcfc;
        }
        .client-card-mobile.retired { /* Para clientes que ya retiraron */
            background-color: rgba(40, 167, 69, 0.05); /* Verde suave */
            border-left-color: var(--success-green);
        }
        .client-card-mobile .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .client-card-mobile .header h6 {
            font-size: 1.1em;
            font-weight: 700;
            margin: 0;
        }
        .client-card-mobile .header small {
            color: var(--text-light);
            font-size: 0.85em;
        }
        .client-card-mobile .badge-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            color: #fff;
        }
        .badge-status.active { background-color: var(--success-green); }
        .badge-status.inactive { background-color: var(--danger-red); }

        .client-card-mobile .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .client-card-mobile .form-col-50 {
            flex: 1; /* Ocupa la mitad del espacio disponible */
        }
        .client-card-mobile .form-col-center {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .client-card-mobile .form-label-float {
            position: relative;
        }
        .client-card-mobile .form-label-float input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 0.9em;
            box-sizing: border-box;
        }
        .client-card-mobile .form-label-float label {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9em;
            color: var(--text-light);
            pointer-events: none;
            transition: all 0.2s ease;
            background-color: transparent; /* Asegura que la etiqueta no tape el input */
            padding: 0 4px;
        }
        .client-card-mobile .form-label-float input:focus + label,
        .client-card-mobile .form-label-float input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 0.75em;
            color: var(--primary-blue-dark);
            transform: translateY(-50%);
            background-color: var(--card-bg);
            left: 8px;
        }

        .client-card-mobile .form-switch-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }
        .client-card-mobile .form-switch-custom input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .client-card-mobile .form-switch-custom .slider {
            position: relative;
            width: 45px; /* Más ancho */
            height: 25px; /* Más alto */
            background-color: var(--secondary-gray);
            border-radius: 25px;
            transition: background-color 0.4s;
            margin-right: 10px;
        }
        .client-card-mobile .form-switch-custom .slider:before {
            content: "";
            position: absolute;
            width: 21px; /* Más grande */
            height: 21px; /* Más grande */
            border-radius: 50%;
            background-color: #fff;
            top: 2px;
            left: 2px;
            transition: transform 0.4s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .client-card-mobile .form-switch-custom input:checked + .slider {
            background-color: var(--success-green);
        }
        .client-card-mobile .form-switch-custom input:checked + .slider:before {
            transform: translateX(20px); /* Más desplazamiento */
        }
        .client-card-mobile .form-switch-custom label {
            font-size: 0.95em;
            font-weight: 500;
            color: var(--text-dark);
            margin: 0; /* Reset margin */
            cursor: pointer;
        }

        .client-card-mobile .amount-display {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--text-dark);
        }
        .client-card-mobile .amount-display strong {
            color: var(--primary-blue-dark);
        }
        .client-card-mobile .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }


        /* Estilos de tabla (escritorio) */
        .table-container {
            overflow-x: auto; /* Para tablas responsivas */
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0; /* Elimina margen inferior de tabla */
        }
        .data-table thead {
            background-color: var(--primary-blue-dark);
            color: var(--text-light-color);
        }
        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color); /* Borde entre columnas */
            font-size: 0.9em;
            vertical-align: middle;
        }
        .data-table th:last-child,
        .data-table td:last-child {
            border-right: none;
        }
        .data-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
            white-space: nowrap;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none; /* Elimina borde inferior de última fila */
        }
        .data-table tbody tr:hover {
            background-color: var(--bg-light);
        }
        .data-table tbody tr.inactive-row {
            opacity: 0.7;
            background-color: #fefefe;
        }
        .data-table tbody tr.retired-row {
            background-color: rgba(40, 167, 69, 0.05); /* Verde suave */
            color: var(--success-green);
        }
        .data-table tbody tr.retired-row:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }
        .data-table .input-table {
            width: 70px; /* Ancho fijo para inputs de números */
            text-align: center;
            padding: 8px 5px;
            border-radius: 6px;
            font-size: 0.9em;
        }
        .data-table .switch-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .data-table .amount-value {
            font-weight: 700;
            color: var(--primary-blue-dark);
        }
        .data-table .actions-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .data-table tfoot td {
            font-weight: 700;
            background-color: var(--bg-light);
            border-top: 2px solid var(--border-color);
            font-size: 1em;
        }
        .data-table tfoot .total-value {
            color: var(--danger-red); /* Color para los totales */
        }


        /* Paginación */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .pagination-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            border-radius: 8px;
            overflow: hidden; /* Para que los bordes redondeados funcionen bien */
            box-shadow: var(--card-shadow);
        }
        .pagination-item .page-link {
            display: block;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-right: none;
            color: var(--primary-blue);
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
            font-weight: 500;
            font-size: 0.9em;
        }
        .pagination-item:last-child .page-link {
            border-right: 1px solid var(--border-color); /* Restaura el borde derecho para el último */
        }
        .pagination-item .page-link:hover {
            background-color: var(--primary-blue);
            color: #fff;
        }
        .pagination-item.active .page-link {
            background-color: var(--primary-blue);
            color: #fff;
            border-color: var(--primary-blue);
            font-weight: 700;
        }
        .pagination-item.disabled .page-link {
            color: var(--text-light);
            cursor: not-allowed;
            background-color: var(--bg-light);
        }

        /* Alertas de notificación */
        .alert-custom {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-size: 1em;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid; /* Para el color del tipo de alerta */
            background-color: #fff; /* Asegura un fondo blanco para la alerta flotante */
        }
        .alert-custom .icon {
            font-size: 1.4em;
            margin-right: 15px;
        }
        .alert-custom .close-btn {
            background: none;
            border: none;
            font-size: 1.5em;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.2s ease;
        }
        .alert-custom .close-btn:hover {
            color: var(--text-dark);
        }
        /* Definición de RGB para las notificaciones */
        :root {
            --info-cyan-rgb: 23, 162, 184;
            --success-green-rgb: 40, 167, 69;
            --warning-yellow-rgb: 255, 193, 7;
            --danger-red-rgb: 220, 53, 69;
        }


        /* Utilidades Flexbox y Grid (similares a Bootstrap pero con CSS puro) */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .align-items-start { align-items: flex-start; }
        .justify-content-center { justify-content: center; }
        .justify-content-around { justify-content: space-around; }
        .flex-column { flex-direction: column; }

        .row-grid { display: grid; gap: 15px; } /* Usaremos grid para filas */
        .col-span-1 { grid-column: span 1; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }

        /* Margins & Paddings */
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .mb-5 { margin-bottom: 25px; }
        .mt-2 { margin-top: 10px; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }
        .mt-5 { margin-top: 25px; }
        .p-3 { padding: 15px; }
        .px-4 { padding-left: 20px; padding-right: 20px; }
        .py-2 { padding-top: 10px; padding-bottom: 10px; }
        .mr-2 { margin-right: 8px; }
        .ml-4 { margin-left: 20px; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        /* Typography */
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: 700; }
        .text-muted { color: var(--text-light); }
        .small-text { font-size: 0.85em; }
        .fs-08 { font-size: 0.8em; }

        /* Responsividad general */
        @media (min-width: 600px) { /* Small devices (tables) */
            .main-container { padding: 0 20px; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
            .d-lg-none { display: none !important; }
            .d-none.d-lg-block { display: block !important; }
            .col-sm-4 { grid-column: span 1; } /* Simula col-sm-4 para 3 columnas en sm */
        }

        @media (min-width: 900px) { /* Medium devices (desktops) */
            .main-container { padding: 0 30px; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); } /* 6 columnas en lg */
            .col-md-3 { grid-column: span 1; } /* Simula col-md-3 */
        }

        @media (min-width: 1200px) { /* Large devices (large desktops) */
            .main-container { padding: 0 30px; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); } /* Puedes ajustar minmax */
            .col-lg-2 { grid-column: span 1; } /* Simula col-lg-2 */
        }

        /* Esconder elementos de escritorio en móvil y viceversa */
        .desktop-only { display: none; }
        .mobile-only { display: block; }

        @media (min-width: 992px) { /* Bootstrap's lg breakpoint */
            .desktop-only { display: block; }
            .mobile-only { display: none; }
        }
        /* Ajustes específicos para FontAwesome, si es necesario */
        .fas { font-family: "Font Awesome 6 Free"; font-weight: 900; }
        /* Clases para iconos específicos que no vienen de Bootstrap directamente */
        .icon-primary { color: var(--primary-blue); }
        .icon-success { color: var(--success-green); }
        .icon-warning { color: var(--warning-yellow); }
        .icon-danger { color: var(--danger-red); }
        .icon-info { color: var(--info-cyan); }
    </style>
</head>
<body>

<div class="main-container">
    <header class="section-header">
        <h1>Sistema de Gestión de Retiro de Leche</h1>
        <p>Administre eficientemente a los beneficiarios, inventarios y retiros de leche del programa Liconsa.</p>
    </header>

    <div id="dynamic-notification-area"></div>
    <?php if (!empty($mensaje_notificacion)): ?>
        <div class="custom-alert <?php echo htmlspecialchars($tipo_notificacion); ?> static-notification">
            <i class="alert-icon <?php echo htmlspecialchars($alert_tailwind_styles[$tipo_notificacion]['icon']); ?>"></i>
            <span><?php echo htmlspecialchars($mensaje_notificacion); ?></span>
            <button type="button" class="alert-close" onclick="this.closest('.custom-alert').style.display='none';" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($inventario_activo && $inventario->id): ?>
        <div id="inventory-summary-section" class="card">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list mr-2"></i>Inventario: <?php echo htmlspecialchars($nombre_mes_display . ' ' . $anio_display); ?></h3>
                <span id="inventory-status-badge" class="badge <?php echo $estado_inventario_display === 'cerrado' ? 'secondary' : 'success'; ?>">
                    <?php echo $estado_inventario_display === 'cerrado' ? 'Cerrado' : 'Abierto'; ?>
                </span>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="icon icon-primary"><i class="fas fa-archive"></i></div>
                    <div class="label">Cajas</div>
                    <div id="stat-cajas-ingresadas" class="value"><?php echo $cajas_ingresadas_display; ?></div>
                </div>
                <div class="stat-item">
                    <div class="icon icon-primary"><i class="fas fa-box-open"></i></div>
                    <div class="label">Sobres Totales</div>
                    <div id="stat-sobres-totales" class="value"><?php echo number_format($sobres_totales); ?></div>
                </div>
                <div class="stat-item warning">
                    <div class="icon"><i class="fas fa-shopping-basket"></i></div>
                    <div class="label">S. Retirados</div>
                    <div id="stat-sobres-retirados" class="value"><?php echo number_format($sobres_retirados); ?></div>
                </div>
                <div class="stat-item info">
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                    <div class="label">S. Restantes</div>
                    <div id="stat-sobres-restantes" class="value"><?php echo number_format($sobres_restantes); ?></div>
                </div>
                <div class="stat-item info">
                    <div class="icon"><i class="fas fa-pallet"></i></div>
                    <div class="label">Cajas Restantes</div>
                    <div id="stat-cajas-restantes" class="value"><?php echo $cajas_restantes; ?></div>
                </div>
                <div class="stat-item success">
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="label">Recaudado</div>
                    <div id="stat-dinero-recaudado" class="value currency"><?php echo number_format($dinero_recaudado, 2); ?></div>
                </div>
            </div>
            <div class="custom-progress-bar">
                <div id="progress-bar-retirado" class="custom-progress-bar-fill" style="width: <?php echo round($porcentaje_retirado); ?>%"></div>
            </div>
            <div class="progress-text">
                <span id="badge-porcentaje-retirado"><?php echo round($porcentaje_retirado); ?>% Retirado</span>
            </div>
            <div class="stats-details-row">
                <div class="stat-detail"><strong>Sobres por Caja:</strong> <span id="text-sobres-por-caja"><?php echo $sobres_por_caja_display; ?></span></div>
                <div class="stat-detail"><strong>Precio por Sobre:</strong> $<span id="text-precio-sobre"><?php echo number_format($precio_sobre_display, 2); ?></span></div>
                <div class="stat-detail"><strong>Valor Total Inventario:</strong> $<span id="text-valor-total"><?php echo number_format($valor_total, 2); ?></span></div>
            </div>
            <?php if (isset($inventario->estado) && $inventario->estado === 'abierto'): ?>
                <div class="text-center mt-4">
                    <a href="../inventarios/cerrar_inventario.php?id=<?php echo $inventario->id; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de cerrar este inventario? Esta acción no se puede deshacer y ya no se podrán registrar más retiros para este mes.')"><i class="fas fa-lock mr-2"></i>Cerrar Inventario del Mes</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="custom-alert warning text-center" role="alert">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle mr-2"></i>No hay Inventario Activo</h4>
            <p>Para registrar retiros, primero debe <a href="<?php echo $baseUrl; ?>/inventarios/crear_inventario.php" class="alert-link">crear un inventario</a> para el mes actual o seleccionar uno del <a href="<?php echo $baseUrl; ?>/inventarios/historial_inventarios.php" class="alert-link">historial</a>.</p>
        </div>
    <?php endif; ?>

    <div class="search-container">
        <h4 class="section-title mb-3"><i class="fas fa-users mr-2"></i>Registro de Retiros de Beneficiarios</h4>
        <form action="dashboard.php" method="GET" id="formBusquedaDashboard">
            <div class="relative-search-wrapper">
                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-search"></i></span>
                    <input type="text" name="busqueda" id="busqueda_dashboard_input" class="search-input" placeholder="Buscar por nombre o tarjeta..." value="<?php echo htmlspecialchars($busqueda); ?>" autocomplete="off" <?php if (!$inventario_activo) echo 'disabled'; ?>>
                    <?php if ($es_busqueda_activa): ?>
                        <a href="dashboard.php<?php echo $inventario_activo && $inventario->id ? '?inventario_id=' . $inventario->id : ''; ?>" class="btn btn-secondary clear-search-btn">Limpiar</a>
                    <?php endif; ?>
                </div>
                <div id="sugerencias_busqueda_dashboard_container" class="autocomplete-suggestions" style="display:none;"></div>
            </div>
        </form>
    </div>


    <?php if (!empty($clientes_data_temp)): ?>
        <?php if ($inventario_activo && isset($inventario->estado) && $inventario->estado === 'abierto'): ?>
            <!-- Vista Móvil: Clientes en tarjetas -->
            <div class="mobile-only">
                <?php
                $subtotal_sobres_movil = 0;
                $subtotal_dinero_movil = 0;
                
                foreach ($clientes_data_temp as $row_cliente):
                    $retiro_info = false;
                    $monto_cliente = 0;
                    $sobres_retirados_cliente = 0;
                    $retiro_realizado_cliente = false;
                    if ($inventario_activo && $inventario->id) {
                        $retiro_obj_temp = new Retiro();
                        $retiro_obj_temp->cliente_id = $row_cliente['id'];
                        $retiro_obj_temp->inventario_id = $inventario->id;
                        if ($retiro_obj_temp->leer()) {
                            $retiro_info = true;
                            $sobres_retirados_cliente = $retiro_obj_temp->sobres_retirados;
                            $retiro_realizado_cliente = $retiro_obj_temp->retiro;
                            if ($retiro_obj_temp->retiro) {
                                $monto_cliente = $retiro_obj_temp->monto_pagado;
                                if ($row_cliente['estado'] == 'activo') {
                                   $subtotal_sobres_movil += $sobres_retirados_cliente;
                                   $subtotal_dinero_movil += $monto_cliente;
                                }
                            }
                        }
                    }
                    $clase_extra_retiro_movil = ($retiro_realizado_cliente && $row_cliente['estado'] == 'activo') ? 'retired' : '';
                    
                    
                    $params_url_estado_movil = [
                        'id' => $row_cliente['id'],
                        'origen_dashboard' => 1
                    ];
                    if ($inventario_activo && $inventario->id) $params_url_estado_movil['inventario_id'] = $inventario->id;
                    if ($es_busqueda_activa) $params_url_estado_movil['busqueda'] = $busqueda;
                    if (!$es_busqueda_activa && $pagina_actual > 1) $params_url_estado_movil['pagina'] = $pagina_actual;

                    $url_baja_reactivar_movil = "../clientes/eliminar_cliente.php?" . http_build_query($params_url_estado_movil);
                    $confirm_msg_movil = "¿Está seguro de dar de baja a '" . htmlspecialchars($row_cliente['nombre_completo']) . "'? Pasará a la lista de inactivos.";
                    if ($row_cliente['estado'] != 'activo') {
                        $url_baja_reactivar_movil .= "&activar=1";
                        $confirm_msg_movil = "¿Está seguro de reactivar a '" . htmlspecialchars($row_cliente['nombre_completo']) . "'? Pasará a la lista de activos.";
                    }

                ?>
                <div id="cliente-card-movil-<?php echo $row_cliente['id']; ?>" class="client-card-mobile <?php echo $row_cliente['estado'] == 'inactivo' ? 'inactive' : ''; ?> <?php echo $clase_extra_retiro_movil; ?>">
                    <div class="header">
                        <div>
                            <h6><?php echo htmlspecialchars($row_cliente['nombre_completo']); ?></h6>
                            <small>Tarjeta: <?php echo htmlspecialchars($row_cliente['numero_tarjeta']); ?></small>
                        </div>
                        <?php if ($es_busqueda_activa || $row_cliente['estado'] == 'inactivo'): ?>
                        <span class="badge-status <?php echo $row_cliente['estado'] == 'activo' ? 'active' : 'inactive'; ?>">
                            <?php echo ucfirst($row_cliente['estado']); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($row_cliente['estado'] == 'activo'): ?>
                        <form class="retiro-form" data-cliente-id="<?php echo $row_cliente['id']; ?>" action="dashboard.php<?php if ($inventario_activo && $inventario->id) echo '?inventario_id=' . $inventario->id; if($es_busqueda_activa) echo ($inventario_activo && $inventario->id ? '&':'?').'busqueda='.urlencode($busqueda); if(!$es_busqueda_activa && $pagina_actual > 1) echo ($inventario_activo && $inventario->id || $es_busqueda_activa ? '&':'?').'pagina='.$pagina_actual; ?>" method="post">
                            <input type="hidden" name="cliente_id" value="<?php echo $row_cliente['id']; ?>">
                            <div class="form-row">
                                <div class="form-col-50">
                                    <div class="form-label-float">
                                        <input type="number" name="dotacion_maxima_form" id="dotacion_mob_<?php echo $row_cliente['id']; ?>" value="<?php echo $row_cliente['dotacion_maxima']; ?>" min="0" placeholder=" ">
                                        <label for="dotacion_mob_<?php echo $row_cliente['id']; ?>">Dotación</label>
                                    </div>
                                </div>
                                <div class="form-col-center">
                                    <label class="form-switch-custom">
                                        <input type="checkbox" name="retiro_checkbox" value="si"
                                               id="switch_mob_<?php echo $row_cliente['id']; ?>"
                                               <?php echo $retiro_realizado_cliente ? 'checked' : ''; ?>
                                               data-dotacion="<?php echo $row_cliente['dotacion_maxima']; ?>"
                                               data-precio="<?php echo ($inventario_activo && isset($inventario->precio_sobre)) ? $inventario->precio_sobre : 0; ?>"
                                               data-target-sobres="#sobres_mob_<?php echo $row_cliente['id']; ?>"
                                               data-target-monto="#monto_mob_<?php echo $row_cliente['id']; ?>">
                                        <span class="slider"></span>
                                        ¿Retiró?
                                    </label>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col-50">
                                    <div class="form-label-float">
                                        <input type="number" id="sobres_mob_<?php echo $row_cliente['id']; ?>" name="sobres_retirados_form" class="sobres-input"
                                               value="<?php echo $sobres_retirados_cliente; ?>" min="0" max="<?php echo $row_cliente['dotacion_maxima']; ?>"
                                               <?php echo !$retiro_realizado_cliente ? 'disabled' : ''; ?>
                                               data-precio="<?php echo ($inventario_activo && isset($inventario->precio_sobre)) ? $inventario->precio_sobre : 0; ?>"
                                               data-target-monto="#monto_mob_<?php echo $row_cliente['id']; ?>" placeholder=" ">
                                        <label for="sobres_mob_<?php echo $row_cliente['id']; ?>">Sobres</label>
                                    </div>
                                </div>
                                <div class="form-col-center">
                                    <strong style="margin-right: 5px;">Monto:</strong> <span id="monto_mob_<?php echo $row_cliente['id']; ?>" class="amount-display">$<?php echo number_format($monto_cliente, 2); ?></span>
                                </div>
                            </div>
                            <div class="actions">
                                <?php if ($retiro_info && $retiro_realizado_cliente): ?>
                                    <button type="submit" class="btn btn-warning submit-retiro-btn">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-outline-primary submit-retiro-btn">
                                        <i class="fas fa-save"></i> Registrar Retiro
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo $url_baja_reactivar_movil; ?>" class="btn btn-danger btn-baja-cliente" onclick="return confirm('<?php echo addslashes($confirm_msg_movil); ?>')">
                                    <i class="fas fa-user-times"></i> Dar de Baja
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                           <p class="small-text mb-2"><strong>Dotación:</strong> <?php echo htmlspecialchars($row_cliente['dotacion_maxima']); ?></p>
                           <p class="text-muted small-text mb-3">No se pueden registrar retiros para clientes inactivos.</p>
                           <a href="<?php echo $url_baja_reactivar_movil; ?>" class="btn btn-success" onclick="return confirm('<?php echo addslashes($confirm_msg_movil); ?>')">
                               <i class="fas fa-user-check"></i> Reactivar Cliente
                           </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if ($inventario_activo && !$es_busqueda_activa && $total_clientes_a_mostrar > $registros_por_pagina): ?>
                <div class="client-card-mobile card bg-light mt-3">
                    <h6 class="text-center mb-3 fw-bold">Subtotales de esta Página (Móvil)</h6>
                    <div class="d-flex justify-content-around">
                        <div class="text-center">
                            <span class="d-block text-muted small-text">Sobres:</span>
                            <h5 id="subtotal-sobres-movil" class="value total-value"><?php echo $subtotal_sobres_movil; ?></h5>
                        </div>
                        <div class="text-center">
                            <span class="d-block text-muted small-text">Monto:</span>
                            <h5 id="subtotal-dinero-movil" class="value currency total-value"><?php echo number_format($subtotal_dinero_movil, 2); ?></h5>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Vista Escritorio: Clientes en tabla -->
            <div class="desktop-only">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 8%;">Tarjeta</th>
                                <th style="width: 30%;">Beneficiario</th>
                                <?php if ($es_busqueda_activa): ?>
                                <th class="text-center" style="width: 10%;">Estado</th>
                                <?php endif; ?>
                                <th class="text-center" style="width: 8%;">Dotación</th>
                                <th class="text-center" style="width: 10%;">¿Retiró?</th>
                                <th class="text-center" style="width: 8%;">Sobres</th>
                                <th class="text-center" style="width: 10%;">Monto</th>
                                <th class="text-center" style="width: 16%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $subtotal_sobres_escritorio = 0;
                            $subtotal_dinero_escritorio = 0;
                            
                            foreach ($clientes_data_temp as $row_cliente):
                                $retiro_info = false;
                                $monto_cliente = 0;
                                $sobres_retirados_cliente = 0;
                                $retiro_realizado_cliente = false;
                                if ($inventario_activo && $inventario->id) {
                                    $retiro_obj_temp_desk = new Retiro();
                                    $retiro_obj_temp_desk->cliente_id = $row_cliente['id'];
                                    $retiro_obj_temp_desk->inventario_id = $inventario->id;
                                    if ($retiro_obj_temp_desk->leer()) {
                                        $retiro_info = true;
                                        $sobres_retirados_cliente = $retiro_obj_temp_desk->sobres_retirados;
                                        $retiro_realizado_cliente = $retiro_obj_temp_desk->retiro;
                                        if ($retiro_obj_temp_desk->retiro) {
                                            $monto_cliente = $retiro_obj_temp_desk->monto_pagado;
                                            if ($row_cliente['estado'] == 'activo') {
                                               $subtotal_sobres_escritorio += $sobres_retirados_cliente;
                                               $subtotal_dinero_escritorio += $monto_cliente;
                                            }
                                        }
                                    }
                                }
                                $clase_extra_retiro_escritorio = ($retiro_realizado_cliente && $row_cliente['estado'] == 'activo') ? 'retired-row' : '';
                                
                                
                                $params_url_estado = [
                                    'id' => $row_cliente['id'],
                                    'origen_dashboard' => 1
                                ];
                                if ($inventario_activo && $inventario->id) $params_url_estado['inventario_id'] = $inventario->id;
                                if ($es_busqueda_activa) $params_url_estado['busqueda'] = $busqueda;
                                if (!$es_busqueda_activa && $pagina_actual > 1) $params_url_estado['pagina'] = $pagina_actual;

                                $url_baja_reactivar = "../clientes/eliminar_cliente.php?" . http_build_query($params_url_estado);
                                $confirm_msg = "";
                                $btn_baja_reactivar_class = "";
                                $btn_baja_reactivar_icon = "";

                                if ($row_cliente['estado'] == 'activo') {
                                    $confirm_msg = "¿Está seguro de dar de baja a \\'" . htmlspecialchars($row_cliente['nombre_completo'], ENT_QUOTES) . "\\'? Pasará a la lista de inactivos.";
                                    $btn_baja_reactivar_class = "btn-outline-danger";
                                    $btn_baja_reactivar_icon = "fas fa-user-times";
                                } else {
                                    $url_baja_reactivar .= "&activar=1";
                                    $confirm_msg = "¿Está seguro de reactivar a \\'" . htmlspecialchars($row_cliente['nombre_completo'], ENT_QUOTES) . "\\'? Pasará a la lista de activos.";
                                    $btn_baja_reactivar_class = "btn-outline-success";
                                    $btn_baja_reactivar_icon = "fas fa-user-check";
                                }

                            ?>
                                <tr id="cliente-row-desk-<?php echo $row_cliente['id']; ?>" class="<?php echo $row_cliente['estado'] == 'inactivo' ? 'inactive-row' : ''; ?> <?php echo $clase_extra_retiro_escritorio; ?>">
                                    <td><?php echo htmlspecialchars($row_cliente['numero_tarjeta']); ?></td>
                                    <td><?php echo htmlspecialchars($row_cliente['nombre_completo']); ?></td>
                                    <?php if ($es_busqueda_activa): ?>
                                    <td class="text-center">
                                        <span class="badge-status <?php echo $row_cliente['estado'] == 'activo' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst($row_cliente['estado']); ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    
                                    <?php if ($row_cliente['estado'] == 'activo'): ?>
                                        <form class="retiro-form" data-cliente-id="<?php echo $row_cliente['id']; ?>" action="dashboard.php<?php if ($inventario_activo && $inventario->id) echo '?inventario_id=' . $inventario->id; if($es_busqueda_activa) echo ($inventario_activo && $inventario->id ? '&':'?').'busqueda='.urlencode($busqueda); if(!$es_busqueda_activa && $pagina_actual > 1) echo ($inventario_activo && $inventario->id || $es_busqueda_activa ? '&':'?').'pagina='.$pagina_actual; ?>" method="post">
                                            <input type="hidden" name="cliente_id" value="<?php echo $row_cliente['id']; ?>">
                                            <td class="text-center">
                                                <input type="number" name="dotacion_maxima_form" class="input-table" value="<?php echo $row_cliente['dotacion_maxima']; ?>" min="0">
                                            </td>
                                            <td class="text-center">
                                                <label class="form-switch-custom switch-wrapper">
                                                    <input type="checkbox" name="retiro_checkbox" value="si"
                                                            id="switch_desk_<?php echo $row_cliente['id']; ?>"
                                                            <?php echo $retiro_realizado_cliente ? 'checked' : ''; ?>
                                                            data-dotacion="<?php echo $row_cliente['dotacion_maxima']; ?>"
                                                            data-precio="<?php echo ($inventario_activo && isset($inventario->precio_sobre)) ? $inventario->precio_sobre : 0; ?>"
                                                            data-target-sobres="#sobres_desk_<?php echo $row_cliente['id']; ?>"
                                                            data-target-monto="#monto_desk_<?php echo $row_cliente['id']; ?>">
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" id="sobres_desk_<?php echo $row_cliente['id']; ?>" name="sobres_retirados_form" class="input-table sobres-input"
                                                            value="<?php echo $sobres_retirados_cliente; ?>" min="0" max="<?php echo $row_cliente['dotacion_maxima']; ?>"
                                                            <?php echo !$retiro_realizado_cliente ? 'disabled' : ''; ?>
                                                            data-precio="<?php echo ($inventario_activo && isset($inventario->precio_sobre)) ? $inventario->precio_sobre : 0; ?>"
                                                            data-target-monto="#monto_desk_<?php echo $row_cliente['id']; ?>">
                                            </td>
                                            <td class="text-center">
                                                <span id="monto_desk_<?php echo $row_cliente['id']; ?>" class="amount-value">$<?php echo number_format($monto_cliente, 2); ?></span>
                                            </td>
                                            <td class="text-center actions-cell">
                                                <?php if ($retiro_info && $retiro_realizado_cliente): ?>
                                                    <button type="submit" class="btn btn-warning submit-retiro-btn small-text">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-outline-primary submit-retiro-btn small-text">
                                                        <i class="fas fa-save"></i> Registrar
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?php echo $url_baja_reactivar; ?>" class="btn <?php echo $btn_baja_reactivar_class; ?> small-text" onclick="return confirm('<?php echo addslashes($confirm_msg); ?>');" title="<?php echo $row_cliente['estado'] == 'activo' ? 'Dar de Baja' : 'Reactivar'; ?>">
                                                    <i class="fas <?php echo $btn_baja_reactivar_icon; ?>"></i>
                                                </a>
                                            </td>
                                        </form>
                                    <?php else: // Cliente inactivo en la tabla de escritorio ?>
                                        <td class="text-center"><?php echo htmlspecialchars($row_cliente['dotacion_maxima']); ?></td>
                                        <td class="text-center text-muted small-text" colspan="3">Inactivo</td>
                                        <td class="text-center actions-cell">
                                             <a href="<?php echo $url_baja_reactivar; ?>" class="btn btn-success small-text" onclick="return confirm('<?php echo addslashes($confirm_msg); ?>');" title="Reactivar">
                                                 <i class="fas <?php echo $btn_baja_reactivar_icon; ?>"></i> Reactivar
                                             </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($inventario_activo && !$es_busqueda_activa && $total_clientes_a_mostrar > $registros_por_pagina): // Mostrar subtotales solo si hay paginación ?>
                            <tfoot>
                                <tr>
                                    <td colspan="<?php echo $es_busqueda_activa ? '5' : '4'; ?>" class="text-end"><strong>Subtotales de esta
                                    <td class="text-center"><strong id="subtotal-sobres-escritorio" class="total-value"><?php echo $subtotal_sobres_escritorio; ?></strong></td>
                                    <td class="text-center"><strong id="subtotal-dinero-escritorio" class="total-value currency"><?php echo number_format($subtotal_dinero_escritorio, 2); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif ($inventario_activo && isset($inventario->estado) && $inventario->estado === 'cerrado'): ?>
            <div class="custom-alert info">
               
                <div>
                    <h4 class="alert-heading">Inventario Cerrado</h4>
                    <p>El inventario actual está cerrado. Solo se pueden visualizar los retiros registrados. No se pueden realizar nuevas operaciones de retiro.</p>
                </div>
            </div>
        <?php endif; ?>


        <?php if (!$es_busqueda_activa && $total_paginas > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Paginación de clientes">
                    <ul class="pagination-list">
                        <?php
                        $url_base_paginacion = "dashboard.php";
                        $params_paginacion = [];
                        if ($inventario_activo && $inventario->id) $params_paginacion['inventario_id'] = $inventario->id;
                        
                        if ($pagina_actual > 1): ?>
                            <li class="pagination-item"><a class="page-link" href="<?php echo $url_base_paginacion . '?' . http_build_query(array_merge($params_paginacion, ['pagina' => 1])); ?>">&laquo; Primera</a></li>
                            <li class="pagination-item"><a class="page-link" href="<?php echo $url_base_paginacion . '?' . http_build_query(array_merge($params_paginacion, ['pagina' => $pagina_actual - 1])); ?>">Anterior</a></li>
                        <?php endif; ?>
                        <?php
                        $rango = 2;
                        for ($i = max(1, $pagina_actual - $rango); $i <= min($pagina_actual + $rango, $total_paginas); $i++): ?>
                            <li class="pagination-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $url_base_paginacion . '?' . http_build_query(array_merge($params_paginacion, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="pagination-item"><a class="page-link" href="<?php echo $url_base_paginacion . '?' . http_build_query(array_merge($params_paginacion, ['pagina' => $pagina_actual + 1])); ?>">Siguiente</a></li>
                            <li class="pagination-item"><a class="page-link" href="<?php echo $url_base_paginacion . '?' . http_build_query(array_merge($params_paginacion, ['pagina' => $total_paginas])); ?>">Última &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    <?php elseif($es_busqueda_activa && $inventario_activo): ?>
        <div class="custom-alert warning" role="alert">
            <i class="alert-icon fas fa-exclamation-triangle"></i>
            <div>
                No se encontraron clientes que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
            </div>
        </div>
    <?php elseif (!$inventario_activo): ?>
        <!-- Este else ya está cubierto por el alert-warning de "No hay Inventario Activo" al inicio -->
    <?php else: // No hay clientes para mostrar en el inventario activo (no es búsqueda) ?>
        <?php if ($inventario_activo): ?>
        <div class="custom-alert info" role="alert">
            <i class="alert-icon fas fa-info-circle"></i>
            <div>
                No hay clientes activos para mostrar en este inventario. Puede <a href="<?php echo $baseUrl; ?>/clientes/agregar_cliente.php" class="alert-link">agregar un nuevo cliente</a>.
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($inventario_activo && isset($inventario->estado) && $inventario->estado === 'abierto'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard JavaScript iniciado');
    
    const notificationArea = document.getElementById('dynamic-notification-area');
    
    // Verificar que los elementos principales existen
    const switches = document.querySelectorAll('input[type="checkbox"][name="retiro_checkbox"]');
    const sobresInputs = document.querySelectorAll('.sobres-input');
    
    console.log(`📊 Elementos encontrados:`, {
        switches: switches.length,
        sobresInputs: sobresInputs.length
    });
    
    // Test inicial para verificar elementos
    console.log('🧪 Ejecutando test de elementos...');
    switches.forEach((checkbox, index) => {
        console.log(`  Switch ${index + 1}:`, {
            id: checkbox.id,
            name: checkbox.name,
            value: checkbox.value,
            checked: checkbox.checked,
            dotacion: checkbox.dataset.dotacion,
            targetSobres: checkbox.dataset.targetSobres,
            targetMonto: checkbox.dataset.targetMonto
        });
    });

    function showDynamicNotification(message, type = 'info') {
        const staticNotification = document.querySelector('.static-notification');
        if (staticNotification) {
            staticNotification.style.display = 'none';
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert ${type}`; // Usar la nueva clase custom-alert
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <i class="alert-icon fas ${getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button type="button" class="alert-close" aria-label="Close" onclick="this.closest('.custom-alert').remove();">&times;</button>
        `;
        if(notificationArea) notificationArea.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000); // Duración de la notificación: 5 segundos
    }
    
    // Función auxiliar para obtener iconos de alerta (Font Awesome)
    function getAlertIcon(type) {
        switch(type) {
            case 'info': return 'fa-info-circle';
            case 'success': return 'fa-check-circle';
            case 'warning': return 'fa-exclamation-triangle';
            case 'danger': return 'fa-times-circle';
            default: return 'fa-info-circle';
        }
    }


    function formatNumber(num) {
        if (typeof num !== 'number') {
            num = parseFloat(num) || 0;
        }
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function formatCurrency(num) {
           if (typeof num !== 'number') {
            num = parseFloat(num) || 0;
        }
        return '$' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function updateGlobalInventoryStats(stats) {
        if (!stats) {
            console.warn("updateGlobalInventoryStats recibió stats nulos o indefinidos.");
            document.getElementById('stat-cajas-ingresadas').textContent = '0';
            document.getElementById('stat-sobres-totales').textContent = '0';
            document.getElementById('stat-sobres-retirados').textContent = '0';
            document.getElementById('stat-sobres-restantes').textContent = '0';
            document.getElementById('stat-cajas-restantes').textContent = '0';
            document.getElementById('stat-dinero-recaudado').textContent = formatCurrency(0);
            
            const progressBar = document.getElementById('progress-bar-retirado');
            const percentageBadge = document.getElementById('badge-porcentaje-retirado');
            if(progressBar) progressBar.style.width = '0%';
            if(progressBar) progressBar.setAttribute('aria-valuenow', 0);
            if(percentageBadge) percentageBadge.textContent = '0% Retirado';

            document.getElementById('text-sobres-por-caja').textContent = '0';
            document.getElementById('text-precio-sobre').textContent = '0.00';
            document.getElementById('text-valor-total').textContent = '0.00';
            return;
        }

        document.getElementById('stat-cajas-ingresadas').textContent = stats.cajas_ingresadas !== undefined ? stats.cajas_ingresadas : '0';
        document.getElementById('stat-sobres-totales').textContent = formatNumber(stats.sobres_totales);
        document.getElementById('stat-sobres-retirados').textContent = formatNumber(stats.sobres_retirados);
        document.getElementById('stat-sobres-restantes').textContent = formatNumber(stats.sobres_restantes);
        document.getElementById('stat-cajas-restantes').textContent = stats.cajas_restantes !== undefined ? stats.cajas_restantes : '0';
        document.getElementById('stat-dinero-recaudado').textContent = formatCurrency(stats.dinero_recaudado);
        
        const progressBar = document.getElementById('progress-bar-retirado');
        const percentageBadge = document.getElementById('badge-porcentaje-retirado');
        const roundedPercentage = Math.round(stats.porcentaje_retirado || 0);

        if(progressBar) progressBar.style.width = roundedPercentage + '%';
        if(progressBar) progressBar.setAttribute('aria-valuenow', roundedPercentage);
        if(percentageBadge) percentageBadge.textContent = roundedPercentage + '% Retirado';
        
        document.getElementById('text-sobres-por-caja').textContent = stats.sobres_por_caja !== undefined ? stats.sobres_por_caja : '0';
        document.getElementById('text-precio-sobre').textContent = (parseFloat(stats.precio_sobre) || 0).toFixed(2);
        document.getElementById('text-valor-total').textContent = formatCurrency(stats.valor_total_inventario);
    }
    
    function recalculatePageSubtotals() {
        let currentPageSobresDesk = 0;
        let currentPageDineroDesk = 0;
        document.querySelectorAll('.data-table tbody tr').forEach(row => {
            if (row.id.startsWith('cliente-row-desk-') && row.classList.contains('retired-row') && !row.classList.contains('inactive-row')) {
                const sobresInput = row.querySelector('.sobres-input');
                const montoSpan = row.querySelector('.amount-value'); // Cambiado de strong a span
                if (sobresInput && montoSpan) {
                    currentPageSobresDesk += parseInt(sobresInput.value) || 0;
                    currentPageDineroDesk += parseFloat(montoSpan.textContent.replace('$', '').replace(/,/g, '')) || 0;
                }
            }
        });
        const subtotalSobresDeskEl = document.getElementById('subtotal-sobres-escritorio');
        const subtotalDineroDeskEl = document.getElementById('subtotal-dinero-escritorio');
        if (subtotalSobresDeskEl) subtotalSobresDeskEl.textContent = formatNumber(currentPageSobresDesk);
        if (subtotalDineroDeskEl) subtotalDineroDeskEl.textContent = formatCurrency(currentPageDineroDesk);

        let currentPageSobresMovil = 0;
        let currentPageDineroMovil = 0;
        document.querySelectorAll('.mobile-only .client-card-mobile').forEach(card => {
             if (card.id.startsWith('cliente-card-movil-') && card.classList.contains('retired') && !card.classList.contains('inactive')) {
                const sobresInput = card.querySelector('.sobres-input');
                const montoSpan = card.querySelector('.amount-display');
                 if (sobresInput && montoSpan) {
                    currentPageSobresMovil += parseInt(sobresInput.value) || 0;
                    currentPageDineroMovil += parseFloat(montoSpan.textContent.replace('$', '').replace(/,/g, '')) || 0;
                }
            }
        });
        const subtotalSobresMovilEl = document.getElementById('subtotal-sobres-movil');
        const subtotalDineroMovilEl = document.getElementById('subtotal-dinero-movil');
        if (subtotalSobresMovilEl) subtotalSobresMovilEl.textContent = formatNumber(currentPageSobresMovil);
        if (subtotalDineroMovilEl) subtotalDineroMovilEl.textContent = formatCurrency(currentPageDineroMovil);
    }


    document.querySelectorAll('.retiro-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            let containerElement = form.closest('.client-card-mobile') || form.closest('tr');

            const submitButton = containerElement ? containerElement.querySelector('.submit-retiro-btn') : null;

            if (!submitButton) {
                console.error('Submit button .submit-retiro-btn not found in form container for form:', form);
                showDynamicNotification('Error: No se pudo procesar la acción. Botón no encontrado.', 'danger');
                return;
            }                        // Procesar el formulario independientemente del estado del botón
                        // (permitir tanto registrar nuevos retiros como editar existentes)
                        
                        // Determinar el tipo de acción basado en el texto del botón
                        const isEditAction = submitButton.innerHTML.includes('Editar');
                        console.log(`🎯 Tipo de acción: ${isEditAction ? 'EDITAR RETIRO EXISTENTE' : 'REGISTRAR NUEVO RETIRO'}`);
                        
                        const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${isEditAction ? 'Actualizando...' : 'Guardando...'}`;
            submitButton.disabled = true;

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Network response was not ok: ${response.statusText}. Server response: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('📡 Respuesta completa del servidor:', data);
                console.log('🔍 Análisis detallado de la respuesta:', {
                    status: data.status,
                    cliente_id: data.cliente_id,
                    'retiro_data.retiro_realizado': data.retiro_data?.retiro_realizado,
                    'retiro_data.sobres_retirados': data.retiro_data?.sobres_retirados,
                    'retiro_data.monto_pagado': data.retiro_data?.monto_pagado,
                    retiro_info_exists: data.retiro_info_exists,
                    dotacion_maxima: data.dotacion_maxima
                });
                
                if (typeof data !== 'object' || data === null) {
                    console.error('Invalid JSON response:', data);
                    showDynamicNotification('Error: Respuesta inesperada del servidor.', 'danger');
                    throw new Error('Invalid JSON response from server.');
                }
                
                console.log(`✅ Datos del retiro recibidos:`, {
                    clienteId: data.cliente_id,
                    retiroData: data.retiro_data,
                    retiroInfoExists: data.retiro_info_exists,
                    dotacionMaxima: data.dotacion_maxima
                });
                
                showDynamicNotification(data.message, data.tipo_mensaje);
                
                // Mostrar notificación adicional para acciones de edición
                if (data.status === 'success' && data.cliente_id) {
                    const isEditActionResponse = data.retiro_data && data.retiro_info_exists;
                    if (isEditActionResponse) {
                        setTimeout(() => {
                            showDynamicNotification('💡 Puede seguir editando este retiro o desmarcarlo si fue un error.', 'info');
                        }, 2000);
                    }
                }

                if (data.status === 'success' && data.cliente_id) {
                    console.log('✅ INICIO DE ACTUALIZACIÓN DE INTERFAZ');
                    const clienteId = data.cliente_id;
                    const retiroData = data.retiro_data;
                    const dotacionMaxima = data.dotacion_maxima;
                    const retiroInfoExistsAfterSave = data.retiro_info_exists;
                    
                    // Variables para los botones que serán usadas después
                    let actionButtonDesk = null;
                    let actionButtonMob = null;

                    console.log('🔍 Datos recibidos para actualización:', {
                        clienteId,
                        retiroData,
                        retiroInfoExistsAfterSave,
                        dotacionMaxima
                    });

                    // Actualizar fila en tabla de escritorio
                    console.log('🔍 Buscando elementos del escritorio...');
                    const rowDesk = document.getElementById(`cliente-row-desk-${clienteId}`);
                    console.log(`🔍 Fila escritorio encontrada:`, rowDesk ? 'SÍ' : 'NO');
                    
                    if (rowDesk) {
                        const switchDesk = rowDesk.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
                        const sobresDesk = rowDesk.querySelector(`#sobres_desk_${clienteId}`);
                        const montoDesk = rowDesk.querySelector(`#monto_desk_${clienteId}`);
                        const dotacionInputDesk = rowDesk.querySelector('input[name="dotacion_maxima_form"]');
                        actionButtonDesk = rowDesk.querySelector('.submit-retiro-btn'); // Asignar a la variable global

                        console.log('🔍 Elementos escritorio encontrados:', {
                            switchDesk: switchDesk ? 'SÍ' : 'NO',
                            sobresDesk: sobresDesk ? 'SÍ' : 'NO',
                            montoDesk: montoDesk ? 'SÍ' : 'NO',
                            dotacionInputDesk: dotacionInputDesk ? 'SÍ' : 'NO',
                            actionButtonDesk: actionButtonDesk ? 'SÍ' : 'NO'
                        });

                        if (dotacionInputDesk) {
                            dotacionInputDesk.value = dotacionMaxima;
                            // Actualizar el valor original para que no aparezca como "cambio pendiente"
                            dotacionInputDesk.dataset.originalValue = dotacionMaxima;
                            // Restaurar el estilo original (quitar indicadores de cambio)
                            dotacionInputDesk.style.borderColor = '';
                            dotacionInputDesk.style.backgroundColor = '';
                            dotacionInputDesk.style.borderWidth = '';
                            // Quitar indicador de cambio pendiente
                            const changeIndicator = dotacionInputDesk.parentElement.querySelector('.change-indicator');
                            if (changeIndicator) {
                                changeIndicator.remove();
                            }
                            console.log(`📊 Dotación máxima actualizada en escritorio: ${dotacionMaxima}`);
                        }
                        if (switchDesk) {
                             switchDesk.checked = retiroData.retiro_realizado;
                             switchDesk.dataset.dotacion = dotacionMaxima;
                        }
                        if (sobresDesk) {
                            sobresDesk.value = retiroData.sobres_retirados;
                            // Los inputs siempre estarán habilitados para permitir edición
                            sobresDesk.removeAttribute('disabled');
                            sobresDesk.max = dotacionMaxima;
                        }
                        if (montoDesk) montoDesk.textContent = formatCurrency(retiroData.monto_pagado);
                        
                        // DEBUGGING ESPECÍFICO PARA EL PROBLEMA
                        console.log('🔍 ANÁLISIS DETALLADO DEL PROBLEMA - Cliente:', clienteId);
                        console.log('🔍 Estado del checkbox después de actualizar:', {
                            switchDesk_checked: switchDesk ? switchDesk.checked : 'N/A'
                        });
                        console.log('🔍 Datos del servidor:', {
                            retiro_realizado: retiroData.retiro_realizado,
                            retiro_realizado_type: typeof retiroData.retiro_realizado,
                            retiro_info_exists: retiroInfoExistsAfterSave,
                            retiro_info_exists_type: typeof retiroInfoExistsAfterSave
                        });
                        
                        if (actionButtonDesk) {
                            try {
                                // Determinar el estado del botón basado en los datos reales
                                // Convertir explícitamente a boolean para evitar problemas de tipo
                                const retiroExiste = Boolean(retiroInfoExistsAfterSave);
                                const retiroRealizado = Boolean(retiroData.retiro_realizado);
                                const tieneRetiroRegistrado = retiroExiste && retiroRealizado;
                                
                                console.log(`🔄 Actualizando botón escritorio para cliente ${clienteId}:`, {
                                    retiroInfoExists: retiroInfoExistsAfterSave,
                                    retiroInfoExists_boolean: retiroExiste,
                                    retiroRealizado: retiroData.retiro_realizado,
                                    retiroRealizado_boolean: retiroRealizado,
                                    tieneRetiroRegistrado: tieneRetiroRegistrado,
                                    botonActual: actionButtonDesk.innerHTML,
                                    switchChecked: switchDesk ? switchDesk.checked : 'N/A'
                                });
                                
                                // CORRECCIÓN ADICIONAL: Si el switch está marcado, debe haber un retiro
                                if (switchDesk && switchDesk.checked && !tieneRetiroRegistrado) {
                                    console.log('⚠️ INCONSISTENCIA DETECTADA: Switch marcado pero sin retiro registrado. Forzando corrección.');
                                    const tieneRetiroRegistradoCorregido = true; // Forzar estado correcto
                                    
                                    // Limpiar todas las clases primero
                                    actionButtonDesk.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                                    actionButtonDesk.disabled = false;
                                    
                                    // Forzar estado de editar
                                    actionButtonDesk.innerHTML = `<i class="fas fa-edit"></i> Editar`;
                                    actionButtonDesk.classList.add('btn-warning');
                                    console.log(`🔧 CORRECCIÓN FORZADA: Botón cambiado a EDITAR para cliente ${clienteId}`);
                                    
                                } else {
                                    // Lógica normal
                                    // Limpiar todas las clases primero
                                    actionButtonDesk.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                                    actionButtonDesk.disabled = false;
                                    
                                    if (tieneRetiroRegistrado) {
                                        // Estado: Editar (permitir modificar retiro registrado)
                                        actionButtonDesk.innerHTML = `<i class="fas fa-edit"></i> Editar`;
                                        actionButtonDesk.classList.add('btn-warning');
                                        console.log(`✅ Botón ESCRITORIO cambiado a EDITAR (amarillo) para cliente ${clienteId}`);
                                    } else {
                                        // Estado: Registrar (editable)
                                        actionButtonDesk.innerHTML = `<i class="fas fa-save"></i> Registrar`;
                                        actionButtonDesk.classList.add('btn-outline-primary');
                                        console.log(`✅ Botón ESCRITORIO cambiado a REGISTRAR (azul) para cliente ${clienteId}`);
                                    }
                                }
                                
                                console.log(`🎯 Estado final del botón escritorio:`, {
                                    innerHTML: actionButtonDesk.innerHTML,
                                    className: actionButtonDesk.className,
                                    disabled: actionButtonDesk.disabled
                                });
                            } catch (error) {
                                console.error(`❌ Error al actualizar botón escritorio para cliente ${clienteId}:`, error);
                                showDynamicNotification('Error interno al actualizar la interfaz. Intente refrescar la página.', 'warning');
                            }
                        }
                        if (retiroData.retiro_realizado) {
                            rowDesk.classList.add('retired-row');
                        } else {
                            rowDesk.classList.remove('retired-row');
                        }
                    }

                    // Actualizar tarjeta en vista móvil
                    console.log('🔍 Buscando elementos móviles...');
                    const cardMovil = document.getElementById(`cliente-card-movil-${clienteId}`);
                    console.log(`🔍 Tarjeta móvil encontrada:`, cardMovil ? 'SÍ' : 'NO');
                    
                    if (cardMovil) {
                        const switchMob = cardMovil.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
                        const sobresMob = cardMovil.querySelector(`#sobres_mob_${clienteId}`);
                        const montoMob = cardMovil.querySelector(`#monto_mob_${clienteId}`);
                        const dotacionInputMob = cardMovil.querySelector('input[name="dotacion_maxima_form"]');
                        actionButtonMob = cardMovil.querySelector('.submit-retiro-btn'); // Asignar a la variable global

                        console.log('🔍 Elementos móviles encontrados:', {
                            switchMob: switchMob ? 'SÍ' : 'NO',
                            sobresMob: sobresMob ? 'SÍ' : 'NO',
                            montoMob: montoMob ? 'SÍ' : 'NO',
                            dotacionInputMob: dotacionInputMob ? 'SÍ' : 'NO',
                            actionButtonMob: actionButtonMob ? 'SÍ' : 'NO'
                        });

                        if (dotacionInputMob) {
                            dotacionInputMob.value = dotacionMaxima;
                            // Actualizar el valor original para que no aparezca como "cambio pendiente"
                            dotacionInputMob.dataset.originalValue = dotacionMaxima;
                            // Restaurar el estilo original (quitar indicadores de cambio)
                            dotacionInputMob.style.borderColor = '';
                            dotacionInputMob.style.backgroundColor = '';
                            dotacionInputMob.style.borderWidth = '';
                            // Quitar indicador de cambio pendiente
                            const changeIndicator = dotacionInputMob.parentElement.querySelector('.change-indicator');
                            if (changeIndicator) {
                                changeIndicator.remove();
                            }
                            console.log(`📊 Dotación máxima actualizada en móvil: ${dotacionMaxima}`);
                        }
                        if (switchMob) {
                            switchMob.checked = retiroData.retiro_realizado;
                            switchMob.dataset.dotacion = dotacionMaxima;
                        }
                        if (sobresMob) {
                            sobresMob.value = retiroData.sobres_retirados;
                            // Los inputs siempre estarán habilitados para permitir edición
                            sobresMob.removeAttribute('disabled');
                            sobresMob.max = dotacionMaxima;
                        }
                        if (montoMob) montoMob.textContent = formatCurrency(retiroData.monto_pagado);

                        if (actionButtonMob) {
                            try {
                                // Determinar el estado del botón basado en los datos reales
                                // Convertir explícitamente a boolean para evitar problemas de tipo
                                const retiroExiste = Boolean(retiroInfoExistsAfterSave);
                                const retiroRealizado = Boolean(retiroData.retiro_realizado);
                                const tieneRetiroRegistrado = retiroExiste && retiroRealizado;
                                
                                console.log(`🔄 Actualizando botón móvil para cliente ${clienteId}:`, {
                                    retiroInfoExists: retiroInfoExistsAfterSave,
                                    retiroInfoExists_boolean: retiroExiste,
                                    retiroRealizado: retiroData.retiro_realizado,
                                    retiroRealizado_boolean: retiroRealizado,
                                    tieneRetiroRegistrado: tieneRetiroRegistrado,
                                    botonActual: actionButtonMob.innerHTML,
                                    switchChecked: switchMob ? switchMob.checked : 'N/A'
                                });
                                
                                // CORRECCIÓN ADICIONAL: Si el switch está marcado, debe haber un retiro
                                if (switchMob && switchMob.checked && !tieneRetiroRegistrado) {
                                    console.log('⚠️ INCONSISTENCIA DETECTADA MÓVIL: Switch marcado pero sin retiro registrado. Forzando corrección.');
                                    
                                    // Limpiar todas las clases primero
                                    actionButtonMob.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                                    actionButtonMob.disabled = false;
                                    
                                    // Forzar estado de editar
                                    actionButtonMob.innerHTML = `<i class="fas fa-edit"></i> Editar`;
                                    actionButtonMob.classList.add('btn-warning');
                                    console.log(`🔧 CORRECCIÓN FORZADA MÓVIL: Botón cambiado a EDITAR para cliente ${clienteId}`);
                                    
                                } else {
                                    // Lógica normal
                                    // Limpiar todas las clases primero
                                    actionButtonMob.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                                    actionButtonMob.disabled = false;
                                    
                                    if (tieneRetiroRegistrado) {
                                        // Estado: Editar (permitir modificar retiro registrado)
                                        actionButtonMob.innerHTML = `<i class="fas fa-edit"></i> Editar`;
                                        actionButtonMob.classList.add('btn-warning');
                                        console.log(`✅ Botón MÓVIL cambiado a EDITAR (amarillo) para cliente ${clienteId}`);
                                    } else {
                                        // Estado: Registrar (editable)
                                        actionButtonMob.innerHTML = `<i class="fas fa-save"></i> Registrar Retiro`;
                                        actionButtonMob.classList.add('btn-outline-primary');
                                        console.log(`✅ Botón MÓVIL cambiado a REGISTRAR (azul) para cliente ${clienteId}`);
                                    }
                                }
                                
                                console.log(`🎯 Estado final del botón móvil:`, {
                                    innerHTML: actionButtonMob.innerHTML,
                                    className: actionButtonMob.className,
                                    disabled: actionButtonMob.disabled
                                });
                            } catch (error) {
                                console.error(`❌ Error al actualizar botón móvil para cliente ${clienteId}:`, error);
                                showDynamicNotification('Error interno al actualizar la interfaz móvil. Intente refrescar la página.', 'warning');
                            }
                        }
                         if (retiroData.retiro_realizado) {
                            cardMovil.classList.add('retired');
                        } else {
                            cardMovil.classList.remove('retired');
                        }
                    }
                    
                    if (data.global_stats) {
                        updateGlobalInventoryStats(data.global_stats);
                    }
                    recalculatePageSubtotals();
                    
                    // FORZAR ACTUALIZACIÓN VISUAL - Refrescar la visualización de los botones
                    console.log('🔄 FORZANDO ACTUALIZACIÓN VISUAL FINAL');
                    setTimeout(() => {
                        // Re-verificar y actualizar los botones para asegurar que estén en el estado correcto
                        const finalRowDesk = document.getElementById(`cliente-row-desk-${clienteId}`);
                        const finalCardMovil = document.getElementById(`cliente-card-movil-${clienteId}`);
                        
                        if (finalRowDesk) {
                            const finalButtonDesk = finalRowDesk.querySelector('.submit-retiro-btn');
                            if (finalButtonDesk) {
                                console.log(`🎯 ESTADO FINAL - Botón Escritorio Cliente ${clienteId}:`, {
                                    innerHTML: finalButtonDesk.innerHTML,
                                    classes: finalButtonDesk.className,
                                    disabled: finalButtonDesk.disabled
                                });
                            }
                        }
                        
                        if (finalCardMovil) {
                            const finalButtonMovil = finalCardMovil.querySelector('.submit-retiro-btn');
                            if (finalButtonMovil) {
                                console.log(`🎯 ESTADO FINAL - Botón Móvil Cliente ${clienteId}:`, {
                                    innerHTML: finalButtonMovil.innerHTML,
                                    classes: finalButtonMovil.className,
                                    disabled: finalButtonMovil.disabled
                                });
                            }
                        }
                    }, 50);
                    
                    // Forzar recálculo de montos después de la actualización exitosa
                    setTimeout(() => {
                        console.log('🔄 Forzando recálculo de montos después de actualización exitosa...');
                        
                        // Recalcular monto en escritorio
                        if (sobresDesk) {
                            console.log(`🧮 Recalculando monto escritorio para cliente ${clienteId}:`, {
                                sobres: sobresDesk.value,
                                dotacion: dotacionMaxima,
                                precio: sobresDesk.dataset.precio
                            });
                            calcularMonto(sobresDesk);
                        }
                        
                        // Recalcular monto en móvil
                        if (sobresMob) {
                            console.log(`🧮 Recalculando monto móvil para cliente ${clienteId}:`, {
                                sobres: sobresMob.value,
                                dotacion: dotacionMaxima,
                                precio: sobresMob.dataset.precio
                            });
                            calcularMonto(sobresMob);
                        }
                        
                        console.log('✅ Recálculo de montos completado');
                    }, 100);

                    // DEBUG: Verificar estado después de todas las actualizaciones
                    setTimeout(() => {
                        console.log('🔍 VERIFICACIÓN POST-ACTUALIZACIÓN:');
                        if (typeof verificarEstadoBotones === 'function') {
                            verificarEstadoBotones();
                        } else {
                            console.log('🔍 Verificación manual del botón actualizado:', {
                                clienteId: clienteId,
                                botonEscritorio: actionButtonDesk ? actionButtonDesk.innerHTML : 'No encontrado',
                                botonMovil: actionButtonMob ? actionButtonMob.innerHTML : 'No encontrado'
                            });
                        }
                    }, 200);
                }
            })
            .catch(error => {
                console.error('❌ Error en fetch completo:', error);
                console.error('❌ Stack trace:', error.stack);
                showDynamicNotification(`Error de conexión o procesamiento: ${error.message}. Revise la consola para más detalles.`, 'danger');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                }
            });
        });
    });


    const searchInputDashboard = document.getElementById('busqueda_dashboard_input');
    const suggestionsContainerDashboard = document.getElementById('sugerencias_busqueda_dashboard_container');
    const searchFormDashboard = document.getElementById('formBusquedaDashboard');
    const searchButtonGroup = searchFormDashboard.querySelector('.input-group-custom'); // Obtener el grupo de input para la posición
    let debounceTimerDashboard;

    if (searchInputDashboard && suggestionsContainerDashboard && searchFormDashboard && searchButtonGroup) {
        searchInputDashboard.addEventListener('input', function () {
            clearTimeout(debounceTimerDashboard);
            const query = this.value.trim();
            
            if (query.length < 1) {
                suggestionsContainerDashboard.innerHTML = '';
                suggestionsContainerDashboard.style.display = 'none';
                return;
            }
            
            const fetchURL = '../clientes/autocomplete_sugerencias.php'; 

            debounceTimerDashboard = setTimeout(() => {
                fetch(`${fetchURL}?term=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red al obtener sugerencias: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        suggestionsContainerDashboard.innerHTML = '';
                        if (data.length > 0) {
                            // Obtener la posición del input-group-custom que es el padre directo relativo
                            const groupRect = searchButtonGroup.getBoundingClientRect();
                            const formRect = searchFormDashboard.getBoundingClientRect();

                            suggestionsContainerDashboard.style.left = `${groupRect.left - formRect.left}px`;
                            suggestionsContainerDashboard.style.top = `${groupRect.bottom - formRect.top}px`;
                            suggestionsContainerDashboard.style.width = `${groupRect.width}px`;
                            suggestionsContainerDashboard.style.display = 'block';

                            data.forEach(item => {
                                const a = document.createElement('a');
                                a.classList.add('suggestion-item'); 
                                
                                let displayText = `<strong>${item.nombre_completo || 'N/A'}</strong>`;
                                if (item.numero_tarjeta) {
                                    displayText += ` <span class="small-text text-muted"> (Tjt: ${item.numero_tarjeta})</small>`;
                                }
                                a.innerHTML = displayText;

                                a.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    searchInputDashboard.value = item.nombre_completo;
                                    suggestionsContainerDashboard.innerHTML = '';
                                    suggestionsContainerDashboard.style.display = 'none';
                                    searchFormDashboard.submit();
                                });
                                suggestionsContainerDashboard.appendChild(a);
                            });
                        } else {
                            suggestionsContainerDashboard.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error en fetch de sugerencias (dashboard):', error);
                        suggestionsContainerDashboard.innerHTML = '<div class="suggestion-item" style="color: var(--danger-red);">Error al cargar sugerencias.</div>';
                        suggestionsContainerDashboard.style.display = 'none'; 
                    });
            }, 300);
        });

        document.addEventListener('click', function(event) {
            if (suggestionsContainerDashboard && searchInputDashboard) {
                const isClickInsideSearch = searchInputDashboard.contains(event.target);
                const isClickInsideSuggestions = suggestionsContainerDashboard.contains(event.target);

                if (!isClickInsideSearch && !isClickInsideSuggestions) {
                    suggestionsContainerDashboard.innerHTML = '';
                    suggestionsContainerDashboard.style.display = 'none';
                }
            }
        });
    }


    function calcularMonto(sobresInputElem) {
        if (!sobresInputElem) {
            console.warn('⚠️ calcularMonto llamado con input null/undefined');
            return;
        }
        
        const sobres = parseInt(sobresInputElem.value) || 0;
        const precio = parseFloat(sobresInputElem.dataset.precio) || 0;
        const montoSelector = sobresInputElem.dataset.targetMonto;
        const montoElement = document.querySelector(montoSelector);

        console.log(`🧮 Calculando monto:`, {
            inputId: sobresInputElem.id,
            sobres: sobres,
            precio: precio,
            montoSelector: montoSelector,
            montoElement: montoElement ? montoElement.id : 'NO ENCONTRADO',
            elementTagName: montoElement ? montoElement.tagName : 'N/A',
            elementClasses: montoElement ? montoElement.className : 'N/A'
        });

        if (montoElement) {
            const montoTotal = sobres * precio;
            const montoFormateado = formatCurrency(montoTotal);
            const montoAnterior = montoElement.textContent;
            
            // Actualizar el contenido
            montoElement.textContent = montoFormateado;
            
            // Feedback visual si el monto cambió
            if (montoAnterior !== montoFormateado && montoTotal > 0) {
                montoElement.style.transition = 'all 0.3s ease';
                montoElement.style.transform = 'scale(1.05)';
                montoElement.style.fontWeight = 'bold';
                montoElement.style.color = '#059669'; // Verde para cambios positivos
                
                setTimeout(() => {
                    montoElement.style.transform = 'scale(1)';
                    montoElement.style.fontWeight = '';
                    montoElement.style.color = '';
                }, 800);
            }
            
            // Si el monto es 0, aplicar estilo diferente
            if (montoTotal === 0) {
                montoElement.style.color = '#6b7280'; // Gris para cero
                montoElement.style.fontWeight = 'normal';
            }
            
            console.log(`💰 Monto calculado: ${sobres} sobres × $${precio.toFixed(2)} = ${montoFormateado}`);
            console.log(`✅ Elemento actualizado: ${montoElement.tagName}#${montoElement.id} = "${montoElement.textContent}"`);
            
            // Actualizar también inputs hidden si existen (para envío de formulario)
            const form = sobresInputElem.closest('form');
            if (form) {
                let hiddenMontoInput = form.querySelector('input[name="monto_calculado"]');
                if (hiddenMontoInput) {
                    hiddenMontoInput.value = montoTotal.toFixed(2);
                    console.log(`🔒 Input oculto de monto actualizado: ${montoTotal.toFixed(2)}`);
                }
            }
            
        } else {
            console.error(`❌ No se encontró el elemento de monto con selector: ${montoSelector}`);
            // Intentar encontrar el elemento con métodos alternativos para debug
            const alternativeElement = document.getElementById(montoSelector.replace('#', ''));
            if (alternativeElement) {
                console.log(`🔄 Elemento encontrado con getElementById: ${alternativeElement.tagName}#${alternativeElement.id}`);
                // Intentar usar este elemento como fallback
                const montoTotal = sobres * precio;
                const montoFormateado = formatCurrency(montoTotal);
                alternativeElement.textContent = montoFormateado;
                console.log(`🔧 Fallback aplicado: ${montoFormateado}`);
            }
        }
    }

    document.querySelectorAll('input[type="checkbox"][name="retiro_checkbox"]').forEach(function(switchEl) {
        const targetSobresSelector = switchEl.dataset.targetSobres;
        const sobresInput = document.querySelector(targetSobresSelector);
        
        console.log(`🔧 Configurando switch para cliente:`, {
            switchId: switchEl.id,
            targetSobresSelector: targetSobresSelector,
            sobresInput: sobresInput ? sobresInput.id : 'NO ENCONTRADO',
            dotacion: switchEl.dataset.dotacion
        });
        
        function toggleSobresInput() {
            if (!sobresInput) {
                console.error(`❌ No se encontró el input de sobres con selector: ${targetSobresSelector}`);
                return;
            }
            const dotacionActual = parseInt(switchEl.dataset.dotacion) || 0;
            if (switchEl.checked) {
                // Activar el input y establecer la dotación máxima por defecto
                sobresInput.removeAttribute('disabled');
                // Solo establecer dotación completa si el valor actual es 0
                if (parseInt(sobresInput.value) === 0) {
                    sobresInput.value = dotacionActual; 
                }
                console.log(`✅ Switch activado - Cliente dotación: ${dotacionActual}, sobres establecidos: ${sobresInput.value}`);
            } else {
                // NO desactivar el input, solo poner en 0 para permitir edición posterior
                sobresInput.removeAttribute('disabled');
                sobresInput.value = '0';
                console.log(`❌ Switch desactivado - sobres establecidos en 0, pero input sigue habilitado para edición`);
            }
            calcularMonto(sobresInput);
        }
        
        toggleSobresInput(); // Ejecutar al cargar la página para el estado inicial
        switchEl.addEventListener('change', toggleSobresInput);
    });

    document.querySelectorAll('.sobres-input').forEach(function(input) {
        console.log(`🎯 Configurando input de sobres: ${input.id}`, {
            dataPrecio: input.dataset.precio,
            dataTargetMonto: input.dataset.targetMonto,
            valorInicial: input.value
        });
        
        input.addEventListener('input', function() {
            console.log(`📝 Evento INPUT disparado en: ${this.id}, nuevo valor: ${this.value}`);
            
            const form = this.closest('form');
            if (!form) {
                console.warn(`⚠️ No se encontró form para input: ${this.id}`);
                return;
            }
            const dotacionMaximaInput = form.querySelector('input[name="dotacion_maxima_form"]');
            const switchEl = form.querySelector('input[type="checkbox"][name="retiro_checkbox"]');
            if (!dotacionMaximaInput) {
                console.warn(`⚠️ No se encontró dotacionMaximaInput para input: ${this.id}`);
                return;
            }

            const dotacionMaxima = parseInt(dotacionMaximaInput.value) || 0;
            let currentValue = parseInt(this.value) || 0;

            // Validar que no exceda la dotación máxima
            if (currentValue > dotacionMaxima) {
                this.value = dotacionMaxima;
                console.log(`⚠️ Valor ajustado al máximo permitido: ${dotacionMaxima}`);
            }
            // Validar que no sea negativo
            else if (currentValue < 0) {
                this.value = 0;
                console.log(`⚠️ Valor ajustado a 0 (no puede ser negativo)`);
            }
            
            console.log(`🔄 Llamando a calcularMonto para input: ${this.id}`);
            calcularMonto(this);
            
            // Log para debug
            const finalValue = parseInt(this.value) || 0;
            console.log(`📝 Sobres para cliente: ${finalValue} de ${dotacionMaxima} máximos`);
        });
        
        // Evento change para cuando el usuario presiona Enter o sale del campo
        input.addEventListener('change', function() {
            console.log(`🔄 Evento CHANGE disparado en: ${this.id}, nuevo valor: ${this.value}`);
            calcularMonto(this);
        });
        
        // Evento keyup para actualización en tiempo real mientras escribes
        input.addEventListener('keyup', function() {
            console.log(`⌨️ Evento KEYUP disparado en: ${this.id}, nuevo valor: ${this.value}`);
            calcularMonto(this);
        });
        
        // Evento blur para cuando el usuario sale del campo (hace clic fuera)
        input.addEventListener('blur', function() {
            console.log(`�️ Evento BLUR disparado en: ${this.id}, valor final: ${this.value}`);
            calcularMonto(this);
        });
        
        // Test directo: agregar un listener de click para debug
        input.addEventListener('focus', function() {
            console.log(`🎯 Input enfocado: ${this.id}`, {
                value: this.value,
                dataPrecio: this.dataset.precio,
                dataTargetMonto: this.dataset.targetMonto,
                disabled: this.disabled
            });
        });
        
        // Recalcular monto al cargar la página si el switch está activado
        const form = input.closest('form');
        if (form) {
            const switchEl = form.querySelector('input[type="checkbox"][name="retiro_checkbox"]');
            if (switchEl && switchEl.checked) {
                calcularMonto(input);
                console.log(`🔄 Monto recalculado al cargar página para input con valor: ${input.value}`);
            }
        }
    });

    // Función para validar y forzar consistencia de datos
    function validarYForzarConsistencia(clienteId, dotacionMaxima) {
        console.log(`🔍 Validando consistencia para cliente ${clienteId} con dotación ${dotacionMaxima}`);
        
        // Validar escritorio
        const rowDesk = document.getElementById(`cliente-row-desk-${clienteId}`);
        if (rowDesk) {
            const switchDesk = rowDesk.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
            const sobresDesk = rowDesk.querySelector(`#sobres_desk_${clienteId}`);
            const dotacionInputDesk = rowDesk.querySelector('input[name="dotacion_maxima_form"]');
            
            if (dotacionInputDesk && switchDesk && sobresDesk) {
                // Asegurar que todos los elementos tengan la dotación correcta
                if (parseInt(dotacionInputDesk.value) !== dotacionMaxima) {
                    console.log(`🔧 Corrigiendo dotación escritorio: ${dotacionInputDesk.value} → ${dotacionMaxima}`);
                    dotacionInputDesk.value = dotacionMaxima;
                }
                
                switchDesk.dataset.dotacion = dotacionMaxima;
                sobresDesk.max = dotacionMaxima;
                
                // Si los sobres exceden la nueva dotación, ajustar
                const sobresActuales = parseInt(sobresDesk.value) || 0;
                if (sobresActuales > dotacionMaxima) {
                    console.log(`🔧 Ajustando sobres escritorio: ${sobresActuales} → ${dotacionMaxima}`);
                    sobresDesk.value = dotacionMaxima;
                    calcularMonto(sobresDesk);
                }
            }
        }
        
        // Validar móvil
        const cardMovil = document.getElementById(`cliente-card-mob-${clienteId}`);
        if (cardMovil) {
            const switchMob = cardMovil.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
            const sobresMob = cardMovil.querySelector(`#sobres_mob_${clienteId}`);
            const dotacionInputMob = cardMovil.querySelector('input[name="dotacion_maxima_form"]');
            
            if (dotacionInputMob && switchMob && sobresMob) {
                // Asegurar que todos los elementos tengan la dotación correcta
                if (parseInt(dotacionInputMob.value) !== dotacionMaxima) {
                    console.log(`🔧 Corrigiendo dotación móvil: ${dotacionInputMob.value} → ${dotacionMaxima}`);
                    dotacionInputMob.value = dotacionMaxima;
                }
                
                switchMob.dataset.dotacion = dotacionMaxima;
                sobresMob.max = dotacionMaxima;
                
                // Si los sobres exceden la nueva dotación, ajustar
                const sobresActuales = parseInt(sobresMob.value) || 0;
                if (sobresActuales > dotacionMaxima) {
                    console.log(`🔧 Ajustando sobres móvil: ${sobresActuales} → ${dotacionMaxima}`);
                    sobresMob.value = dotacionMaxima;
                    calcularMonto(sobresMob);
                }
            }
        }
        
        console.log(`✅ Validación de consistencia completada para cliente ${clienteId}`);
    }

    document.querySelectorAll('input[name="dotacion_maxima_form"]').forEach(function(dotacionInput) {
        // Guardar valor original para detectar cambios
        dotacionInput.dataset.originalValue = dotacionInput.value;
        
        // Función para manejar el cambio de dotación
        function manejarCambioDotacion() {
            const form = dotacionInput.closest('form');
            if (!form) return;
            
            const switchEl = form.querySelector('input[type="checkbox"][name="retiro_checkbox"]');
            const sobresInput = form.querySelector('.sobres-input');
            const montoElement = sobresInput ? document.querySelector(sobresInput.dataset.targetMonto) : null;
            const nuevaDotacion = parseInt(dotacionInput.value) || 0;
            const valorOriginal = parseInt(dotacionInput.dataset.originalValue) || 0;

            // Obtener el ID del cliente desde el formulario
            const clienteIdHidden = form.querySelector('input[name="cliente_id"]');
            const clienteId = clienteIdHidden ? parseInt(clienteIdHidden.value) : null;

            console.log(`🔄 Dotación cambiada de ${valorOriginal} a: ${nuevaDotacion} para cliente ${clienteId}`);

            // Feedback visual si ha cambiado
            if (nuevaDotacion !== valorOriginal) {
                dotacionInput.style.borderColor = '#f59e0b'; // Color naranja para indicar cambio
                dotacionInput.style.backgroundColor = '#fef3c7'; // Fondo amarillo claro
                dotacionInput.style.borderWidth = '2px';
                
                // Mostrar indicador de cambio pendiente
                let changeIndicator = dotacionInput.parentElement.querySelector('.change-indicator');
                if (!changeIndicator) {
                    changeIndicator = document.createElement('span');
                    changeIndicator.className = 'change-indicator text-xs text-orange-600 ml-2 font-medium';
                    changeIndicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Cambio pendiente';
                    dotacionInput.parentElement.appendChild(changeIndicator);
                }
                
                // Agregar animación de parpadeo sutil al monto si es necesario
                if (montoElement && switchEl && switchEl.checked) {
                    montoElement.style.transition = 'background-color 0.3s ease';
                    montoElement.style.backgroundColor = '#fef3c7';
                    setTimeout(() => {
                        montoElement.style.backgroundColor = '';
                    }, 1000);
                }
            } else {
                // Restaurar estilo original
                dotacionInput.style.borderColor = '';
                dotacionInput.style.backgroundColor = '';
                dotacionInput.style.borderWidth = '';
                
                // Quitar indicador de cambio
                const changeIndicator = dotacionInput.parentElement.querySelector('.change-indicator');
                if (changeIndicator) {
                    changeIndicator.remove();
                }
            }

            // Actualizar el dataset del switch con la nueva dotación
            if (switchEl) {
                switchEl.dataset.dotacion = nuevaDotacion;
                console.log(`📊 Dataset del switch actualizado a: ${nuevaDotacion}`);
            }
            
            if (sobresInput) {
                // Actualizar el máximo del input de sobres
                const maxAnterior = parseInt(sobresInput.max) || 0;
                sobresInput.max = nuevaDotacion;
                console.log(`📏 Máximo de sobres actualizado de ${maxAnterior} a ${nuevaDotacion}`);
                
                // Si el switch está activado, ajustar los sobres según la nueva dotación
                if (switchEl && switchEl.checked) {
                    const valorSobresActual = parseInt(sobresInput.value) || 0;
                    
                    // Si los sobres actuales exceden la nueva dotación, ajustar
                    if (valorSobresActual > nuevaDotacion) {
                        sobresInput.value = nuevaDotacion;
                        console.log(`⚠️ Sobres ajustados de ${valorSobresActual} a ${nuevaDotacion} (nueva dotación máxima)`);
                        
                        // Destacar visualmente el cambio
                        sobresInput.style.transition = 'background-color 0.3s ease';
                        sobresInput.style.backgroundColor = '#fee2e2';
                        setTimeout(() => {
                            sobresInput.style.backgroundColor = '';
                        }, 1500);
                        
                        calcularMonto(sobresInput);
                    }
                    // Si no hay sobres asignados o es 0, asignar la nueva dotación completa
                    else if (valorSobresActual === 0 || nuevaDotacion > maxAnterior) {
                        sobresInput.value = nuevaDotacion;
                        console.log(`✅ Sobres establecidos en dotación completa: ${nuevaDotacion}`);
                        
                        // Destacar visualmente el cambio
                        sobresInput.style.transition = 'background-color 0.3s ease';
                        sobresInput.style.backgroundColor = '#dcfce7';
                        setTimeout(() => {
                            sobresInput.style.backgroundColor = '';
                        }, 1500);
                        
                        calcularMonto(sobresInput);
                    }
                    // Si el valor actual está dentro del rango, mantenerlo pero recalcular
                    else {
                        console.log(`ℹ️ Sobres mantenidos en: ${valorSobresActual} (dentro del rango válido)`);
                        calcularMonto(sobresInput); // Recalcular inmediatamente
                        
                        // Feedback visual sutil
                        if (montoElement) {
                            montoElement.style.transition = 'color 0.3s ease';
                            montoElement.style.color = '#059669';
                            setTimeout(() => {
                                montoElement.style.color = '';
                            }, 1000);
                        }
                    }
                }
                // Si el switch no está activado pero hay valor en sobres, recalcular también
                else if (!switchEl || !switchEl.checked) {
                    if (parseInt(sobresInput.value) > 0) {
                        calcularMonto(sobresInput);
                        console.log(`🔄 Monto recalculado para cliente ${clienteId} (switch desactivado)`);
                    }
                }
            }

            // Aplicar validación de consistencia si tenemos el ID del cliente
            if (clienteId) {
                setTimeout(() => {
                    validarYForzarConsistencia(clienteId, nuevaDotacion);
                }, 100);
            }
        }
        
        // Agregar event listeners para diferentes eventos
        dotacionInput.addEventListener('change', manejarCambioDotacion);
        dotacionInput.addEventListener('input', manejarCambioDotacion); // Para cambios en tiempo real
        dotacionInput.addEventListener('keyup', function(e) {
            // Recalcular en tiempo real al escribir, pero con un pequeño delay
            clearTimeout(this.recalcTimeout);
            this.recalcTimeout = setTimeout(manejarCambioDotacion, 300);
        });
    });
    
    // FUNCIÓN DE TEST MANUAL - puedes ejecutar esto en la consola del navegador
    window.testCalcularMonto = function(inputId, newValue) {
        console.log(`🧪 TEST MANUAL: Probando cálculo para ${inputId} con valor ${newValue}`);
        const input = document.getElementById(inputId);
        if (input) {
            input.value = newValue;
            console.log(`✅ Valor establecido: ${input.value}`);
            calcularMonto(input);
        } else {
            console.error(`❌ No se encontró input con ID: ${inputId}`);
        }
    };
    
    // FUNCIÓN PARA LISTAR TODOS LOS INPUTS DE SOBRES
    window.listarInputsSobres = function() {
        console.log('📋 Lista de todos los inputs de sobres:');
        const inputs = document.querySelectorAll('.sobres-input');
        inputs.forEach((input, index) => {
            console.log(`  ${index + 1}. ID: ${input.id}`, {
                value: input.value,
                disabled: input.disabled,
                dataPrecio: input.dataset.precio,
                dataTargetMonto: input.dataset.targetMonto
            });
        });
        return inputs;
    };
    
    // FUNCIÓN PARA VERIFICAR ESTADO DE BOTONES
    window.verificarEstadoBotones = function(clienteEspecifico = null) {
        console.log(`🔍 Estado actual de ${clienteEspecifico ? 'cliente ' + clienteEspecifico : 'todos los botones'}:`);
        const buttons = document.querySelectorAll('.submit-retiro-btn');
        buttons.forEach((button, index) => {
            const containerElement = button.closest('.client-card-mobile') || button.closest('tr');
            let clienteId = 'DESCONOCIDO';
            
            if (containerElement) {
                if (containerElement.id.includes('cliente-card-movil-')) {
                    clienteId = containerElement.id.replace('cliente-card-movil-', '');
                } else if (containerElement.id.includes('cliente-row-desk-')) {
                    clienteId = containerElement.id.replace('cliente-row-desk-', '');
                }
            }
            
            // Si se especificó un cliente, solo mostrar ese
            if (clienteEspecifico && clienteId !== clienteEspecifico.toString()) {
                return;
            }
            
            const vista = containerElement?.closest('.mobile-only') ? 'MÓVIL' : 'ESCRITORIO';
            
            console.log(`  ${index + 1}. Cliente ID: ${clienteId} (${vista})`, {
                innerHTML: button.innerHTML,
                classes: button.className,
                disabled: button.disabled,
                tipo: button.innerHTML.includes('Editar') ? 'EDITAR' : 'REGISTRAR',
                elemento: button
            });
        });
        return buttons;
    };
    
    // FUNCIÓN ESPECÍFICA PARA VERIFICAR UN CLIENTE
    window.verificarCliente = function(clienteId) {
        console.log(`🎯 Verificando estado completo del cliente ${clienteId}:`);
        
        // Buscar elementos del cliente en escritorio
        const rowDesk = document.getElementById(`cliente-row-desk-${clienteId}`);
        if (rowDesk) {
            const switchDesk = rowDesk.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
            const sobresDesk = rowDesk.querySelector(`#sobres_desk_${clienteId}`);
            const montoDesk = rowDesk.querySelector(`#monto_desk_${clienteId}`);
            const actionButtonDesk = rowDesk.querySelector('.submit-retiro-btn');
            
            console.log(`📊 ESCRITORIO - Cliente ${clienteId}:`, {
                switchChecked: switchDesk?.checked,
                sobresValue: sobresDesk?.value,
                montoText: montoDesk?.textContent,
                botonHTML: actionButtonDesk?.innerHTML,
                botonClasses: actionButtonDesk?.className
            });
        }
        
        // Buscar elementos del cliente en móvil
        const cardMovil = document.getElementById(`cliente-card-movil-${clienteId}`);
        if (cardMovil) {
            const switchMob = cardMovil.querySelector(`input[type="checkbox"][name="retiro_checkbox"]`);
            const sobresMob = cardMovil.querySelector(`#sobres_mob_${clienteId}`);
            const montoMob = cardMovil.querySelector(`#monto_mob_${clienteId}`);
            const actionButtonMob = cardMovil.querySelector('.submit-retiro-btn');
            
            console.log(`📱 MÓVIL - Cliente ${clienteId}:`, {
                switchChecked: switchMob?.checked,
                sobresValue: sobresMob?.value,
                montoText: montoMob?.textContent,
                botonHTML: actionButtonMob?.innerHTML,
                botonClasses: actionButtonMob?.className
            });
        }
        
        return { rowDesk, cardMovil };
    };
    
    // FUNCIÓN DE SINCRONIZACIÓN DE BOTONES
    window.sincronizarBotones = function() {
        console.log('🔄 SINCRONIZANDO TODOS LOS BOTONES...');
        
        // Para todos los switches marcados, asegurar que el botón esté en estado "Editar"
        document.querySelectorAll('input[type="checkbox"][name="retiro_checkbox"]:checked').forEach(function(switchEl) {
            const containerElement = switchEl.closest('.client-card-mobile') || switchEl.closest('tr');
            const submitButton = containerElement ? containerElement.querySelector('.submit-retiro-btn') : null;
            
            if (submitButton) {
                let clienteId = 'desconocido';
                if (containerElement.id.includes('cliente-card-movil-')) {
                    clienteId = containerElement.id.replace('cliente-card-movil-', '');
                } else if (containerElement.id.includes('cliente-row-desk-')) {
                    clienteId = containerElement.id.replace('cliente-row-desk-', '');
                }
                
                const vista = containerElement?.closest('.mobile-only') ? 'MÓVIL' : 'ESCRITORIO';
                
                // Si el switch está marcado pero el botón no dice "Editar", corregir
                if (!submitButton.innerHTML.includes('Editar')) {
                    console.log(`🔧 CORRECCIÓN: Switch marcado pero botón incorrecto - Cliente ${clienteId} (${vista})`);
                    
                    submitButton.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                    submitButton.innerHTML = `<i class="fas fa-edit"></i> Editar`;
                    submitButton.classList.add('btn-warning');
                    submitButton.disabled = false;
                    
                    console.log(`✅ CORREGIDO: Botón ${vista} cambiado a EDITAR para cliente ${clienteId}`);
                }
            }
        });
        
        // Para todos los switches no marcados, asegurar que el botón esté en estado "Registrar"
        document.querySelectorAll('input[type="checkbox"][name="retiro_checkbox"]:not(:checked)').forEach(function(switchEl) {
            const containerElement = switchEl.closest('.client-card-mobile') || switchEl.closest('tr');
            const submitButton = containerElement ? containerElement.querySelector('.submit-retiro-btn') : null;
            
            if (submitButton) {
                let clienteId = 'desconocido';
                if (containerElement.id.includes('cliente-card-movil-')) {
                    clienteId = containerElement.id.replace('cliente-card-movil-', '');
                } else if (containerElement.id.includes('cliente-row-desk-')) {
                    clienteId = containerElement.id.replace('cliente-row-desk-', '');
                }
                
                const vista = containerElement?.closest('.mobile-only') ? 'MÓVIL' : 'ESCRITORIO';
                
                // Si el switch no está marcado pero el botón dice "Editar", corregir
                if (submitButton.innerHTML.includes('Editar')) {
                    console.log(`🔧 CORRECCIÓN: Switch no marcado pero botón dice Editar - Cliente ${clienteId} (${vista})`);
                    
                    submitButton.classList.remove('btn-outline-primary', 'btn-success', 'btn-warning');
                    if (vista === 'MÓVIL') {
                        submitButton.innerHTML = `<i class="fas fa-save"></i> Registrar Retiro`;
                    } else {
                        submitButton.innerHTML = `<i class="fas fa-save"></i> Registrar`;
                    }
                    submitButton.classList.add('btn-outline-primary');
                    submitButton.disabled = false;
                    
                    console.log(`✅ CORREGIDO: Botón ${vista} cambiado a REGISTRAR para cliente ${clienteId}`);
                }
            }
        });
    };
    
    // EJECUTAR SINCRONIZACIÓN AL CARGAR LA PÁGINA
    setTimeout(() => {
        console.log('🚀 EJECUTANDO SINCRONIZACIÓN INICIAL...');
        sincronizarBotones();
    }, 1000);
    
    recalculatePageSubtotals(); // Recalcular subtotales al cargar la página
});
</script>
<?php endif; ?>
<?php
// Asegúrate que layout/footer.php sea la versión SIN Bootstrap (o un footer simple)
include_once __DIR__ . "/../layout/footer.php";
ob_end_flush();
?>
