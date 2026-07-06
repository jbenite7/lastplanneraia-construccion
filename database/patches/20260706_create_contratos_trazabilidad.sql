-- =============================================================================
-- Patch: 20260706_create_contratos_trazabilidad.sql
-- Fix:   Crea la tabla contratos_trazabilidad necesaria para el registro
--        de cambio de contratos (antes/después) usado por
--        ContratosApiController::recordContractTrace.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `contratos_trazabilidad` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `actividad_id` int NOT NULL,
  `semana` int NOT NULL,
  `usuario` varchar(120) DEFAULT NULL,
  `origen` varchar(40) NOT NULL DEFAULT 'manual',
  `campos_cambiados` json NOT NULL,
  `antes` json DEFAULT NULL,
  `despues` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contratos_traza_project_week` (`project_id`, `semana`),
  KEY `idx_contratos_traza_activity` (`project_id`, `actividad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;