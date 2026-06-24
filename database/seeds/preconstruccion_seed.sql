-- =====================================================================
-- SEED: Proyecto Pre-Construccion - Aeropuerto Regional
-- FECHA: 2026-06-24
-- BASE DE DATOS: lastplanneraia_dev
--
-- Politica:
-- - INSERT IGNORE para idempotencia (re-ejecutable sin duplicar).
-- - No elimina datos ni tablas existentes.
-- - No modifica proyectos existentes.
-- - Crea tablas IF NOT EXISTS.
--
-- Uso:
--   docker exec last-planner-aia-db-1 mysql -uroot -p'Jbe#1106z' \
--     lastplanneraia_dev < database/seeds/preconstruccion_seed.sql
--
-- Verificacion post-ejecucion:
--   docker exec last-planner-aia-db-1 mysql -uroot -p'Jbe#1106z' \
--     lastplanneraia_dev -e "SELECT Id, Proyecto_Proceso, Base_de_Datos, Area \
--     FROM general_proyectos_procesos WHERE Area='Pre-Construccion'"
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- PARTE 1: Registrar proyecto en general_proyectos_procesos
-- =====================================================================
-- Valores basados en el esquema real de la tabla:
--   Id, Proyecto_Proceso, Base_de_Datos, Area, pc_restr_{2,3,4}_nombre,
--   Activo, Acceso, pdcActivo, fechaInicioLineaBase, fechaFinLineaBase,
--   costoDiaRetraso, urlCambios

INSERT IGNORE INTO `general_proyectos_procesos` (
    `Proyecto_Proceso`,
    `Base_de_Datos`,
    `Area`,
    `pc_restr_2_nombre`,
    `pc_restr_3_nombre`,
    `pc_restr_4_nombre`,
    `Activo`,
    `Acceso`,
    `pdcActivo`,
    `fechaInicioLineaBase`,
    `fechaFinLineaBase`,
    `costoDiaRetraso`,
    `urlCambios`
) VALUES (
    'Aeropuerto Regional PC',
    'da_aeropuerto_pc',
    'Pre-Construccion',
    'Permisos Ambientales',
    'Disenos',
    'Apropiacion Presupuestal',
    1,
    1,
    0,
    '2026-07-01',
    '2026-12-31',
    8000000,
    NULL
);

-- =====================================================================
-- PARTE 2: Crear tablas del proyecto (da_aeropuerto_pc_*)
-- =====================================================================
-- Esquema exacto de createPreConstructionTables() en Project.php

-- -----------------------------------------------
-- 2.1 da_aeropuerto_pc_programa
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_programa` (
    `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Id` varchar(500) DEFAULT NULL,
    `Actividad` varchar(500) DEFAULT NULL,
    `Titulo` int(11) DEFAULT NULL,
    `Fecha_Inicio` date DEFAULT NULL,
    `Fecha_Fin` date DEFAULT NULL,
    `Ruta_Critica` int(11) DEFAULT NULL,
    `Ejecutado` float DEFAULT 0,
    `Estado` varchar(50) DEFAULT NULL,
    `Semanas_Inicio` int(1) DEFAULT 0,
    `Estado_Restricciones` float DEFAULT 0,
    `D_y_E` float DEFAULT 0,
    `Materiales` float DEFAULT 0,
    `MdeO` float DEFAULT 0,
    `Equipos` float DEFAULT 0,
    `Predecesora` float DEFAULT 0,
    `Pdto_Cons` float DEFAULT 0,
    `Modelo` varchar(9) DEFAULT '0',
    `Responsable_AIA` varchar(100) DEFAULT NULL,
    `Observaciones` mediumtext DEFAULT NULL,
    `Ult_Act_Est` date DEFAULT NULL,
    `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Columnas restriccion_pc (del parche 20260624_preconstruccion_schema.sql)
SET @tbl_exists_pc = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa'
    AND COLUMN_NAME = 'restriccion_pc_1');
