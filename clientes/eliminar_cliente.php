<?php
// clientes/eliminar_cliente.php
// Iniciar sesión para mensajes flash, si no está ya iniciada.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/Cliente.php"; // Asegúrate que la clase Cliente esté disponible
// Asumimos que Cliente.php incluye la conexión a la BD y la clase Configuracion si fuera necesaria aquí (aunque no lo es para esta acción).

$mensaje = "Acción no especificada o ID de cliente faltante.";
$tipo_mensaje = "danger";
$pagina_redireccion = "listar_clientes.php"; // Redirección por defecto

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_accion'] = $mensaje;
    $_SESSION['tipo_mensaje_accion'] = $tipo_mensaje;
    header("Location: " . $pagina_redireccion);
    exit;
}

$cliente = new Cliente();
$cliente->id = (int)$_GET['id']; // Convertir a entero

// Verificar si existe el cliente
if (!$cliente->leerUno()) { // leerUno() carga los datos del cliente en el objeto, incluyendo su estado actual
    $_SESSION['mensaje_accion'] = "Cliente no encontrado.";
    $_SESSION['tipo_mensaje_accion'] = "warning";
    header("Location: " . $pagina_redireccion);
    exit;
}

// Determinar si se va a activar o desactivar el cliente
$activar = isset($_GET['activar']) && $_GET['activar'] == 1;
$nuevo_estado = $activar ? 'activo' : 'inactivo';

// Intentar cambiar el estado del cliente
// Ahora se pasan ambos argumentos: el ID del cliente y el nuevo estado.
if ($cliente->cambiarEstado($cliente->id, $nuevo_estado)) {
    $mensaje = $activar ? "Cliente reactivado correctamente." : "Cliente dado de baja correctamente.";
    $tipo_mensaje = "success";
} else {
    $mensaje = "No se pudo cambiar el estado del cliente. Intente más tarde.";
    $tipo_mensaje = "danger";
}

// Guardar mensaje en sesión para mostrarlo después de la redirección
$_SESSION['mensaje_accion'] = $mensaje;
$_SESSION['tipo_mensaje_accion'] = $tipo_mensaje;

// Determinar la página de redirección basada en el origen
if (isset($_GET['origen_busqueda_activos']) || isset($_GET['origen_busqueda_inactivos'])) {
    // Si la acción vino de una búsqueda en listar_clientes.php, intentamos volver a esa búsqueda.
    // Para esto, necesitaríamos que listar_clientes.php pase el término de búsqueda original.
    // Por simplicidad, si viene de una búsqueda, redirigimos a listar_clientes.php (que mostrará activos por defecto).
    // Una mejora sería pasar el término de búsqueda como parámetro aquí y añadirlo a la URL de redirección.
    // Ejemplo: $termino_busqueda_original = isset($_GET['termino_busqueda_previo']) ? $_GET['termino_busqueda_previo'] : '';
    // $pagina_redireccion = "listar_clientes.php" . (!empty($termino_busqueda_original) ? "?busqueda=" . urlencode($termino_busqueda_original) : "");
    
    // Por ahora, redirigimos a la lista de activos si la baja/alta fue desde una búsqueda en la lista principal
    $pagina_redireccion = "listar_clientes.php"; 
    // Si se dio de baja, y el usuario quiere ver la lista de inactivos, deberá navegar allí.
    // Si se reactivó, aparecerá en la lista de activos.

} elseif (isset($_GET['origen']) && $_GET['origen'] === 'inactivos') {
    // Si la acción se originó desde la lista de inactivos (ej. al reactivar)
    if ($nuevo_estado === 'activo') { // Si se reactivó, tiene más sentido ir a la lista de activos
        $pagina_redireccion = "listar_clientes.php";
    } else { // Si por alguna razón se intentó desactivar desde aquí (no debería ser el flujo normal)
        $pagina_redireccion = "listar_clientes_inactivos.php";
    }
} else {
    // Origen por defecto o desde la lista de activos
    if ($nuevo_estado === 'inactivo') { // Si se dio de baja, tiene más sentido ir a la lista de inactivos para verlo allí
         // Opcional: podrías decidir quedarte en la lista de activos, y el cliente simplemente desaparecerá.
        $pagina_redireccion = "listar_clientes_inactivos.php";
    } else { // Si se reactivó (aunque el flujo normal sería desde la lista de inactivos)
        $pagina_redireccion = "listar_clientes.php";
    }
}

// Redirigir
header("Location: " . $pagina_redireccion);
exit;
?>
