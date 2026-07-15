# Registro de validacion - BI Control Tower

## 2026-07-14 - Programa General / Radar

### Feature registrada

- Radar con tres ejes independientes: Productividad, Eficiencia y Desempeno PAC.
- Fuente operacional: `programacion_semanal`.
- Poblacion: compromisos con `Activa IN ('1','NA')`; `Es_TNP=1` se excluye del calculo.
- Productividad: promedio global de `MIN(P_Completado valido, 1) * 100`.
- Eficiencia: promedio global por fila de `Ejecutado_Real / Compromiso * 100`; no suma unidades incompatibles.
- Desempeno: `COUNT(PAC=1) / COUNT(PAC IN (0,1)) * 100`.
- Muestra minima independiente por eje: 3 registros validos.
- Multiproyecto: numeradores y denominadores globales; no promedia porcentajes de proyecto.
- Drill-down por eje con actividad, proyecto, corte, unidad, compromiso, ejecutado, progreso, PAC,
  Responsable AIA, Sub-Contratista, criticidad, TNP y razon de exclusion.

### Fixes registrados

- Eliminadas filas `Activa=0` de la poblacion y del detalle del Radar.
- Detalle paginado con `limit`, `offset`, total y `has_more`; maximo 100 registros por pagina.
- Mobile materializa solo cards; tablet y desktop materializan solo tabla.
- Error HTTP/red separado del estado valido sin datos.
- Tabs ARIA con `tabpanel`, `tabindex`, flechas, `Home` y `End`.
- Cobertura 403 y aislamiento de proyecto para `/api/bi/report/programa-general/radar-detail`.
- Lineage y diccionario metrico alineados con `programacion_semanal` y la poblacion activa.
- Shell BI restaurado en fila desde 768 px pese al `body` global en columna.
- Superficies dark/linen gobernadas por tokens; sin clases de fondo que mezclen temas.
- Tooltip sin puntuacion duplicada y drawer con superficie opaca.

### Matriz nativa

Contexto con muestra valida: `project_id=74`, semana 4.

| Modo | Viewport | Tema | Ejes | Detalle | Overflow X | Resultado |
| --- | --- | --- | --- | --- | ---: | --- |
| Mobile dark | 390x844 | dark | 1 columna | 10 cards, 0 filas | 0 | PASS |
| Mobile linen | 390x844 | linen | 1 columna | 10 cards, 0 filas | 0 | PASS |
| Tablet dark | 1024x768 | dark | 2 columnas | 0 cards, 10 filas | 0 | PASS |
| Tablet linen | 1024x768 | linen | 2 columnas | 0 cards, 10 filas | 0 | PASS |
| Desktop dark | 1440x900 | dark | 3 columnas | 0 cards, 10 filas | 0 | PASS |
| Desktop linen | 1440x900 | linen | 3 columnas | 0 cards, 10 filas | 0 | PASS |

Valores observados en los seis modos: Productividad 70,1%; Eficiencia 84,2%; PAC 50%.

### Evidencia automatizada

- `tests/test_bi_programa_general_radar.php`: PASS.
- `tests/test_bi_programa_general_chart_values.php`: PASS en proyecto, multiproyecto, rango y filtros.
- `tests/test_bi_filters_apply_to_charts.php`: PASS para semana, rango, Responsable AIA,
  Sub-Contratista y etapa.
- `tests/test_bi_metric_contracts.php`: PASS.
- `tests/test_bi_source_reconciliation.php`: 17 PASS.
- `tests/test_bi_real_data_sources.php`: 99 PASS.
- Playwright Radar/RBAC/layout/error: 5 PASS.
- PHPStan enfocado: PASS.
- Revisor independiente final: sin hallazgos P0-P2.

Capturas: `evidence/radar/`.

## 2026-07-14 - Programa General / Variacion probable de fecha final

### Feature registrada

- El indicador ejecutivo publica `pg_finish_variance_days_p50`: fecha final P50 simulada menos
  fecha final contractual, en dias calendario.
- El rango probable 80% se comunica con P10 optimista, P50 mas probable y P90 pesimista.
- Metodo: Monte Carlo de curva S con 240 simulaciones y minimo 3 incrementos reales positivos
  por proyecto.
- Multiproyecto: obtiene la fecha maxima de terminacion en cada simulacion y calcula percentiles
  despues de consolidar el portafolio.
