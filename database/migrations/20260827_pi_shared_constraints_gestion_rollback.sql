-- 20260827_pi_shared_constraints_gestion_rollback.sql
--
-- Reversa de 20260827_pi_shared_constraints_gestion.sql (CT-7.3). Quita las 5 columnas de
-- gestion de `pi_shared_constraints`.
--
-- ADVERTENCIA — este archivo se escribio y se probo (Task 4, Paso 5) exclusivamente contra
-- `lastplanneraia_test_backup`, la base de prueba con el respaldo restaurado. NO se ha
-- ejecutado ni debe ejecutarse contra `lastplanneraia_dev` sin una autorizacion explicita
-- nueva de Felipe: dev se queda con las columnas nuevas aplicadas (Paso 4). Este script existe
-- para que la reversa exista y este probada, no para usarse de rutina.
--
-- Idempotente: cada DROP COLUMN esta condicionado a que la columna SI exista.
--
-- Verificado (Paso 5): `ValorObjetivo` — el valor numerico original que la migracion de ida
-- solo LEE, nunca escribe — quedo con la misma huella (MD5 de project_id+Id+ValorObjetivo por
-- fila) antes de aplicar la migracion de ida, despues de aplicarla, y despues de esta reversa:
-- 5967d5a59e8a16faf6c8eefb1e72a37c en las tres mediciones. La migracion nunca lo toco.

SET @db := DATABASE();

SET @existe_responsable := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'ResponsableAsignado'
);
SET @sql_responsable := IF(@existe_responsable > 0,
    'ALTER TABLE pi_shared_constraints DROP COLUMN ResponsableAsignado',
    'DO 0');
PREPARE stmt FROM @sql_responsable; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_fecha := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'FechaCompromiso'
);
SET @sql_fecha := IF(@existe_fecha > 0,
    'ALTER TABLE pi_shared_constraints DROP COLUMN FechaCompromiso',
    'DO 0');
PREPARE stmt FROM @sql_fecha; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_estado := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'EstadoLiberacion'
);
SET @sql_estado := IF(@existe_estado > 0,
    'ALTER TABLE pi_shared_constraints DROP COLUMN EstadoLiberacion',
    'DO 0');
PREPARE stmt FROM @sql_estado; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_asignadopor := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'AsignadoPor'
);
SET @sql_asignadopor := IF(@existe_asignadopor > 0,
    'ALTER TABLE pi_shared_constraints DROP COLUMN AsignadoPor',
    'DO 0');
PREPARE stmt FROM @sql_asignadopor; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_asignadoen := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'AsignadoEn'
);
SET @sql_asignadoen := IF(@existe_asignadoen > 0,
    'ALTER TABLE pi_shared_constraints DROP COLUMN AsignadoEn',
    'DO 0');
PREPARE stmt FROM @sql_asignadoen; EXECUTE stmt; DEALLOCATE PREPARE stmt;
