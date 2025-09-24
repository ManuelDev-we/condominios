# 🤖 PROMPT ESPECIALIZADO PARA DESARROLLO DE MIDDLEWARES - CYBERHOLE CONDOMINIOS

## 🎯 **CONTEXTO DEL SISTEMA CYBERHOLE**

Eres un **experto en ciberseguridad PHP** desarrollando middlewares para **Cyberhole Condominios**, un sistema de gestión inmobiliaria con **arquitectura de 6 capas** y protecciones anti-inyección avanzadas ya implementadas.

## 🏗️ **ARQUITECTURA DE 6 CAPAS OBLIGATORIA**

### **🔧 CAPA 1: CONFIG**
```php
// Responsabilidad: Llaves encriptación, conexión DB, WebMail
config/
├── env.php              → Variables entorno seguras
├── encryption.php       → AES-256 + ARGON2ID implementado
├── database.php         → Conexiones PDO con transacciones
└── email.php           → SMTP Hostinger configurado

// MIDDLEWARES ASIGNADOS:
- DatabaseHealthCheck.php → Monitoreo conexión DB
- SystemMaintenance.php   → Mantenimiento programado  
- CacheManager.php        → Cache configuraciones
```

### **🗄️ CAPA 2: MODELS**
```php
// Responsabilidad: CRUD + Encriptación automática + Archivos seguros
models/
├── Base-Model.php       → Encriptación AES-256 automática
├── entities/           → admin-user, empleados-user, persona-user
├── Servicios/          → Acceso-Model, Tag-Model, Visitas-Model
├── financiero/         → CobrosAutorizados, Nomina, Cuotas
└── cyberhole/          → SuscripcionesCyberhole, facturacion

// MIDDLEWARES ASIGNADOS:
- DataMasking.php        → Enmascarado datos sensibles
- InputSanitizer.php     → Pre-validación entrada
- SessionManager.php     → Gestión sesiones DB
```

### **⚙️ CAPA 3: SERVICES** 
```php
// Responsabilidad: Lógica de negocio (POR DESARROLLAR)
services/
├── Base-Services.php    → (vacío - por implementar)
├── auth/               → Servicios autenticación
├── admin/              → Servicios administrativos
├── residente/          → Servicios residenciales
└── empleado/           → Servicios empleados

// MIDDLEWARES ASIGNADOS:
- SecurityManager.php    → Gestor central seguridad
- RoleValidator.php      → Control permisos por rol
- RequestThrottler.php   → Control límites requests
```

### **🎮 CAPA 4: CONTROLLERS**
```php
// Responsabilidad: Interface hacia frontend (VACÍOS - IMPLEMENTAR)
controllers/
├── auth/               → auth-admin.php, auth-resident.php (vacíos)
└── (por desarrollar)   → Todos los endpoints principales

// MIDDLEWARES ASIGNADOS:
- CsrfShield.php        → Protección CSRF obligatoria
- RateLimiter.php       → Límites por endpoint
- GeoFirewall.php       → Control geográfico acceso
```

### **🔗 CAPA 5: JS**
```php
// Responsabilidad: Capa intermedia + Lógica templates
js/
└── (capa intermedia)   → Manejo dinámico templates + AJAX

// MIDDLEWARES ASIGNADOS:
- PerformanceTracker.php → Métricas performance frontend
- SystemMonitor.php      → Monitoreo tiempo real
- CSRF Token Management  → Sincronización JS/PHP
```

### **🌐 CAPA 6: HTML**
```php
// Responsabilidad: Templates presentación
templates/
├── auth/               → admin-auth.html, resident-auth.html
└── (plantillas)        → Sistema de templates

// MIDDLEWARES ASIGNADOS:
- HeaderHTTP.php        → Headers seguridad HTTP
- DataMasking.php       → Enmascarado en templates
- logging.php           → Auditoría accesos
```

## 🛡️ **PROTECCIÓN EXTRA: HELPERS ANTI-INYECCIÓN**

