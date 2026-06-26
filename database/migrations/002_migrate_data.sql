-- ==========================================================================
-- Migration 002: Migrate ALL data from 144 per-project tables to 16 global tables
-- Strategy: 9 projects x 16 table types = 144 INSERT...SELECT blocks
-- Uses INSERT IGNORE for idempotency (safe to re-run)
-- WITH pre/post row count verification after every block
-- ==========================================================================

START TRANSACTION;

-- ============================================================
-- TABLE: programa
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 27, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM prueba_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=27);
SELECT 'programa', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 68, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM optimizacionJMC_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=68);
SELECT 'programa', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 69, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaConfinamientoDos_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=69);
SELECT 'programa', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 70, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaDieciseisDescendente_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=70);
SELECT 'programa', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 71, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaDieciseisAscendente_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=71);
SELECT 'programa', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 72, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaMampDos_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=72);
SELECT 'programa', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 73, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM da_porto_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=73);
SELECT 'programa', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 74, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM milan_campestre_torre_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=74);
SELECT 'programa', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_programa);
INSERT IGNORE INTO programa (project_id, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 75, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM da_aeropuerto_pc_programa;
SET @row_count_post = (SELECT COUNT(*) FROM programa WHERE project_id=75);
SELECT 'programa', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: actividades
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 27, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM prueba_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=27);
SELECT 'actividades', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 68, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM optimizacionJMC_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=68);
SELECT 'actividades', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 69, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM metrolineaConfinamientoDos_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=69);
SELECT 'actividades', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 70, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM metrolineaDieciseisDescendente_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=70);
SELECT 'actividades', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 71, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM metrolineaDieciseisAscendente_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=71);
SELECT 'actividades', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 72, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM metrolineaMampDos_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=72);
SELECT 'actividades', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 73, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM da_porto_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=73);
SELECT 'actividades', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 74, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM milan_campestre_torre_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=74);
SELECT 'actividades', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_actividades);
INSERT IGNORE INTO actividades (project_id, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada`)
SELECT 75, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`, `OC1`, `paqueteOC1`, `OC2`, `paqueteOC2`, `OC3`, `paqueteOC3`, `OC4`, `paqueteOC4`, `OC5`, `paqueteOC5`, `numeroSubcontratos`, `confianza_deteccion`, `ultimo_auto_definir`, `fechaInicioProyectada` FROM da_aeropuerto_pc_actividades;
SET @row_count_post = (SELECT COUNT(*) FROM actividades WHERE project_id=75);
SELECT 'actividades', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: cambios
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 27, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM prueba_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=27);
SELECT 'cambios', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 68, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM optimizacionJMC_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=68);
SELECT 'cambios', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 69, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM metrolineaConfinamientoDos_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=69);
SELECT 'cambios', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 70, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM metrolineaDieciseisDescendente_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=70);
SELECT 'cambios', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 71, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM metrolineaDieciseisAscendente_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=71);
SELECT 'cambios', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 72, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM metrolineaMampDos_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=72);
SELECT 'cambios', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 73, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM da_porto_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=73);
SELECT 'cambios', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 74, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM milan_campestre_torre_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=74);
SELECT 'cambios', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_cambios);
INSERT IGNORE INTO cambios (project_id, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`)
SELECT 75, `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes` FROM da_aeropuerto_pc_cambios;
SET @row_count_post = (SELECT COUNT(*) FROM cambios WHERE project_id=75);
SELECT 'cambios', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: cic
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 27, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM prueba_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=27);
SELECT 'cic', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 68, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM optimizacionJMC_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=68);
SELECT 'cic', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 69, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM metrolineaConfinamientoDos_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=69);
SELECT 'cic', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 70, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM metrolineaDieciseisDescendente_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=70);
SELECT 'cic', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 71, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM metrolineaDieciseisAscendente_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=71);
SELECT 'cic', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 72, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM metrolineaMampDos_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=72);
SELECT 'cic', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 73, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM da_porto_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=73);
SELECT 'cic', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 74, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM milan_campestre_torre_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=74);
SELECT 'cic', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_cic);
INSERT IGNORE INTO cic (project_id, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)
SELECT 75, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM da_aeropuerto_pc_cic;
SET @row_count_post = (SELECT COUNT(*) FROM cic WHERE project_id=75);
SELECT 'cic', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: pdc
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 27, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM prueba_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=27);
SELECT 'pdc', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 68, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM optimizacionJMC_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=68);
SELECT 'pdc', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 69, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM metrolineaConfinamientoDos_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=69);
SELECT 'pdc', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 70, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM metrolineaDieciseisDescendente_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=70);
SELECT 'pdc', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 71, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM metrolineaDieciseisAscendente_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=71);
SELECT 'pdc', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 72, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM metrolineaMampDos_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=72);
SELECT 'pdc', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 73, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM da_porto_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=73);
SELECT 'pdc', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 74, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM milan_campestre_torre_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=74);
SELECT 'pdc', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_pdc);
INSERT IGNORE INTO pdc (project_id, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)
SELECT 75, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM da_aeropuerto_pc_pdc;
SET @row_count_post = (SELECT COUNT(*) FROM pdc WHERE project_id=75);
SELECT 'pdc', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: profesionales
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 27, `id`, `nombre`, `email`, `cargo`, `activo` FROM prueba_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=27);
SELECT 'profesionales', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 68, `id`, `nombre`, `email`, `cargo`, `activo` FROM optimizacionJMC_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=68);
SELECT 'profesionales', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 69, `id`, `nombre`, `email`, `cargo`, `activo` FROM metrolineaConfinamientoDos_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=69);
SELECT 'profesionales', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 70, `id`, `nombre`, `email`, `cargo`, `activo` FROM metrolineaDieciseisDescendente_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=70);
SELECT 'profesionales', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 71, `id`, `nombre`, `email`, `cargo`, `activo` FROM metrolineaDieciseisAscendente_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=71);
SELECT 'profesionales', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 72, `id`, `nombre`, `email`, `cargo`, `activo` FROM metrolineaMampDos_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=72);
SELECT 'profesionales', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 73, `id`, `nombre`, `email`, `cargo`, `activo` FROM da_porto_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=73);
SELECT 'profesionales', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 74, `id`, `nombre`, `email`, `cargo`, `activo` FROM milan_campestre_torre_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=74);
SELECT 'profesionales', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_profesionales);
INSERT IGNORE INTO profesionales (project_id, `id`, `nombre`, `email`, `cargo`, `activo`)
SELECT 75, `id`, `nombre`, `email`, `cargo`, `activo` FROM da_aeropuerto_pc_profesionales;
SET @row_count_post = (SELECT COUNT(*) FROM profesionales WHERE project_id=75);
SELECT 'profesionales', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: programacion_semanal
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 27, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM prueba_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=27);
SELECT 'programacion_semanal', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 68, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM optimizacionJMC_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=68);
SELECT 'programacion_semanal', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 69, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM metrolineaConfinamientoDos_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=69);
SELECT 'programacion_semanal', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 70, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM metrolineaDieciseisDescendente_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=70);
SELECT 'programacion_semanal', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 71, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM metrolineaDieciseisAscendente_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=71);
SELECT 'programacion_semanal', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 72, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM metrolineaMampDos_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=72);
SELECT 'programacion_semanal', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 73, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM da_porto_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=73);
SELECT 'programacion_semanal', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 74, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM milan_campestre_torre_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=74);
SELECT 'programacion_semanal', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_programacion_semanal);
INSERT IGNORE INTO programacion_semanal (project_id, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales`)
SELECT 75, `Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Categoria_CP`, `CP`, `Observaciones_CP`, `Reprogramada_Por_Usuario`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`, `alerta_crisis`, `reprogramaciones_semanales` FROM da_aeropuerto_pc_programacion_semanal;
SET @row_count_post = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=75);
SELECT 'programacion_semanal', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: programa_consolidado
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 27, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM prueba_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=27);
SELECT 'programa_consolidado', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 68, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM optimizacionJMC_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=68);
SELECT 'programa_consolidado', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 69, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaConfinamientoDos_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=69);
SELECT 'programa_consolidado', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 70, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaDieciseisDescendente_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=70);
SELECT 'programa_consolidado', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 71, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaDieciseisAscendente_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=71);
SELECT 'programa_consolidado', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 72, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM metrolineaMampDos_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=72);
SELECT 'programa_consolidado', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 73, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM da_porto_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=73);
SELECT 'programa_consolidado', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 74, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM milan_campestre_torre_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=74);
SELECT 'programa_consolidado', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_programa_consolidado);
INSERT IGNORE INTO programa_consolidado (project_id, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
SELECT 75, `Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`, `alerta_crisis`, `reprogramaciones_acumuladas`, `dias_reprogramacion_acumulada`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4` FROM da_aeropuerto_pc_programa_consolidado;
SET @row_count_post = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=75);
SELECT 'programa_consolidado', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: semanas_activas
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 27, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM prueba_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=27);
SELECT 'semanas_activas', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 68, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM optimizacionJMC_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=68);
SELECT 'semanas_activas', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 69, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM metrolineaConfinamientoDos_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=69);
SELECT 'semanas_activas', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 70, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM metrolineaDieciseisDescendente_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=70);
SELECT 'semanas_activas', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 71, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM metrolineaDieciseisAscendente_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=71);
SELECT 'semanas_activas', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 72, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM metrolineaMampDos_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=72);
SELECT 'semanas_activas', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 73, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM da_porto_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=73);
SELECT 'semanas_activas', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 74, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM milan_campestre_torre_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=74);
SELECT 'semanas_activas', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_semanas_activas);
INSERT IGNORE INTO semanas_activas (project_id, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`)
SELECT 75, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCierreCompromisos`, `fecha_ultimo_saneo`, `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron` FROM da_aeropuerto_pc_semanas_activas;
SET @row_count_post = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=75);
SELECT 'semanas_activas', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: subcontratistas
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 27, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM prueba_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=27);
SELECT 'subcontratistas', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 68, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM optimizacionJMC_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=68);
SELECT 'subcontratistas', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 69, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM metrolineaConfinamientoDos_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=69);
SELECT 'subcontratistas', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 70, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM metrolineaDieciseisDescendente_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=70);
SELECT 'subcontratistas', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 71, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM metrolineaDieciseisAscendente_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=71);
SELECT 'subcontratistas', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 72, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM metrolineaMampDos_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=72);
SELECT 'subcontratistas', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 73, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM da_porto_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=73);
SELECT 'subcontratistas', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 74, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM milan_campestre_torre_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=74);
SELECT 'subcontratistas', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_subcontratistas);
INSERT IGNORE INTO subcontratistas (project_id, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
SELECT 75, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo` FROM da_aeropuerto_pc_subcontratistas;
SET @row_count_post = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=75);
SELECT 'subcontratistas', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: auto_program_log
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 27, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM prueba_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=27);
SELECT 'auto_program_log', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 68, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM optimizacionJMC_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=68);
SELECT 'auto_program_log', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 69, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM metrolineaConfinamientoDos_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=69);
SELECT 'auto_program_log', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 70, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM metrolineaDieciseisDescendente_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=70);
SELECT 'auto_program_log', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 71, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM metrolineaDieciseisAscendente_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=71);
SELECT 'auto_program_log', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 72, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM metrolineaMampDos_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=72);
SELECT 'auto_program_log', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 73, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM da_porto_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=73);
SELECT 'auto_program_log', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 74, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM milan_campestre_torre_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=74);
SELECT 'auto_program_log', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_auto_program_log);
INSERT IGNORE INTO auto_program_log (project_id, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en`)
SELECT 75, `id`, `semana`, `consecutivo`, `accion`, `detalle`, `categoria_cnp`, `cnp`, `creado_en` FROM da_aeropuerto_pc_auto_program_log;
SET @row_count_post = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=75);
SELECT 'auto_program_log', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: lps_drawer_comentarios
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 27, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM prueba_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=27);
SELECT 'lps_drawer_comentarios', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 68, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM optimizacionJMC_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=68);
SELECT 'lps_drawer_comentarios', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 69, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM metrolineaConfinamientoDos_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=69);
SELECT 'lps_drawer_comentarios', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 70, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM metrolineaDieciseisDescendente_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=70);
SELECT 'lps_drawer_comentarios', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 71, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM metrolineaDieciseisAscendente_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=71);
SELECT 'lps_drawer_comentarios', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 72, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM metrolineaMampDos_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=72);
SELECT 'lps_drawer_comentarios', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 73, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM da_porto_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=73);
SELECT 'lps_drawer_comentarios', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 74, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM milan_campestre_torre_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=74);
SELECT 'lps_drawer_comentarios', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_lps_drawer_comentarios);
INSERT IGNORE INTO lps_drawer_comentarios (project_id, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`)
SELECT 75, `id`, `proyecto_id`, `consecutivo_en_programa`, `semana`, `usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at` FROM da_aeropuerto_pc_lps_drawer_comentarios;
SET @row_count_post = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=75);
SELECT 'lps_drawer_comentarios', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: lps_escalamientos
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 27, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM prueba_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=27);
SELECT 'lps_escalamientos', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 68, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM optimizacionJMC_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=68);
SELECT 'lps_escalamientos', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 69, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM metrolineaConfinamientoDos_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=69);
SELECT 'lps_escalamientos', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 70, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM metrolineaDieciseisDescendente_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=70);
SELECT 'lps_escalamientos', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 71, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM metrolineaDieciseisAscendente_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=71);
SELECT 'lps_escalamientos', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 72, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM metrolineaMampDos_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=72);
SELECT 'lps_escalamientos', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 73, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM da_porto_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=73);
SELECT 'lps_escalamientos', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 74, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM milan_campestre_torre_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=74);
SELECT 'lps_escalamientos', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_lps_escalamientos);
INSERT IGNORE INTO lps_escalamientos (project_id, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre`)
SELECT 75, `id`, `proyecto_id`, `semana`, `consecutivo_en_programa`, `modulo`, `trigger_origen`, `nivel_actual`, `estado`, `fecha_detonacion`, `fecha_ultimo_escalamiento`, `fecha_cierre`, `usuario_cierre_id`, `justificacion_cierre` FROM da_aeropuerto_pc_lps_escalamientos;
SET @row_count_post = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=75);
SELECT 'lps_escalamientos', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: pg_tracking
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 27, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM prueba_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=27);
SELECT 'pg_tracking', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 68, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM optimizacionJMC_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=68);
SELECT 'pg_tracking', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 69, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM metrolineaConfinamientoDos_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=69);
SELECT 'pg_tracking', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 70, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM metrolineaDieciseisDescendente_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=70);
SELECT 'pg_tracking', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 71, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM metrolineaDieciseisAscendente_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=71);
SELECT 'pg_tracking', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 72, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM metrolineaMampDos_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=72);
SELECT 'pg_tracking', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 73, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM da_porto_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=73);
SELECT 'pg_tracking', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 74, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM milan_campestre_torre_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=74);
SELECT 'pg_tracking', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_pg_tracking);
INSERT IGNORE INTO pg_tracking (project_id, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado`)
SELECT 75, `consecutivo_en_programa`, `semana`, `fecha_inicio`, `fecha_fin`, `estado`, `restricciones_hash`, `fechas_hash`, `estado_hash`, `titulo`, `ultimo_detectado` FROM da_aeropuerto_pc_pg_tracking;
SET @row_count_post = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=75);
SELECT 'pg_tracking', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: pi_shared_constraints
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 27, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM prueba_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=27);
SELECT 'pi_shared_constraints', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 68, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM optimizacionJMC_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=68);
SELECT 'pi_shared_constraints', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 69, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM metrolineaConfinamientoDos_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=69);
SELECT 'pi_shared_constraints', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 70, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM metrolineaDieciseisDescendente_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=70);
SELECT 'pi_shared_constraints', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 71, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM metrolineaDieciseisAscendente_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=71);
SELECT 'pi_shared_constraints', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 72, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM metrolineaMampDos_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=72);
SELECT 'pi_shared_constraints', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 73, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM da_porto_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=73);
SELECT 'pi_shared_constraints', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 74, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM milan_campestre_torre_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=74);
SELECT 'pi_shared_constraints', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_pi_shared_constraints);
INSERT IGNORE INTO pi_shared_constraints (project_id, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
SELECT 75, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn` FROM da_aeropuerto_pc_pi_shared_constraints;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=75);
SELECT 'pi_shared_constraints', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- TABLE: pi_shared_constraint_links
-- ============================================================

