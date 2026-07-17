-- =============================================================
-- BI View 001: bi_pg_semana
-- Grain: project_id + Semana + unique_id
-- Question: ¿Qué hay que hacer?
-- Source: programa_consolidado + semanas_activas
-- =============================================================
CREATE OR REPLACE VIEW `bi_pg_semana` AS
SELECT
    pc.project_id,
    pc.Semana,
    pc.Consecutivo_en_Programa AS unique_id,
    pc.Id,
    pc.Actividad,
    pc.Titulo,
    pc.Fecha_Inicio,
    pc.Fecha_Fin,
    CASE
        WHEN pc.Fecha_Inicio IS NULL AND pc.Fecha_Fin IS NULL THEN NULL
        WHEN pc.Fecha_Inicio IS NOT NULL AND pc.Fecha_Fin IS NOT NULL
         AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0 THEN NULL
        ELSE DATEDIFF(COALESCE(pc.Fecha_Fin, pc.Fecha_Inicio), COALESCE(pc.Fecha_Inicio, pc.Fecha_Fin)) + 1
    END AS duration_days,
    pc.Ruta_Critica,
    pc.Ejecutado,
    pc.Estado,
    pc.Semanas_Inicio,
    pc.Estado_Restricciones,
    pc.D_y_E,
    pc.Materiales,
    pc.MdeO,
    pc.Equipos,
    pc.Predecesora,
    pc.Sub_Contratista AS sub_contratista,
    pc.Responsable_AIA AS responsable_aia,

    -- Restricciones duras: ¿están listas?
    CASE
        WHEN COALESCE(pc.Titulo, 0) = 1 THEN NULL
        WHEN CAST(COALESCE(pc.D_y_E, '0') AS DECIMAL(10,2)) >= 1.0
         AND CAST(COALESCE(pc.Materiales, '0') AS DECIMAL(10,2)) >= 1.0
         AND CAST(COALESCE(pc.MdeO, '0') AS DECIMAL(10,2)) >= 1.0
         AND CAST(COALESCE(pc.Equipos, '0') AS DECIMAL(10,2)) >= 1.0
         AND CAST(COALESCE(pc.Predecesora, '0') AS DECIMAL(10,2)) >= 0.5
        THEN 1 ELSE 0
    END AS hard_restrictions_ready,

    -- ¿Está en ventana Lookahead (6 semanas)?
    CASE WHEN pc.Semanas_Inicio BETWEEN 0 AND 6 THEN 1 ELSE 0 END AS is_lookahead_window,

    -- ¿Debería iniciar esta semana?
    CASE WHEN pc.Semanas_Inicio = 0 AND COALESCE(pc.Titulo, 0) = 0 AND pc.Ejecutado < 1 THEN 1 ELSE 0 END AS should_start_this_week,

    -- ¿Está atrasada?
    CASE
        WHEN COALESCE(pc.Titulo, 0) = 1 THEN NULL
        WHEN pc.Fecha_Fin IS NOT NULL
         AND pc.Fecha_Fin < COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)
         AND (pc.Ejecutado IS NULL OR pc.Ejecutado < 1)
        THEN 1 ELSE 0
    END AS is_late,

    -- ¿Está atrasada Y es crítica?
    CASE
        WHEN COALESCE(pc.Titulo, 0) = 1 THEN NULL
        WHEN pc.Ruta_Critica = 1
        AND pc.Fecha_Fin IS NOT NULL
         AND pc.Fecha_Fin < COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)
         AND (pc.Ejecutado IS NULL OR pc.Ejecutado < 1)
        THEN 1 ELSE 0
    END AS is_critical_late,

    -- Avance teórico por duración (proporcional al tiempo transcurrido)
    CASE
        WHEN COALESCE(pc.Titulo, 0) = 1 THEN NULL
        WHEN pc.Fecha_Inicio IS NULL OR pc.Fecha_Fin IS NULL THEN NULL
        WHEN COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) IS NULL THEN NULL
        WHEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0 THEN NULL
        ELSE
            LEAST(1.0, GREATEST(0.0,
                (DATEDIFF(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), pc.Fecha_Inicio) + 1) * 1.0
                / (DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)
            ))
    END AS theoretical_progress_by_duration,

    -- Delta de avance (real - teórico)
    CASE
        WHEN COALESCE(pc.Titulo, 0) = 1 THEN NULL
        WHEN pc.Ejecutado IS NOT NULL
         AND pc.Fecha_Inicio IS NOT NULL
         AND pc.Fecha_Fin IS NOT NULL
         AND COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) IS NOT NULL
         AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
        THEN
            COALESCE(pc.Ejecutado, 0) -
            LEAST(1.0, GREATEST(0.0,
                (DATEDIFF(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), pc.Fecha_Inicio) + 1) * 1.0
                / (DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)
            ))
        ELSE NULL
    END AS progress_delta,

    -- Fechas de la semana
    sa.Fecha_Inicio_Sem,
    sa.Fecha_Fin_Sem,
    sa.Semanal_Confirmada

FROM programa_consolidado pc
LEFT JOIN semanas_activas sa
    ON pc.project_id = sa.project_id
   AND pc.Semana = sa.Semana
WHERE COALESCE(pc.Titulo, 0) = 0  -- solo actividades, incluyendo Titulo NULL legado
ORDER BY pc.project_id, pc.Semana, pc.Consecutivo_en_Programa;
