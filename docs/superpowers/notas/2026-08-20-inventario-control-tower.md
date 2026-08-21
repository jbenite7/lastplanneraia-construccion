---
capa: fuente
tipo: inventario
estado: vigente
fecha: 2026-08-20
areas: [bi, rbac, design-system]
fuente: recorrido del informe Power BI + extracción del código, 2026-08-20
resumen: "Punto de partida del replanteo: todas las métricas, gráficas e indicadores que existen hoy, en el informe Power BI de gerencia y en la Torre de Control de la aplicación"
project: lps-aia
---

# Inventario de lo que existe hoy — Control Tower

> Punto de partida del replanteo. **Describe lo que hay, no lo que debería haber.** Las columnas
> «se ve» y «obedece al filtro» son observación directa del 2026-08-20, no lectura de código.
> Ver también [[2026-08-20-replanteo-control-tower-notas]] para las decisiones y hallazgos.

## Resumen en una tabla

| | Informe Power BI | Torre de Control |
|---|---|---|
| Dónde vive | SharePoint · Tableros de Gestión AIA | La aplicación, `/bi/*` |
| Quién entra | Quien tenga el sitio | Admin siempre; el resto según el interruptor global |
| Alcance | **Una obra a la vez** | Multi-proyecto |
| Páginas / hojas | 4 | 8 |
| Corte visto | 11/08/2026, semana 10 | El que se elija en el filtro |
| Actualización | Diaria, automática | En vivo contra la base |
| Fuente declarada | «Last Planner AIA Web App» | La misma base |

**El punto crítico: son dos motores calculando las mismas cifras.** El informe se alimenta de esta
aplicación pero recalcula en Power BI; la Torre recalcula en PHP. Nada obliga a que coincidan.

---

# Parte 1 · El informe Power BI (lo que la gerencia usa hoy)

`constructoraia.sharepoint.com/sites/TablerosAIA/SitePages/Last-Planner-AIA.aspx`

La página envuelve el informe con un **Objetivo** y una **Guía de Acción** de tres pasos —
Analice · Detecte · Actúe — y una ficha técnica al pie (fuente, actualización diaria, responsable:
Dirección de Tecnología, Procesos e Innovación).

Selector de obra siempre visible: Metrolínea Estación 16 · Metrolínea Mampostería Estación 2 ·
Milán Campestre Torre 19 · Optimización Aeropuerto JMC.

## Hoja 1 · Programa General

| Indicador | Forma | Valor visto | Obedece al filtro |
|---|---|---|---|
| % Avance de Obra | Medidor, con meta | 17,98% contra 76,21% | **No** |
| % Cumplimiento Cronograma | Tarjeta | 23,59% | **No** |
| Días de Retraso / Adelanto | Tarjeta | 88 | **No** |
| Radar de tres ejes | Radar | % Actividades Comprometidas · % Ejecutado · % Cantidades Comprometidas | Sí |
| % Actividades Comprometidas | Tarjeta | 24,00% | Sí |
| % Cantidades Comprometidas | Tarjeta | 24,00% | Sí |
| % Completado | Tarjeta | 20,17% | Sí |
| Causas de No Programación | Dona | 184 (96,84%) · 3 (1,58%) — Programación, Administrativas, Mano de Obra | Sí |
| Causas de No Cumplimiento | Dona | 14 (46,67%) · 6 (20%) · 5 (16,67%) · 3 (10%) · 1 (3,33%) | Sí |
| Cumplimiento semanal por responsable | Tabla | 30 actividades, 14,84% | Sí |

**Escondido en tooltip:** al pasar el mouse sobre el medidor de avance se despliega la **Curva S de
Cronograma** — % Ejecutado Semanal en barras, Curva S Teórica y Curva S Real, de junio a septiembre
de 2026.

**Escondido en tooltip:** el desglose de causa a **subcausa**. No Programación se abre en
Restricciones habilitantes no cumplidas 182 (98,91%) y dos entradas de «Actividad predecesora
incompleta / n…» de 1 (0,54%) cada una. No Cumplimiento se abre en Personal insuficiente
(subcontratista) 14 (100%).

