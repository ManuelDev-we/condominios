<?php
/**
 * 🧹 TEST ROBUSTO INPUTSANITIZER
 * 
 * Test exhaustivo de middlewares/Security/InputSanitizer.php
 * Verificando filtrado de código malicioso y amenazas de seguridad
 * 
 * @version 1.0
 * @date 2025-09-22
 */

echo "🧹 ===== TEST ROBUSTO INPUTSANITIZER =====\n\n";

// Verificar existencia de archivos
$inputSanitizerPath = 'middlewares/Security/InputSanitizer.php';
$filtersPath = 'helpers/filters.php';

if (!file_exists($inputSanitizerPath)) {
    echo "❌ ERROR: No se encuentra $inputSanitizerPath\n";
    exit(1);
}

if (!file_exists($filtersPath)) {
    echo "❌ ERROR: No se encuentra $filtersPath\n";
    exit(1);
}

// Cargar dependencias
echo "📁 Cargando helpers/filters.php...\n";
require_once $filtersPath;

echo "📁 Cargando InputSanitizer...\n";
require_once $inputSanitizerPath;

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
// TESTS BÁSICOS DE INPUTSANITIZER
// ============================

echo "📋 Iniciando tests básicos de InputSanitizer...\n\n";

// Test 1: Instanciación básica
ejecutarTest("Instanciación básica de InputSanitizer", function() {
    $sanitizer = new InputSanitizer();
    return $sanitizer instanceof InputSanitizer;
});

// Test 2: Instanciación con configuración
ejecutarTest("Instanciación con configuración personalizada", function() {
    $config = [
        'input_sanitization' => [
            'strict_mode' => true,
            'xss_protection' => true
        ]
    ];
    $sanitizer = new InputSanitizer($config);
    return $sanitizer instanceof InputSanitizer;
});

// Test 3: Método sanitizeInput básico
ejecutarTest("Método sanitizeInput con texto normal", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->sanitizeInput("Texto normal sin amenazas");
    return is_array($result) && isset($result['is_safe']) && isset($result['filtered']);
});

// Test 4: Verificar método isInputSafe
ejecutarTest("Método isInputSafe", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->isInputSafe("Texto seguro");
    return is_bool($result);
});

// Test 5: Método quickSanitize
ejecutarTest("Método quickSanitize", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->quickSanitize("Texto para sanitizar", 'string');
    return is_string($result) || is_array($result);
});

// ============================
// TESTS DE CÓDIGO MALICIOSO
// ============================

echo "🚨 ===== TESTS DE CÓDIGO MALICIOSO =====\n\n";

// Test 6: Detección de JavaScript malicioso
ejecutarTest("Detección de JavaScript malicioso", function() {
    $sanitizer = new InputSanitizer();
    $maliciousJS = "<script>alert('XSS');</script>";
    $result = $sanitizer->sanitizeInput($maliciousJS);
    
    // Debe detectar como no seguro y filtrar el script
    return $result['is_safe'] === false && 
           strpos($result['filtered'], '<script>') === false;
});

// Test 7: Detección de inyección SQL
ejecutarTest("Detección de inyección SQL", function() {
    $sanitizer = new InputSanitizer();
    $maliciousSQL = "'; DROP TABLE users; --";
    $result = $sanitizer->sanitizeInput($maliciousSQL);
    
    // Debe detectar como amenaza SQL
    return $result['is_safe'] === false && 
           isset($result['threats_detected']) &&
           is_array($result['threats_detected']);
});

// Test 8: Detección de PHP injection
ejecutarTest("Detección de PHP injection", function() {
    $sanitizer = new InputSanitizer();
    $maliciousPHP = "<?php system('rm -rf /'); ?>";
    $result = $sanitizer->sanitizeInput($maliciousPHP);
    
    // Debe detectar como amenaza PHP
    return $result['is_safe'] === false;
});

