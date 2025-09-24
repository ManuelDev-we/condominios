<?php
/**
 * 🏷️ TAG MODEL - Modelo de Tags Simples
 * Manejo básico de tags sin encriptación
 * CRUD básico + getters/setters
 * 
 * @package Cyberhole\Models\Dispositivos
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../base-model.php';

class TagModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla tags
    public ?int $id_tag;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $codigo_tag;
    public ?int $activo;
    public ?string $creado_en;

    public function __construct(
        ?int $id_tag = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $codigo_tag = null,
        ?int $activo = 1,
        ?string $creado_en = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'tags';
        $this->primaryKey = 'id_tag';
        $this->fillableFields = [
            'id_persona', 'id_casa', 'id_condominio', 'id_calle', 
            'codigo_tag', 'activo'
        ];
        
        // Sin campos encriptados ni ocultos para TagModel
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_tag = $id_tag;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->codigo_tag = $codigo_tag;
        $this->activo = $activo ?? 1;
        $this->creado_en = $creado_en;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nuevo tag
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_tag' => $id]);
            
            return [
                'success' => true,
                'id_tag' => $id,
                'message' => 'Tag creado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Leer tag por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Tag no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'tag' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar tag
     */
    public function updateTag(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el tag'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_tag' => $id]);
            
            return [
                'success' => true,
                'message' => 'Tag actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar tag
     */
    public function delate(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el tag'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_tag' => $id]);
            
            return [
                'success' => true,
                'message' => 'Tag eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // GETTERS Y SETTERS
    // ===========================================

    /**
     * Obtener tags por ID de persona
     */
    public function getByPersona(int $id_persona): array 
    {
        try {
            $results = $this->findMany(['id_persona' => $id_persona]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags por ID de casa
     */
    public function getByCasa(int $id_casa): array 
    {
        try {
            $results = $this->findMany(['id_casa' => $id_casa]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags por ID de calle
     */
    public function getByCalle(int $id_calle): array 
    {
        try {
            $results = $this->findMany(['id_calle' => $id_calle]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags por ID de condominio
     */
    public function getByCondominio(int $id_condominio): array 
    {
        try {
            $results = $this->findMany(['id_condominio' => $id_condominio]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags activos
     */
    public function getActivos(): array 
    {
        try {
            $results = $this->findMany(['activo' => 1]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags inactivos
     */
    public function getInactivos(): array 
    {
        try {
            $results = $this->findMany(['activo' => 0]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags activos por calle
     */
    public function getActivosByCalle(int $id_calle): array 
    {
        try {
            $results = $this->findMany(['id_calle' => $id_calle, 'activo' => 1]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tags activos por condominio
     */
    public function getActivosByCondominio(int $id_condominio): array 
    {
        try {
            $results = $this->findMany(['id_condominio' => $id_condominio, 'activo' => 1]);
            
            return [
                'success' => true,
                'tags' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar tag por código
     */
    public function getByCodigo(string $codigo_tag): array 
    {
        try {
            $result = $this->findOne(['codigo_tag' => $codigo_tag]);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Tag con código no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'tag' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si código de tag existe
     */
    public function codigoExists(string $codigo_tag, int $exclude_id = null): bool 
    {
        try {
            $conditions = ['codigo_tag' => $codigo_tag];
            
            if ($exclude_id) {
                $conditions['id_tag !='] = $exclude_id;
            }
            
            $result = $this->findOne($conditions);
            return !empty($result);
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function setActivo(int $id, bool $activo): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id, ['activo' => $activo ? 1 : 0]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estado'
                ];
            }
            
            $this->commit();
            $this->logActivity('set_activo', ['id_tag' => $id, 'activo' => $activo]);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas de tags
     */
    public function obtenerEstadisticas(int $id_condominio = null): array 
    {
        try {
            $conditions = [];
            if ($id_condominio) {
                $conditions['id_condominio'] = $id_condominio;
            }
            
            $total = $this->count($conditions);
            
            $conditions['activo'] = 1;
            $activos = $this->count($conditions);
            
            $conditions['activo'] = 0;
            $inactivos = $this->count($conditions);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total' => $total,
                    'activos' => $activos,
                    'inactivos' => $inactivos,
                    'porcentaje_activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar código único para tag
     */
    public function generarCodigoUnico(string $prefix = 'TAG'): string 
    {
        do {
            $codigo = $prefix . '_' . time() . '_' . rand(1000, 9999);
        } while ($this->codigoExists($codigo));
        
        return $codigo;
    }
}