<?php
/**
 * 💻 VENTAS EQUIPOS FISICOS MODEL - Modelo de Ventas de Hardware
 * Manejo completo de ventas de equipos físicos con búsquedas por condominio
 * Gestión de ventas de hardware con IVA y proveedores
 * 
 * @package Cyberhole\Models\Cyberhole
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../Base-Model.php';

class VentasEquiposFisicosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla ventas_equipos_fisicos
    public ?int $id_hardware;
    public ?float $iva;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_proveedor;
    public ?string $tipo;
    public ?string $codigo_hardware;
    public ?string $descripcion;
    public ?string $fecha_registro;

    public function __construct(
        ?int $id_hardware = null,
        ?float $iva = 0.00,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_proveedor = null,
        ?string $tipo = null,
        ?string $codigo_hardware = null,
        ?string $descripcion = null,
        ?string $fecha_registro = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'ventas_equipos_fisicos';
        $this->primaryKey = 'id_hardware';
        $this->fillableFields = [
            'iva', 'id_condominio', 'id_calle', 'id_proveedor',
            'tipo', 'codigo_hardware', 'descripcion'
        ];
        
        // No hay campos encriptados según especificaciones
        $this->encryptedFields = [];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_hardware = $id_hardware;
        $this->iva = $iva ?? 0.00;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_proveedor = $id_proveedor;
        $this->tipo = $tipo;
        $this->codigo_hardware = $codigo_hardware;
        $this->descripcion = $descripcion;
        $this->fecha_registro = $fecha_registro;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nueva venta de equipo
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar datos de la venta
            $validacion = $this->validarDatosVenta($data);
            if (!$validacion['valid']) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => $validacion['error']
                ];
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', [
                'id_hardware' => $id, 
                'id_condominio' => $data['id_condominio'],
                'tipo' => $data['tipo'] ?? null
            ]);
            
            return [
                'success' => true,
                'id_hardware' => $id,
                'message' => 'Venta de equipo registrada exitosamente'
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
     * Leer venta por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Venta no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'venta' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar venta
     */
    public function updateVenta(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar datos de la venta
            $validacion = $this->validarDatosVenta($data, true);
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
                    'error' => 'No se pudo actualizar la venta'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_hardware' => $id]);
            
            return [
                'success' => true,
                'message' => 'Venta actualizada exitosamente'
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
     * Eliminar venta
     */
    public function deleteVenta(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la venta'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_hardware' => $id]);
            
            return [
                'success' => true,
                'message' => 'Venta eliminada exitosamente'
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

    public function getId_hardware(): ?int 
    {
        return $this->id_hardware;
    }

    public function setId_hardware(?int $id_hardware): void 
    {
        $this->id_hardware = $id_hardware;
    }

    public function getIva(): ?float 
    {
        return $this->iva;
    }

    public function setIva(?float $iva): void 
    {
        $this->iva = $iva;
    }

    public function getId_condominio(): ?int 
    {
        return $this->id_condominio;
    }

    public function setId_condominio(?int $id_condominio): void 
    {
        $this->id_condominio = $id_condominio;
    }

    public function getId_calle(): ?int 
    {
        return $this->id_calle;
    }

    public function setId_calle(?int $id_calle): void 
    {
        $this->id_calle = $id_calle;
    }

    public function getId_proveedor(): ?int 
    {
        return $this->id_proveedor;
    }

    public function setId_proveedor(?int $id_proveedor): void 
    {
        $this->id_proveedor = $id_proveedor;
    }

    public function getTipo(): ?string 
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): void 
    {
        $this->tipo = $tipo;
    }

    public function getCodigo_hardware(): ?string 
    {
        return $this->codigo_hardware;
    }

    public function setCodigo_hardware(?string $codigo_hardware): void 
    {
        $this->codigo_hardware = $codigo_hardware;
    }

    public function getDescripcion(): ?string 
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void 
    {
        $this->descripcion = $descripcion;
    }

    // ===========================================
    // BÚSQUEDAS ESPECÍFICAS
    // ===========================================

    /**
     * Obtener ventas por condominio
     */
    public function getVentasByCondominio(int $id_condominio): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.id_condominio = ?
                    ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
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
     * Obtener ventas por calle
     */
    public function getVentasByCalle(int $id_calle): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.id_calle = ?
                    ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, [$id_calle]);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
                'id_calle' => $id_calle
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener ventas por proveedor
     */
    public function getVentasByProveedor(int $id_proveedor): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.id_proveedor = ?
                    ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, [$id_proveedor]);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
                'id_proveedor' => $id_proveedor
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener ventas por tipo de equipo
     */
    public function getVentasByTipo(string $tipo): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.tipo = ?
                    ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, [$tipo]);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
                'tipo' => $tipo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener ventas por rango de fechas
     */
    public function getVentasByRangoFechas(string $fecha_inicio, string $fecha_fin, int $id_condominio = null): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.fecha_registro BETWEEN ? AND ?";
            
            $params = [$fecha_inicio, $fecha_fin];
            
            if ($id_condominio) {
                $sql .= " AND v.id_condominio = ?";
                $params[] = $id_condominio;
            }
            
            $sql .= " ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, $params);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
                'rango_fechas' => "Desde {$fecha_inicio} hasta {$fecha_fin}",
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
     * Buscar ventas por código de hardware
     */
    public function getVentasByCodigo(string $codigo_hardware): array 
    {
        try {
            $sql = "SELECT v.*, 
                           DATE_FORMAT(v.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM {$this->tableName} v
                    WHERE v.codigo_hardware LIKE ?
                    ORDER BY v.fecha_registro DESC";
            
            $stmt = $this->executeQuery($sql, ["%{$codigo_hardware}%"]);
            $ventas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'ventas' => $ventas,
                'total_ventas' => count($ventas),
                'codigo_buscado' => $codigo_hardware
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
     * Obtener reporte de ventas por condominio
     */
    public function getReporteVentasCondominio(int $id_condominio, string $periodo = 'mensual'): array 
    {
        try {
            $fechaFormato = $periodo === 'anual' ? '%Y' : '%Y-%m';
            
            $sql = "SELECT 
                        DATE_FORMAT(fecha_registro, '{$fechaFormato}') as periodo,
                        COUNT(*) as total_ventas,
                        COUNT(DISTINCT tipo) as tipos_diferentes,
                        COUNT(DISTINCT id_proveedor) as proveedores_diferentes,
                        AVG(iva) as iva_promedio
                    FROM {$this->tableName}
                    WHERE id_condominio = ?
                    GROUP BY DATE_FORMAT(fecha_registro, '{$fechaFormato}')
                    ORDER BY periodo DESC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $reporte = $stmt->fetchAll();
            
            return [
                'success' => true,
                'reporte_ventas' => $reporte,
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

    /**
     * Obtener estadísticas generales de ventas
     */
    public function getEstadisticasVentas(): array 
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ventas,
                        COUNT(DISTINCT id_condominio) as condominios_con_ventas,
                        COUNT(DISTINCT tipo) as tipos_equipos_vendidos,
                        COUNT(DISTINCT id_proveedor) as proveedores_activos,
                        AVG(iva) as iva_promedio,
                        MIN(fecha_registro) as primera_venta,
                        MAX(fecha_registro) as ultima_venta
                    FROM {$this->tableName}";
            
            $stmt = $this->executeQuery($sql);
            $estadisticas = $stmt->fetch();
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_ventas' => (int) $estadisticas['total_ventas'],
                    'condominios_con_ventas' => (int) $estadisticas['condominios_con_ventas'],
                    'tipos_equipos_vendidos' => (int) $estadisticas['tipos_equipos_vendidos'],
                    'proveedores_activos' => (int) $estadisticas['proveedores_activos'],
                    'iva_promedio' => round($estadisticas['iva_promedio'], 2),
                    'primera_venta' => $estadisticas['primera_venta'],
                    'ultima_venta' => $estadisticas['ultima_venta']
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
     * Obtener top de tipos de equipos más vendidos
     */
    public function getTopTiposEquipos(int $limite = 10): array 
    {
        try {
            $sql = "SELECT 
                        tipo,
                        COUNT(*) as total_ventas,
                        AVG(iva) as iva_promedio
                    FROM {$this->tableName}
                    GROUP BY tipo
                    ORDER BY total_ventas DESC
                    LIMIT {$limite}";
            
            $stmt = $this->executeQuery($sql);
            $top_tipos = $stmt->fetchAll();
            
            return [
                'success' => true,
                'top_tipos_equipos' => $top_tipos,
                'limite' => $limite
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener top de proveedores con más ventas
     */
    public function getTopProveedores(int $limite = 10): array 
    {
        try {
            $sql = "SELECT 
                        id_proveedor,
                        COUNT(*) as total_ventas,
                        COUNT(DISTINCT tipo) as tipos_diferentes,
                        AVG(iva) as iva_promedio
                    FROM {$this->tableName}
                    WHERE id_proveedor IS NOT NULL
                    GROUP BY id_proveedor
                    ORDER BY total_ventas DESC
                    LIMIT {$limite}";
            
            $stmt = $this->executeQuery($sql);
            $top_proveedores = $stmt->fetchAll();
            
            return [
                'success' => true,
                'top_proveedores' => $top_proveedores,
                'limite' => $limite
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
     * Validar datos de la venta
     */
    private function validarDatosVenta(array $data, bool $esActualizacion = false): array 
    {
        $errores = [];
        
        // Validar id_condominio
        if (!$esActualizacion || isset($data['id_condominio'])) {
            if (empty($data['id_condominio']) || !is_numeric($data['id_condominio'])) {
                $errores[] = 'El ID del condominio es requerido y debe ser numérico';
            }
        }
        
        // Validar tipo
        if (!$esActualizacion || isset($data['tipo'])) {
            if (empty($data['tipo'])) {
                $errores[] = 'El tipo de equipo es requerido';
            } elseif (strlen($data['tipo']) > 100) {
                $errores[] = 'El tipo de equipo no puede exceder 100 caracteres';
            }
        }
        
        // Validar codigo_hardware
        if (!$esActualizacion || isset($data['codigo_hardware'])) {
            if (empty($data['codigo_hardware'])) {
                $errores[] = 'El código de hardware es requerido';
            } elseif (strlen($data['codigo_hardware']) > 256) {
                $errores[] = 'El código de hardware no puede exceder 256 caracteres';
            }
        }
        
        // Validar IVA
        if (isset($data['iva'])) {
            if ($data['iva'] < 0 || $data['iva'] > 100) {
                $errores[] = 'El IVA debe estar entre 0% y 100%';
            }
        }
        
        // Validar IDs opcionales
        if (isset($data['id_calle']) && !empty($data['id_calle'])) {
            if (!is_numeric($data['id_calle'])) {
                $errores[] = 'El ID de calle debe ser numérico';
            }
        }
        
        if (isset($data['id_proveedor']) && !empty($data['id_proveedor'])) {
            if (!is_numeric($data['id_proveedor'])) {
                $errores[] = 'El ID de proveedor debe ser numérico';
            }
        }
        
        return [
            'valid' => empty($errores),
            'error' => implode(', ', $errores)
        ];
    }

    /**
     * Obtener tipos únicos de equipos vendidos
     */
    public function getTiposUnicos(): array 
    {
        try {
            $sql = "SELECT DISTINCT tipo FROM {$this->tableName} ORDER BY tipo ASC";
            $stmt = $this->executeQuery($sql);
            $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'success' => true,
                'tipos_equipos' => $tipos
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si código de hardware ya existe
     */
    public function verificarCodigoExistente(string $codigo_hardware, int $excluir_id = null): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE codigo_hardware = ?";
            $params = [$codigo_hardware];
            
            if ($excluir_id) {
                $sql .= " AND id_hardware != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $this->executeQuery($sql, $params);
            $result = $stmt->fetch();
            
            return $result['total'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
