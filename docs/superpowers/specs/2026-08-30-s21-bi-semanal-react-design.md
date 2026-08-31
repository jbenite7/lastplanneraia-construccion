---
capa: fuente
tipo: spec
estado: autorrevisado
id: S21
fecha: 2026-08-31
superficie: bi-semanal
rutas:
  - "/bi/semanal"
  - "/api/bi/report/semanal"
  - "/api/bi/report/programa-general/cnp-detail"
  - "/api/bi/report/programa-general/cnc-detail"
depende_de: [T01, T03, S08, S09, S10, S17, S18, S20]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [arquitectura, frontend, bi, semanal, pac, riesgo, cnp, cnc, tnp, rbac, accesibilidad, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, ForecastService, MetricDictionaryService, LineageService, RiskScoringService, StorytellingService, ActionRecommendationService, SemanalApiController, bi_ps_compromisos, bi_pg_semana, programacion_semanal, semanas_activas, VIEW-04/05/06/08, bi-spa.js, CSS, pruebas, respuestas read-only servidas, specs CT-8.4/CT-9/N5/N8, S08-S10, S17-S20 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S21 de la hoja BI Programacion Semanal a React: PAC con numerador/denominador y variacion, riesgo de incumplimiento por compromiso en tres niveles, causas CNC/CNP por clic, cobertura de captura, TNP/crisis, filtros, ancla propia por obra, responsive y oscuro/claro, sin mutaciones ni cambios RLS/schema/datos."
---

# S21 — Hoja BI Programacion Semanal en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementacion,
> commits, DDL/DML, cambios RLS, cambios de permisos, correo, deploy, publicacion ni trabajo en
> `/admin/`. Su plan se escribe a continuacion con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

S21 desarrolla CT-8.4 para el lienzo de obra y dos ritmos:

- reunion semanal de obra en el dia ancla propio del proyecto: cerrar lo que termina y preparar lo
  que entra;
- revision diaria corta del residente: salvar el compromiso que presenta riesgo alto.

La hoja se construye una sola vez para A, D y R. No existen variantes por audiencia. El alcance,
periodo, filtros, capacidades y enlaces se resuelven en servidor.

Consume:

- T01 para sesion, proyecto, shell, sidebar, tema, route outlet y cliente HTTP;
- T03 para gate por hoja, query, periodo, filtros, estados, drawer y linaje;
- S08 como fuente operativa y destino de compromisos, avance, cierre y TNP;
- S09/S10 como fuentes canonicas y destinos de CNP/CNC;
- S17 para entrada desde el panorama;
- S18 para recibir deep links causales desde Programa General;
- S20 como paso anterior de la reunion semanal.

S21 es una hoja BI read-only. La captura sana de Programacion Semanal no se reemplaza ni se
reimplementa aqui. Cuando hace falta comprometer, justificar, corregir o cerrar, el servidor entrega
un enlace autorizado a S08, S09 o S10.

## Resultado buscado

`/bi/semanal` pasa a la SPA principal y:

1. abre en la semana correcta de cada obra segun sus fechas, no segun lunes o semana ISO;
2. en el dia ancla muestra la semana que cierra y ofrece pasar a la que entra;
3. dice cuantos compromisos se cumplieron de cuantos, con PAC y registros faltantes;
4. compara conteo y PAC con la semana anterior para revelar si el compromiso se encoge;
5. conserva una tendencia visible por corte y breakdown por proyecto;
6. explica que PAC mide confiabilidad del plan, no desempeño moral de una persona;
7. integra el modelo `ps_pac_expected` como Riesgo de incumplimiento por compromiso;
8. muestra alto, medio o bajo, nunca el porcentaje crudo por compromiso;
9. deja como insuficiente todo compromiso sin variables o muestra historica minima;
10. ordena el riesgo para la revision diaria y cabe completo en movil;
11. muestra causas de no cumplimiento y no programacion con cobertura de captura;
12. abre subcausa y actividad por clic, nunca solo por hover;
13. conserva texto causal y atribuciones completas sin truncamiento;
14. hace visibles TNP sin categorizar y ausencia de alertas de crisis como señales de adopcion;
15. preserva proyecto, periodo, responsable, subcontratista, etapa, focus y categoria;
16. soporta lectura multiproyecto sin promediar porcentajes de obra ni comparar personas;
17. ofrece linaje, muestras, pesos, umbrales, denominadores, cobertura y limitaciones;
18. funciona en desktop, tablet y movil, oscuro y claro, teclado, touch, zoom y lector de pantalla;
19. permanece completamente read-only.

## Alcance

### Incluido

- `GET /bi/semanal` como ruta SPA al corte.
- `GET /api/bi/report/semanal` estabilizado como snapshot canonico.
- Reutilizacion de los GET CNP/CNC detail existentes para drilldown.
- A/D/R segun gate BI y `lps.indicadores.ver` por proyecto.
- Proyecto activo por defecto y multi explicito con breakdown.
- Semana/rango resueltos contra `semanas_activas` por obra.
- Contexto de fase, corte anterior y semana siguiente.
- PAC con numerador, denominador, faltantes, porcentaje y comparacion.
- Historia visible de PAC y tamaño del compromiso.
- Riesgo por compromiso con seis variables, evidencia y muestra.
- Politica N5 de alto/medio/bajo, versionada.
- Titular factual por plantillas finitas.
- Lista de compromisos priorizada y detalle contextual sin endpoint nuevo.
- Resumen y drilldown CNC/CNP, categorias, subcausas y actividades.
- Cobertura de causas y registro no disponible declarado.
- Conteos/cobertura de TNP y alertas de crisis.
- Filtros, focus/deep links, estados y recarga.
- Tabla desktop/tablet y tarjetas movil.
- Oscuro/claro, cinco viewports, zoom, reduced motion y accesibilidad.
- Contratos PHP, Zod, pruebas puras y navegador totalmente interceptado.
- Convivencia, corte, rollback y retiro diferido de la seccion legacy.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, ProjectScope, grants, usuarios, credenciales, schema, vistas SQL,
  tablas, columnas, indices, triggers, datos o seeds.
- Ejecutar DDL/DML, aun con rollback.
- Editar compromisos, PAC, avance, responsable, subcontratista, CNP, CNC, CP/TNP o crisis desde BI.
- Confirmar/reabrir semana, autoprogramar, crear TNP o generar CIC; corresponde a S08.
- Cambiar catalogos o mutaciones de S09/S10.
- Crear una auditoria de quien registro CNP/CNC: la fuente no conserva ese actor.
- Llamar responsable del compromiso al registrador de la causa.
- Cambiar la formula o los seis pesos de `ForecastService`.
- Fabricar una prediccion si falta una variable o la muestra minima.
- Mostrar el score crudo de cumplimiento esperado por compromiso.
- Usar `fulfillment_alert` o `bi_riesgos` como sustituto del modelo N5.
- Enviar correo o notificaciones desde React/esta hoja.
- Crear cola, suscripcion, telemetria o tabla de notificaciones.
- Convertir la ausencia de crisis en ausencia de riesgo.
- Reintroducir `reprogramaciones_semanales`.
- Crear un segundo PAC/PPC con la misma formula.
- Añadir exportacion, impresion o descarga que la hoja BI legacy no ofrece.
- Añadir libreria de tabla, grafica, estado o formularios.
- Eliminar endpoints causales mientras S18 u otro caller los use.
- Eliminar layout/JS/CSS BI compartidos antes del gate T03/S17–S24.
- Regenerar goldens sin aprobacion explicita.

## Punto de partida medido

### React

- No existe pagina, modulo, schema, gateway ni ruta React principal de BI Semanal.
- El frontend actual contiene solo el shell minimo y sus contratos base.
- S08–S10 y S17–S20 existen como specs/planes en este worktree, no como implementacion disponible.

### Rutas y acceso

| Verbo | Ruta | Uso actual |
|---|---|---|
| GET | `/bi/semanal` | layout BI legacy |
| GET | `/api/bi/report/semanal` | brief semanal |
| GET | `/api/bi/projects` | proyectos BI |
| GET | `/api/bi/weeks` | semanas |
| GET | `/api/bi/filter-options` | filtros |
| GET | `/api/bi/report/programa-general/cnp-detail` | detalle causal compartido |
| GET | `/api/bi/report/programa-general/cnc-detail` | detalle causal compartido |
| GET | `/api/bi/lineage` | linaje lazy legacy |

El gate vigente:

- A entra al preview;
- D/R dependen de `bi.control_tower.visible`;
- otros roles reciben 404;
- cada proyecto exige membresia visible y `lps.indicadores.ver`;
- scope no autorizado produce 403.

Las acciones operativas tienen capacidades separadas:

- `lps.programacion_semanal.ver/editar`;
- `lps.cnp.ver/editar`;
- `lps.cnc.ver/editar`.

React no deriva esas capacidades de la letra del rol.

### Vista y render legacy

La seccion `view-semanal` contiene:

- un texto inicial;
- un canvas de PAC;
- una tabla de cuatro columnas.

`renderSemanal()` recorre las cuatro filas de scorecard como si fueran actividades. Por tanto:

- Compromisos activos, PAC, En riesgo y CNC aparecen bajo la columna Actividad;
- `action/status` aparece como Responsable;
- cualquier valor mayor a 75 se marca completa, lo demas pendiente;
- un conteo se convierte en porcentaje visual;
- no hay compromisos, causas, historia, variacion, riesgo N5 o drilldown;
- el donut es la unica lectura grafica.

La funcion oculta la tabla si no hay scorecard, pero no gobierna todos los estados del canvas. No
existe contrato mobile de esta hoja.

### Payload servido

El GET devuelve el envelope legacy:

- `respuesta/project_ids/project_id/semana/report_key/role/filters`;
- `data_source/raw_row_count`;
- `executive_brief/scorecard/charts/drivers/risks/recommended_actions/lineage`;
- campos PDC/snapshot nulos.

Una lectura read-only para proyecto 73, semana 1 produjo:

- cuatro compromisos activos;
- tres PAC=1;
- PAC 75 por ciento;
- cero `fulfillment_alert`;
- cero CNC en el scorecard;
- donut 75/25;
- cero drivers, diez riesgos genericos y cero acciones;
- linaje `ps_pac_expected` y `ps_weekly_fulfillment`.

Los diez riesgos proceden de `bi_riesgos/bi_pg_semana`, no del modelo por compromiso, y sus
nombres contienen HTML `<b>/<small>`. `renderSemanal` no los muestra, pero el payload los
transporta.

### Contradiccion de semana abierta

En la fecha auditada, proyecto 73 tiene:

| Semana | Inicio | Fin | Confirmada | Compromisos | PAC registrado |
|---:|---|---|---:|---:|---:|
| 1 | 2026-08-18 | 2026-08-24 | si | 4 | 4 |
| 2 | 2026-08-25 | 2026-08-31 | no | 4 | 0 |

Para semana 2, legacy publico simultaneamente:

- PAC 0 por ciento;
- riesgo alto;
- confianza Alta;
- Todos los compromisos tienen datos completos;
- ningun compromiso esta en riesgo.

La causa es `scorecardPS()`: cuenta `PAC=1`, divide entre todas las filas y convierte ausencia
total en cero. El catalogo ejecutable ya declara que una semana abierta sin PAC es `null`, no
cero. S21 usa la metrica canonica y elimina esa contradiccion.

### Semana por calendario

El fallback `currentWeekBogota()` devuelve `format('W')`, semana ISO. Los proyectos usan su propio
numero y fechas en `semanas_activas`. Para Da Porto la semana del 25–31 de agosto es Semana 2, no
la semana ISO 36.

La sesion suele esconder el defecto al traer `$_SESSION['semana']`, pero no es una garantia.
T03/S21 resuelven el corte por las fechas reales del proyecto.

### Fuente semanal

`fetchSemanal()` lee `programacion_semanal` con:

- scope por proyecto/semana/rango;
- `Activa IN ('1','NA')`;
- filtros de subcontratista, responsable y etapa;
- `SELECT ps.*`.

`bi_ps_compromisos` expone grano:

    project_id + Semana + row_id

Campos relevantes:

- identidad, actividad, descripcion, ubicacion y fechas;
- contratista/responsable;
- compromiso, ejecucion, P_Completado y PAC;
- critica, atrasada, Activa, is_TNP;
- Categoria/CNP/Observaciones CNP;
- Categoria/CNC/Observaciones CNC;
- flags de poblacion/faltantes/readiness/alerta;
- contexto del Programa General.

La columna `pac_expected_baseline` siempre es null por compatibilidad. No contiene la prediccion
aprobada.

### PAC actual

`scorecardPS()`:

- denominador = todas las filas activas/NA de `fetchSemanal`;
- no excluye TNP;
- numerador = PAC=1;
- ausencia total se convierte en cero;
- redondea a entero;
- usa umbrales 60/80 para texto de riesgo.

`ps_weekly_fulfillment` es ejecutable y define:

- activos;
- sin TNP;
- cumplidos / compromisos;
- semana seleccionada;
- null cuando no existe PAC registrado;
- ratio de sumas, no promedio por obra.

S21 adopta la metrica ejecutable como unica autoridad de PAC.

### Riesgo por compromiso

`ps_pac_expected` esta catalogada como descriptiva y
`integration_status=planned_for_programacion_semanal`.

`ForecastService::forecastPacExpected` ya implementa:

- 25 por ciento PAC historico del contratista;
- 20 por ciento PAC historico del responsable;
- 15 por ciento criticidad;
- 20 por ciento restricciones listas;
- 10 por ciento avance actual;
- 10 por ciento CNC reciente;
- seis variables obligatorias;
- minimo tres observaciones para contratista y responsable;
- rechazo de valores invalidos;
- cero defaults sinteticos;
- modelo `PAC_BASELINE_1.0`.

Brecha: nadie ensambla esas variables por compromiso ni llama el calculo desde el brief. Los helpers
historicos de `ForecastService` consultan `cic/cip`, no devuelven muestra y no tienen callers.
No satisfacen N5.

`fulfillment_alert` es otra heuristica: compromiso critico con PAC bajo, progreso incompleto o
asignacion ausente. `bi_riesgos` usa otro score de riesgo. Ninguno es el modelo de seis pesos.

### Causas

S18 ya caracteriza dos endpoints de detalle:

- CNP: `Activa=0` con causa;
- CNC: `Activa IN ('1','NA')` con causa;
- categoria, causa, observacion, responsable, contratista, fechas, cantidades, prioridad, impacto,
  accion y paginacion;
- scope y filtros BI.

S09/S10 amplian el universo canonico:

- CNP conserva no programadas aunque su clasificacion este vacia/incompleta;
- CNC conserva incumplimiento actual, rastro historico incompleto e inconsistencia;
- TNP no pertenece a la poblacion PAC/CNC;
- desconocidos nunca desaparecen.

La tabla `programacion_semanal` no registra quien escribio CNP/CNC ni cuando. Tiene responsable del
compromiso, pero no actor de captura. D32 no puede completarse historicamente sin nuevo contrato de
datos. S21 muestra:

- responsable del compromiso, rotulado correctamente;
- `recordedBy.status=unavailable`;
- texto La fuente actual no registra quien cargo esta causa;
- cantidad con causa, sin causa y cobertura.

No modifica schema ni inventa atribucion.

### TNP, crisis y campo retirado

N8 confirma:

- `Categoria_CP/CP` son captura viva de Trabajo No Planificado;
- `alerta_crisis` es accion viva del drawer;
- la Torre debe medir adopcion;
- `reprogramaciones_semanales` no tiene escritor/lector funcional y no se reintroduce.

Para proyecto 73, semanas 1 y 2 tienen cero TNP y cero crisis. Ese cero no prueba que no existan
trabajos no planificados o crisis; solo dice que no se registraron.

## Semantica target

### Un snapshot, dos ritmos

La pagina inicial hace un solo GET canonico. El snapshot contiene:

- contexto del corte;
- PAC/historia;
- compromisos y riesgo;
- resumen causal;
- adopcion;
- capabilities/hrefs;
- linaje/limitaciones.

El lienzo declara:

- Reunion semanal: revisar PAC, tamaño del compromiso y causas.
- Revision diaria: revisar primero compromisos de riesgo alto.

No hay dos versiones de la hoja. El diseño responsive y el focus cambian la prioridad visual sin
cambiar datos o autorizacion.

### Orden del lienzo

1. contexto, obra, periodo, fase y filtros;
2. titular PAC factual con numerador/denominador;
3. variacion y tendencia de PAC/tamaño;
4. lista de riesgo por compromiso;
5. causas CNC;
6. causas CNP;
7. cobertura TNP/crisis;
8. limitaciones y linaje.

En movil, el resumen es compacto y la lista de riesgo queda visible sin desplazamiento horizontal.

### Periodo y dia ancla

El motor de T03 usa `semanas_activas`:

- si hay semana explicita, la respeta despues de autorizarla;
- si hay rango, el rango manda y cada obra aporta sus cortes dentro de el;
- sin query, selecciona la fila cuyas fechas contienen hoy Bogota;
- si hoy coincide con `Fecha_Fin_Sem`, esa es la semana que cierra;
- ofrece `nextWeek` solo si existe una semana autorizada posterior;
- publica start/end/confirmed/closeDate/phase;
- si no hay fila comparable, retorna insufficient, no semana ISO.

Fases visibles:

- `upcoming`;
- `open`;
- `closing`;
- `closed`;
- `unknown`.

En multi, cada obra conserva su corte y fase; no se finge una Semana N comun.

### PAC y contrapeso

Poblacion:

    Activa IN ('1','NA') AND is_TNP=0

Campos:

- `commitmentCount`;
- `fulfilledCount` PAC=1;
- `notFulfilledCount` PAC=0;
- `unrecordedPacCount` PAC null;
- `pacStatus=available|insufficient|not_applicable`;
- `pacPercent` entero o una decimal solo si la reconciliacion lo exige;
- numerador/denominador visibles.

Reglas:

- cero cumplidos con PAC registrados es 0 valido;
- cero PAC registrados produce null/insufficient;
- una semana abierta nunca se llama riesgo alto por PAC ausente;
- TNP no entra;
- compromiso binario;
- multi suma numeradores/denominadores, nunca promedia porcentajes.

El contrapeso compara con la semana de proyecto inmediatamente anterior:

- delta de compromisos;
- delta de cumplidos;
- delta de PAC en puntos solo cuando ambos PAC son comparables;
- faltantes en ambos cortes;
- breakdown por obra.

La historia usa los cortes del rango o, en semana simple, hasta seis semanas terminando en la
seleccionada. Incluye un punto anterior de contexto si existe.

`PPC` no es una segunda metrica: legacy lo usa como alias visual de PAC. React muestra solo
`PAC — Porcentaje de compromisos cumplidos` y conserva el alias exclusivamente en adapters.

Junto al PAC:

> El PAC describe la confiabilidad del plan comprometido. No evalua por si solo a la persona ni
> distingue si el compromiso fue impuesto.

### Ensamble de riesgo N5

Un `WeeklyRiskFeatureAssembler` server-side construye por
`project_id + Semana + row_id`:

1. **PAC contratista 4w:** ratio PAC=1 / PAC registrado de compromisos activos, no TNP, del mismo
   contratista normalizado, en las cuatro semanas del proyecto estrictamente anteriores.
2. **PAC responsable 4w:** la misma regla para el responsable.
3. **Criticidad:** booleano de la fila actual; si es invalido, dato faltante.
4. **Restricciones listas:** `bi_pg_semana.hard_restrictions_ready` unido por proyecto, semana y
   `Consecutivo_En_Programa/unique_id`.
5. **Avance actual:** `P_Completado` finito en 0–1; fuera del rango es invalido.
6. **CNC reciente:** conteo de CNC para la misma actividad
   `project_id + Consecutivo_En_Programa` en las cuatro semanas anteriores.

Reglas de historia:

- cuatro semanas de proyecto, no cuatro numeros ni 28 dias calendario;
- el corte seleccionado y los posteriores nunca entran;
- PAC historico solo usa PAC no null;
- muestras se publican;
- contratista y responsable requieren al menos tres observaciones cada uno;
- nombre ausente, join ausente, muestra corta o valor invalido produce insufficient;
- ningun faltante se reemplaza por cero/promedio global.

El assembler llama el calculo puro existente de `ForecastService`. No duplica pesos.

### Niveles de riesgo

`ForecastService` produce internamente un score de cumplimiento esperado. React no lo recibe ni
lo muestra. La policy `WEEKLY_RISK_LEVELS_1.0` reutiliza los umbrales observables 60/80 del
scorecard semanal actual sobre la misma escala:

| Cumplimiento esperado interno | Nivel visible |
|---:|---|
| menor a 0,60 | alto |
| 0,60 a menor de 0,80 | medio |
| 0,80 a 1,00 | bajo |
| null/invalido/muestra corta | insuficiente |

La trazabilidad declara umbrales, modelo, pesos, variables y muestra. El detalle muestra factores
como listos/no listos, criticidad, muestra y CNC, pero no el porcentaje crudo.

Solo `alto` devuelve `notificationEligible=true`. S21 no envia la notificacion; entrega una
decision pura para el limite de distribucion que corresponda. Medio/bajo/insuficiente nunca se
marcan elegibles.

Orden:

1. alto;
2. medio;
3. bajo;
4. insuficiente;
5. dentro del nivel, critica primero;
6. fecha de inicio mas proxima;
7. clave estable.

Un compromiso insuficiente no se disfraza de bajo ni desaparece. Se agrupa como Sin señal
calculable con la causa.

### Fila de compromiso

Cada fila incluye:

- clave `projectId:week:rowId`;
- proyecto/corte;
- codigo, actividad, ubicacion y fechas;
- responsable y contratista;
- compromiso/ejecutado/unidad;
- PAC observado;
- criticidad;
- riskLevel/status;
- factores y muestras sin score crudo;
- missing/invalid reasons;
- notificationEligible;
- recomendacion factual;
- href autorizado a S08/S10.

El detalle viaja en el snapshot y abre sin otro GET.

### Titular

Plantillas server-side:

- cerrada/comparable: N de M compromisos cumplidos (P por ciento); delta de tamaño/PAC;
- abierta con PAC parcial: N de M registrados; PAC provisional, faltan K;
- abierta sin PAC: M compromisos en curso; PAC pendiente de cierre;
- sin compromisos: no hay compromisos en el corte;
- multi: resumen agregado ponderado y obras con estado insuficiente;
- riesgo: X alto, Y medio, Z bajo, W sin señal.

No usa IA generativa, HTML, nivel derivado de PAC ausente ni lenguaje de evaluacion personal.

### Causas y cobertura

S21 consume los resolvers canonicos S09/S10.

CNC:

- poblacion de incumplimiento comparable de S10;
- documentada/incompleta/inconsistente;
- categoria/subcausa/observacion completa;
- cantidades y brecha;
- criticas, sin responsable y sin causa;
- ranking por categoria;
- click abre actividades paginadas.

CNP:

- poblacion no programada de S09;
- clasificacion completa/incompleta/desconocida;
- ranking por categoria;
- click abre actividades paginadas;
- explica el denominador, no sustituye el riesgo de compromiso.

Cobertura:

- eligible;
- documented;
- missing;
- inconsistent cuando aplique;
- rate o null;
- responsible del compromiso;
- `recordedBy unavailable`;
- texto de limitacion.

El responsable no se presenta como culpable ni autor de la causa. El filtro por persona sirve para
la conversacion operativa, no para un ranking de desempeño.

### TNP y crisis

Adopcion:

- total TNP;
- TNP categorizados por `Categoria_CP + CP`;
- TNP sin categorizar;
- cobertura o null;
- alertas de crisis registradas;
- crisis por proyecto/corte.

Estados:

- cero TNP: No se registraron TNP en este corte, no No hubo TNP;
- cero crisis: Ninguna crisis registrada, no Sin crisis;
- filas sin categoria: CTA autorizado a S08;
- `reprogramaciones_semanales` no aparece.

S21 no implementa el veredicto de 90 dias ni persiste aperturas. Solo hace visible la adopcion del
corte/rango.

### Filtros, focus y deep links

Query T03:

- `project_ids/project_id`;
- `semana` o `desde/hasta`;
- `sub/resp/etapa`;
- `focus=pac|risk|cnp|cnc|tnp|crisis`;
- `category` para CNP/CNC;
- `commitment` como clave compuesta opcional.

La barra principal mantiene obra y periodo. Los filtros secundarios viven en el drawer. Todo filtro
gobierna las secciones compatibles. Una seccion que usa historia previa o no soporta un filtro lo
declara junto a la cifra.

Deep links S18:

- `focus=cnp|cnc` abre el desglose;
- `category` selecciona la categoria;
- scope/periodo/filtros se conservan;
- foco se mueve al titulo del drawer;
- un focus inexistente produce estado recuperable, no error fatal.

### Acciones

S21 no muta. El servidor devuelve:

- Ver/editar Programacion Semanal segun capacidades S08;
- Ver/corregir CNP segun S09;
- Ver/corregir CNC segun S10;
- Ver restricciones S20 cuando un factor lo exige;
- ninguna accion cuando el destino no esta autorizado.

Los href incluyen solo scope/periodo/focus permitidos. Nunca rol, permiso, db o prefix.

## Contrato HTTP target

### Snapshot principal

Se conserva:

    GET /api/bi/report/semanal

Forma conceptual:

    {
      "ok": true,
      "data": {
        "reportKey": "semanal",
        "scope": {},
        "period": {
          "mode": "week|range",
          "projects": [],
          "contextPoint": null
        },
        "capabilities": {},
        "coverage": {},
        "headline": {},
        "pac": {
          "current": {},
          "previous": {},
          "delta": {},
          "history": [],
          "projectBreakdown": []
        },
        "riskSummary": {},
        "commitments": [],
        "causes": {
          "cnc": {},
          "cnp": {}
        },
        "adoption": {
          "tnp": {},
          "crisis": {}
        },
        "limitations": [],
        "lineage": []
      },
      "meta": {
        "requestId": "...",
        "generatedAt": "...",
        "schemaVersion": 1
      }
    }

Invariantes:

- `reportKey=semanal`;
- corte/fase por proyecto;
- PAC reconcilia numerador/denominador/faltantes;
- historia ordenada por fecha;
- compromisos usan clave compuesta;
- high+medium+low+insufficient = compromisos elegibles;
- no existe raw `pacExpected` publico;
- solo high es notificationEligible;
- causas/adopcion reconcilian;
- cero y null son distintos;
- todos los textos son planos;
- capabilities/hrefs vienen del servidor.

### Detalles causales compartidos

Se reutilizan:

    GET /api/bi/report/programa-general/cnp-detail
    GET /api/bi/report/programa-general/cnc-detail

Query:

- scope/periodo/filtros T03;
- `category`;
- `limit` 1–100;
- `offset` >=0;
- `include_summary` boolean.

Durante convivencia aceptan `report_key=programa-general-*-detail`. Un presenter compartido entrega
el schema causal canonico de S09/S10. S21 no crea aliases HTTP nuevos.

No hay endpoint de riesgo detail: toda la evidencia necesaria ya viaja en el snapshot.

### Errores

| HTTP | code | Uso |
|---:|---|---|
| 400 | BAD_REQUEST | forma/query invalida |
| 401 | UNAUTHENTICATED | sesion ausente/expirada |
| 403 | FORBIDDEN | proyecto no autorizado |
| 404 | NOT_FOUND | hoja oculta |
| 422 | INVALID_PERIOD | semana/rango/focus/category invalido |
| 429 | RATE_LIMITED | si middleware vigente lo produce |
| 500 | INTERNAL_ERROR | fallo sin internals |
| 503 | TEMPORARILY_UNAVAILABLE | dependencia temporal |

Un bloque parcial se expresa en 200 con estado/limitacion tipada. No convierte toda la hoja en
error si PAC o riesgo siguen coherentes.

## Arquitectura target

### Backend

    BiSemanalReadService
      -> BiSheetAccessPolicy / BiProjectScope / BiQueryParser
      -> WeeklyPeriodResolver
      -> WeeklyCommitmentReader
      -> WeeklyPacProjector
      -> WeeklyRiskFeatureAssembler
      -> PacExpectedPolicy (extraida de ForecastService)
      -> WeeklyRiskLevelPolicy
      -> WeeklyCauseSummary (S09/S10)
      -> WeeklyAdoptionSummary
      -> WeeklyHeadline
      -> LineageService
      -> BiSemanalPresenter

Principios:

- un read model coherente;
- consultas por project_id y parametros preparados;
- no `SELECT *` en la ruta nueva;
- reloj Bogota inyectable;
- weeks por fecha real;
- historia sin leakage;
- sumas/ratios antes de redondear;
- servicios puros y fakeables;
- presenter canonical/legacy desde el mismo modelo;
- texto plano;
- sin cambios de schema.

`PacExpectedPolicy` extrae el calculo puro ya probado de `ForecastService`; el servicio historico
delega en ella. No se copian los pesos en un segundo algoritmo.

`MetricDictionaryService` conserva key `ps_pac_expected`, cambia la etiqueta a Riesgo de
incumplimiento y marca `integration_status=integrated_in_programacion_semanal`. Puede seguir
`descriptiva` para el endpoint escalar generico, porque el resultado es una coleccion por
compromiso, no un `MetricResult` escalar.

### Frontend

    frontend/src/lib/api/esquemas/biSemanal.ts
    frontend/src/lib/api/biSemanal.ts
    frontend/src/modulos/bi/SemanalPagina.tsx
    frontend/src/modulos/bi/semanal/
      ContextoSemana.tsx
      TitularPac.tsx
      ContrapesoPac.tsx
      TendenciaPac.tsx
      ResumenRiesgo.tsx
      ListaCompromisosRiesgo.tsx
      TablaCompromisosRiesgo.tsx
      TarjetasCompromisosRiesgo.tsx
      DetalleCompromiso.tsx
      ResumenCausas.tsx
      DesgloseCausal.tsx
      CoberturaCaptura.tsx
      ResumenAdopcion.tsx
      useBiSemanal.ts

- solo gateway llama `cliente.ts`;
- tipos derivan de Zod;
- no decision de negocio en componentes;
- tabla/cards reciben el mismo modelo;
- el drawer T03 alberga riesgo/causas/linaje;
- no `dangerouslySetInnerHTML`;
- no Chart.js.

### Estado cliente

- idle;
- loading;
- ready;
- refreshing;
- partial;
- empty;
- insufficient;
- offline;
- invalid_query;
- error.

Un cambio de query aborta la lectura anterior; una respuesta stale se ignora; cache key incluye
usuario, proyectos, periodo, filtros y focus. Detalles causales tienen cache/paginacion separada por
kind/category/query y nunca cruzan proyectos.

## Responsive y accesibilidad

### Desktop/tablet

`>=768`:

- resumen PAC y tendencia;
- tabla de compromisos;
- dos paneles causales;
- drawer lateral.

Columnas:

- nivel;
- actividad;
- responsable/contratista;
- fecha;
- criticidad/restricciones;
- muestra/estado;
- accion.

No se muestra score crudo.

### Movil

`<768`:

- titular compacto;
- conteo alto/medio/bajo/insuficiente;
- tarjetas de compromiso ordenadas;
- responsable, actividad, fecha, criticidad, restricciones, muestra y accion;
- causas/adopcion plegables por boton;
- drawer full-screen.

La tabla no se monta. Las tarjetas no esconden un porcentaje crudo.

### Graficos y alternativa textual

PAC/tamaño pueden usar SVG nativo:

- `title/desc`;
- puntos/barras por fecha real;
- dos escalas separadas o paneles, nunca conteo y porcentaje en el mismo eje;
- tabla visible equivalente;
- punto de contexto identificado.

Causas usan botones/barras semanticas con lista y conteos. Ninguna interaccion depende de hover o
canvas.

### Accesibilidad

- un h1;
- landmarks y encabezados sin saltos;
- texto de fase/corte;
- niveles con texto/icono, no solo color;
- tabla con caption/th scope;
- tarjetas en lista semantica;
- causa completa recuperable;
- botones reales;
- drawer con foco inicial/trap/Escape/retorno;
- live region de carga/filtros;
- targets 44x44 en movil;
- zoom 200 por ciento;
- reduced motion;
- axe serious/critical cero.

### Tema

- oscuro default/fallback;
- claro equivalente;
- tokens `public/css/tokens.css`;
- sin hex/rgb/hsl, `!important`, inline color o tema local;
- cinco viewports: 390x844, 480x900, 768x1024, 1180x820, 1440x900;
- cero overflow horizontal de pagina.

## Seguridad y limite RLS

S21 no modifica RLS:

- sesion/gate antes de montar;
- proyecto reautorizado en cada request;
- toda fuente por project_id;
- historia y joins conservan project_id;
- claves compuestas evitan colision multi;
- filtros/ids cliente no conceden autoridad;
- capacidades y hrefs server-side;
- cero mutaciones/CSRF porque la hoja es read-only;
- HTML se normaliza a texto;
- errores no filtran SQL, tablas, archivos, stack o IDs ajenos;
- `docs/security/rls-runtime-boundary.md` permanece intacto.

## Convivencia, corte y rollback

1. Caracterizar payload legacy y detalles compartidos con fixtures.
2. Construir read model/presenter canonico sin cambiar la ruta.
3. Montar ruta shadow en la SPA T01.
4. Comparar PAC, poblaciones causales, filtros y enlaces.
5. Probar roles, fases, themes, viewports y deep links.
6. Cortar `/bi/semanal`.
7. Mantener adapter legacy y endpoints de detalle.
8. Retirar seccion/render/chart semanal legacy solo con cero callers y gate T03.

S21 no borra dialogs CNP/CNC de S18 hasta que deep links y detalles canonicos esten verdes. Los
endpoints se retiran o renombran solo con cero callers, fuera de esta entrega si corresponde.

Rollback:

- devolver la ruta al render legacy;
- conservar contratos canonicos;
- no restaurar datos;
- verificar que adapter sigue sirviendo scorecard/donut legacy;
- restaurar React.

## Estrategia de pruebas

### PHP puro

- acceso/query/periodo;
- semana por ancla y no ISO;
- fase/next/previous;
- PAC disponible/null/cero;
- TNP excluido;
- comparacion y tendencia;
- multi ponderado;
- assembler de seis variables;
- cuatro semanas previas sin leakage;
- muestra minima;
- weights y niveles 60/80;
- no score publico;
- solo high eligible;
- poblacion/coverage CNP/CNC;
- recordedBy unavailable;
- TNP/crisis;
- headline/plain text;
- presenter canonical/legacy;
- rutas y source invariants.

Fakes/fixtures/call logs; cero MySQL/DDL/DML.

### Frontend

- schemas/gateway;
- estados;
- PAC/contrapeso/tendencia;
- riesgo/lista/detalle;
- focus/deep links;
- causas/paginacion;
- adopcion;
- table/cards;
- drawer;
- dark/light;
- stale/cache.

### Browser interceptado

Antes de navegar:

- interceptar bootstrap/proyectos/semanas/filtros/report;
- interceptar detalle CNP/CNC solo en sus escenarios;
- fallar en cualquier request inesperado;
- fallar en cualquier POST/PUT/PATCH/DELETE;
- nunca llegar a MySQL.

Escenarios:

- A/D/R y oculto/denegado;
- semana cerrada;
- semana abierta sin PAC;
- PAC cero valido;
- anchor/next week;
- multi;
- high/medium/low/insufficient;
- CNP/CNC deep links y paginacion;
- recordedBy limitation;
- TNP/crisis zero y missing category;
- empty/partial/offline/error;
- dark/light;
- cinco viewports;
- 200 zoom;
- teclado/foco/Escape;
- axe/consola/red.

No se regeneran goldens.

## Criterios de aceptacion

1. S21-AC-01: el documento excluye admin, RLS, schema, datos, DDL/DML, correo y deploy.
2. S21-AC-02: `/bi/semanal` es ruta SPA principal al corte.
3. S21-AC-03: S21 reutiliza T01/T03 y no duplica shell, query, periodo, tema, drawer ni cliente.
4. S21-AC-04: S08/S09/S10 conservan propiedad de mutaciones y catalogos operativos.
5. S21-AC-05: A/D/R entran segun gate y `lps.indicadores.ver`.
6. S21-AC-06: roles ocultos reciben 404.
7. S21-AC-07: proyecto no autorizado recibe 403 sin datos.
8. S21-AC-08: role/permiso/project/db/prefix/capability cliente no conceden autoridad.
9. S21-AC-09: proyecto activo autorizado es default.
10. S21-AC-10: multi requiere seleccion explicita y conserva breakdown por obra.
11. S21-AC-11: la hoja compartida no crea variantes por audiencia.
12. S21-AC-12: default se resuelve por fechas de semanas_activas del proyecto.
13. S21-AC-13: en Fecha_Fin_Sem abre la semana que cierra y ofrece nextWeek si existe.
14. S21-AC-14: no usa lunes, ISO week o numero global como fallback silencioso.
15. S21-AC-15: rango manda y semana es atajo autorizado.
16. S21-AC-16: filtros gobiernan toda seccion compatible o se declara limitacion.
17. S21-AC-17: multi devuelve cutoff/fase propios por proyecto.
18. S21-AC-18: el GET semanal existente es el unico request inicial de datos.
19. S21-AC-19: drilldowns reutilizan los dos GET causales existentes.
20. S21-AC-20: report y detalles tienen contrato PHP y schemas Zod.
21. S21-AC-21: no se crea endpoint de lectura, detalle o mutacion nuevo.
22. S21-AC-22: envelope contiene scope, period, capabilities, coverage y meta.
23. S21-AC-23: periodo publica start/end/confirmed/closeDate/phase/previous/next por obra.
24. S21-AC-24: semana abierta sin PAC devuelve pacPercent null/insufficient.
25. S21-AC-25: ausencia de PAC nunca produce riesgo alto/confianza alta.
26. S21-AC-26: PAC usa Activa 1/NA y excluye TNP.
27. S21-AC-27: PAC expone compromisos/cumplidos/incumplidos/sin registro reconciliados.
28. S21-AC-28: cero cumplidos registrado se distingue de dato ausente.
29. S21-AC-29: titular muestra N de M, porcentaje sin decimales innecesarios y faltantes.
30. S21-AC-30: contrapeso muestra conteo actual/anterior y deltas.
31. S21-AC-31: delta PAC solo existe cuando ambos cortes son comparables.
32. S21-AC-32: tendencia usa fechas/cortes reales y punto de contexto identificado.
33. S21-AC-33: multi suma numeradores/denominadores y nunca promedia PAC de obras.
34. S21-AC-34: cada obra conserva PAC, faltantes, fase y deltas propios.
35. S21-AC-35: PPC no aparece como segunda metrica duplicada.
36. S21-AC-36: la limitacion D91 acompaña PAC y evita evaluar a la persona.
37. S21-AC-37: ps_pac_expected se rotula Riesgo de incumplimiento e integra en S21.
38. S21-AC-38: calculo conserva pesos 25/20/15/20/10/10.
39. S21-AC-39: historia del modelo usa cuatro semanas previas reales del proyecto.
40. S21-AC-40: semana seleccionada y futuras no contaminan variables historicas.
41. S21-AC-41: PAC contratista usa misma entidad, PAC registrado y publica muestra.
42. S21-AC-42: PAC responsable usa misma entidad, PAC registrado y publica muestra.
43. S21-AC-43: criticidad viene de la fila actual y dato invalido queda insufficient.
44. S21-AC-44: restricciones se unen por proyecto+semana+unique_id.
45. S21-AC-45: avance actual exige valor finito 0–1.
46. S21-AC-46: CNC reciente cuenta la misma actividad en cuatro semanas previas.
47. S21-AC-47: contratista y responsable requieren cada uno muestra minima tres.
48. S21-AC-48: cualquier variable faltante/invalida produce insufficient con razones.
49. S21-AC-49: no hay cero, promedio global o sustitucion sintetica.
50. S21-AC-50: WEEKLY_RISK_LEVELS_1.0 usa <0.60 alto, <0.80 medio, resto bajo.
51. S21-AC-51: el payload publico no contiene porcentaje/score esperado crudo.
52. S21-AC-52: solo nivel alto es notificationEligible.
53. S21-AC-53: S21 no envia correo ni ejecuta side effects de notificacion.
54. S21-AC-54: lista ordena alto/medio/bajo/insufficient, critica, fecha y key estable.
55. S21-AC-55: cada compromiso usa key projectId:week:rowId y muestra identidad/owner/corte.
56. S21-AC-56: evidencia incluye modelo, nivel, factores, muestras y limitaciones.
57. S21-AC-57: PAC observado y riesgo predictivo son estructuras/rotulos separados.
58. S21-AC-58: bi_riesgos generico no es autoridad de la lista React.
59. S21-AC-59: fulfillment_alert queda solo como compatibilidad legacy.
60. S21-AC-60: headline server-side es factual y sensible a fase/comparabilidad.
61. S21-AC-61: semana abierta dice PAC pendiente/provisional y nunca cierre definitivo.
62. S21-AC-62: semana cerrada muestra N/M y deltas.
63. S21-AC-63: headline/riesgos/acciones son texto plano sin HTML.
64. S21-AC-64: detalle de riesgo abre desde snapshot sin otro GET.
65. S21-AC-65: acciones/hrefs vienen del servidor y respetan capacidades S08-S10/S20.
66. S21-AC-66: la hoja no hace mutaciones ni solicita CSRF.
67. S21-AC-67: resumen CNP usa la poblacion canonica S09, incluida incompleta.
68. S21-AC-68: resumen CNC usa la poblacion canonica S10, incluida incompleta/inconsistente.
69. S21-AC-69: completitud causal coincide con validacion S08/S09/S10.
70. S21-AC-70: incumplimiento/no programada sin causa permanece visible.
71. S21-AC-71: cobertura expone eligible/documented/missing/rate o null.
72. S21-AC-72: categorias desconocidas conservan original y no desaparecen.
73. S21-AC-73: causa/atribucion completa es recuperable sin truncamiento.
74. S21-AC-74: desglose de subcausa se abre por click/teclado, no hover.
75. S21-AC-75: detalle causal conserva category/limit/offset/total/hasMore y query.
76. S21-AC-76: focus=cnp|cnc/category desde S18 abre el drawer correcto.
77. S21-AC-77: desglose admite filtro por responsable sin ranking personal.
78. S21-AC-78: recordedBy unavailable se muestra como limitacion de fuente.
79. S21-AC-79: responsable del compromiso nunca se rotula registrador.
80. S21-AC-80: adopcion TNP muestra total/categorizados/sin categoria/cobertura.
81. S21-AC-81: adopcion crisis muestra conteo por corte/proyecto.
82. S21-AC-82: cero TNP/crisis se redacta no registrado, no ausencia real.
83. S21-AC-83: reprogramaciones_semanales no se lee, muestra ni reintroduce.
84. S21-AC-84: causas/adopcion no se usan para reconvenir o calificar personas.
85. S21-AC-85: corregir/capturar navega a rutas operativas autorizadas.
86. S21-AC-86: orden es contexto, PAC, contrapeso, riesgo, CNC, CNP, adopcion, linaje.
87. S21-AC-87: existe un h1 y jerarquia/landmarks coherentes.
88. S21-AC-88: >=768 monta tabla y <768 monta cards para compromisos.
89. S21-AC-89: tabla y cards nunca se montan simultaneamente.
90. S21-AC-90: tarjeta movil muestra nivel, actividad, owner, fecha, criticidad, restricciones, muestra y accion.
91. S21-AC-91: detalles causales tambien usan tabla/cards en el mismo breakpoint.
92. S21-AC-92: grafico PAC/tamaño tiene alternativa textual visible y escalas no mezcladas.
93. S21-AC-93: drawer cumple foco inicial, trap, Escape y retorno.
94. S21-AC-94: ninguna accion/causa/nivel depende solo de hover, canvas o color.
95. S21-AC-95: blancos tactiles son al menos 44x44 en movil.
96. S21-AC-96: oscuro/claro tienen informacion y capacidades identicas.
97. S21-AC-97: solo tokens.css; sin literal de color, important, inline color o libreria nueva.
98. S21-AC-98: cinco viewports y zoom 200 no tienen overflow horizontal de pagina.
99. S21-AC-99: teclado/touch/foco/reduced-motion cumplen y axe serious/critical es cero.
100. S21-AC-100: loading/ready/refreshing/partial/empty/insufficient/offline/invalid/error son visibles.
101. S21-AC-101: stale response se ignora y cambio de query aborta requests.
102. S21-AC-102: cache no cruza usuario/proyecto/periodo/filtros/focus/category.
103. S21-AC-103: solo cliente.ts llama fetch y tipos derivan de z.infer.
104. S21-AC-104: linaje cubre PAC, riesgo, causas, adopcion, muestras, umbrales y filtros.
105. S21-AC-105: errores canonicos no filtran internals.
106. S21-AC-106: tests PHP nuevos usan fakes y cero MySQL/DDL/DML.
107. S21-AC-107: browser intercepta todos los GET y falla ante cualquier mutacion.
108. S21-AC-108: no se ejecutan suites DML ni se regeneran goldens sin aprobacion.
109. S21-AC-109: RLS/schema/grants/usuarios/credenciales/datos permanecen intactos.
110. S21-AC-110: presenter legacy conserva scorecard/chart mientras tenga caller.
111. S21-AC-111: endpoints causales compartidos permanecen hasta cero callers.
112. S21-AC-112: rollback cambia ruta/codigo y no restaura datos.
113. S21-AC-113: enlaces S17/S18/S20/S08-S10 preservan scope/periodo/filtros/focus.
114. S21-AC-114: no se añade email, exportacion, impresion o persistencia de acciones.
115. S21-AC-115: consola/red quedan limpias y no se tolera request inesperado.
116. S21-AC-116: la pantalla declara los ritmos semanal y diario sin crear dos variantes.

## Entregas verticales

### Entrega 1 — Periodo y PAC

- access/query;
- semana por ancla/fase;
- PAC canonico;
- anterior/tendencia;
- multi;
- endpoint/Zod.

### Entrega 2 — Riesgo diario

- feature assembler;
- calculo puro;
- niveles N5;
- lista/evidencia;
- titular/acciones;
- linaje.

### Entrega 3 — Causas y adopcion

- resumen CNC/CNP;
- cobertura;
- deep links/drilldown;
- recordedBy limitation;
- TNP/crisis.

### Entrega 4 — Interfaz

- PAC/tendencia;
- tabla/cards;
- drawer;
- filtros/estados;
- oscuro/claro/a11y.

### Entrega 5 — Corte

- route;
- browser interceptado;
- adapter;
- convivencia;
- rollback;
- retiro diferido.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| semana ISO equivoca obra | resolver por fechas |
| semana abierta parece PAC 0 | null/insufficient |
| PAC alto por compromiso menor | conteo/delta juntos |
| PAC juzga persona | copy D91 |
| risk usa heuristica vieja | assembler + ForecastService |
| score parece precision | tres niveles, no score |
| historia filtra futuro | cutoffs estrictamente previos |
| faltante parece bajo | insufficient |
| multi promedia | sumas + breakdown |
| causas omiten faltantes | poblaciones S09/S10 |
| responsable parece registrador | recordedBy unavailable |
| texto causal se corta | detalle completo/click |
| cero crisis parece sano | no registrado |
| TNP contamina PAC | exclusion |
| detalle duplica endpoint | reusar contratos S18 |
| HTML entra al DOM | presenter texto plano |
| hoja empieza a escribir | hrefs operativos |
| tabla movil | cards exclusivas |
| tests alteran semana | interception/fakes |
| retiro rompe S18 | cero callers |

## Decisiones descartadas

- Copiar el donut/table legacy: semantica incorrecta.
- Mostrar 0 cuando PAC falta: contradiccion medida.
- Usar semana ISO/lunes: contradice calendario por obra.
- Separar porcentaje de denominador: esconde encogimiento.
- Mostrar PAC y PPC iguales: duplicacion.
- Usar `fulfillment_alert`: no es N5.
- Usar `bi_riesgos`: otro grano/modelo.
- Exponer score crudo: falsa precision.
- Imputar historia global: oculta insuficiencia.
- Contar semana actual en historia: leakage.
- Marcar insufficient como bajo: falso negativo.
- Crear endpoint risk-detail: snapshot basta.
- Crear endpoints semanal-cnp/cnc: ya existen compartidos.
- Llamar responsable al registrador: dato inexistente.
- Agregar columna de actor: prohibido por limite schema.
- Interpretar cero crisis como sin crisis: confunde captura/realidad.
- Reintroducir campo muerto: decision N8.
- Enviar correo desde React: side effect fuera de hoja.
- Editar S08/S09/S10 desde BI: duplica dominios.
- Chart.js/dos DOM responsive: deuda innecesaria.
- Probar con transacciones rollback: sigue siendo DML.

## Decisiones pendientes

Ninguna. Si la implementacion descubre una fuente gobernada del actor causal, una calibracion
aprobada posterior a los umbrales 60/80, una formula distinta para CNC reciente, un consumidor
externo del score crudo o una divergencia real entre S09/S10 y los detalles S18, se detiene solo ese
tramo, se aporta evidencia y se enmienda esta spec. No se inventa dato ni se toca RLS/schema.

## Siguiente gate

Invocar `superpowers:writing-plans` para
`docs/superpowers/plans/2026-08-30-s21-bi-semanal-react.md`, autorrevisarlo, actualizar el atlas y
continuar S22. No implementar S21 en esta sesion.
