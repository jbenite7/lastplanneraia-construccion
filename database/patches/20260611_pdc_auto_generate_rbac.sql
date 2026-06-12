-- Patch: Permiso RBAC para auto-generación PDC
-- Fecha: 2026-06-11
-- Idempotente: INSERT IGNORE evita duplicados.

INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.pdc.auto_generar', 'lps', 'pdc_auto_generar', 'Auto-generar PDC desde el programa general', 1, 0, NOW(), NOW());

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A', 'lps.pdc.auto_generar', 1, 'patch_20260611', NOW(), NOW()),
    ('D', 'lps.pdc.auto_generar', 1, 'patch_20260611', NOW(), NOW()),
    ('OT', 'lps.pdc.auto_generar', 1, 'patch_20260611', NOW(), NOW());
