# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Diagnóstico y Roadmap Detallado:** Realización de un análisis técnico completo y creación de un `ROADMAP.md` con un plan de acción de 3 meses para modernizar la aplicación.
- Gestión de credenciales de base de datos a través de un archivo `.env` para mejorar la seguridad.
- Implementación de `.gitignore` para excluir archivos y directorios no esenciales del control de versiones.
- Documentación del entorno de desarrollo y recomendaciones de extensiones de VS Code en `README.md`.

### Changed
- **Estructura del Proyecto:** Se ha centralizado el proyecto en el directorio raíz, moviendo la configuración principal y la documentación fuera de la subcarpeta `construccion/`.
- **Documentación Mejorada:** Se han actualizado `README.md`, `CHANGELOG.md` y `ROADMAP.md` para reflejar el estado actual, el diagnóstico y la visión futura del proyecto.
- **Dependencia Actualizada:** Se ha actualizado `phpoffice/phpspreadsheet` a la versión `^1.29.0`.

### Security
- **Eliminación de Inyección SQL (Trabajo en curso):**
    - Se ha creado la clase `construccion/src/Database.php`, que centraliza la conexión a la base de datos mediante PDO y obliga el uso de consultas preparadas para prevenir inyecciones SQL.
    - Se refactorizó `construccion/conexion.php` para utilizar la nueva clase `Database`.
    - Se refactorizó por completo el módulo de `login` (`construccion/login/login.php` y `construccion/login/views/login.view.php`) para usar el nuevo sistema seguro.
    - Se refactorizó el script `construccion/generarReporteSubcontratistas.php` como ejemplo de migración para otros módulos.

### Removed
- **Directorio Obsoleto:** Se ha eliminado por completo el directorio `PI/`, que contenía una versión antigua o paralela de la aplicación, reduciendo la complejidad y el código a mantener.
