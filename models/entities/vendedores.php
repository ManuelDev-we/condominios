<?php
require_once __DIR__ . '/../Base-Model.php';

class Vendedor extends BaseModel {
    // Propiedades públicas correspondientes a la tabla vendedores
    public ?int $id_vendedor;
    public ?string $nombre;
    public ?string $correo;
    public ?string $contrasena;
    public ?string $telefono;
    public ?int $activo;
    public ?string $fecha_registro;
    
    public function __construct(
        ?int $id_vendedor = null,
        ?string $nombre = null,
        ?string $correo = null,
        ?string $contrasena = null,
        ?string $telefono = null,
        ?int $activo = null,
        ?string $fecha_registro = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'vendedores';
        $this->primaryKey = 'id_vendedor';
        $this->fillableFields = [
            'nombre', 'correo', 'contrasena', 'telefono', 'activo'
        ];
        $this->encryptedFields = [
            'nombre', 'correo', 'telefono'
        ];
        $this->hiddenFields = ['contrasena'];
        
        // Asignar propiedades
        $this->id_vendedor = $id_vendedor;
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->contrasena = $contrasena;
        $this->telefono = $telefono;
        $this->activo = $activo;
        $this->fecha_registro = $fecha_registro;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Registrar nuevo vendedor
     */
    public function registro(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Hash de contraseña con ARGON2ID (más fuerte)
            if (isset($data['contrasena'])) {
                $data['contrasena'] = $this->hashPasswordSecure($data['contrasena']);
            }
            
            // Establecer valores por defecto
            $data['fecha_registro'] = $this->getCurrentTimestamp();
            $data['activo'] = $data['activo'] ?? 1; // Activo por defecto
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('registro', ['id_vendedor' => $id]);
            
            return [
                'success' => true,
                'id_vendedor' => $id,
                'message' => 'Vendedor registrado exitosamente'
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
     * Login de vendedor
     */
    public function login(string $correo, string $contrasena): array 
    {
        try {
            $this->beginTransaction();
            
            $vendedor = $this->findByCorreo($correo);
            
            if (!$vendedor || !$this->verifyPassword($contrasena, $vendedor['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            if ($vendedor['activo'] == 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Vendedor inactivo'
                ];
            }
            
            $this->commit();
            $this->logActivity('login', ['id_vendedor' => $vendedor['id_vendedor']]);
            
            // Desencriptar datos sensibles para respuesta
            $vendedor = $this->decryptSensitiveFields($vendedor);
            $vendedor = $this->hideSecretFields($vendedor);
            
            return [
                'success' => true,
                'vendedor' => $vendedor,
                'message' => 'Login exitoso'
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
     * Buscar vendedor por correo (busca en todos los registros desencriptando)
     */
    public function findByCorreo(string $correo): ?array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    $correoDesencriptado = $this->decryptField($record['correo']);
                    if ($correoDesencriptado === $correo) {
                        return $record;
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en findByCorreo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar vendedor por ID
     */
    public function getById(int $id): ?array 
    {
        try {
            $this->beginTransaction();
            
            $result = parent::findById($id);
            
            if ($result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            $this->commit();
            
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Eliminar vendedor
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
                    'error' => 'No se pudo eliminar el vendedor'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_vendedor' => $id]);
            
            return [
                'success' => true,
                'message' => 'Vendedor eliminado exitosamente'
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
     * Actualizar vendedor
     */
    public function updateVendedor(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Si se actualiza contraseña, hasharla
            if (isset($data['contrasena'])) {
                $data['contrasena'] = $this->hashPasswordSecure($data['contrasena']);
            }
            
            $updated = parent::update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el vendedor'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_vendedor' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Vendedor actualizado exitosamente'
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
     * Obtener perfil de vendedor
     */
    public function getProfile(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $vendedor = parent::findById($id);
            
            if (!$vendedor) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Vendedor no encontrado'
                ];
            }
            
            // Desencriptar datos sensibles
            $vendedor = $this->decryptSensitiveFields($vendedor);
            $vendedor = $this->hideSecretFields($vendedor);
            
            $this->commit();
            
            return [
                'success' => true,
                'vendedor' => $vendedor
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
     * Activar/desactivar vendedor
     */
    public function setEstado(int $id, int $activo): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = parent::update($id, ['activo' => $activo]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estado del vendedor'
                ];
            }
            
            $this->commit();
            $this->logActivity('estado_change', ['id_vendedor' => $id, 'activo' => $activo]);
            
            return [
                'success' => true,
                'message' => 'Estado del vendedor actualizado exitosamente'
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
     * Listar todos los vendedores
     */
    public function getAllVendedores(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $vendedores = parent::findMany([], $limit, $offset);
            
            // Desencriptar datos de cada vendedor
            foreach ($vendedores as &$vendedor) {
                $vendedor = $this->decryptSensitiveFields($vendedor);
                $vendedor = $this->hideSecretFields($vendedor);
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'vendedores' => $vendedores,
                'total' => count($vendedores)
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
     * Listar vendedores activos
     */
    public function getVendedoresActivos(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $vendedores = parent::findMany(['activo' => 1], $limit, $offset);
            
            // Desencriptar datos de cada vendedor
            foreach ($vendedores as &$vendedor) {
                $vendedor = $this->decryptSensitiveFields($vendedor);
                $vendedor = $this->hideSecretFields($vendedor);
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'vendedores' => $vendedores,
                'total' => count($vendedores)
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
     * Validar datos de vendedor
     */
    public function validateVendedorData(array $data): array 
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        }
        
        if (empty($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico es requerido y debe ser válido';
        }
        
        if (empty($data['contrasena'])) {
            $errors[] = 'La contraseña es requerida';
        }
        
        return $errors;
    }

    /**
     * Verificar si existe un vendedor con ese correo
     */
    public function existsByCorreo(string $correo): bool 
    {
        return $this->findByCorreo($correo) !== null;
    }
}
?>
