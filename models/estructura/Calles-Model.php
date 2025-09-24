<?php
require_once __DIR__ . '/../Base-Model.php';

class Calles extends BaseModel {
    // Propiedades públicas correspondientes a la tabla calles
    public ?int $id_calle;
    public ?int $id_condominio;
    public ?string $nombre;
    public ?string $descripcion;

    public function __construct(
        ?int $id_calle = null,
        ?int $id_condominio = null,
        ?string $nombre = null,
        ?string $descripcion = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'calles';
        $this->primaryKey = 'id_calle';
        $this->fillableFields = [
            'id_condominio', 'nombre', 'descripcion'
        ];
        $this->encryptedFields = []; // Sin encriptación
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_calle = $id_calle;
        $this->id_condominio = $id_condominio;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nueva calle
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_calle' => $id]);
            
            return [
                'success' => true,
                'id_calle' => $id,
                'message' => 'Calle creada exitosamente'
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
     * Obtener calle por ID
     */
    public function getById(int $id): ?array 
    {
        try {
            $this->beginTransaction();
            
            $result = parent::findById($id);
            
            $this->commit();
            
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todas las calles de un condominio
     */
    public function getByCondominio(int $id_condominio, int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $calles = parent::findMany(['id_condominio' => $id_condominio], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $calles,
                'total' => count($calles)
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
     * Obtener todas las calles
     */
    public function getAll(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $calles = parent::findMany([], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $calles,
                'total' => count($calles)
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
     * Actualizar calle
     */
    public function updateCalle(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = parent::update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la calle'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_calle' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Calle actualizada exitosamente'
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
     * Eliminar calle (puede fallar por cascada)
     */
    public function delate(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = parent::delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la calle'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_calle' => $id]);
            
            return [
                'success' => true,
                'message' => 'Calle eliminada exitosamente'
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
    // MÉTODOS DE BÚSQUEDA Y FILTRADO
    // ===========================================

    /**
     * Buscar calles por nombre (LIKE) dentro de un condominio específico
     */
    public function searchByNombreInCondominio(int $id_condominio, string $nombre): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_condominio = ? AND nombre LIKE ? ORDER BY nombre";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$nombre}%"]);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $results,
                'total' => count($results)
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
     * Buscar calles por descripción (LIKE) dentro de un condominio específico
     */
    public function searchByDescripcionInCondominio(int $id_condominio, string $descripcion): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_condominio = ? AND descripcion LIKE ? ORDER BY nombre";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$descripcion}%"]);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $results,
                'total' => count($results)
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
     * Búsqueda general por nombre o descripción (LIKE) dentro de un condominio
     */
    public function searchInCondominio(int $id_condominio, string $termino): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND (nombre LIKE ? OR descripcion LIKE ?) 
                    ORDER BY nombre";
            $params = [$id_condominio, "%{$termino}%", "%{$termino}%"];
            $stmt = $this->executeQuery($sql, $params);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $results,
                'total' => count($results)
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
     * Buscar calles por array de IDs dentro de un condominio específico
     */
    public function findByIdsInCondominio(int $id_condominio, array $ids): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($ids)) {
                $this->commit();
                return [
                    'success' => true,
                    'calles' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND {$this->primaryKey} IN ({$placeholders}) 
                    ORDER BY nombre";
            $params = array_merge([$id_condominio], $ids);
            $stmt = $this->executeQuery($sql, $params);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $results,
                'total' => count($results)
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
     * Buscar calles por array de nombres dentro de un condominio específico
     */
    public function findByNombresInCondominio(int $id_condominio, array $nombres): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($nombres)) {
                $this->commit();
                return [
                    'success' => true,
                    'calles' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($nombres) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND nombre IN ({$placeholders}) 
                    ORDER BY nombre";
            $params = array_merge([$id_condominio], $nombres);
            $stmt = $this->executeQuery($sql, $params);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'calles' => $results,
                'total' => count($results)
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
    // MÉTODOS DE VALIDACIÓN
    // ===========================================

    /**
     * Validar datos de calle
     */
    public function validateCalleData(array $data): array 
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre de la calle es requerido';
        }
        
        if (empty($data['id_condominio']) || !is_numeric($data['id_condominio'])) {
            $errors[] = 'El ID del condominio es requerido y debe ser numérico';
        }
        
        return $errors;
    }

    /**
     * Verificar si existe una calle con ese nombre en el condominio
     */
    public function existsByNombreInCondominio(int $id_condominio, string $nombre): bool 
    {
        try {
            $this->beginTransaction();
            
            $result = parent::findOne(['id_condominio' => $id_condominio, 'nombre' => $nombre]);
            
            $this->commit();
            
            return $result !== null;
            
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * Contar total de calles en un condominio
     */
    public function getCountByCondominio(int $id_condominio): int 
    {
        try {
            $this->beginTransaction();
            
            $count = parent::count(['id_condominio' => $id_condominio]);
            
            $this->commit();
            
            return $count;
            
        } catch (Exception $e) {
            $this->rollback();
            return 0;
        }
    }

    // ===========================================
    // MÉTODOS RELACIONALES
    // ===========================================

    /**
     * Obtener casas de una calle
     */
    public function getCasas(int $id_calle): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM casas WHERE id_calle = ? ORDER BY casa";
            $stmt = $this->executeQuery($sql, [$id_calle]);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'casas' => $results,
                'total' => count($results)
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
     * Obtener información del condominio al que pertenece la calle
     */
    public function getCondominio(int $id_calle): ?array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT c.* FROM condominios c 
                    INNER JOIN calles ca ON c.id_condominio = ca.id_condominio 
                    WHERE ca.id_calle = ?";
            $stmt = $this->executeQuery($sql, [$id_calle]);
            $result = $stmt->fetch();
            
            $this->commit();
            
            return $result ?: null;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getCondominio: " . $e->getMessage());
            return null;
        }
    }
}
?>