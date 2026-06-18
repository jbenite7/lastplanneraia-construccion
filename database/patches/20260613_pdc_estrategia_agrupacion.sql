-- ============================================================================
-- Migración: Agregar columna estrategia_agrupacion a project_family_strategy
-- ============================================================================
-- Permite configurar por proyecto+semana cómo se agrupan las actividades
-- del PG en _actividades:
--   - 'familia'   : Agrupa por familia detectada (default - da_porto pattern)
--   - 'categoria' : Agrupa por categoría de familia (ACABADOS, ESTRUCTURA)
--   - 'capitulo'  : Agrupa por capítulo del PG (1.4 = ACABADOS)
--   - 'plantilla' : Agrupa por plantilla maestra (Residencial, Comercial, Vial)
-- ============================================================================

ALTER TABLE `general_pdc_project_family_strategy`
    ADD COLUMN `estrategia_agrupacion` ENUM('familia','categoria','capitulo','plantilla')
    NOT NULL DEFAULT 'familia' AFTER `aplicada`;

-- Verificar el cambio
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'general_pdc_project_family_strategy'
  AND COLUMN_NAME = 'estrategia_agrupacion';
