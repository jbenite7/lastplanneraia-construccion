-- =============================================================
-- BI View 006: bi_cip_responsables
-- Grain: project_id + Semana + profesional (Responsable_AIA)
-- Question: ¿Quién cumple lo que gestiona?
-- Source: cip
-- ONLY fulfillment metrics. No Calidad/SST/GSA/ADM.
-- =============================================================
CREATE OR REPLACE VIEW `bi_cip_responsables` AS
SELECT
    project_id,
    Semana,
    profesional AS Responsable_AIA,
    correo_contacto,

    -- Cumplimiento (únicas dimensiones aprobadas para responsables)
    PAC,
    PAC_Acum,
    P_Completado,
    P_Completado_Acum,

    -- Actividades cumplidas por tipo
    Act_Criticas_Cumplidas,
    Act_No_Criticas_Cumplidas,
    Act_Atrasadas_Cumplidas,
    Act_Criticas_Cumplidas_Acum,
    Act_No_Criticas_Cumplidas_Acum,
    Act_Atrasadas_Cumplidas_Acum,

    -- Consolidado
    PAC_Consolidado,
    PAC_Consolidado_Acum,

    -- Número de compromisos (desde programacion_semanal)
    (
        SELECT COUNT(*)
        FROM programacion_semanal ps
        WHERE ps.project_id = cip.project_id
          AND ps.Semana = cip.Semana
          AND ps.Responsable_AIA = cip.profesional
          AND ps.Activa IN ('1', 'NA')
    ) AS number_of_commitments,

    -- Compromisos críticos
    (
        SELECT COUNT(*)
        FROM programacion_semanal ps
        WHERE ps.project_id = cip.project_id
          AND ps.Semana = cip.Semana
          AND ps.Responsable_AIA = cip.profesional
          AND ps.Critica = 1
          AND ps.Activa IN ('1', 'NA')
    ) AS critical_commitments,

    -- Compromisos incumplidos
    (
        SELECT COUNT(*)
        FROM programacion_semanal ps
        WHERE ps.project_id = cip.project_id
          AND ps.Semana = cip.Semana
          AND ps.Responsable_AIA = cip.profesional
          AND ps.PAC = 0
          AND ps.Activa IN ('1', 'NA')
    ) AS missed_commitments,

    -- Alerta de cumplimiento (sin calidad/SST/GSA/ADM)
    CASE
        WHEN CAST(REPLACE(cip.PAC, ',', '.') AS DECIMAL(10,2)) IS NOT NULL
         AND CAST(REPLACE(cip.PAC, ',', '.') AS DECIMAL(10,2)) < 0.5
        THEN 1
        WHEN (
            SELECT COUNT(*)
            FROM programacion_semanal ps
            WHERE ps.project_id = cip.project_id
              AND ps.Semana = cip.Semana
              AND ps.Responsable_AIA = cip.profesional
              AND ps.Critica = 1
              AND ps.PAC = 0
              AND ps.Activa IN ('1', 'NA')
        ) > 0
        THEN 1
        ELSE 0
    END AS fulfillment_alert

FROM cip
ORDER BY project_id, Semana,
    CAST(REPLACE(COALESCE(NULLIF(PAC, 'NA'), '0'), ',', '.') AS DECIMAL(10,2)) DESC;
