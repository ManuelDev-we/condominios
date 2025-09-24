<?php
/**
 * 🔧 Sistema de Carga Automática de Middlewares PSR-4
 * 
 * Sistema de carga automática de clases para middlewares basado en PSR-4
 * Carga la configuración desde Middlewares-PSR-4.json
 * 
 * @package Cyberhole\Middlewares
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-21
 */

class MiddlewareAutoloader 
{
    private static $instance = null;
    private $middlewares = [];
    private $dependencies = [];
    private $loadedClasses = [];
    private $configPath;
    private $basePath;
    
    /**
     * Singleton para el autoloader
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
        $this->configPath = __DIR__ . '/../data/Middlewares-PSR-4.json';
        $this->basePath = dirname(__DIR__, 2) . '/'; // Ruta base del proyecto
        $this->loadConfiguration();
        $this->registerAutoloader();
    }
    
    /**
     * Cargar configuración desde Middlewares-PSR-4.json
     */
    private function loadConfiguration(): void 
    {
        if (!file_exists($this->configPath)) {
            throw new Exception("❌ Archivo de configuración PSR-4 no encontrado: {$this->configPath}");
        }
        
        $config = json_decode(file_get_contents($this->configPath), true);
        
        if (!$config) {
            throw new Exception("❌ Error al decodificar configuración PSR-4");
        }
        
        // Cargar mapeo de middlewares
        $this->middlewares = $config['middlewares'] ?? [];
        $this->dependencies = $config['dependencies'] ?? [];
        
        $this->log("✅ Configuración PSR-4 cargada: " . count($this->getAllClasses()) . " middlewares disponibles");
    }
    
    /**
     * Registrar el autoloader
     */
    private function registerAutoloader(): void 
    {
        spl_autoload_register([$this, 'loadClass']);
        $this->log("🔧 Autoloader PSR-4 registrado");
    }
    
