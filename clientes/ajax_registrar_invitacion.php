<?php
// clientes/ajax_registrar_invitacion.php
header('Content-Type: application/json');
require_once __DIR__ . "/cliente.php"; // O la ruta correcta a tu clase Cliente

$response = ['success' => false, 'message' => 'Solicitud incorrecta.'];

if (isset($_POST['cliente_id'])) {
    $cliente_id = filter_var($_POST['cliente_id'], FILTER_VALIDATE_INT);

    if ($cliente_id) {
        $cliente = new Cliente();
        if ($cliente->marcarInvitacionEnviada($cliente_id)) { // Usar el nuevo método
            $response['success'] = true;
            $response['message'] = 'Invitación marcada como enviada.';
        } else {
            $response['message'] = 'Error al actualizar la base de datos.';
            error_log("ajax_registrar_invitacion: Falla al llamar a marcarInvitacionEnviada para ID " . $cliente_id);
        }
    } else {
        $response['message'] = 'ID de cliente inválido.';
    }
} else {
    $response['message'] = 'No se recibió el ID del cliente.';
}

echo json_encode($response);
?>
