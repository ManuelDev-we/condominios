<?php
/**
 * 💰 COBROS AUTORIZADOS MODEL - Modelo de Cobros Autorizados
 * Manejo de cobros recurrentes con encriptación de datos sensibles
 * Restricciones por condominio y segmentación por fechas
 * 
 * @package Cyberhole\Models\Financiero
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class CobrosAutorizadosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla cobros_autorizados
    public ?int $id_cobro_autorizado;
    public ?int $id_persona;
    public ?int $id_casa;
    public ?int $id_condominio;
    public ?float $monto;
    public ?string $concepto;
    public ?bool $es_recurrente;
    public ?string $frecuencia;
    public ?string $fecha;
    public ?bool $activo;
    public ?string $token_pago;
    public ?string $creado_en;
    public ?int $id_cuota;
    public ?string $rfc;
    public ?string $metodo_pago;
    public ?float $iva;

    public function __construct(
        ?int $id_cobro_autorizado = null,
        ?int $id_persona = null,
        ?int $id_casa = null,
        ?int $id_condominio = null,
        ?float $monto = null,
        ?string $concepto = null,
        ?bool $es_recurrente = false,
        ?string $frecuencia = 'mensual',
        ?string $fecha = null,
        ?bool $activo = true,
        ?string $token_pago = null,
        ?string $creado_en = null,
        ?int $id_cuota = null,
        ?string $rfc = null,
        ?string $metodo_pago = null,
        ?float $iva = 0.00
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'cobros_autorizados';
        $this->primaryKey = 'id_cobro_autorizado';
        $this->fillableFields = [
            'id_persona', 'id_casa', 'id_condominio', 'monto', 'concepto', 'es_recurrente', 
            'frecuencia', 'fecha', 'activo', 'token_pago', 'id_cuota', 
            'rfc', 'metodo_pago', 'iva'
        ];
        
        // Campos que se encriptan: concepto y RFC (campos sensibles)
        $this->encryptedFields = ['concepto', 'rfc'];
        $this->hiddenFields = ['token_pago'];
        
        // Asignar propiedades
        $this->id_cobro_autorizado = $id_cobro_autorizado;
        $this->id_persona = $id_persona;
        $this->id_casa = $id_casa;
        $this->id_condominio = $id_condominio;
        $this->monto = $monto;
        $this->concepto = $concepto;
        $this->es_recurrente = $es_recurrente;
        $this->frecuencia = $frecuencia;
        $this->fecha = $fecha;
        $this->activo = $activo;
        $this->token_pago = $token_pago;
        $this->creado_en = $creado_en;
        $this->id_cuota = $id_cuota;
        $this->rfc = $rfc;
        $this->metodo_pago = $metodo_pago;
        $this->iva = $iva;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo cobro autorizado
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar condominio
            if (!$this->validarCondominio($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El condominio especificado no es válido'
                ];
            }
            
            // Validar que la casa pertenezca al condominio
            if (!$this->validarCasaCondominio($data['id_casa'], $data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La casa especificada no pertenece al condominio'
                ];
            }
            
            // Generar token de pago único
            if (!isset($data['token_pago']) || empty($data['token_pago'])) {
                $data['token_pago'] = $this->generarTokenPago();
            }
            
            // Establecer fecha si no se proporciona
            if (!isset($data['fecha']) || empty($data['fecha'])) {
                $data['fecha'] = date('Y-m-d');
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear el cobro autorizado'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_cobro_autorizado' => $id]);
            
            return [
                'success' => true,
                'id_cobro_autorizado' => $id,
                'message' => 'Cobro autorizado creado exitosamente'
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
     * Leer cobro autorizado por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Cobro autorizado no encontrado'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            // Ocultar campos sensibles
            $result = $this->hideSecretFields($result);
            
            return [
                'success' => true,
                'cobro_autorizado' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar cobro autorizado
     */
    public function updateCobroAutorizado(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar condominio si se está actualizando
            if (isset($data['id_condominio']) && !$this->validarCondominio($data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El condominio especificado no es válido'
                ];
            }
            
            // Validar que la casa pertenezca al condominio si se están actualizando ambos
            if (isset($data['id_casa']) && isset($data['id_condominio'])) {
                if (!$this->validarCasaCondominio($data['id_casa'], $data['id_condominio'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La casa especificada no pertenece al condominio'
                    ];
                }
            } elseif (isset($data['id_casa'])) {
                // Si solo se actualiza la casa, obtener el condominio actual
                $cobroActual = $this->findById($id);
                if ($cobroActual && !$this->validarCasaCondominio($data['id_casa'], $cobroActual['id_condominio'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La casa especificada no pertenece al condominio actual'
                    ];
                }
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el cobro autorizado'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_cobro_autorizado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Cobro autorizado actualizado exitosamente'
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
     * Eliminar cobro autorizado (soft delete - marcar como inactivo)
     */
    public function deleteCobroAutorizado(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            // Hacer soft delete marcando como inactivo
            $updated = $this->update($id, ['activo' => false]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el cobro autorizado'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_cobro_autorizado' => $id]);
            
            return [
                'success' => true,
                'message' => 'Cobro autorizado eliminado exitosamente'
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
    // MÉTODOS DE CONSULTA SEGMENTADOS POR CONDOMINIO
    // ===========================================

    /**
     * Obtener cobros por condominio con paginación
     */
    public function getCobrosByCondominio(int $id_condominio, int $page = 1, int $limit = 10): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT ca.*, c.numero as numero_casa, calle.nombre as calle_nombre, 
                           p.nombres, p.apellido1, p.apellido2
                    FROM {$this->tableName} ca
                    INNER JOIN casas c ON ca.id_casa = c.id_casa
                    INNER JOIN calles calle ON c.id_calle = calle.id_calle
                    INNER JOIN condominios cond ON calle.id_condominio = cond.id_condominio
                    INNER JOIN personas p ON ca.id_persona = p.id_persona
                    WHERE cond.id_condominio = ? AND ca.activo = 1
                    ORDER BY ca.creado_en DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            // Contar total
            $sqlCount = "SELECT COUNT(*) as total
                        FROM {$this->tableName} ca
                        INNER JOIN casas c ON ca.id_casa = c.id_casa
                        INNER JOIN calles calle ON c.id_calle = calle.id_calle
                        INNER JOIN condominios cond ON calle.id_condominio = cond.id_condominio
                        WHERE cond.id_condominio = ? AND ca.activo = 1";
            
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio]);
            $total = $stmtCount->fetch()['total'];
            
            return [
                'success' => true,
                'cobros_autorizados' => $results,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
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
     * Obtener cobros por persona
     */
    public function getCobrosByPersona(int $id_persona, int $limit = 10): array 
    {
        try {
            $sql = "SELECT ca.*, c.numero as numero_casa
                    FROM {$this->tableName} ca
                    INNER JOIN casas c ON ca.id_casa = c.id_casa
                    WHERE ca.id_persona = ? AND ca.activo = 1
                    ORDER BY ca.creado_en DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_persona, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            return [
                'success' => true,
                'cobros_autorizados' => $results,
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
     * Obtener cobros recurrentes por condominio
     */
    public function getCobrosRecurrentesByCondominio(int $id_condominio, int $limit = 10): array 
    {
        try {
            $sql = "SELECT ca.*, c.numero as numero_casa, p.nombres, p.apellido1
                    FROM {$this->tableName} ca
                    INNER JOIN casas c ON ca.id_casa = c.id_casa
                    INNER JOIN calles calle ON c.id_calle = calle.id_calle
                    INNER JOIN condominios cond ON calle.id_condominio = cond.id_condominio
                    INNER JOIN personas p ON ca.id_persona = p.id_persona
                    WHERE cond.id_condominio = ? AND ca.es_recurrente = 1 AND ca.activo = 1
                    ORDER BY ca.fecha DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            return [
                'success' => true,
                'cobros_recurrentes' => $results,
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
     * Obtener cobros por rango de fechas
     */
    public function getCobrosByFechas(int $id_condominio, string $fecha_inicio, string $fecha_fin, int $limit = 10): array 
    {
        try {
            $sql = "SELECT ca.*, c.numero as numero_casa, p.nombres, p.apellido1
                    FROM {$this->tableName} ca
                    INNER JOIN casas c ON ca.id_casa = c.id_casa
                    INNER JOIN calles calle ON c.id_calle = calle.id_calle
                    INNER JOIN condominios cond ON calle.id_condominio = cond.id_condominio
                    INNER JOIN personas p ON ca.id_persona = p.id_persona
                    WHERE cond.id_condominio = ? AND ca.fecha BETWEEN ? AND ? AND ca.activo = 1
                    ORDER BY ca.fecha DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_inicio, $fecha_fin, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            return [
                'success' => true,
                'cobros_autorizados' => $results,
                'total' => count($results),
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // GETTERS Y SETTERS ESPECIALIZADOS
    // ===========================================

    /**
     * Obtener cobros activos
     */
    public function getCobrosActivos(int $id_condominio = null, int $limit = 10): array 
    {
        try {
            if ($id_condominio) {
                return $this->getCobrosByCondominio($id_condominio, 1, $limit);
            }
            
            $results = $this->findMany(['activo' => 1], $limit);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            return [
                'success' => true,
                'cobros_autorizados' => $results,
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
     * Obtener estadísticas de cobros por condominio
     */
    public function getEstadisticasByCondominio(int $id_condominio): array 
    {
        try {
            // Total de cobros
            $sqlTotal = "SELECT COUNT(*) as total, SUM(ca.monto) as total_monto
                        FROM {$this->tableName} ca
                        INNER JOIN casas c ON ca.id_casa = c.id_casa
                        INNER JOIN calles calle ON c.id_calle = calle.id_calle
                        WHERE calle.id_condominio = ? AND ca.activo = 1";
            
            $stmtTotal = $this->executeQuery($sqlTotal, [$id_condominio]);
            $totales = $stmtTotal->fetch();
            
            // Cobros recurrentes
            $sqlRecurrentes = "SELECT COUNT(*) as total_recurrentes
                              FROM {$this->tableName} ca
                              INNER JOIN casas c ON ca.id_casa = c.id_casa
                              INNER JOIN calles calle ON c.id_calle = calle.id_calle
                              WHERE calle.id_condominio = ? AND ca.es_recurrente = 1 AND ca.activo = 1";
            
            $stmtRecurrentes = $this->executeQuery($sqlRecurrentes, [$id_condominio]);
            $recurrentes = $stmtRecurrentes->fetch();
            
            // Por frecuencia
            $sqlFrecuencias = "SELECT ca.frecuencia, COUNT(*) as cantidad
                              FROM {$this->tableName} ca
                              INNER JOIN casas c ON ca.id_casa = c.id_casa
                              INNER JOIN calles calle ON c.id_calle = calle.id_calle
                              WHERE calle.id_condominio = ? AND ca.es_recurrente = 1 AND ca.activo = 1
                              GROUP BY ca.frecuencia";
            
            $stmtFrecuencias = $this->executeQuery($sqlFrecuencias, [$id_condominio]);
            $frecuencias = $stmtFrecuencias->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_cobros' => (int)$totales['total'],
                    'monto_total' => (float)$totales['total_monto'],
                    'cobros_recurrentes' => (int)$recurrentes['total_recurrentes'],
                    'por_frecuencia' => $frecuencias,
                    'promedio_por_cobro' => $totales['total'] > 0 ? round($totales['total_monto'] / $totales['total'], 2) : 0
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
     * Generar token de pago único
     */
    private function generarTokenPago(): string 
    {
        return 'TOKEN_' . time() . '_' . uniqid() . '_' . rand(1000, 9999);
    }

    /**
     * Validar que un condominio existe
     */
    private function validarCondominio(int $id_condominio): bool 
    {
        try {
            $sql = "SELECT id_condominio FROM condominios WHERE id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validar que una casa pertenece a un condominio específico
     */
    private function validarCasaCondominio(int $id_casa, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT c.id_casa
                    FROM casas c
                    INNER JOIN calles calle ON c.id_calle = calle.id_calle
                    WHERE c.id_casa = ? AND calle.id_condominio = ?";
            
            $stmt = $this->executeQuery($sql, [$id_casa, $id_condominio]);
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Activar/Desactivar cobro autorizado
     */
    public function toggleActivo(int $id, bool $activo): array 
    {
        try {
            $updated = $this->update($id, ['activo' => $activo]);
            
            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estado del cobro autorizado'
                ];
            }
            
            $this->logActivity('toggle_activo', ['id_cobro_autorizado' => $id, 'activo' => $activo]);
            
            return [
                'success' => true,
                'message' => 'Estado del cobro autorizado actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener cobros próximos a vencer (para cobros recurrentes)
     */
    public function getCobrosProximosVencer(int $id_condominio, int $dias = 7): array 
    {
        try {
            $fecha_limite = date('Y-m-d', strtotime("+{$dias} days"));
            
            $sql = "SELECT ca.*, c.numero as numero_casa, p.nombres, p.apellido1
                    FROM {$this->tableName} ca
                    INNER JOIN casas c ON ca.id_casa = c.id_casa
                    INNER JOIN calles calle ON c.id_calle = calle.id_calle
                    INNER JOIN condominios cond ON calle.id_condominio = cond.id_condominio
                    INNER JOIN personas p ON ca.id_persona = p.id_persona
                    WHERE cond.id_condominio = ? 
                    AND ca.es_recurrente = 1 
                    AND ca.activo = 1 
                    AND ca.fecha <= ?
                    ORDER BY ca.fecha ASC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_limite]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result = $this->hideSecretFields($result);
            }
            
            return [
                'success' => true,
                'cobros_proximos' => $results,
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
}
?>