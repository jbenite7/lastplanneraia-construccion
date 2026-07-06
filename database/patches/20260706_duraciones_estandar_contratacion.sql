-- =============================================================================
-- Patch: 20260706_duraciones_estandar_contratacion.sql
-- Fix:   Limpieza y estandarización de duraciones en
--        general_dias_procesos_contratacion.
--
-- 1. Elimina filas default donde TODOS los pasos = 1 (398 en producción).
--    Esos registros se crearon con valores por defecto sin datos reales.
-- 2. Reclasifica CONCRETO y ACERO DE REFUERZO de Suministro → Orden de Compra.
-- 3. Inserta filas ESTÁNDAR por cada modalidad con la duración de mayor
--    confianza estadística (moda si CV≤0.5 y frecuencia≥40%, sino mediana).
--
-- Idempotente: DELETE/UPDATE condicional, INSERT ON DUPLICATE KEY.
-- =============================================================================

-- 1. Eliminar filas default (todos los 7 campos = 1, sin info real)
DELETE FROM general_dias_procesos_contratacion
WHERE diasElaboracionPliegos = 1
  AND diasEntregaPliegos = 1
  AND diasReciboPropuestas = 1
  AND diasCuadrosComparativos = 1
  AND diasLegalizacionContrato = 1
  AND diasFabricacion = 1
  AND diasInsumosObra = 1;

-- 2. Reclasificar CONCRETO y ACERO DE REFUERZO a Orden de Compra
UPDATE general_dias_procesos_contratacion
SET tipoPaquete = 'Orden de Compra'
WHERE tipoPaquete = 'Suministro'
  AND (TRIM(paqueteContratacion) = 'CONCRETO' OR TRIM(paqueteContratacion) = 'ACERO DE REFUERZO');

-- 3. Insertar duraciones estándar por modalidad (idempotente)
INSERT INTO general_dias_procesos_contratacion
    (paqueteContratacion, tipoPaquete,
     diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
     diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
VALUES
    ('ESTÁNDAR MO', 'Mano de Obra',               7,  7,  1, 15, 20,  7,  7),
    ('ESTÁNDAR S',  'Suministro',                 7, 10,  1, 20, 15, 30, 15),
    ('ESTÁNDAR SI', 'Suministro e Instalación',   10, 15,  1, 15, 20, 30, 10),
    ('ESTÁNDAR OC', 'Orden de Compra',             7,  5,  7, 25, 20, 17, 15)
ON DUPLICATE KEY UPDATE
    diasElaboracionPliegos   = VALUES(diasElaboracionPliegos),
    diasEntregaPliegos       = VALUES(diasEntregaPliegos),
    diasReciboPropuestas     = VALUES(diasReciboPropuestas),
    diasCuadrosComparativos  = VALUES(diasCuadrosComparativos),
    diasLegalizacionContrato = VALUES(diasLegalizacionContrato),
    diasFabricacion          = VALUES(diasFabricacion),
    diasInsumosObra          = VALUES(diasInsumosObra);