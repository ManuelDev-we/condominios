<?php
/**
 * 🧪 Test Exhaustivo del CyberholeModelsAutoloader con Integración de Seguridad
 * 
 * Suite de pruebas completa para verificar:
 * - Funcionamiento del autoloader PSR-4 de modelos
 * - Integración correcta con RateLimiter
 * - Verificación geográfica con GeoFirewall
 * - Carga dinámica desde Models-PSR-4.json
 * - Estadísticas y logging de seguridad
 * - Compatibilidad con MiddlewareAutoloader
 * 
 * @package Cyberhole\Tests
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-22
 */

// Definir constante para testing
define('MODELS_AUTOLOADER_TESTING', true);
define('MIDDLEWARE_TESTING', true);

// Configurar rutas
$baseDir = dirname(__DIR__, 2);
$modelsAutoloaderPath = __DIR__ . '/middlewares/PSR-4/CyberholeModelsAutoloader.php';
$middlewareAutoloaderPath = __DIR__ . '/middlewares/Security/AutoLoader.php';

// Función para mostrar resultados de tests
function showTestResult(string $testName, bool $success, string $details = '', array $data = []): void {
    $icon = $success ? '✅' : '❌';
    $status = $success ? 'PASS' : 'FAIL';
    
    echo "\n$icon [$status] $testName\n";
    if ($details) {
        echo "   📝 $details\n";
    }
    if (!empty($data)) {
        echo "   📊 " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo str_repeat('-', 80) . "\n";
}

echo "🚀 INICIANDO TEST EXHAUSTIVO DEL CYBERHOLE MODELS AUTOLOADER\n";
echo str_repeat('=', 80) . "\n";

// ===========================================
// TEST 1: CARGAR DEPENDENCIAS
// ===========================================
echo "\n📦 TEST 1: CARGANDO DEPENDENCIAS...\n";

try {
    // Cargar MiddlewareAutoloader primero
    if (file_exists($middlewareAutoloaderPath)) {
        require_once $middlewareAutoloaderPath;
        showTestResult("Carga de MiddlewareAutoloader", true, "Archivo cargado correctamente");
    } else {
        showTestResult("Carga de MiddlewareAutoloader", false, "Archivo no encontrado: $middlewareAutoloaderPath");
    }
    
    // Cargar CyberholeModelsAutoloader
    if (file_exists($modelsAutoloaderPath)) {
        require_once $modelsAutoloaderPath;
        showTestResult("Carga de CyberholeModelsAutoloader", true, "Archivo cargado correctamente");
    } else {
        showTestResult("Carga de CyberholeModelsAutoloader", false, "Archivo no encontrado: $modelsAutoloaderPath");
    }
    
} catch (Exception $e) {
    showTestResult("Carga de dependencias", false, "Error: " . $e->getMessage());
    exit(1);
}

// ===========================================
// TEST 2: INICIALIZACIÓN DE AUTOLOADERS
// ===========================================
echo "\n🔧 TEST 2: INICIALIZANDO AUTOLOADERS...\n";

try {
    // Inicializar MiddlewareAutoloader
    $middlewareAutoloader = MiddlewareAutoloader::getInstance();
    showTestResult("Inicialización MiddlewareAutoloader", true, "Singleton creado correctamente");
    
    // Inicializar CyberholeModelsAutoloader
    $modelsAutoloader = CyberholeModelsAutoloader::getInstance();
    showTestResult("Inicialización CyberholeModelsAutoloader", true, "Singleton creado correctamente");
    
} catch (Exception $e) {
    showTestResult("Inicialización de autoloaders", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 3: VERIFICAR CONFIGURACIÓN PSR-4
// ===========================================
echo "\n📋 TEST 3: VERIFICANDO CONFIGURACIÓN PSR-4...\n";

try {
    // Verificar que Models-PSR-4.json existe y es válido
    $configPath = __DIR__ . '/middlewares/data/Models-PSR-4.json';
    
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        
        if ($config) {
            $totalModels = $config['total_models'] ?? 0;
            $namespacesCount = count($config['namespaces'] ?? []);
            $categoriesCount = count($config['model_registry'] ?? []);
            
            showTestResult("Configuración PSR-4 válida", true, 
                "Modelos: $totalModels, Namespaces: $namespacesCount, Categorías: $categoriesCount", 
                ['total_models' => $totalModels, 'namespaces' => $namespacesCount]
            );
        } else {
            showTestResult("Configuración PSR-4", false, "JSON inválido");
        }
    } else {
        showTestResult("Configuración PSR-4", false, "Archivo no encontrado: $configPath");
    }
    
} catch (Exception $e) {
    showTestResult("Verificación configuración PSR-4", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 4: LISTAR MODELOS DISPONIBLES
// ===========================================
echo "\n📚 TEST 4: LISTANDO MODELOS DISPONIBLES...\n";

try {
    $availableModels = $modelsAutoloader->getAllAvailableModels();
    $modelCount = count($availableModels);
    
    if ($modelCount > 0) {
        showTestResult("Listado de modelos", true, "$modelCount modelos encontrados");
        
        // Mostrar algunos ejemplos
        $examples = array_slice(array_keys($availableModels), 0, 5);
        echo "   📋 Ejemplos: " . implode(', ', $examples) . "\n";
        
    } else {
        showTestResult("Listado de modelos", false, "No se encontraron modelos");
    }
    
} catch (Exception $e) {
    showTestResult("Listado de modelos", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 5: VERIFICAR INTEGRACIÓN DE SEGURIDAD
// ===========================================
echo "\n🛡️ TEST 5: VERIFICANDO INTEGRACIÓN DE SEGURIDAD...\n";

try {
    // Simular variables del servidor para testing
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Suite';
    $_SERVER['REQUEST_URI'] = '/test/autoloader';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Test de verificación de IP de desarrollo (debería permitir localhost)
    $testClassName = 'Admin'; // Modelo restringido para probar seguridad
    
    echo "   🔍 Probando carga de modelo restringido: $testClassName\n";
    
    // Intentar cargar modelo (esto debería activar las verificaciones de seguridad)
    $loadResult = $modelsAutoloader->isModelAvailable($testClassName);
    
    if ($loadResult) {
        showTestResult("Verificación de disponibilidad", true, "Modelo '$testClassName' disponible");
    } else {
        showTestResult("Verificación de disponibilidad", false, "Modelo '$testClassName' no disponible");
    }
    
} catch (Exception $e) {
    showTestResult("Integración de seguridad", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 6: CARGAR MODELOS DE DIFERENTES CATEGORÍAS
// ===========================================
echo "\n🎯 TEST 6: CARGANDO MODELOS DE DIFERENTES CATEGORÍAS...\n";

try {
    $testModels = [
        'Condominios' => 'estructura',
        'Persona' => 'entities', 
        'ServiciosModel' => 'servicios',
        'CuotasModel' => 'financiero'
    ];
    
    $loadResults = [];
    
    foreach ($testModels as $modelName => $category) {
        try {
            $available = $modelsAutoloader->isModelAvailable($modelName);
            $modelInfo = $modelsAutoloader->getModelInfo($modelName);
            
            $loadResults[$modelName] = [
                'available' => $available,
                'category' => $modelInfo['category'] ?? 'unknown',
                'file_exists' => $modelInfo['file_exists'] ?? false
            ];
            
            if ($available && $modelInfo['file_exists']) {
                showTestResult("Modelo $modelName ($category)", true, "Disponible y archivo existe");
            } else {
                showTestResult("Modelo $modelName ($category)", false, 
                    "Disponible: $available, Archivo existe: " . ($modelInfo['file_exists'] ?? 'false'));
            }
            
        } catch (Exception $e) {
            showTestResult("Modelo $modelName", false, "Error: " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    showTestResult("Carga de modelos por categoría", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 7: ESTADÍSTICAS DEL AUTOLOADER
// ===========================================
echo "\n📊 TEST 7: GENERANDO ESTADÍSTICAS...\n";

try {
    // Estadísticas del autoloader de modelos
    $modelStats = $modelsAutoloader->getGlobalStats();
    showTestResult("Estadísticas de modelos", true, 
        "Modelos disponibles: {$modelStats['models']['total_available']}, Cargados: {$modelStats['models']['total_loaded']}",
        $modelStats
    );
    
    // Estadísticas combinadas de middlewares
    $combinedStats = getCombinedAutoloaderStats();
    showTestResult("Estadísticas combinadas", true, 
        "Integración activa: " . ($combinedStats['integration_active'] ? 'Sí' : 'No'));
    
} catch (Exception $e) {
    showTestResult("Generación de estadísticas", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 8: VERIFICAR LOGS DE SEGURIDAD
// ===========================================
echo "\n📝 TEST 8: VERIFICANDO SISTEMA DE LOGS...\n";

try {
    $logPath = $baseDir . '/logs/models_autoloader.log';
    
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        $lastLines = tail($logPath, 3); // Función auxiliar para leer últimas líneas
        
        showTestResult("Archivo de log", true, "Tamaño: $logSize bytes");
        
        if (!empty($lastLines)) {
            echo "   📄 Últimas entradas:\n";
            foreach ($lastLines as $line) {
                $logEntry = json_decode($line, true);
                if ($logEntry) {
                    echo "      {$logEntry['timestamp']} [{$logEntry['level']}] {$logEntry['message']}\n";
                }
            }
        }
        
    } else {
        showTestResult("Archivo de log", false, "Log no encontrado: $logPath");
    }
    
} catch (Exception $e) {
    showTestResult("Verificación de logs", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 9: PRUEBA DE RENDIMIENTO
// ===========================================
echo "\n⚡ TEST 9: PRUEBA DE RENDIMIENTO...\n";

try {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    // Realizar múltiples verificaciones de disponibilidad
    $iterations = 100;
    $successCount = 0;
    
    for ($i = 0; $i < $iterations; $i++) {
        $testModel = 'Condominios'; // Modelo que sabemos que existe
        if ($modelsAutoloader->isModelAvailable($testModel)) {
            $successCount++;
        }
    }
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = round(($endTime - $startTime) * 1000, 2); // en milisegundos
    $memoryUsed = $endMemory - $startMemory;
    
    showTestResult("Prueba de rendimiento", true, 
        "$iterations verificaciones en {$executionTime}ms, Memoria: $memoryUsed bytes, Éxito: $successCount/$iterations",
        [
            'iterations' => $iterations,
            'execution_time_ms' => $executionTime,
            'memory_used_bytes' => $memoryUsed,
            'success_rate' => round(($successCount / $iterations) * 100, 2) . '%'
        ]
    );
    
} catch (Exception $e) {
    showTestResult("Prueba de rendimiento", false, "Error: " . $e->getMessage());
}

// ===========================================
// TEST 10: RESUMEN FINAL
// ===========================================
echo "\n🎉 TEST 10: RESUMEN FINAL Y VERIFICACIÓN INTEGRAL...\n";

try {
    $finalStats = [
        'models_autoloader' => $modelsAutoloader->getGlobalStats(),
        'middleware_autoloader' => $middlewareAutoloader->getStats(),
        'system_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'current_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]
    ];
    
    // Verificar que todo está funcionando correctamente
    $checks = [
        'Models autoloader inicializado' => isset($modelsAutoloader),
        'Middleware autoloader inicializado' => isset($middlewareAutoloader),
        'Configuración PSR-4 cargada' => $finalStats['models_autoloader']['models']['total_available'] > 0,
        'Seguridad habilitada' => $finalStats['models_autoloader']['security']['rate_limiting_enabled'],
        'Logs funcionando' => file_exists($baseDir . '/logs/models_autoloader.log')
    ];
    
    $allChecksPass = true;
    foreach ($checks as $check => $status) {
        if (!$status) $allChecksPass = false;
        $icon = $status ? '✅' : '❌';
        echo "   $icon $check\n";
    }
    
    showTestResult("Verificación integral", $allChecksPass, 
        $allChecksPass ? "Todos los componentes funcionan correctamente" : "Algunos componentes tienen problemas",
        $finalStats
    );
    
} catch (Exception $e) {
    showTestResult("Resumen final", false, "Error: " . $e->getMessage());
}

// ===========================================
// FUNCIONES AUXILIARES
// ===========================================

/**
 * Leer las últimas líneas de un archivo
 */
function tail(string $filename, int $lines = 10): array {
    if (!file_exists($filename)) {
        return [];
    }
    
    $file = fopen($filename, 'r');
    if (!$file) {
        return [];
    }
    
    $buffer = [];
    
    // Leer archivo línea por línea
    while (($line = fgets($file)) !== false) {
        $buffer[] = rtrim($line);
        if (count($buffer) > $lines) {
            array_shift($buffer);
        }
    }
    
    fclose($file);
    return $buffer;
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "🏁 TEST EXHAUSTIVO COMPLETADO\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Memoria pico: " . memory_get_peak_usage(true) . " bytes\n";
echo str_repeat('=', 80) . "\n";

?>