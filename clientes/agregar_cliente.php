<?php
// clientes/agregar_cliente.php
session_start();
require_once '../config/security_check.php';
include_once __DIR__ . "/cliente.php";

$cliente = new Cliente();
$mensaje_formulario = '';
$tipo_mensaje_formulario = '';

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesamiento del formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje_formulario = "Error de seguridad. Recargue la página e intente nuevamente.";
        $tipo_mensaje_formulario = "danger";
    } elseif (
        !empty($_POST['numero_tarjeta']) &&
        !empty($_POST['nombre_completo']) &&
        !empty($_POST['dotacion_maxima'])
    ) {
        $numero_tarjeta = preg_replace('/\D/', '', trim($_POST['numero_tarjeta']));
        $nombre_completo = trim($_POST['nombre_completo']);
        $dotacion_maxima = (int)$_POST['dotacion_maxima'];
        
        $telefono_completo = null;
        if (!empty($_POST['telefono'])) {
            $telefono_input = trim($_POST['telefono']);
            if (preg_match('/^\+\d{10,15}$/', $telefono_input)) {
                $telefono_completo = $telefono_input;
            } else {
                $mensaje_formulario = "El formato del teléfono no es válido. Debe incluir la lada internacional (ej: +52XXXXXXXXXX).";
                $tipo_mensaje_formulario = "warning";
            }
        }

        if (empty($mensaje_formulario)) { 
            if (!preg_match('/^\d{1,5}$/', $numero_tarjeta)) {
                $mensaje_formulario = "El número de tarjeta debe tener entre 1 y 5 dígitos numéricos.";
                $tipo_mensaje_formulario = "warning";
            } elseif (strlen($nombre_completo) < 3 || strlen($nombre_completo) > 100) {
                $mensaje_formulario = "El nombre completo debe tener entre 3 y 100 caracteres.";
                $tipo_mensaje_formulario = "warning";
            } elseif ($dotacion_maxima < 1 || $dotacion_maxima > 999) {
                $mensaje_formulario = "La dotación máxima debe estar entre 1 y 999.";
                $tipo_mensaje_formulario = "warning";
            } else {
                // Verificar si ya existe el número de tarjeta
                if ($cliente->existeTarjeta($numero_tarjeta)) {
                    $mensaje_formulario = "Ya existe un cliente con el número de tarjeta $numero_tarjeta.";
                    $tipo_mensaje_formulario = "danger";
                } else {
                    // Crear cliente usando el método crearCliente
                    if ($cliente->crearCliente($numero_tarjeta, $nombre_completo, $dotacion_maxima, $telefono_completo)) {
                        $_SESSION['mensaje_accion'] = "Cliente agregado exitosamente.";
                        $_SESSION['tipo_mensaje_accion'] = "success";
                        header("Location: listar_clientes.php"); 
                        exit;
                    } else {
                        $mensaje_formulario = "Error al crear el cliente. Intente nuevamente.";
                        $tipo_mensaje_formulario = "danger";
                    }
                }
            }
        }
    } else {
        $mensaje_formulario = "Por favor, complete todos los campos requeridos marcados con *";
        $tipo_mensaje_formulario = "warning";
    }
}

// 2. DEFINE VARIABLES PARA LA VISTA Y MANEJA MENSAJES DE SESIÓN
$page_title = "Agregar Nuevo Cliente - Gestión Liconsa"; 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; // Ajusta si es diferente
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 

if (isset($_SESSION['mensaje_accion']) && empty($mensaje_formulario)) {
    $mensaje_formulario = $_SESSION['mensaje_accion']; 
    $tipo_mensaje_formulario = isset($_SESSION['tipo_mensaje_accion']) ? $_SESSION['tipo_mensaje_accion'] : 'info';
    unset($_SESSION['mensaje_accion']);
    unset($_SESSION['tipo_mensaje_accion']);
}

