<?php
/**
 * 💸 CUOTAS MODEL - Modelo de Cuotas
 * Manejo de cuotas con recurrencia y compresión de archivos PDF
 * Restricciones por condominio y calle, segmentación por fechas
 * 
 * @package Cyberhole\Models\Financiero
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class CuotasModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla cuotas
    public ?int $id_cuota;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?string $descripcion;
    public ?string $concepto; // Alias para descripcion
    public ?float $monto;
    public ?float $monto_base; // Alias para monto
    public ?float $monto_total; // Alias para monto con IVA
    public ?float $iva;
    public ?string $fecha_generacion;
    public ?string $fecha_vencimiento;
    public ?string $uso_cfdi;
    public ?string $moneda;
    public ?string $uuid_factura;
    public ?string $archivo_pdf;
    public ?string $estatus;
    public ?string $recurrencia;

    public function __construct(
        ?int $id_cuota = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?string $descripcion = null,
        ?float $monto = null,
        ?float $iva = 0.00,
        ?string $fecha_generacion = null,
        ?string $fecha_vencimiento = null,
        ?string $uso_cfdi = 'G03',
        ?string $moneda = 'MXN',
        ?string $uuid_factura = null,
        ?string $archivo_pdf = null,
        ?string $estatus = 'pendiente',
        ?string $recurrencia = 'unica'
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'cuotas';
        $this->primaryKey = 'id_cuota';
        $this->fillableFields = [
            'id_condominio', 'id_calle', 'descripcion', 'monto', 'iva',
            'fecha_generacion', 'fecha_vencimiento', 'uso_cfdi', 'moneda',
            'uuid_factura', 'archivo_pdf', 'estatus', 'recurrencia'
        ];
        
        // Campos que se comprimen y encriptan: uuid_factura y archivo_pdf
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_cuota = $id_cuota;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->descripcion = $descripcion;
        $this->concepto = $descripcion; // Alias
        $this->monto = $monto;
        $this->monto_base = $monto; // Alias para monto
        $this->monto_total = $monto + ($monto * ($iva / 100)); // Monto con IVA
        $this->iva = $iva;
        $this->fecha_generacion = $fecha_generacion;
        $this->fecha_vencimiento = $fecha_vencimiento;
        $this->uso_cfdi = $uso_cfdi;
        $this->moneda = $moneda;
        $this->uuid_factura = $uuid_factura;
        $this->archivo_pdf = $archivo_pdf;
        $this->estatus = $estatus;
        $this->recurrencia = $recurrencia;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva cuota
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar que la calle pertenezca al condominio especificado
            if (!$this->validarCalleCondominio($data['id_calle'], $data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La calle especificada no pertenece al condominio'
                ];
            }
            
            // Procesar archivo PDF si existe
            if (isset($data['archivo_pdf_file']) && $data['archivo_pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['archivo_pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_comprimido'];
                }
                unset($data['archivo_pdf_file']);
            }
            
            // Comprimir y encriptar UUID si existe
            if (isset($data['uuid_factura']) && !empty($data['uuid_factura'])) {
                $data['uuid_factura'] = $this->comprimirYEncriptarUUID($data['uuid_factura']);
            }
            
            // Establecer fecha de generación si no se proporciona
            if (!isset($data['fecha_generacion']) || empty($data['fecha_generacion'])) {
                $data['fecha_generacion'] = $this->getCurrentTimestamp();
            }
            
            // Calcular fecha de vencimiento si no se proporciona
            if (!isset($data['fecha_vencimiento']) || empty($data['fecha_vencimiento'])) {
                $data['fecha_vencimiento'] = $this->calcularFechaVencimiento($data['recurrencia'] ?? 'unica');
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la cuota'
                ];
            }
            
            // Si es recurrente, programar siguiente cuota
            if (isset($data['recurrencia']) && $data['recurrencia'] !== 'unica') {
                $this->programarSiguienteCuota($id, $data);
            }
            
            $this->commit();
            $this->logActivity('create', ['id_cuota' => $id]);
            
            return [
                'success' => true,
                'id_cuota' => $id,
                'message' => 'Cuota creada exitosamente'
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
     * Leer cuota por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Cuota no encontrada'
                ];
            }
            
            // Descomprimir y desencriptar UUID si existe
            if (!empty($result['uuid_factura'])) {
                $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
            }
            
            return [
                'success' => true,
                'cuota' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar cuota
     */
    public function updateCuota(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar que la calle pertenezca al condominio si se están actualizando
            if (isset($data['id_calle']) && isset($data['id_condominio'])) {
                if (!$this->validarCalleCondominio($data['id_calle'], $data['id_condominio'])) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'La calle especificada no pertenece al condominio'
                    ];
                }
            }
            
            // Procesar archivo PDF si existe
            if (isset($data['archivo_pdf_file']) && $data['archivo_pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['archivo_pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_comprimido'];
                }
                unset($data['archivo_pdf_file']);
            }
            
            // Comprimir y encriptar UUID si existe
            if (isset($data['uuid_factura']) && !empty($data['uuid_factura'])) {
                $data['uuid_factura'] = $this->comprimirYEncriptarUUID($data['uuid_factura']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la cuota'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_cuota' => $id]);
            
            return [
                'success' => true,
                'message' => 'Cuota actualizada exitosamente'
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
     * Eliminar cuota
     */
    public function deleteCuota(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la cuota'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_cuota' => $id]);
            
            return [
                'success' => true,
                'message' => 'Cuota eliminada exitosamente'
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
     * Obtener cuotas por condominio con paginación
     */
    public function getCuotasByCondominio(int $id_condominio, int $page = 1, int $limit = 10): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ?
                    ORDER BY c.fecha_generacion DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
            }
            
            // Contar total
            $total = $this->count(['id_condominio' => $id_condominio]);
            
            return [
                'success' => true,
                'cuotas' => $results,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
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
     * Obtener cuotas por calle
     */
    public function getCuotasByCalle(int $id_calle, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_calle = ?
                    ORDER BY c.fecha_generacion DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_calle, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
            }
            
            return [
                'success' => true,
                'cuotas' => $results,
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
     * Obtener cuotas por rango de fechas
     */
    public function getCuotasByFechas(int $id_condominio, string $fecha_inicio, string $fecha_fin, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ? AND DATE(c.fecha_generacion) BETWEEN ? AND ?
                    ORDER BY c.fecha_generacion DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_inicio, $fecha_fin, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
            }
            
            return [
                'success' => true,
                'cuotas' => $results,
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

    /**
     * Obtener cuotas por recurrencia
     */
    public function getCuotasByRecurrencia(int $id_condominio, string $recurrencia, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ? AND c.recurrencia = ?
                    ORDER BY c.fecha_generacion DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $recurrencia, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
            }
            
            return [
                'success' => true,
                'cuotas' => $results,
                'total' => count($results),
                'recurrencia' => $recurrencia
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // MÉTODOS DE MANEJO DE ARCHIVOS PDF
    // ===========================================

    /**
     * Procesar archivo PDF: comprimir y encriptar
     */
    private function procesarArchivoPDF(string $pdfPath): array 
    {
        try {
            // Comprimir archivo PDF
            $compressionResult = $this->compressFile($pdfPath);
            
            if (!$compressionResult['success']) {
                throw new Exception('Error comprimiendo PDF: ' . $compressionResult['error']);
            }
            
            // Encriptar PDF comprimido
            $pdfEncriptado = $this->encryptField($compressionResult['data']);
            
            return [
                'success' => true,
                'pdf_comprimido' => $pdfEncriptado,
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
     * Recuperar archivo PDF: desencriptar y descomprimir
     */
    public function recuperarArchivoPDF(int $id_cuota, string $outputPath = null): array 
    {
        try {
            $cuota = $this->findById($id_cuota);
            
            if (!$cuota || !$cuota['archivo_pdf']) {
                return [
                    'success' => false,
                    'error' => 'Cuota o archivo PDF no encontrado'
                ];
            }
            
            // Desencriptar PDF
            $pdfData = $this->decryptField($cuota['archivo_pdf']);
            
            // Descomprimir PDF
            $extension = 'pdf';
            $decompressionResult = $this->decompressFile($pdfData, $extension, $outputPath);
            
            if (!$decompressionResult['success']) {
                throw new Exception('Error descomprimiendo PDF: ' . $decompressionResult['error']);
            }
            
            return [
                'success' => true,
                'pdf_data' => $decompressionResult['data'],
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
    // MÉTODOS DE MANEJO DE UUID FACTURA
    // ===========================================

    /**
     * Comprimir y encriptar UUID de factura
     */
    private function comprimirYEncriptarUUID(string $uuid): string 
    {
        // Comprimir UUID usando gzip
        $comprimido = gzcompress($uuid, 9);
        // Encriptar UUID comprimido
        return $this->encryptField(base64_encode($comprimido));
    }

    /**
     * Descomprimir y desencriptar UUID de factura
     */
    private function descomprimirYDesencriptarUUID(string $uuidEncriptado): string 
    {
        try {
            // Desencriptar
            $uuidComprimido = $this->decryptField($uuidEncriptado);
            // Descomprimir
            return gzuncompress(base64_decode($uuidComprimido));
        } catch (Exception $e) {
            return ''; // Retornar vacío si no se puede descomprimir
        }
    }

    // ===========================================
    // GETTERS Y SETTERS ESPECIALIZADOS
    // ===========================================

    /**
     * Obtener cuotas pendientes por condominio
     */
    public function getCuotasPendientes(int $id_condominio, int $limit = 10): array 
    {
        try {
            return $this->getCuotasByEstatus($id_condominio, 'pendiente', $limit);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener cuotas por estatus
     */
    public function getCuotasByEstatus(int $id_condominio, string $estatus, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ? AND c.estatus = ?
                    ORDER BY c.fecha_vencimiento ASC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $estatus, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
            }
            
            return [
                'success' => true,
                'cuotas' => $results,
                'total' => count($results),
                'estatus' => $estatus
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener cuotas vencidas
     */
    public function getCuotasVencidas(int $id_condominio, int $limit = 10): array 
    {
        try {
            $hoy = date('Y-m-d');
            
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ? AND c.fecha_vencimiento < ? AND c.estatus = 'pendiente'
                    ORDER BY c.fecha_vencimiento ASC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $hoy, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
                // Calcular días de vencimiento
                $result['dias_vencida'] = (new DateTime($hoy))->diff(new DateTime($result['fecha_vencimiento']))->days;
            }
            
            return [
                'success' => true,
                'cuotas_vencidas' => $results,
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
     * Obtener estadísticas de cuotas por condominio
     */
    public function getEstadisticasByCondominio(int $id_condominio): array 
    {
        try {
            // Total de cuotas y monto
            $sqlTotal = "SELECT COUNT(*) as total_cuotas, SUM(monto) as total_monto,
                                AVG(monto) as promedio_cuota
                         FROM {$this->tableName}
                         WHERE id_condominio = ?";
            
            $stmtTotal = $this->executeQuery($sqlTotal, [$id_condominio]);
            $totales = $stmtTotal->fetch();
            
            // Por estatus
            $sqlEstatus = "SELECT estatus, COUNT(*) as cantidad, SUM(monto) as total_monto
                          FROM {$this->tableName}
                          WHERE id_condominio = ?
                          GROUP BY estatus";
            
            $stmtEstatus = $this->executeQuery($sqlEstatus, [$id_condominio]);
            $estatus = $stmtEstatus->fetchAll(PDO::FETCH_ASSOC);
            
            // Por recurrencia
            $sqlRecurrencia = "SELECT recurrencia, COUNT(*) as cantidad
                              FROM {$this->tableName}
                              WHERE id_condominio = ?
                              GROUP BY recurrencia";
            
            $stmtRecurrencia = $this->executeQuery($sqlRecurrencia, [$id_condominio]);
            $recurrencias = $stmtRecurrencia->fetchAll(PDO::FETCH_ASSOC);
            
            // Cuotas vencidas
            $hoy = date('Y-m-d');
            $sqlVencidas = "SELECT COUNT(*) as cuotas_vencidas, SUM(monto) as monto_vencido
                           FROM {$this->tableName}
                           WHERE id_condominio = ? AND fecha_vencimiento < ? AND estatus = 'pendiente'";
            
            $stmtVencidas = $this->executeQuery($sqlVencidas, [$id_condominio, $hoy]);
            $vencidas = $stmtVencidas->fetch();
            
            return [
                'success' => true,
                'estadisticas' => [
                    'totales' => [
                        'cuotas' => (int)$totales['total_cuotas'],
                        'monto_total' => (float)$totales['total_monto'],
                        'promedio_cuota' => (float)$totales['promedio_cuota']
                    ],
                    'por_estatus' => $estatus,
                    'por_recurrencia' => $recurrencias,
                    'vencidas' => [
                        'cantidad' => (int)$vencidas['cuotas_vencidas'],
                        'monto' => (float)$vencidas['monto_vencido']
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

    // ===========================================
    // MÉTODOS AUXILIARES
    // ===========================================

    /**
     * Validar que una calle pertenezca a un condominio
     */
    private function validarCalleCondominio(int $id_calle, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT id_calle FROM calles WHERE id_calle = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_calle, $id_condominio]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Calcular fecha de vencimiento según recurrencia
     */
    private function calcularFechaVencimiento(string $recurrencia): string 
    {
        $fecha_base = new DateTime();
        
        switch ($recurrencia) {
            case 'mensual':
                $fecha_base->add(new DateInterval('P1M'));
                break;
            case 'trimestral':
                $fecha_base->add(new DateInterval('P3M'));
                break;
            case 'anual':
                $fecha_base->add(new DateInterval('P1Y'));
                break;
            case 'unica':
            default:
                $fecha_base->add(new DateInterval('P30D')); // 30 días por defecto
                break;
        }
        
        return $fecha_base->format('Y-m-d');
    }

    /**
     * Programar siguiente cuota para cuotas recurrentes
     */
    private function programarSiguienteCuota(int $id_cuota_base, array $data): bool 
    {
        // Esta función se podría implementar para crear automáticamente las siguientes cuotas
        // Por ahora solo registramos el log
        $this->logActivity('programar_siguiente', ['id_cuota_base' => $id_cuota_base, 'recurrencia' => $data['recurrencia']]);
        return true;
    }

    /**
     * Cambiar estatus de cuota
     */
    public function cambiarEstatus(int $id_cuota, string $nuevo_estatus): array 
    {
        try {
            $updated = $this->update($id_cuota, ['estatus' => $nuevo_estatus]);
            
            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estatus de la cuota'
                ];
            }
            
            $this->logActivity('cambiar_estatus', ['id_cuota' => $id_cuota, 'nuevo_estatus' => $nuevo_estatus]);
            
            return [
                'success' => true,
                'message' => 'Estatus de cuota actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcular total con IVA
     */
    public function calcularTotalConIVA(float $monto_base, float $iva_porcentaje): float 
    {
        return $monto_base + ($monto_base * ($iva_porcentaje / 100));
    }

    /**
     * Obtener próximas cuotas a vencer
     */
    public function getCuotasProximasVencer(int $id_condominio, int $dias = 7): array 
    {
        try {
            $fecha_limite = date('Y-m-d', strtotime("+{$dias} days"));
            
            $sql = "SELECT c.*, ca.nombre as calle_nombre
                    FROM {$this->tableName} c
                    INNER JOIN calles ca ON c.id_calle = ca.id_calle
                    WHERE c.id_condominio = ? 
                    AND c.fecha_vencimiento <= ? 
                    AND c.estatus = 'pendiente'
                    ORDER BY c.fecha_vencimiento ASC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_limite]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar cada resultado
            foreach ($results as &$result) {
                if (!empty($result['uuid_factura'])) {
                    $result['uuid_factura'] = $this->descomprimirYDesencriptarUUID($result['uuid_factura']);
                }
                // Calcular días restantes
                $result['dias_restantes'] = (new DateTime($result['fecha_vencimiento']))->diff(new DateTime())->days;
            }
            
            return [
                'success' => true,
                'cuotas_proximas' => $results,
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