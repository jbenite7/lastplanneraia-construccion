-- 20260722_pdc_importar_rbac.sql
-- PDC v2 / A1: permiso de importación de presupuesto (A y D). Idempotente.
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.pdc.importar', 'lps', 'pdc_importar', 'Importar presupuesto Excel al plan de compras v2', 1, 1, NOW(), NOW());

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A', 'lps.pdc.importar', 1, 'patch_20260722', NOW(), NOW()),
    ('D', 'lps.pdc.importar', 1, 'patch_20260722', NOW(), NOW());
