<?php
// Script para corregir la contraseña del usuario admin
require_once 'config/Conexion.php';

echo "<h1>Reparando credenciales de administrador</h1>\n";

try {
    // Conectar a la base de datos
    $database = new Conexion();
    $db = $database->conectar();
    
    if (!$db) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    echo "<p>✅ Conexión a base de datos exitosa</p>\n";
    
    // Generar nuevo hash para la contraseña "admin123"
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "<p>🔐 Nuevo hash generado para contraseña 'admin123'</p>\n";
    echo "<p><code>$newHash</code></p>\n";
    
    // Actualizar el usuario admin
    $query = "UPDATE usuarios SET contrasena_hash = ? WHERE nombre_usuario = 'admin'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$newHash])) {
        echo "<p>✅ Contraseña del usuario 'admin' actualizada exitosamente</p>\n";
        
        // Verificar que el cambio funcionó
        $verifyQuery = "SELECT nombre_usuario, rol, role, activo FROM usuarios WHERE nombre_usuario = 'admin'";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->execute();
        $user = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>📋 Datos del usuario admin:</p>\n";
            echo "<ul>\n";
            echo "<li>Usuario: " . htmlspecialchars($user['nombre_usuario']) . "</li>\n";
            echo "<li>Rol: " . htmlspecialchars($user['rol']) . "</li>\n";
            echo "<li>Role: " . htmlspecialchars($user['role']) . "</li>\n";
            echo "<li>Activo: " . ($user['activo'] ? 'Sí' : 'No') . "</li>\n";
            echo "</ul>\n";
            
            // Probar el hash
            $testQuery = "SELECT contrasena_hash FROM usuarios WHERE nombre_usuario = 'admin'";
            $testStmt = $db->prepare($testQuery);
            $testStmt->execute();
            $hashData = $testStmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($newPassword, $hashData['contrasena_hash'])) {
                echo "<p>✅ Verificación de contraseña: CORRECTA</p>\n";
                echo "<h2 style='color: green;'>🎉 PROBLEMA SOLUCIONADO!</h2>\n";
                echo "<p><strong>Ahora puedes hacer login con:</strong></p>\n";
                echo "<ul>\n";
                echo "<li>Usuario: <strong>admin</strong></li>\n";
                echo "<li>Contraseña: <strong>admin123</strong></li>\n";
                echo "</ul>\n";
                echo "<p><a href='login/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Ir al Login</a></p>\n";
            } else {
                echo "<p>❌ Error: La verificación de contraseña falló</p>\n";
            }
        }
        
    } else {
        echo "<p>❌ Error al actualizar la contraseña</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><small>Este archivo se puede eliminar después de usar. Solo es para reparar el problema de login.</small></p>\n";
?>
