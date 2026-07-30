-- 20260729_pdc_v2_equipo_alquilado_comprado.sql
-- PDC v2 / Ola 2 — Equipo alquilado vs equipo comprado.
--
-- NO amplía ningún enum: `general_maestro_insumos.tipo_recurso` es `varchar(60)` (lo siembra el
-- importador SINCO desde «TIPO DESCRIPCION») y admite los valores nuevos sin DDL.
--
-- Lo que falta es la AUDITORÍA de la clasificación: quién la hizo y cuándo. Sin ese par, «lo dijo
-- una persona» y «lo trajo el Excel de SINCO» son indistinguibles, el importador no sabe a quién
-- respetar, y el punto 5 de la condición de hecho —reimportar no borra una clasificación— no sería
-- verificable, sólo afirmable. Es el mismo problema del NULL mudo que se pagó en B1.
--
-- Vuelta atrás: `ALTER TABLE general_maestro_insumos DROP COLUMN clasificado_por, DROP COLUMN clasificado_at;`
-- (probada antes de continuar; ver la bitácora del goal `pdc-preparar-b1`).

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `clasificado_por` varchar(120) DEFAULT NULL AFTER `tipo_recurso`,
  ADD COLUMN `clasificado_at` datetime DEFAULT NULL AFTER `clasificado_por`;
