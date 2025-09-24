<?php
/**
 * 📅 SUSCRIPCIONES MODEL - Modelo de Suscripciones
 * Manejo completo de suscripciones con búsquedas por condominio
 * Gestión de estados activos/inactivos y fechas de vigencia
 * 
 * @package Cyberhole\Models\Cyberhole
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../Base-Model.php';

class SuscripcionesModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla suscripcion
    public ?int $id_suscripcion;
    public ?int $id_condominio;
    public ?int $activo;
    public ?string $fecha_inicio;
    public ?string $fecha_fin;

    public function __construct(
        ?int $id_suscripcion = null,
        ?int $id_condominio = null,
        ?int $activo = 1,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'suscripcion';
        $this->primaryKey = 'id_suscripcion';
        $this->fillableFields = [
            'id_condominio', 'activo', 'fecha_inicio', 'fecha_fin'
        ];
        
        // No hay campos encriptados en suscripciones
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_suscripcion = $id_suscripcion;
        $this->id_condominio = $id_condominio;
        $this->activo = $activo ?? 1;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nueva suscripción
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar datos de la suscripción
            $validacion = $this->validarDatosSuscripcion($data);
            if (!$validacion['valid']) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => $validacion['error']
                ];
            }
            
            // Verificar si ya existe una suscripción activa para el condominio
            $suscripcionExistente = $this->getSuscripcionActivaByCondominio($data['id_condominio']);
            if ($suscripcionExistente['success'] && !empty($suscripcionExistente['suscripcion'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Ya existe una suscripción activa para este condominio'
                ];
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_suscripcion' => $id, 'id_condominio' => $data['id_condominio']]);
            
            return [
                'success' => true,
                'id_suscripcion' => $id,
                'message' => 'Suscripción creada exitosamente'
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
     * Leer suscripción por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Suscripción no encontrada'
                ];
            }
            
            // Añadir información adicional
            $result['dias_restantes'] = $this->calcularDiasRestantes($result['fecha_fin']);
            $result['estado_suscripcion'] = $this->determinarEstadoSuscripcion($result);
            
            return [
                'success' => true,
                'suscripcion' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar suscripción
     */
    public function updateSuscripcion(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar datos de la suscripción
            $validacion = $this->validarDatosSuscripcion($data, true);
            if (!$validacion['valid']) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => $validacion['error']
                ];
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la suscripción'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_suscripcion' => $id]);
            
            return [
                'success' => true,
                'message' => 'Suscripción actualizada exitosamente'
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
     * Eliminar suscripción
     */
    public function deleteSuscripcion(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la suscripción'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_suscripción' => $id]);
            
            return [
                'success' => true,
                'message' => 'Suscripción eliminada exitosamente'
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
    // GETTERS Y SETTERS
    // ===========================================

    public function getId_suscripcion(): ?int 
    {
        return $this->id_suscripcion;
    }

    public function setId_suscripcion(?int $id_suscripcion): void 
    {
        $this->id_suscripcion = $id_suscripcion;
    }

    public function getId_condominio(): ?int 
    {
        return $this->id_condominio;
    }

    public function setId_condominio(?int $id_condominio): void 
    {
        $this->id_condominio = $id_condominio;
    }

    public function getActivo(): ?int 
    {
        return $this->activo;
    }

    public function setActivo(?int $activo): void 
    {
        $this->activo = $activo;
    }

    public function getFecha_inicio(): ?string 
    {
        return $this->fecha_inicio;
    }

    public function setFecha_inicio(?string $fecha_inicio): void 
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getFecha_fin(): ?string 
    {
        return $this->fecha_fin;
    }

    public function setFecha_fin(?string $fecha_fin): void 
    {
        $this->fecha_fin = $fecha_fin;
    }

    // ===========================================
    // BÚSQUEDAS ESPECÍFICAS
    // ===========================================

    /**
     * Obtener suscripciones por condominio
     */
    public function getSuscripcionesByCondominio(int $id_condominio, bool $solo_activas = false): array 
    {
        try {
            $sql = "SELECT s.*, 
                           DATEDIFF(s.fecha_fin, NOW()) as dias_restantes,
                           CASE 
                               WHEN s.activo = 0 THEN 'Inactiva'
                               WHEN s.fecha_fin < NOW() THEN 'Vencida'
                               WHEN DATEDIFF(s.fecha_fin, NOW()) <= 7 THEN 'Por vencer'
                               ELSE 'Activa'
                           END as estado_suscripcion
                    FROM {$this->tableName} s
                    WHERE s.id_condominio = ?";
            
            $params = [$id_condominio];
            
            if ($solo_activas) {
                $sql .= " AND s.activo = 1 AND s.fecha_fin > NOW()";
            }
            
            $sql .= " ORDER BY s.fecha_inicio DESC";
            
            $stmt = $this->executeQuery($sql, $params);
            $suscripciones = $stmt->fetchAll();
            
            return [
                'success' => true,
                'suscripciones' => $suscripciones,
                'total_suscripciones' => count($suscripciones),
                'id_condominio' => $id_condominio
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener suscripción activa por condominio
     */
    public function getSuscripcionActivaByCondominio(int $id_condominio): array 
    {
        try {
            $sql = "SELECT s.*, 
                           DATEDIFF(s.fecha_fin, NOW()) as dias_restantes
                    FROM {$this->tableName} s
                    WHERE s.id_condominio = ? 
                    AND s.activo = 1 
                    AND s.fecha_fin > NOW()
                    ORDER BY s.fecha_fin DESC
                    LIMIT 1";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $suscripcion = $stmt->fetch();
            
            if (!$suscripcion) {
                return [
                    'success' => true,
                    'suscripcion' => null,
                    'message' => 'No hay suscripción activa para este condominio'
                ];
            }
            
            $suscripcion['estado_suscripcion'] = $this->determinarEstadoSuscripcion($suscripcion);
            
            return [
                'success' => true,
                'suscripcion' => $suscripcion
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener suscripciones próximas a vencer
     */
    public function getSuscripcionesProximasVencer(int $dias_aviso = 7): array 
    {
        try {
            $sql = "SELECT s.*, 
                           DATEDIFF(s.fecha_fin, NOW()) as dias_restantes
                    FROM {$this->tableName} s
                    WHERE s.activo = 1 
                    AND s.fecha_fin > NOW()
                    AND DATEDIFF(s.fecha_fin, NOW()) <= ?
                    ORDER BY s.fecha_fin ASC";
            
            $stmt = $this->executeQuery($sql, [$dias_aviso]);
            $suscripciones = $stmt->fetchAll();
            
            return [
                'success' => true,
                'suscripciones_por_vencer' => $suscripciones,
                'total_por_vencer' => count($suscripciones),
                'dias_aviso' => $dias_aviso
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener suscripciones vencidas
     */
    public function getSuscripcionesVencidas(): array 
    {
        try {
            $sql = "SELECT s.*, 
                           ABS(DATEDIFF(NOW(), s.fecha_fin)) as dias_vencida
                    FROM {$this->tableName} s
                    WHERE s.activo = 1 
                    AND s.fecha_fin < NOW()
                    ORDER BY s.fecha_fin ASC";
            
            $stmt = $this->executeQuery($sql);
            $suscripciones = $stmt->fetchAll();
            
            return [
                'success' => true,
                'suscripciones_vencidas' => $suscripciones,
                'total_vencidas' => count($suscripciones)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todas las suscripciones activas
     */
    public function getAllSuscripcionesActivas(): array 
    {
        try {
            $sql = "SELECT s.*, 
                           DATEDIFF(s.fecha_fin, NOW()) as dias_restantes,
                           CASE 
                               WHEN s.fecha_fin < NOW() THEN 'Vencida'
                               WHEN DATEDIFF(s.fecha_fin, NOW()) <= 7 THEN 'Por vencer'
                               ELSE 'Activa'
                           END as estado_suscripcion
                    FROM {$this->tableName} s
                    WHERE s.activo = 1
                    ORDER BY s.fecha_fin ASC";
            
            $stmt = $this->executeQuery($sql);
            $suscripciones = $stmt->fetchAll();
            
            return [
                'success' => true,
                'suscripciones_activas' => $suscripciones,
                'total_activas' => count($suscripciones)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // MÉTODOS ESPECIALIZADOS
    // ===========================================

    /**
     * Activar suscripción
     */
    public function activarSuscripcion(int $id_suscripcion): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id_suscripcion, ['activo' => 1]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo activar la suscripción'
                ];
            }
            
            $this->commit();
            $this->logActivity('activate', ['id_suscripcion' => $id_suscripcion]);
            
            return [
                'success' => true,
                'message' => 'Suscripción activada exitosamente'
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
     * Desactivar suscripción
     */
    public function desactivarSuscripcion(int $id_suscripcion): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id_suscripcion, ['activo' => 0]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo desactivar la suscripción'
                ];
            }
            
            $this->commit();
            $this->logActivity('deactivate', ['id_suscripcion' => $id_suscripcion]);
            
            return [
                'success' => true,
                'message' => 'Suscripción desactivada exitosamente'
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
     * Renovar suscripción
     */
    public function renovarSuscripcion(int $id_suscripcion, int $dias_extension): array 
    {
        try {
            $this->beginTransaction();
            
            $suscripcion = $this->read($id_suscripcion);
            if (!$suscripcion['success']) {
                $this->rollback();
                return $suscripcion;
            }
            
            $fechaActual = $suscripcion['suscripcion']['fecha_fin'];
            $nuevaFechaFin = date('Y-m-d H:i:s', strtotime($fechaActual . " + {$dias_extension} days"));
            
            $updated = $this->update($id_suscripcion, [
                'fecha_fin' => $nuevaFechaFin,
                'activo' => 1
            ]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo renovar la suscripción'
                ];
            }
            
            $this->commit();
            $this->logActivity('renew', [
                'id_suscripcion' => $id_suscripcion,
                'dias_extension' => $dias_extension,
                'nueva_fecha_fin' => $nuevaFechaFin
            ]);
            
            return [
                'success' => true,
                'message' => 'Suscripción renovada exitosamente',
                'nueva_fecha_fin' => $nuevaFechaFin,
                'dias_agregados' => $dias_extension
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
     * Obtener reporte de suscripciones
     */
    public function getReporteSuscripciones(): array 
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_suscripciones,
                        SUM(CASE WHEN activo = 1 AND fecha_fin > NOW() THEN 1 ELSE 0 END) as activas_vigentes,
                        SUM(CASE WHEN activo = 1 AND fecha_fin < NOW() THEN 1 ELSE 0 END) as activas_vencidas,
                        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivas,
                        SUM(CASE WHEN activo = 1 AND fecha_fin > NOW() AND DATEDIFF(fecha_fin, NOW()) <= 7 THEN 1 ELSE 0 END) as por_vencer,
                        AVG(DATEDIFF(fecha_fin, fecha_inicio)) as duracion_promedio
                    FROM {$this->tableName}";
            
            $stmt = $this->executeQuery($sql);
            $reporte = $stmt->fetch();
            
            return [
                'success' => true,
                'reporte' => [
                    'total_suscripciones' => (int) $reporte['total_suscripciones'],
                    'activas_vigentes' => (int) $reporte['activas_vigentes'],
                    'activas_vencidas' => (int) $reporte['activas_vencidas'],
                    'inactivas' => (int) $reporte['inactivas'],
                    'por_vencer_7_dias' => (int) $reporte['por_vencer'],
                    'duracion_promedio_dias' => round($reporte['duracion_promedio'], 0)
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
     * Validar datos de la suscripción
     */
    private function validarDatosSuscripcion(array $data, bool $esActualizacion = false): array 
    {
        $errores = [];
        
        // Validar id_condominio
        if (!$esActualizacion || isset($data['id_condominio'])) {
            if (empty($data['id_condominio']) || !is_numeric($data['id_condominio'])) {
                $errores[] = 'El ID del condominio es requerido y debe ser numérico';
            }
        }
        
        // Validar activo
        if (isset($data['activo'])) {
            if (!in_array($data['activo'], [0, 1])) {
                $errores[] = 'El campo activo debe ser 0 o 1';
            }
        }
        
        // Validar fechas
        if (isset($data['fecha_inicio']) && !empty($data['fecha_inicio'])) {
            if (!$this->validarFecha($data['fecha_inicio'])) {
                $errores[] = 'La fecha de inicio no tiene un formato válido';
            }
        }
        
        if (isset($data['fecha_fin']) && !empty($data['fecha_fin'])) {
            if (!$this->validarFecha($data['fecha_fin'])) {
                $errores[] = 'La fecha de fin no tiene un formato válido';
            }
            
            // Validar que fecha_fin sea posterior a fecha_inicio
            if (isset($data['fecha_inicio']) && !empty($data['fecha_inicio'])) {
                if (strtotime($data['fecha_fin']) <= strtotime($data['fecha_inicio'])) {
                    $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
                }
            }
        }
        
        return [
            'valid' => empty($errores),
            'error' => implode(', ', $errores)
        ];
    }

    /**
     * Validar formato de fecha
     */
    private function validarFecha(string $fecha): bool 
    {
        return date('Y-m-d H:i:s', strtotime($fecha)) === $fecha || 
               date('Y-m-d', strtotime($fecha)) === $fecha;
    }

    /**
     * Calcular días restantes
     */
    private function calcularDiasRestantes(?string $fecha_fin): ?int 
    {
        if (!$fecha_fin) {
            return null;
        }
        
        $fechaFin = strtotime($fecha_fin);
        $fechaActual = time();
        
        return ceil(($fechaFin - $fechaActual) / (24 * 60 * 60));
    }

    /**
     * Determinar estado de la suscripción
     */
    private function determinarEstadoSuscripcion(array $suscripcion): string 
    {
        if ($suscripcion['activo'] == 0) {
            return 'Inactiva';
        }
        
        if (!$suscripcion['fecha_fin']) {
            return 'Sin fecha de vencimiento';
        }
        
        $diasRestantes = $this->calcularDiasRestantes($suscripcion['fecha_fin']);
        
        if ($diasRestantes < 0) {
            return 'Vencida';
        } elseif ($diasRestantes <= 7) {
            return 'Por vencer';
        } else {
            return 'Activa';
        }
    }
}
?>