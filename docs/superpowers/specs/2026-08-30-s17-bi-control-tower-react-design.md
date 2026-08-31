---
capa: fuente
tipo: spec
estado: autorrevisado
id: S17
fecha: 2026-08-31
superficie: bi-control-tower
rutas:
  - "/bi/control-tower"
  - "/api/bi/control-tower"
depende_de: [T01, T03, S18, S19, S20, S21, S22, S23, S24]
views: []
areas: [arquitectura, frontend, bi, control-tower, rbac, accesibilidad, design-system, contratos]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, BiPreviewAccessPolicy, BiProjectScope, BiAccessComponent, ControlTowerService, StorytellingService, RiskScoringService, ActionRecommendationService, LineageService, MetricDictionaryService, vistas BI, bi-spa.js, ct-app, specs aprobadas y pruebas en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S17 del Resumen Ejecutivo de Control Tower a React y TypeScript: hoja de gerencia con alcance multiproyecto autorizado, titular auditable, panorama ordenado por obra, scorecard y evidencia recuperables, riesgos, acciones con dueño y fecha, drilldowns entre hojas y linaje visible; oscuro/claro, responsive y accesible, sin escrituras, RLS, schema ni datos."
---

# S17 — Resumen Ejecutivo de Control Tower en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que bloqueen su plan. Esta spec no autoriza implementacion, commits,
> DDL/DML, cambios RLS, cambios de grants, usuarios, credenciales o datos, publicacion, deploy ni
> trabajo en /admin/. Su plan se escribe a continuacion con superpowers:writing-plans dentro del
> programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

S17 migra la superficie navegable /bi/control-tower, hoja 8.1 Resumen Ejecutivo. No posee por si
sola ninguna de las vistas PHP compartidas: T03 es propietario de VIEW-04 a VIEW-09 y del retiro
final de:

- views/bi/_filters.php;
- views/bi/_layout.php;
- views/bi/_nav.php;
- views/bi/control-tower-piloto.php;
- views/bi/control-tower.php;
- views/bi/index.php, si se confirma que no tiene consumidor.

S17 consume un incremento vertical de T03 para ruta, marco, filtros, proyectos, periodo, permisos,
cliente, estados y drawer de linaje. Las hojas S18 a S24 reciben los drilldowns. No se duplica el
marco ocho veces ni se espera a terminar todo T03 para entregar una primera hoja funcional.

La fuente de producto vigente es la spec v0 aprobada del 2026-08-26:

- la decision es en que obra debe intervenir gerencia esta semana;
- es la unica hoja que compara obras;
- el lienzo abre con titular narrativo, panorama por obra y acciones con nombre y fecha;
- el orden del panorama es la señal;
- PAC/PPC no se repiten aqui porque viven en Programacion Semanal;
- cada cifra conserva formula, denominador, corte, fuente, limitaciones y estado de completitud;
- la Torre se construye en React y TypeScript con tokens del design system;
- oscuro, claro, 1180x820 y 390x844 son gates, no variantes opcionales.

## Resultado buscado

/bi/control-tower pasa a la SPA principal y:

1. permanece oculto si el gate global BI no permite abrirlo;
2. monta Resumen Ejecutivo solo en el lienzo de gerencia aprobado;
3. usa exclusivamente proyectos autorizados por membresia y capacidad efectiva;
4. abre por defecto comparando todos los proyectos BI autorizados del usuario de gerencia;
5. conserva selector de proyectos y periodo por el contrato compartido de T03;
6. mantiene URL como estado reproducible y compartible, sin autoridad de seguridad;
7. entrega un titular narrativo por reglas auditables, nunca IA generativa;
8. muestra una fila por obra con restricciones, desviacion y tendencia;
9. ordena las obras de forma determinista por señales adversas, sin puntaje opaco;
10. resalta como maximo una obra: la primera que requiere intervencion;
11. no usa color como unico portador de severidad;
12. conserva scorecard, drivers y riesgos como evidencia secundaria;
13. elimina del lienzo los graficos duplicados PPC semanal y PAC vs Programado;
14. presenta acciones recomendadas con obra, dueño, fecha, evidencia e impacto;
15. navega cada accion al desglose correcto preservando scope y periodo;
16. hace visible el linaje de toda metrica mostrada;
17. distingue dato completo, parcial, insuficiente, no disponible y error;
18. conserva denominador junto a cualquier porcentaje;
19. evita promediar porcentajes entre obras;
20. no crea, cierra ni persiste acciones desde S17;
21. ofrece tabla panoramica en desktop/tablet y tarjetas equivalentes en movil;
22. soporta oscuro y claro solo con tokens canonicos;
23. modela carga, recarga, error, vacio, sin proyectos, parcial y offline;
24. satisface teclado, foco, lectores, zoom, touch y reduced motion;
25. deja la ruta legacy como fallback temporal hasta que el corte sea verificable;
26. no toca RLS, schema, grants, usuarios, credenciales ni datos.

## Alcance

### Incluido

- GET /bi/control-tower como ruta SPA al corte.
- GET /api/bi/control-tower como contrato JSON tipado.
- La porcion de T03 necesaria para montar una hoja.
- Gate oculto BI y composicion del lienzo por rol.
- Scope multiproyecto server-authoritative.
- Default de todos los proyectos autorizados para Resumen Ejecutivo.
- Selector de subconjunto de proyectos autorizados.
- Periodo efectivo y cortes por proyecto.
- Filtros compartidos desde/hasta, subcontratista, responsable y etapa.
- Alias de query durante convivencia.
- Titular de cinco piezas y su plantilla/variables.
- Panorama por proyecto.
- Restricciones huerfanas.
- Preparacion de restricciones con numerador/denominador.
- Terminacion probable y rango cuando la metrica sea publicable.
- Brecha contra fecha comprometida en palabras.
- Tendencia entre cortes comparables.
- Regla determinista de prioridad.
- Scorecard de ocho KPI actuales como evidencia.
- Drivers explicativos ligados a una obra y una señal.
- Riesgos con nivel, confianza, fuente y entidad.
- Acciones con dueño, fecha, impacto, evidencia y destino.
- Linaje visible por metrica.
- Drilldowns por navegacion a S18–S24.
- Estados completos y parciales.
- Desktop, tablet, movil, oscuro, claro y accesibilidad.
- Contrato PHP, esquema Zod, Vitest, Testing Library y Playwright interceptado.
- Corte strangler, compatibilidad, retiro y rollback.

