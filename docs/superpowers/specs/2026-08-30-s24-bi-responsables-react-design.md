---
capa: fuente
tipo: spec
estado: vigente
id: S24
fecha: 2026-08-31
superficie: bi-responsables
rutas:
  - "/bi/responsables"
  - "/api/bi/report/cip"
depende_de: [T01, T03, S13, S17, S20, S21]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [bi, rbac, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, ReportProcessor, MetricDictionaryService, LineageService, StorytellingService, ActionRecommendationService, BiPreviewAccessPolicy, BiProjectScope, RbacCatalog, bi_cip_responsables, cip, programacion_semanal, profesionales, bi_pg_semana, bi_pi_restricciones, VIEW-04/05/06/08, control-tower.php, bi-spa.js, pruebas, respuestas read-only servidas, specs CT-6/CT-8.8/CT-9, reparto de lienzos, S13, S17, S20, S21 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S24 de la hoja BI Responsables a React: alcance personal o de obra aplicado en servidor, poblacion canonica sin depender de cip, alerta factual PAC/criticos con completitud, carga y restricciones como contexto, contrapeso causal, filtros, drawer, responsive y oscuro/claro, sin ranking personal, mutaciones ni cambios RLS/schema/datos."
---

# S24 — Hoja BI Responsables en React

> **Estado:** diseño técnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementación,
> commits, DDL/DML, cambios RLS, cambios de capacidades, correo, deploy, publicación ni trabajo en
> `/admin/`. Su plan se escribe a continuación con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S24 desarrolla CT-8.8 para el lienzo Obra. La hoja existe para preparar una conversación privada de
ayuda: qué persona necesita que le descarguen trabajo o le destraben una restricción. No existe para
calificar personas, construir una liga ni proyectarse en una reunión.

Consume:

- T01 para sesión, proyecto activo, shell, sidebar, temas, route outlet y único cliente HTTP;
- T03 para política por hoja, query canónica, período, estados, drawer y linaje;
- S13 para identidad profesional, cargo y enlace autorizado al maestro, sin copiar sus mutaciones;
- S17 para composición del lienzo Obra y para mantener esta hoja fuera del Resumen Ejecutivo;
- S20 para restricciones abiertas que afectan los compromisos de una persona;
- S21 para población de compromisos, PAC, criticidad, causas, cobertura y comparación semanal.

S24 es read-only. No cambia compromisos, PAC, causas, responsables, restricciones ni profesionales.
Sus acciones son enlaces autorizados a S08/S13/S20/S21 o instrucciones de conversación; nunca son
órdenes automáticas, correos, bloqueos ni mutaciones.

## Resultado buscado

`/bi/responsables` pasa a la SPA principal y:

1. presenta personas reales, no dos KPI dentro de una tabla de personas;
2. responde “quién necesita ayuda” con lenguaje factual y no punitivo;
3. aplica visibilidad personal/de obra en el servidor antes de leer filas;
4. abre al Residente en sus propios compromisos;
5. conserva el interruptor aprobado del Residente hacia toda la obra;
6. abre al Director y Admin A con toda su obra, sin un interruptor redundante;
7. no monta la hoja para roles fuera de A/D/R;
8. deja de depender de que `cip` haya sido poblada por un proceso lateral;
9. usa la población canónica de compromisos de S21;
10. distingue PAC cero de PAC no registrado;
11. activa la señal sólo por PAC comparable menor a 50% o un crítico incumplido observado;
12. nunca llama “cumpliendo” a una semana abierta sin PAC;
13. muestra siempre carga y restricciones abiertas junto a la señal;
14. conserva numerador, denominador, faltantes, corte y cobertura;
15. muestra causas con el contrapeso D32 y declara que el actor de captura no existe en la fuente;
16. evita rankings ordinales y promedios de personas;
17. compara cada persona sólo consigo misma cuando la identidad es confiable;
18. filtra, busca, cuenta y pagina sin romper el alcance personal;
19. abre un drawer contextual sin endpoint por fila;
20. funciona en desktop, tablet y móvil, oscuro y claro, teclado, touch, zoom y lector de pantalla.

## Alcance

### Incluido

- `GET /bi/responsables` como ruta SPA al corte.
- `GET /api/bi/report/cip` estabilizado como único snapshot/paginación canónicos.
- Política por hoja A/D/R y `lps.indicadores.ver` por proyecto; A no depende del flag y D/R sí.
- Un solo proyecto autorizado por snapshot; selector T03 puede cambiar de proyecto.
- Residente en `mine` por defecto y `project` sólo mediante el interruptor aprobado.
- Admin A y Director en `project` por defecto y sin variante de composición.
- Alias legacy `alcance=obra` durante convivencia; query canónica `scope=mine|project`.
- Semana como atajo y rango como autoridad, resueltos con `semanas_activas`.
- Población de compromisos S21: activos/NA, sin TNP, responsable no vacío.
- Identidad profesional S13 cuando el cruce es único; referencia de asignación opaca en los demás
  casos.
- PAC actual con cumplidos, incumplidos, sin registro, porcentaje y completitud.
- Críticos incumplidos observados.
- Carga de compromisos actual, previa y delta, sin umbral inventado de saturación.
- Restricciones no listas que afectan compromisos de la persona, reutilizando S20.
- Causas CNC, cobertura y `recordedBy=unavailable` reutilizando S21.
- Señal `needs_support|clear|insufficient` y evidencia explícita.
- Comparación contra el período previo real sólo con identidad comparable.
- Resumen, conteos, historial corto, lista paginada y detalle embebido.
- Búsqueda, filtros, orden, paginación y focus gobernados por servidor.
- Enlaces a Semanal, Intermedia y Profesionales según capacidades.
- Linaje de `cip_fulfillment_alert`, carga, restricciones y causa.
- Frontera explícita para que S17 no proyecte señales personales en el lienzo Gerencia.
- Tabla desktop/tablet y tarjetas móviles.
- Oscuro/claro, cinco viewports, zoom, reduced motion y accesibilidad.
- Contratos PHP/Zod, políticas puras, reconciliación SELECT-only y navegador interceptado.
- Convivencia, corte, rollback y retiro diferido del bloque legacy.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, `ProjectScope`, grants, usuarios, credenciales, membresías, schema,
  vista SQL, tabla, columna, índice, trigger, datos, seeds o fixtures persistentes.
- Ejecutar DDL/DML, incluso con rollback.
- Modificar `database/bi/006_bi_cip_responsables.sql`.
- Poblar, recalcular, reparar, borrar o sincronizar `cip` al leer la hoja.
- Invocar `ReportProcessor::updateCICProyectos()` desde una lectura, prueba o recarga.
- Cambiar `internal.bi.preview`, el flag, aliases de rol o catálogo de capacidades.
- Dar esta hoja a OT, DCV, V, C, G, SG, S u otros roles fuera de A/D/R.
- Permitir scope multiproyecto en una hoja que nombra personas.
- Inferir equipo por jerarquía inexistente; para R, “equipo” sigue significando sus propios
  compromisos según la decisión del 2026-08-24.
- Mostrar toda la obra cuando no se pudo resolver la identidad propia.
- Aceptar `resp` como forma de escoger otra persona dentro de `mine`.
- Exponer correo, usuario, NIT, teléfono, IDs de membresía o campos de sesión.
- Construir una evaluación laboral, score de desempeño, ranking, percentil o semáforo disciplinario.
- Llamar “culpable”, “falló”, “peor”, “mejor” o equivalentes a una persona.
- Promediar PAC de personas o de proyectos.
- Inventar un umbral de carga alta o saturación.
- Tratar PAC ausente como cero o alerta.
- Tratar causa registrada por el compromiso como si la hubiera registrado su responsable.
- Añadir auditoría histórica de actor causal sin fuente.
- Editar PAC, CNC/CNP, compromiso, responsable, restricción o profesional.
- Enviar correo, campana, recordatorio o notificación.
- Añadir endpoint de detalle, mutación, exportación o descarga.
- Usar canvas, hover-only, color-only o una librería nueva de tabla/gráfica/estado.
- Retirar vistas/scripts BI compartidos antes del gate T03/S17–S24.
- Regenerar goldens o baselines sin aprobación explícita.

## Fuentes y precedencia

1. Código y contratos vigentes del repositorio.
2. CT-6, CT-8.8, CT-9 y reparto de lienzos aprobado.
3. S13, S17, S20, S21 y T03.
4. Esta spec para las decisiones específicas S24.
5. Legacy sólo como caracterización y compatibilidad.

Cuando legacy contradice el producto aprobado, se conserva el dato válido y se corrige la
interpretación. La frase “Todos los responsables están cumpliendo” con cero filas no es paridad.

## Punto de partida medido

### React

- La SPA principal sólo tiene el shell mínimo y las piezas ya migradas del programa.
- No existe página, ruta, schema Zod, gateway, hook, store, componente ni prueba S24.
- `frontend/src/lib/api/cliente.ts` es la única frontera HTTP permitida.
- La isla `ct-app` no contiene Responsables y no es destino de esta migración.

### Rutas y acceso actuales

| Verbo | Ruta | Uso legacy |
|---|---|---|
| GET | `/bi/responsables` | layout BI compartido |
| GET | `/api/bi/report/cip` | brief CIP |
| GET | `/api/bi/filter-options` | opciones globales, incluida persona |
| GET | `/api/bi/lineage` | linaje lazy compartido |

`BiPreviewAccessPolicy` admite A y, por flag, D/R. S17 añade la política por hoja:

| Lienzo | Roles | Responsables |
|---|---|---|
| Gerencia | A | no como entrada de ese lienzo |
| Obra | A, D, R | sí |

El target admite A cuando elige Obra sin depender del flag, admite D/R sujetos al flag y devuelve
404 a los demás roles para página y API. Un proyecto fuera del scope devuelve 403 después del gate
de hoja. No se crea capacidad nueva.

### Alcance personal legacy

`BiViewController::maybeRedirectToOwnScope()`:

- redirige R a `resp=<nombre>` si no hay filtro ni `alcance=obra`;
- resuelve nombre por usuario → email → `profesionales`;
- no redirige A ni D;
- muestra a R el enlace “Ver toda la obra”.

La decisión aprobada aclara que “equipo” para R significa sus compromisos propios. La brecha es de
seguridad: el filtro vive en la redirección de página, pero el endpoint acepta `resp` libre o ningún
`resp`. S24 mueve la regla a la lectura del servidor. La URL describe intención; nunca concede
visibilidad.

### Vista legacy

`view-cip` contiene una tabla con columnas Nombre, Rol, Actividades y Cumplimiento, más el enlace de
alcance para R. No contiene titular, propósito, carga, restricciones, causas, cobertura, detalle,
comparación, búsqueda, paginación, linaje visible, recarga explícita ni tarjetas móviles.

`renderCIP()` recorre `data.scorecard`. Por ello pinta las filas “Responsables evaluados” y “En
alerta cumplimiento” como si fueran personas; Rol y Actividades quedan vacíos. La tabla no consume
filas CIP reales.

El contenedor aplica `overflow-x: hidden`. No existe alternativa móvil; una tabla ancha puede perder
contenido en vez de transformarse en tarjetas.

### Endpoint legacy

`GET /api/bi/report/cip` llama `ControlTowerService::getBrief(..., 'cip', ...)` y devuelve el envelope
compartido con:

- metadata de proyecto, semana, rol y filtros;
- `raw_row_count`;
- brief narrativo;
- dos KPI;
- riesgos genéricos de otro motor;
- hasta tres acciones;
- linaje.

No devuelve personas, carga, restricciones, cargo ni detalle. Con cero filas, el scorecard contiene
ceros y el brief afirma “Todos los responsables están cumpliendo”. Los riesgos pueden seguir
trayendo filas de `bi_riesgos` no asociadas a CIP.

### Fuente `cip` y vista SQL

`bi_cip_responsables` parte de `cip`. La tabla se puebla sólo por `ReportProcessor` y su población
exige PAC ya registrado. Por diseño histórico, una semana abierta sin PAC ni siquiera crea persona.
La vista añade conteos desde `programacion_semanal` y calcula alerta con PAC textual o crítico
incumplido.

Problemas observados:

- la existencia de una persona depende de un proceso lateral con escritura;
- una lectura no puede corregir `cip` sin violar pureza y esta sesión prohíbe datos;
- `CAST('NA' AS DECIMAL)` se interpreta como cero en la condición de alerta;
- una fila sin PAC puede quedar marcada;
- el endpoint no entrega las filas de todos modos;
- el email existe en la vista pero no debe cruzar el contrato S24.

### Lectura read-only de caracterización

Medición SELECT-only del 2026-08-31, sin nombres ni mutaciones:

| Población | Filas/persona-semana | Proyectos | Personas distintas |
|---|---:|---:|---:|
| `programacion_semanal` con responsable | 193 persona-semana | 9 | 29 |
| `cip` | 138 persona-semana | 5 | 20 |
| `bi_cip_responsables` | 138 persona-semana | 5 | — |

En la población canónica activa, no TNP, hay compromisos con responsable en ocho proyectos; `cip`
sólo tiene cinco. En el proyecto 73:

- semana 1: 4 compromisos, 2 personas, 4 PAC registrados, 3 cumplidos y 1 incumplido;
- semana 2: 4 compromisos, 2 personas y 0 PAC registrados;
- `cip` y la vista: 0 filas en ambas semanas;
- brief semana 2: 0 evaluados, 0 alertas, 10 riesgos ajenos y “Todos los responsables están
  cumpliendo”.

En la vista global auditada, 12 de 138 filas tienen PAC no disponible y las 12 quedan marcadas como
alerta. Esto confirma que la vista no puede ser autoridad de la nueva hoja.

### Pruebas existentes

Son útiles y read-only:

- `tests/test_bi_alcance_responsables.php` para el cruce de identidad;
- `tests/test_cip_poblado.php` como diagnóstico histórico, no como gate del target;
- `tests/test_bi_metric_contracts.php` y `tests/test_bi_restriction_thresholds.php`;
- `tests/test_bi_source_reconciliation.php` para invariantes estáticas.

No se ejecutan en S24 pruebas que llaman `ReportProcessor`, siembran, actualizan o restauran
`cip/cic`. El plan inspecciona cada prueba antes de correrla.

## Modelo canónico

### Grano e identidad

El grano primario es:

    projectId + primaryWeek + personRef

`personRef` es server-side y project-qualified:

- `professional:<id>` cuando el nombre asignado cruza de forma única con S13;
- `assignment:<digest>` cuando sólo existe el texto de `Responsable_AIA`;
- nunca es email, usuario ni nombre sin calificar por proyecto.

La fila incluye `identityConfidence=professional|assignment_name|ambiguous`. Sólo
`professional` admite comparación histórica automática. Una ambigüedad no fusiona personas.

### Admisión y alcance en servidor

Orden obligatorio:

1. sesión;
2. `BiSheetAccessPolicy` para S24;
3. flag BI para D/R;
4. un proyecto autorizado con `lps.indicadores.ver`;
5. rol real de membresía en ese proyecto;
6. resolución de `viewerScope`;
7. período y filtros;
8. lectura de personas.

Reglas:

- D sólo usa `project`;
- R usa `mine` por defecto;
- R puede pedir `project` con el interruptor aprobado;
- `scope` no reemplaza ningún gate;
- en `mine`, el servidor resuelve la identidad de sesión y fuerza ese `personRef`;
- si no la resuelve, devuelve `identity_unresolved` sin filas; nunca cae a toda la obra;
- `resp` legacy dentro de `mine` sólo puede coincidir con la identidad propia;
- múltiples proyectos se rechazan para esta hoja.

### Población

Reutiliza el lector S21, no `cip`:

    Responsable_AIA no vacío
    AND Activa IN ('1', 'NA')
    AND Es_TNP = 0
    AND semana dentro del período autorizado

`PAC` puede ser 1, 0 o null. Un responsable con compromisos abiertos y PAC null permanece visible.
Una persona sin compromiso en el período no se inventa desde el maestro.

### Período

- Un snapshot S24 pertenece a un proyecto.
- `semana` es atajo que resuelve inicio/fin reales del proyecto.
- `desde/hasta` manda cuando existe.
- Sin query, T03 selecciona la semana del proyecto que contiene hoy Bogotá.
- `primaryWeek` es el corte más reciente del proyecto dentro del rango.
- La lista y señal principal corresponden a `primaryWeek`.
- `history` usa las semanas autorizadas del rango y una previa real para comparación.
- Cada bloque declara si usa `primaryWeek` o `historyRange`; no mezcla silenciosamente bases.

### Fila de persona

Cada fila contiene:

- `personRef`, nombre visible, cargo o estado de vínculo y confianza de identidad;
- proyecto, semana primaria y fechas de corte;
- `fulfillment` actual;
- `supportSignal`;
- `commitmentLoad`;
- `openRestrictions`;
- `causalContext`;
- comparación previa si existe;
- hrefs autorizados;
- detalle embebido y referencias de linaje.

No contiene email, usuario, membresía, permiso crudo, prefijo ni nombre de tabla.

### PAC y completitud

Para la población de una persona:

- `commitmentCount`: total elegible;
- `fulfilledCount`: PAC=1;
- `missedCount`: PAC=0;
- `unrecordedCount`: PAC null;
- `recordedCount = fulfilled + missed`;
- `pacPercent = fulfilled / recordedCount` sólo cuando es comparable;
- `criticalMissedCount`: crítica con PAC=0.

`pacStatus`:

- `available`: período cerrado y todos los compromisos tienen PAC;
- `partial`: existe PAC pero faltan registros o el período sigue abierto;
- `insufficient`: no hay PAC comparable;
- `not_applicable`: no hay población, caso que produce vacío y no una fila falsa.

Cero cumplidos con registros completos es 0 válido. Cero registros nunca es 0%.

### Señal de apoyo

`cip_fulfillment_alert` conserva la fórmula aprobada, corregida por completitud:

    (PAC comparable < 0.50) OR (criticalMissedCount > 0)

Resultado:

- `needs_support` si hay un crítico incumplido observado, incluso con PAC parcial;
- `needs_support` si PAC completo/comparable es menor a 50%;
- `clear` sólo con PAC comparable >=50% y cero críticos incumplidos;
- `insufficient` cuando no existe evidencia suficiente y no hay crítico incumplido observado.

La UI dice “Señal de apoyo”, “Sin señal observada” o “Datos insuficientes”. No dice “cumple/no
cumple” como juicio personal. La señal no autoriza decisiones laborales y nunca usa causas por sí
solas.

### Carga de compromisos

`commitmentLoad` contiene conteo actual, conteo previo y delta. No etiqueta “alta”, “baja” ni
“saturada” porque no existe umbral aprobado. La carga acompaña la señal como contexto factual.

En multi-semana, la serie muestra un conteo por semana; no suma semanas y lo llama carga actual.

### Restricciones abiertas

S24 adapta el modelo S20/S21 para los compromisos de la persona:

- `openRestrictionCount`: restricciones duras no listas distintas;
- `blockedCommitmentCount`: compromisos distintos afectados;
- tipos y actividades afectadas en el detalle;
- estado de cobertura si no existe un vínculo suficiente.

El join usa proyecto, semana y clave de actividad; nunca sólo nombre o `unique_id` global. Una
restricción asignada a una persona distinta no cambia quién es responsable del compromiso.

### Causas y contrapeso D32

Para incumplimientos de la persona, S24 reutiliza la población causal S21:

- con causa, sin causa y cobertura;
- categoría, subcausa y observación completa en el drawer;
- responsable del compromiso rotulado como tal;
- `recordedBy.status=unavailable`;
- texto: “La fuente actual no registra quién cargó esta causa”.

No hay ranking de personas por causa. Un desglose causal dentro de una persona se ordena para
lectura, pero la pantalla advierte que la causa es autodeclarada y no sostiene por sí sola una
decisión sobre esa persona.

### Comparación

La comparación usa el período real inmediatamente anterior del mismo proyecto y `personRef`:

- PAC sólo si ambos cortes son comparables;
- delta de carga siempre que ambos tengan población;
- delta de restricciones sólo con cobertura comparable;
- no compara identidades `assignment_name` o `ambiguous` automáticamente;
- no compara personas entre sí;
- no fabrica semana N-1 si falta.

### Resumen y titular

El resumen cuenta personas distintas del `primaryWeek`:

- visibles por scope;
- con señal de apoyo;
- sin señal observada;
- con datos insuficientes;
- con críticos incumplidos;
- con restricciones abiertas;
- con PAC pendiente.

No promedia PAC de personas. El titular es una plantilla server-side factual:

- mine: “Tienes N compromisos; M restricciones abiertas requieren contexto”.
- project con señales: “N personas muestran una señal de apoyo; revisar carga y restricciones”.
- project insuficiente: “Falta cerrar PAC para N personas; no se emite evaluación”.
- vacío: “No hay compromisos con responsable en este corte”.

### Lista y orden

Orden canónico:

1. `needs_support` por críticos incumplidos;
2. `needs_support` por PAC comparable;
3. `insufficient` con restricciones abiertas;
4. demás `insufficient`;
5. `clear`;
6. desempate por restricciones, compromisos y nombre normalizado.

No se muestra número de puesto. El orden es cola de conversación, no ranking de desempeño.

### Drawer

El drawer T03 recibe el detalle embebido:

- identidad/cargo y corte;
- PAC con base y faltantes;
- compromisos actuales y previos;
- críticos incumplidos;
- restricciones y actividades afectadas;
- causas y contrapeso;
- limitaciones;
- enlaces autorizados;
- linaje.

No hace GET por persona y no monta formularios.

## Búsqueda, filtros, orden y paginación

Query canónica:

- `project_id` único;
- `semana` o `desde/hasta`;
- `scope=mine|project`;
- `q` por nombre/cargo visible;
- `person_ref` para focus exacto;
- `support=all|needs_support|clear|insufficient`;
- `open_restrictions=all|yes|no`;
- `missing_pac=all|yes|no`;
- `cause_category` gobernada;
- `sort=conversation|name|load|restrictions`;
- `page` y `per_page` acotados;
- `focus` T03.

Aliases legacy: `alcance=obra`, `resp`, `responsable`, `fecha_desde` y `fecha_hasta`. Sólo el
adapter los acepta. No aparecen en enlaces nuevos.

Reglas:

- todo filtro gobierna titular, conteos, lista, historial y breakdown;
- las opciones de filtro declaran `scopePeriod=filter-options`;
- `mine` no devuelve opciones de otras personas;
- `q/person_ref` no amplían scope;
- orden/paginación ocurren tras autorización y filtros;
- `per_page` máximo 100;
- query inválida es 422 tipado, no fallback silencioso.

## Contrato HTTP objetivo

### GET /api/bi/report/cip

Respuesta conceptual:

~~~json
{
  "ok": true,
  "data": {
    "reportKey": "cip",
    "scope": {
      "project": { "id": 73, "name": "Obra" },
      "viewerScope": "mine",
      "canChangeToProject": true,
      "primaryWeek": {},
      "historyRange": {},
      "filters": {},
      "isFiltered": false
    },
    "capabilities": {
      "canView": true,
      "canChangeScope": true
    },
    "purpose": {
      "kind": "support",
      "meetingProjection": false,
      "text": "Ver quién necesita ayuda, no quién falla."
    },
    "coverage": {},
    "headline": {},
    "summary": {},
    "breakdowns": {},
    "people": [],
    "pagination": {},
    "filterOptions": {},
    "limitations": [],
    "lineage": []
  },
  "meta": {
    "requestId": "opaque",
    "generatedAt": "2026-08-31T12:00:00-05:00",
    "schemaVersion": 1
  }
}
~~~

Invariantes:

- no expone rol crudo; capacidades/`viewerScope` bastan;
- no expone nombres de otras personas en `mine`;
- `people.length <= per_page`;
- summary/breakdowns corresponden a la población filtrada completa, no sólo la página;
- cada fila tiene `projectId` y `personRef`;
- `needs_support + clear + insufficient = visiblePeople`;
- PAC muestra base y completitud;
- detalle va embebido;
- compatibility fields legacy, si aún existen, se derivan del mismo modelo.

### Errores

| HTTP | code | Uso |
|---:|---|---|
| 400 | `BAD_REQUEST` | forma HTTP inválida |
| 401 | `UNAUTHENTICATED` | sesión ausente/expirada |
| 403 | `FORBIDDEN` | proyecto fuera del scope |
| 404 | `NOT_FOUND` | hoja oculta por rol/flag |
| 409 | `STALE_CONTEXT` | proyecto activo cambió |
| 422 | `VALIDATION_ERROR` | query o período no válidos |
| 422 | `IDENTITY_UNRESOLVED` | R en mine sin identidad cruzable |
| 429 | `RATE_LIMITED` | si middleware vigente lo produce |
| 500 | `INTERNAL_ERROR` | fallo sin internals |
| 503 | `TEMPORARILY_UNAVAILABLE` | dependencia temporal |

No se devuelven SQL, tablas, prefijos, rutas físicas, emails, sesión ni stack traces.

## Arquitectura backend

### BiResponsablesReadService

Fachada propuesta:

    BiResponsablesReadService
      -> BiSheetAccessPolicy / BiProjectScope
      -> BiResponsablesQueryParser
      -> S13ResponsibleIdentityAdapter
      -> S21ResponsibleCommitmentReader
      -> S20ResponsibleRestrictionAdapter
      -> ResponsibleFulfillmentPolicy
      -> ResponsibleSupportSignalPolicy
      -> ResponsibleCausalCounterweight
      -> ResponsibleComparisonPolicy
      -> ResponsibleSummary / Headline / Order
      -> LineageService
      -> BiResponsablesPresenter

Principios:

- una única lectura canónica produce API y compatibilidad;
- todas las consultas llevan `project_id` y parámetros preparados;
- no hay SQL dinámico por prefijo;
- no hay lectura/escritura de `cip` para construir población;
- scope personal se fuerza antes del reader;
- reloj Bogotá y período son inyectables;
- políticas son puras y probables sin BD;
- ninguna regla de UI reinterpreta alerta, completitud u orden.

### Adaptadores de S13, S20 y S21

S24 no copia contratos:

- S13 resuelve `personRef`, cargo y href de Profesionales;
- S21 entrega compromisos, PAC, críticos, causas y período previo;
- S20 entrega restricciones no listas enlazadas a esos compromisos;
- si una dependencia aún no existe al ejecutar, se implementa primero su plan dueño.

Los adaptadores reciben un scope ya autorizado. No invocan endpoints HTTP internos ni causan
sincronizaciones.

### Gate D61 y catálogo métrico

La hoja no corta a React hasta reemplazar la autoridad rota. El gate es:

1. candidato: agregador S24 sobre el reader canónico;
2. oráculo independiente: reducción PHP de filas canónicas S21;
3. cuatro semanas reales de al menos dos proyectos;
4. igualdad de población, PAC, críticos y alerta;
5. cero lectura/escritura de `cip` en el candidato;
6. discrepancia bloquea el corte.

La vista rota no es el oráculo. Sus diferencias quedan documentadas como defecto histórico. Tras el
gate, `cip_fulfillment_alert` actualiza fuente/estado en `MetricDictionaryService` y el linaje nombra
`programacion_semanal`, S21/S20, fórmula, completitud y limitaciones. No se modifica SQL.

### Frontera con S17

S17 no consume filas, conteos ni alertas personales de S24. El campo legacy
`responsibles_at_risk_count` no se proyecta en el Resumen Ejecutivo canónico. Que A pueda abrir S24
desde Obra no crea una proyección personal ni un drilldown en S17. La integración compartida se
limita a:

- declarar S24 sólo en el lienzo Obra A/D/R;
- conservar su ruta como destino dentro de ese lienzo;
- impedir que nombres, conteos de personas o causas entren al lienzo Gerencia;
- mantener el caller legacy hasta su retiro por censo, sin convertirlo en contrato nuevo.

### Compatibilidad

Mientras legacy siga llamando el endpoint, el presenter puede conservar `respuesta`, `scorecard` y
`executive_brief` como campos aditivos derivados del modelo. No devuelve raw rows de la vista ni
mantiene dos cálculos. Tras el censo de callers T03, se retiran campos legacy.

## Arquitectura frontend

### Estructura propuesta

    frontend/src/lib/api/esquemas/biResponsables.ts
    frontend/src/lib/api/biResponsables.ts
    frontend/src/modulos/bi/ResponsablesPagina.tsx
    frontend/src/modulos/bi/responsables/
      PropositoResponsables.tsx
      SelectorAlcanceResponsables.tsx
      ResumenResponsables.tsx
      FiltrosResponsables.tsx
      ListaResponsables.tsx
      TablaResponsables.tsx
      TarjetasResponsables.tsx
      TarjetaResponsable.tsx
      EstadoApoyo.tsx
      BasePacResponsable.tsx
      ContextoCarga.tsx
      ContextoRestricciones.tsx
      ContextoCausal.tsx
      DetalleResponsable.tsx
      HistorialResponsable.tsx
      estadoResponsables.ts
      queryResponsables.ts
      useBiResponsables.ts

Responsabilidades:

- gateway usa exclusivamente `cliente.ts`;
- Zod valida éxito, errores, unions y paginación;
- tipos salen de `z.infer`;
- hook maneja abort, stale response, refresh y cache key;
- componentes reciben decisiones server-side;
- tabla/tarjetas usan el mismo view model;
- drawer contiene detalle una sola vez;
- no hay `dangerouslySetInnerHTML`.

### Estado remoto

Estados:

- `idle`;
- `loading`;
- `ready`;
- `refreshing`;
- `empty`;
- `partial`;
- `identity_unresolved`;
- `invalid_query`;
- `forbidden_project`;
- `hidden_sheet`;
- `offline`;
- `error`.

Una petición reemplazada se aborta; una respuesta obsoleta no pisa estado. La cache se particiona
por usuario/sesión, proyecto, viewerScope, período, filtros, orden, página y focus.

### Orden del lienzo

1. marco T03 con proyecto/período;
2. propósito privado “ayuda, no falla” y aviso “no proyectar”;
3. selector de alcance sólo para R;
4. titular y conteos;
5. filtros;
6. lista de conversación;
7. limitaciones y linaje.

En móvil, propósito, scope y primera tarjeta caben antes del desplazamiento largo.

### Tabla y tarjetas

A `>=768px` se monta una tabla semántica con:

- persona/cargo;
- señal de apoyo;
- PAC con base;
- compromisos y delta;
- restricciones abiertas;
- críticos incumplidos;
- acción de detalle.

A `<768px` se montan tarjetas editables sólo en el sentido de interacción/filtros; S24 no tiene
campos de datos editables. Cada tarjeta muestra nombre, señal textual, PAC/base, carga,
restricciones, críticos y botón de detalle. Tabla y tarjetas no coexisten ocultas.

### Visualización

PAC/carga pueden usar SVG nativo pequeño, siempre acompañado por texto y numerador/denominador.
No se mezclan escalas. No hay gráfico de ranking de personas ni canvas.

### Acciones

Hrefs server-side o null:

- abrir compromisos filtrados en S21/S08;
- abrir restricciones que afectan a la persona en S20;
- abrir Profesionales S13 cuando el usuario puede;
- volver a `mine` o ampliar a `project` para R.

No se construyen URLs desde IDs sin autorización y no hay botones falsamente habilitados.

## Estados de producto

### Vacíos

- sin compromisos en mine: “No tienes compromisos asignados en este corte”.
- sin compromisos en project: “No hay compromisos con responsable en este corte”.
- filtros sin resultado: conserva filtros, permite limpiar y no afirma salud.
- identidad no resuelta: explica que no se pudo vincular la sesión con Profesionales; no muestra la
  obra.

### Parcial e insuficiente

- PAC parcial muestra registrados/faltantes;
- período abierto muestra “pendiente de cierre”;
- restricciones sin cobertura dicen qué vínculo falta;
- identidad sólo por nombre deshabilita comparación;
- causa sin actor muestra `recordedBy unavailable`;
- una limitación no borra los demás datos válidos.

### Carga y recarga

- skeleton conserva geometría sin nombres ficticios;
- recarga repite sólo el GET, conserva query/foco y aborta la lectura anterior;
- durante refresh se conserva contenido previo y se anuncia actualización;
- offline no borra la última lectura válida;
- 401 ofrece volver a entrar; 404 desmonta la hoja; 403 de proyecto vuelve al selector.

## Tema, sidebar y design system

- S24 usa `public/css/tokens.css` y componentes compartidos.
- Oscuro es default/fallback; claro tiene contraste equivalente.
- Sin hex/rgb/hsl propios, estilos inline de color, `!important`, familia local de tokens o CSS
  global nuevo.
- Sidebar canónica muestra Responsables sólo en lienzo Obra A/D/R; el flag aplica a D/R, no a A.
- Ruta activa, título y breadcrumbs usan el mismo manifiesto T03.
- R ve el control mine/project dentro de la hoja, no una segunda entrada de sidebar.
- A ve y puede abrir S24 al elegir Obra, siempre con alcance project y sin depender del flag.
- No se reintroduce tema linen.

Viewports obligatorios: 390x844, 480x900, 768x1024, 1180x820 y 1440x900, más 200% zoom.

## Accesibilidad

- un `h1` y jerarquía sin saltos;
- propósito/no proyección expuesto a lector de pantalla;
- selector de alcance como grupo con nombre/estado;
- tabla con caption, headers y celdas asociadas;
- tarjetas con encabezado accesible;
- señal no depende de color/icono;
- filtros con label, conteo y botón limpiar;
- cambios de scope/período/filtros anunciados;
- drawer con título, foco inicial, trap, Escape y retorno;
- 44px mínimos en touch;
- foco visible en oscuro/claro;
- texto causal completo accesible por clic, no hover;
- SVG con alternativa textual visible;
- orden DOM útil sin CSS;
- reduced motion;
- sin overflow horizontal a 320px ni 200% zoom;
- axe serious/critical cero en estados principales.

## Seguridad, privacidad y RLS

- No se modifica RLS ni el runtime boundary ya documentado.
- La página y el API usan el mismo `BiSheetAccessPolicy`.
- `MultiProjectScope` sólo se usa para el proyecto único ya autorizado; S24 rechaza más de uno.
- Scope personal se calcula server-side desde sesión y S13.
- Un filtro de nombre nunca concede acceso.
- `mine` no filtra después de traer la obra: el reader recibe sólo `personRef` propio.
- `IDENTITY_UNRESOLVED` es fail-closed.
- Query rechaza `role`, `permiso`, `user`, `username`, `email`, `db`, `prefix`, `capability` o
  `project_ids` múltiples.
- Consultas preparadas y project-qualified.
- Respuestas `Cache-Control: no-store`; cache cliente aislada por sesión/scope.
- Logs usan requestId, projectId y códigos, nunca nombres/emails/causas.
- Errores no filtran internals.
- No hay CSRF porque S24 no muta.
- Tests cubren R mine, R project, A/D project, A sin flag, OT/otros 404 y proyecto ajeno 403.

## Coexistencia, corte y rollback

### Coexistencia

- Legacy permanece activo mientras se implementan políticas, contrato y React.
- El endpoint conserva compatibilidad aditiva desde un solo modelo.
- `bi_cip_responsables` y `cip` permanecen por callers históricos, pero no son autoridad S24.
- No se toca `ReportProcessor`.

### Corte

`/bi/responsables` entra a `SpaRouter` sólo cuando:

1. gate D61 pasa en cuatro semanas/dos proyectos;
2. contrato PHP y Zod coinciden;
3. R mine no filtra datos de otros;
4. R project y A/D project funcionan conforme a la decisión aprobada;
5. estados de PAC abierto/cero/insuficiente están probados;
6. tabla/tarjetas, temas y accesibilidad están verdes;
7. S17 conserva la frontera de lienzo y no proyecta señales personales;
8. censo de callers legacy está registrado.

### Rollback

- devolver sólo la ruta de página al render legacy;
- conservar servicio/endpoint canónicos si callers ya dependen;
- no revertir datos porque no se escribieron;
- verificar que scope y gate siguen cerrados;
- retirar adapter sólo tras cero callers.

## Pruebas

### PHP puras y contratos

- admisión A/D/R, A sin flag, D/R sujetos al flag y OT/otros ocultos;
- parser de scope/query/período;
- identity mine resuelta/no resuelta/ambigua;
- población activa/NA, sin TNP y PAC null visible;
- PAC 0 válido, PAC ausente insuficiente;
- alerta por PAC comparable y por crítico incumplido;
- clear sólo con evidencia completa;
- carga y delta sin umbral;
- restricciones project-qualified;
- contrapeso causal/recordedBy unavailable;
- comparación sólo identidad profesional;
- orden sin ordinal visible;
- summary, pagination y filtros;
- presenter y errores sin sensibles;
- contrato HTTP de `GET /api/bi/report/cip`.

### Reconciliación SELECT-only

- dos proyectos, cuatro semanas reales como mínimo;
- candidato vs oráculo independiente;
- población, PAC, críticos, alerta y conteos exactos;
- `cip` no aparece en el SQL/call log del candidato;
- no INSERT/UPDATE/DELETE/DDL;
- salida sólo agregada, sin nombres ni emails.

### Frontend

- schemas de éxito/error/unions;
- query codec y aliases de compatibilidad;
- abort/stale/cache isolation;
- selector mine/project;
- estados loading/refresh/empty/identity/offline/error;
- filtros/conteos/paginación;
- tabla/tarjetas exclusivas por breakpoint;
- drawer/foco/Escape/retorno;
- lenguaje no punitivo;
- temas/tokens/no colores literales;
- accesibilidad Testing Library + axe.

### Navegador interceptado

- R mine recibe sólo su persona;
- R cambia a toda la obra y vuelve;
- D abre obra sin toggle;
- A abre project sin toggle y sin depender del flag; OT/otros reciben 404;
- proyecto ajeno 403 sin payload;
- semana abierta con PAC null no afirma cumplimiento;
- PAC cero comparable sí puede activar señal;
- crítico incumplido activa señal con PAC parcial;
- filtros gobiernan resumen/lista;
- drawer y enlaces autorizados;
- 390/480/768/1180/1440, oscuro/claro y 200% zoom;
- axe y consola/red sin errores;
- toda petición no esperada o no GET falla la prueba.

No se ejecutan suites que escriben `cip/cic` ni se regeneran goldens.

## Criterios de aceptación

### Acceso, scope y período

1. **S24-AC-001:** `/bi/responsables` y su API usan la misma política de hoja.
2. **S24-AC-002:** sólo A/D/R montan S24.
3. **S24-AC-003:** A abre en project al elegir Obra y no depende del flag.
4. **S24-AC-004:** OT y otros roles reciben 404 sin payload sensible.
5. **S24-AC-005:** flag apagado oculta página y API a D/R, pero no a A.
6. **S24-AC-006:** proyecto no autorizado devuelve 403 antes del reader.
7. **S24-AC-007:** S24 rechaza scope multiproyecto.
8. **S24-AC-008:** A y D usan project scope y no ven toggle redundante.
9. **S24-AC-009:** R abre en mine por defecto.
10. **S24-AC-010:** R puede cambiar explícitamente a project conforme al reparto aprobado.
11. **S24-AC-011:** `scope` nunca reemplaza sesión, rol, flag o membresía.
12. **S24-AC-012:** mine se fuerza en servidor antes de leer compromisos.
13. **S24-AC-013:** identidad propia no resuelta falla cerrada sin datos de obra.
14. **S24-AC-014:** `resp` no permite escoger otra persona dentro de mine.
15. **S24-AC-015:** `alcance=obra` se acepta sólo como alias legacy autorizado.
16. **S24-AC-016:** semana se resuelve con fechas reales de la obra.
17. **S24-AC-017:** rango manda sobre semana y query incompatible es 422.
18. **S24-AC-018:** default usa la semana vigente por fecha Bogotá.
19. **S24-AC-019:** primaryWeek e historyRange se declaran por separado.
20. **S24-AC-020:** autoridad query rechaza role/user/email/db/prefix/capability.

### Población, identidad y contrato de persona

21. **S24-AC-021:** población usa responsable no vacío, Activa 1/NA y Es_TNP=0.
22. **S24-AC-022:** compromiso con PAC null permanece visible.
23. **S24-AC-023:** persona sin compromiso no se inventa desde Profesionales.
24. **S24-AC-024:** S24 no lee `cip` para construir población.
25. **S24-AC-025:** recargar/filtrar/detallar no llama ReportProcessor.
26. **S24-AC-026:** cada fila usa projectId+primaryWeek+personRef.
27. **S24-AC-027:** personRef nunca es email, usuario o nombre global.
28. **S24-AC-028:** cruce único S13 produce identityConfidence professional.
29. **S24-AC-029:** texto sin cruce produce assignment_name sin fusionar proyectos.
30. **S24-AC-030:** ambigüedad no escoge una persona arbitraria.
31. **S24-AC-031:** cargo proviene de S13 o muestra vínculo no disponible.
32. **S24-AC-032:** payload no expone email/usuario/membresía/prefijo/rol crudo.
33. **S24-AC-033:** comparación automática exige identidad professional.
34. **S24-AC-034:** cambio de nombre ambiguo no se interpreta como tendencia.
35. **S24-AC-035:** filterOptions mine no contiene otras personas.
36. **S24-AC-036:** summary usa población filtrada completa, no sólo la página.
37. **S24-AC-037:** detalle viene embebido sin GET por fila.
38. **S24-AC-038:** vínculos a S13/S20/S21 son server-authorized o null.

### PAC, señal y contexto

39. **S24-AC-039:** fulfillment publica commitments/fulfilled/missed/unrecorded.
40. **S24-AC-040:** recorded=fulfilled+missed y reconciliación es exacta.
41. **S24-AC-041:** PAC cero completo es valor válido.
42. **S24-AC-042:** cero PAC registrados produce null/insufficient, no 0%.
43. **S24-AC-043:** PAC parcial muestra base y faltantes.
44. **S24-AC-044:** semana abierta nunca se declara cierre definitivo.
45. **S24-AC-045:** PAC available exige corte cerrado y registros completos.
46. **S24-AC-046:** alerta por PAC exige valor comparable menor a 0.50.
47. **S24-AC-047:** crítico incumplido observado activa needs_support.
48. **S24-AC-048:** PAC ausente sin crítico observado queda insufficient.
49. **S24-AC-049:** clear exige PAC comparable >=0.50 y cero críticos incumplidos.
50. **S24-AC-050:** needs_support/clear/insufficient particionan personas visibles.
51. **S24-AC-051:** copy usa ayuda/señal/evidencia, no juicio personal.
52. **S24-AC-052:** señal no se usa como decisión laboral automática.
53. **S24-AC-053:** carga actual muestra conteo, no etiqueta inventada.
54. **S24-AC-054:** carga previa y delta usan semana previa real.
55. **S24-AC-055:** serie multi-semana no suma y llama total actual.
56. **S24-AC-056:** openRestrictionCount cuenta restricciones duras distintas no listas.
57. **S24-AC-057:** blockedCommitmentCount cuenta compromisos distintos afectados.
58. **S24-AC-058:** join de restricciones usa project+week+activity key.
59. **S24-AC-059:** cobertura insuficiente de restricciones se declara.
60. **S24-AC-060:** carga y restricciones acompañan siempre una señal needs_support.
61. **S24-AC-061:** causalContext cuenta con causa, sin causa y cobertura.
62. **S24-AC-062:** causa/subcausa/observación se recuperan completas.
63. **S24-AC-063:** responsable del compromiso nunca se rotula registrador.
64. **S24-AC-064:** recordedBy unavailable declara ausencia de actor causal.
65. **S24-AC-065:** causa no sostiene sola una decisión sobre la persona.
66. **S24-AC-066:** no existe ranking ordinal de personas.
67. **S24-AC-067:** no existe promedio PAC entre personas.
68. **S24-AC-068:** comparación PAC sólo existe cuando ambos cortes son comparables.

### Resumen, filtros, HTTP y drawer

69. **S24-AC-069:** titular es plantilla server-side factual.
70. **S24-AC-070:** vacío no afirma que todos cumplen.
71. **S24-AC-071:** mine usa copy en segunda persona sin revelar otros.
72. **S24-AC-072:** project cuenta personas distintas, no filas persona-semana.
73. **S24-AC-073:** conteos incluyen needs_support/clear/insufficient.
74. **S24-AC-074:** resumen declara críticos, restricciones y PAC pendiente.
75. **S24-AC-075:** q busca sólo nombre/cargo dentro del scope autorizado.
76. **S24-AC-076:** person_ref enfoca sin ampliar scope.
77. **S24-AC-077:** support filtra lista, resumen, breakdown e historial aplicable.
78. **S24-AC-078:** open_restrictions gobierna toda la hoja.
79. **S24-AC-079:** missing_pac gobierna toda la hoja.
80. **S24-AC-080:** cause_category usa catálogo gobernado y misma población.
81. **S24-AC-081:** sort conversation es determinista sin puesto visible.
82. **S24-AC-082:** sort name/load/restrictions conserva alcance y filtros.
83. **S24-AC-083:** page/per_page se validan y per_page <=100.
84. **S24-AC-084:** filtros activos se serializan en URL canónica.
85. **S24-AC-085:** aliases legacy no aparecen en enlaces nuevos.
86. **S24-AC-086:** respuesta cumple envelope ok/data/meta versionado.
87. **S24-AC-087:** contrato PHP cubre success y todos los errores tipados.
88. **S24-AC-088:** schema Zod es estricto y tipos salen de z.infer.
89. **S24-AC-089:** ningún componente llama fetch directamente.
90. **S24-AC-090:** compatibilidad legacy deriva del mismo read model.
91. **S24-AC-091:** drawer abre detalle embebido sin red adicional.
92. **S24-AC-092:** drawer muestra PAC/base/carga/restricciones/causas/limitaciones.

### Estado, responsive, tema y accesibilidad

93. **S24-AC-093:** propósito “ayuda, no falla” es visible.
94. **S24-AC-094:** aviso “no proyectar” es visible y accesible.
95. **S24-AC-095:** loading usa skeleton sin nombres ficticios.
96. **S24-AC-096:** refreshing conserva contenido y anuncia actualización.
97. **S24-AC-097:** empty mine y empty project tienen copy distinto.
98. **S24-AC-098:** filtros vacíos ofrecen limpiar sin afirmar salud.
99. **S24-AC-099:** identity_unresolved no muestra datos de obra.
100. **S24-AC-100:** offline conserva última lectura y ofrece reintentar.
101. **S24-AC-101:** 401/403/404/422/500/503 tienen estados distintos.
102. **S24-AC-102:** abort y stale response evitan pisadas.
103. **S24-AC-103:** cache se particiona por sesión/scope/período/filtros/página.
104. **S24-AC-104:** >=768 monta sólo tabla semántica.
105. **S24-AC-105:** <768 monta sólo tarjetas.
106. **S24-AC-106:** tarjeta muestra señal, PAC/base, carga, restricciones y críticos.
107. **S24-AC-107:** tabla conserva las mismas decisiones que tarjetas.
108. **S24-AC-108:** SVG tiene alternativa textual visible.
109. **S24-AC-109:** no hay canvas, hover-only ni color-only.
110. **S24-AC-110:** sidebar muestra Responsables en Obra para A/D/R, con flag sólo para D/R.
111. **S24-AC-111:** R ve un selector interno mine/project, no sidebar duplicada.
112. **S24-AC-112:** ruta activa/título/breadcrumb usan manifiesto T03.
113. **S24-AC-113:** oscuro es default/fallback y claro equivalente.
114. **S24-AC-114:** estilos usan tokens sin colores literales/inline/important.
115. **S24-AC-115:** 390/480/768/1180/1440 no pierden contenido.
116. **S24-AC-116:** 320px y 200% zoom no tienen overflow horizontal.
117. **S24-AC-117:** un h1 y jerarquía de headings válida.
118. **S24-AC-118:** selector de alcance tiene nombre/estado accesibles.
119. **S24-AC-119:** tabla usa caption/headers asociados.
120. **S24-AC-120:** tarjetas tienen encabezado y región distinguible.
121. **S24-AC-121:** filtros tienen label, conteo y limpiar.
122. **S24-AC-122:** cambios de scope/período/filtro se anuncian.
123. **S24-AC-123:** drawer atrapa/devuelve foco y cierra con Escape/botón.
124. **S24-AC-124:** objetivos touch son >=44px y foco visible en ambos temas.
125. **S24-AC-125:** reduced motion se respeta.
126. **S24-AC-126:** axe serious/critical es cero.

### Integridad, reconciliación y cierre

127. **S24-AC-127:** no se modifica RLS/runtime boundary.
128. **S24-AC-128:** no se modifica schema/vista SQL/tabla/índice/trigger.
129. **S24-AC-129:** no se modifica dato, seed, fixture persistente, grant, usuario o credencial.
130. **S24-AC-130:** ninguna lectura produce INSERT/UPDATE/DELETE/DDL.
131. **S24-AC-131:** candidato S24 no consulta `cip` ni `bi_cip_responsables`.
132. **S24-AC-132:** gate D61 compara cuatro semanas reales de dos proyectos.
133. **S24-AC-133:** candidato y oráculo igualan población/PAC/críticos/alerta.
134. **S24-AC-134:** discrepancia bloquea route cut y estado ejecutable.
135. **S24-AC-135:** catálogo métrico nombra la fuente canónica y completitud.
136. **S24-AC-136:** linaje declara fórmula, corte, base y limitaciones.
137. **S24-AC-137:** S17 no proyecta conteos ni alertas personales de S24 en Gerencia.
138. **S24-AC-138:** T03 expone S24 en Obra para A/D/R y S17 nunca proyecta nombres en Gerencia.
139. **S24-AC-139:** pruebas puras cubren políticas, parser, presenter y errores.
140. **S24-AC-140:** prueba PHP de contrato cubre el endpoint existente.
141. **S24-AC-141:** frontend cubre schema/query/estado/componentes/a11y.
142. **S24-AC-142:** navegador interceptado falla en URL inesperada o no-GET.
143. **S24-AC-143:** R mine/project, A/D project, A sin flag y OT/otros 404 están cubiertos.
144. **S24-AC-144:** PAC null, PAC cero y crítico incumplido están cubiertos.
145. **S24-AC-145:** consola, page errors y fallos de red quedan en cero.
146. **S24-AC-146:** pruebas con DML sobre cip/cic quedan excluidas.
147. **S24-AC-147:** goldens/baselines no se regeneran.
148. **S24-AC-148:** route cut ocurre sólo tras dependencia/gate/contrato/a11y verdes.
149. **S24-AC-149:** rollback es sólo código/ruta y nunca datos.
150. **S24-AC-150:** caller census gobierna retiro de legacy y vista compartida.

## Entregas verticales

### Entrega 1 — Frontera privada y lectura canónica

- gate A/D/R y scope project o mine/project server-side;
- identidad fail-closed;
- población S21 sin `cip`;
- PAC/completitud/señal;
- contrato PHP.

### Entrega 2 — Contexto de ayuda

- carga y comparación;
- restricciones S20;
- causas/contrapeso S21;
- resumen, titular, filtros y drawer.

### Entrega 3 — React útil

- ruta, schema, gateway y estado;
- propósito/scope/resumen/lista;
- tabla/tarjetas, oscuro/claro y accesibilidad.

### Entrega 4 — Gate D61 y corte

- reconciliación dos proyectos/cuatro semanas;
- frontera S17 verificada;
- navegador interceptado;
- censo, route cut, rollback ensayado y evidencia.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| identidad mine no cruza | fail-closed e instrucción S13 |
| `cip` vuelve a ser autoridad por compatibilidad | call-log/static invariant del candidato |
| PAC null parece 0 | union de completitud y pruebas explícitas |
| hoja se usa para castigar | copy, ausencia de ranking, contexto y D32 |
| causa parece atribuida al responsable | recordedBy unavailable visible |
| R evade mine con query | scope forzado antes del reader |
| toggle project filtra sólo cliente | contrato y prueba server-side |
| restricciones duplican compromisos | claves compuestas y distinct explícito |
| carga se llama saturación | sin umbral/etiqueta; sólo conteo/delta |
| S17 proyecta señales personales | frontera de lienzo y retiro del campo legacy visible |
| ruta corta antes de arreglar D61 | reconciliation gate bloqueante |
| mobile oculta contexto | cards exclusivas con campos mínimos |

## Alternativas descartadas

- Poblar `cip` desde GET: lectura con side effects y DML prohibido.
- Corregir la vista SQL: fuera de alcance y no arregla la dependencia de materialización.
- Conservar scorecard como filas: no representa personas.
- Filtrar mine sólo en React: fuga de privacidad.
- Quitar el interruptor de R: contradice decisión aprobada del 2026-08-24.
- Negar S24 a A por su lienzo actual: contradice T03, donde A puede elegir Obra y el canvas no es
  una restricción adicional.
- Usar email como key: dato sensible y mutable.
- Ranking de PAC: contradice propósito D47/D91.
- Alerta con PAC null convertido a cero: defecto medido.
- Inventar actor causal: la fuente no lo registra.
- Añadir endpoint por fila: detalle ya cabe en snapshot.
- Reutilizar `ct-app`: isla a retirar, no arquitectura objetivo.

## Decisiones pendientes

Ninguna. Si la ejecución descubre una jerarquía de equipo gobernada, un actor causal auditable, un
umbral de carga aprobado o una autorización distinta para S24, se requiere enmienda explícita. No
se infiere durante implementación.

## Autor revisión

- Inventario contrastado con rutas, controladores, servicios, SQL, pruebas y respuesta real.
- Medición fue SELECT-only, agregada y sin nombres.
- Contradicciones legacy/producto resueltas conforme a T03, sin ampliar permisos fuera de A/D/R ni
  tocar datos.
- 150 criterios numerados, únicos y trazables.
- Sin implementación, DDL/DML, RLS, `/admin/`, commit ni publicación.
