<?php
// clientes/Cliente.php

require_once __DIR__ . '/../config/conexion.php';

class Cliente
{
    private $conn;
    private $table = 'clientes';

    public $id;
    public $numero_tarjeta;
    public $nombre_completo;
    public $dotacion_maxima;
    public $telefono;
    public $estado; 
    public $fecha_invitacion_grupo; 

    public function __construct()
    {
        $database = new Conexion(); 
        $this->conn = $database->conectar(); 
        if (!$this->conn) {
            error_log("FATAL: Fallo al obtener la conexión a la BD en la clase Cliente. Verificar config/conexion.php.");
        }
    }

    public function crear()
    {
        if (!$this->conn) {
            error_log("Error en Cliente::crear(): No hay conexión a la BD.");
            return false;
        }
        if ($this->verificarNumeroTarjeta($this->numero_tarjeta)) { 
            error_log("Intento de crear cliente con número de tarjeta duplicado: " . htmlspecialchars($this->numero_tarjeta));
            return false; 
        }

        $query = "INSERT INTO {$this->table} 
                    SET numero_tarjeta = :numero_tarjeta, 
                        nombre_completo = :nombre_completo, 
                        dotacion_maxima = :dotacion_maxima, 
                        telefono = :telefono, 
                        estado = 'activo'"; 

        $stmt = $this->conn->prepare($query);

        $this->numero_tarjeta = trim(strip_tags($this->numero_tarjeta));
        $this->nombre_completo = trim(strip_tags($this->nombre_completo));
        $this->dotacion_maxima = (int)trim(strip_tags($this->dotacion_maxima));
        $this->telefono = isset($this->telefono) ? trim(strip_tags($this->telefono)) : null;

        $stmt->bindParam(':numero_tarjeta', $this->numero_tarjeta);
        $stmt->bindParam(':nombre_completo', $this->nombre_completo);
        $stmt->bindParam(':dotacion_maxima', $this->dotacion_maxima, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $this->telefono);

        try {
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            error_log("Error en Cliente::crear() al ejecutar statement: " . implode(" | ", $stmt->errorInfo()));
            return false;
        } catch (PDOException $e) {
            error_log("Error PDO al crear cliente: " . $e->getMessage());
            return false;
        }
    }

    public function actualizar()
    {
        if (!$this->conn) {
            error_log("Error en Cliente::actualizar(): No hay conexión a la BD.");
            return false;
        }
        
        $query = "UPDATE {$this->table} 
                  SET nombre_completo = :nombre_completo, 
                      dotacion_maxima = :dotacion_maxima, 
                      telefono = :telefono,
                      estado = :estado 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre_completo = trim(strip_tags($this->nombre_completo));
        $this->dotacion_maxima = (int)trim(strip_tags($this->dotacion_maxima));
        $this->telefono = isset($this->telefono) ? trim(strip_tags($this->telefono)) : null;
        $this->estado = trim(strip_tags($this->estado)); 
        $this->id = (int)strip_tags($this->id);

        $stmt->bindParam(':nombre_completo', $this->nombre_completo);
        $stmt->bindParam(':dotacion_maxima', $this->dotacion_maxima, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar cliente (ID: {$this->id}): " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarDotacionPorId($cliente_id, $nueva_dotacion) {
        if (!$this->conn) return false;
        $query = "UPDATE {$this->table} SET dotacion_maxima = :dotacion_maxima WHERE id = :id";
        
        $dotacion_san = (int)trim(strip_tags($nueva_dotacion));
        $id_san = (int)trim(strip_tags($cliente_id));
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':dotacion_maxima', $dotacion_san, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id_san, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar dotación para cliente ID {$id_san}: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id_cliente, $nuevo_estado)
    {
        if (!$this->conn) return false;
        $query = "UPDATE {$this->table} SET estado = :estado WHERE id = :id";
        
        $estado_san = trim(strip_tags($nuevo_estado));
        $id_san = (int)strip_tags($id_cliente);
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estado', $estado_san);
            $stmt->bindParam(':id', $id_san, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error PDO al cambiar estado para cliente ID {$id_san}: " . $e->getMessage());
            return false;
        }
    }

    public function leerPaginadoActivos($desde_registro_numero, $registros_por_pagina)
    {
        if (!$this->conn) return false;
        if ($desde_registro_numero < 0) $desde_registro_numero = 0; 
        
        $query = "SELECT id, numero_tarjeta, nombre_completo, dotacion_maxima, telefono, estado, fecha_invitacion_grupo 
                  FROM {$this->table} 
                  WHERE estado = 'activo'
                  ORDER BY numero_tarjeta ASC, nombre_completo ASC
                  LIMIT :desde_registro_numero, :registros_por_pagina";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':desde_registro_numero', $desde_registro_numero, PDO::PARAM_INT);
            $stmt->bindParam(':registros_por_pagina', $registros_por_pagina, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error PDO en leerPaginadoActivos: " . $e->getMessage());
            return false;
        }
    }

    public function contarActivos()
    {
        if (!$this->conn) return 0;
        $query = "SELECT COUNT(*) as total_filas FROM {$this->table} WHERE estado = 'activo'";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total_filas'] : 0;
        } catch (PDOException $e) {
            error_log("Error PDO en contarActivos: " . $e->getMessage());
            return 0;
        }
    }
    
