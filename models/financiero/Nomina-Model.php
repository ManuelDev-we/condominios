<?php
/**
 * 💰 NOMINA MODEL - Modelo de Nómina
 * Manejo de nómina con cálculos de deducciones y restricciones por condominio
 * Soporte para ISR, IMSS, INFONAVIT y percepciones/deducciones
 * 
 * @package Cyberhole\Models\Financiero
 * @author ManuelDev
 * @version 2.0 COMPLETE
 */

require_once __DIR__ . '/../base-model.php';

class NominaModel extends BaseModel {
    
    // Propiedades públicas correspondientes a la tabla nomina
    public ?int $id_nomina;
    public ?int $id_empleado;
    public ?int $id_condominio;
    public ?string $periodo;
    public ?float $salario_base;
    public ?float $percepciones_extras;
    public ?float $deduccion_isr;
    public ?float $deduccion_imss;
    public ?float $deduccion_infonavit;
    public ?float $otras_deducciones;
    public ?float $salario_neto;
    public ?string $fecha_pago;
    public ?string $metodo_pago;
    public ?string $estatus_pago;
    public ?string $recibo_nomina_pdf;
    public ?string $observaciones;
    public ?string $fecha_registro;

    public function __construct(
        ?int $id_nomina = null,
        ?int $id_empleado = null,
        ?int $id_condominio = null,
        ?string $periodo = null,
        ?float $salario_base = 0.0,
        ?float $percepciones_extras = 0.0,
        ?float $deduccion_isr = 0.0,
        ?float $deduccion_imss = 0.0,
        ?float $deduccion_infonavit = 0.0,
        ?float $otras_deducciones = 0.0,
        ?float $salario_neto = 0.0,
        ?string $fecha_pago = null,
        ?string $metodo_pago = 'transferencia',
        ?string $estatus_pago = 'pendiente',
        ?string $recibo_nomina_pdf = null,
        ?string $observaciones = null,
        ?string $fecha_registro = null
    ) {
        parent::__construct();
        
        // Configuración del modelo
        $this->tableName = 'nomina';
        $this->primaryKey = 'id_nomina';
        $this->fillableFields = [
            'id_empleado', 'id_condominio', 'periodo', 'salario_base', 'percepciones_extras',
            'deduccion_isr', 'deduccion_imss', 'deduccion_infonavit', 'otras_deducciones',
            'salario_neto', 'fecha_pago', 'metodo_pago', 'estatus_pago', 'recibo_nomina_pdf',
            'observaciones', 'fecha_registro'
        ];
        
        // Campos que se encriptan: observaciones y datos del PDF (información sensible)
        $this->encryptedFields = ['observaciones', 'recibo_nomina_pdf'];
        $this->hiddenFields = [];
        
        // Asignar propiedades
        $this->id_nomina = $id_nomina;
        $this->id_empleado = $id_empleado;
        $this->id_condominio = $id_condominio;
        $this->periodo = $periodo;
        $this->salario_base = $salario_base;
        $this->percepciones_extras = $percepciones_extras;
        $this->deduccion_isr = $deduccion_isr;
        $this->deduccion_imss = $deduccion_imss;
        $this->deduccion_infonavit = $deduccion_infonavit;
        $this->otras_deducciones = $otras_deducciones;
        $this->salario_neto = $salario_neto;
        $this->fecha_pago = $fecha_pago;
        $this->metodo_pago = $metodo_pago;
        $this->estatus_pago = $estatus_pago;
        $this->recibo_nomina_pdf = $recibo_nomina_pdf;
        $this->observaciones = $observaciones;
        $this->fecha_registro = $fecha_registro;
    }

    // ===========================================
    // MÉTODOS CRUD PRINCIPALES
    // ===========================================

    /**
     * Crear nueva nómina con cálculos automáticos
     */
    public function create(array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Validar empleado y condominio
            if (!$this->validarEmpleadoCondominio($data['id_empleado'], $data['id_condominio'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'El empleado no pertenece al condominio especificado'
                ];
            }
            
            // Validar que no exista nómina duplicada para el mismo periodo
            if ($this->existeNominaPeriodo($data['id_empleado'], $data['periodo'])) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'Ya existe una nómina para este empleado en el periodo especificado'
                ];
            }
            
