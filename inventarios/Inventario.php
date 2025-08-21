<?php
// inventarios/Inventario.php
require_once __DIR__ . '/../config/conexion.php'; // Asegúrate que esta ruta sea correcta

class Inventario {
    private $conn;
    private $table = 'inventarios';
    
    // Propiedades del inventario
    public $id;
    public $mes; 
    public $anio; 
    public $cajas_ingresadas;
    public $sobres_por_caja;
    public $precio_sobre;
    public $estado; 
    public $fecha_creacion;
    public $fecha_cierre;
    
    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->conectar();
        if (!$this->conn) {
            error_log("FATAL: Fallo al obtener la conexión a la BD en la clase Inventario. Verificar config/conexion.php.");
            // Considera manejar este error de forma más robusta si es necesario
        }
    }
    
    public function crear() {
        if (!$this->conn) {
            error_log("Error en Inventario::crear(): No hay conexión a la BD.");
            return false;
        }
        if($this->verificarMesAnio()) {
            error_log("Intento de crear inventario duplicado para Mes: {$this->mes}, Año: {$this->anio}");
            return false; 
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  SET mes = :mes, 
                      anio = :anio, 
                      cajas_ingresadas = :cajas_ingresadas, 
                      sobres_por_caja = :sobres_por_caja,
                      precio_sobre = :precio_sobre,
                      estado = 'abierto'";
        
        try {
            $stmt = $this->conn->prepare($query);
        
            // Asumiendo que las propiedades ya están limpias y con el tipo correcto
            // (ej. casteadas a int/float en el script que llama)
            $stmt->bindParam(':mes', $this->mes, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $this->anio, PDO::PARAM_INT);
            $stmt->bindParam(':cajas_ingresadas', $this->cajas_ingresadas, PDO::PARAM_INT);
            $stmt->bindParam(':sobres_por_caja', $this->sobres_por_caja, PDO::PARAM_INT);
            $stmt->bindParam(':precio_sobre', $this->precio_sobre); 

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::crear(): " . $e->getMessage());
        }
        return false;
    }
    
    public function leer() {
        if (!$this->conn) return false;
        $query = "SELECT * FROM " . $this->table . " ORDER BY anio DESC, mes DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::leer(): " . $e->getMessage());
            return false;
        }
    }
    
    public function leerUno() {
        if (!$this->conn || empty($this->id)) return false;
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 0,1";
        try {
            $stmt = $this->conn->prepare($query);
            $id_san = (int)$this->id;
            $stmt->bindParam(':id', $id_san, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->id = (int)$row['id'];
                $this->mes = (int)$row['mes'];
                $this->anio = (int)$row['anio'];
                $this->cajas_ingresadas = (int)$row['cajas_ingresadas'];
                $this->sobres_por_caja = (int)$row['sobres_por_caja'];
                $this->precio_sobre = (float)$row['precio_sobre'];
                $this->estado = $row['estado'];
                $this->fecha_creacion = $row['fecha_creacion'];
                $this->fecha_cierre = $row['fecha_cierre'];
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::leerUno() para ID {$this->id}: " . $e->getMessage());
        }
        return false;
    }
    
    public function obtenerInventarioActivo() {
        if (!$this->conn) return false;
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE estado = 'abierto' 
                  ORDER BY anio DESC, mes DESC 
                  LIMIT 0,1";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->id = (int)$row['id'];
                $this->mes = (int)$row['mes'];
                $this->anio = (int)$row['anio'];
                $this->cajas_ingresadas = (int)$row['cajas_ingresadas'];
                $this->sobres_por_caja = (int)$row['sobres_por_caja'];
                $this->precio_sobre = (float)$row['precio_sobre'];
                $this->estado = $row['estado'];
                $this->fecha_creacion = $row['fecha_creacion'];
                $this->fecha_cierre = $row['fecha_cierre'];
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::obtenerInventarioActivo(): " . $e->getMessage());
        }
        return false;
    }
    
    public function cerrar() {
        if (!$this->conn || empty($this->id)) return false;
        $query = "UPDATE " . $this->table . " 
                  SET estado = 'cerrado', fecha_cierre = NOW() 
                  WHERE id = :id AND estado = 'abierto'";
        try {
            $stmt = $this->conn->prepare($query);
            $id_san = (int)$this->id;
            $stmt->bindParam(':id', $id_san, PDO::PARAM_INT);
            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::cerrar() para ID {$this->id}: " . $e->getMessage());
        }
        return false;
    }
    
    private function verificarMesAnio() {
        if (!$this->conn || empty($this->mes) || empty($this->anio)) return true; // Asumir que existe si los datos son inválidos para prevenir error
        $mes_int = (int)$this->mes;
        $anio_int = (int)$this->anio;
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE mes = :mes AND anio = :anio";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mes', $mes_int, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $anio_int, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::verificarMesAnio(): " . $e->getMessage());
            return true; // En caso de error de BD, es más seguro asumir que existe para evitar duplicados accidentales.
        }
    }
    
    public function calcularSobresTotales() {
        return (int)($this->cajas_ingresadas ?? 0) * (int)($this->sobres_por_caja ?? 0);
    }
    
    public function calcularSobresRetirados() {
        if (!$this->conn || empty($this->id)) return 0;
        $query = "SELECT SUM(sobres_retirados) as total FROM retiros WHERE inventario_id = :inventario_id";
        try {
            $stmt = $this->conn->prepare($query);
            $id_san = (int)$this->id;
            $stmt->bindParam(':inventario_id', $id_san, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['total'] ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::calcularSobresRetirados() para ID {$this->id}: " . $e->getMessage());
            return 0;
        }
    }
    
    public function calcularSobresRestantes() {
        $sobres_totales = $this->calcularSobresTotales();
        $sobres_retirados = $this->calcularSobresRetirados();
        return $sobres_totales - $sobres_retirados;
    }
    
    public function calcularCajasRestantes() {
        $sobres_restantes = $this->calcularSobresRestantes();
        $sobres_por_caja_val = (int)($this->sobres_por_caja ?? 0);
        if ($sobres_por_caja_val > 0) {
            return floor($sobres_restantes / $sobres_por_caja_val);
        }
        return 0;
    }
    
    public function calcularDineroRecaudado() {
        if (!$this->conn || empty($this->id)) return 0.0;
        $query = "SELECT SUM(monto_pagado) as total FROM retiros WHERE inventario_id = :inventario_id";
        try {
            $stmt = $this->conn->prepare($query);
            $id_san = (int)$this->id;
            $stmt->bindParam(':inventario_id', $id_san, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['total'] ? (float)$row['total'] : 0.0;
        } catch (PDOException $e) {
            error_log("Error PDO en Inventario::calcularDineroRecaudado() para ID {$this->id}: " . $e->getMessage());
            return 0.0;
        }
    }
    
    public static function nombreMesStatic($numeroMes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $numeroMesInt = (int)$numeroMes;
        return $meses[$numeroMesInt] ?? 'Mes inválido';
    }

    public function obtenerNombreMes() {
        return self::nombreMesStatic($this->mes);
    }
}
?>
