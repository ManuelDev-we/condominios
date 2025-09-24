# 🔥 GeoFirewall - Control de Acceso Geográfico

Middleware de seguridad avanzado que controla el acceso a tu aplicación basado en geolocalización IP. Diseñado específicamente para el sistema de condominios Cyberhole.

## 🌍 Características Principales

- ✅ **Control por País**: 37 países permitidos organizados por idioma
- 🛡️ **Protección Automática**: Una línea de código protege toda tu aplicación  
- 📊 **Logging Completo**: Registra todos los intentos de acceso
- 🔧 **IPs de Desarrollo**: Siempre permite localhost y redes privadas
- 🚫 **Lista de Bloqueo**: Países de alto riesgo automáticamente bloqueados
- 📈 **Estadísticas**: Dashboard de acceso geográfico
- 🌐 **IPv4 e IPv6**: Soporte completo para ambos protocolos

## 📋 Países Permitidos

### 🇪🇸 Latinoamérica (Español) - 19 países
- **México** (prioridad alta)
- **Argentina, Colombia, Chile, Perú, Venezuela, Ecuador** (prioridad media)
- **Guatemala, Costa Rica, Panamá, Rep. Dominicana** (prioridad media)
- **Bolivia, Paraguay, Uruguay, Nicaragua, Honduras, El Salvador, Cuba, Puerto Rico** (prioridad baja)

### 🇺🇸 América (Inglés) - 8 países
- **Estados Unidos, Canadá** (prioridad alta)
- **Jamaica, Barbados, Trinidad y Tobago, Bahamas, Belice, Guyana** (prioridad baja)

### 🇪🇺 Europa (Inglés/Español/Francés) - 10 países
- **España, Reino Unido, Francia** (prioridad alta)
- **Irlanda, Bélgica, Suiza** (prioridad media)
- **Luxemburgo, Mónaco, Malta, Chipre** (prioridad baja)

## 🚀 Instalación

### 1. Estructura de Archivos
```
middlewares/
├── Protections/
│   └── GeoFirewall.php
└── data/
    └── geo_database.json
logs/
└── geo_access.log (se crea automáticamente)
```

### 2. Configuración Automática
El sistema se configura automáticamente al instanciar. No requiere configuración adicional.

## 📖 Uso

### Protección Automática (Recomendado)
```php
<?php
require_once 'middlewares/Protections/GeoFirewall.php';

// Una sola línea protege toda tu aplicación
GeoFirewall::protect();

// Tu código continúa solo si la IP está autorizada
echo "¡Acceso autorizado!";
?>
```

### Verificación Manual
```php
<?php
require_once 'middlewares/Protections/GeoFirewall.php';

$geoFirewall = new GeoFirewall();
$result = $geoFirewall->verifyAccess();

if ($result['allowed']) {
    echo "✅ Acceso desde: " . $result['country'];
    echo "📍 IP: " . $result['ip'];
    // Continuar con tu lógica
} else {
    echo "🚫 Acceso denegado: " . $result['reason'];
    // Manejar acceso denegado
}
?>
```

### Verificación de IP Específica
```php
<?php
$geoFirewall = new GeoFirewall();

// Verificar si una IP estaría permitida (sin logging)
$allowed = $geoFirewall->wouldAllowIP('189.130.1.1');
echo $allowed ? 'Permitida' : 'Bloqueada';
?>
```

## 🔧 Integración en Aplicaciones

### Sistema de Login
```php
<?php
// Verificar geografía antes de autenticación
$geoFirewall = new GeoFirewall();
$geoResult = $geoFirewall->verifyAccess();

if (!$geoResult['allowed']) {
    throw new Exception('Acceso geográfico denegado');
}

// Continuar con login normal
authenticateUser($username, $password);
?>
```

### API REST
```php
<?php
// Proteger endpoints de API
require_once 'middlewares/Protections/GeoFirewall.php';

GeoFirewall::protect(); // Bloquea automáticamente IPs no autorizadas

// Tu API continúa solo con IPs permitidas
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'data' => $apiData]);
?>
```

### Páginas Administrativas
```php
<?php
// Protección extra para áreas sensibles
require_once 'middlewares/Protections/GeoFirewall.php';

$geoFirewall = new GeoFirewall();
$result = $geoFirewall->verifyAccess();

if (!$result['allowed'] || $result['country'] === 'DEV') {
    if ($result['country'] !== 'DEV') {
        // Solo desarrollo o países de alta prioridad
        $countryPriority = getCurrentCountryPriority($result['country']);
        if ($countryPriority !== 'high') {
            throw new Exception('Área restringida');
        }
    }
}
?>
```

