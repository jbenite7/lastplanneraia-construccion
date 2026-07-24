-- 20260723_pdc_v2_versionamiento_inteligente.sql
-- PDC v2 / Fase A1.7: versionamiento inteligente del importador.
-- version_numero: identificador secuencial por proyecto (Versión N), independiente
--   de la columna VERSION del Excel (que puede venir vacía, como en el DAPORTO real).
-- contenido_hash: SHA-256 del CONTENIDO canónico (items+insumos), no del binario;
--   permite detectar re-cargues idénticos vs la versión activa (anti-duplicado).
-- NULL en contenido_hash para versiones históricas previas a esta migración.

ALTER TABLE `pdc_presupuesto_versiones`
  ADD COLUMN `version_numero` int NOT NULL DEFAULT 0 AFTER `version_label`,
  ADD COLUMN `contenido_hash` char(64) DEFAULT NULL AFTER `archivo_hash`,
  ADD KEY `idx_pdcpv_project_numero` (`project_id`, `version_numero`);

-- Backfill idempotente: numera las versiones existentes por created_at asc dentro de cada proyecto.
UPDATE `pdc_presupuesto_versiones` v
JOIN (
  SELECT `id`, ROW_NUMBER() OVER (PARTITION BY `project_id` ORDER BY `created_at`, `id`) AS rn
  FROM `pdc_presupuesto_versiones`
) n ON n.`id` = v.`id`
SET v.`version_numero` = n.rn
WHERE v.`version_numero` = 0;
