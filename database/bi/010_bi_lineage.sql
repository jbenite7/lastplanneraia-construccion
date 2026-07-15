-- =============================================================
-- BI View 010: bi_lineage
-- Grain: metric_key (one row for each BI metric definition)
-- Purpose: Single source of truth for every KPI's formula, source, grain, filters.
-- This is a metadata table, not a query-time lineage tracker.
-- last_updated is a static contract revision date, never query execution time.
-- =============================================================
CREATE OR REPLACE VIEW `bi_lineage` AS
SELECT
    'pg_activities_to_do' AS metric_key,
    'Actividades en ventana Lookahead' AS metric_name,
    'Número de actividades con Semanas_Inicio entre 0 y 6' AS definition,
    'COUNT(DISTINCT unique_id) WHERE COALESCE(Titulo,0)=0 AND Semanas_Inicio BETWEEN 0 AND 6' AS formula,
    'bi_pg_semana' AS source_view,
    'programa_consolidado, semanas_activas' AS source_tables,
    'project_id + Semana' AS grain,
    'COALESCE(Titulo,0)=0, Semanas_Inicio BETWEEN 0 AND 6' AS filters,
    '1.0' AS version,
    CAST('2026-07-10' AS DATE) AS last_updated,
    'Solo actividades reales, excluye títulos y actividades terminadas' AS known_limitations

UNION ALL SELECT
    'pi_hard_restrictions_ready_rate', 'Porcentaje de actividades listas en ventana',
    'Actividades con todas las restricciones duras cumplidas / actividades en ventana',
    'SUM(CASE WHEN hard_restrictions_ready=1 THEN 1 ELSE 0 END) / COUNT(*)', 'bi_pg_semana',
    'programa_consolidado', 'project_id + Semana',
    'COALESCE(Titulo,0)=0, Semanas_Inicio BETWEEN 0 AND 6', '1.0', CAST('2026-07-10' AS DATE),
    'Umbrales: D_y_E=1.0, Materiales=1.0, MdeO=1.0, Equipos=1.0, Predecesora=0.5'

UNION ALL SELECT
    'ps_pac_expected', 'PAC esperado (baseline)',
    'Estimación basada en desempeño histórico, criticidad, restricciones, avance y CNC recientes',
    '0.25*PAC contratista + 0.20*PAC responsable + 0.15*criticidad + 0.20*restricciones + 0.10*avance + 0.10*CNC',
    'ForecastService::forecastPacExpected', 'bi_ps_compromisos, programacion_semanal, programa_consolidado',
    'project_id + Semana + row_id',
    'Activa=Si; muestra contratista>=3; muestra responsable>=3', '1.1', CAST('2026-07-10' AS DATE),
    'No proyecta cuando falta una variable obligatoria o evidencia histórica mínima.'

UNION ALL SELECT
    'ps_weekly_fulfillment', 'Productividad semanal',
    'Porcentaje de compromisos cumplidos (PAC=1) sobre compromisos activos totales',
    'SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END) / COUNT(*)', 'bi_ps_compromisos',
    'programacion_semanal', 'project_id + Semana',
    'Activa=Si, Es_TNP=0', '1.0', CAST('2026-07-10' AS DATE), NULL

UNION ALL SELECT
    'pi_restriction_pareto', 'Pareto de restricciones no liberadas',
    'Distribución de restricciones por tipo para actividades no listas',
    'COUNT(*) GROUP BY restriction_type WHERE is_ready=0 ORDER BY COUNT(*) DESC',
    'bi_pi_restricciones', 'programa_consolidado', 'project_id + Semana + restriction_type',
    'COALESCE(Titulo,0)=0, is_ready=0, is_hard=1', '1.0', CAST('2026-07-10' AS DATE), NULL

UNION ALL SELECT
    'pdc_at_risk', 'Paquetes PDC en riesgo',
    'Paquetes no listos para iniciar dentro de 6 semanas del corte semanal',
    'COUNT(*) WHERE listo_para_iniciar=0 AND fechaInicio <= Fecha_Fin_Sem/Fecha_Inicio_Sem + 6w',
    'bi_pdc_general', 'pdc, semanas_activas', 'project_id + semana + consecutivo',
    'titulo=0; corte=COALESCE(Fecha_Fin_Sem,Fecha_Inicio_Sem)', '1.1', CAST('2026-07-10' AS DATE), NULL

UNION ALL SELECT
    'cic_cal_integral', 'Calificación integral de contratista',
    'Score compuesto: PAC + Calidad + GSA + SST + ADM (promedio ponderado)',
    'PROMEDIO(PAC, Calidad, GSA, SST, ADM) — cálculo delegado a tabla cic',
    'bi_cic_contratistas', 'cic', 'project_id + Semana + subcontratista',
    NULL, '1.0', CAST('2026-07-10' AS DATE),
    'Pesos exactos dependen de la configuración en la tabla cic (no en esta view).'

UNION ALL SELECT
    'cic_aprobacion_status', 'Estado de aprobación del proveedor',
    'Clasificación basada en Cal_Integral: >=70 Aprobado, 50-69 Seguimiento, <50 No Aceptado',
    'CASE WHEN Cal_Integral>=70 THEN Aprobado WHEN >=50 THEN Seguimiento ELSE No Aceptado END',
    'bi_cic_contratistas', 'cic', 'project_id + Semana + subcontratista',
    NULL, '1.0', CAST('2026-07-10' AS DATE),
    'Umbrales definidos en docs/bi/risk-scoring.md. Revisables con datos reales.'

UNION ALL SELECT
    'cip_fulfillment_alert', 'Alerta de cumplimiento de responsable',
    'Responsable con PAC <50% o compromisos críticos incumplidos',
    'CASE WHEN PAC<0.5 OR critical_missed>0 THEN 1 ELSE 0', 'bi_cip_responsables',
    'cip, programacion_semanal', 'project_id + Semana + Responsable_AIA',
    NULL, '1.0', CAST('2026-07-10' AS DATE),
    'Solo cumplimiento. No incluye calidad, SST, GSA, ni ADM.'

UNION ALL SELECT
    'curva_s_desviacion', 'Desviación de la Curva S',
    'Diferencia entre % avance real y % avance teórico (ponderados por duración)',
    '(SUM(Ejecutado*duration_days)-SUM(teorico*duration_days))/SUM(duration_days)', 'bi_curva_s_duracion',
    'bi_pg_semana, programa_consolidado, semanas_activas', 'project_id + Semana',
    'COALESCE(Titulo,0)=0; duración inclusiva DATEDIFF+1', '1.1', CAST('2026-07-10' AS DATE),
    'El denominador global ponderado es la suma de duration_days válidos de todas las actividades del corte.'

UNION ALL SELECT
    'riesgo_score_100', 'Risk score (0-100)',
    'Suma ponderada: 35*prob + 25*impact + 20*urgency + 10*criticality + 10*confidence',
    'ROUND(35*p + 25*i + 20*u + 10*c + 10*d)', 'bi_riesgos',
    'bi_pg_semana, bi_cic_contratistas, bi_pdc_general, semanas_activas',
    'project_id + Semana + entity_type + entity_id',
    'Corte=COALESCE(Fecha_Fin_Sem,Fecha_Inicio_Sem)', 'RISK-SCORE-1.1', CAST('2026-07-10' AS DATE),
    'Fórmula calibrable. Los riesgos usan el corte de su semana; drivers obligatorios para scores >30.'
