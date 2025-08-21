<?php
// clientes/enviar_mensaje.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Definición de Variables Esenciales para Header/Menú ---
$page_title = "Enviar Mensaje a Beneficiarios"; 

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web'; // Ajusta si es diferente
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 
// --- Fin Definición de Variables ---

include_once __DIR__ . "/../layout/header.php"; // Asegúrate que este sea el header con Tailwind
// Se elimina: include_once __DIR__ . "/../layout/menu.php";
include_once __DIR__ . "/cliente.php";

// Inicialización de variables
$mensaje_feedback = ''; // Renombrado para evitar conflicto con $mensaje_notificacion del header
$tipo_feedback = '';
$seleccionados_post = []; // Para guardar los IDs de los clientes seleccionados en el POST
$texto_mensaje_post = '';

// Obtener todos los clientes activos con teléfono registrado
$cliente_handler = new Cliente(); // Renombrado para claridad
$clientes_con_telefono = $cliente_handler->obtenerClientesConTelefono();

// Procesar el formulario para envío de mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['seleccionados']) && !empty($_POST['seleccionados']) && !empty($_POST['texto_mensaje'])) {
        $seleccionados_post = $_POST['seleccionados'];
        $texto_mensaje_post = trim($_POST['texto_mensaje']);
        
        $mensaje_feedback = "Mensaje preparado para " . count($seleccionados_post) . " beneficiarios. Revise los enlaces generados abajo.";
        $tipo_feedback = "success";
    } else {
        $mensaje_feedback = "Por favor, seleccione al menos un beneficiario y escriba un mensaje.";
        $tipo_feedback = "warning";
        
        $seleccionados_post = isset($_POST['seleccionados']) ? $_POST['seleccionados'] : [];
        $texto_mensaje_post = isset($_POST['texto_mensaje']) ? trim($_POST['texto_mensaje']) : '';
    }
}

