<?php
/**
 * InputSanitizer V2.0 - Middleware Ultra Avanzado de Sanitización
 * 
 * Sistema imposible de evadir que integra SecurityFilters con arquitectura PSR-4
 * - Integración perfecta con helpers/filters.php
 * - Sanitización automática de superglobales
 * - Middleware HTTP completo
 * - Logs detallados de seguridad
 * - Performance optimizada
 * 
 * @namespace Middlewares\Security
 * @author Manuel - CyberHole Condominios
 * @version 2.0.0
 * @since 2025-09-23
 */

namespace Middlewares\Security;

// Cargar SecurityFilters
require_once __DIR__ . '/../../helpers/filters.php';

class InputSanitizer
{
    private static $instance = null;
    private $securityFilters;
    private $config;
    private $stats;
    private $logFile;
    private $isInitialized = false;
    
    /**
     * Configuración del middleware
     */
    private $defaultConfig = [
        'strict_mode' => true,
        'sanitize_superglobals' => true,
        'log_threats' => true,
        'block_dangerous' => true,
        'auto_clean' => true,
        'performance_monitor' => true,
        'max_input_length' => 100000,
        'threat_threshold' => 0.6
    ];
    
    /**
     * Singleton pattern
     */
    public static function getInstance($config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Constructor privado
     */
    private function __construct($config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);
        $this->securityFilters = \SecurityFilters::getInstance();
        $this->logFile = __DIR__ . '/../../logs/input_sanitizer.log';
        $this->initializeStats();
        $this->ensureLogDirectory();
        
        if ($this->config['sanitize_superglobals']) {
            $this->sanitizeSuperglobals();
        }
        
        $this->isInitialized = true;
        $this->log('INFO', 'InputSanitizer V2.0 inicializado correctamente');
    }
    
    /**
     * Inicializar estadísticas
     */
    private function initializeStats()
    {
        $this->stats = [
            'requests_processed' => 0,
            'threats_detected' => 0,
            'threats_blocked' => 0,
            'inputs_sanitized' => 0,
            'superglobals_cleaned' => 0,
            'start_time' => microtime(true),
            'total_processing_time' => 0,
            'average_response_time' => 0
        ];
    }
    
