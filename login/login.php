<?php
// login/login.php
// Esta es la página de inicio de sesión para administradores, ubicada dentro de la carpeta 'login/'.

// Incluir configuración de seguridad
require_once '../config/security_config.php';

// Aplicar headers de seguridad usando la configuración centralizada
SecurityConfig::applySecurityHeaders();

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// Iniciar la sesión de PHP si aún no está activa. Es crucial que esto esté al principio del script.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de conexión a la base de datos.
// La ruta sube un nivel de 'login/' a la raíz del proyecto, y luego baja a 'config/'.
require_once '../config/Conexion.php';
require_once '../config/security_config.php';

// Función para generar token CSRF
function generateCSRFToken() {
    return SecurityConfig::generateCSRFToken();
}

// Función para validar token CSRF
function validateCSRFToken($token) {
    return SecurityConfig::validateCSRFToken($token);
}

// Funciones simplificadas para programa personal - sin rate limiting

// --- VERIFICACIÓN DE SEGURIDAD: REDIRECCIÓN SI YA ESTÁ LOGUEADO ---
// Si un usuario ya está logueado, verificamos su rol para redirigirlo correctamente.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        // Si es un administrador, lo redirigimos a la página principal 'index.php' en la raíz.
        // La ruta sube dos niveles de 'login/' a la raíz.
        header('Location: ../index.php');
        exit(); // Terminar la ejecución del script.
    } else {
        // Si el usuario está logueado pero NO es un administrador,
        // establecer el mensaje antes de destruir la sesión
        $error_msg_temp = "Acceso denegado. Solo administradores pueden iniciar sesión aquí.";
        session_destroy();
        // Iniciar nueva sesión para mostrar el mensaje
        session_start();
        $_SESSION['error_message'] = $error_msg_temp;
        header('Location: login.php'); // Redirige a esta misma página (login.php en login/)
        exit(); // Terminar la ejecución del script.
    }
}

// Inicialización de variables para mensajes de error y éxito.
$error_message = '';
$success_message = '';

// Recuperar mensajes de la sesión si existen (ej. después de un intento de acceso denegado).
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Limpiar el mensaje de la sesión una vez mostrado.
}
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Limpiar el mensaje de la sesión una vez mostrado.
}

// Manejar un error específico si se intentó acceder al dashboard sin ser admin,
// y fue redirigido aquí. Solo mostrar una vez y limpiar el parámetro.
if (isset($_GET['error']) && $_GET['error'] == 'no_admin_access') {
    $error_message = "Acceso denegado. Solo administradores pueden iniciar sesión aquí.";
    // Redirigir para limpiar el parámetro GET y evitar que aparezca en cada recarga
    echo "<script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>";
}

