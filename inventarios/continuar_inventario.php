<?php
require_once '../config/conexion.php';
session_start();

// Validar inventario_id
if (!isset($_GET['inventario_id']) || !is_numeric($_GET['inventario_id'])) {
    die('Inventario no válido.');
}

$inventario_id = (int) $_GET['inventario_id'];

// Verificar que el inventario exista
$stmt = $pdo->prepare("SELECT id, estado FROM inventarios WHERE id = ?");
$stmt->execute([$inventario_id]);
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inventario) {
    die('Inventario no encontrado.');
}

// (Opcional) Verificar si el inventario está cerrado
if ($inventario['estado'] === 'cerrado') {
    die('Este inventario ya está cerrado. No se puede continuar.');
}

// Activar el inventario en la sesión
$_SESSION['inventario_id'] = $inventario_id;

// Redireccionar
$redirect = $_GET['redirect'] ?? 'listar'; // Valor por defecto
if ($redirect === 'resumen') {
    header('Location: ../resumen/dashboard.php');
} else {
    header('Location: ../clientes/listar_clientes.php');
}
exit;
?>
