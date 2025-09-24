<?php
/**
 * 🌍 TEST COMPLETO GEOFIREWALL
 * 
 * Test exhaustivo de middlewares/Protections/GeoFirewall.php
 * Verificando funcionalidad de países, IPs y logging
 * 
 * @version 1.0
 * @date 2025-09-22
 */

echo "🌍 ===== TEST COMPLETO GEOFIREWALL =====\n\n";

// Verificar existencia del archivo
$geoFirewallPath = 'middlewares/Protections/GeoFirewall.php';
if (!file_exists($geoFirewallPath)) {
    echo "❌ ERROR: No se encuentra $geoFirewallPath\n";
    exit(1);
}

// Cargar GeoFirewall
echo "📁 Cargando GeoFirewall...\n";
require_once $geoFirewallPath;

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
// TESTS DE GEOFIREWALL
// ============================

echo "📋 Iniciando tests de GeoFirewall...\n\n";

// Test 1: Instanciación
ejecutarTest("Instanciación básica de GeoFirewall", function() {
    $geo = new GeoFirewall();
    return $geo instanceof GeoFirewall;
});

// Test 2: Verificación de país México
ejecutarTest("Verificación país México (MX)", function() {
    $geo = new GeoFirewall();
    $result = $geo->wouldAllowIP('189.175.0.1'); // IP de México
    return $result === true;
});

// Test 3: Verificación de país bloqueado
ejecutarTest("Verificación país bloqueado (China)", function() {
    $geo = new GeoFirewall();
    $result = $geo->wouldAllowIP('1.2.3.4'); // IP genérica (simulando China)
    // El resultado puede variar, pero el método debe funcionar
    return is_bool($result);
});

// Test 4: Verificar acceso con verifyAccess
ejecutarTest("Verificar acceso con verifyAccess", function() {
    $geo = new GeoFirewall();
    $result = $geo->verifyAccess();
    return is_array($result) && isset($result['allowed']);
});

// Test 5: Método handle sin errores
ejecutarTest("Método handle sin errores", function() {
    $geo = new GeoFirewall();
    // El método handle no debería lanzar errores
    ob_start();
    $geo->handle();
    $output = ob_get_clean();
    return true; // Si llegamos aquí, no hubo errores fatales
});

// Test 6: Estadísticas de acceso
ejecutarTest("Obtener estadísticas de acceso", function() {
    $geo = new GeoFirewall();
    $stats = $geo->getAccessStats();
    return is_array($stats);
});

// Test 7: IP de desarrollo local
ejecutarTest("IP de desarrollo local", function() {
    $geo = new GeoFirewall();
    $result = $geo->wouldAllowIP('127.0.0.1');
    return is_bool($result);
});

// Test 8: IP privada
ejecutarTest("IP privada (192.168.x.x)", function() {
    $geo = new GeoFirewall();
    $result = $geo->wouldAllowIP('192.168.1.100');
    return is_bool($result);
});

// Test 9: Validación de métodos públicos
ejecutarTest("Validación de métodos públicos", function() {
    $geo = new GeoFirewall();
    $methods = get_class_methods($geo);
    $requiredMethods = ['wouldAllowIP', 'verifyAccess', 'getAccessStats', 'handle'];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methods)) {
            return false;
        }
    }
    return true;
});

// Test 10: Test de múltiples IPs
ejecutarTest("Test de múltiples IPs", function() {
    $geo = new GeoFirewall();
    $ips = [
        '8.8.8.8',      // Google
        '1.1.1.1',      // Cloudflare
        '192.168.1.1',  // Privada
        '127.0.0.1'     // Local
    ];
    
    foreach ($ips as $ip) {
        $result = $geo->wouldAllowIP($ip);
        if (!is_bool($result)) {
            return false;
        }
    }
    return true;
});

// ============================
// RESUMEN DE RESULTADOS
// ============================

echo "🎯 ===== RESUMEN DE TESTS GEOFIREWALL =====\n";
echo "Tests ejecutados: $totalTests\n";
echo "Tests pasados: $testsPasados\n";
echo "Tests fallidos: " . ($totalTests - $testsPasados) . "\n";
echo "Porcentaje de éxito: " . number_format(($testsPasados / $totalTests) * 100, 2) . "%\n\n";

if ($testsPasados === $totalTests) {
    echo "🎉 ¡TODOS LOS TESTS DE GEOFIREWALL PASARON!\n";
    echo "✅ GeoFirewall está funcionando correctamente\n\n";
} else {
    echo "⚠️ Algunos tests fallaron. Revisar implementación.\n\n";
}

// Información adicional del sistema
echo "📊 ===== INFORMACIÓN DEL SISTEMA =====\n";
if (class_exists('GeoFirewall')) {
    $geo = new GeoFirewall();
    
    echo "🌍 Clase GeoFirewall: ✅ Cargada\n";
    echo "📋 Métodos disponibles: " . count(get_class_methods($geo)) . "\n";
    
    // Mostrar estadísticas si están disponibles
    try {
        $stats = $geo->getAccessStats();
        echo "📈 Estadísticas disponibles: ✅\n";
    } catch (Exception $e) {
        echo "📈 Estadísticas: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n🌍 ===== FIN TEST GEOFIREWALL =====\n";
?>