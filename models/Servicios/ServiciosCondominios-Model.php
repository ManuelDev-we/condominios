<?php
/**
 * 🏢 SERVICIOS CONDOMINIOS MODEL - Modelo de Servicios del Condominio
 * Manejo de servicios con fotos comprimidas/encriptadas y horarios de atención
 * Control por condominio y limpieza automática de datos antiguos
 * 
 * @package Cyberhole\Models\Servicios
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class ServiciosCondominiosModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla servicios_condominio
    public ?int $id_servicio_condominio;
    public ?int $id_condominio;
    public ?string $descripcion;
    public ?string $foto1;
    public ?string $foto2;
    public ?string $foto3;
    public ?string $foto4;
    public ?string $foto5;
    public ?string $lunes_apertura;
    public ?string $lunes_cierre;
    public ?string $martes_apertura;
    public ?string $martes_cierre;
    public ?string $miercoles_apertura;
    public ?string $miercoles_cierre;
    public ?string $jueves_apertura;
    public ?string $jueves_cierre;
    public ?string $viernes_apertura;
    public ?string $viernes_cierre;
    public ?string $sabado_apertura;
    public ?string $sabado_cierre;
    public ?string $domingo_apertura;
    public ?string $domingo_cierre;

    public function __construct(
        ?int $id_servicio_condominio = null,
        ?int $id_condominio = null,
        ?string $descripcion = null,
        ?string $foto1 = null,
        ?string $foto2 = null,
        ?string $foto3 = null,
        ?string $foto4 = null,
        ?string $foto5 = null,
        ?string $lunes_apertura = null,
        ?string $lunes_cierre = null,
        ?string $martes_apertura = null,
        ?string $martes_cierre = null,
        ?string $miercoles_apertura = null,
        ?string $miercoles_cierre = null,
        ?string $jueves_apertura = null,
        ?string $jueves_cierre = null,
        ?string $viernes_apertura = null,
        ?string $viernes_cierre = null,
        ?string $sabado_apertura = null,
        ?string $sabado_cierre = null,
        ?string $domingo_apertura = null,
        ?string $domingo_cierre = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'servicios_condominio';
        $this->primaryKey = 'id_servicio_condominio';
        $this->fillableFields = [
            'id_condominio', 'descripcion', 'foto1', 'foto2', 'foto3', 'foto4', 'foto5',
            'lunes_apertura', 'lunes_cierre', 'martes_apertura', 'martes_cierre',
            'miercoles_apertura', 'miercoles_cierre', 'jueves_apertura', 'jueves_cierre',
            'viernes_apertura', 'viernes_cierre', 'sabado_apertura', 'sabado_cierre',
            'domingo_apertura', 'domingo_cierre'
        ];
        
        // Las fotos se comprimen y encriptan
        $this->encryptedFields = [];
        $this->compressedFields = ['foto1', 'foto2', 'foto3', 'foto4', 'foto5'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_servicio_condominio = $id_servicio_condominio;
        $this->id_condominio = $id_condominio;
        $this->descripcion = $descripcion;
        $this->foto1 = $foto1;
        $this->foto2 = $foto2;
        $this->foto3 = $foto3;
        $this->foto4 = $foto4;
        $this->foto5 = $foto5;
        $this->lunes_apertura = $lunes_apertura;
        $this->lunes_cierre = $lunes_cierre;
        $this->martes_apertura = $martes_apertura;
        $this->martes_cierre = $martes_cierre;
        $this->miercoles_apertura = $miercoles_apertura;
        $this->miercoles_cierre = $miercoles_cierre;
        $this->jueves_apertura = $jueves_apertura;
        $this->jueves_cierre = $jueves_cierre;
        $this->viernes_apertura = $viernes_apertura;
        $this->viernes_cierre = $viernes_cierre;
        $this->sabado_apertura = $sabado_apertura;
        $this->sabado_cierre = $sabado_cierre;
        $this->domingo_apertura = $domingo_apertura;
        $this->domingo_cierre = $domingo_cierre;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nuevo servicio de condominio
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
            
            // Validar horarios
            $validacionHorarios = $this->validarHorarios($data);
            if (!$validacionHorarios['valid']) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => $validacionHorarios['error']
                ];
            }
            
            // Procesar fotos si vienen en el request
            $fotosProcessed = $this->procesarFotos($data);
            if (!$fotosProcessed['success']) {
                $this->rollback();
                return $fotosProcessed;
            }
            $data = array_merge($data, $fotosProcessed['fotos_procesadas']);
            
            // Limpiar datos antiguos antes de insertar
            $this->limpiarDatosAntiguos($data['id_condominio']);
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear el servicio del condominio'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_servicio_condominio' => $id]);
            
            return [
                'success' => true,
                'id_servicio_condominio' => $id,
                'message' => 'Servicio del condominio creado exitosamente'
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
     * Leer servicio de condominio por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Servicio del condominio no encontrado'
                ];
            }
            
            // Procesar fotos para mostrar
            $result = $this->procesarFotosParaMostrar($result);
            
            return [
                'success' => true,
                'servicio_condominio' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar servicio de condominio
     */
    public function updateServicioCondominio(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que el servicio existe
            $servicio = $this->findById($id);
            if (!$servicio) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Servicio del condominio no encontrado'
                ];
            }
            
            // Validar horarios si se proporcionan
            if ($this->tieneHorarios($data)) {
                $validacionHorarios = $this->validarHorarios($data);
                if (!$validacionHorarios['valid']) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => $validacionHorarios['error']
                    ];
                }
            }
            
            // Procesar nuevas fotos si vienen en el request
            if ($this->tieneFotos($data)) {
                // Eliminar fotos anteriores
                $this->eliminarFotosAnteriores($servicio);
                
                $fotosProcessed = $this->procesarFotos($data);
                if (!$fotosProcessed['success']) {
                    $this->rollback();
                    return $fotosProcessed;
                }
                $data = array_merge($data, $fotosProcessed['fotos_procesadas']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el servicio del condominio'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_servicio_condominio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Servicio del condominio actualizado exitosamente'
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
     * Eliminar servicio de condominio
     */
    public function delate(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            // Obtener el servicio para eliminar archivos asociados
            $servicio = $this->findById($id);
            if ($servicio) {
                $this->eliminarFotosAnteriores($servicio);
            }
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar el servicio del condominio'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_servicio_condominio' => $id]);
            
            return [
                'success' => true,
                'message' => 'Servicio del condominio eliminado exitosamente'
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
     * Obtener servicios de condominio por condominio (paginado de 10 en 10)
     */
    public function getServiciosCondominiosByCondominio(int $id_condominio, int $page = 1): array 
    {
        try {
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Limpiar datos antiguos
            $this->limpiarDatosAntiguos($id_condominio);
            
            $conditions = ['id_condominio' => $id_condominio];
            $results = $this->findMany($conditions, $limit, $offset);
            
            // Procesar fotos para mostrar
            foreach ($results as &$servicio) {
                $servicio = $this->procesarFotosParaMostrar($servicio);
            }
            
            // Contar total
            $total = $this->count($conditions);
            
            return [
                'success' => true,
                'servicios_condominios' => $results,
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
     * Obtener un servicio específico por ID y condominio
     */
    public function getServicioCondominioByIdCondominio(int $id_servicio_condominio, int $id_condominio): array 
    {
        try {
            $conditions = [
                'id_servicio_condominio' => $id_servicio_condominio,
                'id_condominio' => $id_condominio
            ];
            
            $result = $this->findOne($conditions);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Servicio del condominio no encontrado en el condominio especificado'
                ];
            }
            
            // Procesar fotos para mostrar
            $result = $this->procesarFotosParaMostrar($result);
            
            return [
                'success' => true,
                'servicio_condominio' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener servicios abiertos hoy por condominio
     */
    public function getServiciosAbiertosHoyByCondominio(int $id_condominio): array 
    {
        try {
            $diaSemana = strtolower(date('l')); // monday, tuesday, etc.
            $diaSpanish = $this->traducirDiaSemana($diaSemana);
            $horaActual = date('H:i:s');
            
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND {$diaSpanish}_apertura IS NOT NULL 
                    AND {$diaSpanish}_cierre IS NOT NULL 
                    AND {$diaSpanish}_apertura <= ? 
                    AND {$diaSpanish}_cierre >= ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $horaActual, $horaActual]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar fotos para mostrar
            foreach ($results as &$servicio) {
                $servicio = $this->procesarFotosParaMostrar($servicio);
                $servicio['hora_apertura_hoy'] = $servicio["{$diaSpanish}_apertura"];
                $servicio['hora_cierre_hoy'] = $servicio["{$diaSpanish}_cierre"];
            }
            
            return [
                'success' => true,
                'servicios_abiertos_hoy' => $results,
                'dia_consultado' => $diaSpanish,
                'hora_actual' => $horaActual,
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
     * Obtener horarios de un día específico por condominio
     */
    public function getHorariosDiaByCondominio(string $dia, int $id_condominio): array 
    {
        try {
            // Validar día
            $diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            if (!in_array(strtolower($dia), $diasValidos)) {
                return [
                    'success' => false,
                    'error' => 'Día no válido. Use: ' . implode(', ', $diasValidos)
                ];
            }
            
            $diaLower = strtolower($dia);
            
            $sql = "SELECT *, {$diaLower}_apertura as apertura, {$diaLower}_cierre as cierre 
                    FROM {$this->tableName} 
                    WHERE id_condominio = ? 
                    AND {$diaLower}_apertura IS NOT NULL 
                    AND {$diaLower}_cierre IS NOT NULL
                    ORDER BY {$diaLower}_apertura ASC";
            
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar fotos para mostrar
            foreach ($results as &$servicio) {
                $servicio = $this->procesarFotosParaMostrar($servicio);
            }
            
            return [
                'success' => true,
                'servicios_dia' => $results,
                'dia_consultado' => $dia,
                'cantidad' => count($results)
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
     * Validar horarios de apertura y cierre
     */
    private function validarHorarios(array $data): array 
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        foreach ($dias as $dia) {
            $apertura = $data["{$dia}_apertura"] ?? null;
            $cierre = $data["{$dia}_cierre"] ?? null;
            
            // Si hay apertura debe haber cierre y viceversa
            if (($apertura && !$cierre) || (!$apertura && $cierre)) {
                return [
                    'valid' => false,
                    'error' => "Si especifica horario de {$dia}, debe incluir tanto apertura como cierre"
                ];
            }
            
            // Si hay ambos, validar que apertura sea antes que cierre
            if ($apertura && $cierre) {
                if (strtotime($apertura) >= strtotime($cierre)) {
                    return [
                        'valid' => false,
                        'error' => "La hora de apertura de {$dia} debe ser anterior a la hora de cierre"
                    ];
                }
            }
        }
        
        return ['valid' => true];
    }

    /**
     * Verificar si el array tiene horarios
     */
    private function tieneHorarios(array $data): bool 
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        foreach ($dias as $dia) {
            if (isset($data["{$dia}_apertura"]) || isset($data["{$dia}_cierre"])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si el array tiene fotos
     */
    private function tieneFotos(array $data): bool 
    {
        for ($i = 1; $i <= 5; $i++) {
            if (isset($data["foto{$i}"]) && !empty($data["foto{$i}"])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Procesar fotos (comprimir y encriptar)
     */
    private function procesarFotos(array $data): array 
    {
        try {
            $fotosProcessed = [];
            
            for ($i = 1; $i <= 5; $i++) {
                $fotoKey = "foto{$i}";
                if (isset($data[$fotoKey]) && !empty($data[$fotoKey])) {
                    $resultado = $this->procesarFoto($data[$fotoKey], "servicios_condominio_foto{$i}");
                    if (!$resultado['success']) {
                        return [
                            'success' => false,
                            'error' => "Error procesando {$fotoKey}: " . $resultado['error']
                        ];
                    }
                    $fotosProcessed[$fotoKey] = $resultado['archivo_procesado'];
                }
            }
            
            return [
                'success' => true,
                'fotos_procesadas' => $fotosProcessed
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error procesando fotos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar una foto individual
     */
    private function procesarFoto(string $foto_data, string $subfolder): array 
    {
        try {
            if (strpos($foto_data, 'data:image/') === 0) {
                // Es base64, extraer y procesar
                $resultado = $this->compressAndEncryptFile($foto_data, $subfolder);
            } else {
                // Es ruta de archivo
                $resultado = $this->compressAndEncryptFile($foto_data, $subfolder);
            }
            
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al procesar la foto: ' . $resultado['error']
                ];
            }
            
            return [
                'success' => true,
                'archivo_procesado' => $resultado['encrypted_path']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error procesando foto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar fotos para mostrar (descomprimir y desencriptar)
     */
    private function procesarFotosParaMostrar(array $servicio): array 
    {
        for ($i = 1; $i <= 5; $i++) {
            $fotoKey = "foto{$i}";
            if (!empty($servicio[$fotoKey])) {
                $resultado = $this->procesarFotoParaMostrar($servicio[$fotoKey]);
                if ($resultado['success']) {
                    $servicio["{$fotoKey}_url"] = $resultado['url'];
                }
            }
        }
        return $servicio;
    }

    /**
     * Procesar foto individual para mostrar
     */
    private function procesarFotoParaMostrar(string $encrypted_path): array 
    {
        try {
            $resultado = $this->decompressAndDecryptFile($encrypted_path);
            
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al procesar foto para mostrar'
                ];
            }
            
            return [
                'success' => true,
                'url' => $resultado['public_url']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error mostrando foto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar fotos anteriores
     */
    private function eliminarFotosAnteriores(array $servicio): void 
    {
        try {
            for ($i = 1; $i <= 5; $i++) {
                $fotoKey = "foto{$i}";
                if (!empty($servicio[$fotoKey])) {
                    $this->deleteFile($servicio[$fotoKey]);
                }
            }
        } catch (Exception $e) {
            error_log("Error eliminando fotos anteriores: " . $e->getMessage());
        }
    }

    /**
     * Traducir día de la semana de inglés a español
     */
    private function traducirDiaSemana(string $diaIngles): string 
    {
        $traduccion = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo'
        ];
        
        return $traduccion[strtolower($diaIngles)] ?? 'lunes';
    }

    /**
     * Limpiar datos antiguos (más de 8 años)
     */
    private function limpiarDatosAntiguos(int $id_condominio): void 
    {
        try {
            // Para servicios de condominio, podemos limpiar por fecha de creación si existe un campo timestamp
            // Como no hay campo de fecha en esta tabla, solo hacemos log
            $this->logActivity('cleanup_servicios_condominios', [
                'id_condominio' => $id_condominio,
                'nota' => 'No hay campo de fecha para limpieza automática'
            ]);
            
        } catch (Exception $e) {
            error_log("Error en limpieza de servicios condominios: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de servicios de condominio
     */
    public function obtenerEstadisticasServiciosCondominios(int $id_condominio): array 
    {
        try {
            $conditions = ['id_condominio' => $id_condominio];
            $total = $this->count($conditions);
            
            // Contar servicios con fotos
            $sqlConFotos = "SELECT COUNT(*) as count FROM {$this->tableName} 
                           WHERE id_condominio = ? 
                           AND (foto1 IS NOT NULL OR foto2 IS NOT NULL OR foto3 IS NOT NULL 
                                OR foto4 IS NOT NULL OR foto5 IS NOT NULL)";
            $stmtConFotos = $this->executeQuery($sqlConFotos, [$id_condominio]);
            $conFotosResult = $stmtConFotos->fetch(PDO::FETCH_ASSOC);
            $serviciosConFotos = $conFotosResult['count'];
            
            // Contar servicios con horarios definidos por día
            $estadisticasDias = [];
            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            
            foreach ($dias as $dia) {
                $sqlDia = "SELECT COUNT(*) as count FROM {$this->tableName} 
                          WHERE id_condominio = ? 
                          AND {$dia}_apertura IS NOT NULL 
                          AND {$dia}_cierre IS NOT NULL";
                $stmtDia = $this->executeQuery($sqlDia, [$id_condominio]);
                $diaResult = $stmtDia->fetch(PDO::FETCH_ASSOC);
                $estadisticasDias[$dia] = $diaResult['count'];
            }
            
            return [
                'success' => true,
                'estadisticas' => [
                    'total_servicios_condominios' => $total,
                    'servicios_con_fotos' => $serviciosConFotos,
                    'porcentaje_con_fotos' => $total > 0 ? round(($serviciosConFotos / $total) * 100, 2) : 0,
                    'servicios_por_dia' => $estadisticasDias,
                    'dia_mas_activo' => $total > 0 ? array_keys($estadisticasDias, max($estadisticasDias))[0] : null
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