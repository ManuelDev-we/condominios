<?php
/**
 * 🛒 COMPRAS MODEL - Modelo de Compras
 * Manejo de compras con encriptación de datos fiscales y compresión de archivos PDF
 * Restricciones por condominio y segmentación por fechas/empleados
 * 
 * @package Cyberhole\Models\Financiero
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class ComprasModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla compras
    public ?int $id_compra;
    public ?int $id_condominio;
    public ?int $id_inventario;
    public ?int $id_servicio;
    public ?string $tipo_compra;
    public ?int $cantidad;
    public ?string $fecha_compra;
    public ?string $descripcion;
    public ?float $monto_total;
    public ?float $iva;
    public ?int $id_emisor;
    public ?string $tipo_emisor;
    public ?string $rfc_emisor;
    public ?string $razon_social_emisor;
    // Alias para compatibilidad con test
    public ?string $proveedor_rfc;
    public ?string $regimen_fiscal_emisor;
    public ?string $cp_fiscal_emisor;
    public ?string $rfc_receptor;
    public ?string $razon_social_receptor;
    public ?string $regimen_fiscal_receptor;
    public ?string $cp_fiscal_receptor;
    public ?string $uso_cfdi;
    public ?string $forma_pago;
    public ?string $metodo_pago;
    public ?string $moneda;
    public ?string $uuid_factura;
    public ?string $archivo_pdf;
    public ?string $estatus;

    public function __construct(
        ?int $id_compra = null,
        ?int $id_condominio = null,
        ?int $id_inventario = null,
        ?int $id_servicio = null,
        ?string $tipo_compra = 'inventario',
        ?int $cantidad = null,
        ?string $fecha_compra = null,
        ?string $descripcion = null,
        ?float $monto_total = null,
        ?float $iva = 0.00,
        ?int $id_emisor = null,
        ?string $tipo_emisor = 'empleado',
        ?string $rfc_emisor = null,
        ?string $razon_social_emisor = null,
        ?string $regimen_fiscal_emisor = null,
        ?string $cp_fiscal_emisor = null,
        ?string $rfc_receptor = null,
        ?string $razon_social_receptor = null,
        ?string $regimen_fiscal_receptor = null,
        ?string $cp_fiscal_receptor = null,
        ?string $uso_cfdi = 'G03',
        ?string $forma_pago = '03',
        ?string $metodo_pago = 'PUE',
        ?string $moneda = 'MXN',
        ?string $uuid_factura = null,
        ?string $archivo_pdf = null,
        ?string $estatus = 'pendiente'
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'compras';
        $this->primaryKey = 'id_compra';
        $this->fillableFields = [
            'id_condominio', 'id_inventario', 'id_servicio', 'tipo_compra', 'cantidad',
            'fecha_compra', 'descripcion', 'monto_total', 'iva', 'id_emisor', 'tipo_emisor',
            'rfc_emisor', 'razon_social_emisor', 'regimen_fiscal_emisor', 'cp_fiscal_emisor',
            'rfc_receptor', 'razon_social_receptor', 'regimen_fiscal_receptor', 'cp_fiscal_receptor',
            'uso_cfdi', 'forma_pago', 'metodo_pago', 'moneda', 'uuid_factura', 'archivo_pdf', 'estatus'
        ];
        
        // Campos que se encriptan: descripción, RFC y razón social (datos fiscales sensibles)
        $this->encryptedFields = ['descripcion', 'rfc_emisor', 'razon_social_emisor', 'rfc_receptor', 'razon_social_receptor'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_compra = $id_compra;
        $this->id_condominio = $id_condominio;
        $this->id_inventario = $id_inventario;
        $this->id_servicio = $id_servicio;
        $this->tipo_compra = $tipo_compra;
        $this->cantidad = $cantidad;
        $this->fecha_compra = $fecha_compra;
        $this->descripcion = $descripcion;
        $this->monto_total = $monto_total;
        $this->iva = $iva;
        $this->id_emisor = $id_emisor;
        $this->tipo_emisor = $tipo_emisor;
        $this->rfc_emisor = $rfc_emisor;
        $this->razon_social_emisor = $razon_social_emisor;
        $this->regimen_fiscal_emisor = $regimen_fiscal_emisor;
        $this->cp_fiscal_emisor = $cp_fiscal_emisor;
        $this->rfc_receptor = $rfc_receptor;
        $this->razon_social_receptor = $razon_social_receptor;
        $this->regimen_fiscal_receptor = $regimen_fiscal_receptor;
        $this->cp_fiscal_receptor = $cp_fiscal_receptor;
        $this->uso_cfdi = $uso_cfdi;
        $this->forma_pago = $forma_pago;
        $this->metodo_pago = $metodo_pago;
        $this->moneda = $moneda;
        $this->uuid_factura = $uuid_factura;
        $this->archivo_pdf = $archivo_pdf;
        $this->estatus = $estatus;
        
        // Alias for test compatibility
        $this->proveedor_rfc = $rfc_emisor;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva compra
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
            
            // Procesar archivo PDF si existe
            if (isset($data['archivo_pdf_file']) && $data['archivo_pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['archivo_pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_comprimido'];
                }
                unset($data['archivo_pdf_file']);
            }
            
            // Establecer fecha de compra si no se proporciona
            if (!isset($data['fecha_compra']) || empty($data['fecha_compra'])) {
                $data['fecha_compra'] = $this->getCurrentTimestamp();
            }
            
            // Validar tipo de compra y relación
            if (!$this->validarTipoCompra($data)) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Tipo de compra inválido o falta relación con inventario/servicio'
                ];
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la compra'
                ];
            }
            
            // Actualizar inventario si es una compra de inventario
            if ($data['tipo_compra'] === 'inventario' && isset($data['id_inventario']) && isset($data['cantidad'])) {
                $this->actualizarInventario($data['id_inventario'], $data['cantidad']);
            }
            
            $this->commit();
            $this->logActivity('create', ['id_compra' => $id]);
            
            return [
                'success' => true,
                'id_compra' => $id,
                'message' => 'Compra creada exitosamente'
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
     * Leer compra por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Compra no encontrada'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'compra' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar compra
     */
    public function updateCompra(int $id, array $data): array 
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
            
            // Procesar archivo PDF si existe
            if (isset($data['archivo_pdf_file']) && $data['archivo_pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['archivo_pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_comprimido'];
                }
                unset($data['archivo_pdf_file']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la compra'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_compra' => $id]);
            
            return [
                'success' => true,
                'message' => 'Compra actualizada exitosamente'
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
     * Eliminar compra
     */
    public function deleteCompra(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la compra'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_compra' => $id]);
            
            return [
                'success' => true,
                'message' => 'Compra eliminada exitosamente'
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
     * Obtener compras por condominio con paginación
     */
    public function getComprasByCondominio(int $id_condominio, int $page = 1, int $limit = 10): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT c.*, 
                           CASE 
                               WHEN c.tipo_emisor = 'empleado' THEN e.nombres
                               WHEN c.tipo_emisor = 'admin' THEN a.nombres
                               ELSE 'N/A'
                           END as emisor_nombre
                    FROM {$this->tableName} c
                    LEFT JOIN empleados e ON c.id_emisor = e.id_empleado AND c.tipo_emisor = 'empleado'
                    LEFT JOIN admin a ON c.id_emisor = a.id_admin AND c.tipo_emisor = 'admin'
                    WHERE c.id_condominio = ?
                    ORDER BY c.fecha_compra DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            // Contar total
            $total = $this->count(['id_condominio' => $id_condominio]);
            
            return [
                'success' => true,
                'compras' => $results,
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
     * Obtener compras por empleado
     */
    public function getComprasByEmpleado(int $id_condominio, int $id_empleado, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_condominio = ? AND c.id_emisor = ? AND c.tipo_emisor = 'empleado'
                    ORDER BY c.fecha_compra DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $id_empleado, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'compras' => $results,
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
     * Obtener compras por proveedor RFC
     */
    public function getComprasByProveedor(int $id_condominio, string $proveedor_rfc, int $limit = 10): array 
    {
        try {
            // Encriptar RFC para búsqueda
            $rfcEncriptado = $this->encryptField($proveedor_rfc);
            
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_condominio = ? AND c.proveedor_rfc = ?
                    ORDER BY c.fecha_compra DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $rfcEncriptado, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'compras' => $results,
                'total' => count($results),
                'proveedor_rfc' => $proveedor_rfc
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener compras por rango de fechas
     */
    public function getComprasByFechas(int $id_condominio, string $fecha_inicio, string $fecha_fin, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_condominio = ? AND DATE(c.fecha_compra) BETWEEN ? AND ?
                    ORDER BY c.fecha_compra DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_inicio, $fecha_fin, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'compras' => $results,
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
     * Obtener compras por tipo
     */
    public function getComprasByTipo(int $id_condominio, string $tipo_compra, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_condominio = ? AND c.tipo_compra = ?
                    ORDER BY c.fecha_compra DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $tipo_compra, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'compras' => $results,
                'total' => count($results),
                'tipo_compra' => $tipo_compra
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
    public function recuperarArchivoPDF(int $id_compra, string $outputPath = null): array 
    {
        try {
            $compra = $this->findById($id_compra);
            
            if (!$compra || !$compra['archivo_pdf']) {
                return [
                    'success' => false,
                    'error' => 'Compra o archivo PDF no encontrado'
                ];
            }
            
            // El archivo ya viene desencriptado por BaseModel::findById
            $pdfData = $compra['archivo_pdf'];
            
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
    // GETTERS Y SETTERS ESPECIALIZADOS
    // ===========================================

    /**
     * Obtener estadísticas de compras por condominio
     */
    public function getEstadisticasByCondominio(int $id_condominio): array 
    {
        try {
            // Total de compras y monto
            $sqlTotal = "SELECT COUNT(*) as total_compras, SUM(monto_total) as total_monto,
                                AVG(monto_total) as promedio_compra
                         FROM {$this->tableName}
                         WHERE id_condominio = ?";
            
            $stmtTotal = $this->executeQuery($sqlTotal, [$id_condominio]);
            $totales = $stmtTotal->fetch();
            
            // Por tipo de compra
            $sqlTipos = "SELECT tipo_compra, COUNT(*) as cantidad, SUM(monto_total) as total_monto
                         FROM {$this->tableName}
                         WHERE id_condominio = ?
                         GROUP BY tipo_compra";
            
            $stmtTipos = $this->executeQuery($sqlTipos, [$id_condominio]);
            $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
            
            // Por estatus
            $sqlEstatus = "SELECT estatus, COUNT(*) as cantidad
                          FROM {$this->tableName}
                          WHERE id_condominio = ?
                          GROUP BY estatus";
            
            $stmtEstatus = $this->executeQuery($sqlEstatus, [$id_condominio]);
            $estatus = $stmtEstatus->fetchAll(PDO::FETCH_ASSOC);
            
            // Compras por mes (últimos 12 meses)
            $sqlMensual = "SELECT YEAR(fecha_compra) as año, MONTH(fecha_compra) as mes, 
                                  COUNT(*) as cantidad, SUM(monto_total) as total_monto
                           FROM {$this->tableName}
                           WHERE id_condominio = ? AND fecha_compra >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY YEAR(fecha_compra), MONTH(fecha_compra)
                           ORDER BY año DESC, mes DESC";
            
            $stmtMensual = $this->executeQuery($sqlMensual, [$id_condominio]);
            $mensual = $stmtMensual->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'totales' => [
                        'compras' => (int)$totales['total_compras'],
                        'monto_total' => (float)$totales['total_monto'],
                        'promedio_compra' => (float)$totales['promedio_compra']
                    ],
                    'por_tipo' => $tipos,
                    'por_estatus' => $estatus,
                    'mensual' => $mensual
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
     * Obtener compras pendientes por condominio
     */
    public function getComprasPendientes(int $id_condominio, int $limit = 10): array 
    {
        try {
            return $this->getComprasByEstatus($id_condominio, 'pendiente', $limit);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener compras por estatus
     */
    public function getComprasByEstatus(int $id_condominio, string $estatus, int $limit = 10): array 
    {
        try {
            $sql = "SELECT c.*
                    FROM {$this->tableName} c
                    WHERE c.id_condominio = ? AND c.estatus = ?
                    ORDER BY c.fecha_compra DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $estatus, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
            }
            
            return [
                'success' => true,
                'compras' => $results,
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

    // ===========================================
    // MÉTODOS AUXILIARES
    // ===========================================

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
     * Validar tipo de compra y relaciones
     */
    private function validarTipoCompra(array $data): bool 
    {
        if ($data['tipo_compra'] === 'inventario') {
            return isset($data['id_inventario']) && !empty($data['id_inventario']);
        } elseif ($data['tipo_compra'] === 'servicio') {
            return isset($data['id_servicio']) && !empty($data['id_servicio']);
        }
        return false;
    }

    /**
     * Actualizar inventario después de compra
     */
    private function actualizarInventario(int $id_inventario, int $cantidad): bool 
    {
        try {
            $sql = "UPDATE inventarios SET cantidad_actual = cantidad_actual + ? WHERE id_inventario = ?";
            $stmt = $this->executeQuery($sql, [$cantidad, $id_inventario]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cambiar estatus de compra
     */
    public function cambiarEstatus(int $id_compra, string $nuevo_estatus): array 
    {
        try {
            $updated = $this->update($id_compra, ['estatus' => $nuevo_estatus]);
            
            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estatus de la compra'
                ];
            }
            
            $this->logActivity('cambiar_estatus', ['id_compra' => $id_compra, 'nuevo_estatus' => $nuevo_estatus]);
            
            return [
                'success' => true,
                'message' => 'Estatus de compra actualizado exitosamente'
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
}
?>