- La ausencia de muestra se mantiene como `unavailable`; no se reemplaza por cero.
- El detalle operativo usa una metrica independiente, `pg_observed_activity_delay_days`, para
  actividades `Titulo=0`, vencidas, incompletas y con fechas validas.
- Cada actividad usa el corte propio de su proyecto y conserva proyecto, responsable AIA,
  subcontratista, criticidad, plan, real e implicacion.
- Interaccion por doble click en desktop y boton explicito en mobile; paginacion maxima de 100.

### Separacion semantica

- El chart P50 ya no contiene `observed_days` ni lo presenta como retraso operativo.
- El detalle separa forecast contractual, desglose por proyecto y retraso ya observado.
- El atraso de actividades paralelas no se interpreta como atraso del proyecto; la implicacion
  sobre la fecha final depende de ruta critica y logica de red.
- La cohorte de la simulacion se fija con las actividades que coinciden en el corte solicitado;
  luego sus `unique_id` se siguen en el historial sin depender de cambios de texto en responsable,
  subcontratista o etapa.
- Un proyecto seleccionado sin actividades coincidentes en el corte permanece en el desglose como
  `unavailable`, con razon explicita; nunca reutiliza como actual un snapshot historico anterior.
- La fecha contractual se toma del primer snapshot disponible para la misma cohorte y no se mueve
  con reprogramaciones ni con valores historicos mutables de los filtros.
- Diccionario y lineage incluyen forecast, retraso observado y los tres ejes del Radar.

### Datos nativos observados

Contexto: `project_id=68` (Optimizacion Aeropuerto JMC), semana 6.

- Fin contractual: 24/04/2027.
- P10: 13/01/2029.
- P50: 28/04/2029, variacion `+735 dias`.
- P90: 14/07/2029.
- Actividades vencidas e incompletas: 46.
- Actividades vencidas en ruta critica: 4.
- Mayor retraso observado por actividad: 75 dias.

### Matriz nativa

| Modo | Viewport | Tema | Detalle | Overflow X | Resultado |
| --- | --- | --- | --- | ---: | --- |
| Mobile dark | 390x844 | dark | 46 cards, tabla oculta | 0 | PASS |
| Mobile linen | 390x844 | linen | 46 cards, tabla oculta | 0 | PASS |
| Tablet dark | 1024x768 | dark | 46 filas, cards ocultas | 0 | PASS |
| Tablet linen | 1024x768 | linen | 46 filas, cards ocultas | 0 | PASS |
| Desktop dark | 1440x900 | dark | 46 filas, cards ocultas | 0 | PASS |
| Desktop linen | 1440x900 | linen | 46 filas, cards ocultas | 0 | PASS |

### Evidencia automatizada

- `tests/test_bi_programa_general_chart_values.php`: PASS para proyecto unico, multiproyecto,
  rango y filtros de Sub-Contratista y Responsable AIA.
- `tests/test_bi_metric_contracts.php`: PASS.
- `tests/test_bi_filters_apply_to_charts.php`: PASS.
- `tests/test_bi_source_reconciliation.php`: 17 PASS.
- `tests/test_bi_real_data_sources.php`: 99 PASS.
- `tests/browser/bi_control_tower.spec.mjs`: 26 PASS.
- PHPStan enfocado: PASS.
- Regresion de filtro solo historico: proyecto 63, semana 17, sin forecast fabricado: PASS.
- Regresion de cohorte mutable: proyecto 62, semana 28, P50 10/04/2025 y `+187 dias`: PASS.
- Paginacion backend y UI con `offset=0` y `offset=1`, sin repetir actividades: PASS.
- Revisor independiente final: sin hallazgos P0-P2.

Capturas: `evidence/delay/`.

## 2026-07-14 - Programa General / Causas de No Programacion

### Feature registrada

- Fuente operacional: `programacion_semanal`; corte y urgencia: `semanas_activas`.
- Poblacion: filas unicas `project_id + Semana + Consecutivo` con `Activa='0'` y CNP no vacia.
- El chart distribuye actividades no programadas por categoria canonica y expone el conteo total.
- La lectura ejecutiva muestra total, actividades criticas, inicios vencidos, faltantes de
  Responsable AIA, causa principal y accion recomendada.
