<?php
echo "🔍 EJECUTANDO ANÁLISIS DE TESTS FALLIDOS\n";
echo "=======================================\n\n";

// Ejecutar el test y capturar salida
ob_start();
include 'test_psr4_middlewares.php';
$output = ob_get_clean();

// Buscar líneas con errores
$lines = explode("\n", $output);
$errors = [];
$failedTests = [];

foreach ($lines as $lineNum => $line) {
    if (strpos($line, '❌') !== false || strpos($line, 'FAIL') !== false) {
        $errors[] = "Línea " . ($lineNum + 1) . ": " . trim($line);
    }
    if (strpos($line, 'Tests fallidos') !== false) {
        $failedTests[] = trim($line);
    }
}

echo "📊 ERRORES ENCONTRADOS:\n";
echo "========================\n";
if (empty($errors)) {
    echo "✅ No se encontraron errores explícitos\n";
} else {
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

echo "\n📈 ESTADÍSTICAS DE FALLOS:\n";
echo "==========================\n";
foreach ($failedTests as $stat) {
    echo $stat . "\n";
}

echo "\n🎯 ANÁLISIS COMPLETADO\n";
?>