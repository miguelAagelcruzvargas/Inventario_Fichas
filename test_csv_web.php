<!DOCTYPE html>
<html>
<head>
    <title>Test CSV Parsing</title>
</head>
<body>
    <h1>Test de Parsing CSV</h1>
    <?php
    // Test del parsing de CSV con comillas
    $archivo = __DIR__ . "/lista_nuevos.csv";
    
    echo "<p>Probando parsing de CSV con líneas entre comillas...</p>";
    
    if (!file_exists($archivo)) {
        echo "<p style='color: red;'>Archivo no encontrado: {$archivo}</p>";
        exit;
    }
    
    $handle = fopen($archivo, 'r');
    if ($handle === false) {
        echo "<p style='color: red;'>No se pudo abrir el archivo.</p>";
        exit;
    }
    
    $fila = 0;
    while (($linea = fgets($handle)) !== false && $fila < 5) {
        $fila++;
        $linea_original = $linea;
        
        // Limpiar la línea
        $linea = trim($linea);
        
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h3>FILA {$fila}</h3>";
        echo "<p><strong>Original:</strong> " . htmlspecialchars($linea_original) . "</p>";
        echo "<p><strong>Hex:</strong> " . bin2hex(substr($linea_original, 0, 50)) . "</p>";
        echo "<p><strong>Limpia:</strong> " . htmlspecialchars($linea) . "</p>";
        
        // Manejar líneas completamente entre comillas
        if (strlen($linea) > 1 && $linea[0] === '"' && $linea[-1] === '"') {
            $linea = substr($linea, 1, -1);
            echo "<p><strong>Removidas comillas externas:</strong> " . htmlspecialchars($linea) . "</p>";
        }
        
        // Parsear con str_getcsv
        $datos = str_getcsv($linea, ',', '"');
        
        echo "<p><strong>Datos parseados:</strong></p>";
        echo "<pre>" . print_r($datos, true) . "</pre>";
        echo "<p><strong>Número de columnas:</strong> " . count($datos) . "</p>";
        echo "</div>";
    }
    
    fclose($handle);
    ?>
</body>
</html>
