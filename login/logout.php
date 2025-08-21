<?php
// login/logout.php
// Este archivo ahora está dentro de la carpeta 'login/'.
session_start();

// Destruir todas las variables de la sesión actual.
$_SESSION = array();

// Si la sesión usa cookies, es una buena práctica invalidar también la cookie de sesión.
// Esto ayuda a asegurar que la sesión se cierre completamente desde el lado del cliente.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Establecer el tiempo en el pasado para expirar la cookie
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finalmente, destruir la sesión en el servidor.
session_destroy();

// Redirigir al usuario de vuelta a la página de login de administradores.
// La ruta es directa porque 'login.php' está en la misma carpeta 'login/'.
header('Location: login.php');
exit(); // Terminar la ejecución del script.
?>
