# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Gestión de credenciales de base de datos a través de un archivo `.env` para mejorar la seguridad.
- Implementación de `.gitignore` para excluir archivos y directorios no esenciales del control de versiones.
- Documentación detallada del entorno de desarrollo, incluyendo prerrequisitos y explicación de las extensiones de VS Code recomendadas en `README.md`.
- Creación de archivos iniciales de diagnóstico del proyecto (`README.md`, `CHANGELOG.md`, `ROADMAP.md`).

### Changed
- Actualización de la dependencia `phpoffice/phpspreadsheet` a la versión `^1.30.0`.
- El `ROADMAP.md` fue actualizado para incluir una visión de modernización a largo plazo.

### Fixed
- ...

### Security
- Refactorización de la conexión a la base de datos y consultas para usar PDO, mitigando riesgos de inyección SQL.