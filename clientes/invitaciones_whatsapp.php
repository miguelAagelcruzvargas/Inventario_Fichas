<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Invitaciones al Grupo de WhatsApp"; 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = '/gestion_leche_web';
$baseUrl = $protocol . $host . $project_folder;
$current_page = basename($_SERVER['PHP_SELF']); 

require_once __DIR__ . '/../config/Configuracion.php'; 
require_once __DIR__ . '/../layout/header.php'; 
// Se elimina: require_once __DIR__ . '/../layout/menu.php'; 
require_once __DIR__ . "/cliente.php"; 

$config_handler = new Configuracion();
$clave_enlace_whatsapp = 'enlace_grupo_whatsapp'; 
$enlace_grupo_leche_actual = $config_handler->obtenerValor($clave_enlace_whatsapp);

if ($enlace_grupo_leche_actual === null || $enlace_grupo_leche_actual === '') {
    $enlace_grupo_leche_actual = ''; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_enlace_grupo'])) {
    if (isset($_POST['nuevo_enlace_grupo'])) {
        $nuevo_enlace = trim(filter_input(INPUT_POST, 'nuevo_enlace_grupo', FILTER_SANITIZE_URL));
        if (!empty($nuevo_enlace) && filter_var($nuevo_enlace, FILTER_VALIDATE_URL) && strpos($nuevo_enlace, 'chat.whatsapp.com/') !== false) {
            if ($config_handler->actualizarValor($clave_enlace_whatsapp, $nuevo_enlace)) {
                $_SESSION['mensaje_accion_invitaciones'] = '¡Enlace del grupo de WhatsApp actualizado correctamente!';
                $_SESSION['tipo_mensaje_accion_invitaciones'] = 'success';
            } else {
                $_SESSION['mensaje_accion_invitaciones'] = 'Error: No se pudo guardar el nuevo enlace.';
                $_SESSION['tipo_mensaje_accion_invitaciones'] = 'danger';
            }
        } else {
            $_SESSION['mensaje_accion_invitaciones'] = 'Error: El enlace proporcionado no parece ser un grupo de WhatsApp válido o está vacío.';
            $_SESSION['tipo_mensaje_accion_invitaciones'] = 'danger';
        }
    }
    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
    exit;
}

$mensaje_notificacion = '';
$tipo_notificacion = '';
if (isset($_SESSION['mensaje_accion_invitaciones'])) {
    $mensaje_notificacion = $_SESSION['mensaje_accion_invitaciones'];
    $tipo_notificacion = $_SESSION['tipo_mensaje_accion_invitaciones'];
    unset($_SESSION['mensaje_accion_invitaciones']);
    unset($_SESSION['tipo_mensaje_accion_invitaciones']);
} elseif (isset($_GET['mensaje_lista'])) {
    $mensaje_notificacion = htmlspecialchars(urldecode($_GET['mensaje_lista']));
    $tipo_notificacion = isset($_GET['tipo_lista']) ? htmlspecialchars($_GET['tipo_lista']) : 'info';
}

$alert_tailwind_styles = [
    'info'    => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'icon_text' => 'text-blue-500', 'icon' => 'fas fa-info-circle'],
    'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_text' => 'text-green-500', 'icon' => 'fas fa-check-circle'],
    'warning' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'icon_text' => 'text-yellow-500', 'icon' => 'fas fa-exclamation-triangle'],
    'danger'  => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_text' => 'text-red-500', 'icon' => 'fas fa-times-circle'],
];
$current_alert_style = null;
if (!empty($tipo_notificacion) && isset($alert_tailwind_styles[$tipo_notificacion])) {
    $current_alert_style = $alert_tailwind_styles[$tipo_notificacion];
}

