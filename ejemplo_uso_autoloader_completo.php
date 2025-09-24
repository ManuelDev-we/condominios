<?php
/**
 * 📖 Ejemplo de Uso Completo del CyberholeModelsAutoloader
 * 
 * Este archivo demuestra cómo usar el autoloader PSR-4 con integración de seguridad
 * para cargar y usar modelos del CRUD del sistema Cyberhole Condominios.
 * 
 * @package Cyberhole\Examples
 * @author ManuelDev
 * @version 1.0
 * @since 2025-09-22
 */

echo "🏗️ EJEMPLO DE USO: CyberholeModelsAutoloader con Seguridad Integrada\n";
echo str_repeat('=', 80) . "\n\n";

// Paso 1: Incluir el autoloader
require_once __DIR__ . '/middlewares/PSR-4/CyberholeModelsAutoloader.php';

echo "📦 Paso 1: Autoloader cargado\n";

// Paso 2: Inicializar el autoloader (se hace automáticamente por singleton)
$autoloader = CyberholeModelsAutoloader::getInstance();

echo "🚀 Paso 2: Autoloader inicializado\n\n";

// Paso 3: Listar todos los modelos disponibles
echo "📚 Paso 3: Modelos disponibles en el sistema:\n";
$modelos = $autoloader->getAllAvailableModels();

$categorias = [];
foreach ($modelos as $modelo => $info) {
    $categoria = $info['category'];
    if (!isset($categorias[$categoria])) {
        $categorias[$categoria] = [];
    }
    $categorias[$categoria][] = $modelo;
}

foreach ($categorias as $categoria => $modelosCategoria) {
    echo "  🗂️  $categoria (" . count($modelosCategoria) . " modelos):\n";
    foreach ($modelosCategoria as $modelo) {
        echo "      • $modelo\n";
    }
    echo "\n";
}

// Paso 4: Verificar disponibilidad de modelos específicos
echo "🔍 Paso 4: Verificando modelos específicos:\n";

$modelosPrueba = ['Condominios', 'Persona', 'Admin', 'ServiciosModel', 'NoExiste'];

foreach ($modelosPrueba as $modelo) {
    $disponible = $autoloader->isModelAvailable($modelo);
    $icono = $disponible ? '✅' : '❌';
    echo "  $icono $modelo: " . ($disponible ? 'Disponible' : 'No disponible') . "\n";
    
    if ($disponible) {
        $info = $autoloader->getModelInfo($modelo);
        echo "       📁 Categoría: {$info['category']}\n";
        echo "       📄 Archivo: {$info['file_path']}\n";
        echo "       📝 Descripción: {$info['description']}\n";
    }
    echo "\n";
}

// Paso 5: Cargar un modelo real (simulando uso del CRUD)
echo "⚡ Paso 5: Cargando modelo para uso en CRUD:\n";