-- Proyecto: Prueba (Id=27) -> prefix=prueba
SET @row_count_pre = (SELECT COUNT(*) FROM prueba_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 27, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM prueba_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=27);
SELECT 'pi_shared_constraint_links', 'prueba', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Optimización Aeropuerto JMC (Id=68) -> prefix=optimizacionJMC
SET @row_count_pre = (SELECT COUNT(*) FROM optimizacionJMC_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 68, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM optimizacionJMC_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=68);
SELECT 'pi_shared_constraint_links', 'optimizacionJMC', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Confinamiento Estación 2 (Id=69) -> prefix=metrolineaConfinamientoDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaConfinamientoDos_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 69, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM metrolineaConfinamientoDos_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=69);
SELECT 'pi_shared_constraint_links', 'metrolineaConfinamientoDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Descendente (Id=70) -> prefix=metrolineaDieciseisDescendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 70, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM metrolineaDieciseisDescendente_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=70);
SELECT 'pi_shared_constraint_links', 'metrolineaDieciseisDescendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Estación 16 - Edificio Ascendente (Id=71) -> prefix=metrolineaDieciseisAscendente
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 71, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM metrolineaDieciseisAscendente_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=71);
SELECT 'pi_shared_constraint_links', 'metrolineaDieciseisAscendente', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Metrolinea Mampostería Estación 2 (Id=72) -> prefix=metrolineaMampDos
SET @row_count_pre = (SELECT COUNT(*) FROM metrolineaMampDos_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 72, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM metrolineaMampDos_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=72);
SELECT 'pi_shared_constraint_links', 'metrolineaMampDos', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Da Porto (Id=73) -> prefix=da_porto
SET @row_count_pre = (SELECT COUNT(*) FROM da_porto_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 73, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM da_porto_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=73);
SELECT 'pi_shared_constraint_links', 'da_porto', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Milán Campestre Torre 19 (Id=74) -> prefix=milan_campestre_torre
SET @row_count_pre = (SELECT COUNT(*) FROM milan_campestre_torre_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 74, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM milan_campestre_torre_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=74);
SELECT 'pi_shared_constraint_links', 'milan_campestre_torre', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;

