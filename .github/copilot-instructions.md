# Cyberhole Condominios - AI Coding Assistant Instructions

## 🏗️ Project Architecture

This is a **PHP-based condominium management system** with a custom MVC-like architecture designed for multi-tenant property management. The system uses a **security-first approach** with comprehensive encryption and input filtering.

### Core Components Structure
```
config/         # Configuration modules (env, database, email, encryption)
models/         # Domain models organized by business areas
├── entities/   # Core user entities (admin, persona, empleados)
├── estructura/ # Property structure (condominios, areas, calles)
├── servicios/  # Service modules (access, payments, bookings)
├── crm-ligero/ # CRM features (tasks, inventory, purchases)
└── cyberhole/  # Platform-specific (subscriptions, plans)
lib/           # Business logic libraries (EmailSender)
helpers/       # Utility classes (SecurityFilters)
templates/     # HTML templates
controllers/   # Request handlers
services/      # Business services
```

## 🔐 Security Patterns (CRITICAL)

### Input Filtering System
**Always use `SecurityFilters` class for ANY user input:**
```php
$filters = new SecurityFilters();
$result = $filters->filterInput($userInput, $strict = true);
if (!$result['is_safe']) {
    // Handle threats: $result['threats_detected']
}
$cleanData = $result['filtered'];
```

### Configuration & Environment
- All configs use `EnvironmentConfig::getDatabaseConfig()` pattern
- **Never hardcode credentials** - use `config/env.php` system
- Database connections: `DatabaseConfig::getConnection()` (PDO)
- Email: `EmailSender::sendRealEmail()` with SMTP config

### Authentication & Encryption
- Passwords: `EnvironmentConfig::hashPassword()` / `verifyPassword()` (uses pepper)
- JWT tokens: `EncryptionConfig::generateJWT()` / `verifyJWT()`
- Database encryption: `EncryptionConfig::encryptForDatabase()` / `decryptFromDatabase()`
- All user entities have email verification tokens and recovery systems

## 🏢 Business Domain

### Multi-Tenant Structure
- **Condominios** (properties) → **Calles** (streets) → **Casas** (units)
- Multiple user types: Admin, Personas (residents), Empleados (staff)
- Services tied to condominios with RFC-based billing

### Key Entities & Relationships
- **Admin**: System administrators with full access
- **Personas**: Residents linked to casa/calle/condominio
- **ServiciosResidentes**: Service requests with photo tracking and payment status
- **AreasComunes**: Shared facilities with schedule management
- **AccesosResidentes/Empleados**: Physical access control with entry/exit logs

### Financial Integration
- Full Mexican tax compliance (RFC, CFDI, SAT integration)
- Invoice management with XML/PDF storage
- Purchase orders and inventory tracking

## 🛠️ Development Workflows

### Testing Infrastructure
Run comprehensive tests: `php test_config_modules.php`
- Tests all config modules, encryption, database connectivity
- Validates security functions and environment setup

### File Organization Conventions
- Models use nullable properties with full constructors
- Email templates stored in `templates/auth/`
- All uploads go to `uploads/` with proper validation
- Logs in `logs/` directory with rotation

### Database Patterns
- Use PDO with prepared statements via `DatabaseConfig::execute()`
- Transaction support: `beginTransaction()`, `commit()`, `rollback()`
- All sensitive data encrypted before storage
- Email verification required for all user registrations

## 🚨 Critical DO/DON'T

### ✅ DO
- Use `SecurityFilters` for ALL user input
- Encrypt sensitive data with `EncryptionConfig`
- Use environment variables for all configuration
- Test email functionality with `EmailSender::sendRealEmail()`
- Include email verification in user registration flows

### ❌ DON'T
- Never trust user input without filtering
- Don't hardcode SMTP credentials or API keys
- Don't bypass the encryption system for sensitive data
- Don't modify security patterns without understanding implications

## 📧 Email System

The system uses production SMTP with Hostinger:
- `EmailSender::sendRealEmail()` for actual delivery
- HTML email templates with proper headers
- Email verification required for all user types
- Recovery token system for password resets

Use for user registration, notifications, and administrative communications.

## 🔧 Integration Points

- **XAMPP Development**: Configured for Windows/Apache/MySQL/PHP stack
- **Production Ready**: Full encryption, error logging, and security validations
- **Mexican Compliance**: Built-in SAT/CFDI support for tax requirements
- **Multi-language**: Spanish-first with proper character encoding (UTF-8)

When working with this codebase, prioritize security patterns and follow the existing architectural conventions.