- El detalle conserva proyecto, semana, actividad, ubicacion, categoria, causa, observacion,
  inicio, urgencia, criticidad, Responsable AIA, Subcontratista, impacto y accion recomendada.
- No se publica un enlace operativo falso: el modulo CNP actual no consume todavia deep links
  confiables por proyecto, semana y actividad.
- Filtros reconciliados: proyecto unico, multiproyecto, semana, rango, Responsable AIA,
  Sub-Contratista y busqueda de Actividad/Ubicacion.

### Fixes registrados

- La narrativa compartida separa CNP de CNC; una actividad incumplida ya no se describe como
  actividad no programada.
- Lineage y metricas declaran `programacion_semanal` y `semanas_activas`.
- El primer bloque entrega resumen y 25 registros; paginas posteriores usan conteo y
  `LIMIT/OFFSET` SQL sin reconstruir el universo completo.
- Una falla de `Cargar mas` conserva los registros visibles, muestra alerta accesible y permite
  reintentar el mismo offset.
- Doble click sobre un segmento filtra por categoria; cada categoria tambien expone un boton
  nativo operable con teclado y el boton de detalle abre todo el universo.
- El filtro canonico de categoria se aplica en `COUNT DISTINCT` y `LIMIT/OFFSET` SQL; las
  paginas posteriores con `include_summary=0` no reconstruyen el universo completo.
- El lineage CNP consume la fecha de publicacion gobernada por el diccionario de metricas.
- Paginacion verificada hasta `pagination.total`, con claves `source_row_key` unicas.
- Cobertura 403 y aislamiento de proyecto para los endpoints CNP y CNC.
- Mobile transforma el detalle en cards; tablet horizontal y desktop usan tabla.
- Superficies dark y linen separadas por tokens; sin overflow horizontal ni texto fuera de
  controles.

### Datos nativos observados

Contexto: `project_id=68` (Optimizacion Aeropuerto JMC), semana 6.

- Actividades con CNP: 33.
- Actividades criticas: 2.
- Actividades con inicio vencido: 30.
- Actividades sin Responsable AIA: 7.
- Causa principal: Programacion, 33 actividades (100%).

### Matriz nativa

| Modo | Viewport | Tema | Detalle inicial | Overflow X | Resultado |
| --- | --- | --- | --- | ---: | --- |
| Mobile dark | 390x844 | dark | 25 cards | 0 | PASS |
| Mobile linen | 390x844 | linen | 25 cards | 0 | PASS |
| Tablet dark | 1024x768 | dark | 25 filas | 0 | PASS |
| Tablet linen | 1024x768 | linen | 25 filas | 0 | PASS |
| Desktop dark | 1440x900 | dark | 25 filas | 0 | PASS |
| Desktop linen | 1440x900 | linen | 25 filas | 0 | PASS |

### Evidencia automatizada

- `tests/test_bi_programa_general_cnp.php`: PASS para fuente, filtros, categoria, paginacion,
  metricas accionables y regresion narrativa CNC.
- `tests/test_bi_programa_general_chart_values.php`: PASS en cuatro escenarios.
- `tests/test_bi_filters_apply_to_charts.php`: PASS.
- `tests/test_bi_metric_contracts.php`: PASS.
- `tests/test_bi_source_reconciliation.php`: 17 PASS.
- `tests/test_bi_real_data_sources.php`: 101 PASS.
- `tests/browser/bi_control_tower.spec.mjs`: 28 PASS.
- PHPStan enfocado: PASS.
- Activacion de categoria con `Enter`, cierre con `Escape` y restitucion de foco: PASS.
- Segunda pagina filtrada por categoria con `include_summary=0`: PASS.
- Revisor independiente final: sin hallazgos P0-P2.

Capturas regeneradas el 2026-07-14 04:10: `evidence/cnp/` (tarjeta y detalle para los
seis modos, con control de categoria final visible).

## 2026-07-14 - Programa General / Causas de No Cumplimiento

### Feature registrada

- Fuente operacional: `programacion_semanal`; corte temporal: `semanas_activas`.
- Poblacion: filas unicas `project_id + Semana + Consecutivo` con
  `Activa IN ('1','NA')` y CNC no vacia.
- El chart y el detalle consumen el mismo universo deduplicado; sus totales y categorias no
  pueden divergir por filas repetidas.
