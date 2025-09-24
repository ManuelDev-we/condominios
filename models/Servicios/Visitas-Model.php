<?php
/**
 * 🚪 VISITAS MODEL - Modelo de Visitantes
 * Manejo de visitantes con foto_identificacion comprimida/encriptada
 * Control QR/MANUAL y seguimiento entrada/salida por condominio
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class VisitasModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla visitantes
    public ?int $id_visitante;
    public ?string $nombre;
    public ?string $foto_identificacion;
    public ?int $id_condominio;
    public ?int $id_casa;
    public ?string $forma_ingreso;
    public ?string $placas;
    public ?string $fecha_hora_qr_generado;
    public ?string $fecha_hora_entrada;
    public ?string $fecha_hora_salida;

    public function __construct(
        ?int $id_visitante = null,
        ?string $nombre = null,
        ?string $foto_identificacion = null,
        ?int $id_condominio = null,
        ?int $id_casa = null,
        ?string $forma_ingreso = 'MANUAL',
        ?string $placas = null,
        ?string $fecha_hora_qr_generado = null,
        ?string $fecha_hora_entrada = null,
        ?string $fecha_hora_salida = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'visitantes';
        $this->primaryKey = 'id_visitante';
        $this->fillableFields = [
            'nombre', 'foto_identificacion', 'id_condominio', 'id_casa', 
            'forma_ingreso', 'placas', 'fecha_hora_qr_generado', 
            'fecha_hora_entrada', 'fecha_hora_salida'
        ];
        
        // Campos que se comprimen y encriptan: foto_identificacion
        $this->encryptedFields = [];
        $this->compressedFields = ['foto_identificacion'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_visitante = $id_visitante;
        $this->nombre = $nombre;
        $this->foto_identificacion = $foto_identificacion;
        $this->id_condominio = $id_condominio;
        $this->id_casa = $id_casa;
        $this->forma_ingreso = $forma_ingreso ?? 'MANUAL';
        $this->placas = $placas;
        $this->fecha_hora_qr_generado = $fecha_hora_qr_generado;
        $this->fecha_hora_entrada = $fecha_hora_entrada;
        $this->fecha_hora_salida = $fecha_hora_salida;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * 🆕 Crear nueva visita
     */
    public function crearVisita(array $datos): array 
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

            // Validar casa pertenece al condominio
            if (!$this->validarCasaCondominio($datos['id_casa'], $datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'La casa especificada no pertenece al condominio',
                    'data' => null
                ];
            }

            // Validar forma de ingreso
            if (!in_array($datos['forma_ingreso'], ['QR', 'MANUAL'])) {
                return [
                    'success' => false,
                    'message' => 'La forma de ingreso debe ser QR o MANUAL',
                    'data' => null
                ];
            }

            // Procesar foto de identificación si existe
            if (!empty($datos['foto_identificacion'])) {
                $datos['foto_identificacion'] = $this->procesarFotoIdentificacion($datos['foto_identificacion']);
            }

            // Generar QR si la forma de ingreso es QR
            if ($datos['forma_ingreso'] === 'QR') {
                $datos['fecha_hora_qr_generado'] = date('Y-m-d H:i:s');
            }

            // Establecer fecha de entrada si no se proporciona
            if (empty($datos['fecha_hora_entrada'])) {
                $datos['fecha_hora_entrada'] = date('Y-m-d H:i:s');
            }

            $this->beginTransaction();
            
            $id = $this->create($datos);
            if ($id) {
                $this->commit();
                
                $nuevaVisita = $this->getVisitaById($id);
                
                return [
                    'success' => true,
                    'message' => 'Visita registrada exitosamente',
                    'data' => $nuevaVisita['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al registrar la visita',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en crearVisita: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 📋 Obtener visita por ID
     */
    public function getVisitaById(int $id): array 
    {
        try {
            $visita = $this->findById($id);
            
            if ($visita) {
                return [
                    'success' => true,
                    'message' => 'Visita encontrada',
                    'data' => $visita
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Visita no encontrada',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en getVisitaById: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * ✏️ Actualizar visita
     */
    public function actualizarVisita(int $id, array $datos): array 
    {
        try {
            // Verificar que existe
            $visitaExistente = $this->getVisitaById($id);
            if (!$visitaExistente['success']) {
                return $visitaExistente;
            }

            // Validar condominio si se proporciona
            if (isset($datos['id_condominio']) && !$this->validarExistenciaCondominio($datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => null
                ];
            }

            // Validar casa si se proporciona
            if (isset($datos['id_casa'])) {
                $id_condominio = $datos['id_condominio'] ?? $visitaExistente['data']['id_condominio'];
                if (!$this->validarCasaCondominio($datos['id_casa'], $id_condominio)) {
                    return [
                        'success' => false,
                        'message' => 'La casa especificada no pertenece al condominio',
                        'data' => null
                    ];
                }
            }

            // Validar forma de ingreso si se proporciona
            if (isset($datos['forma_ingreso']) && !in_array($datos['forma_ingreso'], ['QR', 'MANUAL'])) {
                return [
                    'success' => false,
                    'message' => 'La forma de ingreso debe ser QR o MANUAL',
                    'data' => null
                ];
            }

            // Procesar foto si se proporciona
            if (!empty($datos['foto_identificacion'])) {
                $datos['foto_identificacion'] = $this->procesarFotoIdentificacion($datos['foto_identificacion']);
            }

            $this->beginTransaction();
            
            $actualizado = $this->update($id, $datos);
            if ($actualizado) {
                $this->commit();
                
                $visitaActualizada = $this->getVisitaById($id);
                
                return [
                    'success' => true,
                    'message' => 'Visita actualizada exitosamente',
                    'data' => $visitaActualizada['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la visita',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en actualizarVisita: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 🗑️ Eliminar visita
     */
    public function eliminarVisita(int $id): array 
    {
        try {
            // Verificar que existe
            $visitaExistente = $this->getVisitaById($id);
            if (!$visitaExistente['success']) {
                return $visitaExistente;
            }

            $this->beginTransaction();
            
            $eliminado = $this->delete($id);
            if ($eliminado) {
                $this->commit();
                return [
                    'success' => true,
                    'message' => 'Visita eliminada exitosamente',
                    'data' => null
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al eliminar la visita',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en eliminarVisita: " . $e->getMessage());
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
     * 📋 Obtener visitas por condominio (paginado)
     */
    public function getVisitasByCondominio(int $id_condominio, int $pagina = 1): array 
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
            $visitas = $this->findWithPagination($conditions, $pagina, 10, ['fecha_hora_entrada' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Visitas por condominio obtenidas exitosamente',
                'data' => $visitas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getVisitasByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 🏠 Obtener visitas por casa y condominio
     */
    public function getVisitasByCasaCondominio(int $id_casa, int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            if (!$this->validarCasaCondominio($id_casa, $id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'La casa especificada no pertenece al condominio',
                    'data' => []
                ];
            }

            $conditions = [
                'id_casa' => $id_casa,
                'id_condominio' => $id_condominio
            ];
            
            $visitas = $this->findAll($conditions, ['fecha_hora_entrada' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Visitas por casa y condominio obtenidas exitosamente',
                'data' => $visitas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getVisitasByCasaCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 📱 Obtener visitas por forma de ingreso y condominio
     */
    public function getVisitasByFormaIngresoCondominio(string $forma_ingreso, int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            if (!in_array($forma_ingreso, ['QR', 'MANUAL'])) {
                return [
                    'success' => false,
                    'message' => 'La forma de ingreso debe ser QR o MANUAL',
                    'data' => []
                ];
            }

            $conditions = [
                'forma_ingreso' => $forma_ingreso,
                'id_condominio' => $id_condominio
            ];
            
            $visitas = $this->findAll($conditions, ['fecha_hora_entrada' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Visitas por forma de ingreso obtenidas exitosamente',
                'data' => $visitas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getVisitasByFormaIngresoCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 🕐 Obtener visitas activas (sin salida) por condominio
     */
    public function getVisitasActivasByCondominio(int $id_condominio): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_hora_salida IS NULL
                    ORDER BY fecha_hora_entrada DESC";
            
            $visitas = $this->executeQuery($sql, [$id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Visitas activas obtenidas exitosamente',
                'data' => $visitas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getVisitasActivasByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 📊 Obtener reporte de visitas por fechas y condominio
     */
    public function getReporteVisitasFechasCondominio(int $id_condominio, string $fecha_inicio, string $fecha_fin): array 
    {
        try {
            if (!$this->validarExistenciaCondominio($id_condominio)) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => []
                ];
            }

            // Validar fechas
            if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
                return [
                    'success' => false,
                    'message' => 'La fecha de inicio debe ser menor o igual a la fecha de fin',
                    'data' => []
                ];
            }

            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND DATE(fecha_hora_entrada) BETWEEN ? AND ?
                    ORDER BY fecha_hora_entrada DESC";
            
            $params = [$id_condominio, $fecha_inicio, $fecha_fin];
            $visitas = $this->executeQuery($sql, $params);
            
            return [
                'success' => true,
                'message' => 'Reporte de visitas por fechas obtenido exitosamente',
                'data' => $visitas
            ];
            
        } catch (Exception $e) {
            error_log("Error en getReporteVisitasFechasCondominio: " . $e->getMessage());
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
     * 📷 Procesar foto de identificación (comprimir y encriptar)
     */
    private function procesarFotoIdentificacion(string $foto): string 
    {
        try {
            return $this->compressAndEncryptFile($foto);
        } catch (Exception $e) {
            error_log("Error procesando foto de identificación: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Validar que la casa pertenece al condominio
     */
    private function validarCasaCondominio(int $id_casa, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT c.id_casa FROM casas c
                    INNER JOIN calles cl ON c.id_calle = cl.id_calle
                    WHERE c.id_casa = ? AND cl.id_condominio = ?";
            $resultado = $this->executeQuery($sql, [$id_casa, $id_condominio]);
            
            return !empty($resultado);
        } catch (Exception $e) {
            error_log("Error validando casa-condominio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🚪 Registrar salida de visitante
     */
    public function registrarSalida(int $id_visitante): array 
    {
        try {
            $datosActualizacion = ['fecha_hora_salida' => date('Y-m-d H:i:s')];
            return $this->actualizarVisita($id_visitante, $datosActualizacion);
            
        } catch (Exception $e) {
            error_log("Error en registrarSalida: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 📱 Generar código QR para visitante
     */
    public function generarCodigoQR(int $id_visitante): array 
    {
        try {
            $datosActualizacion = [
                'forma_ingreso' => 'QR',
                'fecha_hora_qr_generado' => date('Y-m-d H:i:s')
            ];
            
            return $this->actualizarVisita($id_visitante, $datosActualizacion);
            
        } catch (Exception $e) {
            error_log("Error en generarCodigoQR: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    // ===========================================
    // LIMPIEZA AUTOMÁTICA DE DATOS
    // ===========================================

    /**
     * 🧹 Limpiar datos antiguos (mayor a 8 años) por condominio
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

            $fecha_limite = date('Y-m-d H:i:s', strtotime('-8 years'));
            
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_hora_entrada < ?";
            
            $this->executeQuery($sql, [$id_condominio, $fecha_limite]);
            
            return [
                'success' => true,
                'message' => 'Limpieza de datos antiguos completada',
                'data' => [
                    'id_condominio' => $id_condominio,
                    'fecha_limite' => $fecha_limite
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en limpieza de visitantes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la limpieza de datos antiguos',
                'data' => null
            ];
        }
    }

    /**
     * 📊 Obtener estadísticas de visitantes
     */
    public function obtenerEstadisticasVisitantes(int $id_condominio): array 
    {
        try {
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar visitantes activos (sin salida)
            $sqlActivos = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? AND fecha_hora_salida IS NULL";
            $activos = $this->executeQuery($sqlActivos, [$id_condominio]);
            
            // Contar por forma de ingreso
            $sqlQR = "SELECT COUNT(*) as count FROM {$this->tableName} 
                     WHERE id_condominio = ? AND forma_ingreso = 'QR'";
            $qr = $this->executeQuery($sqlQR, [$id_condominio]);
            
            $sqlManual = "SELECT COUNT(*) as count FROM {$this->tableName} 
                         WHERE id_condominio = ? AND forma_ingreso = 'MANUAL'";
            $manual = $this->executeQuery($sqlManual, [$id_condominio]);
            
            // Contar visitantes con foto
            $sqlConFoto = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? AND foto_identificacion IS NOT NULL";
            $conFoto = $this->executeQuery($sqlConFoto, [$id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => [
                    'total_visitas' => $total,
                    'visitas_activas' => $activos[0]['count'] ?? 0,
                    'ingresos_qr' => $qr[0]['count'] ?? 0,
                    'ingresos_manual' => $manual[0]['count'] ?? 0,
                    'visitas_con_foto' => $conFoto[0]['count'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasVisitantes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }
}