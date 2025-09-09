<?php
/**
 * 🔐 Configuración de Entorno Segura
 * Carga y manejo seguro de variables de entorno desde .env
 * 
 * @package Cyberhole\Configuration
 * @author ManuelDev
 * @version 1.0
 */

class EnvironmentConfig 
{
    private static $variables = [];
    private static $loaded = false;
    
    /**
     * Cargar variables desde archivo .env
     */
    public static function load(): void 
    {
        if (self::$loaded) {
            return;
        }
        
        $envFile = dirname(__DIR__) . '/.env';
        
        if (!file_exists($envFile)) {
            throw new Exception("Archivo .env no encontrado en: " . $envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Saltar comentarios
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            // Parsear línea variable=valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || 
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$variables[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtener valor de variable de entorno
     */
    public static function get(string $key, $default = null) 
    {
        self::load();
        return self::$variables[$key] ?? $default;
    }
    
    /**
     * Obtener variable requerida (lanza excepción si no existe)
     */
    public static function getRequired(string $key) 
    {
        $value = self::get($key);
        if ($value === null) {
            throw new Exception("Variable de entorno requerida no encontrada: " . $key);
        }
        return $value;
    }
    
    /**
     * Obtener configuración de base de datos
     */
    public static function getDatabaseConfig(): array 
    {
        return [
            'host' => self::getRequired('DB_HOST'),
            'database' => self::getRequired('DB_DATABASE'),
            'username' => self::getRequired('DB_USERNAME'),
            'password' => self::getRequired('DB_PASSWORD'),
            'charset' => self::get('DB_CHARSET', 'utf8mb4'),
            'collation' => self::get('DB_COLLATION', 'utf8mb4_unicode_ci')
        ];
    }
    
    /**
     * Obtener configuración de correo
     */
    public static function getMailConfig(): array 
    {
        return [
            'mailer' => self::get('MAIL_MAILER', 'smtp'),
            'host' => self::getRequired('MAIL_HOST'),
            'port' => (int)self::get('MAIL_PORT', 587),
            'username' => self::getRequired('MAIL_USERNAME'),
            'password' => self::getRequired('MAIL_PASSWORD'),
            'encryption' => self::get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => self::get('MAIL_FROM_ADDRESS', self::getRequired('MAIL_USERNAME')),
            'from_name' => self::get('MAIL_FROM_NAME', 'Cyberhole CRM')
        ];
    }
    
    /**
     * Obtener configuración de seguridad y JWT
     */
    public static function getSecurityConfig(): array 
    {
        return [
            'jwt_secret' => self::getRequired('JWT_SECRET'),
            'session_lifetime' => (int)self::get('SESSION_LIFETIME', 3600),
            'max_requests_per_hour' => (int)self::get('MAX_REQUESTS_PER_HOUR', 100),
            'max_login_attempts' => (int)self::get('MAX_LOGIN_ATTEMPTS', 5),
            'lockout_duration' => (int)self::get('LOCKOUT_DURATION', 900),
            'bcrypt_rounds' => (int)self::get('BCRYPT_ROUNDS', 12),
            'pepper_secret' => self::getRequired('PEPPER_SECRET'),
            'cyberhole_encryption_key' => self::getRequired('CYBERHOLE_ENCRYPTION_KEY'),
            'cyberhole_password_pepper' => self::getRequired('CYBERHOLE_PASSWORD_PEPPER')
        ];
    }
    
    /**
     * Obtener configuración de encriptación AES
     */
    public static function getEncryptionConfig(): array 
    {
        return [
            'aes_key' => self::getRequired('AES_KEY'),
            'aes_method' => self::get('AES_METHOD', 'AES-256-CBC'),
            'key_length' => strlen(self::getRequired('AES_KEY')),
            'cyberhole_encryption_key' => self::getRequired('CYBERHOLE_ENCRYPTION_KEY'),
            'pepper' => self::getRequired('CYBERHOLE_PASSWORD_PEPPER')
        ];
    }
    
    /**
     * Obtener configuración CORS
     */
    public static function getCorsConfig(): array 
    {
        $allowedOrigins = self::get('ALLOWED_ORIGINS', '');
        $originsArray = !empty($allowedOrigins) ? explode(',', $allowedOrigins) : [];
        
        return [
            'allowed_origins' => $originsArray,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'expose_headers' => ['Content-Length', 'X-Kuma-Revision'],
            'max_age' => 86400,
            'supports_credentials' => true
        ];
    }
    
    /**
     * Obtener configuración del sitio
     */
    public static function getSiteConfig(): array 
    {
        return [
            'url' => self::get('SITE_URL', 'http://localhost'),
            'name' => self::get('SITE_NAME', 'Cyberhole Condominios'),
            'admin_email' => self::get('ADMIN_EMAIL', 'admin@cyberhole.net'),
            'environment' => self::get('APP_ENV', 'development'),
            'debug_mode' => filter_var(self::get('DEBUG_MODE', 'false'), FILTER_VALIDATE_BOOLEAN)
        ];
    }
    
    /**
     * Obtener configuración de uploads
     */
    public static function getUploadConfig(): array 
    {
        return [
            'upload_path' => self::get('UPLOAD_PATH', 'uploads/'),
            'max_file_size' => (int)self::get('MAX_FILE_SIZE', 2097152), // 2MB default
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
        ];
    }
    
    /**
     * Obtener configuración de logs
     */
    public static function getLogConfig(): array 
    {
        return [
            'level' => self::get('LOG_LEVEL', 'INFO'),
            'file' => self::get('LOG_FILE', 'logs/app.log'),
            'max_size' => (int)self::get('MAX_LOG_SIZE', 10485760) // 10MB default
        ];
    }
    
    /**
     * Validar que todas las variables críticas existan
     */
    public static function validate(): bool 
    {
        $required = [
            // Base de datos
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            // Correo
            'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD',
            // Seguridad
            'JWT_SECRET', 'PEPPER_SECRET', 'CYBERHOLE_ENCRYPTION_KEY', 'CYBERHOLE_PASSWORD_PEPPER',
            // Encriptación
            'AES_KEY'
        ];
        
        foreach ($required as $var) {
            if (empty(self::get($var))) {
                throw new Exception("Variable crítica faltante: " . $var);
            }
        }
        
        // Validar longitud de claves
        $aesKey = self::get('AES_KEY');
        if (strlen($aesKey) !== 32) {
            throw new Exception("AES_KEY debe tener exactamente 32 caracteres");
        }
        
        return true;
    }
    
    /**
     * Obtener configuración completa del sistema
     */
    public static function getAllConfigs(): array 
    {
        return [
            'database' => self::getDatabaseConfig(),
            'mail' => self::getMailConfig(),
            'security' => self::getSecurityConfig(),
            'encryption' => self::getEncryptionConfig(),
            'cors' => self::getCorsConfig(),
            'site' => self::getSiteConfig(),
            'upload' => self::getUploadConfig(),
            'log' => self::getLogConfig()
        ];
    }
    
    /**
     * Verificar si estamos en modo debug
     */
    public static function isDebugMode(): bool 
    {
        return filter_var(self::get('DEBUG_MODE', 'false'), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Verificar si estamos en producción
     */
    public static function isProduction(): bool 
    {
        return self::get('APP_ENV', 'development') === 'production';
    }
    
    /**
     * Generar hash seguro con pepper
     */
    public static function hashPassword(string $password): string 
    {
        $pepper = self::getRequired('CYBERHOLE_PASSWORD_PEPPER');
        $rounds = (int)self::get('BCRYPT_ROUNDS', 12);
        return password_hash($password . $pepper, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }
    
    /**
     * Verificar password con pepper
     */
    public static function verifyPassword(string $password, string $hash): bool 
    {
        $pepper = self::getRequired('CYBERHOLE_PASSWORD_PEPPER');
        return password_verify($password . $pepper, $hash);
    }
}

// Funciones helper para compatibilidad
function env(string $key, $default = null) 
{
    return EnvironmentConfig::get($key, $default);
}

function database_config(): array 
{
    return EnvironmentConfig::getDatabaseConfig();
}

function mail_config(): array 
{
    return EnvironmentConfig::getMailConfig();
}

function security_config(): array 
{
    return EnvironmentConfig::getSecurityConfig();
}

function encryption_config(): array 
{
    return EnvironmentConfig::getEncryptionConfig();
}

function cors_config(): array 
{
    return EnvironmentConfig::getCorsConfig();
}

function site_config(): array 
{
    return EnvironmentConfig::getSiteConfig();
}

function upload_config(): array 
{
    return EnvironmentConfig::getUploadConfig();
}

function log_config(): array 
{
    return EnvironmentConfig::getLogConfig();
}

function is_debug(): bool 
{
    return EnvironmentConfig::isDebugMode();
}

function is_production(): bool 
{
    return EnvironmentConfig::isProduction();
}

function hash_password(string $password): string 
{
    return EnvironmentConfig::hashPassword($password);
}

function verify_password(string $password, string $hash): bool 
{
    return EnvironmentConfig::verifyPassword($password, $hash);
}

// Auto-cargar al incluir este archivo
EnvironmentConfig::load();
