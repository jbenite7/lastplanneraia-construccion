# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Unreleased]

### Fixed
- Error 404 en el Panel Administrativo mediante la configuración de reglas `mod_rewrite` en `.htaccess`.
- Error de conexión a la base de datos en entorno MAMP ajustando el puerto (8889) y host (127.0.0.1) en el `.env`.
- Fallo en la carga de la clase `Database` en el Front Controller del administrador.
- Procesamiento de URIs en el `Router` para soportar subdirectorios en servidores locales.

### Added
- Logs de diagnóstico en `admin/public/index.php` para facilitar la depuración de rutas.
- Verificación de existencia del archivo `Database.php` antes de su inclusión.

### Added
- **Security & Compatibility:**
  - **Hybrid Password Authentication:** Updated `construccion/login/login.php` to support both legacy SHA-512 hashes and modern BCRYPT (`password_verify`). This ensures new users created in the Admin Panel can access the legacy system.
  - **Data Integrity Audit:** Verified SHA-512 usage in `general_usuarios` and documented the migration path for password security.
- **Admin Panel Infrastructure:**
  - Front Controller implemented in `admin/public/index.php`.
  - URL rewriting configured in `admin/public/.htaccess` for clean routes.
  - Core Router implemented in `admin/src/Core/Router.php`.
  - Security hardening for administrative routes and sessions.
  - Base Controller and User Model implemented in `admin/src/`.
- **Frontend & UI:**
  - Integrated AdminLTE 3 (CDN) for the administrative dashboard.
  - Modular layout system implemented in `admin/views/layouts/main.php`.
  - Dynamic breadcrumbs system for administrative pages.
- **Database Layer:**
  - Implemented Singleton pattern in `Database` class for efficient connection management.
  - Refactored legacy `construccion/conexion.php` to use the `Database::getInstance()` Singleton, unifying connection logic.
  - Automatic environment variable loading for database credentials via `phpdotenv`.

### Changed
- **Architecture:** Transitioned from procedural connection management to a centralized Singleton pattern.
- Updated `.gitignore` to include administrative logs and protect sensitive data.

### Fixed
- Fixed 500 Internal Server Error in the admin panel by properly initializing the Database instance.
- Corrected path resolution for `.env` loading in administrative scripts.
