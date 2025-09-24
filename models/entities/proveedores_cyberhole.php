<?php
require_once __DIR__ . '/../Base-Model.php';

class ProveedorCyberhole extends BaseModel {
    // Propiedades públicas correspondientes a la tabla proveedores_cyberhole
    public ?int $id_proveedor;
    public ?string $nombre_empresa;
    public ?string $correo;
    public ?string $contrasena;
    public ?string $telefono;
    public ?string $contacto;
    public ?string $direccion;
    public ?string $fecha_registro;
    
    public function __construct(
        ?int $id_proveedor = null,
        ?string $nombre_empresa = null,
        ?string $correo = null,
        ?string $contrasena = null,
        ?string $telefono = null,
        ?string $contacto = null,
        ?string $direccion = null,
        ?string $fecha_registro = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'proveedores_cyberhole';
        $this->primaryKey = 'id_proveedor';
        $this->fillableFields = [
            'nombre_empresa', 'correo', 'contrasena', 'telefono',
            'contacto', 'direccion'
        ];
        $this->encryptedFields = [
            'nombre_empresa', 'correo', 'telefono', 'contacto', 'direccion'
        ];
        $this->hiddenFields = ['contrasena'];
        
        // Asignar propiedades
        $this->id_proveedor = $id_proveedor;
        $this->nombre_empresa = $nombre_empresa;
        $this->correo = $correo;
        $this->contrasena = $contrasena;
        $this->telefono = $telefono;
        $this->contacto = $contacto;
        $this->direccion = $direccion;
        $this->fecha_registro = $fecha_registro;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Registrar nuevo proveedor
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
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('registro', ['id_proveedor' => $id]);
            
            return [
                'success' => true,
                'id_proveedor' => $id,
                'message' => 'Proveedor registrado exitosamente'
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
     * Login de proveedor
     */
    public function login(string $correo, string $contrasena): array 
    {
        try {
            $this->beginTransaction();
            
            $proveedor = $this->findByCorreo($correo);
            
            if (!$proveedor || !$this->verifyPassword($contrasena, $proveedor['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            $this->commit();
            $this->logActivity('login', ['id_proveedor' => $proveedor['id_proveedor']]);
            
            // Desencriptar datos sensibles para respuesta
            $proveedor = $this->decryptSensitiveFields($proveedor);
            $proveedor = $this->hideSecretFields($proveedor);
            
            return [
                'success' => true,
                'proveedor' => $proveedor,
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
     * Buscar proveedor por correo (busca en todos los registros desencriptando)
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
     * Buscar proveedor por ID
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
     * Eliminar proveedor
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
                    'error' => 'No se pudo eliminar el proveedor'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_proveedor' => $id]);
            
            return [
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
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
     * Actualizar proveedor
     */
    public function updateProveedor(int $id, array $data): array 
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
                    'error' => 'No se pudo actualizar el proveedor'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_proveedor' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente'
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
     * Obtener perfil de proveedor
     */
    public function getProfile(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $proveedor = parent::findById($id);
            
            if (!$proveedor) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Proveedor no encontrado'
                ];
            }
            
            // Desencriptar datos sensibles
            $proveedor = $this->decryptSensitiveFields($proveedor);
            $proveedor = $this->hideSecretFields($proveedor);
            
            $this->commit();
            
            return [
                'success' => true,
                'proveedor' => $proveedor
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
     * Listar todos los proveedores
     */
    public function getAllProveedores(int $limit = 0, int $offset = 0): array 
    {
        try {
            $this->beginTransaction();
            
            $proveedores = parent::findMany([], $limit, $offset);
            
            // Desencriptar datos de cada proveedor
            foreach ($proveedores as &$proveedor) {
                $proveedor = $this->decryptSensitiveFields($proveedor);
                $proveedor = $this->hideSecretFields($proveedor);
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'proveedores' => $proveedores,
                'total' => count($proveedores)
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
     * Validar datos de proveedor
     */
    public function validateProveedorData(array $data): array 
    {
        $errors = [];
        
        if (empty($data['nombre_empresa'])) {
            $errors[] = 'El nombre de la empresa es requerido';
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
     * Verificar si existe un proveedor con ese correo
     */
    public function existsByCorreo(string $correo): bool 
    {
        return $this->findByCorreo($correo) !== null;
    }
}
?>