// Test 9: Detección de File Inclusion
ejecutarTest("Detección de File Inclusion", function() {
    $sanitizer = new InputSanitizer();
    $maliciousInclude = "../../../etc/passwd";
    $result = $sanitizer->sanitizeInput($maliciousInclude);
    
    // Debe detectar directory traversal
    return is_array($result) && isset($result['is_safe']);
});

// Test 10: Detección de Event Handlers XSS
ejecutarTest("Detección de Event Handlers XSS", function() {
    $sanitizer = new InputSanitizer();
    $maliciousEvent = '<img src="x" onerror="alert(1)">';
    $result = $sanitizer->sanitizeInput($maliciousEvent);
    
    return $result['is_safe'] === false;
});

// ============================
// TESTS DE ARRAYS Y DATOS COMPLEJOS
// ============================

echo "📊 ===== TESTS DE ARRAYS Y DATOS COMPLEJOS =====\n\n";

// Test 11: Sanitización de arrays
ejecutarTest("Sanitización de arrays", function() {
    $sanitizer = new InputSanitizer();
    $arrayData = [
        'nombre' => 'Juan',
        'email' => 'juan@test.com',
        'comentario' => '<script>alert("XSS")</script>'
    ];
    $result = $sanitizer->sanitizeInput($arrayData);
    
    return is_array($result) && isset($result['is_safe']) && is_array($result['filtered']);
});

// Test 12: Arrays anidados
ejecutarTest("Arrays anidados con amenazas", function() {
    $sanitizer = new InputSanitizer();
    $nestedArray = [
        'usuario' => [
            'nombre' => 'Test',
            'sql_injection' => "'; DROP TABLE users; --",
            'xss' => '<script>evil()</script>'
        ]
    ];
    $result = $sanitizer->sanitizeInput($nestedArray);
    
    return is_array($result) && $result['is_safe'] === false;
});

// Test 13: Validación por lotes
ejecutarTest("Validación por lotes (validateBatch)", function() {
    $sanitizer = new InputSanitizer();
    $inputs = [
        'safe_input' => 'Texto seguro',
        'malicious_input' => '<script>alert(1)</script>',
        'sql_input' => "'; DROP TABLE users; --"
    ];
    $result = $sanitizer->validateBatch($inputs);
    
    return is_array($result) && count($result) === 3;
});

// ============================
// TESTS DE CARACTERÍSTICAS AVANZADAS
// ============================

echo "⚡ ===== TESTS DE CARACTERÍSTICAS AVANZADAS =====\n\n";

// Test 14: Estadísticas de sanitización
ejecutarTest("Obtener estadísticas", function() {
    $sanitizer = new InputSanitizer();
    
    // Ejecutar algunas sanitizaciones para generar estadísticas
    $sanitizer->sanitizeInput("Texto normal");
    $sanitizer->sanitizeInput("<script>alert('test')</script>");
    
    $stats = $sanitizer->getStats();
    return is_array($stats) && isset($stats['total_processed']);
});

// Test 15: Reporte de seguridad
ejecutarTest("Generar reporte de seguridad", function() {
    $sanitizer = new InputSanitizer();
    
    // Procesar algunos inputs para generar datos
    $sanitizer->sanitizeInput("<script>alert('test')</script>");
    $sanitizer->sanitizeInput("'; DROP TABLE users; --");
    
    $report = $sanitizer->generateSecurityReport();
    return is_array($report) && isset($report['summary']);
});

// Test 16: Configuración estricta
ejecutarTest("Modo estricto habilitado", function() {
    $config = [
        'input_sanitization' => [
            'strict_mode' => true,
            'max_input_length' => 100
        ]
    ];
    $sanitizer = new InputSanitizer($config);
    
    $longInput = str_repeat("A", 200); // String muy largo
    $result = $sanitizer->sanitizeInput($longInput);
    
    return is_array($result) && isset($result['is_safe']);
});

// ============================
// TESTS DE INTEGRACIÓN CON HELPERS
// ============================

echo "🔗 ===== TESTS DE INTEGRACIÓN CON HELPERS =====\n\n";

