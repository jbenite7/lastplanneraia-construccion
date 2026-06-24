-- =====================================================================
-- PARCHE PRODUCCION: CATALOGO CNC/CNP PARA PRE-CONSTRUCCION
-- FECHA: 2026-06-24
-- ALCANCE: general_cnc (tabla global)
--
-- Acciones:
--   1. Agrega columna Area VARCHAR(50) DEFAULT 'Construccion' a general_cnc.
--   2. Backfill: filas existentes sin Area se marcan como 'Construccion'.
--   3. Inserta 12 causas CNC para proyectos Pre-Construccion en 5 categorias:
--      - Disenos (3), Modelacion (2), Presupuesto (3), Contratacion (2), Tramites (2).
--
-- Politica:
-- - Aditivo e idempotente (information_schema check + INSERT ... WHERE NOT EXISTS).
-- - No elimina datos ni columnas.
-- - No modifica valores existentes (solo backfill NULL/vacio a 'Construccion').
--
-- =====================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------
-- 1. Agregar columna Area a general_cnc (idempotente)
-- -----------------------------------------------
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_cnc'
    AND COLUMN_NAME = 'Area');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `general_cnc` ADD COLUMN `Area` VARCHAR(50) DEFAULT ''Construccion'' AFTER `CNC`',
    'SELECT "Area already exists on general_cnc" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 2. Backfill: filas existentes -> 'Construccion'
-- -----------------------------------------------
UPDATE `general_cnc`
SET `Area` = 'Construccion'
WHERE `Area` IS NULL OR TRIM(`Area`) = '';

-- -----------------------------------------------
-- 3. INSERT causas CNC Pre-Construccion (idempotente)
-- -----------------------------------------------

-- === Categoria: Disenos ===

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Disenos', 'Cambios en los disenos', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Disenos'
      AND `CNC` = 'Cambios en los disenos'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Disenos', 'Incumplimiento del disenador/Asesor', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Disenos'
      AND `CNC` = 'Incumplimiento del disenador/Asesor'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Disenos', 'Definiciones pendientes', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Disenos'
      AND `CNC` = 'Definiciones pendientes'
      AND `Area` = 'Pre-Construccion'
);

-- === Categoria: Modelacion ===

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Modelacion', 'Pendiente actualizacion de modelo', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Modelacion'
      AND `CNC` = 'Pendiente actualizacion de modelo'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Modelacion', 'Pendiente de talles', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Modelacion'
      AND `CNC` = 'Pendiente de talles'
      AND `Area` = 'Pre-Construccion'
);

-- === Categoria: Presupuesto ===

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Presupuesto', 'No se ha completado cuadro comparativo', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Presupuesto'
      AND `CNC` = 'No se ha completado cuadro comparativo'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Presupuesto', 'No hay cotizaciones', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Presupuesto'
      AND `CNC` = 'No hay cotizaciones'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Presupuesto', 'No hay cantidades', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Presupuesto'
      AND `CNC` = 'No hay cantidades'
      AND `Area` = 'Pre-Construccion'
);

-- === Categoria: Contratacion ===

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Contratacion', 'No hay contrato', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Contratacion'
      AND `CNC` = 'No hay contrato'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Contratacion', 'Pendiente pago a disenador', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Contratacion'
      AND `CNC` = 'Pendiente pago a disenador'
      AND `Area` = 'Pre-Construccion'
);

-- === Categoria: Tramites ===

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Tramites', 'Tramites pendientes', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Tramites'
      AND `CNC` = 'Tramites pendientes'
      AND `Area` = 'Pre-Construccion'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`)
SELECT 'Tramites', 'Pendiente formulario actualizado', 'Pre-Construccion'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Tramites'
      AND `CNC` = 'Pendiente formulario actualizado'
      AND `Area` = 'Pre-Construccion'
);

-- -----------------------------------------------
-- 4. Verificacion: contar registros PC insertados
-- -----------------------------------------------
SELECT
    `Area`,
    COUNT(*) AS total_causas
FROM `general_cnc`
GROUP BY `Area`
ORDER BY `Area`;
