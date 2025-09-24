<?php
/**
 * 📧 EmailSender - Librería robusta para envío de correos
 * Envío de emails usando SMTP con cURL y configuración segura
 * 
 * @package Cyberhole\Libraries
 * @author ManuelDev
 * @version 1.0
 */

require_once __DIR__ . '/../config/email.php';

class EmailSender 
{
    private static $config = null;
    
    /**
     * Inicializar configuración
     */
    private static function init(): void 
    {
        if (self::$config === null) {
            self::$config = EmailConfig::getSmtpConfig();
        }
    }
    
    /**
     * Enviar email real usando SMTP con cURL (corregido)
     */
    public static function sendRealEmail(string $to, string $subject, string $body, array $options = []): bool 
    {
        self::init();
        
        try {
            // Validar email
            if (!EmailConfig::validateEmail($to)) {
                throw new Exception("Email de destinatario inválido: $to");
            }
            
            // Preparar datos del mensaje
            $from_email = $options['from_email'] ?? self::$config['from_email'];
            $from_name = $options['from_name'] ?? self::$config['from_name'];
            
            // Usar método alternativo más simple
            return self::sendViaSocket($to, $from_email, $from_name, $subject, $body, $options);
            
        } catch (Exception $e) {
            error_log("EmailSender error: " . $e->getMessage());
            EmailConfig::logEmail($to, $subject, false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar via socket SMTP directo (más confiable)
     */
    private static function sendViaSocket(string $to, string $from_email, string $from_name, string $subject, string $body, array $options = []): bool 
    {
        $config = self::$config;
        
        try {
            // Conectar al servidor SMTP
            if ($config['encryption'] === 'ssl') {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);
                
                $socket = stream_socket_client(
                    "ssl://{$config['host']}:{$config['port']}", 
                    $errno, 
                    $errstr, 
                    30, 
                    STREAM_CLIENT_CONNECT, 
                    $context
                );
            } else {
                $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
            }
            
            if (!$socket) {
                throw new Exception("No se pudo conectar al servidor SMTP: $errstr ($errno)");
            }
            
            // Función helper para comunicación SMTP
            $smtpCommand = function($command, $expectedCode = 250) use ($socket) {
                if (!empty($command)) {
                    fwrite($socket, $command . "\r\n");
                }
                
                // Leer respuesta completa (puede ser multilínea)
                $response = '';
                do {
                    $line = fgets($socket);
                    $response .= $line;
                    
                    // Las respuestas multilínea tienen un guión después del código
                    $isLastLine = !isset($line[3]) || $line[3] !== '-';
                } while (!$isLastLine);
                
                if (EnvironmentConfig::isDebugMode()) {
                    error_log("SMTP: $command -> " . trim($response));
                }
                
                $code = (int)substr($response, 0, 3);
                if ($code !== $expectedCode) {
                    throw new Exception("SMTP Error: Expected $expectedCode, got $code: " . trim($response));
                }
                
                return $response;
            };
            
            // Saludo inicial
            $smtpCommand("", 220); // Leer saludo del servidor
            $smtpCommand("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            
            // Autenticación
            $smtpCommand("AUTH LOGIN", 334);
            $smtpCommand(base64_encode($config['username']), 334);
            $smtpCommand(base64_encode($config['password']), 235);
            
            // Configurar remitente y destinatario
            $smtpCommand("MAIL FROM: <$from_email>");
            $smtpCommand("RCPT TO: <$to>");
            
            // Enviar datos del mensaje
            $smtpCommand("DATA", 354);
            
            // Headers del email
            $headers = [];
            $headers[] = "From: $from_name <$from_email>";
            $headers[] = "To: $to";
            $headers[] = "Subject: $subject";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Date: " . date('r');
            $headers[] = "X-Mailer: Cyberhole EmailSender 1.0";
            
            // Enviar headers y cuerpo
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
            fwrite($socket, $message . "\r\n");
            
            $smtpCommand("", 250); // Confirmar recepción
            
            // Cerrar conexión
            $smtpCommand("QUIT", 221);
            fclose($socket);
            
            // Log exitoso
            EmailConfig::logEmail($to, $subject, true);
            error_log("Email sent successfully via SMTP socket to: $to");
            
            return true;
            
        } catch (Exception $e) {
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
            throw $e;
        }
    }
    
    /**
     * Enviar email usando template
     */
    public static function sendTemplateEmail(string $to, string $template, array $data = []): bool 
    {
        try {
            $templatePath = dirname(__DIR__) . '/templates/email/' . $template . '.html';
            
            if (!file_exists($templatePath)) {
                throw new Exception("Template no encontrado: $template");
            }
            
            $body = file_get_contents($templatePath);
            
            // Reemplazar variables
            foreach ($data as $key => $value) {
                $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
            }
            
            $subject = $data['subject'] ?? 'Notificación de Cyberhole';
            
            return self::sendRealEmail($to, $subject, $body, $data);
            
        } catch (Exception $e) {
            error_log("Template email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar email de bienvenida - FUNCIONALIDAD ESPECÍFICA
     */
    public static function sendWelcomeEmail(string $to, string $name, string $verificationToken = ''): bool 
    {
        try {
            $subject = '🎉 ¡Bienvenido a Cyberhole Condominios!';
            
            $body = self::createWelcomeEmailTemplate($name, $verificationToken);
            
            $options = [
                'from_name' => 'Cyberhole Condominios',
                'priority' => 'high'
            ];
            
            $result = self::sendRealEmail($to, $subject, $body, $options);
            
            if ($result) {
                error_log("Welcome email sent successfully to: $to");
                EmailConfig::logEmail($to, $subject, true, "Email de bienvenida enviado exitosamente");
            } else {
                error_log("Failed to send welcome email to: $to");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear template de email de bienvenida
     */
    private static function createWelcomeEmailTemplate(string $name, string $token = ''): string 
    {
        $currentDate = date('d/m/Y H:i:s');
        $siteUrl = EnvironmentConfig::get('SITE_URL', 'https://cyberhole.net');
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenido a Cyberhole Condominios</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .welcome-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
                .success-badge { background: #28a745; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; display: inline-block; margin: 10px 0; }
                .info-box { background: #e9f4ff; border-left: 4px solid #007cba; padding: 15px; margin: 15px 0; }
                .button { display: inline-block; background: #007cba; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 10px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding: 20px; color: #666; font-size: 14px; }
                .highlight { color: #007cba; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏠 Cyberhole Condominios</h1>
                    <p style='margin: 0; font-size: 18px;'>Sistema de Gestión de Condominios</p>
                </div>
                
                <div class='content'>
                    <div class='welcome-box'>
                        <h2>¡Hola, <span class='highlight'>$name</span>!</h2>
                        
                        <div class='success-badge'>
                            ✅ Registro Exitoso
                        </div>
                        
                        <p><strong>¡Felicidades! Has sido correctamente ingresado a la plataforma Cyberhole Condominios.</strong></p>
                        
                        <div class='info-box'>
                            <h3>📋 Detalles de tu Registro:</h3>
                            <ul>
                                <li><strong>Fecha de registro:</strong> $currentDate</li>
                                <li><strong>Estado:</strong> <span style='color: #28a745;'>✅ Activo</span></li>
                                <li><strong>Plataforma:</strong> Cyberhole Condominios v1.0</li>
                                <li><strong>Email confirmado:</strong> $name</li>
                            </ul>
                        </div>
                        
                        <h3>🚀 ¿Qué puedes hacer ahora?</h3>
                        <ul>
                            <li>✅ Acceder al panel de administración</li>
                            <li>🏠 Gestionar información de condominios</li>
                            <li>👥 Administrar residentes y empleados</li>
                            <li>💰 Controlar pagos y servicios</li>
                            <li>📊 Generar reportes y estadísticas</li>
                            <li>🔒 Configurar seguridad avanzada</li>
                        </ul>
                        
                        " . (!empty($token) ? "
                        <div class='info-box'>
                            <h3>🔐 Token de Verificación:</h3>
                            <p>Tu token de acceso: <code style='background: #f8f9fa; padding: 5px; border-radius: 3px;'>$token</code></p>
                        </div>
                        " : "") . "
                        
                        <a href='$siteUrl' class='button'>🚀 Acceder a la Plataforma</a>
                        
                        <h3>💡 Características Principales:</h3>
                        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>
                            <div style='background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;'>
                                <h4>🔐 Seguridad Avanzada</h4>
                                <p>Encriptación AES-256 y autenticación multifactor</p>
                            </div>
                            <div style='background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;'>
                                <h4>📱 Multi-dispositivo</h4>
                                <p>Acceso desde cualquier dispositivo de forma segura</p>
                            </div>
                            <div style='background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;'>
                                <h4>📊 Reportes Completos</h4>
                                <p>Estadísticas detalladas y exportación de datos</p>
                            </div>
                            <div style='background: white; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;'>
                                <h4>🤝 Soporte 24/7</h4>
                                <p>Asistencia técnica especializada</p>
                            </div>
                        </div>
                        
                        <div class='info-box'>
                            <h3>📞 ¿Necesitas Ayuda?</h3>
                            <p>Nuestro equipo de soporte está disponible para ayudarte:</p>
                            <ul>
                                <li>📧 Email: soporte@cyberhole.net</li>
                                <li>💬 Chat en línea: Disponible en la plataforma</li>
                                <li>📱 WhatsApp: +52 55 1234 5678</li>
                                <li>🌐 Centro de ayuda: $siteUrl/help</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>¡Gracias por confiar en Cyberhole Condominios!</strong></p>
                    <p>Este correo fue enviado automáticamente desde nuestro sistema seguro.</p>
                    <p>© 2025 Cyberhole Condominios - Todos los derechos reservados</p>
                    <p style='font-size: 12px; color: #999;'>
                        Si no solicitaste este registro, por favor contacta inmediatamente a nuestro soporte técnico.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Enviar email de verificación
     */
    public static function sendVerificationEmail(string $to, string $name, string $token): bool 
    {
        $verificationUrl = EnvironmentConfig::get('SITE_URL') . '/verify-email?token=' . urlencode($token);
        
        $body = "
        <h1>Verificación de Email - Cyberhole</h1>
        <p>Hola $name,</p>
        <p>Para completar tu registro, por favor verifica tu email haciendo clic en el siguiente enlace:</p>
        <p><a href='$verificationUrl' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verificar Email</a></p>
        <p>Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:</p>
        <p>$verificationUrl</p>
        <p>Este enlace expira en 24 horas.</p>
        <p>Saludos,<br>Equipo de Cyberhole</p>
        ";
        
        return self::sendRealEmail($to, 'Verifica tu email - Cyberhole', $body);
    }
    
    /**
     * Enviar notificación
     */
    public static function sendNotification(string $to, string $title, string $message, array $data = []): bool 
    {
        $body = "
        <h1>$title</h1>
        <p>$message</p>
        " . (!empty($data['extra_info']) ? "<p>Información adicional: {$data['extra_info']}</p>" : "") . "
        <p>Saludos,<br>Sistema Cyberhole</p>
        ";
        
        return self::sendRealEmail($to, $title, $body, $data);
    }
}

// Funciones helper
if (!function_exists('send_real_email')) {
    function send_real_email(string $to, string $subject, string $body, array $options = []): bool 
    {
        return EmailSender::sendRealEmail($to, $subject, $body, $options);
    }
}

if (!function_exists('send_welcome_email')) {
    function send_welcome_email(string $to, string $name, string $token = ''): bool 
    {
        return EmailSender::sendWelcomeEmail($to, $name, $token);
    }
}

if (!function_exists('send_verification_email')) {
    function send_verification_email(string $to, string $name, string $token): bool 
    {
        return EmailSender::sendVerificationEmail($to, $name, $token);
    }
}