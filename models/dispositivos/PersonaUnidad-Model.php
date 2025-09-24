<?php
/**
 * 👥 PERSONA UNIDAD MODEL - Modelo de Personas por Unidad
 * Manejo de relaciones persona-unidad con encriptación de datos personales
 * Compresión y encriptación de fotos
 * 
 * @package Cyberhole\Models\Dispositivos
 * @author ManuelDev
 * @version 2.0 CLEAN
 */

require_once __DIR__ . '/../base-model.php';

class PersonaUnidadModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla personas_unidad
    public ?int $id_persona_unidad;
    public ?string $telefono_1;
    public ?string $telefono_2;
    public ?string $curp;
    public ?string $nombres;
    public ?string $apellido1;
    public ?string $apellido2;
    public ?string $fecha_nacimiento;
    public ?string $foto;
    public ?string $creado_en;

    public function __construct(
        ?int $id_persona_unidad = null,
        ?string $telefono_1 = null,
        ?string $telefono_2 = null,
        ?string $curp = null,
        ?string $nombres = null,
        ?string $apellido1 = null,
        ?string $apellido2 = null,
        ?string $fecha_nacimiento = null,
        ?string $foto = null,
        ?string $creado_en = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'personas_unidad';
        $this->primaryKey = 'id_persona_unidad';
        $this->fillableFields = [
            'telefono_1', 'telefono_2', 'curp', 'nombres', 
            'apellido1', 'apellido2', 'fecha_nacimiento', 'foto'
        ];
        
        // Campos que se encriptan: teléfonos, CURP, nombres y apellidos
        $this->encryptedFields = ['telefono_1', 'telefono_2', 'curp', 'nombres', 'apellido1', 'apellido2'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_persona_unidad = $id_persona_unidad;
        $this->telefono_1 = $telefono_1;
        $this->telefono_2 = $telefono_2;
        $this->curp = $curp;
        $this->nombres = $nombres;
        $this->apellido1 = $apellido1;
        $this->apellido2 = $apellido2;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->foto = $foto;
        $this->creado_en = $creado_en;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva persona unidad
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar foto si existe
            if (isset($data['foto_file']) && $data['foto_file']) {
                $fotoResult = $this->procesarFoto($data['foto_file']);
                if ($fotoResult['success']) {
                    $data['foto'] = $fotoResult['foto_encriptada'];
                }
                unset($data['foto_file']);
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la persona unidad'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_persona_unidad' => $id]);
            
            return [
                'success' => true,
                'id_persona_unidad' => $id,
                'message' => 'Persona unidad creada exitosamente'
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
     * Leer persona unidad por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Persona unidad no encontrada'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'persona_unidad' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar persona unidad
     */
    public function updatePersonaUnidad(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar foto si existe
            if (isset($data['foto_file']) && $data['foto_file']) {
                $fotoResult = $this->procesarFoto($data['foto_file']);
                if ($fotoResult['success']) {
                    $data['foto'] = $fotoResult['foto_encriptada'];
                }
                unset($data['foto_file']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la persona unidad'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_persona_unidad' => $id]);
            
            return [
                'success' => true,
                'message' => 'Persona unidad actualizada exitosamente'
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
     * Eliminar persona unidad
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
                    'error' => 'No se pudo eliminar la persona unidad'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_persona_unidad' => $id]);
            
            return [
                'success' => true,
                'message' => 'Persona unidad eliminada exitosamente'
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
    // MÉTODOS DE MANEJO DE FOTOS
    // ===========================================

    /**
     * Procesar foto: comprimir y encriptar
     */
    private function procesarFoto(string $fotoPath): array 
    {
        try {
            // Comprimir foto
            $compressionResult = $this->compressFile($fotoPath);
            
            if (!$compressionResult['success']) {
                throw new Exception('Error comprimiendo foto: ' . $compressionResult['error']);
            }
            
            // Encriptar foto comprimida
            $fotoEncriptada = $this->encryptField($compressionResult['data']);
            
            return [
                'success' => true,
                'foto_encriptada' => $fotoEncriptada,
                'compression_info' => $compressionResult
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recuperar foto: desencriptar y descomprimir
     */
    public function recuperarFoto(int $id_persona_unidad, string $outputPath = null): array 
    {
        try {
            $persona = $this->findById($id_persona_unidad);
            
            if (!$persona || !$persona['foto']) {
                return [
                    'success' => false,
                    'error' => 'Persona unidad o foto no encontrada'
                ];
            }
            
            // La foto ya viene desencriptada por BaseModel::findById
            $fotoData = $persona['foto'];
            
            // Descomprimir foto
            $extension = 'jpg'; // Por defecto, podríamos almacenar la extensión
            $decompressionResult = $this->decompressFile($fotoData, $extension, $outputPath);
            
            if (!$decompressionResult['success']) {
                throw new Exception('Error descomprimiendo foto: ' . $decompressionResult['error']);
            }
            
            return [
                'success' => true,
                'foto_data' => $decompressionResult['data'],
                'saved_to' => $decompressionResult['saved_to'] ?? null
            ];
            
        } catch (Exception $e) {
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
     * Buscar por teléfono (encriptado)
     */
    public function getByTelefono(string $telefono): array 
    {
        try {
            // El teléfono se debe encriptar para comparar
            $telefonoEncriptado = $this->encryptField($telefono);
            
            // Buscar en ambos campos de teléfono usando OR lógico
            $query = "SELECT * FROM {$this->tableName} WHERE telefono_1 = ? OR telefono_2 = ?";
            $stmt = $this->executeQuery($query, [$telefonoEncriptado, $telefonoEncriptado]);
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'error' => 'Error ejecutando consulta'
                ];
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'personas_unidad' => $results,
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
     * Buscar por nombres (aproximado)
     */
    public function getByNombres(string $nombres): array 
    {
        try {
            // Para búsqueda por nombres, necesitamos hacer una búsqueda más compleja
            // debido a la encriptación. Una opción es obtener todos y filtrar.
            $allResults = $this->findMany();
            $matches = [];
            
            foreach ($allResults as $result) {
                $decryptedResult = $this->decryptSensitiveFields($result);
                
                // Buscar coincidencias en nombres
                if (stripos($decryptedResult['nombres'] ?? '', $nombres) !== false ||
                    stripos($decryptedResult['apellido1'] ?? '', $nombres) !== false ||
                    stripos($decryptedResult['apellido2'] ?? '', $nombres) !== false) {
                    $matches[] = $decryptedResult;
                }
            }
            
            return [
                'success' => true,
                'personas_unidad' => $matches,
                'total' => count($matches)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener personas activas
     */
    public function getActivos(): array 
    {
        try {
            // Obtener todas las personas (no hay campo activo en esta tabla)
            $results = $this->findMany();
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'personas_unidad' => $results,
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
     * Obtener estadísticas de personas unidad
     */
    public function obtenerEstadisticas(): array 
    {
        try {
            $total = $this->count();
            
            // Contar personas con teléfono 1
            $conTelefono1 = 0;
            // Contar personas con CURP
            $conCurp = 0;
            // Contar personas con foto
            $conFoto = 0;
            
            $allResults = $this->findMany();
            foreach ($allResults as $result) {
                if (!empty($result['telefono_1'])) $conTelefono1++;
                if (!empty($result['curp'])) $conCurp++;
                if (!empty($result['foto'])) $conFoto++;
            }
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total' => $total,
                    'con_telefono' => $conTelefono1,
                    'con_curp' => $conCurp,
                    'con_foto' => $conFoto,
                    'porcentaje_completos' => $total > 0 ? round(($conCurp / $total) * 100, 2) : 0
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // MÉTODOS AUXILIARES
    // ===========================================

    /**
     * Obtener nombre completo de persona
     */
    public function getNombreCompleto(int $id_persona_unidad): string 
    {
        try {
            $result = $this->read($id_persona_unidad);
            
            if (!$result['success']) {
                return '';
            }
            
            $persona = $result['persona_unidad'];
            $nombreCompleto = trim(
                ($persona['nombres'] ?? '') . ' ' . 
                ($persona['apellido1'] ?? '') . ' ' . 
                ($persona['apellido2'] ?? '')
            );
            
            return $nombreCompleto;
            
        } catch (Exception $e) {
            return '';
        }
    }
}
?>