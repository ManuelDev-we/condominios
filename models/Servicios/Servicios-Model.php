<?php
/**
 * 🔧 SERVICIOS MODEL - Modelo de Servicios del Condominio
 * Manejo de servicios con RFC y nombre encriptados
 * Control por condominio y limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';
require_once __DIR__ . '/../../config/encryption.php';

class ServiciosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla servicios
    public ?int $id_servicio;
    public ?int $id_condominio;
    public ?string $rfc;
    public ?string $nombre;
    public ?string $descripcion;
    public ?string $fecha_inicio;
    public ?string $fecha_fin;

    public function __construct(
        ?int $id_servicio = null,
        ?int $id_condominio = null,
        ?string $rfc = null,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'servicios';
        $this->primaryKey = 'id_servicio';
        $this->fillableFields = [
            'id_condominio', 'rfc', 'nombre', 'descripcion', 
            'fecha_inicio', 'fecha_fin'
        ];
        
        // Campos que se encriptan: RFC y nombre
        $this->encryptedFields = ['rfc', 'nombre'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_servicio = $id_servicio;
        $this->id_condominio = $id_condominio;
        $this->rfc = $rfc;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo servicio
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
            
            // Validar fechas
            if (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
                if (strtotime($data['fecha_inicio']) >= strtotime($data['fecha_fin'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La fecha de inicio debe ser anterior a la fecha de fin'
                    ];
                }
            }
            
            // Limpiar datos antiguos antes de insertar
            $this->limpiarDatosAntiguos($data['id_condominio']);
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear el servicio'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_servicio' => $id]);
            
            return [
                'success' => true,
                'id_servicio' => $id,
                'message' => 'Servicio creado exitosamente'
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
     * Leer servicio por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Servicio no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'servicio' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar servicio
     */
    public function updateServicio(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el servicio existe
            $servicio = $this->findById($id);
            if (!$servicio) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Servicio no encontrado'
                ];
            }
            
            // Validar fechas si se proporcionan
            if (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
                if (strtotime($data['fecha_inicio']) >= strtotime($data['fecha_fin'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La fecha de inicio debe ser anterior a la fecha de fin'
                    ];
                }
            } elseif (isset($data['fecha_inicio']) && isset($servicio['fecha_fin'])) {
                if (strtotime($data['fecha_inicio']) >= strtotime($servicio['fecha_fin'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La nueva fecha de inicio debe ser anterior a la fecha de fin existente'
                    ];
                }
            } elseif (isset($data['fecha_fin']) && isset($servicio['fecha_inicio'])) {
                if (strtotime($servicio['fecha_inicio']) >= strtotime($data['fecha_fin'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La nueva fecha de fin debe ser posterior a la fecha de inicio existente'
                    ];
                }
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el servicio'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_servicio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Servicio actualizado exitosamente'
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
     * Eliminar servicio
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
                    'error' => 'No se pudo eliminar el servicio'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_servicio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Servicio eliminado exitosamente'
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
     * Obtener servicios por condominio (paginado de 10 en 10)
     */
    public function getServiciosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $results = $this->findMany($conditions, $limit, $offset, 'fecha_inicio DESC');
            
            // Contar total
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'servicios' => $results,
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
     * Obtener un servicio específico por ID y condominio
     */
    public function getServicioByIdCondominio(int $id_servicio, int $id_condominio): array 
    {
        try {
            $conditions = [
                'id_servicio' => $id_servicio,
                'id_condominio' => $id_condominio
            ];
            
            $result = $this->findOne($conditions);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Servicio no encontrado en el condominio especificado'
                ];
            }
            
            return [
                'success' => true,
                'servicio' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener servicios activos por condominio
     */
    public function getServiciosActivosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $fechaActual = $this->getCurrentTimestamp();
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND (fecha_fin IS NULL OR fecha_fin >= ?) 
                    AND (fecha_inicio IS NULL OR fecha_inicio <= ?) 
                    ORDER BY fecha_inicio DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fechaActual, $fechaActual]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos si es necesario
            foreach ($results as &$result) {
                $result = $this->decryptFields($result);
            }
            
            // Contar total de activos
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? 
                        AND (fecha_fin IS NULL OR fecha_fin >= ?) 
                        AND (fecha_inicio IS NULL OR fecha_inicio <= ?)";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio, $fechaActual, $fechaActual]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'servicios_activos' => $results,
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
     * Obtener servicios vencidos por condominio
     */
    public function getServiciosVencidosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $fechaActual = $this->getCurrentTimestamp();
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND fecha_fin IS NOT NULL 
                    AND fecha_fin < ? 
                    ORDER BY fecha_fin DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fechaActual]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos si es necesario
            foreach ($results as &$result) {
                $result = $this->decryptFields($result);
            }
            
            // Contar total de vencidos
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? 
                        AND fecha_fin IS NOT NULL 
                        AND fecha_fin < ?";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio, $fechaActual]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'servicios_vencidos' => $results,
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
     * Buscar servicios por nombre en un condominio
     */
    public function buscarServiciosByNombreCondominio(string $nombre, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Buscar por nombre encriptado y descripción
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND descripcion LIKE ? 
                    ORDER BY fecha_inicio DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $searchTerm = "%{$nombre}%";
            $stmt = $this->executeQuery($sql, [$id_condominio, $searchTerm]);
            $descResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // También buscar en nombres encriptados
            $sqlAll = "SELECT * FROM {$this->tableName} WHERE id_condominio = ?";
            $stmtAll = $this->executeQuery($sqlAll, [$id_condominio]);
            $allResults = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
            
            $nameResults = [];
            foreach ($allResults as $result) {
                $decrypted = $this->decryptFields($result);
                if (stripos($decrypted['nombre'], $nombre) !== false) {
                    $nameResults[] = $decrypted;
                }
            }
            
            // Combinar resultados y eliminar duplicados
            $combinedResults = array_merge($descResults, $nameResults);
            $uniqueResults = [];
            $seenIds = [];
            
            foreach ($combinedResults as $result) {
                if (!in_array($result['id_servicio'], $seenIds)) {
                    $uniqueResults[] = $result;
                    $seenIds[] = $result['id_servicio'];
                }
            }
            
            // Paginar resultados únicos
            $total = count($uniqueResults);
            $results = array_slice($uniqueResults, $offset, $limit);
            
            return [
                'success' => true,
                'servicios_busqueda' => $results,
                'nombre_buscado' => $nombre,
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
     * Obtener servicios por rango de fechas en un condominio
     */
    public function getServiciosByRangoFechasCondominio(string $fecha_inicio, string $fecha_fin, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND (
                        (fecha_inicio BETWEEN ? AND ?) OR 
                        (fecha_fin BETWEEN ? AND ?) OR 
                        (fecha_inicio <= ? AND fecha_fin >= ?)
                    )
                    ORDER BY fecha_inicio DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->executeQuery($sql, [
                $id_condominio, 
                $fecha_inicio, $fecha_fin,
                $fecha_inicio, $fecha_fin,
                $fecha_inicio, $fecha_fin
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos si es necesario
            foreach ($results as &$result) {
                $result = $this->decryptFields($result);
            }
            
            // Contar total en el rango
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? 
                        AND (
                            (fecha_inicio BETWEEN ? AND ?) OR 
                            (fecha_fin BETWEEN ? AND ?) OR 
                            (fecha_inicio <= ? AND fecha_fin >= ?)
                        )";
            $stmtCount = $this->executeQuery($sqlCount, [
                $id_condominio, 
                $fecha_inicio, $fecha_fin,
                $fecha_inicio, $fecha_fin,
                $fecha_inicio, $fecha_fin
            ]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'servicios_rango' => $results,
                'fecha_inicio_consulta' => $fecha_inicio,
                'fecha_fin_consulta' => $fecha_fin,
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
     * Desencriptar campos sensibles del RFC
     */
    private function decryptFields(array $data): array 
    {
        if (!empty($data['rfc'])) {
            $encryptionConfig = new EncryptionConfig();
            $data['rfc'] = $encryptionConfig->decryptFromDatabase($data['rfc']);
        }
        return $data;
    }

    /**
     * Limpiar datos antiguos (más de 8 años)
     */
    private function limpiarDatosAntiguos(int $id_condominio): void 
    {
        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime('-8 years'));
            
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND (fecha_fin IS NOT NULL AND fecha_fin < ?)";
            
            $this->executeQuery($sql, [$id_condominio, $fechaLimite]);
            
            $this->logActivity('cleanup_servicios', [
                'id_condominio' => $id_condominio,
                'fecha_limite' => $fechaLimite
            ]);
            
        } catch (Exception $e) {
            error_log("Error limpiando datos antiguos de servicios: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de servicios por condominio
     */
    public function obtenerEstadisticasServiciosCondominio(int $id_condominio): array 
    {
        try {
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            $fechaActual = $this->getCurrentTimestamp();
            
            // Contar servicios activos
            $sqlActivos = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? 
                          AND (fecha_fin IS NULL OR fecha_fin >= ?) 
                          AND (fecha_inicio IS NULL OR fecha_inicio <= ?)";
            $stmtActivos = $this->executeQuery($sqlActivos, [$id_condominio, $fechaActual, $fechaActual]);
            $activosResult = $stmtActivos->fetch(PDO::FETCH_ASSOC);
            $activos = $activosResult['count'];
            
            // Contar servicios vencidos
            $sqlVencidos = "SELECT COUNT(*) as count FROM {$this->tableName} 
                           WHERE id_condominio = ? 
                           AND fecha_fin IS NOT NULL 
                           AND fecha_fin < ?";
            $stmtVencidos = $this->executeQuery($sqlVencidos, [$id_condominio, $fechaActual]);
            $vencidosResult = $stmtVencidos->fetch(PDO::FETCH_ASSOC);
            $vencidos = $vencidosResult['count'];
            
            // Servicios sin fecha de fin (permanentes)
            $sqlPermanentes = "SELECT COUNT(*) as count FROM {$this->tableName} 
                              WHERE id_condominio = ? AND fecha_fin IS NULL";
            $stmtPermanentes = $this->executeQuery($sqlPermanentes, [$id_condominio]);
            $permanentesResult = $stmtPermanentes->fetch(PDO::FETCH_ASSOC);
            $permanentes = $permanentesResult['count'];
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_servicios' => $total,
                    'servicios_activos' => $activos,
                    'servicios_vencidos' => $vencidos,
                    'servicios_permanentes' => $permanentes,
                    'porcentaje_activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0,
                    'porcentaje_vencidos' => $total > 0 ? round(($vencidos / $total) * 100, 2) : 0,
                    'porcentaje_permanentes' => $total > 0 ? round(($permanentes / $total) * 100, 2) : 0
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