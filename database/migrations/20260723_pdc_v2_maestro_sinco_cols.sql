-- 20260723_pdc_v2_maestro_sinco_cols.sql
-- PDC v2 / Fase A2.5: extiende el maestro global con los datos autoritativos de SINCO.
-- Aditivo (columnas nullable). El maestro sigue casando presupuestos por (descripcion_norm, unidad);
-- codigo_sinco es la clave del upsert del import SINCO.

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `codigo_sinco` varchar(50) DEFAULT NULL AFTER `id`,
  ADD COLUMN `agrupacion` varchar(150) DEFAULT NULL AFTER `tipo_insumo`,
  ADD COLUMN `tipo_recurso` varchar(60) DEFAULT NULL AFTER `agrupacion`,
  ADD COLUMN `valor_unitario` decimal(18,4) DEFAULT NULL AFTER `tipo_recurso`,
  ADD COLUMN `iva` decimal(5,2) DEFAULT NULL AFTER `valor_unitario`,
  ADD UNIQUE KEY `uq_gmi_codigo_sinco` (`codigo_sinco`),
  ADD KEY `idx_gmi_agrupacion` (`agrupacion`);
