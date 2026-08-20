-- 20260820_general_flags.sql
-- Tabla global de interruptores (spec 2026-08-20-interruptor-control-tower-admin-design.md).
-- Sin project_id a propósito: los flags que viven aquí son globales por diseño.
-- Idempotente: IF NOT EXISTS + INSERT IGNORE, para poder re-correrla sin daño.

CREATE TABLE IF NOT EXISTS general_flags (
  clave VARCHAR(100) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  actualizado_por VARCHAR(100) NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encendido al sembrar: el estado publicado hoy es «A y D lo ven» y la llegada del
-- interruptor no debe cambiar el comportamiento.
INSERT IGNORE INTO general_flags (clave, valor, actualizado_por)
VALUES ('bi.control_tower.visible', '1', 'migracion');
