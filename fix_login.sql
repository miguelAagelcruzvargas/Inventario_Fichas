-- Archivo para corregir problema de login
-- Agregar columna rol a la tabla usuarios y actualizar datos

USE gestion_leche;

-- 1. Agregar columna rol a la tabla usuarios
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS rol ENUM('admin', 'usuario') DEFAULT 'admin';

-- 2. Actualizar usuario existente para que tenga rol admin
UPDATE usuarios SET rol = 'admin' WHERE nombre_usuario = 'admin';

-- 3. Verificar que el usuario admin existe, si no, crearlo
INSERT IGNORE INTO usuarios (nombre_usuario, password, email, nombre_completo, estado, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sistema.local', 'Administrador del Sistema', 'activo', 'admin');

-- 4. Mostrar el usuario para verificar
SELECT id, nombre_usuario, email, nombre_completo, estado, rol FROM usuarios WHERE nombre_usuario = 'admin';