$alert_styles = [
    'info'    => ['bg' => 'bg-blue-50', 'border' => 'border-blue-400', 'text' => 'text-blue-700', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-400', 'text' => 'text-green-700', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-400', 'text' => 'text-yellow-700', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-50', 'border' => 'border-red-400', 'text' => 'text-red-700', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = $alert_styles[$tipo_mensaje_formulario] ?? $alert_styles['info'];

// 3. AHORA SÍ, SE INCLUYE EL HEADER Y SE EMPIEZA A MOSTRAR LA PÁGINA
include_once __DIR__ . "/../layout/header.php"; 
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-3xl mx-auto"> 
        
        <nav class="mb-6 text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex flex-wrap space-x-1 sm:space-x-2">
                <li class="flex items-center">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Inicio</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <a href="listar_clientes.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Clientes Activos</a>
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
                    <i class="fas fa-user-plus fa-3x text-liconsa-blue mb-4"></i>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Registrar Nuevo Cliente</h2>
                    <p class="text-gray-500 mt-2 text-sm sm:text-base">Complete los datos para añadir un nuevo beneficiario.</p>
                </div>

                <?php if ($mensaje_formulario): ?>
                    <div class="<?php echo $current_alert_style['bg']; ?> border-l-4 <?php echo $current_alert_style['border']; ?> <?php echo $current_alert_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="<?php echo $current_alert_style['icon']; ?> fa-lg <?php echo $current_alert_style['icon_text']; ?> mr-3 mt-1"></i>
                            </div>
                            <div class="ml-3 flex-grow">
                                <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_mensaje_formulario)); ?></p>
                                <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_formulario); ?></p>
                            </div>
                             <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_style['text']; ?>" onclick="this.closest('[role=alert]').style.display='none';" aria-label="Close">
                                <span class="sr-only">Cerrar</span>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="agregar_cliente.php" id="clienteForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 mb-6">
                        <div>
                            <label for="numero_tarjeta" class="block text-sm font-medium text-gray-700 mb-1">Nº Tarjeta <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-gray-400"></i>
                                </div>
                                <input type="text" class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm" 
                                       id="numero_tarjeta" name="numero_tarjeta" maxlength="5" pattern="^\d{1,5}$" required
                                       placeholder="Solo números, 1-5 dígitos" title="Entre 1 y 5 dígitos numéricos."
                                       value="<?php echo isset($_POST['numero_tarjeta']) ? htmlspecialchars($_POST['numero_tarjeta']) : ''; ?>">
                                <div class="validation-message text-xs mt-1" id="tarjeta-validation"></div>
                            </div>
                        </div>

                        <div>
                            <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm"
                                       id="nombre_completo" name="nombre_completo" minlength="3" maxlength="100" required
                                       placeholder="Nombre(s) Apellido Paterno Apellido Materno"
                                       value="<?php echo isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : ''; ?>">
                                <div class="validation-message text-xs mt-1" id="nombre-validation"></div>
                            </div>
                        </div>

                        <div>
                            <label for="dotacion_maxima" class="block text-sm font-medium text-gray-700 mb-1">Dotación Máxima <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-boxes text-gray-400"></i>
                                </div>
                                <input type="number" class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm"
                                       id="dotacion_maxima" name="dotacion_maxima" min="1" max="999"
                                       value="<?php echo isset($_POST['dotacion_maxima']) ? htmlspecialchars($_POST['dotacion_maxima']) : '12'; ?>" required>
                                <div class="validation-message text-xs mt-1" id="dotacion-validation"></div>
                            </div>
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono <small class="text-gray-500">(Opcional, ej: +522731234567)</small></label>
                            <div class="relative rounded-md shadow-sm">
                                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm"
                                       id="telefono" name="telefono" 
                                       placeholder="+52XXXXXXXXXX" 
                                       title="Incluir lada internacional. Ej: +52 para México."
                                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                                <div class="validation-message text-xs mt-1" id="telefono-validation"></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6 md:my-8 border-gray-200">

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center gap-3">
                        <div class="text-center">
                            <a href="subir_csv.php" class="inline-flex bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg items-center text-sm transition duration-150 ease-in-out">
                                <i class="fas fa-file-csv mr-2"></i>Carga Masiva CSV
                            </a>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row gap-3">
                            <a href="listar_clientes.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-6 rounded-lg shadow-sm hover:shadow-md flex items-center justify-center text-sm transition duration-150 ease-in-out">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" class="bg-liconsa-green hover:bg-green-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg flex items-center justify-center text-sm transition duration-150 ease-in-out transform hover:scale-105" id="submitBtn">
                                <i class="fas fa-save mr-2"></i>Guardar Cliente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clienteForm');
    const numeroTarjetaInput = document.getElementById('numero_tarjeta');
    const nombreInput = document.getElementById('nombre_completo');
    const dotacionInput = document.getElementById('dotacion_maxima');
    const telefonoInput = document.getElementById('telefono');
    const submitBtn = document.getElementById('submitBtn');

    // Validación en tiempo real
    let validations = {
        tarjeta: false,
        nombre: false,
        dotacion: false,
        telefono: true // Opcional, por defecto válido
    };

    function updateSubmitButton() {
        const allValid = Object.values(validations).every(v => v);
        submitBtn.disabled = !allValid;
        submitBtn.style.opacity = allValid ? '1' : '0.7';
        if (!allValid) {
            submitBtn.title = 'Complete todos los campos correctamente';
        } else {
            submitBtn.title = '';
        }
    }

    function showValidation(inputId, messageId, isValid, message) {
        const input = document.getElementById(inputId);
        const messageEl = document.getElementById(messageId);
        
        // Limpiar clases previas
        input.classList.remove('border-red-400', 'border-green-400');
        messageEl.classList.remove('text-red-500', 'text-green-500');
        
        if (message) {
            if (isValid) {
                input.classList.add('border-green-400');
                messageEl.classList.add('text-green-500');
                messageEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + message;
            } else {
                input.classList.add('border-red-400');
                messageEl.classList.add('text-red-500');
                messageEl.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>' + message;
            }
        } else {
            messageEl.innerHTML = '';
        }
    }

    // Limpiar solo números para tarjeta
    numeroTarjetaInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        
        const value = this.value;
        if (value === '') {
            validations.tarjeta = false;
            showValidation('numero_tarjeta', 'tarjeta-validation', false, 'Campo obligatorio');
        } else if (value.length < 1 || value.length > 5) {
            validations.tarjeta = false;
            showValidation('numero_tarjeta', 'tarjeta-validation', false, 'Entre 1 y 5 dígitos');
        } else {
            validations.tarjeta = true;
            showValidation('numero_tarjeta', 'tarjeta-validation', true, 'Válido');
        }
        updateSubmitButton();
    });

    // Validar nombre
    nombreInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value === '') {
            validations.nombre = false;
            showValidation('nombre_completo', 'nombre-validation', false, 'Campo obligatorio');
        } else if (value.length < 3) {
            validations.nombre = false;
            showValidation('nombre_completo', 'nombre-validation', false, 'Mínimo 3 caracteres');
        } else if (value.length > 100) {
            validations.nombre = false;
            showValidation('nombre_completo', 'nombre-validation', false, 'Máximo 100 caracteres');
        } else {
            validations.nombre = true;
            showValidation('nombre_completo', 'nombre-validation', true, 'Válido');
        }
        updateSubmitButton();
    });

    // Validar dotación
    dotacionInput.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (isNaN(value) || value < 1) {
            validations.dotacion = false;
            showValidation('dotacion_maxima', 'dotacion-validation', false, 'Mínimo 1');
        } else if (value > 999) {
            validations.dotacion = false;
            showValidation('dotacion_maxima', 'dotacion-validation', false, 'Máximo 999');
        } else {
            validations.dotacion = true;
            showValidation('dotacion_maxima', 'dotacion-validation', true, 'Válido');
        }
        updateSubmitButton();
    });

    // Validar teléfono
    telefonoInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value === '') {
            validations.telefono = true;
            showValidation('telefono', 'telefono-validation', true, '');
        } else if (!/^\+\d{10,15}$/.test(value)) {
            validations.telefono = false;
            showValidation('telefono', 'telefono-validation', false, 'Formato: +52XXXXXXXXXX');
        } else {
            validations.telefono = true;
            showValidation('telefono', 'telefono-validation', true, 'Válido');
        }
        updateSubmitButton();
    });

    // Limpiar números para teléfono manteniendo el +
    telefonoInput.addEventListener('keydown', function(e) {
        if (this.value === '' && e.key !== '+' && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
            this.value = '+';
        }
    });

    telefonoInput.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/[\d+]/.test(char)) {
            e.preventDefault();
        }
    });

    // Validación inicial al cargar
    if (numeroTarjetaInput.value) numeroTarjetaInput.dispatchEvent(new Event('input'));
    if (nombreInput.value) nombreInput.dispatchEvent(new Event('input'));
    if (dotacionInput.value) dotacionInput.dispatchEvent(new Event('input'));
    if (telefonoInput.value) telefonoInput.dispatchEvent(new Event('input'));

    // Prevenir envío si hay errores
    form.addEventListener('submit', function(e) {
        const allValid = Object.values(validations).every(v => v);
        if (!allValid) {
            e.preventDefault();
            alert('Por favor, complete todos los campos correctamente antes de continuar.');
            return false;
        }
        
        // Mostrar indicador de carga
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        submitBtn.disabled = true;
    });

    updateSubmitButton();
});
</script>

<?php 
include_once __DIR__ . "/../layout/footer.php"; 
?>
