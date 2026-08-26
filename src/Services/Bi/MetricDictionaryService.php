<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * Executable, code-first BI metric catalog.
 */
class MetricDictionaryService
{
    /**
     * Get the complete contract for one metric key.
     */
    public function getDefinition(string $metricKey): array
    {
        return self::catalog()[$metricKey] ?? [];
    }

    /**
     * Export every metric contract in stable catalog order.
     */
    public function exportDictionary(): array
    {
        return array_values(self::catalog());
    }

    /**
     * Export dictionary as a markdown table string.
     */
    public function exportAsMarkdown(): string
    {
        $md = "| Metric Key | Name | Definition | Formula | Source View | Grain | Version |\n";
        $md .= "|---|---|---|---|---|---|---|\n";
        foreach ($this->exportDictionary() as $metric) {
            $md .= sprintf(
                "| %s | %s | %s | %s | %s | %s | %s |\n",
                $metric['metric_key'],
                $metric['metric_name'],
                substr($metric['definition'], 0, 80),
                substr($metric['formula'], 0, 60),
                $metric['execution_source'],
                $metric['grain'],
                $metric['version'],
            );
        }
        return $md;
    }

    /** @return array<string, array<string, mixed>> */
    private static function catalog(): array
    {
        $catalog = [];

        $catalog['pg_activities_to_do'] = [
            'metric_key' => 'pg_activities_to_do',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Actividades en ventana Lookahead',
            'definition' => 'Actividades no terminadas dentro de la ventana Lookahead.',
            'formula' => 'COUNT(DISTINCT unique_id) WHERE Titulo=0 AND Semanas_Inicio BETWEEN 0 AND 6',
            'unit' => 'actividades',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Semanas_Inicio BETWEEN 0 AND 6', 'Ejecutado<1'],
            'aggregation_policy' => 'COUNT DISTINCT unique_id',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reports observed activity count.',
            'version' => '1.0',
            'known_limitations' => 'Excluye títulos y actividades terminadas.',
        ];

        $catalog['pg_activity_progress_contribution'] = [
            'metric_key' => 'pg_activity_progress_contribution',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Aporte de actividad al avance del programa',
            'definition' => 'Aporte ponderado de cada actividad al avance real, teórico y recuperable del corte.',
            'formula' => 'avance_actividad * duracion_calendario_inclusiva / suma_duraciones_del_alcance',
            'unit' => 'puntos porcentuales de avance',
            'execution_source' => 'ControlTowerService::programaProgressActivities',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana + unique_id',
            'cutoff_policy' => 'Último corte válido por proyecto dentro de la semana o rango solicitado.',
            'filters' => ['Titulo=0', 'Fecha_Inicio válida', 'Fecha_Fin válida', 'filtros BI activos'],
            'aggregation_policy' => 'Suma ponderada por duración calendario inclusiva; multiproyecto usa un denominador global del alcance.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; explica el avance observado y teórico al corte.',
            'version' => '1.0',
            'known_limitations' => 'La ponderación es temporal mientras no exista una fuente financiera reconciliable por actividad.',
        ];

        $catalog['pi_hard_restrictions_ready_rate'] = [
            'metric_key' => 'pi_hard_restrictions_ready_rate',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'intermedia',
            'metric_name' => 'Porcentaje de actividades listas en ventana',
            'definition' => 'Proporción de actividades Lookahead con restricciones duras cumplidas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Semanas_Inicio BETWEEN 0 AND 6', 'Ejecutado<1'],
            'aggregation_policy' => 'Ratio de sumas, no promedio de porcentajes.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reports current restriction readiness.',
            'version' => '1.0',
            'known_limitations' => 'D_y_E, Materiales, MdeO y Equipos requieren 1.0; Predecesora 0.5.',
        ];