SET @sql_pc = IF(@tbl_exists_pc = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa` ADD COLUMN `restriccion_pc_1` VARCHAR(10) DEFAULT ''0%'' AFTER `Modelo`',
    'SELECT "restriccion_pc_1 already exists" AS info');
PREPARE stmt FROM @sql_pc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pc2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa'
    AND COLUMN_NAME = 'restriccion_pc_2');
SET @sql_pc2 = IF(@col_exists_pc2 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa` ADD COLUMN `restriccion_pc_2` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_1`',
    'SELECT "restriccion_pc_2 already exists" AS info');
PREPARE stmt FROM @sql_pc2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pc3 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa'
    AND COLUMN_NAME = 'restriccion_pc_3');
SET @sql_pc3 = IF(@col_exists_pc3 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa` ADD COLUMN `restriccion_pc_3` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_2`',
    'SELECT "restriccion_pc_3 already exists" AS info');
PREPARE stmt FROM @sql_pc3;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pc4 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa'
    AND COLUMN_NAME = 'restriccion_pc_4');
SET @sql_pc4 = IF(@col_exists_pc4 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa` ADD COLUMN `restriccion_pc_4` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_3`',
    'SELECT "restriccion_pc_4 already exists" AS info');
PREPARE stmt FROM @sql_pc4;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 2.2 da_aeropuerto_pc_programa_consolidado
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_programa_consolidado` (
    `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Semana` int(3) NOT NULL,
    `Consecutivo_en_Programa` int(11) NOT NULL,
    `Id` varchar(500) DEFAULT NULL,
    `Actividad` varchar(500) DEFAULT NULL,
    `Titulo` int(11) DEFAULT NULL,
    `Fecha_Inicio` date DEFAULT NULL,
    `Fecha_Fin` date DEFAULT NULL,
    `Ruta_Critica` int(11) DEFAULT NULL,
    `Ejecutado` float DEFAULT 0,
    `Estado` varchar(100) DEFAULT NULL,
    `Semanas_Inicio` int(10) DEFAULT 0,
    `Estado_Restricciones` float NOT NULL DEFAULT 0,
    `D_y_E` varchar(9) NOT NULL DEFAULT '0',
    `Materiales` varchar(9) NOT NULL DEFAULT '0',
    `MdeO` varchar(9) NOT NULL DEFAULT '0',
    `Equipos` varchar(9) NOT NULL DEFAULT '0',
    `Predecesora` varchar(9) NOT NULL DEFAULT '0',
    `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
    `Modelo` varchar(9) NOT NULL DEFAULT '0',
    `Sub_Contratista` varchar(100) DEFAULT NULL,
    `Responsable_AIA` varchar(100) DEFAULT NULL,
    `Observaciones` mediumtext DEFAULT NULL,
    `Ult_Act_Est` date DEFAULT NULL,
    `Ult_Act_Restr` date DEFAULT NULL,
    `Activa` int(1) NOT NULL DEFAULT 0,
    `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
    `codigo_actividad` varchar(11) DEFAULT NULL,
    `medir_productividad` int(11) DEFAULT 0,
    `cantidad_ppto` int(11) DEFAULT NULL,
    `unidad` varchar(20) DEFAULT NULL,
    `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Columnas restriccion_pc en consolidado
SET @tbl_exists_pcc = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa_consolidado'
    AND COLUMN_NAME = 'restriccion_pc_1');
SET @sql_pcc = IF(@tbl_exists_pcc = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa_consolidado` ADD COLUMN `restriccion_pc_1` VARCHAR(10) DEFAULT ''0%'' AFTER `Modelo`',
    'SELECT "restriccion_pc_1 already exists on consolidado" AS info');
PREPARE stmt FROM @sql_pcc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pcc2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa_consolidado'
    AND COLUMN_NAME = 'restriccion_pc_2');
SET @sql_pcc2 = IF(@col_exists_pcc2 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa_consolidado` ADD COLUMN `restriccion_pc_2` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_1`',
    'SELECT "restriccion_pc_2 already exists on consolidado" AS info');
PREPARE stmt FROM @sql_pcc2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pcc3 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa_consolidado'
    AND COLUMN_NAME = 'restriccion_pc_3');
SET @sql_pcc3 = IF(@col_exists_pcc3 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa_consolidado` ADD COLUMN `restriccion_pc_3` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_2`',
    'SELECT "restriccion_pc_3 already exists on consolidado" AS info');
PREPARE stmt FROM @sql_pcc3;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_pcc4 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'da_aeropuerto_pc_programa_consolidado'
    AND COLUMN_NAME = 'restriccion_pc_4');
