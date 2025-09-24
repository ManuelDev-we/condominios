# 🛡️ DOCUMENTACIÓN DE CIBERSEGURIDAD - MIDDLEWARES CYBERHOLE

## 📋 Índice General

1. [**Protections** - Protecciones Perimetrales](#protections---protecciones-perimetrales)
   - [GeoFirewall.php](#geofirewallphp)
   - [HoneypotDetector.php](#honeypotdetectorphp)
   - [RequestThrottler.php](#requestthrottlerphp)

2. [**Security** - Seguridad del Sistema](#security---seguridad-del-sistema)
   - [CsrfShield.php](#csrfshieldphp)
   - [InputSanitizer.php](#inputsanitizerphp)
   - [SecurityManager.php](#securitymanagerphp)

3. [**System** - Sistema y Monitoreo](#system---sistema-y-monitoreo)
   - [CacheManager.php](#cachemanagerphp)
   - [DatabaseHealthCheck.php](#databasehealthcheckphp)
   - [PerformanceTracker.php](#performancetrackerphp)
   - [SystemController.php](#systemcontrollerphp)
   - [SystemMaintenance.php](#systemmaintenancephp)
   - [SystemMonitor.php](#systemmonitorphp)

---

## 🔰 Protections - Protecciones Perimetrales

### GeoFirewall.php

#### **¿Qué es?**
Un firewall geográfico que controla el acceso basado en la ubicación geográfica del usuario, utilizando su dirección IP para determinar el país de origen.

#### **¿Para qué sirve?**
- **Bloquear países de alto riesgo** donde se originan la mayoría de ataques cibernéticos
- **Permitir solo países específicos** para aplicaciones con audiencia local (México y países permitidos)
- **Prevenir ataques automatizados** desde botnets distribuidas geográficamente
- **Cumplir con regulaciones locales** que requieren restricciones geográficas
- **Reducir la superficie de ataque** limitando el acceso geográfico

#### **¿Cómo se usa?**
```php
// Inicialización básica
$geoFirewall = new GeoFirewall();

// Verificar IP actual del usuario
if (!$geoFirewall->isAllowed($_SERVER['REMOTE_ADDR'])) {
    // Bloquear acceso y registrar intento
    $geoFirewall->logBlockedAttempt($_SERVER['REMOTE_ADDR']);
    exit('Acceso denegado desde tu ubicación');
}

// Configuración de países permitidos
$geoFirewall->setAllowedCountries(['MX', 'US', 'CA', 'ES']);

// Verificación avanzada con logging
$result = $geoFirewall->checkAccess($ip, $userAgent, $requestPath);
if ($result['blocked']) {
    // Manejar bloqueo con detalles
    error_log("Acceso bloqueado: " . $result['reason']);
}
```

**Casos de uso específicos:**
- Sistema de condominios solo para México
- Bloqueo de países con alta actividad maliciosa
- Protección de APIs sensibles por región

---

### HoneypotDetector.php

#### **¿Qué es?**
Un detector de honeypots (trampas para atacantes) que identifica bots, scrapers y atacantes automatizados mediante campos ocultos y técnicas de detección avanzadas.

#### **¿Para qué sirve?**
- **Detectar bots maliciosos** que intentan enviar formularios automáticamente
- **Identificar scrapers** que extraen datos sin autorización
- **Prevenir spam automatizado** en formularios de contacto y registro
- **Detectar ataques de fuerza bruta** automatizados
- **Proteger formularios críticos** (login, registro, contacto)

#### **¿Cómo se usa?**
```php
// Generar campos honeypot en formularios
$honeypot = new HoneypotDetector();

// En el formulario HTML
echo $honeypot->generateHiddenFields('contact_form');

// Verificar en el procesamiento del formulario
if ($honeypot->isBot($_POST, $_SERVER)) {
    // Es un bot - registrar y bloquear
    $honeypot->logBotAttempt($_SERVER['REMOTE_ADDR'], $_POST);
    exit('Formulario inválido');
}

// Verificación con múltiples técnicas
$result = $honeypot->advancedBotDetection([
    'post_data' => $_POST,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'timing' => $_SESSION['form_start_time']
]);

if ($result['is_bot']) {
    // Aplicar medidas antibot
    $honeypot->applyBotCountermeasures($result);
}
```

**Casos de uso específicos:**
- Formularios de registro de usuarios
- Formularios de contacto de condominios
- Sistemas de login y autenticación

---

### RequestThrottler.php

#### **¿Qué es?**
Un limitador de velocidad de peticiones que controla la frecuencia de requests por IP, usuario o endpoint para prevenir ataques de denegación de servicio y abuso.

#### **¿Para qué sirve?**
- **Prevenir ataques DDoS** limitando requests por segundo/minuto
- **Proteger APIs** de abuso y sobrecarga
- **Limitar intentos de login** para prevenir ataques de fuerza bruta
- **Controlar uso de recursos** por parte de usuarios legítimos
- **Implementar rate limiting** en endpoints sensibles

#### **¿Cómo se usa?**
```php
// Configuración básica de throttling
$throttler = new RequestThrottler();

// Verificar límites por IP
if (!$throttler->isAllowed($_SERVER['REMOTE_ADDR'], 'general', 60, 3600)) {
    // Límite excedido - 60 requests por hora
    http_response_code(429);
    exit('Límite de peticiones excedido');
}

// Throttling específico para login
if (!$throttler->checkLoginAttempts($_SERVER['REMOTE_ADDR'], 5, 900)) {
    // Máximo 5 intentos de login por 15 minutos
    $throttler->logRateLimitExceeded('login', $_SERVER['REMOTE_ADDR']);
    exit('Demasiados intentos de login');
}

// Configuración avanzada por endpoint
$config = [
    'api/upload' => ['limit' => 10, 'window' => 3600],
    'api/search' => ['limit' => 100, 'window' => 3600],
    'login' => ['limit' => 5, 'window' => 900]
];

$throttler->setEndpointLimits($config);
```

**Casos de uso específicos:**
- API de servicios de condominios
- Sistema de autenticación
- Formularios de contacto y consultas

---

## 🔐 Security - Seguridad del Sistema

### CsrfShield.php

#### **¿Qué es?**
Un escudo contra ataques CSRF (Cross-Site Request Forgery) que genera y valida tokens únicos para proteger formularios y acciones sensibles.

#### **¿Para qué sirve?**
- **Prevenir ataques CSRF** donde sitios maliciosos ejecutan acciones en nombre del usuario
- **Proteger formularios críticos** (transferencias, cambios de configuración, eliminaciones)
- **Validar origen de peticiones** asegurando que vienen del sitio legítimo
- **Proteger APIs** de requests no autorizados
- **Mantener integridad de sesiones** de usuario

#### **¿Cómo se usa?**
```php
// Generar token CSRF para formularios
$csrfShield = new CsrfShield();
$token = $csrfShield->generateToken();

// En el formulario HTML
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';

// Validar token en el procesamiento
if (!$csrfShield->validateToken($_POST['csrf_token'])) {
    // Token inválido - posible ataque CSRF
    $csrfShield->logCSRFAttempt($_SERVER['REMOTE_ADDR'], $_POST);
    exit('Token de seguridad inválido');
}

// Protección de APIs
$csrfShield->protectAPI([
    'methods' => ['POST', 'PUT', 'DELETE'],
    'header' => 'X-CSRF-Token',
    'session_key' => 'api_csrf_token'
]);

// Regenerar tokens periódicamente
$csrfShield->refreshToken(3600); // Cada hora
```

**Casos de uso específicos:**
- Formularios de pago de servicios
- Configuración de cuentas de usuario
- Eliminación de registros importantes

---

### InputSanitizer.php

#### **¿Qué es?**
Un sanitizador avanzado de entrada que limpia, valida y neutraliza datos de usuario para prevenir inyecciones y ataques basados en input malicioso.

#### **¿Para qué sirve?**
- **Prevenir inyecciones SQL** sanitizando queries de base de datos
- **Bloquear inyecciones XSS** limpiando HTML y JavaScript malicioso
- **Neutralizar inyecciones de comandos** en el sistema operativo
- **Validar formatos de datos** (emails, teléfonos, RFC, etc.)
- **Filtrar contenido malicioso** en uploads y formularios

#### **¿Cómo se usa?**
```php
// Sanitización básica de datos
$sanitizer = new InputSanitizer();

// Limpiar datos del formulario
$cleanData = $sanitizer->sanitizeArray($_POST, [
    'name' => 'string',
    'email' => 'email',
    'phone' => 'phone',
    'rfc' => 'rfc_mexican',
    'message' => 'text_safe'
]);

// Sanitización específica por tipo
$safeName = $sanitizer->sanitizeString($_POST['name'], 'name');
$safeEmail = $sanitizer->sanitizeEmail($_POST['email']);
$safeHTML = $sanitizer->sanitizeHTML($_POST['description']);

// Validación y sanitización combinada
$result = $sanitizer->validateAndSanitize($_POST['user_input'], [
    'type' => 'text',
    'max_length' => 255,
    'allow_html' => false,
    'required' => true
]);

if (!$result['valid']) {
    // Datos inválidos o maliciosos
    $sanitizer->logSuspiciousInput($_POST, $result['threats']);
}
```

**Casos de uso específicos:**
- Formularios de registro de residentes
- Sistemas de comentarios y reportes
- Upload de documentos e imágenes

---

### SecurityManager.php

#### **¿Qué es?**
El gestor central de seguridad que coordina todas las medidas de protección, mantiene políticas de seguridad y responde a incidentes de manera unificada.

#### **¿Para qué sirve?**
- **Coordinar todos los middlewares** de seguridad en un punto central
- **Implementar políticas de seguridad** consistentes en toda la aplicación
- **Gestionar niveles de amenaza** y respuestas automáticas
- **Centralizar logging de seguridad** para análisis y auditoría
- **Automatizar respuestas** a incidentes de seguridad

#### **¿Cómo se usa?**
```php
// Inicialización del gestor de seguridad
$securityManager = new SecurityManager();

// Aplicar todas las verificaciones de seguridad
$securityResult = $securityManager->performSecurityCheck($_SERVER, $_POST);

if (!$securityResult['passed']) {
    // Manejar fallo de seguridad según severidad
    $securityManager->handleSecurityIncident($securityResult);
}

// Configurar políticas de seguridad
$securityManager->setPolicies([
    'max_login_attempts' => 5,
    'session_timeout' => 3600,
    'require_2fa' => true,
    'allowed_file_types' => ['jpg', 'png', 'pdf'],
    'max_file_size' => 5242880
]);

// Monitoreo continuo
$securityManager->startSecurityMonitoring([
    'suspicious_patterns' => true,
    'failed_logins' => true,
    'unusual_traffic' => true
]);
```

**Casos de uso específicos:**
- Portal de administración de condominios
- Sistema de gestión de pagos
- Área de documentos confidenciales

---

## 🖥️ System - Sistema y Monitoreo

### CacheManager.php

#### **¿Qué es?**
Un gestor de caché inteligente que optimiza el rendimiento almacenando temporalmente datos frecuentemente accedidos en memoria o archivos.

#### **¿Para qué sirve?**
- **Acelerar respuestas** almacenando resultados de queries complejas
- **Reducir carga en base de datos** evitando consultas repetitivas
- **Mejorar experiencia de usuario** con tiempos de carga más rápidos
- **Optimizar recursos del servidor** minimizando procesamiento redundante
- **Implementar estrategias de caché** inteligentes por tipo de contenido

#### **¿Cómo se usa?**
```php
// Gestión básica de caché
$cacheManager = new CacheManager();

// Almacenar datos en caché
$cacheManager->set('condominios_list', $condominiosData, 3600); // 1 hora

// Recuperar del caché
$cachedData = $cacheManager->get('condominios_list');
if ($cachedData === null) {
    // No está en caché, obtener de BD
    $data = $database->getCondominios();
    $cacheManager->set('condominios_list', $data, 3600);
}

// Caché inteligente con tags
$cacheManager->setWithTags('user_123_profile', $userProfile, ['user', 'profile'], 7200);
$cacheManager->invalidateTag('user'); // Invalida todos los cachés de usuarios

// Estrategias de caché avanzadas
$cacheManager->setStrategy('database_queries', [
    'ttl' => 1800,
    'auto_refresh' => true,
    'compression' => true
]);
```

**Casos de uso específicos:**
- Lista de condominios y unidades
- Datos de configuración del sistema
- Resultados de búsquedas frecuentes

---

### DatabaseHealthCheck.php

#### **¿Qué es?**
Un monitor de salud de base de datos que verifica constantemente el estado, rendimiento y disponibilidad de las conexiones de base de datos.

#### **¿Para qué sirve?**
- **Monitorear disponibilidad** de la base de datos en tiempo real
- **Detectar problemas de rendimiento** antes de que afecten usuarios
- **Verificar integridad de datos** y consistencia de tablas
- **Alertar sobre anomalías** en conexiones o queries
- **Mantener estadísticas** de salud de la base de datos

#### **¿Cómo se usa?**
```php
// Verificación básica de salud
$dbHealthCheck = new DatabaseHealthCheck();

// Verificar conexión y estado
$healthStatus = $dbHealthCheck->checkDatabaseHealth();
if (!$healthStatus['healthy']) {
    // Base de datos con problemas
    $dbHealthCheck->alertAdministrators($healthStatus);
}

// Monitoreo de rendimiento
$performanceMetrics = $dbHealthCheck->getPerformanceMetrics();
if ($performanceMetrics['avg_query_time'] > 2.0) {
    // Queries muy lentas detectadas
    $dbHealthCheck->optimizeDatabasePerformance();
}

// Verificación de integridad
$integrityCheck = $dbHealthCheck->verifyDataIntegrity([
    'tables' => ['usuarios', 'condominios', 'servicios'],
    'check_constraints' => true,
    'check_foreign_keys' => true
]);

// Monitoreo automático
$dbHealthCheck->startAutomaticMonitoring(300); // Cada 5 minutos
```

**Casos de uso específicos:**
- Sistemas críticos de facturación
- Backup y recuperación de datos
- Monitoreo proactivo de rendimiento

---

### PerformanceTracker.php

#### **¿Qué es?**
Un rastreador de rendimiento que mide, analiza y optimiza el performance de la aplicación monitoreando tiempos de respuesta y uso de recursos.

#### **¿Para qué sirve?**
- **Medir tiempos de respuesta** de páginas y APIs
- **Identificar cuellos de botella** en el código
- **Monitorear uso de memoria** y CPU
- **Generar reportes de rendimiento** para optimización
- **Alertar sobre degradación** del performance

#### **¿Cómo se usa?**
```php
// Iniciar medición de rendimiento
$performanceTracker = new PerformanceTracker();
$performanceTracker->startTracking('page_load');

// ... código a medir ...

// Finalizar medición
$performanceTracker->endTracking('page_load');

// Medir función específica
$performanceTracker->measureFunction('getUserData', function() use ($userId) {
    return $database->getUserById($userId);
});

// Análisis de memoria
$memoryUsage = $performanceTracker->trackMemoryUsage();
if ($memoryUsage['peak'] > 128 * 1024 * 1024) { // 128MB
    $performanceTracker->alertHighMemoryUsage($memoryUsage);
}

// Reportes de rendimiento
$report = $performanceTracker->generatePerformanceReport([
    'timeframe' => '24h',
    'include_queries' => true,
    'include_memory' => true
]);
```

**Casos de uso específicos:**
- Optimización de páginas de dashboard
- Monitoreo de APIs de servicios
- Análisis de carga durante picos de uso

---

### SystemController.php

#### **¿Qué es?**
Un controlador central del sistema que gestiona el estado general de la aplicación, coordina servicios y mantiene la operación estable.

#### **¿Para qué sirve?**
- **Controlar estado del sistema** (activo, mantenimiento, emergencia)
- **Coordinar inicialización** de todos los componentes
- **Gestionar configuraciones** dinámicas del sistema
- **Implementar circuit breakers** para servicios externos
- **Manejar shutdowns** y reinicios seguros

#### **¿Cómo se usa?**
```php
// Inicialización del controlador del sistema
$systemController = new SystemController();

// Verificar estado del sistema
if (!$systemController->isSystemHealthy()) {
    $systemController->enterMaintenanceMode();
}

// Gestión de servicios
$systemController->registerService('email_service', $emailService);
$systemController->registerService('payment_gateway', $paymentGateway);

// Verificar disponibilidad de servicios
if (!$systemController->isServiceAvailable('payment_gateway')) {
    // Usar servicio alternativo o diferir operación
    $systemController->handleServiceUnavailability('payment_gateway');
}

// Configuración dinámica
$systemController->updateConfiguration([
    'max_concurrent_users' => 500,
    'enable_debug_mode' => false,
    'maintenance_window' => '2025-09-15 02:00:00'
]);

// Shutdown seguro
register_shutdown_function([$systemController, 'gracefulShutdown']);
```

**Casos de uso específicos:**
- Mantenimiento programado del sistema
- Gestión de actualizaciones de software
- Control de carga durante eventos masivos

---

### SystemMaintenance.php

#### **¿Qué es?**
Un sistema de mantenimiento automatizado que realiza tareas de limpieza, optimización y mantenimiento preventivo de la aplicación.

#### **¿Para qué sirve?**
- **Limpiar archivos temporales** y logs antiguos automáticamente
- **Optimizar base de datos** con tareas de mantenimiento programadas
- **Actualizar índices** y estadísticas de rendimiento
- **Verificar integridad** de archivos y configuraciones
- **Ejecutar backups** automáticos del sistema

#### **¿Cómo se usa?**
```php
// Configurar mantenimiento automático
$maintenance = new SystemMaintenance();

// Programar tareas de mantenimiento
$maintenance->scheduleTask('cleanup_logs', [
    'frequency' => 'daily',
    'time' => '02:00',
    'retention_days' => 30
]);

$maintenance->scheduleTask('optimize_database', [
    'frequency' => 'weekly',
    'day' => 'sunday',
    'time' => '03:00'
]);

// Ejecutar mantenimiento manual
$maintenanceResult = $maintenance->runMaintenance([
    'clean_cache' => true,
    'optimize_images' => true,
    'update_statistics' => true
]);

// Modo de mantenimiento para usuarios
$maintenance->enableMaintenanceMode([
    'message' => 'Sistema en mantenimiento. Disponible en 30 minutos.',
    'allowed_ips' => ['192.168.1.100'], // IPs de administradores
    'estimated_duration' => 1800 // 30 minutos
]);
```

**Casos de uso específicos:**
- Mantenimiento nocturno automático
- Optimización de base de datos de condominios
- Limpieza de archivos de upload antiguos

---

### SystemMonitor.php

#### **¿Qué es?**
Un monitor integral del sistema que supervisa métricas clave, detecta anomalías y genera alertas para mantener la salud operacional.

#### **¿Para qué sirve?**
- **Monitorear métricas del servidor** (CPU, memoria, disco, red)
- **Detectar anomalías** en patrones de uso y comportamiento
- **Generar alertas** automáticas para administradores
- **Mantener historial** de métricas para análisis de tendencias
- **Predecir problemas** antes de que afecten usuarios

#### **¿Cómo se usa?**
```php
// Iniciar monitoreo del sistema
$systemMonitor = new SystemMonitor();

// Configurar métricas a monitorear
$systemMonitor->addMetric('cpu_usage', [
    'threshold_warning' => 70,
    'threshold_critical' => 90,
    'check_interval' => 60
]);

$systemMonitor->addMetric('memory_usage', [
    'threshold_warning' => 80,
    'threshold_critical' => 95,
    'check_interval' => 60
]);

// Monitoreo de aplicación
$systemMonitor->monitorApplication([
    'response_time_threshold' => 3000, // 3 segundos
    'error_rate_threshold' => 5, // 5% de errores
    'concurrent_users_max' => 1000
]);

// Configurar alertas
$systemMonitor->setAlertHandlers([
    'email' => 'admin@cyberhole.net',
    'sms' => '+52123456789',
    'webhook' => 'https://alerts.cyberhole.net/webhook'
]);

// Generar reportes
$report = $systemMonitor->generateSystemReport('weekly');
```

**Casos de uso específicos:**
- Monitoreo 24/7 de producción
- Alertas de problemas críticos
- Análisis de capacidad y crecimiento

---

## 🔗 Integración y Flujo de Trabajo

### Orden de Ejecución Recomendado

1. **SystemController** - Verificar estado del sistema
2. **GeoFirewall** - Filtrar por ubicación geográfica
3. **RequestThrottler** - Limitar velocidad de peticiones
4. **HoneypotDetector** - Detectar bots y automatización
5. **CsrfShield** - Validar tokens CSRF (solo formularios)
6. **InputSanitizer** - Limpiar y validar datos de entrada
7. **SecurityManager** - Aplicar políticas de seguridad
8. **CacheManager** - Optimizar respuestas con caché
9. **PerformanceTracker** - Medir rendimiento
10. **SystemMonitor** - Registrar métricas

### Configuración Global

```php
// Configuración unificada de middlewares
$middlewareConfig = [
    'geo_firewall' => [
        'enabled' => true,
        'allowed_countries' => ['MX', 'US', 'CA'],
        'block_vpn' => true
    ],
    'request_throttler' => [
        'enabled' => true,
        'general_limit' => 60,
        'login_limit' => 5
    ],
    'csrf_protection' => [
        'enabled' => true,
        'token_lifetime' => 3600
    ],
    'input_sanitization' => [
        'enabled' => true,
        'strict_mode' => true
    ]
];
```

Esta documentación proporciona una guía completa para entender, implementar y usar todos los middlewares de ciberseguridad del sistema Cyberhole Condominios.