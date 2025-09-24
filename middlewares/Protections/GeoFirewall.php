<?php
/**
 * 🔥 GeoFirewall - Control de Acceso Geográfico por IP
 * 
 * Middleware de seguridad que controla el acceso basado en geolocalización IP
 * utilizando la configuración de geo_database.json
 * 
 * @package Cyberhole\Middlewares\Protections
 * @author ManuelDev
 * @version 2.0
 * @since 2025-09-21
 */

class GeoFirewall 
{
    private $config;
    private $logPath;
    private $isEnabled;
    
    /**
     * Constructor - Carga configuración geográfica
     */
    public function __construct() 
    {
        $this->loadGeoConfig();
        
        // Configurar path de logs con fallback
        $configLogPath = $this->config['monitoring']['log_path'] ?? 'logs/geo_access.log';
        $this->logPath = __DIR__ . '/../../' . $configLogPath;
        
        $this->isEnabled = $this->config['geo_access_control']['enabled'] ?? true;
        
        // Crear directorio de logs si no existe
        $this->ensureLogDirectory();
        
        // Ahora sí podemos hacer log
        $this->logAccess("✅ Configuración geográfica cargada exitosamente", $this->getUserIP());
    }
    
    /**
     * Carga la configuración geográfica desde geo_database.json
     */
    private function loadGeoConfig(): void 
    {
        $configPath = __DIR__ . '/../data/geo_database.json';
        
        if (!file_exists($configPath)) {
            throw new Exception("❌ Archivo de configuración geográfica no encontrado: $configPath");
        }
        
        $configContent = file_get_contents($configPath);
        $this->config = json_decode($configContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("❌ Error al parsear configuración geográfica: " . json_last_error_msg());
        }
        
        // Log se hará después de que logPath esté inicializado
    }
    
    /**
     * Método principal - Verifica acceso geográfico
     * 
     * @return array Resultado de la verificación
     */
    public function verifyAccess(): array 
    {
        if (!$this->isEnabled) {
            return [
                'allowed' => true,
                'reason' => 'GeoFirewall deshabilitado',
                'ip' => $this->getUserIP(),
                'country' => 'N/A'
            ];
        }
        
        $userIP = $this->getUserIP();
        
        // 1. Verificar IPs de desarrollo (siempre permitidas)
        if ($this->isDevelopmentIP($userIP)) {
            $this->logAccess("✅ IP de desarrollo permitida", $userIP);
            return [
                'allowed' => true,
                'reason' => 'IP de desarrollo autorizada',
                'ip' => $userIP,
                'country' => 'DEV'
            ];
        }
        
        // 2. Verificar IPs bloqueadas
        if ($this->isBlockedIP($userIP)) {
            $this->logAccess("🚫 IP bloqueada por región de alto riesgo", $userIP, 'BLOCKED');
            return [
                'allowed' => false,
                'reason' => 'IP de región bloqueada por seguridad',
                'ip' => $userIP,
                'country' => 'BLOCKED'
            ];
        }
        
        // 3. Verificar IPs permitidas por país
        $countryCheck = $this->checkAllowedCountries($userIP);
        if ($countryCheck['found']) {
            $this->logAccess("✅ IP permitida desde {$countryCheck['country']}", $userIP);
            return [
                'allowed' => true,
                'reason' => "IP autorizada desde {$countryCheck['country']}",
                'ip' => $userIP,
                'country' => $countryCheck['country_code'],
                'priority' => $countryCheck['priority']
            ];
        }
        
        // 4. IP no autorizada - Denegar acceso
        $this->logAccess("🚫 IP no autorizada - Acceso denegado", $userIP, 'DENIED');
        
        return [
            'allowed' => false,
            'reason' => 'IP no autorizada geográficamente',
            'ip' => $userIP,
            'country' => 'UNKNOWN'
        ];
    }
    
