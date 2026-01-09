# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Unreleased]

### Added
- **User Management Enhancements:**
  - Automatic username suggestion based on name or email prefix with duplication detection.
  - Duplication prevention for full names and email addresses during user creation.
  - Integrated **Select2** for job titles (cargos) with AJAX search and tag support.
  - Password generator and visibility toggle in the user creation form.
- **UI/UX Optimizations:**
  - **Sticky Headers:** Implemented persistent table headers for Projects and Users lists.
  - **Horizontal Space Optimization:** Applied compact styling (`table-sm`), smaller fonts, and `text-break` rules to eliminate horizontal scrolling.
  - **DataTables Fix:** Resolved "table.buttons is not a function" error by properly including Buttons extension assets.
  - **CORS Resolution:** Inlined Spanish translation for DataTables to avoid cross-origin request issues on local environments.

### Removed
- **Redundant Files Cleanup:** 
  - Deleted multiple test and backup files in `/construccion` including `prueba.php` (various modules), `listar_programacion_semanal1.php`, `pruebaSpreadsheets.php`, `charts1.php`, and the `prueba_correos/` directory.
  - **New Cleanup:** Removed legacy management scripts replaced by the Admin MVC: `Cargar_Nuevos_Proyectos.php`, `eliminar_proyectos.php`, and `Copia_de_Seguridad_LPS.php`.
  - **Redundancy:** Removed root redirects `cerrar.php`, `login/login.php` (and its directory), and `check_schema.php`.
  - **Maintenance:** Deleted `admin/test_models.php`, `admin/test_create_project.php`, and duplicate view `construccion/pdc/pdc.view.nuevaBarra.php`.

### Changed
- **Permission Refinement:** Simplified global permission display in the user list to show "Administrador" or "Usuario" badges based on global codes (A/U).
- **Cleanup:** Removed the redundant "Proyecto" column from the global user list to focus on user profile data.

## [0.4.0] - 2026-01-08

### Added
- **Gestión de Miembros:** Implementación completa del sistema de membresía para vincular usuarios únicos a múltiples proyectos.
- **Inteligencia de Roles:** 
  - Motor de normalización de cargos (limpieza de acentos, géneros y artículos).
  - Búsqueda difusa (Fuzzy Matching) mediante algoritmo de Levenshtein para tolerancia a errores de ortografía.
  - Sistema de aprendizaje persistente en la tabla `role_intelligence` que evoluciona con el uso del administrador.
- **UI Proyectos:** Nueva interfaz para asignar y revocar acceso a proyectos con sugerencias inteligentes en tiempo real.
- **Seguridad:** Protocolo de "Seguridad por Defecto" que asigna rol de Visualizador ante cargos desconocidos.

### Changed
- **Normalización de Datos:** Unificación de la tabla `general_usuarios` eliminando más de 100 registros duplicados y consolidando sus accesos en la nueva tabla `project_members`.
- **Arquitectura:** Centralización de la lógica de permisos en la clase `RoleManager`.

## [0.3.0] - 2026-01-08

### Added
- **Integridad de Datos:** Creación automática de 10 tablas relacionales por cada proyecto nuevo.
- **Gestión de Prefijos:** Renombrado atómico de tablas de base de datos cuando se modifica el prefijo del proyecto.
- **Respaldos:** Funcionalidad para exportar y descargar un volcado SQL completo de las tablas de un proyecto.
- **Eliminación Segura:** Flujo de trabajo con SweetAlert2 que descarga un respaldo antes de eliminar físicamente las tablas.
- **UI Proyectos:** Integración completa de DataTables con traducción al español, Toastr para feedback asíncrono y corrección de solapamiento en el layout.

### Fixed
- Error de namespace en la generación del token CSRF en el layout principal (`\Admin\Core\Security`).
- Delegación de eventos en DataTables para asegurar que los botones funcionen tras buscas o cambios de página.

## [0.2.0] - 2026-01-08

### Added
- **Project Management Enhancements:**
  - Expanded project schema with new fields: Area (Construction/PI), Access Control, PDC Status, Baseline Dates (Start/End), Delay Cost, and Change Control URL.
  - Automatic Database Name Generation: Implemented a robust `slugify` logic that:
    - Removes Spanish stop words (el, de, la, etc.).
    - Converts numbers (1-10) to Roman numerals (i, ii, iii...).
    - Handles transliteration and separates words with underscores.
    - Automatically appends `_pi` suffix for PI area projects.
  - Full CRUD implementation:
    - New Project creation view with advanced fields.
    - Project Edit view with database name manual override capability.
    - Updated Project list with Area column and improved styling.
  - Secure routing for all project CRUD operations with CSRF protection.

## [0.1.0] - 2026-01-08

### Added
- **Project Module:**
  - Initial implementation of `ProjectController`.
  - Advanced projects list view with DataTables (search, export, pagination).
  - Clean routing for project management (/admin/proyectos).
- **Navigation & Compatibility:**
  - Standardized all administrative links to use friendly URLs.
  - Implemented `admin/index.php` as a bridge to prevent directory listing and improve entry point handling.
  - Hardened `admin/.htaccess` security.

### Fixed
- Fixed 403 Forbidden/Directory Listing issue when accessing `/admin/`.
- Corrected sidebar links that were pointing to non-existent .php files.

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