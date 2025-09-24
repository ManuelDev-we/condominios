<?php
/**
 * 💰 FACTURACION CYBERHOLE MODEL - Modelo de Facturación
 * Manejo completo de facturación con encriptación de todos los campos varbinary
 * Búsquedas por condominio y manejo de datos sensibles SAT/CFDI
 * Integración con Stripe y sistemas fiscales mexicanos
 * 
 * @package Cyberhole\Models\Cyberhole
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../Base-Model.php';

class FacturacionCyberholeModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla facturacion
    public ?int $id_factura;
    public ?int $id_condominio;
    public ?int $id_suscripcion;
    public ?string $subtotal;
    public ?string $iva;
    public ?string $total;
    public ?string $moneda;
    public ?string $rfc_receptor;
    public ?string $razon_social_receptor;
    public ?string $regimen_fiscal_receptor;
    public ?string $cp_fiscal_receptor;
    public ?string $uso_cfdi;
    public ?string $uuid_cfdi;
    public ?string $archivo_pdf;
    public ?string $stripe_payment_id;
    public ?string $stripe_invoice_id;
    public ?string $stripe_customer_id;
    public ?string $estatus;
    public ?string $fecha_emision;
    public ?string $fecha_pago;

    public function __construct(
        ?int $id_factura = null,
        ?int $id_condominio = null,
        ?int $id_suscripcion = null,
        ?string $subtotal = null,
        ?string $iva = null,
        ?string $total = null,
        ?string $moneda = 'MXN',
        ?string $rfc_receptor = null,
        ?string $razon_social_receptor = null,
        ?string $regimen_fiscal_receptor = null,
        ?string $cp_fiscal_receptor = null,
        ?string $uso_cfdi = null,
        ?string $uuid_cfdi = null,
        ?string $archivo_pdf = null,
        ?string $stripe_payment_id = null,
        ?string $stripe_invoice_id = null,
        ?string $stripe_customer_id = null,
        ?string $estatus = 'PENDIENTE',
        ?string $fecha_emision = null,
        ?string $fecha_pago = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'facturacion';
        $this->primaryKey = 'id_factura';
        $this->fillableFields = [
            'id_condominio', 'id_suscripcion', 'subtotal', 'iva', 'total',
            'moneda', 'rfc_receptor', 'razon_social_receptor', 'regimen_fiscal_receptor',
            'cp_fiscal_receptor', 'uso_cfdi', 'uuid_cfdi', 'archivo_pdf',
            'stripe_payment_id', 'stripe_invoice_id', 'stripe_customer_id', 'estatus', 'fecha_pago'
        ];
        
        // Campos que se encriptan: todos los varbinary
        $this->encryptedFields = [
            'subtotal', 'iva', 'total', 'rfc_receptor', 'razon_social_receptor',
            'regimen_fiscal_receptor', 'cp_fiscal_receptor', 'uso_cfdi', 'uuid_cfdi',
            'stripe_payment_id', 'stripe_invoice_id', 'stripe_customer_id'
        ];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_factura = $id_factura;
        $this->id_condominio = $id_condominio;
        $this->id_suscripcion = $id_suscripcion;
        $this->subtotal = $subtotal;
        $this->iva = $iva;
        $this->total = $total;
        $this->moneda = $moneda;
        $this->rfc_receptor = $rfc_receptor;
        $this->razon_social_receptor = $razon_social_receptor;
        $this->regimen_fiscal_receptor = $regimen_fiscal_receptor;
        $this->cp_fiscal_receptor = $cp_fiscal_receptor;
        $this->uso_cfdi = $uso_cfdi;
        $this->uuid_cfdi = $uuid_cfdi;
        $this->archivo_pdf = $archivo_pdf;
        $this->stripe_payment_id = $stripe_payment_id;
        $this->stripe_invoice_id = $stripe_invoice_id;
        $this->stripe_customer_id = $stripe_customer_id;
        $this->estatus = $estatus ?? 'PENDIENTE';
        $this->fecha_emision = $fecha_emision;
        $this->fecha_pago = $fecha_pago;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nueva factura
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar archivo PDF si existe
            if (isset($data['pdf_file']) && $data['pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_encriptado'];
                }
                unset($data['pdf_file']);
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_factura' => $id, 'id_condominio' => $data['id_condominio'] ?? null]);
            
            return [
                'success' => true,
                'id_factura' => $id,
                'message' => 'Factura creada exitosamente'
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
     * Leer factura por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Factura no encontrada'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'factura' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar factura
     */
    public function updateFactura(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar archivo PDF si existe
            if (isset($data['pdf_file']) && $data['pdf_file']) {
                $pdfResult = $this->procesarArchivoPDF($data['pdf_file']);
                if ($pdfResult['success']) {
                    $data['archivo_pdf'] = $pdfResult['pdf_encriptado'];
                }
                unset($data['pdf_file']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la factura'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_factura' => $id]);
            
            return [
                'success' => true,
                'message' => 'Factura actualizada exitosamente'
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
     * Eliminar factura
     */
    public function deleteFactura(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la factura'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_factura' => $id]);
            
            return [
                'success' => true,
                'message' => 'Factura eliminada exitosamente'
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

    public function getId_factura(): ?int 
    {
        return $this->id_factura;
    }

    public function setId_factura(?int $id_factura): void 
    {
        $this->id_factura = $id_factura;
    }

    public function getId_condominio(): ?int 
    {
        return $this->id_condominio;
    }

    public function setId_condominio(?int $id_condominio): void 
    {
        $this->id_condominio = $id_condominio;
    }

    public function getId_suscripcion(): ?int 
    {
        return $this->id_suscripcion;
    }

    public function setId_suscripcion(?int $id_suscripcion): void 
    {
        $this->id_suscripcion = $id_suscripcion;
    }

    public function getSubtotal(): ?string 
    {
        return $this->subtotal;
    }

    public function setSubtotal(?string $subtotal): void 
    {
        $this->subtotal = $subtotal;
    }

    public function getIva(): ?string 
    {
        return $this->iva;
    }

    public function setIva(?string $iva): void 
    {
        $this->iva = $iva;
    }

    public function getTotal(): ?string 
    {
        return $this->total;
    }

    public function setTotal(?string $total): void 
    {
        $this->total = $total;
    }

    public function getEstatus(): ?string 
    {
        return $this->estatus;
    }

    public function setEstatus(?string $estatus): void 
    {
        $this->estatus = $estatus;
    }

    public function getRfc_receptor(): ?string 
    {
        return $this->rfc_receptor;
    }

    public function setRfc_receptor(?string $rfc_receptor): void 
    {
        $this->rfc_receptor = $rfc_receptor;
    }

    public function getUuid_cfdi(): ?string 
    {
        return $this->uuid_cfdi;
    }

    public function setUuid_cfdi(?string $uuid_cfdi): void 
    {
        $this->uuid_cfdi = $uuid_cfdi;
    }

    // ===========================================
    // BÚSQUEDAS ESPECÍFICAS
    // ===========================================

    /**
     * Obtener facturas por condominio
     */
    public function getFacturasByCondominio(int $id_condominio, string $estatus = null): array 
    {
        try {
            $conditions = ['id_condominio' => $id_condominio];
            if ($estatus) {
                $conditions['estatus'] = $estatus;
            }
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_condominio = ?";
            $params = [$id_condominio];
            
            if ($estatus) {
                $sql .= " AND estatus = ?";
                $params[] = $estatus;
            }
            
            $sql .= " ORDER BY fecha_emision DESC";
            
            $stmt = $this->executeQuery($sql, $params);
            $facturas = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($facturas as &$factura) {
                $factura = $this->decryptSensitiveFields($factura);
            }
            
            return [
                'success' => true,
                'facturas' => $facturas,
                'total_facturas' => count($facturas)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener facturas por suscripción
     */
    public function getFacturasBySuscripcion(int $id_suscripcion): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id_suscripcion = ? ORDER BY fecha_emision DESC";
            $stmt = $this->executeQuery($sql, [$id_suscripcion]);
            $facturas = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($facturas as &$factura) {
                $factura = $this->decryptSensitiveFields($factura);
            }
            
            return [
                'success' => true,
                'facturas' => $facturas,
                'total_facturas' => count($facturas)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener facturas por rango de fechas
     */
    public function getFacturasByRangoFechas(string $fecha_inicio, string $fecha_fin, int $id_condominio = null): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE fecha_emision BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
            
            if ($id_condominio) {
                $sql .= " AND id_condominio = ?";
                $params[] = $id_condominio;
            }
            
            $sql .= " ORDER BY fecha_emision DESC";
            
            $stmt = $this->executeQuery($sql, $params);
            $facturas = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($facturas as &$factura) {
                $factura = $this->decryptSensitiveFields($factura);
            }
            
            return [
                'success' => true,
                'facturas' => $facturas,
                'total_facturas' => count($facturas),
                'rango_fechas' => "Desde {$fecha_inicio} hasta {$fecha_fin}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener facturas pendientes de pago
     */
    public function getFacturasPendientes(int $id_condominio = null): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE estatus = 'PENDIENTE'";
            $params = [];
            
            if ($id_condominio) {
                $sql .= " AND id_condominio = ?";
                $params[] = $id_condominio;
            }
            
            $sql .= " ORDER BY fecha_emision ASC";
            
            $stmt = $this->executeQuery($sql, $params);
            $facturas = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($facturas as &$factura) {
                $factura = $this->decryptSensitiveFields($factura);
            }
            
            return [
                'success' => true,
                'facturas_pendientes' => $facturas,
                'total_pendientes' => count($facturas)
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
     * Marcar factura como pagada
     */
    public function marcarComoPagada(int $id_factura, string $stripe_payment_id = null): array 
    {
        try {
            $this->beginTransaction();
            
            $updateData = [
                'estatus' => 'PAGADA',
                'fecha_pago' => $this->getCurrentTimestamp()
            ];
            
            if ($stripe_payment_id) {
                $updateData['stripe_payment_id'] = $stripe_payment_id;
            }
            
            $updated = $this->update($id_factura, $updateData);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estatus de la factura'
                ];
            }
            
            $this->commit();
            $this->logActivity('payment_confirmed', ['id_factura' => $id_factura]);
            
            return [
                'success' => true,
                'message' => 'Factura marcada como pagada exitosamente'
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
     * Marcar factura como fallida
     */
    public function marcarComoFallida(int $id_factura, string $motivo = null): array 
    {
        try {
            $this->beginTransaction();
            
            $updated = $this->update($id_factura, ['estatus' => 'FALLIDA']);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estatus de la factura'
                ];
            }
            
            $this->commit();
            $this->logActivity('payment_failed', ['id_factura' => $id_factura, 'motivo' => $motivo]);
            
            return [
                'success' => true,
                'message' => 'Factura marcada como fallida'
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
     * Obtener reporte financiero por condominio
     */
    public function getReporteFinanciero(int $id_condominio, string $periodo = 'mensual'): array 
    {
        try {
            $fechaFormato = $periodo === 'anual' ? '%Y' : '%Y-%m';
            
            $sql = "SELECT 
                        DATE_FORMAT(fecha_emision, '{$fechaFormato}') as periodo,
                        COUNT(*) as total_facturas,
                        SUM(CASE WHEN estatus = 'PAGADA' THEN 1 ELSE 0 END) as facturas_pagadas,
                        SUM(CASE WHEN estatus = 'PENDIENTE' THEN 1 ELSE 0 END) as facturas_pendientes,
                        SUM(CASE WHEN estatus = 'FALLIDA' THEN 1 ELSE 0 END) as facturas_fallidas
                    FROM {$this->tableName} 
                    WHERE id_condominio = ?
                    GROUP BY DATE_FORMAT(fecha_emision, '{$fechaFormato}')
                    ORDER BY periodo DESC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $reporte = $stmt->fetchAll();
            
            return [
                'success' => true,
                'reporte_financiero' => $reporte,
                'periodo' => $periodo,
                'id_condominio' => $id_condominio
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
     * Procesar y encriptar archivo PDF
     */
    private function procesarArchivoPDF(array $pdfData): array 
    {
        try {
            $uploadResult = $this->processFileUpload($pdfData, 'uploads/facturas/');
            
            if (!$uploadResult['success']) {
                throw new Exception("Error procesando PDF: " . $uploadResult['error']);
            }
            
            // Encriptar base64 del PDF
            $pdfEncriptado = $this->encryptField($uploadResult['base64_data']);
            
            return [
                'success' => true,
                'pdf_encriptado' => $pdfEncriptado,
                'info_archivo' => $uploadResult
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    /**
     * Calcular totales de facturación
     */
    public function calcularTotales(float $subtotal, float $porcentaje_iva = 16.0): array 
    {
        $iva = round($subtotal * ($porcentaje_iva / 100), 2);
        $total = round($subtotal + $iva, 2);
        
        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'porcentaje_iva' => $porcentaje_iva
        ];
    }
}
?>
