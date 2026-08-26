-- 20260827_pi_shared_constraints_gestion.sql
--
-- CT-7.3 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, Task 4 del plan
-- 2026-08-26-ola1-torre-etapa-piloto). Agrega a `pi_shared_constraints` las 5 columnas de
-- gestion que D30/D33 necesitan: estado explicito de liberacion, responsable y fecha de
-- compromiso, con su auditoria de asignacion.
--
-- Autorizacion: Felipe, en el chat, sobre la salida real del dry-run
-- (scripts/dry-run-constraints-gestion.php, corrido contra `lastplanneraia_dev` el
-- 2026-08-26 — ver .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-4-report.md):
-- «Si a ambas — aplicar con N/A->no_aplica, y sin reconciliacion contra Power BI, dejando la
-- razon documentada en el commit». Las dos decisiones que el dry-run dejo abiertas quedan
-- resueltas asi:
--
--   1. `ValorObjetivo` no numerico ('N/A', 21 de 191 filas) -> EstadoLiberacion = 'no_aplica'.
--   2. Se aplica SIN un numero de Power BI que reconcilie contra el total reconstruido. El
--      dry-run investigo por que: las dos metricas de restricciones que hoy corren
--      (`pi_hard_restrictions_ready_rate`, `pi_restriction_pareto`) filtran `is_hard=1` y
--      excluyen por diseno esta poblacion; la unica vista que si la incluye
--      (`bi_pi_restricciones` WHERE is_hard=0) mide a otro grano (4005 links, no 191
--      constraints) y con otra formula (ValorAplicado>=ValorObjetivo, no una clasificacion de
--      ValorObjetivo solo) — no hay, en este entorno, un numero comparable al de esta
--      migracion. El detalle completo de esa investigacion vive en task-4-report.md.
--
-- Respaldo verificable ya tomado y probado antes de aplicar (paso 1): dump de
-- `pi_shared_constraints` + `pi_shared_constraint_links` restaurado en
-- `lastplanneraia_test_backup`, filas y CHECKSUM TABLE identicos entre origen y restauracion.
--
-- Idempotente: cada ADD COLUMN esta condicionado a que la columna no exista ya
-- (information_schema.COLUMNS), mismo patron que
-- database/migrations/20260807_proyectos_lineabase_columns.sql. El backfill de
-- EstadoLiberacion es una UPDATE incondicional: recalcula el estado de TODAS las filas a
-- partir de ValorObjetivo, asi que re-correr esta migracion no cambia el resultado (a menos
-- que ValorObjetivo mismo haya cambiado, que esta migracion nunca toca).

SET @db := DATABASE();

-- ---------------------------------------------------------------------------
-- 1. Las 5 columnas de CT-7.3, cada una solo si falta.

SET @existe_responsable := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'ResponsableAsignado'
);
SET @sql_responsable := IF(@existe_responsable = 0,
    'ALTER TABLE pi_shared_constraints
        ADD COLUMN ResponsableAsignado VARCHAR(120) NULL DEFAULT NULL AFTER Nota',
    'DO 0');
PREPARE stmt FROM @sql_responsable; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_fecha := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'FechaCompromiso'
);
SET @sql_fecha := IF(@existe_fecha = 0,
    'ALTER TABLE pi_shared_constraints
        ADD COLUMN FechaCompromiso DATE NULL DEFAULT NULL AFTER ResponsableAsignado',
    'DO 0');
PREPARE stmt FROM @sql_fecha; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_estado := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'EstadoLiberacion'
);
SET @sql_estado := IF(@existe_estado = 0,
    "ALTER TABLE pi_shared_constraints
        ADD COLUMN EstadoLiberacion ENUM('sin_gestionar','en_gestion','liberada','no_aplica')
            NOT NULL DEFAULT 'sin_gestionar' AFTER FechaCompromiso",
    'DO 0');
PREPARE stmt FROM @sql_estado; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_asignadopor := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'AsignadoPor'
);
SET @sql_asignadopor := IF(@existe_asignadopor = 0,
    'ALTER TABLE pi_shared_constraints
        ADD COLUMN AsignadoPor VARCHAR(120) NULL DEFAULT NULL AFTER EstadoLiberacion',
    'DO 0');
PREPARE stmt FROM @sql_asignadopor; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_asignadoen := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pi_shared_constraints'
      AND COLUMN_NAME = 'AsignadoEn'
);
SET @sql_asignadoen := IF(@existe_asignadoen = 0,
    'ALTER TABLE pi_shared_constraints
        ADD COLUMN AsignadoEn DATETIME NULL DEFAULT NULL AFTER AsignadoPor',
    'DO 0');
PREPARE stmt FROM @sql_asignadoen; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 2. Backfill de EstadoLiberacion, regla de compatibilidad CT-7.3 + extension no_aplica
-- (confirmada por Felipe, ver cabecera): ValorObjetivo no numerico -> no_aplica; '0' ->
-- sin_gestionar; intermedio (0 < valor < 1) -> en_gestion; >=1.0 -> liberada. No toca
-- ValorObjetivo: solo LEE esa columna para escribir EstadoLiberacion.
UPDATE pi_shared_constraints
SET EstadoLiberacion = CASE
    WHEN ValorObjetivo NOT REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN 'no_aplica'
    WHEN CAST(ValorObjetivo AS DECIMAL(10,4)) = 0 THEN 'sin_gestionar'
    WHEN CAST(ValorObjetivo AS DECIMAL(10,4)) >= 1.0 THEN 'liberada'
    ELSE 'en_gestion'
END;
