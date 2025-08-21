<?php
require_once '../config/conexion.php';
session_start();

// Validar inventario
if (!isset($_GET['inventario_id']) || !is_numeric($_GET['inventario_id'])) {
    die('Inventario no válido.');
}

$inventario_id = (int) $_GET['inventario_id'];

// Verificar que el inventario exista
$stmt = $pdo->prepare("SELECT id FROM inventarios WHERE id = ?");
$stmt->execute([$inventario_id]);
if ($stmt->rowCount() === 0) {
    die('Inventario no encontrado.');
}

// Activar el inventario
$_SESSION['inventario_id'] = $inventario_id;

// Redireccionar según parámetro
if (isset($_GET['redirect']) && $_GET['redirect'] == 'resumen') {
    header('Location: ../resumen/dashboard.php');
} else {
    header('Location: ../clientes/listar_clientes.php');
}
exit;
?>
