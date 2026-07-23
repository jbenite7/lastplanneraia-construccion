-- PDC v2 / follow-up A1: clave de idempotencia del import.
-- Un retry de confirmar con el mismo token (p.ej. timeout HTTP tras commit)
-- debe responder la versión ya creada, no duplicarla. NULL permitido para
-- versiones históricas previas a esta migración (UNIQUE ignora NULLs en MySQL).

ALTER TABLE `pdc_presupuesto_versiones`
  ADD COLUMN `import_token` char(32) DEFAULT NULL AFTER `archivo_hash`,
  ADD UNIQUE KEY `uq_pdcpv_import_token` (`import_token`);
