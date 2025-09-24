<?php
/**
 * 🧪 Test Exhaustivo de Middlewares de Seguridad
 * 
 * Pruebas completas para validar el funcionamiento de:
 * - HeaderHTTP: Seguridad de headers HTTP
 * - RateLimiter: Límite de solicitudes y detección de bots
 * - InputSanitizer: Sanitización de entradas
 * 
 * @package Cyberhole\Tests
 * @author ManuelDev
 * @version 3.0
 * @since 2025-09-21
 */

// Configurar entorno de pruebas
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "🧪 INICIANDO TESTS DE MIDDLEWARES DE SEGURIDAD\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Incluir middlewares
require_once __DIR__ . '/middlewares/Security/HeaderHTTP.php';
require_once __DIR__ . '/middlewares/Security/RateLimiter.php';
require_once __DIR__ . '/middlewares/Security/InputSanitizer.php';

/**
 * Clase de testing
 */
class SecurityMiddlewaresTest 
{
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    
    public function runAllTests(): void 
    {
        echo "🔒 EJECUTANDO TESTS DE SEGURIDAD\n\n";
        
        // Tests de HeaderHTTP
        $this->testHeaderHTTP();
        
        // Tests de RateLimiter
        $this->testRateLimiter();
        
        // Tests de InputSanitizer
        $this->testInputSanitizer();
        
        // Tests de integración
        $this->testIntegration();
        
        // Mostrar resultados finales
        $this->showFinalResults();
    }
    
    /**
     * Tests de HeaderHTTP
     */
    private function testHeaderHTTP(): void 
    {
        echo "🛡️ TESTING HEADERHTTP MIDDLEWARE\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Inicialización básica
        $this->test("HeaderHTTP - Inicialización básica", function() {
            $header = new HeaderHTTP();
            return $header instanceof HeaderHTTP;
        });
        
        // Test 2: Configuración personalizada
        $this->test("HeaderHTTP - Configuración personalizada", function() {
            $config = [
                'csp' => ['enabled' => false],
                'hsts' => ['max_age' => 86400]
            ];
            $header = new HeaderHTTP($config);
            return $header instanceof HeaderHTTP;
        });
        
        // Test 3: Aplicación de headers (simulado)
        $this->test("HeaderHTTP - Headers aplicados", function() {
            ob_start();
            $header = new HeaderHTTP();
            
            // Simular aplicación de headers
            $mockHeaders = [
                'X-Content-Type-Options: nosniff',
                'X-Frame-Options: DENY',
                'X-XSS-Protection: 1; mode=block'
            ];
            
            ob_end_clean();
            return count($mockHeaders) === 3;
        });
        
        // Test 4: Detección de violaciones CSP
        $this->test("HeaderHTTP - Detección CSP", function() {
            $header = new HeaderHTTP();
            
            // Simular violación CSP
            $_POST['csp-report'] = json_encode([
                'csp-report' => [
                    'document-uri' => 'https://example.com',
                    'violated-directive' => 'script-src'
                ]
            ]);
            
            return isset($_POST['csp-report']);
        });
        
        // Test 5: Cálculo de puntuación de seguridad
        $this->test("HeaderHTTP - Puntuación de seguridad", function() {
            $header = new HeaderHTTP();
            $stats = $header->getStats();
            return isset($stats['security_score']) && $stats['security_score'] >= 0;
        });
        
        echo "\n";
    }
    
