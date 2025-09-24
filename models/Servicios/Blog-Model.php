<?php
/**
 * 📝 BLOG MODEL - Modelo de Blog del Condominio
 * Manejo de entradas de blog con imágenes comprimidas y encriptadas
 * Control de visibilidad por condominio y limpieza automática
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class BlogModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla blog
    public ?int $id_blog;
    public ?string $titulo;
    public ?string $contenido;
    public ?string $imagen;
    public ?string $visible_para;
    public ?int $creado_por_admin;
    public ?int $id_condominio;
    public ?string $fecha_creacion;

    public function __construct(
        ?int $id_blog = null,
        ?string $titulo = null,
        ?string $contenido = null,
        ?string $imagen = null,
        ?string $visible_para = null,
        ?int $creado_por_admin = null,
        ?int $id_condominio = null,
        ?string $fecha_creacion = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'blog';
        $this->primaryKey = 'id_blog';
        $this->fillableFields = [
            'titulo', 'contenido', 'imagen', 'visible_para', 
            'creado_por_admin', 'id_condominio', 'fecha_creacion'
        ];
        
        // Las imágenes se comprimen y encriptan
        $this->encryptedFields = [];
        $this->compressedFields = ['imagen']; // Campo de imagen que se comprime y encripta
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_blog = $id_blog;
        $this->titulo = $titulo;
        $this->contenido = $contenido;
        $this->imagen = $imagen;
        $this->visible_para = $visible_para; // "todos", "admin", "residentes"
        $this->creado_por_admin = $creado_por_admin;
        $this->id_condominio = $id_condominio;
        $this->fecha_creacion = $fecha_creacion;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva entrada de blog
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
            
            // Validar visible_para
            if (!in_array($data['visible_para'], ['todos', 'admin', 'residentes'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'visible_para debe ser: todos, admin o residentes'
                ];
            }
            
            // Procesar imagen si viene en el request
            if (isset($data['imagen']) && !empty($data['imagen'])) {
                $resultadoImagen = $this->procesarImagen($data['imagen']);
                if (!$resultadoImagen['success']) {
                    $this->rollback();
                    return $resultadoImagen;
                }
                $data['imagen'] = $resultadoImagen['archivo_procesado'];
            }
            
            // Limpiar datos antiguos antes de insertar
            $this->limpiarDatosAntiguos($data['id_condominio']);
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la entrada del blog'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_blog' => $id]);
            
            return [
                'success' => true,
                'id_blog' => $id,
                'message' => 'Entrada de blog creada exitosamente'
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
     * Leer entrada de blog por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Entrada de blog no encontrada'
                ];
            }
            
            // Procesar imagen para mostrar si existe
            if (!empty($result['imagen'])) {
                $imagenProcesada = $this->procesarImagenParaMostrar($result['imagen']);
                if ($imagenProcesada['success']) {
                    $result['imagen_url'] = $imagenProcesada['url'];
                }
            }
            
            return [
                'success' => true,
                'blog' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar entrada de blog
     */
    public function updateBlog(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que la entrada existe
            $blog = $this->findById($id);
            if (!$blog) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Entrada de blog no encontrada'
                ];
            }
            
            // Validar visible_para si se proporciona
            if (isset($data['visible_para']) && !in_array($data['visible_para'], ['todos', 'admin', 'residentes'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'visible_para debe ser: todos, admin o residentes'
                ];
            }
            
            // Procesar nueva imagen si viene en el request
            if (isset($data['imagen']) && !empty($data['imagen'])) {
                // Eliminar imagen anterior si existe
                if (!empty($blog['imagen'])) {
                    $this->eliminarImagenAnterior($blog['imagen']);
                }
                
                $resultadoImagen = $this->procesarImagen($data['imagen']);
                if (!$resultadoImagen['success']) {
                    $this->rollback();
                    return $resultadoImagen;
                }
                $data['imagen'] = $resultadoImagen['archivo_procesado'];
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la entrada del blog'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_blog' => $id]);
            
            return [
                'success' => true,
                'message' => 'Entrada de blog actualizada exitosamente'
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
     * Eliminar entrada de blog
     */
    public function delate(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            // Obtener la entrada para eliminar archivos asociados
            $blog = $this->findById($id);
            if ($blog && !empty($blog['imagen'])) {
                $this->eliminarImagenAnterior($blog['imagen']);
            }
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la entrada del blog'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_blog' => $id]);
            
            return [
                'success' => true,
                'message' => 'Entrada de blog eliminada exitosamente'
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
     * Obtener entradas de blog por condominio (paginado de 10 en 10)
     */
    public function getBlogsByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $results = $this->findMany($conditions, $limit, $offset, 'fecha_creacion DESC');
            
            // Procesar imágenes para mostrar
            foreach ($results as &$blog) {
                if (!empty($blog['imagen'])) {
                    $imagenProcesada = $this->procesarImagenParaMostrar($blog['imagen']);
                    if ($imagenProcesada['success']) {
                        $blog['imagen_url'] = $imagenProcesada['url'];
                    }
                }
            }
            
            // Contar total
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'blogs' => $results,
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
     * Obtener una entrada específica por ID y condominio
     */
    public function getBlogByIdCondominio(int $id_blog, int $id_condominio): array 
    {
        try {
            $conditions = [
                'id_blog' => $id_blog,
                'id_condominio' => $id_condominio
            ];
            
            $result = $this->findOne($conditions);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Entrada de blog no encontrada en el condominio especificado'
                ];
            }
            
            // Procesar imagen para mostrar
            if (!empty($result['imagen'])) {
                $imagenProcesada = $this->procesarImagenParaMostrar($result['imagen']);
                if ($imagenProcesada['success']) {
                    $result['imagen_url'] = $imagenProcesada['url'];
                }
            }
            
            return [
                'success' => true,
                'blog' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener blogs por visibilidad en un condominio
     */
    public function getBlogsByVisibilidadCondominio(string $visible_para, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Validar visible_para
            if (!in_array($visible_para, ['todos', 'admin', 'residentes'])) {
                return [
                    'success' => false,
                    'error' => 'visible_para debe ser: todos, admin o residentes'
                ];
            }
            
            $conditions = [
                'visible_para' => $visible_para,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset, 'fecha_creacion DESC');
            
            // Procesar imágenes para mostrar
            foreach ($results as &$blog) {
                if (!empty($blog['imagen'])) {
                    $imagenProcesada = $this->procesarImagenParaMostrar($blog['imagen']);
                    if ($imagenProcesada['success']) {
                        $blog['imagen_url'] = $imagenProcesada['url'];
                    }
                }
            }
            
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'blogs_visibilidad' => $results,
                'visible_para' => $visible_para,
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
     * Obtener blogs por administrador en un condominio
     */
    public function getBlogsByAdminCondominio(int $creado_por_admin, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $conditions = [
                'creado_por_admin' => $creado_por_admin,
                'id_condominio' => $id_condominio
            ];
            
            $results = $this->findMany($conditions, $limit, $offset, 'fecha_creacion DESC');
            
            // Procesar imágenes para mostrar
            foreach ($results as &$blog) {
                if (!empty($blog['imagen'])) {
                    $imagenProcesada = $this->procesarImagenParaMostrar($blog['imagen']);
                    if ($imagenProcesada['success']) {
                        $blog['imagen_url'] = $imagenProcesada['url'];
                    }
                }
            }
            
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'blogs_admin' => $results,
                'creado_por_admin' => $creado_por_admin,
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
     * Obtener blogs recientes por condominio
     */
    public function getBlogsRecientesByCondominio(int $id_condominio, int $limit = 5): array 
    {
        try {
            // Obtener blogs de los últimos 30 días
            $fechaLimite = date('Y-m-d H:i:s', strtotime('-30 days'));
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_creacion >= ? 
                    ORDER BY fecha_creacion DESC 
                    LIMIT {$limit}";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fechaLimite]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar imágenes para mostrar
            foreach ($results as &$blog) {
                if (!empty($blog['imagen'])) {
                    $imagenProcesada = $this->procesarImagenParaMostrar($blog['imagen']);
                    if ($imagenProcesada['success']) {
                        $blog['imagen_url'] = $imagenProcesada['url'];
                    }
                }
            }
            
            return [
                'success' => true,
                'blogs_recientes' => $results,
                'limite_dias' => 30,
                'cantidad' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar blogs por título o contenido en un condominio
     */
    public function buscarBlogsCondominio(string $termino, int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND (titulo LIKE ? OR contenido LIKE ?) 
                    ORDER BY fecha_creacion DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $searchTerm = "%{$termino}%";
            $stmt = $this->executeQuery($sql, [$id_condominio, $searchTerm, $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar imágenes para mostrar
            foreach ($results as &$blog) {
                if (!empty($blog['imagen'])) {
                    $imagenProcesada = $this->procesarImagenParaMostrar($blog['imagen']);
                    if ($imagenProcesada['success']) {
                        $blog['imagen_url'] = $imagenProcesada['url'];
                    }
                }
            }
            
            // Contar total de resultados
            $sqlCount = "SELECT COUNT(*) as total FROM {$this->tableName} 
                        WHERE id_condominio = ? 
                        AND (titulo LIKE ? OR contenido LIKE ?)";
            $stmtCount = $this->executeQuery($sqlCount, [$id_condominio, $searchTerm, $searchTerm]);
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            return [
                'success' => true,
                'blogs_busqueda' => $results,
                'termino_busqueda' => $termino,
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
     * Procesar imagen (comprimir y encriptar)
     */
    private function procesarImagen(string $imagen_data): array 
    {
        try {
            // Verificar si es base64 o ruta de archivo
            if (strpos($imagen_data, 'data:image/') === 0) {
                // Es base64, extraer y procesar
                $resultado = $this->compressAndEncryptFile($imagen_data, 'blog_images');
            } else {
                // Es ruta de archivo
                $resultado = $this->compressAndEncryptFile($imagen_data, 'blog_images');
            }
            
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al procesar la imagen: ' . $resultado['error']
                ];
            }
            
            return [
                'success' => true,
                'archivo_procesado' => $resultado['encrypted_path']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error procesando imagen: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar imagen para mostrar (descomprimir y desencriptar)
     */
    private function procesarImagenParaMostrar(string $encrypted_path): array 
    {
        try {
            $resultado = $this->decompressAndDecryptFile($encrypted_path);
            
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al procesar imagen para mostrar'
                ];
            }
            
            return [
                'success' => true,
                'url' => $resultado['public_url']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error mostrando imagen: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar imagen anterior
     */
    private function eliminarImagenAnterior(string $encrypted_path): void 
    {
        try {
            $this->deleteFile($encrypted_path);
        } catch (Exception $e) {
            error_log("Error eliminando imagen anterior: " . $e->getMessage());
        }
    }

    /**
     * Limpiar datos antiguos (más de 8 años)
     */
    private function limpiarDatosAntiguos(int $id_condominio): void 
    {
        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime('-8 years'));
            
            // Obtener blogs antiguos para eliminar sus imágenes
            $sql = "SELECT imagen FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_creacion < ? 
                    AND imagen IS NOT NULL AND imagen != ''";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $fechaLimite]);
            $blogsAntiguos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Eliminar imágenes asociadas
            foreach ($blogsAntiguos as $blog) {
                if (!empty($blog['imagen'])) {
                    $this->eliminarImagenAnterior($blog['imagen']);
                }
            }
            
            // Eliminar registros antiguos
            $sqlDelete = "DELETE FROM {$this->tableName} 
                         WHERE id_condominio = ? AND fecha_creacion < ?";
            
            $this->executeQuery($sqlDelete, [$id_condominio, $fechaLimite]);
            
            $this->logActivity('cleanup_blogs', [
                'id_condominio' => $id_condominio,
                'fecha_limite' => $fechaLimite,
                'blogs_eliminados' => count($blogsAntiguos)
            ]);
            
        } catch (Exception $e) {
            error_log("Error limpiando datos antiguos de blogs: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de blogs por condominio
     */
    public function obtenerEstadisticasBlogsCondominio(int $id_condominio): array 
    {
        try {
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar por visibilidad
            $visibilidades = ['todos', 'admin', 'residentes'];
            $estadisticasVisibilidad = [];
            
            foreach ($visibilidades as $visibilidad) {
                $conditionsVis = [
                    'id_condominio' => $id_condominio,
                    'visible_para' => $visibilidad
                ];
                $count = $this->count($conditionsVis);
                $estadisticasVisibilidad[$visibilidad] = $count;
            }
            
            // Contar autores únicos (administradores)
            $sqlAdmins = "SELECT COUNT(DISTINCT creado_por_admin) as count FROM {$this->tableName} 
                         WHERE id_condominio = ? AND creado_por_admin IS NOT NULL";
            $stmtAdmins = $this->executeQuery($sqlAdmins, [$id_condominio]);
            $adminsResult = $stmtAdmins->fetch(PDO::FETCH_ASSOC);
            $adminsUnicos = $adminsResult['count'];
            
            // Blogs con imagen
            $sqlConImagen = "SELECT COUNT(*) as count FROM {$this->tableName} 
                            WHERE id_condominio = ? AND imagen IS NOT NULL AND imagen != ''";
            $stmtConImagen = $this->executeQuery($sqlConImagen, [$id_condominio]);
            $conImagenResult = $stmtConImagen->fetch(PDO::FETCH_ASSOC);
            $blogsConImagen = $conImagenResult['count'];
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_blogs' => $total,
                    'admins_autores' => $adminsUnicos,
                    'blogs_con_imagen' => $blogsConImagen,
                    'porcentaje_con_imagen' => $total > 0 ? round(($blogsConImagen / $total) * 100, 2) : 0,
                    'por_visibilidad' => $estadisticasVisibilidad,
                    'promedio_blogs_por_admin' => $adminsUnicos > 0 ? round($total / $adminsUnicos, 2) : 0
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