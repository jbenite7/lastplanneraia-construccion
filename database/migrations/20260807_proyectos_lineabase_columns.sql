-- B-9 (barrido del 2026-08-07): `general_proyectos_procesos` necesita
-- `fechaInicioLineaBase` y `fechaFinLineaBase`, pero NINGUNA migracion las creaba.
--
-- Como se detecto: los 3 fallos de `e2e/tests/admin/proyectos-crud.spec.mjs` contra el
-- stack de CI eran un HTTP 500 al crear proyecto, con
--   PDOException SQLSTATE[42S22]: Unknown column 'fechaInicioLineaBase' in 'field list'
--   admin/src/Models/Project.php:356 -> Database->query('INSERT INTO gen...')
--
-- No era un defecto de la aplicacion: la base real SI tiene las dos columnas y el
-- fixture de CI —que se construye desde este directorio— NO. Es decir, existen en la
-- base real porque se anadieron fuera del control de migraciones.
--
-- Consecuencia mientras no exista esta migracion: cualquier entorno levantado o
-- restaurado desde `database/migrations/` nace roto en
--   · `admin/src/Models/Project.php` (crear y actualizar proyecto -> 500)
--   · `src/Services/Pdc/FlujoCajaService.php` (lee ambas columnas)
-- y el propio CI no puede cubrir esa area, asi que su verde prueba menos de lo que
-- aparenta en todo lo que las toque.
--
-- Definicion copiada de la base real, verificada con information_schema:
--   fechaInicioLineaBase  date  NULL  DEFAULT NULL  (posicion 8)
--   fechaFinLineaBase     date  NULL  DEFAULT NULL  (posicion 9)
-- El AFTER reproduce ese orden (van tras `pdcActivo`) para que un entorno nuevo quede
-- identico al real y no meramente equivalente.
--
-- IDEMPOTENTE: usa un bloque condicional sobre information_schema, asi que aplicarla
-- sobre la base real —que ya las tiene— no hace nada y no falla.

SET @db := DATABASE();

SET @existe_inicio := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'general_proyectos_procesos'
      AND COLUMN_NAME = 'fechaInicioLineaBase'
);
SET @sql_inicio := IF(@existe_inicio = 0,
    'ALTER TABLE general_proyectos_procesos
        ADD COLUMN fechaInicioLineaBase DATE NULL DEFAULT NULL AFTER pdcActivo',
    'DO 0');
PREPARE stmt FROM @sql_inicio; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_fin := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'general_proyectos_procesos'
      AND COLUMN_NAME = 'fechaFinLineaBase'
);
SET @sql_fin := IF(@existe_fin = 0,
    'ALTER TABLE general_proyectos_procesos
        ADD COLUMN fechaFinLineaBase DATE NULL DEFAULT NULL AFTER fechaInicioLineaBase',
    'DO 0');
PREPARE stmt FROM @sql_fin; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Tercera columna del mismo hueco, encontrada al VERIFICAR la migracion en vez de
-- darla por buena: tras anadir las dos de arriba, crear proyecto seguia devolviendo
-- 500, ahora por `Unknown column 'costoDiaRetraso'`. Se dejo de ir una a una y se
-- comparo el censo completo de columnas entre la base real (14) y el fixture (13):
-- esta era la unica que faltaba. Tampoco la crea ninguna migracion — solo la nombran
-- las semillas — y la consume `admin/src/Models/Project.php`.
--   costoDiaRetraso  float  NOT NULL  DEFAULT 5000000  (posicion 10 en la base real)
SET @existe_costo := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'general_proyectos_procesos'
      AND COLUMN_NAME = 'costoDiaRetraso'
);
SET @sql_costo := IF(@existe_costo = 0,
    'ALTER TABLE general_proyectos_procesos
        ADD COLUMN costoDiaRetraso FLOAT NOT NULL DEFAULT 5000000 AFTER fechaFinLineaBase',
    'DO 0');
PREPARE stmt FROM @sql_costo; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- LO QUE ESTA MIGRACION *NO* ARREGLA, hallado al verificarla (2026-08-07)
--
-- Tras anadir las tres columnas, crear proyecto en el fixture de CI seguia dando
-- 500, ahora por `Field 'Id' doesn't have a default value`. La causa es estructural
-- y mas honda que unas columnas:
--
--   `general_proyectos_procesos` NO TIENE NINGUNA MIGRACION QUE LA CREE.
--   Su unico CREATE TABLE en todo database/ esta en
--   `database/fixtures/design-system-ci.sql:32`, que es un fixture de CI.
--   Multiples migraciones la REFERENCIAN, pero ninguna la crea.
--
-- Y ese fixture ha derivado del esquema real:
--   · real:    `Id` int AUTO_INCREMENT   · fixture: `Id` int (sin auto_increment)
--   · real:    14 columnas               · fixture: 11 (las 3 de arriba faltaban)
--
-- Consecuencias, que exceden a esta migracion:
--   1. No se puede reconstruir la base desde `database/migrations/`: la tabla nucleo
--      del producto no esta ahi.
--   2. El fixture contra el que corre CI es estructuralmente distinto de produccion,
--      asi que un verde de CI prueba menos de lo que aparenta en todo lo que la toque
--      — por eso `e2e/tests/admin/proyectos-crud.spec.mjs` no puede pasar hoy.
--
-- Arreglarlo pide una decision de arquitectura de datos (llevar el DDL de las tablas
-- nucleo a migraciones versionadas y regenerar el fixture desde ellas), no un parche.
-- Se deja reportado en `docs/reportes/barrido-completo-2026-08-07.md` (B-9).
