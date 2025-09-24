<?php
/**
 * 🛡️ HeaderHTTP - Middleware de Seguridad para Headers HTTP
 * 
 * Implementa headers de seguridad críticos para proteger contra:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME sniffing
 * - Ataques HTTPS downgrade
 * - Content injection
 * 
 * @package Cyberhole\Middlewares\Security
 * @author ManuelDev
 * @version 3.0
 * @since 2025-09-21
 */

class HeaderHTTP 
{
    private $config;
    private $isEnabled;
    private $logPath;
    
    /**
     * Configuración por defecto de headers de seguridad
     */
    private $defaultConfig = [
        'security_headers' => [
            'enabled' => true,
            'enforce_https' => true,
            'content_security_policy' => [
                'enabled' => true,
                'policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:; frame-src 'none'; object-src 'none'; base-uri 'self';",
                'report_only' => false
            ],
            'strict_transport_security' => [
                'enabled' => true,
                'max_age' => 31536000, // 1 año
                'include_subdomains' => true,
                'preload' => true
            ],
            'x_frame_options' => [
                'enabled' => true,
                'value' => 'DENY' // DENY, SAMEORIGIN, ALLOW-FROM
            ],
            'x_content_type_options' => [
                'enabled' => true,
                'value' => 'nosniff'
            ],
            'x_xss_protection' => [
                'enabled' => true,
                'value' => '1; mode=block'
            ],
            'referrer_policy' => [
                'enabled' => true,
                'value' => 'strict-origin-when-cross-origin'
            ],
            'permissions_policy' => [
                'enabled' => true,
                'policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
            ],
            'expect_ct' => [
                'enabled' => true,
                'max_age' => 86400, // 24 horas
                'enforce' => true
            ]
        ],
        'custom_headers' => [
            'X-Powered-By' => '', // Ocultar tecnología
            'Server' => 'Cyberhole-Security', // Información mínima
            'X-Content-Duration' => '30',
            'X-Request-ID' => 'auto-generate'
        ],
        'cors' => [
            'enabled' => false,
            'allowed_origins' => ['https://yourdomain.com'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'allow_credentials' => false,
            'max_age' => 3600
        ],
        'logging' => [
            'enabled' => true,
            'log_path' => 'logs/header_security.log',
            'log_violations' => true
        ]
    ];
    
    public function __construct(array $customConfig = []) 
    {
        $this->config = array_merge_recursive($this->defaultConfig, $customConfig);
        $this->isEnabled = $this->config['security_headers']['enabled'] ?? true;
        $this->logPath = __DIR__ . '/../../' . ($this->config['logging']['log_path'] ?? 'logs/header_security.log');
        
        $this->ensureLogDirectory();
        $this->logActivity("🛡️ HeaderHTTP middleware inicializado", 'INFO');
    }
    
    /**
     * Aplicar todos los headers de seguridad
     */
    public function apply(): void 
    {
        if (!$this->isEnabled) {
            return;
        }
        
        $this->applySecurityHeaders();
        $this->applyCustomHeaders();
        $this->applyCORSHeaders();
        $this->detectViolations();
        
        $this->logActivity("✅ Headers de seguridad aplicados", 'INFO');
    }
    
    /**
     * Aplicar headers de seguridad principales
     */
    private function applySecurityHeaders(): void 
    {
        $headers = $this->config['security_headers'];
        
        // Content Security Policy
        if ($headers['content_security_policy']['enabled']) {
            $cspHeader = $headers['content_security_policy']['report_only'] ? 
                'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            
            header($cspHeader . ': ' . $headers['content_security_policy']['policy']);
        }
        
        // Strict Transport Security (HTTPS)
        if ($headers['strict_transport_security']['enabled'] && $this->isHTTPS()) {
            $hstsValue = 'max-age=' . $headers['strict_transport_security']['max_age'];
            
            if ($headers['strict_transport_security']['include_subdomains']) {
                $hstsValue .= '; includeSubDomains';
            }
            
            if ($headers['strict_transport_security']['preload']) {
                $hstsValue .= '; preload';
            }
            
            header('Strict-Transport-Security: ' . $hstsValue);
        }
        
        // X-Frame-Options (Clickjacking protection)
        if ($headers['x_frame_options']['enabled']) {
            header('X-Frame-Options: ' . $headers['x_frame_options']['value']);
        }
        
        // X-Content-Type-Options (MIME sniffing protection)
        if ($headers['x_content_type_options']['enabled']) {
            header('X-Content-Type-Options: ' . $headers['x_content_type_options']['value']);
        }
        
        // X-XSS-Protection
        if ($headers['x_xss_protection']['enabled']) {
            header('X-XSS-Protection: ' . $headers['x_xss_protection']['value']);
        }
        
        // Referrer Policy
        if ($headers['referrer_policy']['enabled']) {
            header('Referrer-Policy: ' . $headers['referrer_policy']['value']);
        }
        
        // Permissions Policy
        if ($headers['permissions_policy']['enabled']) {
            header('Permissions-Policy: ' . $headers['permissions_policy']['policy']);
        }
        
        // Expect-CT
        if ($headers['expect_ct']['enabled']) {
            $expectCT = 'max-age=' . $headers['expect_ct']['max_age'];
            if ($headers['expect_ct']['enforce']) {
                $expectCT .= ', enforce';
            }
            header('Expect-CT: ' . $expectCT);
        }
    }
    
    /**
     * Aplicar headers personalizados
     */
    private function applyCustomHeaders(): void 
    {
        foreach ($this->config['custom_headers'] as $headerName => $headerValue) {
            if ($headerValue === 'auto-generate' && $headerName === 'X-Request-ID') {
                $headerValue = $this->generateRequestID();
            }
            
            if ($headerValue === '') {
                // Remover header (útil para X-Powered-By)
                header_remove($headerName);
            } else {
                header($headerName . ': ' . $headerValue);
            }
        }
    }
    
    /**
     * Aplicar headers CORS si están habilitados
     */
    private function applyCORSHeaders(): void 
    {
        if (!$this->config['cors']['enabled']) {
            return;
        }
        
        $cors = $this->config['cors'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Verificar origen permitido
        if (in_array($origin, $cors['allowed_origins']) || in_array('*', $cors['allowed_origins'])) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $cors['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $cors['allowed_headers']));
        header('Access-Control-Max-Age: ' . $cors['max_age']);
        
        if ($cors['allow_credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Manejar preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * Detectar violaciones de seguridad
     */
    private function detectViolations(): void 
    {
        $violations = [];
        
        // Detectar headers inseguros enviados por el cliente
        $dangerousHeaders = [
            'X-Forwarded-Host' => 'Host header injection attempt',
            'X-Original-URL' => 'URL override attempt',
            'X-Rewrite-URL' => 'URL rewrite attempt'
        ];
        
        foreach ($dangerousHeaders as $header => $description) {
            $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper($header));
            if (isset($_SERVER[$headerKey])) {
                $violations[] = [
                    'type' => 'dangerous_header',
                    'header' => $header,
                    'value' => $_SERVER[$headerKey],
                    'description' => $description
                ];
            }
        }
        
        // Detectar user agents sospechosos
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousPatterns = [
            '/sqlmap/i' => 'SQL injection tool',
            '/nikto/i' => 'Vulnerability scanner',
            '/nmap/i' => 'Network scanner',
            '/burp/i' => 'Security testing tool',
            '/curl.*bot/i' => 'Automated tool',
            '/python-requests/i' => 'Script-based request'
        ];
        
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $userAgent)) {
                $violations[] = [
                    'type' => 'suspicious_user_agent',
                    'user_agent' => $userAgent,
                    'pattern' => $pattern,
                    'description' => $description
                ];
            }
        }
        
        // Log violations
        if (!empty($violations) && $this->config['logging']['log_violations']) {
            foreach ($violations as $violation) {
                $this->logActivity("🚨 Violación detectada: " . json_encode($violation), 'WARNING');
            }
        }
    }
    
    /**
     * Forzar HTTPS
     */
    public function enforceHTTPS(): void 
    {
        if (!$this->config['security_headers']['enforce_https']) {
            return;
        }
        
        if (!$this->isHTTPS()) {
            $httpsURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            $this->logActivity("🔒 Redirigiendo a HTTPS: $httpsURL", 'INFO');
            
            header('Location: ' . $httpsURL, true, 301);
            exit;
        }
    }
    
    /**
     * Verificar si la conexión es HTTPS
     */
    private function isHTTPS(): bool 
    {
        return (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        );
    }
    
    /**
     * Generar ID único de request
     */
    private function generateRequestID(): string 
    {
        return 'req_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
    }
    
    /**
     * Obtener información de seguridad actual
     */
    public function getSecurityInfo(): array 
    {
        return [
            'https_enabled' => $this->isHTTPS(),
            'headers_applied' => $this->isEnabled,
            'request_id' => $this->generateRequestID(),
            'ip_address' => $this->getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('c'),
            'security_score' => $this->calculateSecurityScore()
        ];
    }
    
    /**
     * Calcular puntuación de seguridad
     */
    private function calculateSecurityScore(): int 
    {
        $score = 0;
        
        // HTTPS (+30 puntos)
        if ($this->isHTTPS()) $score += 30;
        
        // Headers de seguridad (+10 puntos cada uno)
        $securityHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options', 
            'X-XSS-Protection',
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'Referrer-Policy'
        ];
        
        foreach ($securityHeaders as $header) {
            if ($this->isHeaderSet($header)) $score += 10;
        }
        
        // Sin headers peligrosos (+10 puntos)
        $dangerousHeaders = ['X-Powered-By', 'Server'];
        $hasDangerousHeaders = false;
        foreach ($dangerousHeaders as $header) {
            if ($this->isHeaderSet($header) && $this->getHeaderValue($header) !== 'Cyberhole-Security') {
                $hasDangerousHeaders = true;
                break;
            }
        }
        if (!$hasDangerousHeaders) $score += 10;
        
        return min($score, 100); // Máximo 100 puntos
    }
    
    /**
     * Verificar si un header está configurado
     */
    private function isHeaderSet(string $headerName): bool 
    {
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, $headerName . ':') === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtener valor de un header
     */
    private function getHeaderValue(string $headerName): ?string 
    {
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, $headerName . ':') === 0) {
                return trim(substr($header, strlen($headerName) + 1));
            }
        }
        return null;
    }
    
    /**
     * Obtener IP del usuario
     */
    private function getUserIP(): string 
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Log de actividades
     */
    private function logActivity(string $message, string $level = 'INFO'): void 
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'ip' => $this->getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        
        try {
            file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("❌ Error escribiendo log HeaderHTTP: " . $e->getMessage());
        }
    }
    
    /**
     * Crear directorio de logs
     */
    private function ensureLogDirectory(): void 
    {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Método estático para aplicación rápida
     */
    public static function secure(array $config = []): void 
    {
        $headerHTTP = new self($config);
        $headerHTTP->enforceHTTPS();
        $headerHTTP->apply();
    }
    
    /**
     * Generar reporte de seguridad
     */
    public function generateSecurityReport(): array 
    {
        return [
            'security_info' => $this->getSecurityInfo(),
            'configuration' => [
                'headers_enabled' => $this->isEnabled,
                'https_enforced' => $this->config['security_headers']['enforce_https'],
                'csp_enabled' => $this->config['security_headers']['content_security_policy']['enabled'],
                'cors_enabled' => $this->config['cors']['enabled']
            ],
            'recommendations' => $this->getSecurityRecommendations()
        ];
    }
    
    /**
     * Obtener recomendaciones de seguridad
     */
    private function getSecurityRecommendations(): array 
    {
        $recommendations = [];
        
        if (!$this->isHTTPS()) {
            $recommendations[] = '🔒 Habilitar HTTPS para máxima seguridad';
        }
        
        if (!$this->config['security_headers']['content_security_policy']['enabled']) {
            $recommendations[] = '🛡️ Habilitar Content Security Policy (CSP)';
        }
        
        if ($this->config['security_headers']['content_security_policy']['report_only']) {
            $recommendations[] = '⚠️ Cambiar CSP de report-only a enforcement';
        }
        
        $securityScore = $this->calculateSecurityScore();
        if ($securityScore < 80) {
            $recommendations[] = "📊 Mejorar puntuación de seguridad (actual: $securityScore/100)";
        }
        
        return $recommendations;
    }
}

/**
 * 🛡️ Función helper para aplicación rápida
 */
function applySecureHeaders(array $config = []) {
    HeaderHTTP::secure($config);
}

?>
