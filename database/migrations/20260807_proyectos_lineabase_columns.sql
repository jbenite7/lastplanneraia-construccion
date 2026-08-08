-- B-9 (barrido del 2026-08-07): `general_proyectos_procesos` no tenia NINGUNA migracion.
--
-- Como se llego aqui: los 3 fallos de `e2e/tests/admin/proyectos-crud.spec.mjs` eran un
-- HTTP 500 al crear proyecto. El log daba
--   PDOException SQLSTATE[42S22]: Unknown column 'fechaInicioLineaBase'
--   admin/src/Models/Project.php:356 -> Database->query('INSERT INTO gen...')
-- No era un defecto de la aplicacion: la base real SI tiene la columna y el fixture de CI
-- no. Al arreglarlo aparecieron dos capas mas —faltaba tambien `costoDiaRetraso`, y luego
-- `Id` no era AUTO_INCREMENT— hasta dar con la causa raiz:
--
--   El unico CREATE TABLE de esta tabla en todo `database/` vivia en
--   `database/fixtures/design-system-ci.sql`, que es un FIXTURE DE PRUEBAS.
--   Muchas migraciones la REFERENCIAN; ninguna la creaba.
--
-- Es decir: no se podia reconstruir la base desde `database/migrations/`, porque la tabla
-- nucleo del producto no estaba ahi. Esta migracion cierra ese hueco.
--
-- DOS MITADES, ambas idempotentes:
--   1. CREATE TABLE IF NOT EXISTS con el esquema REAL, para un entorno nuevo.
--   2. ALTERs condicionales de las tres columnas que faltaban, para un entorno que ya
--      tiene la tabla (la base real las tiene: alli esta migracion no hace nada).
--
-- El DDL de la mitad 1 es copia literal de `SHOW CREATE TABLE` de la base real, verificado
-- el 2026-08-07 — incluido `utf8mb3`. Se copia tal cual y NO se moderniza a `utf8mb4` a
-- proposito: el objetivo es que un entorno reconstruido se comporte igual que produccion,
-- no parecido. Cambiar el charset es una decision propia, con su migracion de datos.

-- ---------------------------------------------------------------------------
-- 1. La tabla, para un entorno nuevo.
CREATE TABLE IF NOT EXISTS `general_proyectos_procesos` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Proyecto_Proceso` varchar(50) NOT NULL,
  `Base_de_Datos` varchar(30) NOT NULL,
  `Area` varchar(50) NOT NULL,
  `Activo` int NOT NULL DEFAULT '1',
  `Acceso` int NOT NULL DEFAULT '1',
  `pdcActivo` int NOT NULL DEFAULT '0',
  `fechaInicioLineaBase` date DEFAULT NULL,
  `fechaFinLineaBase` date DEFAULT NULL,
  `costoDiaRetraso` float NOT NULL DEFAULT '5000000',
  `urlCambios` longtext,
  `pc_restr_2_nombre` varchar(100) DEFAULT NULL,
  `pc_restr_3_nombre` varchar(100) DEFAULT NULL,
  `pc_restr_4_nombre` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- ---------------------------------------------------------------------------
-- 2. Las tres columnas, para un entorno que ya tenia la tabla sin ellas.
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
-- 3. La clave primaria autoincremental, para un entorno que ya tenia la tabla sin ella.
-- Sin esto, un INSERT que no pasa `Id` explicito muere con
-- `Field 'Id' doesn't have a default value`, que es justo lo que hacia
-- `admin/src/Models/Project.php` en el fixture de CI.
SET @es_auto := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'general_proyectos_procesos'
      AND COLUMN_NAME = 'Id'
      AND EXTRA LIKE '%auto_increment%'
);
SET @sql_auto := IF(@es_auto = 0,
    'ALTER TABLE general_proyectos_procesos
        MODIFY COLUMN Id INT NOT NULL AUTO_INCREMENT',
    'DO 0');
PREPARE stmt FROM @sql_auto; EXECUTE stmt; DEALLOCATE PREPARE stmt;