SET @sql_pcc4 = IF(@col_exists_pcc4 = 0,
    'ALTER TABLE `da_aeropuerto_pc_programa_consolidado` ADD COLUMN `restriccion_pc_4` VARCHAR(10) DEFAULT ''0%'' AFTER `restriccion_pc_3`',
    'SELECT "restriccion_pc_4 already exists on consolidado" AS info');
PREPARE stmt FROM @sql_pcc4;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 2.3 da_aeropuerto_pc_semanas_activas
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_semanas_activas` (
    `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Semana` int(11) NOT NULL,
    `Fecha_Inicio_Sem` date NOT NULL,
    `Fecha_Fin_Sem` date NOT NULL,
    `Semanal_Confirmada` int(1) DEFAULT 0,
    `fechaCierreCompromisos` date DEFAULT NULL,
    `fechaCreacionSemana` date DEFAULT NULL,
    `reprogramacion` int(11) NOT NULL DEFAULT 0,
    `diferenciaEstructuraCron` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.4 da_aeropuerto_pc_profesionales
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_profesionales` (
    `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nombre` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `cargo` varchar(100) NOT NULL,
    `activo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.5 da_aeropuerto_pc_subcontratistas
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_subcontratistas` (
    `Id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `subcontratista` varchar(200) NOT NULL,
    `correo_contacto` varchar(200) NOT NULL,
    `NIT` bigint(10) NOT NULL,
    `alcance` varchar(200) NOT NULL,
    `tipo_proveedor` varchar(200) NOT NULL,
    `activo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.6 da_aeropuerto_pc_cnc
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_cnc` (
    `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Semana` int(3) DEFAULT NULL,
    `subcontratista` varchar(200) DEFAULT NULL,
    `categoria` varchar(200) DEFAULT NULL,
    `causa` varchar(500) DEFAULT NULL,
    `observaciones` mediumtext DEFAULT NULL,
    `responsable` varchar(200) DEFAULT NULL,
    `fecha_registro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.7 da_aeropuerto_pc_cnp
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_cnp` (
    `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Semana` int(3) DEFAULT NULL,
    `subcontratista` varchar(200) DEFAULT NULL,
    `categoria` varchar(200) DEFAULT NULL,
    `causa` varchar(500) DEFAULT NULL,
    `observaciones` mediumtext DEFAULT NULL,
    `responsable` varchar(200) DEFAULT NULL,
    `fecha_registro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- =====================================================================
-- PARTE 3: Datos semilla - Actividades del programa
-- =====================================================================
-- 20 actividades de pre-construccion para un aeropuerto regional.
-- Fechas: julio 2026 - diciembre 2026 (6 meses).
-- Predecesora: referencia al Consecutivo de la actividad antecesora.
-- restriccion_pc_1 = 'Avances Fisicos/Financieros'
-- restriccion_pc_2 = 'Permisos Ambientales'
-- restriccion_pc_3 = 'Disenos'
-- restriccion_pc_4 = 'Apropiacion Presupuestal'

INSERT IGNORE INTO `da_aeropuerto_pc_programa` (
    `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`,
    `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`,
    `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`,
    `Predecesora`, `Pdto_Cons`, `Modelo`, `restriccion_pc_1`,
    `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`,
    `Responsable_AIA`, `Observaciones`
) VALUES
-- 1. Estudios Topograficos
('1', 'Estudios Topograficos', 1, '2026-07-01', '2026-07-14',
 1, 0, 'En Ejecucion', 0,
 100, 100, 100, 100, 100,
 0, 100, '0', '100%',
 '100%', '100%', '100%',
 'Ing. Carlos Mendez', 'Levantamiento topografico completo del predio'),

-- 2. Estudios Geotecnicos
('2', 'Estudios Geotecnicos', 1, '2026-07-08', '2026-07-28',
 1, 0, 'En Ejecucion', 0,
 100, 100, 100, 100, 100,
 1, 100, '0', '100%',
 '100%', '100%', '100%',
 'Ing. Carlos Mendez', 'Sondajes y ensayos de suelos'),

-- 3. Gestion Permisos Ambientales (TAR, Licencia Ambiental)
('3', 'Gestion Permisos Ambientales', 1, '2026-07-01', '2026-09-30',
 1, 0, 'En Ejecucion', 0,
 0, 0, 0, 0, 0,
 0, 0, '0', '100%',
 '0%', '100%', '100%',
 'Dra. Maria Lopez', 'Tramite ante Corpoboyaca - ETA y Licencia'),

-- 4. Diseno Arquitectonico
('4', 'Diseno Arquitectonico', 1, '2026-07-15', '2026-09-15',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 0, 0, '0', '100%',
 '100%', '0%', '100%',
 'Arq. Andres Garcia', 'Planos arquitectonicos terminal aerea'),

-- 5. Diseno Estructural
('5', 'Diseno Estructural', 1, '2026-08-01', '2026-10-01',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '100%',
 'Ing. Roberto Diaz', 'Calculo estructural y planos'),

-- 6. Diseno Mecanico-Electrico
('6', 'Diseno Mecanico-Electrico', 1, '2026-08-15', '2026-10-15',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '100%',
 'Ing. Luis Ramirez', 'Instalaciones M&E'),

-- 7. Diseno Sanitario
('7', 'Diseno Sanitario', 0, '2026-08-15', '2026-10-15',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '100%',
 'Ing. Luis Ramirez', 'Acueducto, alcantarillado, agua caliente'),

-- 8. Diseno Vial y Accesos
('8', 'Diseno Vial y Accesos', 0, '2026-08-01', '2026-09-30',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 1, 0, '0', '100%',
 '100%', '0%', '100%',
 'Ing. Carlos Mendez', 'Intersecciones y accesos al aeropuerto'),

-- 9. Estudio de Impacto Vial
('9', 'Estudio de Impacto Vial', 0, '2026-08-01', '2026-09-15',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 8, 0, '0', '100%',
 '0%', '0%', '100%',
 'Ing. Carlos Mendez', 'Para concepto DAP'),

-- 10. Gestion Permisos Municipales
('10', 'Gestion Permisos Municipales', 0, '2026-09-01', '2026-11-30',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '0%',
 'Lda. Patricia Rojas', 'Licencia de construccion y conceptos'),

-- 11. Gestion Permisos Aeroportuarios (Aerocivil)
('11', 'Gestion Permisos Aeroportuarios', 0, '2026-08-01', '2026-11-30',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 0, 0, '0', '100%',
 '0%', '100%', '0%',
 'Lda. Patricia Rojas', 'Concepto favorable Aerocivil'),

-- 12. Presupuesto Detallado
('12', 'Presupuesto Detallado', 0, '2026-09-15', '2026-10-31',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '0%',
 'Ing. Sandra Morales', 'Presupuesto por capítulos'),

-- 13. Plan de Contratacion
('13', 'Plan de Contratacion', 0, '2026-10-01', '2026-10-31',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 12, 0, '0', '100%',
 '100%', '0%', '0%',
 'Ing. Sandra Morales', 'Estrategia de contratacion por paquetes'),

-- 14. Estudios Ambientales Complementarios
('14', 'Estudios Ambientales Complementarios', 0, '2026-07-15', '2026-09-15',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 0, 0, '0', '100%',
 '0%', '100%', '100%',
 'Dra. Maria Lopez', 'Bioacustica, calidad de aire'),

-- 15. Mobilizacion del Equipo de Obra
('15', 'Mobilizacion del Equipo de Obra', 0, '2026-11-01', '2026-11-30',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 10, 0, '0', '100%',
 '100%', '100%', '0%',
 'Residente Juan Perez', 'Oficina provisional y vallado'),

-- 16. Preparacion del Terreno
('16', 'Preparacion del Terreno', 0, '2026-12-01', '2026-12-31',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 15, 0, '0', '100%',
 '100%', '100%', '0%',
 'Residente Juan Perez', 'Descapote y nivelacion general'),

-- 17. Movimiento de Tierras
('17', 'Movimiento de Tierras', 0, '2026-12-01', '2026-12-31',
 1, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 15, 0, '0', '100%',
 '100%', '100%', '0%',
 'Residente Juan Perez', 'Corte y relleno topografico'),

-- 18. Diseno Paisajistico
('18', 'Diseno Paisajistico', 0, '2026-09-01', '2026-10-31',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '100%',
 'Arq. Andres Garcia', 'Zonas verdes y paisajismo exterior'),

-- 19. Diseno Interior Terminal
('19', 'Diseno Interior Terminal', 0, '2026-09-01', '2026-10-31',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 4, 0, '0', '100%',
 '100%', '0%', '100%',
 'Arq. Andres Garcia', 'Interior terminal de pasajeros'),

-- 20. Apropiacion Presupuestal Final
('20', 'Apropiacion Presupuestal Final', 0, '2026-11-01', '2026-12-15',
 0, 0, 'No Iniciado', 0,
 0, 0, 0, 0, 0,
 12, 0, '0', '100%',
 '100%', '100%', '0%',
 'Ing. Sandra Morales', 'Cierre de apropiacion y pase a obra');

-- =====================================================================
-- PARTE 4: Datos semilla - Programa Consolidado (Semana 1)
-- =====================================================================
-- Replica las actividades al consolidado para la primera semana.

INSERT IGNORE INTO `da_aeropuerto_pc_programa_consolidado` (
    `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`,
    `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`,
    `Estado`, `Semanas_Inicio`, `Estado_Restricciones`,
    `D_y_E`, `Materiales`, `MdeO`, `Equipos`,
    `Predecesora`, `Pdto_Cons`, `Modelo`,
    `Sub_Contratista`, `Responsable_AIA`, `Observaciones`,
    `Activa`, `codigo_actividad`
) VALUES
(1, 1, '1', 'Estudios Topograficos', 1,
 '2026-07-01', '2026-07-14', 1, 0,
 'En Ejecucion', 0, 100,
 '100%', '100%', '100%', '100%',
 '0', 100, '0',
 NULL, 'Ing. Carlos Mendez', 'Actividad en curso semana 1',
 1, 'PC-001'),

(1, 2, '2', 'Estudios Geotecnicos', 1,
 '2026-07-08', '2026-07-28', 1, 0,
 'En Ejecucion', 0, 100,
 '100%', '100%', '100%', '100%',
 '1', 100, '0',
 NULL, 'Ing. Carlos Mendez', 'Actividad en curso semana 1',
 1, 'PC-002'),

(1, 3, '3', 'Gestion Permisos Ambientales', 1,
 '2026-07-01', '2026-09-30', 1, 0,
 'En Ejecucion', 0, 0,
 '0%', '0%', '0%', '0%',
 '0', 0, '0',
 NULL, 'Dra. Maria Lopez', 'Tramite en curso',
 1, 'PC-003'),

(1, 4, '4', 'Diseno Arquitectonico', 1,
 '2026-07-15', '2026-09-15', 1, 0,
 'No Iniciado', 0, 0,
 '0%', '0%', '0%', '0%',
 '0', 0, '0',
 NULL, 'Arq. Andres Garcia', 'Esperando estudios topograficos',
 0, 'PC-004'),

(1, 5, '5', 'Diseno Estructural', 1,
 '2026-08-01', '2026-10-01', 1, 0,
 'No Iniciado', 0, 0,
 '0%', '0%', '0%', '0%',
 '4', 0, '0',
 NULL, 'Ing. Roberto Diaz', 'Depende de diseno arquitectonico',
 0, 'PC-005'),

(1, 14, '14', 'Estudios Ambientales Complementarios', 0,
 '2026-07-15', '2026-09-15', 0, 0,
 'No Iniciado', 0, 0,
 '0%', '0%', '0%', '0%',
 '0', 0, '0',
 NULL, 'Dra. Maria Lopez', 'Bioacustica y calidad de aire',
 0, 'PC-014'),

(1, 15, '15', 'Mobilizacion del Equipo de Obra', 0,
 '2026-11-01', '2026-11-30', 0, 0,
 'No Iniciado', 0, 0,
 '0%', '0%', '0%', '0%',
 '10', 0, '0',
 NULL, 'Residente Juan Perez', 'Depende de permisos municipales',
 0, 'PC-015'),

(1, 17, '17', 'Movimiento de Tierras', 0,
 '2026-12-01', '2026-12-31', 1, 0,
 'No Iniciado', 0, 0,
 '0%', '0%', '0%', '0%',
 '15', 0, '0',
 NULL, 'Residente Juan Perez', 'Critico - ruta de construccion',
 0, 'PC-017');

-- =====================================================================
-- PARTE 5: Datos semilla - Semanas Activas
-- =====================================================================
-- 3 semanas de ejemplo (julio 2026)

INSERT IGNORE INTO `da_aeropuerto_pc_semanas_activas` (
    `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`,
    `Semanal_Confirmada`, `fechaCierreCompromisos`,
    `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`
) VALUES
(1, '2026-07-01', '2026-07-07', 1, '2026-07-04', '2026-06-30', 0, 0),
(2, '2026-07-08', '2026-07-14', 1, '2026-07-11', '2026-07-07', 0, 0),
(3, '2026-07-15', '2026-07-21', 0, NULL, '2026-07-14', 0, 0);

-- =====================================================================
-- PARTE 6: Datos semilla - Profesionales
-- =====================================================================

INSERT IGNORE INTO `da_aeropuerto_pc_profesionales` (
    `nombre`, `email`, `cargo`, `activo`
) VALUES
('Ing. Carlos Mendez', 'cmendez@aia.com.co', 'Director de Proyecto', 1),
('Dra. Maria Lopez', 'mlopez@aia.com.co', 'Profesional Ambiental', 1),
('Arq. Andres Garcia', 'agarcia@aia.com.co', 'Arquitecto', 1),
('Ing. Roberto Diaz', 'rdiaz@aia.com.co', 'Ingeniero Estructural', 1),
('Ing. Luis Ramirez', 'lramirez@aia.com.co', 'Ingeniero Mecanico-Electrico', 1),
('Ing. Sandra Morales', 'smorales@aia.com.co', 'Control de Costos', 1),
('Lda. Patricia Rojas', 'projas@aia.com.co', 'Abogada Ambiental', 1),
('Residente Juan Perez', 'jperez@aia.com.co', 'Residente de Obra', 1);

-- =====================================================================
-- PARTE 7: Datos semilla - Subcontratistas (Interesados Externos)
-- =====================================================================

INSERT IGNORE INTO `da_aeropuerto_pc_subcontratistas` (
    `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`
) VALUES
('Geotecnica del Norte SAS', 'contacto@geotecnanorte.com', 890123456, 'Estudios geotecnicos y topograficos', 'Consultoria', 1),
('Ambiental Total LTDA', 'info@ambientaltotal.com', 890234567, 'Estudios y gestiones ambientales', 'Consultoria', 1),
('Construcciones Aeropuertarias SA', 'proyectos@construaero.com', 890345678, 'Obra civil y movimientos de tierra', 'Contratista', 1);

-- =====================================================================
-- PARTE 8: Datos semilla - CNC (Causas de No Cumplimiento)
-- =====================================================================
-- Ejemplo de un registro CNC para la semana 1

INSERT IGNORE INTO `da_aeropuerto_pc_cnc` (
    `Semana`, `subcontratista`, `categoria`, `causa`, `observaciones`, `responsable`, `fecha_registro`
) VALUES
(1, 'Geotecnica del Norte SAS', 'Disenos', 'Falta de Definicion Arquitectonica o de Ingenieria (AIA)',
 'Se requiere planos topograficos base para iniciar disenos',
 'Ing. Carlos Mendez', '2026-07-05');

-- =====================================================================
-- PARTE 9: Datos semilla - CNP (Causas de No Programacion)
-- =====================================================================
-- Ejemplo de un registro CNP para la semana 1

INSERT IGNORE INTO `da_aeropuerto_pc_cnp` (
    `Semana`, `subcontratista`, `categoria`, `causa`, `observaciones`, `responsable`, `fecha_registro`
) VALUES
(1, NULL, 'Permisos', 'Tramite de permisos pendiente',
 'Permiso ambiental en proceso - ETA en revision tecnica',
 'Dra. Maria Lopez', '2026-07-03');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- VERIFICACION POST-EJECUCION
-- =====================================================================
-- Ejecutar estas consultas para validar:

-- 1. Proyecto registrado correctamente:
-- SELECT Id, Proyecto_Proceso, Base_de_Datos, Area,
--        pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre
-- FROM general_proyectos_procesos
-- WHERE Area = 'Pre-Construccion';

-- 2. Tablas creadas:
-- SHOW TABLES LIKE 'da_aeropuerto_pc_%';

-- 3. Actividades insertadas:
-- SELECT COUNT(*) AS total_actividades FROM da_aeropuerto_pc_programa;

-- 4. Columnas restriccion_pc:
-- DESCRIBE da_aeropuerto_pc_programa;

-- =====================================================================
-- FIN Seed Script - Proyecto Pre-Construccion Aeropuerto Regional
-- =====================================================================
