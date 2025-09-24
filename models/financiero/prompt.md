Necesito que tomes como ejemplo mi archivo de models\dispositivos\PersonaUnidad-Model.php ya que tu trabajo sera desarrollarme mis archivos de la carpeta de models\financiero los cuales son los siguientes ;

models\financiero\CobrosAutorizados-Model.php
models\financiero\Compras-Model.php
models\financiero\Cuotas-Model.php
models\financiero\Inventarios-Model.php
models\financiero\Nomina-Model.php
 
tienes que desarrollarles el crud, tienes que desarrollarle todos los setters y getters, pero siempre haciendo referencias a los condominios por restricciones. Estrictamente para el desarrollo de mis modelos , vas a crear primero las entidades publicas , luego su contructor, cada entidad representa una columna de mi base de datos , tienes que hacer uso de mi models\Base-Model.php y tiennes que comprimir y encriptar algunos datos, necesito que los datos como inventarios , compras y cobros autorizados me desarrolles todos sus getters y setters de acuerdo a sus fechas de creacion, id condominos, empleados , compras , etc , aqui es muy importante que hagamos ese tipo de segmentaciones pero siempre y cuando sea restringido por condominios principalmente como restriccion y que muestres de 10 en 10 datos cuando sea para consultas considerablemente altas 




