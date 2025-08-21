<?php
// config/security_middleware.php
// Middleware de seguridad para todas las páginas

require_once 'security_config.php';

class SecurityMiddleware {
    
    // Aplicar headers de seguridad
    public static function applySecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Solo aplicar HSTS si está en HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src \'self\' https://fonts.gstatic.com https://cdnjs.cloudflare.com; script-src \'self\' \'unsafe-inline\'; object-src \'none\'; base-uri \'self\'; form-action \'self\';');
    }
    
    // Configurar sesión segura
    public static function configureSecureSession() {
        // Solo configurar si no hay sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.name', 'SECURE_SESS_ID');
        } else {
            // Si la sesión ya está activa, aplicar configuraciones que sí se pueden cambiar
            if (isset($_SERVER['HTTPS'])) {
                ini_set('session.cookie_secure', 1);
            }
        }
    }
    
    // Verificar si la IP está bloqueada
    public static function checkIPBlacklist() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if (SecurityConfig::isBlacklisted($ip)) {
            SecurityConfig::logSecurityEvent('BLOCKED_IP', $ip, 'Access denied - IP in blacklist');
            http_response_code(403);
            die('Access denied.');
        }
    }
    
    // Detectar actividad sospechosa
    public static function detectSuspiciousActivity() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Si la IP está en lista blanca, saltar verificaciones
        if (SecurityConfig::isWhitelisted($ip)) {
            return false;
        }
        
        // Verificar user-agent sospechoso
        foreach (SecurityConfig::$suspiciousUserAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                SecurityConfig::logSecurityEvent('SUSPICIOUS_AGENT', $ip, "User-Agent: {$userAgent}");
                return true;
            }
        }
        
        // Verificar si no hay user-agent
        if (empty($userAgent)) {
            SecurityConfig::logSecurityEvent('NO_USER_AGENT', $ip, 'Missing User-Agent header');
            return true;
        }
        
        return false;
    }
    
    // Aplicar todas las verificaciones de seguridad
    public static function applySecurityChecks() {
        self::applySecurityHeaders();
        self::configureSecureSession();
        self::checkIPBlacklist();
        
        if (self::detectSuspiciousActivity()) {
            http_response_code(403);
            die('Suspicious activity detected.');
        }
    }
    
    // Limpiar y validar datos de entrada
    public static function sanitizeInput($input, $type = 'string', $maxLength = 255) {
        if (empty($input)) return null;
        
        // Eliminar caracteres de control
        $input = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $input);
        $input = trim($input);
        
        // Verificar longitud
        if (strlen($input) > $maxLength) {
            return false;
        }
        
        // Validar según tipo
        switch ($type) {
            case 'username':
                return preg_match('/^[a-zA-Z0-9._-]+$/', $input) ? $input : false;
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9]+$/', $input) ? $input : false;
            default:
                // Verificar patrones de inyección SQL
                foreach (SecurityConfig::$sqlInjectionPatterns as $pattern) {
                    if (preg_match($pattern, $input)) {
                        SecurityConfig::logSecurityEvent('SQL_INJECTION_ATTEMPT', $_SERVER['REMOTE_ADDR'] ?? '', "Input: {$input}");
                        return false;
                    }
                }
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
