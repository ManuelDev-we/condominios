<?php
/**
 * 📧 Configuración de Correo Electrónico
 * Manejo de configuración y envío de emails
 * 
 * @package Cyberhole\Configuration
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/env.php';

class EmailConfig 
{
    private static $config = null;
    
    /**
     * Obtener configuración de correo
     */
    public static function getConfig(): array 
    {
        if (self::$config === null) {
            self::$config = EnvironmentConfig::getMailConfig();
        }
        return self::$config;
    }
    
    /**
     * Probar configuración de correo
     */
    public static function testConnection(): bool 
    {
        $config = self::getConfig();
        
        try {
            // Para SSL (puerto 465), usar contexto SSL
            if ($config['port'] == 465 || $config['encryption'] === 'ssl') {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);
                
                $socket = @stream_socket_client(
                    "ssl://{$config['host']}:{$config['port']}", 
                    $errno, 
                    $errstr, 
                    30, 
                    STREAM_CLIENT_CONNECT, 
                    $context
                );
            } else {
                // Para TLS (puerto 587) o conexión sin cifrado
                $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
            }
            
            if (!$socket) {
                error_log("SMTP connection failed: $errstr ($errno)");
                return false;
            }
            
            // Leer respuesta del servidor
            $response = fgets($socket);
            fclose($socket);
            
            // Verificar respuesta 220 (servicio listo)
            $isConnected = strpos($response, '220') === 0;
            
            if ($isConnected) {
                error_log("SMTP connection successful: " . trim($response));
            } else {
                error_log("SMTP unexpected response: " . trim($response));
            }
            
            return $isConnected;
            
        } catch (Exception $e) {
            error_log("Email connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar email usando SMTP directo (más robusto que mail())
     */
    public static function sendSmtpEmail(string $to, string $subject, string $body, array $options = []): bool 
    {
        $config = self::getConfig();
        
        try {
            // Configurar SMTP en tiempo de ejecución
            ini_set('SMTP', $config['host']);
            ini_set('smtp_port', $config['port']);
            
            // Headers básicos
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . ($options['from_name'] ?? $config['from_name']) . ' <' . ($options['from_email'] ?? $config['from_address']) . '>',
                'Reply-To: ' . ($options['reply_to'] ?? $config['from_address']),
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Headers adicionales si se proporcionan
            if (!empty($options['cc'])) {
                $headers[] = 'Cc: ' . $options['cc'];
            }
            
            if (!empty($options['bcc'])) {
                $headers[] = 'Bcc: ' . $options['bcc'];
            }
            
            $headerString = implode("\r\n", $headers);
            
            // Intentar envío con configuración actualizada
            $result = mail($to, $subject, $body, $headerString);
            
            // Log del resultado
            self::logEmail($to, $subject, $result, $result ? '' : 'Error de envío SMTP');
            
            if ($result && EnvironmentConfig::isDebugMode()) {
                error_log("Email sent successfully to: " . $to);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $error = "Email sending failed: " . $e->getMessage();
            error_log($error);
            self::logEmail($to, $subject, false, $error);
            return false;
        }
    }

    /**
     * Enviar email básico (mantener compatibilidad)
     */
    public static function sendMail(string $to, string $subject, string $body, array $options = []): bool 
    {
        // Usar la versión SMTP más robusta
        return self::sendSmtpEmail($to, $subject, $body, $options);
    }
    
    /**
     * Enviar email usando template
     */
    public static function sendTemplateEmail(string $to, string $template, array $data = []): bool 
    {
        try {
            $templatePath = dirname(__DIR__) . '/templates/email/' . $template . '.html';
            
            if (!file_exists($templatePath)) {
                throw new Exception("Template de email no encontrado: " . $template);
            }
            
            $body = file_get_contents($templatePath);
            
            // Reemplazar variables en el template
            foreach ($data as $key => $value) {
                $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
            }
            
            $subject = $data['subject'] ?? 'Notificación de Cyberhole';
            
            return self::sendMail($to, $subject, $body, $data);
            
        } catch (Exception $e) {
            error_log("Template email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar dirección de email
     */
    public static function validateEmail(string $email): bool 
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Obtener configuración SMTP para PHPMailer u otras librerías
     */
    public static function getSmtpConfig(): array 
    {
        $config = self::getConfig();
        
        return [
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username'],
            'password' => $config['password'],
            'encryption' => $config['encryption'],
            'auth' => true,
            'from_email' => $config['from_address'],
            'from_name' => $config['from_name']
        ];
    }
    
    /**
     * Crear mensaje de email con formato HTML y texto plano
     */
    public static function createMultipartMessage(string $htmlBody, string $textBody = ''): string 
    {
        if (empty($textBody)) {
            // Generar versión texto desde HTML
            $textBody = strip_tags($htmlBody);
            $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');
        }
        
        $boundary = md5(time());
        
        $message = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        
        // Parte texto plano
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";
        
        // Parte HTML
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        $message .= "--{$boundary}--";
        
        return $message;
    }
    
    /**
     * Loggar actividad de email
     */
    public static function logEmail(string $to, string $subject, bool $success, string $error = ''): void 
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'success' => $success,
            'error' => $error
        ];
        
        error_log("Email log: " . json_encode($logEntry));
    }
}

// Funciones helper para compatibilidad (solo si no existen)
if (!function_exists('send_email')) {
    function send_email(string $to, string $subject, string $body, array $options = []): bool 
    {
        return EmailConfig::sendMail($to, $subject, $body, $options);
    }
}

if (!function_exists('send_template_email')) {
    function send_template_email(string $to, string $template, array $data = []): bool 
    {
        return EmailConfig::sendTemplateEmail($to, $template, $data);
    }
}

if (!function_exists('validate_email')) {
    function validate_email(string $email): bool 
    {
        return EmailConfig::validateEmail($email);
    }
}

if (!function_exists('smtp_config')) {
    function smtp_config(): array 
    {
        return EmailConfig::getSmtpConfig();
    }
}
