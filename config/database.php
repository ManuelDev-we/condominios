<?php
/**
 * 🗄️ Configuración de Base de Datos
 * Manejo de conexiones y configuración de base de datos
 * 
 * @package Cyberhole\Configuration
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/env.php';

class DatabaseConfig 
{
    private static $connection = null;
    private static $config = null;
    
    /**
     * Obtener configuración de base de datos
     */
    public static function getConfig(): array 
    {
        if (self::$config === null) {
            self::$config = EnvironmentConfig::getDatabaseConfig();
        }
        return self::$config;
    }
    
    /**
     * Crear conexión PDO
     */
    public static function getConnection(): PDO 
    {
        if (self::$connection === null) {
            $config = self::getConfig();
            
            try {
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['collation']}"
                ];
                
                self::$connection = new PDO($dsn, $config['username'], $config['password'], $options);
                
                // Log successful connection if debug mode is enabled
                if (EnvironmentConfig::isDebugMode()) {
                    error_log("Database connection established successfully");
                }
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Probar conexión a la base de datos
     */
    public static function testConnection(): bool 
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener información del servidor de base de datos
     */
    public static function getServerInfo(): array 
    {
        try {
            $pdo = self::getConnection();
            
            return [
                'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
                'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ejecutar query con logging opcional
     */
    public static function execute(string $query, array $params = []): PDOStatement 
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($query);
            
            if (EnvironmentConfig::isDebugMode()) {
                error_log("Executing query: " . $query . " with params: " . json_encode($params));
            }
            
            $stmt->execute($params);
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " Query: " . $query);
            throw new Exception("Error en consulta de base de datos: " . $e->getMessage());
        }
    }
    
    /**
     * Cerrar conexión
     */
    public static function closeConnection(): void 
    {
        self::$connection = null;
    }
    
    /**
     * Iniciar transacción
     */
    public static function beginTransaction(): bool 
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public static function commit(): bool 
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transacción
     */
    public static function rollback(): bool 
    {
        return self::getConnection()->rollback();
    }
    
    /**
     * Verificar si hay una transacción activa
     */
    public static function inTransaction(): bool 
    {
        return self::getConnection()->inTransaction();
    }
    
    /**
     * Obtener el último ID insertado
     */
    public static function lastInsertId(): string 
    {
        return self::getConnection()->lastInsertId();
    }
}

// Funciones helper para compatibilidad (solo si no existen)
if (!function_exists('db_connection')) {
    function db_connection(): PDO 
    {
        return DatabaseConfig::getConnection();
    }
}

if (!function_exists('db_execute')) {
    function db_execute(string $query, array $params = []): PDOStatement 
    {
        return DatabaseConfig::execute($query, $params);
    }
}

if (!function_exists('db_test')) {
    function db_test(): bool 
    {
        return DatabaseConfig::testConnection();
    }
}

if (!function_exists('db_config')) {
    function db_config(): array 
    {
        return DatabaseConfig::getConfig();
    }
}