## Hoja 2 · Última Semana

| Indicador | Forma | Valor visto |
|---|---|---|
| % Avance Semanal | Medidor, con meta | 4,84% contra 17,79% |
| % Cumplimiento Plan Semanal | Tarjeta | 27,21% |
| # Actividades sin Programar | Tarjeta | 90 |
| Radar de tres ejes | Radar | 26,23% · 26,23% · 20,17% |
| Causas de No Programación | Dona | Programación |
| Causas de No Cumplimiento | Dona | Mano de Obra · Programación · Administrativas |
| Tareas por estado | Tabla | Total Críticas 118 · No Comprometida 87 · No Cumplida 116 · Atrasada 117 |

## Hoja 3 · Liberación de Restricciones

| Indicador | Forma | Valor visto |
|---|---|---|
| Estado de Restricciones por Semana | Barras apiladas, por semanas para iniciar (0 a 6) | En «0 ya debió iniciar»: 612 y 247 |
| Liberación General de Restricciones | Dona | Liberada 671 (66,57%) · Sin gestionar 318 (31,55%) · En proceso 19 (1,88%) |
| Pareto de Restricciones No Liberadas | Barras ordenadas | Actividad Predecesora 100 · Materiales 77 · Mano de Obra 62 · Equipos 58 · Diseños y Especificaciones 18 |
| Número de Actividades Afectadas | Tres tarjetas | 91 · 4 · 10 |

## Hoja 4 · Proveedores

Filtros propios: Proyecto · NIT-Proveedor · Alcance · Tipo de Proveedor · Rango de Fechas.

| Indicador | Forma | Estado real |
|---|---|---|
| Calificación Integral de Proveedores | Tabla con pesos declarados: PAC 30%, Calidad 20%, Social-Ambiental 20%, SST 20%, Administración 10% | **Solo PAC trae datos.** Las otras cuatro columnas están vacías y aun así se muestra Total 55,08% |
| Aprobación de Proveedores | Indicador de estado | «No Aceptado» |
| Promedio Calificaciones Integrales | Barras | En blanco |

## Problemas observados en el informe

1. **El filtro no gobierna toda la página.** Al filtrar por una causa (3 actividades), las tarjetas
   pasaron a 100% / 100% / 40,73% y la tabla a 3 actividades, pero avance, cumplimiento y días de
   retraso no se movieron. Media pantalla habla de 3 actividades y la otra media de la obra entera,
   sin ninguna señal de cuál es cuál.
2. **La visual más valiosa está en el lugar más frágil.** La curva S solo existe para quien pase el
   mouse. En comité proyectado, en captura o en pantalla táctil, no existe.
3. **Un nivel completo de detalle solo alcanzable por hover:** el paso de causa a subcausa, que es
   justamente el «por qué» que promete la Guía de Acción.
4. **Todo en rojo.** Avance, cumplimiento, retraso y las tres tarjetas laterales. Cuando todo grita,
   nada avisa.
5. **La etiqueta promete más que el dato.** «Calificación Integral» con cuatro de cinco componentes
   vacíos.
6. **Categorías duplicadas o indistinguibles** en el catálogo de causas.
7. **Radar ilegible** en estado normal: el área queda diminuta contra la escala.
8. **«88 Días de Retraso / Adelanto»**: el signo es ambiguo, la etiqueta no dice de qué lado está.
9. **Cumple «Analice» y no «Detecte» ni «Actúe».**

---

# Parte 2 · La Torre de Control de la aplicación

## Las 8 hojas

