-- ==========================================================================
-- Migration 003: Add Foreign Key Constraints (Wave 3 - Data Migration)
--
-- Strategy:
--   1. Fix orphan data: insert placeholder parent records where child rows
--      reference non-existent parent rows (pre-existing data quality issues)
--   2. Add unique indexes on FK target columns that aren't already PKs
--   3. Add all foreign key constraints with appropriate ON DELETE behavior
--   4. Verify all constraints created correctly
-- ==========================================================================

START TRANSACTION;

-- ============================================================
-- PHASE 0: Add unique indexes required for FK references
-- (IF NOT EXISTS via MySQL 8.0 information_schema check)
-- ============================================================

-- semanas_activas.Semana needs to be unique per project (it's indexed but not unique)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'semanas_activas'
    AND INDEX_NAME = 'uq_semanas_activas_project_semana');
SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `semanas_activas` ADD UNIQUE INDEX `uq_semanas_activas_project_semana` (`project_id`, `Semana`)',
  'SELECT 1 AS idx_already_exists_semanas');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- subcontratistas.subcontratista needs to be unique per project for FK reference
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subcontratistas'
    AND INDEX_NAME = 'uq_subcontratistas_project_subcontratista');
SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `subcontratistas` ADD UNIQUE INDEX `uq_subcontratistas_project_subcontratista` (`project_id`, `subcontratista`)',
  'SELECT 1 AS idx_already_exists_subcontratistas');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PHASE 1: Fix column type mismatches for FK compatibility
-- ============================================================

-- pi_shared_constraint_links.ConsecutivoEnPrograma is bigint unsigned
-- but programa.Consecutivo is int. Values fit in int (max=1861).
ALTER TABLE `pi_shared_constraint_links` MODIFY COLUMN `ConsecutivoEnPrograma` int NOT NULL;

-- ============================================================
-- PHASE 2: Fix orphan data — insert missing parent records
-- ============================================================

-- 2a. Insert missing programa records referenced by child tables
-- These are (project_id, Consecutivo) pairs that exist in child tables
-- but have no corresponding programa row.

INSERT IGNORE INTO `programa` (
  `project_id`, `Consecutivo`, `Actividad`, `Fecha_Inicio`, `Fecha_Fin`,
  `Titulo`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`,
  `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`,
  `Predecesora`, `Pdto_Cons`, `Modelo`
)
SELECT DISTINCT
  refs.project_id,
  refs.consecutivo,
  CONCAT('PLACEHOLDER (refs: ', refs.src, ')') AS `Actividad`,
  '2000-01-01' AS `Fecha_Inicio`,
  '2000-01-01' AS `Fecha_Fin`,
  0, 0, 0, 'Migrated', 0, 0, '0', '0', '0', '0', 0, 0, '0'
FROM (
  SELECT c.project_id, c.Consecutivo_en_Programa AS consecutivo, 'PC' AS src
  FROM `programa_consolidado` c
  LEFT JOIN `programa` p ON c.Consecutivo_en_Programa = p.Consecutivo AND c.project_id = p.project_id
  WHERE p.Consecutivo IS NULL
  UNION
  SELECT s.project_id, s.Consecutivo_En_Programa, 'PS'
  FROM `programacion_semanal` s
  LEFT JOIN `programa` p ON s.Consecutivo_En_Programa = p.Consecutivo AND s.project_id = p.project_id
  WHERE p.Consecutivo IS NULL
  UNION
  SELECT l.project_id, l.ConsecutivoEnPrograma, 'PSCL'
  FROM `pi_shared_constraint_links` l
  LEFT JOIN `programa` p ON l.ConsecutivoEnPrograma = p.Consecutivo AND l.project_id = p.project_id
  WHERE p.Consecutivo IS NULL
  UNION
  SELECT a.project_id, a.consecutivo, 'APL'
  FROM `auto_program_log` a
  LEFT JOIN `programa` p ON a.consecutivo = p.Consecutivo AND a.project_id = p.project_id
  WHERE p.Consecutivo IS NULL
) AS refs;

-- 2b. Insert missing subcontratista records
-- "AIA (MO Directa)" is used in cic but missing from subcontratistas for 4 projects.
-- Id values derived from MAX(Id) per project.

INSERT IGNORE INTO `subcontratistas` (`project_id`, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`)
SELECT DISTINCT
  c.project_id,
  COALESCE(s.max_id, 0) + ROW_NUMBER() OVER (PARTITION BY c.project_id ORDER BY c.subcontratista) AS `Id`,
  c.subcontratista,
  'placeholder@example.com' AS `correo_contacto`,
  0 AS `NIT`,
  'Internal' AS `alcance`,
  'Internal' AS `tipo_proveedor`
