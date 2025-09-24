<?php
/**
 * 🏛️ AREAS COMUNES MODEL - Gestión de Áreas Comunes
 * Modelo completo para administración de espacios comunitarios
 * Filtros por condominio/calle, horarios, estado y búsquedas avanzadas
 * 
 * @package Cyberhole\Models\Estructura
 * @author ManuelDev
 * @version 1.0 COMPLETE
 */

require_once __DIR__ . '/../Base-Model.php';

class AreasComunes extends BaseModel 
{
    // Configuración del modelo
    protected $tableName = 'areas_comunes';
    protected $primaryKey = 'id_area_comun';
    protected $encryptedFields = []; // Sin encriptación para estructura
    
    // Campos permitidos para inserción/actualización
    protected $fillableFields = [
        'nombre', 'descripcion', 'id_condominio', 'id_calle', 'estado',
        'lunes_apertura', 'lunes_cierre', 'martes_apertura', 'martes_cierre',
        'miercoles_apertura', 'miercoles_cierre', 'jueves_apertura', 'jueves_cierre',
        'viernes_apertura', 'viernes_cierre', 'sabado_apertura', 'sabado_cierre',
        'domingo_apertura', 'domingo_cierre'
    ];
    
    // Propiedades del modelo
    public ?int $id_area_comun = null;
    public ?string $nombre = null;
    public ?string $descripcion = null;
    public ?int $id_condominio = null;
    public ?int $id_calle = null;
    public ?int $estado = 1;
    public ?string $lunes_apertura = null;
    public ?string $lunes_cierre = null;
    public ?string $martes_apertura = null;
    public ?string $martes_cierre = null;
    public ?string $miercoles_apertura = null;
    public ?string $miercoles_cierre = null;
    public ?string $jueves_apertura = null;
    public ?string $jueves_cierre = null;
    public ?string $viernes_apertura = null;
    public ?string $viernes_cierre = null;
    public ?string $sabado_apertura = null;
    public ?string $sabado_cierre = null;
    public ?string $domingo_apertura = null;
    public ?string $domingo_cierre = null;
    
    /**
     * Constructor con parámetros opcionales
     */
    public function __construct(
        ?string $nombre = null,
        ?string $descripcion = null,
        ?int $id_condominio = null,
        ?int $id_calle = null,
        ?int $estado = 1
    ) {
        parent::__construct();
        
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->id_condominio = $id_condominio;
        $this->id_calle = $id_calle;
        $this->estado = $estado ?? 1;
    }
    
    // ========================================
    // MÉTODOS CRUD BÁSICOS
    // ========================================
    
