<?php
// config/path_config.php

// Medida de seguridad para prevenir el acceso directo al archivo.
if (!defined('ACCESS_ALLOWED')) {
    die('Acceso directo no permitido.');
}

/**
 * Inicializa las variables globales de ruta para la aplicación.
 * * Esta función calcula la URL base y el directorio base del proyecto,
 * y los almacena en variables globales para que puedan ser utilizadas
 * en toda la aplicación (headers, links, includes, etc.).
 */
function initializePathVariables() {
    // Usamos 'global' para poder modificar las variables fuera del ámbito de esta función.
    global $baseUrl, $baseDir, $current_page;

    // Determinar el protocolo (http o https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    
    // Obtener el host (ej. localhost, midominio.com)
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtener el nombre de la carpeta del proyecto.
    // Esto hace que el código sea más portable si cambias el nombre de la carpeta.
    $projectName = 'gestion_leche_web';
    
    // Construir la URL base completa
    $baseUrl = $protocol . $host . '/' . $projectName;

    // Construir la ruta del directorio base en el servidor
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $projectName;
    
    // Obtener el nombre del archivo actual que se está ejecutando
    $current_page = basename($_SERVER['PHP_SELF']);
}

/**
 * Devuelve la URL base previamente inicializada.
 * Es una función de ayuda por si se prefiere no usar la variable global directamente.
 * @return string La URL base de la aplicación.
 */
function getBaseUrl() {
    global $baseUrl;
    // Llama a la inicialización por si acaso no se ha hecho antes.
    if (!isset($baseUrl)) {
        initializePathVariables();
    }
    return $baseUrl;
}

?>
