---
capa: fuente
tipo: spec
estado: vigente
id: S22
fecha: 2026-08-31
superficie: bi-pdc
rutas:
  - "/bi/pdc"
  - "/api/bi/report/pdc"
  - "/api/bi/report/pdc/detail"
depende_de: [T01, T03, S12, S17]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [bi, pdc, rbac, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, SeguimientoService, MetricDictionaryService, LineageService, StorytellingService, ActionRecommendationService, BiPreviewAccessPolicy, RbacCatalog, pdc_plan_paquete, pdc_plan_paso, pdc_paquete_frente, general_paquetes_contratacion, VIEW-04/09, control-tower.php, bi-spa.js, CSS, pruebas, respuestas read-only servidas, specs CT-8.6/CT-11.b, B3, S12, S17 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S22 de la hoja BI Plan de Compras a React: cobertura por conteo y valor con denominadores, pasos planeados por horizonte, vencido confirmado separado de fecha pasada sin avance, lista accionable, responsables, duracion provisional, contrapeso de cierre real, filtros, drawer, eventos puros y enlaces al PDC v2, responsive y oscuro/claro, sin mutaciones ni cambios RLS/schema/datos."
---

# S22 — Hoja BI Plan de Compras en React

> **Estado:** diseño técnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementación,
> commits, DDL/DML, cambios RLS, cambios de capacidades, correo, deploy, publicación ni trabajo en
> `/admin/`. Su plan se escribe a continuación con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S22 desarrolla CT-8.6 para el lienzo de Obra. Sirve tres decisiones sin convertir BI en otro módulo
operativo:

- dirección y residencia ven qué paso de contratación requiere atención hoy;
- gerencia conserva en S17 el indicador agregado de cobertura; A también puede abrir esta hoja al
  elegir el lienzo Obra;
- Compras recibe la acción en S12, no acceso implícito a BI.

Consume:

- T01 para sesión, proyecto, shell, sidebar, tema, route outlet y único cliente HTTP;
- T03 para gate por hoja, query, corte, filtros, estados, drawer, linaje y señales de distribución;
- S12 como dueño de Plan de Compras v2, sus fechas, responsables, duraciones, mutaciones y rutas
  operativas;
- S17 como panorama gerencial y punto de entrada permitido según lienzo.

S22 es read-only. No amarra paquetes, no recalcula fechas, no asigna responsables y no registra
fechas reales. Cuando una decisión exige actuar, el servidor entrega un `href` autorizado hacia
`/plan-compras/ensamble/plan` o `/plan-compras/seguimiento/avance`.

## Resultado buscado

`/bi/pdc` pasa a la SPA principal y:

1. declara que el corte es hoy del servidor en `America/Bogota` y que la semana BI no aplica;
2. muestra cobertura por conteo y por valor con numerador, denominador y estado de suficiencia;
3. agrega obras sumando magnitudes crudas, nunca promediando porcentajes;
4. muestra destinos sin responsable como tercera cifra de cobertura operativa;
5. separa un vencido confirmado de una fecha pasada sin avance registrado;
6. rotula las fechas como planeadas y marca las duraciones provisionales;
7. conserva la escala ya vencido, esta semana, 2, 3 y 6 semanas, adelante y sin fecha;
8. trata sin fecha como alarma y no lo esconde en un total ambiguo;
9. exhibe pasos cerrados con fecha real sobre pasos planeados como contrapeso obligatorio;
10. abre con una lista accionable de pasos y no solo con gráficos agregados;
11. identifica obra, destino, paso, responsable, fecha, días y certeza sin revelar proveedor;
12. permite buscar y filtrar por estado, paso, responsable y proyecto;
13. conserva breakdown por horizonte, paso, responsable y obra;
14. ofrece carga inicial en un GET y detalle paginado en el GET ya existente;
15. usa un drawer contextual compartido y enlaces operativos autorizados;
16. produce una señal pura al entrar un paso en ventana de vencimiento, sin enviar nada;
17. nombra la acción en estados vacíos o de datos incompletos;
18. funciona en desktop, tablet y móvil, oscuro y claro, teclado, touch, zoom y lector de pantalla;
19. conserva aislamiento por proyecto, admite A/D/R según el lienzo Obra y no amplía el acceso de
    OT ni otros roles;
20. permanece completamente read-only.

## Alcance

### Incluido

- `GET /bi/pdc` como ruta SPA al corte.
- `GET /api/bi/report/pdc` estabilizado como snapshot canónico.
- `GET /api/bi/report/pdc/detail` estabilizado como detalle paginado.
- A sin dependencia del flag y D/R con flag BI, siempre según `BiSheetAccessPolicy` y
  `lps.indicadores.ver` por proyecto.
- Proyecto activo por defecto y multi explícito, con filas calificadas por proyecto.
- Corte único `today` resuelto en servidor con zona `America/Bogota`.
- Declaración visible de que el selector de semana no aplica.
- Cobertura por conteo y valor con magnitudes crudas y porcentajes derivados.
- Conteo de destinos sin responsable.
- Conteo de destinos con duración provisional.
- Pasos planeados, cerrados con fecha real y pendientes.
- Estado de decisión separado del cubo temporal.
- Vencido confirmado, fecha pasada sin avance, ventana 1/2/3/6, adelante y sin fecha.
- Lista urgente, búsqueda, filtros y paginación.
- Breakdown por horizonte, paso, responsable, proyecto y suficiencia.
- Titular factual escrito en servidor.
- Acciones contextuales read-only hacia S12 según capacidades reales.
- Señal de distribución pura para T03, sin side effects.
- Linaje y limitaciones de `pdc_at_risk` y `compras.duracion_real_paso`.
- Tabla desktop/tablet y tarjetas móviles.
- Drawer contextual accesible.
- Oscuro/claro, cinco viewports, zoom, reduced motion y accesibilidad.
- Contratos PHP y Zod, pruebas puras y navegador totalmente interceptado.
- Convivencia, corte, rollback y retiro diferido de la sección legacy.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, `ProjectScope`, grants, usuarios, credenciales, schema, vistas SQL,
  tablas, columnas, índices, triggers, datos, seeds o fixtures persistentes.
- Ejecutar DDL/DML, aun dentro de una transacción revertida.
- Cambiar `internal.bi.preview`, `bi.control_tower.visible`, catálogos de roles, aliases o
  capacidades.
- Admitir OT, V, C, DCV, G, S o SG a la hoja PDC.
- Dar a OT una excepción al gate BI.
- Editar responsables, amarres, frentes, duraciones, pasos o fechas reales desde BI.
- Recalcular, reprogramar, copiar reglas, partir lotes o restaurar pasos desde BI.
- Reintroducir PDC v1, `bi_pdc_general`, `/pdc`, `/api/pdc/*` o sus 18 tablas eliminadas.
- Leer proveedor, contacto, precio unitario, valor de un destino o datos comerciales por fila.
- Etiquetar como vencido todo paso con fecha pasada.
- Convertir duración planeada en estimación o predicción calibrada.
- Publicar `compras.duracion_real_paso` como métrica comparativa antes de 20 cierres por tipo.
- Permitir que el cliente elija una fecha histórica arbitraria.
- Aplicar filtros de lista a cobertura presupuestal sin declararlo.
- Enviar correo, notificación, webhook o evento persistente desde la hoja.
- Resolver destinatarios, deduplicación diaria o marcador histórico dentro de S22.
- Añadir endpoint, alias, mutación o descarga.
- Replicar el CSV de flujo de caja de S12.
- Añadir librería de tabla, gráfica, estado o formularios.
- Mostrar canvas Chart.js o una visualización que dependa de hover o color.
- Añadir variantes de payload por audiencia.
- Eliminar endpoints o componentes BI compartidos antes del gate T03/S17–S24.
- Regenerar goldens sin aprobación explícita.

## Fuentes y precedencia

Para S22 manda esta precedencia:

1. código y contratos ejecutables vigentes;
2. AGENTS de raíz y frontend;
3. CT-8.6 y CT-11.b de la v0 del producto;
4. S17 para reparto de lienzos y T03 para infraestructura BI;
5. S12 para semántica y mutaciones de Plan de Compras v2;
6. B3 como caracterización histórica;
7. memoria y comentarios legacy solo como contexto.

CT-8.6 corrige la interpretación anterior de B3. En particular:

- `vencido` no equivale a `fecha_fin < hoy AND fecha_real IS NULL`;
- `sin avance registrado` es un estado separado;
- el contrapeso de cierres reales es obligatorio;
- `sin responsable` es una cifra principal;
- el vacío debe nombrar la acción.

## Punto de partida medido

### React

- No existe página, módulo, schema, gateway ni ruta React para BI PDC.
- El frontend actual contiene el shell mínimo y contratos base.
- El directorio `pdc-app/` es la isla operativa de Plan de Compras v2; no es S22.
- S12, S17 y T03 existen como specs/planes, no como implementación disponible en este worktree.

### Rutas y acceso actuales

| Verbo | Ruta | Uso legacy |
|---|---|---|
| GET | `/bi/pdc` | layout BI compartido |
| GET | `/api/bi/report/pdc` | brief agregado |
| GET | `/api/bi/report/pdc/detail` | detalle no consumido por la UI |
| GET | `/api/bi/projects` | proyectos BI |
| GET | `/api/bi/weeks` | semanas globales, aunque PDC las ignora |
| GET | `/api/bi/filter-options` | filtros globales no aplicados por PDC |
| GET | `/api/bi/lineage` | linaje lazy legacy |

El gate global vigente admite A y, con flag, D/R. S17 aprobó una política adicional por hoja:

| Lienzo | Roles | PDC |
|---|---|---|
| Gerencia | A | no como entrada de ese lienzo |
| Obra | A, D, R | sí |
| Operación de compras | OT | S12, no BI |

Por tanto el target de S22:

- admite A cuando elige Obra, sin depender del flag;
- admite D/R si el flag está activo y el proyecto tiene `lps.indicadores.ver`;
- devuelve 404 a OT y demás roles para página y ambos API;
- devuelve 403 para un proyecto fuera del scope después de pasar el gate de hoja;
- no cambia `RbacCatalog` ni crea una capacidad nueva.

### Contradicción de actor y decisión segura

CT-8.6 nombra a Compras como actor que destraba. El rol OT tiene capacidades operativas de PDC,
pero `internal.bi.preview` lo excluye y S17 no incluyó OT en ningún lienzo BI.

S22 no resuelve esa tensión ampliando permisos. La decisión cerrada es:

1. A/D/R observan la hoja desde el lienzo Obra y escalan desde allí;
2. OT actúa en S12 mediante un enlace directo o una señal distribuida por T03;
3. el enlace de la señal apunta a S12, no a `/bi/pdc`;
4. si en el futuro se quiere que OT vea BI, requiere autorización separada y una enmienda de S17;
5. la migración conserva el límite de acceso y no lo presenta como paridad faltante.

### Vista legacy

La sección `view-pdc` contiene:

- titular y subtitular calculados en JavaScript;
- fecha de corte;
- tres KPI: vencidas, en riesgo a tres semanas y sin mirar;
- canvas de horizonte;
- canvas por paso;
- canvas por responsable;
- canvas de cobertura por obra;
- un `details` con scorecard y cuatro tablas.

No contiene:

- lista accionable de pasos;
- separación entre vencido y sin avance;
- contador de cierres reales;
- número de destinos sin responsable;
- marca de duración provisional;
- búsqueda o filtros locales;
- drawer contextual;
- tarjetas móviles;
- uso del GET de detalle;
- señal de distribución.

El responsive legacy conserva canvases y tablas con overflow. No existe una composición móvil
orientada a la decisión.

### Contrato servido

`GET /api/bi/report/pdc` usa el envelope general de `ControlTowerService::getBrief()`:

- `respuesta/project_ids/project_id/semana/report_key/role/filters`;
- `data_source/raw_row_count`;
- `executive_brief/scorecard/charts/drivers/risks/recommended_actions/lineage`;
- `pdc_breakdown`.

Cada fila de `data_source` contiene:

- `project_id/obra`;
- `cobertura/cobertura_valor`;
- `vencidos/en_riesgo`;
- `destinos/pasos/sin_mirar/hoy`.

`pdc_breakdown` contiene:

- `totales` por horizonte;
- `por_paso` con pendientes y vencidos;
- `por_responsable` con pendientes y vencidos.

`GET /api/bi/report/pdc/detail` devuelve:

- `respuesta`;
- `hoy`;
- `paquetes[]` con proyecto, paquete, lote, paso, fecha, estado, días y responsable.

El detalle actual:

- no pagina;
- no filtra;
- no busca;
- no incluye total;
- no incluye nombre de obra;
- no incluye IDs estables suficientes;
- convierte `diasDesfase=null` en cero;
- no incluye frente, duración provisional, evidencia de avance ni href;
- no es consumido por `bi-spa.js`.

### Lectura read-only servida

Con el runtime montado desde este worktree y servicios PHP existentes, una lectura SELECT-only del
proyecto de caracterización produjo al 2026-08-31:

| Magnitud | Valor |
|---|---:|
| Cobertura por conteo | 95,2 % |
| Cobertura por valor | 100 % |
| Destinos con pasos pendientes | 29 |
| Pasos planeados | 203 |
| Pasos cerrados con fecha real | 2 |
| Pasos pendientes | 201 |
| Fecha pasada según cubo legacy | 59 |
| Vencido confirmado según CT-8.6 | 6 |
| Fecha pasada sin avance registrado | 53 |
| Próximas 3 semanas | 2 |
| Próximas 6 semanas adicionales | 4 |
| Adelante | 136 |
| Sin fecha en pasos ya calculados | 0 |
| Destinos sin responsable | 29 |
| Destinos con duración provisional | 9 |
| Paquetes que esperan fechas | 44 |
| Sin amarre a frente | 44 |
| Pendientes de recalcular | 0 |

El endpoint legacy llamó `vencidos` a los 59 casos. CT-8.6 permite llamar vencidos solo a 6. Los
otros 53 deben mostrarse como `sin avance registrado` hasta que exista al menos un paso real cerrado
en su destino.

No se escribieron datos. La puerta de servicio estaba cerrada y no se modificó `.env`. No se
ejecutaron las pruebas PDC que crean o eliminan fixtures persistentes.

### Defectos semánticos confirmados

1. `fetchPdc()` y `pdcBreakdown()` leen vencimientos por separado.
2. Un cruce de medianoche puede producir dos cortes dentro del mismo payload.
3. `scorecardPDC()` pondera porcentajes de cobertura por destinos.
4. Esa ponderación no equivale a sumar numeradores/denominadores.
5. Cobertura cero también representa ausencia de denominador.
6. `sin_mirar` solo mide cronograma desactualizado, no paquetes que esperan fechas.
7. `StorytellingService::briefPDC()` usa campos eliminados de PDC v1.
8. Puede declarar “todos listos” aunque haya pasos pasados.
9. `ActionRecommendationService::actionsFromPDC()` usa campos eliminados de PDC v1.
10. Puede devolver cero acciones ante decenas de pasos urgentes.
11. `detalleDestinos()` pierde null en `diasDesfase`.
12. La UI nunca llama el detalle que ya existe.
13. La semana elegida viaja en el payload aunque no gobierna PDC.
14. Los filtros globales viajan aunque no se aplican.
15. El proveedor está correctamente excluido y debe seguir excluido.

## Modelo de dominio de lectura

### Grano

La unidad mínima es un paso planeado de un destino de contratación:

`projectId + packageId + subpackageId + stepId/stepOrder`.

Un destino es:

`projectId + packageId + subpackageId`.

Si `subpackageId=0`, el destino es el paquete. Si es distinto de cero, el destino es el lote. No se
agrega por paquete ignorando lote.

La clave estable de fila es:

`projectId:packageId:subpackageId:stepId`.

Solo si `stepId` es null por compatibilidad de catálogo se usa:

`projectId:packageId:subpackageId:order`.

La clave nunca contiene nombre, responsable ni fecha mutable.

### Corte

Un único `PdcCutoff` se crea al inicio del request:

- `mode: "today"`;
- `date: YYYY-MM-DD`;
- `timezone: "America/Bogota"`;
- `weekApplicable: false`;
- `generatedAt` en ISO-8601.

Todos los lectores del snapshot reciben el mismo objeto. Ningún servicio vuelve a pedir “hoy”.

La interfaz:

- oculta el selector de semana dentro de esta hoja o lo muestra deshabilitado;
- muestra “Corte: hoy del servidor, <fecha>”;
- no actualiza `$_SESSION['semana']`;
- ignora un `semana` legado de forma explícita y lo declara en `meta.ignoredQuery`;
- rechaza `cutoff/date/hoy` enviados por el cliente con 422, en lugar de fingir histórico.

### Cobertura

La cobertura por conteo expone:

- `coveredCount`;
- `eligibleCount`;
- `percent`;
- `status`: `available | insufficient`.

La cobertura por valor expone:

- `coveredValue` en unidad monetaria agregada;
- `eligibleValue`;
- `percent`;
- `status`.

El frontend no necesita recibir valor de cada destino. El agregado de valor se autoriza porque ya es
una métrica BI; el detalle comercial permanece en S12.

Reglas:

1. porcentaje = numerador / denominador × 100;
2. denominador cero produce `percent=null` y `status=insufficient`;
3. multi-proyecto suma numeradores y denominadores;
4. nunca promedia porcentajes;
5. cada proyecto conserva el mismo par crudo;
6. redondeo de display a una decimal, cálculo con precisión completa;
7. valores negativos o numerador mayor al denominador son `invalid` y no se corrigen en cliente;
8. filtro de lista no altera cobertura; se declara `filterApplicability: "scope"`.

### Pasos y progreso

El contrapeso expone:

- `plannedSteps`: todos los pasos planeados del scope activo;
- `closedWithActual`: pasos con `fecha_real` válida;
- `pendingSteps`: pasos sin `fecha_real`;
- `closurePercent`: cerrado / planeado;
- `destinationsWithAnyActual`;
- `destinationsWithoutAnyActual`.

Un paso cerrado no aparece en la lista pendiente, pero sí en el contrapeso.

La existencia de avance se evalúa por destino, no por paquete global. Un cierre en un lote no
convierte otros lotes en “con avance”.

### Cubo temporal y estado de decisión

`scheduleBucket` depende solo de `fecha_fin` y corte:

- `past`;
- `week1`;
- `week2`;
- `week3`;
- `week6`;
- `ahead`;
- `without_date`.

Los límites conservan la función vigente:

- pasado: fecha < hoy;
- week1: 0–6 días;
- week2: 7–13;
- week3: 14–20;
- week6: 21–41;
- adelante: 42 o más;
- sin fecha: null/inválida.

`decisionState` añade evidencia de avance:

- `overdue`: pendiente, fecha pasada y destino con al menos un cierre real;
- `unrecorded_progress`: pendiente, fecha pasada y destino sin cierres reales;
- `week1`, `week2`, `week3`, `week6`, `ahead` o `without_date`;
- `invalid` si una fecha presente no cumple ISO o contradice el modelo.

`daysDelta`:

- positivo para días vencidos;
- cero para hoy;
- negativo para días que faltan;
- null cuando no hay fecha o es inválida.

La UI dice:

- “Vencido hace N días” para `overdue`;
- “Fecha pasada · sin avance registrado” para `unrecorded_progress`;
- “Planeado para hoy/en N días” para ventanas;
- “Sin fecha planeada” para `without_date`.

Nunca convierte `unrecorded_progress` en verde ni lo oculta. Es una alarma de calidad/registro
distinta del incumplimiento confirmado.

### Duración planeada y provisional

Cada destino expone:

- `durationDays`;
- `durationSource: package_catalog | company_median | unavailable`;
- `durationProvisional: boolean`.

La interfaz usa “duración planeada”. Si viene de mediana corporativa muestra “provisional” junto a
la cifra. Si no hay duración, muestra “sin duración” y una acción a S12. No usa “estimada”,
“pronosticada” ni “probable”.

`compras.duracion_real_paso` entra al catálogo como descriptiva:

- grano: tipo de paquete + paso;
- fuente: cierres con fecha real;
- mínimo de publicación: 20 pasos cerrados por tipo;
- debajo del umbral: `insufficient`;
- en S22 solo aparece en linaje/completitud;
- no compara responsables ni publica ranking.

### Responsabilidad

Para cada destino se conserva `responsableUserId` internamente y se entrega:

- `responsible.label`;
- `responsible.assigned`;
- `responsible.href` solo si la acción está autorizada.

`withoutResponsibleDestinations` cuenta destinos, no pasos. Un destino con nueve pasos sin
responsable cuenta una vez.

“Sin responsable” aparece primero en el breakdown y no se reemplaza por “Compras”. La hoja no
inventa dueño.

### Paquetes que esperan fechas

El read model distingue:

- `packagesAwaitingDates`;
- `packagesWithoutFrontAnchor`;
- `packagesPendingRecalculation`;
- `pendingStepsWithoutDate`;
- `staleSchedulePackages`.

No se agrupan como `sin mirar`.

Si no hay pasos planeados y existen paquetes pendientes, el estado vacío principal dice:

“<N> paquetes esperan fechas — amarrar y recalcular en Ensamble › Plan”.

El enlace se entrega solo con `lps.paquetes_contratacion.ver`; la capacidad de editar gobierna si la
pantalla destino permite ejecutar la acción.

### Titular factual

`PdcHeadline` recibe el read model canónico, no filas legacy. Plantillas finitas:

1. si hay vencidos: “<N> pasos vencidos requieren seguimiento; <M> fechas pasadas siguen sin
   avance registrado”;
2. si no hay vencidos y sí hay sin avance: “<M> fechas pasadas necesitan confirmar avance”;
3. si hay próximos: “<N> pasos entran en ventana en las próximas seis semanas”;
4. si faltan fechas: “<N> paquetes esperan fechas del plan de compras”;
5. si todo está cubierto: “No hay alertas de contratación al corte de hoy”;
6. sin denominador: “No hay base suficiente para evaluar el plan de compras”.

El titular nunca dice “todos listos” basándose en campos ausentes y no afirma causalidad o impacto
sobre obra sin evidencia.

### Lista urgente

La lista inicial incluye, en este orden:

1. `without_date`;
2. `overdue`;
3. `unrecorded_progress`;
4. `week1`;
5. `week2`;
6. `week3`;
7. `week6`.

`ahead` no ocupa la lista inicial, pero queda en conteos y puede pedirse con filtro.

Desempate:

1. fecha planeada ascendente, null primero solo dentro de `without_date`;
2. obra;
3. destino;
4. orden de paso;
5. clave estable.

Cada fila contiene:

- clave estable;
- proyecto y obra;
- paquete y lote;
- paso, clave y orden;
- fecha planeada y `daysDelta`;
- `scheduleBucket` y `decisionState`;
- responsable;
- duración, fuente y provisionalidad;
- evidencia de avance del destino;
- `href` a seguimiento si está autorizado;
- ninguna información de proveedor.

### Breakdown

Se entregan listas ordenadas por:

- horizonte;
- estado de decisión;
- paso;
- responsable;
- proyecto.

Cada elemento tiene conteo y, donde aplica, confirmado/sin avance/sin fecha. Un breakdown no mezcla
conteo de destinos con conteo de pasos sin declarar `unit`.

El gráfico principal usa SVG nativo y una tabla de datos visible. La escala incluye `ahead` aunque
la lista urgente no lo muestre.

### Filtros y búsqueda

Query canónica del detalle:

| Parámetro | Regla |
|---|---|
| `projects` | scope autorizado de T03 |
| `q` | 0–100 caracteres, paquete/lote/paso/responsable/obra |
| `status` | lista cerrada de estados de decisión |
| `step` | clave exacta del catálogo servido |
| `responsible` | ID autorizado o `unassigned` |
| `project` | uno de los proyectos del scope ya autorizado |
| `limit` | entero 1–100, default 25 |
| `offset` | entero >=0, default 0 |
| `include_summary` | boolean, default false |

Reglas:

- valores desconocidos devuelven 422 con error por campo;
- la búsqueda se normaliza para comparación, pero el texto mostrado conserva caracteres;
- el cliente espera 250 ms antes de pedir por búsqueda;
- cambiar filtro reinicia offset;
- el catálogo de filtros sale del scope completo, no del resultado filtrado;
- main y detail comparten exactamente la misma semántica;
- cobertura y contrapeso global permanecen de scope y declaran su aplicabilidad;
- lista, breakdown por paso/responsable/estado y conteo filtrado sí obedecen filtros;
- la URL conserva filtros y se puede compartir sin contener IDs no autorizados.

## Contratos HTTP objetivo

### GET /api/bi/report/pdc

No se añade endpoint. El éxito canónico es:

```json
{
  "ok": true,
  "data": {
    "reportKey": "pdc",
    "scope": {},
    "cutoff": {
      "mode": "today",
      "date": "2026-08-31",
      "timezone": "America/Bogota",
      "weekApplicable": false
    },
    "capabilities": {},
    "hrefs": {},
    "headline": {},
    "coverage": {
      "count": {},
      "value": {},
      "withoutResponsibleDestinations": 0,
      "provisionalDurationDestinations": 0,
      "byProject": []
    },
    "execution": {
      "plannedSteps": 0,
      "closedWithActual": 0,
      "pendingSteps": 0,
      "closurePercent": null,
      "overdueConfirmed": 0,
      "pastDueWithoutProgress": 0,
      "week1": 0,
      "week2": 0,
      "week3": 0,
      "week6": 0,
      "ahead": 0,
      "withoutDate": 0
    },
    "planningGaps": {},
    "horizon": [],
    "urgentSteps": {
      "items": [],
      "pagination": {}
    },
    "byStep": [],
    "byResponsible": [],
    "byProject": [],
    "lineage": [],
    "limitations": [],
    "distributionSignals": []
  },
  "meta": {}
}
```

El main incluye la primera página urgente para evitar un waterfall inicial. El cliente no llama
detail hasta paginar, buscar, filtrar o refrescar el drawer.

El presenter de compatibilidad puede conservar las claves legacy mientras `bi-spa.js` exista, pero
React consume solo el contrato canónico. Los dos presenters derivan del mismo read model y corte.

### GET /api/bi/report/pdc/detail

Éxito:

```json
{
  "ok": true,
  "data": {
    "reportKey": "pdc-detail",
    "scope": {},
    "cutoff": {},
    "query": {},
    "items": [],
    "summary": {},
    "filterOptions": {},
    "pagination": {
      "limit": 25,
      "offset": 0,
      "total": 0,
      "returnedCount": 0,
      "nextOffset": 0,
      "hasMore": false
    },
    "limitations": []
  },
  "meta": {}
}
```

`summary` solo se calcula si `include_summary=true`. La primera página del main y el detail sin
filtros deben tener las mismas claves y orden para el mismo corte.

Si main y detail cruzan el cambio de fecha de Bogotá:

- detail devuelve su propio corte;
- el cliente detecta fecha distinta;
- descarta la combinación;
- refresca main una vez;
- no mezcla conteos de dos días.

### Errores

Todos los errores usan el envelope T01/T03:

| HTTP | code | Caso |
|---:|---|---|
| 401 | `AUTH_REQUIRED` | sin sesión |
| 403 | `PROJECT_SCOPE_FORBIDDEN` | proyecto no autorizado |
| 404 | `NOT_FOUND` | hoja oculta por rol/flag |
| 409 | `SNAPSHOT_CHANGED` | consistencia no recuperable |
| 422 | `VALIDATION_ERROR` | filtro, paginación o fecha cliente inválida |
| 500 | `PDC_REPORT_UNAVAILABLE` | fallo interno |
| 503 | `PDC_SOURCE_UNAVAILABLE` | fuente temporalmente no disponible |

Ningún error revela SQL, tabla, prefijo, IDs de otro proyecto, rol interno o stack. El frontend
parsea incluso el error con Zod.

## Arquitectura backend

### BiPdcReadService

Un nuevo `BiPdcReadService` orquesta:

1. política por hoja;
2. scope autorizado;
3. `PdcCutoff` único;
4. query validada;
5. lector de cobertura;
6. lector de pasos y progreso;
7. lector de brechas de planificación;
8. proyección de estados;
9. agregaciones;
10. titular;
11. acciones/hrefs;
12. linaje;
13. señales puras;
14. presenter canónico y compatibilidad.

No hereda semántica de `StorytellingService::briefPDC()` ni
`ActionRecommendationService::actionsFromPDC()`. Esos métodos dejan de gobernar PDC cuando el
nuevo servicio entra.

### Lectores y reutilización

`PdcPlanningReader` abstrae datos. La implementación de base:

- usa `MultiProjectScope`/`queryForProjects`;
- conserva consultas preparadas;
- lee PDC v2 únicamente;
- evita N+1;
- no arma nombres de tablas desde input;
- agrupa una vez por destino;
- devuelve magnitudes crudas;
- no ejecuta mutaciones.

`SeguimientoService::clasificarVencimiento()` se reutiliza o se delega a una política pura extraída,
para que S12 y S22 no diverjan.

`paquetesSinFechas()` se generaliza mediante una lectura multiproyecto compartida, sin copiar SQL en
BI.

### Coherencia del snapshot

No se exige transacción de escritura. La coherencia se obtiene con:

- un único corte inyectado;
- una sola lectura base de pasos para detalle/agregados;
- una sola lectura de cobertura cruda;
- identificador de snapshot basado en corte/scope/versión de lectura;
- presenter puro;
- 409 tipado si una fuente declara versión incompatible.

No se persiste snapshot.

### Autoría de decisiones

El servidor autoriza:

- `decisionState`;
- orden;
- suficiencia;
- titular;
- limitaciones;
- capacidades;
- hrefs;
- señales elegibles.

React no reconstruye permisos, estados de negocio, porcentajes ni clasificación temporal.

### Señal de distribución

S22 proyecta, sin persistir ni enviar:

- `kind: "pdc_step_enters_expiry_window"`;
- clave estable de proyecto/destino/paso;
- `windowEnteredOn = plannedEnd - 42 días`;
- certeza factual: “entra en ventana planeada”;
- corte;
- acción/href a S12;
- destinatario lógico `purchases`, sin resolver persona/correo;
- `eligible` solo para paso pendiente con fecha válida;
- marcador de calibración como `unavailable` hasta tener historia.

T03 es dueño de transición, deduplicación, agrupación diaria, destinatarios, canal y marcador
histórico. Cargar o refrescar la página no dispara el evento.

### Linaje

`pdc_at_risk` declara:

- definición corregida con `overdue` y `unrecorded_progress` separados;
- fuente PDC v2;
- grano paso-destino;
- corte hoy Bogotá;
- denominador y exclusiones;
- cobertura;
- limitaciones;
- `supportsDateRange=false`.

`compras.duracion_real_paso` declara:

- estado descriptivo;
- muestra cerrada por tipo/paso;
- mínimo 20;
- sin publicación si es insuficiente;
- duración planeada no sustituye duración real.

No se exponen nombres SQL al usuario final; el drawer técnico puede mostrarlos solo donde el
contrato T03 lo permita.

## Arquitectura frontend

### Estructura

`frontend/src/modulos/bi/pdc/` contiene la UI y su estado; los schemas y gateways viven en
`frontend/src/lib/api/` siguiendo el corte ya fijado por S17–S21. En conjunto contienen:

- schemas/tipos derivados de Zod;
- gateway que usa `cliente.ts`;
- parser/serializer de query;
- controlador de estado;
- página;
- titular;
- cobertura;
- contrapeso;
- escala;
- lista/tabla/tarjetas;
- filtros;
- breakdown;
- drawer;
- estados;
- pruebas.

No contiene `fetch`, clase de API genérica paralela, permisos por letra de rol ni copia de tokens.

### Máquina de estados

Estados remotos:

- `idle`;
- `loading`;
- `ready`;
- `refreshing`;
- `partial`;
- `empty`;
- `insufficient`;
- `offline`;
- `invalid-contract`;
- `forbidden`;
- `not-found`;
- `error`.

Reglas:

- primera carga muestra skeleton con geometría estable;
- refresh conserva datos y marca antigüedad;
- error de detail no borra main;
- error de un bloque compatible produce `partial` con reintento local;
- contrato Zod inválido no renderiza cifras parciales;
- offline conserva último snapshot de la misma identidad, marcado;
- cache nunca cruza usuario, scope, fecha ni filtros;
- requests anteriores se abortan y respuestas tardías se ignoran;
- un 404 no deja nav ni contenido fantasma.

### Layout y responsive

Orden de decisión:

1. titular y corte;
2. alarmas sin fecha/sin responsable;
3. lista urgente;
4. cobertura;
5. contrapeso real/planeado;
6. escala temporal;
7. breakdown;
8. linaje y limitaciones.

Breakpoints:

- `<768px`: tarjetas únicamente;
- `>=768px`: tabla semántica únicamente;
- no se montan ambas representaciones;
- 390×844 y 480×900 priorizan estado, destino, responsable, fecha/días y acción;
- 768×1024 permite tabla condensada y drawer overlay;
- 1180×820 es desktop canónico;
- 1440×900 no estira líneas ni gráficos sin límite.

La tabla desktop/tablet conserva columnas mínimas:

- obra;
- destino;
- paso;
- estado;
- fecha planeada;
- días;
- responsable;
- duración;
- acción.

En móvil cada tarjeta conserva exactamente la misma información y la acción no depende de swipe.

### Visualización

- SVG nativo con etiquetas de texto;
- tabla de datos visible asociada;
- patrones/íconos/texto además de color;
- cero canvas;
- cero tooltip como único acceso;
- ejes y leyenda con unidades;
- estados separados visual y semánticamente;
- “sin avance” no usa el mismo nombre/acento que “vencido”;
- valores null dicen “sin dato” o “insuficiente”, nunca cero.

### Drawer contextual

Un único drawer T03:

- abre desde KPI, barra, breakdown o fila;
- modo lista filtrada o detalle de fila;
- conserva botón volver;
- muestra evidencia que justificó el estado;
- muestra corte, fuente, provisionalidad y limitaciones;
- ofrece href operativo si está autorizado;
- foco inicial, trap, Escape y retorno;
- URL con `focus` estable;
- no abre un segundo drawer encima;
- no ejecuta mutaciones.

### Acciones y navegación

El servidor puede entregar:

- `planHref` a `/plan-compras/ensamble/plan`;
- `trackingHref` a `/plan-compras/seguimiento/avance`;
- `rowHref` al seguimiento con contexto solo si S12 lo soporta canónicamente;
- null si la capacidad/ruta no aplica.

S22 no inventa query de deep link a nivel fila si S12 aún no la definió. En ese caso usa la ruta alta
y el drawer conserva la identificación para que la persona la encuentre.

Los filtros que viajan a S12 se revalidan allí. Un href nunca sustituye autorización.

## Estados de producto

### Carga

- skeleton para titular, alarmas, lista y cobertura;
- `aria-busy=true`;
- texto “Cargando plan de compras al corte de hoy”;
- sin cifras `--` interpretables como cero.

### Vacío accionable

Casos:

1. no hay pasos y hay paquetes sin fechas: mensaje de amarrar/recalcular con href;
2. no hay pasos ni paquetes contratables: “No hay destinos contratables en el alcance”;
3. filtro sin resultados: conserva resumen y ofrece limpiar filtros;
4. no hay alertas pero sí plan: confirma corte y muestra próximos/adelante;
5. cobertura sin denominador: insuficiente, no 0 %.

### Parcial e insuficiente

Cada bloque declara:

- qué falta;
- qué cifras siguen válidas;
- qué acción corrige la fuente;
- cuándo se actualizó;
- reintento local.

Falta de responsable o fecha es dato de decisión, no error técnico. Fuente caída o contrato inválido
sí es error técnico.

### Recarga y antigüedad

- botón Recargar accesible;
- conserva filtros/focus;
- anuncia actualización por live region;
- no duplica requests;
- muestra `generatedAt`;
- al cambiar día, invalida cache anterior;
- no refresca automáticamente en loop.

## Tema y design system

- usa `public/css/tokens.css`;
- oscuro es default/fallback;
- claro es equivalente funcional;
- sin hex/rgb/hsl en módulo;
- sin `!important`;
- sin estilos inline de color;
- sin familia local de sombras, radios o espacios;
- estados usan tokens semánticos;
- focus visible en ambos temas;
- densidad de tabla usa escala canónica;
- alto táctil mínimo 44 px;
- reduced motion elimina transiciones no esenciales.

La sidebar pertenece a T01. S22 aporta su metadato:

- etiqueta “Plan de Compras”;
- grupo/lienzo Obra;
- ruta `/bi/pdc`;
- visible para A al elegir Obra y para D/R cuando su flag y gate de hoja están abiertos;
- activa por coincidencia exacta;
- no aparece en el lienzo Gerencia ni a OT.

## Accesibilidad

- un `h1` único y jerarquía de headings;
- landmarks `main/nav/aside` heredados;
- tabla con caption, scope y encabezados reales;
- tarjetas como lista semántica;
- filtros con label persistente;
- errores ligados por `aria-describedby`;
- conteos anunciados sin ruido;
- drawer con nombre y foco;
- gráficos con título, descripción y tabla;
- leyenda textual;
- no depende de color, hover, drag o precisión fina;
- truncamiento visual preserva nombre completo accesible;
- zoom 200 % sin scroll horizontal de página;
- contraste AA en claro/oscuro;
- axe serious/critical cero.

## Seguridad, privacidad y RLS

- página y ambos API pasan la misma `BiSheetAccessPolicy`;
- A/D/R únicamente para esta hoja;
- A no depende del flag BI;
- flag BI gobierna a D/R;
- cada proyecto exige membresía y `lps.indicadores.ver`;
- scope sale de `BiProjectScope`;
- IDs cliente nunca conceden acceso;
- proyectos no autorizados fallan antes de leer;
- filas y agregados siempre se califican por `project_id`;
- multi conserva identidad de obra;
- detalle no expone proyectos descartados;
- proveedor y datos comerciales no salen;
- capacidades/hrefs nacen en servidor;
- errores no filtran estructura interna;
- logs no incluyen payload completo;
- cache se particiona por usuario/scope/corte;
- no se cambia RLS, grants, usuarios ni runtime boundary;
- no se ejecuta DDL/DML;
- no se toca `/admin/`.

## Coexistencia, corte y rollback

### Coexistencia

Mientras legacy viva:

- `BiPdcReadService` es dueño del modelo canónico;
- presenter React produce `ok/data/meta`;
- presenter legacy adapta scorecard/charts/`pdc_breakdown`;
- main/detail comparten corte y semántica;
- `bi-spa.js` no recibe datos PDC v1;
- la ruta se corta a React solo tras contratos y navegador en verde.

### Corte

Orden:

1. backend canónico y compatibilidad;
2. contrato PHP;
3. Zod/gateway;
4. estado y query;
5. núcleo visible;
6. detail/drawer;
7. responsive/a11y/tema;
8. corte de página;
9. censo de callers;
10. retiro diferido del bloque legacy cuando T03 lo autorice.

### Rollback

Rollback:

- devuelve `/bi/pdc` al layout legacy;
- conserva GETs y presenter legacy;
- no restaura tablas ni datos;
- no deshace PDC v2;
- no amplía permisos;
- no borra evidencia.

## Estrategia de pruebas

### PHP puras y contratos

Crear pruebas con lectores fake y fixtures sintéticos para:

- gate por hoja A/D/R, A sin flag y denegación OT/otros;
- scope simple/multi y proyecto ajeno;
- corte único Bogotá;
- clasificación de bordes 0/6/7/13/14/20/21/41/42;
- vencido vs sin avance por destino;
- aislamiento entre lotes;
- null/fecha inválida;
- cobertura cruda y multi;
- denominador cero;
- cierres reales/planeados;
- sin responsable por destino;
- duración provisional;
- paquetes sin fechas;
- titular;
- orden estable;
- filtros y paginación;
- main/detail;
- errores;
- hrefs/capacidades;
- señal pura;
- linaje y umbral 20;
- exclusión de proveedor;
- compatibilidad legacy;
- ausencia de DML.

No ejecutar como evidencia de S22 `tests/test_pdc_v2_torre_control.php`: crea y elimina fixtures en
base. Tampoco usar una prueba que dependa de datos reales para afirmar contrato. La cobertura nueva
debe ser pura, fakeable y repetible.

### Frontend

Vitest/Testing Library:

- schemas success/error;
- gateway solo por `cliente.ts`;
- query;
- controlador de aborto/cache;
- estados;
- titular/corte;
- cobertura;
- contrapeso;
- lista/tabla/tarjetas;
- filtros;
- drawer;
- href nulo/autorizado;
- claro/oscuro;
- teclado y live regions;
- sin render dual.

### Navegador interceptado

Playwright sin backend real:

- A/D/R cargan, filtran, buscan, paginan y abren drawer;
- A conserva acceso con el flag apagado; OT y demás roles reciben 404;
- scope ajeno 403;
- vacío accionable;
- vencido separado de sin avance;
- móvil con tarjetas;
- tablet/desktop con tabla;
- oscuro/claro;
- refresh/offline/partial/invalid;
- cambio de corte main/detail;
- sin request inesperado;
- cero mutaciones;
- consola limpia;
- axe serio/crítico cero.

Viewports:

- 390×844;
- 480×900;
- 768×1024;
- 1180×820;
- 1440×900;
- 1180×820 al 200 %.

No regenerar goldens.

## Criterios de aceptación

### Acceso y alcance

1. **S22-AC-001:** `/bi/pdc` sirve la SPA, no el layout legacy, tras el gate de corte.
2. **S22-AC-002:** D autorizado puede abrir página y API con flag activo.
3. **S22-AC-003:** R autorizado puede abrir página y API con flag activo.
4. **S22-AC-004:** A puede abrir página, main y detail al elegir Obra y no depende del flag.
5. **S22-AC-005:** OT recibe 404 en BI y conserva acceso operativo según S12.
6. **S22-AC-006:** otros roles reciben 404 sin payload sensible.
7. **S22-AC-007:** flag apagado oculta página y API a D/R, pero no a A.
8. **S22-AC-008:** proyecto fuera del scope devuelve 403.
9. **S22-AC-009:** scope por defecto es el proyecto activo autorizado.
10. **S22-AC-010:** multi-proyecto es explícito y conserva obra en cada fila.
11. **S22-AC-011:** página, main y detail usan la misma política por hoja.
12. **S22-AC-012:** no cambia catálogo RBAC, capacidades, RLS ni grants.

### Corte y contratos

13. **S22-AC-013:** un único corte Bogotá gobierna todo el main.
14. **S22-AC-014:** la UI declara fecha y que semana no aplica.
15. **S22-AC-015:** query de fecha histórica cliente devuelve 422.
16. **S22-AC-016:** `semana` legacy se ignora y declara.
17. **S22-AC-017:** main usa `ok/data/meta` y pasa contrato PHP.
18. **S22-AC-018:** detail usa `ok/data/meta` y pasa contrato PHP.
19. **S22-AC-019:** errores 401/403/404/409/422/500/503 son tipados.
20. **S22-AC-020:** Zod valida success y error antes de renderizar.
21. **S22-AC-021:** main incluye primera página urgente sin waterfall.
22. **S22-AC-022:** detail pagina con limit/offset/total/hasMore.
23. **S22-AC-023:** main/detail comparten claves y orden para el mismo corte.
24. **S22-AC-024:** un cambio de día entre main/detail fuerza una recarga coherente.

### Cobertura y planificación

25. **S22-AC-025:** cobertura de conteo expone numerador y denominador.
26. **S22-AC-026:** cobertura de valor expone numerador y denominador.
27. **S22-AC-027:** multi suma magnitudes crudas.
28. **S22-AC-028:** ningún porcentaje de obra se promedia.
29. **S22-AC-029:** denominador cero produce null/insufficient.
30. **S22-AC-030:** incoherencia de numerador/denominador produce invalid.
31. **S22-AC-031:** filtros de lista no alteran cobertura y la limitación es visible.
32. **S22-AC-032:** cobertura por proyecto conserva magnitudes crudas.
33. **S22-AC-033:** paquetes esperando fechas se distinguen de cronograma obsoleto.
34. **S22-AC-034:** sin amarre se distingue de pendiente de recalcular.
35. **S22-AC-035:** pasos pendientes sin fecha se cuentan aparte.
36. **S22-AC-036:** un vacío con paquetes pendientes nombra amarrar/recalcular y enlaza S12.

### Progreso y estados

37. **S22-AC-037:** planeados, cerrados reales y pendientes son visibles juntos.
38. **S22-AC-038:** cierre real se cuenta por `fecha_real` válida.
39. **S22-AC-039:** avance se evalúa por destino, incluido lote.
40. **S22-AC-040:** vencido exige fecha pasada, pendiente y avance real previo del destino.
41. **S22-AC-041:** fecha pasada sin avance produce `unrecorded_progress`.
42. **S22-AC-042:** la UI nunca llama vencido a `unrecorded_progress`.
43. **S22-AC-043:** límites temporales 0/6/7/13/14/20/21/41/42 son exactos.
44. **S22-AC-044:** sin fecha es alarma independiente.
45. **S22-AC-045:** fecha inválida no se normaliza silenciosamente.
46. **S22-AC-046:** `daysDelta` conserva positivo/cero/negativo/null.
47. **S22-AC-047:** horizonte incluye ahead aunque lista inicial lo omita.
48. **S22-AC-048:** orden urgente sigue la política aprobada.
49. **S22-AC-049:** clave estable no depende de texto mutable.
50. **S22-AC-050:** un paso cerrado sale de lista pero entra al contrapeso.
51. **S22-AC-051:** titular factual usa estados canónicos.
52. **S22-AC-052:** titular no usa campos ni afirmaciones de PDC v1.

### Responsables, duración y privacidad

53. **S22-AC-053:** sin responsable se cuenta por destino, no por paso.
54. **S22-AC-054:** “Sin responsable” no se reemplaza por un dueño inventado.
55. **S22-AC-055:** fila muestra responsable o ausencia explícita.
56. **S22-AC-056:** duración se rotula planeada.
57. **S22-AC-057:** mediana corporativa se marca provisional.
58. **S22-AC-058:** duración ausente permanece sin dato.
59. **S22-AC-059:** `compras.duracion_real_paso` es descriptiva y exige 20 cierres.
60. **S22-AC-060:** métrica insuficiente no se publica como comparación.
61. **S22-AC-061:** proveedor/contacto/precio/valor por destino no cruzan HTTP.
62. **S22-AC-062:** cobertura de valor agregada no permite reconstruir valores por fila.
63. **S22-AC-063:** nombres completos son accesibles sin ser claves.
64. **S22-AC-064:** multi no mezcla responsable ni destino entre proyectos.

### Filtros, detalle y drawer

65. **S22-AC-065:** búsqueda cubre obra/destino/paso/responsable.
66. **S22-AC-066:** búsqueda mayor a 100 caracteres devuelve 422.
67. **S22-AC-067:** estado acepta solo catálogo cerrado.
68. **S22-AC-068:** paso acepta solo clave servida.
69. **S22-AC-069:** responsable acepta ID autorizado o unassigned.
70. **S22-AC-070:** proyecto filtrado pertenece al scope.
71. **S22-AC-071:** limit fuera de 1–100 devuelve 422.
72. **S22-AC-072:** offset negativo devuelve 422.
73. **S22-AC-073:** cambiar filtro reinicia offset.
74. **S22-AC-074:** catálogo de filtros no colapsa por selección.
75. **S22-AC-075:** URL conserva filtros/focus autorizados.
76. **S22-AC-076:** drawer abre desde KPI, escala, breakdown y fila.
77. **S22-AC-077:** drawer lista y detalle comparten una sola instancia.
78. **S22-AC-078:** drawer conserva foco, Escape y retorno.
79. **S22-AC-079:** drawer muestra evidencia, corte, fuente y limitación.
80. **S22-AC-080:** href operativo es servidor-autorizado o null.
81. **S22-AC-081:** S22 no inventa un deep link fila no soportado por S12.
82. **S22-AC-082:** ninguna acción del drawer muta datos.

### Breakdown, señales y linaje

83. **S22-AC-083:** breakdown de horizonte declara unidad paso.
84. **S22-AC-084:** breakdown de responsable separa sin responsable.
85. **S22-AC-085:** breakdown por paso separa vencido/sin avance/pendiente.
86. **S22-AC-086:** breakdown por proyecto conserva cobertura y estados.
87. **S22-AC-087:** SVG tiene alternativa tabular visible.
88. **S22-AC-088:** visual no depende de canvas, hover ni color.
89. **S22-AC-089:** señal de entrada a ventana es pura y estable.
90. **S22-AC-090:** abrir/recargar la página no envía ni persiste señal.
91. **S22-AC-091:** señal apunta a S12, no exige acceso BI a OT.
92. **S22-AC-092:** T03 conserva destinatarios, dedupe, canal y agrupación diaria.
93. **S22-AC-093:** `pdc_at_risk` declara corte hoy y sin rango histórico.
94. **S22-AC-094:** linaje separa vencido confirmado de sin avance.
95. **S22-AC-095:** linaje declara denominador, cobertura y exclusiones.
96. **S22-AC-096:** limitaciones aparecen junto al bloque afectado.

### Estado, responsive, tema y accesibilidad

97. **S22-AC-097:** loading usa skeleton estable y `aria-busy`.
98. **S22-AC-098:** refreshing conserva datos y marca antigüedad.
99. **S22-AC-099:** error detail no borra main.
100. **S22-AC-100:** partial identifica bloque y reintento.
101. **S22-AC-101:** invalid-contract no renderiza cifras parciales.
102. **S22-AC-102:** offline no cruza identidad de cache.
103. **S22-AC-103:** respuesta tardía no pisa query vigente.
104. **S22-AC-104:** filtro vacío conserva resumen y limpiar filtros.
105. **S22-AC-105:** bajo 768 px se montan solo tarjetas.
106. **S22-AC-106:** desde 768 px se monta solo tabla semántica.
107. **S22-AC-107:** móvil conserva estado/destino/responsable/fecha/días/acción.
108. **S22-AC-108:** cinco viewports no tienen overflow de página.
109. **S22-AC-109:** zoom 200 % conserva lectura y operación.
110. **S22-AC-110:** oscuro es default y claro es equivalente.
111. **S22-AC-111:** módulo usa tokens sin colores literales ni `!important`.
112. **S22-AC-112:** touch targets son mínimo 44 px.
113. **S22-AC-113:** reduced motion elimina movimiento no esencial.
114. **S22-AC-114:** teclado cubre filtros, tabla/tarjetas, gráfico y drawer.
115. **S22-AC-115:** lector recibe encabezados, conteos, estados y anuncios.
116. **S22-AC-116:** axe serious/critical es cero.

### Integridad, coexistencia y cierre

117. **S22-AC-117:** solo `cliente.ts` llama fetch en frontend.
118. **S22-AC-118:** no existe request inesperado ni mutación en navegador.
119. **S22-AC-119:** consultas usan scope y parámetros preparados.
120. **S22-AC-120:** detail no filtra proyectos descartados.
121. **S22-AC-121:** presenter legacy y React derivan del mismo read model.
122. **S22-AC-122:** PDC v1 no se consulta ni reintroduce.
123. **S22-AC-123:** tests nuevos son puros y no escriben base.
124. **S22-AC-124:** `/admin/` permanece intacto.
125. **S22-AC-125:** sidebar muestra PDC en Obra para A/D/R, con flag aplicable solo a D/R.
126. **S22-AC-126:** consola de navegador queda limpia.
127. **S22-AC-127:** censo confirma callers antes de retirar legacy.
128. **S22-AC-128:** rollback cambia rutas/código y nunca datos.
129. **S22-AC-129:** diff no incluye RLS, grants, schema, datos ni credenciales.
130. **S22-AC-130:** no hay commit, push, PR o deploy sin autorización y gate aplicable.

## Entregas verticales

1. **Lectura canónica:** cutoff, scope, cobertura cruda, pasos, progreso, estados y contrato.
2. **Núcleo visible:** titular, alarmas, lista urgente, cobertura y contrapeso.
3. **Exploración:** filtros, detail paginado, breakdown y drawer.
4. **Acción segura:** hrefs, vacíos accionables, linaje y señal pura.
5. **Calidad de superficie:** responsive, oscuro/claro, accesibilidad y estados.
6. **Corte:** compatibilidad, route handoff, caller census y rollback.

Cada entrega deja una decisión visible y verificable. Ninguna requiere mutación.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Llamar vencido a calendario sin evidencia | `decisionState` separado de `scheduleBucket` |
| Promediar porcentajes de cobertura | magnitudes crudas y prueba multi |
| Doble corte por medianoche | `PdcCutoff` único e inyectado |
| OT excluido de BI | acción/señal directa a S12, sin ampliar permiso |
| A queda oculto si navega en Gerencia | manifiesto T03 lo muestra al elegir Obra, sin aplicar flag |
| Sin responsable multiplicado por pasos | conteo por destino |
| Provisional leído como predicción | rotulado planeado/provisional |
| Payload comercial sensible | agregados de valor; detalle sin proveedor/valor |
| Filtros alteran KPI global | aplicabilidad explícita |
| Main/detail divergen | row schema/proyector compartido y prueba de corte |
| Pruebas escriben datos | lectores fake, fixtures sintéticos, suite DML excluida |
| Legacy consume shape anterior | presenter de compatibilidad temporal |
| Evento duplicado por refresh | S22 solo proyecta; T03 deduplica |
| Dos UIs operativas | S22 read-only y S12 dueño único de mutaciones |

## Alternativas descartadas

- Dar acceso BI a OT: cambia la política aprobada y requiere autorización separada.
- Negar PDC a A por su lienzo actual: contradice T03, donde el canvas es preferencia y A puede
  elegir Obra.
- Reutilizar `briefPDC()`: depende de campos PDC v1 eliminados.
- Tratar todos los pasados como vencidos: contradice CT-8.6 y la evidencia medida.
- Promediar porcentajes por destinos: matemáticamente incorrecto.
- Llamar “sin mirar” a todas las brechas: es ambiguo.
- Pedir detail inmediatamente: añade waterfall sin valor.
- Cargar todos los pasos sin paginar: no escala.
- Mutar desde drawer: duplica S12.
- Añadir un endpoint React nuevo: los dos GET existentes bastan.
- Mostrar Chart.js: no cumple alternativa accesible ni reduce dependencia legacy.
- Enviar notificación desde el GET: side effect no idempotente y fuera de T03.
- Usar datos reales en tests: frágil y puede escribir.
- Retirar legacy antes del censo: rompe callers compartidos.

## Decisiones pendientes

Ninguna para implementar S22 bajo el alcance aprobado.

Una eventual admisión de OT a BI no es una decisión pendiente de S22: es una ampliación de producto,
RBAC/montaje de lienzos y seguridad que exige autorización separada. La experiencia definida aquí
funciona con A/D/R como lectores en Obra y S12 como destino operativo de Compras.

## Autor revisión

La autorrevisión comprobó:

- rutas y controladores reales;
- ausencia de React actual;
- contrato main y detail;
- no uso del detail en legacy;
- PDC v2 como única fuente;
- campos PDC v1 todavía usados por storytelling/actions;
- diferencia medida 59 vs 6/53;
- cobertura y denominadores;
- grano destino/lote;
- reparto de lienzos T03 y exclusividad gerencial de S17;
- capacidades operativas S12;
- oscuro/claro, responsive, sidebar y accesibilidad;
- RLS/runtime boundary intactos;
- ausencia de DDL/DML;
- 130 criterios observables;
- plan habilitado sin decisión abierta.

El siguiente artefacto obligatorio es
`docs/superpowers/plans/2026-08-30-s22-bi-pdc-react.md` mediante
`superpowers:writing-plans`. No se implementa en esta sesión.
