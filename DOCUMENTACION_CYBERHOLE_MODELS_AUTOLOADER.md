# 🏗️ CyberholeModelsAutoloader - Documentación Completa

## 🎯 Descripción General

El **CyberholeModelsAutoloader** es un sistema avanzado de carga automática PSR-4 diseñado específicamente para el proyecto Cyberhole Condominios. Integra controles de seguridad robustos incluyendo rate limiting y verificación geográfica para proteger el acceso a los modelos del CRUD.

### ✨ Características Principales

- ✅ **Autoloader PSR-4 Completo**: Carga dinámica desde `Models-PSR-4.json`
- 🛡️ **Seguridad Integrada**: RateLimiter + GeoFirewall automáticos
- 🌍 **Control Geográfico**: Verificación de IP por ubicación
- 🚦 **Rate Limiting**: Protección contra ataques DoS/DDoS
- 📊 **Estadísticas Avanzadas**: Monitoreo de uso y rendimiento
- 🔒 **Modelos Restringidos**: Protección especial para modelos sensibles
- 📝 **Logging Completo**: Trazabilidad de eventos de seguridad

---

## 📁 Estructura de Archivos

```
middlewares/
├── PSR-4/
│   └── CyberholeModelsAutoloader.php   # Autoloader principal
├── Security/
│   ├── AutoLoader.php                  # Autoloader de middlewares (actualizado)
│   └── RateLimiter.php                 # Control de límites de solicitudes
├── Protections/
│   └── GeoFirewall.php                 # Verificación geográfica
└── data/
    ├── Models-PSR-4.json              # Configuración de modelos
    └── geo_database.json               # Base de datos geográfica
```

---

## 🚀 Instalación y Configuración

### 1. Inclusión del Autoloader

```php
<?php
// Cargar el autoloader principal
require_once 'middlewares/PSR-4/CyberholeModelsAutoloader.php';

// El autoloader se inicializa automáticamente
$autoloader = CyberholeModelsAutoloader::getInstance();
?>
```

### 2. Configuración de Seguridad (Opcional)

```php
<?php
// Personalizar configuración de seguridad
$configSeguridad = [
    'rate_limiting' => [
        'enabled' => true,
        'model_load_limit' => 100,    // Modelos por hora
        'burst_limit' => 20,          // Modelos por minuto
        'suspicious_threshold' => 30   // Umbral de sospecha
    ],
    'geo_filtering' => [
        'enabled' => true,
        'block_unauthorized_countries' => true
    ],
    'model_protection' => [
        'sensitive_models' => [
            'Admin', 'EmpleadosUser', 'ClavesRegistro'
        ],
        'restrict_sensitive' => true
    ]
];

$autoloader->configureSecurity($configSeguridad);
?>
```

---

## 💻 Uso Básico

### Cargar un Modelo Individual

```php
<?php
// Método 1: Función helper (recomendado)
if (loadCyberholeModel('Condominios')) {
    $condominios = new Condominios();
    // Usar el modelo...
}

// Método 2: Instancia del autoloader
$autoloader = CyberholeModelsAutoloader::getInstance();
if ($autoloader->loadClass('Persona')) {
    $persona = new Persona();
    // Usar el modelo...
}
?>
```

### Cargar Múltiples Modelos

```php
<?php
$modelos = ['Condominios', 'Casas', 'Persona', 'ServiciosModel'];
$resultados = $autoloader->loadModels($modelos);

foreach ($resultados as $modelo => $cargado) {
    if ($cargado) {
        echo "✅ $modelo cargado correctamente\n";
    } else {
        echo "❌ Error cargando $modelo\n";
    }
}
?>
```

### Verificar Disponibilidad

```php
<?php
// Verificar si un modelo está disponible
if (isModelAvailable('Admin')) {
    echo "Modelo Admin disponible\n";
}

// Obtener información detallada
$info = $autoloader->getModelInfo('ServiciosModel');
if ($info) {
    echo "Categoría: {$info['category']}\n";
    echo "Descripción: {$info['description']}\n";
    echo "Archivo: {$info['file_path']}\n";
}
?>
```

---

## 🔒 Sistema de Seguridad

### Rate Limiting

El sistema limita automáticamente:
- **50 modelos/hora** por IP (configurable)
- **10 modelos/minuto** como burst (configurable)
- **Detección de bots** por patrones sospechosos

