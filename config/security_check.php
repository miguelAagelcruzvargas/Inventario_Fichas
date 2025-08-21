<?php
// config/security_check.php
// Archivo de verificación de seguridad que aplica todas las verificaciones necesarias

require_once __DIR__ . '/security_middleware.php';

// Aplicar todas las verificaciones de seguridad
SecurityMiddleware::applySecurityChecks();
?>
