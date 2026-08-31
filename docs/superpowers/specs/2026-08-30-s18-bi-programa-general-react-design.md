---
capa: fuente
tipo: spec
estado: vigente
id: S18
fecha: 2026-08-31
superficie: bi-programa-general
rutas:
  - "/bi/programa-general"
  - "/api/bi/report/programa-general"
  - "/api/bi/report/programa-general/compliance-detail"
  - "/api/bi/report/programa-general/progress-detail"
  - "/api/bi/report/programa-general/delay-detail"
  - "/api/bi/report/programa-general/radar-detail"
  - "/api/bi/report/programa-general/cnp-detail"
  - "/api/bi/report/programa-general/cnc-detail"
depende_de: [T01, T03, S05, S09, S10, S17, S19, S20, S21]
views: []
areas: [bi, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, MetricDictionaryService, MetricExecutor, RiskScoringService, LineageService, LineaBaseContractualService, PDC v2, vistas BI, bi-spa.js, CSS, specs aprobadas y pruebas en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S18 de la hoja BI Programa General a React y TypeScript: pronostico P50 con rango y linea contractual, avance/cumplimiento, riesgo explicado, desempeño de cronograma en dinero solo con presupuesto suficiente, radar corregido, actividades y seis drilldowns paginados, titulares CNP/CNC y linaje; read-only, oscuro/claro, responsive y accesible, sin RLS, schema ni datos."
---

# S18 — Hoja BI Programa General en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que bloqueen su plan. Esta spec no autoriza implementacion, commits,
> DDL/DML, cambios RLS, schema, grants, usuarios, credenciales o datos, publicacion, deploy ni
> trabajo en /admin/. Su plan se escribe a continuacion con superpowers:writing-plans.

## Relacion con el programa

S18 migra /bi/programa-general, hoja 8.2 de Control Tower. Consume:

- T01 para sesion, shell, tema, cliente y errores;
- T03 para marco BI, lienzos, proyecto/periodo/filtros, query codec y linaje;
- S17 para la entrada desde Resumen Ejecutivo con focus y scope;
- S19 para la hoja Curva S completa;
- S20 para el detalle de restricciones;
- S21 para el destino final de las causas semanales;
- S05 como Programa General operacional editable;
- S09/S10 como superficies operacionales de CNP/CNC.

S18 es analitica y read-only. No reemplaza la tabla editable S05, no guarda programa, no gestiona
restricciones y no edita causas.

Las vistas/partials PHP siguen siendo propiedad de T03. S18 no puede retirar control-tower.php ni
bi-spa.js mientras cualquiera de S17–S24 los consuma.

## Resultado buscado

/bi/programa-general pasa a la SPA principal y:

1. se monta una sola vez en los lienzos de gerencia y obra;
2. permite A, D y R conforme al gate BI y scope de proyecto;
3. conserva proyectos, semana/rango, subcontratista, responsable y etapa;
4. abre con la fecha probable de terminacion, rango P10–P90 y brecha en palabras;
5. usa exclusivamente la linea base contractual declarada;
6. publica el pronostico solo con historia suficiente;
7. distingue retraso probable de retraso observado por actividad;
8. conserva avance real, teorico, cumplimiento y brecha con denominadores;
9. conserva la serie real/teorica/proyectada como evidencia, no como titular;
10. explica qué actividades componen y faltan al avance;
11. muestra riesgo operativo combinado y su formula/limitaciones;
12. incorpora desempeño de cronograma en dinero cuando el presupuesto activo y el amarre alcanzan;
13. declara expresamente que no existe costo real y no calcula CPI;
14. muestra insufficient cuando no hay presupuesto o cobertura;
15. conserva el radar con tres ejes corregidos y escala fija 0–100;
16. nombra Productividad como Avance promedio;
17. conserva raw value, display value, muestra, formula y exclusiones por eje;
18. no dibuja un eje sin muestra como cero;
19. presenta CNP/CNC solo como titular causal en el lienzo;
20. conserva sus detalles paginados durante la transición y los entrega a S21;
21. conserva tabla desktop/tablet y tarjetas movil de actividades;
22. conserva filtros all/missing/earned y solo ruta critica;
23. conserva seis drilldowns con carga, vacio, error, retry, paginacion y foco;
24. hace visible el linaje de cada metrica;
25. ofrece oscuro/claro y cinco viewports sin overflow;
26. no ejecuta escrituras ni toca RLS/schema/datos.

## Alcance

### Incluido

- GET /bi/programa-general como ruta SPA al corte.
- Los siete GET JSON ya registrados.
- Contrato canónico del brief.
- Contratos canónicos de seis detalles.
- T03 scope, filtros, query y hoja por lienzo.
- Pronostico Monte Carlo actual, 240 simulaciones.
- Minimo de tres incrementos positivos por proyecto.
- P10, P50, P90 y rango probable 80 por ciento.
- Linea contractual declarada.
- Breakdown por proyecto en multi-scope.
- Avance real/teorico ponderado por duracion inclusiva.
- Cumplimiento real/teorico y brecha.
- Serie de ejecucion y toggle de proyecciones.
- Estado/rango semantico sin depender del color.
- Actividad snapshot inicial de 25.
- Progress detail con 1..100, offset, sort y critical_only.
- Compliance detail con paginacion target.
- Delay detail con forecast y retraso observado.
- Risk score y drivers explicados.
- Schedule value PV/EV/SPI read-only desde presupuesto activo y amarre.
- Coverage del presupuesto/amarre.
- Radar y detalle de tres ejes.
- CNP/CNC titulares, categorias, resumen y detalles.
- Compatibilidad de deep links y focus.
- Linaje.
- Dark/light, responsive, a11y y pruebas.
- Corte strangler, retiro diferido y rollback.

### Fuera de alcance

- Todo /admin/.
- Editar Programa General; corresponde a S05.
- Importar/actualizar cronograma; corresponde a S06.
- Gestionar restricciones; corresponde a S20.
- Editar/reprogramar CNP; corresponde a S09.
- Editar CNC; corresponde a S10.
- Resolver compromisos semanales; corresponde a S21.
- Rediseñar la hoja Curva S completa; corresponde a S19.
- Cambiar la linea base contractual.
- Crear una linea base cuando falta.
- Cambiar el modelo Monte Carlo, sus 240 simulaciones o minimo de muestra.
- Convertir retrasos paralelos de actividades en retraso del proyecto.
- Calcular costo real, CPI, variacion de costo, facturado, causado o pagado.
- Conectar contabilidad.
- Estimar valores sin presupuesto activo.
- Repartir valor no amarrado de forma sintetica.
- Alterar presupuesto, APU, versiones o amarres PDC.
- Recalcular amarres.
- Cambiar formulas/estado de ejecucion del catalogo sin su gate propio.
- Cambiar la formula RISK-SCORE-1.0.
- Mostrar Productividad desde medir_productividad.
- Mezclar unidades en eficiencia.
- Tratar TNP como muestra de radar.
- Inventar muestra para un eje.
- Convertir CNP/CNC en conversación principal de 8.2.
- Introducir endpoints nuevos si los siete actuales bastan.
- Modificar capacidades o fallback RBAC.
- Modificar RLS, runtime boundary, schema, SQL views, indices, grants, usuarios, credenciales,
  membresias o datos.
- Ejecutar DDL, DML o tests con rollback real.
- Retirar vistas/scripts compartidos antes de T03/S17–S24.
- Regenerar goldens sin aprobacion.
- Implementar, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- No existe ruta, schema, gateway, store, componentes ni pruebas S18 en frontend/.
- ct-app no cubre esta hoja.
- La hoja vive dentro de views/bi/control-tower.php.
- public/js/modules/bi-spa.js concentra render, fetch, paginacion, dialogs, charts y foco.
- Hay fetch directo en el script legacy; el target usa cliente.ts.

### Rutas y payloads

| ID | Metodo/ruta | Query propia | Resultado legacy |
|---|---|---|---|
| S18-01 | GET /api/bi/report/programa-general | query BI compartida | brief completo |
| S18-02 | GET .../compliance-detail | limit | summary/explanation/activities |
| S18-03 | GET .../progress-detail | limit, offset, sort, critical_only | summary/groups/activities/pagination |
| S18-04 | GET .../delay-detail | limit, offset | forecast/observed/activities/pagination |
| S18-05 | GET .../radar-detail | axis, limit, offset | summary/records/pagination |
| S18-06 | GET .../cnp-detail | category, limit, offset, include_summary | summary/activities/pagination |
| S18-07 | GET .../cnc-detail | category, limit, offset, include_summary | summary/activities/pagination |

Todos pasan por BiPreviewAccessPolicy, auth, BiProjectScope y filtros. El target conserva las rutas y
estabiliza envelopes; no crea /v2.

### Acceso y lienzos

Programa General pertenece a ambos lienzos aprobados:

| Rol | Gate | Hoja |
|---|---|---|
| A | internal.bi.preview; Admin no depende del flag | permitida |
| D | internal.bi.preview + flag global | permitida |
| R | internal.bi.preview + flag global | permitida |
| otros | sin capacidad/lienzo | 404 |

Cada proyecto exige membresia y lps.indicadores.ver. Un proyecto no autorizado produce 403 sin
datos. En multi-scope, role=MULTI es descriptivo y no concede nada.

### Filtros y periodo

La hoja soporta:

- project_ids/project_id;
- semana en scope simple;
- desde/hasta en scope multiproyecto o rango explicito;
- sub/resp/etapa;
- aliases legacy;
- opciones contextuales T03.

Rango reemplaza semana cuando existe. Cada proyecto conserva su cutoff. Los detalles reciben
exactamente la misma query base que el brief; al abrir, no vuelven a interpretar el scope desde
variables globales.

### Brief legacy

getBrief programa-general entrega:

- executive_brief;
- scorecard;
- charts;
- activity_snapshot;
- drivers;
- risks;
- recommended_actions;
- lineage;
- filtros, fuente y conteo.

El scorecard contiene:

- porcentaje avance fisico;
- porcentaje avance teorico;
- desviacion vs plan;
- criticas atrasadas;
- total actividades.

Los charts contienen:

1. programa-curva-ejecucion.
2. programa-gauge.
3. programa-compliance.
4. programa-dias-retraso.
5. programa-cnp.
6. programa-cnc.
7. programa-radar-productividad.

El target deja de transportar presentación Chart.js como contrato principal. Emite datos de dominio
y mantiene un adapter chart legacy mientras haya consumidores.

### Avance y cumplimiento

El avance:

- excluye Titulos;
- usa el ultimo corte valido por proyecto;
- pondera por duracion calendario inclusiva;
- limita Ejecutado a 0..1;
- calcula teorico por dias transcurridos;
- agrega multi-proyecto con denominador global de duracion;
- conserva project_cutoffs;
- calcula real_pct, theoretical_pct, compliance_pct, gap_pp y status.

scheduleCompliancePct:

- real/planned por 100;
- maximo de presentacion 150;
- planned=0 y real>0 devuelve 100;
- planned=0 y real=0 devuelve 0.

S18 conserva la formula y la acompaña con texto/denominador; no presenta 150 como porcentaje fisico.

### Actividades y composición

activity_snapshot devuelve las primeras 25 actividades ordenadas por:

1. mayor recoverable_pp;
2. ruta critica;
3. mayor real_contribution_pp;
4. fin planificado;
5. activity_key.

Cada actividad incluye:

- project/project_id, week, unique_id, id y activity_key;
- actividad/etapa;
- inicio/fin/corte;
- duracion/peso;
- real/planned/gap;
- aporte real/planificado/recuperable;
- estado, critica, atrasada y dias observados;
- responsable/subcontratista;
- bloqueo dominante.

Progress detail añade:

- sort all/missing/earned;
- critical_only;
- groups por project/stage/responsible/subcontractor;
- limit 1..100;
- offset, total, returned_count, has_more y next_offset.

### Forecast y retraso

pg_finish_variance_days_p50:

- usa toda la historia hasta el corte, no solo Semana = X;
- simula por proyecto;
- exige linea contractual declarada;
- exige proyeccion disponible y muestra completa;
- usa 240 simulaciones;
- agrega portafolio como max completion date por simulacion y luego percentiles;
- devuelve P10/P50/P90, variation_days, project breakdown, metodo y muestra;
- unavailable conserva reason y no fabrica fechas.

pg_observed_activity_delay_days:

- cuenta actividades incompletas cuyo fin planificado es anterior al cutoff;
- publica count, sum y max;
- ordena criticas primero y luego dias;
- advierte que dias paralelos no equivalen a retraso del proyecto.

El legacy ya separa ambos en delay detail. El target hace esa separación visible también en el
lienzo.

### Radar

Los tres ejes vigentes son:

| Eje | Formula | Poblacion |
|---|---|---|
| productividad, rotulo Avance promedio | promedio min(P_Completado,1) por valor valido | activas no TNP |
| eficiencia | promedio Ejecutado_Real/Compromiso por fila | compromiso>0, ejecutado>=0, no TNP |
| desempeno | PAC=1 / PAC en 0,1 | activas no TNP |

Cada eje:

- exige minimo tres registros;
- conserva raw_value;
- limita display_value a 100;
- conserva numerator/denominator;
- publica warning si falta muestra o eficiencia supera 100;
- desglosa por proyecto;
- tiene formula y fuente.

El nombre viejo Productividad no describe medir_productividad. El codigo ya lo renombro Avance
promedio; S18 lo fija como contrato.

### CNP/CNC

CNP:

- universo Activa='0' y CNP no vacia;
- actividad unica project_id+Semana+Consecutivo;
- categoria canónica sin truncar original;
- critica, inicio vencido/proximo, sin responsable/subcontratista;
- impacto y accion recomendada read-only.

CNC:

- universo Activa IN ('1','NA') y CNC no vacia;
- cantidades comprometida/ejecutada;
- completion/shortfall cuando comparables;
- sin ejecucion, parcial, brecha 50 o mas, observacion faltante;
- prioridad, impacto y accion read-only.

Los detalles soportan category, load more y include_summary=false. operational_link es null,
action_available=false y read_only=true: no son editores.

### Brechas medibles

- El titular legacy no abre con el P50 en la forma N3 aprobada.
- El forecast es una tarjeta secundaria y usa lenguaje tecnico P50/P10/P90.
- La serie, dos donas, seis tarjetas y tabla compiten por prioridad.
- Riesgos viaja en el brief pero no se pinta.
- Linaje viaja pero no se pinta.
- Valor de cronograma en dinero no existe.
- El radar se llama bien en datos, pero el id legacy conserva productividad.
- Los canvas no tienen lectura tabular equivalente completa.
- Double-click desktop es una interacción escondida.
- Los detalles usan fetch directo.
- Compliance detail no tiene offset/pagination completa.
- CNP/CNC ocupan dos cards y detalles en una hoja donde el diseño aprobado pide solo titular.
- La vista y dialogs se montan aunque la hoja no sea activa.

## Diseño funcional objetivo

### Orden del lienzo

1. encabezado de hoja y alcance;
2. titular de terminacion;
3. riesgo de fecha y desempeño de cronograma en dinero, si disponible;
4. avance/cumplimiento y serie;
5. radar operativo;
6. actividades que explican el corte;
7. titular causal CNP/CNC con destino Semanal;
8. evidencia, limitaciones y linaje.

El usuario puede contestar primero cuando termina y por qué; los detalles quedan disponibles sin
dominar el primer viewport.

### Titular N3

Cuando el forecast es available:

> Terminación probable: 15 de marzo — entre el 2 de marzo y el 4 de abril. 88 días después de la
> comprometida.

Reglas:

- texto viene del servidor por plantilla;
- nunca muestra P50 en el titular;
- nunca usa signo/color solos;
- en ahead dice dias antes;
- en on_time dice en la fecha comprometida;
- multi-proyecto muestra fecha del alcance y ofrece breakdown;
- debajo: 240 simulaciones, minimo de incrementos, cutoff y confianza;
- unavailable muestra reason y no una fecha cero.

### Riesgo combinado

S18 presenta:

- top riesgos de actividad del scope;
- score RISK-SCORE-1.0;
- probabilidad, impacto, urgencia, criticidad y confianza;
- restricciones abiertas/sin gestionar;
- antigüedad observable por semana/cutoff o fecha compromiso;
- cuantas tocan ruta critica;
- drivers y entidad.

No crea otra formula. El score vigente sigue:

    35*probability + 25*impact + 20*urgency + 10*criticality + 10*confidence

La pantalla explica que:

- es deterministico y calibrable;
- no es probabilidad estadistica;
- no se promedia entre entidades;
- antigüedad y ruta critica son evidencia adicional del riesgo;
- una ruta critica mal mantenida limita la lectura.

### Desempeño de cronograma en dinero

La lectura aprobada es schedule value, no control de costo.

Fuentes read-only:

- version activa y no obsoleta de pdc_presupuesto_versiones;
- valores de pdc_insumo_actividades;
- unique_id amarrado al cronograma;
- programa_consolidado al cutoff.

Por actividad:

- budgetValue = suma de valor amarrado a unique_id;
- plannedValue = budgetValue por plannedProgress al cutoff;
- earnedValue = budgetValue por realProgress al cutoff.

Por scope:

- PV = suma plannedValue;
- EV = suma earnedValue;
- scheduleVariance = EV - PV;
- SPI = EV/PV cuando PV>0;
- budgetCoverage = valor amarrado / costo positivo de version activa;
- activityCoverage = actividades con valor amarrado / actividades de presupuesto aplicables.

Contrato discriminado:

| status | Regla | UI |
|---|---|---|
| available | presupuesto activo, valor positivo y amarre suficiente | PV/EV/SV/SPI + cobertura |
| partial | alguna obra/actividad tiene valor, pero cobertura no es total | cifras rotuladas al alcance cubierto + warning |
| insufficient | sin version, valor o amarre util | no muestra cifra; dice qué falta |

No se reparte valor huerfano. Multi-scope publica breakdown y no oculta obras insufficient.

Copy obligatorio:

> Desempeño de cronograma valorado con presupuesto y APU. No incluye costo real, facturado,
> causado ni pagado; por tanto no calcula desempeño de costos.

No se muestra CPI, CV, BAC/EAC de costos ni sobrecosto.

### Avance y cumplimiento

Se muestran como metricas textuales y barras/progress:

- avance real;
- avance teorico;
- brecha en puntos;
- cumplimiento;
- cutoff;
- peso total y actividades incluidas;
- status textual.

La serie usa SVG accesible:

- teorica;
- real;
- probable;
- optimista;
- pesimista;
- toggle proyecciones;
- tabla de puntos equivalente;
- estado projection unavailable/partial;
- enlace a S19 para la Curva S completa.

No usa canvas sin alternativa textual.

### Radar

- SVG 0–100, tamaño protagonista secundario;
- tres ejes con labels completos;
- null produce hueco/estado No disponible, nunca 0;
- raw>100 se muestra en texto y se limita solo el poligono;
- tabla equivalente con raw/display/sample/formula/status;
- cada eje abre detalle y linaje;
- flechas/Home/End cambian tabs en drawer;
- detalle muestra elegibilidad y causa de exclusión por registro.

### Actividades

Desktop/tablet: tabla semantica.

Movil: tarjetas equivalentes.

Controles:

- todas;
- lo que mas falta;
- lo que ya suma;
- solo ruta critica;
- agrupar por proyecto, etapa, responsable o subcontratista;
- cargar mas.

El servidor ordena/pagina. React no reordena páginas parciales como si fueran el universo. Una nueva
query cancela la anterior y reinicia offset. Load more deduplica activity_key y conserva registros
si falla.

### CNP/CNC y transición a S21

En el lienzo S18 solo aparece:

- conteo;
- principal categoria;
- critica/sin asignar o brecha severa;
- frase causal;
- CTA Ver causas en Programacion Semanal.

Durante la convivencia, los detalles S18 se migran a un componente compartido y siguen disponibles
para no perder capacidad. Cuando S21 corte:

- el CTA canónico navega a /bi/semanal con focus=cnp|cnc y category;
- deep links S18 se redirigen conservando query;
- los endpoints siguen como contratos compartidos hasta cero callers;
- la conversación causal vive en S21;
- S18 no duplica la UI final.

### Drilldowns

| Detalle | Apertura | Paginacion |
|---|---|---|
| cumplimiento | CTA visible, no dblclick secreto | target limit/offset/total/next |
| aporte al avance | CTA/actividad | limit/offset/sort/critical_only |
| fecha/retraso | titular/CTA | limit/offset |
| radar | eje/CTA | axis/limit/offset |
| CNP | titular/category | category/limit/offset/include_summary |
| CNC | titular/category | category/limit/offset/include_summary |

Todos:

- drawer/dialog T03 accesible;
- URL focus reproducible;
- loading/empty/error/retry;
- request race control;
- load more;
- foco inicial/retorno/Escape;
- tabla >=768 y cards <768;
- sin mutaciones.

## Contratos HTTP target

### Envelope

Cada ruta retorna:

    { "ok": true, "data": { } }

o:

    {
      "ok": false,
      "error": {
        "code": "BI_PROGRAM_UNAVAILABLE",
        "message": "No pudimos cargar Programa General.",
        "retryable": true,
        "fieldErrors": {}
      }
    }

Durante convivencia puede incluir respuesta=BIEN fuera del bloque canónico.

### Brief canónico

data:

- reportKey=programa-general;
- scope/period/filters;
- headline;
- forecast;
- progress;
- executionSeries;
- risk;
- scheduleValue;
- radar;
- activitySnapshot;
- causesHeadline;
- actions;
- lineage;
- meta/partialFailures.

No incluye configuracion Chart.js como contrato React.

### Detail canónico

Cada detalle incluye:

- reportKey específico;
- metricKeys;
- scope/period/filters;
- summary;
- records/activities;
- pagination uniforme;
- lineage;
- meta.

Paginacion:

- limit 1..100;
- offset >=0;
- total;
- returnedCount;
- nextOffset;
- hasMore.

Compliance detail gana offset target conservando limit. El adapter legacy puede seguir aceptando
solo limit hasta migrar callers.

### Validación

- axis: productividad|eficiencia|desempeno;
- sort: all|missing|earned;
- critical_only/include_summary: boolean canónico;
- category: trim, limite y catálogo no autoritativo;
- limit/offset acotados;
- filtros T03;
- authority keys rechazadas;
- project scope revalidado en cada detalle.

### Errores

| HTTP | code |
|---:|---|
| 400/422 | BI_PROGRAM_QUERY_INVALID |
| 403 | BI_PROJECT_SCOPE_DENIED |
| 404 | NOT_FOUND |
| 409 | BI_PROGRAM_PERIOD_NOT_COMPARABLE |
| 500 | BI_PROGRAM_UNAVAILABLE |
| 500 parcial | success con meta.partialFailures por seccion |

Un error de schedule value no borra forecast/avance si el snapshot sigue coherente. Cada sección
declara status; no se mezclan cortes.

## Zod y gateway

Schemas:

- biProgramaGeneral.ts;
- biProgramaCumplimientoDetalle.ts;
- biProgramaAvanceDetalle.ts;
- biProgramaRetrasoDetalle.ts;
- biProgramaRadarDetalle.ts;
- biProgramaCausalDetalle.ts parametrizado por cnp/cnc.

Tipos solo con z.infer.

Refinamientos:

- forecast available exige fechas/rango/240 samples;
- unavailable exige reason y fechas null;
- P10<=P50<=P90;
- variation coherente con fechas;
- progress porcentajes finitos y gap=real-planned dentro de tolerancia;
- scheduleValue available/partial exige moneda, coverage y PV/EV;
- insufficient no contiene cifras;
- no cost fields;
- radar denominator/sample coherentes;
- unavailable axis tiene raw/display null;
- display<=100;
- activity_key unico por página;
- pagination coherente;
- causal read_only=true/action_available=false;
- project IDs positivos;
- dates ISO.

Gateways usan solo frontend/src/lib/api/cliente.ts. Ningun componente llama fetch.

## Estado frontend

### Snapshot principal

- queryKey canónica;
- requestId/AbortSignal;
- reemplazo atomico;
- stale response ignorada;
- refetch rotulado;
- partial por seccion;
- cache no cruza usuarios/scope.

### Detalles

Cada drawer tiene:

- closed;
- loading initial;
- ready;
- empty;
- loading more;
- append error conservando rows;
- fatal error;
- retry;
- requestId y dedupe.

Cambiar axis/sort/category/critical:

- actualiza focus/query;
- limpia paginas;
- aborta;
- vuelve a offset 0;
- no mezcla resultados.

### Estados visibles

- loading;
- ready;
- partial;
- empty;
- insufficient forecast;
- insufficient budget;
- partial radar;
- empty causes;
- offline;
- invalid query;
- server error.

Un cero válido se distingue de sin datos.

## Responsive y accesibilidad

### Desktop 1180x820 y 1440x900

- titular y decisión arriba;
- dos columnas para riesgo/value cuando caben;
- avance/serie y radar en grid;
- activity table;
- drawers laterales o dialog amplio;
- evidencia colapsada.

### Tablet 768x1024

- una columna para bloques complejos;
- tabla de actividades/detalles preservada;
- filtros T03 drawer;
- SVG refluye.

### Movil 390x844 y 480x900

- cards de actividad/detalle, no tabla encogida;
- titular en frases;
- schedule value compacto con copy completo;
- radar + tabla de ejes;
- drawers full-height;
- CTA visible en vez de double-click;
- no overflow de página.

### A11y

- h1 unico;
- headings jerarquicos;
- cifras con label/unidad/base;
- SVG title/desc;
- tabla alternativa de cada visual;
- no color-only;
- botones con aria-expanded/controls;
- tabs radar ARIA y teclado;
- dialog focus trap/return/Escape;
- progress con texto;
- fechas datetime;
- live regions solo tras acciones;
- 44x44;
- 200 por ciento zoom;
- reduced motion;
- axe serious/critical cero;
- consola limpia.

## Design system

- tokens.css exclusivamente;
- oscuro default/fallback;
- claro idéntico;
- sin colores literales;
- sin Chart.js nuevo;
- SVG usa custom properties/tokens;
- severidad textual + rail/tint canónicos;
- null/insufficient neutro;
- no utility classes legacy;
- no !important nuevo;
- cualquier token nuevo se documenta y prueba.

## Seguridad, aislamiento y RLS

- T03 sheet policy autoriza A/D/R.
- BiPreviewAccessPolicy sigue ocultando con 404.
- BiProjectScope/MultiProjectScope decide proyectos.
- Cada lectura y detalle revalida scope.
- Presupuesto/amarre se filtra por project_id y version activa del mismo proyecto.
- unique_id nunca se cruza sin project_id.
- Linea base se lee por project_id.
- Query no acepta role/db/prefix.
- Cache key incluye usuario, scope, periodo, filtros y detalle.
- Error no filtra proyecto ni fuentes internas.
- Todos los endpoints son GET.
- Tripwire browser falla en cualquier mutación.
- No se toca RLS, schema, SQL views, grants, usuarios, credenciales o datos.

Tests nuevos usan fakes; no MySQL. Los tests BI existentes con fixtures mutantes sirven como
referencia de formulas, no se ejecutan dentro del gate seguro de esta entrega sin autorización.

## Arquitectura objetivo

### Backend

- BiProgramQuery usa T03 BiQuery.
- BiProgramReadService orquesta snapshot seccional.
- BiProgramProjectReader port.
- PdoBiProgramProjectReader adapter read-only.
- BiProgramForecastAdapter encapsula el algoritmo existente.
- BiProgramProgressAdapter encapsula avance/detalles.
- BiProgramRiskAdapter encapsula RiskScoringService y exposición de restricciones.
- BiProgramScheduleValueReader calcula PV/EV/SPI con budget coverage.
- BiProgramRadarAdapter.
- BiProgramCausalAdapter.
- BiProgramPresenter y detail presenters.
- BiControlTowerApiController queda transport thin.
- ControlTowerService conserva adapters legacy.

No se copia el algoritmo de 3.817 líneas a otra clase de una vez. Se extrae por seams con tests de
caracterización y se delega, preservando formulas.

### Frontend

Propuesta:

- frontend/src/modulos/bi/ProgramaGeneralBiPagina.tsx
- frontend/src/modulos/bi/programa/TitularTerminacion.tsx
- frontend/src/modulos/bi/programa/RiesgoPrograma.tsx
- frontend/src/modulos/bi/programa/ValorCronograma.tsx
- frontend/src/modulos/bi/programa/ResumenAvance.tsx
- frontend/src/modulos/bi/programa/SerieEjecucion.tsx
- frontend/src/modulos/bi/programa/RadarPrograma.tsx
- frontend/src/modulos/bi/programa/ActividadesPrograma.tsx
- frontend/src/modulos/bi/programa/TitularCausas.tsx
- frontend/src/modulos/bi/programa/DetalleCumplimiento.tsx
- frontend/src/modulos/bi/programa/DetalleAvance.tsx
- frontend/src/modulos/bi/programa/DetalleRetraso.tsx
- frontend/src/modulos/bi/programa/DetalleRadar.tsx
- frontend/src/modulos/bi/programa/DetalleCausal.tsx
- frontend/src/modulos/bi/programa/useProgramaGeneralBi.ts
- frontend/src/modulos/bi/programa/useDetalleBi.ts
- frontend/src/lib/api/esquemas/biPrograma*.ts
- frontend/src/lib/api/biProgramaGeneral.ts
- CSS en capa de modulo.

T03 aporta marco, filtros, responsive primitive, drawer y linaje.

## Convivencia y retiro

### Corte

1. caracterizar contratos;
2. estabilizar API y Zod;
3. shadow route;
4. comparar fixtures;
5. probar roles/themes/viewports;
6. cortar /bi/programa-general;
7. conservar adapters legacy.

### Retiro diferido

S18 no elimina dialogs/section de control-tower.php porque el archivo sigue compartido. T03 elimina
al final:

- section view-programa-general;
- seis dialogs;
- funciones render/fetch PG en bi-spa.js;
- CSS exclusivo;
- Chart.js bindings;
- imports/vendors sin otro caller.

CNP/CNC detail UI se retira de S18 solo cuando S21 prueba su destino canónico y deep links.

### Rollback

- devolver /bi/programa-general al render legacy;
- conservar contratos canónicos;
- no restaurar datos;
- verificar A/D/R permitido y V denegado;
- probar forecast available/unavailable;
- restaurar React.

## Estrategia de pruebas

### PHP puro

- sheet access A/D/R;
- query y scope;
- brief presenter;
- forecast available/ahead/delayed/on_time/unavailable;
- percentiles y project breakdown con fixtures estáticos;
- linea contractual ausente;
- retraso observado separado;
- progress y contribution order;
- pagination/sort/critical;
- compliance pagination;
- risk formula/drivers;
- schedule value available/partial/insufficient;
- PV/EV/SPI y coverage;
- no cost metrics;
- radar 3 axes/min sample/raw/display;
- axis eligibility;
- CNP/CNC universes/categories/priorities;
- detail pagination;
- no authority keys;
- denied payload empty;
- source invariants read-only/project-scoped.

No Database.

### Frontend

- todos los Zod/refinements;
- gateway solo cliente;
- main/detail state machines;
- titular N3;
- unavailable copy;
- risk explanation;
- schedule value/no-cost copy;
- series SVG/table/toggle;
- progress/cumplimiento;
- radar partial/null/raw>100/keyboard;
- activities table/cards/load more;
- CNP/CNC headlines and transition;
- six details;
- lineage;
- dark/light/responsive/a11y.

### Browser interceptado

- T03 context antes de navegar;
- siete GET interceptados;
- toda mutación bloqueada;
- A/D/R permitted, V/hidden denied;
- single/multi project;
- week/range/filters;
- forecast states;
- budget states;
- partial risk/radar;
- six details and pagination failures;
- deep links/focus;
- five viewports;
- dark/light;
- 200 zoom/reduced motion/keyboard;
- no overflow;
- axe/consola;
- cero mutaciones.

## Criterios de aceptacion

1. S18-AC-01: /admin/ queda fuera.
2. S18-AC-02: /bi/programa-general es ruta SPA al corte.
3. S18-AC-03: S18 consume T01/T03 y no duplica shell.
4. S18-AC-04: A/D/R autorizados segun gate/lienzo.
5. S18-AC-05: otros roles reciben 404.
6. S18-AC-06: proyecto no autorizado recibe 403 sin datos.
7. S18-AC-07: projectId cliente no autoriza.
8. S18-AC-08: semana/rango/sub/resp/etapa se preservan.
9. S18-AC-09: cada detalle reutiliza la query canónica.
10. S18-AC-10: las siete rutas tienen contrato PHP/Zod.
11. S18-AC-11: no se crea endpoint paralelo.
12. S18-AC-12: authority keys se rechazan.
13. S18-AC-13: titular usa fecha+rango+brecha en palabras.
14. S18-AC-14: titular no dice P50.
15. S18-AC-15: linea contractual es declarada.
16. S18-AC-16: forecast usa 240 simulaciones.
17. S18-AC-17: minimo de historia se respeta.
18. S18-AC-18: P10<=P50<=P90.
19. S18-AC-19: unavailable no fabrica fechas.
20. S18-AC-20: multi-scope conserva breakdown/cutoffs.
21. S18-AC-21: retraso probable y observado están separados.
22. S18-AC-22: retraso observado advierte limite de paralelismo.
23. S18-AC-23: avance real/teorico pondera duracion inclusiva.
24. S18-AC-24: cumplimiento conserva formula y base.
25. S18-AC-25: gap es real menos teorico.
26. S18-AC-26: serie conserva real/teorica/3 proyecciones.
27. S18-AC-27: toggle proyecciones es accesible.
28. S18-AC-28: serie tiene tabla textual equivalente.
29. S18-AC-29: link a S19 conserva scope.
30. S18-AC-30: activity snapshot inicia con 25.
31. S18-AC-31: activity fields/aportes/bloqueos se conservan.
32. S18-AC-32: progress detail soporta all/missing/earned.
33. S18-AC-33: progress detail soporta critical_only.
34. S18-AC-34: grouping project/stage/responsible/subcontractor.
35. S18-AC-35: pagination 1..100/offset/total/next coherente.
36. S18-AC-36: load-more deduplica y conserva rows al fallar.
37. S18-AC-37: riesgo combinado se pinta.
38. S18-AC-38: formula/partes/limitaciones de riesgo se explican.
39. S18-AC-39: risk score no se promedia.
40. S18-AC-40: antigüedad/restricciones criticas se muestran.
41. S18-AC-41: schedule value usa presupuesto activo/no obsoleto.
42. S18-AC-42: amarre exige project_id+unique_id.
43. S18-AC-43: PV/EV/SV/SPI siguen formulas declaradas.
44. S18-AC-44: coverage por valor/actividad se muestra.
45. S18-AC-45: partial rotula alcance cubierto.
46. S18-AC-46: insufficient no muestra cifra.
47. S18-AC-47: no se reparte valor huerfano.
48. S18-AC-48: copy declara ausencia de costo real.
49. S18-AC-49: no hay CPI/CV/sobrecosto.
50. S18-AC-50: radar usa Avance promedio/Eficiencia/Desempeño.
51. S18-AC-51: radar escala visual fija 0–100.
52. S18-AC-52: raw>100 se conserva y display se limita.
53. S18-AC-53: eje exige muestra minima 3.
54. S18-AC-54: eje unavailable no se vuelve cero.
55. S18-AC-55: cada eje muestra numerador/denominador/formula.
56. S18-AC-56: radar excluye TNP y valores invalidos.
57. S18-AC-57: detail radar conserva elegibilidad/exclusiones.
58. S18-AC-58: radar tiene SVG+tabla y tabs teclado.
59. S18-AC-59: CNP universo Activa=0+CNP no vacia.
60. S18-AC-60: CNC universo Activa 1/NA+CNC no vacia.
61. S18-AC-61: categorias conservan original/canónica/known.
62. S18-AC-62: cantidades CNC invalidas producen unknown.
63. S18-AC-63: causal details son read-only.
64. S18-AC-64: lienzo muestra solo titular causal.
65. S18-AC-65: detalles siguen disponibles durante convivencia.
66. S18-AC-66: destino final causal es S21 con focus/category.
67. S18-AC-67: seis detalles tienen loading/empty/error/retry.
68. S18-AC-68: seis detalles tienen foco/Escape/retorno.
69. S18-AC-69: details usan tabla >=768/cards <768.
70. S18-AC-70: cada metrica tiene linaje visible.
71. S18-AC-71: main snapshot no mezcla cortes/secciones.
72. S18-AC-72: stale detail response se ignora.
73. S18-AC-73: retry/load son GET y cero mutaciones.
74. S18-AC-74: solo cliente.ts llama fetch.
75. S18-AC-75: dark/light capacidad idéntica.
76. S18-AC-76: cinco viewports sin page overflow.
77. S18-AC-77: 200% zoom conserva lectura/acciones.
78. S18-AC-78: no color-only/hover/dblclick-only.
79. S18-AC-79: teclado/touch/focus/reduced motion cumplen.
80. S18-AC-80: axe serious/critical cero y consola limpia.
81. S18-AC-81: solo tokens, sin color literal/important.
82. S18-AC-82: PHP tests nuevos usan fakes/no MySQL.
83. S18-AC-83: browser intercepta siete GET y bloquea mutaciones.
84. S18-AC-84: legacy compartido se retira solo con T03/S17–S24.
85. S18-AC-85: rollback no restaura datos.
86. S18-AC-86: RLS/schema/grants/usuarios/credenciales/datos no cambian.
87. S18-AC-87: no se regenera golden sin aprobacion.

## Entregas verticales

### Entrega 1 — Contratos y decision de fecha

- query/permissions;
- brief/detail schemas;
- forecast/titular;
- progreso básico;
- lineage.

### Entrega 2 — Explicacion operativa

- actividades;
- compliance/progress/delay details;
- risk;
- estados parciales.

### Entrega 3 — Radar y valor de cronograma

- radar corregido/detail;
- schedule value/coverage/no-cost;
- pruebas de insuficiencia.

### Entrega 4 — Causas, calidad y corte

- titulares/detalles/transicion S21;
- responsive/a11y/themes;
- browser;
- corte/rollback/retiro diferido.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| P50 se lee como certeza | rango, metodo, muestra y confidence |
| Falta linea contractual | unavailable, nunca deducir de corte |
| Retrasos por actividad se suman como proyecto | separación y limitación visible |
| Valor en dinero parece costo | copy obligatorio y ausencia estructural de CPI |
| Cobertura parcial infla conclusion | status partial + coverage + scope |
| Amarre cruza proyectos | project_id+version+unique_id |
| Presupuesto obsoleto contamina | solo version activa/no obsoleta |
| Radar null parece cero | unión discriminada/null visible |
| Eficiencia >100 rompe escala | raw visible, display cap 100 |
| CNP/CNC duplican Semanal | titular aquí, destino S21, adapter temporal |
| Load more mezcla filtros | queryKey/requestId/reset offset |
| Seven schemas divergen | building blocks Zod compartidos + PHP fixtures |
| S18 reescribe motor gigante | extracción por seam/delegación |
| Tests mutan BD | fakes/interception |
| Retiro rompe otras hojas | gate T03 conjunto |

## Decisiones descartadas

- Copiar UI legacy uno a uno: jerarquía y accesibilidad deficientes.
- Titular con P50 técnico: N3 exige lenguaje de fecha.
- Deducir línea base del primer/último corte: ya produjo regresión.
- Sumar días observados: actividades paralelas.
- Canvas sin tabla: inaccesible.
- Nueva libreria chart: SVG suficiente.
- Productividad desde medir_productividad: fuente vacia/incorrecta.
- Cero para eje sin muestra: dato inventado.
- Promediar eficiencia con unidades sumadas: formula falsa.
- Valor ganado desde cantidad_ppto: no es valor monetario reconciliado.
- Inventar precio promedio: sintético prohibido.
- Calcular CPI sin costo real: imposible.
- Mostrar cifras solo de obras con presupuesto sin aviso: sesgo.
- Recalcular amarres al leer: DML sorpresa.
- Hacer CNP/CNC protagonistas: contradice D39.
- Quitar detalles ya: rompe paridad antes de S21.
- Mantener dblclick como unica acción: inaccesible.
- Fetch por componente: rompe contrato frontend.
- Probar con base real/rollback: sigue siendo DML.

## Decisiones pendientes

Ninguna. Si la implementación descubre una fuente de costo real reconciliada, una version de
presupuesto activa marcada obsoleta con semantica distinta, un cambio aprobado del modelo de riesgo
o un consumidor externo de los chart payloads, debe detener solo ese tramo, aportar evidencia y
enmendar esta spec. No se inventa la nueva semantica.

## Siguiente gate

Invocar superpowers:writing-plans para
docs/superpowers/plans/2026-08-30-s18-bi-programa-general-react.md, autorrevisarlo, actualizar el
atlas y continuar S19. No implementar S18 en esta sesion.
