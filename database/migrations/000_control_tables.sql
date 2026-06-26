-- ============================================================================
-- 000_control_tables.sql
-- Migration Control Tables
-- Fecha: 2026-06-26
-- Proposito: tablas de control para ETL zero-loss
-- Compatible: MySQL 8.0+
-- Ejecucion: mysql -u app -p last_planner < database/migrations/000_control_tables.sql
-- ============================================================================
-- ORDEN: Tablas padre primero, hijas despues (respeta FKs logicas)
-- POLITICA: CREATE TABLE IF NOT EXISTS → seguro re-ejecutar
-- ============================================================================

SET NAMES utf8mb4;
USE `last_planner`;

-- -----------------------------------------------
-- 1. migration_runs
-- Registro maestro de ejecuciones ETL
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `migration_runs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `started_at` DATETIME NOT NULL,
  `finished_at` DATETIME NULL,
  `mode` ENUM('full','delta','cutover_delta') NOT NULL,
  `status` ENUM('running','success','failed','cancelled') NOT NULL,
  `requested_by` VARCHAR(120) NOT NULL,
  `notes` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------
-- 2. migration_batches
-- Fragmentos de datos migrados dentro de un run
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `migration_batches` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `run_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `source_table` VARCHAR(191) NOT NULL,
  `target_table` VARCHAR(191) NOT NULL,
  `chunk_start` BIGINT NULL,
  `chunk_end` BIGINT NULL,
  `rows_read` BIGINT NOT NULL DEFAULT 0,
  `rows_written` BIGINT NOT NULL DEFAULT 0,
  `checksum_source` VARCHAR(64) NULL,
  `checksum_target` VARCHAR(64) NULL,
  `status` ENUM('running','success','failed') NOT NULL,
  `error_message` TEXT NULL,
  `started_at` DATETIME NOT NULL,
  `finished_at` DATETIME NULL,
  KEY `idx_mig_batches_run` (`run_id`),
  KEY `idx_mig_batches_proj` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------
-- 3. migration_watermarks
-- Punto de control para reanudar migraciones delta
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `migration_watermarks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `source_table` VARCHAR(191) NOT NULL,
  `watermark_type` ENUM('id','semana_id','timestamp','none') NOT NULL,
  `watermark_value` VARCHAR(191) NULL,
  `updated_at` DATETIME NOT NULL,
  UNIQUE KEY `uq_watermark` (`project_id`, `source_table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------
-- 4. migration_reconciliation
-- Conteo y checksum por tabla para auditoria post-migracion
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `migration_reconciliation` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `run_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `table_name` VARCHAR(191) NOT NULL,
  `source_count` BIGINT NOT NULL,
  `target_count` BIGINT NOT NULL,
  `diff_count` BIGINT NOT NULL,
  `source_checksum` VARCHAR(64) NULL,
  `target_checksum` VARCHAR(64) NULL,
  `status` ENUM('ok','warn','fail') NOT NULL,
  `details` TEXT NULL,
  `checked_at` DATETIME NOT NULL,
  KEY `idx_mig_rec_run` (`run_id`),
  KEY `idx_mig_rec_proj` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------
-- 5. migration_errors
-- Registro detallado de errores durante la migracion
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `migration_errors` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `run_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `source_table` VARCHAR(191) NOT NULL,
  `source_pk` VARCHAR(191) NULL,
  `error_code` VARCHAR(64) NULL,
  `error_message` TEXT NOT NULL,
  `payload` JSON NULL,
  `created_at` DATETIME NOT NULL,
  KEY `idx_mig_err_run` (`run_id`),
  KEY `idx_mig_err_proj` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