    /**
     * Cargar clase automáticamente
     */
    public function loadClass(string $className): bool 
    {
        // Verificar si la clase ya está cargada
        if (isset($this->loadedClasses[$className]) || class_exists($className, false)) {
            return true;
        }
        
        // Buscar la clase en los middlewares
        $classPath = $this->findClassPath($className);
        
        if (!$classPath) {
            $this->log("❌ Clase no encontrada en registro: $className", 'WARNING');
            return false;
        }
        
        // Cargar dependencias primero
        $this->loadDependencies($className);
        
        // Cargar la clase
        $fullPath = $this->basePath . $classPath;
        
        if (!file_exists($fullPath)) {
            $this->log("❌ Archivo no encontrado: $fullPath", 'ERROR');
            return false;
        }
        
        try {
            require_once $fullPath;
            $this->loadedClasses[$className] = $fullPath;
            
            // Verificar que la clase se cargó correctamente
            if (!class_exists($className, false)) {
                $this->log("❌ Clase no definida en archivo: $className en $fullPath", 'ERROR');
                return false;
            }
            
            $this->log("✅ Clase cargada: $className desde $classPath");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ Error cargando clase $className: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Buscar ruta de clase en configuración
     */
    private function findClassPath(string $className): ?string 
    {
        // Buscar en todas las categorías
        foreach ($this->middlewares as $category => $classes) {
            if (isset($classes[$className])) {
                return $classes[$className];
            }
        }
        
        // Búsqueda flexible (case-insensitive)
        $lowerClassName = strtolower($className);
        foreach ($this->middlewares as $category => $classes) {
            foreach ($classes as $registeredClass => $path) {
                if (strtolower($registeredClass) === $lowerClassName) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Cargar dependencias de una clase
     */
    private function loadDependencies(string $className): void 
    {
        if (!isset($this->dependencies[$className])) {
            return;
        }
        
        foreach ($this->dependencies[$className] as $dependency) {
            if (!isset($this->loadedClasses[$dependency])) {
                $this->loadClass($dependency);
            }
        }
    }
    
    /**
     * Obtener todas las clases disponibles
     */
    public function getAllClasses(): array 
    {
        $allClasses = [];
        
        foreach ($this->middlewares as $category => $classes) {
            $allClasses = array_merge($allClasses, array_keys($classes));
        }
        
        return $allClasses;
    }
    
    /**
     * Obtener clases cargadas
     */
    public function getLoadedClasses(): array 
    {
        return $this->loadedClasses;
    }
    
    /**
     * Verificar si una clase existe en el registro
     */
    public function classExists(string $className): bool 
    {
        return $this->findClassPath($className) !== null;
    }
    
    /**
     * Obtener información de una clase
     */
    public function getClassInfo(string $className): ?array 
    {
        $path = $this->findClassPath($className);
        
        if (!$path) {
            return null;
        }
        
        return [
            'class' => $className,
            'path' => $path,
            'full_path' => $this->basePath . $path,
            'loaded' => isset($this->loadedClasses[$className]),
            'dependencies' => $this->dependencies[$className] ?? [],
            'category' => $this->getClassCategory($className)
        ];
    }
    
    /**
     * Obtener categoría de una clase
     */
    private function getClassCategory(string $className): ?string 
    {
        foreach ($this->middlewares as $category => $classes) {
            if (isset($classes[$className])) {
                return $category;
            }
        }
        
        return null;
    }
    
    /**
     * Cargar múltiples clases
     */
    public function loadClasses(array $classNames): array 
    {
        $results = [];
        
        foreach ($classNames as $className) {
            $results[$className] = $this->loadClass($className);
        }
        
        return $results;
    }
    
    /**
     * Obtener estadísticas del autoloader mejoradas con integración de modelos
     */
    public function getStats(): array 
    {
        $totalClasses = count($this->getAllClasses());
        $loadedClasses = count($this->loadedClasses);
        
        $stats = [
            'middleware_autoloader' => [
                'total_classes' => $totalClasses,
                'loaded_classes' => $loadedClasses,
                'load_percentage' => $totalClasses > 0 ? round(($loadedClasses / $totalClasses) * 100, 2) : 0,
                'config_path' => $this->configPath,
                'base_path' => $this->basePath
            ]
        ];
        
        // Integrar estadísticas del CyberholeModelsAutoloader si está disponible
        if (class_exists('CyberholeModelsAutoloader', false)) {
            try {
                $modelsAutoloader = CyberholeModelsAutoloader::getInstance();
                $stats['models_autoloader'] = $modelsAutoloader->getGlobalStats();
                $stats['integration_active'] = true;
            } catch (Exception $e) {
                $stats['integration_active'] = false;
                $stats['integration_error'] = $e->getMessage();
            }
        } else {
            $stats['integration_active'] = false;
            $stats['models_autoloader'] = null;
        }
        
        // Estadísticas combinadas
        $stats['combined_stats'] = [
            'total_middleware_classes' => $totalClasses,
            'total_loaded_middleware' => $loadedClasses,
            'total_available_models' => $stats['models_autoloader']['models']['total_available'] ?? 0,
            'total_loaded_models' => $stats['models_autoloader']['models']['total_loaded'] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        return $stats;
    }
    
    /**
     * Log de actividades
     */
    private function log(string $message, string $level = 'INFO'): void 
    {
        $logPath = $this->basePath . '../logs/middleware_autoloader.log';
        
        // Crear directorio si no existe
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'memory_usage' => memory_get_usage(true),
            'loaded_classes' => count($this->loadedClasses)
        ];
        
        try {
            file_put_contents($logPath, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("❌ Error escribiendo log autoloader: " . $e->getMessage());
        }
    }
    
    /**
     * Reinicializar autoloader (para testing)
     */
    public function reset(): void 
    {
        $this->loadedClasses = [];
        $this->loadConfiguration();
        $this->log("🔄 Autoloader reinicializado");
    }
}

/**
 * 🚀 Función helper para inicializar autoloader
 */
function initMiddlewareAutoloader(): MiddlewareAutoloader {
    return MiddlewareAutoloader::getInstance();
}

/**
 * 📦 Función helper para cargar middleware específico
 */
function loadMiddleware(string $className): bool {
    $autoloader = MiddlewareAutoloader::getInstance();
    return $autoloader->loadClass($className);
}

/**
 * 📊 Función helper para obtener estadísticas combinadas
 */
function getAutoloaderStats(): array {
    $autoloader = MiddlewareAutoloader::getInstance();
    return $autoloader->getStats();
}

/**
 * 🔗 Función helper para obtener estadísticas integradas (middlewares + modelos)
 */
function getCombinedAutoloaderStats(): array {
    $middlewareStats = getAutoloaderStats();
    
    // Si existe el autoloader de modelos, incluir sus estadísticas
    if (class_exists('CyberholeModelsAutoloader', false)) {
        try {
            $modelsAutoloader = CyberholeModelsAutoloader::getInstance();
            $middlewareStats['models_detailed'] = $modelsAutoloader->getGlobalStats();
        } catch (Exception $e) {
            $middlewareStats['models_error'] = $e->getMessage();
        }
    }
    
    return $middlewareStats;
}

/**
 * 🚀 Función helper para inicializar ambos autoloaders
 */
function initAllAutoloaders(): array {
    $results = [];
    
    // Inicializar middleware autoloader
    try {
        $middlewareAutoloader = initMiddlewareAutoloader();
        $results['middleware'] = [
            'status' => 'success',
            'instance' => $middlewareAutoloader
        ];
    } catch (Exception $e) {
        $results['middleware'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    // Inicializar models autoloader si está disponible
    if (function_exists('initCyberholeModelsAutoloader')) {
        try {
            $modelsAutoloader = initCyberholeModelsAutoloader();
            $results['models'] = [
                'status' => 'success',
                'instance' => $modelsAutoloader
            ];
        } catch (Exception $e) {
            $results['models'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $results['models'] = [
            'status' => 'not_available',
            'message' => 'CyberholeModelsAutoloader not loaded'
        ];
    }
    
    return $results;
}

// Auto-inicializar si no se está incluyendo desde test
if (!defined('MIDDLEWARE_TESTING')) {
    $autoloader = initMiddlewareAutoloader();
}

?>
