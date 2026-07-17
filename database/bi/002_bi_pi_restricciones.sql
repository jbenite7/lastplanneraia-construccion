-- =============================================================
-- BI View 002: bi_pi_restricciones
-- Grain: project_id + Semana + unique_id + restriction_type
-- Question: ¿Podemos hacerlo?
-- Source: programa_consolidado + pi_shared_constraints + pi_shared_constraint_links
-- Only activity rows (Titulo=0), no title rows.
-- =============================================================
CREATE OR REPLACE VIEW `bi_pi_restricciones` AS
SELECT
    pc.project_id,
    pc.Semana,
    pc.Consecutivo_en_Programa AS unique_id,
    pc.Id,
    pc.Actividad,
    pc.Fecha_Inicio,
    pc.Fecha_Fin,
    pc.Ruta_Critica,
    pc.Ejecutado,
    pc.Estado,
    pc.Semanas_Inicio,
    pc.Sub_Contratista AS subcontractor,
    pc.Responsable_AIA AS responsible,

    -- Desnormalizar las 5 restricciones duras como filas
    'D_y_E' AS restriction_type,
    CAST(COALESCE(pc.D_y_E, '0') AS DECIMAL(10,2)) AS restriction_value,
    1.0 AS required_threshold,
    CASE WHEN CAST(COALESCE(pc.D_y_E, '0') AS DECIMAL(10,2)) >= 1.0 THEN 1 ELSE 0 END AS is_ready,
    1 AS is_hard

FROM programa_consolidado pc
WHERE pc.Titulo = 0

UNION ALL

SELECT
    pc.project_id, pc.Semana, pc.Consecutivo_en_Programa,
    pc.Id, pc.Actividad, pc.Fecha_Inicio, pc.Fecha_Fin,
    pc.Ruta_Critica, pc.Ejecutado, pc.Estado, pc.Semanas_Inicio,
    pc.Sub_Contratista, pc.Responsable_AIA,
    'Materiales',
    CAST(COALESCE(pc.Materiales, '0') AS DECIMAL(10,2)),
    1.0,
    CASE WHEN CAST(COALESCE(pc.Materiales, '0') AS DECIMAL(10,2)) >= 1.0 THEN 1 ELSE 0 END,
    1
FROM programa_consolidado pc
WHERE pc.Titulo = 0

UNION ALL

SELECT
    pc.project_id, pc.Semana, pc.Consecutivo_en_Programa,
    pc.Id, pc.Actividad, pc.Fecha_Inicio, pc.Fecha_Fin,
    pc.Ruta_Critica, pc.Ejecutado, pc.Estado, pc.Semanas_Inicio,
    pc.Sub_Contratista, pc.Responsable_AIA,
    'MdeO',
    CAST(COALESCE(pc.MdeO, '0') AS DECIMAL(10,2)),
    1.0,
    CASE WHEN CAST(COALESCE(pc.MdeO, '0') AS DECIMAL(10,2)) >= 1.0 THEN 1 ELSE 0 END,
    1
FROM programa_consolidado pc
WHERE pc.Titulo = 0

UNION ALL

SELECT
    pc.project_id, pc.Semana, pc.Consecutivo_en_Programa,
    pc.Id, pc.Actividad, pc.Fecha_Inicio, pc.Fecha_Fin,
    pc.Ruta_Critica, pc.Ejecutado, pc.Estado, pc.Semanas_Inicio,
    pc.Sub_Contratista, pc.Responsable_AIA,
    'Equipos',
    CAST(COALESCE(pc.Equipos, '0') AS DECIMAL(10,2)),
    1.0,
    CASE WHEN CAST(COALESCE(pc.Equipos, '0') AS DECIMAL(10,2)) >= 1.0 THEN 1 ELSE 0 END,
    1
FROM programa_consolidado pc
WHERE pc.Titulo = 0

UNION ALL

SELECT
    pc.project_id, pc.Semana, pc.Consecutivo_en_Programa,
    pc.Id, pc.Actividad, pc.Fecha_Inicio, pc.Fecha_Fin,
    pc.Ruta_Critica, pc.Ejecutado, pc.Estado, pc.Semanas_Inicio,
    pc.Sub_Contratista, pc.Responsable_AIA,
    'Predecesora',
    CAST(COALESCE(pc.Predecesora, '0') AS DECIMAL(10,2)),
    0.5,
    CASE WHEN CAST(COALESCE(pc.Predecesora, '0') AS DECIMAL(10,2)) >= 0.5 THEN 1 ELSE 0 END,
    1
FROM programa_consolidado pc
WHERE pc.Titulo = 0

-- Shared constraints (from pi_shared_constraints)
UNION ALL

SELECT
    pc.project_id,
    pcl.Semana,
    pcl.ConsecutivoEnPrograma AS unique_id,
    pc.Id,
    pc.Actividad,
    pc.Fecha_Inicio,
    pc.Fecha_Fin,
    pc.Ruta_Critica,
    pc.Ejecutado,
    pc.Estado,
    pc.Semanas_Inicio,
    pc.Sub_Contratista,
    pc.Responsable_AIA,
    psc.Restriccion AS restriction_type,
    CAST(pcl.ValorAplicado AS DECIMAL(10,2)) AS restriction_value,
    CAST(psc.ValorObjetivo AS DECIMAL(10,2)) AS required_threshold,
    CASE
        WHEN CAST(pcl.ValorAplicado AS DECIMAL(10,2)) >= CAST(psc.ValorObjetivo AS DECIMAL(10,2))
        THEN 1 ELSE 0
    END AS is_ready,
    0 AS is_hard  -- shared constraints are always soft by default

FROM pi_shared_constraint_links pcl
JOIN pi_shared_constraints psc
    ON pcl.SharedConstraintId = psc.Id
   AND pcl.project_id = psc.project_id
JOIN programa_consolidado pc
    ON pcl.ConsecutivoEnPrograma = pc.Consecutivo_en_Programa
   AND pcl.Semana = pc.Semana
   AND pcl.project_id = pc.project_id
WHERE pc.Titulo = 0

ORDER BY project_id, Semana, unique_id, restriction_type;
