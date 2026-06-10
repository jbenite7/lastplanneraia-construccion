-- Patch: Eliminar paso Licify del Plan de Compras
-- Fecha: 2026-06-09
-- Motivo: El paso "Ingreso a plataforma Licify" ya no se usa. 
--          En todos los proyectos históricos, diasIngresoLicify = 0, 1 o 2 (negligible).
--          Los 7 pasos quedan: Elaboración, Entrega, Recibo, Cuadros, Legalización, Fabricación, Insumos en Obra.

-- 1. Eliminar columna de general_dias_procesos_contratacion
ALTER TABLE `general_dias_procesos_contratacion` DROP COLUMN `diasIngresoLicify`;

-- 2. Eliminar columnas de cada {db}_pdc por proyecto
ALTER TABLE `prueba_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `optimizacionJMC_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `metrolineaConfinamientoDos_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `metrolineaDieciseisDescendente_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `metrolineaDieciseisAscendente_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `metrolineaMampDos_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `da_porto_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
ALTER TABLE `milan_campestre_torre_pdc` DROP COLUMN `fechaIngresoLicify`, DROP COLUMN `diasIngresoLicify`, DROP COLUMN `fechaRealIngresoLicify`;