// Estilos para las alertas de feedback (Tailwind CSS)
$alert_tailwind_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_feedback_style = null;
if (!empty($tipo_feedback) && isset($alert_tailwind_styles[$tipo_feedback])) {
    $current_alert_feedback_style = $alert_tailwind_styles[$tipo_feedback];
}
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
                     <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/listar_clientes.php" class="text-gray-500 hover:text-liconsa-blue transition duration-150 ease-in-out">Clientes</a>
                    <i class="fas fa-chevron-right text-gray-400 mx-1 sm:mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($page_title); ?></span>
                </li>
            </ol>
        </nav>

        <?php if (!empty($mensaje_feedback) && $current_alert_feedback_style): ?>
            <div class="<?php echo $current_alert_feedback_style['bg']; ?> border-l-4 <?php echo $current_alert_feedback_style['border']; ?> <?php echo $current_alert_feedback_style['text']; ?> p-4 mb-6 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="<?php echo $current_alert_feedback_style['icon']; ?> fa-lg <?php echo $current_alert_feedback_style['icon_text']; ?> mr-3 mt-1"></i>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="font-bold text-sm md:text-base"><?php echo ucfirst(htmlspecialchars($tipo_feedback)); ?></p>
                        <p class="text-xs md:text-sm"><?php echo htmlspecialchars($mensaje_feedback); ?></p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-200/80 inline-flex h-8 w-8 items-center justify-center <?php echo $current_alert_feedback_style['text']; ?>" onclick="this.closest('[role=alert]').remove();" aria-label="Close">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-xl rounded-xl border border-gray-200 p-6 sm:p-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-comment-dots text-liconsa-blue mr-3"></i>
                Enviar Mensaje a Beneficiarios
            </h1>
            <p class="text-sm text-gray-500 mb-6">Redacte un mensaje y seleccione los destinatarios.</p>
            
            <form method="post" id="formMensaje" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label for="texto_mensaje" class="block text-sm font-medium text-gray-700 mb-1">Mensaje:</label>
                            <textarea id="texto_mensaje" name="texto_mensaje" rows="5" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm" 
                                      placeholder="Escriba aquí el mensaje..." required><?php echo htmlspecialchars($texto_mensaje_post); ?></textarea>
                            <p class="mt-1 text-xs text-gray-500">Este mensaje se usará para generar los enlaces de SMS y WhatsApp.</p>
                        </div>
                        
                        <div class="space-y-2 sm:space-y-0 sm:flex sm:space-x-3">
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-liconsa-blue hover:bg-blue-700 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out">
                                <i class="fas fa-cogs mr-2"></i>Preparar Mensajes
                            </button>
                            <button type="button" id="seleccionarTodos" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium text-sm rounded-lg shadow-sm transition duration-150 ease-in-out">
                                <i class="fas fa-check-square mr-2"></i>Seleccionar Todos
                            </button>
                            <button type="button" id="deseleccionarTodos" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium text-sm rounded-lg shadow-sm transition duration-150 ease-in-out">
                                <i class="far fa-square mr-2"></i>Deseleccionar Todos
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seleccione los beneficiarios:</label>
                        <div class="border border-gray-300 rounded-lg shadow-sm h-64 sm:h-72 overflow-y-auto bg-gray-50 p-1">
                            <?php if ($clientes_con_telefono && count($clientes_con_telefono) > 0): ?>
                                <?php foreach ($clientes_con_telefono as $cliente_item): ?>
                                    <label class="flex items-center px-3 py-2.5 hover:bg-gray-100 cursor-pointer rounded-md transition-colors">
                                        <input class="form-check-input h-4 w-4 text-liconsa-blue border-gray-300 rounded focus:ring-liconsa-blue mr-3" type="checkbox" name="seleccionados[]" value="<?php echo $cliente_item['id']; ?>" 
                                               <?php echo in_array($cliente_item['id'], $seleccionados_post) ? 'checked' : ''; ?>>
                                        <span class="text-sm text-gray-800"><?php echo htmlspecialchars($cliente_item['nombre_completo']); ?></span>
                                        <span class="ml-auto text-xs text-gray-500"><?php echo htmlspecialchars($cliente_item['telefono']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-sm text-gray-500">
                                    No hay beneficiarios con teléfono registrado para mostrar.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($seleccionados_post) && !empty($texto_mensaje_post) && $tipo_feedback === 'success'): ?>
                <hr class="my-6 border-gray-200">
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Enlaces de Mensajes Preparados:</h3>
                    <div class="bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 rounded-md mb-4 text-sm" role="alert">
                        <p><strong class="font-medium">Instrucciones:</strong> Haga clic en los enlaces de abajo para enviar el mensaje a cada beneficiario (abrirá la aplicación correspondiente) o utilice el botón de "Mensaje Grupal SMS" para intentar enviar a todos los seleccionados a la vez (puede no funcionar en todos los dispositivos/navegadores para múltiples números).</p>
                    </div>
                    
                    <div class="mb-4">
                        <?php 
                        $numeros_seleccionados = [];
                        foreach ($seleccionados_post as $id_sel) {
                            foreach ($clientes_con_telefono as $cli_data) {
                                if ($cli_data['id'] == $id_sel) {
                                    $numeros_seleccionados[] = preg_replace('/\D/', '', $cli_data['telefono']);
                                    break;
                                }
                            }
                        }
                        $enlace_sms_grupal = "sms:" . implode(',', array_filter($numeros_seleccionados)) . "?body=" . urlencode($texto_mensaje_post);
                        ?>
                        <a href="<?php echo htmlspecialchars($enlace_sms_grupal); ?>" 
                           class="inline-flex items-center px-6 py-3 bg-teal-500 hover:bg-teal-600 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out" target="_blank">
                            <i class="fas fa-comments mr-2"></i> Enviar Mensaje Grupal SMS (<?php echo count($seleccionados_post); ?>)
                        </a>
                         <p class="mt-1 text-xs text-gray-500">Nota: El envío grupal por SMS depende de la configuración de su dispositivo.</p>
                    </div>
                    
                    <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Teléfono</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($seleccionados_post as $id_sel): ?>
                                    <?php 
                                    $datos_cliente_sel = null;
                                    foreach ($clientes_con_telefono as $cli_data) {
                                        if ($cli_data['id'] == $id_sel) {
                                            $datos_cliente_sel = $cli_data;
                                            break;
                                        }
                                    }
                                    if (!$datos_cliente_sel) continue;
                                    $telefono_limpio_sel = preg_replace('/\D/', '', $datos_cliente_sel['telefono']);
                                    $numero_para_wa_sel = '52' . $telefono_limpio_sel; // Asumiendo prefijo 52 para México
                                    if (strlen($telefono_limpio_sel) === 10 && substr($telefono_limpio_sel, 0, 2) !== '52') {
                                        $numero_para_wa_sel = '52' . $telefono_limpio_sel;
                                    } elseif (str_starts_with($telefono_limpio_sel, '52') && strlen($telefono_limpio_sel) === 12) {
                                        $numero_para_wa_sel = $telefono_limpio_sel;
                                    }
                                    $link_sms_individual = "sms:" . $telefono_limpio_sel . "?body=" . urlencode($texto_mensaje_post);
                                    $link_whatsapp_individual = "https://wa.me/" . $numero_para_wa_sel . "?text=" . urlencode($texto_mensaje_post);
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-800"><?php echo htmlspecialchars($datos_cliente_sel['nombre_completo']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 hidden sm:table-cell"><?php echo htmlspecialchars($datos_cliente_sel['telefono']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <div class="inline-flex rounded-md shadow-sm space-x-2" role="group">
                                                <a href="<?php echo htmlspecialchars($link_sms_individual); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-sky-700 bg-sky-100 hover:bg-sky-200 rounded-md border border-sky-300">
                                                    <i class="fas fa-sms mr-1.5"></i> SMS
                                                </a>
                                                <a href="<?php echo htmlspecialchars($link_whatsapp_individual); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md border border-green-300">
                                                    <i class="fab fa-whatsapp mr-1.5"></i> WhatsApp
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 bg-gray-50 p-6 rounded-lg border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 mb-2 flex items-center"><i class="fas fa-info-circle text-liconsa-blue mr-2"></i>Acerca del Sistema de Mensajes</h3>
            <ul class="list-disc list-inside space-y-1 text-sm text-gray-600">
                <li><strong>Mensaje Individual:</strong> Abre la aplicación de SMS o WhatsApp de su dispositivo con el mensaje y destinatario pre-cargados.</li>
                <li><strong>Mensaje Grupal SMS:</strong> Intenta abrir la aplicación de SMS con múltiples destinatarios. Su efectividad puede variar según el dispositivo y la aplicación de mensajería.</li>
                <li><strong>Importante:</strong> Los mensajes se envían a través de su dispositivo/servicio telefónico. Pueden aplicarse cargos estándar de su proveedor de telefonía. Este sistema solo facilita la preparación de los mensajes.</li>
            </ul>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seleccionarTodosBtn = document.getElementById('seleccionarTodos');
    const deseleccionarTodosBtn = document.getElementById('deseleccionarTodos');
    const checkboxesClientes = document.querySelectorAll('.form-check-input'); // Usar una clase más genérica si es necesario

    if (seleccionarTodosBtn) {
        seleccionarTodosBtn.addEventListener('click', function() {
            checkboxesClientes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
        });
    }
    
    if (deseleccionarTodosBtn) {
        deseleccionarTodosBtn.addEventListener('click', function() {
            checkboxesClientes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
        });
    }
});
</script>

<?php 
include_once __DIR__ . "/../layout/footer.php"; 
?>