## 📊 Monitoreo y Estadísticas

### Obtener Estadísticas
```php
<?php
$geoFirewall = new GeoFirewall();
$stats = $geoFirewall->getAccessStats();

echo "Total requests: " . $stats['total_requests'];
echo "Permitidos: " . $stats['allowed'];
echo "Denegados: " . $stats['denied'];
echo "Bloqueados: " . $stats['blocked'];

// Top IPs más activas
foreach ($stats['ips'] as $ip => $count) {
    echo "$ip: $count requests";
}
?>
```

### Revisar Logs
```bash
# Ver logs en tiempo real
tail -f logs/geo_access.log

# Buscar accesos denegados
grep "DENIED" logs/geo_access.log

# Buscar país específico
grep "México" logs/geo_access.log
```

## 🛡️ Características de Seguridad

### IPs Siempre Permitidas
- `127.0.0.1`, `::1`, `localhost` (desarrollo)
- `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16` (redes privadas)

### Países Bloqueados
- China (CN), Rusia (RU), Corea del Norte (KP)
- Irán (IR), Siria (SY), Afganistán (AF)

### Headers de Seguridad
El middleware detecta automáticamente:
- `X-Real-IP` (Nginx)
- `X-Forwarded-For` (Load Balancer)
- `HTTP_CF_CONNECTING_IP` (Cloudflare)
- `HTTP_CLIENT_IP` (Proxy)

## 📁 Estructura de Respuesta

### Acceso Permitido
```json
{
    "allowed": true,
    "reason": "IP autorizada desde México",
    "ip": "189.130.1.1",
    "country": "MX",
    "priority": "high"
}
```

### Acceso Denegado
```json
{
    "allowed": false,
    "reason": "IP no autorizada geográficamente",
    "ip": "1.1.1.1",
    "country": "UNKNOWN"
}
```

### Error HTTP (Protección Automática)
```json
{
    "error": true,
    "code": 403,
    "message": "Acceso denegado por ubicación geográfica",
    "details": {
        "reason": "IP no autorizada geográficamente",
        "ip": "1.1.1.1",
        "timestamp": "2025-09-21T10:30:00+00:00",
        "support": "Contacta al administrador si crees que esto es un error"
    }
}
```

## 🔧 Configuración Avanzada

### Deshabilitar Temporalmente
```php
<?php
// Editar geo_database.json
{
    "geo_access_control": {
        "enabled": false  // Deshabilita el firewall
    }
}
?>
```

### Personalizar Logging
```php
<?php
// En geo_database.json
{
    "access_rules": {
        "log_all_access": false,  // Deshabilitar logs
        "max_attempts_per_ip": 10,
        "lockout_duration_minutes": 60
    }
}
?>
```

## 📋 Casos de Uso

### ✅ Recomendado Para:
- Sistemas de administración de condominios
- APIs con datos sensibles
- Portales de pago y facturación  
- Dashboards administrativos
- Sistemas de acceso vehicular

### ⚠️ Consideraciones:
- VPNs pueden cambiar la geolocalización aparente
- ISPs móviles pueden tener rangos amplios
- IPs corporativas pueden aparecer en países diferentes

## 🚨 Troubleshooting

### IP Local Bloqueada
```php
// Verificar si tu IP está en desarrollo
$geoFirewall = new GeoFirewall();
$result = $geoFirewall->verifyAccess();
var_dump($result);
```

### Logs No Se Crean
```bash
# Verificar permisos de directorio
chmod 755 logs/
chmod 644 logs/geo_access.log
```

### País No Detectado
- Verificar rangos IP en `geo_database.json`
- La IP puede estar fuera de los rangos configurados
- Revisar logs para ver la IP exacta

## 📈 Rendimiento

- ⚡ **Rápido**: Verificación en <1ms
- 💾 **Liviano**: ~50KB en memoria
- 📊 **Escalable**: Soporta miles de requests/minuto
- 🔧 **Optimizado**: Caching automático de configuración

## 🔄 Actualización

Para agregar nuevos países o rangos IP:
1. Editar `middlewares/data/geo_database.json`
2. Agregar rangos en la sección correspondiente
3. No requiere reinicio del servidor

## 📞 Soporte

Para soporte o reportar problemas:
- Revisar logs en `logs/geo_access.log`
- Verificar configuración en `geo_database.json`
- Ejecutar `test_geofirewall.php` para diagnóstico

---

🛡️ **GeoFirewall** - Protección geográfica para aplicaciones críticas | v2.0 | ManuelDev 2025