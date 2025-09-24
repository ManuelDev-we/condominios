<?php
/**
 * 🏥 SERVICIOS RESIDENTES MODEL - Modelo de Servicios de Residentes
 * Manejo de servicios solicitados por residentes con RFC encriptado
 * Fotos comprimidas/encriptadas y seguimiento de pagos
 * Control por condominio y limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class ServiciosResidentesModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla servicios_residentes
    public ?int $id_servicio_residente;
    public ?int $id_condominio;
    public ?int $id_calle;
    public ?int $id_casa;
    public ?int $id_persona;
    public ?string $rfc;
    public ?int $id_servicio_condominio;
    public ?float $monto;
    public ?string $descripcion;
    public ?string $fecha_servicio;
    public ?string $hora_servicio;
    public ?int $es_recurrente;
    public ?string $firma_token;
    public ?int $pagado;
    public ?string $iva;
    public ?string $foto1;
    public ?string $foto2;
    public ?string $foto_entrega;

    public function __construct(
        ?int $id_servicio_residente = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $id_casa = null,
        ?int $id_persona = null,
        ?string $rfc = null,
        ?int $id_servicio_condominio = null,
        ?float $monto = null,
        ?string $descripcion = null,
        ?string $fecha_servicio = null,
        ?string $hora_servicio = null,
        ?int $es_recurrente = null,
        ?string $firma_token = null,
        ?int $pagado = null,
        ?string $iva = null,
        ?string $foto1 = null,
        ?string $foto2 = null,
        ?string $foto_entrega = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'servicios_residentes';
        $this->primaryKey = 'id_servicio_residente';
        $this->fillableFields = [
            'id_condominio', 'id_calle', 'id_casa', 'id_persona', 'rfc', 
            'id_servicio_condominio', 'monto', 'descripcion', 'fecha_servicio', 
            'hora_servicio', 'es_recurrente', 'firma_token', 'pagado', 'iva',
            'foto1', 'foto2', 'foto_entrega'
        ];
        
        // Campos que se encriptan: RFC
        $this->encryptedFields = ['rfc'];
        // Campos que se comprimen y encriptan: fotos
        $this->compressedFields = ['foto1', 'foto2', 'foto_entrega'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_servicio_residente = $id_servicio_residente;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->id_casa = $id_casa;
        $this->id_persona = $id_persona;
        $this->rfc = $rfc;
        $this->id_servicio_condominio = $id_servicio_condominio;
        $this->monto = $monto;
        $this->descripcion = $descripcion;
        $this->fecha_servicio = $fecha_servicio;
        $this->hora_servicio = $hora_servicio;
        $this->es_recurrente = $es_recurrente;
        $this->firma_token = $firma_token;
        $this->pagado = $pagado;
        $this->iva = $iva;
        $this->foto1 = $foto1;
        $this->foto2 = $foto2;
        $this->foto_entrega = $foto_entrega;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * 🆕 Crear nuevo servicio de residente
     */
    public function crearServicioResidente(array $datos): array 
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

            // Validar servicio condominio
            if (!$this->validarServicioCondominio($datos['id_servicio_condominio'], $datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'El servicio del condominio especificado no existe',
                    'data' => null
                ];
            }

            // Procesar fotos si existen
            if (!empty($datos['foto1'])) {
                $datos['foto1'] = $this->procesarFoto($datos['foto1']);
            }
            if (!empty($datos['foto2'])) {
                $datos['foto2'] = $this->procesarFoto($datos['foto2']);
            }
            if (!empty($datos['foto_entrega'])) {
                $datos['foto_entrega'] = $this->procesarFoto($datos['foto_entrega']);
            }

            $this->beginTransaction();
            
            $id = $this->create($datos);
            if ($id) {
                $this->commit();
                
                $nuevoServicio = $this->getServicioResidenteById($id);
                
                return [
                    'success' => true,
                    'message' => 'Servicio de residente creado exitosamente',
                    'data' => $nuevoServicio['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al crear el servicio de residente',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en crearServicioResidente: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 📋 Obtener servicio de residente por ID
     */
    public function getServicioResidenteById(int $id): array 
    {
        try {
            $servicio = $this->findById($id);
            
            if ($servicio) {
                return [
                    'success' => true,
                    'message' => 'Servicio de residente encontrado',
                    'data' => $servicio
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Servicio de residente no encontrado',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en getServicioResidenteById: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * ✏️ Actualizar servicio de residente
     */
    public function actualizarServicioResidente(int $id, array $datos): array 
    {
        try {
            // Verificar que existe
            $servicioExistente = $this->getServicioResidenteById($id);
            if (!$servicioExistente['success']) {
                return $servicioExistente;
            }

            // Validar condominio si se proporciona
            if (isset($datos['id_condominio']) && !$this->validarExistenciaCondominio($datos['id_condominio'])) {
                return [
                    'success' => false,
                    'message' => 'El condominio especificado no existe',
                    'data' => null
                ];
            }

            // Validar servicio condominio si se proporciona
            if (isset($datos['id_servicio_condominio'])) {
                $id_condominio = $datos['id_condominio'] ?? $servicioExistente['data']['id_condominio'];
                if (!$this->validarServicioCondominio($datos['id_servicio_condominio'], $id_condominio)) {
                    return [
                        'success' => false,
                        'message' => 'El servicio del condominio especificado no existe',
                        'data' => null
                    ];
                }
            }

            // Procesar fotos si existen
            if (!empty($datos['foto1'])) {
                $datos['foto1'] = $this->procesarFoto($datos['foto1']);
            }
            if (!empty($datos['foto2'])) {
                $datos['foto2'] = $this->procesarFoto($datos['foto2']);
            }
            if (!empty($datos['foto_entrega'])) {
                $datos['foto_entrega'] = $this->procesarFoto($datos['foto_entrega']);
            }

            $this->beginTransaction();
            
            $actualizado = $this->update($id, $datos);
            if ($actualizado) {
                $this->commit();
                
                $servicioActualizado = $this->getServicioResidenteById($id);
                
                return [
                    'success' => true,
                    'message' => 'Servicio de residente actualizado exitosamente',
                    'data' => $servicioActualizado['data']
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al actualizar el servicio de residente',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en actualizarServicioResidente: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 🗑️ Eliminar servicio de residente
     */
    public function eliminarServicioResidente(int $id): array 
    {
        try {
            // Verificar que existe
            $servicioExistente = $this->getServicioResidenteById($id);
            if (!$servicioExistente['success']) {
                return $servicioExistente;
            }

            $this->beginTransaction();
            
            $eliminado = $this->delete($id);
            if ($eliminado) {
                $this->commit();
                return [
                    'success' => true,
                    'message' => 'Servicio de residente eliminado exitosamente',
                    'data' => null
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Error al eliminar el servicio de residente',
                    'data' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error en eliminarServicioResidente: " . $e->getMessage());
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
     * 📋 Obtener servicios de residentes por condominio (paginado)
     */
    public function getServiciosResidentesByCondominio(int $id_condominio, int $pagina = 1): array 
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
            $servicios = $this->findWithPagination($conditions, $pagina, 10, ['fecha_servicio' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Servicios de residentes obtenidos exitosamente',
                'data' => $servicios
            ];
            
        } catch (Exception $e) {
            error_log("Error en getServiciosResidentesByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

  

    /**
     * 💰 Obtener servicios pendientes de pago por condominio
     */
    public function getServiciosPendientesPagoByCondominio(int $id_condominio): array 
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
                'id_condominio' => $id_condominio,
                'pagado' => 0
            ];
            
            $servicios = $this->findAll($conditions, ['fecha_servicio' => 'ASC']);
            
            return [
                'success' => true,
                'message' => 'Servicios pendientes de pago obtenidos exitosamente',
                'data' => $servicios
            ];
            
        } catch (Exception $e) {
            error_log("Error en getServiciosPendientesPagoByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 🔄 Obtener servicios recurrentes por condominio
     */
    public function getServiciosRecurrentesByCondominio(int $id_condominio): array 
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
                'id_condominio' => $id_condominio,
                'es_recurrente' => 1
            ];
            
            $servicios = $this->findAll($conditions, ['fecha_servicio' => 'DESC']);
            
            return [
                'success' => true,
                'message' => 'Servicios recurrentes obtenidos exitosamente',
                'data' => $servicios
            ];
            
        } catch (Exception $e) {
            error_log("Error en getServiciosRecurrentesByCondominio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ];
        }
    }

    /**
     * 📊 Obtener reporte de servicios por fechas y condominio
     */
    public function getReporteServiciosFechasCondominio(int $id_condominio, string $fecha_inicio, string $fecha_fin): array 
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

            $sql = "SELECT sr.*, sc.descripcion as servicio_descripcion
                    FROM {$this->tableName} sr
                    LEFT JOIN servicios_condominio sc ON sr.id_servicio_condominio = sc.id_servicio_condominio
                    WHERE sr.id_condominio = ? 
                    AND sr.fecha_servicio BETWEEN ? AND ?
                    ORDER BY sr.fecha_servicio DESC, sr.hora_servicio DESC";
            
            $params = [$id_condominio, $fecha_inicio, $fecha_fin];
            $servicios = $this->executeQuery($sql, $params);
            
            return [
                'success' => true,
                'message' => 'Reporte de servicios por fechas obtenido exitosamente',
                'data' => $servicios
            ];
            
        } catch (Exception $e) {
            error_log("Error en getReporteServiciosFechasCondominio: " . $e->getMessage());
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
     * 📷 Procesar foto (comprimir y encriptar)
     */
    private function procesarFoto(string $foto): string 
    {
        try {
            return $this->compressAndEncryptFile($foto);
        } catch (Exception $e) {
            error_log("Error procesando foto: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Validar que el servicio del condominio existe y pertenece al condominio
     */
    private function validarServicioCondominio(int $id_servicio_condominio, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT id_servicio_condominio FROM servicios_condominio 
                    WHERE id_servicio_condominio = ? AND id_condominio = ?";
            $resultado = $this->executeQuery($sql, [$id_servicio_condominio, $id_condominio]);
            
            return !empty($resultado);
        } catch (Exception $e) {
            error_log("Error validando servicio condominio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 💰 Marcar servicio como pagado
     */
    public function marcarComoPagado(int $id_servicio_residente): array 
    {
        try {
            $datosActualizacion = ['pagado' => 1];
            return $this->actualizarServicioResidente($id_servicio_residente, $datosActualizacion);
            
        } catch (Exception $e) {
            error_log("Error en marcarComoPagado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }

    /**
     * 📝 Actualizar firma token
     */
    public function actualizarFirmaToken(int $id_servicio_residente, string $firma_token): array 
    {
        try {
            $datosActualizacion = ['firma_token' => $firma_token];
            return $this->actualizarServicioResidente($id_servicio_residente, $datosActualizacion);
            
        } catch (Exception $e) {
            error_log("Error en actualizarFirmaToken: " . $e->getMessage());
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

            $fecha_limite = date('Y-m-d', strtotime('-8 years'));
            
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE id_condominio = ? AND fecha_servicio < ?";
            
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
            error_log("Error en limpieza de servicios residentes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la limpieza de datos antiguos',
                'data' => null
            ];
        }
    }

    /**
     * 📊 Obtener estadísticas de servicios de residentes
     */
    public function obtenerEstadisticasServiciosResidentes(int $id_condominio): array 
    {
        try {
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar servicios pagados
            $conditionsPagados = ['id_condominio' => $id_condominio, 'pagado' => 1];
            $pagados = $this->count($conditionsPagados);
            
            // Contar servicios pendientes
            $conditionsPendientes = ['id_condominio' => $id_condominio, 'pagado' => 0];
            $pendientes = $this->count($conditionsPendientes);
            
            // Contar servicios recurrentes
            $conditionsRecurrentes = ['id_condominio' => $id_condominio, 'es_recurrente' => 1];
            $recurrentes = $this->count($conditionsRecurrentes);
            
            // Sumar montos totales
            $sqlMontos = "SELECT 
                            SUM(monto) as monto_total,
                            SUM(CASE WHEN pagado = 1 THEN monto ELSE 0 END) as monto_pagado,
                            SUM(CASE WHEN pagado = 0 THEN monto ELSE 0 END) as monto_pendiente
                          FROM {$this->tableName} 
                          WHERE id_condominio = ?";
            $montos = $this->executeQuery($sqlMontos, [$id_condominio]);
            
            return [
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => [
                    'total_servicios' => $total,
                    'servicios_pagados' => $pagados,
                    'servicios_pendientes' => $pendientes,
                    'servicios_recurrentes' => $recurrentes,
                    'monto_total' => $montos[0]['monto_total'] ?? 0,
                    'monto_pagado' => $montos[0]['monto_pagado'] ?? 0,
                    'monto_pendiente' => $montos[0]['monto_pendiente'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasServiciosResidentes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'data' => null
            ];
        }
    }
}