    public function contar() 
    {
        if (!$this->conn) return 0;
        $query = "SELECT COUNT(*) as total_filas FROM {$this->table}";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total_filas'] : 0;
        } catch (PDOException $e) {
            error_log("Error PDO en contar (todos los clientes): " . $e->getMessage());
            return 0;
        }
    }

    public function buscar($termino_busqueda) 
    {
        if (!$this->conn) return false;
        
        // Usar CONCAT para evitar parámetros duplicados
        $query = "SELECT id, numero_tarjeta, nombre_completo, dotacion_maxima, telefono, estado, fecha_invitacion_grupo
                  FROM " . $this->table . "
                  WHERE CONCAT(nombre_completo, ' ', numero_tarjeta, ' ', IFNULL(telefono, '')) LIKE ?
                  ORDER BY estado ASC, numero_tarjeta ASC, nombre_completo ASC"; 
        
        try {
            $stmt = $this->conn->prepare($query);
            $termino_general = "%" . trim($termino_busqueda) . "%";
            $stmt->bindParam(1, $termino_general, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error PDO en buscar clientes: " . $e->getMessage());
            return false;
        }
    }

    public function leerInactivos($desde_registro_numero, $registros_por_pagina)
    {
        if (!$this->conn) return false;
        if ($desde_registro_numero < 0) $desde_registro_numero = 0; 
        $query = "SELECT id, numero_tarjeta, nombre_completo, dotacion_maxima, telefono, estado, fecha_invitacion_grupo 
                  FROM {$this->table} 
                  WHERE estado = 'inactivo'
                  ORDER BY numero_tarjeta ASC, nombre_completo ASC
                  LIMIT :desde_registro_numero, :registros_por_pagina";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':desde_registro_numero', $desde_registro_numero, PDO::PARAM_INT);
            $stmt->bindParam(':registros_por_pagina', $registros_por_pagina, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error PDO en leerInactivos: " . $e->getMessage());
            return false;
        }
    }

    public function contarInactivos()
    {
        if (!$this->conn) return 0;
        $query = "SELECT COUNT(*) as total_filas FROM {$this->table} WHERE estado = 'inactivo'";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total_filas'] : 0;
        } catch (PDOException $e) {
            error_log("Error PDO en contarInactivos: " . $e->getMessage());
            return 0;
        }
    }
    
    public function obtenerSugerencias($termino, $limite = 8, $solo_activos = true) {
        if (!$this->conn) return [];
        
        // Limpiar y preparar el término de búsqueda
        $termino_limpio = trim($termino);
        if (empty($termino_limpio)) return [];
        
        $estado_filter = $solo_activos ? "AND estado = 'activo'" : "";
        
        // Simplificar la consulta para evitar parámetros duplicados - usar CONCAT en vez de múltiples LIKE
        $termino_busqueda = "%" . $termino_limpio . "%";
        $termino_prefijo = $termino_limpio . "%";
        
        $query = "SELECT id, nombre_completo, numero_tarjeta, telefono, estado 
                  FROM " . $this->table . "
                  WHERE (CONCAT(nombre_completo, ' ', numero_tarjeta, ' ', IFNULL(telefono, '')) LIKE ?)
                  {$estado_filter}
                  ORDER BY 
                    CASE
                        WHEN numero_tarjeta LIKE ? THEN 1
                        WHEN nombre_completo LIKE ? THEN 2
                        WHEN telefono LIKE ? THEN 3
                        ELSE 4
                    END, 
                    numero_tarjeta ASC, nombre_completo ASC
                  LIMIT ?";
        try {
            $stmt = $this->conn->prepare($query);
            
            // Bind en orden: 1=busqueda, 2,3,4=prefijos para ordenar, 5=limite
            $stmt->bindParam(1, $termino_busqueda, PDO::PARAM_STR);
            $stmt->bindParam(2, $termino_prefijo, PDO::PARAM_STR);
            $stmt->bindParam(3, $termino_prefijo, PDO::PARAM_STR);
            $stmt->bindParam(4, $termino_prefijo, PDO::PARAM_STR);
            $stmt->bindParam(5, $limite, PDO::PARAM_INT);
            
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log para debugging
            error_log("obtenerSugerencias OK - Término: '$termino_limpio', Resultados: " . count($resultado));
            
            return $resultado;
        } catch (PDOException $e) {
            error_log("Error PDO en obtenerSugerencias: " . $e->getMessage());
            return [];
        }
    }

    public function leerUno()
    {
        if (!$this->conn || empty($this->id)) {
            error_log("Error en Cliente::leerUno(): ID de cliente no especificado o no hay conexión.");
            return false;
        }
        $query = "SELECT id, numero_tarjeta, nombre_completo, dotacion_maxima, telefono, estado, fecha_invitacion_grupo 
                  FROM {$this->table} WHERE id = :id LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $id_san = (int)strip_tags($this->id);
            $stmt->bindParam(':id', $id_san, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $this->id = (int)$row['id']; 
                $this->numero_tarjeta = $row['numero_tarjeta'];
                $this->nombre_completo = $row['nombre_completo'];
                $this->dotacion_maxima = (int)$row['dotacion_maxima'];
                $this->telefono = $row['telefono'];
                $this->estado = $row['estado'];
                $this->fecha_invitacion_grupo = $row['fecha_invitacion_grupo']; 
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error PDO al leer un cliente (ID: {$this->id}): " . $e->getMessage());
        }
        return false;
    }

    public function getClientesParaInvitacion() { 
        if (!$this->conn) return false;
        $query = "SELECT id, nombre_completo, numero_tarjeta, telefono, fecha_invitacion_grupo
                  FROM " . $this->table . "
                  WHERE estado = 'activo' AND telefono IS NOT NULL AND telefono != ''
                  ORDER BY numero_tarjeta ASC, nombre_completo ASC"; // Cambiado orden
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error PDO en getClientesParaInvitacion: " . $e->getMessage());
            return false;
        }
    }

    public function marcarInvitacionEnviada($cliente_id) {
        if (!$this->conn) return false;
        $query = "UPDATE " . $this->table . " SET fecha_invitacion_grupo = NOW() WHERE id = :id";
        
        $id_cliente_san = (int)$cliente_id;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id_cliente_san, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error PDO en marcarInvitacionEnviada para ID {$id_cliente_san}: " . $e->getMessage());
            return false;
        }
    }

    private function verificarNumeroTarjeta($numero_tarjeta_a_verificar, $excluir_id_actual = null)
    {
        if (!$this->conn || empty($numero_tarjeta_a_verificar)) return false; 
        
        $sql_excluir_id = "";
        $params = [':numero_tarjeta' => trim(strip_tags($numero_tarjeta_a_verificar))];

        if ($excluir_id_actual !== null) {
            $sql_excluir_id = " AND id != :id_actual";
            $params[':id_actual'] = (int)$excluir_id_actual;
        }

        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE numero_tarjeta = :numero_tarjeta" . $sql_excluir_id;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params); 
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error PDO al verificar número de tarjeta {$numero_tarjeta_a_verificar}: " . $e->getMessage());
            return false; 
        }
    }
    
    public function existeNumeroTarjeta($numero_tarjeta, $excluir_id = null)
    {
        return $this->verificarNumeroTarjeta($numero_tarjeta, $excluir_id);
    }

    // Método simplificado para crear un cliente con parámetros directos
    public function crearCliente($numero_tarjeta, $nombre_completo, $dotacion_maxima, $telefono = null)
    {
        $this->numero_tarjeta = $numero_tarjeta;
        $this->nombre_completo = $nombre_completo;
        $this->dotacion_maxima = $dotacion_maxima;
        $this->telefono = $telefono;
        
        return $this->crear();
    }

    // Método para verificar si existe una tarjeta (alias para compatibilidad)
    public function existeTarjeta($numero_tarjeta)
    {
        return $this->verificarNumeroTarjeta($numero_tarjeta);
    }

    public function getUltimoError() {
        return "Hubo un error en la última operación de cliente (detalle no específico).";
    }
}
?>
