-- ============================================================================
-- 002_alter_existing_tables.sql
-- Migración Quirúrgica: Columnas/PKs nuevas en tablas existentes (aditivo)
-- Compatible: MySQL 8.0+ / 8.4.6 (Producción SiteGround)
-- Fecha: 2026-03-26
-- ============================================================================
-- POLÍTICA: Solo ADD COLUMN. Nunca DROP. Idempotente via IF NOT EXISTS /
-- condicionales con prepared statements.
-- NOTA: Este script NO usa DELIMITER (compatible con mysql < stdin)
-- ============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------
-- 1. general_usuarios: PK + AUTO_INCREMENT + force_password_change
--    Producción tiene columna `id` pero SIN PK y SIN AUTO_INCREMENT
-- -----------------------------------------------
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_usuarios');

-- 1a. Agregar PK si no existe (sobre columna `id` existente)
SET @pk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_usuarios'
    AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@tbl_exists = 0, 'SELECT "general_usuarios no existe, skip" AS info',
    IF(@pk_exists = 0,
    'ALTER TABLE `general_usuarios` MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)',
    'SELECT "PK already exists on general_usuarios" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1b. Agregar columna force_password_change
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_usuarios'
    AND COLUMN_NAME = 'force_password_change');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_usuarios` ADD COLUMN `force_password_change` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT "force_password_change already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1c. Agregar columna activo
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_usuarios'
    AND COLUMN_NAME = 'activo');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_usuarios` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT "activo already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 2. general_proyectos_procesos: PK + AUTO_INCREMENT
--    Producción tiene columna `Id` (mayúscula) SIN PK y SIN AUTO_INCREMENT
-- -----------------------------------------------
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos');
SET @pk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos'
    AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@tbl_exists = 0, 'SELECT "general_proyectos_procesos no existe, skip" AS info',
    IF(@pk_exists = 0,
    'ALTER TABLE `general_proyectos_procesos` MODIFY COLUMN `Id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`Id`)',
    'SELECT "PK already exists on general_proyectos_procesos" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 3. general_auditoria_acciones: columnas nuevas de auditoría
-- -----------------------------------------------
-- Verificar si la tabla existe antes de intentar alterarla
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones');

-- 3a. event_code
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones'
    AND COLUMN_NAME = 'event_code');
SET @sql = IF(@tbl_exists = 0, 'SELECT "general_auditoria_acciones no existe, skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_auditoria_acciones` ADD COLUMN `event_code` VARCHAR(120) DEFAULT NULL',
    'SELECT "event_code already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3b. event_action
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones'
    AND COLUMN_NAME = 'event_action');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_auditoria_acciones` ADD COLUMN `event_action` VARCHAR(80) DEFAULT NULL',
    'SELECT "event_action already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3c. event_result
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones'
    AND COLUMN_NAME = 'event_result');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_auditoria_acciones` ADD COLUMN `event_result` VARCHAR(20) DEFAULT NULL',
    'SELECT "event_result already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3d. context_json
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones'
    AND COLUMN_NAME = 'context_json');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_auditoria_acciones` ADD COLUMN `context_json` JSON DEFAULT NULL',
    'SELECT "context_json already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3e. ip_address
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_auditoria_acciones'
    AND COLUMN_NAME = 'ip_address');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_auditoria_acciones` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL',
    'SELECT "ip_address already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- FIN Script 002 — Alteraciones aditivas idempotentes (sin DELIMITER)
-- ============================================================================
