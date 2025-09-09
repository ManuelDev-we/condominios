# 🏠 Cyberhole Condominios

Sistema de gestión integral para condominios desarrollado en PHP.

## 🚀 Características

- **Gestión de Residentes**: Administración completa de propietarios e inquilinos
- **Control de Accesos**: Sistema de control de entrada y salida de visitantes
- **Gestión de Servicios**: Administración de servicios del condominio
- **Sistema de Pagos**: Control de cuotas y pagos de mantenimiento
- **Reserva de Áreas Comunes**: Sistema de reserva de espacios compartidos
- **Blog Interno**: Comunicación entre administración y residentes
- **Dashboard Administrativo**: Panel de control para administradores

## 🔧 Tecnologías

- **Backend**: PHP 8.2+
- **Base de Datos**: MySQL/MariaDB
- **Autenticación**: JWT + Session Management
- **Encriptación**: AES-256-CBC
- **Email**: SMTP Integration
- **Arquitectura**: MVC Pattern

## 📁 Estructura del Proyecto

```
condominios/
├── config/                 # Configuraciones del sistema
│   ├── env.php             # Configuración de entorno
│   ├── database.php        # Configuración de base de datos
│   ├── email.php           # Configuración de correo
│   └── encryption.php      # Configuración de encriptación
├── controllers/            # Controladores MVC
├── models/                 # Modelos de datos
│   ├── entities/          # Entidades de usuario
│   ├── estructura/        # Modelos de estructura
│   ├── servicios/         # Modelos de servicios
│   └── crm-ligero/       # Módulos CRM
├── services/              # Servicios de negocio
├── templates/             # Plantillas HTML
├── routers/               # Enrutamiento
├── helpers/               # Funciones auxiliares
├── logs/                  # Archivos de log
└── uploads/               # Archivos subidos
```

## ⚙️ Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/ManuelDev-we/condominios.git
   cd condominios
   ```

2. **Configurar el archivo .env**
   ```bash
   cp .env.example .env
   # Editar .env con tus configuraciones
   ```

3. **Configurar la base de datos**
   - Crear base de datos MySQL
   - Importar el archivo SQL incluido
   - Configurar credenciales en .env

4. **Configurar el servidor web**
   - Apache/Nginx con PHP 8.2+
   - Apuntar document root a la carpeta del proyecto

## 🔐 Configuración de Seguridad

El sistema incluye múltiples capas de seguridad:

### Variables de Entorno Requeridas

```env
# Base de datos
DB_HOST=localhost
DB_DATABASE=condominios_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Correo electrónico
MAIL_HOST=smtp.tu-servidor.com
MAIL_USERNAME=tu_email@dominio.com
MAIL_PASSWORD=tu_password_email

# Seguridad
JWT_SECRET=tu_jwt_secret_muy_largo_y_seguro
AES_KEY=clave_aes_de_exactamente_32_chars
PEPPER_SECRET=tu_pepper_secret_para_passwords
CYBERHOLE_ENCRYPTION_KEY=clave_de_encriptacion_cyberhole
CYBERHOLE_PASSWORD_PEPPER=pepper_adicional_passwords
```

### Características de Seguridad

- **Encriptación AES-256-CBC** para datos sensibles
- **JWT** para autenticación de sesiones
- **Password Hashing** con salt y pepper
- **Rate Limiting** para prevenir ataques de fuerza bruta
- **CORS** configurado para APIs seguras
- **Validación de integridad** en datos encriptados

## 📧 Configuración de Email

Soporta múltiples proveedores SMTP:
- Gmail
- Outlook
- Servidores SMTP personalizados

## 🗄️ Base de Datos

Compatible con:
- MySQL 5.7+
- MariaDB 10.3+
- Conexión PDO para máxima seguridad

## 🧪 Testing

El proyecto incluye tests automatizados:

```bash
php test_config_modules.php
```

## 📝 Logs

Sistema de logging integrado:
- Logs de aplicación
- Logs de errores
- Logs de configuración
- Logs de email

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👥 Autor

**ManuelDev** - [GitHub](https://github.com/ManuelDev-we)

## 📞 Soporte

Para soporte técnico o consultas:
- 📧 Email: soporte@cyberhole.net
- 🐛 Issues: [GitHub Issues](https://github.com/ManuelDev-we/condominios/issues)

---

⭐ ¡No olvides darle una estrella al proyecto si te resulta útil!
