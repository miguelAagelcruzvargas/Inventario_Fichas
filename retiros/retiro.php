<?php
// retiros/Retiro.php
require_once __DIR__ . '/../config/conexion.php';

class Retiro {
    private $conn;
    private $table = 'retiros';
    
    // Propiedades del retiro
    public $id;
    public $cliente_id;
    public $inventario_id;
    public $retiro;
    public $sobres_retirados;
    public $monto_pagado;
    public $fecha_registro;
    
    // Constructor con conexión a la base de datos
    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->conectar();
    }
    
    // Crear o actualizar un retiro
    public function guardar() {
        // Verificar si ya existe un retiro para este cliente e inventario
        if($this->verificarExistencia()) {
            return $this->actualizar();
        } else {
            return $this->crear();
        }
    }
    
    // Crear un nuevo retiro
    private function crear() {
        $query = "INSERT INTO " . $this->table . " 
                  SET cliente_id = :cliente_id, 
                      inventario_id = :inventario_id, 
                      retiro = :retiro, 
                      sobres_retirados = :sobres_retirados,
                      monto_pagado = :monto_pagado";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitización de datos
        $this->cliente_id = htmlspecialchars(strip_tags($this->cliente_id));
        $this->inventario_id = htmlspecialchars(strip_tags($this->inventario_id));
        $this->retiro = $this->retiro ? 1 : 0;
        $this->sobres_retirados = htmlspecialchars(strip_tags($this->sobres_retirados));
        $this->monto_pagado = htmlspecialchars(strip_tags($this->monto_pagado));
        
        // Vinculación de parámetros
        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->bindParam(':inventario_id', $this->inventario_id);
        $stmt->bindParam(':retiro', $this->retiro);
        $stmt->bindParam(':sobres_retirados', $this->sobres_retirados);
        $stmt->bindParam(':monto_pagado', $this->monto_pagado);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Actualizar un retiro existente
    private function actualizar() {
        $query = "UPDATE " . $this->table . " 
                  SET retiro = :retiro, 
                      sobres_retirados = :sobres_retirados,
                      monto_pagado = :monto_pagado
                  WHERE cliente_id = :cliente_id AND inventario_id = :inventario_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitización de datos
        $this->cliente_id = htmlspecialchars(strip_tags($this->cliente_id));
        $this->inventario_id = htmlspecialchars(strip_tags($this->inventario_id));
        $this->retiro = $this->retiro ? 1 : 0;
        $this->sobres_retirados = htmlspecialchars(strip_tags($this->sobres_retirados));
        $this->monto_pagado = htmlspecialchars(strip_tags($this->monto_pagado));
        
        // Vinculación de parámetros
        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->bindParam(':inventario_id', $this->inventario_id);
        $stmt->bindParam(':retiro', $this->retiro);
        $stmt->bindParam(':sobres_retirados', $this->sobres_retirados);
        $stmt->bindParam(':monto_pagado', $this->monto_pagado);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Leer un retiro específico
    public function leer() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE cliente_id = :cliente_id AND inventario_id = :inventario_id 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->bindParam(':inventario_id', $this->inventario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->cliente_id = $row['cliente_id'];
            $this->inventario_id = $row['inventario_id'];
            $this->retiro = $row['retiro'];
            $this->sobres_retirados = $row['sobres_retirados'];
            $this->monto_pagado = $row['monto_pagado'];
            $this->fecha_registro = $row['fecha_registro'];
            return true;
        }
        
        return false;
    }
    
    // Leer retiros por inventario
    public function leerPorInventario($inventario_id, $pagina = 1, $por_pagina = 12) {
        $inicio = ($pagina - 1) * $por_pagina;
        
        $query = "SELECT r.*, c.numero_tarjeta, c.nombre_completo, c.dotacion_maxima 
                  FROM " . $this->table . " r
                  INNER JOIN clientes c ON r.cliente_id = c.id
                  WHERE r.inventario_id = :inventario_id
                  ORDER BY c.nombre_completo ASC
                  LIMIT :inicio, :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':inventario_id', $inventario_id);
        $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
        $stmt->bindParam(':limite', $por_pagina, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Contar retiros por inventario para paginación
    public function contarPorInventario($inventario_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE inventario_id = :inventario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':inventario_id', $inventario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Verificar si ya existe un retiro para este cliente e inventario
    private function verificarExistencia() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE cliente_id = :cliente_id AND inventario_id = :inventario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->bindParam(':inventario_id', $this->inventario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] > 0;
    }
}