-- Proyecto: Aeropuerto Regional PC (Pre-Construccion) (Id=75) -> prefix=da_aeropuerto_pc
SET @row_count_pre = (SELECT COUNT(*) FROM da_aeropuerto_pc_pi_shared_constraint_links);
INSERT IGNORE INTO pi_shared_constraint_links (project_id, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
SELECT 75, `Id`, `SharedConstraintId`, `Semana`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn` FROM da_aeropuerto_pc_pi_shared_constraint_links;
SET @row_count_post = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=75);
SELECT 'pi_shared_constraint_links', 'da_aeropuerto_pc', @row_count_pre AS pre, @row_count_post AS post, IF(@row_count_pre = @row_count_post, 'OK', 'MISMATCH') AS status;


-- ============================================================
-- FINAL SUMMARY
-- ============================================================

SELECT '--- MIGRATION SUMMARY ---' AS "";

SELECT 'Table' AS table_name, 'Project' AS prefix,
       'Rows_Migrated' AS metric,
       SUM(CASE WHEN status = 'OK' THEN 1 ELSE 0 END) AS ok_count,
       SUM(CASE WHEN status = 'MISMATCH' THEN 1 ELSE 0 END) AS mismatch_count
FROM (
  SELECT 'dummy' AS table_name, 'dummy' AS prefix, 'dummy' AS status
  UNION ALL
  SELECT 'programa', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_programa) = (SELECT COUNT(*) FROM programa WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'actividades', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_actividades) = (SELECT COUNT(*) FROM actividades WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cambios', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_cambios) = (SELECT COUNT(*) FROM cambios WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'cic', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_cic) = (SELECT COUNT(*) FROM cic WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pdc', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_pdc) = (SELECT COUNT(*) FROM pdc WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'profesionales', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_profesionales) = (SELECT COUNT(*) FROM profesionales WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programacion_semanal', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_programacion_semanal) = (SELECT COUNT(*) FROM programacion_semanal WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'programa_consolidado', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_programa_consolidado) = (SELECT COUNT(*) FROM programa_consolidado WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'semanas_activas', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_semanas_activas) = (SELECT COUNT(*) FROM semanas_activas WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'subcontratistas', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_subcontratistas) = (SELECT COUNT(*) FROM subcontratistas WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'auto_program_log', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_auto_program_log) = (SELECT COUNT(*) FROM auto_program_log WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_drawer_comentarios', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_lps_drawer_comentarios) = (SELECT COUNT(*) FROM lps_drawer_comentarios WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'lps_escalamientos', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_lps_escalamientos) = (SELECT COUNT(*) FROM lps_escalamientos WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pg_tracking', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_pg_tracking) = (SELECT COUNT(*) FROM pg_tracking WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraints', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_pi_shared_constraints) = (SELECT COUNT(*) FROM pi_shared_constraints WHERE project_id=75), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'prueba',
         IF((SELECT COUNT(*) FROM prueba_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=27), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'optimizacionJMC',
         IF((SELECT COUNT(*) FROM optimizacionJMC_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=68), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'metrolineaConfinamientoDos',
         IF((SELECT COUNT(*) FROM metrolineaConfinamientoDos_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=69), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'metrolineaDieciseisDescendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisDescendente_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=70), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'metrolineaDieciseisAscendente',
         IF((SELECT COUNT(*) FROM metrolineaDieciseisAscendente_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=71), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'metrolineaMampDos',
         IF((SELECT COUNT(*) FROM metrolineaMampDos_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=72), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'da_porto',
         IF((SELECT COUNT(*) FROM da_porto_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=73), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'milan_campestre_torre',
         IF((SELECT COUNT(*) FROM milan_campestre_torre_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=74), 'OK', 'MISMATCH') AS status
  UNION ALL
  SELECT 'pi_shared_constraint_links', 'da_aeropuerto_pc',
         IF((SELECT COUNT(*) FROM da_aeropuerto_pc_pi_shared_constraint_links) = (SELECT COUNT(*) FROM pi_shared_constraint_links WHERE project_id=75), 'OK', 'MISMATCH') AS status
) AS sub
GROUP BY 1, 2, 3;

COMMIT;

-- End of migration 002