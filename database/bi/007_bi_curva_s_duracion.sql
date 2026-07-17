-- =============================================================
-- BI View 007: bi_curva_s_duracion
-- Grain: project_id + Semana
-- Source: bi_pg_semana (activities at their historical week cutoff)
-- =============================================================
CREATE OR REPLACE VIEW `bi_curva_s_duracion` AS
SELECT
    pg.project_id,
    pg.Semana,
    COUNT(*) AS total_activities,
    COUNT(*) AS real_activities,

    -- Global weighted denominator: valid inclusive calendar days for every
    -- activity in the project-week; reused by real and theoretical progress.
    SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END) AS total_duration_days,

    SUM(CASE WHEN pg.duration_days IS NOT NULL
        THEN LEAST(1.0, GREATEST(0.0, COALESCE(pg.Ejecutado, 0))) * pg.duration_days
        ELSE 0 END) AS weighted_real_progress,

    SUM(CASE WHEN pg.duration_days IS NOT NULL
        THEN COALESCE(pg.theoretical_progress_by_duration, 0) * pg.duration_days
        ELSE 0 END) AS weighted_theoretical_progress,

    CASE WHEN SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END) > 0
        THEN SUM(CASE WHEN pg.duration_days IS NOT NULL
            THEN LEAST(1.0, GREATEST(0.0, COALESCE(pg.Ejecutado, 0))) * pg.duration_days
            ELSE 0 END)
            / SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END)
        ELSE 0
    END AS pct_avance_real,

    CASE WHEN SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END) > 0
        THEN SUM(CASE WHEN pg.duration_days IS NOT NULL
            THEN COALESCE(pg.theoretical_progress_by_duration, 0) * pg.duration_days ELSE 0 END)
            / SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END)
        ELSE 0
    END AS pct_avance_teorico,

    CASE WHEN SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END) > 0
        THEN (
            SUM(CASE WHEN pg.duration_days IS NOT NULL
                THEN LEAST(1.0, GREATEST(0.0, COALESCE(pg.Ejecutado, 0))) * pg.duration_days
                ELSE 0 END)
            - SUM(CASE WHEN pg.duration_days IS NOT NULL
                THEN COALESCE(pg.theoretical_progress_by_duration, 0) * pg.duration_days ELSE 0 END)
        ) / SUM(CASE WHEN pg.duration_days IS NOT NULL THEN pg.duration_days ELSE 0 END)
        ELSE 0
    END AS pct_desviacion,

    SUM(CASE WHEN pg.is_critical_late = 1 THEN 1 ELSE 0 END) AS critical_late,
    SUM(CASE WHEN pg.Ruta_Critica = 1 THEN 1 ELSE 0 END) AS total_critical

FROM bi_pg_semana pg
GROUP BY pg.project_id, pg.Semana
ORDER BY pg.project_id, pg.Semana;