            // Calcular deducciones automáticamente si no se proporcionan
            $calculosDeducciones = $this->calcularDeducciones(
                $data['salario_base'],
                $data['percepciones_extras'] ?? 0.0,
                $data['id_empleado']
            );
            
            // Aplicar cálculos si no se proporcionaron manualmente
            if (!isset($data['deduccion_isr'])) {
                $data['deduccion_isr'] = $calculosDeducciones['isr'];
            }
            if (!isset($data['deduccion_imss'])) {
                $data['deduccion_imss'] = $calculosDeducciones['imss'];
            }
            if (!isset($data['deduccion_infonavit'])) {
                $data['deduccion_infonavit'] = $calculosDeducciones['infonavit'];
            }
            
            // Calcular salario neto
            $data['salario_neto'] = $this->calcularSalarioNeto($data);
            
            // Establecer fecha de registro si no se proporciona
            if (!isset($data['fecha_registro']) || empty($data['fecha_registro'])) {
                $data['fecha_registro'] = $this->getCurrentTimestamp();
            }
            
            // Comprimir PDF si se proporciona
            if (isset($data['recibo_nomina_pdf']) && !empty($data['recibo_nomina_pdf'])) {
                $data['recibo_nomina_pdf'] = $this->compressPDF($data['recibo_nomina_pdf']);
            }
            
            $id = $this->insert($data);
            