        $catalog['ps_pac_expected'] = [
            'metric_key' => 'ps_pac_expected',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'semanal',
            'metric_name' => 'PAC esperado (baseline)',
            'definition' => 'Estimación transparente basada en desempeño histórico, criticidad, restricciones, avance y CNC recientes.',
            'formula' => '0.25*PAC contratista + 0.20*PAC responsable + 0.15*criticidad + 0.20*restricciones + 0.10*avance + 0.10*CNC',
            'unit' => 'porcentaje',
            'execution_source' => 'ForecastService::forecastPacExpected',
            'source_relations' => ['bi_ps_compromisos', 'programacion_semanal', 'programa_consolidado'],
            'grain' => 'project_id + Semana + row_id',
            'cutoff_policy' => 'Semana seleccionada; no se calcula fuera de su contexto.',
            'filters' => ['Activa=Si'],
            'aggregation_policy' => 'Score ponderado por compromiso; no se emite si falta una variable o una muestra mínima de 3 observaciones.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'Modelo estadístico reglado con variables obligatorias y muestra mínima explícita; sin sustituciones sintéticas.',
            'integration_status' => 'planned_for_programacion_semanal',
            'version' => '1.1',
            'known_limitations' => 'No proyecta cuando contratista o responsable tienen menos de 3 observaciones históricas.',
        ];

