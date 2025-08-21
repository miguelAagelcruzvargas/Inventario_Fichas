<?php
// inventarios/cerrar_inventario.php
include_once __DIR__ . "/Inventario.php";

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: historial_inventarios.php");
    exit;
}

$inventario = new Inventario();
$inventario->id = $_GET['id'];

// Verificar si existe el inventario
if (!$inventario->leerUno()) {
    header("Location: historial_inventarios.php?mensaje=Inventario no encontrado.&tipo=danger");
    exit;
}

// Verificar si el inventario ya está cerrado
if ($inventario->estado == 'cerrado') {
    header("Location: historial_inventarios.php?mensaje=Este inventario ya está cerrado.&tipo=warning");
    exit;
}

// Intentar cerrar el inventario
if ($inventario->cerrar()) {
    $mensaje = "Inventario cerrado correctamente.";
    $tipo_mensaje = "success";
} else {
    $mensaje = "No se pudo cerrar el inventario.";
    $tipo_mensaje = "danger";
}

// Redirigir con mensaje
header("Location: historial_inventarios.php?mensaje=" . urlencode($mensaje) . "&tipo=" . urlencode($tipo_mensaje));
exit;