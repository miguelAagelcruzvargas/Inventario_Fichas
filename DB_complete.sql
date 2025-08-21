-- Base de datos completa para el Sistema de Gestión de Leche
-- Usar este archivo para crear la base de datos y todas las tablas necesarias

CREATE DATABASE IF NOT EXISTS gestion_leche CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_leche;

-- 1. Tabla de usuarios para el sistema de login
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NULL,
  nombre_completo VARCHAR(100) NULL,
  estado ENUM('activo', 'inactivo') DEFAULT 'activo',
  ultimo_acceso DATETIME NULL,
  intentos_fallidos INT DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero_tarjeta VARCHAR(20) NOT NULL UNIQUE,
  nombre_completo VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NULL,
  dotacion_maxima INT NOT NULL DEFAULT 0,
  estado ENUM('activo', 'inactivo') DEFAULT 'activo',
  fecha_invitacion_grupo DATETIME NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Tabla de inventarios
CREATE TABLE IF NOT EXISTS inventarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mes VARCHAR(20) NOT NULL,
  anio INT NOT NULL,
  mes_anio VARCHAR(30) NOT NULL,
  cajas_ingresadas INT NOT NULL,
  sobres_por_caja INT NOT NULL DEFAULT 36,
  precio_por_sobre DECIMAL(10,2) NOT NULL DEFAULT 13.00,
  estado ENUM('activo', 'cerrado') DEFAULT 'activo',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_mes_anio (mes, anio)
);

-- 4. Tabla de clientes por inventario
CREATE TABLE IF NOT EXISTS clientes_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventario_id INT NOT NULL,
  cliente_id INT NOT NULL,
  tarjeta VARCHAR(20) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  dotacion_max INT NOT NULL,
  retiro_hecho BOOLEAN DEFAULT FALSE,
  sobres_retirados INT DEFAULT NULL,
  monto_total DECIMAL(10,2) DEFAULT 0,
  estado ENUM('activo', 'dado_de_baja') DEFAULT 'activo',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (inventario_id) REFERENCES inventarios(id) ON DELETE CASCADE,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  UNIQUE KEY unique_cliente_inventario (inventario_id, cliente_id)
);

-- 5. Tabla de retiros
CREATE TABLE IF NOT EXISTS retiros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventario_id INT NOT NULL,
  cliente_inventario_id INT NOT NULL,
  sobres_retirados INT NOT NULL,
  monto_pagado DECIMAL(10,2) NOT NULL,
  fecha_retiro DATETIME DEFAULT CURRENT_TIMESTAMP,
  observaciones TEXT NULL,
  FOREIGN KEY (inventario_id) REFERENCES inventarios(id) ON DELETE CASCADE,
  FOREIGN KEY (cliente_inventario_id) REFERENCES clientes_inventario(id) ON DELETE CASCADE
);

-- 6. Tabla de resultados del inventario
CREATE TABLE IF NOT EXISTS resultados_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventario_id INT NOT NULL,
  sobres_totales INT DEFAULT 0,
  sobres_retirados INT DEFAULT 0,
  sobres_restantes INT DEFAULT 0,
  cajas_restantes INT DEFAULT 0,
  sobres_sueltos INT DEFAULT 0,
  monto_recaudado DECIMAL(10,2) DEFAULT 0,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (inventario_id) REFERENCES inventarios(id) ON DELETE CASCADE
);

-- 7. Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(100) NOT NULL UNIQUE,
  valor TEXT NOT NULL,
  descripcion VARCHAR(255) NULL,
  tipo ENUM('string', 'int', 'float', 'boolean', 'json') DEFAULT 'string',
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Índices para optimización
CREATE INDEX idx_clientes_numero_tarjeta ON clientes (numero_tarjeta);
CREATE INDEX idx_clientes_estado ON clientes (estado);
CREATE INDEX idx_inventarios_estado ON inventarios (estado);
CREATE INDEX idx_inventarios_mes_anio ON inventarios (mes, anio);
CREATE INDEX idx_clientes_inventario_estado ON clientes_inventario (estado);
CREATE INDEX idx_retiros_fecha ON retiros (fecha_retiro);
CREATE INDEX idx_usuarios_estado ON usuarios (estado);
CREATE INDEX idx_usuarios_ultimo_acceso ON usuarios (ultimo_acceso);

-- Insertar usuario administrador por defecto (password: admin123)
INSERT IGNORE INTO usuarios (nombre_usuario, password, email, nombre_completo, estado) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sistema.local', 'Administrador del Sistema', 'activo');

-- Insertar configuraciones básicas del sistema
INSERT IGNORE INTO configuracion (clave, valor, descripcion, tipo) VALUES 
('sobres_por_caja', '36', 'Número de sobres por caja por defecto', 'int'),
('precio_por_sobre', '13.00', 'Precio por sobre por defecto', 'float'),
('sistema_nombre', 'Sistema de Gestión de Leche', 'Nombre del sistema', 'string'),
('whatsapp_api_enabled', 'false', 'Habilitar integración con WhatsApp', 'boolean');

-- Mensaje de confirmación
SELECT 'Base de datos creada exitosamente' as mensaje;
