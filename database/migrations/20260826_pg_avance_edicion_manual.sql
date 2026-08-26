-- 2026-08-26 — Bitácora del avance editado a mano en Programa General.
--
-- Existe para que `WeeklyRealProgressCarryoverService` deje de adivinar, en su caso ambiguo, si
-- un valor de `Ejecutado` lo puso una persona o es residuo del defecto corregido el 2026-08-25
-- (commit c1e3365e). Sin esta evidencia, una edición real y un residuo producen el mismo dato.
--
-- Nombre fijo, nunca por TableResolver: sigue la convención de `general_auditoria_acciones`, la
-- otra tabla de auditoría del repo, que `Database::logActivity` referencia por nombre directo.
--
-- Reversión:  DROP TABLE pg_avance_edicion_manual;

CREATE TABLE IF NOT EXISTS `pg_avance_edicion_manual` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `Semana` int NOT NULL,
  `unique_id` int NOT NULL,
  `valor_anterior` decimal(12,6) DEFAULT NULL,
  `valor_nuevo` decimal(12,6) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`project_id`, `Semana`, `unique_id`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ediciones manuales de Ejecutado en programa_consolidado; la consulta el arrastre';