```php
<?php
// Los límites se aplican automáticamente
// Si se exceden, el acceso se bloquea temporalmente

// Verificar estado actual
$stats = $autoloader->getLoadStats($ip);
echo "Modelos cargados: {$stats['total_loads']}\n";
echo "Cache hits: {$stats['cache_hits']}\n";
?>
```

### Verificación Geográfica

Controla el acceso por ubicación:
- **Países autorizados** definidos en `geo_database.json`
- **Bloqueo automático** de regiones no autorizadas
- **Prioridad por país** (límites ajustados)

```php
<?php
// Verificación automática en cada carga
// Configuración en geo_database.json:
// - México: Prioridad alta (límites x2)
// - España: Prioridad media (límites x1.5)
// - Otros países autorizados: Prioridad normal
?>
```

### Modelos Restringidos

Protección especial para modelos sensibles:

```php
<?php
// Modelos que requieren privilegios especiales
$modelosRestringidos = [
    'Admin',           // Solo administradores
    'EmpleadosUser',   // Personal autorizado
    'ClavesRegistro',  // Administradores únicamente
    'FacturacionCyberholeModel', // Datos financieros
    'NominaModel'      // Información de nómina
];

// Se verifica automáticamente la sesión/permisos
?>
```

---

## 📊 Estadísticas y Monitoreo

### Estadísticas Globales

```php
<?php
$stats = getModelAutoloaderStats();

echo "📈 ESTADÍSTICAS GLOBALES:\n";
echo "Modelos disponibles: {$stats['models']['total_available']}\n";
echo "Modelos cargados: {$stats['models']['total_loaded']}\n";
echo "Rate limiting: " . ($stats['security']['rate_limiting_enabled'] ? 'ON' : 'OFF') . "\n";
echo "Verificación geo: " . ($stats['security']['geo_filtering_enabled'] ? 'ON' : 'OFF') . "\n";
?>
```

### Estadísticas por IP

```php
<?php
$ip = '192.168.1.100';
$statsIP = $autoloader->getLoadStats($ip);

if (!empty($statsIP)) {
    echo "📋 ESTADÍSTICAS PARA IP: $ip\n";
    echo "Total cargas: {$statsIP['total_loads']}\n";
    echo "Cache hits: {$statsIP['cache_hits']}\n";
    echo "Modelos únicos: " . count($statsIP['models_loaded']) . "\n";
    echo "Puntuación humana: {$statsIP['human_score']}/10\n";
}
?>
```

### Modelos Cargados

```php
<?php
$cargados = $autoloader->getLoadedModels();

foreach ($cargados as $modelo => $info) {
    echo "✅ $modelo:\n";
    echo "   • Cargado: " . date('H:i:s', $info['loaded_at']) . "\n";
    echo "   • Categoría: {$info['category']}\n";
    echo "   • Veces usado: {$info['load_count']}\n";
    echo "   • IP: {$info['ip']}\n";
}
?>
```

---

## 🗂️ Catálogo de Modelos Disponibles

### Estructura del Condominio
- **Condominios**: Gestión de condominios
- **Calles**: Administración de calles
- **Casas**: Manejo de unidades/casas
- **AreasComunes**: Áreas compartidas

### Entidades de Usuario
- **Admin**: Administradores del sistema
- **Persona**: Residentes/personas
- **EmpleadosUser**: Personal/empleados
- **Vendedor**: Vendedores
- **ProveedorCyberhole**: Proveedores

### Servicios y Operaciones
- **ServiciosModel**: Servicios generales
- **ServiciosCondominiosModel**: Servicios específicos
- **ServiciosResidentesModel**: Servicios para residentes
- **AccesoModel**: Control de acceso
- **VisitasModel**: Gestión de visitas

### Financiero
- **CuotasModel**: Cuotas y pagos
- **CobrosAutorizadosModel**: Cobros autorizados
- **ComprasModel**: Gestión de compras
- **InventariosModel**: Control de inventario
- **NominaModel**: Nómina de empleados

### Dispositivos y Control
- **TagModel**: Etiquetas de acceso
- **EngomadoModel**: Registro vehicular
- **PersonaUnidadModel**: Relación persona-unidad

### Cyberhole Platform
- **PlanesModel**: Planes de servicio
- **SuscripcionesModel**: Suscripciones
- **FacturacionCyberholeModel**: Facturación

---

## 🛡️ Manejo de Errores y Seguridad

### Errores Comunes

