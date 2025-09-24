<?php
/**
 * 🚗 ENGOMADO MODEL - Modelo de Engomados para Vehículos
 * Manejo completo de engomados con encriptación de placa/modelo
 * Compresión y encriptación de fotos
 * 
 * @package Cyberhole\Models\Dispositivos
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../base-model.php';

class EngomadoModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla engomados
    public ?int $id_engomado;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $placa;
    public ?string $modelo;
    public ?string $color;
    public ?int $anio;
    public ?string $foto;
    public ?int $activo;
    public ?string $creado_en;

    public function __construct(
        ?int $id_engomado = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $placa = null,
        ?string $modelo = null,
        ?string $color = null,
        ?int $anio = null,
        ?string $foto = null,
        ?int $activo = 1,
        ?string $creado_en = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'engomados';
        $this->primaryKey = 'id_engomado';
        $this->fillableFields = [
            'id_persona', 'id_casa', 'id_condominio', 'id_calle', 
            'placa', 'modelo', 'color', 'anio', 'foto', 'activo'
        ];
        
        // Campos que se encriptan: placa, modelo
        $this->encryptedFields = ['placa', 'modelo'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_engomado = $id_engomado;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->placa = $placa;
        $this->modelo = $modelo;
        $this->color = $color;
        $this->anio = $anio;
        $this->foto = $foto;
        $this->activo = $activo ?? 1;
        $this->creado_en = $creado_en;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nuevo engomado
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
            
            $this->commit();
            $this->logActivity('create', ['id_engomado' => $id]);
            
            return [
                'success' => true,
                'id_engomado' => $id,
                'message' => 'Engomado creado exitosamente'
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
     * Leer engomado por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Engomado no encontrado'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'engomado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar engomado
     */
    public function updateEngomado(int $id, array $data): array 
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
                    'error' => 'No se pudo actualizar el engomado'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_engomado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Engomado actualizado exitosamente'
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
     * Eliminar engomado
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
                    'error' => 'No se pudo eliminar el engomado'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_engomado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Engomado eliminado exitosamente'
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
    public function recuperarFoto(int $id_engomado, string $outputPath = null): array 
    {
        try {
            $engomado = $this->findById($id_engomado);
            
            if (!$engomado || !$engomado['foto']) {
                return [
                    'success' => false,
                    'error' => 'Engomado o foto no encontrada'
                ];
            }
            
            // La foto ya viene desencriptada por BaseModel::findById
            $fotoData = $engomado['foto'];
            
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
     * Obtener engomados por ID de persona
     */
    public function getByPersona(int $id_persona): array 
    {
        try {
            $results = $this->findMany(['id_persona' => $id_persona]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados por ID de casa
     */
    public function getByCasa(int $id_casa): array 
    {
        try {
            $results = $this->findMany(['id_casa' => $id_casa]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados por ID de calle
     */
    public function getByCalle(int $id_calle): array 
    {
        try {
            $results = $this->findMany(['id_calle' => $id_calle]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados por ID de condominio
     */
    public function getByCondominio(int $id_condominio): array 
    {
        try {
            $results = $this->findMany(['id_condominio' => $id_condominio]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados activos
     */
    public function getActivos(): array 
    {
        try {
            $results = $this->findMany(['activo' => 1]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados inactivos
     */
    public function getInactivos(): array 
    {
        try {
            $results = $this->findMany(['activo' => 0]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados activos por calle
     */
    public function getActivosByCalle(int $id_calle): array 
    {
        try {
            $results = $this->findMany(['id_calle' => $id_calle, 'activo' => 1]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Obtener engomados activos por condominio
     */
    public function getActivosByCondominio(int $id_condominio): array 
    {
        try {
            $results = $this->findMany(['id_condominio' => $id_condominio, 'activo' => 1]);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'engomados' => $results,
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
     * Cambiar estado activo/inactivo
     */
    public function setActivo(int $id, bool $activo): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id, ['activo' => $activo ? 1 : 0]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estado'
                ];
            }
            
            $this->commit();
            $this->logActivity('set_activo', ['id_engomado' => $id, 'activo' => $activo]);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
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
     * Obtener estadísticas de engomados
     */
    public function obtenerEstadisticas(int $id_condominio = null): array 
    {
        try {
            $conditions = [];
            if ($id_condominio) {
                $conditions['id_condominio'] = $id_condominio;
            }
            
            $total = $this->count($conditions);
            
            $conditions['activo'] = 1;
            $activos = $this->count($conditions);
            
            $conditions['activo'] = 0;
            $inactivos = $this->count($conditions);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total' => $total,
                    'activos' => $activos,
                    'inactivos' => $inactivos,
                    'porcentaje_activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
