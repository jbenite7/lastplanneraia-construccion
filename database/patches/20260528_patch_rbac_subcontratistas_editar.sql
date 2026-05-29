-- =============================================================================
-- Patch: 20260528_patch_rbac_subcontratistas_editar.sql
-- Fix:   Otorga lps.subcontratistas.editar a roles R (Residente) y DCV
--        (Profesional DCV) que solo tenían permiso de lectura.
-- =============================================================================
--
-- Contexto:
--   Los roles R y DCV podían editar PG/PI/PS/CIC/CNC/CNP/CC pero no
--   subcontratistas, a pesar de que el frontend lo permitía. Esto generaba
--   error 403 al intentar guardar cambios.
--
-- Idempotente: todas las inserciones usan IGNORE.

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('R',   'lps.subcontratistas.editar', 1, 'patch_20260528', NOW(), NOW()),
    ('DCV', 'lps.subcontratistas.editar', 1, 'patch_20260528', NOW(), NOW());
