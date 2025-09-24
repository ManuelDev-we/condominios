<?php
/**
 * 🔐 ENCRYPTION CLEAN - Sistema de Encriptación Simplificado y Seguro
 * Funcionalidades específicas: SHA hash, AES-256, password check, array encryption
 * 
 * @package Cyberhole\Configuration
 * @author ManuelDev
 * @version 2.0 CLEAN
 */

require_once __DIR__ . '/env.php';

class EncryptionConfig 
{
    private static $config = null;
    private static $key = null;
    private static $pepper = null;
    
    /**
     * Inicializar configuración de encriptación
     */
    private static function init(): void 
    {
        if (self::$config === null) {
            self::$config = EnvironmentConfig::getEncryptionConfig();
            
            // Generar clave AES-256 desde configuración
            $secretKey = self::$config['secret_key'] ?? 'cyberhole_default_secret_2025';
            self::$key = hash('sha256', $secretKey, true); // 32 bytes para AES-256
            
            // Pepper para passwords
            self::$pepper = self::$config['pepper'] ?? 'cyberhole_pepper_secret_2025';
        }
    }
    
    /**
     * Hash SHA-256 para datos generales
     */
    public static function hashSHA(string $data): string 
    {
        self::init();
        return hash('sha256', $data);
    }
    