- El detalle CNC compara `Compromiso` contra `Ejecutado_Real`: publica cantidad comprometida,
  ejecucion real, porcentaje de cumplimiento, brecha en cantidad y porcentaje, unidad y estado.
- El cumplimiento por actividad es `Ejecutado_Real / Compromiso * 100` cuando el compromiso es
  positivo. La brecha es `MAX(0, Compromiso - Ejecutado_Real)`.
- La lectura ejecutiva muestra total de incumplimientos, actividades sin ejecucion, brechas de
  al menos 50%, cumplimiento medio, causa principal y accion recomendada.
- Responsable AIA y Subcontratista se presentan juntos como responsables del compromiso.
- La implicacion sobre fecha final es cualitativa: una CNC no se declara atraso contractual sin
  evidencia adicional de ruta critica y logica de red.
- No se publica un deep link falso hacia una fila CNC: el modulo operativo actual no demuestra
  todavia que pueda enfocar de forma confiable proyecto, semana y actividad.

### Datos y reconciliacion

Contexto con datos: `project_id=68` (Optimizacion Aeropuerto JMC), semana 4.

- CNC: 8 actividades unicas.
- Sin ejecucion: 8.
- Brecha igual o mayor a 50%: 8.
- Cumplimiento medio: 0%.
- Categorias: Programacion 5, Administrativas 2 y Mano de Obra 1.
- Filtro `PROCOPAL` + `Mildred Buitrago`: 2 CNC, ambas de Programacion.
- El rango `2026-06-10` a `2026-06-16` conserva las mismas 2 CNC filtradas.
- Multiproyecto autorizado JMC + Da Porto, semana 4: 8 CNC; la consolidacion cuenta actividades
  unicas y no promedia porcentajes de proyecto.

Contexto vacio real: JMC, semana 6.

- CNC: 0.
- El chart muestra `Sin registros`, oculta acciones de categoria y explica que no inventa causas.
- La fuente directa y `bi_ps_compromisos` se reconciliaron en 0 para este corte.

Escenario multiproyecto de contrato `[62,63,65,68,70]`, semana 6: 26 CNC, distribuidas en
Programacion 13, Disenos 6, Causas Exogenas 2, Equipos 2, Materiales 2 y Mano de Obra 1.

### Matriz nativa

| Modo | Viewport | Tema | Detalle | Overflow X | Resultado |
| --- | --- | --- | --- | ---: | --- |
| Mobile dark | 390x844 | dark | 8 cards, tabla oculta | 0 | PASS |
| Mobile linen | 390x844 | linen | 8 cards, tabla oculta | 0 | PASS |
| Tablet dark | 1024x768 | dark | 8 filas, cards ocultas | 0 | PASS |
| Tablet linen | 1024x768 | linen | 8 filas, cards ocultas | 0 | PASS |
| Desktop dark | 1440x900 | dark | 8 filas, cards ocultas | 0 | PASS |
| Desktop linen | 1440x900 | linen | 8 filas, cards ocultas | 0 | PASS |

En los seis modos se verificaron tema exclusivo, texto contenido, tarjeta sin overflow y cero
scroll horizontal. Mobile usa cards por registro; tablet horizontal y desktop usan tabla.

### Evidencia automatizada

- `tests/test_bi_programa_general_cnc.php`: PASS para fuente independiente, JMC semanas 4 y 6,
  multiproyecto, rango, Responsable AIA, Subcontratista, etapa, categorias, paginacion,
  deduplicacion y formulas por actividad.
- `tests/test_bi_programa_general_cnp.php`: PASS de regresion sobre la logica causal compartida.
- `tests/test_bi_programa_general_chart_values.php`: PASS en cuatro escenarios.
- `tests/test_bi_filters_apply_to_charts.php`: PASS.
- `tests/test_bi_metric_contracts.php`: PASS.
- `tests/browser/bi_control_tower.spec.mjs`: 29 PASS; CNC comprueba teclado, foco, tabla, cards,
  paginacion completa, detalle cuantitativo, empty state y overflow.
- Endpoints de detalle mantienen 403 para roles sin permiso y aislamiento por proyecto.
- PHPStan enfocado y sintaxis PHP/JS: PASS.
- Revisor independiente final: sin hallazgos P0-P2.

Capturas: `evidence/cnc/`.

