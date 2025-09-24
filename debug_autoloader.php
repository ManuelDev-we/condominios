<?php
require_once 'middlewares/Security/logging.php';

echo "🔧 DEBUG AUTOLOADER PSR-4\n";
echo "========================\n\n";

try {
    $auto = MiddlewareAutoloader::getInstance();
    
    echo "📊 Estadísticas:\n";
    $stats = $auto->getStats();
    print_r($stats);
    
    echo "\n📦 Info GeoFirewall:\n";
    $geoInfo = $auto->getClassInfo('GeoFirewall');
    print_r($geoInfo);
    
    echo "\n🔍 Intentando cargar GeoFirewall:\n";
    $result = $auto->loadClass('GeoFirewall');
    echo "Resultado: " . ($result ? "✅ Success" : "❌ Failed") . "\n";
    
    if (class_exists('GeoFirewall')) {
        echo "✅ Clase GeoFirewall disponible\n";
    } else {
        echo "❌ Clase GeoFirewall NO disponible\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
?>