# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Admin Panel Infrastructure:**
  - Front Controller implemented in `admin/public/index.php`.
  - URL rewriting configured in `admin/public/.htaccess` for clean routes.
  - Core Router implemented in `admin/src/Core/Router.php`.
  - Security hardening for administrative routes and sessions.
- **Database Layer:**
  - Implemented Singleton pattern in `Database` class for efficient connection management.
  - Automatic environment variable loading for database credentials via `phpdotenv`.
- **Documentation:**
  - Updated `ROADMAP.md` with detailed execution status and future planning.

### Changed
- Refactored `construccion/src/Database.php` to support modern architecture and Singleton pattern.
- Updated `.gitignore` to include administrative logs and protect sensitive data.

### Fixed
- Fixed 500 Internal Server Error in the admin panel by properly initializing the Database instance.