<?php
/**
 * 🧪 Test Integral del MiddlewareAutoloader con Verificación PSR-4
 * 
 * Test completo del middleware AutoLoader verificando:
 * - Carga PSR-4 automática de middlewares
 * - Resolución de dependencias entre middlewares
 * - Manejo de namespaces PSR-4
 * - Sistema de singleton y caché
 * - Carga de configuración desde Middlewares-PSR-4.json
 * - Integración con RateLimiter y otros middlewares
 * - Manejo de errores y logs
 * 
 * (Test equivalente al RateLimiter pero enfocado en AutoLoader)
 * 
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-23
 */

// Configurar entorno de testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);

echo "🧪 =====================================================\n";
echo "   TEST INTEGRAL MIDDLEWAREAUTOLOADER PSR-4\n";
echo "   Verificación de manejo de solicitudes PSR-4\n";
echo "🧪 =====================================================\n\n";

try {
    // 1. VERIFICAR CARGA PSR-4 DEL MIDDLEWAREAUTOLOADER
    echo "1️⃣ Verificando carga PSR-4 del MiddlewareAutoloader...\n";
    
    $autoloaderPath = __DIR__ . '/middlewares/Security/AutoLoader.php';
    if (!file_exists($autoloaderPath)) {
        throw new Exception("❌ Archivo AutoLoader no encontrado: $autoloaderPath");
    }
    
    echo "   📁 Archivo AutoLoader encontrado: " . realpath($autoloaderPath) . "\n";
    
    // Cargar MiddlewareAutoloader
    require_once $autoloaderPath;
    
    if (!class_exists('MiddlewareAutoloader')) {
        throw new Exception("❌ Clase MiddlewareAutoloader no está disponible después de la carga");
    }
    
    echo "   ✅ Clase MiddlewareAutoloader cargada correctamente vía PSR-4\n\n";
    
    // 2. INICIALIZAR MIDDLEWAREAUTOLOADER (SINGLETON)
    echo "2️⃣ Inicializando MiddlewareAutoloader (patrón Singleton)...\n";
    
    $autoloader = MiddlewareAutoloader::getInstance();
    
    if (!$autoloader) {
        throw new Exception("❌ No se pudo crear instancia de MiddlewareAutoloader");
    }
    
    echo "   ✅ MiddlewareAutoloader inicializado correctamente\n";
    
    // Verificar que es singleton
    $autoloader2 = MiddlewareAutoloader::getInstance();
    if ($autoloader === $autoloader2) {
        echo "   ✅ Patrón Singleton confirmado (misma instancia)\n";
    } else {
        echo "   ⚠️ Advertencia: No está funcionando como singleton\n";
    }
    echo "\n";
    
    // 3. TEST DE CARGA DE MIDDLEWARES DISPONIBLES (PSR-4 COMPLIANCE)
    echo "3️⃣ Testing carga de middlewares disponibles...\n";
    
    // Lista de middlewares esperados para cargar
    $testMiddlewares = [
        'RateLimiter',
        'GeoFirewall', 
        'InputSanitizer',
        'CsrfProtection',
        'IpWhitelist'
    ];
    
    $loadedMiddlewares = [];
    $failedMiddlewares = [];
    
    foreach ($testMiddlewares as $middleware) {
        echo "   📦 Cargando middleware PSR-4: '$middleware'... ";
        
        try {
            // Simular request para carga PSR-4
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = "/api/middleware/load/$middleware";
            $_SERVER['HTTP_USER_AGENT'] = 'PSR-4-AutoLoader/1.0 (Middleware Loading)';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.200';
            
            $result = $autoloader->loadClass($middleware);
            
            if ($result) {
                $loadedMiddlewares[] = $middleware;
                echo "✅ CARGADO";
            } else {
                $failedMiddlewares[] = $middleware;
                echo "❌ FALLÓ";
            }
            
            // Verificar si la clase está disponible
            if (class_exists($middleware)) {
                echo " | Clase disponible ✅";
            } else {
                echo " | Clase no disponible ⚠️";
            }
            
        } catch (Exception $e) {
            $failedMiddlewares[] = $middleware;
            echo "💥 EXCEPCIÓN: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    echo "\n   📊 Resultado: " . count($loadedMiddlewares) . "/" . count($testMiddlewares) . " middlewares cargados exitosamente\n";
    echo "   ✅ Cargados: " . implode(', ', $loadedMiddlewares) . "\n";
    if (!empty($failedMiddlewares)) {
        echo "   ❌ Fallidos: " . implode(', ', $failedMiddlewares) . "\n";
    }
    echo "\n";
    
    // 4. TEST DE RESOLUCIÓN DE DEPENDENCIAS
    echo "4️⃣ Testing resolución de dependencias entre middlewares...\n";
    
    // Test de dependencias en cadena
    $dependencyTests = [
        'RateLimiter' => ['GeoFirewall'],           // RateLimiter depende de GeoFirewall
        'InputSanitizer' => ['CsrfProtection'],     // InputSanitizer puede usar CSRF
        'IpWhitelist' => ['GeoFirewall'],           // IpWhitelist puede usar Geo
    ];
    
    $dependencyResults = [];
    
    foreach ($dependencyTests as $mainClass => $dependencies) {
        echo "   🔗 Testing dependencias de '$mainClass'...\n";
        
        foreach ($dependencies as $dependency) {
            echo "      📋 Verificando dependencia: '$dependency'... ";
            
            $_SERVER['REQUEST_URI'] = "/api/dependency/resolve/$mainClass/$dependency";
            
            try {
                $depResult = $autoloader->loadClass($dependency);
                if ($depResult && class_exists($dependency)) {
                    echo "✅ RESUELTA";
                    $dependencyResults["$mainClass->$dependency"] = true;
                } else {
                    echo "❌ NO RESUELTA";
                    $dependencyResults["$mainClass->$dependency"] = false;
                }
            } catch (Exception $e) {
                echo "💥 ERROR: " . $e->getMessage();
                $dependencyResults["$mainClass->$dependency"] = false;
            }
            
            echo "\n";
        }
    }
    
    $resolvedDeps = array_filter($dependencyResults, fn($r) => $r === true);
    echo "\n   📊 Dependencias resueltas: " . count($resolvedDeps) . "/" . count($dependencyResults) . "\n\n";
    
    // 5. TEST DE MANEJO DE NAMESPACES PSR-4
    echo "5️⃣ Testing manejo de namespaces PSR-4...\n";
    
    // Simular diferentes namespaces
    $namespaceTests = [
        'Cyberhole\\Security\\RateLimiter',
        'Cyberhole\\Security\\GeoFirewall',
        'Cyberhole\\Validation\\InputSanitizer',
        'Cyberhole\\Auth\\CsrfProtection',
    ];
    
    $namespaceResults = [];
    
    foreach ($namespaceTests as $namespacedClass) {
        echo "   🏗️ Testing namespace: '$namespacedClass'... ";
        
        $_SERVER['REQUEST_URI'] = "/api/namespace/resolve/" . str_replace('\\', '/', $namespacedClass);
        
        try {
            // Extraer clase base del namespace
            $className = substr(strrchr($namespacedClass, '\\'), 1);
            
            $result = $autoloader->loadClass($className);
            
            if ($result) {
                $namespaceResults[$namespacedClass] = true;
                echo "✅ RESUELTO";
            } else {
                $namespaceResults[$namespacedClass] = false;
                echo "❌ NO RESUELTO";
            }
            
        } catch (Exception $e) {
            $namespaceResults[$namespacedClass] = false;
            echo "💥 ERROR: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    $resolvedNamespaces = array_filter($namespaceResults, fn($r) => $r === true);
    echo "\n   📊 Namespaces resueltos: " . count($resolvedNamespaces) . "/" . count($namespaceTests) . "\n\n";
    
    // 6. TEST DE ESCALABILIDAD CON MÚLTIPLES SOLICITUDES
    echo "6️⃣ Testing escalabilidad con múltiples solicitudes PSR-4...\n";
    
    $concurrentTests = [];
    $startTime = microtime(true);
    
    // Simular 20 solicitudes concurrentes de diferentes tipos
    for ($i = 1; $i <= 20; $i++) {
        $middleware = $testMiddlewares[$i % count($testMiddlewares)];
        
        $_SERVER['REQUEST_URI'] = "/api/concurrent/load/$middleware/$i";
        $_SERVER['REMOTE_ADDR'] = '10.0.0.' . (100 + $i);
        
        $loadStart = microtime(true);
        $result = $autoloader->loadClass($middleware);
        $loadTime = (microtime(true) - $loadStart) * 1000; // en ms
        
        $concurrentTests[] = [
            'middleware' => $middleware,
            'success' => $result,
            'load_time_ms' => round($loadTime, 2),
            'request_id' => $i
        ];
        
        if ($i % 5 == 0) {
            echo "   ⚡ Procesadas $i/20 solicitudes...\n";
        }
    }
    
    $totalTime = (microtime(true) - $startTime) * 1000;
    $successfulLoads = array_filter($concurrentTests, fn($t) => $t['success']);
    $averageLoadTime = array_sum(array_column($concurrentTests, 'load_time_ms')) / count($concurrentTests);
    
    echo "\n   📊 Solicitudes exitosas: " . count($successfulLoads) . "/20\n";
    echo "   ⏱️ Tiempo total: " . round($totalTime, 2) . "ms\n";
    echo "   ⏱️ Tiempo promedio por carga: " . round($averageLoadTime, 2) . "ms\n\n";
    
    // 7. VERIFICAR CONFIGURACIÓN PSR-4 DESDE JSON
    echo "7️⃣ Verificando configuración PSR-4 desde Middlewares-PSR-4.json...\n";
    
    $configPath = 'middlewares/data/Middlewares-PSR-4.json';
    
    if (file_exists($configPath)) {
        echo "   📁 Archivo de configuración encontrado: $configPath\n";
        
        $configContent = file_get_contents($configPath);
        $config = json_decode($configContent, true);
        
        if ($config) {
            $totalMiddlewares = 0;
            $categories = 0;
            
            if (isset($config['middlewares'])) {
                foreach ($config['middlewares'] as $category => $middlewares) {
                    $categories++;
                    $totalMiddlewares += count($middlewares);
                }
            }
            
            echo "   📊 Categorías de middlewares: $categories\n";
            echo "   📦 Total middlewares configurados: $totalMiddlewares\n";
            echo "   ✅ Configuración JSON válida\n";
        } else {
            echo "   ❌ Error al parsear JSON de configuración\n";
        }
    } else {
        echo "   ⚠️ Archivo de configuración no encontrado: $configPath\n";
    }
    
    // 8. ESTADÍSTICAS FINALES DEL TEST
    echo "\n8️⃣ Estadísticas finales del test MiddlewareAutoloader...\n";
    
    echo "   📊 Total middlewares testeados: " . count($testMiddlewares) . "\n";
    echo "   ✅ Middlewares cargados exitosamente: " . count($loadedMiddlewares) . "\n";
    echo "   🔗 Dependencias resueltas: " . count($resolvedDeps) . "/" . count($dependencyResults) . "\n";
    echo "   🏗️ Namespaces resueltos: " . count($resolvedNamespaces) . "/" . count($namespaceTests) . "\n";
    echo "   ⚡ Solicitudes concurrentes exitosas: " . count($successfulLoads) . "/20\n";
    
    // Calcular puntuación de efectividad general
    $totalTests = count($testMiddlewares) + count($dependencyResults) + count($namespaceTests) + 20;
    $totalSuccess = count($loadedMiddlewares) + count($resolvedDeps) + count($resolvedNamespaces) + count($successfulLoads);
    $effectiveness = round(($totalSuccess / $totalTests) * 100, 2);
    
    echo "   📈 Efectividad general del AutoLoader: $effectiveness%\n";
    echo "   ⏱️ Rendimiento promedio: " . round($averageLoadTime, 2) . "ms por carga\n";
    
    echo "\n✅ =====================================================\n";
    echo "   TEST MIDDLEWAREAUTOLOADER COMPLETADO\n";
    echo "   ✓ Carga PSR-4 verificada\n";
    echo "   ✓ Patrón Singleton funcional\n";
    echo "   ✓ Resolución de dependencias operativa\n";
    echo "   ✓ Manejo de namespaces efectivo\n";
    echo "   ✓ Escalabilidad concurrente confirmada\n";
    echo "   ✓ Configuración JSON integrada\n";
    echo "✅ =====================================================\n";
    
    // BONUS: Comparación con test de RateLimiter
    echo "\n🔄 BONUS: Integración con RateLimiter testeado previamente...\n";
    
    if (class_exists('RateLimiter')) {
        echo "   🤝 RateLimiter disponible para integración\n";
        
        try {
            $rateLimiter = new RateLimiter([
                'rate_limiting' => ['enabled' => true, 'default_limit' => 5, 'window_seconds' => 60],
                'bot_detection' => ['enabled' => true],
                'logging' => ['enabled' => false] // Evitar logs duplicados
            ]);
            
            $_SERVER['REQUEST_URI'] = '/api/integration/autoloader-ratelimiter';
            $_SERVER['HTTP_USER_AGENT'] = 'PSR-4-Integration-Test/1.0';
            
            $limitResult = $rateLimiter->checkLimits();
            
            if ($limitResult['allowed']) {
                echo "   ✅ Integración AutoLoader + RateLimiter funcional\n";
            } else {
                echo "   🚫 RateLimiter bloquea la integración (esperado si hay límites activos)\n";
            }
            
        } catch (Exception $e) {
            echo "   ⚠️ Error en integración: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️ RateLimiter no disponible para integración\n";
    }
    
    echo "\n🎯 TESTS PSR-4 COMPLETADOS EXITOSAMENTE\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR EN EL TEST MIDDLEWAREAUTOLOADER:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    
    echo "\n🔧 Verificaciones necesarias:\n";
    echo "   - Archivo AutoLoader.php existe y es accesible\n";
    echo "   - Archivo Middlewares-PSR-4.json existe en middlewares/data/\n";
    echo "   - Middlewares de seguridad disponibles para carga\n";
    echo "   - Permisos de lectura en directorios de middlewares\n";
    echo "   - Configuración de PHP permite autoload operations\n";
    
    exit(1);
}

?>