### Fuera de alcance

- Todo /admin/.
- Editar el interruptor global bi.control_tower.visible.
- Cambiar capacidades, alias de roles o fallback RBAC.
- Cambiar el modelo de membresias.
- Dar acceso a roles fuera de A, D y R.
- Montar Resumen Ejecutivo en el lienzo de obra.
- Rediseñar las otras siete hojas.
- Implementar sus drilldowns internos.
- Gestionar restricciones; corresponde a S20.
- Crear o cerrar filas de bi_action_queue.
- Invocar ActionRecommendationService::createAction o closeAction.
- Enviar correo de vispera o medir su apertura.
- Telemetria de uso.
- Reimplementar el motor de metricas.
- Cambiar formulas del catalogo para hacerlas coincidir con la UI.
- Publicar una metrica descriptiva como ejecutable sin su gate de paridad.
- Inventar datos para un proyecto incompleto.
- Promediar porcentajes de proyectos.
- Convertir ausencia de datos en cero.
- Mostrar costo real, que no tiene fuente.
- Conservar los dos graficos de PAC/PPC en Resumen Ejecutivo.
- Crear un score numerico de portafolio sin formula aprobada.
- Modificar RLS, runtime boundary, schema, indices, views SQL, migraciones, grants, usuarios,
  credenciales o datos.
- Ejecutar DDL, DML o pruebas con rollback real.
- Retirar las vistas compartidas antes de S17–S24 y T03.
- Regenerar goldens o baselines sin aprobacion.
- Implementar, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- La SPA principal solo tiene shell, login y selector de proyectos.
- No existe ruta, schema Zod, gateway, store, componentes ni pruebas S17.
- ct-app es una isla separada y solo cubre /bi/intermedia cuando CT_PILOTO=1.
- S17 no se puede implementar dentro de ct-app: el destino aprobado es la SPA principal.
- frontend/src/lib/api/cliente.ts es el unico cliente HTTP permitido.

### Inventario de rutas relevante

