<?php
// config/conexion.php
class Conexion {
    private $host = 'localhost';
    private $db_name = 'gestion_leche';
    private $username = 'root';  
    private $password = '';      
    private $conn;

    public function conectar() {
        $this->conn = null;

        try {
            // Opciones de PDO para mayor seguridad
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Usar prepared statements reales
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES'",
                PDO::ATTR_PERSISTENT => false, // Desactivar conexiones persistentes por seguridad
            ];

            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
            
        } catch(PDOException $e) {
            // No mostrar información sensible en producción
            error_log("Database connection error: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
            
            // En desarrollo puedes cambiar esto por el mensaje real del error
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    // Método para cerrar la conexión explícitamente
    public function cerrarConexion() {
        $this->conn = null;
    }
}
?>
