<?php
// config/Configuracion.php

// No es necesario incluir conexion.php aquí directamente si el constructor lo maneja.
// Sin embargo, si la clase Conexion no se carga automáticamente (autoloading),
// el constructor deberá asegurarse de que se incluya.

class Configuracion {
    private $conn;
    private $table_name = "configuracion"; // Nombre de tu tabla de configuración

    public function __construct() {
        // Asegurar que la clase Conexion esté disponible.
        if (!class_exists('Conexion')) {
            // Determinar la ruta al archivo conexion.php.
            // Esta es una forma común, asumiendo que conexion.php está en el directorio config
            // y que este archivo (Configuracion.php) también está en config.
            // Si tu estructura es diferente, ajusta esta ruta.
            $ruta_conexion_directa = __DIR__ . '/conexion.php'; // Si están en el mismo directorio
            $ruta_conexion_proyecto_raiz = $_SERVER['DOCUMENT_ROOT'] . '/gestion_leche_web/config/conexion.php'; // Ejemplo si conoces la ruta desde la raíz del proyecto

            if (file_exists($ruta_conexion_directa)) {
                require_once $ruta_conexion_directa;
            } elseif (file_exists($ruta_conexion_proyecto_raiz)) {
                // Esta ruta es un ejemplo si Configuracion.php estuviera en otro lugar
                // y necesitaras una ruta más absoluta relativa a la raíz del documento web.
                // Para tu caso, si ambos están en /config/, la primera opción debería bastar.
                require_once $ruta_conexion_proyecto_raiz;
            } else {
                $error_msg = "FATAL: No se pudo encontrar el archivo de la clase Conexion. Verifique la ruta. Intentadas: " . $ruta_conexion_directa . " y " . $ruta_conexion_proyecto_raiz;
                error_log($error_msg);
                // Es un error crítico, la aplicación no puede funcionar sin conexión.
                die("Error crítico del sistema: Falta el archivo de conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }
        
        $database = new Conexion();
        $this->conn = $database->conectar();

        if (!$this->conn) {
            error_log("FATAL: Fallo al obtener la conexión a la BD en la clase Configuracion.");
            die("Error crítico del sistema: No se pudo conectar a la base de datos para la configuración. Por favor, contacte al administrador.");
        }
    }

    /**
     * Obtiene el valor de una clave de configuración.
     * @param string $clave La clave de configuración.
     * @return string|null El valor de la configuración o null si no se encuentra o en caso de error.
     */
    public function obtenerValor($clave) {
        $query = "SELECT valor FROM " . $this->table_name . " WHERE clave = :clave LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':clave', $clave, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['valor'] : null;
        } catch (PDOException $e) {
            error_log("Error PDO al obtener configuración ('{$clave}'): " . $e->getMessage());
            // Considera si quieres que el usuario vea un error o simplemente devolver null
            // y que la lógica de la aplicación maneje un valor de configuración faltante.
            return null; 
        }
    }

    /**
     * Actualiza o inserta un valor de configuración.
     * Se asume que la columna 'clave' tiene una restricción UNIQUE en la base de datos.
     * @param string $clave La clave de configuración.
     * @param string $valor El nuevo valor para la clave.
     * @return bool True si tuvo éxito, false si falló.
     */
    public function actualizarValor($clave, $valor) {
        // Esta consulta utiliza la sintaxis ON DUPLICATE KEY UPDATE de MySQL/MariaDB.
        // Si la 'clave' ya existe, actualiza 'valor'. Si no existe, inserta una nueva fila.
        $query = "INSERT INTO " . $this->table_name . " (clave, valor) 
                  VALUES (:clave, :valor) 
                  ON DUPLICATE KEY UPDATE valor = :valor_update";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':clave', $clave, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
            $stmt->bindParam(':valor_update', $valor, PDO::PARAM_STR); // El mismo valor para la parte de UPDATE

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar configuración ('{$clave}'): " . $e->getMessage());
            return false;
        }
    }
}
?>
