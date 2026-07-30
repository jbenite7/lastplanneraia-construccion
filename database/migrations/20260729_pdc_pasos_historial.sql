-- 20260729_pdc_pasos_historial.sql
-- A4.1 · diferido nº 3 — quién cambió la configuración de pasos de una obra, cuándo y a qué.
--
-- Tabla de SOLO ANEXAR: una fila por guardado, con la configuración completa en JSON. Se guarda
-- entera y no un diff porque la lista es corta (siete pasos de media) y un diff obliga a
-- reconstruir el estado leyendo toda la cadena para responder «¿cómo estaba en mayo?», que es la
-- única pregunta que esta tabla existe para contestar.
--
-- Tabla global aislada por project_id, como el resto del módulo. Sin FK a general_proyectos_procesos
-- a propósito: el historial de una obra retirada sigue siendo la respuesta a «por qué se movieron
-- aquellas fechas», y un ON DELETE CASCADE se lo llevaría justo cuando alguien pregunta.
CREATE TABLE IF NOT EXISTS pdc_proyecto_pasos_historial (
    id BIGINT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    configuracion JSON NOT NULL,
    pasos SMALLINT NOT NULL,
    actualizado_por VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ppph_proyecto (project_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
