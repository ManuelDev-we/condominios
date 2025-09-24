<?php
/**
 * Sistema de compresión y descompresión segura de archivos
 * Maneja imágenes (JPG, PNG), documentos (PDF, TXT) de manera segura
 * Convierte archivos a base64 para almacenamiento en base de datos
 */

class SourcesManager
{
    private $allowedTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain'
    ];

    private $maxFileSize = 10485760; // 10MB
    private $compressionQuality = 85; // Para imágenes JPG

    /**
     * Comprime y convierte archivo a base64 para almacenamiento seguro
     */
    public function compressFile($filePath, $targetSize = 'longtext')
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("Archivo no encontrado: $filePath");
            }

            $fileInfo = $this->validateFile($filePath);
            $fileData = file_get_contents($filePath);
            
            // Compresión según tipo de archivo
            $compressedData = $this->compressFileData($fileData, $fileInfo['extension']);
            
            // Convertir a base64
            $base64Data = base64_encode($compressedData);
            
            // Validar tamaño para base de datos
            $this->validateDatabaseSize($base64Data, $targetSize);
            
            return [
                'success' => true,
                'data' => $base64Data,
                'original_name' => basename($filePath),
                'extension' => $fileInfo['extension'],
                'mime_type' => $fileInfo['mime'],
                'original_size' => strlen($fileData),
                'compressed_size' => strlen($compressedData),
                'base64_size' => strlen($base64Data),
                'compression_ratio' => round((1 - strlen($compressedData) / strlen($fileData)) * 100, 2)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Descomprime archivo desde base64 y lo restaura
     */
    public function decompressFile($base64Data, $originalExtension, $outputPath = null)
    {
        try {
            // Decodificar base64
            $compressedData = base64_decode($base64Data);
            if ($compressedData === false) {
                throw new Exception("Error al decodificar base64");
            }

            // Descomprimir según tipo
            $originalData = $this->decompressFileData($compressedData, $originalExtension);
            
            // Si se especifica ruta de salida, guardar archivo
            if ($outputPath) {
                $directory = dirname($outputPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                file_put_contents($outputPath, $originalData);
            }

            return [
                'success' => true,
                'data' => $originalData,
                'size' => strlen($originalData),
                'saved_to' => $outputPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesa archivo en array con metadata completa
     */
    public function processFileToArray($filePath)
    {
        $compression = $this->compressFile($filePath);
        
        if (!$compression['success']) {
            return $compression;
        }

        return [
            'success' => true,
            'file_array' => [
                'name' => $compression['original_name'],
                'extension' => $compression['extension'],
                'mime_type' => $compression['mime_type'],
                'data' => $compression['data'],
                'metadata' => [
                    'original_size' => $compression['original_size'],
                    'compressed_size' => $compression['compressed_size'],
                    'base64_size' => $compression['base64_size'],
                    'compression_ratio' => $compression['compression_ratio'],
                    'processed_at' => date('Y-m-d H:i:s')
                ]
            ]
        ];
    }

    /**
     * Valida archivo y obtiene información
     */
    private function validateFile($filePath)
    {
        $fileSize = filesize($filePath);
        if ($fileSize > $this->maxFileSize) {
            throw new Exception("Archivo demasiado grande. Máximo: " . ($this->maxFileSize / 1024 / 1024) . "MB");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!array_key_exists($extension, $this->allowedTypes)) {
            throw new Exception("Tipo de archivo no permitido: $extension");
        }

        $mimeType = mime_content_type($filePath);
        if ($mimeType !== $this->allowedTypes[$extension]) {
            throw new Exception("El tipo MIME no coincide con la extensión del archivo");
        }

        return [
            'extension' => $extension,
            'mime' => $mimeType,
            'size' => $fileSize
        ];
    }

    /**
     * Comprime datos según el tipo de archivo
     */
    private function compressFileData($data, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $this->compressJpeg($data);
                
            case 'png':
                return $this->compressPng($data);
                
            case 'pdf':
            case 'txt':
                return gzcompress($data, 9); // Compresión máxima para documentos
                
            default:
                return $data;
        }
    }

    /**
     * Descomprime datos según el tipo de archivo
     */
    private function decompressFileData($data, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
                return $data; // Las imágenes ya están procesadas
                
            case 'pdf':
            case 'txt':
                $decompressed = gzuncompress($data);
                if ($decompressed === false) {
                    throw new Exception("Error al descomprimir archivo $extension");
                }
                return $decompressed;
                
            default:
                return $data;
        }
    }

    /**
     * Comprime imagen JPEG
     */
    private function compressJpeg($data)
    {
        $image = imagecreatefromstring($data);
        if (!$image) {
            throw new Exception("Error al procesar imagen JPEG");
        }

        ob_start();
        imagejpeg($image, null, $this->compressionQuality);
        $compressedData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        return $compressedData;
    }

    /**
     * Comprime imagen PNG
     */
    private function compressPng($data)
    {
        $image = imagecreatefromstring($data);
        if (!$image) {
            throw new Exception("Error al procesar imagen PNG");
        }

        // Configurar compresión PNG (0-9, donde 9 es máxima compresión)
        imagesavealpha($image, true);
        
        ob_start();
        imagepng($image, null, 9);
        $compressedData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        return $compressedData;
    }

    /**
     * Valida tamaño para almacenamiento en base de datos
     */
    private function validateDatabaseSize($data, $targetSize)
    {
        $dataSize = strlen($data);
        
        switch (strtolower($targetSize)) {
            case 'tinytext':
                if ($dataSize > 255) {
                    throw new Exception("Archivo demasiado grande para TINYTEXT (máx: 255 bytes)");
                }
                break;
                
            case 'text':
                if ($dataSize > 65535) {
                    throw new Exception("Archivo demasiado grande para TEXT (máx: 64KB)");
                }
                break;
                
            case 'mediumtext':
                if ($dataSize > 16777215) {
                    throw new Exception("Archivo demasiado grande para MEDIUMTEXT (máx: 16MB)");
                }
                break;
                
            case 'longtext':
                if ($dataSize > 4294967295) {
                    throw new Exception("Archivo demasiado grande para LONGTEXT (máx: 4GB)");
                }
                break;
        }
    }

    /**
     * Obtiene información de archivo procesado
     */
    public function getFileInfo($base64Data, $extension)
    {
        $data = base64_decode($base64Data);
        
        return [
            'extension' => $extension,
            'base64_size' => strlen($base64Data),
            'decoded_size' => strlen($data),
            'estimated_original_size' => $this->estimateOriginalSize($data, $extension)
        ];
    }

    /**
     * Estima el tamaño original del archivo
     */
    private function estimateOriginalSize($data, $extension)
    {
        switch ($extension) {
            case 'pdf':
            case 'txt':
                // Para archivos comprimidos con gzip, intentar descomprimir
                try {
                    $decompressed = gzuncompress($data);
                    return strlen($decompressed);
                } catch (Exception $e) {
                    return strlen($data);
                }
                
            default:
                return strlen($data);
        }
    }
}

// Ejemplo de uso y testing
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "<h2>Testing Sources Manager</h2>";
    
    $sources = new SourcesManager();
    
    // Buscar el archivo de prueba
    $testFile = dirname(__DIR__) . '/uploads/image_67166465.JPG';
    
    if (!file_exists($testFile)) {
        // Crear archivo de prueba si no existe
        $uploadsDir = dirname(__DIR__) . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Crear una imagen de prueba simple
        $testImage = imagecreate(200, 200);
        $background = imagecolorallocate($testImage, 255, 255, 255);
        $textColor = imagecolorallocate($testImage, 0, 0, 0);
        imagestring($testImage, 5, 50, 90, 'TEST IMAGE', $textColor);
        
        imagejpeg($testImage, $testFile, 90);
        imagedestroy($testImage);
        echo "<p>✓ Archivo de prueba creado: $testFile</p>";
    }
    
    echo "<h3>1. Compresión de archivo:</h3>";
    $result = $sources->compressFile($testFile);
    
    if ($result['success']) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ Compresión exitosa:</strong><br>";
        echo "Archivo: {$result['original_name']}<br>";
        echo "Tipo: {$result['mime_type']}<br>";
        echo "Tamaño original: " . number_format($result['original_size']) . " bytes<br>";
        echo "Tamaño comprimido: " . number_format($result['compressed_size']) . " bytes<br>";
        echo "Tamaño base64: " . number_format($result['base64_size']) . " bytes<br>";
        echo "Ratio de compresión: {$result['compression_ratio']}%<br>";
        echo "Datos base64 (primeros 100 chars): " . substr($result['data'], 0, 100) . "...<br>";
        echo "</div>";
        
        // Test descompresión
        echo "<h3>2. Descompresión de archivo:</h3>";
        $outputPath = dirname(__DIR__) . '/cache/restored_image.jpg';
        $decompressResult = $sources->decompressFile($result['data'], $result['extension'], $outputPath);
        
        if ($decompressResult['success']) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✓ Descompresión exitosa:</strong><br>";
            echo "Tamaño restaurado: " . number_format($decompressResult['size']) . " bytes<br>";
            echo "Guardado en: {$decompressResult['saved_to']}<br>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✗ Error en descompresión:</strong> {$decompressResult['error']}";
            echo "</div>";
        }
        
        // Test de array
        echo "<h3>3. Procesamiento a array:</h3>";
        $arrayResult = $sources->processFileToArray($testFile);
        
        if ($arrayResult['success']) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✓ Procesamiento a array exitoso:</strong><br>";
            echo "<pre>" . print_r($arrayResult['file_array'], true) . "</pre>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✗ Error en compresión:</strong> {$result['error']}";
        echo "</div>";
    }
}
?>