CREATE TABLE `nomina` (
  `id_nomina` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `id_condominio` int(11) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `isr` decimal(10,2) DEFAULT 0.00,
  `imss` decimal(10,2) DEFAULT 0.00,
  `infonavit` decimal(10,2) DEFAULT 0.00,
  `aportes_pensiones` decimal(10,2) DEFAULT 0.00,
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `cobros_autorizados` (
  `id_cobro_autorizado` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_casa` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  encriptado
  `es_recurrente` tinyint(1) NOT NULL DEFAULT 0,
  `frecuencia` enum('semanal','quincenal','mensual','bimestral','trimestral','anual') DEFAULT 'mensual',
  `fecha` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `token_pago` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `id_cuota` int(11) DEFAULT NULL,
  `rfc` varchar(256) NOT NULL,
  encriptado
  `metodo_pago` varchar(3) NOT NULL,
  `iva` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `inventarios` (
  `id_inventario` int(11) NOT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `id_condominio` int(11) NOT NULL,
  `rfc` varchar(256) NOT NULL,
  encriptado
  `nombre` varchar(256) NOT NULL,
  encriptado
  `descripcion` text DEFAULT NULL,
  encriptado
  `cantidad_actual` int(11) NOT NULL DEFAULT 0,
  `unidad_medida` varchar(20) DEFAULT 'pieza',
  `tiempo_vida_dias` int(11) DEFAULT NULL,
  `fecha_alta` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `compras` (
  `id_compra` int(11) NOT NULL,
  `id_condominio` int(11) NOT NULL,
  `id_inventario` int(11) DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `tipo_compra` enum('inventario','servicio') NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `fecha_compra` datetime DEFAULT current_timestamp(),
  `descripcion` text DEFAULT NULL,
  encriptado
  `monto_total` decimal(10,2) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT 0.00,
  `id_emisor` int(11) NOT NULL,
  `tipo_emisor` enum('empleado','admin','empleado') NOT NULL,
  `rfc_emisor` varchar(256) NOT NULL,
  encriptado
  `razon_social_emisor` varchar(256) NOT NULL,
  encriptado
  `regimen_fiscal_emisor` varchar(3) NOT NULL,
  `cp_fiscal_emisor` varchar(5) NOT NULL,
  `rfc_receptor` varchar(256) NOT NULL,
  `razon_social_receptor` varchar(256) NOT NULL,
  `regimen_fiscal_receptor` varchar(3) NOT NULL,
  `cp_fiscal_receptor` varchar(5) NOT NULL,
  `uso_cfdi` varchar(3) DEFAULT 'G03',
  `forma_pago` varchar(2) DEFAULT '03',
  `metodo_pago` varchar(3) DEFAULT 'PUE',
  `moneda` varchar(3) DEFAULT 'MXN',
  `uuid_factura` text DEFAULT NULL,
  comprimir y encriptar
  `archivo_pdf` text DEFAULT NULL,
  comprimir y encriptar
  `estatus` varchar(20) DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `cuotas` (
  `id_cuota` int(11) NOT NULL,
  `id_condominio` int(11) NOT NULL,
  `id_calle` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `iva` decimal(5,2) DEFAULT 0.00,
  `fecha_generacion` datetime DEFAULT current_timestamp(),
  `fecha_vencimiento` date NOT NULL,
  `uso_cfdi` varchar(3) DEFAULT 'G03',
  `moneda` varchar(3) DEFAULT 'MXN',
  `uuid_factura` text DEFAULT NULL,
    comprimir y encriptar
  `archivo_pdf` text DEFAULT NULL,
    comprimir y encriptar
  `estatus` varchar(20) DEFAULT 'pendiente',
  `recurrencia` enum('unica','mensual','trimestral','anual') NOT NULL DEFAULT 'unica'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;


<?php
/**
 * 🏗️ BASE MODEL - Modelo Base Abstracto
 * Integración completa de config/database.php, config/encryption.php, config/sources.php
 * Métodos abstractos para todos los modelos del sistema Cyberhole Condominios
 * 
 * @package Cyberhole\Models
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

// Importar todos los módulos de configuración
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../config/sources.php';

abstract class BaseModel 
{
    // Propiedades de configuración
    protected static $connection = null;
    protected static $encryption = null;
    protected static $sources = null;
    
    // Propiedades de instancia
    protected $tableName = '';
    protected $primaryKey = 'id';
    protected $fillableFields = [];
    protected $encryptedFields = [];
    protected $hiddenFields = ['password', 'password_hash'];
    
    // Control de transacciones
    protected static $transactionActive = false;
    
    /**
     * Constructor - Inicializar módulos de configuración
     */
    public function __construct() 
    {
        $this->initializeConfig();
    }
    
    /**
     * Inicializar configuraciones
     */
    private function initializeConfig(): void 
    {
        try {
            // Inicializar conexión de base de datos
            if (self::$connection === null) {
                self::$connection = DatabaseConfig::getConnection();
            }
            
            // Inicializar sistema de archivos
            if (self::$sources === null) {
                self::$sources = new SourcesManager();
            }
            
        } catch (Exception $e) {
            error_log("Error inicializando BaseModel: " . $e->getMessage());
            throw new Exception("Error al inicializar modelo base: " . $e->getMessage());
        }
    }
    

    
    // ===========================================
    // MÉTODOS DE BASE DE DATOS SEGUROS
    // ===========================================
    
    /**
     * Ejecutar query con parámetros seguros
     */
    protected function executeQuery(string $sql, array $params = []): ?PDOStatement 
    {
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar registro por ID
     */
    protected function findById(int $id): ?array 
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeQuery($sql, [$id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Buscar un registro por condiciones
     */
    protected function findOne(array $conditions): ?array 
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE ";
        $params = [];
        $whereClauses = [];
        
        foreach ($conditions as $field => $value) {
            $whereClauses[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql .= implode(' AND ', $whereClauses) . " LIMIT 1";
        $stmt = $this->executeQuery($sql, $params);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Buscar múltiples registros
     */
    protected function findMany(array $conditions = [], int $limit = 0, int $offset = 0): array 
    {
        $sql = "SELECT * FROM {$this->tableName}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insertar nuevo registro
     */
    protected function insert(array $data): int 
    {
        // Filtrar solo campos permitidos
        $data = $this->filterFillableFields($data);
        
        // Encriptar campos sensibles
        $data = $this->encryptSensitiveFields($data);
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->executeQuery($sql, array_values($data));
        
        return (int) self::$connection->lastInsertId();
    }
    
    /**
     * Actualizar registro
     */
    protected function update(int $id, array $data): bool 
    {
        // Filtrar solo campos permitidos
        $data = $this->filterFillableFields($data);
        
        // Encriptar campos sensibles
        $data = $this->encryptSensitiveFields($data);
        
        $setClauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . 
               " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Eliminar registro
     */
    protected function delete(int $id): bool 
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = ?";
        $stmt = $this->executeQuery($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    // ===========================================
    // MÉTODOS DE ENCRIPTACIÓN
    // ===========================================
    
    /**
     * Encriptar campo con AES-256
     */
    protected function encryptField(string $data): string 
    {
        return EncryptionConfig::encryptAES256($data);
    }
    
    /**
     * Desencriptar campo
     */
    protected function decryptField(string $encryptedData): string 
    {
        return EncryptionConfig::decryptAES256($encryptedData);
    }
    
    /**
     * Hash de contraseña con ARGON2ID
     */
    protected function hashPasswordSecure(string $password): string 
    {
        return EncryptionConfig::hashPassword($password);
    }
    
    /**
     * Verificar contraseña
     */
    protected function verifyPassword(string $password, string $hash): bool 
    {
        return EncryptionConfig::checkPassword($password, $hash);
    }
    
    /**
     * Hash SHA-256 para datos generales
     */
    protected function hashSHA(string $data): string 
    {
        return EncryptionConfig::hashSHA($data);
    }
    
    /**
     * Encriptar array completo
     */
    protected function encryptArray(array $data): string 
    {
        return EncryptionConfig::encryptArray($data);
    }
    
    /**
     * Desencriptar array
     */
    protected function decryptArray(string $encryptedData): array 
    {
        return EncryptionConfig::decryptArray($encryptedData);
    }
    
    /**
     * Encriptar campos sensibles automáticamente
     */
    private function encryptSensitiveFields(array $data): array 
    {
        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encryptField($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Desencriptar campos sensibles en resultados
     */
    protected function decryptSensitiveFields(array $data): array 
    {
        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && $data[$field] !== null) {
                try {
                    $data[$field] = $this->decryptField($data[$field]);
                } catch (Exception $e) {
                    // Si no se puede desencriptar, mantener valor original o null
                    error_log("Error desencriptando campo {$field}: " . $e->getMessage());
                    $data[$field] = null; // Establecer como null si falla desencriptación
                }
            }
        }
        
        return $data;
    }
    
    // ===========================================
    // MÉTODOS DE MANEJO DE ARCHIVOS
    // ===========================================
    
    /**
     * Comprimir y convertir archivo a base64
     */
    protected function compressFile(string $filePath): array 
    {
        return self::$sources->compressFile($filePath);
    }
    
    /**
     * Descomprimir archivo desde base64
     */
    protected function decompressFile(string $base64Data, string $extension, string $outputPath = null): array 
    {
        return self::$sources->decompressFile($base64Data, $extension, $outputPath);
    }
    
    /**
     * Procesar upload de archivo
     */
    protected function processFileUpload(array $fileData, string $uploadDir = 'uploads/'): array 
    {
        try {
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new Exception('Archivo no válido para upload');
            }
            
            // Generar nombre único
            $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $fileName = 'file_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Mover archivo
            if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
                throw new Exception('Error moviendo archivo uploadado');
            }
            
            // Comprimir archivo
            $compressionResult = $this->compressFile($filePath);
            
            if (!$compressionResult['success']) {
                unlink($filePath); // Limpiar archivo si falla compresión
                throw new Exception('Error comprimiendo archivo: ' . $compressionResult['error']);
            }
            
            // Limpiar archivo temporal (opcional, mantener base64)
            // unlink($filePath);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'original_name' => $fileData['name'],
                'base64_data' => $compressionResult['data'],
                'compression_info' => $compressionResult
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ===========================================
    // MÉTODOS DE VALIDACIÓN Y FILTRADO
    // ===========================================
    
    /**
     * Filtrar solo campos permitidos
     */
    private function filterFillableFields(array $data): array 
    {
        if (empty($this->fillableFields)) {
            return $data; // Si no hay restricciones, permitir todos
        }
        
        return array_intersect_key($data, array_flip($this->fillableFields));
    }
    
    /**
     * Ocultar campos sensibles en respuestas
     */
    protected function hideSecretFields(array $data): array 
    {
        foreach ($this->hiddenFields as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    // ===========================================
    // MÉTODOS DE TRANSACCIONES
    // ===========================================
    
    /**
     * Iniciar transacción
     */
    protected function beginTransaction(): bool 
    {
        if (!self::$transactionActive) {
            self::$transactionActive = self::$connection->beginTransaction();
            return self::$transactionActive;
        }
        
        return true; // Ya hay transacción activa
    }
    
    /**
     * Confirmar transacción
     */
    protected function commit(): bool 
    {
        if (self::$transactionActive) {
            $result = self::$connection->commit();
            self::$transactionActive = false;
            return $result;
        }
        
        return false;
    }
    
    /**
     * Rollback transacción
     */
    protected function rollback(): bool 
    {
        if (self::$transactionActive) {
            $result = self::$connection->rollback();
            self::$transactionActive = false;
            return $result;
        }
        
        return false;
    }
    
    // ===========================================
    // MÉTODOS DE UTILIDAD
    // ===========================================
    

    
    /**
     * Obtener timestamp actual
     */
    protected function getCurrentTimestamp(): string 
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Contar registros en tabla
     */
    protected function count(array $conditions = []): int 
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->fetch();
        
        return (int) $result['total'];
    }
    
    /**
     * Verificar si registro existe
     */
    protected function exists(array $conditions): bool 
    {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Obtener último registro insertado
     */
    protected function getLastInsertId(): int 
    {
        return (int) self::$connection->lastInsertId();
    }
    
    /**
     * Log de actividad
     */
    protected function logActivity(string $action, array $details = []): void 
    {
        $logData = [
            'timestamp' => $this->getCurrentTimestamp(),
            'model' => get_class($this),
            'action' => $action,
            'details' => $details
        ];
        
        error_log("BaseModel Activity: " . json_encode($logData));
    }
    
    // ===========================================
    // MÉTODOS DE CONFIGURACIÓN
    // ===========================================
    
    /**
     * Obtener configuración de base de datos
     */
    protected function getDatabaseConfig(): array 
    {
        return DatabaseConfig::getConfig();
    }
    
    /**
     * Obtener información de encriptación
     */
    protected function getEncryptionInfo(): array 
    {
        return EncryptionConfig::getConfigInfo();
    }
    
    /**
     * Probar conectividad
     */
    protected function testConnectivity(): array 
    {
        return [
            'database' => DatabaseConfig::testConnection(),
            'encryption' => EncryptionConfig::validateConfig(),
            'sources' => class_exists('SourcesManager')
        ];
    }
    
    // ===========================================
    // DESTRUCTOR Y LIMPIEZA
    // ===========================================
    
    /**
     * Destructor - limpiar recursos
     */
    public function __destruct() 
    {
        // Rollback si hay transacción pendiente
        if (self::$transactionActive) {
            $this->rollback();
        }
        
        // Limpiar datos sensibles de encriptación
        EncryptionConfig::clearSensitiveData();
    }
}

// ===========================================
// FUNCIONES HELPER GLOBALES
// ===========================================

if (!function_exists('base_model_hash_password')) {
    function base_model_hash_password(string $password): string 
    {
        return EncryptionConfig::hashPassword($password);
    }
}

if (!function_exists('base_model_check_password')) {
    function base_model_check_password(string $password, string $hash): bool 
    {
        return EncryptionConfig::checkPassword($password, $hash);
    }
}

if (!function_exists('base_model_encrypt')) {
    function base_model_encrypt(string $data): string 
    {
        return EncryptionConfig::encryptAES256($data);
    }
}

if (!function_exists('base_model_decrypt')) {
    function base_model_decrypt(string $encryptedData): string 
    {
        return EncryptionConfig::decryptAES256($encryptedData);
    }
}
?>
