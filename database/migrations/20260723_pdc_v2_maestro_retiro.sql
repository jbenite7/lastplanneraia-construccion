-- 20260723_pdc_v2_maestro_retiro.sql
-- Follow-up A2: auditoría de ediciones del catálogo global de insumos
-- (retiro/reactivación con activo=0/1 y trazabilidad de quién/cuándo).

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `actualizado_por` varchar(100) NOT NULL DEFAULT '' AFTER `creado_por`,
  ADD COLUMN `updated_at` datetime NULL DEFAULT NULL AFTER `created_at`;
