<?php
require_once __DIR__ . '/../Base-Model.php';

class Casas extends BaseModel {
    // Propiedades públicas correspondientes a la tabla casas
    public ?int $id_casa;
    public ?string $casa;
    public ?int $id_condominio;
    public ?int $id_calle;

    public function __construct(
        ?int $id_casa = null,
        ?string $casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'casas';
        $this->primaryKey = 'id_casa';
        $this->fillableFields = [
            'casa', 'id_condominio', 'id_calle'
        ];
        $this->encryptedFields = []; // Sin encriptación
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_casa = $id_casa;
        $this->casa = $casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nueva casa
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_casa' => $id]);
            
            return [
                'success' => true,
                'id_casa' => $id,
                'message' => 'Casa creada exitosamente'
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
     * Obtener casa por ID
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
     * Obtener todas las casas de un condominio
     */
    public function getByCondominio(int $id_condominio, int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $casas = parent::findMany(['id_condominio' => $id_condominio], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'casas' => $casas,
                'total' => count($casas)
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
     * Obtener todas las casas de una calle específica
     */
    public function getByCalle(int $id_calle, int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $casas = parent::findMany(['id_calle' => $id_calle], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'casas' => $casas,
                'total' => count($casas)
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
     * Obtener casas por condominio y calle específicos
     */
    public function getByCondominioAndCalle(int $id_condominio, int $id_calle, int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $casas = parent::findMany([
                'id_condominio' => $id_condominio, 
                'id_calle' => $id_calle
            ], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'casas' => $casas,
                'total' => count($casas)
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
     * Obtener todas las casas
     */
    public function getAll(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $casas = parent::findMany([], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'casas' => $casas,
                'total' => count($casas)
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
     * Actualizar casa
     */
    public function updateCasa(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = parent::update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la casa'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_casa' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Casa actualizada exitosamente'
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
     * Eliminar casa (puede fallar por cascada)
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
                    'error' => 'No se pudo eliminar la casa'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_casa' => $id]);
            
            return [
                'success' => true,
                'message' => 'Casa eliminada exitosamente'
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
     * Buscar casas por nombre/número (LIKE) dentro de un condominio específico
     */
    public function searchByCasaInCondominio(int $id_condominio, string $casa): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_condominio = ? AND casa LIKE ? ORDER BY casa";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$casa}%"]);
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
     * Buscar casas por nombre/número (LIKE) dentro de una calle específica
     */
    public function searchByCasaInCalle(int $id_calle, string $casa): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_calle = ? AND casa LIKE ? ORDER BY casa";
            $stmt = $this->executeQuery($sql, [$id_calle, "%{$casa}%"]);
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
     * Buscar casas por nombre/número (LIKE) en condominio y calle específicos
     */
    public function searchByCasaInCondominioAndCalle(int $id_condominio, int $id_calle, string $casa): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND id_calle = ? AND casa LIKE ? 
                    ORDER BY casa";
            $stmt = $this->executeQuery($sql, [$id_condominio, $id_calle, "%{$casa}%"]);
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
     * Buscar casas por array de IDs dentro de un condominio específico
     */
    public function findByIdsInCondominio(int $id_condominio, array $ids): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($ids)) {
                $this->commit();
                return [
                    'success' => true,
                    'casas' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND {$this->primaryKey} IN ({$placeholders}) 
                    ORDER BY casa";
            $params = array_merge([$id_condominio], $ids);
            $stmt = $this->executeQuery($sql, $params);
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
     * Buscar casas por array de nombres/números dentro de un condominio específico
     */
    public function findByCasasInCondominio(int $id_condominio, array $casas): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($casas)) {
                $this->commit();
                return [
                    'success' => true,
                    'casas' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($casas) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND casa IN ({$placeholders}) 
                    ORDER BY casa";
            $params = array_merge([$id_condominio], $casas);
            $stmt = $this->executeQuery($sql, $params);
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
     * Buscar casas por array de IDs de calles dentro de un condominio específico
     */
    public function findByCallesInCondominio(int $id_condominio, array $id_calles): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($id_calles)) {
                $this->commit();
                return [
                    'success' => true,
                    'casas' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($id_calles) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND id_calle IN ({$placeholders}) 
                    ORDER BY id_calle, casa";
            $params = array_merge([$id_condominio], $id_calles);
            $stmt = $this->executeQuery($sql, $params);
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

    // ===========================================
    // MÉTODOS DE VALIDACIÓN
    // ===========================================

    /**
     * Validar datos de casa
     */
    public function validateCasaData(array $data): array 
    {
        $errors = [];
        
        if (empty($data['casa'])) {
            $errors[] = 'El nombre/número de la casa es requerido';
        }
        
        if (empty($data['id_condominio']) || !is_numeric($data['id_condominio'])) {
            $errors[] = 'El ID del condominio es requerido y debe ser numérico';
        }
        
        if (empty($data['id_calle']) || !is_numeric($data['id_calle'])) {
            $errors[] = 'El ID de la calle es requerido y debe ser numérico';
        }
        
        return $errors;
    }

    /**
     * Verificar si existe una casa con ese nombre en el condominio y calle
     */
    public function existsByCasaInCondominioAndCalle(int $id_condominio, int $id_calle, string $casa): bool 
    {
        try {
            $this->beginTransaction();
            
            $result = parent::findOne([
                'id_condominio' => $id_condominio, 
                'id_calle' => $id_calle, 
                'casa' => $casa
            ]);
            
            $this->commit();
            
            return $result !== null;
            
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * Contar total de casas en un condominio
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

    /**
     * Contar total de casas en una calle
     */
    public function getCountByCalle(int $id_calle): int 
    {
        try {
            $this->beginTransaction();
            
            $count = parent::count(['id_calle' => $id_calle]);
            
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
     * Obtener información del condominio al que pertenece la casa
     */
    public function getCondominio(int $id_casa): ?array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT co.* FROM condominios co 
                    INNER JOIN casas ca ON co.id_condominio = ca.id_condominio 
                    WHERE ca.id_casa = ?";
            $stmt = $this->executeQuery($sql, [$id_casa]);
            $result = $stmt->fetch();
            
            $this->commit();
            
            return $result ?: null;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getCondominio: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener información de la calle a la que pertenece la casa
     */
    public function getCalle(int $id_casa): ?array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT ca.* FROM calles ca 
                    INNER JOIN casas c ON ca.id_calle = c.id_calle 
                    WHERE c.id_casa = ?";
            $stmt = $this->executeQuery($sql, [$id_casa]);
            $result = $stmt->fetch();
            
            $this->commit();
            
            return $result ?: null;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getCalle: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener información completa de casa con condominio y calle
     */
    public function getFullInfo(int $id_casa): ?array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT 
                        c.id_casa, c.casa, c.id_condominio, c.id_calle,
                        co.nombre as condominio_nombre, co.rfc as condominio_rfc, 
                        co.direccion as condominio_direccion,
                        ca.nombre as calle_nombre, ca.descripcion as calle_descripcion
                    FROM casas c
                    INNER JOIN condominios co ON c.id_condominio = co.id_condominio
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_casa = ?";
            $stmt = $this->executeQuery($sql, [$id_casa]);
            $result = $stmt->fetch();
            
            $this->commit();
            
            return $result ?: null;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getFullInfo: " . $e->getMessage());
            return null;
        }
    }
}
?>