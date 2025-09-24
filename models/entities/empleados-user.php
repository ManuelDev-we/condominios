<?php
require_once __DIR__ . '/../Base-Model.php';

class EmpleadosUser extends BaseModel {
    // Propiedades públicas correspondientes a la tabla empleados_condominio
    public ?int $id_empleado;
    public ?string $rfc;
    public ?int $id_condominio;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $puesto;
    public ?string $email;
    public ?string $contrasena;
    public ?string $fecha_contrato;
    public ?string $id_acceso;
    public ?int $activo;
    public ?string $creado_en;
    public ?string $recovery_token;
    public ?string $recovery_token_expiry;
    public ?int $email_verificado;
    public ?string $email_verification_token;
    public ?string $email_verification_expires;
    
    public function __construct(
        ?int $id_empleado = null,
        ?string $rfc = null,
        ?int $id_condominio = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $puesto = null,
        ?string $email = null,
        ?string $contrasena = null,
        ?string $fecha_contrato = null,
        ?string $id_acceso = null,
        ?int $activo = null,
        ?string $creado_en = null,
        ?string $recovery_token = null,
        ?string $recovery_token_expiry = null,
        ?int $email_verificado = null,
        ?string $email_verification_token = null,
        ?string $email_verification_expires = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'empleados_condominio';
        $this->primaryKey = 'id_empleado';
        $this->fillableFields = [
            'rfc', 'id_condominio', 'nombres', 'apellido1', 'apellido2', 'puesto',
            'email', 'contrasena', 'fecha_contrato', 'id_acceso', 'activo',
            'creado_en', 'recovery_token', 'recovery_token_expiry',
            'email_verificado', 'email_verification_token', 'email_verification_expires'
        ];
        $this->encryptedFields = [
            'rfc', 'nombres', 'apellido1', 'apellido2', 'email',
            'id_acceso', 'recovery_token', 'email_verification_token'
        ];
        $this->hiddenFields = ['contrasena', 'recovery_token', 'email_verification_token'];
        
        // Asignar propiedades
        $this->id_empleado = $id_empleado;
        $this->rfc = $rfc;
        $this->id_condominio = $id_condominio;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->puesto = $puesto;
        $this->email = $email;
        $this->contrasena = $contrasena;
        $this->fecha_contrato = $fecha_contrato;
        $this->id_acceso = $id_acceso;
        $this->activo = $activo;
        $this->creado_en = $creado_en;
        $this->recovery_token = $recovery_token;
        $this->recovery_token_expiry = $recovery_token_expiry;
        $this->email_verificado = $email_verificado;
        $this->email_verification_token = $email_verification_token;
        $this->email_verification_expires = $email_verification_expires;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Registrar nuevo empleado
     */
    public function registro(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar que id_condominio es obligatorio
            if (!isset($data['id_condominio']) || empty($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El ID del condominio es obligatorio'
                ];
            }
            
            // Validar que el condominio existe
            if (!$this->condominioExists($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El condominio especificado no existe'
                ];
            }
            
            // Validar que el email no existe
            if (isset($data['email']) && $this->emailExists($data['email'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El email ya está registrado'
                ];
            }
            
            // Validar que el RFC no existe
            if (isset($data['rfc']) && $this->rfcExists($data['rfc'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El RFC ya está registrado'
                ];
            }
            
            // Hash de contraseña con ARGON2ID (más fuerte)
            if (isset($data['contrasena'])) {
                $data['contrasena'] = $this->hashPasswordSecure($data['contrasena']);
            }
            
            // Establecer valores por defecto
            $data['creado_en'] = $this->getCurrentTimestamp();
            $data['email_verificado'] = 0;
            $data['activo'] = $data['activo'] ?? 1; // Activo por defecto
            $data['fecha_contrato'] = $data['fecha_contrato'] ?? date('Y-m-d'); // Fecha actual por defecto
            
            // Generar ID de acceso único
            if (!isset($data['id_acceso']) || empty($data['id_acceso'])) {
                $data['id_acceso'] = $this->generateAccessId();
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('registro', ['id_empleado' => $id, 'id_condominio' => $data['id_condominio']]);
            
            return [
                'success' => true,
                'id_empleado' => $id,
                'message' => 'Empleado registrado exitosamente'
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
     * Login de empleado
     */
    public function login(string $email, string $contrasena): array 
    {
        try {
            $this->beginTransaction();
            
            $empleado = $this->findByEmail($email);
            
            if (!$empleado || !$this->verifyPassword($contrasena, $empleado['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            if ($empleado['email_verificado'] == 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Email no verificado'
                ];
            }
            
            if ($empleado['activo'] == 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Empleado inactivo'
                ];
            }
            
            $this->commit();
            $this->logActivity('login', ['id_empleado' => $empleado['id_empleado']]);
            
            // Desencriptar datos sensibles para respuesta
            $empleado = $this->decryptSensitiveFields($empleado);
            $empleado = $this->hideSecretFields($empleado);
            
            return [
                'success' => true,
                'user' => $empleado,
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
     * Buscar empleado por email (busca en todos los registros desencriptando)
     */
    public function findByEmail(string $email): ?array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    // Verificar que el campo email no sea nulo antes de desencriptar
                    if ($record['email'] && !empty($record['email'])) {
                        $emailDesencriptado = $this->decryptField($record['email']);
                        if ($emailDesencriptado === $email) {
                            return $record;
                        }
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en findByEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar empleado por ID
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
     * Eliminar empleado
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
                    'error' => 'No se pudo eliminar el empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_empleado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Empleado eliminado exitosamente'
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
     * Actualizar empleado
     */
    public function updateEmpleado(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Si se actualiza contraseña, hasharla
            if (isset($data['contrasena'])) {
                $data['contrasena'] = $this->hashPasswordSecure($data['contrasena']);
            }
            
            // Si se actualiza id_condominio, validar que existe
            if (isset($data['id_condominio']) && !$this->condominioExists($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El condominio especificado no existe'
                ];
            }
            
            $updated = parent::update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_empleado' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Empleado actualizado exitosamente'
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
     * Obtener perfil de empleado
     */
    public function getProfile(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $empleado = $this->getById($id);
            
            if (!$empleado) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Empleado no encontrado'
                ];
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'empleado' => $empleado
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
     * Verificar si email existe (busca en todos los registros desencriptando)
     */
    public function emailExists(string $email): bool 
    {
        try {
            $sql = "SELECT id_empleado, email FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    // Verificar que el campo email no sea nulo antes de desencriptar
                    if ($record['email'] && !empty($record['email'])) {
                        $emailDesencriptado = $this->decryptField($record['email']);
                        if ($emailDesencriptado === $email) {
                            return true;
                        }
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en emailExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $id, string $contraseñaActual, string $nuevaContrasena): array 
    {
        try {
            $this->beginTransaction();
            
            // Obtener empleado con contraseña incluida (sin ocultar campos secretos)
            $sql = "SELECT * FROM {$this->tableName} WHERE id_empleado = ?";
            $stmt = $this->executeQuery($sql, [$id]);
            $empleado = $stmt->fetch();
            
            if (!$empleado) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Empleado no encontrado'
                ];
            }
            
            // Verificar contraseña actual
            if (!EnvironmentConfig::verifyPassword($contraseñaActual, $empleado['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Contraseña actual incorrecta'
                ];
            }
            
            $hashedPassword = $this->hashPasswordSecure($nuevaContrasena);
            
            $updated = parent::update($id, ['contrasena' => $hashedPassword]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar la contraseña'
                ];
            }
            
            $this->commit();
            $this->logActivity('change_password', ['id_empleado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
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
    // MÉTODOS ESPECÍFICOS DE EMPLEADOS
    // ===========================================

    /**
     * Buscar empleados por condominio
     */
    public function getByCondominio(int $id_condominio): array 
    {
        try {
            $empleados = parent::findMany(['id_condominio' => $id_condominio]);
            
            // Desencriptar datos sensibles para cada empleado
            foreach ($empleados as &$empleado) {
                $empleado = $this->decryptSensitiveFields($empleado);
                $empleado = $this->hideSecretFields($empleado);
            }
            
            return $empleados;
            
        } catch (Exception $e) {
            error_log("Error en getByCondominio: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar empleados por puesto
     */
    public function getByPuesto(string $puesto): array 
    {
        try {
            $empleados = parent::findMany(['puesto' => $puesto]);
            
            // Desencriptar datos sensibles para cada empleado
            foreach ($empleados as &$empleado) {
                $empleado = $this->decryptSensitiveFields($empleado);
                $empleado = $this->hideSecretFields($empleado);
            }
            
            return $empleados;
            
        } catch (Exception $e) {
            error_log("Error en getByPuesto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar empleados por RFC (requiere búsqueda encriptada)
     */
    public function findByRFC(string $rfc): ?array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    // Verificar que el campo rfc no sea nulo antes de desencriptar
                    if ($record['rfc'] && !empty($record['rfc'])) {
                        $rfcDesencriptado = $this->decryptField($record['rfc']);
                        if ($rfcDesencriptado === $rfc) {
                            $record = $this->decryptSensitiveFields($record);
                            $record = $this->hideSecretFields($record);
                            return $record;
                        }
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en findByRFC: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener empleados activos
     */
    public function getEmpleadosActivos(): array 
    {
        try {
            $this->beginTransaction();
            
            $empleados = parent::findMany(['activo' => 1]);
            
            // Desencriptar datos sensibles para cada empleado
            foreach ($empleados as &$empleado) {
                $empleado = $this->decryptSensitiveFields($empleado);
                $empleado = $this->hideSecretFields($empleado);
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'empleados' => $empleados,
                'total' => count($empleados)
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
     * Activar/Desactivar empleado
     */
    public function setActivo(int $id, bool $activo): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = parent::update($id, ['activo' => $activo ? 1 : 0]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estado del empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('set_activo', ['id_empleado' => $id, 'activo' => $activo]);
            
            return [
                'success' => true,
                'message' => 'Estado del empleado actualizado exitosamente'
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
     * Obtener información completa del empleado con datos del condominio
     */
    public function getFullInfo(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $empleado = $this->getById($id);
            
            if (!$empleado) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Empleado no encontrado'
                ];
            }
            
            // Obtener información del condominio
            $sql = "SELECT id_condominio, nombre, direccion FROM condominios WHERE id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$empleado['id_condominio']]);
            $condominio = $stmt->fetch();
            
            if ($condominio) {
                $empleado['condominio_info'] = $condominio;
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'empleado' => $empleado
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
    // MÉTODOS DE UTILIDAD
    // ===========================================

    /**
     * Verificar si condominio existe
     */
    private function condominioExists(int $id_condominio): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM condominios WHERE id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $result = $stmt->fetch();
            
            return (int) $result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("Error verificando condominio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar ID de acceso único
     */
    private function generateAccessId(): string 
    {
        return hash('sha256', uniqid() . time() . random_bytes(16));
    }

    /**
     * Verificar RFC único (requiere búsqueda encriptada)
     */
    public function rfcExists(string $rfc): bool 
    {
        try {
            $sql = "SELECT id_empleado, rfc FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    // Verificar que el campo rfc no sea nulo antes de desencriptar
                    if ($record['rfc'] && !empty($record['rfc'])) {
                        $rfcDesencriptado = $this->decryptField($record['rfc']);
                        if ($rfcDesencriptado === $rfc) {
                            return true;
                        }
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en rfcExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar empleados por nombre (búsqueda parcial encriptada)
     */
    public function searchByNombre(string $nombre): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            $empleados = [];
            
            foreach ($records as $record) {
                try {
                    // Verificar que los campos no sean nulos antes de desencriptar
                    if ($record['nombres'] && !empty($record['nombres']) &&
                        $record['apellido1'] && !empty($record['apellido1'])) {
                        
                        $nombreDesencriptado = $this->decryptField($record['nombres']);
                        $apellido1Desencriptado = $this->decryptField($record['apellido1']);
                        $apellido2Desencriptado = '';
                        
                        if ($record['apellido2'] && !empty($record['apellido2'])) {
                            $apellido2Desencriptado = $this->decryptField($record['apellido2']);
                        }
                        
                        $nombreCompleto = $nombreDesencriptado . ' ' . $apellido1Desencriptado . ' ' . $apellido2Desencriptado;
                        
                        if (stripos($nombreCompleto, $nombre) !== false) {
                            $record = $this->decryptSensitiveFields($record);
                            $record = $this->hideSecretFields($record);
                            $empleados[] = $record;
                        }
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return $empleados;
            
        } catch (Exception $e) {
            error_log("Error en searchByNombre: " . $e->getMessage());
            return [];
        }
    }
}

?>