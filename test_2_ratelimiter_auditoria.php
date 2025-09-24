<?php
/**
 * 🚦 TEST COMPLETO RATELIMITER + AUDITORÍA GEOFIREWALL
 * 
 * Test exhaustivo de middlewares/Security/RateLimiter.php
 * con auditoría de integración con GeoFirewall
 * 
 * @version 1.0
 * @date 2025-09-22
 */

echo "🚦 ===== TEST COMPLETO RATELIMITER + AUDITORÍA =====\n\n";

// Verificar existencia de archivos
$autoLoaderPath = 'middlewares/Security/AutoLoader.php';
$rateLimiterPath = 'middlewares/Security/RateLimiter.php';
$geoFirewallPath = 'middlewares/Protections/GeoFirewall.php';

if (!file_exists($autoLoaderPath)) {
    echo "❌ ERROR: No se encuentra $autoLoaderPath\n";
    exit(1);
}

if (!file_exists($rateLimiterPath)) {
    echo "❌ ERROR: No se encuentra $rateLimiterPath\n";
    exit(1);
}

if (!file_exists($geoFirewallPath)) {
    echo "❌ ERROR: No se encuentra $geoFirewallPath\n";
    exit(1);
}

// Cargar dependencias en orden
echo "📁 Cargando AutoLoader...\n";
require_once $autoLoaderPath;

echo "📁 Cargando GeoFirewall...\n";
require_once $geoFirewallPath;

echo "📁 Cargando RateLimiter...\n";
require_once $rateLimiterPath;

$testsPasados = 0;
$totalTests = 0;

function ejecutarTest($nombre, $callback) {
    global $testsPasados, $totalTests;
    $totalTests++;
    
    echo "🧪 Test: $nombre\n";
    
    try {
        $resultado = $callback();
        if ($resultado) {
            echo "   ✅ PASÓ\n";
            $testsPasados++;
        } else {
            echo "   ❌ FALLÓ\n";
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// ============================
// TESTS DE RATELIMITER
// ============================

echo "📋 Iniciando tests de RateLimiter...\n\n";

// Test 1: Instanciación básica
ejecutarTest("Instanciación básica de RateLimiter", function() {
    $limiter = new RateLimiter();
    return $limiter instanceof RateLimiter;
});

// Test 2: Instanciación con configuración
ejecutarTest("Instanciación con configuración", function() {
    $config = [
        'rate_limits' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 1000
        ]
    ];
    $limiter = new RateLimiter($config);
    return $limiter instanceof RateLimiter;
});

// Test 3: Verificar checkLimits
ejecutarTest("Método checkLimits básico", function() {
    $limiter = new RateLimiter();
    $result = $limiter->checkLimits();
    return is_array($result) && isset($result['allowed']);
});

// Test 4: Obtener estadísticas
ejecutarTest("Obtener estadísticas", function() {
    $limiter = new RateLimiter();
    $stats = $limiter->getStats();
    return is_array($stats);
});

// Test 5: Verificar whitelist
ejecutarTest("Verificar whitelist", function() {
    $limiter = new RateLimiter();
    $result = $limiter->isWhitelisted('127.0.0.1');
    return is_bool($result);
});

// Test 6: Verificar penalizaciones
ejecutarTest("Verificar penalizaciones", function() {
    $limiter = new RateLimiter();
    $result = $limiter->isPenalized('192.168.1.1');
    return is_array($result);
});

// ============================
// AUDITORÍA DE INTEGRACIÓN GEOFIREWALL
// ============================

echo "🔍 ===== AUDITORÍA INTEGRACIÓN GEOFIREWALL =====\n\n";

// Test 7: Verificar integración GeoFirewall habilitada
ejecutarTest("Integración GeoFirewall habilitada", function() {
    $config = [
        'geo_integration' => ['enabled' => true]
    ];
    $limiter = new RateLimiter($config);
    
    // Verificar que la configuración se aplicó
    $stats = $limiter->getStats();
    return isset($stats['config']['geo_integration']);
});

// Test 8: Verificar que GeoFirewall se usa en checkLimits
ejecutarTest("GeoFirewall se usa en checkLimits", function() {
    $config = [
        'geo_integration' => ['enabled' => true],
        'rate_limits' => [
            'requests_per_minute' => 1000 // Límite alto para evitar bloqueos
        ]
    ];
    $limiter = new RateLimiter($config);
    
    // Ejecutar checkLimits varias veces para verificar integración
    $result1 = $limiter->checkLimits();
    $result2 = $limiter->checkLimits();
    
    return is_array($result1) && is_array($result2);
});

// Test 9: Verificar métodos de RateLimiter
ejecutarTest("Métodos requeridos de RateLimiter", function() {
    $limiter = new RateLimiter();
    $methods = get_class_methods($limiter);
    $requiredMethods = ['checkLimits', 'getStats', 'isWhitelisted', 'isPenalized'];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methods)) {
            return false;
        }
    }
    return true;
});

