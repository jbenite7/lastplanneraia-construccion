-- =============================================================
-- BI View 009: bi_control_tower_summary
-- Grain: project_id + Semana
-- Purpose: Aggregated executive summary feeding the overview page.
-- Counts activities_to_do, activities_can_do, activities_will_do, and risk metrics.
-- =============================================================
CREATE OR REPLACE VIEW `bi_control_tower_summary` AS
SELECT
    pg.project_id,
    pg.Semana,

    -- Activities TO DO (Programa General — what needs to be done)
    COUNT(DISTINCT CASE WHEN COALESCE(pg.Titulo, 0) = 0 AND pg.is_lookahead_window = 1
        THEN pg.unique_id END) AS activities_to_do_count,

    -- Activities CAN DO (hard restrictions ready — what can be done)
    COUNT(DISTINCT CASE WHEN COALESCE(pg.Titulo, 0) = 0 AND pg.is_lookahead_window = 1 AND pg.hard_restrictions_ready = 1
        THEN pg.unique_id END) AS activities_can_do_count,

    -- Activities WILL DO (committed in weekly plan — what will be done)
    (
        SELECT COUNT(DISTINCT ps.Consecutivo_En_Programa)
        FROM programacion_semanal ps
        WHERE ps.project_id = pg.project_id
          AND ps.Semana = pg.Semana
          AND ps.Activa = 'Si'
          AND ps.Es_TNP = 0
    ) AS activities_will_do_count,

    -- Critical late activities
    COUNT(DISTINCT CASE WHEN pg.is_critical_late = 1 THEN pg.unique_id END) AS critical_late_count,

    -- Hard restriction blocked
    COUNT(DISTINCT CASE WHEN COALESCE(pg.Titulo, 0) = 0 AND pg.is_lookahead_window = 1 AND pg.hard_restrictions_ready = 0
        THEN pg.unique_id END) AS hard_restriction_blocked_count,

    -- Weekly commitments
    (
        SELECT COUNT(*)
        FROM programacion_semanal ps
        WHERE ps.project_id = pg.project_id
          AND ps.Semana = pg.Semana
          AND ps.Activa = 'Si'
    ) AS weekly_commitments_count,

    -- Weekly commitments at risk (fulfillment_alert)
    (
        SELECT COUNT(*)
        FROM bi_ps_compromisos bps
        WHERE bps.project_id = pg.project_id
          AND bps.Semana = pg.Semana
          AND bps.fulfillment_alert = 1
    ) AS weekly_commitments_at_risk_count,

    -- PDC at risk: la fuente era `bi_pdc_general` (tabla `pdc` del PDC v1), eliminada el
    -- 2026-08-04. La columna se conserva en cero para no romper a sus consumidores
    -- (ControlTowerService, MetricDictionaryService) hasta que el Plan de Compras v2
    -- publique su propia vista BI de riesgo de compras.
    0 AS pdc_at_risk_count,

    -- Contractors at risk
    (
        SELECT COUNT(DISTINCT bc.subcontratista)
        FROM bi_cic_contratistas bc
        WHERE bc.project_id = pg.project_id
          AND bc.Semana = pg.Semana
          AND bc.alert_contractor_future_risk = 1
    ) AS contractors_at_risk_count,

    -- Responsibles at risk
    (
        SELECT COUNT(DISTINCT br.Responsable_AIA)
        FROM bi_cip_responsables br
        WHERE br.project_id = pg.project_id
          AND br.Semana = pg.Semana
          AND br.fulfillment_alert = 1
    ) AS responsibles_at_risk_count,

    -- Curva S metrics
    cs.pct_avance_real,
    cs.pct_avance_teorico,
    cs.pct_desviacion,
    cs.total_critical,
    cs.critical_late AS curva_critical_late

FROM bi_pg_semana pg
LEFT JOIN bi_curva_s_duracion cs
    ON pg.project_id = cs.project_id
   AND pg.Semana = cs.Semana
GROUP BY pg.project_id, pg.Semana
ORDER BY pg.project_id, pg.Semana;
