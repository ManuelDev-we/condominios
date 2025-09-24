<?php
/**
 * 🚦 RateLimiter - Middleware de Límite de Solicitudes con Integración Geográfica
 * 
 * Protege contra ataques DoS/DDoS y detecta bots mediante:
 * - Límites de solicitudes por IP
 * - Detección de patrones de bots
 * - Análisis de comportamiento sospechoso
 * - Sistema de penalizaciones dinámicas
 * - Integración con GeoFirewall para control geográfico
 * - Whitelist automático solo para usuarios legítimos verificados
 * 
 * @package Cyberhole\Middlewares\Security
 * @author ManuelDev
 * @version 3.2
 * @since 2025-09-21
 */

// Cargar sistema de autoload PSR-4
require_once __DIR__ . '/AutoLoader.php';

class RateLimiter 
{
    private $config;
    private $cacheFile;
    private $logPath;
    private $requestData;
    private $geoFirewall;
    
    /**
     * Configuración por defecto
     */
    private $defaultConfig = [
        'rate_limiting' => [
            'enabled' => true,
            'default_limit' => 100, // requests por ventana
            'window_seconds' => 3600, // 1 hora
            'burst_limit' => 20, // requests rápidas permitidas
            'burst_window' => 60, // ventana de burst en segundos
            'geo_adjusted_limits' => true, // Ajustar límites según geolocalización
            'priority_multipliers' => [
                'high' => 2.0,   // Países de alta prioridad (2x límites)
                'medium' => 1.5, // Países de media prioridad (1.5x límites)
                'low' => 1.0     // Países de baja prioridad (límites normales)
            ]
        ],
        'bot_detection' => [
            'enabled' => true,
            'geo_enhanced' => true, // Usar datos geográficos para detección
            'suspicious_patterns' => [
                'rapid_requests' => 10, // >10 requests en 10 segundos
                'identical_user_agent' => 50, // >50 requests con mismo UA
                'no_user_agent' => true, // Sin User-Agent
                'automated_tools' => ['curl', 'wget', 'python', 'requests', 'bot', 'crawler', 'scraper'],
                'suspicious_extensions' => ['.xml', '.json', '.txt', '.log', '.bak'],
                'geo_anomalies' => true // Detectar anomalías geográficas
            ],
            'penalties' => [
                'warning' => 1.5, // multiplicador de tiempo de bloqueo
                'suspicious' => 3.0,
                'confirmed_bot' => 10.0,
                'attack' => 24.0, // horas de bloqueo
                'geo_violation' => 48.0 // Violación geográfica
            ]
        ],
        'whitelist' => [
            'enabled' => true,
            'ips' => [
                // ELIMINADAS todas las excepciones de localhost y redes privadas
                // Solo bots legítimos específicos si es absolutamente necesario
            ],
            'user_agents' => [
                'Googlebot', 'Bingbot', 'facebookexternalhit'
            ],
            'auto_whitelist' => true, // Whitelist automático para usuarios legítimos
            'auto_criteria' => [
                'min_sessions' => 5,
                'min_different_pages' => 10,
                'human_behavior_score' => 8.0,
                'geo_verification' => true, // Verificar consistencia geográfica
                'min_time_span' => 3600 // Mínimo 1 hora de actividad
            ]
        ],
        'geo_integration' => [
            'enabled' => true,
            'block_geo_denied' => true, // Bloquear si GeoFirewall niega acceso
            'priority_boost' => true, // Dar prioridad a países autorizados
            'log_geo_events' => true
        ],
        'storage' => [
            'cache_file' => 'cache/rate_limiter.json',
            'cleanup_interval' => 3600, // limpiar cada hora
            'max_entries' => 10000
        ],
        'responses' => [
            'blocked_message' => 'Demasiadas solicitudes. Por favor, espera antes de intentar nuevamente.',
            'bot_detected_message' => 'Comportamiento automatizado detectado. Acceso temporal suspendido.',
            'geo_blocked_message' => 'Acceso no autorizado desde tu ubicación geográfica.',
            'json_response' => true,
            'http_code' => 429
        ],
        'logging' => [
            'enabled' => true,
            'log_path' => 'logs/rate_limiter.log',
            'log_blocked' => true,
            'log_bots' => true,
            'log_geo_events' => true,
            'log_stats' => true
        ]
    ];
    
