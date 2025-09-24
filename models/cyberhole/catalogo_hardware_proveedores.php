<?php
/**
 * 🛠️ CATALOGO HARDWARE PROVEEDORES MODEL - Modelo de Catálogo de Hardware
 * Manejo completo de catálogo con encriptación de codigo_hardware
 * Búsquedas por proveedor, nombre, tipo y rangos de precios
 * Paginación de 10 elementos por página
 * 
 * @package Cyberhole\Models\Cyberhole
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../Base-Model.php';

class CatalogoHardwareProveedoresModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla catalogo_hardware_proveedores
    public ?int $id_catalogo;
    public ?int $id_proveedor;
    public ?string $nombre_equipo;
    public ?string $tipo;
    public ?string $codigo_hardware;
    public ?string $descripcion;
    public ?string $foto_1;
    public ?string $foto_2;
    public ?string $foto_3;
    public ?string $foto_4;
    public ?float $precio_unitario;
    public ?int $stock_disponible;
    public ?string $fecha_registro;

    public function __construct(
        ?int $id_catalogo = null,
        ?int $id_proveedor = null,
        ?string $nombre_equipo = null,
        ?string $tipo = null,
        ?string $codigo_hardware = null,
        ?string $descripcion = null,
        ?string $foto_1 = null,
        ?string $foto_2 = null,
        ?string $foto_3 = null,
        ?string $foto_4 = null,
        ?float $precio_unitario = null,
        ?int $stock_disponible = null,
        ?string $fecha_registro = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'catalogo_hardware_proveedores';
        $this->primaryKey = 'id_catalogo';
        $this->fillableFields = [
            'id_proveedor', 'nombre_equipo', 'tipo', 'codigo_hardware', 
            'descripcion', 'foto_1', 'foto_2', 'foto_3', 'foto_4',
            'precio_unitario', 'stock_disponible'
        ];
        
        // Campos que se encriptan: codigo_hardware
        $this->encryptedFields = ['codigo_hardware'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_catalogo = $id_catalogo;
        $this->id_proveedor = $id_proveedor;
        $this->nombre_equipo = $nombre_equipo;
        $this->tipo = $tipo;
        $this->codigo_hardware = $codigo_hardware;
        $this->descripcion = $descripcion;
        $this->foto_1 = $foto_1;
        $this->foto_2 = $foto_2;
        $this->foto_3 = $foto_3;
        $this->foto_4 = $foto_4;
        $this->precio_unitario = $precio_unitario;
        $this->stock_disponible = $stock_disponible;
        $this->fecha_registro = $fecha_registro;
    }

    // ===========================================
    // MÉTODOS CRUD
    // ===========================================

    /**
     * Crear nuevo producto en catálogo
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar fotos si existen
            for ($i = 1; $i <= 4; $i++) {
                $fotoKey = "foto_{$i}_file";
                if (isset($data[$fotoKey]) && $data[$fotoKey]) {
                    $fotoResult = $this->procesarFoto($data[$fotoKey], $i);
                    if ($fotoResult['success']) {
                        $data["foto_{$i}"] = $fotoResult['foto_encriptada'];
                    }
                    unset($data[$fotoKey]);
                }
            }
            
            $id = $this->insert($data);
            
            $this->commit();
            $this->logActivity('create', ['id_catalogo' => $id]);
            
            return [
                'success' => true,
                'id_catalogo' => $id,
                'message' => 'Producto agregado al catálogo exitosamente'
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
     * Leer producto por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Producto no encontrado'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            return [
                'success' => true,
                'producto' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar producto
     */
    public function updateProducto(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Procesar fotos si existen
            for ($i = 1; $i <= 4; $i++) {
                $fotoKey = "foto_{$i}_file";
                if (isset($data[$fotoKey]) && $data[$fotoKey]) {
                    $fotoResult = $this->procesarFoto($data[$fotoKey], $i);
                    if ($fotoResult['success']) {
                        $data["foto_{$i}"] = $fotoResult['foto_encriptada'];
                    }
                    unset($data[$fotoKey]);
                }
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el producto'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_catalogo' => $id]);
            
            return [
                'success' => true,
                'message' => 'Producto actualizado exitosamente'
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
     * Eliminar producto
     */
    public function deleteProducto(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el producto'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_catalogo' => $id]);
            
            return [
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
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

    public function getId_catalogo(): ?int 
    {
        return $this->id_catalogo;
    }

    public function setId_catalogo(?int $id_catalogo): void 
    {
        $this->id_catalogo = $id_catalogo;
    }

    public function getId_proveedor(): ?int 
    {
        return $this->id_proveedor;
    }

    public function setId_proveedor(?int $id_proveedor): void 
    {
        $this->id_proveedor = $id_proveedor;
    }

    public function getNombre_equipo(): ?string 
    {
        return $this->nombre_equipo;
    }

    public function setNombre_equipo(?string $nombre_equipo): void 
    {
        $this->nombre_equipo = $nombre_equipo;
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

    public function getPrecio_unitario(): ?float 
    {
        return $this->precio_unitario;
    }

    public function setPrecio_unitario(?float $precio_unitario): void 
    {
        $this->precio_unitario = $precio_unitario;
    }

    public function getStock_disponible(): ?int 
    {
        return $this->stock_disponible;
    }

    public function setStock_disponible(?int $stock_disponible): void 
    {
        $this->stock_disponible = $stock_disponible;
    }

    // ===========================================
    // BÚSQUEDAS ESPECÍFICAS
    // ===========================================

    /**
     * Buscar productos por proveedor (paginado)
     */
    public function getProductosByProveedor(int $id_proveedor, int $pagina = 1): array 
    {
        try {
            $limite = 10;
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM {$this->tableName} WHERE id_proveedor = ? ORDER BY fecha_registro DESC LIMIT {$limite} OFFSET {$offset}";
            $stmt = $this->executeQuery($sql, [$id_proveedor]);
            $productos = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($productos as &$producto) {
                $producto = $this->decryptSensitiveFields($producto);
            }
            
            // Contar total
            $total = $this->count(['id_proveedor' => $id_proveedor]);
            $totalPaginas = ceil($total / $limite);
            
            return [
                'success' => true,
                'productos' => $productos,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_productos' => $total,
                    'productos_por_pagina' => $limite
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
     * Buscar productos por nombre (paginado)
     */
    public function getProductosByNombre(string $nombre, int $pagina = 1): array 
    {
        try {
            $limite = 10;
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM {$this->tableName} WHERE nombre_equipo LIKE ? ORDER BY nombre_equipo ASC LIMIT {$limite} OFFSET {$offset}";
            $stmt = $this->executeQuery($sql, ["%{$nombre}%"]);
            $productos = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($productos as &$producto) {
                $producto = $this->decryptSensitiveFields($producto);
            }
            
            // Contar total
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE nombre_equipo LIKE ?";
            $stmtCount = $this->executeQuery($sqlCount, ["%{$nombre}%"]);
            $total = $stmtCount->fetch()['total'];
            $totalPaginas = ceil($total / $limite);
            
            return [
                'success' => true,
                'productos' => $productos,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_productos' => $total,
                    'productos_por_pagina' => $limite
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
     * Buscar productos por tipo (paginado)
     */
    public function getProductosByTipo(string $tipo, int $pagina = 1): array 
    {
        try {
            $limite = 10;
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM {$this->tableName} WHERE tipo = ? ORDER BY nombre_equipo ASC LIMIT {$limite} OFFSET {$offset}";
            $stmt = $this->executeQuery($sql, [$tipo]);
            $productos = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($productos as &$producto) {
                $producto = $this->decryptSensitiveFields($producto);
            }
            
            // Contar total
            $total = $this->count(['tipo' => $tipo]);
            $totalPaginas = ceil($total / $limite);
            
            return [
                'success' => true,
                'productos' => $productos,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_productos' => $total,
                    'productos_por_pagina' => $limite
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
     * Buscar productos por rango de precios (paginado)
     */
    public function getProductosByRangoPrecios(float $precio_min, float $precio_max, int $pagina = 1): array 
    {
        try {
            $limite = 10;
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM {$this->tableName} WHERE precio_unitario BETWEEN ? AND ? ORDER BY precio_unitario ASC LIMIT {$limite} OFFSET {$offset}";
            $stmt = $this->executeQuery($sql, [$precio_min, $precio_max]);
            $productos = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($productos as &$producto) {
                $producto = $this->decryptSensitiveFields($producto);
            }
            
            // Contar total
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE precio_unitario BETWEEN ? AND ?";
            $stmtCount = $this->executeQuery($sqlCount, [$precio_min, $precio_max]);
            $total = $stmtCount->fetch()['total'];
            $totalPaginas = ceil($total / $limite);
            
            return [
                'success' => true,
                'productos' => $productos,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_productos' => $total,
                    'productos_por_pagina' => $limite,
                    'filtro_precio' => "Desde $${$precio_min} hasta $${$precio_max}"
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
     * Obtener todos los productos paginados
     */
    public function getAllProductosPaginados(int $pagina = 1): array 
    {
        try {
            $limite = 10;
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM {$this->tableName} ORDER BY fecha_registro DESC LIMIT {$limite} OFFSET {$offset}";
            $stmt = $this->executeQuery($sql);
            $productos = $stmt->fetchAll();
            
            // Desencriptar datos sensibles
            foreach ($productos as &$producto) {
                $producto = $this->decryptSensitiveFields($producto);
            }
            
            // Contar total
            $total = $this->count();
            $totalPaginas = ceil($total / $limite);
            
            return [
                'success' => true,
                'productos' => $productos,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_productos' => $total,
                    'productos_por_pagina' => $limite
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
     * Procesar y encriptar foto
     */
    private function procesarFoto(array $fotoData, int $numeroFoto): array 
    {
        try {
            $uploadResult = $this->processFileUpload($fotoData, 'uploads/catalogo/');
            
            if (!$uploadResult['success']) {
                throw new Exception("Error procesando foto {$numeroFoto}: " . $uploadResult['error']);
            }
            
            // Encriptar base64 de la foto
            $fotoEncriptada = $this->encryptField($uploadResult['base64_data']);
            
            return [
                'success' => true,
                'foto_encriptada' => $fotoEncriptada,
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
     * Obtener tipos únicos de productos
     */
    public function getTiposUnicos(): array 
    {
        try {
            $sql = "SELECT DISTINCT tipo FROM {$this->tableName} ORDER BY tipo ASC";
            $stmt = $this->executeQuery($sql);
            $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'success' => true,
                'tipos' => $tipos
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas del catálogo
     */
    public function getEstadisticasCatalogo(): array 
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_productos,
                        COUNT(DISTINCT id_proveedor) as total_proveedores,
                        COUNT(DISTINCT tipo) as total_tipos,
                        AVG(precio_unitario) as precio_promedio,
                        MIN(precio_unitario) as precio_minimo,
                        MAX(precio_unitario) as precio_maximo,
                        SUM(stock_disponible) as stock_total
                    FROM {$this->tableName}";
            
            $stmt = $this->executeQuery($sql);
            $estadisticas = $stmt->fetch();
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_productos' => (int) $estadisticas['total_productos'],
                    'total_proveedores' => (int) $estadisticas['total_proveedores'],
                    'total_tipos' => (int) $estadisticas['total_tipos'],
                    'precio_promedio' => round($estadisticas['precio_promedio'], 2),
                    'precio_minimo' => (float) $estadisticas['precio_minimo'],
                    'precio_maximo' => (float) $estadisticas['precio_maximo'],
                    'stock_total' => (int) $estadisticas['stock_total']
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
