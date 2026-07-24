-- 20260724_pdc_v2_version_numero_unique.sql
-- PDC v2 / follow-up del review A1.7: unicidad a nivel DB del versionamiento.
--
-- (A) UNIQUE (project_id, version_numero). La auto-numeración de
--     PresupuestoImportService::confirmar() usa `SELECT COALESCE(MAX(version_numero),0)+1`
--     (lectura no bloqueante) seguida de INSERT: dos confirmaciones concurrentes sobre el
--     mismo proyecto podrían asignar el mismo número. Con el UNIQUE, la 2ª transacción
--     falla con duplicate-key y hace rollback en vez de duplicar en silencio (el import es
--     single-user por proyecto, así que la carrera es de baja probabilidad; esto elimina la
--     corrupción posible en el caso raro).
--
-- (B) Una sola versión activa por proyecto. MySQL no soporta índices parciales/condicionales,
--     así que se emula con una columna generada que vale project_id sólo cuando activa=1 y
--     NULL en caso contrario; el UNIQUE ignora los NULL (mismo idiom que uq_pdcpv_import_token).
--     Cierra a nivel DB el riesgo de doble-activo (preexistente a A1.7). No requiere tocar el
--     servicio: en confirmar() el `UPDATE activa=0` corre antes del `INSERT activa=1` dentro de
--     la misma transacción, de modo que nunca hay dos filas con activa_unica=project_id a la vez.
--
-- Idempotente (guardada por information_schema; re-ejecutar es no-op) y segura sobre datos
-- existentes: aborta con SIGNAL si los datos ya violan alguna de las dos restricciones antes de
-- crear el índice (no debería ocurrir: el backfill A1.7 dejó version_numero 1..N sin duplicados).

SET NAMES utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_apply_version_uniqueness$$
CREATE PROCEDURE pdc_v2_apply_version_uniqueness()
BEGIN
  -- Guard (A): no debe haber (project_id, version_numero) duplicados.
  IF EXISTS (
    SELECT 1 FROM `pdc_presupuesto_versiones`
    GROUP BY `project_id`, `version_numero` HAVING COUNT(*) > 1
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Abortado: (project_id, version_numero) duplicados; renumerar antes de aplicar el UNIQUE.';
  END IF;

  -- Guard (B): no debe haber más de una versión activa por proyecto.
  IF EXISTS (
    SELECT 1 FROM `pdc_presupuesto_versiones`
    WHERE `activa` = 1 GROUP BY `project_id` HAVING COUNT(*) > 1
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Abortado: proyectos con más de una versión activa; dejar una sola activa antes de aplicar el UNIQUE.';
  END IF;

  -- (A) Reemplazar el KEY no único por un UNIQUE sobre las mismas columnas.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones'
      AND INDEX_NAME = 'uq_pdcpv_project_numero'
  ) THEN
    IF EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones'
        AND INDEX_NAME = 'idx_pdcpv_project_numero'
    ) THEN
      ALTER TABLE `pdc_presupuesto_versiones` DROP INDEX `idx_pdcpv_project_numero`;
    END IF;
    ALTER TABLE `pdc_presupuesto_versiones`
      ADD UNIQUE KEY `uq_pdcpv_project_numero` (`project_id`, `version_numero`);
  END IF;

  -- (B) Columna generada que emula el índice único parcial de la versión activa.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones'
      AND COLUMN_NAME = 'activa_unica'
  ) THEN
    ALTER TABLE `pdc_presupuesto_versiones`
      ADD COLUMN `activa_unica` int
        GENERATED ALWAYS AS (IF(`activa` = 1, `project_id`, NULL)) VIRTUAL AFTER `activa`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones'
      AND INDEX_NAME = 'uq_pdcpv_activa_unica'
  ) THEN
    ALTER TABLE `pdc_presupuesto_versiones`
      ADD UNIQUE KEY `uq_pdcpv_activa_unica` (`activa_unica`);
  END IF;
END$$

CALL pdc_v2_apply_version_uniqueness()$$
DROP PROCEDURE IF EXISTS pdc_v2_apply_version_uniqueness$$

DELIMITER ;
