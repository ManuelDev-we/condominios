<?php
/**
 * 🏗️ CyberholeModelsAutoloader - Autoloader PSR-4 para Modelos con Seguridad Integrada
 * 
 * Sistema de carga automática de modelos del CRUD con:
 * - Carga dinámica desde Models-PSR-4.json
 * - Integración completa con RateLimiter (detección de bots/ataques)
 * - Verificación geográfica con GeoFirewall
 * - Control de acceso por IP y ubicación
 * - Estadísticas avanzadas de uso de modelos
 * - Logs de seguridad detallados
 * 
 * @package Cyberhole\Middlewares\PSR4
 * @author ManuelDev
 * @version 3.0
 * @since 2025-09-22
 */

// Cargar dependencias de seguridad
require_once __DIR__ . '/../Security/AutoLoader.php';

class CyberholeModelsAutoloader
{
    private static $instance = null;
    private $modelRegistry = [];
    private $namespaces = [];
    private $loadedModels = [];
    private $loadStats = [];
    private $configPath;
    private $basePath;
    private $logPath;
    
    // Middleware de seguridad
    private $rateLimiter;
    private $geoFirewall;
    private $middlewareAutoloader;
    
    // Configuración de seguridad
    private $securityConfig = [
        'rate_limiting' => [
            'enabled' => true,
            'model_load_limit' => 50,  // Máximo de modelos por IP por hora
            'burst_limit' => 10,       // Máximo de modelos en 1 minuto
            'window_seconds' => 3600,  // Ventana de tiempo
            'suspicious_threshold' => 20, // Umbral para comportamiento sospechoso
        ],
        'geo_filtering' => [
            'enabled' => true,
            'block_unauthorized_countries' => true,
            'log_geo_access' => true,
        ],
        'model_protection' => [
            'sensitive_models' => [
                'Admin', 'EmpleadosUser', 'ClavesRegistro', 
                'FacturacionCyberholeModel', 'NominaModel'
            ],
            'restrict_sensitive' => true,
            'admin_only_models' => ['Admin', 'ClavesRegistro', 'EmpleadosUser', 'FacturacionCyberholeModel', 'NominaModel'],
        ],
        'logging' => [
            'enabled' => true,
            'log_path' => 'logs/models_autoloader.log',
            'log_all_loads' => true,
            'log_security_events' => true,
        ]
    ];
    
    /**
     * Singleton - Obtener instancia única
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor privado para singleton
     */
    private function __construct()
    {
        $this->configPath = __DIR__ . '/../data/Models-PSR-4.json';
        $this->basePath = dirname(__DIR__, 2) . '/';
        $this->logPath = $this->basePath . ($this->securityConfig['logging']['log_path'] ?? 'logs/models_autoloader.log');
        
        // Inicializar componentes
        $this->initializeSecurityMiddlewares();
        $this->loadModelsConfiguration();
        $this->registerPSR4Autoloader();
        $this->ensureDirectories();
        
        $this->logSecurity("🚀 CyberholeModelsAutoloader inicializado con seguridad completa", 'INFO');
    }
    
