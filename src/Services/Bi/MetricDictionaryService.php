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
            // CT-16 (Task 3 paso 5, batch 1, 2026-08-26): investigado antes de mover de estado.
            // (a) `execution_source: bi_pg_semana` es correcto y se conserva -- podria confundirse
            //     con `scorecardPI()` (report_key 'intermedia' en vivo, "% Restricciones listas"),
            //     pero esa es OTRA metrica: opera sobre `bi_pi_restricciones` a grano de
            //     RESTRICCION (is_hard/is_ready por restriccion), mientras esta metrica -- formula,
            //     filtros y known_limitations coherentes entre si -- opera a grano de ACTIVIDAD
            //     (`hard_restrictions_ready` por actividad Lookahead), igual que
            //     `activities_can_do_count` en `fetchOverview()`. No son intercambiables: se dejo
            //     `bi_pg_semana` tal como esta.
            // (b) El filtro `'Semanas_Inicio BETWEEN 0 AND 6'` no es parseable por
            //     `MetricExecutor::parseFilter()` (solo reconoce =,>=,<=,!=,>,<, no BETWEEN).
            //     Semanas_Inicio es `int` (verificado con SHOW COLUMNS), asi que se reemplaza por
            //     dos filtros equivalentes: `Semanas_Inicio>=0` y `Semanas_Inicio<=6` -- mismo
            //     universo, sin cambiar la definicion.
            // (c) Verificado que `bi_pg_semana` (vista, `database/bi/001_bi_pg_semana.sql`) y
            //     `ControlTowerService::programaGeneralDirectSelect()` calculan
            //     `hard_restrictions_ready` con el MISMO CASE de 5 condiciones (D_y_E, Materiales,
            //     MdeO, Equipos >=1.0, Predecesora >=0.5) -- no hay drift entre la vista y el
            //     camino en vivo.
            // Paridad: 6/6 combinaciones (obra, semana) reales, delta EXACTO 0 en las 6 -- la vista
            // reproduce la tabla base al bit. 'ejecutable': no existe un metodo dedicado que
            // publicara esta razon como KPI aislado (nunca se mostro como % en produccion, solo
            // como dos conteos crudos dentro de `fetchOverview()`), asi que no hay "SQL viejo" que
            // borrar en el sentido estricto del brief -- se documenta en
            // `$oldMethodRetainedByMetric` del arnes por que `programaGeneralDirectSelect()` sigue
            // existiendo (alimenta TODO el reporte 'programa-general', no solo esta metrica).
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'intermedia',
            'metric_name' => 'Porcentaje de actividades listas en ventana',
            'definition' => 'Proporción de actividades Lookahead con restricciones duras cumplidas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Semanas_Inicio>=0', 'Semanas_Inicio<=6', 'Ejecutado<1'],
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
            //  5. 'descriptiva' -> 'en_paridad' -> 'ejecutable': rol A ajusto el arnes para que
            //     "ambos caminos concuerdan en null" cuente como paridad (rama nueva en
            //     tests/test_bi_paridad_metricas.php, distinta del caso asimetrico que sigue
            //     fallando fuerte). Con eso, las 6 combinaciones (obra, semana) reales calzan: 5
            //     numericas (1 exacta + 4 dentro de tolerancia 0.005) + 1 de "sin dato, ambos
            //     caminos concuerdan". Primera metrica del trinquete en llegar a `ejecutable`.
            //     `ControlTowerService::scorecardPS()` NO se borro -- sigue siendo la unica fuente
            //     de otros 3 KPIs en vivo (`Compromisos activos`, `En riesgo`, `CNC esta semana`)
            //     sin cobertura en el catalogo; borrarlo entero habria roto el reporte 'semanal'
            //     real de `BiControlTowerApiController`. El paso 4 del brief ("borrar su SQL de
            //     ControlTowerService") se cumple parcialmente por diseno: la metrica queda
            //     `ejecutable` en el catalogo -- MetricExecutor es la fuente de verdad para PAC de
            //     aqui en adelante -- pero el metodo viejo sigue existiendo porque todavia le sirve
            //     a otras 3 metricas sin catalogar. Ver el reporte de Task 3 (rol B) para el detalle.
            'estado_ejecucion' => 'ejecutable',
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
            // Fix ronda 1 (2026-08-26): last_updated estaba ausente -- LineageService.php:82
            // publicaba el default '2026-07-10' para una metrica cuya formula y filtros cambiaron
            // hoy (correccion CT-16 de filtros + reescritura del resolver de paridad a delta
            // exacto). No sube `version`: el contrato semantico del ratio (SUM(PAC=1)/COUNT(*),
            // compromisos activos cumplidos sobre activos) no cambio, solo la expresion SQL y como
            // se verifica -- mismo criterio que se documenta junto a `estado_ejecucion` arriba.
            'last_updated' => '2026-08-26 00:00:00',
            'known_limitations' => 'Depende de que los compromisos activos tengan PAC registrado — '
                . 'una semana abierta sin PAC registrado da un valor indefinido (null), no 0%. '
                . 'Ejecutable con paridad EXACTA (delta 0) en 6/6 combinaciones (obra, semana) '
                . 'reales (Fix ronda 1, 2026-08-26): 5 numericas exactas + 1 de "ambos caminos '
                . 'concuerdan en sin dato" (obra 73, semana 2 -- semana recien abierta sin ningun '
                . 'PAC registrado; el arnes de paridad cuenta el acuerdo en null como paridad, no '
                . 'como discrepancia, por ruling del controlador). Ya no hace falta tolerancia '
                . 'declarada: la version anterior de esta nota citaba 5/6 con tolerancia 0.005 '
                . 'porque el resolver de paridad comparaba contra el KPI ya redondeado por '
                . 'scorecardPS() -- corregido para leer el ratio sin redondear de la misma consulta '
                . 'cruda, ver tests/test_bi_paridad_metricas.php.',
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
            // Task 3 paso 5, tanda 2 (2026-08-26). CT-16, investigado antes de mover de estado:
            // (a) `filters` originales (`Activa IN ('1','NA')`, `Es_TNP<>1`, `PAC IN (0,1)`) usan
            //     IN/<> que `MetricExecutor::parseFilter()` no reconoce (solo =,>=,<=,!=,>,<).
            //     Verificado contra la base COMPLETA (no solo las dos obras de prueba), igual que
            //     el ruling anterior de `ps_weekly_fulfillment`:
            //       - `Activa` solo toma '0'/'1'/'NA' en toda la tabla -> `Activa!='0'` es
            //         equivalente a `IN ('1','NA')` sin necesitar soporte de IN.
            //       - `Es_TNP` (tinyint(1) NOT NULL) es 0 en las 5721 filas de la tabla completa,
            //         nunca 1 -> `Es_TNP<>1` es equivalente a `Es_TNP=0`.
            //       - `PAC` (int NULL) solo toma NULL/0/1 en toda la tabla (3487 NULL, 1269 con 1,
            //         965 con 0) -> `PAC>=0` excluye NULL (comparacion SQL contra NULL es
            //         desconocida, la fila se descarta) e incluye 0 y 1 -> equivalente a
            //         `PAC IN (0,1)` sin necesitar soporte de IN.
            //     Filtros corregidos: `["Activa!='0'", 'Es_TNP=0', 'PAC>=0']`.
            // (b) `formula` original (`COUNT(PAC=1) / COUNT(PAC IN (0,1)) × 100`) no calza con el
            //     patron `SUM(expr) / COUNT(*)` que reconoce `buildSelectExpression()`. Con el
            //     filtro `PAC>=0` ya restringiendo la poblacion a PAC IN (0,1), `COUNT(*)` del
            //     ejecutor equivale a `COUNT(PAC IN (0,1))`, y `SUM(PAC=1)` equivale a
            //     `COUNT(PAC=1)` (PAC solo toma 0 o 1 en esa poblacion) -> formula reescrita a
            //     `SUM(PAC=1) / COUNT(*)`, en escala 0-1 (no x100), igual que las dos metricas ya
            //     migradas (`ps_weekly_fulfillment`, `pi_hard_restrictions_ready_rate`).
            // (c) Verificado contra `ControlTowerService::programaRadar()` (metodo real de
            //     produccion, invocado via `getProgramaRadarDetail()` en el resolver del arnes, NO
            //     una reconstruccion propia de SQL): eje 'desempeno' usa `radarPacValue()`
            //     (`$pac === 0.0 || $pac === 1.0 ? $pac : null`) sumado y contado por fila elegible
            //     -- exactamente `SUM(PAC=1)/COUNT(PAC IN (0,1))` sobre la poblacion
            //     Activa!='0' AND Es_TNP=0. Paridad: 6/6 combinaciones (obra, semana) reales, delta
            //     EXACTO 0 en las 5 con datos (PAC solo toma 0/1, sin redondeo de por medio) + 1 de
            //     "sin dato, ambos caminos concuerdan" (obra 73, semana 2 -- misma semana recien
            //     abierta sin PAC registrado que ya afecto a `ps_weekly_fulfillment`).
            // 'ejecutable': `programaRadar()` calcula los 3 ejes del radar (productividad,
            // eficiencia, desempeno) en una sola pasada sobre la poblacion -- no se puede borrar
            // sin romper los otros 2 ejes, que quedan estructuralmente bloqueados para
            // MetricExecutor (ver known_limitations) y no tienen todavia forma ejecutable. Motivo
            // completo en `$oldMethodRetainedByMetric` del arnes.
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'programa-general',
            'metric_name' => 'Radar: Desempeño PAC',
            'definition' => 'Proporción de compromisos con PAC registrado como cumplido.',
            'formula' => 'SUM(PAC=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal'],
            'grain' => 'project_id + Semana + row_id',
            'cutoff_policy' => 'Semana seleccionada o semanas contenidas en el rango explícito.',
            'filters' => ["Activa!='0'", 'Es_TNP=0', 'PAC>=0'],
            'aggregation_policy' => 'Numerador PAC=1 y denominador global PAC válido; no promedia porcentajes de proyecto.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; requiere mínimo 3 PAC válidos.',
            'version' => '2.0',
            // Fix ronda 1 (2026-08-26): last_updated estaba ausente -- LineageService.php:82
            // publicaba el default '2026-07-10' para una metrica cuya formula y filtros se
            // reescribieron hoy mismo (tanda 2). No sube `version`: el contrato semantico del eje
            // 'desempeno' (SUM(PAC=1)/COUNT(*) sobre compromisos activos con PAC registrado) no
            // cambio, es la primera vez que se declara en forma ejecutable.
            'last_updated' => '2026-08-26 00:00:00',
            'known_limitations' => 'PAC nulo o diferente de 0/1 se excluye, no se interpreta como '
                . 'incumplido. Paridad exacta (delta 0) en 6/6 combinaciones (obra, semana) reales '
                . '(Task 3, tanda 2, 2026-08-26): 5 numericas + 1 de acuerdo en "sin dato". '
                . 'MetricExecutor no aplica el "minimo 3 PAC validos" de forecast_policy -- ese es un '
                . 'umbral de PRESENTACION de `programaRadarAxis()` (oculta el valor si la muestra es '
                . 'chica), no una condicion del calculo del ratio en si; con menos de 3 observaciones '
                . 'el ratio sigue siendo matematicamente valido, solo no se muestra en el radar '
                . 'legado. Los ejes "productividad" y "eficiencia" del mismo radar NO se migraron en '
                . 'esta tanda: "productividad" necesita capar cada fila a maximo 1.0 antes de sumar '
                . '(`MIN(P_Completado,1)`), y "eficiencia" necesita promediar un ratio POR FILA '
                . '(`Ejecutado_Real/Compromiso`), y `MetricExecutor` solo sabe construir '
                . '`SUM(columna_simple)/COUNT(*)` -- ninguna de las dos operaciones es expresable en '
                . 'esa gramatica sin extender el ejecutor (verificado con datos reales: capar '
                . 'P_Completado SI cambia el resultado -- 2 a 4 filas por semana en la obra 65 '
                . 'superan 1.0, hasta P_Completado=14). Documentado como hallazgo estructural, no '
                . 'forzado (CT-16). Fix ronda 1 (2026-08-26), senalado por el revisor: el endpoint en '
                . 'vivo (programa-general-radar-detail, ControlTowerService.php:3311) publica su '
                . "propio summary.formula como 'COUNT(PAC=1) / COUNT(PAC IN (0,1)) × 100.' -- NO se "
                . 'toco ControlTowerService.php (regla de toda la task), asi que ese texto sigue vivo '
                . 'y distinto, letra por letra, del `formula` de este catalogo. Aclaracion para que '
                . 'las dos descripciones no se lean como contradictorias: son la MISMA cifra en dos '
                . 'formas -- el denominador "PAC IN (0,1)" del endpoint es la misma calificacion que '
                . 'expresan los filtros de este catalogo (Activa!=\'0\', Es_TNP=0, PAC>=0; PAC>=0 '
                . 'excluye NULL e incluye 0/1, verificado equivalente a PAC IN (0,1) arriba), y el '
                . '×100 del endpoint es solo su escala de PRESENTACION (0-100) frente a la escala '
                . '0-1 que usa `MetricResult::value()` y el resto de metricas ejecutable de este '
                . 'catalogo. El `formula` de este catalogo se mantiene como '
                . "'SUM(PAC=1) / COUNT(*)' -- unica forma que "
                . '`MetricExecutor::buildSelectExpression()` reconoce (regex ancorado, no admite '
                . 'texto aclaratorio dentro del campo sin romper la ejecucion) -- la reconciliacion '
                . 'vive aqui, no en el campo `formula`.',
        ];

        $catalog['pg_finish_variance_days_p50'] = [
            'metric_key' => 'pg_finish_variance_days_p50',
            // Fix ronda 2 (2026-08-26), decision explicita para cuando alguien migre esta metrica
            // (no se migra en este fix): su `grain` no menciona "Semana" a proposito, y NO debe
            // recibir `MetricScope::week()` (igualdad `Semana = ?`) el dia que se ejecute con
            // MetricExecutor. Verificado contra `ControlTowerService::programaDelayForecast()` y
            // `fetchProgramaGeneralForecastTrend()` (linea ~1279): "semana" aqui selecciona el
            // CORTE (cutoff) de cada proyecto hasta el cual se toma su historia completa de
            // snapshots (`fetchProgramaGeneralTrend($projectIds, '', ...)` -- semana vacia a
            // proposito, filtrado despues por `cutoff <= $cutoffs[$projectId]`) para alimentar el
            // Monte Carlo de 240 corridas. Filtrar por `Semana = ?` restringiria la entrada del
            // Monte Carlo a las filas de UNA sola semana en vez de "toda la historia hasta el
            // corte de esa semana", rompiendo la simulacion por falta de muestra -- no es un error
            // tecnico de columna faltante (como `pdc_at_risk`), es un error de semantica. La forma
            // correcta de expresar este corte es un limite de RANGO (`cutoff <= X`), mas cercano al
            // `startDate`/`endDate` que ya existe en `MetricScope` (hoy sin consumir en
            // `buildWhereClause()`) que a `week()`. Ver el mismo razonamiento junto a
            // `metricScopeUsaSemana()` en tests/test_bi_paridad_metricas.php.
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
            // Task 3 cierre (2026-08-26), investigado por ruling del controlador (Bitacora entrada
            // 6): esta metrica es, por su propia definicion, un DESGLOSE por tipo de restriccion
            // (una distribucion de N filas -- una por restriction_type), no un escalar unico.
            // Confirmado con datos reales (obra 65, semana 25): 5 filas
            // (Predecesora=437, Materiales=354, Equipos=343, D_y_E=338, MdeO=324), no un numero.
            // `MetricExecutor::execute()` esta arquitectonicamente atado a un escalar: hace UN
            // `->fetch()` (no `fetchAll()`) y `MetricResult::value()` es `float|null`, nunca una
            // lista. Ademas, ni `aggregation_policy` ('COUNT por tipo de restriccion.') ni
            // `formula` ('COUNT(*) GROUP BY restriction_type WHERE is_ready=0 ORDER BY COUNT(*)
            // DESC') calzan con el unico patron que reconoce `buildSelectExpression()`
            // (`SUM(expr_simple)/COUNT(*)`) -- confirmado ejecutando `MetricExecutor::execute()`
            // directo: `RuntimeException: ni 'aggregation_policy' ni 'formula' tienen una forma SQL
            // reconocida`. Esta es una TERCERA categoria de vacio estructural, distinta de las 3 ya
            // documentadas en la tanda anterior (conteo puro, razon por fila, valor capado): aqui
            // ni siquiera el CONTRATO de salida (un solo numero) es el correcto -- el motor tendria
            // que devolver una lista, no una expresion nueva. No es un fix acotado tipo
            // `MetricScope::week()`; es cambiar la forma de `MetricResult` para un caso, o -- mas
            // consistente con como ya se sirven paretos/listas en el resto del sistema -- que Task 7
            // (la hoja de Intermedia) consuma esta distribucion directo de `bi_pi_restricciones`
            // como lista, sin pasar por el ejecutor de escalares. No se forzo (CT-16); no se toco
            // `MetricExecutor.php`. Se queda `descriptiva`.
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
            'known_limitations' => 'Depende de la clasificación de restricciones en programa '
                . 'consolidado. Es una DISTRIBUCION por restriction_type (N filas), no un escalar -- '
                . 'no encaja en el contrato de MetricExecutor::execute() (un solo valor float|null). '
                . 'Verificado con datos reales (obra 65, semana 25): 5 filas, no un numero. Task 3, '
                . 'cierre (2026-08-26): no se migra con el ejecutor actual; candidata a servirse '
                . 'directo como lista en Task 7 (hoja de Intermedia), no via este motor de escalares.',
        ];

        // D58 (Ola 1, Torre de Control piloto, Task 7 paso 3-bis): semaforo por semanas para
        // iniciar del lienzo de Intermedia (CT-8.3 punto 4). Cuatro franjas por urgencia segun
        // `Semanas_Inicio` -- semana 0 (listas para iniciar ya), 1-2, 3-4, 5-6 -- cada una
        // contando actividades del lookahead (`Titulo=0`, `Ejecutado<1`) que caen en esa ventana,
        // sobre `bi_pg_semana` (grano de ACTIVIDAD, igual que `pi_hard_restrictions_ready_rate` y
        // `pg_activities_to_do`), no `bi_pi_restricciones` (grano de RESTRICCION, que multiplicaria
        // el conteo). Rangos con comparaciones simples (`>=`/`<=`/`=`), nunca BETWEEN -- mismo
        // criterio de la correccion CT-16 ya aplicada a `pi_hard_restrictions_ready_rate`, porque
        // `MetricExecutor::parseFilter()` no reconoce BETWEEN.
        //
        // Las 4 nacen 'ejecutable' -- correccion posterior al analisis original (ver
        // tests/unit/MetricCatalogSemaforoTest.php, docblock actualizado, y
        // tests/test_bi_semaforo_franjas.php). El analisis previo asumia que el numerador tenia
        // que expresar la franja como rango compuesto (`Semanas_Inicio>=X AND Semanas_Inicio<=Y`),
        // y ese rango en efecto NUNCA cabe en `NUMERATOR_EXPRESSION_PATTERN` (solo admite UNA
        // comparacion simple). La correccion es que el numerador no tiene por que expresar la
        // franja: la franja ya vive en `filters` (WHERE), que admite varias comparaciones simples
        // encadenadas. El numerador queda fijo en `hard_restrictions_ready=1` (comparacion simple,
        // igual patron que `pi_hard_restrictions_ready_rate`), y la fraccion resultante es "listas
        // por franja / todas las de la franja" -- una metrica distinta a la que se penso primero
        // (conteo de actividades en la ventana), pero ejecutable con el motor tal cual esta, sin
        // tocar `MetricExecutor.php`.
        $catalog['pi_semaforo_semana_0'] = [
            'metric_key' => 'pi_semaforo_semana_0',
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'intermedia',
            'metric_name' => 'Semáforo semanas para iniciar: semana 0 (listas ya)',
            'definition' => 'Proporción de actividades del lookahead con inicio en la semana 0 que '
                . 'ya tienen sus restricciones duras liberadas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio=0'],
            'aggregation_policy' => 'Fraccion sobre las actividades de la franja 0 (definida en '
                . '`filters`): numerador = las que ademas tienen hard_restrictions_ready=1 (listas), '
                . 'denominador = todas las de la franja.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reporta la fraccion de liberacion de restricciones '
                . 'duras observada al corte dentro de la franja semana 0.',
            'version' => '1.0',
            'known_limitations' => 'Se declara como fraccion de listas por franja, no como conteo '
                . 'de actividades en la ventana -- el numerador es siempre hard_restrictions_ready=1 '
                . '(comparacion simple, admitida por MetricExecutor::NUMERATOR_EXPRESSION_PATTERN) y '
                . 'la franja (Semanas_Inicio=0) vive en `filters`, que si admite varias comparaciones '
                . 'simples en el WHERE. El limite real que persiste es otro: el numerador NUNCA '
                . 'admite un rango compuesto (`Semanas_Inicio>=X AND Semanas_Inicio<=Y`) -- por eso '
                . 'la franja se resuelve en filters y no en el numerador.',
        ];

        $catalog['pi_semaforo_semana_1_2'] = [
            'metric_key' => 'pi_semaforo_semana_1_2',
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'intermedia',
            'metric_name' => 'Semáforo semanas para iniciar: semanas 1-2',
            'definition' => 'Proporción de actividades del lookahead con inicio entre la semana 1 '
                . 'y la 2 que ya tienen sus restricciones duras liberadas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=1', 'Semanas_Inicio<=2'],
            'aggregation_policy' => 'Fraccion sobre las actividades de la franja 1-2 (definida en '
                . '`filters`): numerador = las que ademas tienen hard_restrictions_ready=1 (listas), '
                . 'denominador = todas las de la franja.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reporta la fraccion de liberacion de restricciones '
                . 'duras observada al corte dentro de la franja semanas 1-2.',
            'version' => '1.0',
            'known_limitations' => 'Se declara como fraccion de listas por franja, no como conteo '
                . 'de actividades en la ventana -- el numerador es siempre hard_restrictions_ready=1 '
                . '(comparacion simple, admitida por MetricExecutor::NUMERATOR_EXPRESSION_PATTERN) y '
                . 'la franja (Semanas_Inicio>=1 AND Semanas_Inicio<=2) vive en `filters`, que si '
                . 'admite varias comparaciones simples en el WHERE. El limite real que persiste es '
                . 'otro: el numerador NUNCA admite un rango compuesto -- por eso la franja se '
                . 'resuelve en filters y no en el numerador.',
        ];

        $catalog['pi_semaforo_semana_3_4'] = [
            'metric_key' => 'pi_semaforo_semana_3_4',
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'intermedia',
            'metric_name' => 'Semáforo semanas para iniciar: semanas 3-4',
            'definition' => 'Proporción de actividades del lookahead con inicio entre la semana 3 '
                . 'y la 4 que ya tienen sus restricciones duras liberadas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=3', 'Semanas_Inicio<=4'],
            'aggregation_policy' => 'Fraccion sobre las actividades de la franja 3-4 (definida en '
                . '`filters`): numerador = las que ademas tienen hard_restrictions_ready=1 (listas), '
                . 'denominador = todas las de la franja.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reporta la fraccion de liberacion de restricciones '
                . 'duras observada al corte dentro de la franja semanas 3-4.',
            'version' => '1.0',
            'known_limitations' => 'Se declara como fraccion de listas por franja, no como conteo '
                . 'de actividades en la ventana -- el numerador es siempre hard_restrictions_ready=1 '
                . '(comparacion simple, admitida por MetricExecutor::NUMERATOR_EXPRESSION_PATTERN) y '
                . 'la franja (Semanas_Inicio>=3 AND Semanas_Inicio<=4) vive en `filters`, que si '
                . 'admite varias comparaciones simples en el WHERE. El limite real que persiste es '
                . 'otro: el numerador NUNCA admite un rango compuesto -- por eso la franja se '
                . 'resuelve en filters y no en el numerador.',
        ];

        $catalog['pi_semaforo_semana_5_6'] = [
            'metric_key' => 'pi_semaforo_semana_5_6',
            'estado_ejecucion' => 'ejecutable',
            'report_key' => 'intermedia',
            'metric_name' => 'Semáforo semanas para iniciar: semanas 5-6',
            'definition' => 'Proporción de actividades del lookahead con inicio entre la semana 5 '
                . 'y la 6 que ya tienen sus restricciones duras liberadas.',
            'formula' => 'SUM(hard_restrictions_ready=1) / COUNT(*)',
            'unit' => 'porcentaje',
            'execution_source' => 'bi_pg_semana',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana',
            'cutoff_policy' => 'Fin de la semana seleccionada en semanas_activas.',
            'filters' => ['Titulo=0', 'Ejecutado<1', 'Semanas_Inicio>=5', 'Semanas_Inicio<=6'],
            'aggregation_policy' => 'Fraccion sobre las actividades de la franja 5-6 (definida en '
                . '`filters`): numerador = las que ademas tienen hard_restrictions_ready=1 (listas), '
                . 'denominador = todas las de la franja.',
            'supports_multi_project' => true,
            'supports_date_range' => true,
            'synthetic_defaults_allowed' => false,
            'forecast_policy' => 'No forecast; reporta la fraccion de liberacion de restricciones '
                . 'duras observada al corte dentro de la franja semanas 5-6.',
            'version' => '1.0',
            'known_limitations' => 'Se declara como fraccion de listas por franja, no como conteo '
                . 'de actividades en la ventana -- el numerador es siempre hard_restrictions_ready=1 '
                . '(comparacion simple, admitida por MetricExecutor::NUMERATOR_EXPRESSION_PATTERN) y '
                . 'la franja (Semanas_Inicio>=5 AND Semanas_Inicio<=6) vive en `filters`, que si '
                . 'admite varias comparaciones simples en el WHERE. El limite real que persiste es '
                . 'otro: el numerador NUNCA admite un rango compuesto -- por eso la franja se '
                . 'resuelve en filters y no en el numerador. Los mayores a 6 (fuera de ventana) '
                . 'quedan fuera de las 4 franjas por diseño: is_lookahead_window en bi_pg_semana '
                . 'acota Semanas_Inicio a 0-6.',
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