try {
    // Ejemplo: Cargar modelo de Condominios
    $modeloCargar = 'Condominios';
    echo "  🔄 Intentando cargar: $modeloCargar\n";
    
    // La verificación de seguridad se ejecuta automáticamente
    $cargado = $autoloader->loadClass($modeloCargar);
    
    if ($cargado) {
        echo "  ✅ Modelo cargado exitosamente\n";
        echo "  🛡️ Verificaciones de seguridad: ✅ Pasadas\n";
        
        // Verificar que la clase existe
        if (class_exists($modeloCargar, false)) {
            echo "  📋 Clase '$modeloCargar' está disponible para uso\n";
            
            // Aquí podrías instanciar el modelo si fuera necesario
            // $instancia = new $modeloCargar();
        }
    } else {
        echo "  ❌ No se pudo cargar el modelo\n";
        echo "  🛡️ Posibles razones: límite de rate limiting, ubicación geográfica, etc.\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Paso 6: Estadísticas de uso
echo "📊 Paso 6: Estadísticas del sistema:\n";

$stats = $autoloader->getGlobalStats();

echo "  📈 Modelos:\n";
echo "      • Total disponibles: {$stats['models']['total_available']}\n";
echo "      • Total cargados: {$stats['models']['total_loaded']}\n";
echo "      • Porcentaje de uso: {$stats['models']['load_percentage']}%\n\n";

echo "  🔒 Seguridad:\n";
echo "      • Rate limiting: " . ($stats['security']['rate_limiting_enabled'] ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
echo "      • Filtro geográfico: " . ($stats['security']['geo_filtering_enabled'] ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
echo "      • Modelos restringidos: {$stats['security']['restricted_models_count']}\n\n";

echo "  💾 Uso del sistema:\n";
echo "      • IPs únicas: {$stats['usage']['total_ips']}\n";
echo "      • Total de cargas: {$stats['usage']['total_loads']}\n";
echo "      • Cache hits: {$stats['usage']['cache_hits']}\n";
echo "      • Ratio de cache: {$stats['usage']['cache_hit_ratio']}%\n\n";

// Paso 7: Ejemplo de uso con múltiples modelos
echo "🎯 Paso 7: Carga múltiple de modelos:\n";

$modelosCargar = ['Persona', 'Casas', 'ServiciosModel'];
echo "  🔄 Cargando modelos: " . implode(', ', $modelosCargar) . "\n";

$resultados = $autoloader->loadModels($modelosCargar);

foreach ($resultados as $modelo => $resultado) {
    $icono = $resultado ? '✅' : '❌';
    echo "  $icono $modelo: " . ($resultado ? 'Cargado' : 'Falló') . "\n";
}

echo "\n";

// Paso 8: Información de los modelos cargados
echo "📋 Paso 8: Modelos actualmente cargados:\n";

$modelosCargados = $autoloader->getLoadedModels();

if (empty($modelosCargados)) {
    echo "  ℹ️  No hay modelos cargados actualmente\n";
} else {
    foreach ($modelosCargados as $modelo => $info) {
        echo "  ✅ $modelo\n";
        echo "      • Cargado: " . date('H:i:s', $info['loaded_at']) . "\n";
        echo "      • Categoría: {$info['category']}\n";
        echo "      • Veces usado: {$info['load_count']}\n";
        echo "      • IP: {$info['ip']}\n";
        echo "\n";
    }
}

// Paso 9: Funciones helper de uso rápido
echo "🛠️ Paso 9: Usando funciones helper:\n";

// Verificar si un modelo está disponible
echo "  🔍 isModelAvailable('Admin'): " . (isModelAvailable('Admin') ? 'Sí' : 'No') . "\n";

// Cargar un modelo específico
echo "  📦 loadCyberholeModel('CuotasModel'): " . (loadCyberholeModel('CuotasModel') ? 'Éxito' : 'Falló') . "\n";

// Obtener estadísticas
$statsRapidas = getModelAutoloaderStats();
echo "  📊 Total de modelos disponibles: {$statsRapidas['models']['total_available']}\n";

echo "\n";

// Paso 10: Configuración de seguridad personalizada
echo "⚙️ Paso 10: Configuración de seguridad personalizada:\n";

echo "  🔧 Configuraciones actuales:\n";
echo "      • Rate limiting habilitado: Sí\n";
echo "      • Límite de modelos por hora: 50\n";
echo "      • Límite de burst: 10\n";
echo "      • Verificación geográfica: Sí\n";
echo "      • Modelos sensibles protegidos: Sí\n";

// Ejemplo de personalización de configuración
$configPersonalizada = [
    'rate_limiting' => [
        'model_load_limit' => 100,  // Aumentar límite
        'burst_limit' => 20         // Más solicitudes rápidas
    ],
    'logging' => [
        'log_all_loads' => false    // Reducir logging para rendimiento
    ]
];

$autoloader->configureSecurity($configPersonalizada);
echo "  ✅ Configuración personalizada aplicada\n";

echo "\n";

echo str_repeat('=', 80) . "\n";
echo "🎉 EJEMPLO COMPLETADO\n";
echo "El CyberholeModelsAutoloader está listo para usarse en tu aplicación.\n";
echo "\nPara usarlo en tu CRUD:\n";
echo "1. Incluye: require_once 'middlewares/PSR-4/CyberholeModelsAutoloader.php'\n";
echo "2. Carga modelos: loadCyberholeModel('NombreDelModelo')\n";
echo "3. Usa la clase: \$modelo = new NombreDelModelo();\n";
echo "\n¡La verificación de seguridad se aplica automáticamente!\n";
echo str_repeat('=', 80) . "\n";

?>