    /**
     * Obtiene la IP real del usuario
     */
    private function getUserIP(): string 
    {
        // Prioridad de headers para obtener IP real
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_X_FORWARDED_FOR',     // Load balancer
            'HTTP_X_FORWARDED',         // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster
            'HTTP_CLIENT_IP',           // Proxy
            'REMOTE_ADDR'              // Directo
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Si contiene múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback a localhost para desarrollo
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Verifica si es una IP de desarrollo
     */
    private function isDevelopmentIP(string $ip): bool 
    {
        $devRanges = $this->config['development_ips']['ranges'] ?? [];
        
        // IPs específicas de desarrollo
        $devIPs = ['127.0.0.1', '::1', 'localhost'];
        if (in_array($ip, $devIPs)) {
            return true;
        }
        
        // Verificar rangos de desarrollo
        foreach ($devRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si es una IP bloqueada
     */
    private function isBlockedIP(string $ip): bool 
    {
        // Esta función se puede expandir para verificar contra listas de IPs bloqueadas
        // Por ahora retorna false ya que el bloqueo se hace por código de país
        return false;
    }
    
    /**
     * Verifica si la IP está en algún país permitido
     */
    private function checkAllowedCountries(string $ip): array 
    {
        $allowedCountries = $this->config['allowed_countries'] ?? [];
        
        foreach ($allowedCountries as $regionName => $regionData) {
            $countries = $regionData['countries'] ?? [];
            
            foreach ($countries as $countryCode => $countryInfo) {
                $ipRanges = $countryInfo['ip_ranges'] ?? [];
                
                foreach ($ipRanges as $range) {
                    if ($this->ipInRange($ip, $range)) {
                        return [
                            'found' => true,
                            'country' => $countryInfo['name'],
                            'country_code' => $countryCode,
                            'region' => $regionName,
                            'priority' => $countryInfo['priority'] ?? 'medium',
                            'language' => $countryInfo['language'] ?? 'unknown'
                        ];
                    }
                }
            }
        }
        
        return ['found' => false];
    }
    
    /**
     * Verifica si una IP está dentro de un rango CIDR
     */
    private function ipInRange(string $ip, string $range): bool 
    {
        // Manejar caso de localhost
        if ($range === 'localhost' && ($ip === '127.0.0.1' || $ip === '::1')) {
            return true;
        }
        
        // Verificar si es un rango CIDR válido
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        // Verificar IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4InRange($ip, $subnet, $mask);
        }
        
        // Verificar IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InRange($ip, $subnet, $mask);
        }
        
        return false;
    }
    
    /**
     * Verifica rango IPv4
     */
    private function ipv4InRange(string $ip, string $subnet, int $mask): bool 
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        
        $maskLong = -1 << (32 - $mask);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * Verifica rango IPv6
     */
    private function ipv6InRange(string $ip, string $subnet, int $mask): bool 
    {
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }
        
        $byteMask = intval($mask / 8);
        $bitMask = $mask % 8;
        
        // Comparar bytes completos
        if ($byteMask > 0 && substr($ipBin, 0, $byteMask) !== substr($subnetBin, 0, $byteMask)) {
            return false;
        }
        
        // Comparar bits restantes
        if ($bitMask > 0) {
            $mask = 0xFF << (8 - $bitMask);
            $ipByte = ord($ipBin[$byteMask]) & $mask;
            $subnetByte = ord($subnetBin[$byteMask]) & $mask;
            return $ipByte === $subnetByte;
        }
        
        return true;
    }
    
    /**
     * Registra eventos de acceso
     */
    private function logAccess(string $message, string $ip, string $level = 'INFO'): void 
    {
        if (!($this->config['access_rules']['log_all_access'] ?? true)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'ip' => $ip,
            'message' => $message,
            'user_agent' => $userAgent,
            'request_uri' => $requestUri,
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct'
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        
        try {
            file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("❌ Error escribiendo log geo: " . $e->getMessage());
        }
    }
    
    /**
     * Asegura que el directorio de logs existe
     */
    private function ensureLogDirectory(): void 
    {
        $logDir = dirname($this->logPath);
        
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                throw new Exception("❌ No se pudo crear directorio de logs: $logDir");
            }
        }
    }
    
    /**
     * Middleware principal - Bloquea acceso si no está autorizado
     */
    public function handle(): void 
    {
        $accessResult = $this->verifyAccess();
        
        if (!$accessResult['allowed']) {
            $this->denyAccess($accessResult);
        }
    }
    
    /**
     * Deniega el acceso y termina la ejecución
     */
    private function denyAccess(array $accessResult): void 
    {
        // Headers de seguridad
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Geo-Access: Denied');
        header('X-Geo-Reason: ' . $accessResult['reason']);
        
        // Respuesta JSON de error
        $response = [
            'error' => true,
            'code' => 403,
            'message' => 'Acceso denegado por ubicación geográfica',
            'details' => [
                'reason' => $accessResult['reason'],
                'ip' => $accessResult['ip'],
                'timestamp' => date('c'),
                'support' => 'Contacta al administrador si crees que esto es un error'
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Obtiene estadísticas de acceso
     */
    public function getAccessStats(): array 
    {
        if (!file_exists($this->logPath)) {
            return [
                'total_requests' => 0,
                'allowed' => 0,
                'denied' => 0,
                'countries' => []
            ];
        }
        
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [
            'total_requests' => 0,
            'allowed' => 0,
            'denied' => 0,
            'blocked' => 0,
            'countries' => [],
            'ips' => []
        ];
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            $stats['total_requests']++;
            
            if (strpos($entry['message'], '✅') !== false) {
                $stats['allowed']++;
            } elseif (strpos($entry['message'], '🚫') !== false) {
                if (strpos($entry['message'], 'bloqueada') !== false) {
                    $stats['blocked']++;
                } else {
                    $stats['denied']++;
                }
            }
            
            // Contar IPs únicas
            $ip = $entry['ip'] ?? 'unknown';
            $stats['ips'][$ip] = ($stats['ips'][$ip] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    /**
     * Método estático para uso rápido
     */
    public static function protect(): void 
    {
        $geoFirewall = new self();
        $geoFirewall->handle();
    }
    
    /**
     * Verifica si una IP específica estaría permitida (sin logging)
     */
    public function wouldAllowIP(string $ip): bool 
    {
        // Backup del estado actual
        $originalLogging = $this->config['access_rules']['log_all_access'] ?? true;
        
        // Deshabilitar logging temporalmente
        $this->config['access_rules']['log_all_access'] = false;
        
        // Simular verificación
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $ip;
        
        $result = $this->verifyAccess();
        
        // Restaurar estado
        $_SERVER['REMOTE_ADDR'] = $currentIP;
        $this->config['access_rules']['log_all_access'] = $originalLogging;
        
        return $result['allowed'];
    }
}

/**
 * 🛡️ Función helper para uso directo
 */
function geoFirewallProtect() {
    GeoFirewall::protect();
}

?>
