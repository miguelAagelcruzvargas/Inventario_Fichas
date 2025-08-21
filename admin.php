<?php
// create_admin_user.php (O si lo renombraste, admin.php)
// Este script se ejecuta desde la línea de comandos (CLI) para crear un nuevo usuario administrador.
// Uso: php create_admin_user.php (o php admin.php)

// Ajusta la ruta a tu archivo de conexión a la base de datos.
// Si este script está en la raíz del proyecto y Conexion.php está en config/,
// entonces la ruta correcta es 'config/Conexion.php'.
require_once 'config/Conexion.php';

// Asegurarse de que el script solo se ejecute desde la línea de comandos.
if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la línea de comandos (CLI).\n");
}

echo "--- Creación de Nuevo Usuario Administrador para Gestión de Leche ---\n";
echo "Este usuario tendrá acceso completo al sistema.\n";

// Función para leer la entrada del usuario de forma segura desde la CLI.
function leer_entrada($prompt) {
    echo $prompt . ": ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    return $line;
}

// Función para leer la contraseña sin mostrarla en la terminal.
function leer_password($prompt) {
    echo $prompt . ": ";
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        system('stty -echo');
    }
    $handle = fopen("php://stdin", "r");
    $password = trim(fgets($handle));
    fclose($handle);
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        system('stty echo');
    }
    echo "\n";
    return $password;
}

// --- Solicitar datos del administrador ---
$nombre_usuario = leer_entrada("Nombre de usuario para el administrador"); // Usamos nombre_usuario
$password = leer_password("Contraseña para el administrador");
$password_confirm = leer_password("Confirmar contraseña");
$email = leer_entrada("Email del administrador (opcional, presiona Enter para omitir)");

// --- Validaciones básicas ---
if (empty($nombre_usuario) || empty($password)) {
    die("Error: Nombre de usuario y contraseña son obligatorios.\n");
}

if (strlen($nombre_usuario) < 3 || strlen($nombre_usuario) > 50) {
    die("Error: El nombre de usuario debe tener entre 3 y 50 caracteres.\n");
}

if ($password !== $password_confirm) {
    die("Error: Las contraseñas no coinciden.\n");
}

// Validación de fortaleza de contraseña: al menos 8 caracteres, mayúscula, minúscula, número, y símbolo.
if (strlen($password) < 8 ||
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match("/[^a-zA-Z0-9]/", $password)) { // Al menos un carácter especial
    die("Error: La contraseña debe tener al menos 8 caracteres, incluyendo una mayúscula, una minúscula, un número y un carácter especial.\n");
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: El formato del correo electrónico no es válido si se proporciona.\n");
}

// --- Hashear la contraseña ---
$contrasena_hash = password_hash($password, PASSWORD_DEFAULT); // Usamos contrasena_hash
if ($contrasena_hash === false) {
    die("Error crítico: No se pudo hashear la contraseña. Esto puede indicar un problema en la configuración de PHP.\n");
}

// --- Conectar a la base de datos ---
$database = new Conexion();
$pdo = $database->conectar();

if (!$pdo) {
    die("Error: No se pudo conectar a la base de datos. Por favor, revisa tu 'config/Conexion.php'.\n");
}

try {
    // --- Verificar si el nombre de usuario ya existe ---
    // ¡CORRECCIÓN APLICADA AQUÍ: USANDO 'nombre_usuario'!
    $stmt_check_user = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :nombre_usuario LIMIT 1");
    $stmt_check_user->execute([':nombre_usuario' => $nombre_usuario]);
    if ($stmt_check_user->fetch()) {
        die("Error: El nombre de usuario '{$nombre_usuario}' ya existe.\n");
    }

    // --- Verificar si el email ya existe (si se proporcionó) ---
    if (!empty($email)) {
        $stmt_check_email = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $stmt_check_email->execute([':email' => $email]);
        if ($stmt_check_email->fetch()) {
            die("Error: El email '{$email}' ya está en uso por otro usuario.\n");
        }
    }

    // --- Insertar el nuevo usuario administrador ---
    // ¡CORRECCIÓN APLICADA AQUÍ: USANDO 'nombre_usuario' y 'contrasena_hash'!
    $sql_insert = "INSERT INTO usuarios 
                   (nombre_usuario, contrasena_hash, email, role) 
                   VALUES 
                   (:nombre_usuario, :contrasena_hash, :email, 'admin')";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        ':nombre_usuario' => $nombre_usuario,
        ':contrasena_hash' => $contrasena_hash,
        ':email' => $email ?: null,
    ]);

    $ultimo_id_insertado = $pdo->lastInsertId();
    echo "---------------------------------------------------\n";
    echo "¡Usuario administrador '{$nombre_usuario}' creado exitosamente con ID: {$ultimo_id_insertado}!\n";
    echo "Ahora este usuario puede iniciar sesión en el panel de administración.\n";

} catch (PDOException $e) {
    error_log("Error PDO al crear usuario admin: " . $e->getMessage());
    die("Error al crear el usuario administrador: " . $e->getMessage() . "\n");
}

?>