            if (!$id) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo crear la nómina'
                ];
            }
            
            $this->commit();
            $this->logActivity('create', ['id_nomina' => $id]);
            
            return [
                'success' => true,
                'id_nomina' => $id,
                'salario_neto' => $data['salario_neto'],
                'calculos' => $calculosDeducciones,
                'message' => 'Nómina creada exitosamente'
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
     * Leer nómina por ID
     */
    public function read(int $id): array 
    {
        try {
            $result = $this->findById($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Nómina no encontrada'
                ];
            }
            
            // Desencriptar campos sensibles
            $result = $this->decryptSensitiveFields($result);
            
            // Descomprimir PDF si existe
            if (!empty($result['recibo_nomina_pdf'])) {
                $result['recibo_nomina_pdf'] = $this->decompressPDF($result['recibo_nomina_pdf']);
            }
            
            // Agregar información adicional
            $result['total_percepciones'] = $result['salario_base'] + $result['percepciones_extras'];
            $result['total_deducciones'] = $result['deduccion_isr'] + $result['deduccion_imss'] + 
                                         $result['deduccion_infonavit'] + $result['otras_deducciones'];
            
            return [
                'success' => true,
                'nomina' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar nómina
     */
    public function updateNomina(int $id, array $data): array 
    {
        try {
            $this->beginTransaction();
            
            // Si se actualiza salario, recalcular deducciones y salario neto
            if (isset($data['salario_base']) || isset($data['percepciones_extras'])) {
                $nominaActual = $this->findById($id);
                if (!$nominaActual) {
                    $this->rollback();
                    return [
                        'success' => false,
                        'error' => 'Nómina no encontrada'
                    ];
                }
                
                $salarioBase = $data['salario_base'] ?? $nominaActual['salario_base'];
                $percepcionesExtras = $data['percepciones_extras'] ?? $nominaActual['percepciones_extras'];
                
                // Recalcular deducciones si no se proporcionan manualmente
                if (!isset($data['deduccion_isr']) && !isset($data['deduccion_imss']) && !isset($data['deduccion_infonavit'])) {
                    $calculosDeducciones = $this->calcularDeducciones($salarioBase, $percepcionesExtras, $nominaActual['id_empleado']);
                    $data['deduccion_isr'] = $calculosDeducciones['isr'];
                    $data['deduccion_imss'] = $calculosDeducciones['imss'];
                    $data['deduccion_infonavit'] = $calculosDeducciones['infonavit'];
                }
                
                // Recalcular salario neto
                $dataCompleta = array_merge($nominaActual, $data);
                $data['salario_neto'] = $this->calcularSalarioNeto($dataCompleta);
            }
            
            // Comprimir PDF si se proporciona
            if (isset($data['recibo_nomina_pdf']) && !empty($data['recibo_nomina_pdf'])) {
                $data['recibo_nomina_pdf'] = $this->compressPDF($data['recibo_nomina_pdf']);
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar la nómina'
                ];
            }
            
            $this->commit();
            $this->logActivity('update', ['id_nomina' => $id]);
            
            return [
                'success' => true,
                'message' => 'Nómina actualizada exitosamente'
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
     * Eliminar nómina
     */
    public function deleteNomina(int $id): array 
    {
        try {
            $this->beginTransaction();
            
            // Verificar que la nómina no esté pagada
            $nomina = $this->findById($id);
            if ($nomina && $nomina['estatus_pago'] === 'pagado') {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se puede eliminar una nómina que ya ha sido pagada'
                ];
            }
            
            $deleted = $this->delete($id);
            
            if (!$deleted) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo eliminar la nómina'
                ];
            }
            
            $this->commit();
            $this->logActivity('delete', ['id_nomina' => $id]);
            
            return [
                'success' => true,
                'message' => 'Nómina eliminada exitosamente'
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
     * Obtener nóminas por condominio con paginación
     */
    public function getNominasByCondominio(int $id_condominio, int $page = 1, int $limit = 10): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT n.*, e.nombre as empleado_nombre, e.apellidos as empleado_apellidos
                    FROM {$this->tableName} n
                    LEFT JOIN empleados e ON n.id_empleado = e.id_empleado
                    WHERE n.id_condominio = ?
                    ORDER BY n.fecha_registro DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['total_percepciones'] = $result['salario_base'] + $result['percepciones_extras'];
                $result['total_deducciones'] = $result['deduccion_isr'] + $result['deduccion_imss'] + 
                                             $result['deduccion_infonavit'] + $result['otras_deducciones'];
            }
            
            // Contar total
            $total = $this->count(['id_condominio' => $id_condominio]);
            
            return [
                'success' => true,
                'nominas' => $results,
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
     * Obtener nóminas por periodo
     */
    public function getNominasByPeriodo(int $id_condominio, string $periodo, int $limit = 10): array 
    {
        try {
            $sql = "SELECT n.*, e.nombre as empleado_nombre, e.apellidos as empleado_apellidos
                    FROM {$this->tableName} n
                    LEFT JOIN empleados e ON n.id_empleado = e.id_empleado
                    WHERE n.id_condominio = ? AND n.periodo = ?
                    ORDER BY n.salario_neto DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $periodo, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['total_percepciones'] = $result['salario_base'] + $result['percepciones_extras'];
                $result['total_deducciones'] = $result['deduccion_isr'] + $result['deduccion_imss'] + 
                                             $result['deduccion_infonavit'] + $result['otras_deducciones'];
            }
            
            return [
                'success' => true,
                'nominas_periodo' => $results,
                'total' => count($results),
                'periodo' => $periodo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener nóminas pendientes de pago
     */
    public function getNominasPendientes(int $id_condominio, int $limit = 10): array 
    {
        try {
            $sql = "SELECT n.*, e.nombre as empleado_nombre, e.apellidos as empleado_apellidos
                    FROM {$this->tableName} n
                    LEFT JOIN empleados e ON n.id_empleado = e.id_empleado
                    WHERE n.id_condominio = ? AND n.estatus_pago = 'pendiente'
                    ORDER BY n.fecha_pago ASC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_condominio, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['total_percepciones'] = $result['salario_base'] + $result['percepciones_extras'];
                $result['total_deducciones'] = $result['deduccion_isr'] + $result['deduccion_imss'] + 
                                             $result['deduccion_infonavit'] + $result['otras_deducciones'];
                $result['dias_para_pago'] = $this->calcularDiasParaPago($result['fecha_pago']);
            }
            
            return [
                'success' => true,
                'nominas_pendientes' => $results,
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
     * Obtener nóminas por empleado
     */
    public function getNominasByEmpleado(int $id_empleado, int $limit = 10): array 
    {
        try {
            $sql = "SELECT n.*
                    FROM {$this->tableName} n
                    WHERE n.id_empleado = ?
                    ORDER BY n.fecha_registro DESC
                    LIMIT ?";
            
            $stmt = $this->executeQuery($sql, [$id_empleado, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Desencriptar campos sensibles para cada resultado
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveFields($result);
                $result['total_percepciones'] = $result['salario_base'] + $result['percepciones_extras'];
                $result['total_deducciones'] = $result['deduccion_isr'] + $result['deduccion_imss'] + 
                                             $result['deduccion_infonavit'] + $result['otras_deducciones'];
            }
            
            return [
                'success' => true,
                'nominas_empleado' => $results,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===========================================
    // MÉTODOS DE CÁLCULOS FISCALES
    // ===========================================

    /**
     * Calcular deducciones automáticamente
     */
    public function calcularDeducciones(float $salarioBase, float $percepcionesExtras, int $id_empleado): array 
    {
        try {
            $salarioBruto = $salarioBase + $percepcionesExtras;
            
            // Obtener información del empleado para cálculos específicos
            $empleadoInfo = $this->getEmpleadoInfo($id_empleado);
            
            // Cálculo ISR (simplificado - en producción usar tablas oficiales SAT)
            $isr = $this->calcularISR($salarioBruto);
            
            // Cálculo IMSS (4.25% empleado + otros conceptos)
            $imss = $this->calcularIMSS($salarioBruto);
            
            // Cálculo INFONAVIT (5% si aplica)
            $infonavit = $this->calcularINFONAVIT($salarioBruto, $empleadoInfo);
            
            return [
                'isr' => round($isr, 2),
                'imss' => round($imss, 2),
                'infonavit' => round($infonavit, 2),
                'salario_bruto' => $salarioBruto
            ];
            
        } catch (Exception $e) {
            // En caso de error, retornar cálculos básicos
            return [
                'isr' => round($salarioBase * 0.10, 2), // 10% estimado
                'imss' => round($salarioBase * 0.0425, 2), // 4.25%
                'infonavit' => round($salarioBase * 0.05, 2), // 5%
                'salario_bruto' => $salarioBase + $percepcionesExtras
            ];
        }
    }

    /**
     * Calcular ISR (Impuesto Sobre la Renta)
     */
    private function calcularISR(float $salarioBruto): float 
    {
        // Tabla simplificada ISR 2024 (mensual)
        // En producción debe usar las tablas oficiales del SAT
        if ($salarioBruto <= 5952.84) {
            return 0; // Exento
        } elseif ($salarioBruto <= 50524.67) {
            $excedente = $salarioBruto - 5952.84;
            return ($excedente * 0.0640) - 114.29;
        } elseif ($salarioBruto <= 88793.04) {
            $excedente = $salarioBruto - 50524.67;
            return ($excedente * 0.1088) + 2966.91;
        } else {
            $excedente = $salarioBruto - 88793.04;
            return ($excedente * 0.1600) + 7130.48;
        }
    }

    /**
     * Calcular IMSS (Instituto Mexicano del Seguro Social)
     */
    private function calcularIMSS(float $salarioBruto): float 
    {
        // Cálculo básico IMSS para empleado
        // Enfermedad y maternidad: 0.25%
        // Invalidez y vida: 0.625%
        // Retiro: 1.125%
        // Cesantía: 1.125%
        // Infonavit: 1.125% (si no se calcula separado)
        
        $porcentajeTotal = 0.0425; // 4.25% total empleado
        return $salarioBruto * $porcentajeTotal;
    }

    /**
     * Calcular INFONAVIT
     */
    private function calcularINFONAVIT(float $salarioBruto, ?array $empleadoInfo): float 
    {
        // INFONAVIT: 5% del salario para crédito de vivienda
        // Solo aplica si el empleado tiene crédito INFONAVIT activo
        if ($empleadoInfo && isset($empleadoInfo['credito_infonavit']) && $empleadoInfo['credito_infonavit']) {
            return $salarioBruto * 0.05;
        }
        
        return 0.0;
    }

    /**
     * Calcular salario neto
     */
    private function calcularSalarioNeto(array $datos): float 
    {
        $percepciones = ($datos['salario_base'] ?? 0) + ($datos['percepciones_extras'] ?? 0);
        $deducciones = ($datos['deduccion_isr'] ?? 0) + ($datos['deduccion_imss'] ?? 0) + 
                      ($datos['deduccion_infonavit'] ?? 0) + ($datos['otras_deducciones'] ?? 0);
        
        return max(0, $percepciones - $deducciones);
    }

    // ===========================================
    // MÉTODOS DE GESTIÓN DE PAGOS
    // ===========================================

    /**
     * Marcar nómina como pagada
     */
    public function marcarComoPagada(int $id_nomina, string $metodo_pago = 'transferencia'): array 
    {
        try {
            $this->beginTransaction();
            
            $data = [
                'estatus_pago' => 'pagado',
                'metodo_pago' => $metodo_pago,
                'fecha_pago' => $this->getCurrentTimestamp()
            ];
            
            $updated = $this->update($id_nomina, $data);
            
            if (!$updated) {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el estatus de pago'
                ];
            }
            
            $this->commit();
            $this->logActivity('pago_nomina', ['id_nomina' => $id_nomina, 'metodo_pago' => $metodo_pago]);
            
            return [
                'success' => true,
                'message' => 'Nómina marcada como pagada exitosamente'
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
     * Procesar pago masivo de nóminas
     */
    public function procesarPagoMasivo(array $ids_nomina, string $metodo_pago = 'transferencia'): array 
    {
        try {
            $this->beginTransaction();
            
            $exitosos = 0;
            $errores = [];
            
            foreach ($ids_nomina as $id) {
                $resultado = $this->marcarComoPagada($id, $metodo_pago);
                if ($resultado['success']) {
                    $exitosos++;
                } else {
                    $errores[] = "ID $id: " . $resultado['error'];
                }
            }
            
            if ($exitosos > 0) {
                $this->commit();
                return [
                    'success' => true,
                    'pagos_exitosos' => $exitosos,
                    'pagos_fallidos' => count($errores),
                    'errores' => $errores,
                    'message' => "Se procesaron $exitosos pagos exitosamente"
                ];
            } else {
                $this->rollback();
                return [
                    'success' => false,
                    'error' => 'No se pudo procesar ningún pago',
                    'errores' => $errores
                ];
            }
            
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
     * Obtener estadísticas de nómina por condominio
     */
    public function getEstadisticasByCondominio(int $id_condominio): array 
    {
        try {
            // Estadísticas generales
            $sqlGeneral = "SELECT 
                            COUNT(*) as total_nominas,
                            SUM(salario_neto) as total_nomina_mensual,
                            AVG(salario_neto) as salario_promedio,
                            SUM(deduccion_isr) as total_isr,
                            SUM(deduccion_imss) as total_imss,
                            SUM(deduccion_infonavit) as total_infonavit
                          FROM {$this->tableName}
                          WHERE id_condominio = ?";
            
            $stmtGeneral = $this->executeQuery($sqlGeneral, [$id_condominio]);
            $general = $stmtGeneral->fetch();
            
            // Por estatus de pago
            $sqlEstatus = "SELECT estatus_pago, COUNT(*) as cantidad, SUM(salario_neto) as total
                          FROM {$this->tableName}
                          WHERE id_condominio = ?
                          GROUP BY estatus_pago";
            
            $stmtEstatus = $this->executeQuery($sqlEstatus, [$id_condominio]);
            $estatus = $stmtEstatus->fetchAll(PDO::FETCH_ASSOC);
            
            // Por método de pago
            $sqlMetodo = "SELECT metodo_pago, COUNT(*) as cantidad, SUM(salario_neto) as total
                         FROM {$this->tableName}
                         WHERE id_condominio = ?
                         GROUP BY metodo_pago";
            
            $stmtMetodo = $this->executeQuery($sqlMetodo, [$id_condominio]);
            $metodos = $stmtMetodo->fetchAll(PDO::FETCH_ASSOC);
            
            // Por periodo (últimos 6)
            $sqlPeriodos = "SELECT periodo, COUNT(*) as empleados, SUM(salario_neto) as total_periodo
                           FROM {$this->tableName}
                           WHERE id_condominio = ?
                           GROUP BY periodo
                           ORDER BY periodo DESC
                           LIMIT 6";
            
            $stmtPeriodos = $this->executeQuery($sqlPeriodos, [$id_condominio]);
            $periodos = $stmtPeriodos->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'generales' => [
                        'total_nominas' => (int)$general['total_nominas'],
                        'total_nomina_mensual' => (float)$general['total_nomina_mensual'],
                        'salario_promedio' => (float)$general['salario_promedio'],
                        'total_deducciones' => [
                            'isr' => (float)$general['total_isr'],
                            'imss' => (float)$general['total_imss'],
                            'infonavit' => (float)$general['total_infonavit']
                        ]
                    ],
                    'por_estatus' => $estatus,
                    'por_metodo_pago' => $metodos,
                    'por_periodo' => $periodos
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
     * Generar reporte fiscal de nómina
     */
    public function generarReporteFiscal(int $id_condominio, string $periodo): array 
    {
        try {
            $nominas = $this->getNominasByPeriodo($id_condominio, $periodo, 100);
            
            if (!$nominas['success']) {
                return $nominas;
            }
            
            $totalSalarios = 0;
            $totalISR = 0;
            $totalIMSS = 0;
            $totalINFONAVIT = 0;
            $totalOtrasDeducciones = 0;
            $empleadosDetalle = [];
            
            foreach ($nominas['nominas_periodo'] as $nomina) {
                $totalSalarios += $nomina['salario_neto'];
                $totalISR += $nomina['deduccion_isr'];
                $totalIMSS += $nomina['deduccion_imss'];
                $totalINFONAVIT += $nomina['deduccion_infonavit'];
                $totalOtrasDeducciones += $nomina['otras_deducciones'];
                
                $empleadosDetalle[] = [
                    'empleado' => $nomina['empleado_nombre'] . ' ' . $nomina['empleado_apellidos'],
                    'salario_bruto' => $nomina['salario_base'] + $nomina['percepciones_extras'],
                    'salario_neto' => $nomina['salario_neto'],
                    'isr' => $nomina['deduccion_isr'],
                    'imss' => $nomina['deduccion_imss'],
                    'infonavit' => $nomina['deduccion_infonavit']
                ];
            }
            
            return [
                'success' => true,
                'reporte_fiscal' => [
                    'periodo' => $periodo,
                    'id_condominio' => $id_condominio,
                    'fecha_generacion' => $this->getCurrentTimestamp(),
                    'totales' => [
                        'empleados' => count($empleadosDetalle),
                        'salarios_netos' => $totalSalarios,
                        'isr_retenido' => $totalISR,
                        'imss_empleados' => $totalIMSS,
                        'infonavit' => $totalINFONAVIT,
                        'otras_deducciones' => $totalOtrasDeducciones
                    ],
                    'detalle_empleados' => $empleadosDetalle
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
     * Validar que un empleado pertenece al condominio
     */
    private function validarEmpleadoCondominio(int $id_empleado, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT id_empleado FROM empleados WHERE id_empleado = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_empleado, $id_condominio]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si existe nómina para el periodo
     */
    private function existeNominaPeriodo(int $id_empleado, string $periodo): bool 
    {
        try {
            $sql = "SELECT id_nomina FROM {$this->tableName} WHERE id_empleado = ? AND periodo = ?";
            $stmt = $this->executeQuery($sql, [$id_empleado, $periodo]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener información del empleado
     */
    private function getEmpleadoInfo(int $id_empleado): ?array 
    {
        try {
            $sql = "SELECT * FROM empleados WHERE id_empleado = ?";
            $stmt = $this->executeQuery($sql, [$id_empleado]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Calcular días para el pago
     */
    private function calcularDiasParaPago(string $fecha_pago): int 
    {
        $fechaPago = new DateTime($fecha_pago);
        $hoy = new DateTime();
        $diff = $hoy->diff($fechaPago);
        
        if ($fechaPago < $hoy) {
            return -$diff->days; // Días vencidos (negativo)
        } else {
            return $diff->days; // Días restantes (positivo)
        }
    }

    /**
     * Comprimir PDF de recibo
     */
    private function compressPDF(string $pdfData): string 
    {
        try {
            // Comprimir usando gzip
            $compressed = gzcompress($pdfData, 9);
            // Codificar en base64 para almacenamiento
            $encoded = base64_encode($compressed);
            // Encriptar para seguridad adicional
            return $this->encryptField($encoded);
        } catch (Exception $e) {
            return $this->encryptField($pdfData); // Fallback: solo encriptar
        }
    }

    /**
     * Descomprimir PDF de recibo
     */
    private function decompressPDF(string $encryptedData): string 
    {
        try {
            // Desencriptar
            $decrypted = $this->decryptField($encryptedData);
            // Decodificar base64
            $decoded = base64_decode($decrypted);
            // Descomprimir
            return gzuncompress($decoded);
        } catch (Exception $e) {
            // Fallback: intentar solo desencriptar
            return $this->decryptField($encryptedData);
        }
    }
}
?>