<?php
/**
 * 🔧 MANTENIMIENTO FISICOS MODEL - Modelo de Mantenimiento Físico de Hardware
 * Manejo completo de mantenimientos físicos con encriptación de campos sensibles
 * Control de frecuencias, próximos servicios y observaciones
 * Integración con sistema de hardware y servicios
 * 
 * @package Cyberhole\Models\Cyberhole
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../Base-Model.php';

class MantenimientoFisicosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla mantenimiento_fisicos
    public ?int $id_servicio;
    public ?int $id_hardware;
    public ?int $frecuencia_dias;
    public ?string $proximo_servicio;
    public ?int $realizado;
    public ?string $observaciones;
    public ?string $fecha_creacion;

    public function __construct(
        ?int $id_servicio = null,
        ?int $id_hardware = null,
        ?int $frecuencia_dias = null,
        ?string $proximo_servicio = null,
        ?int $realizado = 0,
        ?string $observaciones = null,
        ?string $fecha_creacion = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'mantenimiento_fisicos';
        $this->primaryKey = 'id_servicio';
        $this->fillableFields = [
            'id_hardware', 'frecuencia_dias', 'proximo_servicio', 
            'realizado', 'observaciones'
        ];
        
        // Campos que se encriptan: observaciones por ser información sensible
        $this->encryptedFields = [
            'observaciones'
        ];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_servicio = $id_servicio;
        $this->id_hardware = $id_hardware;
        $this->frecuencia_dias = $frecuencia_dias;
        $this->proximo_servicio = $proximo_servicio;
        $this->realizado = $realizado ?? 0;
        $this->observaciones = $observaciones;
        $this->fecha_creacion = $fecha_creacion;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nuevo mantenimiento físico
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Calcular próximo servicio si no se proporciona
            if (!isset($data['proximo_servicio']) && isset($data['frecuencia_dias'])) {
                $data['proximo_servicio'] = $this->calcularProximoServicio($data['frecuencia_dias']);
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', [
                'id_servicio' => $id, 
                'id_hardware' => $data['id_hardware'] ?? null,
                'frecuencia_dias' => $data['frecuencia_dias'] ?? null
            ]);
            
            return [
                'success' => true,
                'id_servicio' => $id,
                'message' => 'Mantenimiento físico creado exitosamente'
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
     * Leer mantenimiento por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Mantenimiento físico no encontrado'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'mantenimiento' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar mantenimiento
     */
    public function updateMantenimiento(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Recalcular próximo servicio si cambia la frecuencia
            if (isset($data['frecuencia_dias']) && !isset($data['proximo_servicio'])) {
                $data['proximo_servicio'] = $this->calcularProximoServicio($data['frecuencia_dias']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el mantenimiento'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_servicio' => $id, 'data' => $data]);
            
            return [
                'success' => true,
                'message' => 'Mantenimiento actualizado exitosamente'
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
     * Eliminar mantenimiento
     */
    public function deleteMantenimiento(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el mantenimiento'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_servicio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Mantenimiento eliminado exitosamente'
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
    // MÉTODOS ESPECÍFICOS DE NEGOCIO
    // ===========================================

    /**
     * Buscar mantenimientos por hardware
     */
    public function buscarPorHardware(int $id_hardware): array 
    {
        try {
            $results = $this->findMany(['id_hardware' => $id_hardware]);
            
            // Desencriptar campos sensibles en todos los resultados
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'mantenimientos' => $results,
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
     * Buscar mantenimientos pendientes
     */
    public function buscarPendientes(): array 
    {
        try {
            $results = $this->findMany(['realizado' => 0]);
            
            // Desencriptar campos sensibles en todos los resultados
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'mantenimientos_pendientes' => $results,
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
     * Buscar mantenimientos realizados
     */
    public function buscarRealizados(): array 
    {
        try {
            $results = $this->findMany(['realizado' => 1]);
            
            // Desencriptar campos sensibles en todos los resultados
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'mantenimientos_realizados' => $results,
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
     * Buscar mantenimientos por fechas próximas
     */
    public function buscarProximosVencimientos(int $dias = 7): array 
    {
        try {
            $fecha_limite = date('Y-m-d', strtotime("+{$dias} days"));
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE proximo_servicio <= ? AND realizado = 0 
                    ORDER BY proximo_servicio ASC";
            
            $stmt = $this->executeQuery($sql, [$fecha_limite]);
            $results = $stmt->fetchAll();
            
            // Desencriptar campos sensibles en todos los resultados
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'proximos_vencimientos' => $results,
                'total' => count($results),
                'dias_limite' => $dias
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Marcar mantenimiento como realizado
     */
    public function marcarRealizado(int $id, string $observaciones = null): array 
    {
        try {
            $this->beginTransaction();
            
            $data = [
                'realizado' => 1
            ];
            
            if ($observaciones) {
                $data['observaciones'] = $observaciones;
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo marcar el mantenimiento como realizado'
                ];
            }
            
            // Programar próximo mantenimiento
            $mantenimiento = $this->findById($id);
            if ($mantenimiento && $mantenimiento['frecuencia_dias']) {
                $nuevo_proximo = $this->calcularProximoServicio($mantenimiento['frecuencia_dias']);
                $this->update($id, ['proximo_servicio' => $nuevo_proximo]);
            }
            
            $this->commit();
            $this->logActivity('marcar_realizado', [
                'id_servicio' => $id, 
                'observaciones' => $observaciones
            ]);
            
            return [
                'success' => true,
                'message' => 'Mantenimiento marcado como realizado exitosamente'
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
     * Obtener estadísticas de mantenimiento
     */
    public function obtenerEstadisticas(): array 
    {
        try {
            $total = $this->count();
            $pendientes = $this->count(['realizado' => 0]);
            $realizados = $this->count(['realizado' => 1]);
            
            // Contar próximos vencimientos (7 días)
            $fecha_limite = date('Y-m-d', strtotime('+7 days'));
            $sql = "SELECT COUNT(*) as total FROM {$this->tableName} 
                    WHERE proximo_servicio <= ? AND realizado = 0";
            $stmt = $this->executeQuery($sql, [$fecha_limite]);
            $proximos = $stmt->fetch()['total'];
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_mantenimientos' => $total,
                    'pendientes' => $pendientes,
                    'realizados' => $realizados,
                    'proximos_vencimientos_7_dias' => $proximos,
                    'porcentaje_realizados' => $total > 0 ? round(($realizados / $total) * 100, 2) : 0
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
     * Calcular próximo servicio basado en frecuencia
     */
    private function calcularProximoServicio(int $frecuencia_dias): string 
    {
        return date('Y-m-d', strtotime("+{$frecuencia_dias} days"));
    }

    /**
     * Validar datos de entrada
     */
    public function validarDatos(array $data): array 
    {
        $errors = [];
        
        // Validar id_hardware
        if (!isset($data['id_hardware']) || !is_numeric($data['id_hardware']) || $data['id_hardware'] <= 0) {
            $errors[] = 'ID de hardware es requerido y debe ser un número positivo';
        }
        
        // Validar frecuencia_dias
        if (!isset($data['frecuencia_dias']) || !is_numeric($data['frecuencia_dias']) || $data['frecuencia_dias'] <= 0) {
            $errors[] = 'Frecuencia en días es requerida y debe ser un número positivo';
        }
        
        // Validar formato de fecha si se proporciona
        if (isset($data['proximo_servicio']) && $data['proximo_servicio']) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['proximo_servicio']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['proximo_servicio']) {
                $errors[] = 'Formato de fecha próximo servicio inválido (debe ser Y-m-d)';
            }
        }
        
        // Validar realizado
        if (isset($data['realizado']) && !in_array($data['realizado'], [0, 1])) {
            $errors[] = 'Campo realizado debe ser 0 (pendiente) o 1 (realizado)';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Listar todos los mantenimientos con paginación
     */
    public function listarTodos(int $limit = 50, int $offset = 0): array 
    {
        try {
            $results = $this->findMany([], $limit, $offset);
            $total = $this->count();
            
            // Desencriptar campos sensibles en todos los resultados
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'mantenimientos' => $results,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // MÉTODOS DE VALIDACIÓN Y CONECTIVIDAD
    // ===========================================

    /**
     * Probar conectividad del modelo
     */
    public function probarConectividad(): array 
    {
        try {
            $conectividad = $this->testConnectivity();
            $total_registros = $this->count();
            
            return [
                'success' => true,
                'modelo' => 'MantenimientoFisicosModel',
                'tabla' => $this->tableName,
                'conectividad' => $conectividad,
                'total_registros' => $total_registros,
                'timestamp' => $this->getCurrentTimestamp()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
