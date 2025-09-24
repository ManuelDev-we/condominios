<?php
/**
 * 🛡️ CSRF Shield - Middleware de Protección contra Cross-Site Request Forgery
 * Genera y valida tokens CSRF para prevenir ataques de falsificación de peticiones
 * 
 * @package Cyberhole\Middlewares\Security
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../../config/encryption.php';
require_once __DIR__ . '/../../helpers/filters.php';

class CsrfShield 
{
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    private static $maxTokens = 10; // Máximo de tokens activos por sesión
    private static $tokenExpiry = 3600; // 1 hora en segundos
    
    /**
     * Generar token CSRF único
     */
    public static function generateToken(): string 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        // Inicializar array de tokens si no existe
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        // Limpiar tokens expirados
        self::cleanExpiredTokens();
        
        // Verificar límite de tokens
        if (count($_SESSION[self::$sessionKey]) >= self::$maxTokens) {
            // Remover el token más antiguo
            array_shift($_SESSION[self::$sessionKey]);
        }
        
        // Almacenar nuevo token con timestamp
        $_SESSION[self::$sessionKey][$token] = $timestamp;
        
        return $token;
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateToken(string $token): bool 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token)) {
            return false;
        }
        
        // Verificar si existe el array de tokens
        if (!isset($_SESSION[self::$sessionKey]) || !is_array($_SESSION[self::$sessionKey])) {
            return false;
        }
        
        // Limpiar tokens expirados
        self::cleanExpiredTokens();
        
        // Verificar si el token existe y es válido
        if (isset($_SESSION[self::$sessionKey][$token])) {
            $tokenTime = $_SESSION[self::$sessionKey][$token];
            
            // Verificar si no ha expirado
            if ((time() - $tokenTime) <= self::$tokenExpiry) {
                // Token válido - removerlo para uso único
                unset($_SESSION[self::$sessionKey][$token]);
                return true;
            } else {
                // Token expirado - removerlo
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
        
        return false;
    }
    
    /**
     * Limpiar tokens expirados
     */
    private static function cleanExpiredTokens(): void 
    {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $token => $timestamp) {
            if (($currentTime - $timestamp) > self::$tokenExpiry) {
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
    }
    
    /**
     * Middleware principal - Proteger petición
     */
    public static function protect(): array 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Solo verificar en métodos que modifican datos
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return self::validateRequest();
        }
        
        return [
            'success' => true,
            'message' => 'CSRF validation not required for ' . $method,
            'token' => self::generateToken() // Generar token para futuros formularios
        ];
    }
    
    /**
     * Validar petición entrante
     */
    private static function validateRequest(): array 
    {
        // Obtener token desde diferentes fuentes
        $token = self::getTokenFromRequest();
        
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'CSRF token missing',
                'error_code' => 'CSRF_TOKEN_MISSING',
                'http_code' => 403
            ];
        }
        
        // Filtrar token por seguridad
        $filters = new SecurityFilters();
        $tokenResult = $filters->filterInput($token, true);
        
        if (!$tokenResult['is_safe']) {
            return [
                'success' => false,
                'message' => 'CSRF token contains malicious content',
                'error_code' => 'CSRF_TOKEN_MALICIOUS',
                'threats' => $tokenResult['threats_detected'],
                'http_code' => 403
            ];
        }
        
        $cleanToken = $tokenResult['filtered'];
        
        // Validar token
        if (self::validateToken($cleanToken)) {
            return [
                'success' => true,
                'message' => 'CSRF validation successful',
                'token' => self::generateToken() // Nuevo token para próxima petición
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid or expired CSRF token',
            'error_code' => 'CSRF_TOKEN_INVALID',
            'http_code' => 403
        ];
    }
    
    /**
     * Obtener token desde diferentes fuentes de la petición
     */
    private static function getTokenFromRequest(): ?string 
    {
        // 1. Header personalizado
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        
        // 2. POST data
        if (isset($_POST[self::$tokenName])) {
            return $_POST[self::$tokenName];
        }
        
        // 3. GET parameter (menos recomendado)
        if (isset($_GET[self::$tokenName])) {
            return $_GET[self::$tokenName];
        }
        
        // 4. JSON body
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data[self::$tokenName])) {
                return $data[self::$tokenName];
            }
        }
        
        return null;
    }
    
    /**
     * Generar HTML para campo hidden con token CSRF
     */
    public static function getHiddenField(): string 
    {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Obtener token actual para JavaScript/AJAX
     */
    public static function getTokenForAjax(): string 
    {
        return self::generateToken();
    }
    
    /**
     * Configurar nombre del token (opcional)
     */
    public static function setTokenName(string $name): void 
    {
        self::$tokenName = $name;
    }
    
    /**
     * Configurar tiempo de expiración (opcional)
     */
    public static function setTokenExpiry(int $seconds): void 
    {
        self::$tokenExpiry = $seconds;
    }
    
    /**
     * Obtener estadísticas de tokens activos
     */
    public static function getTokenStats(): array 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$sessionKey])) {
            return [
                'active_tokens' => 0,
                'max_tokens' => self::$maxTokens,
                'expiry_time' => self::$tokenExpiry
            ];
        }
        
        self::cleanExpiredTokens();
        
        return [
            'active_tokens' => count($_SESSION[self::$sessionKey]),
            'max_tokens' => self::$maxTokens,
            'expiry_time' => self::$tokenExpiry
        ];
    }
}