    public function __construct(array $customConfig = []) 
    {
        // Merge configuración de forma inteligente
        $this->config = $this->mergeConfigSafely($this->defaultConfig, $customConfig);
        
        // Configurar rutas de archivos de forma segura
        $cacheFile = $this->config['storage']['cache_file'] ?? 'cache/rate_limiter_cache.json';
        $logPath = $this->config['logging']['log_path'] ?? 'logs/rate_limiter.log';
        
        $this->cacheFile = __DIR__ . '/../../' . $cacheFile;
        $this->logPath = __DIR__ . '/../../' . $logPath;
        
        // Inicializar autoloader PSR-4
        $autoloader = MiddlewareAutoloader::getInstance();
        
        // Inicializar GeoFirewall usando PSR-4 si está habilitado
        if ($this->config['geo_integration']['enabled']) {
            try {
                // Cargar GeoFirewall usando autoloader
                if ($autoloader->loadClass('GeoFirewall')) {
                    $this->geoFirewall = new GeoFirewall();
                    $this->logActivity("🌍 GeoFirewall integrado exitosamente via PSR-4", 'INFO');
                } else {
                    throw new Exception("No se pudo cargar GeoFirewall via PSR-4");
                }
            } catch (Exception $e) {
                $this->logActivity("⚠️ Error integrando GeoFirewall: " . $e->getMessage(), 'WARNING');
                $this->geoFirewall = null;
            }
        }
        
        $this->ensureDirectories();
        $this->loadRequestData();
        $this->cleanup();
        
        $this->logActivity("🚦 RateLimiter middleware inicializado con integración geográfica PSR-4", 'INFO');
    }
    
