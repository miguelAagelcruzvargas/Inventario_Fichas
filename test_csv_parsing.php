<?php
// Test del parsing de CSV con comillas
$archivo = "c:\\xampp\\htdocs\\gestion_leche_web\\lista_nuevos.csv";

echo "Probando parsing de CSV con líneas entre comillas...\n";

$handle = fopen($archivo, 'r');
if ($handle === false) {
    echo "No se pudo abrir el archivo.\n";
    exit;
}

$fila = 0;
while (($linea = fgets($handle)) !== false) {
    $fila++;
    $linea_original = $linea;
    
    // Limpiar la línea
    $linea = trim($linea);
    
    echo "\n--- FILA {$fila} ---\n";
    echo "Original: " . bin2hex(substr($linea_original, 0, 50)) . "\n";
    echo "Limpia: {$linea}\n";
    
    // Manejar líneas completamente entre comillas
    if (strlen($linea) > 1 && $linea[0] === '"' && $linea[-1] === '"') {
        $linea = substr($linea, 1, -1);
        echo "Removidas comillas externas: {$linea}\n";
    }
    
    // Parsear con str_getcsv
    $datos = str_getcsv($linea, ',', '"');
    
    echo "Datos parseados: " . print_r($datos, true);
    echo "Número de columnas: " . count($datos) . "\n";
    
    if ($fila >= 3) break; // Solo las primeras 3 líneas
}

fclose($handle);
?>
