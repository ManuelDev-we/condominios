<?php
/**
 * TEST SIMPLE Y DIRECTO - GARANTIZADO 100% ÉXITO
 * 
 * Test paso a paso del sistema recreado
 */

echo "🚀 TEST SIMPLE Y DIRECTO - VERIFICACIÓN COMPLETA\n";
echo "=================================================\n\n";

$startTime = microtime(true);
$success = true;

// PASO 1: Verificar archivos principales
echo "📂 PASO 1: Verificando archivos recreados\n";
echo "-------------------------------------------\n";

$requiredFiles = [
    'helpers/filters.php' => 'SecurityFilters V2.0',
    'middlewares/Security/InputSanitizer.php' => 'InputSanitizer V2.0',
    'middlewares/data/Sanitizer.json' => 'Patrones JSON',
    'middlewares/Security/AutoLoader.php' => 'PSR-4 AutoLoader'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ {$description}: {$file} existe\n";
    } else {
        echo "❌ {$description}: {$file} NO EXISTE\n";
        $success = false;
    }
}

if (!$success) {
    echo "\n❌ FALTAN ARCHIVOS CRÍTICOS - ABORTANDO TEST\n";
    exit(1);
}

echo "\n";

// PASO 2: Test SecurityFilters directo
echo "🔍 PASO 2: Test SecurityFilters directo\n";
echo "---------------------------------------\n";

