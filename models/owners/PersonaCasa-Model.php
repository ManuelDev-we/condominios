<?php
/**
 * 🏠 PERSONA CASA MODEL - Relación Muchos-a-Muchos
 * Modelo completo para gestión de relaciones persona-casa
 * Control de roles y prevención de duplicados
 * 
 * @package Cyberhole\Models\Owners
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class PersonaCasa extends BaseModel 
{
    // Configuración del modelo
    protected $tableName = 'persona_casa';
    protected $primaryKey = ['id_persona', 'id_casa'];
    protected $encryptedFields = []; // Sin encriptación especificada
    
    // Campos permitidos para inserción/actualización
    protected $fillableFields = ['id_persona', 'id_casa', 'rol'];
    
    // Propiedades del modelo
    public ?int $id_persona = null;
    public ?int $id_casa = null;
    public ?string $rol = null;
    
    // Roles válidos según enum de base de datos REAL
    private $rolesValidos = ['propietario', 'residente', 'inquilino'];
    
    /**
     * Constructor con parámetros opcionales
     */
    public function __construct(
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?string $rol = null
    ) {
        parent::__construct();
        
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->rol = $rol;
    }
    
    // ========================================
    // MÉTODOS CRUD BÁSICOS
    // ========================================
    
    /**
     * Crear nueva relación persona-casa
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas obligatorias
            if (empty($data['id_persona'])) {
                return ['success' => false, 'error' => 'El ID de la persona es obligatorio'];
            }
            
            if (empty($data['id_casa'])) {
                return ['success' => false, 'error' => 'El ID de la casa es obligatorio'];
            }
            
            if (empty($data['rol'])) {
                return ['success' => false, 'error' => 'El rol es obligatorio'];
            }
            
            // Validar rol
            if (!in_array($data['rol'], $this->rolesValidos)) {
                return [
                    'success' => false, 
                    'error' => 'Rol inválido. Valores permitidos: ' . implode(', ', $this->rolesValidos)
                ];
            }
            
            // Verificar que persona existe
            if (!$this->personaExists($data['id_persona'])) {
                return ['success' => false, 'error' => 'La persona especificada no existe'];
            }
            
            // Verificar que casa existe
            if (!$this->casaExists($data['id_casa'])) {
                return ['success' => false, 'error' => 'La casa especificada no existe'];
            }
            
            // Verificar duplicados (misma persona y casa)
            if ($this->relacionExists($data['id_persona'], $data['id_casa'])) {
                return [
                    'success' => false, 
                    'error' => 'Ya existe una relación entre esta persona y casa'
                ];
            }
            
            // Insertar registro
            $this->executeQuery(
                "INSERT INTO {$this->tableName} (id_persona, id_casa, rol) VALUES (?, ?, ?)",
                [$data['id_persona'], $data['id_casa'], $data['rol']]
            );
            
            $this->logActivity('create', [
                'id_persona' => $data['id_persona'],
                'id_casa' => $data['id_casa'],
                'rol' => $data['rol']
            ]);
            
            return [
                'success' => true,
                'data' => $data,
                'message' => 'Relación persona-casa creada exitosamente'
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
    public function readRelacion(int $id_persona, int $id_casa): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_persona = ? AND id_casa = ?";
            $stmt = $this->executeQuery($sql, [$id_persona, $id_casa]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Relación persona-casa no encontrada'
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
     * Actualizar relación (solo el rol se puede cambiar)
     */
    public function updateRelacion(int $id_persona, int $id_casa, array $data): array 
    {
        try {
            // Verificar que existe
            $existingRecord = $this->readRelacion($id_persona, $id_casa);
            if (!$existingRecord['success']) {
                return $existingRecord;
            }
            
            // Solo se puede actualizar el rol
            if (!isset($data['rol'])) {
                return [
                    'success' => false,
                    'error' => 'No se especificó el rol a actualizar'
                ];
            }
            
            // Validar rol
            if (!in_array($data['rol'], $this->rolesValidos)) {
                return [
                    'success' => false, 
                    'error' => 'Rol inválido. Valores permitidos: ' . implode(', ', $this->rolesValidos)
                ];
            }
            
            $sql = "UPDATE {$this->tableName} SET rol = ? WHERE id_persona = ? AND id_casa = ?";
            $stmt = $this->executeQuery($sql, [$data['rol'], $id_persona, $id_casa]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('update', [
                    'id_persona' => $id_persona,
                    'id_casa' => $id_casa,
                    'nuevo_rol' => $data['rol']
                ]);
                return [
                    'success' => true,
                    'message' => 'Rol actualizado exitosamente'
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
     * Eliminar relación persona-casa
     */
    public function deleteRelacion(int $id_persona, int $id_casa): array 
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE id_persona = ? AND id_casa = ?";
            $stmt = $this->executeQuery($sql, [$id_persona, $id_casa]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity('delete', [
                    'id_persona' => $id_persona,
                    'id_casa' => $id_casa
                ]);
                return [
                    'success' => true,
                    'message' => 'Relación persona-casa eliminada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Relación persona-casa no encontrada'
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
    public function relacionExists(int $id_persona, int $id_casa): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_persona = ? AND id_casa = ?";
        $stmt = $this->executeQuery($sql, [$id_persona, $id_casa]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si persona existe
     */
    private function personaExists(int $id_persona): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM personas WHERE id_persona = ?";
        $stmt = $this->executeQuery($sql, [$id_persona]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    /**
     * Verificar si casa existe
     */
    private function casaExists(int $id_casa): bool 
    {
        $sql = "SELECT COUNT(*) as total FROM casas WHERE id_casa = ?";
        $stmt = $this->executeQuery($sql, [$id_casa]);
        $result = $stmt->fetch();
        return (int) $result['total'] > 0;
    }
    
    // ========================================
    // MÉTODOS GETTERS ADICIONALES (CON PAGINACIÓN)
    // ========================================
    
    /**
     * Obtener todas las casas que tiene una persona (paginado de 10 en 10)
     */
    public function getCasasByPersona(int $id_persona, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT pc.*, c.casa, ca.nombre, co.nombre as nombre_condominio
                    FROM {$this->tableName} pc
                    INNER JOIN casas c ON pc.id_casa = c.id_casa
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    INNER JOIN condominios co ON ca.id_condominio = co.id_condominio
                    WHERE pc.id_persona = ? 
                    ORDER BY co.nombre ASC, ca.nombre ASC, c.casa ASC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_persona, $limit, $offset]);
            $resultados = $stmt->fetchAll();
            
            // Contar total para paginación
            $totalStmt = $this->executeQuery(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_persona = ?", 
                [$id_persona]
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
                'message' => 'Casas obtenidas exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error obteniendo casas por persona: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Obtener todas las personas que tiene una casa (paginado de 10 en 10)
     */
    public function getPersonasByCasa(int $id_casa, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT pc.*, p.nombres, p.apellido1, p.correo_electronico
                    FROM {$this->tableName} pc
                    INNER JOIN personas p ON pc.id_persona = p.id_persona
                    WHERE pc.id_casa = ? 
                    ORDER BY pc.rol ASC, p.nombres ASC, p.apellido1 ASC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_casa, $limit, $offset]);
            $resultados = $stmt->fetchAll();
            
            // Contar total para paginación
            $totalStmt = $this->executeQuery(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE id_casa = ?", 
                [$id_casa]
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
                'message' => 'Personas obtenidas exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error obteniendo personas por casa: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Actualizar rol de una relación persona-casa
     */
    public function updateRol(int $id_persona, int $id_casa, string $nuevo_rol): array 
    {
        try {
            // Validar que el rol sea válido
            if (!in_array($nuevo_rol, $this->rolesValidos)) {
                return [
                    'success' => false,
                    'error' => 'Rol no válido. Roles permitidos: ' . implode(', ', $this->rolesValidos)
                ];
            }
            
            // Verificar que la relación existe
            $existeRelacion = $this->readRelacion($id_persona, $id_casa);
            if (!$existeRelacion['success']) {
                return [
                    'success' => false,
                    'error' => 'La relación persona-casa no existe'
                ];
            }
            
            // Actualizar el rol
            $sql = "UPDATE {$this->tableName} SET rol = ? WHERE id_persona = ? AND id_casa = ?";
            $stmt = $this->executeQuery($sql, [$nuevo_rol, $id_persona, $id_casa]);
            
            return [
                'success' => true,
                'message' => 'Rol actualizado exitosamente',
                'data' => [
                    'id_persona' => $id_persona,
                    'id_casa' => $id_casa,
                    'nuevo_rol' => $nuevo_rol
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error actualizando rol: ' . $e->getMessage()
            ];
        }
    }
}
?>
