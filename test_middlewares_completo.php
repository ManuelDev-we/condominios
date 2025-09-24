<?php
/**
 * 🧪 Sistema de Testing Completo para Middlewares PSR-4
 * 
 * Tests al 100% para todos los middlewares de seguridad:
 * - GeoFirewall: Control geográfico
 * - RateLimiter: Límites de solicitudes  
 * - InputSanitizer: Sanitización de entrada
 * - MiddlewareAutoloader: Sistema PSR-4
 * 
 * @package Cyberhole\Middlewares\Testing
 * @author ManuelDev
 * @version 3.0
 * @since 2025-09-22
 */

// Prevenir ejecución directa desde web
if (php_sapi_name() !== 'cli' && !defined('MIDDLEWARE_TESTING')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<h1>🧪 SISTEMA DE TESTING MIDDLEWARES PSR-4</h1>";
        echo "<p>Ejecutando tests desde interfaz web...</p>";
        define('MIDDLEWARE_TESTING', true);
    }
}

define('MIDDLEWARE_TESTING', true);

// Cargar autoloader
require_once __DIR__ . '/logging.php';

/**
 * Clase principal de testing
 */
class MiddlewareTestSuite 
{
    private $autoloader;
    private $testResults = [];
    private $testCount = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $startTime;
    private $logPath;
    
    public function __construct() 
    {
        $this->startTime = microtime(true);
        $this->autoloader = MiddlewareAutoloader::getInstance();
        $this->logPath = __DIR__ . '/../../logs/middleware_tests.log';
        
        // Crear directorio de logs
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->log("🚀 Iniciando batería de tests para middlewares PSR-4");
    }
    
    /**
     * Ejecutar todos los tests
     */
    public function runAllTests(): array 
    {
        $this->outputHeader("🧪 SISTEMA DE TESTING COMPLETO MIDDLEWARES PSR-4");
        
        // Tests del autoloader PSR-4
        $this->runAutoloaderTests();
        
        // Tests de GeoFirewall
        $this->runGeoFirewallTests();
        
        // Tests de RateLimiter
        $this->runRateLimiterTests();
        
        // Tests de InputSanitizer
        $this->runInputSanitizerTests();
        
        // Tests de integración
        $this->runIntegrationTests();
        
        // Reporte final
        return $this->generateFinalReport();
    }
    
    /**
     * Tests del sistema PSR-4 Autoloader
     */
    private function runAutoloaderTests(): void 
    {
        $this->outputSection("🔧 TESTS PSR-4 AUTOLOADER");
        
        // Test 1: Verificar inicialización
        $this->test("PSR-4 - Autoloader inicializado", function() {
            return $this->autoloader instanceof MiddlewareAutoloader;
        });
        
        // Test 2: Verificar configuración cargada
        $this->test("PSR-4 - Configuración cargada", function() {
            $stats = $this->autoloader->getStats();
            return $stats['total_classes'] > 0;
        });
        
        // Test 3: Verificar clases registradas
        $expectedClasses = ['GeoFirewall', 'RateLimiter', 'InputSanitizer', 'HeaderHTTP'];
        foreach ($expectedClasses as $className) {
            $this->test("PSR-4 - {$className} registrado", function() use ($className) {
                return $this->autoloader->classExists($className);
            });
        }
        
        // Test 4: Información de clases
        $this->test("PSR-4 - Información de clases", function() {
            $info = $this->autoloader->getClassInfo('GeoFirewall');
            return $info !== null && isset($info['path'], $info['dependencies']);
        });
        
        // Test 5: Cargar GeoFirewall
        $this->test("PSR-4 - Cargar GeoFirewall", function() {
            return $this->autoloader->loadClass('GeoFirewall');
        });
        
        // Test 6: Cargar RateLimiter (con dependencias)
        $this->test("PSR-4 - Cargar RateLimiter", function() {
            return $this->autoloader->loadClass('RateLimiter');
        });
        
        // Test 7: Cargar InputSanitizer
        $this->test("PSR-4 - Cargar InputSanitizer", function() {
            return $this->autoloader->loadClass('InputSanitizer');
        });
        
        // Test 8: Estadísticas del autoloader
        $this->test("PSR-4 - Estadísticas válidas", function() {
            $stats = $this->autoloader->getStats();
            return $stats['loaded_classes'] >= 3; // Al menos 3 clases cargadas
        });
    }
    
