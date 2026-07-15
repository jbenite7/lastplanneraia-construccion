-- =============================================================
-- BI View 008: bi_riesgos
-- Grain: project_id + Semana + entity_type + entity_id
-- Purpose: Compute risk_score_100 with weighted sum formula and N drivers.
-- Formula (Doc Section 8.7):
--   risk_score_100 = 35*probability + 25*impact + 20*urgency + 10*criticality + 10*confidence
-- Levels: Bajo 0-30, Medio 31-60, Alto 61-80, Crítico 81-100
-- =============================================================
CREATE OR REPLACE VIEW `bi_riesgos` AS

-- Riesgos de actividad (desde bi_pg_semana)
SELECT
    pg.project_id,
    pg.Semana,
    'actividad' AS entity_type,
    pg.unique_id AS entity_id,
    pg.Actividad AS entity_name,

    -- Probability: basado en restricciones duras + estado
    CASE
        WHEN pg.hard_restrictions_ready = 0 AND pg.is_lookahead_window = 1 THEN 0.80
        WHEN pg.hard_restrictions_ready = 0 THEN 0.50
        WHEN pg.is_late = 1 THEN 0.70
        WHEN pg.should_start_this_week = 1 AND pg.hard_restrictions_ready = 1 THEN 0.30
        ELSE 0.20
    END AS probability_score,

    -- Impact: basado en duración + criticidad
    CASE
        WHEN pg.Ruta_Critica = 1 AND pg.duration_days > 30 THEN 0.90
        WHEN pg.Ruta_Critica = 1 THEN 0.70
        WHEN pg.duration_days > 30 THEN 0.50
        WHEN pg.duration_days > 10 THEN 0.30
        ELSE 0.15
    END AS impact_score,

    -- Urgency: basado en semanas hasta inicio
    CASE
        WHEN pg.Semanas_Inicio = 0 THEN 0.95
        WHEN pg.Semanas_Inicio = 1 THEN 0.80
        WHEN pg.Semanas_Inicio BETWEEN 2 AND 3 THEN 0.60
        WHEN pg.Semanas_Inicio BETWEEN 4 AND 6 THEN 0.40
        WHEN pg.Semanas_Inicio IS NULL THEN 0.30
        ELSE 0.10
    END AS urgency_score,

    -- Criticality: ruta crítica + atraso
    CASE
        WHEN pg.is_critical_late = 1 THEN 1.0
        WHEN pg.Ruta_Critica = 1 AND pg.is_late = 1 THEN 0.90
        WHEN pg.Ruta_Critica = 1 THEN 0.60
        WHEN pg.is_late = 1 THEN 0.40
        ELSE 0.20
    END AS criticality_score,

    -- Data confidence: basado en completitud de datos
    CASE
        WHEN pg.Responsable_AIA IS NOT NULL AND pg.Responsable_AIA != ''
         AND pg.sub_contratista IS NOT NULL AND pg.sub_contratista != ''
         AND pg.Fecha_Inicio IS NOT NULL AND pg.Fecha_Fin IS NOT NULL
         AND pg.D_y_E IS NOT NULL AND pg.Materiales IS NOT NULL AND pg.MdeO IS NOT NULL
        THEN 0.90
        WHEN pg.Responsable_AIA IS NOT NULL AND pg.Responsable_AIA != ''
         AND pg.Fecha_Inicio IS NOT NULL
        THEN 0.60
        ELSE 0.30
    END AS data_confidence_score,

    -- Score compuesto (suma ponderada)
    ROUND(
        35 * CASE
            WHEN pg.hard_restrictions_ready = 0 AND pg.is_lookahead_window = 1 THEN 0.80
            WHEN pg.hard_restrictions_ready = 0 THEN 0.50
            WHEN pg.is_late = 1 THEN 0.70
            WHEN pg.should_start_this_week = 1 AND pg.hard_restrictions_ready = 1 THEN 0.30
            ELSE 0.20 END
        + 25 * CASE
            WHEN pg.Ruta_Critica = 1 AND pg.duration_days > 30 THEN 0.90
            WHEN pg.Ruta_Critica = 1 THEN 0.70
            WHEN pg.duration_days > 30 THEN 0.50
            WHEN pg.duration_days > 10 THEN 0.30
            ELSE 0.15 END
        + 20 * CASE
            WHEN pg.Semanas_Inicio = 0 THEN 0.95
            WHEN pg.Semanas_Inicio = 1 THEN 0.80
            WHEN pg.Semanas_Inicio BETWEEN 2 AND 3 THEN 0.60
            WHEN pg.Semanas_Inicio BETWEEN 4 AND 6 THEN 0.40
            WHEN pg.Semanas_Inicio IS NULL THEN 0.30
            ELSE 0.10 END
        + 10 * CASE
            WHEN pg.is_critical_late = 1 THEN 1.0
            WHEN pg.Ruta_Critica = 1 AND pg.is_late = 1 THEN 0.90
            WHEN pg.Ruta_Critica = 1 THEN 0.60
            WHEN pg.is_late = 1 THEN 0.40
            ELSE 0.20 END
        + 10 * CASE
            WHEN pg.Responsable_AIA IS NOT NULL AND pg.Responsable_AIA != ''
             AND pg.sub_contratista IS NOT NULL AND pg.sub_contratista != ''
             AND pg.Fecha_Inicio IS NOT NULL AND pg.Fecha_Fin IS NOT NULL
            THEN 0.90
            WHEN pg.Responsable_AIA IS NOT NULL AND pg.Responsable_AIA != ''
             AND pg.Fecha_Inicio IS NOT NULL
            THEN 0.60
            ELSE 0.30 END
    , 0) AS risk_score_100,

    'actividad' AS risk_type,
    'bi_pg_semana' AS source_view,
    CAST(COALESCE(pg.Fecha_Fin_Sem, pg.Fecha_Inicio_Sem) AS DATETIME) AS computed_at

