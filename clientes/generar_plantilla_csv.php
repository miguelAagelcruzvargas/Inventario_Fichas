<?php
// clientes/generar_plantilla_csv.php
// Genera plantilla CSV con formato correcto

// Aplicar medidas de seguridad
require_once '../config/security_middleware.php';
SecurityMiddleware::applySecurityHeaders();
SecurityMiddleware::configureSecureSession();

// Verificar sesión de administrador
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Configurar headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_clientes_' . date('Y-m-d') . '.csv"');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Crear contenido del CSV
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel lo abra correctamente)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
fputcsv($output, ['Folio', 'Nombre Completo', 'Numero Tarjeta', 'Sobres Maximos']);

// Datos de ejemplo
$ejemplos = [
    [1, 'Juan Pérez López', '12345678', 10],
    [2, 'María González Martínez', '87654321', 15],
    [3, 'Pedro Martínez García', '11223344', 8],
    [4, 'Ana López Rodríguez', '55667788', 12],
    [5, 'Luis García Hernández', '99887766', 6],
    [6, 'Carmen Ruiz Díaz', '44332211', 20],
    [7, 'Roberto Sánchez Valle', '77889900', 18],
    [8, 'Isabel Morales Castro', '13579246', 14],
    [9, 'Francisco Jiménez Ramos', '24681357', 9],
    [10, 'Elena Vargas Ortiz', '97531864', 16]
];

foreach ($ejemplos as $ejemplo) {
    fputcsv($output, $ejemplo);
}

fclose($output);
exit();
?>