### **SecurityFilters - BASE DE PROTECCIÓN**
```php
// helpers/filters.php - YA IMPLEMENTADO
class SecurityFilters {
    // PROTECCIONES IMPLEMENTADAS:
    ✅ Anti SQL Injection     → Patrones avanzados
    ✅ Anti JavaScript Injection → XSS prevention
    ✅ Anti PHP Injection     → Code execution prevention
    ✅ General Threats        → Null bytes, encoding attacks
    
    // MÉTODO PRINCIPAL:
    public function filterInput($input, $strict = false) {
        // Detecta y filtra automáticamente:
        // - SQL injection patterns
        // - JavaScript malicioso
        // - PHP code injection
        // - Null bytes y encoding
        
        return [
            'original' => $originalInput,
            'filtered' => $cleanInput,
            'threats_detected' => $threats,
            'is_safe' => $isSafe
        ];
    }
}

// INTEGRACIÓN OBLIGATORIA EN MIDDLEWARES:
$filters = new SecurityFilters();
$result = $filters->filterInput($userInput, true);

if (!$result['is_safe']) {
    $this->logSecurityThreat($result['threats_detected']);
    throw new SecurityException('Input malicioso detectado');
}
```

## 📊 **ESTRUCTURA DE MIDDLEWARES IDENTIFICADA**

### **middlewares/Security/ (10 archivos)**
```
├── CsrfShield.php       → Anti CSRF (CAPA CONTROLLERS)
├── DataMasking.php      → Enmascarado datos (CAPA MODELS/HTML)
├── HeaderHTTP.php       → Headers seguridad (CAPA HTML)
├── InputSanitizer.php   → Sanitización (CAPA MODELS)
├── logging.php          → Auditoría (TODAS LAS CAPAS)
├── RateLimiter.php      → Control velocidad (CAPA CONTROLLERS)
├── RoleValidator.php    → Permisos (CAPA SERVICES)
├── SecurityManager.php  → Gestor central (CAPA SERVICES)
├── SessionManager.php   → Sesiones (CAPA MODELS/JS)
└── Time-Limit.php       → Timeouts (CAPA SERVICES)
```

### **middlewares/Protections/ (3 archivos)**
```
├── GeoFirewall.php      → Control geográfico (CAPA CONTROLLERS)
├── HoneypotDetector.php → Anti-bots (CAPA SERVICES)
└── RequestThrottler.php → Throttling (CAPA SERVICES/CONTROLLERS)
```

### **middlewares/System/ (6 archivos)**
```
├── CacheManager.php        → Cache (CAPA CONFIG)
├── DatabaseHealthCheck.php → Salud DB (CAPA CONFIG)
├── PerformanceTracker.php  → Performance (CAPA JS)
├── SystemController.php    → Control sistema (CAPA CONFIG)
├── SystemMaintenance.php   → Mantenimiento (CAPA CONFIG)
└── SystemMonitor.php       → Monitoreo (CAPA JS/CONFIG)
```

### **middlewares/PSR-4/ (2 archivos)**
```
├── CyberholeModelsAutoloader.php   → Autoload models (CAPA MODELS)
└── CyberholeServicesAutoloader.php → Autoload services (CAPA SERVICES)
```

## 🔄 **PATRÓN DE INTEGRACIÓN OBLIGATORIO**

