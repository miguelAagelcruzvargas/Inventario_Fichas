<?php
// config/security_config.php
// Configuración de seguridad avanzada

class SecurityConfig {
    
    // Lista negra de IPs (añadir IPs maliciosas conocidas)
    public static $blacklistedIPs = [
        // Ejemplo: '192.168.1.100',
        // '10.0.0.5'
    ];
    
    // Lista blanca de IPs (IPs confiables que pueden saltarse algunas verificaciones)
    public static $whitelistedIPs = [
        '127.0.0.1',
        '::1'
    ];
    
    // Configuración de rate limiting
    public static $rateLimitConfig = [
        'max_attempts' => 5,
        'time_window' => 900, // 15 minutos
        'lockout_time' => 1800 // 30 minutos
    ];
    
    // User agents sospechosos
    public static $suspiciousUserAgents = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'burp', 'w3af', 
        'havij', 'beef', 'curl', 'wget', 'python-requests',
        'scanner', 'bot', 'crawler'
    ];
    
    // Patrones de inyección SQL
    public static $sqlInjectionPatterns = [
        '/(\bselect\b|\bunion\b|\binsert\b|\bdelete\b|\bupdate\b|\bdrop\b)/i',
        '/(--|#|\*|\/\*|\*\/)/i',
        '/(\bor\b|\band\b)\s*[\'"]?\w*[\'"]?\s*[=<>]/i',
        '/(\bexec\b|\bexecute\b|\bsp_\b)/i',
        '/(\bcast\b|\bconvert\b|\bsubstring\b|\bchar\b|\bascii\b)/i'
    ];
    
    // Verificar si una IP está en la lista negra
    public static function isBlacklisted($ip) {
        return in_array($ip, self::$blacklistedIPs);
    }
    
    // Verificar si una IP está en la lista blanca
    public static function isWhitelisted($ip) {
        return in_array($ip, self::$whitelistedIPs);
    }
    
    // Añadir IP a la lista negra temporalmente
    public static function addToBlacklist($ip) {
        if (!in_array($ip, self::$blacklistedIPs)) {
            self::$blacklistedIPs[] = $ip;
            // Guardar en archivo para persistencia
            file_put_contents('../logs/blacklisted_ips.log', $ip . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // Registrar evento de seguridad
    public static function logSecurityEvent($event, $ip, $details = '') {
        $logMessage = date('Y-m-d H:i:s') . " - [{$event}] IP: {$ip} - {$details}\n";
        error_log($logMessage, 3, '../logs/security_events.log');
    }
    
    // Aplicar headers de seguridad
    public static function applySecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src \'self\' https://fonts.gstatic.com https://cdnjs.cloudflare.com; script-src \'self\' \'unsafe-inline\';');
    }
    
    // Generar token CSRF
    public static function generateCSRFToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    // Validar token CSRF
    public static function validateCSRFToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verificar si el token ha expirado (1 hora)
        if ((time() - $_SESSION['csrf_token_time']) > 3600) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Validación de entrada
    public static function validateInput($input, $maxLength = 50) {
        if (empty($input)) return false;
        if (strlen($input) > $maxLength) return false;
        
        // Solo permitir caracteres alfanuméricos, guiones bajos y puntos
        if (!preg_match('/^[a-zA-Z0-9._]+$/', $input)) return false;
        
        return true;
    }
}
?>