    /**
     * Inicializar middlewares de seguridad
     */
    private function initializeSecurityMiddlewares(): void
    {
        try {
            // Inicializar MiddlewareAutoloader para cargar dependencias
            $this->middlewareAutoloader = MiddlewareAutoloader::getInstance();
            
            // Cargar RateLimiter con configuración específica para modelos
            if ($this->securityConfig['rate_limiting']['enabled']) {
                $rateLimiterConfig = [
                    'rate_limiting' => [
                        'enabled' => true,
                        'default_limit' => $this->securityConfig['rate_limiting']['model_load_limit'],
                        'window_seconds' => $this->securityConfig['rate_limiting']['window_seconds'],
                        'burst_limit' => $this->securityConfig['rate_limiting']['burst_limit'],
                        'burst_window' => 60,
                    ],
                    'bot_detection' => [
                        'enabled' => true,
                        'suspicious_patterns' => [
                            'rapid_requests' => $this->securityConfig['rate_limiting']['suspicious_threshold'],
                            'automated_tools' => ['curl', 'wget', 'python', 'requests', 'bot'],
                        ]
                    ],
                    'logging' => [
                        'enabled' => true,
                        'log_path' => 'logs/models_rate_limiter.log'
                    ]
                ];
                
                // Cargar RateLimiter usando autoloader PSR-4
                if ($this->middlewareAutoloader->loadClass('RateLimiter')) {
                    $this->rateLimiter = new RateLimiter($rateLimiterConfig);
                    $this->logSecurity("✅ RateLimiter integrado para protección de modelos", 'INFO');
                } else {
                    throw new Exception("No se pudo cargar RateLimiter");
                }
            }
            
            // Cargar GeoFirewall
            if ($this->securityConfig['geo_filtering']['enabled']) {
                if ($this->middlewareAutoloader->loadClass('GeoFirewall')) {
                    $this->geoFirewall = new GeoFirewall();
                    $this->logSecurity("🌍 GeoFirewall integrado para verificación geográfica", 'INFO');
                } else {
                    throw new Exception("No se pudo cargar GeoFirewall");
                }
            }
            
        } catch (Exception $e) {
            $this->logSecurity("❌ Error inicializando middlewares de seguridad: " . $e->getMessage(), 'ERROR');
            
            // En caso de error, crear instancias nulas pero permitir continuar
            $this->rateLimiter = null;
            $this->geoFirewall = null;
        }
    }
    
    /**
     * Cargar configuración de modelos desde Models-PSR-4.json
     */
    private function loadModelsConfiguration(): void
    {
        if (!file_exists($this->configPath)) {
            throw new Exception("❌ Archivo de configuración de modelos no encontrado: {$this->configPath}");
        }
        
        $config = json_decode(file_get_contents($this->configPath), true);
        
        if (!$config) {
            throw new Exception("❌ Error al decodificar configuración de modelos PSR-4");
        }
        
        // Cargar namespaces PSR-4
        $this->namespaces = $config['namespaces'] ?? [];
        
        // Cargar registro de modelos
        $this->modelRegistry = $config['model_registry'] ?? [];
        
        // Inicializar estadísticas
        $this->initializeStats();
        
        $totalModels = $config['total_models'] ?? $this->countTotalModels();
        $this->logSecurity("✅ Configuración PSR-4 cargada: $totalModels modelos disponibles", 'INFO');
    }
    
    /**
     * Registrar autoloader PSR-4
     */
    private function registerPSR4Autoloader(): void
    {
        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->logSecurity("🔧 Autoloader PSR-4 de modelos registrado", 'INFO');
    }
    
