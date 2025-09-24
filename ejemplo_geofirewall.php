<?php
/**
 * 🛡️ EJEMPLO DE USO - GeoFirewall
 * 
 * Demostración práctica de cómo implementar el GeoFirewall
 * en tu aplicación de condominios
 * 
 * @package Cyberhole\Examples
 * @author ManuelDev
 * @version 1.0
 */

// Incluir el GeoFirewall
require_once __DIR__ . '/middlewares/Protections/GeoFirewall.php';

/**
 * EJEMPLO 1: Protección Automática de Ruta
 * Bloquea automáticamente IPs no autorizadas
 */
function ejemploProteccionAutomatica() {
    echo "🛡️ EJEMPLO 1: Protección Automática\n";
    echo "===================================\n";
    
    try {
        // Una sola línea protege toda tu aplicación
        GeoFirewall::protect();
        
        echo "✅ Acceso autorizado - Usuario desde país permitido\n";
        echo "📍 Tu aplicación continuaría ejecutándose normalmente aquí\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

/**
 * EJEMPLO 2: Verificación Manual con Control Personalizado
 * Permite manejar el resultado según tus necesidades
 */
function ejemploVerificacionManual() {
    echo "🔍 EJEMPLO 2: Verificación Manual\n";
    echo "=================================\n";
    
    try {
        $geoFirewall = new GeoFirewall();
        $result = $geoFirewall->verifyAccess();
        
        if ($result['allowed']) {
            echo "✅ ACCESO PERMITIDO\n";
            echo "📍 IP: {$result['ip']}\n";
            echo "🌎 País: {$result['country']}\n";
            echo "📝 Razón: {$result['reason']}\n";
            
            // Aquí continúa tu lógica de aplicación
            echo "🚀 Continuando con la aplicación...\n";
            
        } else {
            echo "🚫 ACCESO DENEGADO\n";
            echo "📍 IP: {$result['ip']}\n";
            echo "🌎 País: {$result['country']}\n";
            echo "📝 Razón: {$result['reason']}\n";
            
            // Aquí puedes implementar lógica personalizada
            // Como redireccionar, mostrar mensaje específico, etc.
            echo "🔄 Redirigiendo a página de error...\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

/**
 * EJEMPLO 3: Verificación de IP Específica
 * Útil para validar IPs antes de procesos críticos
 */
function ejemploVerificacionEspecifica() {
    echo "🎯 EJEMPLO 3: Verificación de IP Específica\n";
    echo "===========================================\n";
    
    try {
        $geoFirewall = new GeoFirewall();
        
        // IPs de prueba
        $testIPs = [
            '189.130.1.1' => 'IP México',
            '8.8.8.8' => 'IP Estados Unidos',
            '192.168.1.100' => 'IP desarrollo',
            '1.1.1.1' => 'IP no autorizada'
        ];
        
        foreach ($testIPs as $ip => $description) {
            $allowed = $geoFirewall->wouldAllowIP($ip);
            $status = $allowed ? '✅ PERMITIDA' : '❌ BLOQUEADA';
            echo "  📍 $ip ($description): $status\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

/**
 * EJEMPLO 4: Integración en Sistema de Login
 * Como usar GeoFirewall en autenticación
 */
function ejemploSistemaLogin() {
    echo "🔐 EJEMPLO 4: Integración en Login\n";
    echo "==================================\n";
    
    try {
        // Simular datos de login
        $username = 'admin@condominio.com';
        $password = 'password123';
        
        $geoFirewall = new GeoFirewall();
        $geoResult = $geoFirewall->verifyAccess();
        
        if (!$geoResult['allowed']) {
            echo "🚫 LOGIN BLOQUEADO POR GEOLOCALIZACIÓN\n";
            echo "📍 IP: {$geoResult['ip']}\n";
            echo "📝 Razón: {$geoResult['reason']}\n";
            echo "🛡️ Por seguridad, el acceso desde esta ubicación está restringido\n";
            return false;
        }
        
        // Aquí continuaría tu lógica de autenticación normal
        echo "✅ VERIFICACIÓN GEOGRÁFICA EXITOSA\n";
        echo "🌎 Acceso desde: {$geoResult['country']}\n";
        echo "🔐 Procediendo con autenticación de usuario...\n";
        echo "👤 Usuario: $username\n";
        echo "✅ Login exitoso\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Error en verificación geográfica: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "\n";
}

/**
 * EJEMPLO 5: Dashboard de Estadísticas
 * Ver estadísticas de acceso geográfico
 */
function ejemploDashboardStats() {
    echo "📊 EJEMPLO 5: Dashboard de Estadísticas\n";
    echo "=======================================\n";
    
    try {
        $geoFirewall = new GeoFirewall();
        $stats = $geoFirewall->getAccessStats();
        
        echo "📈 ESTADÍSTICAS DE ACCESO GEOGRÁFICO:\n";
        echo "-------------------------------------\n";
        echo "📊 Total requests: {$stats['total_requests']}\n";
        echo "✅ Permitidos: {$stats['allowed']}\n";
        echo "❌ Denegados: {$stats['denied']}\n";
        echo "🚫 Bloqueados: {$stats['blocked']}\n";
        
        if (!empty($stats['ips'])) {
            echo "\n🔍 TOP IPs MÁS ACTIVAS:\n";
            arsort($stats['ips']);
            $topIPs = array_slice($stats['ips'], 0, 5, true);
            
            foreach ($topIPs as $ip => $count) {
                echo "  📍 $ip: $count requests\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error obteniendo estadísticas: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

/**
 * EJEMPLO 6: Uso en API REST
 * Proteger endpoints de API
 */
function ejemploAPIRest() {
    echo "🌐 EJEMPLO 6: Protección de API REST\n";
    echo "====================================\n";
    
    try {
        // Simular request a API
        $endpoint = '/api/condominios/datos-sensibles';
        $method = 'GET';
        
        echo "📡 REQUEST: $method $endpoint\n";
        
        $geoFirewall = new GeoFirewall();
        $result = $geoFirewall->verifyAccess();
        
        if (!$result['allowed']) {
            // Respuesta JSON de error geográfico
            $apiResponse = [
                'success' => false,
                'error' => 'GEO_ACCESS_DENIED',
                'message' => 'Acceso restringido por ubicación geográfica',
                'details' => [
                    'ip' => $result['ip'],
                    'reason' => $result['reason'],
                    'timestamp' => date('c')
                ]
            ];
            
            echo "📤 RESPONSE: " . json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";
            return;
        }
        
        // Procesar request normalmente
        echo "✅ Verificación geográfica exitosa\n";
        echo "🔄 Procesando request de API...\n";
        
        $apiResponse = [
            'success' => true,
            'data' => [
                'condominios' => ['Condominio A', 'Condominio B'],
                'total' => 2
            ],
            'geo_info' => [
                'country' => $result['country'],
                'ip' => $result['ip']
            ]
        ];
        
        echo "📤 RESPONSE: " . json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";
        
    } catch (Exception $e) {
        echo "❌ Error en API: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// EJECUTAR TODOS LOS EJEMPLOS
echo "🚀 EJEMPLOS PRÁCTICOS DE GEOFIREWALL\n";
echo str_repeat("=", 50) . "\n\n";

ejemploProteccionAutomatica();
ejemploVerificacionManual();
ejemploVerificacionEspecifica();
ejemploSistemaLogin();
ejemploDashboardStats();
ejemploAPIRest();

echo "🏁 EJEMPLOS COMPLETADOS\n";
echo "========================\n";
echo "✅ GeoFirewall está listo para usar en producción\n";
echo "📖 Revisa los logs en: logs/geo_access.log\n";
echo "🛡️ Tu aplicación está protegida geográficamente\n";

?>