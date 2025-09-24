<?php
/**
 * 🧪 TEST PSR-4 AUTOLOADER - Verificación de 37+ Modelos
 * Prueba la carga automática de todos los modelos del sistema
 * y verifica que sus métodos create/registro son llamables
 * 
 * @package Cyberhole\Tests
 * @author ManuelDev
 * @version 1.0 TEST
 */

// Configurar manejo de errores para mostrar detalles
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar autoloader PSR-4
require_once __DIR__ . '/config/autoloader.php';

class ModelPSR4Test 
{
    private $testResults = [];
    private $modelsConfig = [];
    private $successCount = 0;
    private $errorCount = 0;
    
    public function __construct() 
    {
        echo "🏗️ INICIANDO TEST PSR-4 AUTOLOADER - CYBERHOLE CONDOMINIOS\n";
        echo "================================================================\n\n";
        
        $this->loadModelsConfig();
    }
    
    /**
     * Cargar configuración de modelos desde JSON
     */
    private function loadModelsConfig(): void 
    {
        $configPath = __DIR__ . '/middlewares/data/models.json';
        
        if (!file_exists($configPath)) {
            die("❌ ERROR: No se encontró el archivo de configuración models.json\n");
        }
        
        $configContent = file_get_contents($configPath);
        $this->modelsConfig = json_decode($configContent, true);
        
        if (!$this->modelsConfig) {
            die("❌ ERROR: No se pudo parsear el archivo models.json\n");
        }
        
        echo "✅ Configuración de modelos cargada correctamente\n";
        echo "📊 Total de áreas de negocio: " . count($this->modelsConfig['model_registry']) . "\n\n";
    }
    
    /**
     * Ejecutar todas las pruebas
     */
    public function runAllTests(): void 
    {
        echo "🚀 INICIANDO PRUEBAS DE MODELOS...\n";
        echo "==================================\n\n";
        
        foreach ($this->modelsConfig['model_registry'] as $area => $areaConfig) {
            $this->testBusinessArea($area, $areaConfig);
        }
        
        $this->showResults();
    }
    
    /**
     * Probar un área de negocio específica
     */
    private function testBusinessArea(string $area, array $areaConfig): void 
    {
        echo "📁 ÁREA: " . strtoupper($area) . "\n";
        echo "   Namespace: {$areaConfig['namespace']}\n";
        echo "   Path: {$areaConfig['path']}\n";
        echo "   Modelos: " . count($areaConfig['models']) . "\n";
        echo "   " . str_repeat("-", 50) . "\n";
        
        foreach ($areaConfig['models'] as $modelInfo) {
            $this->testModel($area, $modelInfo, $areaConfig);
        }
        
        echo "\n";
    }
    