// --- PROCESAMIENTO DEL FORMULARIO DE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $_SESSION['error_message'] = "Token de seguridad inválido. Recarga la página e inténtalo de nuevo.";
        header('Location: login.php');
        exit();
    }
    
    // Recoger y limpiar los datos de entrada del formulario.
    $nombre_usuario_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    // Validación básica de entrada
    if (empty($nombre_usuario_input) || empty($password_input)) {
        $_SESSION['error_message'] = "Usuario y contraseña son requeridos.";
        header('Location: login.php');
        exit();
    }
    
    // Crear una nueva instancia de la clase Conexion para conectar a la base de datos.
    $database = new Conexion();
    $db = $database->conectar();

    // Verificar si la conexión a la base de datos fue exitosa.
    if (!$db) {
        $_SESSION['error_message'] = "Error de conexión a la base de datos.";
        error_log("Database connection failed in login.php at " . date('Y-m-d H:i:s'));
        header('Location: login.php');
        exit();
    }
    
    try {
        // Verificar qué columnas existen en la tabla usuarios
        $columnsQuery = "SHOW COLUMNS FROM usuarios";
        $columnsStmt = $db->prepare($columnsQuery);
        $columnsStmt->execute();
        $existingColumns = array_column($columnsStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        // Construir consulta básica
        $selectFields = "id, nombre_usuario";
        
        // Verificar qué columna de contraseña existe
        if (in_array('password_hash', $existingColumns)) {
            $selectFields .= ", password_hash";
            $passwordColumn = 'password_hash';
        } else if (in_array('contrasena_hash', $existingColumns)) {
            $selectFields .= ", contrasena_hash";
            $passwordColumn = 'contrasena_hash';
        } else {
            $selectFields .= ", contrasena";
            $passwordColumn = 'contrasena';
        }
        
        // Verificar qué columna de rol existe
        if (in_array('rol', $existingColumns)) {
            $selectFields .= ", rol";
            $roleColumn = 'rol';
        } else {
            $selectFields .= ", role";
            $roleColumn = 'role';
        }
        
        // Solo incluir columna activo si existe
        if (in_array('activo', $existingColumns)) $selectFields .= ", activo";
        
        // Preparar la consulta SQL
        $query = "SELECT {$selectFields} FROM usuarios WHERE nombre_usuario = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre_usuario_input]);

        // Obtener los datos del usuario como un array asociativo.
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si la cuenta está activa (solo si la columna existe)
        if ($user && isset($user['activo']) && $user['activo'] == 0) {
            $_SESSION['error_message'] = "Tu cuenta ha sido desactivada. Contacta al administrador.";
            header('Location: login.php');
            exit();
        } else if ($user && password_verify($password_input, $user[$passwordColumn])) {
            // Si la contraseña es correcta, verificamos que el rol del usuario sea 'admin'.
            if ($user[$roleColumn] === 'admin') {
                // Si es administrador, almacenar sus datos esenciales en la sesión.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['user_role'] = $user[$roleColumn];
                $_SESSION['login_time'] = time();

                // Regenerar el ID de sesión para prevenir ataques de fijación de sesión.
                session_regenerate_id(true);

                // Redirigir al administrador a la página principal 'index.php' en la raíz.
                header('Location: ../index.php');
                exit();
            } else {
                // Usuario existe y contraseña correcta, pero no es admin.
                $_SESSION['error_message'] = "Acceso denegado. Solo administradores pueden iniciar sesión aquí.";
                header('Location: login.php');
                exit();
            }
        } else {
            // Usuario no encontrado o contraseña incorrecta. Mensaje genérico por seguridad.
            $_SESSION['error_message'] = "Usuario o contraseña inválidos.";
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        // Registrar el error detallado en el log del servidor y mostrar un mensaje genérico al usuario.
        error_log("Error de login PDO en login/login.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
        $_SESSION['error_message'] = "Ocurrió un error inesperado. Por favor, inténtalo más tarde.";
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Administrador - Gestión Leche</title>
    <!-- Incluir Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');

        :root {
            --primary-blue: #007bff;
            --primary-blue-dark: #0056b3;
            --gradient-start-bg: #89CFF0; /* Un azul cielo vibrante */
            --gradient-end-bg: #007bff;   /* Azul profundo */
            --shadow-color: rgba(0, 0, 0, 0.25); /* Sombra más pronunciada */
            --text-dark: #333;
            --text-light: #666;
            --border-color: #ddd;
            --input-focus: #80bdff;
            --error-red: #dc3545;
            --error-bg: #f8d7da;
            --error-border: #f5c6cb;
            --success-green: #28a745;
            --success-bg: #d4edda;
            --success-border: #c3e6cb;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--gradient-start-bg) 0%, var(--gradient-end-bg) 100%);
            background-size: 200% 200%; /* Para la animación de degradado */
            animation: gradientAnimation 15s ease infinite; /* Animación de degradado */
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            box-sizing: border-box;
            overflow: hidden; /* Evita barras de desplazamiento si hay elementos fuera de pantalla */
        }

        /* Animación para el fondo degradado */
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-wrapper {
            background-color: rgba(255, 255, 255, 0.95); /* Fondo blanco semitransparente para un efecto moderno */
            backdrop-filter: blur(10px); /* Efecto de cristal esmerilado */
            -webkit-backdrop-filter: blur(10px); /* Compatibilidad con Safari */
            padding: 40px;
            border-radius: 20px; /* Bordes más redondeados */
            box-shadow: 0 20px 40px var(--shadow-color); /* Sombra más prominente */
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-sizing: border-box;
            transition: transform 0.4s ease-in-out, box-shadow 0.4s ease-in-out;
            position: relative; /* Para el posicionamiento del icono */
            z-index: 1; /* Asegura que esté sobre el fondo */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Borde sutil */
        }

        .login-wrapper:hover {
            transform: translateY(-8px) scale(1.01); /* Efecto de elevación y zoom sutil */
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        h2 {
            font-size: 2.8em; /* Título aún más grande */
            color: var(--primary-blue-dark);
            margin-bottom: 5px;
            font-weight: 800;
            letter-spacing: -0.8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1); /* Sombra de texto sutil */
        }

        .subtitle {
            font-size: 1.2em;
            color: var(--text-light);
            margin-bottom: 35px;
            font-weight: 400;
        }

        .message-box {
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px; /* Más espacio */
            font-size: 1em;
            font-weight: 600;
            text-align: left;
            border: 1px solid transparent;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .error-message {
            background-color: var(--error-bg);
            color: var(--error-red);
            border-color: var(--error-border);
        }

        .success-message {
            background-color: var(--success-bg);
            color: var(--success-green);
            border-color: var(--success-border);
        }

        .form-group {
            margin-bottom: 25px; /* Más espacio entre campos */
            text-align: left;
            position: relative; /* Necesario para posicionar el icono */
        }

        label {
            display: block;
            margin-bottom: 10px; /* Más espacio para la etiqueta */
            color: var(--text-dark);
            font-weight: 700; /* Más peso */
            font-size: 1em;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 18px; /* Más padding */
            border: 2px solid var(--border-color); /* Borde más visible */
            border-radius: 12px; /* Bordes más redondeados */
            font-size: 1.1em; /* Texto más grande */
            color: var(--text-dark);
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            -webkit-appearance: none;
            background-color: #f0f4f8; /* Un fondo ligeramente azulado/grisáceo para inputs */
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px var(--input-focus); /* Sombra de enfoque más grande */
            outline: none;
            background-color: #fff;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%; /* Alinea verticalmente */
            transform: translateY(-50%); /* Ajuste fino */
            cursor: pointer;
            color: var(--text-light);
            font-size: 1.1em;
            transition: color 0.2s ease;
            padding: 5px; /* Área de clic más grande */
            z-index: 2; /* Asegura que el icono sea clickeable */
        }

        .toggle-password:hover {
            color: var(--primary-blue);
        }

        button[type="submit"] {
            width: 100%;
            padding: 18px; /* Más padding para el botón */
            background-color: var(--primary-blue);
            color: #fff;
            border: none;
            border-radius: 12px; /* Bordes más redondeados */
            font-size: 1.25em; /* Texto del botón más grande */
            font-weight: 800; /* Extra bold */
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4); /* Sombra más grande del botón */
            letter-spacing: 1px;
            text-transform: uppercase; /* Texto en mayúsculas */
        }

        button[type="submit"]:hover {
            background-color: var(--primary-blue-dark);
            transform: translateY(-4px); /* Más efecto de elevación */
            box-shadow: 0 12px 25px rgba(0, 123, 255, 0.5);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            body {
                padding: 15px; /* Menos padding en el body para dispositivos pequeños */
            }
            .login-wrapper {
                margin: 0; /* Sin margen en pantallas muy pequeñas */
                padding: 25px; /* Menos padding interno */
                border-radius: 15px;
            }
            h2 {
                font-size: 2.2em;
            }
            .subtitle {
                font-size: 1em;
                margin-bottom: 25px;
            }
            .message-box {
                padding: 10px 15px;
                margin-bottom: 20px;
                font-size: 0.9em;
            }
            label {
                font-size: 0.9em;
                margin-bottom: 6px;
            }
            input[type="text"],
            input[type="password"] {
                padding: 12px 15px;
                font-size: 1em;
                border-radius: 8px;
            }
            button[type="submit"] {
                padding: 14px;
                font-size: 1.1em;
                border-radius: 8px;
            }
            .toggle-password {
                right: 10px; /* Ajusta la posición para pantallas pequeñas */
                font-size: 1em;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="text-content">
            <h2>Gestión Liconsa</h2>
            <p class="subtitle">Panel de Administración</p>
        </div>

        <?php if ($error_message): ?>
            <div class="message-box error-message">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message-box success-message">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
            
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit">
                Iniciar Sesión
            </button>
        </form>
    </div>

    <script>
        // Función para alternar la visibilidad de la contraseña
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-ocultar mensajes de error después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.error-message');
            const successMessage = document.querySelector('.success-message');
            
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.style.transition = 'opacity 0.5s ease';
                    errorMessage.style.opacity = '0';
                    setTimeout(function() {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 5000); // 5 segundos
            }
            
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.transition = 'opacity 0.5s ease';
                    successMessage.style.opacity = '0';
                    setTimeout(function() {
                        successMessage.style.display = 'none';
                    }, 500);
                }, 3000); // 3 segundos para mensajes de éxito
            }
        });
    </script>

</body>
</html>