| Hoja | Ruta de pantalla | Endpoint |
|---|---|---|
| Resumen Ejecutivo | `/bi/control-tower` | `/api/bi/control-tower` |
| Programa General | `/bi/programa-general` | `/api/bi/report/programa-general` |
| Programación Intermedia | `/bi/intermedia` | `/api/bi/report/intermedia` |
| Programación Semanal | `/bi/semanal` | `/api/bi/report/semanal` |
| Plan de Compras | `/bi/pdc` | `/api/bi/report/pdc` |
| Proveedores | `/bi/contratistas` | `/api/bi/report/cic` |
| Responsables | `/bi/responsables` | `/api/bi/report/cip` |
| Curva S | `/bi/curva-s` | `/api/bi/report/curva-s` |

Endpoints de apoyo: `/api/bi/projects`, `/api/bi/weeks`, `/api/bi/filter-options`, `/api/bi/lineage`.
Endpoints de detalle (se abren desde la gráfica): `progress-detail`, `compliance-detail`,
`delay-detail`, `radar-detail`, `cnp-detail`, `cnc-detail`, `pdc/detail`.

## Las 19 métricas del catálogo

Definidas en `src/Services/Bi/MetricDictionaryService.php`. Cada una declara nombre, definición,
fórmula, unidad, fuente de ejecución, grano, política de corte, filtros, política de agregación,
si admite multi-proyecto y rango de fechas, política de pronóstico, versión y limitaciones conocidas.

| Clave | Hoja | Nombre | Unidad | Fuente | Grano | Versión |
|---|---|---|---|---|---|---|
| `pg_activities_to_do` | Programa General | Actividades en ventana Lookahead | actividades | `bi_pg_semana` | proyecto + semana | 1.0 |
| `pg_activity_progress_contribution` | Programa General | Aporte de actividad al avance | puntos porcentuales | `ControlTowerService` | proyecto + semana + actividad | 1.0 |
| `pg_finish_variance_days_p50` | Programa General | Variación probable de fecha final P50 | días calendario | `ControlTowerService` | portafolio al corte | 2.0 |
| `pg_observed_activity_delay_days` | Programa General | Retraso observado por actividad | días calendario | `ControlTowerService` | proyecto + semana + actividad | 1.0 |
| `pg_cnp_activity_count` | Programa General | Actividades con Causa de No Programación | actividades | `programacion_semanal` | proyecto + semana + consecutivo | 1.0 |
| `pg_cnc_activity_count` | Programa General | Actividades con Causa de No Cumplimiento | actividades | `programacion_semanal` | proyecto + semana + consecutivo | 1.0 |
| `pg_radar_productividad` | Programa General | Radar: Productividad | porcentaje | `programacion_semanal` | proyecto + semana + fila | 2.0 |
| `pg_radar_eficiencia` | Programa General | Radar: Eficiencia | porcentaje | `programacion_semanal` | proyecto + semana + fila | 2.0 |
| `pg_radar_desempeno` | Programa General | Radar: Desempeño PAC | porcentaje | `programacion_semanal` | proyecto + semana + fila | 2.0 |
| `pi_hard_restrictions_ready_rate` | Intermedia | Porcentaje de actividades listas en ventana | porcentaje | `bi_pg_semana` | proyecto + semana | 1.0 |
| `pi_restriction_pareto` | Intermedia | Pareto de restricciones no liberadas | restricciones | `bi_pi_restricciones` | proyecto + semana + tipo | 1.0 |
| `ps_pac_expected` | Semanal | PAC esperado (baseline) | porcentaje | `ForecastService` | proyecto + semana + fila | 1.1 |
| `ps_weekly_fulfillment` | Semanal | Productividad semanal | porcentaje | `bi_ps_compromisos` | proyecto + semana | 1.0 |
| `pdc_at_risk` | Plan de Compras | Pasos de contratación vencidos | pasos | `pdc_plan_paso` | proyecto + paquete + subpaquete | 1.0 |
| `cic_cal_integral` | Proveedores | Calificación integral de contratista | score | `bi_cic_contratistas` | proyecto + semana + subcontratista | 1.0 |
| `cic_aprobacion_status` | Proveedores | Estado de aprobación del proveedor | estado | `bi_cic_contratistas` | proyecto + semana + subcontratista | 1.0 |
| `cip_fulfillment_alert` | Responsables | Alerta de cumplimiento de responsable | alerta | `bi_cip_responsables` | proyecto + semana + responsable | 1.0 |
| `curva_s_desviacion` | Curva S | Desviación de la Curva S | puntos porcentuales | `bi_curva_s_duracion` | proyecto + semana | 1.0 |
| `riesgo_score_100` | Riesgos | Risk score (0-100) | score | `bi_riesgos` | proyecto + semana + entidad | RISK-SCORE-1.0 |

