<?php
/**
 * 👨‍💼 ADMIN COND MODEL - Relación Admin-Condominio
 * Modelo completo para gestión de administradores por condominio
 * Control de asignaciones y validaciones de permisos
 * 
 * @package Cyberhole\Models\Owners
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class AdminCond extends BaseModel 
{
    // Configuración del modelo
    protected $tableName = 'admin_cond';
    protected $primaryKey = ['id_admin', 'id_condominio'];
    protected $encryptedFields = []; // Sin encriptación especificada
    
    // Campos permitidos para inserción/actualización
    protected $fillableFields = ['id_admin', 'id_condominio'];
    
    // Propiedades del modelo
    public ?int $id_admin = null;
    public ?int $id_condominio = null;
    
    /**
     * Constructor con parámetros opcionales
     */
    public function __construct(
        ?int $id_admin = null,
        ?int $id_condominio = null
    ) {
        parent::__construct();
        
        $this->id_admin = $id_admin;
        $this->id_condominio = $id_condominio;
    }
    
    // ========================================
    // MÉTODOS CRUD BÁSICOS
    // ========================================
    
    /**
     * Crear nueva relación admin-condominio
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas obligatorias
            if (empty($data['id_admin'])) {
                return ['success' => false, 'error' => 'El ID del admin es obligatorio'];
            }
            
            if (empty($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El ID del condominio es obligatorio'];
            }
            
            // Verificar que admin existe
            if (!$this->adminExists($data['id_admin'])) {
                return ['success' => false, 'error' => 'El administrador especificado no existe'];
            }
            
            // Verificar que condominio existe
            if (!$this->condominioExists($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El condominio especificado no existe'];
            }
            
            // Verificar duplicados (mismo admin y condominio)
            if ($this->relacionExists($data['id_admin'], $data['id_condominio'])) {
                return [
                    'success' => false, 
                    'error' => 'Ya existe una relación entre este admin y condominio'
                ];
            }
            
            // Insertar registro
            $this->executeQuery(
                "INSERT INTO {$this->tableName} (id_admin, id_condominio) VALUES (?, ?)",
                [$data['id_admin'], $data['id_condominio']]
            );
            
            $this->logActivity('create', [
                'id_admin' => $data['id_admin'],
                'id_condominio' => $data['id_condominio']
            ]);
            
            return [
                'success' => true,
                'data' => $data,
                'message' => 'Relación admin-condominio creada exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Leer relación específica
     */
    public function readRelacion(int $id_admin, int $id_condominio): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_admin = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_admin, $id_condominio]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Relación admin-condominio no encontrada'
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
     * Actualizar relación (sin campos modificables, placeholder para consistencia)
     */
    public function updateRelacion(int $id_admin, int $id_condominio, array $data): array 
    {
        try {
            // Verificar que existe
            $existingRecord = $this->readRelacion($id_admin, $id_condominio);
            if (!$existingRecord['success']) {
                return $existingRecord;
            }
            
            // No hay campos modificables en esta tabla, pero mantenemos el método
            return [
                'success' => true,
                'message' => 'Relación admin-condominio confirmada (sin cambios necesarios)'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar relación admin-condominio
     */
    public function deleteRelacion(int $id_admin, int $id_condominio): array 
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE id_admin = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_admin, $id_condominio]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('delete', [
                    'id_admin' => $id_admin,
                    'id_condominio' => $id_condominio
                ]);
                return [
                    'success' => true,
                    'message' => 'Relación admin-condominio eliminada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Relación admin-condominio no encontrada'
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
     * Verificar si relación existe
     */
    public function relacionExists(int $id_admin, int $id_condominio): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_admin = ? AND id_condominio = ?";
        $stmt = $this->executeQuery($sql, [$id_admin, $id_condominio]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si admin existe
     */
    private function adminExists(int $id_admin): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM admin WHERE id_admin = ?";
        $stmt = $this->executeQuery($sql, [$id_admin]);
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
    
    // ========================================
    // MÉTODOS GETTERS ADICIONALES (CON PAGINACIÓN)
    // ========================================
    
    /**
     * Obtener todos los condominios que maneja un admin (paginado de 10 en 10)
     */
    public function getCondominiosByAdmin(int $id_admin, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT ac.*, c.nombre_condominio, c.direccion, c.telefono, c.email 
                    FROM {$this->tableName} ac
                    INNER JOIN condominios c ON ac.id_condominio = c.id_condominio
                    WHERE ac.id_admin = ? 
                    ORDER BY c.nombre_condominio ASC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_admin, $limit, $offset]);
            $resultados = $stmt->fetchAll();
            
            // Contar total para paginación
            $totalStmt = $this->executeQuery(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_admin = ?", 
                [$id_admin]
            );
            $total = $totalStmt->fetch()['total'];
            
            return [
                'success' => true,
                'data' => $resultados,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Condominios obtenidos exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error obteniendo condominios por admin: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Obtener todos los admins que tiene un condominio (paginado de 10 en 10)
     */
    public function getAdminsByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT ac.*, a.nombre, a.apellido, a.email, a.telefono, a.activo
                    FROM {$this->tableName} ac
                    INNER JOIN admin a ON ac.id_admin = a.id_admin
                    WHERE ac.id_condominio = ? 
                    ORDER BY a.nombre ASC, a.apellido ASC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $resultados = $stmt->fetchAll();
            
            // Contar total para paginación
            $totalStmt = $this->executeQuery(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_condominio = ?", 
                [$id_condominio]
            );
            $total = $totalStmt->fetch()['total'];
            
            return [
                'success' => true,
                'data' => $resultados,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Administradores obtenidos exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error obteniendo admins por condominio: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
}
?>