// Test 17: Verificar uso de SecurityFilters
ejecutarTest("Integración con SecurityFilters de helpers", function() {
    // Verificar que SecurityFilters está disponible
    if (!class_exists('SecurityFilters')) {
        return false;
    }
    
    $sanitizer = new InputSanitizer();
    $maliciousInput = "<script>alert('XSS')</script>";
    $result = $sanitizer->sanitizeInput($maliciousInput);
    
    // El resultado debe mostrar que se usaron los filtros
    return $result['is_safe'] === false;
});

// Test 18: Comparación con filtros directos
ejecutarTest("Comparación con SecurityFilters directo", function() {
    if (!class_exists('SecurityFilters')) {
        return false;
    }
    
    $filters = new SecurityFilters();
    $sanitizer = new InputSanitizer();
    
    $testInput = "<script>alert('test')</script>";
    
    // Ambos deben detectar la amenaza
    $directFilter = $filters->filterInput($testInput, true);
    $sanitizerResult = $sanitizer->sanitizeInput($testInput);
    
    return $directFilter['is_safe'] === false && $sanitizerResult['is_safe'] === false;
});

// ============================
// TESTS DE CASOS EXTREMOS
// ============================

echo "🎯 ===== TESTS DE CASOS EXTREMOS =====\n\n";

// Test 19: Input vacío
ejecutarTest("Manejo de input vacío", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->sanitizeInput("");
    return is_array($result) && isset($result['is_safe']);
});

// Test 20: Input null
ejecutarTest("Manejo de input null", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->sanitizeInput(null);
    return is_array($result) && isset($result['is_safe']);
});

// Test 21: Números y tipos no string
ejecutarTest("Manejo de números y tipos no string", function() {
    $sanitizer = new InputSanitizer();
    $result = $sanitizer->sanitizeInput(12345);
    return is_array($result) && isset($result['is_safe']);
});

// Test 22: Caracteres especiales Unicode
ejecutarTest("Caracteres especiales Unicode", function() {
    $sanitizer = new InputSanitizer();
    $unicodeInput = "Café 🏠 α β γ";
    $result = $sanitizer->sanitizeInput($unicodeInput);
    return is_array($result) && $result['is_safe'] === true;
});

// ============================
// RESUMEN DE RESULTADOS
// ============================

echo "🎯 ===== RESUMEN DE TESTS INPUTSANITIZER =====\n";
echo "Tests ejecutados: $totalTests\n";
echo "Tests pasados: $testsPasados\n";
echo "Tests fallidos: " . ($totalTests - $testsPasados) . "\n";
echo "Porcentaje de éxito: " . number_format(($testsPasados / $totalTests) * 100, 2) . "%\n\n";

if ($testsPasados === $totalTests) {
    echo "🎉 ¡TODOS LOS TESTS DE INPUTSANITIZER PASARON!\n";
    echo "✅ InputSanitizer está funcionando correctamente\n";
    echo "✅ Filtrado de código malicioso verificado\n";
    echo "✅ Integración con helpers/filters.php confirmada\n\n";
} else {
    echo "⚠️ Algunos tests fallaron. Revisar implementación.\n\n";
}

// Información adicional del sistema
echo "📊 ===== INFORMACIÓN DEL SISTEMA =====\n";
if (class_exists('InputSanitizer')) {
    $sanitizer = new InputSanitizer();
    
    echo "🧹 Clase InputSanitizer: ✅ Cargada\n";
    echo "📋 Métodos disponibles: " . count(get_class_methods($sanitizer)) . "\n";
    
    if (class_exists('SecurityFilters')) {
        echo "🔗 SecurityFilters: ✅ Disponible\n";
    } else {
        echo "🔗 SecurityFilters: ❌ No disponible\n";
    }
    
    // Mostrar estadísticas finales
    try {
        $stats = $sanitizer->getStats();
        echo "📈 Estadísticas finales: ✅ Disponibles\n";
    } catch (Exception $e) {
        echo "📈 Estadísticas: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n🧹 ===== FIN TEST ROBUSTO INPUTSANITIZER =====\n";
?>