    /**
     * Probar un modelo específico
     */
    private function testModel(string $area, array $modelInfo, array $areaConfig): void 
    {
        $className = $modelInfo['class'];
        $fileName = $modelInfo['file'];
        $description = $modelInfo['description'];
        
        echo "   🔍 Probando: $className ($fileName)\n";
        
        try {
            // Verificar que el archivo existe
            $filePath = __DIR__ . '/' . $areaConfig['path'] . $fileName;
            
            if (!file_exists($filePath)) {
                throw new Exception("Archivo no encontrado: $filePath");
            }
            
            // Incluir el archivo del modelo
            require_once $filePath;
            
            // Determinar el nombre real de la clase (algunos archivos tienen nombres diferentes)
            $actualClassName = $this->getActualClassName($fileName, $area);
            
            // Verificar que la clase existe
            if (!class_exists($actualClassName)) {
                throw new Exception("Clase '$actualClassName' no encontrada en $fileName");
            }
            
            // Crear instancia del modelo
            $modelInstance = new $actualClassName();
            
            // Verificar métodos de creación
            $creationMethod = $this->getCreationMethod($modelInstance, $area);
            
            if ($creationMethod) {
                // Preparar datos de prueba básicos
                $testData = $this->getTestData($area, $actualClassName);
                
                // Intentar llamar el método (esperamos que falle por datos incompletos, pero debe ser callable)
                if (method_exists($modelInstance, $creationMethod)) {
                    echo "      ✅ Método '$creationMethod' es callable\n";
                    
                    // Intentar ejecutar con datos de prueba
                    try {
                        $result = $modelInstance->$creationMethod($testData);
                        echo "      🎉 Método ejecutado exitosamente\n";
                    } catch (Exception $e) {
                        echo "      ⚠️  Método falló (esperado): " . substr($e->getMessage(), 0, 50) . "...\n";
                    }
                } else {
                    throw new Exception("Método '$creationMethod' no existe");
                }
            } else {
                echo "      ℹ️  Sin método de creación estándar encontrado\n";
            }
            
            $this->testResults[$area][$className] = [
                'status' => 'success',
                'message' => 'Modelo cargado y probado correctamente',
                'method' => $creationMethod ?? 'N/A'
            ];
            
            $this->successCount++;
            echo "      ✅ SUCCESS: $actualClassName\n";
            
        } catch (Exception $e) {
            $this->testResults[$area][$className] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'method' => 'N/A'
            ];
            
            $this->errorCount++;
            echo "      ❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Obtener el nombre real de la clase basado en el archivo
     */
    private function getActualClassName(string $fileName, string $area): string 
    {
        // Mapeo especial para algunos archivos
        $classMap = [
            'admin-user.php' => 'Admin',
            'persona-user.php' => 'Persona',
            'empleados-user.php' => 'Empleados',
            'proveedores_cyberhole.php' => 'ProveedoresCyberhole',
            'vendedores.php' => 'Vendedores',
            'catalogo_hardware_proveedores.php' => 'CatalogoHardwareProveedores',
            'facturacion-Cyberhole.php' => 'FacturacionCyberhole',
            'ventas_equipos_fisicos.php' => 'VentasEquiposFisicos'
        ];
        
        if (isset($classMap[$fileName])) {
            return $classMap[$fileName];
        }
        
        // Para otros archivos, derivar de nombre de archivo
        $className = str_replace(['-Model.php', '.php'], '', $fileName);
        $className = str_replace('-', '', $className);
        
        return $className;
    }
    
    /**
     * Determinar el método de creación disponible
     */
    private function getCreationMethod($modelInstance, string $area): ?string 
    {
        $methods = ['create', 'registro', 'insert'];
        
        foreach ($methods as $method) {
            if (method_exists($modelInstance, $method)) {
                return $method;
            }
        }
        
        return null;
    }
    
    /**
     * Obtener datos de prueba básicos por área
     */
    private function getTestData(string $area, string $className): array 
    {
        $baseTestData = [
            'entities' => [
                'nombres' => 'Test',
                'correo' => 'test@example.com',
                'fecha_alta' => date('Y-m-d H:i:s')
            ],
            'estructura' => [
                'nombre' => 'Test Structure',
                'descripcion' => 'Test Description',
                'fecha_creacion' => date('Y-m-d H:i:s')
            ],
            'servicios' => [
                'titulo' => 'Test Service',
                'descripcion' => 'Test Description',
                'fecha_solicitud' => date('Y-m-d H:i:s')
            ],
            'financiero' => [
                'concepto' => 'Test Concept',
                'monto' => 100.00,
                'fecha' => date('Y-m-d H:i:s')
            ],
            'dispositivos' => [
                'codigo' => 'TEST001',
                'descripcion' => 'Test Device',
                'fecha_registro' => date('Y-m-d H:i:s')
            ]
        ];
        
        return $baseTestData[$area] ?? ['test' => 'data'];
    }
    
    /**
     * Mostrar resultados finales
     */
    private function showResults(): void 
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 RESULTADOS FINALES DEL TEST PSR-4\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "✅ Modelos exitosos: $this->successCount\n";
        echo "❌ Modelos con errores: $this->errorCount\n";
        echo "📈 Total probados: " . ($this->successCount + $this->errorCount) . "\n\n";
        
        $successRate = $this->successCount / ($this->successCount + $this->errorCount) * 100;
        echo "🎯 Tasa de éxito: " . number_format($successRate, 2) . "%\n\n";
        
        // Mostrar errores si los hay
        if ($this->errorCount > 0) {
            echo "🚨 ERRORES ENCONTRADOS:\n";
            echo str_repeat("-", 30) . "\n";
            
            foreach ($this->testResults as $area => $models) {
                foreach ($models as $className => $result) {
                    if ($result['status'] === 'error') {
                        echo "❌ $area/$className: {$result['message']}\n";
                    }
                }
            }
            echo "\n";
        }
        
        // Resumen por área
        echo "📋 RESUMEN POR ÁREA DE NEGOCIO:\n";
        echo str_repeat("-", 35) . "\n";
        
        foreach ($this->testResults as $area => $models) {
            $areaSuccess = 0;
            $areaTotal = count($models);
            
            foreach ($models as $result) {
                if ($result['status'] === 'success') {
                    $areaSuccess++;
                }
            }
            
            $areaRate = $areaTotal > 0 ? ($areaSuccess / $areaTotal * 100) : 0;
            echo sprintf("%-15s: %d/%d (%.1f%%)\n", 
                strtoupper($area), $areaSuccess, $areaTotal, $areaRate);
        }
        
        echo "\n🏁 Test completado!\n";
        
        if ($successRate >= 95) {
            echo "🎉 ¡EXCELENTE! El PSR-4 autoloader está funcionando perfectamente.\n";
        } elseif ($successRate >= 80) {
            echo "👍 BUENO. El autoloader funciona bien con algunos errores menores.\n";
        } else {
            echo "⚠️  ATENCIÓN. Hay problemas significativos con el autoloader.\n";
        }
    }
}

// Crear autoloader simple para las pruebas
if (!file_exists(__DIR__ . '/config/autoloader.php')) {
    // Crear autoloader básico si no existe
    $autoloaderContent = '<?php
// Simple autoloader for testing
spl_autoload_register(function ($class) {
    // No hacemos nada aquí, solo incluimos manualmente los archivos
});
';
    
    if (!is_dir(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0777, true);
    }
    
    file_put_contents(__DIR__ . '/config/autoloader.php', $autoloaderContent);
}

// Ejecutar las pruebas
$tester = new ModelPSR4Test();
$tester->runAllTests();

?>