    /**
     * Crear nueva área común
     */
    public function create(array $data): array 
    {
        try {
            // Validaciones básicas
            if (empty($data['nombre'])) {
                return ['success' => false, 'error' => 'El nombre del área común es obligatorio'];
            }
            
            if (empty($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El ID del condominio es obligatorio'];
            }
            
            // Verificar que el condominio existe
            if (!$this->condominioExists($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El condominio especificado no existe'];
            }
            
            // Verificar calle si se proporciona
            if (!empty($data['id_calle']) && !$this->calleExists($data['id_calle'], $data['id_condominio'])) {
                return ['success' => false, 'error' => 'La calle especificada no existe en este condominio'];
            }
            
            // Insertar registro
            $id = $this->insert($data);
            
            $this->logActivity('create', ['id_area_comun' => $id]);
            
            return [
                'success' => true,
                'id_area_comun' => $id,
                'message' => 'Área común creada exitosamente'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener área común por ID
     */
    public function getById(int $id): ?array 
    {
        try {
            return $this->findById($id);
        } catch (Exception $e) {
            error_log("Error en getById AreaComun: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener todas las áreas comunes
     */
    public function getAll(): array 
    {
        try {
            $areas = $this->findMany();
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar área común
     */
    public function updateArea(int $id, array $data): array 
    {
        try {
            // Verificar que el área existe
            if (!$this->findById($id)) {
                return ['success' => false, 'error' => 'Área común no encontrada'];
            }
            
            // Validar condominio si se actualiza
            if (!empty($data['id_condominio']) && !$this->condominioExists($data['id_condominio'])) {
                return ['success' => false, 'error' => 'El condominio especificado no existe'];
            }
            
            // Validar calle si se actualiza
            if (!empty($data['id_calle'])) {
                $condominio_id = $data['id_condominio'] ?? $this->getCondominioIdByArea($id);
                if (!$this->calleExists($data['id_calle'], $condominio_id)) {
                    return ['success' => false, 'error' => 'La calle especificada no existe en este condominio'];
                }
            }
            
            // Actualizar registro
            $updated = $this->update($id, $data);
            
            if ($updated) {
                $this->logActivity('update', ['id_area_comun' => $id, 'fields' => array_keys($data)]);
                return ['success' => true, 'message' => 'Área común actualizada exitosamente'];
            }
            
            return ['success' => false, 'error' => 'No se pudo actualizar el área común'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Eliminar área común
     */
    public function delate(int $id): array 
    {
        try {
            $deleted = $this->delete($id);
            
            if ($deleted) {
                $this->logActivity('delete', ['id_area_comun' => $id]);
                return ['success' => true, 'message' => 'Área común eliminada exitosamente'];
            }
            
            return ['success' => false, 'error' => 'No se pudo eliminar el área común'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ========================================
    // MÉTODOS DE FILTRADO POR CONDOMINIO
    // ========================================
    
    /**
     * Obtener áreas comunes por condominio
     */
    public function getByCondominio(int $id_condominio): array 
    {
        try {
            $areas = $this->findMany(['id_condominio' => $id_condominio]);
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener áreas comunes por calle
     */
    public function getByCalle(int $id_calle): array 
    {
        try {
            $areas = $this->findMany(['id_calle' => $id_calle]);
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener áreas comunes por condominio y calle
     */
    public function getByCondominioAndCalle(int $id_condominio, int $id_calle): array 
    {
        try {
            $areas = $this->findMany([
                'id_condominio' => $id_condominio,
                'id_calle' => $id_calle
            ]);
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener áreas comunes activas por condominio
     */
    public function getActivasByCondominio(int $id_condominio): array 
    {
        try {
            $areas = $this->findMany([
                'id_condominio' => $id_condominio,
                'estado' => 1
            ]);
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ========================================
    // MÉTODOS DE BÚSQUEDA LIKE
    // ========================================
    
    /**
     * Buscar áreas por nombre en condominio específico
     */
    public function searchByNombreInCondominio(int $id_condominio, string $nombre): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND nombre LIKE ?";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$nombre}%"]);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Buscar áreas por descripción en condominio específico
     */
    public function searchByDescripcionInCondominio(int $id_condominio, string $descripcion): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND descripcion LIKE ?";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$descripcion}%"]);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Búsqueda general en condominio (nombre o descripción)
     */
    public function searchInCondominio(int $id_condominio, string $termino): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND (nombre LIKE ? OR descripcion LIKE ?)";
            $stmt = $this->executeQuery($sql, [$id_condominio, "%{$termino}%", "%{$termino}%"]);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ========================================
    // MÉTODOS DE BÚSQUEDA POR ARRAYS
    // ========================================
    
    /**
     * Buscar áreas por múltiples IDs en condominio
     */
    public function findByIdsInCondominio(int $id_condominio, array $ids): array 
    {
        try {
            if (empty($ids)) {
                return ['success' => true, 'data' => [], 'total' => 0];
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND id_area_comun IN ({$placeholders})";
            
            $params = array_merge([$id_condominio], $ids);
            $stmt = $this->executeQuery($sql, $params);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Buscar áreas por múltiples nombres en condominio
     */
    public function findByNombresInCondominio(int $id_condominio, array $nombres): array 
    {
        try {
            if (empty($nombres)) {
                return ['success' => true, 'data' => [], 'total' => 0];
            }
            
            $placeholders = implode(',', array_fill(0, count($nombres), '?'));
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND nombre IN ({$placeholders})";
            
            $params = array_merge([$id_condominio], $nombres);
            $stmt = $this->executeQuery($sql, $params);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Buscar áreas por múltiples calles en condominio
     */
    public function findByCallesInCondominio(int $id_condominio, array $id_calles): array 
    {
        try {
            if (empty($id_calles)) {
                return ['success' => true, 'data' => [], 'total' => 0];
            }
            
            $placeholders = implode(',', array_fill(0, count($id_calles), '?'));
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND id_calle IN ({$placeholders})";
            
            $params = array_merge([$id_condominio], $id_calles);
            $stmt = $this->executeQuery($sql, $params);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ========================================
    // MÉTODOS DE HORARIOS
    // ========================================
    
    /**
     * Actualizar horarios de área común
     */
    public function updateHorarios(int $id, array $horarios): array 
    {
        try {
            $horarios_data = [];
            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            
            foreach ($dias as $dia) {
                if (isset($horarios[$dia])) {
                    $horarios_data["{$dia}_apertura"] = $horarios[$dia]['apertura'] ?? null;
                    $horarios_data["{$dia}_cierre"] = $horarios[$dia]['cierre'] ?? null;
                }
            }
            
            if (empty($horarios_data)) {
                return ['success' => false, 'error' => 'No se proporcionaron horarios válidos'];
            }
            
            return $this->updateArea($id, $horarios_data);
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener áreas abiertas en día específico
     */
    public function getAbiertasEnDia(int $id_condominio, string $dia): array 
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE id_condominio = ? AND estado = 1 
                    AND {$dia}_apertura IS NOT NULL AND {$dia}_cierre IS NOT NULL";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $areas = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ========================================
    // MÉTODOS RELACIONALES
    // ========================================
    
    /**
     * Obtener condominio del área común
     */
    public function getCondominio(int $id_area): ?array 
    {
        try {
            $sql = "SELECT c.* FROM condominios c 
                    INNER JOIN {$this->tableName} ac ON c.id_condominio = ac.id_condominio 
                    WHERE ac.id_area_comun = ?";
            $stmt = $this->executeQuery($sql, [$id_area]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener calle del área común
     */
    public function getCalle(int $id_area): ?array 
    {
        try {
            $sql = "SELECT ca.* FROM calles ca 
                    INNER JOIN {$this->tableName} ac ON ca.id_calle = ac.id_calle 
                    WHERE ac.id_area_comun = ?";
            $stmt = $this->executeQuery($sql, [$id_area]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener información completa del área común
     */
    public function getFullInfo(int $id_area): ?array 
    {
        try {
            $sql = "SELECT ac.*, c.nombre as condominio_nombre, ca.nombre as calle_nombre 
                    FROM {$this->tableName} ac
                    INNER JOIN condominios c ON ac.id_condominio = c.id_condominio
                    LEFT JOIN calles ca ON ac.id_calle = ca.id_calle
                    WHERE ac.id_area_comun = ?";
            $stmt = $this->executeQuery($sql, [$id_area]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // ========================================
    // MÉTODOS DE CONTEO
    // ========================================
    
    /**
     * Contar áreas comunes totales
     */
    public function getTotalCount(): int 
    {
        return $this->count();
    }
    
    /**
     * Contar áreas por condominio
     */
    public function getCountByCondominio(int $id_condominio): int 
    {
        return $this->count(['id_condominio' => $id_condominio]);
    }
    
    /**
     * Contar áreas activas por condominio
     */
    public function getCountActivasByCondominio(int $id_condominio): int 
    {
        return $this->count(['id_condominio' => $id_condominio, 'estado' => 1]);
    }
    
    /**
     * Contar áreas por calle
     */
    public function getCountByCalle(int $id_calle): int 
    {
        return $this->count(['id_calle' => $id_calle]);
    }
    
    // ========================================
    // MÉTODOS DE ESTADO
    // ========================================
    
    /**
     * Activar área común
     */
    public function activar(int $id): array 
    {
        return $this->updateArea($id, ['estado' => 1]);
    }
    
    /**
     * Desactivar área común
     */
    public function desactivar(int $id): array 
    {
        return $this->updateArea($id, ['estado' => 0]);
    }
    
    // ========================================
    // MÉTODOS DE VALIDACIÓN
    // ========================================
    
    /**
     * Verificar si condominio existe
     */
    private function condominioExists(int $id_condominio): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM condominios WHERE id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_condominio]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar si calle existe en condominio
     */
    private function calleExists(int $id_calle, int $id_condominio): bool 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM calles WHERE id_calle = ? AND id_condominio = ?";
            $stmt = $this->executeQuery($sql, [$id_calle, $id_condominio]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener ID de condominio por área común
     */
    private function getCondominioIdByArea(int $id_area): ?int 
    {
        try {
            $area = $this->findById($id_area);
            return $area ? $area['id_condominio'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // ========================================
    // GETTERS Y SETTERS
    // ========================================
    
    public function getIdAreaComun(): ?int { return $this->id_area_comun; }
    public function setIdAreaComun(?int $id_area_comun): void { $this->id_area_comun = $id_area_comun; }
    
    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(?string $nombre): void { $this->nombre = $nombre; }
    
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): void { $this->descripcion = $descripcion; }
    
    public function getIdCondominio(): ?int { return $this->id_condominio; }
    public function setIdCondominio(?int $id_condominio): void { $this->id_condominio = $id_condominio; }
    
    public function getIdCalle(): ?int { return $this->id_calle; }
    public function setIdCalle(?int $id_calle): void { $this->id_calle = $id_calle; }
    
    public function getEstado(): ?int { return $this->estado; }
    public function setEstado(?int $estado): void { $this->estado = $estado; }
    
    // Getters y setters para horarios
    public function getLunesApertura(): ?string { return $this->lunes_apertura; }
    public function setLunesApertura(?string $lunes_apertura): void { $this->lunes_apertura = $lunes_apertura; }
    
    public function getLunesCierre(): ?string { return $this->lunes_cierre; }
    public function setLunesCierre(?string $lunes_cierre): void { $this->lunes_cierre = $lunes_cierre; }
    
    public function getMartesApertura(): ?string { return $this->martes_apertura; }
    public function setMartesApertura(?string $martes_apertura): void { $this->martes_apertura = $martes_apertura; }
    
    public function getMartesCierre(): ?string { return $this->martes_cierre; }
    public function setMartesCierre(?string $martes_cierre): void { $this->martes_cierre = $martes_cierre; }
    
    public function getMiercolesApertura(): ?string { return $this->miercoles_apertura; }
    public function setMiercolesApertura(?string $miercoles_apertura): void { $this->miercoles_apertura = $miercoles_apertura; }
    
    public function getMiercolesCierre(): ?string { return $this->miercoles_cierre; }
    public function setMiercolesCierre(?string $miercoles_cierre): void { $this->miercoles_cierre = $miercoles_cierre; }
    
    public function getJuevesApertura(): ?string { return $this->jueves_apertura; }
    public function setJuevesApertura(?string $jueves_apertura): void { $this->jueves_apertura = $jueves_apertura; }
    
    public function getJuevesCierre(): ?string { return $this->jueves_cierre; }
    public function setJuevesCierre(?string $jueves_cierre): void { $this->jueves_cierre = $jueves_cierre; }
    
    public function getViernesApertura(): ?string { return $this->viernes_apertura; }
    public function setViernesApertura(?string $viernes_apertura): void { $this->viernes_apertura = $viernes_apertura; }
    
    public function getViernesCierre(): ?string { return $this->viernes_cierre; }
    public function setViernesCierre(?string $viernes_cierre): void { $this->viernes_cierre = $viernes_cierre; }
    
    public function getSabadoApertura(): ?string { return $this->sabado_apertura; }
    public function setSabadoApertura(?string $sabado_apertura): void { $this->sabado_apertura = $sabado_apertura; }
    
    public function getSabadoCierre(): ?string { return $this->sabado_cierre; }
    public function setSabadoCierre(?string $sabado_cierre): void { $this->sabado_cierre = $sabado_cierre; }
    
    public function getDomingoApertura(): ?string { return $this->domingo_apertura; }
    public function setDomingoApertura(?string $domingo_apertura): void { $this->domingo_apertura = $domingo_apertura; }
    
    public function getDomingoCierre(): ?string { return $this->domingo_cierre; }
    public function setDomingoCierre(?string $domingo_cierre): void { $this->domingo_cierre = $domingo_cierre; }
}
