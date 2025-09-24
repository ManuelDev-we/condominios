<?php
require_once __DIR__ . '/../Base-Model.php';

class Persona extends BaseModel {
    // Propiedades públicas correspondientes a la tabla personas
    public ?int $id_persona;
    public ?string $curp;
    public ?string $rfc;
    public ?string $razon_social;
    public ?string $regimen_fiscal;
    public ?string $cp_fiscal;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $correo_electronico;
    public ?string $contrasena;
    public ?string $fecha_nacimiento;
    public ?int $jerarquia;
    public ?string $creado_en;
    public ?string $recovery_token;
    public ?string $recovery_token_expiry;
    public ?int $email_verificado;
    public ?string $email_verification_token;
    public ?string $email_verification_expires;
    
    public function __construct(
        ?int $id_persona = null,
        ?string $curp = null,
        ?string $rfc = null,
        ?string $razon_social = null,
        ?string $regimen_fiscal = null,
        ?string $cp_fiscal = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $correo_electronico = null,
        ?string $contrasena = null,
        ?string $fecha_nacimiento = null,
        ?int $jerarquia = null,
        ?string $creado_en = null,
        ?string $recovery_token = null,
        ?string $recovery_token_expiry = null,
        ?int $email_verificado = null,
        ?string $email_verification_token = null,
        ?string $email_verification_expires = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'personas';
        $this->primaryKey = 'id_persona';
        $this->fillableFields = [
            'curp', 'rfc', 'razon_social', 'regimen_fiscal', 'cp_fiscal',
            'nombres', 'apellido1', 'apellido2', 'correo_electronico', 'contrasena',
            'fecha_nacimiento', 'jerarquia', 'recovery_token', 'recovery_token_expiry',
            'email_verificado', 'email_verification_token', 'email_verification_expires'
        ];
        $this->encryptedFields = [
            'curp', 'rfc', 'razon_social', 'regimen_fiscal',
            'nombres', 'apellido1', 'apellido2', 'correo_electronico',
            'recovery_token', 'email_verification_token'
        ];
        $this->hiddenFields = ['contrasena', 'recovery_token', 'email_verification_token'];
        
        // Asignar propiedades
        $this->id_persona = $id_persona;
        $this->curp = $curp;
        $this->rfc = $rfc;
        $this->razon_social = $razon_social;
        $this->regimen_fiscal = $regimen_fiscal;
        $this->cp_fiscal = $cp_fiscal;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->correo_electronico = $correo_electronico;
        $this->contrasena = $contrasena;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->jerarquia = $jerarquia;
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
     * Registrar nueva persona
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
            $data['creado_en'] = $this->getCurrentTimestamp();
            $data['email_verificado'] = 0;
            $data['jerarquia'] = $data['jerarquia'] ?? 0; // 0 por defecto
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('registro', ['id_persona' => $id]);
            
            return [
                'success' => true,
                'id_persona' => $id,
                'message' => 'Persona registrada exitosamente'
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
     * Login de persona
     */
    public function login(string $correo_electronico, string $contrasena): array 
    {
        try {
            $this->beginTransaction();
            
            $persona = $this->findByCorreo($correo_electronico);
            
            if (!$persona || !$this->verifyPassword($contrasena, $persona['contrasena'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ];
            }
            
            if ($persona['email_verificado'] == 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Email no verificado'
                ];
            }
            
            $this->commit();
            $this->logActivity('login', ['id_persona' => $persona['id_persona']]);
            
            // Desencriptar datos sensibles para respuesta
            $persona = $this->decryptSensitiveFields($persona);
            $persona = $this->hideSecretFields($persona);
            
            return [
                'success' => true,
                'persona' => $persona,
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
     * Buscar persona por correo (busca en todos los registros desencriptando)
     */
    public function findByCorreo(string $correo_electronico): ?array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    $correoDesencriptado = $this->decryptField($record['correo_electronico']);
                    if ($correoDesencriptado === $correo_electronico) {
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
     * Buscar persona por ID
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
     * Eliminar persona
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
                    'error' => 'No se pudo eliminar la persona'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_persona' => $id]);
            
            return [
                'success' => true,
                'message' => 'Persona eliminada exitosamente'
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
     * Actualizar persona
     */
    public function updatePersona(int $id, array $data): array 
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
                    'error' => 'No se pudo actualizar la persona'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_persona' => $id, 'fields' => array_keys($data)]);
            
            return [
                'success' => true,
                'message' => 'Persona actualizada exitosamente'
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
     * Obtener perfil de persona
     */
    public function getProfile(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $persona = $this->getById($id);
            
            if (!$persona) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Persona no encontrada'
                ];
            }
            
            $this->commit();
            
            return [
                'success' => true,
                'persona' => $persona
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
    public function correoExists(string $correo_electronico): bool 
    {
        try {
            $sql = "SELECT id_persona, correo_electronico FROM {$this->tableName}";
            $stmt = $this->executeQuery($sql, []);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                try {
                    $correoDesencriptado = $this->decryptField($record['correo_electronico']);
                    if ($correoDesencriptado === $correo_electronico) {
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
            $this->logActivity('change_password', ['id_persona' => $id]);
            
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
