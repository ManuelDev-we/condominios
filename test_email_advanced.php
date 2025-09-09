<?php
/**
 * 🧪 Test Avanzado de Email - Verificación Completa
 * Test exhaustivo para config/email.php con envío real a fsg20132@gmail.com
 * 
 * @package Cyberhole\Testing
 * @author ManuelDev
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos de configuración
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/email.php';

class AdvancedEmailTest 
{
    private static $results = [];
    private static $totalTests = 0;
    private static $passedTests = 0;
    private static $testEmail = 'fsg20132@gmail.com';
    
    /**
     * Ejecutar todos los tests de email
     */
    public static function runAllTests(): void 
    {
        echo "<h1>🧪 Test Avanzado de Email - Verificación Completa</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .pass { color: #28a745; font-weight: bold; }
            .fail { color: #dc3545; font-weight: bold; }
            .info { color: #17a2b8; }
            .warning { color: #ffc107; }
            .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .test-item { margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 3px; }
            .critical { background: #f8d7da; border: 1px solid #f5c6cb; }
        </style>\n";
        
        echo "<div class='info'><strong>🎯 Objetivo:</strong> Enviar email exitosamente a " . self::$testEmail . "</div>\n";
        
        try {
            // Test 1: Verificar configuraciones básicas
            self::testBasicConfiguration();
            
            // Test 2: Test de conexión SMTP
            self::testSmtpConnection();
            
            // Test 3: Test de validación de emails
            self::testEmailValidation();
            
            // Test 4: Test de envío básico
            self::testBasicEmailSending();
            
            // Test 5: Test de envío con template
            self::testTemplateEmailSending();
            
            // Test 6: Test de funciones helper
            self::testHelperFunctions();
            
        } catch (Exception $e) {
            self::logError("Error crítico en tests: " . $e->getMessage());
        }
        
        self::showResults();
    }
    
    /**
     * Test de configuraciones básicas
     */
    private static function testBasicConfiguration(): void 
    {
        echo "<div class='section'><h2>📝 Test Configuración Básica</h2>\n";
        
        // Test 1: Verificar que EmailConfig existe
        self::test("Clase EmailConfig existe", function() {
            return class_exists('EmailConfig');
        });
        
        // Test 2: Obtener configuración
        self::test("Obtener configuración de email", function() {
            $config = EmailConfig::getConfig();
            return is_array($config) && 
                   isset($config['host']) && 
                   isset($config['port']) && 
                   isset($config['username']) && 
                   isset($config['password']);
        });
        
        // Test 3: Verificar configuración SMTP
        self::test("Configuración SMTP válida", function() {
            $smtp = EmailConfig::getSmtpConfig();
            return is_array($smtp) && 
                   !empty($smtp['host']) && 
                   !empty($smtp['username']) && 
                   !empty($smtp['password']);
        });
        
        // Test 4: Mostrar configuración (sin passwords)
        self::test("Mostrar configuración actual", function() {
            $config = EmailConfig::getConfig();
            echo "<div class='info'>";
            echo "<strong>Host:</strong> " . $config['host'] . "<br>";
            echo "<strong>Port:</strong> " . $config['port'] . "<br>";
            echo "<strong>Username:</strong> " . $config['username'] . "<br>";
            echo "<strong>Encryption:</strong> " . $config['encryption'] . "<br>";
            echo "<strong>From Name:</strong> " . $config['from_name'] . "<br>";
            echo "</div>";
            return true;
        });
        
        echo "</div>\n";
    }
    
    /**
     * Test de conexión SMTP
     */
    private static function testSmtpConnection(): void 
    {
        echo "<div class='section'><h2>🔗 Test Conexión SMTP</h2>\n";
        
        // Test 1: Conexión básica
        self::test("Conexión SMTP básica", function() {
            return EmailConfig::testConnection();
        });
        
        // Test 2: Conexión manual detallada
        self::test("Conexión SMTP detallada", function() {
            $config = EmailConfig::getConfig();
            $timeout = 10;
            
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            if ($config['encryption'] === 'ssl') {
                $socket = @stream_socket_client(
                    "ssl://{$config['host']}:{$config['port']}", 
                    $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context
                );
            } else {
                $socket = @stream_socket_client(
                    "{$config['host']}:{$config['port']}", 
                    $errno, $errstr, $timeout
                );
            }
            
            if (!$socket) {
                echo "<div class='fail'>Error: $errstr ($errno)</div>";
                return false;
            }
            
            $response = fgets($socket);
            fclose($socket);
            
            echo "<div class='info'>Respuesta del servidor: " . trim($response) . "</div>";
            return strpos($response, '220') === 0;
        });
        
        echo "</div>\n";
    }
    
    /**
     * Test de validación de emails
     */
    private static function testEmailValidation(): void 
    {
        echo "<div class='section'><h2>✅ Test Validación de Emails</h2>\n";
        
        // Test 1: Email válido
        self::test("Validar email válido", function() {
            return EmailConfig::validateEmail(self::$testEmail);
        });
        
        // Test 2: Email inválido
        self::test("Rechazar email inválido", function() {
            return !EmailConfig::validateEmail('email-invalido');
        });
        
        // Test 3: Diversos formatos
        self::test("Validar diversos formatos", function() {
            $valid = [
                'test@example.com',
                'user.name@domain.co.uk',
                'user+tag@example.org'
            ];
            
            $invalid = [
                'plainaddress',
                '@missingdomain.com',
                'missing@.com',
                'spaces in@email.com'
            ];
            
            foreach ($valid as $email) {
                if (!EmailConfig::validateEmail($email)) {
                    return false;
                }
            }
            
            foreach ($invalid as $email) {
                if (EmailConfig::validateEmail($email)) {
                    return false;
                }
            }
            
            return true;
        });
        
        echo "</div>\n";
    }
    
    /**
     * Test de envío básico de email
     */
    private static function testBasicEmailSending(): void 
    {
        echo "<div class='section'><h2>📧 Test Envío Básico de Email</h2>\n";
        
        // Test 1: Envío simple
        self::test("Envío de email básico", function() {
            $subject = "🧪 Test Email - " . date('Y-m-d H:i:s');
            $body = "<h1>Test de Email Exitoso</h1>
                     <p>Este es un email de prueba enviado desde el sistema Cyberhole Condominios.</p>
                     <p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
                     <p><strong>Sistema:</strong> Test Automatizado</p>
                     <p>Si recibes este email, significa que el sistema de correo está funcionando correctamente.</p>";
            
            $result = EmailConfig::sendMail(self::$testEmail, $subject, $body);
            
            if ($result) {
                echo "<div class='pass'>✅ Email enviado exitosamente a " . self::$testEmail . "</div>";
            } else {
                echo "<div class='fail'>❌ Error al enviar email</div>";
            }
            
            return $result;
        });
        
        // Test 2: Envío con opciones adicionales
        self::test("Envío con opciones personalizadas", function() {
            $subject = "🔧 Test Email Avanzado - " . date('Y-m-d H:i:s');
            $body = "<h1>Test Email Avanzado</h1>
                     <p>Este email incluye opciones personalizadas.</p>
                     <ul>
                         <li>From Name personalizado</li>
                         <li>Reply-To configurado</li>
                         <li>Formato HTML</li>
                     </ul>";
            
            $options = [
                'from_name' => 'Sistema Test Cyberhole',
                'reply_to' => 'noreply@cyberhole.net'
            ];
            
            return EmailConfig::sendMail(self::$testEmail, $subject, $body, $options);
        });
        
        echo "</div>\n";
    }
    
    /**
     * Test de envío con template
     */
    private static function testTemplateEmailSending(): void 
    {
        echo "<div class='section'><h2>📄 Test Email con Template</h2>\n";
        
        // Crear template de prueba
        $templateContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{subject}}</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px;">
    <h1 style="color: #007bff;">{{title}}</h1>
    <p>Hola {{name}},</p>
    <p>{{message}}</p>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>Detalles del test:</strong><br>
        Fecha: {{date}}<br>
        Sistema: {{system}}
    </div>
    <p>Saludos,<br>{{from_name}}</p>
</body>
</html>';
        
        // Crear directorio de templates si no existe
        $templateDir = __DIR__ . '/templates/email';
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
        
        // Guardar template
        file_put_contents($templateDir . '/test_template.html', $templateContent);
        
        // Test 1: Envío con template
        self::test("Envío con template HTML", function() {
            $data = [
                'subject' => '📋 Test Template Email - ' . date('Y-m-d H:i:s'),
                'title' => 'Test de Template Exitoso',
                'name' => 'Desarrollador',
                'message' => 'Este email fue generado usando un template HTML personalizado del sistema Cyberhole Condominios.',
                'date' => date('Y-m-d H:i:s'),
                'system' => 'Cyberhole Condominios v1.0',
                'from_name' => 'Sistema Cyberhole'
            ];
            
            return EmailConfig::sendTemplateEmail(self::$testEmail, 'test_template', $data);
        });
        
        echo "</div>\n";
    }
    
    /**
     * Test de funciones helper
     */
    private static function testHelperFunctions(): void 
    {
        echo "<div class='section'><h2>🔧 Test Funciones Helper</h2>\n";
        
        // Test 1: Función send_email
        self::test("Función helper send_email", function() {
            if (!function_exists('send_email')) {
                return false;
            }
            
            return send_email(
                self::$testEmail,
                '🔗 Test Helper Function - ' . date('H:i:s'),
                '<h1>Test Helper Function</h1><p>Enviado usando la función helper send_email().</p>'
            );
        });
        
        // Test 2: Función validate_email
        self::test("Función helper validate_email", function() {
            return function_exists('validate_email') && 
                   validate_email(self::$testEmail);
        });
        
        // Test 3: Crear mensaje multipart
        self::test("Crear mensaje multipart", function() {
            $html = '<h1>Test HTML</h1><p>Contenido en HTML</p>';
            $message = EmailConfig::createMultipartMessage($html);
            
            return strpos($message, 'multipart/alternative') !== false && 
                   strpos($message, 'text/html') !== false && 
                   strpos($message, 'text/plain') !== false;
        });
        
        echo "</div>\n";
    }
    
    /**
     * Ejecutar un test individual
     */
    private static function test(string $description, callable $testFunction): void 
    {
        self::$totalTests++;
        
        try {
            $result = $testFunction();
            if ($result) {
                echo "<div class='test-item'><span class='pass'>✅ PASS</span> - {$description}</div>\n";
                self::$passedTests++;
                self::$results[] = ['test' => $description, 'status' => 'PASS'];
            } else {
                echo "<div class='test-item'><span class='fail'>❌ FAIL</span> - {$description}</div>\n";
                self::$results[] = ['test' => $description, 'status' => 'FAIL'];
            }
        } catch (Exception $e) {
            echo "<div class='test-item critical'><span class='fail'>💥 ERROR</span> - {$description}: {$e->getMessage()}</div>\n";
            self::$results[] = ['test' => $description, 'status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mostrar resultados finales
     */
    private static function showResults(): void 
    {
        echo "<div class='section'>\n";
        echo "<h2>📊 Resultados Finales del Test de Email</h2>\n";
        
        $successRate = round((self::$passedTests / self::$totalTests) * 100, 2);
        
        echo "<div style='font-size: 18px; margin: 20px 0;'>\n";
        echo "<strong>Tests Ejecutados:</strong> " . self::$totalTests . "<br>\n";
        echo "<strong class='pass'>Tests Exitosos:</strong> " . self::$passedTests . "<br>\n";
        echo "<strong class='fail'>Tests Fallidos:</strong> " . (self::$totalTests - self::$passedTests) . "<br>\n";
        echo "<strong class='info'>Tasa de Éxito:</strong> {$successRate}%<br>\n";
        echo "</div>\n";
        
        if ($successRate === 100.0) {
            echo "<div style='color: #28a745; font-size: 24px; font-weight: bold; text-align: center; padding: 20px; background: #d4edda; border-radius: 10px;'>\n";
            echo "🎉 ¡PERFECTO! Sistema de Email al 100% - Email enviado a " . self::$testEmail . "\n";
            echo "</div>\n";
        } elseif ($successRate >= 80) {
            echo "<div style='color: #ffc107; font-size: 20px; font-weight: bold;'>\n";
            echo "⚠️ BUENO. Algunos tests fallaron, pero emails se enviaron.\n";
            echo "</div>\n";
        } else {
            echo "<div style='color: #dc3545; font-size: 20px; font-weight: bold;'>\n";
            echo "🚨 CRÍTICO. Sistema de email presenta errores graves.\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Log de errores
     */
    private static function logError(string $message): void 
    {
        error_log("AdvancedEmailTest Error: " . $message);
        echo "<div class='test-item critical'><span class='fail'>💥 ERROR CRÍTICO</span> - {$message}</div>\n";
    }
}

// Ejecutar tests
AdvancedEmailTest::runAllTests();
?>