FROM bi_pg_semana pg
WHERE COALESCE(pg.Titulo, 0) = 0

UNION ALL

-- Riesgos de contratista (desde bi_cic_contratistas)
SELECT
    cic.project_id,
    cic.Semana,
    'contratista' AS entity_type,
    cic.subcontratista AS entity_id,
    cic.subcontratista AS entity_name,

    -- Probability: basado en Cal_Integral_Acum
    CASE
        WHEN cic.Cal_Integral_Acum < 50 THEN 0.80
        WHEN cic.Cal_Integral_Acum < 65 THEN 0.50
        WHEN cic.Cal_Integral_Acum < 75 THEN 0.30
        ELSE 0.10
    END AS probability_score,

    -- Impact: siempre medio-alto para contratistas
    0.50 AS impact_score,

    -- Urgency: contratistas en riesgo son urgentes si hay compromisos activos
    CASE
        WHEN cic.alert_contractor_future_risk = 1 THEN 0.70
        ELSE 0.30
    END AS urgency_score,

    -- Criticality: basado en alerta
    CASE
        WHEN cic.alert_contractor_future_risk = 1 THEN 0.60
        ELSE 0.20
    END AS criticality_score,

    -- Data confidence: basado en completitud de scores
    CASE
        WHEN cic.Cal_Integral IS NOT NULL AND cic.Cal_Integral_Acum IS NOT NULL THEN 0.85
        WHEN cic.PAC IS NOT NULL AND cic.PAC != 'NA' THEN 0.50
        ELSE 0.25
    END AS data_confidence_score,

    ROUND(
        35 * CASE WHEN cic.Cal_Integral_Acum < 50 THEN 0.80 WHEN cic.Cal_Integral_Acum < 65 THEN 0.50 WHEN cic.Cal_Integral_Acum < 75 THEN 0.30 ELSE 0.10 END
        + 25 * 0.50
        + 20 * CASE WHEN cic.alert_contractor_future_risk = 1 THEN 0.70 ELSE 0.30 END
        + 10 * CASE WHEN cic.alert_contractor_future_risk = 1 THEN 0.60 ELSE 0.20 END
        + 10 * CASE WHEN cic.Cal_Integral IS NOT NULL AND cic.Cal_Integral_Acum IS NOT NULL THEN 0.85 WHEN cic.PAC != 'NA' THEN 0.50 ELSE 0.25 END
    , 0) AS risk_score_100,

    'contratista' AS risk_type,
    'bi_cic_contratistas' AS source_view,
    CAST(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS DATETIME) AS computed_at