    /**
     * Método principal - Cargar clase con verificación de seguridad
     */
    public function loadClass(string $className): bool
    {
        // 1. VERIFICACIÓN DE SEGURIDAD PRIORITARIA
        $securityResult = $this->verifySecurityAccess($className);
        if (!$securityResult['allowed']) {
            $this->logSecurity("🚫 Acceso denegado para modelo '$className': {$securityResult['reason']}", 'BLOCKED');
            $this->handleSecurityDenial($securityResult);
            return false;
        }
        
        // 2. Verificar si ya está cargada
        if (isset($this->loadedModels[$className]) || class_exists($className, false)) {
            $this->updateLoadStats($className, 'cache_hit');
            return true;
        }
        
        // 3. Buscar clase en registro PSR-4
        $modelInfo = $this->findModelInfo($className);
        if (!$modelInfo) {
            $this->logSecurity("❌ Modelo no encontrado en registro PSR-4: $className", 'WARNING');
            return false;
        }
        
        // 4. Construir ruta completa del archivo
        $fullPath = $this->basePath . $modelInfo['file_path'];
        
        if (!file_exists($fullPath)) {
            $this->logSecurity("❌ Archivo de modelo no encontrado: $fullPath", 'ERROR');
            return false;
        }
        
        // 5. Cargar dependencias del modelo si existen
        $this->loadModelDependencies($modelInfo);
        
        // 6. Cargar el archivo del modelo
        try {
            require_once $fullPath;
            
            // Verificar que la clase se cargó correctamente
            if (!class_exists($className, false)) {
                $this->logSecurity("❌ Clase '$className' no definida en archivo: $fullPath", 'ERROR');
                return false;
            }
            
            // 7. Verificar memoria después de la carga
            $this->checkMemoryLeak("load_model_$className");
            
            // 8. Registrar carga exitosa
            $this->registerSuccessfulLoad($className, $modelInfo, $securityResult);
            
            $this->logSecurity("✅ Modelo cargado exitosamente: $className desde {$modelInfo['category']}", 'INFO');
            return true;
            
        } catch (Exception $e) {
            $this->logSecurity("❌ Error cargando modelo '$className': " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Verificar acceso de seguridad (RateLimiter + GeoFirewall)
     */
    private function verifySecurityAccess(string $className): array
    {
        $userIP = $this->getUserIP();
        
        // 🔥 NUEVA: Verificación de memoria antes de procesar
        $this->optimizeMemoryUsage();
        
        // 1. Verificación geográfica primero
        if ($this->geoFirewall && $this->securityConfig['geo_filtering']['enabled']) {
            $geoResult = $this->geoFirewall->verifyAccess();
            if (!$geoResult['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'Acceso geográfico denegado: ' . $geoResult['reason'],
                    'type' => 'geo_blocked',
                    'ip' => $userIP,
                    'geo_details' => $geoResult
                ];
            }
        }
        
        // 2. Verificación de límites de carga de modelos
        if ($this->rateLimiter && $this->securityConfig['rate_limiting']['enabled']) {
            $rateLimitResult = $this->rateLimiter->checkLimits();
            if (!$rateLimitResult['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'Límite de cargas de modelos excedido: ' . $rateLimitResult['reason'],
                    'type' => 'rate_limited',
                    'ip' => $userIP,
                    'rate_details' => $rateLimitResult
                ];
            }
        }
        
        // 3. Verificación de modelos sensibles/restringidos
        if ($this->isRestrictedModel($className)) {
            $access = $this->checkRestrictedModelAccess($className);
            if (!$access['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'Modelo restringido: ' . $access['reason'],
                    'type' => 'restricted_model',
                    'ip' => $userIP,
                    'model' => $className,
                    'security_alert' => $access['security_alert'] ?? 'HIGH'
                ];
            }
        }
        
        return [
            'allowed' => true,
            'reason' => 'Acceso autorizado',
            'ip' => $userIP,
            'security_score' => $this->calculateSecurityScore($userIP)
        ];
    }
    
    /**
     * Buscar información del modelo en el registro PSR-4
     */
    private function findModelInfo(string $className): ?array
    {
        // Buscar en todas las categorías del registro
        foreach ($this->modelRegistry as $category => $categoryData) {
            $models = $categoryData['models'] ?? [];
            
            foreach ($models as $model) {
                if ($model['class'] === $className) {
                    return [
                        'class' => $className,
                        'file_path' => $categoryData['path'] . $model['file'],
                        'category' => $category,
                        'namespace' => $categoryData['namespace'],
                        'description' => $model['description'] ?? 'No description',
                        'model_data' => $model
                    ];
                }
            }
        }
        
        // Búsqueda PSR-4 por namespace
        return $this->findByNamespace($className);
    }
    
    /**
     * Búsqueda por namespace PSR-4
     */
    private function findByNamespace(string $className): ?array
    {
        foreach ($this->namespaces as $namespace => $path) {
            if (strpos($className, rtrim($namespace, '\\')) === 0) {
                // Convertir namespace a ruta de archivo
                $relativeClass = substr($className, strlen(rtrim($namespace, '\\')));
                $filePath = $path . str_replace('\\', '/', $relativeClass) . '.php';
                
                return [
                    'class' => $className,
                    'file_path' => $filePath,
                    'category' => 'namespace_resolved',
                    'namespace' => $namespace,
                    'description' => 'Resolved via PSR-4 namespace mapping'
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Verificar si un modelo es restringido
     */
    private function isRestrictedModel(string $className): bool
    {
        $sensitiveModels = $this->securityConfig['model_protection']['sensitive_models'] ?? [];
        $adminOnlyModels = $this->securityConfig['model_protection']['admin_only_models'] ?? [];
        
        return in_array($className, $sensitiveModels) || in_array($className, $adminOnlyModels);
    }
    
    /**
     * Verificar acceso a modelos restringidos
     */
    private function checkRestrictedModelAccess(string $className): array
    {
        $adminOnlyModels = $this->securityConfig['model_protection']['admin_only_models'] ?? [];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userIP = $this->getUserIP();
        
        if (in_array($className, $adminOnlyModels)) {
            // 🔥 CORRECCIÓN: Validación anti-Social Engineering
            if ($this->isSuspiciousAdminTool($userAgent)) {
                return [
                    'allowed' => false,
                    'reason' => 'Herramienta administrativa sospechosa detectada',
                    'security_alert' => 'CRITICAL',
                    'detected_tool' => $userAgent,
                    'ip' => $userIP
                ];
            }
            
            // Verificar token de seguridad para herramientas legítimas
            if ($this->isClaimedAdminTool($userAgent) && !$this->validateSecureAdminToken()) {
                return [
                    'allowed' => false,
                    'reason' => 'Token de seguridad inválido para herramienta administrativa',
                    'security_alert' => 'CRITICAL',
                    'ip' => $userIP
                ];
            }
            
            // Verificar si hay sesión de administrador válida
            if (!$this->isAdminSession()) {
                return [
                    'allowed' => false,
                    'reason' => 'Modelo requiere privilegios de administrador autenticados'
                ];
            }
        }
        
        return ['allowed' => true, 'reason' => 'Acceso autorizado a modelo restringido'];
    }
    
    /**
     * Verificar sesión de administrador (implementación simplificada)
     */
    private function isAdminSession(): bool
    {
        // Implementación básica - puede expandirse
        return isset($_SESSION['admin_user']) || 
               isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * 🔥 NUEVA: Detectar herramientas administrativas sospechosas
     */
    private function isSuspiciousAdminTool(string $userAgent): bool
    {
        $suspiciousTools = [
            'CyberholeMaintenanceTool',
            'CyberholeAPIClient', 
            'CyberholeAdmin',
            'CyberholeInternal',
            'MaintenanceTool',
            'AdminTool',
            'InternalAPI'
        ];
        
        foreach ($suspiciousTools as $tool) {
            if (stripos($userAgent, $tool) !== false) {
                $this->logSecurity("🚨 ALERTA: Herramienta administrativa sospechosa detectada: $tool en UA: $userAgent", 'CRITICAL');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 🔥 NUEVA: Verificar si UA reclama ser herramienta administrativa
     */
    private function isClaimedAdminTool(string $userAgent): bool
    {
        $adminClaims = [
            'maintenance', 'admin', 'internal', 'cyberhole',
            'api', 'tool', 'client', 'management'
        ];
        
        $userAgentLower = strtolower($userAgent);
        foreach ($adminClaims as $claim) {
            if (strpos($userAgentLower, $claim) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 🔥 NUEVA: Validar token seguro para herramientas administrativas
     */
    private function validateSecureAdminToken(): bool
    {
        // Verificar token en headers
        $token = $_SERVER['HTTP_X_CYBERHOLE_ADMIN_TOKEN'] ?? 
                $_SERVER['HTTP_X_API_KEY'] ?? 
                $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        // Lista de tokens válidos (en producción debería estar en base de datos cifrada)
        $validTokens = [
            'cyberhole_admin_secure_' . hash('sha256', 'admin_real_2025'),
            'Bearer ' . hash('sha256', 'cyberhole_maintenance_real_token'),
            'ApiKey cyberhole_production_' . hash('sha256', 'real_internal_key')
        ];
        
        return in_array($token, $validTokens);
    }
    
    /**
     * Cargar dependencias del modelo
     */
    private function loadModelDependencies(array $modelInfo): void
    {
        // Cargar clases base comunes que necesitan los modelos
        $commonDependencies = [
            'SecurityFilters' => 'helpers/SecurityFilters.php',
            'DatabaseConfig' => 'config/database.php',
            'EnvironmentConfig' => 'config/env.php'
        ];
        
        foreach ($commonDependencies as $class => $path) {
            if (!class_exists($class, false)) {
                $fullPath = $this->basePath . $path;
                if (file_exists($fullPath)) {
                    require_once $fullPath;
                }
            }
        }
    }
    
    /**
     * Registrar carga exitosa del modelo
     */
    private function registerSuccessfulLoad(string $className, array $modelInfo, array $securityResult): void
    {
        $this->loadedModels[$className] = [
            'loaded_at' => time(),
            'file_path' => $modelInfo['file_path'],
            'category' => $modelInfo['category'],
            'ip' => $securityResult['ip'],
            'security_score' => $securityResult['security_score'] ?? 0,
            'load_count' => ($this->loadedModels[$className]['load_count'] ?? 0) + 1
        ];
        
        $this->updateLoadStats($className, 'successful_load', $modelInfo);
    }
    
    /**
     * Actualizar estadísticas de carga
     */
    private function updateLoadStats(string $className, string $type, array $modelInfo = []): void
    {
        $userIP = $this->getUserIP();
        $currentTime = time();
        
        if (!isset($this->loadStats[$userIP])) {
            $this->loadStats[$userIP] = [
                'total_loads' => 0,
                'cache_hits' => 0,
                'successful_loads' => 0,
                'failed_loads' => 0,
                'models_loaded' => [],
                'categories_accessed' => [],
                'first_load' => $currentTime,
                'last_load' => $currentTime
            ];
        }
        
        $stats = &$this->loadStats[$userIP];
        $stats['last_load'] = $currentTime;
        
        switch ($type) {
            case 'successful_load':
                $stats['total_loads']++;
                $stats['successful_loads']++;
                $stats['models_loaded'][$className] = ($stats['models_loaded'][$className] ?? 0) + 1;
                if (!empty($modelInfo['category'])) {
                    $stats['categories_accessed'][$modelInfo['category']] = ($stats['categories_accessed'][$modelInfo['category']] ?? 0) + 1;
                }
                break;
                
            case 'cache_hit':
                $stats['cache_hits']++;
                break;
                
            case 'failed_load':
                $stats['total_loads']++;
                $stats['failed_loads']++;
                break;
        }
    }
    
    /**
     * Manejar denegación de seguridad
     */
    private function handleSecurityDenial(array $securityResult): void
    {
        // Log del evento de seguridad
        $this->logSecurity(
            "🛡️ ACCESO DENEGADO: {$securityResult['reason']} - IP: {$securityResult['ip']}", 
            'SECURITY_ALERT'
        );
        
        // Actualizar estadísticas de fallos
        $this->updateLoadStats('DENIED', 'failed_load');
        
        // Si es una aplicación web, podríamos enviar headers apropiados
        if (!defined('MODELS_AUTOLOADER_TESTING')) {
            http_response_code(403);
            header('X-Models-Access: Denied');
            header('X-Security-Reason: ' . $securityResult['type']);
        }
        
        // En producción, podríamos terminar la ejecución
        // exit("Acceso denegado por seguridad");
    }
    
    /**
     * Calcular puntuación de seguridad
     */
    private function calculateSecurityScore(string $ip): float
    {
        $score = 5.0; // Base neutral
        
        // Factores de la IP
        if (isset($this->loadStats[$ip])) {
            $stats = $this->loadStats[$ip];
            
            // Diversidad de modelos cargados (+)
            $modelDiversity = count($stats['models_loaded']);
            if ($modelDiversity > 1) $score += min($modelDiversity * 0.2, 2.0);
            
            // Ratio de éxito (+)
            $totalLoads = $stats['total_loads'] ?: 1;
            $successRatio = $stats['successful_loads'] / $totalLoads;
            $score += $successRatio * 2.0;
            
            // Tiempo de uso (+)
            $timeSpan = time() - $stats['first_load'];
            if ($timeSpan > 300) $score += min($timeSpan / 3600, 1.5);
            
            // Penalizaciones por comportamiento sospechoso (-)
            if ($stats['failed_loads'] > 5) $score -= 1.0;
            if ($totalLoads > 100) $score -= 0.5; // Muchas cargas
        }
        
        return max(0, min(10, $score));
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
     * Inicializar estadísticas
     */
    private function initializeStats(): void
    {
        $this->loadStats = [];
    }
    
    /**
     * Contar total de modelos disponibles
     */
    private function countTotalModels(): int
    {
        $total = 0;
        foreach ($this->modelRegistry as $category => $categoryData) {
            $total += count($categoryData['models'] ?? []);
        }
        return $total;
    }
    
    /**
     * Crear directorios necesarios
     */
    private function ensureDirectories(): void
    {
        $dirs = [dirname($this->logPath)];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Log de eventos de seguridad
     */
    private function logSecurity(string $message, string $level = 'INFO'): void
    {
        if (!$this->securityConfig['logging']['enabled']) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'ip' => $this->getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'memory_usage' => memory_get_usage(true),
            'loaded_models_count' => count($this->loadedModels)
        ];
        
        try {
            file_put_contents($this->logPath, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("❌ Error escribiendo log del autoloader de modelos: " . $e->getMessage());
        }
    }
    
    // ===========================================
    // MÉTODOS PÚBLICOS PARA CONSULTA Y GESTIÓN
    // ===========================================
    
    /**
     * Obtener lista de todos los modelos disponibles
     */
    public function getAllAvailableModels(): array
    {
        $models = [];
        
        foreach ($this->modelRegistry as $category => $categoryData) {
            foreach ($categoryData['models'] ?? [] as $model) {
                $models[$model['class']] = [
                    'class' => $model['class'],
                    'category' => $category,
                    'namespace' => $categoryData['namespace'],
                    'description' => $model['description'],
                    'file' => $model['file'],
                    'loaded' => isset($this->loadedModels[$model['class']])
                ];
            }
        }
        
        return $models;
    }
    
    /**
     * Obtener modelos cargados actualmente
     */
    public function getLoadedModels(): array
    {
        return $this->loadedModels;
    }
    
    /**
     * Obtener estadísticas de carga por IP
     */
    public function getLoadStats(string $ip = null): array
    {
        if ($ip) {
            return $this->loadStats[$ip] ?? [];
        }
        
        return $this->loadStats;
    }
    
    /**
     * Obtener estadísticas globales del autoloader
     */
    public function getGlobalStats(): array
    {
        $totalAvailable = $this->countTotalModels();
        $totalLoaded = count($this->loadedModels);
        $totalIPs = count($this->loadStats);
        
        $totalLoads = 0;
        $totalCacheHits = 0;
        $categoriesUsed = [];
        
        foreach ($this->loadStats as $ipStats) {
            $totalLoads += $ipStats['total_loads'];
            $totalCacheHits += $ipStats['cache_hits'];
            
            foreach ($ipStats['categories_accessed'] ?? [] as $category => $count) {
                $categoriesUsed[$category] = ($categoriesUsed[$category] ?? 0) + $count;
            }
        }
        
        return [
            'models' => [
                'total_available' => $totalAvailable,
                'total_loaded' => $totalLoaded,
                'load_percentage' => $totalAvailable > 0 ? round(($totalLoaded / $totalAvailable) * 100, 2) : 0
            ],
            'usage' => [
                'total_ips' => $totalIPs,
                'total_loads' => $totalLoads,
                'cache_hits' => $totalCacheHits,
                'cache_hit_ratio' => $totalLoads > 0 ? round(($totalCacheHits / $totalLoads) * 100, 2) : 0
            ],
            'categories' => $categoriesUsed,
            'security' => [
                'rate_limiting_enabled' => $this->securityConfig['rate_limiting']['enabled'],
                'geo_filtering_enabled' => $this->securityConfig['geo_filtering']['enabled'],
                'restricted_models_count' => count($this->securityConfig['model_protection']['sensitive_models'])
            ]
        ];
    }
    
    /**
     * Verificar si un modelo específico está disponible
     */
    public function isModelAvailable(string $className): bool
    {
        return $this->findModelInfo($className) !== null;
    }
    
    /**
     * Cargar múltiples modelos con verificación de seguridad
     */
    public function loadModels(array $classNames): array
    {
        $results = [];
        
        foreach ($classNames as $className) {
            $results[$className] = $this->loadClass($className);
        }
        
        return $results;
    }
    
    /**
     * Obtener información detallada de un modelo
     */
    public function getModelInfo(string $className): ?array
    {
        $modelInfo = $this->findModelInfo($className);
        
        if (!$modelInfo) {
            return null;
        }
        
        $modelInfo['is_loaded'] = isset($this->loadedModels[$className]);
        $modelInfo['is_restricted'] = $this->isRestrictedModel($className);
        $modelInfo['full_path'] = $this->basePath . $modelInfo['file_path'];
        $modelInfo['file_exists'] = file_exists($modelInfo['full_path']);
        
        if (isset($this->loadedModels[$className])) {
            $modelInfo['load_info'] = $this->loadedModels[$className];
        }
        
        return $modelInfo;
    }
    
    /**
     * Limpiar cachés y reinicializar
     */
    public function reset(): void
    {
        $this->loadedModels = [];
        $this->loadStats = [];
        $this->loadModelsConfiguration();
        $this->logSecurity("🔄 Autoloader de modelos reinicializado", 'INFO');
    }
    
    /**
     * Habilitar/deshabilitar componentes de seguridad
     */
    public function configureSecurity(array $config): void
    {
        $this->securityConfig = array_merge_recursive($this->securityConfig, $config);
        $this->logSecurity("⚙️ Configuración de seguridad actualizada", 'INFO');
    }
    
    /**
     * 🔥 NUEVA: Optimización automática de memoria
     */
    private function optimizeMemoryUsage(): void
    {
        $currentMemory = memory_get_usage(true);
        $memoryLimit = 2097152; // 2MB límite
        
        if ($currentMemory > $memoryLimit) {
            // Limpiar variables temporales
            if (count($this->loadedModels) > 50) {
                // Mantener solo los 30 modelos más recientes
                $sortedByTime = $this->loadedModels;
                uasort($sortedByTime, function($a, $b) {
                    return $b['loaded_at'] - $a['loaded_at'];
                });
                
                $this->loadedModels = array_slice($sortedByTime, 0, 30, true);
                $this->logSecurity("🧹 Limpieza de memoria: modelos antiguos removidos", 'INFO');
            }
            
            // Ejecutar garbage collection
            gc_collect_cycles();
            
            $newMemory = memory_get_usage(true);
            $memoryFreed = $currentMemory - $newMemory;
            
            if ($memoryFreed > 0) {
                $this->logSecurity("💾 Memoria optimizada: " . number_format($memoryFreed / 1024, 2) . " KB liberados", 'INFO');
            }
            
            // Si sigue muy alta, registrar alerta
            if ($newMemory > $memoryLimit) {
                $this->logSecurity("⚠️ ALERTA: Uso de memoria alto: " . number_format($newMemory / 1024, 2) . " KB", 'WARNING');
            }
        }
    }
    
    /**
     * 🔥 NUEVA: Verificar y alertar sobre uso excesivo de memoria
     */
    private function checkMemoryLeak(string $operation): void
    {
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        if ($currentMemory > 1048576) { // 1MB
            $this->logSecurity("⚡ VULNERABILIDAD DETECTADA [MEDIA]: Posible fuga de memoria durante '$operation': " . 
                              number_format($currentMemory / 1024, 2) . " KB", 'WARNING');
        }
        
        if ($peakMemory > 3145728) { // 3MB
            $this->logSecurity("🚨 ALERTA CRÍTICA: Pico de memoria muy alto: " . 
                              number_format($peakMemory / 1024, 2) . " KB", 'CRITICAL');
        }
    }
}

// ===========================================
// FUNCIONES HELPER PARA USO DIRECTO
// ===========================================

/**
 * 🚀 Inicializar autoloader de modelos con seguridad
 */
function initCyberholeModelsAutoloader(): CyberholeModelsAutoloader {
    return CyberholeModelsAutoloader::getInstance();
}

/**
 * 📦 Cargar modelo específico con verificación de seguridad
 */
function loadCyberholeModel(string $className): bool {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    return $autoloader->loadClass($className);
}

/**
 * 📊 Obtener estadísticas del autoloader de modelos
 */
function getModelAutoloaderStats(): array {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    return $autoloader->getGlobalStats();
}

/**
 * 🔍 Verificar disponibilidad de modelo
 */
function isModelAvailable(string $className): bool {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    return $autoloader->isModelAvailable($className);
}

/**
 * 📋 Listar todos los modelos disponibles
 */
function listAvailableModels(): array {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    return $autoloader->getAllAvailableModels();
}

// Auto-inicializar si no estamos en modo testing
if (!defined('MODELS_AUTOLOADER_TESTING')) {
    $modelsAutoloader = initCyberholeModelsAutoloader();
}

?>
