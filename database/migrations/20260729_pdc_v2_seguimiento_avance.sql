-- 20260729_pdc_v2_seguimiento_avance.sql
-- PDC v2 / Fase B1: el avance real de cada paso de contratacion.
--
-- Tres columnas sobre `pdc_plan_paso`, ninguna tabla nueva. Cuelgan de esa fila porque A4.1 le dio una
-- identidad estable (`paso_id`, no la posicion) y el upsert de `calcular()` lista solo las cuatro
-- columnas programadas: lo que no se lista, MySQL lo conserva. Es decir, estas tres sobreviven a
-- cualquier recalculo sin que haya que tocar PlanFechasService.
--
-- `fecha_real` NULL = el paso todavia no ha ocurrido. No hay columna de estado a proposito: «en curso /
-- cumplido» se deduce de la fecha, y un estado guardado se desincroniza de su fecha el primer dia en que
-- alguien corrija una y olvide la otra.
--
-- `fecha_inicio` / `fecha_fin` pasan a admitir NULL. Lo exige la regla de reamarre de B1: una fila puede
-- llevar avance real y quedarse, temporalmente, sin fechas programadas (el plan viejo se calculo contra
-- otro frente y ya no vale; el siguiente calculo las reescribe). Antes de esto, conservar la fila
-- obligaba a dejar en ella fechas mentirosas.
--
-- Sin backfill: no existe ningun dato de avance previo que migrar.
--
-- Las guardias por `information_schema` hacen que el archivo converja desde cualquier punto de partida y
-- que una segunda ejecucion sea un no-op real.

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_migra_seguimiento_avance$$
CREATE PROCEDURE pdc_v2_migra_seguimiento_avance()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'fecha_real'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `fecha_real` DATE NULL AFTER `fecha_fin`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'registrado_por'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `registrado_por` VARCHAR(100) NOT NULL DEFAULT '' AFTER `fecha_real`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso' AND COLUMN_NAME = 'registrado_at'
  ) THEN
    ALTER TABLE `pdc_plan_paso` ADD COLUMN `registrado_at` DATETIME NULL AFTER `registrado_por`;
  END IF;

  -- Nulabilidad de las programadas. Se comprueba IS_NULLABLE para que la segunda corrida no reescriba
  -- la tabla entera: un MODIFY sobre millones de filas no es gratis, y aqui converger significa
  -- «dejarlo como debe estar», no «volver a hacerlo».
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND COLUMN_NAME = 'fecha_inicio' AND IS_NULLABLE = 'NO'
  ) THEN
    ALTER TABLE `pdc_plan_paso` MODIFY COLUMN `fecha_inicio` DATE NULL;
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND COLUMN_NAME = 'fecha_fin' AND IS_NULLABLE = 'NO'
  ) THEN
    ALTER TABLE `pdc_plan_paso` MODIFY COLUMN `fecha_fin` DATE NULL;
  END IF;
END$$

CALL pdc_v2_migra_seguimiento_avance()$$
DROP PROCEDURE IF EXISTS pdc_v2_migra_seguimiento_avance$$

DELIMITER ;
