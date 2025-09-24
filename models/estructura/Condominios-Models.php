<?php
require_once __DIR__ . '/../Base-Model.php';

class Condominios extends BaseModel {
    // Propiedades públicas correspondientes a la tabla condominios
    public ?int $id_condominio;
    public ?string $nombre;
    public ?string $rfc;
    public ?string $direccion;
    
    public function __construct(
        ?int $id_condominio = null,
        ?string $nombre = null,
        ?string $rfc = null,
        ?string $direccion = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'condominios';
        $this->primaryKey = 'id_condominio';
        $this->fillableFields = [
            'nombre', 'rfc', 'direccion'
        ];
        $this->encryptedFields = []; // Sin encriptación
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_condominio = $id_condominio;
        $this->nombre = $nombre;
        $this->rfc = $rfc;
        $this->direccion = $direccion;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nuevo condominio
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_condominio' => $id]);
            
            return [
                'success' => true,
                'id_condominio' => $id,
                'message' => 'Condominio creado exitosamente'
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
     * Obtener condominio por ID
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
     * Obtener todos los condominios
     */
    public function getAll(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $condominios = parent::findMany([], $limit, $offset);
            
            $this->commit();
            
            return [
                'success' => true,
                'condominios' => $condominios,
                'total' => count($condominios)
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
     * Actualizar condominio
     */
    public function updateCondominio(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = parent::update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el condominio'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_condominio' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Condominio actualizado exitosamente'
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
     * Eliminar condominio (puede fallar por cascada)
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
                    'error' => 'No se pudo eliminar el condominio'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_condominio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Condominio eliminado exitosamente'
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
     * Buscar condominios por nombre (LIKE)
     */
    public function searchByNombre(string $nombre): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM {$this->tableName} WHERE nombre LIKE ?";
            $stmt = $this->executeQuery($sql, ["%{$nombre}%"]);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'condominios' => $results,
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
     * Buscar condominios por RFC
     */
    public function findByRFC(string $rfc): ?array 
    {
        try {
            $this->beginTransaction();
            
            $result = parent::findOne(['rfc' => $rfc]);
            
            $this->commit();
            
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en findByRFC: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar condominios por array de IDs
     */
    public function findByIds(array $ids): array 
    {
        try {
            $this->beginTransaction();
            
            if (empty($ids)) {
                $this->commit();
                return [
                    'success' => true,
                    'condominios' => [],
                    'total' => 0
                ];
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} IN ({$placeholders})";
            $stmt = $this->executeQuery($sql, $ids);
            $results = $stmt->fetchAll();
            
            $this->commit();
            
            return [
                'success' => true,
                'condominios' => $results,
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
     * Validar datos de condominio
     */
    public function validateCondominioData(array $data): array 
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre del condominio es requerido';
        }
        
        if (empty($data['rfc'])) {
            $errors[] = 'El RFC es requerido';
        } elseif (strlen($data['rfc']) < 12 || strlen($data['rfc']) > 13) {
            $errors[] = 'El RFC debe tener entre 12 y 13 caracteres';
        }
        
        if (empty($data['direccion'])) {
            $errors[] = 'La dirección es requerida';
        }
        
        return $errors;
    }

    /**
     * Verificar si existe un condominio con ese RFC
     */
    public function existsByRFC(string $rfc): bool 
    {
        return $this->findByRFC($rfc) !== null;
    }

    /**
     * Contar total de condominios
     */
    public function getTotalCount(): int 
    {
        try {
            $this->beginTransaction();
            
            $count = parent::count();
            
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
     * Obtener calles de un condominio
     */
    public function getCalles(int $id_condominio): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM calles WHERE id_condominio = ? ORDER BY nombre";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
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
     * Obtener casas de un condominio
     */
    public function getCasas(int $id_condominio): array 
    {
        try {
            $this->beginTransaction();
            
            $sql = "SELECT * FROM casas WHERE id_condominio = ? ORDER BY casa";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
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
}
?>