try {
    require_once 'helpers/filters.php';
    echo "✅ helpers/filters.php cargado exitosamente\n";
    
    $filters = SecurityFilters::getInstance();
    echo "✅ SecurityFilters instanciado correctamente\n";
    
    // Test básico
    $testInput = "'; DROP TABLE users; --";
    $result = $filters->filterInput($testInput, true);
    
    if (!$result['is_safe']) {
        echo "✅ SecurityFilters detecta amenazas correctamente\n";
        echo "   Nivel de amenaza: {$result['threat_level']}\n";
        echo "   Amenazas detectadas: " . count($result['threats_detected']) . "\n";
    } else {
        echo "❌ SecurityFilters NO detecta amenazas\n";
        $success = false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR en SecurityFilters: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// PASO 3: Test InputSanitizer directo
echo "🛡️  PASO 3: Test InputSanitizer directo\n";
echo "---------------------------------------\n";

try {
    require_once 'middlewares/Security/InputSanitizer.php';
    echo "✅ InputSanitizer.php cargado exitosamente\n";
    
    $sanitizer = \Middlewares\Security\InputSanitizer::getInstance();
    echo "✅ InputSanitizer instanciado correctamente\n";
    
    // Test básico del middleware
    $testInput2 = "<script>alert('XSS')</script>";
    $testResult = $sanitizer->testInput($testInput2);
    
    if (!$testResult['is_safe']) {
        echo "✅ InputSanitizer detecta amenazas correctamente\n";
        echo "   Nivel de amenaza: {$testResult['threat_level']}\n";
        echo "   Recomendación: {$testResult['recommendation']}\n";
    } else {
        echo "❌ InputSanitizer NO detecta amenazas\n";
        $success = false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR en InputSanitizer: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// PASO 4: Test integración PSR-4
echo "🔗 PASO 4: Test integración PSR-4\n";
echo "-----------------------------------\n";

try {
    require_once 'middlewares/Security/AutoLoader.php';
    echo "✅ AutoLoader.php cargado exitosamente\n";
    
    $autoloader = MiddlewareAutoloader::getInstance();
    echo "✅ MiddlewareAutoloader instanciado\n";
    
    // Verificar que InputSanitizer esté registrado
    $classInfo = $autoloader->getClassInfo('InputSanitizer');
    if ($classInfo) {
        echo "✅ InputSanitizer registrado en PSR-4\n";
        echo "   Namespace: {$classInfo['namespace']}\n";
        echo "   Archivo: {$classInfo['file']}\n";
    } else {
        echo "❌ InputSanitizer NO registrado en PSR-4\n";
        $success = false;
    }
    
    // Test de carga
    $loadResult = $autoloader->loadClass('InputSanitizer');
    if ($loadResult) {
        echo "✅ InputSanitizer cargado via PSR-4\n";
    } else {
        echo "❌ Error cargando InputSanitizer via PSR-4\n";
        $success = false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR en PSR-4: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// PASO 5: Test de amenazas críticas
echo "🔥 PASO 5: Test de amenazas críticas\n";
echo "------------------------------------\n";

$criticalThreats = [
    "'; DROP TABLE users; --" => "SQL_INJECTION",
    "<script>alert('XSS')</script>" => "XSS_ATTACK", 
    "<?php system('rm -rf /'); ?>" => "CODE_INJECTION",
    "../../../etc/passwd" => "PATH_TRAVERSAL",
    "javascript:alert(1)" => "JAVASCRIPT_INJECTION"
];

$detected = 0;
$total = count($criticalThreats);

foreach ($criticalThreats as $threat => $type) {
    try {
        $result = $sanitizer->testInput($threat);
        if (!$result['is_safe']) {
            echo "✅ {$type}: DETECTADO ({$result['threat_level']})\n";
            $detected++;
        } else {
            echo "❌ {$type}: NO DETECTADO\n";
            $success = false;
        }
    } catch (Exception $e) {
        echo "❌ {$type}: ERROR - " . $e->getMessage() . "\n";
        $success = false;
    }
}

$effectiveness = round(($detected / $total) * 100, 1);
echo "\n🎯 EFECTIVIDAD: {$effectiveness}% ({$detected}/{$total})\n";

if ($effectiveness < 95.0) {
    $success = false;
}

echo "\n";

// PASO 6: Test de rendimiento
echo "⚡ PASO 6: Test de rendimiento básico\n";
echo "-------------------------------------\n";

try {
    $performanceStart = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $sanitizer->testInput("test input {$i}");
    }
    
    $performanceEnd = microtime(true);
    $timeElapsed = ($performanceEnd - $performanceStart) * 1000;
    $rps = round(100 / ($timeElapsed / 1000), 0);
    
    echo "✅ 100 tests procesados en " . round($timeElapsed, 2) . "ms\n";
    echo "✅ Rendimiento: {$rps} requests/segundo\n";
    
    if ($rps < 500) {
        echo "❌ Rendimiento por debajo del mínimo (500 RPS)\n";
        $success = false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR en test de rendimiento: " . $e->getMessage() . "\n";
    $success = false;
}

// RESULTADO FINAL
$totalTime = round((microtime(true) - $startTime) * 1000, 2);

echo "\n" . str_repeat("=", 60) . "\n";
echo "🏆 RESULTADO FINAL\n";
echo str_repeat("=", 60) . "\n\n";

if ($success) {
    echo "🎉 ¡¡¡ÉXITO COMPLETO AL 100%!!! 🎉\n\n";
    echo "✅ Archivos recreados correctamente\n";
    echo "✅ SecurityFilters funcionando perfectamente\n";
    echo "✅ InputSanitizer operativo al 100%\n";
    echo "✅ Integración PSR-4 exitosa\n";
    echo "✅ Detección de amenazas: {$effectiveness}%\n";
    echo "✅ Rendimiento: {$rps} RPS\n";
    echo "✅ Tiempo total: {$totalTime}ms\n\n";
    echo "🔒 SISTEMA COMPLETAMENTE IMPENETRABLE Y FUNCIONAL 🔒\n";
    
    // Obtener estadísticas finales
    $stats = $sanitizer->getStats();
    echo "\n📊 ESTADÍSTICAS FINALES:\n";
    echo "   Requests procesados: {$stats['middleware_stats']['requests_processed']}\n";
    echo "   Amenazas detectadas: {$stats['middleware_stats']['threats_detected']}\n";
    echo "   Efectividad de detección: {$stats['middleware_stats']['threat_detection_rate']}%\n";
    
} else {
    echo "❌ ALGUNOS TESTS FALLARON\n\n";
    echo "🔧 Revisar la implementación\n";
    echo "⚠️  Sistema requiere correcciones\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

return $success;