<?php
/**
 * 📦 INVENTARIOS MODEL - Modelo de Inventarios
 * Manejo de inventarios con encriptación de datos y gestión de stock
 * Restricciones por condominio y control de cantidad actual
 * 
 * @package Cyberhole\Models\Financiero
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class InventariosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla inventarios
    public ?int $id_inventario;
    public ?int $id_compra;
    public ?int $id_condominio;
    public ?string $rfc;
    public ?string $nombre;
    public ?string $descripcion;
    public ?int $cantidad_actual;
    public ?string $unidad_medida;
    public ?int $tiempo_vida_dias;
    public ?string $fecha_alta;

    public function __construct(
        ?int $id_inventario = null,
        ?int $id_compra = null,
        ?int $id_condominio = null,
        ?string $rfc = null,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?int $cantidad_actual = 0,
        ?string $unidad_medida = 'pieza',
        ?int $tiempo_vida_dias = null,
        ?string $fecha_alta = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'inventarios';
        $this->primaryKey = 'id_inventario';
        $this->fillableFields = [
            'id_compra', 'id_condominio', 'rfc', 'nombre', 'descripcion',
            'cantidad_actual', 'unidad_medida', 'tiempo_vida_dias', 'fecha_alta'
        ];
        
        // Campos que se encriptan: RFC, nombre y descripción (datos sensibles)
        $this->encryptedFields = ['rfc', 'nombre', 'descripcion'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_inventario = $id_inventario;
        $this->id_compra = $id_compra;
        $this->id_condominio = $id_condominio;
        $this->rfc = $rfc;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->cantidad_actual = $cantidad_actual;
        $this->unidad_medida = $unidad_medida;
        $this->tiempo_vida_dias = $tiempo_vida_dias;
        $this->fecha_alta = $fecha_alta;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo item de inventario
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
            
            // Validar que no exista item duplicado (mismo nombre, RFC y condominio)
            if ($this->existeItemDuplicado($data['nombre'], $data['rfc'], $data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Ya existe un item de inventario con el mismo nombre y RFC en este condominio'
                ];
            }
            
            // Establecer fecha de alta si no se proporciona
            if (!isset($data['fecha_alta']) || empty($data['fecha_alta'])) {
                $data['fecha_alta'] = $this->getCurrentTimestamp();
            }
            
            // Validar cantidad inicial
            if (isset($data['cantidad_actual']) && $data['cantidad_actual'] < 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La cantidad actual no puede ser negativa'
                ];
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear el item de inventario'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_inventario' => $id]);
            
            return [
                'success' => true,
                'id_inventario' => $id,
                'message' => 'Item de inventario creado exitosamente'
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
     * Leer item de inventario por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Item de inventario no encontrado'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            // Agregar información adicional
            $result['stock_status'] = $this->getStockStatus($result['cantidad_actual']);
            $result['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($result);
            
            return [
                'success' => true,
                'inventario' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar item de inventario
     */
    public function updateInventario(int $id, array $data): array 
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
            
            // Validar cantidad si se está actualizando
            if (isset($data['cantidad_actual']) && $data['cantidad_actual'] < 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La cantidad actual no puede ser negativa'
                ];
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el item de inventario'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_inventario' => $id]);
            
            return [
                'success' => true,
                'message' => 'Item de inventario actualizado exitosamente'
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
     * Eliminar item de inventario
     */
    public function deleteInventario(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que no tenga stock actual
            $item = $this->findById($id);
            if ($item && $item['cantidad_actual'] > 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se puede eliminar un item con stock disponible'
                ];
            }
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el item de inventario'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_inventario' => $id]);
            
            return [
                'success' => true,
                'message' => 'Item de inventario eliminado exitosamente'
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
     * Obtener inventarios por condominio con paginación
     */
    public function getInventariosByCondominio(int $id_condominio, int $page = 1, int $limit = 10): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT i.*, c.descripcion as compra_descripcion, c.fecha_compra
                    FROM {$this->tableName} i
                    LEFT JOIN compras c ON i.id_compra = c.id_compra
                    WHERE i.id_condominio = ?
                    ORDER BY i.fecha_alta DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['stock_status'] = $this->getStockStatus($result['cantidad_actual']);
                $result['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($result);
            }
            
            // Contar total
            $total = $this->count(['id_condominio' => $id_condominio]);
            
            return [
                'success' => true,
                'inventarios' => $results,
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
     * Obtener inventarios por stock bajo
     */
    public function getInventariosStockBajo(int $id_condominio, int $limite_minimo = 5, int $limit = 10): array 
    {
        try {
            $sql = "SELECT i.*
                    FROM {$this->tableName} i
                    WHERE i.id_condominio = ? AND i.cantidad_actual <= ?
                    ORDER BY i.cantidad_actual ASC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limite_minimo, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['stock_status'] = $this->getStockStatus($result['cantidad_actual']);
                $result['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($result);
            }
            
            return [
                'success' => true,
                'inventarios_stock_bajo' => $results,
                'total' => count($results),
                'limite_minimo' => $limite_minimo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener inventarios próximos a vencer
     */
    public function getInventariosProximosVencer(int $id_condominio, int $dias_limite = 30, int $limit = 10): array 
    {
        try {
            $sql = "SELECT i.*
                    FROM {$this->tableName} i
                    WHERE i.id_condominio = ? 
                    AND i.tiempo_vida_dias IS NOT NULL 
                    AND DATEDIFF(DATE_ADD(i.fecha_alta, INTERVAL i.tiempo_vida_dias DAY), CURDATE()) <= ?
                    AND DATEDIFF(DATE_ADD(i.fecha_alta, INTERVAL i.tiempo_vida_dias DAY), CURDATE()) > 0
                    ORDER BY DATEDIFF(DATE_ADD(i.fecha_alta, INTERVAL i.tiempo_vida_dias DAY), CURDATE()) ASC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $dias_limite, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['stock_status'] = $this->getStockStatus($result['cantidad_actual']);
                $result['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($result);
            }
            
            return [
                'success' => true,
                'inventarios_proximos_vencer' => $results,
                'total' => count($results),
                'dias_limite' => $dias_limite
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener inventarios por fecha de alta
     */
    public function getInventariosByFechaAlta(int $id_condominio, string $fecha_inicio, string $fecha_fin, int $limit = 10): array 
    {
        try {
            $sql = "SELECT i.*
                    FROM {$this->tableName} i
                    WHERE i.id_condominio = ? AND DATE(i.fecha_alta) BETWEEN ? AND ?
                    ORDER BY i.fecha_alta DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fecha_inicio, $fecha_fin, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['stock_status'] = $this->getStockStatus($result['cantidad_actual']);
                $result['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($result);
            }
            
            return [
                'success' => true,
                'inventarios' => $results,
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
    // MÉTODOS DE GESTIÓN DE STOCK
    // ===========================================

    /**
     * Agregar cantidad al inventario
     */
    public function agregarStock(int $id_inventario, int $cantidad, string $motivo = 'Entrada de stock'): array 
    {
        try {
            $this->beginTransaction();
            
            if ($cantidad <= 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La cantidad a agregar debe ser mayor a cero'
                ];
            }
            
            $sql = "UPDATE {$this->tableName} SET cantidad_actual = cantidad_actual + ? WHERE id_inventario = ?";
            $stmt = $this->executeQuery($sql, [$cantidad, $id_inventario]);
            
            if ($stmt->rowCount() === 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el stock'
                ];
            }
            
            $this->commit();
            $this->logActivity('agregar_stock', ['id_inventario' => $id_inventario, 'cantidad' => $cantidad, 'motivo' => $motivo]);
            
            return [
                'success' => true,
                'message' => 'Stock agregado exitosamente'
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
     * Retirar cantidad del inventario
     */
    public function retirarStock(int $id_inventario, int $cantidad, string $motivo = 'Salida de stock'): array 
    {
        try {
            $this->beginTransaction();
            
            if ($cantidad <= 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La cantidad a retirar debe ser mayor a cero'
                ];
            }
            
            // Verificar stock disponible
            $item = $this->findById($id_inventario);
            if (!$item || $item['cantidad_actual'] < $cantidad) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Stock insuficiente para realizar la operación'
                ];
            }
            
            $sql = "UPDATE {$this->tableName} SET cantidad_actual = cantidad_actual - ? WHERE id_inventario = ?";
            $stmt = $this->executeQuery($sql, [$cantidad, $id_inventario]);
            
            if ($stmt->rowCount() === 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el stock'
                ];
            }
            
            $this->commit();
            $this->logActivity('retirar_stock', ['id_inventario' => $id_inventario, 'cantidad' => $cantidad, 'motivo' => $motivo]);
            
            return [
                'success' => true,
                'message' => 'Stock retirado exitosamente'
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
     * Ajustar stock (establecer cantidad exacta)
     */
    public function ajustarStock(int $id_inventario, int $nueva_cantidad, string $motivo = 'Ajuste de inventario'): array 
    {
        try {
            $this->beginTransaction();
            
            if ($nueva_cantidad < 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'La nueva cantidad no puede ser negativa'
                ];
            }
            
            $updated = $this->update($id_inventario, ['cantidad_actual' => $nueva_cantidad]);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo ajustar el stock'
                ];
            }
            
            $this->commit();
            $this->logActivity('ajustar_stock', ['id_inventario' => $id_inventario, 'nueva_cantidad' => $nueva_cantidad, 'motivo' => $motivo]);
            
            return [
                'success' => true,
                'message' => 'Stock ajustado exitosamente'
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
    // GETTERS Y SETTERS ESPECIALIZADOS
    // ===========================================

    /**
     * Obtener estadísticas de inventario por condominio
     */
    public function getEstadisticasByCondominio(int $id_condominio): array 
    {
        try {
            // Total de items y cantidad
            $sqlTotal = "SELECT COUNT(*) as total_items, SUM(cantidad_actual) as total_cantidad
                         FROM {$this->tableName}
                         WHERE id_condominio = ?";
            
            $stmtTotal = $this->executeQuery($sqlTotal, [$id_condominio]);
            $totales = $stmtTotal->fetch();
            
            // Items con stock bajo (menor a 5)
            $sqlStockBajo = "SELECT COUNT(*) as items_stock_bajo
                            FROM {$this->tableName}
                            WHERE id_condominio = ? AND cantidad_actual <= 5";
            
            $stmtStockBajo = $this->executeQuery($sqlStockBajo, [$id_condominio]);
            $stockBajo = $stmtStockBajo->fetch();
            
            // Items sin stock
            $sqlSinStock = "SELECT COUNT(*) as items_sin_stock
                           FROM {$this->tableName}
                           WHERE id_condominio = ? AND cantidad_actual = 0";
            
            $stmtSinStock = $this->executeQuery($sqlSinStock, [$id_condominio]);
            $sinStock = $stmtSinStock->fetch();
            
            // Items próximos a vencer (30 días)
            $sqlProximosVencer = "SELECT COUNT(*) as items_proximos_vencer
                                 FROM {$this->tableName}
                                 WHERE id_condominio = ? 
                                 AND tiempo_vida_dias IS NOT NULL 
                                 AND DATEDIFF(DATE_ADD(fecha_alta, INTERVAL tiempo_vida_dias DAY), CURDATE()) <= 30
                                 AND DATEDIFF(DATE_ADD(fecha_alta, INTERVAL tiempo_vida_dias DAY), CURDATE()) > 0";
            
            $stmtProximosVencer = $this->executeQuery($sqlProximosVencer, [$id_condominio]);
            $proximosVencer = $stmtProximosVencer->fetch();
            
            // Por unidad de medida
            $sqlUnidades = "SELECT unidad_medida, COUNT(*) as cantidad_items, SUM(cantidad_actual) as total_unidades
                           FROM {$this->tableName}
                           WHERE id_condominio = ?
                           GROUP BY unidad_medida";
            
            $stmtUnidades = $this->executeQuery($sqlUnidades, [$id_condominio]);
            $unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'totales' => [
                        'items' => (int)$totales['total_items'],
                        'cantidad_total' => (int)$totales['total_cantidad']
                    ],
                    'alertas' => [
                        'stock_bajo' => (int)$stockBajo['items_stock_bajo'],
                        'sin_stock' => (int)$sinStock['items_sin_stock'],
                        'proximos_vencer' => (int)$proximosVencer['items_proximos_vencer']
                    ],
                    'por_unidad' => $unidades
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
     * Buscar inventarios por nombre (encriptado)
     */
    public function buscarPorNombre(int $id_condominio, string $nombre_busqueda, int $limit = 10): array 
    {
        try {
            // Para búsqueda por nombre encriptado, obtenemos todos los items del condominio
            // y filtramos después de desencriptar
            $allItems = $this->findMany(['id_condominio' => $id_condominio], $limit * 2); // Obtener más para filtrar
            $matches = [];
            
            foreach ($allItems as $item) {
                $decryptedItem = $this->decryptSensitiveFields($item);
                
                // Buscar coincidencias en nombre o descripción
                if (stripos($decryptedItem['nombre'] ?? '', $nombre_busqueda) !== false ||
                    stripos($decryptedItem['descripcion'] ?? '', $nombre_busqueda) !== false) {
                    $decryptedItem['stock_status'] = $this->getStockStatus($decryptedItem['cantidad_actual']);
                    $decryptedItem['dias_vida_restantes'] = $this->calcularDiasVidaRestantes($decryptedItem);
                    $matches[] = $decryptedItem;
                    
                    if (count($matches) >= $limit) break;
                }
            }
            
            return [
                'success' => true,
                'inventarios' => $matches,
                'total' => count($matches),
                'busqueda' => $nombre_busqueda
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
     * Verificar si existe item duplicado
     */
    private function existeItemDuplicado(string $nombre, string $rfc, int $id_condominio): bool 
    {
        try {
            // Encriptar nombre y RFC para comparación
            $nombreEncriptado = $this->encryptField($nombre);
            $rfcEncriptado = $this->encryptField($rfc);
            
            $sql = "SELECT id_inventario FROM {$this->tableName} 
                    WHERE nombre = ? AND rfc = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$nombreEncriptado, $rfcEncriptado, $id_condominio]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener status del stock
     */
    private function getStockStatus(int $cantidad): string 
    {
        if ($cantidad === 0) {
            return 'SIN_STOCK';
        } elseif ($cantidad <= 5) {
            return 'STOCK_BAJO';
        } elseif ($cantidad <= 20) {
            return 'STOCK_MEDIO';
        } else {
            return 'STOCK_ALTO';
        }
    }

    /**
     * Calcular días de vida restantes
     */
    private function calcularDiasVidaRestantes(array $item): ?int 
    {
        if (!isset($item['tiempo_vida_dias']) || $item['tiempo_vida_dias'] === null) {
            return null;
        }
        
        $fechaVencimiento = new DateTime($item['fecha_alta']);
        $fechaVencimiento->add(new DateInterval('P' . $item['tiempo_vida_dias'] . 'D'));
        
        $hoy = new DateTime();
        $diff = $hoy->diff($fechaVencimiento);
        
        if ($fechaVencimiento < $hoy) {
            return -$diff->days; // Días vencidos (negativo)
        } else {
            return $diff->days; // Días restantes (positivo)
        }
    }

    /**
     * Obtener movimientos de stock (simulado - podría conectarse a una tabla de movimientos)
     */
    public function getMovimientosStock(int $id_inventario, int $limit = 10): array 
    {
        try {
            // Esta función podría implementarse para obtener un historial de movimientos
            // Por ahora retornamos información básica del item
            $item = $this->read($id_inventario);
            
            if (!$item['success']) {
                return $item;
            }
            
            return [
                'success' => true,
                'movimientos' => [
                    [
                        'fecha' => $item['inventario']['fecha_alta'],
                        'tipo' => 'CREACION',
                        'cantidad' => $item['inventario']['cantidad_actual'],
                        'motivo' => 'Creación inicial del item'
                    ]
                ],
                'total' => 1
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar reporte de inventario
     */
    public function generarReporteInventario(int $id_condominio): array 
    {
        try {
            $estadisticas = $this->getEstadisticasByCondominio($id_condominio);
            $stockBajo = $this->getInventariosStockBajo($id_condominio, 5, 50);
            $proximosVencer = $this->getInventariosProximosVencer($id_condominio, 30, 50);
            
            return [
                'success' => true,
                'reporte' => [
                    'fecha_generacion' => $this->getCurrentTimestamp(),
                    'id_condominio' => $id_condominio,
                    'estadisticas' => $estadisticas['estadisticas'] ?? [],
                    'alertas' => [
                        'items_stock_bajo' => $stockBajo['inventarios_stock_bajo'] ?? [],
                        'items_proximos_vencer' => $proximosVencer['inventarios_proximos_vencer'] ?? []
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