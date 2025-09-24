<?php
/**
 * 🏪 VENDORS CONDOMINIOS MODEL - Relación Vendedor-Condominio
 * Modelo completo para gestión de vendedores asignados a condominios
 * Control de fechas de asignación y administración de vendedores
 * 
 * @package Cyberhole\Models\Owners
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class VendorsCondominios extends BaseModel 
{
    // Configuración del modelo
    protected $tableName = 'vendedores_condominios';
    protected $primaryKey = 'id_relacion';
    protected $encryptedFields = []; // Sin encriptación especificada
    
    // Campos permitidos para inserción/actualización
    protected $fillableFields = ['id_vendedor', 'id_condominio', 'fecha_asignacion'];
    
    // Propiedades del modelo
    public ?int $id_relacion = null;
    public ?int $id_vendedor = null;
    public ?int $id_condominio = null;
    public ?string $fecha_asignacion = null;
    
    /**
     * Constructor con parámetros opcionales
     */
    public function __construct(
        ?int $id_vendedor = null,
        ?int $id_condominio = null,
        ?string $fecha_asignacion = null
    ) {
        parent::__construct();
        
        $this->id_vendedor = $id_vendedor;
        $this->id_condominio = $id_condominio;
        $this->fecha_asignacion = $fecha_asignacion ?? $this->getCurrentTimestamp();
    }
    
    // ========================================
    // MÉTODOS CRUD BÁSICOS
    // ========================================
    
    /**
     * Crear nueva relación vendedor-condominio
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas obligatorias
            if (empty($data['id_vendedor'])) {
                return ['success' => false, 'error' => 'El ID del vendedor es obligatorio'];
            }
            
            if (empty($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El ID del condominio es obligatorio'];
            }
            
            // Verificar que vendedor existe
            if (!$this->vendedorExists($data['id_vendedor'])) {
                return ['success' => false, 'error' => 'El vendedor especificado no existe'];
            }
            
            // Verificar que condominio existe
            if (!$this->condominioExists($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El condominio especificado no existe'];
            }
            
            // Verificar duplicados (mismo vendedor y condominio)
            if ($this->relacionExists($data['id_vendedor'], $data['id_condominio'])) {
                return [
                    'success' => false, 
                    'error' => 'Ya existe una relación entre este vendedor y condominio'
                ];
            }
            
            // Preparar fecha de asignación
            $fechaAsignacion = $data['fecha_asignacion'] ?? $this->getCurrentTimestamp();
            
            // Insertar registro
            $this->executeQuery(
                "INSERT INTO {$this->tableName} (id_vendedor, id_condominio, fecha_asignacion) VALUES (?, ?, ?)",
                [$data['id_vendedor'], $data['id_condominio'], $fechaAsignacion]
            );
            
            $id_relacion = self::$connection->lastInsertId();
            
            $this->logActivity('create', [
                'id_relacion' => $id_relacion,
                'id_vendedor' => $data['id_vendedor'],
                'id_condominio' => $data['id_condominio']
            ]);
            
            return [
                'success' => true,
                'id_relacion' => $id_relacion,
                'data' => array_merge($data, ['fecha_asignacion' => $fechaAsignacion]),
                'message' => 'Relación vendedor-condominio creada exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Leer relación por ID
     */
    public function readRelacion(int $id_relacion): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_relacion = ?";
            $stmt = $this->executeQuery($sql, [$id_relacion]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Relación vendedor-condominio no encontrada'
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
     * Actualizar relación (solo fecha de asignación)
     */
    public function updateRelacion(int $id_relacion, array $data): array 
    {
        try {
            // Verificar que existe
            $existingRecord = $this->readRelacion($id_relacion);
            if (!$existingRecord['success']) {
                return $existingRecord;
            }
            
            // Solo se puede actualizar la fecha de asignación
            if (!isset($data['fecha_asignacion'])) {
                return [
                    'success' => false,
                    'error' => 'No se especificó la fecha de asignación a actualizar'
                ];
            }
            
            $sql = "UPDATE {$this->tableName} SET fecha_asignacion = ? WHERE id_relacion = ?";
            $stmt = $this->executeQuery($sql, [$data['fecha_asignacion'], $id_relacion]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('update', [
                    'id_relacion' => $id_relacion,
                    'nueva_fecha' => $data['fecha_asignacion']
                ]);
                return [
                    'success' => true,
                    'message' => 'Fecha de asignación actualizada exitosamente'
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
     * Eliminar relación vendedor-condominio
     */
    public function deleteRelacion(int $id_relacion): array 
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE id_relacion = ?";
            $stmt = $this->executeQuery($sql, [$id_relacion]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('delete', ['id_relacion' => $id_relacion]);
                return [
                    'success' => true,
                    'message' => 'Relación vendedor-condominio eliminada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Relación vendedor-condominio no encontrada'
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
     * Verificar si relación existe (por vendedor y condominio)
     */
    public function relacionExists(int $id_vendedor, int $id_condominio): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_vendedor = ? AND id_condominio = ?";
        $stmt = $this->executeQuery($sql, [$id_vendedor, $id_condominio]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si vendedor existe
     */
    private function vendedorExists(int $id_vendedor): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM vendedores WHERE id_vendedor = ?";
        $stmt = $this->executeQuery($sql, [$id_vendedor]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
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
}
?>

