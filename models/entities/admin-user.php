<?php
require_once __DIR__ . '/../Base-Model.php';

class Admin extends BaseModel {
    // Propiedades públicas correspondientes a la tabla admin
    public ?int $id_admin;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $rfc;
    public ?string $razon_social;
    public ?string $regimen_fiscal;
    public ?string $cp_fiscal;
    public ?string $correo;
    public ?string $contrasena;
    public ?string $fecha_alta;
    public ?string $recovery_token;
    public ?string $recovery_token_expiry;
    public ?int $email_verificado;
    public ?string $email_verification_token;
    public ?string $email_verification_expires;
    
    public function __construct(
        ?int $id_admin = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $rfc = null,
        ?string $razon_social = null,
        ?string $regimen_fiscal = null,
        ?string $cp_fiscal = null,
        ?string $correo = null,
        ?string $contrasena = null,
        ?string $fecha_alta = null,
        ?string $recovery_token = null,
        ?string $recovery_token_expiry = null,
        ?int $email_verificado = null,
        ?string $email_verification_token = null,
        ?string $email_verification_expires = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'admin';
        $this->primaryKey = 'id_admin';
        $this->fillableFields = [
            'nombres', 'apellido1', 'apellido2', 'rfc', 'razon_social',
            'regimen_fiscal', 'cp_fiscal', 'correo', 'contrasena',
            'recovery_token', 'recovery_token_expiry', 'email_verificado',
            'email_verification_token', 'email_verification_expires'
        ];
        $this->encryptedFields = [
            'nombres', 'apellido1', 'apellido2', 'rfc', 'razon_social',
            'regimen_fiscal', 'correo', 'recovery_token',
            'email_verification_token'
        ];
        $this->hiddenFields = ['contrasena', 'recovery_token', 'email_verification_token'];
        
        // Asignar propiedades
        $this->id_admin = $id_admin;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->rfc = $rfc;
        $this->razon_social = $razon_social;
        $this->regimen_fiscal = $regimen_fiscal;
        $this->cp_fiscal = $cp_fiscal;
        $this->correo = $correo;
        $this->contrasena = $contrasena;
        $this->fecha_alta = $fecha_alta;
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
     * Registrar nuevo administrador
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
            $data['fecha_alta'] = $this->getCurrentTimestamp();
            $data['email_verificado'] = 0;
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('registro', ['id_admin' => $id]);
            
            return [
                'success' => true,
                'id_admin' => $id,
                'message' => 'Administrador registrado exitosamente'
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
     * Login de administrador
     */
    public function login(string $correo, string $contrasena): array 
    {
        try {
            $this->beginTransaction();
            
            $admin = $this->findByCorreo($correo);
            
            if (!$admin || !$this->verifyPassword($contrasena, $admin['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            if ($admin['email_verificado'] == 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Email no verificado'
                ];
            }
            
            $this->commit();
            $this->logActivity('login', ['id_admin' => $admin['id_admin']]);
            
            // Desencriptar datos sensibles para respuesta
            $admin = $this->decryptSensitiveFields($admin);
            $admin = $this->hideSecretFields($admin);
            
            return [
                'success' => true,
                'admin' => $admin,
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
     * Buscar administrador por correo (busca en todos los registros desencriptando)
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
     * Buscar administrador por ID
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
     * Eliminar administrador
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
                    'error' => 'No se pudo eliminar el administrador'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_admin' => $id]);
            
            return [
                'success' => true,
                'message' => 'Administrador eliminado exitosamente'
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
     * Actualizar administrador
     */
    public function updateAdmin(int $id, array $data): array 
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
                    'error' => 'No se pudo actualizar el administrador'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_admin' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Administrador actualizado exitosamente'
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
     * Obtener perfil de administrador
     */
    public function getProfile(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $admin = $this->getById($id);
            
            if (!$admin) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Administrador no encontrado'
                ];
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'admin' => $admin
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
     * Verificar si correo existe (busca en todos los registros desencriptando)
     */
    public function correoExists(string $correo): bool 
    {
        try {
            $sql = "SELECT id_admin, correo FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    $correoDesencriptado = $this->decryptField($record['correo']);
                    if ($correoDesencriptado === $correo) {
                        return true;
                    }
                } catch (Exception $e) {
                    // Si no se puede desencriptar, continuar con el siguiente
                    continue;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en correoExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $id, string $nuevaContrasena): array 
    {
        try {
            $this->beginTransaction();
            
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
            $this->logActivity('change_password', ['id_admin' => $id]);
            
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
}