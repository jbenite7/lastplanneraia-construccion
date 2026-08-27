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
-- Idempotente: cada ADD COLUMN, Y el backfill de EstadoLiberacion, estan condicionados a la
-- MISMA bandera (information_schema.COLUMNS: ¿existia ya EstadoLiberacion antes de correr este
-- archivo?), mismo patron que database/migrations/20260807_proyectos_lineabase_columns.sql.
--
-- Corregido 2026-08-26 (Important 1, revision independiente de Task 4): esta cabecera decia que
-- el backfill era una UPDATE incondicional "asi que re-correr esta migracion no cambia el
-- resultado" — cierto solo en el estado sin la feature de hoy. En cuanto Task 5/7 permitan fijar
-- EstadoLiberacion a mano, una UPDATE incondicional en una re-corrida RESETEARIA esa gestion
-- manual a lo que ValorObjetivo implique. No hay runner de migraciones en este repo (se aplican
-- a mano con `mysql < archivo`), asi que una re-corrida es una accion de operador plausible —
-- reconstruir un ambiente, o aplicar este mismo archivo mas adelante contra una base que ya
-- tiene estado real de usuarios. Por eso el backfill ahora comparte la guarda de
-- @existe_estado: en una re-corrida (columna ya presente) es un no-op completo, igual que los 5
-- ADD COLUMN de arriba. Si algun dia hace falta recalcular el backfill sobre una base ya
-- migrada, eso es una migracion NUEVA y deliberada, nunca una re-corrida de esta.

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
--
-- Guardado por @existe_estado (misma bandera que el ADD COLUMN de EstadoLiberacion, arriba):
-- si la columna ya existia antes de correr este archivo, el backfill es un no-op — no resetea
-- gestion manual ya guardada en una re-corrida. El patron `[.]` (dentro de corchetes, dot
-- literal) evita depender de escapar una barra invertida a traves de la capa extra de
-- PREPARE/EXECUTE que esta guarda introduce.
SET @sql_backfill := IF(@existe_estado = 0,
    "UPDATE pi_shared_constraints
        SET EstadoLiberacion = CASE
            WHEN ValorObjetivo NOT REGEXP '^[0-9]+([.][0-9]+)?$' THEN 'no_aplica'
            WHEN CAST(ValorObjetivo AS DECIMAL(10,4)) = 0 THEN 'sin_gestionar'
            WHEN CAST(ValorObjetivo AS DECIMAL(10,4)) >= 1.0 THEN 'liberada'
            ELSE 'en_gestion'
        END",
    'DO 0');
PREPARE stmt FROM @sql_backfill; EXECUTE stmt; DEALLOCATE PREPARE stmt;
