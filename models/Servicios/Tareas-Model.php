<?php
/**
 * 📋 TAREAS MODEL - Modelo de Tareas del Condominio
 * Manejo de tareas de trabajadores con imagen comprimida/encriptada
 * Control por condominio y limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class TareasModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla tareas
    public ?int $id_tarea;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_trabajador;
    public ?string $descripcion;
    public ?string $imagen;

    public function __construct(
        ?int $id_tarea = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_trabajador = null,
        ?string $descripcion = null,
        ?string $imagen = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'tareas';
        $this->primaryKey = 'id_tarea';
        $this->fillableFields = [
            'id_condominio', 'id_calle', 'id_trabajador', 'descripcion', 'imagen'
        ];
        
        // Campos que se comprimen y encriptan: imagen
        $this->encryptedFields = [];
        $this->compressedFields = ['imagen'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_tarea = $id_tarea;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_trabajador = $id_trabajador;
        $this->descripcion = $descripcion;
        $this->imagen = $imagen;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * 🆕 Crear nueva tarea
     */
    public function crearTarea(array $datos): array 
    {
        try {
            // Validar condominio
            if (!$this->validarExistenciaCondominio($datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => null
                ];
            }

            // Validar calle pertenece al condominio
            if (!$this->validarCalleCondominio($datos['id_calle'], $datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'La calle especificada no pertenece al condominio',
                    'data' => null
                ];
            }

            // Procesar imagen si existe
            if (!empty($datos['imagen'])) {
                $datos['imagen'] = $this->procesarImagen($datos['imagen']);
            }

            $this->beginTransaction();
            
            $id = $this->create($datos);
            if ($id) {
                $this->commit();
                
                $nuevaTarea = $this->getTareaById($id);
                
                return [
                    'success' => true,
                    'message' => 'Tarea creada exitosamente',
                    'data' => $nuevaTarea['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al crear la tarea',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en crearTarea: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 📋 Obtener tarea por ID
     */
    public function getTareaById(int $id): array 
    {
        try {
            $tarea = $this->findById($id);
            
            if ($tarea) {
                return [
                    'success' => true,
                    'message' => 'Tarea encontrada',
                    'data' => $tarea
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en getTareaById: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * ✏️ Actualizar tarea
     */
    public function actualizarTarea(int $id, array $datos): array 
    {
        try {
            // Verificar que existe
            $tareaExistente = $this->getTareaById($id);
            if (!$tareaExistente['success']) {
                return $tareaExistente;
            }

            // Validar condominio si se proporciona
            if (isset($datos['id_condominio']) && !$this->validarExistenciaCondominio($datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => null
                ];
            }

            // Validar calle si se proporciona
            if (isset($datos['id_calle'])) {
                $id_condominio = $datos['id_condominio'] ?? $tareaExistente['data']['id_condominio'];
                if (!$this->validarCalleCondominio($datos['id_calle'], $id_condominio)) {
                    return [
                        'success' => false,
                        'message' => 'La calle especificada no pertenece al condominio',
                        'data' => null
                    ];
                }
            }

            // Procesar imagen si se proporciona
            if (!empty($datos['imagen'])) {
                $datos['imagen'] = $this->procesarImagen($datos['imagen']);
            }

            $this->beginTransaction();
            
            $actualizado = $this->update($id, $datos);
            if ($actualizado) {
                $this->commit();
                
                $tareaActualizada = $this->getTareaById($id);
                
                return [
                    'success' => true,
                    'message' => 'Tarea actualizada exitosamente',
                    'data' => $tareaActualizada['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la tarea',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en actualizarTarea: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 🗑️ Eliminar tarea
     */
    public function eliminarTarea(int $id): array 
    {
        try {
            // Verificar que existe
            $tareaExistente = $this->getTareaById($id);
            if (!$tareaExistente['success']) {
                return $tareaExistente;
            }

            $this->beginTransaction();
            
            $eliminado = $this->delete($id);
            if ($eliminado) {
                $this->commit();
                return [
                    'success' => true,
                    'message' => 'Tarea eliminada exitosamente',
                    'data' => null
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al eliminar la tarea',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en eliminarTarea: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    // ===========================================
    // GETTERS POR CONDOMINIO (RESTRICCIONES)
    // ===========================================

    /**
     * 📋 Obtener tareas por condominio (paginado)
     */
    public function getTareasByCondominio(int $id_condominio, int $pagina = 1): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            $conditions = ['id_condominio' => $id_condominio];
            $tareas = $this->findWithPagination($conditions, $pagina, 10, ['id_tarea' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Tareas por condominio obtenidas exitosamente',
                'data' => $tareas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getTareasByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 🏘️ Obtener tareas por calle y condominio
     */
    public function getTareasByCalleCondominio(int $id_calle, int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            if (!$this->validarCalleCondominio($id_calle, $id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'La calle especificada no pertenece al condominio',
                    'data' => []
                ];
            }

            $conditions = [
                'id_calle' => $id_calle,
                'id_condominio' => $id_condominio
            ];
            
            $tareas = $this->findAll($conditions, ['id_tarea' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Tareas por calle y condominio obtenidas exitosamente',
                'data' => $tareas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getTareasByCalleCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 👷 Obtener tareas por trabajador y condominio
     */
    public function getTareasByTrabajadorCondominio(int $id_trabajador, int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            $conditions = [
                'id_trabajador' => $id_trabajador,
                'id_condominio' => $id_condominio
            ];
            
            $tareas = $this->findAll($conditions, ['id_tarea' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Tareas por trabajador y condominio obtenidas exitosamente',
                'data' => $tareas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getTareasByTrabajadorCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }


    /**
     * 📊 Obtener reporte de tareas por trabajador y condominio
     */
    public function getReporteTareasTrabajadorCondominio(int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            $sql = "SELECT id_trabajador, COUNT(*) as total_tareas, 
                           COUNT(CASE WHEN imagen IS NOT NULL THEN 1 END) as tareas_con_imagen
                    FROM {$this->tableName} 
                    WHERE id_condominio = ?
                    GROUP BY id_trabajador
                    ORDER BY total_tareas DESC";
            
            $reporte = $this->executeQuery($sql, [$id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Reporte de tareas por trabajador obtenido exitosamente',
                'data' => $reporte
            ];
            
        } catch (Exception $e) {
            error_log("Error en getReporteTareasTrabajadorCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    // ===========================================
    // MÉTODOS ESPECIALIZADOS
    // ===========================================

    /**
     * 📷 Procesar imagen (comprimir y encriptar)
     */
    private function procesarImagen(string $imagen): string 
    {
        try {
            return $this->compressAndEncryptFile($imagen);
        } catch (Exception $e) {
            error_log("Error procesando imagen: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Validar que la calle pertenece al condominio
     */
    private function validarCalleCondominio(int $id_calle, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT id_calle FROM calles WHERE id_calle = ? AND id_condominio = ?";
            $resultado = $this->executeQuery($sql, [$id_calle, $id_condominio]);
            
            return !empty($resultado);
        } catch (Exception $e) {
            error_log("Error validando calle-condominio: " . $e->getMessage());
            return false;
        }
    }

    // ===========================================
    // LIMPIEZA AUTOMÁTICA DE DATOS
    // ===========================================

    /**
     * 🧹 Limpiar datos antiguos (mayor a 8 años) por condominio
     * Nota: Las tareas no tienen campo de fecha, por lo que usaremos el ID como referencia temporal
     */
    public function limpiarDatosAntiguos(int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => null
                ];
            }

            // Como la tabla no tiene fecha de creación, implementamos una limpieza basada en criterios alternativos
            // Mantenemos solo las últimas 1000 tareas por condominio para evitar acumulación excesiva
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND id_tarea NOT IN (
                        SELECT id_tarea FROM (
                            SELECT id_tarea FROM {$this->tableName} 
                            WHERE id_condominio = ? 
                            ORDER BY id_tarea DESC 
                            LIMIT 1000
                        ) as t
                    )";
            
            $this->executeQuery($sql, [$id_condominio, $id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Limpieza de datos antiguos completada (mantenidas últimas 1000 tareas)',
                'data' => [
                    'id_condominio' => $id_condominio,
                    'criterio' => 'Últimas 1000 tareas mantenidas'
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en limpieza de tareas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la limpieza de datos antiguos',
                'data' => null
            ];
        }
    }

    /**
     * 📊 Obtener estadísticas de tareas
     */
    public function obtenerEstadisticasTareas(int $id_condominio): array 
    {
        try {
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar tareas con imagen
            $sqlConImagen = "SELECT COUNT(*) as count FROM {$this->tableName} 
                            WHERE id_condominio = ? AND imagen IS NOT NULL";
            $conImagen = $this->executeQuery($sqlConImagen, [$id_condominio]);
            
            // Contar trabajadores únicos
            $sqlTrabajadores = "SELECT COUNT(DISTINCT id_trabajador) as count FROM {$this->tableName} 
                               WHERE id_condominio = ?";
            $trabajadores = $this->executeQuery($sqlTrabajadores, [$id_condominio]);
            
            // Contar calles con tareas
            $sqlCalles = "SELECT COUNT(DISTINCT id_calle) as count FROM {$this->tableName} 
                         WHERE id_condominio = ?";
            $calles = $this->executeQuery($sqlCalles, [$id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => [
                    'total_tareas' => $total,
                    'tareas_con_imagen' => $conImagen[0]['count'] ?? 0,
                    'trabajadores_activos' => $trabajadores[0]['count'] ?? 0,
                    'calles_con_tareas' => $calles[0]['count'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasTareas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }
}
