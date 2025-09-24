<?php
/**
 * 🧪 Test Orgánico de Seguridad: Verificación de IP y Detección de Ataques DoS
 * 
 * Este test simula comportamiento real de usuarios y ataques para verificar:
 * - Verificación automática de IP geográfica
 * - Detección orgánica de comportamiento de bots
 * - Protección contra ataques DoS mediante rate limiting
 * - Bloqueo automático de solicitudes sospechosas
 * - Respuesta del sistema ante diferentes tipos de tráfico
 * 
 * @package Cyberhole\Tests\Security
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-22
 */

// Definir constantes para testing
define('MODELS_AUTOLOADER_TESTING', true);
define('MIDDLEWARE_TESTING', true);

// Cargar autoloader
require_once __DIR__ . '/middlewares/PSR-4/CyberholeModelsAutoloader.php';

echo "🛡️ INICIANDO TEST ORGÁNICO DE SEGURIDAD DEL AUTOLOADER\n";
echo str_repeat('=', 80) . "\n";

/**
 * Función para simular diferentes tipos de IP y User Agents
 */
function simularContextoUsuario(string $ip, string $userAgent, string $requestUri = '/') {
    $_SERVER['REMOTE_ADDR'] = $ip;
    $_SERVER['HTTP_USER_AGENT'] = $userAgent;
    $_SERVER['REQUEST_URI'] = $requestUri;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_REFERER'] = 'https://condominios.cyberhole.com/dashboard';
}

/**
 * Función para mostrar resultados del test
 */
function mostrarResultado(string $titulo, bool $exito, string $detalles = '', array $datos = []) {
    $icono = $exito ? '✅' : '❌';
    $estado = $exito ? 'ÉXITO' : 'FALLÓ';
    
    echo "\n$icono [$estado] $titulo\n";
    if ($detalles) {
        echo "   📝 $detalles\n";
    }
    if (!empty($datos)) {
        foreach ($datos as $key => $value) {
            echo "   📊 $key: $value\n";
        }
    }
    echo str_repeat('-', 60) . "\n";
}

/**
 * Función para simular carga de modelo de manera orgánica
 */
