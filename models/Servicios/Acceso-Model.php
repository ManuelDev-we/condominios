<?php
/**
 * 🚪 ACCESO MODEL - Modelo de Accesos de Residentes
 * Manejo de accesos de residentes con dispositivos TAG/ENGOMADO
 * Control por condominio y limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class AccesoModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla accesos_residentes
    public ?int $id_acceso;
    public ?int $id_persona;
    public ?int $id_condominio;
    public ?int $id_casa;
    public ?int $id_persona_dispositivo;
    public ?string $tipo_dispositivo;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_acceso = null,
        ?int $id_persona = null,
        ?int $id_condominio = null,
        ?int $id_casa = null,
        ?int $id_persona_dispositivo = null,
        ?string $tipo_dispositivo = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'accesos_residentes';
        $this->primaryKey = 'id_acceso';
        $this->fillableFields = [
            'id_persona', 'id_condominio', 'id_casa', 'id_persona_dispositivo', 
            'tipo_dispositivo', 'fecha_hora_entrada', 'fecha_hora_salida'
        ];
        
        // No hay campos sensibles que encriptar en esta tabla
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_acceso = $id_acceso;
        $this->id_persona = $id_persona;
        $this->id_condominio = $id_condominio;
        $this->id_casa = $id_casa;
        $this->id_persona_dispositivo = $id_persona_dispositivo;
        $this->tipo_dispositivo = $tipo_dispositivo;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo acceso de residente
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
                    'error' => 'No se pudo crear el acceso'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'id_acceso' => $id,
                'message' => 'Acceso creado exitosamente'
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
     * Leer acceso por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Acceso no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'acceso' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar acceso
     */
    public function updateAcceso(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el acceso existe
            $acceso = $this->findById($id);
            if (!$acceso) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Acceso no encontrado'
                ];
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el acceso'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'message' => 'Acceso actualizado exitosamente'
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
     * Eliminar acceso
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
                    'error' => 'No se pudo eliminar el acceso'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_acceso' => $id]);
            
            return [
                'success' => true,
                'message' => 'Acceso eliminado exitosamente'
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
     * Obtener accesos por condominio (paginado de 10 en 10)
     */
    public function getAccesosByCondominio(int $id_condominio, int $page = 1): array 
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
                'accesos' => $results,
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
     * Obtener un acceso específico por ID y condominio
     */
    public function getAccesoByIdCondominio(int $id_acceso, int $id_condominio): array 
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
                    'error' => 'Acceso no encontrado en el condominio especificado'
                ];
            }
            
            return [
                'success' => true,
                'acceso' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener accesos por persona en un condominio
     */
    public function getAccesosByPersonaCondominio(int $id_persona, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'id_persona' => $id_persona,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'accesos' => $results,
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
     * Obtener accesos por casa en un condominio
     */
    public function getAccesosByCasaCondominio(int $id_casa, int $id_condominio, int $page = 1): array 
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
                'accesos' => $results,
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
     * Obtener accesos por tipo de dispositivo en un condominio
     */
    public function getAccesosByTipoDispositivoCondominio(string $tipo_dispositivo, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'tipo_dispositivo' => $tipo_dispositivo,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset);
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'accesos' => $results,
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
     * Obtener accesos activos (sin salida) por condominio
     */
    public function getAccesosActivosByCondominio(int $id_condominio, int $page = 1): array 
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
                'accesos_activos' => $results,
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
     * Registrar salida de un acceso
     */
    public function registrarSalida(int $id_acceso, int $id_condominio): array 
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
                    'error' => 'Acceso no encontrado'
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
                    'error' => 'No se pudo registrar la salida'
                ];
            }
            
            $this->commit();
            $this->logActivity('salida', ['id_acceso' => $id_acceso]);
            
            return [
                'success' => true,
                'message' => 'Salida registrada exitosamente'
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
            
            $this->logActivity('cleanup', [
                'id_condominio' => $id_condominio,
                'fecha_limite' => $fechaLimite
            ]);
            
        } catch (Exception $e) {
            error_log("Error limpiando datos antiguos: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de accesos por condominio
     */
    public function obtenerEstadisticasCondominio(int $id_condominio): array 
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
            
            // Contar por tipo de dispositivo
            $sqlTags = "SELECT COUNT(*) as count FROM {$this->tableName} 
                       WHERE id_condominio = ? AND tipo_dispositivo = 'TAG'";
            $stmtTags = $this->executeQuery($sqlTags, [$id_condominio]);
            $tagsResult = $stmtTags->fetch(PDO::FETCH_ASSOC);
            $totalTags = $tagsResult['count'];
            
            $sqlEngomados = "SELECT COUNT(*) as count FROM {$this->tableName} 
                            WHERE id_condominio = ? AND tipo_dispositivo = 'ENGOMADO'";
            $stmtEngomados = $this->executeQuery($sqlEngomados, [$id_condominio]);
            $engoResult = $stmtEngomados->fetch(PDO::FETCH_ASSOC);
            $totalEngomados = $engoResult['count'];
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_accesos' => $total,
                    'accesos_activos' => $activos,
                    'accesos_con_tags' => $totalTags,
                    'accesos_con_engomados' => $totalEngomados,
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
?>