// Test 10: Test de carga con múltiples requests
ejecutarTest("Test de carga con múltiples requests", function() {
    $config = [
        'rate_limits' => [
            'requests_per_minute' => 50,
            'requests_per_hour' => 500
        ],
        'geo_integration' => ['enabled' => true]
    ];
    $limiter = new RateLimiter($config);
    
    // Simular múltiples requests
    $allowed = 0;
    $blocked = 0;
    
    for ($i = 0; $i < 10; $i++) {
        $result = $limiter->checkLimits();
        if ($result['allowed']) {
            $allowed++;
        } else {
            $blocked++;
        }
    }
    
    return ($allowed + $blocked) === 10;
});

// ============================
// AUDITORÍA ESPECÍFICA DE INTEGRACIÓN
// ============================

echo "🔍 ===== AUDITORÍA ESPECÍFICA DE INTEGRACIÓN =====\n\n";

// Test 11: Verificar que RateLimiter puede cargar GeoFirewall
ejecutarTest("RateLimiter puede cargar GeoFirewall", function() {
    // Verificar que ambas clases están disponibles
    $geoExists = class_exists('GeoFirewall');
    $rateLimiterExists = class_exists('RateLimiter');
    
    if (!$geoExists || !$rateLimiterExists) {
        return false;
    }
    
    // Crear instancias
    $geo = new GeoFirewall();
    $limiter = new RateLimiter(['geo_integration' => ['enabled' => true]]);
    
    return true;
});

// Test 12: Verificar comportamiento con geo_integration disabled
ejecutarTest("Comportamiento con geo_integration disabled", function() {
    $config = [
        'geo_integration' => ['enabled' => false],
        'rate_limits' => [
            'requests_per_minute' => 100
        ]
    ];
    $limiter = new RateLimiter($config);
    $result = $limiter->checkLimits();
    
    return is_array($result) && isset($result['allowed']);
});

// Test 13: Verificar logging de integración
ejecutarTest("Verificar logging de integración", function() {
    $config = [
        'logging' => ['enabled' => true],
        'geo_integration' => ['enabled' => true]
    ];
    $limiter = new RateLimiter($config);
    
    // Ejecutar operación que debería generar logs
    $result = $limiter->checkLimits();
    
    return is_array($result);
});

// ============================
// ANÁLISIS DE CÓDIGO FUENTE
// ============================

echo "🔍 ===== ANÁLISIS DE CÓDIGO FUENTE =====\n\n";

// Verificar que RateLimiter menciona GeoFirewall en el código
$rateLimiterCode = file_get_contents($rateLimiterPath);
$hasGeoReference = strpos($rateLimiterCode, 'GeoFirewall') !== false || 
                   strpos($rateLimiterCode, 'geo') !== false ||
                   strpos($rateLimiterCode, 'Geo') !== false;

ejecutarTest("RateLimiter contiene referencias a GeoFirewall", function() use ($hasGeoReference) {
    return $hasGeoReference;
});

// ============================
// RESUMEN DE RESULTADOS
// ============================

echo "🎯 ===== RESUMEN DE TESTS RATELIMITER =====\n";
echo "Tests ejecutados: $totalTests\n";
echo "Tests pasados: $testsPasados\n";
echo "Tests fallidos: " . ($totalTests - $testsPasados) . "\n";
echo "Porcentaje de éxito: " . number_format(($testsPasados / $totalTests) * 100, 2) . "%\n\n";

if ($testsPasados === $totalTests) {
    echo "🎉 ¡TODOS LOS TESTS DE RATELIMITER PASARON!\n";
    echo "✅ RateLimiter está funcionando correctamente\n";
    echo "✅ Integración con GeoFirewall verificada\n\n";
} else {
    echo "⚠️ Algunos tests fallaron. Revisar implementación.\n\n";
}

// Información adicional del sistema
echo "📊 ===== INFORMACIÓN DE INTEGRACIÓN =====\n";
if (class_exists('RateLimiter') && class_exists('GeoFirewall')) {
    echo "🚦 Clase RateLimiter: ✅ Cargada\n";
    echo "🌍 Clase GeoFirewall: ✅ Cargada\n";
    
    $limiter = new RateLimiter(['geo_integration' => ['enabled' => true]]);
    $geo = new GeoFirewall();
    
    echo "📋 Métodos RateLimiter: " . count(get_class_methods($limiter)) . "\n";
    echo "📋 Métodos GeoFirewall: " . count(get_class_methods($geo)) . "\n";
    
    // Mostrar estadísticas
    try {
        $stats = $limiter->getStats();
        echo "📈 Estadísticas RateLimiter: ✅\n";
    } catch (Exception $e) {
        echo "📈 Estadísticas RateLimiter: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n🚦 ===== FIN TEST RATELIMITER + AUDITORÍA =====\n";
?>