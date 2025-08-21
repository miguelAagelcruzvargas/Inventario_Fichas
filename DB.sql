-- Crear la base de datos (si deseas)
CREATE DATABASE IF NOT EXISTS liconsa_gestion;
USE liconsa_gestion;

-- 1. Tabla de Inventarios
CREATE TABLE inventarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mes_anio VARCHAR(20) NOT NULL,                 -- Ejemplo: 'abril 2025'
  cajas_ingresadas INT NOT NULL,
  sobres_por_caja INT NOT NULL DEFAULT 36,
  precio_por_sobre DECIMAL(10,2) NOT NULL DEFAULT 13.00,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo',  -- 'activo' o 'cerrado'
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de Clientes por Inventario
CREATE TABLE clientes_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventario_id INT NOT NULL,
  tarjeta INT NOT NULL,                           -- Número de tarjeta (asignado manualmente)
  nombre VARCHAR(100) NOT NULL,
  dotacion_max INT NOT NULL,
  retiro_hecho BOOLEAN DEFAULT FALSE,
  sobres_retirados INT DEFAULT NULL,              -- Se puede editar manualmente
  monto_total DECIMAL(10,2) DEFAULT 0,
  estado VARCHAR(20) DEFAULT 'activo',            -- 'activo' o 'dado_de_baja'
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

  -- Restricciones
  UNIQUE (inventario_id, tarjeta),                 -- Evita tarjetas duplicadas en un mismo inventario
  FOREIGN KEY (inventario_id) REFERENCES inventarios(id) ON DELETE CASCADE
);

-- 3. Tabla de Resultados del Inventario
CREATE TABLE resultados_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventario_id INT NOT NULL,
  sobres_totales INT DEFAULT 0,                   -- cajas * sobres_por_caja
  sobres_retirados INT DEFAULT 0,                  -- suma sobres retirados
  sobres_restantes INT DEFAULT 0,                  -- sobres totales - sobres retirados
  cajas_restantes INT DEFAULT 0,                   -- sobres_restantes / sobres_por_caja
  sobres_sueltos INT DEFAULT 0,                    -- sobres_restantes % sobres_por_caja
  monto_recaudado DECIMAL(10,2) DEFAULT 0,          -- suma de montos retirados
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Relaciones
  FOREIGN KEY (inventario_id) REFERENCES inventarios(id) ON DELETE CASCADE
);

-- Opcionalmente, podrías agregar índices si deseas optimizar aún más:
CREATE INDEX idx_tarjeta_inventario ON clientes_inventario (tarjeta);
CREATE INDEX idx_estado_inventario ON inventarios (estado);
CREATE INDEX idx_estado_cliente ON clientes_inventario (estado);

-- Listo: base completa y funcional
