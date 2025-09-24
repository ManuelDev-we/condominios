<?php
/**
 * 👥 ACCESO EMPLEADO MODEL - Modelo de Accesos de Empleados
 * Manejo de accesos de empleados con control por condominio
 * Limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class AccesoEmpleadoModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla accesos_empleados
    public ?int $id_acceso;
    public ?int $id_empleado;
    public ?int $id_condominio;
    public ?string $id_acceso_empleado;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_acceso = null,
        ?int $id_empleado = null,
        ?int $id_condominio = null,
        ?string $id_acceso_empleado = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'accesos_empleados';
        $this->primaryKey = 'id_acceso';
        $this->fillableFields = [
            'id_empleado', 'id_condominio', 'id_acceso_empleado', 
            'fecha_hora_entrada', 'fecha_hora_salida'
        ];
        
        // No hay campos sensibles que encriptar en esta tabla
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_acceso = $id_acceso;
        $this->id_empleado = $id_empleado;
        $this->id_condominio = $id_condominio;
        $this->id_acceso_empleado = $id_acceso_empleado;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo acceso de empleado
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el condominio existe
            if (!$this->verificarCondominio($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El condominio especificado no existe'
                ];
            }
            
            // Limpiar datos antiguos antes de insertar
            $this->limpiarDatosAntiguos($data['id_condominio']);
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear el acceso de empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'id_acceso' => $id,
                'message' => 'Acceso de empleado creado exitosamente'
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
     * Leer acceso de empleado por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Acceso de empleado no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'acceso_empleado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar acceso de empleado
     */
    public function updateAccesoEmpleado(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el acceso existe
            $acceso = $this->findById($id);
            if (!$acceso) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Acceso de empleado no encontrado'
                ];
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el acceso de empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'message' => 'Acceso de empleado actualizado exitosamente'
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
     * Eliminar acceso de empleado
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
                    'error' => 'No se pudo eliminar el acceso de empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'message' => 'Acceso de empleado eliminado exitosamente'
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
    // GETTERS Y SETTERS POR CONDOMINIO
    // ===========================================

    /**
     * Obtener accesos de empleados por condominio (paginado de 10 en 10)
     */
    public function getAccesosEmpleadosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $results = $this->findMany($conditions, $limit, $offset);
            
            // Contar total
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'accesos_empleados' => $results,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener un acceso específico de empleado por ID y condominio
     */
    public function getAccesoEmpleadoByIdCondominio(int $id_acceso, int $id_condominio): array 
    {
        try {
            $conditions = [
                'id_acceso' => $id_acceso,
                'id_condominio' => $id_condominio
            ];
            
            $result = $this->findOne($conditions);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Acceso de empleado no encontrado en el condominio especificado'
                ];
            }
            
            return [
                'success' => true,
                'acceso_empleado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener accesos por empleado en un condominio
     */
    public function getAccesosByEmpleadoCondominio(int $id_empleado, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'id_empleado' => $id_empleado,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'accesos_empleados' => $results,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener accesos activos de empleados (sin salida) por condominio
     */
    public function getAccesosEmpleadosActivosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_hora_salida IS NULL 
                    ORDER BY fecha_hora_entrada DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total de activos
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? AND fecha_hora_salida IS NULL";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'accesos_empleados_activos' => $results,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener accesos por identificador único de empleado en condominio
     */
    public function getAccesosByIdAccesoEmpleadoCondominio(string $id_acceso_empleado, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'id_acceso_empleado' => $id_acceso_empleado,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'accesos_empleados' => $results,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Registrar salida de un empleado
     */
    public function registrarSalidaEmpleado(int $id_acceso, int $id_condominio): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el acceso existe y está activo
            $acceso = $this->findOne([
                'id_acceso' => $id_acceso,
                'id_condominio' => $id_condominio
            ]);
            
            if (!$acceso) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Acceso de empleado no encontrado'
                ];
            }
            
            if ($acceso['fecha_hora_salida'] !== null) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El acceso ya tiene fecha de salida registrada'
                ];
            }
            
            // Registrar salida
            $updated = $this->update($id_acceso, [
                'fecha_hora_salida' => $this->getCurrentTimestamp()
            ]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo registrar la salida del empleado'
                ];
            }
            
            $this->commit();
            $this->logActivity('salida_empleado', ['id_acceso' => $id_acceso]);
            
            return [
                'success' => true,
                'message' => 'Salida de empleado registrada exitosamente'
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
    // MÉTODOS AUXILIARES
    // ===========================================

    /**
     * Verificar que el condominio existe
     */
    private function verificarCondominio(int $id_condominio): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM condominios WHERE id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Limpiar datos antiguos (más de 8 años)
     */
    private function limpiarDatosAntiguos(int $id_condominio): void 
    {
        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime('-8 years'));
            
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_hora_entrada < ?";
            
            $this->executeQuery($sql, [$id_condominio, $fechaLimite]);
            
            $this->logActivity('cleanup_empleados', [
                'id_condominio' => $id_condominio,
                'fecha_limite' => $fechaLimite
            ]);
            
        } catch (Exception $e) {
            error_log("Error limpiando datos antiguos de empleados: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de accesos de empleados por condominio
     */
    public function obtenerEstadisticasEmpleadosCondominio(int $id_condominio): array 
    {
        try {
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar accesos activos
            $sqlActivos = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? AND fecha_hora_salida IS NULL";
            $stmtActivos = $this->executeQuery($sqlActivos, [$id_condominio]);
            $activosResult = $stmtActivos->fetch(PDO::FETCH_ASSOC);
            $activos = $activosResult['count'];
            
            // Contar empleados únicos
            $sqlUnicos = "SELECT COUNT(DISTINCT id_empleado) as count FROM {$this->tableName} 
                         WHERE id_condominio = ?";
            $stmtUnicos = $this->executeQuery($sqlUnicos, [$id_condominio]);
            $unicosResult = $stmtUnicos->fetch(PDO::FETCH_ASSOC);
            $empleadosUnicos = $unicosResult['count'];
            
            // Promedio de accesos por empleado
            $promedioAccesos = $empleadosUnicos > 0 ? round($total / $empleadosUnicos, 2) : 0;
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_accesos_empleados' => $total,
                    'accesos_empleados_activos' => $activos,
                    'empleados_unicos' => $empleadosUnicos,
                    'promedio_accesos_por_empleado' => $promedioAccesos,
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

    /**
     * Obtener reporte de horas trabajadas por empleado en condominio
     */
    public function getReporteHorasEmpleadoCondominio(int $id_empleado, int $id_condominio, string $fecha_inicio = null, string $fecha_fin = null): array 
    {
        try {
            // Fechas por defecto: último mes
            if (!$fecha_inicio) {
                $fecha_inicio = date('Y-m-d', strtotime('-1 month'));
            }
            if (!$fecha_fin) {
                $fecha_fin = date('Y-m-d');
            }
            
            $sql = "SELECT *, 
                           CASE 
                               WHEN fecha_hora_salida IS NOT NULL 
                               THEN TIMESTAMPDIFF(MINUTE, fecha_hora_entrada, fecha_hora_salida) 
                               ELSE NULL 
                           END as minutos_trabajados
                    FROM {$this->tableName} 
                    WHERE id_empleado = ? AND id_condominio = ? 
                    AND DATE(fecha_hora_entrada) BETWEEN ? AND ?
                    ORDER BY fecha_hora_entrada DESC";
            
            $stmt = $this->executeQuery($sql, [$id_empleado, $id_condominio, $fecha_inicio, $fecha_fin]);
            $accesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totales
            $totalMinutos = 0;
            $totalSesiones = count($accesos);
            $sesionesCompletas = 0;
            
            foreach ($accesos as $acceso) {
                if ($acceso['minutos_trabajados'] !== null) {
                    $totalMinutos += $acceso['minutos_trabajados'];
                    $sesionesCompletas++;
                }
            }
            
            $totalHoras = round($totalMinutos / 60, 2);
            $promedioHorasPorSesion = $sesionesCompletas > 0 ? round($totalHoras / $sesionesCompletas, 2) : 0;
            
            return [
                'success' => true,
                'reporte' => [
                    'id_empleado' => $id_empleado,
                    'id_condominio' => $id_condominio,
                    'periodo' => [
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin
                    ],
                    'accesos' => $accesos,
                    'resumen' => [
                        'total_sesiones' => $totalSesiones,
                        'sesiones_completas' => $sesionesCompletas,
                        'sesiones_abiertas' => $totalSesiones - $sesionesCompletas,
                        'total_horas' => $totalHoras,
                        'promedio_horas_por_sesion' => $promedioHorasPorSesion
                    ]
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
?>