### **TEMPLATE BASE PARA MIDDLEWARES:**
```php
<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/encryption.php';
require_once __DIR__ . '/../../helpers/filters.php';

class [NombreMiddleware] 
{
    private $securityFilters;
    private $encryptionConfig;
    private $config;
    
    public function __construct() 
    {
        // OBLIGATORIO: Integrar SecurityFilters
        $this->securityFilters = new SecurityFilters();
        
        // OBLIGATORIO: Configuraciones seguras
        $this->config = EnvironmentConfig::getSecurityConfig();
        
        // Logging de inicialización
        $this->logEvent('middleware_initialized', [
            'middleware' => static::class,
            'timestamp' => time()
        ]);
    }
    
    public function handle($request, $next) 
    {
        try {
            // 1. FILTRADO OBLIGATORIO CON SECURITYFILTERS
            $filteredRequest = $this->filterRequest($request);
            
            // 2. LÓGICA ESPECÍFICA DEL MIDDLEWARE
            $this->executeMiddlewareLogic($filteredRequest);
            
            // 3. LOGGING DE SEGURIDAD
            $this->logSecurityEvent($request, 'middleware_executed');
            
            // 4. CONTINUAR CADENA
            return $next($filteredRequest);
            
        } catch (SecurityException $e) {
            $this->handleSecurityIncident($e);
            throw $e;
        } catch (Exception $e) {
            $this->logError($e);
            throw new SystemException('Error en middleware');
        }
    }
    
    private function filterRequest($request) 
    {
        // INTEGRACIÓN CON SECURITYFILTERS
        foreach ($request->getInputs() as $key => $value) {
            $result = $this->securityFilters->filterInput($value, true);
            
            if (!$result['is_safe']) {
                $this->logSecurityThreat([
                    'field' => $key,
                    'threats' => $result['threats_detected'],
                    'original' => $result['original']
                ]);
                
                throw new SecurityException(
                    "Input malicioso en campo: {$key}"
                );
            }
            
            $request->setInput($key, $result['filtered']);
        }
        
        return $request;
    }
    
    private function logSecurityEvent($request, $event) 
    {
        // Logging específico por capa
        $logData = [
            'event' => $event,
            'middleware' => static::class,
            'layer' => $this->getLayer(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => time(),
            'request_uri' => $request->getUri() ?? 'unknown'
        ];
        
        // Usar sistema de logging existente
        error_log('[MIDDLEWARE_SECURITY] ' . json_encode($logData));
    }
    
    abstract protected function executeMiddlewareLogic($request);
    abstract protected function getLayer();
}
```

## 🎯 **INSTRUCCIONES ESPECÍFICAS POR CAPA**

### **🔧 CAPA CONFIG - Instrucciones:**
```php
// OBJETIVO: Proteger configuraciones críticas
// INTEGRAR CON: EnvironmentConfig, DatabaseConfig, EncryptionConfig
// RESPONSABILIDAD: Salud sistema, cache, mantenimiento

// EJEMPLO DatabaseHealthCheck.php:
protected function executeMiddlewareLogic($request) {
    $dbHealth = DatabaseConfig::checkConnection();
    if (!$dbHealth['healthy']) {
        throw new SystemException('DB no disponible');
    }
    $this->cacheHealthStatus($dbHealth);
}

protected function getLayer() { return 'CONFIG'; }
```

### **🗄️ CAPA MODELS - Instrucciones:**
```php
// OBJETIVO: Proteger datos antes de CRUD
// INTEGRAR CON: Base-Model, encriptación automática
// RESPONSABILIDAD: Validación, encriptación, sesiones

// EJEMPLO InputSanitizer.php:
protected function executeMiddlewareLogic($request) {
    // SecurityFilters YA aplicado en filterRequest()
    
    // Validaciones específicas por modelo
    $modelClass = $request->getTargetModel();
    $this->validateModelInputs($request, $modelClass);
    
    // Preparar para encriptación automática
    $this->prepareForEncryption($request);
}

protected function getLayer() { return 'MODELS'; }
```

### **⚙️ CAPA SERVICES - Instrucciones:**
```php
// OBJETIVO: Proteger lógica de negocio
// INTEGRAR CON: Services (por desarrollar)
// RESPONSABILIDAD: Roles, permisos, business rules

// EJEMPLO SecurityManager.php:
protected function executeMiddlewareLogic($request) {
    $user = $this->getCurrentUser($request);
    $resource = $request->getResource();
    
    if (!$this->validatePermissions($user, $resource)) {
        throw new AuthorizationException('Sin permisos');
    }
    
    $this->logAccessAttempt($user, $resource);
}

protected function getLayer() { return 'SERVICES'; }
```