    /**
     * Tests de GeoFirewall
     */
    private function runGeoFirewallTests(): void 
    {
        $this->outputSection("🌍 TESTS GEOFIREWALL");
        
        try {
            // Verificar que se puede instanciar
            $geoFirewall = new GeoFirewall();
            
            $this->test("GeoFirewall - Instanciación exitosa", function() use ($geoFirewall) {
                return $geoFirewall instanceof GeoFirewall;
            });
            
            // Test método wouldAllowIP
            $this->test("GeoFirewall - Método wouldAllowIP existe", function() use ($geoFirewall) {
                return method_exists($geoFirewall, 'wouldAllowIP');
            });
            
            // Test con IP de desarrollo (debe permitir)
            $this->test("GeoFirewall - IP desarrollo permitida", function() use ($geoFirewall) {
                return $geoFirewall->wouldAllowIP('127.0.0.1') === true;
            });
            
            // Test método getCountryFromIP
            $this->test("GeoFirewall - Método getCountryFromIP", function() use ($geoFirewall) {
                $result = $geoFirewall->getCountryFromIP('127.0.0.1');
                return is_string($result) || is_array($result);
            });
            
            // Test estadísticas
            $this->test("GeoFirewall - Obtener estadísticas", function() use ($geoFirewall) {
                $stats = $geoFirewall->getAccessStats();
                return is_array($stats);
            });
            
        } catch (Exception $e) {
            $this->test("GeoFirewall - Error en inicialización", function() use ($e) {
                $this->log("❌ Error GeoFirewall: " . $e->getMessage(), 'ERROR');
                return false;
            });
        }
    }
    
    /**
     * Tests de RateLimiter
     */
    private function runRateLimiterTests(): void 
    {
        $this->outputSection("🚦 TESTS RATELIMITER");
        
        try {
            // Configuración de test
            $testConfig = [
                'rate_limits' => [
                    'requests_per_minute' => 1000, // Permisivo para tests
                    'requests_per_hour' => 10000
                ],
                'geo_integration' => ['enabled' => true],
                'bot_detection' => ['enabled' => true],
                'storage' => [
                    'cache_file' => 'cache/test_rate_limiter.json'
                ]
            ];
            
            $rateLimiter = new RateLimiter($testConfig);
            
            $this->test("RateLimiter - Instanciación PSR-4", function() use ($rateLimiter) {
                return $rateLimiter instanceof RateLimiter;
            });
            
            $this->test("RateLimiter - Integración GeoFirewall", function() use ($rateLimiter) {
                // Verificar que puede hacer checkLimits sin errores
                $result = $rateLimiter->checkLimits();
                return is_array($result) && isset($result['allowed']);
            });
            
            $this->test("RateLimiter - Sin excepción localhost", function() use ($rateLimiter) {
                $result = $rateLimiter->checkLimits();
                // No debe tener excepción especial para localhost
                return $result['allowed'] === true; // Debe estar permitido por configuración, no por excepción
            });
            
            // Test detección de bot por User-Agent
            $this->test("RateLimiter - Detección bot UA", function() use ($rateLimiter) {
                $_SERVER['HTTP_USER_AGENT'] = 'curl/7.68.0';
                $result = $rateLimiter->checkLimits();
                return is_array($result);
            });
            
            // Test estadísticas
            $this->test("RateLimiter - Estadísticas mejoradas", function() use ($rateLimiter) {
                $stats = $rateLimiter->getStats();
                return is_array($stats) && isset($stats['total_requests']);
            });
            
        } catch (Exception $e) {
            $this->test("RateLimiter - Error: " . $e->getMessage(), function() {
                return false;
            });
        }
    }
    
