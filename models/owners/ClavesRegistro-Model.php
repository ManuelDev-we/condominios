<?php
/**
 * 🔑 CLAVES REGISTRO MODEL - Gestión de Códigos de Registro
 * Modelo completo para administración de códigos de registro temporales
 * Generación automática, validación de tiempo y gestión de canjes
 * 
 * @package Cyberhole\Models\Owners
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class ClavesRegistro extends BaseModel 
{
    // Configuración del modelo
    protected $tableName = 'claves_registro';
    protected $primaryKey = 'codigo';
    protected $encryptedFields = []; // Sin encriptación especificada
    
    // Campos permitidos para inserción/actualización
    protected $fillableFields = [
        'codigo', 'id_condominio', 'id_calle', 'id_casa', 
        'fecha_creacion', 'fecha_expiracion', 'usado', 'fecha_canje'
    ];
    
    // Propiedades del modelo
    public ?string $codigo = null;
    public ?int $id_condominio = null;
    public ?int $id_calle = null;
    public ?int $id_casa = null;
    public ?string $fecha_creacion = null;
    public ?string $fecha_expiracion = null;
    public ?int $usado = 0;
    public ?string $fecha_canje = null;
    
    /**
     * Constructor con parámetros opcionales
     */
    public function __construct(
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null
    ) {
        parent::__construct();
        
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->usado = 0;
    }
    
    // ========================================
    // MÉTODOS CRUD BÁSICOS
    // ========================================
    
    /**
     * Crear nueva clave de registro
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas obligatorias
            if (empty($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El ID del condominio es obligatorio'];
            }
            
            if (empty($data['id_calle'])) {
                return ['success' => false, 'error' => 'El ID de la calle es obligatorio'];
            }
            
            if (empty($data['id_casa'])) {
                return ['success' => false, 'error' => 'El ID de la casa es obligatorio'];
            }
            
            // Verificar que condominio, calle y casa existen
            if (!$this->condominioExists($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El condominio especificado no existe'];
            }
            
            if (!$this->calleExists($data['id_calle'], $data['id_condominio'])) {
                return ['success' => false, 'error' => 'La calle especificada no existe en este condominio'];
            }
            
            if (!$this->casaExists($data['id_casa'], $data['id_calle'], $data['id_condominio'])) {
                return ['success' => false, 'error' => 'La casa especificada no existe en esta calle'];
            }
            
            // Generar código único de 36 caracteres
            $codigo = $this->generarCodigoUnico();
            
            // Preparar datos con fechas
            $now = $this->getCurrentTimestamp();
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime($now . ' + 7 days'));
            
            $insertData = [
                'codigo' => $codigo,
                'id_condominio' => $data['id_condominio'],
                'id_calle' => $data['id_calle'],
                'id_casa' => $data['id_casa'],
                'fecha_creacion' => $now,
                'fecha_expiracion' => $fechaExpiracion,
                'usado' => 0,
                'fecha_canje' => null
            ];
            
            // Insertar registro
            $this->executeQuery(
                "INSERT INTO {$this->tableName} (codigo, id_condominio, id_calle, id_casa, fecha_creacion, fecha_expiracion, usado, fecha_canje) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                array_values($insertData)
            );
            
            $this->logActivity('create', ['codigo' => $codigo]);
            
            return [
                'success' => true,
                'codigo' => $codigo,
                'data' => $insertData,
                'message' => 'Clave de registro creada exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Leer clave de registro por código
     */
    public function readClave(string $codigo): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE codigo = ?";
            $stmt = $this->executeQuery($sql, [$codigo]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Clave de registro no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar clave de registro
     */
    public function updateClave(string $codigo, array $data): array 
    {
        try {
            // Verificar que existe
            $existingRecord = $this->readClave($codigo);
            if (!$existingRecord['success']) {
                return $existingRecord;
            }
            
            // Filtrar campos permitidos
            $allowedFields = ['usado', 'fecha_canje'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return [
                    'success' => false,
                    'error' => 'No hay campos válidos para actualizar'
                ];
            }
            
            $setClauses = [];
            $params = [];
            
            foreach ($updateData as $field => $value) {
                $setClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $params[] = $codigo;
            
            $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE codigo = ?";
            $stmt = $this->executeQuery($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('update', ['codigo' => $codigo]);
                return [
                    'success' => true,
                    'message' => 'Clave de registro actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se realizaron cambios'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar clave de registro
     */
    public function deleteClave(string $codigo): array 
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE codigo = ?";
            $stmt = $this->executeQuery($sql, [$codigo]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('delete', ['codigo' => $codigo]);
                return [
                    'success' => true,
                    'message' => 'Clave de registro eliminada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Clave de registro no encontrada'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================
    // MÉTODOS ESPECÍFICOS DE CLAVES
    // ========================================
    
    /**
     * Generar código único de 36 caracteres
     */
    public function generarCodigoUnico(): string 
    {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $longitud = 36;
        
        do {
            $codigo = '';
            for ($i = 0; $i < $longitud; $i++) {
                $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
            }
        } while ($this->codigoExists($codigo));
        
        return $codigo;
    }
    
    /**
     * Verificar si código existe
     */
    public function codigoExists(string $codigo): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE codigo = ?";
        $stmt = $this->executeQuery($sql, [$codigo]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Canjear código
     */
    public function canjearCodigo(string $codigo): array 
    {
        try {
            // Verificar que el código existe
            $claveData = $this->readClave($codigo);
            if (!$claveData['success']) {
                return $claveData;
            }
            
            $clave = $claveData['data'];
            
            // Verificar si ya fue usado
            if ($clave['usado'] == 1) {
                return [
                    'success' => false,
                    'error' => 'Esta clave ya ha sido canjeada'
                ];
            }
            
            // Verificar si expiró
            $now = $this->getCurrentTimestamp();
            if ($now > $clave['fecha_expiracion']) {
                // Eliminar código expirado
                $this->deleteClave($codigo);
                return [
                    'success' => false,
                    'error' => 'Esta clave ha expirado y ha sido eliminada'
                ];
            }
            
            // Marcar como usado
            $updateResult = $this->updateClave($codigo, [
                'usado' => 1,
                'fecha_canje' => $now
            ]);
            
            if ($updateResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Código canjeado exitosamente',
                    'fecha_canje' => $now
                ];
            } else {
                return $updateResult;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpiar códigos expirados
     */
    public function limpiarExpirados(): array 
    {
        try {
            $now = $this->getCurrentTimestamp();
            
            // Eliminar códigos expirados (tanto usados como no usados después de 7 días)
            $sql = "DELETE FROM {$this->tableName} WHERE fecha_expiracion < ?";
            $stmt = $this->executeQuery($sql, [$now]);
            
            $eliminados = $stmt->rowCount();
            
            $this->logActivity('cleanup', ['eliminados' => $eliminados]);
            
            return [
                'success' => true,
                'eliminados' => $eliminados,
                'message' => "Se eliminaron {$eliminados} códigos expirados"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================
    // GETTERS Y SETTERS
    // ========================================
    
    /**
     * Obtener códigos por condominio
     */
    public function getByCodomininio(int $id_condominio): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_condominio = ? ORDER BY fecha_creacion DESC";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener códigos por calle
     */
    public function getByCalle(int $id_calle, int $id_condominio): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_calle = ? AND id_condominio = ? ORDER BY fecha_creacion DESC";
            $stmt = $this->executeQuery($sql, [$id_calle, $id_condominio]);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener códigos por casa
     */
    public function getByCasa(int $id_casa, int $id_calle, int $id_condominio): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_casa = ? AND id_calle = ? AND id_condominio = ? ORDER BY fecha_creacion DESC";
            $stmt = $this->executeQuery($sql, [$id_casa, $id_calle, $id_condominio]);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================
    // MÉTODOS DE VALIDACIÓN
    // ========================================
    
    /**
     * Verificar si condominio existe
     */
    private function condominioExists(int $id_condominio): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM condominios WHERE id_condominio = ?";
        $stmt = $this->executeQuery($sql, [$id_condominio]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si calle existe en condominio
     */
    private function calleExists(int $id_calle, int $id_condominio): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM calles WHERE id_calle = ? AND id_condominio = ?";
        $stmt = $this->executeQuery($sql, [$id_calle, $id_condominio]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si casa existe en calle y condominio
     */
    private function casaExists(int $id_casa, int $id_calle, int $id_condominio): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM casas WHERE id_casa = ? AND id_calle = ? AND id_condominio = ?";
        $stmt = $this->executeQuery($sql, [$id_casa, $id_calle, $id_condominio]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
}
?>