    /**
     * Merge configuración de forma segura evitando conversiones de arrays
     */
    private function mergeConfigSafely(array $default, array $custom): array
    {
        $result = $default;
        
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = $this->mergeConfigSafely($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Verificar límites de rate limiting con integración geográfica
     */
    public function checkLimits(): array 
    {
        if (!$this->config['rate_limiting']['enabled']) {
            return ['allowed' => true, 'reason' => 'Rate limiting deshabilitado'];
        }
        
        $ip = $this->getUserIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $currentTime = time();
        
        // 1. VERIFICACIÓN GEOGRÁFICA PRIMERO
        $geoResult = $this->verifyGeoAccess($ip);
        if (!$geoResult['allowed']) {
            $this->penalizeIP($ip, 'geo_violation', $geoResult['reason'], 
                $this->config['bot_detection']['penalties']['geo_violation']);
            return [
                'allowed' => false,
                'reason' => $this->config['responses']['geo_blocked_message'],
                'geo_reason' => $geoResult['reason'],
                'type' => 'geo_blocked'
            ];
        }
        
        // 2. Verificar whitelist (SIN excepciones de localhost)
        if ($this->isWhitelisted($ip, $userAgent)) {
            $this->logActivity("✅ IP whitelisteada por comportamiento legítimo: $ip", 'INFO');
            return ['allowed' => true, 'reason' => 'IP en whitelist verificada'];
        }
        
        // 3. Verificar si está bloqueado
        $blockStatus = $this->checkBlockStatus($ip);
        if ($blockStatus['blocked']) {
            $this->logActivity("🚫 IP bloqueada: $ip - {$blockStatus['reason']}", 'WARNING');
            return [
                'allowed' => false,
                'reason' => $blockStatus['reason'],
                'blocked_until' => $blockStatus['blocked_until'],
                'type' => 'blocked'
            ];
        }
        
        // 4. Obtener datos de la IP y ajustar límites por geolocalización
        $ipData = $this->getIPData($ip);
        $adjustedLimits = $this->getGeoAdjustedLimits($geoResult);
        
        // 5. Verificar límite de burst (ajustado geográficamente)
        $burstViolation = $this->checkBurstLimit($ipData, $currentTime, $adjustedLimits);
        if ($burstViolation) {
            $this->penalizeIP($ip, 'burst_limit', 'Límite de solicitudes rápidas excedido');
            return [
                'allowed' => false,
                'reason' => 'Demasiadas solicitudes en poco tiempo',
                'type' => 'burst_limit'
            ];
        }
        
        // 6. Verificar límite por ventana (ajustado geográficamente)
        $windowViolation = $this->checkWindowLimit($ipData, $currentTime, $adjustedLimits);
        if ($windowViolation) {
            $this->penalizeIP($ip, 'window_limit', 'Límite de solicitudes por hora excedido');
            return [
                'allowed' => false,
                'reason' => 'Límite de solicitudes por hora excedido',
                'type' => 'window_limit'
            ];
        }
        
        // 7. Verificar comportamiento de bot (mejorado con datos geográficos)
        $botCheck = $this->checkBotBehavior($ip, $userAgent, $ipData, $geoResult);
        if ($botCheck['is_bot']) {
            $penalty = $this->config['bot_detection']['penalties'][$botCheck['severity']] ?? 1.0;
            $this->penalizeIP($ip, 'bot_detected', "Bot detectado: {$botCheck['reason']}", $penalty);
            
            return [
                'allowed' => false,
                'reason' => $this->config['responses']['bot_detected_message'],
                'bot_reason' => $botCheck['reason'],
                'type' => 'bot_detected'
            ];
        }
        
        // 8. Registrar solicitud válida con datos geográficos
        $this->recordRequest($ip, $userAgent, $currentTime, $geoResult);
        
        // 9. Verificar si califica para auto-whitelist
        $this->checkAutoWhitelist($ip, $ipData, $geoResult);
        
        return [
            'allowed' => true, 
            'reason' => 'Solicitud dentro de límites',
            'geo_country' => $geoResult['country'] ?? 'Unknown',
            'adjusted_limits' => $adjustedLimits
        ];
    }
    
    /**
     * Verificar acceso geográfico usando GeoFirewall
     */
    private function verifyGeoAccess(string $ip): array 
    {
        if (!$this->geoFirewall || !$this->config['geo_integration']['enabled']) {
            return ['allowed' => true, 'reason' => 'Verificación geográfica deshabilitada'];
        }
        
        try {
            $geoResult = $this->geoFirewall->verifyAccess();
            
            if ($this->config['geo_integration']['log_geo_events']) {
                $status = $geoResult['allowed'] ? '✅' : '🚫';
                $this->logActivity("$status Verificación geográfica: {$geoResult['reason']}", 'INFO');
            }
            
            return $geoResult;
        } catch (Exception $e) {
            $this->logActivity("❌ Error en verificación geográfica: " . $e->getMessage(), 'ERROR');
            return ['allowed' => true, 'reason' => 'Error en verificación geográfica'];
        }
    }
    
    /**
     * Obtener límites ajustados por geolocalización
     */
    private function getGeoAdjustedLimits(array $geoResult): array 
    {
        $baseLimits = [
            'burst_limit' => $this->config['rate_limiting']['burst_limit'],
            'window_limit' => $this->config['rate_limiting']['default_limit']
        ];
        
        if (!$this->config['rate_limiting']['geo_adjusted_limits'] || !isset($geoResult['priority'])) {
            return $baseLimits;
        }
        
        $priority = $geoResult['priority'] ?? 'low';
        $multiplier = $this->config['rate_limiting']['priority_multipliers'][$priority] ?? 1.0;
        
        return [
            'burst_limit' => (int)($baseLimits['burst_limit'] * $multiplier),
            'window_limit' => (int)($baseLimits['window_limit'] * $multiplier)
        ];
    }
    
    /**
     * Verificar si una IP está en whitelist (SIN excepciones de localhost)
     */
    private function isWhitelisted(string $ip, string $userAgent): bool 
    {
        if (!$this->config['whitelist']['enabled']) {
            return false;
        }
        
        // ELIMINADO: Verificación de localhost y redes privadas por seguridad
        
        // Verificar IPs específicamente whitelisteadas (solo bots legítimos)
        foreach ($this->config['whitelist']['ips'] as $whitelistIP) {
            if ($this->ipInRange($ip, $whitelistIP)) {
                return true;
            }
        }
        
        // Verificar User Agents whitelisteados (bots de motores de búsqueda)
        foreach ($this->config['whitelist']['user_agents'] as $whitelistUA) {
            if (stripos($userAgent, $whitelistUA) !== false) {
                return true;
            }
        }
        
        // Verificar auto-whitelist (usuarios legítimos verificados)
        if (isset($this->requestData['whitelist'][$ip])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar estado de bloqueo
     */
    private function checkBlockStatus(string $ip): array 
    {
        if (!isset($this->requestData['blocked'][$ip])) {
            return ['blocked' => false];
        }
        
        $blockData = $this->requestData['blocked'][$ip];
        $currentTime = time();
        
        if ($blockData['blocked_until'] > $currentTime) {
            return [
                'blocked' => true,
                'reason' => $blockData['reason'],
                'blocked_until' => $blockData['blocked_until']
            ];
        }
        
        // Bloqueo expirado, remover
        unset($this->requestData['blocked'][$ip]);
        $this->saveRequestData();
        
        return ['blocked' => false];
    }
    
    /**
     * Verificar límite de burst con límites ajustados
     */
    private function checkBurstLimit(array $ipData, int $currentTime, array $adjustedLimits): bool 
    {
        $burstWindow = $this->config['rate_limiting']['burst_window'];
        $burstLimit = $adjustedLimits['burst_limit'];
        
        $recentRequests = 0;
        foreach ($ipData['requests'] ?? [] as $timestamp) {
            if ($timestamp > ($currentTime - $burstWindow)) {
                $recentRequests++;
            }
        }
        
        return $recentRequests >= $burstLimit;
    }
    
    /**
     * Verificar límite por ventana con límites ajustados
     */
    private function checkWindowLimit(array $ipData, int $currentTime, array $adjustedLimits): bool 
    {
        $windowSeconds = $this->config['rate_limiting']['window_seconds'];
        $windowLimit = $adjustedLimits['window_limit'];
        
        $windowStart = $currentTime - $windowSeconds;
        $requestsInWindow = 0;
        
        foreach ($ipData['requests'] ?? [] as $timestamp) {
            if ($timestamp > $windowStart) {
                $requestsInWindow++;
            }
        }
        
        return $requestsInWindow >= $windowLimit;
    }
    
    /**
     * Verificar comportamiento de bot con datos geográficos
     */
    private function checkBotBehavior(string $ip, string $userAgent, array $ipData, array $geoResult): array 
    {
        if (!$this->config['bot_detection']['enabled']) {
            return ['is_bot' => false];
        }
        
        $patterns = $this->config['bot_detection']['suspicious_patterns'];
        
        // Sin User-Agent
        if (empty($userAgent) && $patterns['no_user_agent']) {
            return [
                'is_bot' => true,
                'reason' => 'Sin User-Agent',
                'severity' => 'suspicious'
            ];
        }
        
        // Herramientas automatizadas
        foreach ($patterns['automated_tools'] as $tool) {
            if (stripos($userAgent, $tool) !== false) {
                return [
                    'is_bot' => true,
                    'reason' => "Herramienta automatizada: $tool",
                    'severity' => 'confirmed_bot'
                ];
            }
        }
        
        // Anomalías geográficas (nuevo con GeoFirewall)
        if ($patterns['geo_anomalies'] && $this->config['bot_detection']['geo_enhanced']) {
            $geoAnomaly = $this->detectGeoAnomalies($ipData, $geoResult);
            if ($geoAnomaly) {
                return [
                    'is_bot' => true,
                    'reason' => "Anomalía geográfica detectada: {$geoAnomaly}",
                    'severity' => 'suspicious'
                ];
            }
        }
        
        // Solicitudes idénticas
        $identicalUACount = 0;
        foreach ($ipData['user_agents'] ?? [] as $ua => $count) {
            if ($ua === $userAgent) {
                $identicalUACount = $count;
                break;
            }
        }
        
        if ($identicalUACount > $patterns['identical_user_agent']) {
            return [
                'is_bot' => true,
                'reason' => "Demasiadas solicitudes con User-Agent idéntico ($identicalUACount)",
                'severity' => 'confirmed_bot'
            ];
        }
        
        // Extensiones sospechosas
        $requestURI = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($patterns['suspicious_extensions'] as $ext) {
            if (substr($requestURI, -strlen($ext)) === $ext) {
                return [
                    'is_bot' => true,
                    'reason' => "Acceso a archivo sospechoso: $ext",
                    'severity' => 'suspicious'
                ];
            }
        }
        
        // Solicitudes muy rápidas
        $currentTime = time();
        $rapidRequests = 0;
        foreach ($ipData['requests'] ?? [] as $timestamp) {
            if ($timestamp > ($currentTime - 10)) { // últimos 10 segundos
                $rapidRequests++;
            }
        }
        
        if ($rapidRequests > $patterns['rapid_requests']) {
            return [
                'is_bot' => true,
                'reason' => "Solicitudes muy rápidas ($rapidRequests en 10 segundos)",
                'severity' => 'attack'
            ];
        }
        
        return ['is_bot' => false];
    }
    
    /**
     * Detectar anomalías geográficas
     */
    private function detectGeoAnomalies(array $ipData, array $geoResult): ?string 
    {
        // Verificar cambios rápidos de país (posible uso de VPN/Proxy)
        $currentCountry = $geoResult['country'] ?? 'Unknown';
        $recentCountries = $ipData['recent_countries'] ?? [];
        
        if (count($recentCountries) > 2) {
            $uniqueCountries = array_unique(array_values($recentCountries));
            if (count($uniqueCountries) > 2) {
                return "Múltiples países en corto tiempo: " . implode(', ', $uniqueCountries);
            }
        }
        
        return null;
    }
    
    /**
     * Penalizar IP
     */
    private function penalizeIP(string $ip, string $type, string $reason, float $multiplier = 1.0): void 
    {
        $baseBlockTime = 300; // 5 minutos base
        $blockDuration = $baseBlockTime * $multiplier;
        $blockedUntil = time() + $blockDuration;
        
        $this->requestData['blocked'][$ip] = [
            'reason' => $reason,
            'type' => $type,
            'blocked_at' => time(),
            'blocked_until' => $blockedUntil,
            'multiplier' => $multiplier
        ];
        
        $this->saveRequestData();
        
        if ($this->config['logging']['log_blocked']) {
            $this->logActivity("🚫 IP penalizada: $ip - $reason (bloqueo: " . round($blockDuration/60, 1) . " min)", 'WARNING');
        }
    }
    
    /**
     * Registrar solicitud válida con datos geográficos
     */
    private function recordRequest(string $ip, string $userAgent, int $currentTime, array $geoResult): void 
    {
        // Inicializar datos de IP si no existen
        if (!isset($this->requestData['ips'][$ip])) {
            $this->requestData['ips'][$ip] = [
                'requests' => [],
                'user_agents' => [],
                'pages' => [],
                'countries' => [],
                'recent_countries' => [],
                'first_seen' => $currentTime,
                'last_seen' => $currentTime,
                'total_requests' => 0,
                'human_score' => 5.0,
                'geo_consistency' => 0.0
            ];
        }
        
        $ipData = &$this->requestData['ips'][$ip];
        
        // Registrar solicitud
        $ipData['requests'][] = $currentTime;
        $ipData['last_seen'] = $currentTime;
        $ipData['total_requests']++;
        
        // Registrar User-Agent
        if (!isset($ipData['user_agents'][$userAgent])) {
            $ipData['user_agents'][$userAgent] = 0;
        }
        $ipData['user_agents'][$userAgent]++;
        
        // Registrar página
        $page = $_SERVER['REQUEST_URI'] ?? '/';
        if (!isset($ipData['pages'][$page])) {
            $ipData['pages'][$page] = 0;
        }
        $ipData['pages'][$page]++;
        
        // Registrar datos geográficos
        $country = $geoResult['country'] ?? 'Unknown';
        if (!isset($ipData['countries'][$country])) {
            $ipData['countries'][$country] = 0;
        }
        $ipData['countries'][$country]++;
        
        // Mantener historial reciente de países (últimas 10 solicitudes)
        $ipData['recent_countries'][] = $country;
        if (count($ipData['recent_countries']) > 10) {
            array_shift($ipData['recent_countries']);
        }
        
        // Calcular puntuación humana y consistencia geográfica
        $this->calculateHumanScore($ipData);
        $this->calculateGeoConsistency($ipData);
        
        // Limpiar datos antiguos
        $this->cleanupIPData($ipData, $currentTime);
        
        $this->saveRequestData();
    }
    
    /**
     * Calcular consistencia geográfica
     */
    private function calculateGeoConsistency(array &$ipData): void 
    {
        $countries = $ipData['countries'] ?? [];
        if (empty($countries)) {
            $ipData['geo_consistency'] = 0.0;
            return;
        }
        
        $totalRequests = array_sum($countries);
        $dominantCountry = max($countries);
        
        // Consistencia = porcentaje del país dominante
        $ipData['geo_consistency'] = ($dominantCountry / $totalRequests) * 10.0;
    }
    
    /**
     * Calcular puntuación de comportamiento humano mejorada
     */
    private function calculateHumanScore(array &$ipData): void 
    {
        $score = 5.0; // Base neutral
        
        // Diversidad de User-Agents (+)
        $uaCount = count($ipData['user_agents']);
        if ($uaCount > 1) $score += min($uaCount * 0.5, 2.0);
        
        // Diversidad de páginas (+)
        $pageCount = count($ipData['pages']);
        if ($pageCount > 5) $score += min($pageCount * 0.1, 2.0);
        
        // Consistencia temporal (+)
        $timeSpan = $ipData['last_seen'] - $ipData['first_seen'];
        if ($timeSpan > 300) $score += min($timeSpan / 3600, 2.0);
        
        // Consistencia geográfica (+) - NUEVO
        $geoConsistency = $ipData['geo_consistency'] ?? 0;
        if ($geoConsistency > 7.0) $score += 1.0;
        
        // Comportamiento de navegación (+)
        $avgPageViews = $ipData['total_requests'] / max($pageCount, 1);
        if ($avgPageViews < 10) $score += 1.0;
        
        // Penalizaciones (-)
        $dominantUA = max($ipData['user_agents']);
        if ($dominantUA / $ipData['total_requests'] > 0.95) $score -= 2.0;
        
        // Penalización por inconsistencia geográfica (-)
        if ($geoConsistency < 3.0 && count($ipData['countries']) > 3) $score -= 2.0;
        
        $ipData['human_score'] = max(0, min(10, $score));
    }
    
    /**
     * Verificar criterios para auto-whitelist mejorados
     */
    private function checkAutoWhitelist(string $ip, array $ipData, array $geoResult): void 
    {
        if (!$this->config['whitelist']['auto_whitelist']) {
            return;
        }
        
        $criteria = $this->config['whitelist']['auto_criteria'];
        $sessions = $this->estimateSessions($ipData);
        $differentPages = count($ipData['pages'] ?? []);
        $humanScore = $ipData['human_score'] ?? 0;
        $timeSpan = ($ipData['last_seen'] ?? time()) - ($ipData['first_seen'] ?? time());
        $geoConsistency = $ipData['geo_consistency'] ?? 0;
        
        // Verificar criterios básicos
        $meetsBasicCriteria = (
            $sessions >= $criteria['min_sessions'] && 
            $differentPages >= $criteria['min_different_pages'] && 
            $humanScore >= $criteria['human_behavior_score'] &&
            $timeSpan >= $criteria['min_time_span']
        );
        
        // Verificar consistencia geográfica si está habilitada
        $meetsGeoCriteria = true;
        if ($criteria['geo_verification']) {
            $meetsGeoCriteria = $geoConsistency >= 7.0; // Alta consistencia geográfica
        }
        
        if ($meetsBasicCriteria && $meetsGeoCriteria) {
            $this->requestData['whitelist'][$ip] = [
                'added_at' => time(),
                'reason' => 'Comportamiento humano consistente verificado',
                'sessions' => $sessions,
                'pages' => $differentPages,
                'human_score' => $humanScore,
                'geo_consistency' => $geoConsistency,
                'country' => $geoResult['country'] ?? 'Unknown'
            ];
            
            $this->saveRequestData();
            $this->logActivity("✅ IP auto-whitelisteada: $ip (score: $humanScore, geo: $geoConsistency)", 'INFO');
        }
    }
    
    /**
     * Estimar número de sesiones
     */
    private function estimateSessions(array $ipData): int 
    {
        $requests = $ipData['requests'] ?? [];
        if (empty($requests)) return 0;
        
        sort($requests);
        $sessions = 1;
        $sessionTimeout = 1800; // 30 minutos
        
        for ($i = 1; $i < count($requests); $i++) {
            if ($requests[$i] - $requests[$i-1] > $sessionTimeout) {
                $sessions++;
            }
        }
        
        return $sessions;
    }
    
    /**
     * Limpiar datos antiguos de IP
     */
    private function cleanupIPData(array &$ipData, int $currentTime): void 
    {
        $maxAge = $this->config['rate_limiting']['window_seconds'] * 2;
        $cutoff = $currentTime - $maxAge;
        
        // Limpiar requests antiguos
        $ipData['requests'] = array_filter($ipData['requests'], function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Reindexar array
        $ipData['requests'] = array_values($ipData['requests']);
    }
    
    /**
     * Verificar si IP está en rango
     */
    private function ipInRange(string $ip, string $range): bool 
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            
            $maskLong = -1 << (32 - $mask);
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }
        
        return false;
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
     * Obtener datos de IP
     */
    private function getIPData(string $ip): array 
    {
        return $this->requestData['ips'][$ip] ?? [];
    }
    
    /**
     * Cargar datos de solicitudes
     */
    private function loadRequestData(): void 
    {
        if (file_exists($this->cacheFile)) {
            $data = json_decode(file_get_contents($this->cacheFile), true);
            $this->requestData = $data ?: ['ips' => [], 'blocked' => [], 'whitelist' => []];
        } else {
            $this->requestData = ['ips' => [], 'blocked' => [], 'whitelist' => []];
        }
    }
    
    /**
     * Guardar datos de solicitudes
     */
    private function saveRequestData(): void 
    {
        file_put_contents($this->cacheFile, json_encode($this->requestData), LOCK_EX);
    }
    
    /**
     * Limpiar datos antiguos
     */
    private function cleanup(): void 
    {
        $lastCleanup = $this->requestData['last_cleanup'] ?? 0;
        $cleanupInterval = $this->config['storage']['cleanup_interval'];
        
        if (time() - $lastCleanup < $cleanupInterval) {
            return;
        }
        
        $currentTime = time();
        $maxAge = $this->config['rate_limiting']['window_seconds'] * 24; // 24 ventanas
        $cutoff = $currentTime - $maxAge;
        
        // Limpiar IPs inactivas
        foreach ($this->requestData['ips'] as $ip => $data) {
            if ($data['last_seen'] < $cutoff) {
                unset($this->requestData['ips'][$ip]);
            }
        }
        
        // Limpiar bloqueos expirados
        foreach ($this->requestData['blocked'] as $ip => $data) {
            if ($data['blocked_until'] < $currentTime) {
                unset($this->requestData['blocked'][$ip]);
            }
        }
        
        // Limitar número de entradas
        $maxEntries = $this->config['storage']['max_entries'];
        if (count($this->requestData['ips']) > $maxEntries) {
            // Ordenar por último acceso y mantener los más recientes
            uasort($this->requestData['ips'], function($a, $b) {
                return $b['last_seen'] - $a['last_seen'];
            });
            
            $this->requestData['ips'] = array_slice($this->requestData['ips'], 0, $maxEntries, true);
        }
        
        $this->requestData['last_cleanup'] = $currentTime;
        $this->saveRequestData();
    }
    
    /**
     * Crear directorios necesarios
     */
    private function ensureDirectories(): void 
    {
        $dirs = [dirname($this->cacheFile), dirname($this->logPath)];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
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
        
        try {
            file_put_contents($this->logPath, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("❌ Error escribiendo log RateLimiter: " . $e->getMessage());
        }
    }
    
    /**
     * Manejar solicitud bloqueada
     */
    public function handleBlocked(array $result): void 
    {
        http_response_code($this->config['responses']['http_code']);
        
        if ($this->config['responses']['json_response']) {
            header('Content-Type: application/json; charset=utf-8');
            
            $response = [
                'error' => true,
                'code' => $this->config['responses']['http_code'],
                'message' => $result['reason'],
                'type' => $result['type'] ?? 'rate_limit',
                'retry_after' => $result['blocked_until'] ?? (time() + 300),
                'timestamp' => date('c')
            ];
            
            if (isset($result['geo_reason'])) {
                $response['geo_reason'] = $result['geo_reason'];
            }
            
            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {
            echo $result['reason'];
        }
        
        exit;
    }
    
    /**
     * Obtener estadísticas mejoradas
     */
    public function getStats(): array 
    {
        $totalIPs = count($this->requestData['ips']);
        $blockedIPs = count($this->requestData['blocked']);
        $whitelistedIPs = count($this->requestData['whitelist']);
        
        $totalRequests = 0;
        $avgHumanScore = 0;
        $avgGeoConsistency = 0;
        $countryStats = [];
        
        foreach ($this->requestData['ips'] as $ipData) {
            $totalRequests += $ipData['total_requests'];
            $avgHumanScore += $ipData['human_score'] ?? 0;
            $avgGeoConsistency += $ipData['geo_consistency'] ?? 0;
            
            // Estadísticas por país
            foreach ($ipData['countries'] ?? [] as $country => $count) {
                if (!isset($countryStats[$country])) {
                    $countryStats[$country] = 0;
                }
                $countryStats[$country] += $count;
            }
        }
        
        $avgHumanScore = $totalIPs > 0 ? $avgHumanScore / $totalIPs : 0;
        $avgGeoConsistency = $totalIPs > 0 ? $avgGeoConsistency / $totalIPs : 0;
        
        return [
            'total_ips' => $totalIPs,
            'blocked_ips' => $blockedIPs,
            'whitelisted_ips' => $whitelistedIPs,
            'total_requests' => $totalRequests,
            'avg_human_score' => round($avgHumanScore, 2),
            'avg_geo_consistency' => round($avgGeoConsistency, 2),
            'country_stats' => $countryStats,
            'geo_integration_enabled' => $this->config['geo_integration']['enabled'],
            'cache_size' => filesize($this->cacheFile),
            'last_cleanup' => $this->requestData['last_cleanup'] ?? 0
        ];
    }
    
    /**
     * Método estático para aplicación rápida
     */
    public static function protect(array $config = []): void 
    {
        $rateLimiter = new self($config);
        $result = $rateLimiter->checkLimits();
        
        if (!$result['allowed']) {
            $rateLimiter->handleBlocked($result);
        }
    }
}

/**
 * 🚦 Función helper para aplicación rápida
 */
function applyRateLimit(array $config = []) {
    RateLimiter::protect($config);
}

?>