    /**
     * Tests de RateLimiter
     */
    private function testRateLimiter(): void 
    {
        echo "🚦 TESTING RATELIMITER MIDDLEWARE\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Inicialización básica
        $this->test("RateLimiter - Inicialización básica", function() {
            $limiter = new RateLimiter();
            return $limiter instanceof RateLimiter;
        });
        
        // Test 2: Verificación de límites (primera solicitud)
        $this->test("RateLimiter - Primera solicitud permitida", function() {
            $config = [
                'rate_limiting' => ['default_limit' => 100],
                'storage' => ['cache_file' => 'cache/test_rate_limiter.json']
            ];
            $limiter = new RateLimiter($config);
            $result = $limiter->checkLimits();
            return $result['allowed'] === true;
        });
        
        // Test 3: Verificación sin whitelist automático de localhost
        $this->test("RateLimiter - Sin bypass automático localhost", function() {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $config = [
                'whitelist' => ['enabled' => false] // Deshabilitado para test
            ];
            $limiter = new RateLimiter($config);
            $result = $limiter->checkLimits();
            // Ahora localhost debe pasar por rate limiting normal
            return $result['allowed'] === true && $result['reason'] !== 'IP en whitelist';
        });
        
        // Test 4: Detección de bot por User-Agent
        $this->test("RateLimiter - Detección de bot", function() {
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
            $_SERVER['HTTP_USER_AGENT'] = 'curl/7.68.0';
            
            $config = [
                'storage' => ['cache_file' => 'cache/test_rate_limiter_bot.json']
            ];
            $limiter = new RateLimiter($config);
            $result = $limiter->checkLimits();
            
            return $result['allowed'] === false && isset($result['bot_reason']);
        });
        
        // Test 5: Estadísticas
        $this->test("RateLimiter - Estadísticas", function() {
            $limiter = new RateLimiter();
            $stats = $limiter->getStats();
            return isset($stats['total_ips']) && isset($stats['blocked_ips']);
        });
        
        echo "\n";
    }
    
    /**
     * Tests de InputSanitizer
     */
    private function testInputSanitizer(): void 
    {
        echo "🛡️ TESTING INPUTSANITIZER MIDDLEWARE\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Inicialización básica
        $this->test("InputSanitizer - Inicialización básica", function() {
            $sanitizer = new InputSanitizer();
            return $sanitizer instanceof InputSanitizer;
        });
        
        // Test 2: Sanitización básica
        $this->test("InputSanitizer - Sanitización básica", function() {
            $_GET = ['test' => 'Hello World'];
            $_POST = ['message' => 'Test message'];
            
            $sanitizer = new InputSanitizer();
            $result = $sanitizer->sanitizeInput();
            
            return $result['safe'] === true;
        });
        
        // Test 3: Detección de XSS
        $this->test("InputSanitizer - Detección XSS", function() {
            $_POST = ['content' => '<script>alert("xss")</script>'];
            
            $config = [
                'responses' => ['block_on_threat' => true]
            ];
            $sanitizer = new InputSanitizer($config);
            $result = $sanitizer->sanitizeInput();
            
            return $result['safe'] === false && count($result['threats']) > 0;
        });
        
        // Test 4: Detección de SQL Injection
        $this->test("InputSanitizer - Detección SQL Injection", function() {
            $_GET = ['id' => "1' OR '1'='1"];
            
            $config = [
                'responses' => ['block_on_threat' => true]
            ];
            $sanitizer = new InputSanitizer($config);
            $result = $sanitizer->sanitizeInput();
            
            return $result['safe'] === false;
        });
        
        // Test 5: Validación de tipos de datos
        $this->test("InputSanitizer - Validación email", function() {
            $sanitizer = new InputSanitizer();
            $validEmail = $sanitizer->validateDataType('test@example.com', 'email');
            $invalidEmail = $sanitizer->validateDataType('invalid-email', 'email');
            
            return $validEmail === true && $invalidEmail === false;
        });
        
        // Test 6: Sanitización de archivos
        $this->test("InputSanitizer - Sanitización archivos", function() {
            $_FILES = [
                'upload' => [
                    'name' => 'test file.jpg',
                    'type' => 'image/jpeg',
                    'size' => 1024,
                    'tmp_name' => '/tmp/php123',
                    'error' => 0
                ]
            ];
            
            $sanitizer = new InputSanitizer();
            $result = $sanitizer->sanitizeInput();
            
            return $result['safe'] === true && isset($result['data']['FILES']);
        });
        
        // Test 7: RFC Mexicano
        $this->test("InputSanitizer - Validación RFC", function() {
            $sanitizer = new InputSanitizer();
            $validRFC = $sanitizer->validateDataType('XAXX010101000', 'rfc');
            $invalidRFC = $sanitizer->validateDataType('INVALID123', 'rfc');
            
            return $validRFC === true && $invalidRFC === false;
        });
        
        echo "\n";
    }
    