## 2026-07-14 - Programa General / Cronograma de actividades que explica el corte

### Feature registrada

- Fuente operacional: `programa_consolidado`; corte semanal: `semanas_activas`.
- Se excluyen filas de agrupación con `Titulo=1` y fechas inválidas.
- Grano publicado: `project_id + Semana + unique_id`.
- La duración es inclusiva: `DATEDIFF(Fecha_Fin, Fecha_Inicio) + 1`.
- El peso de cada actividad es su duración sobre la duración total del universo filtrado.
- El detalle publica avance real y teórico al corte, brecha, peso, aporte real, aporte
  recuperable, proyecto, etapa, fechas, ruta crítica, atraso observado, Responsable AIA,
  Subcontratista y bloqueo operativo.
- El snapshot inicial y el endpoint paginado comparten universo, denominador, resumen y orden.
- Un proyecto sin datos en la semana solicitada usa su último corte elegible anterior al
  consolidar varios proyectos; los filtros de responsable, subcontratista y etapa se aplican
  después de fijar ese corte y no pueden hacer retroceder el proyecto a otra semana.
- `Lo que más falta`, `Lo que ya suma` y `Solo ruta crítica` filtran y ordenan el universo
  completo en backend antes de paginar.
- Mobile muestra una card por actividad; tablet horizontal y desktop muestran tabla. Ambos
  formatos incluyen una línea temporal compacta con progreso real y teórico.

### Fixes registrados

- La consulta de detalle obtiene primero la semana de snapshot por proyecto y luego consulta
  solo las filas de esos cortes. Se eliminó la reconstrucción del histórico y la simulación
  probabilística en cada página.
- Rendimiento medido por revisión independiente: 62-84 ms para JMC, 73-91 ms para JMC +
  proyecto 74 y 369 ms para 14 proyectos.
- `Cargar más` solicita hasta 100 registros y deduplica por `activity_key` sin alterar el
  prefijo ya visible.
- Las respuestas tardías de página, reporte principal y modal se descartan por `requestId`.
  Un error o `finally` obsoleto no puede borrar resultados ni ocultar el loading de una
  apertura nueva.
- Las pruebas de carrera usan respuestas controladas y esperan su entrega efectiva; no
  dependen de pausas fijas.
- El texto corrige mojibake simple y doble solo en los fragmentos dañados, conservando UTF-8
  válido y emojis.
- El modal usa botones con `aria-pressed`, trap de foco, cierre con Escape y restitución del
  foco al disparador.
- El título de las cards mobile se resuelve en `@layer module` con tokens del design system;
  no se añadieron hex, dimensiones CSS literales ni `!important`.
- El endpoint `progress-detail` quedó incorporado a las regresiones de 403 y aislamiento por
  proyecto; una sesión sin `lps.indicadores.ver` o sin acceso al proyecto no obtiene sus filas.
- El resumen dinámico del cronograma usa `role="status"`, `aria-live="polite"` y
  `aria-atomic="true"` para anunciar carga y paginación a tecnologías de asistencia.
- La preflight automatizada recorre dark y linen en mobile, tablet horizontal y desktop;
  valida cards/tabla y overflow del bloque, acciones y registros en cada combinación.

### Datos reconciliados

Contexto JMC: `project_id=68`, semana 6.

- Actividades válidas: 1.475.
- Avance real: 3,8%.
- Avance teórico al corte: 6,5%.
- Brecha: -2,7 pp.

Contexto asimétrico multiproyecto: proyectos 68 + 74, semana solicitada 6.

- Proyecto 68 usa semana 6; proyecto 74 usa su último corte elegible, semana 5.
- Actividades válidas: 1.634.
- Avance real consolidado: 4,0%.
- Avance teórico consolidado: 8,2%.
- Brecha consolidada: -4,2 pp.

### Preflight técnica responsive — no constituye aprobación

| Modo | Viewport | Tema | Presentación | Overflow X | Estado del gate |
| --- | --- | --- | --- | ---: | --- |
| Mobile dark | 390x844 | dark | cards | 0 | Presentado; aprobación pendiente |
| Mobile linen | 390x844 | linen | cards | 0 | Preflight técnica; no presentado |
| Tablet dark | 1024x768 | dark | tabla horizontal | 0 | Preflight técnica; no presentado |
| Tablet linen | 1024x768 | linen | tabla horizontal | 0 | Preflight técnica; no presentado |
| Desktop dark | 1440x900 | dark | tabla | 0 | Preflight técnica; no presentado |
| Desktop linen | 1440x900 | linen | tabla | 0 | Preflight técnica; no presentado |

