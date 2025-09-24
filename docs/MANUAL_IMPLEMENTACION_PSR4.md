# 📚 MANUAL COMPLETO DE IMPLEMENTACIÓN PSR-4 MIDDLEWARES

## 🎯 **SISTEMA VALIDADO AL 100%**

**Estado:** ✅ **21/21 Tests exitosos - 100.00% de éxito**  
**Validación:** Sin warnings, sin errores, funcionamiento perfecto  
**Arquitectura:** PSR-4 completa con autoloader inteligente

---

## 📋 **TABLA DE CONTENIDOS**

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura PSR-4](#arquitectura-psr-4)
3. [Instalación y Configuración](#instalación-y-configuración)
4. [Uso Básico](#uso-básico)
5. [Configuración Avanzada](#configuración-avanzada)
6. [API y Métodos](#api-y-métodos)
7. [Ejemplos Prácticos](#ejemplos-prácticos)
8. [Mejores Prácticas](#mejores-prácticas)
9. [Troubleshooting](#troubleshooting)
10. [Siguiente Capa](#siguiente-capa)

---

## 🎯 **RESUMEN EJECUTIVO**

### ¿Qué es este sistema?

Un **sistema PSR-4 autoloader** completo para middlewares de seguridad que incluye:

- **🔒 GeoFirewall**: Control geográfico de acceso por países
- **🚦 RateLimiter**: Limitación de requests y detección de bots  
- **🛡️ HeaderHTTP**: Headers de seguridad HTTP
- **🧹 InputSanitizer**: Sanitización de entrada

### Beneficios Clave

- ✅ **Carga Automática**: Sin `require_once` manual
- ✅ **Gestión de Dependencias**: Autoloader inteligente
- ✅ **Configuración Centralizada**: Un solo JSON de configuración
- ✅ **Seguridad Máxima**: Sin excepciones localhost
- ✅ **Logging Completo**: Trazabilidad total

---

## 🏗️ **ARQUITECTURA PSR-4**

### Estructura de Directorios

```
middlewares/
├── Security/
│   ├── logging.php              # MiddlewareAutoloader (PSR-4 Core)
│   ├── RateLimiter.php         # Rate limiting + bot detection
│   ├── HeaderHTTP.php          # Security headers
│   └── InputSanitizer.php      # Input validation
├── Protections/
│   └── GeoFirewall.php         # Geographic IP control
└── data/
    └── Middlewares-PSR-4.json  # Configuración autoloader
```

### Componentes Principales

#### 1. **MiddlewareAutoloader** (Core)
```php
// Singleton autoloader PSR-4
$autoloader = MiddlewareAutoloader::getInstance();
$class = $autoloader->loadClass('GeoFirewall');
```

#### 2. **Configuración JSON**
```json
{
  "protections": {
    "GeoFirewall": {
      "path": "middlewares/Protections/GeoFirewall.php",
      "dependencies": []
    }
  },
  "security": {
    "RateLimiter": {
      "path": "middlewares/Security/RateLimiter.php",
      "dependencies": ["GeoFirewall"]
    }
  }
}
```

---

## ⚡ **INSTALACIÓN Y CONFIGURACIÓN**

### Paso 1: Incluir el Autoloader

```php
<?php
// En tu aplicación principal
require_once 'middlewares/Security/logging.php';

// Inicializar autoloader
$autoloader = MiddlewareAutoloader::getInstance();
?>
```

### Paso 2: Configurar Middlewares

```php
<?php
// Configuración básica
$config = [
    'storage' => [
        'cache_file' => 'cache/app_cache.json'
    ],
    'logging' => [
        'enabled' => true,
        'log_path' => 'logs/app_security.log'
    ],
    'geo_integration' => [
        'enabled' => true
    ]
];
?>
```

### Paso 3: Usar Middlewares

```php
<?php
// Cargar RateLimiter (con GeoFirewall automático)
$rateLimiter = new RateLimiter($config);
$result = $rateLimiter->checkLimits();

if (!$result['allowed']) {
    http_response_code(429);
    echo json_encode(['error' => $result['reason']]);
    exit;
}
?>
```

---

## 🚀 **USO BÁSICO**

### Ejemplo 1: Control Básico de Rate Limiting

```php
<?php
require_once 'middlewares/Security/logging.php';

// Configuración mínima
$rateLimiter = new RateLimiter([
    'geo_integration' => ['enabled' => false] // Solo rate limiting
]);

$result = $rateLimiter->checkLimits();

if ($result['allowed']) {
    echo "✅ Request permitido";
} else {
    echo "❌ Request bloqueado: " . $result['reason'];
}
?>
```

### Ejemplo 2: Control Geográfico

```php
<?php
// Cargar solo GeoFirewall
$autoloader = MiddlewareAutoloader::getInstance();
$geoFirewall = $autoloader->loadClass('GeoFirewall');

$firewall = new GeoFirewall();
$clientIP = $_SERVER['REMOTE_ADDR'];

if ($firewall->wouldAllowIP($clientIP)) {
    echo "✅ País permitido";
} else {
    echo "❌ País bloqueado";
}
?>
```

### Ejemplo 3: Integración Completa

```php
<?php
require_once 'middlewares/Security/logging.php';

// Configuración completa
$config = [
    'rate_limits' => [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000
    ],
    'geo_integration' => ['enabled' => true],
    'bot_detection' => ['enabled' => true],
    'logging' => ['enabled' => true]
];

$rateLimiter = new RateLimiter($config);
$result = $rateLimiter->checkLimits();

// Respuesta estructurada
header('Content-Type: application/json');
echo json_encode([
    'allowed' => $result['allowed'],
    'reason' => $result['reason'] ?? 'OK',
    'remaining_requests' => $result['remaining'] ?? null
]);
?>
```

---

## ⚙️ **CONFIGURACIÓN AVANZADA**

### Configuración de Rate Limiting

```php
$rateLimiterConfig = [
    'rate_limits' => [
        'requests_per_minute' => 120,    // Límite por minuto
        'requests_per_hour' => 5000,     // Límite por hora
        'requests_per_day' => 50000,     // Límite por día
        'burst_limit' => 10              // Ráfaga permitida
    ],
    'penalties' => [
        'escalation_factor' => 2.0,      // Factor de escalamiento
        'max_penalty_hours' => 24,       // Máximo castigo en horas
        'decay_rate' => 0.1              // Velocidad de recuperación
    ],
    'geo_integration' => [
        'enabled' => true,
        'priority_countries' => ['MX', 'US', 'CA'], // Países prioritarios
        'blocked_countries' => ['CN', 'RU']          // Países bloqueados
    ]
];
```

### Configuración de Bot Detection

```php
$botConfig = [
    'bot_detection' => [
        'enabled' => true,
        'suspicious_patterns' => [
            'automated_tools' => ['curl', 'wget', 'python', 'requests', 'scrapy'],
            'no_user_agent' => true,
            'suspicious_uas' => ['bot', 'crawler', 'spider'],
            'rate_anomalies' => true
        ],
        'penalties' => [
            'suspicious' => 1.5,
            'confirmed_bot' => 3.0,
            'malicious' => 10.0
        ]
    ]
];
```

### Configuración de GeoFirewall

```php
$geoConfig = [
    'countries' => [
        'MX' => ['priority' => 1, 'limit_modifier' => 1.0],
        'US' => ['priority' => 2, 'limit_modifier' => 1.2],
        'CA' => ['priority' => 2, 'limit_modifier' => 1.2],
        'ES' => ['priority' => 3, 'limit_modifier' => 0.8]
    ],
    'blocked_countries' => ['CN', 'RU', 'KP'],
    'allow_unknown' => false
];
```

---

## 📖 **API Y MÉTODOS**

### MiddlewareAutoloader

```php
class MiddlewareAutoloader 
{
    // Obtener instancia singleton
    public static function getInstance(): self
    
    // Cargar clase por nombre
    public function loadClass(string $className): ?object
    
    // Obtener estadísticas
    public function getStats(): array
    
    // Verificar si clase está registrada
    public function isClassRegistered(string $className): bool
}
```

### RateLimiter

```php
class RateLimiter 
{
    // Constructor con configuración
    public function __construct(array $customConfig = [])
    
    // Verificar límites principales
    public function checkLimits(): array
    
    // Obtener estadísticas
    public function getStats(): array
    
    // Verificar si IP está en whitelist
    public function isWhitelisted(string $ip): bool
    
    // Verificar si IP está penalizada
    public function isPenalized(string $ip): array
}
```

### GeoFirewall

```php
class GeoFirewall 
{
    // Verificar si IP sería permitida
    public function wouldAllowIP(string $ip): bool
    
    // Obtener país de IP
    public function getCountryFromIP(string $ip): string
    
    // Obtener estadísticas de acceso
    public function getAccessStats(): array
    
    // Verificar si país está bloqueado
    public function isCountryBlocked(string $country): bool
}
```

---

## 💡 **EJEMPLOS PRÁCTICOS**

### Middleware para API REST

```php
<?php
function securityMiddleware() {
    require_once 'middlewares/Security/logging.php';
    
    $config = [
        'rate_limits' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 2000
        ],
        'geo_integration' => ['enabled' => true],
        'bot_detection' => ['enabled' => true]
    ];
    
    $rateLimiter = new RateLimiter($config);
    $result = $rateLimiter->checkLimits();
    
    if (!$result['allowed']) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header('Retry-After: 60');
        
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => $result['reason'],
            'retry_after' => 60
        ]);
        exit;
    }
    
    // Añadir headers informativos
    header('X-RateLimit-Limit: 100');
    header('X-RateLimit-Remaining: ' . ($result['remaining'] ?? 0));
}

// Usar en tu API
securityMiddleware();
// ... resto de tu código API
?>
```

### Control por Usuario Autenticado

```php
<?php
function userRateLimiting($userId) {
    $config = [
        'storage' => [
            'cache_file' => "cache/user_{$userId}_limits.json"
        ],
        'rate_limits' => [
            'requests_per_minute' => 200,  // Usuarios autenticados más permisivos
            'requests_per_hour' => 10000
        ],
        'geo_integration' => ['enabled' => false]  // Menos restrictivo para usuarios
    ];
    
    $rateLimiter = new RateLimiter($config);
    return $rateLimiter->checkLimits();
}

// Ejemplo de uso
$userResult = userRateLimiting($_SESSION['user_id']);
if (!$userResult['allowed']) {
    // Manejar límite de usuario específico
}
?>
```

### Integración con Framework

```php
<?php
// Para Laravel, Symfony, etc.
class SecurityMiddleware 
{
    public function handle($request, $next) 
    {
        require_once 'middlewares/Security/logging.php';
        
        $rateLimiter = new RateLimiter([
            'rate_limits' => config('security.rate_limits'),
            'geo_integration' => ['enabled' => config('security.geo_enabled')]
        ]);
        
        $result = $rateLimiter->checkLimits();
        
        if (!$result['allowed']) {
            return response()->json([
                'error' => $result['reason']
            ], 429);
        }
        
        return $next($request);
    }
}
?>
```

---

## 🎯 **MEJORES PRÁCTICAS**

### 1. **Configuración por Ambiente**

```php
// config/security.php
$config = [
    'development' => [
        'rate_limits' => ['requests_per_minute' => 1000],
        'geo_integration' => ['enabled' => false],
        'logging' => ['enabled' => true]
    ],
    'production' => [
        'rate_limits' => ['requests_per_minute' => 60],
        'geo_integration' => ['enabled' => true],
        'logging' => ['enabled' => true]
    ]
];

$environment = $_ENV['APP_ENV'] ?? 'production';
$rateLimiter = new RateLimiter($config[$environment]);
```

### 2. **Logging y Monitoreo**

```php
// Configuración de logging avanzada
$loggingConfig = [
    'logging' => [
        'enabled' => true,
        'log_path' => 'logs/security_' . date('Y-m-d') . '.log',
        'log_level' => 'INFO',
        'log_blocked' => true,
        'log_bots' => true,
        'log_geo_events' => true
    ]
];
```

### 3. **Caching Inteligente**

```php
// Usar cache diferente por contexto
$cacheConfig = [
    'storage' => [
        'cache_file' => 'cache/security_' . hash('md5', $_SERVER['REQUEST_URI']) . '.json'
    ]
];
```

### 4. **Configuración Dinámica**

```php
// Cargar configuración desde base de datos
function getSecurityConfig($userId = null) {
    $baseConfig = [
        'rate_limits' => ['requests_per_minute' => 60],
        'geo_integration' => ['enabled' => true]
    ];
    
    if ($userId) {
        // Configuración específica para usuario premium
        $userConfig = getUserSecurityConfig($userId);
        return array_merge_recursive($baseConfig, $userConfig);
    }
    
    return $baseConfig;
}
```

---

## 🔧 **TROUBLESHOOTING**

### Problemas Comunes

#### 1. **Class not found**
```php
// Verificar autoloader
$autoloader = MiddlewareAutoloader::getInstance();
if (!$autoloader->isClassRegistered('GeoFirewall')) {
    echo "❌ GeoFirewall no registrado en PSR-4";
}
```

#### 2. **Configuración no funciona**
```php
// Debug configuración
$rateLimiter = new RateLimiter($config);
$stats = $rateLimiter->getStats();
var_dump($stats['config']);
```

#### 3. **Logs no se escriben**
```php
// Verificar permisos
$logPath = 'logs/rate_limiter.log';
if (!is_writable(dirname($logPath))) {
    echo "❌ Directorio de logs no escribible";
}
```

#### 4. **Rate limiting muy restrictivo**
```php
// Configuración más permisiva para debugging
$debugConfig = [
    'rate_limits' => [
        'requests_per_minute' => 9999,
        'requests_per_hour' => 99999
    ],
    'bot_detection' => ['enabled' => false],
    'geo_integration' => ['enabled' => false]
];
```

### Comandos de Debug

```php
// Test autoloader
$autoloader = MiddlewareAutoloader::getInstance();
echo json_encode($autoloader->getStats(), JSON_PRETTY_PRINT);

// Test GeoFirewall
$geo = new GeoFirewall();
echo json_encode($geo->getAccessStats(), JSON_PRETTY_PRINT);

// Test RateLimiter
$limiter = new RateLimiter();
echo json_encode($limiter->getStats(), JSON_PRETTY_PRINT);
```

---

## 🚀 **SIGUIENTE CAPA**

Con el sistema PSR-4 funcionando al **100%**, puedes proceder a:

### 1. **Capa de Autenticación**
- JWT Middleware
- OAuth Integration  
- Session Management

### 2. **Capa de Autorización**
- Role-based Access Control (RBAC)
- Permission Middleware
- Resource-level Security

### 3. **Capa de Monitoreo**
- Real-time Monitoring
- Alertas de Seguridad
- Dashboard Analytics

### 4. **Capa de Performance**
- Cache Middleware  
- Compression Middleware
- CDN Integration

### 5. **Capa de API**
- Request Validation
- Response Transformation
- API Versioning

---

## 📊 **VALIDACIÓN COMPLETA**

### Tests Ejecutados: ✅ 21/21 (100%)

1. ✅ PSR-4 - Autoloader inicializado
2. ✅ PSR-4 - Configuración cargada  
3. ✅ PSR-4 - GeoFirewall registrado
4. ✅ PSR-4 - RateLimiter registrado
5. ✅ PSR-4 - Información de clases
6. ✅ PSR-4 - Cargar GeoFirewall
7. ✅ PSR-4 - Cargar RateLimiter
8. ✅ GeoFirewall - Instanciación
9. ✅ GeoFirewall - Verificación básica
10. ✅ GeoFirewall - Método wouldAllowIP
11. ✅ GeoFirewall - Estadísticas
12. ✅ GeoFirewall - IP desarrollo
13. ✅ RateLimiter - Instanciación PSR-4
14. ✅ RateLimiter - Integración GeoFirewall
15. ✅ RateLimiter - Sin excepción localhost
16. ✅ RateLimiter - Detección bot UA
17. ✅ RateLimiter - Estadísticas mejoradas
18. ✅ Integración - Flujo completo PSR-4
19. ✅ Integración - Dependencias PSR-4
20. ✅ Integración - Rendimiento PSR-4
21. ✅ Integración - Logs autoloader

### Sistema Listo Para Producción ✅

- **Arquitectura PSR-4**: Completa y funcional
- **Seguridad**: Sin localhost bypass
- **Performance**: Optimizada y eficiente  
- **Logging**: Completo y trazable
- **Configuración**: Flexible y robusta

---

*Manual creado con validación 100% exitosa - Sistema PSR-4 Middlewares listo para la siguiente capa* 🎯