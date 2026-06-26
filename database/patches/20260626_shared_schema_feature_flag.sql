-- ===========================================================================
-- Feature flag: shared_schema_enabled
-- ===========================================================================
-- Controla si la app usa tablas compartidas (project_*) en lugar de tablas
-- por prefijo (cada proyecto con su propio conjunto de tablas).
--
-- false (default) → tablas por prefijo (comportamiento actual)
-- true            → tablas project_* (nuevo esquema compartido)
--
-- Idempotente: INSERT IGNORE evita duplicados al re-ejecutar.
-- ===========================================================================

INSERT IGNORE INTO `general_feature_flags`
    (`flag_key`, `flag_value`, `description`, `updated_by`)
VALUES
    ('shared_schema_enabled', 'false',
     'Cuando esta en true, la app lee/escribe sobre tablas project_* en vez de tablas por prefijo',
     'Sistema');
