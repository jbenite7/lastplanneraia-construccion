# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Planificación Extendida (Roadmap):** Se ha extendido el plan de trabajo a 4 meses, integrando una nueva fase para el desarrollo de un Panel de Administración centralizado.
- **Gestión de Administración:** Inclusión en el roadmap de funcionalidades para la gestión de proyectos, usuarios y un sistema de permisos basado en roles (RBAC).
- **Diagnóstico y Roadmap Detallado:** Realización de un análisis técnico completo y creación de un `ROADMAP.md` con un plan de acción de 3 meses para modernizar la aplicación.
- Gestión de credenciales de base de datos a través de un archivo `.env` para mejorar la seguridad.
- Implementación de `.gitignore` para excluir archivos y directorios no esenciales del control de versiones.
- Documentación del entorno de desarrollo y recomendaciones de extensiones de VS Code en `README.md`.

### Changed
- **Roadmap Refinado:** Se ha ajustado la Fase 1 para integrar las tablas existentes `general_usuarios` y `general_proyectos_procesos` en lugar de crear nuevas.
- **Roadmap Actualizado:** Se ha re-priorizado el plan de trabajo, estableciendo como **Fase 1** inmediata la construcción de un Panel de Administración de alta eficiencia (LAMP optimizado, DataTables Server-Side, RBAC) para las próximas 2 semanas.
- **Estructura del Proyecto:** Se ha centralizado el proyecto en el directorio raíz, moviendo la configuración principal y la documentación fuera de la subcarpeta `construccion/`.
- **Consolidación de Código:** Se han unificado los directorios duplicados `pdc` y `pdc1`, eliminando `pdc1` y conservando una única versión del módulo para reducir la deuda técnica.
- **Documentación Mejorada:** Se han actualizado `README.md`, `CHANGELOG.md` y `ROADMAP.md` para reflejar el estado actual, el diagnóstico y la visión futura del proyecto.
- **Dependencia Actualizada:** Se ha actualizado `phpoffice/phpspreadsheet` a la versión `^1.29.0`.

### Security
- **Eliminación de Inyección SQL (En progreso):**
    - Refactorización completa del módulo de Programación Semanal: `listar_programacion_semanal.php` y `guardar_programacion_semanal.php` migrados a PDO con consultas preparadas y validación estricta de lógica de indicadores (CIC).
    - Refactorización de módulos de lectura: `listar_programa_general.php` migrado a PDO con consultas preparadas y validación estricta de nombres de tablas.
    - Refactorización de módulos de escritura: `guardar_programa_general.php` migrado a un sistema de base de datos centralizado y seguro.
    - Se ha creado la clase `construccion/src/Database.php`, que centraliza la conexión a la base de datos mediante PDO y obliga el uso de consultas preparadas para prevenir inyecciones SQL.
    - Se refactorizó `construccion/conexion.php` para utilizar la nueva clase `Database`.
    - Se refactorizó por completo el módulo de `login` (`construccion/login/login.php` y `construccion/login/views/login.view.php`) para usar el nuevo sistema seguro.
    - Se refactorizó el script `construccion/generarReporteSubcontratistas.php` como ejemplo de migración para otros módulos.
    - Se refactorizaron por completo los scripts `construccion/actualizarCICProyectos.php` y `construccion/controlCambios/descargarConsolidadoODC.php` para utilizar PDO, consultas preparadas y validación estricta de parámetros.
    - Se implementó sanitización y prevención de Path Traversal en `construccion/controlCambios/ordenes/cargarPDFServidor.php`.

### Removed
- **Módulos Redundantes:** Se han eliminado los directorios `PI/` y `construccion/ext/`, que contenían versiones antiguas o redundantes de la aplicación, simplificando la estructura del proyecto y reduciendo la deuda técnica.
- **Limpieza de Código Muerto:**
    - Eliminación del módulo huérfano de autenticación con Google: `construccion/controlCambios/cargarODC/`.
    - Eliminación de scripts obsoletos o mal ubicados: `construccion/controlCambios/posicion_controlCambios.php` y `construccion/programacion_semanal/actualizarCICProyectos.php`.
