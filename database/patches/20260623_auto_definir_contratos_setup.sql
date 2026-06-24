-- =============================================================================
-- Patch: 20260623_auto_definir_contratos_setup.sql
-- Fix:   Agrega permiso RBAC lps.contratos.auto_definir + columnas nuevas
--        en {db}_actividades para el feature auto-definir contratos.
--
-- Idempotente: INSERT IGNORE + ADD COLUMN IF NOT EXISTS (MySQL 8.0.19+).
-- =============================================================================

-- 1. Permiso RBAC: lps.contratos.auto_definir
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.contratos.auto_definir', 'lps', 'contratos_auto_definir', 'Auto-definir contratos con preview y confianza', 1, 0, NOW(), NOW());

-- 2. Asignar permiso a roles: A (Admin), D (Director), OT (Oficina Tecnica)
INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A',  'lps.contratos.auto_definir', 1, 'patch_20260623', NOW(), NOW()),
    ('D',  'lps.contratos.auto_definir', 1, 'patch_20260623', NOW(), NOW()),
    ('OT', 'lps.contratos.auto_definir', 1, 'patch_20260623', NOW(), NOW());

-- 3. Nuevas columnas en actividades.
--    Se aplica por cada proyecto existente. Para proyectos nuevos,
--    Project.php ya incluye las columnas en el CREATE TABLE.
--    NOTA: Ejecutar este ALTER para CADA proyecto. Verificar existencia
--    de columnas antes si se ejecuta más de una vez.
ALTER TABLE `{db}_actividades`
    ADD COLUMN `numeroSubcontratos` TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN `confianza_deteccion` DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN `ultimo_auto_definir` DATETIME DEFAULT NULL,
    ADD COLUMN `fechaInicioProyectada` DATE DEFAULT NULL;
