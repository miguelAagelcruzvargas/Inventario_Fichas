<?php
// clientes/autocomplete_sugerencias.php
header('Content-Type: application/json; charset=UTF-8');

// Inicializar la respuesta por defecto
$response = [];

try {
    // Asegúrate que la ruta a Cliente.php es correcta desde este archivo.
    require_once __DIR__ . "/cliente.php"; 

    if (isset($_GET['term']) && !empty(trim($_GET['term']))) {
        $cliente = new Cliente();
        
        // Sanitizar el término de búsqueda proveniente de GET
        $term = trim(strip_tags($_GET['term']));
        
        // Determinar si se deben incluir clientes inactivos basado en un parámetro GET
        $solo_activos = true; 
        if (isset($_GET['solo_activos']) && $_GET['solo_activos'] === '0') {
            $solo_activos = false; // Incluir todos los clientes
        }

        // Realizar la búsqueda solo si el término tiene una longitud mínima
        if (mb_strlen($term) >= 1) { 
            try {
                // El método obtenerSugerencias devuelve un array directamente
                $sugerencias_final = $cliente->obtenerSugerencias($term, 8, $solo_activos); 

                if (is_array($sugerencias_final)) {
                    $response = $sugerencias_final;
                } else {
                    error_log("autocomplete_sugerencias.php: obtenerSugerencias no devolvió un array para el término: " . htmlspecialchars($term));
                    $response = ['error' => 'Error interno: formato de respuesta incorrecto'];
                }
            } catch (Exception $e) {
                error_log("Error en autocomplete_sugerencias.php obtenerSugerencias: " . $e->getMessage());
                $response = ['error' => 'Error al buscar sugerencias'];
            }
        } else {
            $response = []; // Término demasiado corto
        }
    } else {
        $response = []; // No hay término de búsqueda
    }

} catch (Exception $e) {
    error_log("Error fatal en autocomplete_sugerencias.php: " . $e->getMessage());
    $response = ['error' => 'Error al cargar el sistema de búsqueda'];
}

// Asegurar que siempre devolvemos JSON válido
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