FROM bi_cic_contratistas cic
LEFT JOIN semanas_activas sa
    ON sa.project_id = cic.project_id
   AND sa.Semana = cic.Semana

UNION ALL

-- Riesgos de PDC (desde bi_pdc_general)
SELECT
    pdc.project_id,
    pdc.semana,
    'pdc' AS entity_type,
    CAST(pdc.consecutivo AS CHAR) AS entity_id,
    pdc.paqueteContratacion AS entity_name,

    -- Probability: basado en si está listo + días delta
    CASE
        WHEN pdc.listo_para_iniciar = 0 AND pdc.dias_delta_simple > 7 THEN 0.80
        WHEN pdc.listo_para_iniciar = 0 THEN 0.55
        WHEN pdc.necesita_configuracion = 1 THEN 0.40
        ELSE 0.15
    END AS probability_score,

    -- Impact: basado en valor del paquete
    CASE
        WHEN pdc.valorPresupuesto > 100000000 THEN 0.70
        WHEN pdc.valorPresupuesto > 10000000 THEN 0.45
        ELSE 0.25
    END AS impact_score,

    -- Urgency: basado en fecha de inicio próxima
    CASE
        WHEN pdc.fechaInicio IS NOT NULL
         AND pdc.fechaInicio <= DATE_ADD(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), INTERVAL 2 WEEK) THEN 0.85
        WHEN pdc.fechaInicio IS NOT NULL
         AND pdc.fechaInicio <= DATE_ADD(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), INTERVAL 6 WEEK) THEN 0.55
        ELSE 0.25
    END AS urgency_score,

    -- Criticality: PDC siempre impacta flujo
    0.50 AS criticality_score,

    -- Data confidence
    CASE
        WHEN pdc.diasElaboracionPliegos IS NOT NULL AND pdc.diasInsumosObra IS NOT NULL THEN 0.80
        ELSE 0.35
    END AS data_confidence_score,

    ROUND(
        35 * CASE WHEN pdc.listo_para_iniciar = 0 AND pdc.dias_delta_simple > 7 THEN 0.80 WHEN pdc.listo_para_iniciar = 0 THEN 0.55 WHEN pdc.necesita_configuracion = 1 THEN 0.40 ELSE 0.15 END
        + 25 * CASE WHEN pdc.valorPresupuesto > 100000000 THEN 0.70 WHEN pdc.valorPresupuesto > 10000000 THEN 0.45 ELSE 0.25 END
        + 20 * CASE
            WHEN pdc.fechaInicio <= DATE_ADD(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), INTERVAL 2 WEEK) THEN 0.85
            WHEN pdc.fechaInicio <= DATE_ADD(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), INTERVAL 6 WEEK) THEN 0.55
            ELSE 0.25
        END
        + 10 * 0.50
        + 10 * CASE WHEN pdc.diasElaboracionPliegos IS NOT NULL AND pdc.diasInsumosObra IS NOT NULL THEN 0.80 ELSE 0.35 END
    , 0) AS risk_score_100,

    'pdc' AS risk_type,
    'bi_pdc_general' AS source_view,
    CAST(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS DATETIME) AS computed_at

FROM bi_pdc_general pdc
LEFT JOIN semanas_activas sa
    ON sa.project_id = pdc.project_id
   AND sa.Semana = pdc.semana

ORDER BY project_id, Semana, risk_score_100 DESC;
