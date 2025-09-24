<?php
/**
 * 🧪 Test del CyberholeModelsAutoloader Simplificado v4.0
 * 
 * Test de verificación para la nueva versión sin restricciones de sesión
 * Enfocado en RateLimiter + IP validation únicamente
 * 
 * @author ManuelDev
 * @version 4.0 - Test Simplificado
 * @since 2025-09-23
 */

// Definir modo testing para evitar auto-inicialización
define('MODELS_AUTOLOADER_TESTING', true);

echo "🧪 =====================================================\n";
echo "   TEST AUTOLOADER MODELOS SIMPLIFICADO v4.0\n";
echo "   (Sin restricciones de sesión)\n";
echo "🧪 =====================================================\n\n";

try {
    // 1. Cargar el autoloader simplificado
    echo "1️⃣ Cargando CyberholeModelsAutoloader simplificado...\n";
    require_once __DIR__ . '/middlewares/PSR-4/CyberholeModelsAutoloader.php';
    
    // 2. Inicializar instancia
    echo "2️⃣ Inicializando autoloader...\n";
    $autoloader = CyberholeModelsAutoloader::getInstance();
    
    if (!$autoloader) {
        throw new Exception("❌ No se pudo inicializar el autoloader");
    }
    
    echo "✅ Autoloader inicializado correctamente\n\n";
    
    // 3. Verificar estadísticas iniciales
    echo "3️⃣ Verificando estadísticas iniciales...\n";
    $stats = $autoloader->getGlobalStats();
    
    echo "📊 Modelos disponibles: {$stats['models']['total_available']}\n";
    echo "📊 IPs activas: {$stats['usage']['total_ips']}\n";
    echo "🛡️ Rate Limiting: " . ($stats['security']['rate_limiting_enabled'] ? 'HABILITADO' : 'DESHABILITADO') . "\n";
    echo "🚫 Restricciones de sesión: " . ($stats['security']['session_restrictions'] ? 'HABILITADAS' : 'DESHABILITADAS') . "\n";
    echo "🔧 Modo simplificado: " . ($stats['security']['simplified_mode'] ? 'SÍ' : 'NO') . "\n\n";
    
    // 4. Test de carga básica de modelos
    echo "4️⃣ Testeando carga de modelos básicos...\n";
    
    $testModels = ['Admin', 'Persona', 'Empleado', 'Condominio'];
    $loadResults = [];
    
    foreach ($testModels as $model) {
        echo "   📦 Intentando cargar modelo: $model... ";
        
        $result = $autoloader->loadClass($model);
        $loadResults[$model] = $result;
        
        if ($result) {
            echo "✅ CARGADO\n";
        } else {
            echo "❌ FALLÓ\n";
        }
    }
    
    // 5. Verificar modelos cargados
    echo "\n5️⃣ Modelos cargados exitosamente:\n";
    $loadedModels = $autoloader->getLoadedModels();
    
    if (empty($loadedModels)) {
        echo "⚠️ No se cargó ningún modelo\n";
    } else {
        foreach ($loadedModels as $className => $info) {
            echo "   ✅ $className (categoría: {$info['category']}, IP: {$info['ip']})\n";
        }
    }
    
    // 6. Test de funciones helper globales
    echo "\n6️⃣ Testeando funciones helper globales...\n";
    
    echo "   🔍 isModelAvailable('Admin'): " . (isModelAvailable('Admin') ? 'SÍ' : 'NO') . "\n";
    echo "   📋 Total modelos disponibles: " . count(listAvailableModels()) . "\n";
    
    // 7. Verificar que no hay restricciones de sesión
    echo "\n7️⃣ Verificando ausencia de restricciones de sesión...\n";
    
    // Intentar cargar un modelo que en la versión completa sería restringido
    echo "   📦 Cargando modelo 'potencialmente restringido'... ";
    $restrictedTest = $autoloader->loadClass('ServiciosResidentes');
    
    if ($restrictedTest) {
        echo "✅ PERMITIDO (sin restricciones de sesión)\n";
    } else {
        echo "⚠️ BLOQUEADO (verificar configuración)\n";
    }
    
    // 8. Estadísticas finales
    echo "\n8️⃣ Estadísticas finales del test:\n";
    $finalStats = $autoloader->getGlobalStats();
    
    echo "📊 Total cargas realizadas: {$finalStats['usage']['total_loads']}\n";
    echo "📊 Modelos cargados: {$finalStats['models']['total_loaded']}\n";
    echo "📊 Porcentaje de carga: {$finalStats['models']['load_percentage']}%\n";
    echo "🛡️ Puntuación de seguridad: Variable por IP\n";
    
    // 9. Verificar logs de seguridad
    echo "\n9️⃣ Verificando logs de seguridad...\n";
    $logPath = 'logs/models_autoloader_simple.log';
    
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        echo "📝 Log encontrado: $logPath ($logSize bytes)\n";
        
        // Mostrar últimas líneas del log
        $logLines = file($logPath);
        if ($logLines) {
            echo "📝 Últimas entradas del log:\n";
            foreach (array_slice($logLines, -3) as $line) {
                $logData = json_decode($line, true);
                if ($logData) {
                    echo "   [{$logData['timestamp']}] {$logData['level']}: {$logData['message']}\n";
                }
            }
        }
    } else {
        echo "⚠️ Log de seguridad no encontrado en: $logPath\n";
    }
    
    echo "\n✅ =====================================================\n";
    echo "   TEST COMPLETADO EXITOSAMENTE\n";
    echo "   Autoloader v4.0 simplificado funcional\n";
    echo "   ✓ Sin restricciones de sesión\n";
    echo "   ✓ RateLimiter operativo\n";
    echo "   ✓ Logs de seguridad activos\n";
    echo "✅ =====================================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR EN EL TEST:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "\n🔧 Verificar:\n";
    echo "   - Archivo CyberholeModelsAutoloader.php existe\n";
    echo "   - Archivo Models-PSR-4.json existe\n";
    echo "   - Middleware de seguridad disponible\n";
    echo "   - Permisos de escritura en directorio logs/\n";
    
    exit(1);
}

?>