```php
<?php
// Error: Modelo no encontrado
if (!isModelAvailable('ModeloInexistente')) {
    echo "❌ Modelo no disponible\n";
}

// Error: Límite de rate limiting excedido
try {
    loadCyberholeModel('Persona');
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'rate limit') !== false) {
        echo "⚠️ Demasiadas solicitudes, espera un momento\n";
    }
}

// Error: Acceso geográfico denegado
// Se maneja automáticamente con respuesta HTTP 403
?>
```

### Logs de Seguridad

Los eventos se registran automáticamente en:
- `logs/models_autoloader.log`
- `logs/models_rate_limiter.log`
- `logs/geo_access.log`

Formato de log:
```json
{
    "timestamp": "2025-09-22 15:30:45",
    "level": "INFO",
    "message": "✅ Modelo cargado exitosamente: Condominios desde estructura",
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "request_uri": "/admin/condominios",
    "memory_usage": 2097152
}
```

---

## ⚡ Optimización y Rendimiento

### Cache Automático
- Los modelos se cargan **una sola vez** por request
- **Cache hits** mejoran el rendimiento significativamente
- **Estadísticas de cache** disponibles

### Mejores Prácticas

```php
<?php
// ✅ BUENO: Cargar modelos al inicio
loadCyberholeModel('Condominios');
loadCyberholeModel('Casas');
// Usar los modelos múltiples veces...

// ❌ MALO: Cargar repetidamente
for ($i = 0; $i < 100; $i++) {
    loadCyberholeModel('Persona'); // Innecesario, ya está en cache
}

// ✅ BUENO: Carga múltiple eficiente
$modelos = ['Persona', 'Casas', 'ServiciosModel'];
$autoloader->loadModels($modelos);
?>
```

---

## 🧪 Testing y Validación

### Ejecutar Tests

```bash
# Desde PowerShell en Windows con XAMPP
C:\xampp\php\php.exe -f "ruta\al\proyecto\test_cyberhole_models_autoloader_complete.php"
```

### Ejemplo de Test Personalizado

```php
<?php
// Test básico de funcionalidad
function testBasico() {
    $autoloader = CyberholeModelsAutoloader::getInstance();
    
    // Test 1: Verificar disponibilidad
    assert(isModelAvailable('Condominios'), 'Condominios debe estar disponible');
    
    // Test 2: Cargar modelo
    assert(loadCyberholeModel('Persona'), 'Persona debe cargarse correctamente');
    
    // Test 3: Verificar estadísticas
    $stats = getModelAutoloaderStats();
    assert($stats['models']['total_available'] > 0, 'Debe haber modelos disponibles');
    
    echo "✅ Todos los tests básicos pasaron\n";
}

testBasico();
?>
```

---

## 🔧 Resolución de Problemas

### Problemas Comunes

**1. "Modelo no encontrado"**
- Verificar que existe en `Models-PSR-4.json`
- Comprobar que el archivo físico existe
- Revisar nombres de clase (case sensitive)

**2. "Rate limit excedido"**
- Esperar unos minutos antes de reintentar
- Verificar si hay comportamiento de bot
- Ajustar configuración si es necesario

**3. "Acceso geográfico denegado"**
- Verificar configuración en `geo_database.json`
- Comprobar IP real vs detectada
- Añadir país/región a lista autorizada

**4. "Error de permisos en modelos restringidos"**
- Verificar sesión de usuario
- Confirmar privilegios de administrador
- Revisar configuración de modelos sensibles

### Diagnóstico

```php
<?php
// Información de diagnóstico
$autoloader = CyberholeModelsAutoloader::getInstance();

echo "🔍 DIAGNÓSTICO DEL SISTEMA:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Current Memory: " . memory_get_usage(true) . " bytes\n";

$stats = $autoloader->getGlobalStats();
echo "Modelos disponibles: {$stats['models']['total_available']}\n";
echo "Rate limiting: " . ($stats['security']['rate_limiting_enabled'] ? 'ON' : 'OFF') . "\n";
echo "Geo filtering: " . ($stats['security']['geo_filtering_enabled'] ? 'ON' : 'OFF') . "\n";
?>
```

---

## 📞 Soporte y Contribución

### Contacto
- **Desarrollador**: ManuelDev
- **Proyecto**: Cyberhole Condominios
- **Versión**: 3.0

### Contribuir
1. Reportar bugs en logs de seguridad
2. Sugerir mejoras de rendimiento
3. Proponer nuevas características de seguridad
4. Documentar casos de uso adicionales

---

**¡El CyberholeModelsAutoloader está listo para proteger y optimizar tu aplicación!** 🚀🛡️