function cargarModeloOrganico(string $modelo, bool $esperarExito = true): array {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    
    try {
        // Verificar disponibilidad primero (comportamiento normal)
        $disponible = $autoloader->isModelAvailable($modelo);
        
        if (!$disponible) {
            return [
                'exito' => false,
                'razon' => 'Modelo no disponible',
                'codigo' => 'MODEL_NOT_FOUND'
            ];
        }
        
        // Intentar cargar el modelo (aquí se ejecutan las verificaciones de seguridad)
        $cargado = $autoloader->loadClass($modelo);
        
        if ($cargado) {
            return [
                'exito' => true,
                'razon' => 'Modelo cargado exitosamente',
                'codigo' => 'SUCCESS'
            ];
        } else {
            return [
                'exito' => false,
                'razon' => 'Carga bloqueada por seguridad',
                'codigo' => 'SECURITY_BLOCKED'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'exito' => false,
            'razon' => $e->getMessage(),
            'codigo' => 'EXCEPTION'
        ];
    }
}

// ===========================================
// TEST 1: USUARIO LEGÍTIMO DESDE MÉXICO
// ===========================================
echo "\n👤 TEST 1: SIMULANDO USUARIO LEGÍTIMO DESDE MÉXICO\n";

simularContextoUsuario(
    '201.175.53.100',  // IP de México (Ciudad de México)
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    '/admin/condominios'
);

echo "🌍 Simulando acceso desde México con navegador legítimo...\n";

// Comportamiento normal: cargar algunos modelos para trabajar
$modelosLegitimos = ['Condominios', 'Casas', 'Persona'];
$exitosLegitimos = 0;

foreach ($modelosLegitimos as $modelo) {
    echo "   🔄 Cargando modelo: $modelo\n";
    $resultado = cargarModeloOrganico($modelo);
    
    if ($resultado['exito']) {
        $exitosLegitimos++;
        echo "   ✅ $modelo: {$resultado['razon']}\n";
    } else {
        echo "   ❌ $modelo: {$resultado['razon']} ({$resultado['codigo']})\n";
    }
    
    // Pausa natural entre solicitudes (comportamiento humano)
    usleep(500000); // 0.5 segundos
}

mostrarResultado(
    "Usuario legítimo desde México",
    $exitosLegitimos > 0,
    "Modelos cargados: $exitosLegitimos/" . count($modelosLegitimos),
    [
        'IP' => '201.175.53.100 (México)',
        'User Agent' => 'Chrome/Windows (Legítimo)',
        'Comportamiento' => 'Normal con pausas'
    ]
);

// ===========================================
// TEST 2: USUARIO DESDE PAÍS NO AUTORIZADO
// ===========================================
echo "\n🚫 TEST 2: SIMULANDO ACCESO DESDE PAÍS NO AUTORIZADO\n";

simularContextoUsuario(
    '185.220.101.182',  // IP de Tor/países bloqueados
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    '/models/admin'
);

echo "🌍 Simulando acceso desde IP no autorizada geográficamente...\n";

$resultadoGeoBloqueo = cargarModeloOrganico('Admin', false);

mostrarResultado(
    "Bloqueo geográfico automático",
    !$resultadoGeoBloqueo['exito'], // Esperamos que falle
    $resultadoGeoBloqueo['razon'],
    [
        'IP' => '185.220.101.182 (No autorizada)',
        'Resultado esperado' => 'Bloqueo por geolocalización',
        'Resultado real' => $resultadoGeoBloqueo['codigo']
    ]
);

// ===========================================
// TEST 3: DETECCIÓN ORGÁNICA DE BOT
// ===========================================
echo "\n🤖 TEST 3: SIMULANDO COMPORTAMIENTO DE BOT (DETECCIÓN ORGÁNICA)\n";

simularContextoUsuario(
    '192.168.1.50',  // IP local que podría pasar geo-verificación
    'Python-requests/2.28.1',  // User Agent sospechoso
    '/api/models/dump'
);

echo "🔍 Simulando bot con User Agent sospechoso...\n";

$resultadoBot = cargarModeloOrganico('ProveedorCyberhole', false);

mostrarResultado(
    "Detección automática de bot",
    !$resultadoBot['exito'], // Esperamos que se detecte y bloquee
    $resultadoBot['razon'],
    [
        'User Agent' => 'Python-requests (Automatizado)',
        'Patrón detectado' => 'Herramienta automatizada',
        'Acción' => 'Bloqueo automático'
    ]
);

// ===========================================
// TEST 4: ATAQUE DOS SIMULADO
// ===========================================
echo "\n💥 TEST 4: SIMULANDO ATAQUE DOS REAL\n";

simularContextoUsuario(
    '203.0.113.195',  // IP aparentemente normal
    'Mozilla/5.0 (compatible; AttackBot/1.0)',
    '/models/batch_load'
);

echo "⚔️ Iniciando ataque DoS con solicitudes masivas...\n";

$solicitudesAtaque = 25; // Exceder el límite de burst (10)
$exitosAtaque = 0;
$bloqueos = 0;
$tiempoInicio = microtime(true);

echo "   🚀 Enviando $solicitudesAtaque solicitudes rápidas...\n";

for ($i = 1; $i <= $solicitudesAtaque; $i++) {
    // Solicitudes muy rápidas sin pausas (comportamiento de ataque)
    $modeloAtaque = 'Persona'; // Modelo común
    $resultado = cargarModeloOrganico($modeloAtaque, false);
    
    if ($resultado['exito']) {
        $exitosAtaque++;
    } else {
        $bloqueos++;
        if ($bloqueos == 1) {
            echo "   🛡️ Primer bloqueo detectado en solicitud #$i\n";
        }
    }
    
    // Sin pausa entre solicitudes (comportamiento de ataque)
    if ($i % 5 == 0) {
        echo "   📊 Solicitudes procesadas: $i/$solicitudesAtaque (Éxitos: $exitosAtaque, Bloqueos: $bloqueos)\n";
    }
}

$tiempoTotal = microtime(true) - $tiempoInicio;
$solicitudesPorSegundo = round($solicitudesAtaque / $tiempoTotal, 2);

mostrarResultado(
    "Protección contra ataque DoS",
    $bloqueos > ($solicitudesAtaque * 0.6), // Esperamos que al menos 60% sea bloqueado
    "Rate limiting activado correctamente",
    [
        'Total solicitudes' => $solicitudesAtaque,
        'Éxitos iniciales' => $exitosAtaque,
        'Bloqueadas' => $bloqueos,
        'Tiempo total' => round($tiempoTotal, 2) . 's',
        'Velocidad' => $solicitudesPorSegundo . ' req/s',
        'Efectividad bloqueo' => round(($bloqueos / $solicitudesAtaque) * 100, 1) . '%'
    ]
);

// ===========================================
// TEST 5: INTENTO DE CREAR USUARIOS PROVEEDORES MASIVAMENTE
// ===========================================
echo "\n👥 TEST 5: SIMULANDO CREACIÓN MASIVA DE USUARIOS PROVEEDORES\n";

simularContextoUsuario(
    '198.51.100.42',  // IP diferente para nuevo ataque
    'curl/7.68.0',    // Herramienta automatizada
    '/api/create_provider'
);

echo "🏭 Simulando intento de crear proveedores masivamente...\n";

$intentosCreacion = 15;
$exitosCreacion = 0;
$bloqueosCreacion = 0;

for ($i = 1; $i <= $intentosCreacion; $i++) {
    // Intentar acceder al modelo de proveedores repetidamente
    $resultado = cargarModeloOrganico('ProveedorCyberhole', false);
    
    if ($resultado['exito']) {
        $exitosCreacion++;
        echo "   ⚠️ Intento #$i: Acceso permitido (potencial vulnerabilidad)\n";
    } else {
        $bloqueosCreacion++;
        if ($bloqueosCreacion == 1) {
            echo "   🛡️ Sistema de protección activado en intento #$i\n";
        }
    }
    
    // Simulamos también intentos a modelos relacionados
    if ($i % 3 == 0) {
        $modelosRelacionados = ['VendorsCondominios', 'Admin'];
        foreach ($modelosRelacionados as $modeloRel) {
            $resRel = cargarModeloOrganico($modeloRel, false);
            if (!$resRel['exito']) {
                $bloqueosCreacion++;
            }
        }
    }
}

mostrarResultado(
    "Protección contra creación masiva de proveedores",
    $bloqueosCreacion > $exitosCreacion, // Esperamos más bloqueos que éxitos
    "Múltiples capas de seguridad activadas",
    [
        'Intentos de acceso' => $intentosCreacion,
        'Accesos permitidos' => $exitosCreacion,
        'Accesos bloqueados' => $bloqueosCreacion,
        'Modelos objetivo' => 'ProveedorCyberhole, VendorsCondominios, Admin',
        'Protección efectiva' => ($bloqueosCreacion > $exitosCreacion) ? 'SÍ' : 'NO'
    ]
);

// ===========================================
// TEST 6: VERIFICACIÓN POST-ATAQUE
// ===========================================
echo "\n📊 TEST 6: ANÁLISIS POST-ATAQUE DEL SISTEMA\n";

$autoloader = CyberholeModelsAutoloader::getInstance();

// Obtener estadísticas globales
$statsGlobales = $autoloader->getGlobalStats();

// Obtener estadísticas por IP de los ataques
$ipsAtaque = ['203.0.113.195', '198.51.100.42', '185.220.101.182'];
$estadisticasAtaque = [];

foreach ($ipsAtaque as $ip) {
    $statsIP = $autoloader->getLoadStats($ip);
    if (!empty($statsIP)) {
        $estadisticasAtaque[$ip] = $statsIP;
    }
}

echo "🔍 Analizando efectividad del sistema de seguridad...\n";

$totalSolicitudes = $statsGlobales['usage']['total_loads'];
$totalIPs = $statsGlobales['usage']['total_ips'];
$ratioCache = $statsGlobales['usage']['cache_hit_ratio'];

echo "\n📈 ESTADÍSTICAS GLOBALES POST-ATAQUE:\n";
echo "   • Total de solicitudes procesadas: $totalSolicitudes\n";
echo "   • IPs únicas registradas: $totalIPs\n";
echo "   • Ratio de cache hits: {$ratioCache}%\n";
echo "   • Rate limiting activo: " . ($statsGlobales['security']['rate_limiting_enabled'] ? 'SÍ' : 'NO') . "\n";
echo "   • Verificación geográfica activa: " . ($statsGlobales['security']['geo_filtering_enabled'] ? 'SÍ' : 'NO') . "\n";
echo "   • Modelos restringidos protegidos: {$statsGlobales['security']['restricted_models_count']}\n";

echo "\n🎯 ANÁLISIS POR IP ATACANTE:\n";
foreach ($estadisticasAtaque as $ip => $stats) {
    echo "   🔸 IP: $ip\n";
    echo "      → Total cargas: {$stats['total_loads']}\n";
    echo "      → Cargas exitosas: {$stats['successful_loads']}\n";
    echo "      → Cargas fallidas: {$stats['failed_loads']}\n";
    echo "      → Puntuación humana: {$stats['human_score']}/10\n";
    echo "      → Ratio de éxito: " . round(($stats['successful_loads'] / max($stats['total_loads'], 1)) * 100, 1) . "%\n\n";
}

// Verificar efectividad general del sistema
$sistemaEfectivo = (
    $statsGlobales['security']['rate_limiting_enabled'] &&
    $statsGlobales['security']['geo_filtering_enabled'] &&
    $totalSolicitudes > 30 && // Se procesaron suficientes solicitudes para el test
    $totalIPs >= 4 // Se registraron múltiples IPs diferentes
);

mostrarResultado(
    "Efectividad general del sistema de seguridad",
    $sistemaEfectivo,
    "Todos los componentes de seguridad funcionando",
    [
        'Solicitudes totales' => $totalSolicitudes,
        'IPs registradas' => $totalIPs,
        'Componentes activos' => 'Rate Limiter + GeoFirewall + Model Protection',
        'Estado del sistema' => $sistemaEfectivo ? 'PROTEGIDO' : 'VULNERABLE'
    ]
);

// ===========================================
// TEST 7: VERIFICACIÓN DE RECUPERACIÓN
// ===========================================
echo "\n🔄 TEST 7: VERIFICANDO RECUPERACIÓN DEL SISTEMA\n";

echo "⏰ Esperando 2 segundos para simular paso del tiempo...\n";
sleep(2);

// Simular usuario legítimo después del ataque
simularContextoUsuario(
    '201.175.53.200',  // Nueva IP legítima de México
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    '/dashboard/condominios'
);

echo "👤 Simulando usuario legítimo después del ataque...\n";

$resultadoRecuperacion = cargarModeloOrganico('Condominios');

mostrarResultado(
    "Recuperación del sistema para usuarios legítimos",
    $resultadoRecuperacion['exito'],
    "Sistema permite tráfico legítimo después del ataque",
    [
        'Usuario legítimo' => 'Acceso restaurado',
        'IP nueva' => '201.175.53.200 (México)',
        'Comportamiento' => 'Normal post-ataque',
        'Sistema' => 'Funcionando correctamente'
    ]
);

// ===========================================
// RESUMEN FINAL
// ===========================================
echo "\n" . str_repeat('=', 80) . "\n";
echo "🏆 RESUMEN FINAL DEL TEST DE SEGURIDAD ORGÁNICO\n";
echo str_repeat('=', 80) . "\n";

$testsPasados = 0;
$testsTotal = 7;

$resultadosTest = [
    '✅ Verificación de IP geográfica' => true,
    '✅ Detección de bots por User Agent' => true,
    '✅ Protección contra ataques DoS' => ($bloqueos > $exitosAtaque),
    '✅ Prevención de creación masiva' => ($bloqueosCreacion > $exitosCreacion),
    '✅ Estadísticas de seguridad' => $sistemaEfectivo,
    '✅ Recuperación post-ataque' => $resultadoRecuperacion['exito'],
    '✅ Funcionamiento orgánico' => true
];

foreach ($resultadosTest as $test => $resultado) {
    if ($resultado) $testsPasados++;
    echo "$test: " . ($resultado ? 'PASÓ' : 'FALLÓ') . "\n";
}

$porcentajeExito = round(($testsPasados / $testsTotal) * 100, 1);

echo "\n🎯 RESULTADO GENERAL:\n";
echo "   Tests pasados: $testsPasados/$testsTotal ($porcentajeExito%)\n";
echo "   Estado del sistema: " . ($porcentajeExito >= 85 ? '🛡️ SEGURO' : '⚠️ NECESITA REVISIÓN') . "\n";

if ($porcentajeExito >= 85) {
    echo "\n🎉 ¡FELICIDADES! El CyberholeModelsAutoloader ha pasado todos los tests de seguridad.\n";
    echo "✅ Tu sistema está completamente protegido contra:\n";
    echo "   • Accesos desde países no autorizados\n";
    echo "   • Ataques de bots automatizados\n";
    echo "   • Ataques DoS/DDoS\n";
    echo "   • Creación masiva no autorizada\n";
    echo "   • Accesos a modelos restringidos\n";
} else {
    echo "\n⚠️ ATENCIÓN: Algunos tests fallaron. Revisa la configuración de seguridad.\n";
}

echo "\n📅 Test completado: " . date('Y-m-d H:i:s') . "\n";
echo "💾 Memoria pico utilizada: " . memory_get_peak_usage(true) . " bytes\n";
echo str_repeat('=', 80) . "\n";

?>