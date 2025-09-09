<?php
/**
 * 🔐 Configuración de Encriptación
 * Manejo seguro de encriptación AES y funciones criptográficas
 * 
 * @package Cyberhole\Configuration
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/env.php';

class EncryptionConfig 
{
    private static $config = null;
    
    /**
     * Obtener configuración de encriptación
     */
    public static function getConfig(): array 
    {
        if (self::$config === null) {
            self::$config = EnvironmentConfig::getEncryptionConfig();
        }
        return self::$config;
    }
    
    /**
     * Encriptar datos usando AES
     */
    public static function encrypt(string $data): string 
    {
        $config = self::getConfig();
        
        try {
            // Verificar método disponible
            $method = $config['aes_method'];
            $availableMethods = openssl_get_cipher_methods();
            
            if (!in_array($method, $availableMethods)) {
                // Usar método alternativo si no está disponible
                $alternatives = ['aes-256-cbc', 'AES-256-CBC', 'aes256'];
                foreach ($alternatives as $alt) {
                    if (in_array($alt, $availableMethods)) {
                        $method = $alt;
                        break;
                    }
                }
            }
            
            // Generar IV aleatorio
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            
            // Encriptar datos
            $encrypted = openssl_encrypt($data, $method, $config['aes_key'], 0, $iv);
            
            if ($encrypted === false) {
                throw new Exception("Error en encriptación AES");
            }
            
            // Combinar IV + datos encriptados y codificar en base64
            return base64_encode($iv . $encrypted);
            
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw new Exception("Error al encriptar datos: " . $e->getMessage());
        }
    }
    
    /**
     * Desencriptar datos usando AES
     */
    public static function decrypt(string $encryptedData): string 
    {
        $config = self::getConfig();
        
        try {
            // Verificar método disponible
            $method = $config['aes_method'];
            $availableMethods = openssl_get_cipher_methods();
            
            if (!in_array($method, $availableMethods)) {
                // Usar método alternativo si no está disponible
                $alternatives = ['aes-256-cbc', 'AES-256-CBC', 'aes256'];
                foreach ($alternatives as $alt) {
                    if (in_array($alt, $availableMethods)) {
                        $method = $alt;
                        break;
                    }
                }
            }
            
            // Decodificar base64
            $data = base64_decode($encryptedData);
            
            if ($data === false) {
                throw new Exception("Datos encriptados inválidos");
            }
            
            // Extraer IV
            $ivLength = openssl_cipher_iv_length($method);
            $iv = substr($data, 0, $ivLength);
            
            // Extraer datos encriptados
            $encrypted = substr($data, $ivLength);
            
            // Desencriptar
            $decrypted = openssl_decrypt($encrypted, $method, $config['aes_key'], 0, $iv);
            
            if ($decrypted === false) {
                throw new Exception("Error en desencriptación AES");
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            throw new Exception("Error al desencriptar datos: " . $e->getMessage());
        }
    }
    
    /**
     * Generar hash seguro usando Cyberhole encryption key
     */
    public static function hash(string $data): string 
    {
        $config = self::getConfig();
        return hash_hmac('sha256', $data, $config['cyberhole_encryption_key']);
    }
    
    /**
     * Verificar hash
     */
    public static function verifyHash(string $data, string $hash): bool 
    {
        $expectedHash = self::hash($data);
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Generar token seguro aleatorio
     */
    public static function generateToken(int $length = 32): string 
    {
        try {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            
            if (!$strong) {
                throw new Exception("No se pudo generar token seguro");
            }
            
            return bin2hex($bytes);
            
        } catch (Exception $e) {
            error_log("Token generation error: " . $e->getMessage());
            throw new Exception("Error al generar token: " . $e->getMessage());
        }
    }
    
    /**
     * Encriptar password con pepper adicional
     */
    public static function hashPassword(string $password): string 
    {
        return EnvironmentConfig::hashPassword($password);
    }
    
    /**
     * Verificar password con pepper
     */
    public static function verifyPassword(string $password, string $hash): bool 
    {
        return EnvironmentConfig::verifyPassword($password, $hash);
    }
    
    /**
     * Generar clave AES segura
     */
    public static function generateAESKey(): string 
    {
        try {
            return bin2hex(openssl_random_pseudo_bytes(16)); // 32 caracteres hex = 16 bytes
        } catch (Exception $e) {
            error_log("AES key generation error: " . $e->getMessage());
            throw new Exception("Error al generar clave AES: " . $e->getMessage());
        }
    }
    
    /**
     * Validar integridad de configuración de encriptación
     */
    public static function validateConfig(): bool 
    {
        try {
            $config = self::getConfig();
            
            // Verificar longitud de clave AES
            if (strlen($config['aes_key']) !== 32) {
                throw new Exception("Clave AES debe tener 32 caracteres");
            }
            
            // Verificar método de encriptación válido
            $availableMethods = openssl_get_cipher_methods();
            $method = $config['aes_method'];
            
            // Si AES-256-CBC no está disponible, usar una alternativa
            if (!in_array($method, $availableMethods)) {
                // Probar métodos alternativos
                $alternatives = ['aes-256-cbc', 'AES-256-CBC', 'aes256'];
                $methodFound = false;
                
                foreach ($alternatives as $alt) {
                    if (in_array($alt, $availableMethods)) {
                        $method = $alt;
                        $methodFound = true;
                        break;
                    }
                }
                
                if (!$methodFound) {
                    throw new Exception("No hay métodos de encriptación AES disponibles");
                }
            }
            
            // Probar encriptación/desencriptación
            $testData = "test_encryption_" . time();
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            $encrypted = openssl_encrypt($testData, $method, $config['aes_key'], 0, $iv);
            
            if ($encrypted === false) {
                throw new Exception("Error en prueba de encriptación");
            }
            
            $decrypted = openssl_decrypt($encrypted, $method, $config['aes_key'], 0, $iv);
            
            if ($testData !== $decrypted) {
                throw new Exception("Prueba de encriptación/desencriptación falló");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Encryption config validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encriptar datos sensibles para almacenamiento en base de datos
     */
    public static function encryptForDatabase(string $data): string 
    {
        // Usar hash adicional para mayor seguridad en BD
        $hashedData = self::hash($data) . '|' . $data;
        return self::encrypt($hashedData);
    }
    
    /**
     * Desencriptar datos de base de datos con verificación de integridad
     */
    public static function decryptFromDatabase(string $encryptedData): string 
    {
        try {
            $decrypted = self::decrypt($encryptedData);
            
            // Verificar integridad
            if (strpos($decrypted, '|') === false) {
                throw new Exception("Datos corruptos o formato inválido");
            }
            
            list($hash, $originalData) = explode('|', $decrypted, 2);
            
            if (!self::verifyHash($originalData, $hash)) {
                throw new Exception("Verificación de integridad falló");
            }
            
            return $originalData;
            
        } catch (Exception $e) {
            error_log("Database decryption error: " . $e->getMessage());
            throw new Exception("Error al desencriptar datos de BD: " . $e->getMessage());
        }
    }
    
    /**
     * Generar JWT para autenticación
     */
    public static function generateJWT(array $payload, int $expiration = null): string 
    {
        $config = EnvironmentConfig::getSecurityConfig();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        if ($expiration === null) {
            $expiration = time() + $config['session_lifetime'];
        }
        
        $payload['exp'] = $expiration;
        $payload['iat'] = time();
        $payloadJson = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $config['jwt_secret'], true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Verificar y decodificar JWT
     */
    public static function verifyJWT(string $jwt): array 
    {
        $config = EnvironmentConfig::getSecurityConfig();
        
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception("JWT formato inválido");
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verificar firma
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $config['jwt_secret'], true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            throw new Exception("JWT firma inválida");
        }
        
        // Decodificar payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception("JWT expirado");
        }
        
        return $payload;
    }
}

// Funciones helper para compatibilidad (solo si no existen)
if (!function_exists('encrypt_data')) {
    function encrypt_data(string $data): string 
    {
        return EncryptionConfig::encrypt($data);
    }
}

if (!function_exists('decrypt_data')) {
    function decrypt_data(string $encryptedData): string 
    {
        return EncryptionConfig::decrypt($encryptedData);
    }
}

if (!function_exists('generate_token')) {
    function generate_token(int $length = 32): string 
    {
        return EncryptionConfig::generateToken($length);
    }
}

if (!function_exists('hash_data')) {
    function hash_data(string $data): string 
    {
        return EncryptionConfig::hash($data);
    }
}

if (!function_exists('verify_hash')) {
    function verify_hash(string $data, string $hash): bool 
    {
        return EncryptionConfig::verifyHash($data, $hash);
    }
}
