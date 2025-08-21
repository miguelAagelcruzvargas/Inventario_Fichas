<?php
// clientes/editar_cliente.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Editar Cliente"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; 
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

include_once __DIR__ . "/../layout/header.php";
include_once __DIR__ . "/cliente.php";

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: listar_clientes.php");
    exit;
}

$cliente = new Cliente();
$cliente->id = $_GET['id'];
$mensaje = '';
$tipo_mensaje = '';

// Intentar leer el cliente
if (!$cliente->leerUno()) {
    header("Location: listar_clientes.php");
    exit;
}

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Comprobar si todos los campos requeridos están presentes
    if (
        !empty($_POST['nombre_completo']) &&
        !empty($_POST['dotacion_maxima'])
    ) {
        // Asignar valores recibidos
        $cliente->nombre_completo = $_POST['nombre_completo'];
        $cliente->dotacion_maxima = $_POST['dotacion_maxima'];
        $cliente->telefono = isset($_POST['telefono']) ? $_POST['telefono'] : null; // Campo opcional
        
        // Intentar actualizar el cliente
        if ($cliente->actualizar()) {
            $mensaje = "Cliente actualizado correctamente.";
            $tipo_mensaje = "success";
            // Recargar los datos actualizados
            $cliente->leerUno();
        } else {
            $mensaje = "No se pudo actualizar el cliente.";
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "Por favor, complete todos los campos requeridos.";
        $tipo_mensaje = "warning";
    }
}
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex flex-wrap space-x-1 sm:space-x-2">
                <li class="flex items-center">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Inicio</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <a href="listar_clientes.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Clientes</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-700 font-medium">Editar Cliente</span>
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <header class="mb-6 md:mb-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 md:gap-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 text-center md:text-left leading-tight">
                    <i class="fas fa-user-edit text-liconsa-blue mr-2"></i> Editar Cliente
                </h1>
                <a href="listar_clientes.php" class="w-full md:w-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-2.5 px-5 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Listado
                </a>
            </div>
        </header>

        <!-- Mensaje de notificación -->
        <?php if (!empty($mensaje)): 
            $alert_styles = [
                'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
                'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
                'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
                'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
            ];
            $current_alert_style = $alert_styles[$tipo_mensaje] ?? $alert_styles['info'];
        ?>
            <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_mensaje)); ?></p>
                        <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje); ?></p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200/80 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').remove();" aria-label="Close">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="bg-white shadow-xl rounded-xl border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Información del Cliente</h3>
                <p class="text-sm text-gray-600 mt-1">Los campos marcados con <span class="text-red-500">*</span> son obligatorios</p>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $cliente->id); ?>" class="p-6 space-y-6">
                
                <!-- Número de Tarjeta -->
                <div>
                    <label for="numero_tarjeta" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-credit-card text-gray-500 mr-2"></i>Número de Tarjeta:
                    </label>
                    <input type="text" id="numero_tarjeta" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none" 
                           value="<?php echo htmlspecialchars($cliente->numero_tarjeta); ?>" 
                           readonly>
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>El número de tarjeta no puede ser modificado.
                    </p>
                </div>

                <!-- Nombre Completo -->
                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-gray-500 mr-2"></i>Nombre Completo: <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nombre_completo" name="nombre_completo" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($cliente->nombre_completo); ?>" 
                           required>
                </div>

                <!-- Dotación Máxima -->
                <div>
                    <label for="dotacion_maxima" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-box text-gray-500 mr-2"></i>Dotación Máxima: <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="dotacion_maxima" name="dotacion_maxima" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($cliente->dotacion_maxima); ?>" 
                           min="1" required>
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>Cantidad máxima de sobres asignados a este beneficiario.
                    </p>
                </div>

                <!-- Teléfono -->
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-gray-500 mr-2"></i>Número de Teléfono: 
                        <span class="text-gray-500 text-xs">(Opcional para WhatsApp)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input type="tel" id="telefono" name="telefono" 
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue transition duration-150 ease-in-out" 
                               value="<?php echo htmlspecialchars($cliente->telefono ?? ''); ?>" 
                               placeholder="Ejemplo: 2811975587 o 281 197 5587" 
                               pattern="([0-9]{10})|([0-9]{2,3}\s[0-9]{3,4}\s[0-9]{3,4})"
                               title="Ingrese 10 dígitos, con o sin espacios entre ellos">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>Formato: 10 dígitos. Puede ingresar con o sin espacios. Se usará para integración con WhatsApp.
                    </p>
                </div>

                <!-- Estado -->
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on text-gray-500 mr-2"></i>Estado:
                    </label>
                    <div class="flex items-center">
                        <input type="text" id="estado" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none" 
                               value="<?php echo ucfirst(htmlspecialchars($cliente->estado)); ?>" 
                               readonly>
                        <?php if ($cliente->estado == 'activo'): ?>
                            <span class="ml-3 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                        <?php else: ?>
                            <span class="ml-3 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                        <?php endif; ?>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>Para cambiar el estado, use la opción en el listado de clientes.
                    </p>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 pt-6 border-t border-gray-200">
                    <button type="submit" 
                            class="w-full sm:w-auto bg-liconsa-blue hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>Actualizar Cliente
                    </button>
                    <a href="listar_clientes.php" 
                       class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación para el campo de teléfono
    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('change', function() {
            // Solo para validación visual, muestra si el formato es correcto
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
});
</script>

<?php include_once __DIR__ . "/../layout/footer.php"; ?>