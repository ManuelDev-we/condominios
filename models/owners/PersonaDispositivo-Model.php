<?php
/**
 * 📱 PERSONA DISPOSITIVO MODEL - Gestión de Dispositivos de Personas
 * Modelo para gestionar la relación entre personas y dispositivos (tags/engomados)
 * Sin encriptación - información siempre visible por requerimientos de control de acceso
 * 
 * @package Cyberhole\Models\Owners
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class PersonaDispositivo extends BaseModel 
{
    // ========================================
    // PROPIEDADES PÚBLICAS DE LA CLASE
    // ========================================
    
    public $id_persona_dispositivo;
    public $id_persona_unidad; 
    public $tipo_dispositivo;
    public $id_dispositivo;
    public $creado_en;
    
    // Configuración del modelo
    protected $tableName = 'persona_dispositivo';
    protected $primaryKey = 'id_persona_dispositivo';
    protected $fillableFields = [
        'id_persona_unidad', 
        'tipo_dispositivo', 
        'id_dispositivo'
    ];
    
    // No campos encriptados - información siempre visible
    protected $encryptedFields = [];
    
    // Tipos de dispositivo válidos
    private $tiposDispositivo = ['tag', 'engomado'];
    
    /**
     * Constructor parametrizado - Inicializar todas las propiedades
     */
    public function __construct(
        ?int $id_persona_dispositivo = null,
        ?int $id_persona_unidad = null,
        ?string $tipo_dispositivo = null,
        ?int $id_dispositivo = null,
        ?string $creado_en = null
    ) {
        parent::__construct();
        
        $this->id_persona_dispositivo = $id_persona_dispositivo;
        $this->id_persona_unidad = $id_persona_unidad;
        $this->tipo_dispositivo = $tipo_dispositivo;
        $this->id_dispositivo = $id_dispositivo;
        $this->creado_en = $creado_en;
    }
    
    // ========================================
    // MÉTODOS CRUD PRINCIPALES
    // ========================================
    
    /**
     * Crear nueva relación persona-dispositivo
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas obligatorias
            if (empty($data['id_persona_unidad'])) {
                return ['success' => false, 'error' => 'El ID de persona unidad es obligatorio'];
            }
            
            if (empty($data['tipo_dispositivo'])) {
                return ['success' => false, 'error' => 'El tipo de dispositivo es obligatorio'];
            }
            
            if (empty($data['id_dispositivo'])) {
                return ['success' => false, 'error' => 'El ID del dispositivo es obligatorio'];
            }
            
            // Validar tipo de dispositivo
            if (!$this->isValidTipoDispositivo($data['tipo_dispositivo'])) {
                return [
                    'success' => false, 
                    'error' => 'Tipo de dispositivo no válido. Debe ser: ' . implode(', ', $this->tiposDispositivo)
                ];
            }
            
            // Verificar si ya existe la combinación
            if ($this->relacionExists($data['id_persona_unidad'], $data['tipo_dispositivo'], $data['id_dispositivo'])) {
                return [
                    'success' => false, 
                    'error' => 'Ya existe esta relación persona-dispositivo'
                ];
            }
            
            // Preparar datos para inserción
            $insertData = [
                'id_persona_unidad' => (int) $data['id_persona_unidad'],
                'tipo_dispositivo' => $data['tipo_dispositivo'],
                'id_dispositivo' => (int) $data['id_dispositivo']
            ];
            
            // Insertar registro
            $insertedId = $this->insert($insertData);
            
            if ($insertedId > 0) {
                // Log de actividad
                $this->logActivity('create', [
                    'id_persona_dispositivo' => $insertedId,
                    'id_persona_unidad' => $insertData['id_persona_unidad'],
                    'tipo_dispositivo' => $insertData['tipo_dispositivo'],
                    'id_dispositivo' => $insertData['id_dispositivo']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Relación persona-dispositivo creada exitosamente',
                    'id_persona_dispositivo' => $insertedId,
                    'data' => $insertData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al insertar en base de datos'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en PersonaDispositivo->create(): " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Leer relación persona-dispositivo por ID
     */
    public function readDispositivo(int $id_persona_dispositivo): array 
    {
        try {
            $sql = "SELECT id_persona_dispositivo, id_persona_unidad, tipo_dispositivo, 
                           id_dispositivo, creado_en 
                    FROM persona_dispositivo 
                    WHERE id_persona_dispositivo = ?";
            
            $stmt = $this->executeQuery($sql, [$id_persona_dispositivo]);
            $resultado = $stmt->fetch();
            
            if ($resultado) {
                return [
                    'success' => true,
                    'data' => $resultado,
                    'message' => 'Relación encontrada'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Relación persona-dispositivo no encontrada',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar relación: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Leer dispositivos por persona unidad y tipo
     */
    public function readByPersonaYTipo(int $id_persona_unidad, string $tipo_dispositivo): array 
    {
        try {
            // Validar tipo de dispositivo
            if (!$this->isValidTipoDispositivo($tipo_dispositivo)) {
                return [
                    'success' => false,
                    'error' => 'Tipo de dispositivo no válido',
                    'data' => []
                ];
            }
            
            $sql = "SELECT id_persona_dispositivo, id_persona_unidad, tipo_dispositivo, 
                           id_dispositivo, creado_en 
                    FROM persona_dispositivo 
                    WHERE id_persona_unidad = ? AND tipo_dispositivo = ?
                    ORDER BY creado_en DESC";
            
            $stmt = $this->executeQuery($sql, [$id_persona_unidad, $tipo_dispositivo]);
            $resultados = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $resultados,
                'count' => count($resultados),
                'message' => 'Dispositivos encontrados: ' . count($resultados)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar dispositivos: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Leer todos los dispositivos de una persona unidad
     */
    public function readByPersonaUnidad(int $id_persona_unidad): array 
    {
        try {
            $sql = "SELECT id_persona_dispositivo, id_persona_unidad, tipo_dispositivo, 
                           id_dispositivo, creado_en 
                    FROM persona_dispositivo 
                    WHERE id_persona_unidad = ?
                    ORDER BY tipo_dispositivo, creado_en DESC";
            
            $stmt = $this->executeQuery($sql, [$id_persona_unidad]);
            $resultados = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $resultados,
                'count' => count($resultados),
                'message' => 'Dispositivos encontrados: ' . count($resultados)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar dispositivos: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Eliminar relación persona-dispositivo
     */
    public function deleteDispositivo(int $id_persona_dispositivo): array 
    {
        try {
            // Verificar que existe antes de eliminar
            $existingRecord = $this->readDispositivo($id_persona_dispositivo);
            if (!$existingRecord['success']) {
                return [
                    'success' => false,
                    'error' => 'La relación persona-dispositivo no existe'
                ];
            }
            
            $sql = "DELETE FROM persona_dispositivo WHERE id_persona_dispositivo = ?";
            $stmt = $this->executeQuery($sql, [$id_persona_dispositivo]);
            
            if ($stmt->rowCount() > 0) {
                // Log de actividad
                $this->logActivity('delete', [
                    'id_persona_dispositivo' => $id_persona_dispositivo,
                    'deleted_data' => $existingRecord['data']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Relación persona-dispositivo eliminada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la relación'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al eliminar relación: ' . $e->getMessage()
            ];
        }
    }
    
    // ========================================
    // MÉTODOS DE VALIDACIÓN Y UTILIDAD
    // ========================================
    
    /**
     * Validar tipo de dispositivo
     */
    private function isValidTipoDispositivo(string $tipo): bool 
    {
        return in_array($tipo, $this->tiposDispositivo);
    }
    
    /**
     * Verificar si existe la relación
     */
    private function relacionExists(int $id_persona_unidad, string $tipo_dispositivo, int $id_dispositivo): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM persona_dispositivo 
                    WHERE id_persona_unidad = ? AND tipo_dispositivo = ? AND id_dispositivo = ?";
            
            $stmt = $this->executeQuery($sql, [$id_persona_unidad, $tipo_dispositivo, $id_dispositivo]);
            $result = $stmt->fetch();
            
            return (int) $result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("Error verificando relación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si existe relación por ID
     */
    public function dispositivoExists(int $id_persona_dispositivo): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM persona_dispositivo WHERE id_persona_dispositivo = ?";
            $stmt = $this->executeQuery($sql, [$id_persona_dispositivo]);
            $result = $stmt->fetch();
            return (int) $result['total'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Contar dispositivos por persona y tipo
     */
    public function countDispositivosByTipo(int $id_persona_unidad, string $tipo_dispositivo): int 
    {
        try {
            if (!$this->isValidTipoDispositivo($tipo_dispositivo)) {
                return 0;
            }
            
            $sql = "SELECT COUNT(*) as total 
                    FROM persona_dispositivo 
                    WHERE id_persona_unidad = ? AND tipo_dispositivo = ?";
            
            $stmt = $this->executeQuery($sql, [$id_persona_unidad, $tipo_dispositivo]);
            $result = $stmt->fetch();
            
            return (int) $result['total'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obtener tipos de dispositivo válidos
     */
    public function getTiposDispositivo(): array 
    {
        return $this->tiposDispositivo;
    }
    
    /**
     * Estadísticas de dispositivos por persona
     */
    public function getEstadisticasPersona(int $id_persona_unidad): array 
    {
        try {
            $sql = "SELECT tipo_dispositivo, COUNT(*) as cantidad 
                    FROM persona_dispositivo 
                    WHERE id_persona_unidad = ?
                    GROUP BY tipo_dispositivo";
            
            $stmt = $this->executeQuery($sql, [$id_persona_unidad]);
            $resultados = $stmt->fetchAll();
            
            $estadisticas = [
                'tag' => 0,
                'engomado' => 0,
                'total' => 0
            ];
            
            foreach ($resultados as $row) {
                $estadisticas[$row['tipo_dispositivo']] = (int) $row['cantidad'];
                $estadisticas['total'] += (int) $row['cantidad'];
            }
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error obteniendo estadísticas: ' . $e->getMessage(),
                'data' => ['tag' => 0, 'engomado' => 0, 'total' => 0]
            ];
        }
    }
}