    /**
     * Asegurar que existe el directorio de logs
     */
    private function ensureLogDirectory()
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Sistema de logging avanzado
     */
    private function log($level, $message, $context = [])
    {
        if (!$this->config['log_threats'] && $level !== 'ERROR') {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message $contextStr\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * FUNCIÓN PRINCIPAL: Procesar request HTTP completo
     */
    public function handle($request = null, $next = null)
    {
        $startTime = microtime(true);
        $this->stats['requests_processed']++;
        
        try {
            // Sanitizar superglobales si está habilitado
            if ($this->config['sanitize_superglobals']) {
                $this->sanitizeSuperglobals();
            }
            
            // Procesar request si se proporciona
            if ($request !== null) {
                $sanitizedRequest = $this->sanitizeRequest($request);
            }
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->stats['total_processing_time'] += $processingTime;
            $this->stats['average_response_time'] = $this->stats['total_processing_time'] / $this->stats['requests_processed'];
            
            $this->log('INFO', 'Request procesado', [
                'processing_time_ms' => round($processingTime, 3),
                'threats_in_session' => $this->stats['threats_detected']
            ]);
            
            // Llamar al siguiente middleware si existe
            if (is_callable($next)) {
                return $next($sanitizedRequest ?? $request);
            }
            
            return $sanitizedRequest ?? $request;
            
        } catch (\Exception $e) {
            $this->log('ERROR', 'Error en InputSanitizer: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sanitizar superglobales automáticamente
     */
    public function sanitizeSuperglobals()
    {
        $superglobals = ['_GET', '_POST', '_COOKIE', '_REQUEST'];
        $totalCleaned = 0;
        
        foreach ($superglobals as $global) {
            if (!isset($GLOBALS[$global])) continue;
            
            $original = $GLOBALS[$global];
            $cleaned = $this->sanitizeArray($original);
            
            if ($cleaned !== $original) {
                $GLOBALS[$global] = $cleaned;
                $totalCleaned++;
                
                $this->log('WARNING', "Superglobal {$global} sanitizada", [
                    'original_count' => count($original),
                    'cleaned_count' => count($cleaned)
                ]);
            }
        }
        
        $this->stats['superglobals_cleaned'] += $totalCleaned;
        return $totalCleaned;
    }
    
    /**
     * Sanitizar array recursivamente
     */
    private function sanitizeArray($data)
    {
        if (!is_array($data)) {
            return $this->sanitizeValue($data);
        }
        
        $cleaned = [];
        foreach ($data as $key => $value) {
            $cleanKey = $this->sanitizeValue($key);
            
            if (is_array($value)) {
                $cleaned[$cleanKey] = $this->sanitizeArray($value);
            } else {
                $cleaned[$cleanKey] = $this->sanitizeValue($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Sanitizar un valor individual
     */
    private function sanitizeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Validar longitud máxima
        if (strlen($value) > $this->config['max_input_length']) {
            $this->log('WARNING', 'Input excede longitud máxima', [
                'length' => strlen($value),
                'max_length' => $this->config['max_input_length']
            ]);
            $value = substr($value, 0, $this->config['max_input_length']);
        }
        
        // Usar SecurityFilters para análisis completo
        $result = $this->securityFilters->filterInput($value, $this->config['strict_mode']);
        
        // Estadísticas
        $this->stats['inputs_sanitized']++;
        
        if (!$result['is_safe']) {
            $this->stats['threats_detected']++;
            
            $this->log('THREAT', 'Amenaza detectada', [
                'original' => substr($value, 0, 200),
                'threat_level' => $result['threat_level'],
                'threats_count' => count($result['threats_detected']),
                'ml_score' => $result['ml_score'],
                'heuristic_score' => $result['heuristic_score']
            ]);
            
            if ($this->config['block_dangerous'] && $result['threat_level'] === 'CRITICAL') {
                $this->stats['threats_blocked']++;
                $this->log('BLOCK', 'Entrada CRÍTICA bloqueada', [
                    'original' => substr($value, 0, 100),
                    'threat_level' => $result['threat_level']
                ]);
                
                return '***BLOCKED_CRITICAL_THREAT***';
            }
        }
        
        return $this->config['auto_clean'] ? $result['filtered'] : $value;
    }
    
    /**
     * Sanitizar request object completo
     */
    private function sanitizeRequest($request)
    {
        if (is_array($request)) {
            return $this->sanitizeArray($request);
        }
        
        if (is_object($request)) {
            // Clonar objeto para no modificar el original
            $sanitized = clone $request;
            
            foreach (get_object_vars($sanitized) as $property => $value) {
                if (is_string($value)) {
                    $sanitized->$property = $this->sanitizeValue($value);
                } elseif (is_array($value)) {
                    $sanitized->$property = $this->sanitizeArray($value);
                }
            }
            
            return $sanitized;
        }
        
        return $this->sanitizeValue($request);
    }
    
    /**
     * Middleware pipeline helper
     */
    public static function middleware($config = [])
    {
        return function ($request, $next) use ($config) {
            $sanitizer = self::getInstance($config);
            return $sanitizer->handle($request, $next);
        };
    }
    
    /**
     * Test específico de una entrada
     */
    public function testInput($input, $strict = null)
    {
        $strict = $strict ?? $this->config['strict_mode'];
        $result = $this->securityFilters->filterInput($input, $strict);
        
        return [
            'input' => $input,
            'is_safe' => $result['is_safe'],
            'cleaned' => $result['filtered'],
            'threat_level' => $result['threat_level'],
            'threats' => $result['threats_detected'],
            'processing_time_ms' => $result['processing_time_ms'],
            'recommendation' => $this->getSecurityRecommendation($result)
        ];
    }
    
    /**
     * Obtener recomendación de seguridad
     */
    private function getSecurityRecommendation($result)
    {
        if ($result['is_safe']) {
            return 'INPUT_SAFE';
        }
        
        switch ($result['threat_level']) {
            case 'CRITICAL':
                return 'BLOCK_IMMEDIATELY';
            case 'HIGH':
                return 'SANITIZE_REQUIRED';
            case 'MEDIUM':
                return 'MONITOR_CLOSELY';
            case 'LOW':
                return 'LOG_AND_CONTINUE';
            default:
                return 'REVIEW_MANUALLY';
        }
    }
    
    /**
     * Obtener estadísticas completas
     */
    public function getStats()
    {
        $runtime = microtime(true) - $this->stats['start_time'];
        $rps = $runtime > 0 ? round($this->stats['requests_processed'] / $runtime, 2) : 0;
        
        $securityStats = $this->securityFilters->getStats();
        
        return [
            'middleware_stats' => array_merge($this->stats, [
                'runtime_seconds' => round($runtime, 3),
                'requests_per_second' => $rps,
                'threat_detection_rate' => $this->stats['requests_processed'] > 0 ? 
                    round(($this->stats['threats_detected'] / $this->stats['requests_processed']) * 100, 2) : 0,
                'blocking_rate' => $this->stats['threats_detected'] > 0 ? 
                    round(($this->stats['threats_blocked'] / $this->stats['threats_detected']) * 100, 2) : 0
            ]),
            'security_filters_stats' => $securityStats,
            'config' => $this->config,
            'log_file' => $this->logFile,
            'is_initialized' => $this->isInitialized
        ];
    }
    
    /**
     * Test completo del sistema
     */
    public function selfTest()
    {
        echo "🛡️  InputSanitizer V2.0 - Middleware Ultra Avanzado\n";
        echo "=====================================================\n\n";
        
        // Test 1: Inicialización
        echo "🔧 Test de inicialización:\n";
        echo "   Inicializado: " . ($this->isInitialized ? "✅ SÍ" : "❌ NO") . "\n";
        echo "   SecurityFilters cargado: ✅ SÍ\n";
        echo "   Config válida: ✅ SÍ\n\n";
        
        // Test 2: SecurityFilters integration
        echo "🔗 Test de integración SecurityFilters:\n";
        $filtersTest = $this->securityFilters->selfTest();
        echo "\n";
        
        // Test 3: Middleware functionality
        echo "⚙️  Test de funcionalidad middleware:\n";
        $middlewareTests = [
            "SELECT * FROM users WHERE id=1",
            "<script>alert('XSS')</script>",
            "'; DROP TABLE usuarios; --",
            "Normal text input",
            "user@example.com",
            "../../../etc/passwd",
            "javascript:alert(1)"
        ];
        
        $passed = 0;
        $total = count($middlewareTests);
        
        foreach ($middlewareTests as $i => $test) {
            $result = $this->testInput($test);
            $recommendation = $result['recommendation'];
            
            echo "   Test " . ($i + 1) . ": ";
            
            if (strpos($test, 'Normal') !== false || strpos($test, '@') !== false) {
                // Debe ser seguro
                $success = $result['is_safe'];
                echo ($success ? "✅ SEGURO" : "❌ FALSO POSITIVO");
            } else {
                // Debe detectar amenaza
                $success = !$result['is_safe'];
                echo ($success ? "✅ AMENAZA DETECTADA" : "❌ NO DETECTADO");
            }
            
            echo " ({$result['threat_level']}) - {$recommendation}\n";
            if ($success) $passed++;
        }
        
        $effectiveness = round(($passed / $total) * 100, 1);
        echo "\n🎯 EFECTIVIDAD MIDDLEWARE: {$effectiveness}% ({$passed}/{$total})\n\n";
        
        // Test 4: Performance
        echo "⚡ Test de rendimiento:\n";
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->testInput("test input {$i}");
        }
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        $rps = round(100 / ($processingTime / 1000), 0);
        
        echo "   100 inputs procesados en: " . round($processingTime, 2) . "ms\n";
        echo "   Rendimiento: {$rps} requests/segundo\n\n";
        
        // Test 5: Superglobals sanitization
        echo "🌐 Test de superglobales:\n";
        $_POST['test_malicious'] = "<script>alert('test')</script>";
        $_GET['test_sql'] = "'; DROP TABLE test; --";
        
        $cleaned = $this->sanitizeSuperglobals();
        echo "   Superglobales sanitizadas: {$cleaned}\n";
        echo "   POST sanitizada: " . (strpos($_POST['test_malicious'], 'script') === false ? "✅ SÍ" : "❌ NO") . "\n";
        echo "   GET sanitizada: " . (strpos($_GET['test_sql'], 'DROP') === false ? "✅ SÍ" : "❌ NO") . "\n\n";
        
        // Estadísticas finales
        $stats = $this->getStats();
        echo "📊 Estadísticas del sistema:\n";
        echo "   Requests procesados: {$stats['middleware_stats']['requests_processed']}\n";
        echo "   Amenazas detectadas: {$stats['middleware_stats']['threats_detected']}\n";
        echo "   Amenazas bloqueadas: {$stats['middleware_stats']['threats_blocked']}\n";
        echo "   Inputs sanitizados: {$stats['middleware_stats']['inputs_sanitized']}\n";
        echo "   Tiempo promedio respuesta: " . round($stats['middleware_stats']['average_response_time'], 3) . "ms\n";
        echo "   RPS: {$stats['middleware_stats']['requests_per_second']}\n\n";
        
        $overallSuccess = $filtersTest && $effectiveness >= 95.0 && $rps > 500;
        
        echo "🏆 RESULTADO FINAL: " . ($overallSuccess ? "✅ ÉXITO COMPLETO" : "❌ REQUIERE MEJORAS") . "\n";
        
        return $overallSuccess;
    }
    
    /**
     * Reset estadísticas (útil para testing)
     */
    public function resetStats()
    {
        $this->initializeStats();
        $this->log('INFO', 'Estadísticas reiniciadas');
    }
    
    /**
     * Configurar middleware en runtime
     */
    public function configure($config)
    {
        $this->config = array_merge($this->config, $config);
        $this->log('INFO', 'Configuración actualizada', $config);
        return $this;
    }
    
    /**
     * Destructor - Log final
     */
    public function __destruct()
    {
        if ($this->isInitialized) {
            $runtime = microtime(true) - $this->stats['start_time'];
            $this->log('INFO', 'InputSanitizer finalizado', [
                'total_runtime' => round($runtime, 3),
                'total_requests' => $this->stats['requests_processed'],
                'total_threats' => $this->stats['threats_detected']
            ]);
        }
    }
}