FROM `cic` c
LEFT JOIN `subcontratistas` s_exist ON c.subcontratista = s_exist.subcontratista AND c.project_id = s_exist.project_id
LEFT JOIN (SELECT project_id, MAX(Id) AS max_id FROM `subcontratistas` GROUP BY project_id) s ON c.project_id = s.project_id
WHERE c.subcontratista IS NOT NULL AND c.subcontratista != '' AND s_exist.subcontratista IS NULL;

-- ============================================================
-- PHASE 3: Foreign Key Constraints
-- ============================================================

-- -------------------------------------------------------
-- 3a. programa_consolidado → programa
--     Each consolidated entry references a master program activity
-- -------------------------------------------------------
ALTER TABLE `programa_consolidado`
ADD CONSTRAINT `fk_pc__programa__consecutivo`
FOREIGN KEY (`project_id`, `Consecutivo_en_Programa`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3b. programa_consolidado → semanas_activas
--     Each consolidated entry belongs to a specific week
-- -------------------------------------------------------
ALTER TABLE `programa_consolidado`
ADD CONSTRAINT `fk_pc__semanas_activas__semana`
FOREIGN KEY (`project_id`, `Semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3c. programacion_semanal → programa
--     Each weekly program entry references a master program activity
-- -------------------------------------------------------
ALTER TABLE `programacion_semanal`
ADD CONSTRAINT `fk_ps__programa__consecutivo`
FOREIGN KEY (`project_id`, `Consecutivo_En_Programa`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3d. programacion_semanal → semanas_activas
--     Each weekly program entry belongs to a specific week
-- -------------------------------------------------------
ALTER TABLE `programacion_semanal`
ADD CONSTRAINT `fk_ps__semanas_activas__semana`
FOREIGN KEY (`project_id`, `Semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3e. lps_drawer_comentarios → lps_escalamientos
--     Comments can be associated with escalamientos (optional)
--     NOTE: CASCADE because project_id is NOT NULL (PK),
--     so SET NULL is not possible on composite FK
-- -------------------------------------------------------
ALTER TABLE `lps_drawer_comentarios`
ADD CONSTRAINT `fk_ldc__lps_escalamientos__escalamiento_id`
FOREIGN KEY (`project_id`, `escalamiento_id`) REFERENCES `lps_escalamientos`(`project_id`, `id`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3f. lps_drawer_comentarios → programa
--     Comments reference a program activity
-- -------------------------------------------------------
ALTER TABLE `lps_drawer_comentarios`
ADD CONSTRAINT `fk_ldc__programa__consecutivo`
FOREIGN KEY (`project_id`, `consecutivo_en_programa`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3g. lps_drawer_comentarios → semanas_activas
--     Comments belong to a specific week
-- -------------------------------------------------------
ALTER TABLE `lps_drawer_comentarios`
ADD CONSTRAINT `fk_ldc__semanas_activas__semana`
FOREIGN KEY (`project_id`, `semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3h. lps_drawer_comentarios → profesionales
--     Comments have an author (project_id needed for composite FK)
-- -------------------------------------------------------
ALTER TABLE `lps_drawer_comentarios`
ADD CONSTRAINT `fk_ldc__profesionales__usuario_id`
FOREIGN KEY (`project_id`, `usuario_id`) REFERENCES `profesionales`(`project_id`, `id`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3i. lps_drawer_comentarios → lps_drawer_comentarios (self-ref)
--     Comments can have parent comments (threading)
--     NOTE: CASCADE because project_id is NOT NULL (PK)
-- -------------------------------------------------------
ALTER TABLE `lps_drawer_comentarios`
ADD CONSTRAINT `fk_ldc__parent__self`
FOREIGN KEY (`project_id`, `parent_id`) REFERENCES `lps_drawer_comentarios`(`project_id`, `id`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3j. lps_escalamientos → programa
--     Escalamientos reference a program activity
-- -------------------------------------------------------
ALTER TABLE `lps_escalamientos`
ADD CONSTRAINT `fk_le__programa__consecutivo`
FOREIGN KEY (`project_id`, `consecutivo_en_programa`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3k. lps_escalamientos → semanas_activas
--     Escalamientos belong to a specific week
-- -------------------------------------------------------
ALTER TABLE `lps_escalamientos`
ADD CONSTRAINT `fk_le__semanas_activas__semana`
FOREIGN KEY (`project_id`, `semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3l. lps_escalamientos → profesionales
--     Escalamientos can have a closer user
--     RESTRICT prevents deleting a professional who has closed escalamientos
-- -------------------------------------------------------
ALTER TABLE `lps_escalamientos`
ADD CONSTRAINT `fk_le__profesionales__usuario_cierre`
FOREIGN KEY (`project_id`, `usuario_cierre_id`) REFERENCES `profesionales`(`project_id`, `id`)
ON DELETE RESTRICT;

-- -------------------------------------------------------
-- 3m. pi_shared_constraint_links → pi_shared_constraints
--     Constraint links belong to a shared constraint
-- -------------------------------------------------------
ALTER TABLE `pi_shared_constraint_links`
ADD CONSTRAINT `fk_pscl__pi_shared_constraints`
FOREIGN KEY (`project_id`, `SharedConstraintId`) REFERENCES `pi_shared_constraints`(`project_id`, `Id`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3n. pi_shared_constraint_links → programa
--     Constraint links reference a program activity
-- -------------------------------------------------------
ALTER TABLE `pi_shared_constraint_links`
ADD CONSTRAINT `fk_pscl__programa__consecutivo`
FOREIGN KEY (`project_id`, `ConsecutivoEnPrograma`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3o. pi_shared_constraint_links → semanas_activas
--     Constraint links belong to a specific week
-- -------------------------------------------------------
ALTER TABLE `pi_shared_constraint_links`
ADD CONSTRAINT `fk_pscl__semanas_activas__semana`
FOREIGN KEY (`project_id`, `Semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3p. pi_shared_constraints → semanas_activas
--     Shared constraints belong to a specific week
-- -------------------------------------------------------
ALTER TABLE `pi_shared_constraints`
ADD CONSTRAINT `fk_psc__semanas_activas__semana`
FOREIGN KEY (`project_id`, `Semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3q. cic → subcontratistas
--     CIC evaluations can reference a subcontratista
--     NOTE: project_id is NOT NULL (PK), so SET NULL not possible
--     on composite FK. RESTRICT prevents deleting subcontratistas
--     that still have CIC evaluations.
-- -------------------------------------------------------
ALTER TABLE `cic`
ADD CONSTRAINT `fk_cic__subcontratistas__subcontratista`
FOREIGN KEY (`project_id`, `subcontratista`) REFERENCES `subcontratistas`(`project_id`, `subcontratista`)
ON DELETE RESTRICT;

-- -------------------------------------------------------
-- 3r. pg_tracking → programa
--     PG tracking references a program activity
-- -------------------------------------------------------
ALTER TABLE `pg_tracking`
ADD CONSTRAINT `fk_pgt__programa__consecutivo`
FOREIGN KEY (`project_id`, `consecutivo_en_programa`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3s. pg_tracking → semanas_activas
--     PG tracking belongs to a specific week
-- -------------------------------------------------------
ALTER TABLE `pg_tracking`
ADD CONSTRAINT `fk_pgt__semanas_activas__semana`
FOREIGN KEY (`project_id`, `semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3t. auto_program_log → programa
--     Auto-program log references a program activity
-- -------------------------------------------------------
ALTER TABLE `auto_program_log`
ADD CONSTRAINT `fk_apl__programa__consecutivo`
FOREIGN KEY (`project_id`, `consecutivo`) REFERENCES `programa`(`project_id`, `Consecutivo`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3u. auto_program_log → semanas_activas
--     Auto-program log belongs to a specific week
-- -------------------------------------------------------
ALTER TABLE `auto_program_log`
ADD CONSTRAINT `fk_apl__semanas_activas__semana`
FOREIGN KEY (`project_id`, `semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3v. pdc → semanas_activas
--     PDC entries belong to a specific week
-- -------------------------------------------------------
ALTER TABLE `pdc`
ADD CONSTRAINT `fk_pdc__semanas_activas__semana`
FOREIGN KEY (`project_id`, `semana`) REFERENCES `semanas_activas`(`project_id`, `Semana`)
ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3w. pdc → profesionales
--     PDC entries can reference an adjudicated provider (professional)
--     RESTRICT: project_id in composite FK prevents SET NULL
-- -------------------------------------------------------
ALTER TABLE `pdc`
ADD CONSTRAINT `fk_pdc__profesionales__idproveedor`
FOREIGN KEY (`project_id`, `idProveedorAdjudicado`) REFERENCES `profesionales`(`project_id`, `id`)
ON DELETE RESTRICT;

COMMIT;

-- ============================================================
-- VERIFICATION
-- ============================================================

-- V1: List all FK constraints created
SELECT '=== FK CONSTRAINTS APPLIED ===' AS '';
SELECT `CONSTRAINT_NAME`, `TABLE_NAME`, `COLUMN_NAME`,
       `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`
FROM `information_schema`.`KEY_COLUMN_USAGE`
WHERE `TABLE_SCHEMA` = 'lastplanneraia_dev'
  AND `REFERENCED_TABLE_NAME` IS NOT NULL
  AND `CONSTRAINT_NAME` LIKE 'fk_%'
ORDER BY `TABLE_NAME`, `CONSTRAINT_NAME`;

-- V2: Verify 0 orphan rows across all FK relationships
SELECT '=== ORPHAN VERIFICATION ===' AS '';
SELECT
  (SELECT COUNT(*) FROM `programa_consolidado` c
   LEFT JOIN `programa` p ON c.Consecutivo_en_Programa = p.Consecutivo AND c.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_pc__programa`,
  (SELECT COUNT(*) FROM `programa_consolidado` c
   LEFT JOIN `semanas_activas` w ON c.Semana = w.Semana AND c.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_pc__semanas`,
  (SELECT COUNT(*) FROM `programacion_semanal` s
   LEFT JOIN `programa` p ON s.Consecutivo_En_Programa = p.Consecutivo AND s.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_ps__programa`,
  (SELECT COUNT(*) FROM `programacion_semanal` s
   LEFT JOIN `semanas_activas` w ON s.Semana = w.Semana AND s.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_ps__semanas`,
  (SELECT COUNT(*) FROM `lps_drawer_comentarios` c
   LEFT JOIN `lps_escalamientos` e ON c.escalamiento_id = e.id AND c.project_id = e.project_id
   WHERE c.escalamiento_id IS NOT NULL AND e.id IS NULL) AS `orphans_ldc__escalamientos`,
  (SELECT COUNT(*) FROM `lps_drawer_comentarios` c
   LEFT JOIN `programa` p ON c.consecutivo_en_programa = p.Consecutivo AND c.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_ldc__programa`,
  (SELECT COUNT(*) FROM `lps_drawer_comentarios` c
   LEFT JOIN `semanas_activas` w ON c.semana = w.Semana AND c.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_ldc__semanas`,
  (SELECT COUNT(*) FROM `lps_drawer_comentarios` c1
   LEFT JOIN `lps_drawer_comentarios` c2 ON c1.parent_id = c2.id AND c1.project_id = c2.project_id
   WHERE c1.parent_id IS NOT NULL AND c2.id IS NULL) AS `orphans_ldc__parent`,
  (SELECT COUNT(*) FROM `lps_escalamientos` e
   LEFT JOIN `programa` p ON e.consecutivo_en_programa = p.Consecutivo AND e.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_le__programa`,
  (SELECT COUNT(*) FROM `lps_escalamientos` e
   LEFT JOIN `semanas_activas` w ON e.semana = w.Semana AND e.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_le__semanas`,
  (SELECT COUNT(*) FROM `lps_escalamientos` e
   LEFT JOIN `profesionales` pr ON e.usuario_cierre_id = pr.id AND e.project_id = pr.project_id
   WHERE e.usuario_cierre_id IS NOT NULL AND pr.id IS NULL) AS `orphans_le__prof`,
  (SELECT COUNT(*) FROM `pi_shared_constraint_links` l
   LEFT JOIN `pi_shared_constraints` c ON l.SharedConstraintId = c.Id AND l.project_id = c.project_id
   WHERE c.Id IS NULL) AS `orphans_pscl__constraints`,
  (SELECT COUNT(*) FROM `pi_shared_constraint_links` l
   LEFT JOIN `programa` p ON l.ConsecutivoEnPrograma = p.Consecutivo AND l.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_pscl__programa`,
  (SELECT COUNT(*) FROM `pi_shared_constraint_links` l
   LEFT JOIN `semanas_activas` w ON l.Semana = w.Semana AND l.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_pscl__semanas`,
  (SELECT COUNT(*) FROM `pi_shared_constraints` c
   LEFT JOIN `semanas_activas` w ON c.Semana = w.Semana AND c.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_psc__semanas`,
  (SELECT COUNT(*) FROM `cic` c
   LEFT JOIN `subcontratistas` s ON c.subcontratista = s.subcontratista AND c.project_id = s.project_id
   WHERE c.subcontratista IS NOT NULL AND c.subcontratista != '' AND s.subcontratista IS NULL) AS `orphans_cic__sub`,
  (SELECT COUNT(*) FROM `pg_tracking` t
   LEFT JOIN `programa` p ON t.consecutivo_en_programa = p.Consecutivo AND t.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_pgt__programa`,
  (SELECT COUNT(*) FROM `pg_tracking` t
   LEFT JOIN `semanas_activas` w ON t.semana = w.Semana AND t.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_pgt__semanas`,
  (SELECT COUNT(*) FROM `auto_program_log` l
   LEFT JOIN `programa` p ON l.consecutivo = p.Consecutivo AND l.project_id = p.project_id
   WHERE p.Consecutivo IS NULL) AS `orphans_apl__programa`,
  (SELECT COUNT(*) FROM `auto_program_log` l
   LEFT JOIN `semanas_activas` w ON l.semana = w.Semana AND l.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_apl__semanas`,
  (SELECT COUNT(*) FROM `pdc` d
   LEFT JOIN `semanas_activas` w ON d.semana = w.Semana AND d.project_id = w.project_id
   WHERE w.Semana IS NULL) AS `orphans_pdc__semanas`,
  (SELECT COUNT(*) FROM `pdc` d
   LEFT JOIN `profesionales` pr ON d.idProveedorAdjudicado = pr.id AND d.project_id = pr.project_id
   WHERE d.idProveedorAdjudicado IS NOT NULL AND pr.id IS NULL) AS `orphans_pdc__prof`;
