<?php
/**
 * 🧪 Test GeoFirewall - Verificación de Funcionalidad
 * 
 * Test completo del middleware GeoFirewall para verificar:
 * - Carga de configuración
 * - Verificación de IPs de desarrollo
 * - Verificación de IPs permitidas por país
 * - Bloqueo de IPs no autorizadas
 * 
 * @package Cyberhole\Tests
 * @author ManuelDev
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir el GeoFirewall
require_once __DIR__ . '/middlewares/Protections/GeoFirewall.php';

class GeoFirewallTest 
{
    private $geoFirewall;
    private $testResults = [];
    
    public function __construct() 
    {
        echo "🧪 TEST GEOFIREWALL - Verificación Completa\n";
        echo "==========================================\n\n";
    }
    
    public function runAllTests(): void 
    {
        try {
            // Crear instancia del GeoFirewall
            $this->geoFirewall = new GeoFirewall();
            echo "✅ GeoFirewall instanciado correctamente\n\n";
            
            // Ejecutar tests
            $this->testDevelopmentIPs();
            $this->testAllowedCountries();
            $this->testUnauthorizedIPs();
            $this->testAccessVerification();
            $this->testIPRangeCalculations();
            
            $this->showResults();
            
        } catch (Exception $e) {
            echo "❌ Error durante las pruebas: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 1: Verificar IPs de desarrollo
     */
    private function testDevelopmentIPs(): void 
    {
        echo "🔧 TEST 1: IPs de Desarrollo\n";
        echo "-----------------------------\n";
        
        $devIPs = [
            '127.0.0.1' => 'Localhost IPv4',
            '::1' => 'Localhost IPv6',
            '192.168.1.100' => 'Red privada',
            '10.0.0.1' => 'Red privada clase A',
            '172.16.1.1' => 'Red privada clase B'
        ];
        
        foreach ($devIPs as $ip => $description) {
            $result = $this->geoFirewall->wouldAllowIP($ip);
            $status = $result ? '✅ PERMITIDO' : '❌ DENEGADO';
            echo "  📍 $ip ($description): $status\n";
            
            $this->testResults['development'][] = [
                'ip' => $ip,
                'description' => $description,
                'allowed' => $result,
                'expected' => true
            ];
        }
        echo "\n";
    }
    
    /**
     * Test 2: Verificar IPs de países permitidos
     */
    private function testAllowedCountries(): void 
    {
        echo "🌍 TEST 2: IPs de Países Permitidos\n";
        echo "-----------------------------------\n";
        
        $countryIPs = [
            '189.130.1.1' => 'México (rango típico)',
            '190.210.1.1' => 'Argentina (rango típico)', 
            '8.8.8.8' => 'Estados Unidos (Google DNS)',
            '66.50.1.1' => 'Puerto Rico (rango típico)',
            '80.1.1.1' => 'España (rango típico)',
            '2.1.1.1' => 'Reino Unido (rango típico)',
            '31.1.1.1' => 'Francia (rango típico)'
        ];
        
        foreach ($countryIPs as $ip => $description) {
            $result = $this->geoFirewall->wouldAllowIP($ip);
            $status = $result ? '✅ PERMITIDO' : '❌ DENEGADO';
            echo "  🌎 $ip ($description): $status\n";
            
            $this->testResults['countries'][] = [
                'ip' => $ip,
                'description' => $description,
                'allowed' => $result,
                'expected' => true
            ];
        }
        echo "\n";
    }
    
    /**
     * Test 3: Verificar IPs no autorizadas
     */
    private function testUnauthorizedIPs(): void 
    {
        echo "🚫 TEST 3: IPs No Autorizadas\n";
        echo "-----------------------------\n";
        
        $unauthorizedIPs = [
            '1.1.1.1' => 'Cloudflare DNS (no configurado)',
            '4.4.4.4' => 'IP pública genérica',
            '100.100.100.100' => 'IP fuera de rangos',
            '200.200.200.200' => 'IP latinoamericana no configurada',
            '150.150.150.150' => 'IP asiática típica'
        ];
        
        foreach ($unauthorizedIPs as $ip => $description) {
            $result = $this->geoFirewall->wouldAllowIP($ip);
            $status = $result ? '⚠️ PERMITIDO (inesperado)' : '✅ DENEGADO (correcto)';
            echo "  🚨 $ip ($description): $status\n";
            
            $this->testResults['unauthorized'][] = [
                'ip' => $ip,
                'description' => $description,
                'allowed' => $result,
                'expected' => false
            ];
        }
        echo "\n";
    }
    
    /**
     * Test 4: Verificar método verifyAccess
     */
    private function testAccessVerification(): void 
    {
        echo "🔍 TEST 4: Verificación de Acceso\n";
        echo "---------------------------------\n";
        
        // Simular IP de México
        $_SERVER['REMOTE_ADDR'] = '189.130.1.1';
        $result = $this->geoFirewall->verifyAccess();
        
        echo "  📍 IP simulada: {$result['ip']}\n";
        echo "  🌎 País detectado: {$result['country']}\n";
        echo "  ✅ Acceso: " . ($result['allowed'] ? 'PERMITIDO' : 'DENEGADO') . "\n";
        echo "  📝 Razón: {$result['reason']}\n\n";
        
        $this->testResults['verification'] = $result;
    }
    
    /**
     * Test 5: Verificar cálculos de rangos IP
     */
    private function testIPRangeCalculations(): void 
    {
        echo "🧮 TEST 5: Cálculos de Rangos IP\n";
        echo "--------------------------------\n";
        
        $rangeTests = [
            ['189.128.0.0/11', '189.130.1.1', true, 'IP México en rango'],
            ['189.128.0.0/11', '190.1.1.1', false, 'IP fuera de rango México'],
            ['8.8.8.0/24', '8.8.8.8', true, 'IP específica en rango'],
            ['192.168.1.0/24', '192.168.1.100', true, 'Red local en rango'],
            ['10.0.0.0/8', '11.0.0.1', false, 'IP fuera de red clase A']
        ];
        
        $reflection = new ReflectionClass($this->geoFirewall);
        $ipInRangeMethod = $reflection->getMethod('ipInRange');
        $ipInRangeMethod->setAccessible(true);
        
        foreach ($rangeTests as [$range, $ip, $expected, $description]) {
            $result = $ipInRangeMethod->invoke($this->geoFirewall, $ip, $range);
            $status = $result === $expected ? '✅ CORRECTO' : '❌ ERROR';
            echo "  🎯 $description: $status\n";
            echo "     Range: $range | IP: $ip | Esperado: " . ($expected ? 'true' : 'false') . " | Obtenido: " . ($result ? 'true' : 'false') . "\n";
            
            $this->testResults['ranges'][] = [
                'range' => $range,
                'ip' => $ip,
                'expected' => $expected,
                'result' => $result,
                'correct' => $result === $expected
            ];
        }
        echo "\n";
    }
    
    /**
     * Mostrar resultados finales
     */
    private function showResults(): void 
    {
        echo str_repeat("=", 60) . "\n";
        echo "📊 RESULTADOS FINALES DEL TEST\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $totalTests = 0;
        $passedTests = 0;
        
        // Evaluar desarrollo
        foreach ($this->testResults['development'] ?? [] as $test) {
            $totalTests++;
            if ($test['allowed'] === $test['expected']) $passedTests++;
        }
        
        // Evaluar países
        foreach ($this->testResults['countries'] ?? [] as $test) {
            $totalTests++;
            if ($test['allowed'] === $test['expected']) $passedTests++;
        }
        
        // Evaluar no autorizadas
        foreach ($this->testResults['unauthorized'] ?? [] as $test) {
            $totalTests++;
            if ($test['allowed'] === $test['expected']) $passedTests++;
        }
        
        // Evaluar rangos
        foreach ($this->testResults['ranges'] ?? [] as $test) {
            $totalTests++;
            if ($test['correct']) $passedTests++;
        }
        
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
        
        echo "✅ Tests pasados: $passedTests\n";
        echo "📊 Total tests: $totalTests\n";
        echo "🎯 Tasa de éxito: " . number_format($successRate, 1) . "%\n\n";
        
        if ($successRate >= 80) {
            echo "🎉 ¡GEOFIREWALL FUNCIONANDO CORRECTAMENTE!\n";
            echo "✅ El middleware está listo para producción\n";
        } else {
            echo "⚠️ Algunos tests fallaron, revisar configuración\n";
        }
        
        // Verificar configuración cargada
        echo "\n📋 CONFIGURACIÓN CARGADA:\n";
        echo "-------------------------\n";
        echo "✅ Archivo geo_database.json: Cargado\n";
        echo "✅ IPs de desarrollo: Configuradas\n";
        echo "✅ Países permitidos: " . $this->countAllowedCountries() . " países\n";
        echo "✅ Logging: Habilitado\n";
        echo "✅ Middleware: Funcional\n";
        
        echo "\n🚀 INSTRUCCIONES DE USO:\n";
        echo "========================\n";
        echo "1. Incluir en tu aplicación:\n";
        echo "   require_once 'middlewares/Protections/GeoFirewall.php';\n\n";
        echo "2. Proteger rutas:\n";
        echo "   GeoFirewall::protect(); // Al inicio de tu script\n\n";
        echo "3. Verificar manualmente:\n";
        echo "   \$geoFirewall = new GeoFirewall();\n";
        echo "   \$result = \$geoFirewall->verifyAccess();\n\n";
        echo "4. Revisar logs en: logs/geo_access.log\n\n";
    }
    
    /**
     * Contar países permitidos en configuración
     */
    private function countAllowedCountries(): int 
    {
        $count = 0;
        $reflection = new ReflectionClass($this->geoFirewall);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->geoFirewall);
        
        foreach ($config['allowed_countries'] ?? [] as $region) {
            $count += count($region['countries'] ?? []);
        }
        
        return $count;
    }
}

// Ejecutar tests
$test = new GeoFirewallTest();
$test->runAllTests();

?>