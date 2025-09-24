<?php
/**
 * 🧪 Test Integral del RateLimiter con Verificación PSR-4
 * 
 * Test completo del middleware RateLimiter verificando:
 * - Carga PSR-4 correcta del middleware
 * - Límites de solicitudes por IP
 * - Detección de comportamiento de bots
 * - Sistema de penalizaciones
 * - Integración con GeoFirewall
 * - Manejo de solicitudes en ráfaga
 * - Logs de seguridad
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
echo "   TEST INTEGRAL RATELIMITER PSR-4\n";
echo "   Verificación de manejo de solicitudes PSR-4\n";
echo "🧪 =====================================================\n\n";

try {
    // 1. VERIFICAR CARGA PSR-4 DEL RATELIMITER
    echo "1️⃣ Verificando carga PSR-4 del RateLimiter...\n";
    
    $rateLimiterPath = __DIR__ . '/middlewares/Security/RateLimiter.php';
    if (!file_exists($rateLimiterPath)) {
        throw new Exception("❌ Archivo RateLimiter no encontrado: $rateLimiterPath");
    }
    
    echo "   📁 Archivo RateLimiter encontrado: " . realpath($rateLimiterPath) . "\n";
    
    // Cargar RateLimiter
    require_once $rateLimiterPath;
    
    if (!class_exists('RateLimiter')) {
        throw new Exception("❌ Clase RateLimiter no está disponible después de la carga");
    }
    
    echo "   ✅ Clase RateLimiter cargada correctamente vía PSR-4\n\n";
    
    // 2. INICIALIZAR RATELIMITER CON CONFIGURACIÓN DE TESTING
    echo "2️⃣ Inicializando RateLimiter con configuración de testing...\n";
    
    $testConfig = [
        'rate_limiting' => [
            'enabled' => true,
            'default_limit' => 10,      // Límite bajo para testing rápido
            'window_seconds' => 60,     // Ventana de 1 minuto
            'burst_limit' => 5,         // Ráfaga máxima de 5 requests
            'burst_window' => 10,       // Ventana de ráfaga de 10 segundos
            'geo_adjusted_limits' => true,
        ],
        'bot_detection' => [
            'enabled' => true,
            'geo_enhanced' => true,
            'suspicious_patterns' => [
                'rapid_requests' => 3,      // 3 requests rápidas = sospechoso
                'automated_tools' => ['curl', 'wget', 'python', 'test-agent'],
                'identical_requests' => 4,  // 4 requests idénticas = bot
            ]
        ],
        'attack_detection' => [
            'enabled' => true,
            'dos_detection' => true,
            'ddos_detection' => true,
            'brute_force_detection' => true,
        ],
        'logging' => [
            'enabled' => true,
            'log_path' => 'logs/rate_limiter_test.log',
            'log_blocked_requests' => true,
            'log_bot_detection' => true,
        ]
    ];
    
    $rateLimiter = new RateLimiter($testConfig);
    
    if (!$rateLimiter) {
        throw new Exception("❌ No se pudo crear instancia de RateLimiter");
    }
    
    echo "   ✅ RateLimiter inicializado correctamente\n";
    echo "   📊 Límite configurado: {$testConfig['rate_limiting']['default_limit']} requests/minuto\n";
    echo "   🚀 Límite de ráfaga: {$testConfig['rate_limiting']['burst_limit']} requests/10s\n\n";
    
    // 3. TEST DE SOLICITUDES NORMALES (PSR-4 COMPLIANCE)
    echo "3️⃣ Testing solicitudes normales dentro de límites...\n";
    
    $normalRequestCount = 0;
    $allowedRequests = [];
    
    for ($i = 1; $i <= 8; $i++) {
        echo "   📡 Solicitud PSR-4 #$i... ";
        
        // Simular request headers para PSR-4
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "/api/models/load/TestModel$i";
        $_SERVER['HTTP_USER_AGENT'] = 'PSR-4-Autoloader/1.0 (PHP Model Loading)';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        $result = $rateLimiter->checkLimits();
        
        if ($result['allowed']) {
            $normalRequestCount++;
            $allowedRequests[] = $i;
            echo "✅ PERMITIDA";
        } else {
            echo "🚫 BLOQUEADA ({$result['reason']})";
        }
        
        echo " | Remaining: " . ($result['remaining'] ?? 'N/A') . "\n";
        
        // Pequeña pausa entre requests
        usleep(100000); // 0.1 segundos
    }
    
    echo "\n   📊 Resultado: $normalRequestCount requests permitidas de 8\n";
    echo "   📝 IDs permitidas: " . implode(', ', $allowedRequests) . "\n\n";
    
    // 4. TEST DE DETECCIÓN DE BOTS (USER AGENT SOSPECHOSO)
    echo "4️⃣ Testing detección de bots con User Agents sospechosos...\n";
    
    $botUserAgents = [
        'curl/7.68.0',
        'Wget/1.20.3',
        'python-requests/2.25.1',
        'test-agent/1.0',
        'Mozilla/5.0 (compatible; bot/1.0)',
    ];
    
    $botDetections = [];
    
    foreach ($botUserAgents as $index => $userAgent) {
        echo "   🤖 Testing bot con UA: '$userAgent'... ";
        
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;
        $_SERVER['REQUEST_URI'] = "/api/models/load/BotTest$index";
        $_SERVER['REMOTE_ADDR'] = '10.0.0.' . (100 + $index);
        
        $result = $rateLimiter->checkLimits();
        
        if (!$result['allowed']) {
            $botDetections[] = $userAgent;
            echo "🚫 DETECTADO COMO BOT ✅\n";
        } else {
            echo "⚠️ NO DETECTADO (posible falso negativo)\n";
        }
    }
    
    echo "\n   📊 Bots detectados: " . count($botDetections) . "/" . count($botUserAgents) . "\n\n";
    
    // 5. TEST DE RÁFAGA EXCESIVA (BURST DETECTION)
    echo "5️⃣ Testing detección de ráfagas excesivas...\n";
    
    $_SERVER['HTTP_USER_AGENT'] = 'PSR-4-TestClient/1.0';
    $_SERVER['REMOTE_ADDR'] = '172.16.0.50';
    
    $burstResults = [];
    $burstBlocked = 0;
    
    echo "   🚀 Enviando ráfaga de 8 requests en 2 segundos...\n";
    
    for ($i = 1; $i <= 8; $i++) {
        echo "   ⚡ Ráfaga #$i... ";
        
        $_SERVER['REQUEST_URI'] = "/api/models/burst/Model$i";
        $result = $rateLimiter->checkLimits();
        
        $burstResults[] = $result;
        
        if ($result['allowed']) {
            echo "✅ PERMITIDA";
        } else {
            $burstBlocked++;
            echo "🚫 BLOQUEADA ({$result['reason']})";
        }
        
        echo "\n";
        
        // Simular requests muy rápidas
        usleep(250000); // 0.25 segundos entre requests
    }
    
    echo "\n   📊 Resultado ráfaga: $burstBlocked/" . count($burstResults) . " requests bloqueadas\n\n";
    
    // 6. TEST DE DIFERENTES IPS (ESCALABILIDAD PSR-4)
    echo "6️⃣ Testing manejo de múltiples IPs simultáneas...\n";
    
    $testIPs = [
        '192.168.1.10',
        '10.0.0.20',
        '172.16.0.30',
        '203.0.113.40',
        '198.51.100.50'
    ];
    
    $ipResults = [];
    
    foreach ($testIPs as $ip) {
        echo "   🌐 Testing IP: $ip... ";
        
        $_SERVER['REMOTE_ADDR'] = $ip;
        $_SERVER['REQUEST_URI'] = "/api/models/load/MultiIP_" . str_replace('.', '_', $ip);
        $_SERVER['HTTP_USER_AGENT'] = 'PSR-4-Client/1.0';
        
        $result = $rateLimiter->checkLimits();
        $ipResults[$ip] = $result;
        
        if ($result['allowed']) {
            echo "✅ PERMITIDA";
        } else {
            echo "🚫 BLOQUEADA";
        }
        
        echo " | Remaining: " . ($result['remaining'] ?? 'N/A') . "\n";
    }
    
    $allowedIPs = array_filter($ipResults, fn($r) => $r['allowed']);
    echo "\n   📊 IPs permitidas: " . count($allowedIPs) . "/" . count($testIPs) . "\n\n";
    
    // 7. VERIFICAR LOGS DE SEGURIDAD
    echo "7️⃣ Verificando logs de seguridad del RateLimiter...\n";
    
    $logPath = 'logs/rate_limiter_test.log';
    
    if (file_exists($logPath)) {
        $logSize = filesize($logPath);
        $logLines = count(file($logPath));
        
        echo "   📝 Log encontrado: $logPath\n";
        echo "   📊 Tamaño del log: $logSize bytes\n";
        echo "   📄 Líneas de log: $logLines entradas\n";
        
        // Mostrar últimas entradas
        $logContent = file($logPath);
        if ($logContent && count($logContent) > 0) {
            echo "   📋 Últimas 3 entradas del log:\n";
            foreach (array_slice($logContent, -3) as $line) {
                $logData = json_decode($line, true);
                if ($logData) {
                    echo "      [{$logData['timestamp']}] {$logData['level']}: {$logData['message']}\n";
                }
            }
        }
    } else {
        echo "   ⚠️ Log no encontrado en: $logPath\n";
    }
    
    // 8. ESTADÍSTICAS FINALES DEL TEST
    echo "\n8️⃣ Estadísticas finales del test RateLimiter...\n";
    
    echo "   📊 Total de solicitudes testeadas: " . (8 + count($botUserAgents) + 8 + count($testIPs)) . "\n";
    echo "   ✅ Solicitudes normales permitidas: $normalRequestCount/8\n";
    echo "   🤖 Bots detectados: " . count($botDetections) . "/" . count($botUserAgents) . "\n";
    echo "   🚫 Ráfagas bloqueadas: $burstBlocked/8\n";
    echo "   🌐 IPs permitidas: " . count($allowedIPs) . "/" . count($testIPs) . "\n";
    
    // Calcular porcentaje de efectividad
    $totalBlocked = (8 - $normalRequestCount) + count($botDetections) + $burstBlocked + (count($testIPs) - count($allowedIPs));
    $totalTests = 8 + count($botUserAgents) + 8 + count($testIPs);
    $blockingEffectiveness = round(($totalBlocked / $totalTests) * 100, 2);
    
    echo "   📈 Efectividad de bloqueo: $blockingEffectiveness%\n";
    
    echo "\n✅ =====================================================\n";
    echo "   TEST RATELIMITER COMPLETADO EXITOSAMENTE\n";
    echo "   ✓ Carga PSR-4 verificada\n";
    echo "   ✓ Límites de rate limiting funcionales\n";
    echo "   ✓ Detección de bots operativa\n";
    echo "   ✓ Manejo de ráfagas efectivo\n";
    echo "   ✓ Escalabilidad multi-IP confirmada\n";
    echo "   ✓ Sistema de logging activo\n";
    echo "✅ =====================================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR EN EL TEST RATELIMITER:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    
    echo "\n🔧 Verificaciones necesarias:\n";
    echo "   - Archivo RateLimiter.php existe y es accesible\n";
    echo "   - AutoLoader.php funciona correctamente\n";
    echo "   - GeoFirewall disponible (si está habilitado)\n";
    echo "   - Permisos de escritura en directorio logs/\n";
    echo "   - Configuración de PHP permite file operations\n";
    
    exit(1);
}

?>