        $catalog['ps_weekly_fulfillment'] = [
            'metric_key' => 'ps_weekly_fulfillment',
            // Historial de estado_ejecucion (Task 3, 2026-08-26):
            //  1. 'descriptiva' -> 'en_paridad' (paso 3, corrigiendo el filtro de abajo).
            //  2. 'en_paridad' -> 'descriptiva' otra vez: el trinquete corrio y encontro que
            //     `MetricExecutor::execute()` no acotaba por `Semana` -- `MetricScope` solo cargaba
            //     `project_id`(s) y un `startDate`/`endDate` que `buildWhereClause()` nunca leia. El
            //     "camino nuevo" agregaba TODA la historia del proyecto (678 filas, obra 65) en vez
            //     de la semana pedida (24-80 filas), dando el mismo valor sin importar la semana.
            //     Vacio de Task 2 (`MetricExecutor`/`MetricScope`), fuera del catalogo — documentado
            //     y NO forzado. Anotado como entrada 4 (Parada) en la Bitacora del piloto del plan.
            //  3. 'descriptiva' -> 'en_paridad' de nuevo (commit def23b0b, aprobado por el
            //     controlador): `MetricScope` gano un cuarto parametro `?string $week`, y
            //     `MetricExecutor::buildWhereClause()` agrega `Semana = ?` cuando `$scope->week()`
            //     no es null. El arnes de paridad ahora construye el scope con la semana de cada
            //     iteracion (coincide con `cutoff_policy`: "Semana seleccionada; no se infiere con
            //     fecha del servidor" — no hay rango que resolver, la semana pedida ES el corte).
            //  4. 'en_paridad' -> 'descriptiva' una tercera vez: con el vacio de alcance ya resuelto,
            //     el trinquete SI corrio de verdad y paridad calza en 5 de las 6 combinaciones
            //     (obra, semana) reales -- 1 exacta + 4 dentro de una tolerancia de 0.005 declarada
            //     y justificada (cota matematica del `round()` a entero que ya usa `scorecardPS()`
            //     antes de mostrar el porcentaje). La sexta (obra 73, semana 2) no es un defecto de
            //     codigo: esa semana arranco AYER (`semanas_activas`: Fecha_Inicio_Sem 2026-08-25,
            //     Semanal_Confirmada=0, fechaCierreCompromisos=NULL — semana todavia abierta) y no
            //     tiene ningun PAC registrado aun. Ambos caminos coinciden en que el valor es
            //     indefinido (confirmado corriendo el SQL crudo: `SUM(PAC=1)` da NULL con la misma
            //     poblacion que usa `fetchSemanal()`), pero el arnes de rol A trata "no se puede
            //     comparar" como fallo SIEMPRE, incluso cuando los dos caminos concuerdan en null —
            //     ese es un diseño deliberado del ratchet (preferir fallo ruidoso a un acuerdo por
            //     casualidad), asi que no se toco. Pasa a 'ejecutable' solo cuando la sexta
            //     combinacion deje de bloquear: o bien la semana 2 de la obra 73 cierra con datos
            //     reales, o el controlador decide explicitamente tratar "ambos null" como paridad.
            //     Ver el reporte de Task 3 (rol B) para los valores exactos de las 6 combinaciones.
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'semanal',
            'metric_name' => 'Productividad semanal',
            'definition' => 'Porcentaje de compromisos activos cumplidos durante la semana.',
            'formula' => 'SUM(PAC=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_ps_compromisos',
            'source_relations' => ['programacion_semanal'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
            // Correccion CT-16 (2026-08-26, Task 3 paso 3): el catalogo declaraba
            // ['Activa=Si', 'Es_TNP=0'], pero la vista `bi_ps_compromisos` no tiene esas columnas
            // ni esos valores. Verificado contra la base de dev: `Activa` es varchar(3) con valores
            // reales '0'/'1'/'NA' (nunca 'Si'/'No'), y la vista expone `ps.Es_TNP AS is_TNP` — el
            // nombre real de columna en la vista es `is_TNP`, no `Es_TNP` (que si existe en la
            // tabla base `programacion_semanal`, de ahi la confusion). El valor de comparacion de
            // `Activa` va entre comillas para forzar comparacion de string: sin comillas, MySQL
            // compara varchar contra int convirtiendo la columna a numero, y 'NA' se convierte a 0
            // (no-numerico), lo que excluiria incorrectamente las filas 'NA' del universo activo.
            // Esta correccion es real y se conserva aunque la metrica no avance de estado: es lo
            // que corresponde documentar segun el veredicto del controlador (CT-16), independiente
            // de que el gap de abajo bloquee el trinquete de paridad.
            'filters' => ["Activa!='0'", 'is_TNP=0'],
            'aggregation_policy' => 'Ratio de compromisos cumplidos sobre activos.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reports registered PAC.',
            'version' => '1.0',
            'known_limitations' => 'Depende de que los compromisos activos tengan PAC registrado — '
                . 'una semana abierta sin PAC registrado da un valor indefinido (null), no 0%. '
                . 'Paridad calza en 5/6 combinaciones (obra, semana) reales con tolerancia 0.005 '
                . '(ver nota en estado_ejecucion); la sexta esta bloqueada por falta de datos de '
                . 'una semana aun abierta en la obra piloto, no por un defecto de codigo.',
        ];

        $catalog['pg_radar_productividad'] = [
            'metric_key' => 'pg_radar_productividad',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Radar: Avance promedio',
            'definition' => 'Proxy de productividad: avance promedio de P_Completado con población válida independiente; el sobrecumplimiento conserva la fila y limita su aporte al eje a 100%.',
            'formula' => 'PROMEDIO(MIN(P_Completado válido, 1)) × 100',
            'unit' => 'porcentaje',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal'],
            'grain' => 'project_id + Semana + row_id',
            'cutoff_policy' => 'Semana seleccionada o semanas contenidas en el rango explícito.',
            'filters' => ["Activa IN ('1','NA')", 'Es_TNP<>1', 'P_Completado>=0'],
            'aggregation_policy' => 'Suma global de valores válidos / conteo global de valores válidos; no promedia porcentajes de proyecto.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; requiere mínimo 3 registros válidos.',
            'version' => '2.1',
            'known_limitations' => 'P_Completado nulo o negativo se excluye. Valores superiores a 1 permanecen en la muestra y aportan máximo 1 al eje; el detalle conserva el valor bruto.',
        ];

        $catalog['pg_radar_eficiencia'] = [
            'metric_key' => 'pg_radar_eficiencia',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Radar: Eficiencia',
            'definition' => 'Eficiencia de ejecución por fila, sin sumar compromisos ni ejecutados de unidades incompatibles.',
            'formula' => 'PROMEDIO(Ejecutado_Real / Compromiso por fila válida) × 100',
            'unit' => 'porcentaje',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal'],
            'grain' => 'project_id + Semana + row_id',
            'cutoff_policy' => 'Semana seleccionada o semanas contenidas en el rango explícito.',
            'filters' => ["Activa IN ('1','NA')", 'Es_TNP<>1', 'Compromiso>0', 'Ejecutado_Real>=0'],
            'aggregation_policy' => 'Suma global de ratios válidos / conteo global de ratios válidos; valor bruto puede superar 100 y display se limita a 100.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; requiere mínimo 3 ratios válidos.',
            'version' => '2.0',
            'known_limitations' => 'Las filas sin compromiso positivo o con ejecución negativa se excluyen.',
        ];

        $catalog['pg_radar_desempeno'] = [
            'metric_key' => 'pg_radar_desempeno',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Radar: Desempeño PAC',
            'definition' => 'Proporción de compromisos con PAC registrado como cumplido.',
            'formula' => 'COUNT(PAC=1) / COUNT(PAC IN (0,1)) × 100',
            'unit' => 'porcentaje',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal'],
            'grain' => 'project_id + Semana + row_id',
            'cutoff_policy' => 'Semana seleccionada o semanas contenidas en el rango explícito.',
            'filters' => ["Activa IN ('1','NA')", 'Es_TNP<>1', 'PAC IN (0,1)'],
            'aggregation_policy' => 'Numerador PAC=1 y denominador global PAC válido; no promedia porcentajes de proyecto.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; requiere mínimo 3 PAC válidos.',
            'version' => '2.0',
            'known_limitations' => 'PAC nulo o diferente de 0/1 se excluye, no se interpreta como incumplido.',
        ];

        $catalog['pg_finish_variance_days_p50'] = [
            'metric_key' => 'pg_finish_variance_days_p50',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Variación probable de fecha final P50',
            'definition' => 'Diferencia en días calendario entre la fecha final P50 simulada y la fecha final contractual del alcance filtrado.',
            'formula' => 'DATEDIFF(forecast_finish_p50, contractual_finish)',
            'unit' => 'días calendario',
            'execution_source' => 'ControlTowerService::programaDelayForecast',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'portafolio filtrado al corte; detalle por project_id',
            'cutoff_policy' => 'Último corte disponible por proyecto dentro de la semana o rango seleccionado.',
            'filters' => ['Titulo=0', 'proyecto', 'semana o rango', 'Sub-Contratista', 'Responsable AIA', 'Etapa'],
            'aggregation_policy' => 'Máxima fecha de terminación por simulación entre proyectos; luego percentiles P10, P50 y P90.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'Monte Carlo de curva S con 240 simulaciones y mínimo 3 incrementos positivos por proyecto; P10-P90 representa rango probable 80%.',
            'version' => '2.0',
            'known_limitations' => 'No se publica si un proyecto del alcance carece de línea contractual o de historia mínima; el fin contractual ignora desde/hasta pero conserva los demás filtros.',
        ];

        $catalog['pg_observed_activity_delay_days'] = [
            'metric_key' => 'pg_observed_activity_delay_days',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Retraso observado por actividad',
            'definition' => 'Días calendario transcurridos desde el fin planificado hasta el corte propio del proyecto para actividades vencidas e incompletas.',
            'formula' => 'MAX(0, DATEDIFF(project_cutoff, Fecha_Fin)) WHERE Titulo=0 AND Ejecutado<1 AND Fecha_Fin<project_cutoff',
            'unit' => 'días calendario por actividad',
            'execution_source' => 'ControlTowerService::programaObservedDelayPayload',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana + unique_id',
            'cutoff_policy' => 'Corte efectivo de cada proyecto, no el máximo global del portafolio.',
            'filters' => ['Titulo=0', 'Ejecutado<1', 'Fecha_Fin válida y anterior al corte', 'proyecto', 'semana o rango', 'Sub-Contratista', 'Responsable AIA', 'Etapa'],
            'aggregation_policy' => 'Conteo, suma y máximo por actividad; conserva project_id y no promedia máximos entre proyectos.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; describe retraso ya observado al corte.',
            'version' => '1.0',
            'known_limitations' => 'Los días de actividades paralelas no equivalen a días de retraso del proyecto; la implicación sobre fecha final depende de criticidad y lógica de red.',
        ];

        $catalog['pg_cnp_activity_count'] = [
            'metric_key' => 'pg_cnp_activity_count',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Actividades con Causa de No Programación',
            'definition' => 'Actividades que quedaron fuera del compromiso semanal y tienen una CNP registrada.',
            'formula' => "COUNT(DISTINCT project_id, Semana, Consecutivo) WHERE Activa='0' AND TRIM(CNP)<>''",
            'unit' => 'actividades no programadas',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal', 'semanas_activas'],
            'grain' => 'project_id + Semana + Consecutivo',
            'cutoff_policy' => 'Fin de la semana de cada proyecto en semanas_activas; el rango explícito reemplaza la semana.',
            'filters' => ["Activa='0'", "TRIM(CNP)<>''", 'proyecto', 'semana o rango', 'Sub-Contratista', 'Responsable AIA', 'Actividad o Ubicacion'],
            'aggregation_policy' => 'Conteo global de filas únicas conservando project_id; no deduplica Consecutivo entre proyectos ni promedia porcentajes.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; prioriza criticidad, inicio vencido o próximo y responsables faltantes al corte.',
            'version' => '1.0',
            'last_updated' => '2026-07-14 00:00:00',
            'known_limitations' => 'El filtro Etapa es búsqueda textual en Actividad/Ubicacion. El módulo CNP operativo aún no consume enlaces profundos por proyecto, semana y actividad.',
        ];

        $catalog['pg_cnc_activity_count'] = [
            'metric_key' => 'pg_cnc_activity_count',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'programa-general',
            'metric_name' => 'Actividades con Causa de No Cumplimiento',
            'definition' => 'Compromisos semanales activos o no aplicables que conservan una CNC registrada y explican una ejecución inferior a lo comprometido.',
            'formula' => "COUNT(DISTINCT project_id, Semana, Consecutivo) WHERE Activa IN ('1','NA') AND TRIM(CNC)<>''",
            'unit' => 'actividades con incumplimiento documentado',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal', 'semanas_activas'],
            'grain' => 'project_id + Semana + Consecutivo',
            'cutoff_policy' => 'Fin de la semana de cada proyecto en semanas_activas; el rango explícito reemplaza la semana.',
            'filters' => ["Activa IN ('1','NA')", "TRIM(CNC)<>''", 'proyecto', 'semana o rango', 'Sub-Contratista', 'Responsable AIA', 'Actividad o Ubicacion'],
            'aggregation_policy' => 'Conteo global de actividades únicas. Las categorías usan ese mismo universo; el cumplimiento medio es el promedio simple del porcentaje por actividad con compromiso válido.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; prioriza criticidad, magnitud de la brecha, ejecución nula y responsables faltantes.',
            'version' => '1.0',
            'last_updated' => '2026-07-14 00:00:00',
            'known_limitations' => 'Las cantidades de unidades diferentes no se suman. El promedio de cumplimiento pondera cada actividad por igual y la implicación sobre fecha final sigue siendo cualitativa.',
        ];

        $catalog['pi_restriction_pareto'] = [
            'metric_key' => 'pi_restriction_pareto',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'intermedia',
            'metric_name' => 'Pareto de restricciones no liberadas',
            'definition' => 'Distribución de restricciones duras no liberadas por tipo.',
            'formula' => 'COUNT(*) GROUP BY restriction_type WHERE is_ready=0 ORDER BY COUNT(*) DESC',
            'unit' => 'restricciones',
            'execution_source' => 'bi_pi_restricciones',
            'source_relations' => ['programa_consolidado'],
            'grain' => 'project_id + Semana + restriction_type',
            'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
            'filters' => ['Titulo=0', 'is_ready=0', 'is_hard=1'],
            'aggregation_policy' => 'COUNT por tipo de restricción.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; ranks open restrictions.',
            'version' => '1.0',
            'known_limitations' => 'Depende de la clasificación de restricciones en programa consolidado.',
        ];

        $catalog['pdc_at_risk'] = [
            'metric_key' => 'pdc_at_risk',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'pdc',
            'metric_name' => 'Pasos de contratación vencidos',
            'definition' => 'Pasos de contratación pendientes cuya fecha programada ya pasó, por destino (paquete + lote).',
            'formula' => 'COUNT(*) WHERE fecha_real IS NULL AND fecha_fin < hoy',
            'unit' => 'pasos',
            'execution_source' => 'pdc_plan_paso',
            'source_relations' => ['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_subpaquete'],
            'grain' => 'project_id + paquete_id + subpaquete_id (destino)',
            // Fase B3: la fecha de corte NO es la semana seleccionada. Es hoy, puesta por el
            // servidor, para que este panel y la pestaña del módulo no discrepen el mismo día.
            'cutoff_policy' => 'Fecha de hoy del servidor; el selector de semana no aplica a esta métrica.',
            'filters' => ['fecha_real IS NULL', 'paquete activo'],
            'aggregation_policy' => 'COUNT de pasos pendientes; la unidad de destino es paquete + lote.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'Ventana determinística de seis semanas desde el corte.',
            'version' => '1.0',
            'known_limitations' => 'Un paquete sin fechas programadas no puede vencer: el panel declara aparte cuántos no está mirando.',
        ];

        $catalog['cic_cal_integral'] = [
            'metric_key' => 'cic_cal_integral',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'cic',
            'metric_name' => 'Calificación integral de contratista',
            'definition' => 'Score ponderado de PAC, Calidad, GSA, SST y ADM.',
            'formula' => 'PROMEDIO(PAC, Calidad, GSA, SST, ADM) configurado en cic',
            'unit' => 'score',
            'execution_source' => 'bi_cic_contratistas',
            'source_relations' => ['cic'],
            'grain' => 'project_id + Semana + subcontratista',
            'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
            'filters' => [],
            'aggregation_policy' => 'Promedio ponderado según configuración CIC.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reports configured score.',
            'version' => '1.0',
            'known_limitations' => 'Los pesos exactos pertenecen a la configuración CIC.',
        ];

        $catalog['cic_aprobacion_status'] = [
            'metric_key' => 'cic_aprobacion_status',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'cic',
            'metric_name' => 'Estado de aprobación del proveedor',
            'definition' => 'Clasificación por umbrales de la calificación integral.',
            'formula' => 'Cal_Integral>=70 Aprobado; >=50 Seguimiento; <50 No Aceptado',
            'unit' => 'estado',
            'execution_source' => 'bi_cic_contratistas',
            'source_relations' => ['cic'],
            'grain' => 'project_id + Semana + subcontratista',
            'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
            'filters' => [],
            'aggregation_policy' => 'Clasificación individual; conteos por estado cuando se agregue.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; classifies observed score.',
            'version' => '1.0',
            'known_limitations' => 'Umbrales revisables con datos reales.',
            'completeness_inherits_from' => 'cic_cal_integral',
        ];

        $catalog['cip_fulfillment_alert'] = [
            'metric_key' => 'cip_fulfillment_alert',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'cip',
            'metric_name' => 'Alerta de cumplimiento de responsable',
            'definition' => 'Alerta cuando PAC es inferior a 50% o hay críticos incumplidos.',
            'formula' => 'PAC<0.5 OR critical_missed>0',
            'unit' => 'alerta',
            'execution_source' => 'bi_cip_responsables',
            'source_relations' => ['cip', 'programacion_semanal'],
            'grain' => 'project_id + Semana + Responsable_AIA',
            'cutoff_policy' => 'Semana seleccionada; no se infiere con fecha del servidor.',
            'filters' => [],
            'aggregation_policy' => 'Indicador booleano por responsable.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; flags current fulfillment risk.',
            'version' => '1.0',
            'known_limitations' => 'No incluye Calidad, SST, GSA ni ADM.',
        ];

        $catalog['curva_s_desviacion'] = [
            'metric_key' => 'curva_s_desviacion',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'curva-s',
            'metric_name' => 'Desviación de la Curva S',
            'definition' => 'Diferencia entre avance real y teórico ponderados por duración.',
            'formula' => 'pct_avance_real - pct_avance_teorico',
            'unit' => 'puntos porcentuales',
            'execution_source' => 'bi_curva_s_duracion',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0'],
            'aggregation_policy' => 'Promedio ponderado por duración.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'Curva teórica determinística al corte seleccionado.',
            'version' => '1.0',
            'known_limitations' => 'Pondera por duración, no por número de actividades.',
        ];

        $catalog['riesgo_score_100'] = [
            'metric_key' => 'riesgo_score_100',
            'estado_ejecucion' => 'descriptiva',
            'report_key' => 'riesgos',
            'metric_name' => 'Risk score (0-100)',
            'definition' => 'Score de riesgo ponderado por probabilidad, impacto y drivers operativos.',
            'formula' => 'ROUND(35*p + 25*i + 20*u + 10*c + 10*d)',
            'unit' => 'score',
            'execution_source' => 'bi_riesgos',
            'source_relations' => ['bi_pg_semana', 'bi_cic_contratistas'],
            'grain' => 'project_id + Semana + entity_type + entity_id',
            'cutoff_policy' => 'Semana o rango explícitamente seleccionado por el reporte.',
            'filters' => [],
            'aggregation_policy' => 'Score por entidad; no promediar sin contexto de exposición.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; score determinístico versionado.',
            'version' => 'RISK-SCORE-1.0',
            'known_limitations' => 'Fórmula calibrable; drivers obligatorios para scores superiores a 30.',
        ];

        return $catalog;
    }
}