$cliente_handler = new Cliente(); 
$resultado_clientes = $cliente_handler->getClientesParaInvitacion(); 
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="max-w-4xl mx-auto">

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

        <div class="bg-white shadow-xl rounded-xl border border-gray-200 p-6 sm:p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-1 flex items-center">
                <i class="fab fa-whatsapp text-green-500 mr-2 text-2xl"></i>
                Configurar Enlace del Grupo de WhatsApp
            </h2>
            <p class="text-sm text-gray-500 mb-4">Este enlace se usará para generar los mensajes de invitación.</p>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="mb-4">
                    <label for="nuevo_enlace_grupo" class="block text-sm font-medium text-gray-700 mb-1">Enlace actual del grupo:</label>
                    <input type="url" id="nuevo_enlace_grupo" name="nuevo_enlace_grupo" 
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-liconsa-blue focus:border-liconsa-blue text-sm"
                           value="<?php echo htmlspecialchars($enlace_grupo_leche_actual); ?>" 
                           placeholder="https://chat.whatsapp.com/XYZ..." required>
                    <p class="mt-1 text-xs text-gray-500">Pega aquí el enlace completo de invitación a tu grupo de WhatsApp.</p>
                </div>
                <button type="submit" name="actualizar_enlace_grupo" class="inline-flex items-center px-5 py-2.5 bg-liconsa-blue hover:bg-blue-700 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out">
                    <i class="fas fa-save mr-2"></i>Guardar Enlace
                </button>
            </form>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700 flex items-center">
                <i class="fas fa-paper-plane text-sky-500 mr-2"></i>
                Enviar Invitaciones al Grupo
            </h2>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/listar_clientes.php" class="mt-2 sm:mt-0 text-sm text-gray-600 hover:text-liconsa-blue underline">
                Volver al Listado de Clientes
            </a>
        </div>
        <p class="text-sm text-gray-500 mb-6">Lista de clientes activos con número de teléfono. Se usará el enlace configurado arriba.</p>

        <?php if ($resultado_clientes && $resultado_clientes->rowCount() > 0): ?>
            <div class="bg-white shadow-xl rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Completo</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Nº Tarjeta</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Invitación</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $resultado_clientes->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr id="cliente-row-<?php echo $row['id']; ?>" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600 hidden sm:table-cell"><?php echo htmlspecialchars($row['numero_tarjeta']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($row['telefono']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center estado-invitacion">
                                        <?php if (!empty($row['fecha_invitacion_grupo'])): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-sky-100 text-sky-700">
                                                <i class="fas fa-check-circle mr-1.5"></i> Invitado el <?php echo date("d/m/y H:i", strtotime($row['fecha_invitacion_grupo'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1.5"></i> Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <button class="btn-enviar-invitacion inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                data-clienteid="<?php echo $row['id']; ?>"
                                                data-nombrecliente="<?php echo htmlspecialchars($row['nombre_completo']); ?>"
                                                data-enlacegrupo="<?php echo htmlspecialchars($enlace_grupo_leche_actual); ?>">
                                            <i class="fab fa-whatsapp mr-1.5"></i> <?php echo !empty($row['fecha_invitacion_grupo']) ? 'Reenviar' : 'Invitar'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="<?php echo $alert_tailwind_styles['info']['bg']; ?> border-l-4 <?php echo $alert_tailwind_styles['info']['border']; ?> <?php echo $alert_tailwind_styles['info']['text']; ?> p-6 rounded-md shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                         <i class="<?php echo $alert_tailwind_styles['info']['icon']; ?> fa-2x <?php echo $alert_tailwind_styles['info']['icon_text']; ?> mr-3"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium <?php echo $alert_tailwind_styles['info']['text']; ?>">No hay clientes</h3>
                        <div class="mt-2 text-sm <?php echo $alert_tailwind_styles['info']['text']; ?>">
                            <p>No se encontraron clientes activos con número de teléfono para invitar.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const botonesInvitacion = document.querySelectorAll('.btn-enviar-invitacion');

    botonesInvitacion.forEach(button => {
        button.addEventListener('click', function() {
            const clienteId = this.dataset.clienteid;
            const nombreCliente = this.dataset.nombrecliente;
            const enlaceGrupo = this.dataset.enlacegrupo; 
            const botonActual = this; 

            const originalButtonHTML = botonActual.innerHTML;
            botonActual.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Enviando...';
            botonActual.disabled = true;

            const telefonoBoton = this.closest('tr').cells[2].textContent.trim(); 
            let numeroParaWaJs = telefonoBoton.replace(/\D/g, ''); 
            
            if (numeroParaWaJs.length === 10 && !numeroParaWaJs.startsWith('52')) { 
                numeroParaWaJs = '52' + numeroParaWaJs;
            } else if (numeroParaWaJs.startsWith('521') && numeroParaWaJs.length === 13) {
                numeroParaWaJs = '52' + numeroParaWaJs.substring(3);
            } else if (numeroParaWaJs.startsWith('52') && numeroParaWaJs.length === 12) {
                // Formato correcto
            } else if (numeroParaWaJs.startsWith('+')) {
                numeroParaWaJs = numeroParaWaJs.substring(1);
            } else if (numeroParaWaJs.length > 0 && numeroParaWaJs.length < 12 && !numeroParaWaJs.startsWith('52') && !numeroParaWaJs.startsWith('+')){
                numeroParaWaJs = '52' + numeroParaWaJs; 
            }

            if (!enlaceGrupo) {
                alert('Error: El enlace del grupo de WhatsApp no está configurado. Por favor, guárdelo primero.');
                botonActual.innerHTML = originalButtonHTML; 
                botonActual.disabled = false; 
                return;
            }

            const mensajePredefinidoJs = `¡Hola ${nombreCliente}! Te invito a unirte a nuestro grupo de WhatsApp para información sobre la dotación de leche: ${enlaceGrupo}`;
            const mensajeEncodedJs = encodeURIComponent(mensajePredefinidoJs);
            const linkWhatsAppDinamico = `https://wa.me/${numeroParaWaJs}?text=${mensajeEncodedJs}`;

            fetch('ajax_registrar_invitacion.php', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'cliente_id=' + encodeURIComponent(clienteId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const celdaEstado = document.querySelector(`#cliente-row-${clienteId} .estado-invitacion`);
                    if (celdaEstado) {
                        const fechaFormateada = new Date().toLocaleString('es-MX', { 
                            day: '2-digit', month: '2-digit', year: 'numeric', 
                            hour: '2-digit', minute: '2-digit' 
                        }).replace(',', '');
                        celdaEstado.innerHTML = `<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-sky-100 text-sky-700"><i class="fas fa-check-circle mr-1.5"></i> Invitado el ${fechaFormateada}</span>`;
                    }
                    botonActual.innerHTML = '<i class="fab fa-whatsapp mr-1.5"></i> Reenviar';
                    botonActual.disabled = false; 
                                        
                    window.open(linkWhatsAppDinamico, '_blank'); 
                    
                } else {
                    alert('Error al registrar la invitación: ' + (data.message || 'Error desconocido.'));
                    botonActual.innerHTML = originalButtonHTML; 
                    botonActual.disabled = false; 
                }
            })
            .catch(error => {
                console.error('Error en AJAX:', error);
                alert('Error de conexión al intentar registrar la invitación.');
                botonActual.innerHTML = originalButtonHTML; 
                botonActual.disabled = false; 
            });
        });
    });
});
</script>

<?php 
include_once __DIR__ . "/../layout/footer.php"; 
?>