La preflight comprueba reflow y overflow, pero los modos posteriores a mobile dark deberán
presentarse nuevamente, uno por uno y en el orden contractual. No son evidencia de aprobación.
El contraste final del título mobile dark se verificó adicionalmente contra el token computado
`--ds-active-text-primary`.

### Estado de aprobación visual

La matriz anterior registra verificación técnica y responsive; no sustituye la aprobación
explícita del usuario exigida por `goal.md` y `plan.md`.

- Mobile dark: presentado en el navegador integrado a `390x844`; aprobación pendiente.
- Mobile linen: pendiente de presentación después de aprobar mobile dark.
- Tablet horizontal dark: pendiente.
- Tablet horizontal linen: pendiente.
- Desktop dark: pendiente.
- Desktop linen: pendiente.
- Commit atómico del bloque: pendiente de las seis aprobaciones.

Interacciones verificadas en el navegador integrado para mobile dark:

- La carga incremental pasó de 25 a 125 cards, conservó el prefijo y publicó 125
  `activity_key` únicos.
- El bloque y cada card conservaron overflow horizontal en cero después de paginar.
- `Analizar composición del avance` abrió el modal mobile con 50 cards iniciales.
- `Lo que ya suma`, `Lo que más falta`, agrupación por Responsable AIA y `Solo ruta crítica`
  actualizaron registros y responsables coherentemente.
- El modal mostró Responsable AIA y Subcontratista con valores reales, cerró con `Escape`,
  restauró el foco al disparador y mantuvo overflow en cero.
- El filtro `PROCOPAL + Mildred Buitrago`, semana 5, devolvió 51 actividades; las 25 cards
  iniciales conservaron ambos responsables y publicaron 14,5% real, 16,4% teórico y
  brecha de -1,9 pp.

### Evidencia automatizada

- `tests/test_bi_programa_general_activity_timeline.php`: PASS contra oráculo SQL
  independiente, incluyendo multiproyecto asimétrico, no retroceso de filtros, todas las
  páginas, ranking `earned` y ruta crítica.
- `tests/test_bi_programa_general_chart_values.php`: 4 escenarios PASS; las curvas conservan
  su historial completo después de optimizar el snapshot.
- `tests/test_bi_metric_contracts.php`: PASS.
- `tests/test_bi_source_reconciliation.php`: 17 PASS.
- `tests/test_bi_filters_apply_to_charts.php`: PASS.
- `tests/test_bi_real_data_sources.php`: 101 PASS.
- `tests/browser/bi_control_tower.spec.mjs`: 33 PASS en un pase completo con un worker.
- Regresión enfocada del cronograma mobile dark: PASS en dos pases consecutivos de 50,1 s
  y 38,3 s; cubre paginación 25→125, claves únicas, responsables concretos, modal iniciado
  desde el bloque, cards, overflow, focus trap, cierre con `Escape` y restitución de foco.
- Regresiones enfocadas posteriores a revisión: 3 PASS en 1,0 min para RBAC 403, aislamiento
  por proyecto y cronograma completo con matriz técnica de seis modos.
- PHPStan enfocado: PASS, sin errores al nivel configurado 0.
- Sintaxis PHP/JS y `git diff --check`: PASS.
- Design system: 151 pruebas estáticas PASS y contratos PASS. El audit global permanece rojo
  por drift ajeno del worktree (`unauthorized-important`, `raw-token-in-module`,
  `duplicate-canonical-primitive`, `global-module-selector`); el diff de este bloque no añade
  `!important`, hex, dimensiones literales ni aliases de spacing/radius.
- La revisión independiente posterior identificó ambigüedad de gates, ausencia de RBAC para
  `progress-detail`, cobertura parcial de viewports y falta de anuncio accesible; los cuatro
  hallazgos quedaron corregidos y verificados.
- Riesgo residual abierto: las pruebas de datos dependen del dump local de los proyectos 68/74
  y todavía no están incluidas en un fixture BI aislado y reproducible para CI.