## La maquinaria que ya existe

| Pieza | Tamaño | Qué hace |
|---|---|---|
| `ControlTowerService` | orquestador | Arma la respuesta de cada hoja |
| `MetricDictionaryService` | 461 líneas | El catálogo de arriba |
| `LineageService` | 103 líneas | Qué métricas alimentan cada hoja, leído del catálogo |
| `ForecastService` | 452 líneas | Pronóstico |
| `RiskScoringService` | 258 líneas | Puntaje de riesgo |
| `ActionRecommendationService` | 441 líneas | Acción recomendada |
| `StorytellingService` | 373 líneas | Resumen narrativo |
| `bi-spa.js` | 4.199 líneas | Toda la interfaz, en un solo archivo |

Cada respuesta del API viaja con esta forma:
`executive_brief · scorecard · drivers · risks · recommended_actions · lineage`.

## Lo que sí llega a la pantalla

Corrección a una impresión anterior: la narrativa **no** está muerta. La interfaz ya pinta
«Acción recomendada», «Principal causa», «Cómo se calcula» en los ejes del radar, y bloques de
Bloqueo · Impacto · Brecha · Aporte real · Aporte recuperable · Ruta crítica.

Gráficas que usa: 3 barras, 3 donas, 1 línea, 1 radar.

## Lo que se calcula y se bota

**`lineage` viaja en cada respuesta del API y la interfaz no lo pinta.** La trazabilidad —de dónde
sale el número— se calcula en el servidor, se manda por la red y se descarta en el navegador.
`/api/bi/lineage` tampoco lo consume nadie. Es la respuesta al «no sé de dónde sale el número»
llegando al navegador y muriendo ahí.

`/api/bi/report/pdc/detail` tampoco tiene consumidor.

## Quién entra hoy

`BiPreviewAccessPolicy::canOpen()`: exige la capacidad `PERM_INTERNAL_BI_PREVIEW`; el Admin entra
siempre, y para los demás roles con esa capacidad manda el interruptor global editable desde
`/admin/modulos`. Las rutas están fuera de la navegación a propósito.

---

# Parte 3 · Qué se solapa y qué no

| Tema | Power BI | Torre de Control |
|---|---|---|
| Avance de obra contra meta | Medidor | Sí, en Resumen y Programa General |
| Curva S teórica contra real | **Escondida en tooltip** | **Pantalla propia** |
| Causas de no programación y no cumplimiento | Dona + subcausa por hover | Dona + «Principal causa» + detalle por clic |
| Radar de tres ejes | Sí, sin explicación | Sí, con «Cómo se calcula» por eje |
| Restricciones: semáforo, pareto, liberación | **Hoja completa** | Parcial (`pi_restriction_pareto`, tasa de listas) |
| Proveedores con cinco componentes | Tabla, con cuatro vacíos | `cic_cal_integral` + estado de aprobación |
| Cumplimiento por responsable | Tabla dentro de otras hojas | **Hoja propia** |
| Plan de compras | No está | **Hoja propia** |
| Pronóstico, riesgo, acción recomendada | No están | Sí |
| Multi-obra a la vez | **No** | Sí |
| Trazabilidad de la cifra | No | Se calcula y se bota |

**Lo único que el informe tiene y la Torre no, como pieza completa: la hoja de Liberación de
Restricciones** — el semáforo por semanas para iniciar y el conteo de actividades afectadas.
Todo lo demás la Torre ya lo cubre o lo supera.