| ID | Metodo y ruta | Uso S17 |
|---|---|---|
| S17-LEG-01 | GET /bi/control-tower | render legacy compartido |
| S17-LEG-02 | GET /api/bi/control-tower | brief overview |
| T03-LEG-01 | GET /api/bi/projects | proyectos BI autorizados |
| T03-LEG-02 | GET /api/bi/weeks | semanas comunes del scope |
| T03-LEG-03 | GET /api/bi/filter-options | opciones contextuales |
| T03-LEG-04 | GET /api/bi/lineage | linaje global o por metric_key |
| T03-LEG-05 | GET /api/bi/control-tower/metricas/{metricKey} | metrica ejecutable |
| S18–S24 | GET /api/bi/report/* | destinos de drilldown |

Las rutas de restricciones y su POST no pertenecen a S17. La hoja puede enlazar a Intermedia, pero
no las llama ni muta.

No se crea un endpoint paralelo. Se estabiliza GET /api/bi/control-tower y se añade su schema Zod y
prueba de contrato PHP. Los endpoints compartidos quedan bajo T03, aunque el plan S17 puede
implementar el minimo que consume esta entrega.

### Gate y permisos actuales

BiPreviewAccessPolicy:

- exige internal.bi.preview;
- Admin A entra siempre;
- D y R dependen del flag global bi.control_tower.visible;
- quien no pasa recibe 404 antes de confirmar la existencia del modulo.

BiProjectScope:

- lista solo proyectos activos de Construccion o Pre-Construccion;
- exige membresia visible;
- exige lps.indicadores.ver en el rol propio de cada proyecto;
- rechaza project_ids fuera de la lista autorizada;
- devuelve MULTI cuando hay mas de un proyecto;
- hoy, si no hay query, cae al proyecto de sesion.

La spec de reparto por rol aprobada define dos lienzos:

| Lienzo | Roles | Hojas |
|---|---|---|
| Gerencia | A | Resumen Ejecutivo, Programa General, Curva S, Proveedores |
| Obra | A, D, R | Intermedia, Programa General, Semanal, Curva S, PDC, Proveedores, Responsables |

Admin A puede elegir libremente Gerencia u Obra; el lienzo es una preferencia de navegación y no
revoca su autorización por hoja. S17 permanece clasificada en Gerencia y exclusiva de A, incluso si
A tiene Obra como lienzo activo. El acceso directo actual de D/R a /bi/control-tower es una falta de
enforcement del montaje por lienzo, no una capacidad de producto que deba perpetuarse. T03 debe
aplicar una politica por hoja:

- A autorizado en overview;
- D/R reciben 404 en overview y aterrizan en Intermedia;
- el flag global sigue gobernando D/R en sus hojas;
- no se inventa una nueva capacidad;
- invalid project scope sigue siendo 403;
- un payload denegado no contiene proyectos, metricas, filtros ni linaje.

### Scope actual y brecha del resumen

La hoja aprobada es la unica que compara obras. Sin embargo:

- BiProjectScope usa el proyecto de sesion cuando no hay project_ids;
- fetchOverview agrega todos los proyectos seleccionados en una sola fila;
- se pierde el detalle por obra;
- el titular dice riesgo de la obra incluso en scope MULTI;
- no existe panorama;
- los dueños de acciones consolidadas quedan genericos;
- una accion multiproyecto puede traer due_dates_by_project pero la UI solo pinta el texto;
- el frontend no puede responder en que obra meterse.

Target: al omitir project_ids en S17, el servidor selecciona todos los proyectos BI autorizados para
ese usuario. Un subconjunto explicito sigue permitido. Esta excepcion vive en el resolver de scope
de overview, no altera el default de las otras hojas.

### Filtros y periodo actuales

El marco legacy soporta:

- project_ids repetidos o project_id;
- semana;
- desde/hasta;
- sub/resp/etapa;
- aliases fecha_desde, fecha_hasta, subcontratista y responsable;
- semana habilitada en scope simple;
- rango de fechas en scope multiproyecto;
- opciones contextuales de filtros.

La URL es estado, no autoridad. El servidor vuelve a validar cada proyecto y cada filtro. T03
normaliza una unica representacion canonica; los aliases se aceptan solo durante convivencia.

Los dias ancla varian por obra. S17 no finge que un numero de semana implica la misma fecha en todos
los proyectos. El contrato devuelve el corte efectivo por proyecto. En multiproyecto:

- desde/hasta es la representacion visible del periodo;
- sin rango explicito, T03 resuelve un rango por defecto y publica sus fechas;
- cada fila declara su cutoff;
- una obra sin corte comparable queda insufficient, no cero;
- el titular y la prioridad no mezclan cortes fuera del rango efectivo.

### Payload legacy

getBrief devuelve:

- respuesta;
- project_ids y project_id de compatibilidad;
- semana;
- report_key;
- role;
- filters;
- data_source;
- raw_row_count;
- pdc_breakdown, pdc_items y activity_snapshot aunque no apliquen;
- executive_brief;
- scorecard;
- charts;
- drivers;
- risks;
- recommended_actions;
- lineage.

El brief overview actual agrega PG, PS, PDC, CIC y CIP y calcula:

1. Que hacer.
2. Podemos.
3. Se hara.
4. Criticas atrasadas.
5. Bloqueadas por restricciones.
6. Compromisos en riesgo.
7. PDC en riesgo.
8. Contratistas en alerta.

Cada KPI tiene kpi, value, unit y status. Este contrato se conserva durante compatibilidad y se
normaliza en el DTO target; no se indexa por posicion.

### Desajustes observables del legacy

La vista rotula scorecard[0..3] como:

- PPC;
- Programadas;
- Ejecutadas;
- Brecha.

Pero el servidor realmente envia:

- Que hacer;
- Podemos;
- Se hara;
- Criticas atrasadas.

Es una incompatibilidad semantica: los numeros se muestran bajo nombres equivocados.

Ademas:

- drivers overview queda vacio porque fetchOverview produce una fila agregada sin
  restriction_type;
- risks viaja pero no se renderiza;
- lineage viaja pero no se renderiza;
- las acciones solo muestran action, omitiendo owner, due_date, evidence e impact;
- los dos charts renombran los scorecard genéricos como PPC/PAC aunque no representan esas series;
- no hay panorama por proyecto;
- no hay estados de completitud por metrica;
- no hay lectura accesible equivalente de evidencia.

S17 no caracteriza esos errores como paridad deseada. Recupera los datos validos y corrige la
presentacion contra el contrato aprobado.

## Contrato funcional objetivo

### Estructura del lienzo

El orden visible es:

1. encabezado de hoja y resumen de alcance;
2. titular narrativo;
3. panorama de obras;
4. acciones recomendadas;
5. evidencia expandible: scorecard, drivers y riesgos;
6. linaje por accion o metrica.

El primer viewport no se llena de KPIs. La pregunta en que obra intervenir queda contestada antes
de la evidencia.

### Titular narrativo

No es un string libre. El API entrega:

- templateKey estable;
- text final server-side;
- facts usados;
- confidence;
- generatedAt/cutoff;
- priorityProjectId o null;
- actionSummary.

Las plantillas son reglas finitas y probables por test. El frontend no reconstruye frases ni decide
gramatica. Como minimo:

| Condicion | Resultado |
|---|---|
| sin proyectos | estado sin alcance, fuera del payload de datos |
| proyectos pero sin señales publicables | titular de datos insuficientes |
| ninguna señal adversa | titular sano sin afirmar certeza absoluta |
| una obra prioritaria | nombra obra y causa dominante |
| empate de prioridad | aplica desempate estable y declara las razones de la primera |

No se usa IA generativa. La confianza nunca se infiere del color.

### Panorama de obras

Cada fila entrega:

- projectId;
- projectName;
- cutoff;
- coverage/completeness;
- restrictions.orphanCount;
- restrictions.readyRate con value, numerator y denominator;
- schedule.forecastStatus;
- schedule.probableFinish;
- schedule.rangeStart/rangeEnd;
- schedule.committedFinish;
- schedule.varianceDays y texto en palabras;
- trend.direction;
- trend.delta y unit cuando sea comparable;
- priority.rank;
- priority.requiresIntervention;
- priority.reasons;
- availableDrilldowns.

Reglas:

- una fila por proyecto autorizado seleccionado;
- nombre viene del servidor;
- no se omite una obra por falta de datos;
- no se transforma missing en 0;
- un porcentaje siempre incluye numerador y denominador;
- el forecast solo se publica si cumple la politica del catalogo;
- rango probable y fecha comprometida acompañan al P50;
- tendencia compara dos cortes validos de la misma obra y misma metrica;
- no se promedian porcentajes;
- multiobra agrega por sumas cuando una cifra consolidada es legitima;
- cada fila conserva projectId para drilldown.

### Regla de orden

La spec aprobada exige severidad compuesta, pero no autoriza pesos numericos. S17 evita fingir una
precision inexistente:

1. clasifica cada señal como adversa, sana o desconocida segun su propio contrato;
2. ordena primero proyectos con restricciones huerfanas;
3. dentro de ese grupo, mayor orphanCount primero;
4. luego mayor variacion positiva de terminacion;
5. luego tendencia worsening;
6. luego mayor numero de señales adversas;
7. finalmente projectName con colacion estable.

La respuesta incluye priority.reasons y nunca un score opaco. Solo la primera fila adversa lleva
requiresIntervention=true y color de severidad. Las demas conservan texto, datos y orden, sin un
semáforo por fila. Si todas las señales son sanas o desconocidas, ninguna fila se resalta.

Esta regla es server-side, pura y probada con empates, missing y orden de entrada distinto.

### Scorecard

Los ocho KPI legacy se conservan como evidencia secundaria, identificados por metricKey, no por
indice:

| Clave target | Etiqueta |
|---|---|
| activities_to_do | Que hacer |
| activities_can_do | Podemos |
| activities_will_do | Se hara |
| critical_late | Criticas atrasadas |
| hard_restriction_blocked | Bloqueadas por restricciones |
| weekly_commitments_at_risk | Compromisos en riesgo |
| pdc_at_risk | PDC en riesgo |
| contractors_at_risk | Contratistas en alerta |

Cada tarjeta incluye value, unit, status, scope, numerator/denominator si aplica, metricKey o
lineageKey, completeness y drilldown. No se rotula PPC, programadas, ejecutadas o brecha.

### Drivers

Drivers deja de intentar derivar tipos de restriccion de una fila agregada. Cada driver:

- pertenece a projectId o al alcance consolidado;
- nombra la señal;
- declara evidencia;
- declara impacto;
- declara accion sugerida;
- enlaza metricKey y destino;
- incluye completeness.

Los drivers del resumen salen de las mismas señales que ordenan el panorama. No son una segunda
formula. Si no hay driver adverso, se muestra un estado sano; si faltan datos, insuficiente.

### Riesgos

La lista conserva la forma de RiskScoringService:

- projectId;
- risk/entity;
- entityType/entityId;
- riskType;
- riskScore;
- riskLevel;
- probability;
- impact;
- confidence;
- sourceView;
- computedAt;
- drilldown.

El score no reemplaza la regla de prioridad del panorama. El usuario puede abrir detalle en la hoja
correspondiente. Nivel, texto y orden son accesibles sin color. computedAt se presenta como fecha de
cálculo, no como garantia de frescura de la fuente.

### Acciones recomendadas

Cada accion visible incluye:

- action;
- projectId y projectName;
- owner;
- dueDate;
- expectedImpact;
- evidence;
- actionType;
- status;
- targetRoute;
- targetQuery;
- targetLabel.

Reglas:

- una accion sin projectId en scope multiproyecto no se presenta como asignada a una obra;
- S17 debe enriquecerla o marcarla consolidated;
- el dueño de una accion de obra es el director de esa obra segun el contrato aprobado;
- dueDate es absoluta por corte de la obra;
- si solo existe dueDate relativa, el API no la disfraza de fecha;
- el CTA abre la hoja capaz de resolver la accion;
- no crea una accion persistida;
- no marca completada;
- no llama endpoints mutantes;
- si el destino S18–S24 aun no esta cortado, el adapter usa su ruta canónica legacy conservando
  query, nunca un onclick interno.

### Linaje

Toda metrica visible tiene un control Como se calcula que abre el drawer T03 con:

- metricKey y nombre;
- definicion;
- formula;
- fuente de ejecucion;
- tablas fuente;
- grano;
- politica de corte;
- filtros;
- version;
- ultima actualizacion;
- limitaciones conocidas;
- estado de completitud de esta lectura.

El drawer:

- es accesible por teclado;
- tiene titulo asociado;
- mueve y devuelve foco;
- cierra con Escape y boton;
- bloquea fondo sin perder scroll;
- ofrece contenido textual, no solo tooltip;
- no refetch si el brief ya incluye el linaje;
- puede usar GET /api/bi/lineage solo como fallback tipado.

### Drilldowns

S17 no inventa modales de detalle. Navega:

| Señal | Destino |
|---|---|
| forecast/desviacion/criticas | /bi/programa-general |
| restricciones/huerfanas | /bi/intermedia |
| compromisos en riesgo | /bi/semanal |
| PDC en riesgo | /bi/pdc |
| contratistas | /bi/contratistas |
| responsables | /bi/responsables |
| tendencia de avance | /bi/curva-s |

La navegacion conserva project_ids o project_id, periodo, filtros compatibles y un focus target
tipado. Un destino ignora de forma explicita filtros que no soporta; nunca los interpreta como
autoridad.

## Contrato HTTP target

### Query

T03 define un BiQuerySchema compartido. Para S17 acepta:

- project_ids: enteros positivos repetidos o CSV durante compatibilidad;
- project_id: alias simple durante compatibilidad;
- semana: entero positivo solo para scope simple;
- desde/hasta: ISO date;
- sub/resp/etapa: strings recortados con limite;
- aliases legacy solo en adapter;
- focus: enum de destinos permitidos.

Se rechaza:

- ids no positivos;
- proyecto no autorizado;
- fechas imposibles;
- desde mayor que hasta;
- semana junto con rango incompatible;
- filtros arrays no esperados;
- claves de autoridad como role, permiso, db, dbName, prefix, user o capability.

### Exito

El contrato canónico es:

    {
      "ok": true,
      "data": {
        "reportKey": "overview",
        "scope": {},
        "headline": {},
        "portfolio": [],
        "scorecard": [],
        "drivers": [],
        "risks": [],
        "actions": [],
        "lineage": [],
        "meta": {}
      }
    }

scope incluye projectIds, period, filters y role solo como dato informativo calculado por servidor.
meta incluye generatedAt, sourceRelations, queryVersion y partialFailures. No expone db ni prefijos.

Durante convivencia, el adapter PHP puede incluir respuesta=BIEN y project_id, pero React valida y
consume solo el bloque canónico. La compatibilidad se retira con cero callers.

### Error

Forma estable:

    {
      "ok": false,
      "error": {
        "code": "BI_OVERVIEW_UNAVAILABLE",
        "message": "No pudimos cargar el resumen ejecutivo.",
        "retryable": true,
        "fieldErrors": {}
      }
    }

Codigos:

| HTTP | code | UI |
|---:|---|---|
| 400/422 | BI_QUERY_INVALID | error de filtros con accion corregir |
| 403 | BI_PROJECT_SCOPE_DENIED | no acceso al proyecto, sin datos |
| 404 | NOT_FOUND | modulo/hoja ocultos |
| 409 | BI_PERIOD_NOT_COMPARABLE | periodo no comparable |
| 500 | BI_OVERVIEW_UNAVAILABLE | error recuperable |

El gate oculto sigue devolviendo 404. Los detalles internos se registran server-side y no viajan.

### Zod

frontend/src/lib/api/esquemas/biResumenEjecutivo.ts es la unica fuente de tipos:

- discriminated union ok true/false;
- fechas ISO;
- enums de completeness, trend, risk level y forecast status;
- numeros finitos;
- ratios entre 0 y 1 cuando aplica;
- denominadores enteros no negativos;
- projectIds enteros positivos;
- no passthrough de campos de autoridad;
- superRefine para forecast/rango y porcentaje/denominador;
- superRefine para una sola fila requiresIntervention;
- superRefine para rank unico y contiguo;
- accion de proyecto exige projectName y dueDate o explicacion de ausencia.

Ningun componente llama fetch. frontend/src/lib/api/biResumenEjecutivo.ts usa cliente.ts.

## Estado y flujo frontend

### Fuente de verdad

La URL canónica contiene scope y filtros. El flujo:

1. T01 confirma sesion.
2. T03 resuelve hoja y capacidades.
3. T03 parsea URL.
4. gateway solicita el brief.
5. Zod valida antes de exponer datos.
6. S17 renderiza un snapshot coherente.
7. cambiar filtro actualiza URL y dispara una nueva consulta cancelable.
8. respuesta vieja no reemplaza una consulta nueva.
9. recargar reproduce scope y periodo.

No hay window globals, innerHTML, onclick, jQuery ni estado duplicado DOM/URL.

### Estados

| Estado | Presentacion | Accion |
|---|---|---|
| loading-context | shell + esqueleto de controles | ninguna |
| loading-report | titular/filas skeleton sin datos viejos falsos | cancelar por nueva query |
| ready | lienzo completo | filtrar, navegar, ver evidencia |
| partial | datos validos + aviso de piezas ausentes | ver detalle/reintentar |
| empty-scope | no hay proyectos seleccionados | seleccionar autorizados |
| no-authorized-projects | mensaje seguro | volver a proyectos |
| empty-period | no hay cortes en el periodo | cambiar periodo |
| insufficient | obras visibles con datos insuficientes | ver faltantes |
| query-error | filtros invalidos | corregir/restablecer |
| offline | snapshot previo rotulado o estado sin datos | reintentar |
| server-error | error estable | reintentar |

Un snapshot previo puede conservarse durante refetch solo si queda marcado Actualizando y sigue
asociado a la query visible. Nunca se mezcla portfolio nuevo con acciones viejas.

### Recarga

Recargar:

- repite solo GET;
- conserva URL;
- aborta la petición anterior;
- mantiene focus;
- anuncia resultado;
- no llama restricciones POST ni action queue;
- no muta sesion, semana o proyecto.

## Diseño responsive

### Desktop 1180x820 y 1440x900

- panorama en tabla semantica;
- primera columna proyecto fija solo si no crea overflow de pagina;
- columnas: obra, restricciones, terminacion, tendencia, decision;
- acciones en panel lateral o debajo segun ancho;
- evidencia colapsada;
- densidad suficiente para ver varias obras sin perder titular;
- scroll interno de tabla solo como ultimo recurso, con encabezados visibles.

### Tablet 768x1024

- tabla conserva estructura;
- columnas secundarias agrupan texto;
- acciones bajan debajo;
- filtros se abren en drawer T03;
- targets de 44x44 como minimo;
- no hay hover-only.

### Movil 390x844 y 480x900

- una tarjeta por obra en el mismo orden;
- tarjeta incluye las tres señales y decision;
- ninguna cifra se oculta respecto a tabla;
- forecast se expresa en frases cortas;
- acciones son lista vertical;
- evidencia usa acordeones con estados ARIA;
- linaje usa drawer full-height;
- no hay tabla miniaturizada ni page overflow.

### Zoom y movimiento

- 200 por ciento mantiene lectura y acciones;
- texto refluye;
- no hay truncamiento esencial;
- prefers-reduced-motion elimina transiciones no esenciales;
- orden no se comunica con animacion;
- reordenamiento tras filtros se anuncia.

## Design system y temas

- solo public/css/tokens.css y componentes canonicos;
- oscuro es default/fallback;
- claro tiene capacidad identica;
- no se usan hex, rgb, hsl ni colores de Chart.js locales;
- el unico realce de obra usa los tokens semanticos de severidad;
- estados desconocido/insuficiente son neutros, no verdes;
- focus visible en ambos temas;
- graficos duplicados no se recrean;
- si una visualizacion futura necesita color, debe incluir tabla/lectura textual y pertenecer a una
  spec posterior.

T03 aporta layout, nav, filter drawer, status primitives, metric/lineage drawer y superficie de
error. S17 aporta composiciones de Resumen Ejecutivo.

## Accesibilidad

- h1 unico Resumen Ejecutivo;
- resumen de alcance anunciado;
- titular como region etiquetada, no live en cada render;
- actualizaciones solicitadas anuncian un resumen conciso;
- tabla con caption y headers asociados;
- tarjetas movil mantienen headings jerarquicos;
- prioridad incluye texto Intervenir esta semana;
- riesgos incluyen nivel en texto;
- no depende de color, posicion o icono;
- botones tienen nombres por destino;
- drawers/modales tienen focus trap y retorno;
- Escape cierra;
- enlaces usan href real;
- teclado puede filtrar, ordenar, abrir evidencia y navegar;
- touch targets minimos;
- formulas son texto seleccionable;
- fechas usan texto local y datetime ISO;
- numeros usan locale es-CO sin alterar valores;
- axe sin violaciones serias/criticas;
- consola sin errores.

## Seguridad, aislamiento y RLS

S17 consume el runtime boundary ya cerrado; no lo modifica.

Invariantes:

- gate global antes de consultar datos;
- politica por hoja antes de construir el brief;
- session user es la identidad;
- projectIds se intersectan con BiProjectScope;
- cada query usa MultiProjectScope/queryForProjects o store equivalente;
- projectId del cliente nunca autoriza;
- rol mostrado viene del servidor;
- no se expone db/prefix;
- no hay cache cruzada entre usuarios;
- cache key incluye usuario, projectIds ordenados, periodo y filtros;
- respuesta denegada no contiene datos;
- drilldowns revalidan scope;
- linaje no salta el gate;
- errores no enumeran proyectos no autorizados;
- S17 no llama endpoints mutantes;
- no cambia RLS, schema, grants, usuarios, credenciales o datos.

Pruebas nuevas de contrato usan fakes/stubs, no MySQL y no DML. Las suites existentes que escriben
fixtures no son gate de esta sesion documental ni requisito de la implementacion S17 hasta que haya
autorizacion explicita para ejecutarlas.

## Arquitectura objetivo

### Backend

- BiSheetAccessPolicy: composicion por lienzo, compartida T03.
- BiOverviewScopeResolver: default all-authorized solo para overview.
- BiOverviewQuery: DTO validado.
- BiOverviewService: orquesta filas por proyecto.
- BiOverviewProjectReader: lectura project-scoped e inyectable.
- BiOverviewPresenter: contrato canónico y compatibilidad.
- BiOverviewPriority: regla pura de orden.
- BiOverviewHeadline: plantillas puras.
- ControlTowerService permanece adapter de compatibilidad mientras tenga callers.
- BiControlTowerApiController delega; no compone JSON ad hoc.

No se crea una segunda formula de metricas. El reader usa MetricExecutor/catalogo donde la metrica
es ejecutable y adaptadores existentes donde sigue descriptiva, conservando completeness y
lineage.

### Frontend

Propuesta de archivos:

- frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx
- frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.tsx
- frontend/src/modulos/bi/resumen/PanoramaObras.tsx
- frontend/src/modulos/bi/resumen/FilaPanorama.tsx
- frontend/src/modulos/bi/resumen/TarjetaPanorama.tsx
- frontend/src/modulos/bi/resumen/AccionesRecomendadas.tsx
- frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.tsx
- frontend/src/modulos/bi/resumen/RiesgosEjecutivos.tsx
- frontend/src/modulos/bi/resumen/useResumenEjecutivo.ts
- frontend/src/lib/api/esquemas/biResumenEjecutivo.ts
- frontend/src/lib/api/biResumenEjecutivo.ts
- frontend/src/modulos/bi/resumenEjecutivo.css

Los componentes reciben datos ya validados. No calculan metricas, prioridad, frases ni permisos.

### Frontera T03/S17

| T03 | S17 |
|---|---|
| route host y layout BI | contenido overview |
| nav por lienzo | titular |
| project/period/filter controls | panorama |
| query codec | scorecard ejecutivo |
| gate/leaf capability | drivers/riesgos overview |
| loading/error shell | acciones overview |
| lineage drawer | enlaces metricKey |
| responsive frame | tabla/tarjetas |
| retiro views/scripts compartidos | pruebas de hoja |

S17 puede añadir el primer slice de una pieza T03, pero el archivo y la API nacen compartidos y se
prueban como tales.

## Convivencia, corte y retiro

### Fase A — caracterizacion

- congelar payload actual;
- demostrar labels incorrectos;
- demostrar drivers/risks/lineage omitidos;
- caracterizar roles, scope y filtros;
- no cambiar rutas.

### Fase B — API canónica

- policy por hoja;
- default multiproyecto;
- DTO/presenter;
- filas por obra;
- schema PHP/Zod;
- compatibilidad legacy.

### Fase C — React shadow

- ruta interna de prueba no enlazada;
- datos interceptados;
- dark/light/viewports;
- legacy sigue canónico.

### Fase D — corte

- /bi/control-tower sirve SPA;
- acciones enlazan destinos existentes;
- T03 mantiene fallback por flag de ruta, no por base de datos;
- monitoreo de errores de contrato.

### Retiro

S17 no elimina VIEW-04–VIEW-09, bi-spa.js, CSS BI ni vendors porque son compartidos por S18–S24.
Solo después de T03 y las ocho hojas:

- cero rutas renderizan _layout/_nav/_filters/control-tower.php;
- cero scripts cargan bi-spa.js;
- cero hojas usan Chart.js/lucide CDN legacy;
- index.php se elimina si rg y routing prueban cero consumidores;
- CSS exclusivo se retira con auditoria de selectors;
- ct-app se absorbe en S20;
- manifest y route fallback dejan una sola ruta canónica.

### Rollback

- devolver solo /bi/control-tower al render legacy;
- mantener API canónica y adapters;
- no revertir datos porque S17 no escribe;
- conservar URL;
- comprobar A permitido, D/R ocultos por lienzo y proyecto no autorizado;
- restaurar ruta React tras el ensayo.

## Estrategia de pruebas

### PHP puro, sin base

- BiSheetAccessPolicy: A overview, D/R no overview;
- gate 404 y scope 403;
- default all-authorized overview;
- subconjunto autorizado;
- rechazo cross-project;
- query valida fechas/aliases/authority keys;
- presenter success/error;
- una fila por proyecto, incluso insufficient;
- porcentaje conserva numerador/denominador;
- forecast no disponible no fabrica fechas;
- prioridad determinista, empates y missing;
- solo una fila resaltada;
- headline template/facts;
- scorecard por key, no posicion;
- actions con owner/due/project/destination;
- lineage asociado;
- denegado no serializa datos.

Usan fake readers y sesiones sintéticas. No conectan a MySQL.

### Frontend unit/component

- schema Zod success/error y refinamientos;
- gateway usa cliente.ts;
- query codec canónico;
- abort/stale-response;
- loading/partial/empty/insufficient/offline/error;
- titular exacto del servidor;
- tabla desktop y tarjetas movil equivalentes;
- orden del payload respetado;
- una sola prioridad;
- scorecard labels correctos;
- drivers/riesks visibles en evidencia;
- acciones completas y href real;
- lineage drawer;
- no charts PAC/PPC;
- no fetch directo;
- no mutation endpoints;
- tema dark/light;
- teclado/foco/zoom/reduced motion.

### Browser interceptado

Antes de navegar:

- interceptar context T03;
- interceptar GET /api/bi/control-tower;
- registrar cualquier POST/PUT/PATCH/DELETE BI;
- A multiproyecto;
- D/R 404 overview;
- proyecto no autorizado 403;
- filtro/rango/recarga;
- partial per project;
- error y retry;
- cinco viewports;
- dark/light;
- keyboard/focus/zoom;
- no overflow;
- axe/consola;
- cero mutaciones.

No depende de datos reales ni ejecuta DML.

### Validacion contra runtime, en futura implementacion autorizada

Solo despues de pruebas puras:

- comprobar servicios Docker una vez;
- abrir por dev door, nunca /login;
- usar A para overview;
- verificar que D/R aterrizan en Intermedia y no ven overview;
- revisar red y consola;
- no abrir endpoints de escritura;
- no modificar datos;
- documentar SHA y resultados si el frente llega a cierre.

## Criterios de aceptacion

1. S17-AC-01: /admin/ queda fuera.
2. S17-AC-02: /bi/control-tower es ruta SPA al corte.
3. S17-AC-03: S17 consume T01/T03 y no duplica shell BI.
4. S17-AC-04: solo A monta overview segun lienzo aprobado.
5. S17-AC-05: D/R no ven nav overview y reciben 404 directo.
6. S17-AC-06: el gate global BI se conserva.
7. S17-AC-07: scope de proyecto sigue exigiendo lps.indicadores.ver.
8. S17-AC-08: projectIds del cliente nunca autorizan.
9. S17-AC-09: invalid scope devuelve 403 sin datos.
10. S17-AC-10: default overview incluye todos los proyectos BI autorizados.
11. S17-AC-11: subconjunto autorizado se conserva en URL.
12. S17-AC-12: otras hojas no heredan el default all-projects.
13. S17-AC-13: filtros canonicos son desde/hasta/sub/resp/etapa.
14. S17-AC-14: aliases legacy solo viven en adapter temporal.
15. S17-AC-15: autoridad keys se rechazan.
16. S17-AC-16: cada obra publica su cutoff efectivo.
17. S17-AC-17: multiobra no finge semana calendario comun.
18. S17-AC-18: GET control-tower tiene contrato PHP y Zod.
19. S17-AC-19: React consume envelope ok discriminado.
20. S17-AC-20: denied/error no filtra internals.
21. S17-AC-21: una fila panorama por proyecto seleccionado.
22. S17-AC-22: obra sin datos permanece como insufficient.
23. S17-AC-23: missing nunca se transforma en cero.
24. S17-AC-24: restricciones huerfanas usan criterio vigente.
25. S17-AC-25: readiness incluye numerator/denominator.
26. S17-AC-26: no se promedian porcentajes.
27. S17-AC-27: forecast respeta minimo de historia/catalogo.
28. S17-AC-28: forecast muestra fecha, rango y fecha comprometida.
29. S17-AC-29: variance se expresa en palabras.
30. S17-AC-30: trend compara cortes de la misma obra.
31. S17-AC-31: prioridad es server-side y determinista.
32. S17-AC-32: prioridad no usa score numerico inventado.
33. S17-AC-33: priority reasons viajan.
34. S17-AC-34: como maximo una obra se resalta.
35. S17-AC-35: sin señal adversa no se resalta ninguna.
36. S17-AC-36: titular viene de plantilla auditable.
37. S17-AC-37: titular incluye facts/confidence/cutoff.
38. S17-AC-38: frontend no genera narrativa.
39. S17-AC-39: scorecard conserva ocho KPI por key.
40. S17-AC-40: scorecard no usa labels PPC/programadas/ejecutadas/brecha falsos.
41. S17-AC-41: scorecard es evidencia secundaria.
42. S17-AC-42: drivers se ligan a señales/proyectos reales.
43. S17-AC-43: drivers vacios distinguen sano de insufficient.
44. S17-AC-44: risks se muestran con nivel/confianza/fuente.
45. S17-AC-45: risk score no decide orden del panorama.
46. S17-AC-46: acciones muestran obra/dueño/fecha/impacto/evidencia.
47. S17-AC-47: acciones consolidadas se rotulan.
48. S17-AC-48: cada accion tiene href al desglose correcto.
49. S17-AC-49: scope/periodo se preservan al navegar.
50. S17-AC-50: S17 no crea/cierra/persiste acciones.
51. S17-AC-51: S17 hace cero mutaciones HTTP.
52. S17-AC-52: lineage visible para cada metrica.
53. S17-AC-53: lineage incluye formula/fuente/grano/corte/filtros/limitaciones.
54. S17-AC-54: drawer gestiona foco/Escape/retorno.
55. S17-AC-55: no se recrean charts PPC/PAC.
56. S17-AC-56: loading/ready/partial/empty/insufficient/offline/error existen.
57. S17-AC-57: refetch no mezcla snapshots.
58. S17-AC-58: retry es GET idempotente.
59. S17-AC-59: desktop/tablet usan tabla semantica.
60. S17-AC-60: movil usa tarjetas con capacidad equivalente.
61. S17-AC-61: 390x844, 480x900, 768x1024, 1180x820 y 1440x900 no desbordan.
62. S17-AC-62: 200 por ciento zoom conserva lectura/acciones.
63. S17-AC-63: dark/light tienen capacidad identica.
64. S17-AC-64: no hay colores literales ni variantes locales.
65. S17-AC-65: severidad no depende solo de color.
66. S17-AC-66: teclado/touch/focus/reduced motion cumplen.
67. S17-AC-67: axe no reporta serious/critical.
68. S17-AC-68: consola queda limpia.
69. S17-AC-69: solo cliente.ts llama fetch.
70. S17-AC-70: PHP tests nuevos usan fakes y no MySQL.
71. S17-AC-71: browser intercepta antes de navegar y demuestra cero mutaciones.
72. S17-AC-72: views/scripts compartidos no se retiran antes de T03/S18–S24.
73. S17-AC-73: rollback cambia ruta, no datos.
74. S17-AC-74: RLS/schema/grants/usuarios/credenciales/datos no cambian.
75. S17-AC-75: no se regenera golden sin aprobacion.

## Entregas verticales

### Entrega 1 — Acceso, scope y contrato

- policy por hoja;
- default multiproyecto;
- query/DTO;
- PHP contract;
- Zod/gateway;
- errores seguros.

### Entrega 2 — Decision ejecutiva

- filas por proyecto;
- completitud;
- prioridad;
- titular;
- panorama tabla/tarjetas.

### Entrega 3 — Evidencia y acciones

- scorecard correcto;
- drivers/riesgos;
- acciones completas;
- destinos S18–S24;
- linaje.

### Entrega 4 — Calidad y corte

- estados;
- oscuro/claro;
- cinco viewports;
- a11y;
- browser interceptado;
- corte/rollback;
- retiro diferido.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Sigue mostrando una sola obra | default overview all-authorized + test |
| Cruza datos entre obras | MultiProjectScope por lectura y fixture fake sentinela |
| Orden arbitrario parece score cientifico | regla explicita sin score, reasons visibles |
| Una obra sin datos parece sana | completeness insufficient, nunca 0/verde |
| Forecast sin muestra se publica | politica del catalogo y union discriminada |
| Porcentajes se promedian | sumas/denominadores y contract test |
| Labels siguen corridos por indice | metricKey obligatorio |
| Drivers continúan vacios | projection desde señales panorama |
| Riesgos/linaje vuelven a viajar ocultos | componentes + acceptance/browser |
| Accion sin dueño/fecha parece ejecutable | campos obligatorios o estado incomplete |
| CTA llega a hoja no migrada | href canónico con adapter legacy |
| S17 invade S20 escribiendo | tripwire de mutaciones |
| D/R ven hoja equivocada | BiSheetAccessPolicy |
| T03 se duplica por hoja | frontera explicita y archivos compartidos |
| Mobile es tabla encogida | tarjetas equivalentes |
| Color decide prioridad | texto/orden/reasons |
| Retiro rompe las otras siete hojas | gate conjunto T03/S18–S24 |
| Test toca datos | fakes/interception, no MySQL/DML |

## Decisiones descartadas

- Preservar la UI tal cual: tiene labels falsos y contenido omitido.
- Mostrar todas las tarjetas arriba: contradice la decision de la hoja.
- Recrear PPC/PAC: duplican Semanal.
- Un dashboard de charts genericos: no responde donde intervenir.
- Score numerico ponderado: pesos no aprobados.
- Orden client-side: permite drift entre titular y tabla.
- Ocultar obras sin datos: sesga el portafolio.
- Tratar missing como 0: fabrica salud.
- Usar average de porcentajes: denominador errado.
- Fetch por fila desde React: N+1 e incoherencia de corte.
- Narrativa en React: duplica reglas y gramática.
- IA generativa para titular: no auditable.
- Acciones como comentarios sin dueño/fecha: incumple D20.
- Persistir acciones desde overview: escritura fuera del alcance.
- Modal propio por señal: duplica S18–S24.
- Dar overview a D/R por compatibilidad accidental: contradice lienzos aprobados.
- Cambiar internal.bi.preview: RBAC fuera de alcance.
- Meter S17 en ct-app: perpetua isla.
- Retirar bi-spa.js ahora: rompe otras hojas.
- Probar contra base compartida: DML/dependencia innecesaria.

## Decisiones pendientes

Ninguna. Si la implementacion descubre que gerencia corresponde a un rol distinto de A en el RBAC
vigente, una formula aprobada posterior para prioridad, un owner de obra canónico ya disponible, o
un consumidor productivo que dependa de la posicion del scorecard, debe detener solo el tramo
afectado, aportar evidencia y enmendar esta spec. No se infiere un contrato nuevo.

## Siguiente gate

Invocar superpowers:writing-plans para
docs/superpowers/plans/2026-08-30-s17-bi-control-tower-react.md, autorrevisarlo, actualizar el atlas
y continuar S18. No implementar S17 en esta sesion.
