<?php
/**
 * 🧪 Test de GeoFirewall y RateLimiter con Sistema PSR-4
 * 
 * Pruebas exhaustivas para validar:
 * 1. Sistema de carga automática PSR-4
 * 2. GeoFirewall - Control geográfico por IP
 * 3. RateLimiter - Límites con integración geográfica
 * 
 * @package Cyberhole\Tests
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-21
 */

// Configurar entorno de pruebas
define('MIDDLEWARE_TESTING', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "🧪 INICIANDO TESTS DE GEOFIREWALL Y RATELIMITER CON PSR-4\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Incluir sistema de autoload
require_once __DIR__ . '/middlewares/Security/logging.php';

/**
 * Clase de testing para middlewares con PSR-4
 */
class MiddlewarePSR4Test 
{
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $autoloader;
    
    public function __construct() 
    {
        $this->autoloader = MiddlewareAutoloader::getInstance();
    }
    
    public function runAllTests(): void 
    {
        echo "🔧 EJECUTANDO TESTS DE MIDDLEWARES CON PSR-4\n\n";
        
        // Tests del sistema PSR-4
        $this->testPSR4System();
        
        // Tests de GeoFirewall
        $this->testGeoFirewall();
        
        // Tests de RateLimiter
        $this->testRateLimiter();
        
        // Tests de integración
        $this->testIntegration();
        
        // Mostrar resultados finales
        $this->showFinalResults();
    }
    
    /**
     * Tests del sistema PSR-4
     */
    private function testPSR4System(): void 
    {
        echo "🔧 TESTING SISTEMA PSR-4 AUTOLOADER\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Autoloader inicializado
        $this->test("PSR-4 - Autoloader inicializado", function() {
            return $this->autoloader instanceof MiddlewareAutoloader;
        });
        
        // Test 2: Configuración cargada
        $this->test("PSR-4 - Configuración cargada", function() {
            $stats = $this->autoloader->getStats();
            return $stats['total_classes'] > 0;
        });
        
        // Test 3: GeoFirewall existe en registro
        $this->test("PSR-4 - GeoFirewall registrado", function() {
            return $this->autoloader->classExists('GeoFirewall');
        });
        
        // Test 4: RateLimiter existe en registro
        $this->test("PSR-4 - RateLimiter registrado", function() {
            return $this->autoloader->classExists('RateLimiter');
        });
        
        // Test 5: Información de clases
        $this->test("PSR-4 - Información de clases", function() {
            $geoInfo = $this->autoloader->getClassInfo('GeoFirewall');
            $rateInfo = $this->autoloader->getClassInfo('RateLimiter');
            
            return $geoInfo && $rateInfo && 
                   isset($geoInfo['path']) && isset($rateInfo['path']);
        });
        
        // Test 6: Cargar GeoFirewall
        $this->test("PSR-4 - Cargar GeoFirewall", function() {
            $result = $this->autoloader->loadClass('GeoFirewall');
            $classExists = class_exists('GeoFirewall', false);
            return $result && $classExists;
        });
        
        // Test 7: Cargar RateLimiter
        $this->test("PSR-4 - Cargar RateLimiter", function() {
            $result = $this->autoloader->loadClass('RateLimiter');
            $classExists = class_exists('RateLimiter', false);
            return $result && $classExists;
        });
        
        echo "\n";
    }
    
    /**
     * Tests de GeoFirewall
     */
    private function testGeoFirewall(): void 
    {
        echo "🌍 TESTING GEOFIREWALL\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Cargar GeoFirewall antes de los tests
        $this->autoloader->loadClass('GeoFirewall');
        
        // Test 1: Instanciación
        $this->test("GeoFirewall - Instanciación", function() {
            try {
                if (!class_exists('GeoFirewall')) {
                    return false;
                }
                $geo = new GeoFirewall();
                return $geo instanceof GeoFirewall;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 2: Verificación de acceso básica
        $this->test("GeoFirewall - Verificación básica", function() {
            try {
                $geo = new GeoFirewall();
                $result = $geo->verifyAccess();
                return isset($result['allowed']) && isset($result['ip']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 3: Método wouldAllowIP
        $this->test("GeoFirewall - Método wouldAllowIP", function() {
            try {
                $geo = new GeoFirewall();
                $testIP = '192.168.1.100';
                $result = $geo->wouldAllowIP($testIP);
                return is_bool($result);
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 4: Estadísticas
        $this->test("GeoFirewall - Estadísticas", function() {
            try {
                $geo = new GeoFirewall();
                $stats = $geo->getAccessStats();
                return is_array($stats) && isset($stats['total_requests']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 5: IP de desarrollo
        $this->test("GeoFirewall - IP desarrollo (simulada)", function() {
            try {
                // Simular IP de desarrollo
                $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
                $geo = new GeoFirewall();
                $result = $geo->verifyAccess();
                
                // Debe permitir o denegar según configuración
                return isset($result['allowed']) && isset($result['reason']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "\n";
    }
    
    /**
     * Tests de RateLimiter
     */
    private function testRateLimiter(): void 
    {
        echo "🚦 TESTING RATELIMITER CON INTEGRACIÓN PSR-4\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Cargar RateLimiter antes de los tests
        $this->autoloader->loadClass('RateLimiter');
        
        // Test 1: Instanciación con PSR-4
        $this->test("RateLimiter - Instanciación PSR-4", function() {
            try {
                if (!class_exists('RateLimiter')) {
                    return false;
                }
                $config = [
                    'storage' => ['cache_file' => 'cache/test_rate_limiter_psr4.json']
                ];
                $limiter = new RateLimiter($config);
                return $limiter instanceof RateLimiter;
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                return false;
            }
        });
        
        // Test 2: Integración GeoFirewall
        $this->test("RateLimiter - Integración GeoFirewall", function() {
            try {
                $config = [
                    'geo_integration' => ['enabled' => true],
                    'storage' => ['cache_file' => 'cache/test_rate_limiter_geo.json']
                ];
                $limiter = new RateLimiter($config);
                $result = $limiter->checkLimits();
                
                // Debe incluir verificación geográfica
                return isset($result['allowed']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 3: Límites sin excepción de localhost
        $this->test("RateLimiter - Sin excepción localhost", function() {
            try {
                $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
                $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
                
                $config = [
                    'whitelist' => ['enabled' => true], // Habilitado pero sin IPs
                    'storage' => ['cache_file' => 'cache/test_rate_limiter_localhost.json']
                ];
                $limiter = new RateLimiter($config);
                $result = $limiter->checkLimits();
                
                // Localhost NO debe tener bypass automático
                return $result['allowed'] === true && 
                       !strpos($result['reason'], 'whitelist');
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 4: Detección de bot con User-Agent
        $this->test("RateLimiter - Detección bot UA", function() {
            try {
                $_SERVER['REMOTE_ADDR'] = '10.0.0.100';
                $_SERVER['HTTP_USER_AGENT'] = 'python-requests/2.25.1';
                
                $config = [
                    'storage' => ['cache_file' => 'cache/test_rate_limiter_bot.json'],
                    'bot_detection' => ['enabled' => true],
                    'geo_integration' => ['enabled' => false] // Desactivar geo para este test
                ];
                $limiter = new RateLimiter($config);
                $result = $limiter->checkLimits();
                
                // Debe detectar como bot
                return !$result['allowed'] && (isset($result['bot_reason']) || $result['type'] === 'bot_detected');
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 5: Estadísticas mejoradas
        $this->test("RateLimiter - Estadísticas mejoradas", function() {
            try {
                $limiter = new RateLimiter();
                $stats = $limiter->getStats();
                
                return isset($stats['total_ips']) && 
                       isset($stats['geo_integration_enabled']) &&
                       isset($stats['avg_geo_consistency']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "\n";
    }
    
    /**
     * Tests de integración PSR-4
     */
    private function testIntegration(): void 
    {
        echo "🔗 TESTING INTEGRACIÓN PSR-4 COMPLETA\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Flujo completo PSR-4
        $this->test("Integración - Flujo completo PSR-4", function() {
            try {
                // Simular request limpio
                $_SERVER['REMOTE_ADDR'] = '200.23.45.67'; // IP mexicana simulada
                $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Test)';
                $_GET = ['page' => 'home'];
                
                // 1. Verificar autoloader
                $autoloaderStats = $this->autoloader->getStats();
                
                // 2. Cargar e instanciar GeoFirewall
                $this->autoloader->loadClass('GeoFirewall');
                $geo = new GeoFirewall();
                $geoResult = $geo->verifyAccess();
                
                // 3. Cargar e instanciar RateLimiter
                $this->autoloader->loadClass('RateLimiter');
                $config = [
                    'storage' => ['cache_file' => 'cache/test_integration_psr4.json']
                ];
                $limiter = new RateLimiter($config);
                $rateResult = $limiter->checkLimits();
                
                return $autoloaderStats['total_classes'] > 0 && 
                       isset($geoResult['allowed']) &&
                       isset($rateResult['allowed']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 2: Dependencias PSR-4
        $this->test("Integración - Dependencias PSR-4", function() {
            try {
                // Verificar que RateLimiter puede usar GeoFirewall
                $rateInfo = $this->autoloader->getClassInfo('RateLimiter');
                $geoDependency = in_array('GeoFirewall', $rateInfo['dependencies']);
                
                return $geoDependency;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 3: Rendimiento con PSR-4
        $this->test("Integración - Rendimiento PSR-4", function() {
            $startTime = microtime(true);
            
            try {
                for ($i = 0; $i < 10; $i++) {
                    $_SERVER['REMOTE_ADDR'] = "192.168.1." . (100 + $i);
                    
                    $this->autoloader->loadClass('GeoFirewall');
                    $this->autoloader->loadClass('RateLimiter');
                    
                    $geo = new GeoFirewall();
                    $limiter = new RateLimiter([
                        'storage' => ['cache_file' => "cache/test_perf_$i.json"]
                    ]);
                    
                    $geo->verifyAccess();
                    $limiter->checkLimits();
                }
                
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                
                // Debe procesar en tiempo razonable
                return $executionTime < 3.0;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 4: Logs de autoloader
        $this->test("Integración - Logs autoloader", function() {
            $logPath = __DIR__ . '/logs/middleware_autoloader.log';
            return file_exists($logPath) && filesize($logPath) > 0;
        });
        
        echo "\n";
    }
    
    /**
     * Ejecutar un test individual
     */
    private function test(string $name, callable $testFunction): void 
    {
        $this->totalTests++;
        
        try {
            $result = $testFunction();
            
            if ($result) {
                echo "✅ $name\n";
                $this->passedTests++;
                $this->results[] = ['name' => $name, 'status' => 'PASS'];
            } else {
                echo "❌ $name\n";
                $this->results[] = ['name' => $name, 'status' => 'FAIL'];
            }
        } catch (Exception $e) {
            echo "💥 $name - ERROR: " . $e->getMessage() . "\n";
            $this->results[] = ['name' => $name, 'status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mostrar resultados finales
     */
    private function showFinalResults(): void 
    {
        echo "\n" . "=" . str_repeat("=", 60) . "\n";
        echo "📊 RESULTADOS FINALES DE TESTS PSR-4\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = ($this->passedTests / $this->totalTests) * 100;
        
        echo "Tests ejecutados: {$this->totalTests}\n";
        echo "Tests exitosos: {$this->passedTests}\n";
        echo "Tests fallidos: {$failedTests}\n";
        echo "Tasa de éxito: " . number_format($successRate, 2) . "%\n\n";
        
        // Estadísticas del autoloader
        $autoloaderStats = $this->autoloader->getStats();
        echo "📈 ESTADÍSTICAS DEL AUTOLOADER PSR-4:\n";
        echo "- Clases registradas: {$autoloaderStats['total_classes']}\n";
        echo "- Clases cargadas: {$autoloaderStats['loaded_classes']}\n";
        echo "- Porcentaje de carga: {$autoloaderStats['load_percentage']}%\n";
        echo "- Configuración: {$autoloaderStats['config_path']}\n\n";
        
        // Clases cargadas
        $loadedClasses = $this->autoloader->getLoadedClasses();
        if (!empty($loadedClasses)) {
            echo "📦 CLASES CARGADAS VIA PSR-4:\n";
            foreach ($loadedClasses as $class => $path) {
                echo "- $class: $path\n";
            }
            echo "\n";
        }
        
        if ($successRate >= 90) {
            echo "🎉 EXCELENTE: Sistema PSR-4 y middlewares funcionan correctamente\n";
        } elseif ($successRate >= 75) {
            echo "⚠️  BUENO: Sistema funciona con algunas observaciones\n";
        } else {
            echo "🚨 CRÍTICO: Sistema requiere revisión urgente\n";
        }
        
        // Mostrar tests fallidos
        if ($failedTests > 0) {
            echo "\n❌ TESTS FALLIDOS:\n";
            foreach ($this->results as $result) {
                if ($result['status'] !== 'PASS') {
                    echo "- {$result['name']} ({$result['status']})";
                    if (isset($result['error'])) {
                        echo " - {$result['error']}";
                    }
                    echo "\n";
                }
            }
        }
        
        echo "\n📁 ARCHIVOS GENERADOS:\n";
        echo "- logs/middleware_autoloader.log\n";
        echo "- logs/geo_access.log\n";
        echo "- logs/rate_limiter.log\n";
        echo "- cache/test_*.json (archivos de test)\n";
        
        echo "\n✨ Test de PSR-4 y middlewares completado\n";
    }
    
    /**
     * Limpiar archivos de test
     */
    public function cleanup(): void 
    {
        $testFiles = glob('cache/test_*.json');
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Restaurar superglobals
        $_GET = [];
        $_POST = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHP/Test';
    }
}

// Ejecutar tests
try {
    $tester = new MiddlewarePSR4Test();
    $tester->runAllTests();
    $tester->cleanup();
    
    echo "\n🎯 TESTS DE PSR-4 Y MIDDLEWARES FINALIZADOS\n";
    echo "Revisa los logs para detalles de funcionamiento\n\n";
    
} catch (Exception $e) {
    echo "💥 ERROR CRÍTICO EN TESTS: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>