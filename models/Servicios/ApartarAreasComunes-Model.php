<?php
/**
 * 🏢 APARTAR AREAS COMUNES MODEL - Modelo de Apartado de Áreas Comunes
 * Manejo de reservas de áreas comunes con control por condominio
 * Limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class ApartarAreasComunesModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla apartar_areas_comunes
    public ?int $id_apartado;
    public ?int $id_area_comun;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_casa;
    public ?string $fecha_apartado;
    public ?string $descripcion;

    public function __construct(
        ?int $id_apartado = null,
        ?int $id_area_comun = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null,
        ?string $fecha_apartado = null,
        ?string $descripcion = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'apartar_areas_comunes';
        $this->primaryKey = 'id_apartado';
        $this->fillableFields = [
            'id_area_comun', 'id_condominio', 'id_calle', 'id_casa', 
            'fecha_apartado', 'descripcion'
        ];
        
        // No hay campos sensibles que encriptar en esta tabla
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_apartado = $id_apartado;
        $this->id_area_comun = $id_area_comun;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->fecha_apartado = $fecha_apartado;
        $this->descripcion = $descripcion;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva reserva de área común
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
            
            // Verificar que el área común existe
            if (!$this->verificarAreaComun($data['id_area_comun'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El área común especificada no existe'
                ];
            }
            
            // Verificar disponibilidad del área común en la fecha
            if (!$this->verificarDisponibilidad($data['id_area_comun'], $data['fecha_apartado'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El área común no está disponible en la fecha seleccionada'
                ];
            }
            
            // Limpiar datos antiguos antes de insertar
            $this->limpiarDatosAntiguos($data['id_condominio']);
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la reserva del área común'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_apartado' => $id]);
            
            return [
                'success' => true,
                'id_apartado' => $id,
                'message' => 'Reserva de área común creada exitosamente'
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
     * Leer reserva por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Reserva no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'apartado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar reserva
     */
    public function updateApartado(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que la reserva existe
            $apartado = $this->findById($id);
            if (!$apartado) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Reserva no encontrada'
                ];
            }
            
            // Si se cambia la fecha, verificar disponibilidad
            if (isset($data['fecha_apartado']) && $data['fecha_apartado'] !== $apartado['fecha_apartado']) {
                if (!$this->verificarDisponibilidad($apartado['id_area_comun'], $data['fecha_apartado'], $id)) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'El área común no está disponible en la nueva fecha seleccionada'
                    ];
                }
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la reserva'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_apartado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Reserva actualizada exitosamente'
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
     * Eliminar reserva
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
                    'error' => 'No se pudo eliminar la reserva'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_apartado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Reserva eliminada exitosamente'
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
     * Obtener reservas por condominio (paginado de 10 en 10)
     */
    public function getApartadosByCondominio(int $id_condominio, int $page = 1): array 
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
                'apartados' => $results,
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
     * Obtener una reserva específica por ID y condominio
     */
    public function getApartadoByIdCondominio(int $id_apartado, int $id_condominio): array 
    {
        try {
            $conditions = [
                'id_apartado' => $id_apartado,
                'id_condominio' => $id_condominio
            ];
            
            $result = $this->findOne($conditions);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Reserva no encontrada en el condominio especificado'
                ];
            }
            
            return [
                'success' => true,
                'apartado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener reservas por área común en un condominio
     */
    public function getApartadosByAreaComunCondominio(int $id_area_comun, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'id_area_comun' => $id_area_comun,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'apartados' => $results,
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
     * Obtener reservas por casa en un condominio
     */
    public function getApartadosByCasaCondominio(int $id_casa, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'id_casa' => $id_casa,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'apartados' => $results,
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
     * Obtener reservas futuras por condominio
     */
    public function getApartadosFuturosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $fechaActual = $this->getCurrentTimestamp();
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_apartado >= ? 
                    ORDER BY fecha_apartado ASC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fechaActual]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total de futuras
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? AND fecha_apartado >= ?";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio, $fechaActual]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'apartados_futuros' => $results,
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
     * Obtener reservas por fecha específica en un condominio
     */
    public function getApartadosByFechaCondominio(string $fecha, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND DATE(fecha_apartado) = ? 
                    ORDER BY fecha_apartado ASC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total en la fecha
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? AND DATE(fecha_apartado) = ?";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio, $fecha]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'apartados_fecha' => $results,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit),
                'fecha_consultada' => $fecha
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
     * Verificar que el área común existe
     */
    private function verificarAreaComun(int $id_area_comun): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM areas_comunes WHERE id_area_comun = ?";
            $stmt = $this->executeQuery($sql, [$id_area_comun]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar disponibilidad de área común en fecha específica
     */
    private function verificarDisponibilidad(int $id_area_comun, string $fecha_apartado, int $excluir_id = null): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE id_area_comun = ? AND fecha_apartado = ?";
            $params = [$id_area_comun, $fecha_apartado];
            
            // Excluir el ID actual si se está actualizando
            if ($excluir_id !== null) {
                $sql .= " AND id_apartado != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $this->executeQuery($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0; // Disponible si no hay reservas
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
                    WHERE id_condominio = ? AND fecha_apartado < ?";
            
            $this->executeQuery($sql, [$id_condominio, $fechaLimite]);
            
            $this->logActivity('cleanup_apartados', [
                'id_condominio' => $id_condominio,
                'fecha_limite' => $fechaLimite
            ]);
            
        } catch (Exception $e) {
            error_log("Error limpiando datos antiguos de apartados: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de reservas por condominio
     */
    public function obtenerEstadisticasApartadosCondominio(int $id_condominio): array 
    {
        try {
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar reservas futuras
            $fechaActual = $this->getCurrentTimestamp();
            $sqlFuturas = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? AND fecha_apartado >= ?";
            $stmtFuturas = $this->executeQuery($sqlFuturas, [$id_condominio, $fechaActual]);
            $futurasResult = $stmtFuturas->fetch(PDO::FETCH_ASSOC);
            $futuras = $futurasResult['count'];
            
            // Contar áreas únicas utilizadas
            $sqlAreas = "SELECT COUNT(DISTINCT id_area_comun) as count FROM {$this->tableName} 
                        WHERE id_condominio = ?";
            $stmtAreas = $this->executeQuery($sqlAreas, [$id_condominio]);
            $areasResult = $stmtAreas->fetch(PDO::FETCH_ASSOC);
            $areasUnicas = $areasResult['count'];
            
            // Promedio de reservas por área
            $promedioReservas = $areasUnicas > 0 ? round($total / $areasUnicas, 2) : 0;
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_apartados' => $total,
                    'apartados_futuros' => $futuras,
                    'apartados_pasados' => $total - $futuras,
                    'areas_comunes_utilizadas' => $areasUnicas,
                    'promedio_reservas_por_area' => $promedioReservas,
                    'porcentaje_futuras' => $total > 0 ? round(($futuras / $total) * 100, 2) : 0
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
     * Obtener calendario de disponibilidad de área común
     */
    public function getCalendarioDisponibilidadArea(int $id_area_comun, int $id_condominio, string $mes = null, int $anio = null): array 
    {
        try {
            // Fechas por defecto: mes actual
            if (!$mes) {
                $mes = date('m');
            }
            if (!$anio) {
                $anio = date('Y');
            }
            
            $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
            $fechaFin = date('Y-m-t', strtotime($fechaInicio));
            
            $sql = "SELECT DATE(fecha_apartado) as fecha, COUNT(*) as reservas
                    FROM {$this->tableName} 
                    WHERE id_area_comun = ? AND id_condominio = ?
                    AND DATE(fecha_apartado) BETWEEN ? AND ?
                    GROUP BY DATE(fecha_apartado)
                    ORDER BY fecha_apartado ASC";
            
            $stmt = $this->executeQuery($sql, [$id_area_comun, $id_condominio, $fechaInicio, $fechaFin]);
            $reservasPorFecha = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear calendario completo del mes
            $calendario = [];
            $fechaActual = strtotime($fechaInicio);
            $fechaFinMes = strtotime($fechaFin);
            
            while ($fechaActual <= $fechaFinMes) {
                $fechaStr = date('Y-m-d', $fechaActual);
                $reservas = 0;
                
                // Buscar si hay reservas en esta fecha
                foreach ($reservasPorFecha as $reserva) {
                    if ($reserva['fecha'] === $fechaStr) {
                        $reservas = $reserva['reservas'];
                        break;
                    }
                }
                
                $calendario[] = [
                    'fecha' => $fechaStr,
                    'dia_semana' => date('N', $fechaActual), // 1=Lunes, 7=Domingo
                    'reservas' => $reservas,
                    'disponible' => $reservas == 0,
                    'es_pasado' => $fechaStr < date('Y-m-d')
                ];
                
                $fechaActual = strtotime('+1 day', $fechaActual);
            }
            
            return [
                'success' => true,
                'calendario' => [
                    'id_area_comun' => $id_area_comun,
                    'id_condominio' => $id_condominio,
                    'mes' => $mes,
                    'anio' => $anio,
                    'dias' => $calendario,
                    'total_dias' => count($calendario),
                    'dias_disponibles' => count(array_filter($calendario, function($dia) { return $dia['disponible'] && !$dia['es_pasado']; })),
                    'dias_ocupados' => count(array_filter($calendario, function($dia) { return !$dia['disponible']; }))
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