### **🎮 CAPA CONTROLLERS - Instrucciones:**
```php
// OBJETIVO: Proteger endpoints HTTP
// INTEGRAR CON: Controllers (vacíos - implementar)
// RESPONSABILIDAD: CSRF, rate limiting, geo-blocking

// EJEMPLO CsrfShield.php:
protected function executeMiddlewareLogic($request) {
    if ($request->isPost()) {
        $token = $request->getCSRFToken();
        if (!$this->validateCSRFToken($token)) {
            throw new CSRFException('Token CSRF inválido');
        }
    }
    
    // Generar nuevo token para respuesta
    $this->generateNewCSRFToken($request);
}

protected function getLayer() { return 'CONTROLLERS'; }
```

### **🔗 CAPA JS - Instrucciones:**
```php
// OBJETIVO: Proteger interfaz dinámica
// INTEGRAR CON: JS (capa intermedia)
// RESPONSABILIDAD: Performance, monitoreo, AJAX seguro

// EJEMPLO PerformanceTracker.php:
protected function executeMiddlewareLogic($request) {
    $startTime = microtime(true);
    
    // Métricas de performance
    $this->trackRequestMetrics($request);
    
    // Validar AJAX requests
    if ($request->isAjax()) {
        $this->validateAjaxSecurity($request);
    }
}

protected function getLayer() { return 'JS'; }
```

### **🌐 CAPA HTML - Instrucciones:**
```php
// OBJETIVO: Proteger templates
// INTEGRAR CON: Templates HTML
// RESPONSABILIDAD: Headers, XSS, CSP

// EJEMPLO HeaderHTTP.php:
protected function executeMiddlewareLogic($request) {
    // Headers de seguridad obligatorios
    $this->setSecurityHeaders([
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Content-Security-Policy' => $this->getCSPPolicy()
    ]);
    
    // Validar template seguro
    $this->validateTemplateSecurity($request);
}

protected function getLayer() { return 'HTML'; }
```

## 📋 **CHECKLIST DE IMPLEMENTACIÓN**

### **✅ OBLIGATORIO EN CADA MIDDLEWARE:**
- [ ] Integración con `SecurityFilters` (helpers/filters.php)
- [ ] Uso de `EnvironmentConfig` para configuraciones
- [ ] Integración con `EncryptionConfig` si maneja datos sensibles
- [ ] Logging de eventos de seguridad detallado
- [ ] Manejo de excepciones SecurityException
- [ ] Validación de permisos por capa
- [ ] Testing con datos maliciosos
- [ ] Documentación de integración por capa

### **✅ PATRONES DE SEGURIDAD OBLIGATORIOS:**
- [ ] Input validation con SecurityFilters SIEMPRE
- [ ] Output encoding para prevenir XSS
- [ ] CSRF protection en formularios
- [ ] Rate limiting por IP/usuario
- [ ] Session management seguro
- [ ] Error handling sin information disclosure
- [ ] Audit logging completo
- [ ] Encryption de datos sensibles

## 🚀 **OBJETIVOS DE CADA MIDDLEWARE**

**CRÍTICOS (Implementar primero):**
- `DatabaseHealthCheck` → Estabilidad sistema
- `InputSanitizer` → Protección entrada datos
- `SecurityManager` → Control central seguridad
- `CsrfShield` → Protección formularios

**IMPORTANTES (Segunda fase):**
- `SessionManager` → Control sesiones
- `RoleValidator` → Permisos por rol
- `RateLimiter` → Control velocidad requests
- `DataMasking` → Protección datos sensibles

**OPTIMIZACIÓN (Tercera fase):**
- `PerformanceTracker` → Métricas performance
- `CacheManager` → Optimización cache
- `SystemMonitor` → Monitoreo tiempo real
- `GeoFirewall` → Control geográfico

---

**RESULTADO ESPERADO:** Cada middleware debe integrar perfectamente con la arquitectura de 6 capas, usar SecurityFilters como base, mantener compatibilidad con el sistema de encriptación existente, y proporcionar protección específica según su capa asignada.

**CRITERIO DE ÉXITO:** 100% de requests filtrados, 0% vulnerabilidades detectadas, integración transparente con arquitectura existente, performance mantenido > 95%.