    /**
     * Hash seguro de contraseñas con pepper
     */
    public static function hashPassword(string $password): string 
    {
        self::init();
        
        // Agregar pepper y usar password_hash con costo alto
        $pepperedPassword = $password . self::$pepper;
        return password_hash($pepperedPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64 MB
            'time_cost' => 4,        // 4 iteraciones
            'threads' => 3           // 3 threads
        ]);
    }
    
    /**
     * Verificar contraseña con pepper
     */
    public static function checkPassword(string $password, string $hash): bool 
    {
        self::init();
        
        try {
            $pepperedPassword = $password . self::$pepper;
            return password_verify($pepperedPassword, $hash);
        } catch (Exception $e) {
            error_log("Password verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encriptar con AES-256-CBC
     */
    public static function encryptAES256(string $data): string 
    {
        self::init();
        
        try {
            $cipher = 'AES-256-CBC';
            $ivLength = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            $encrypted = openssl_encrypt($data, $cipher, self::$key, OPENSSL_RAW_DATA, $iv);
            
            if ($encrypted === false) {
                throw new Exception('Error en encriptación AES-256');
            }
            
            // Combinar IV + datos encriptados y codificar en base64
            return base64_encode($iv . $encrypted);
            
        } catch (Exception $e) {
            error_log("AES-256 encryption error: " . $e->getMessage());
            throw new Exception("Error al encriptar datos: " . $e->getMessage());
        }
    }
    
    /**
     * Desencriptar con AES-256-CBC
     */
    public static function decryptAES256(string $encryptedData): string 
    {
        self::init();
        
        try {
            $cipher = 'AES-256-CBC';
            $data = base64_decode($encryptedData);
            
            if ($data === false) {
                throw new Exception('Datos base64 inválidos');
            }
            
            $ivLength = openssl_cipher_iv_length($cipher);
            
            if (strlen($data) < $ivLength) {
                throw new Exception('Datos encriptados demasiado cortos');
            }
            
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, $cipher, self::$key, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                throw new Exception('Error en desencriptación AES-256');
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log("AES-256 decryption error: " . $e->getMessage());
            throw new Exception("Error al desencriptar datos: " . $e->getMessage());
        }
    }
    
    /**
     * Encriptar array completo
     */
    public static function encryptArray(array $data): string 
    {
        try {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            if ($jsonData === false) {
                throw new Exception('Error al convertir array a JSON');
            }
            
            return self::encryptAES256($jsonData);
            
        } catch (Exception $e) {
            error_log("Array encryption error: " . $e->getMessage());
            throw new Exception("Error al encriptar array: " . $e->getMessage());
        }
    }
    
    /**
     * Desencriptar array
     */
    public static function decryptArray(string $encryptedData): array 
    {
        try {
            $jsonData = self::decryptAES256($encryptedData);
            $data = json_decode($jsonData, true);
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al convertir JSON a array: ' . json_last_error_msg());
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Array decryption error: " . $e->getMessage());
            throw new Exception("Error al desencriptar array: " . $e->getMessage());
        }
    }
    
    /**
     * Validar configuración de encriptación
     */
    public static function validateConfig(): bool 
    {
        try {
            self::init();
            
            // Verificar que OpenSSL esté disponible
            if (!extension_loaded('openssl')) {
                throw new Exception('Extensión OpenSSL no disponible');
            }
            
            // Verificar que AES-256-CBC esté soportado
            $cipherMethods = openssl_get_cipher_methods();
            if (!in_array('AES-256-CBC', $cipherMethods) && !in_array('aes-256-cbc', $cipherMethods)) {
                throw new Exception('Cipher AES-256-CBC no soportado');
            }
            
            // Test rápido de encriptación/desencriptación
            $testData = 'test_validation_data_2025';
            $encrypted = self::encryptAES256($testData);
            $decrypted = self::decryptAES256($encrypted);
            
            if ($decrypted !== $testData) {
                throw new Exception('Test de encriptación/desencriptación falló');
            }
            
            // Test de array
            $testArray = ['test' => 'data', 'number' => 123];
            $encryptedArray = self::encryptArray($testArray);
            $decryptedArray = self::decryptArray($encryptedArray);
            
            if ($decryptedArray !== $testArray) {
                throw new Exception('Test de array encryption falló');
            }
            
            // Test de password
            $testPassword = 'test_password_123';
            $hash = self::hashPassword($testPassword);
            $isValid = self::checkPassword($testPassword, $hash);
            
            if (!$isValid) {
                throw new Exception('Test de password hash falló');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Encryption validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener información de configuración (sin datos sensibles)
     */
    public static function getConfigInfo(): array 
    {
        self::init();
        
        return [
            'cipher' => 'AES-256-CBC',
            'hash_algorithm' => 'SHA-256',
            'password_algorithm' => 'ARGON2ID',
            'openssl_available' => extension_loaded('openssl'),
            'key_length' => strlen(self::$key),
            'pepper_configured' => !empty(self::$pepper),
            'config_loaded' => self::$config !== null
        ];
    }
    
    /**
     * Generar clave aleatoria para tokens
     */
    public static function generateRandomKey(int $length = 32): string 
    {
        try {
            $randomBytes = openssl_random_pseudo_bytes($length);
            return bin2hex($randomBytes);
        } catch (Exception $e) {
            // Fallback
            return bin2hex(random_bytes($length));
        }
    }
    
    /**
     * Generar token único
     */
    public static function generateToken(string $prefix = '', int $length = 16): string 
    {
        $randomPart = self::generateRandomKey($length);
        $timestamp = time();
        
        if (!empty($prefix)) {
            return strtoupper($prefix) . '_' . $timestamp . '_' . strtoupper($randomPart);
        }
        
        return $timestamp . '_' . strtoupper($randomPart);
    }
    
    /**
     * Limpiar datos sensibles de memoria (best effort)
     */
    public static function clearSensitiveData(): void 
    {
        if (self::$key !== null) {
            self::$key = str_repeat("\0", strlen(self::$key));
            self::$key = null;
        }
        
        if (self::$pepper !== null) {
            self::$pepper = str_repeat("\0", strlen(self::$pepper));
            self::$pepper = null;
        }
        
        self::$config = null;
    }
    
    /**
     * Destructor para limpiar memoria
     */
    public function __destruct() 
    {
        self::clearSensitiveData();
    }
}

// Funciones helper para compatibilidad
if (!function_exists('cyberhole_hash_password')) {
    function cyberhole_hash_password(string $password): string 
    {
        return EncryptionConfig::hashPassword($password);
    }
}

if (!function_exists('cyberhole_check_password')) {
    function cyberhole_check_password(string $password, string $hash): bool 
    {
        return EncryptionConfig::checkPassword($password, $hash);
    }
}

if (!function_exists('cyberhole_encrypt')) {
    function cyberhole_encrypt(string $data): string 
    {
        return EncryptionConfig::encryptAES256($data);
    }
}

if (!function_exists('cyberhole_decrypt')) {
    function cyberhole_decrypt(string $encryptedData): string 
    {
        return EncryptionConfig::decryptAES256($encryptedData);
    }
}

if (!function_exists('cyberhole_encrypt_array')) {
    function cyberhole_encrypt_array(array $data): string 
    {
        return EncryptionConfig::encryptArray($data);
    }
}

if (!function_exists('cyberhole_decrypt_array')) {
    function cyberhole_decrypt_array(string $encryptedData): array 
    {
        return EncryptionConfig::decryptArray($encryptedData);
    }
}
?>