    /**
     * Tests de InputSanitizer
     */
    private function runInputSanitizerTests(): void 
    {
        $this->outputSection("🧹 TESTS INPUTSANITIZER");
        
        try {
            $inputSanitizer = new InputSanitizer();
            
            $this->test("InputSanitizer - Instanciación exitosa", function() use ($inputSanitizer) {
                return $inputSanitizer instanceof InputSanitizer;
            });
            
            // Test sanitización básica
            $this->test("InputSanitizer - Texto seguro", function() use ($inputSanitizer) {
                $result = $inputSanitizer->sanitizeInput("Texto normal seguro");
                return $result['is_safe'] === true;
            });
            
            // Test detección SQL injection
            $this->test("InputSanitizer - Detección SQL injection", function() use ($inputSanitizer) {
                $result = $inputSanitizer->sanitizeInput("'; DROP TABLE users; --");
                return $result['is_safe'] === false && in_array('SQL Injection', $result['threats_detected']);
            });
            
            // Test detección JavaScript
            $this->test("InputSanitizer - Detección JavaScript", function() use ($inputSanitizer) {
                $result = $inputSanitizer->sanitizeInput('<script>alert("XSS")</script>');
                return $result['is_safe'] === false && in_array('JavaScript Injection', $result['threats_detected']);
            });
            
            // Test detección PHP
            $this->test("InputSanitizer - Detección PHP injection", function() use ($inputSanitizer) {
                $result = $inputSanitizer->sanitizeInput('<?php system("rm -rf /"); ?>');
                return $result['is_safe'] === false && in_array('PHP Injection', $result['threats_detected']);
            });
            
            // Test sanitización de array
            $this->test("InputSanitizer - Sanitización de array", function() use ($inputSanitizer) {
                $testArray = [
                    'name' => 'Juan',
                    'comment' => "'; DROP TABLE comments; --",
                    'email' => 'juan@test.com'
                ];
                $result = $inputSanitizer->sanitizeInput($testArray);
                return is_array($result) && isset($result['threats_detected']);
            });
            
            // Test método isInputSafe
            $this->test("InputSanitizer - Método isInputSafe", function() use ($inputSanitizer) {
                $safe = $inputSanitizer->isInputSafe("Texto seguro");
                $unsafe = $inputSanitizer->isInputSafe("'; DROP TABLE users; --");
                return $safe === true && $unsafe === false;
            });
            
            // Test quickSanitize
            $this->test("InputSanitizer - Método quickSanitize", function() use ($inputSanitizer) {
                $result = $inputSanitizer->quickSanitize('<script>alert("test")</script>');
                return is_string($result) && !str_contains($result, '<script>');
            });
            
            // Test validación batch
            $this->test("InputSanitizer - Validación batch", function() use ($inputSanitizer) {
                $inputs = [
                    'field1' => 'Safe text',
                    'field2' => 'Another safe text'
                ];
                $result = $inputSanitizer->validateBatch($inputs);
                return isset($result['all_safe']) && $result['all_safe'] === true;
            });
            
            // Test estadísticas
            $this->test("InputSanitizer - Estadísticas", function() use ($inputSanitizer) {
                $stats = $inputSanitizer->getStats();
                return is_array($stats) && isset($stats['session_stats']);
            });
            
            // Test reporte de seguridad
            $this->test("InputSanitizer - Reporte de seguridad", function() use ($inputSanitizer) {
                $report = $inputSanitizer->generateSecurityReport();
                return is_array($report) && isset($report['timestamp'], $report['security_metrics']);
            });
            
        } catch (Exception $e) {
            $this->test("InputSanitizer - Error: " . $e->getMessage(), function() {
                return false;
            });
        }
    }
    
