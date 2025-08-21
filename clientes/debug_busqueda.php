<?php
// debug_busqueda.php - Script de diagnóstico para la búsqueda

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico del Sistema de Búsqueda</h1>";

// 1. Verificar conexión a la base de datos
echo "<h2>1. Verificación de Conexión a BD</h2>";
try {
    require_once __DIR__ . '/../config/conexion.php';
    $database = new Conexion();
    $conn = $database->conectar();
    if ($conn) {
        echo "✅ Conexión a BD exitosa<br>";
    } else {
        echo "❌ Error en conexión a BD<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al conectar: " . $e->getMessage() . "<br>";
}

// 2. Verificar clase Cliente
echo "<h2>2. Verificación de Clase Cliente</h2>";
try {
    require_once __DIR__ . '/cliente.php';
    $cliente = new Cliente();
    echo "✅ Clase Cliente cargada correctamente<br>";
    
    // 3. Probar método obtenerSugerencias con diferentes términos
    echo "<h2>3. Prueba del Método obtenerSugerencias</h2>";
    
    $terminos_prueba = ['Laura', 'Griselda', 'Teresa', '29', '36', 'rosa'];
    
    foreach ($terminos_prueba as $termino_prueba) {
        echo "<h3>Probando con término: '$termino_prueba'</h3>";
        
        $sugerencias = $cliente->obtenerSugerencias($termino_prueba, 3, false);
        
        if (is_array($sugerencias)) {
            echo "✅ Método devuelve array<br>";
            echo "Número de sugerencias encontradas: " . count($sugerencias) . "<br>";
            
            if (count($sugerencias) > 0) {
                echo "<strong>Sugerencias encontradas:</strong><br>";
                foreach ($sugerencias as $i => $sugerencia) {
                    echo ($i + 1) . ". " . htmlspecialchars($sugerencia['nombre_completo']) . 
                         " (Tarjeta: " . htmlspecialchars($sugerencia['numero_tarjeta']) . 
                         ", Tel: " . htmlspecialchars($sugerencia['telefono'] ?? 'N/A') . 
                         ", Estado: " . htmlspecialchars($sugerencia['estado']) . ")<br>";
                }
            } else {
                echo "⚠️ No se encontraron sugerencias para '$termino_prueba'<br>";
            }
        } else {
            echo "❌ Método no devuelve array<br>";
            var_dump($sugerencias);
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error con Cliente: " . $e->getMessage() . "<br>";
}

// 4. Verificar si hay datos en la tabla
echo "<h2>4. Verificación de Datos en Tabla</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de clientes en BD: " . $result['total'] . "<br>";
    
    if ($result['total'] > 0) {
        $stmt = $conn->query("SELECT nombre_completo, numero_tarjeta, estado FROM clientes LIMIT 5");
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Primeros 5 clientes:</h3>";
        foreach ($clientes as $cliente_item) {
            echo "- " . htmlspecialchars($cliente_item['nombre_completo']) . 
                 " (Tarjeta: " . htmlspecialchars($cliente_item['numero_tarjeta']) . 
                 ", Estado: " . htmlspecialchars($cliente_item['estado']) . ")<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error al consultar datos: " . $e->getMessage() . "<br>";
}

// 5. Probar autocompletado directamente
echo "<h2>5. Prueba de Autocompletado HTTP</h2>";

if (isset($_GET['test_term']) && !empty($_GET['test_term'])) {
    $term = trim($_GET['test_term']);
    echo "<h3>Probando autocompletado con: '$term'</h3>";
    
    // Probar primero directamente el PHP
    echo "<h4>Prueba directa PHP:</h4>";
    try {
        require_once __DIR__ . '/autocomplete_sugerencias.php';
    } catch (Exception $e) {
        echo "❌ Error al incluir autocomplete_sugerencias.php: " . $e->getMessage() . "<br>";
    }
}

echo "<form method='GET'>";
echo "<h3>Probar término personalizado:</h3>";
echo "<input type='text' name='test_term' placeholder='Ingrese término de prueba' value='" . htmlspecialchars($_GET['test_term'] ?? '') . "'>";
echo "<button type='submit'>Probar</button>";
echo "</form>";

echo "<hr>";
echo "<p><strong>Nota:</strong> Los errores anteriores deberían estar solucionados ahora.</p>";
?>
