-- 20260728_pdc_v2_plan_fechas.sql
-- PDC v2 / Fase A4: el plan de compras con fechas.
--
-- (A) pdc_paquete_frente — el amarre. Un paquete apunta a un encabezado del cronograma por su
--     unique_id, que es estable ante reprogramaciones (medido: los 273 de la semana 1 son los
--     mismos en la semana 4). Se guarda la fecha que tenía ese frente al amarrar para poder
--     detectar después que se movió.
-- (B) pdc_plan_paquete — la cabecera del plan calculado.
-- (C) pdc_plan_paso — una fila por paso del proceso. Tabla hija y no siete columnas porque B1
--     pondrá la fecha real junto a la programada sin rehacer el modelo.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS. No toca datos existentes.

CREATE TABLE IF NOT EXISTS pdc_paquete_frente (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  frente_nombre VARCHAR(500) NOT NULL,
  fecha_ancla DATE NOT NULL,
  semana_origen INT NOT NULL,
  origen ENUM('similitud','rama','humano') NOT NULL DEFAULT 'humano',
  confianza ENUM('alta','media','baja') NULL,
  evidencia VARCHAR(500) NOT NULL DEFAULT '',
  confirmado_humano TINYINT(1) NOT NULL DEFAULT 0,
  asignado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppf_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppf_proyecto_frente (project_id, unique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paquete (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  fecha_ancla DATE NOT NULL,
  fecha_arranque DATE NOT NULL,
  dias_totales INT NOT NULL,
  duracion_ref INT NULL,
  duracion_provisional TINYINT(1) NOT NULL DEFAULT 0,
  responsable VARCHAR(100) NOT NULL DEFAULT '',
  calculado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppp_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppp_proyecto_arranque (project_id, fecha_arranque)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paso (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  orden TINYINT NOT NULL,
  paso VARCHAR(60) NOT NULL,
  dias INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pps_proyecto_paquete_orden (project_id, paquete_id, orden),
  KEY idx_pps_proyecto_paquete (project_id, paquete_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