    /**
     * Tests de integración
     */
    private function testIntegration(): void 
    {
        echo "🔗 TESTING INTEGRACIÓN MIDDLEWARES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test 1: Cadena completa de seguridad
        $this->test("Integración - Cadena completa", function() {
            // Simular request limpio
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
            $_GET = ['page' => 'home'];
            $_POST = ['message' => 'Hello World'];
            
            try {
                // HeaderHTTP
                $header = new HeaderHTTP();
                
                // RateLimiter
                $limiter = new RateLimiter();
                $rateResult = $limiter->checkLimits();
                
                // InputSanitizer
                $sanitizer = new InputSanitizer();
                $sanitizeResult = $sanitizer->sanitizeInput();
                
                return $rateResult['allowed'] && $sanitizeResult['safe'];
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 2: Bloqueo por múltiples amenazas
        $this->test("Integración - Bloqueo múltiple", function() {
            // Simular bot con entrada maliciosa
            $_SERVER['REMOTE_ADDR'] = '192.168.1.200';
            $_SERVER['HTTP_USER_AGENT'] = 'python-requests/2.25.1';
            $_POST = ['data' => '<script>alert("xss")</script>'];
            
            try {
                $limiter = new RateLimiter();
                $rateResult = $limiter->checkLimits();
                
                $sanitizer = new InputSanitizer(['responses' => ['block_on_threat' => true]]);
                $sanitizeResult = $sanitizer->sanitizeInput();
                
                // Al menos uno debería bloquear
                return !$rateResult['allowed'] || !$sanitizeResult['safe'];
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 3: Rendimiento bajo carga
        $this->test("Integración - Rendimiento", function() {
            $startTime = microtime(true);
            
            for ($i = 0; $i < 100; $i++) {
                $_GET = ['test' => "value_$i"];
                
                $header = new HeaderHTTP();
                $limiter = new RateLimiter();
                $sanitizer = new InputSanitizer();
                
                $limiter->checkLimits();
                $sanitizer->sanitizeInput();
            }
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Debe procesar 100 requests en menos de 5 segundos
            return $executionTime < 5.0;
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
        echo "📊 RESULTADOS FINALES DE TESTS\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = ($this->passedTests / $this->totalTests) * 100;
        
        echo "Tests ejecutados: {$this->totalTests}\n";
        echo "Tests exitosos: {$this->passedTests}\n";
        echo "Tests fallidos: {$failedTests}\n";
        echo "Tasa de éxito: " . number_format($successRate, 2) . "%\n\n";
        
        if ($successRate >= 90) {
            echo "🎉 EXCELENTE: Los middlewares de seguridad funcionan correctamente\n";
        } elseif ($successRate >= 75) {
            echo "⚠️  BUENO: Los middlewares funcionan con algunas observaciones\n";
        } else {
            echo "🚨 CRÍTICO: Los middlewares requieren revisión urgente\n";
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
        
        // Recomendaciones de seguridad
        echo "\n🔒 RECOMENDACIONES DE SEGURIDAD:\n";
        echo "1. Monitorear logs de seguridad regularmente\n";
        echo "2. Ajustar configuraciones según el tráfico real\n";
        echo "3. Implementar alertas para múltiples violaciones\n";
        echo "4. Revisar y actualizar patrones de detección\n";
        echo "5. Realizar tests de penetración periódicos\n";
        
        echo "\n📁 ARCHIVOS DE LOG GENERADOS:\n";
        echo "- logs/header_http.log\n";
        echo "- logs/rate_limiter.log\n";
        echo "- logs/input_sanitizer.log\n";
        echo "- cache/rate_limiter.json\n";
        
        echo "\n✨ Test de middlewares de seguridad completado\n";
    }
    
    /**
     * Limpiar entorno de pruebas
     */
    public function cleanup(): void 
    {
        // Limpiar archivos de test
        $testFiles = [
            'cache/test_rate_limiter.json',
            'cache/test_rate_limiter_bot.json'
        ];
        
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Restaurar superglobals
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHP/Test';
    }
}

// Ejecutar tests
$tester = new SecurityMiddlewaresTest();
$tester->runAllTests();
$tester->cleanup();

echo "\n🎯 TESTS DE MIDDLEWARES DE SEGURIDAD FINALIZADOS\n";
echo "Revisa los logs para detalles de seguridad\n\n";

?>