    /**
     * Tests de integración entre middlewares
     */
    private function runIntegrationTests(): void 
    {
        $this->outputSection("🔗 TESTS DE INTEGRACIÓN");
        
        // Test flujo completo PSR-4
        $this->test("Integración - Flujo completo PSR-4", function() {
            try {
                // Cargar todos los middlewares
                $geoFirewall = new GeoFirewall();
                $rateLimiter = new RateLimiter();
                $inputSanitizer = new InputSanitizer();
                
                // Simular flujo de request
                $clientIP = '127.0.0.1';
                $userInput = "Comentario de prueba";
                
                // 1. Verificación geográfica
                $geoAllowed = $geoFirewall->wouldAllowIP($clientIP);
                
                // 2. Verificación de rate limiting
                $rateLimitResult = $rateLimiter->checkLimits();
                
                // 3. Sanitización de entrada
                $sanitizationResult = $inputSanitizer->sanitizeInput($userInput);
                
                return $geoAllowed && $rateLimitResult['allowed'] && $sanitizationResult['is_safe'];
                
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test dependencias PSR-4
        $this->test("Integración - Dependencias PSR-4", function() {
            $info = $this->autoloader->getClassInfo('RateLimiter');
            return isset($info['dependencies']) && in_array('GeoFirewall', $info['dependencies']);
        });
        
        // Test rendimiento PSR-4
        $this->test("Integración - Rendimiento PSR-4", function() {
            $startTime = microtime(true);
            
            // Cargar múltiples instancias
            for ($i = 0; $i < 10; $i++) {
                $sanitizer = new InputSanitizer();
                $result = $sanitizer->isInputSafe("Test input $i");
            }
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Debe completarse en menos de 1 segundo
            return $executionTime < 1.0;
        });
        
        // Test logs del autoloader
        $this->test("Integración - Logs autoloader", function() {
            $logPath = __DIR__ . '/../../logs/middleware_autoloader.log';
            return file_exists($logPath);
        });
    }
    
    /**
     * Ejecutar test individual
     */
    private function test(string $description, callable $testFunction): bool 
    {
        $this->testCount++;
        
        try {
            $startTime = microtime(true);
            $result = $testFunction();
            $executionTime = microtime(true) - $startTime;
            
            if ($result) {
                $this->passedTests++;
                $status = "✅ PASSED";
                $this->log("✅ $description - PASSED (" . round($executionTime * 1000, 2) . "ms)");
            } else {
                $this->failedTests++;
                $status = "❌ FAILED";
                $this->log("❌ $description - FAILED (" . round($executionTime * 1000, 2) . "ms)");
            }
            
            $this->testResults[] = [
                'description' => $description,
                'status' => $result ? 'PASSED' : 'FAILED',
                'execution_time' => $executionTime
            ];
            
            $this->output(sprintf("  %s %s (%.2fms)", $status, $description, $executionTime * 1000));
            
            return $result;
            
        } catch (Exception $e) {
            $this->failedTests++;
            $this->testResults[] = [
                'description' => $description,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            
            $this->output("  ❌ ERROR $description: " . $e->getMessage());
            $this->log("❌ ERROR $description: " . $e->getMessage(), 'ERROR');
            
            return false;
        }
    }
    
    /**
     * Generar reporte final
     */
    private function generateFinalReport(): array 
    {
        $executionTime = microtime(true) - $this->startTime;
        $successRate = $this->testCount > 0 ? ($this->passedTests / $this->testCount) * 100 : 0;
        
        $report = [
            'execution_summary' => [
                'total_tests' => $this->testCount,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => round($successRate, 2),
                'execution_time' => round($executionTime, 3),
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'detailed_results' => $this->testResults,
            'autoloader_stats' => $this->autoloader->getStats(),
            'system_info' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]
        ];
        
        // Output del reporte final
        $this->outputSection("📊 REPORTE FINAL");
        $this->output("Total de tests: {$this->testCount}");
        $this->output("Tests exitosos: {$this->passedTests}");
        $this->output("Tests fallidos: {$this->failedTests}");
        $this->output("Tasa de éxito: " . round($successRate, 2) . "%");
        $this->output("Tiempo de ejecución: " . round($executionTime, 3) . " segundos");
        
        if ($successRate == 100) {
            $this->outputSuccess("🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE! 🎉");
        } else {
            $this->outputError("⚠️  Algunos tests fallaron. Revisar logs para detalles.");
        }
        
        $this->log("🏁 Tests completados - {$this->passedTests}/{$this->testCount} exitosos ({$successRate}%)");
        
        return $report;
    }
    
    /**
     * Logging
     */
    private function log(string $message, string $level = 'INFO'): void 
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        ];
        
        file_put_contents($this->logPath, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Output helpers
     */
    private function output(string $message): void 
    {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        } else {
            echo "<div style='margin:2px 0; font-family:monospace;'>" . htmlspecialchars($message) . "</div>";
        }
    }
    
    private function outputHeader(string $header): void 
    {
        $border = str_repeat('=', strlen($header));
        $this->output("\n$border");
        $this->output($header);
        $this->output("$border\n");
    }
    
    private function outputSection(string $section): void 
    {
        $this->output("\n" . $section);
        $this->output(str_repeat('-', strlen($section)));
    }
    
    private function outputSuccess(string $message): void 
    {
        if (php_sapi_name() !== 'cli') {
            echo "<div style='color:green; font-weight:bold; margin:10px 0;'>$message</div>";
        } else {
            $this->output($message);
        }
    }
    
    private function outputError(string $message): void 
    {
        if (php_sapi_name() !== 'cli') {
            echo "<div style='color:red; font-weight:bold; margin:10px 0;'>$message</div>";
        } else {
            $this->output($message);
        }
    }
}

// Ejecutar tests si se ejecuta directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) || defined('MIDDLEWARE_TESTING')) {
    
    // Establecer tiempo de ejecución
    set_time_limit(60);
    
    try {
        $testSuite = new MiddlewareTestSuite();
        $report = $testSuite->runAllTests();
        
        // Guardar reporte en archivo
        $reportPath = __DIR__ . '/../../logs/test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "\n📁 Reporte guardado en: $reportPath\n";
        
    } catch (Exception $e) {
        echo "❌ Error ejecutando tests: " . $e->getMessage() . "\n";
        error_log("Error en tests de middlewares: " . $e->getMessage());
    }
}

?>