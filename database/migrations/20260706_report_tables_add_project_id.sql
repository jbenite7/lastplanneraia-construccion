-- =============================================================
-- Migration: Add project_id to 6 report tables
-- =============================================================
-- The Consolidar Reports admin module does INSERT...SELECT into
-- these tables with `? AS project_id` in the SELECT list, but
-- the columns didn't exist in the target tables.
-- Also adds missing maxSemana to general_curvas_pdc.
-- =============================================================

-- 1. general_curvas
ALTER TABLE `general_curvas`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD INDEX `idx_curvas_project` (`project_id`);

-- 2. general_curvas_pdc (also missing `maxSemana`)
ALTER TABLE `general_curvas_pdc`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD COLUMN `maxSemana` int DEFAULT NULL AFTER `semana`,
  ADD INDEX `idx_curvas_pdc_project` (`project_id`);

-- 3. general_informe_consolidado
ALTER TABLE `general_informe_consolidado`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD INDEX `idx_informe_consolidado_project` (`project_id`);

-- 4. general_informe_restricciones_consolidado
ALTER TABLE `general_informe_restricciones_consolidado`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD INDEX `idx_informe_restricciones_project` (`project_id`);

-- 5. general_informe_pdc
ALTER TABLE `general_informe_pdc`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD INDEX `idx_informe_pdc_project` (`project_id`);

-- 6. general_informe_subcontratistas
ALTER TABLE `general_informe_subcontratistas`
  ADD COLUMN `project_id` int NOT NULL AFTER `id`,
  ADD INDEX `idx_informe_subcontratistas_project` (`project_id`);
