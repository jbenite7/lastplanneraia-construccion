---
capa: fuente
tipo: spec
estado: vigente
id: S06
fecha: 2026-08-30
superficie: actualizar-cronograma
rutas: ["/programa-general-actualizar"]
depende_de: [T01, S04, S05]
views: [VIEW-33]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramaGeneralActualizarController, GeneralApiController, ActivityMatcherService, VIEW-33, hot_actualizar.js, rule_engine.js, decision_logger.js, CSS, pruebas, RBAC, S05 y frontend actual en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S06 de Actualizar Cronograma a React: importación XLSX con preview/confirmación, borrador de semana objetivo, mapeo manual y automático trazable, edición responsive, contratos tipados y acciones resueltas por servidor, sin modificar RLS ni datos durante la fase documental."
---

# S06 — Actualizar Cronograma en React

> **Estado:** diseño técnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que impidan escribir el plan. Esta spec no autoriza implementación, commits, DDL/DML, cambios
> RLS, cambios de permisos, deploy, publicación ni trabajo en `/admin/`. Su plan se escribe a
> continuación con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27
> planes.

## Relación con el programa

Esta spec continúa las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migración React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/superpowers/specs/2026-08-29-programa-general-react-design|S05 — Programa General React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

S06 no es una segunda vista de S05. Programa General es el cronograma operativo de una semana;
Actualizar Cronograma es la herramienta que importa una nueva versión desde Project, crea o
reemplaza el borrador de la siguiente semana y reconcilia sus actividades con la última semana
activa. Comparten filas y algunas validaciones, pero tienen permisos, estados de flujo y objetivos
distintos.

T01 posee sesión, sidebar, proyecto, tema y navegación. S04 posee el cambio de proyecto. S05 posee
el modelo base de actividad, lista, unidades, fechas, avance y enlace de retorno. S06 sólo posee el
flujo de nueva versión, el borrador objetivo, las asociaciones y su trazabilidad.

## Resultado buscado

`/programa-general-actualizar` será una superficie React que conserva, como mínimo, toda capacidad
útil y comportamiento observable del módulo PHP/JS actual:

1. explica la semana base y la semana objetivo que se están reconciliando;
2. permite descargar la plantilla y cargar un `.xlsx` de Project;
3. exige fecha de inicio de la primera semana cuando el proyecto aún no tiene semanas;
4. previsualiza y valida antes de confirmar una importación masiva;
5. crea la semana inicial o crea/reemplaza sólo el borrador objetivo, según el contexto real;
6. muestra por defecto actividades sin asociar y permite alternar al programa completo;
7. permite buscar, filtrar y conocer conteos de pendientes/procesadas/visibles;
8. ofrece mapeo manual contra actividades de la última semana activa;
9. conserva edición autorizada de fechas, unidad, cantidad y avance en filas no asociadas;
10. ejecuta autoasociación, distingue idéntica/alta/media/sin coincidencia y permite revisar;
11. guarda decisiones individuales y por lote con trazabilidad derivada de la sesión;
12. elimina únicamente el borrador que la pantalla presenta como actualización;
13. recarga, vuelve a Programa General y recupera errores sin perder trabajo local;
14. usa tabla en desktop/tablet y tarjetas editables en móvil;
15. tiene capacidad idéntica en tema oscuro y claro y es operable con teclado y lector de pantalla.

Paridad no obliga a conservar Handsontable, jQuery, Bootstrap, TomSelect, Toastr, HTML inyectado ni
los defectos del cliente legacy. React conserva intención, dato, autorización y resultado, y puede
mejorar anticipación, confirmaciones, accesibilidad y recuperación.

## Alcance

### Incluido

- Ruta piloto y ruta canónica React de Actualizar Cronograma.
- Contexto cohesivo de semana base/objetivo, borrador, acciones y CSRF.
- Descarga de la plantilla existente `actualizacionCronogramaLPS.xlsx`.
- Preview y confirmación de importación `.xlsx`.
- Parser, encabezados, fechas, banderas, jerarquía, identificadores estables e impacto del reemplazo.
- Lectura de filas objetivo y catálogo de actividades de la semana base reutilizando la lista de S05.
- Filtro Pendientes/Programa completo, búsqueda, conteos y estados vacíos.
- Mapeo manual, herencia, edición individual, guardado y errores por fila.
- Autoasociación con umbrales configurados por servidor y revisión pendiente/procesada.
- Guardado por lote de decisiones y bitácora server-side.
- Eliminación confirmada del borrador objetivo.
- Tabla desktop/tablet, tarjetas móviles, oscuro/claro y accesibilidad.
- Contratos PHP, esquemas Zod, pruebas puras/componentes y navegador con red interceptada.
- Convivencia con VIEW-33 durante piloto y retiro de sus piezas exclusivas después del corte.

### Fuera de alcance

- `/admin/` y cualquier superficie, permiso o dependencia administrativa.
- Cambiar RLS, `ProjectScope`, grants, usuarios MySQL, credenciales, schema, índices o datos.
- Ejecutar DDL/DML durante esta fase documental o usar una base compartida para fabricar fixtures.
- Cambiar la semántica de activación/cierre de semanas; S06 prepara el borrador, T01/S08 gobiernan
  la semana activa.
- Migrar Programa General, Programación Intermedia, Semanal, PDC o BI dentro de esta entrega.
- Cambiar el algoritmo ponderado de `ActivityMatcherService` o sus valores configurables.
- Crear un motor de IA, entrenar un modelo o mantener `rule_engine.js`, que no tiene consumidores.
- Agregar versionado de filas, locks optimistas o una nueva tabla de versiones.
- Resolver en S06 el efecto histórico de `programa` sobre PDC cuando se elimina un borrador. Se
  conserva la semántica legacy: el borrador de `programa_consolidado` se elimina; el maestro ya
  actualizado no se reconstruye sin una decisión de dominio separada.
- Consumir o retirar `/api/pg/breadcrumb-preview` y `/api/pg/breadcrumb-estandarizar`: la búsqueda
  completa no encontró un caller de VIEW-33; su disposición corresponde al barrido de huérfanos.
- Regenerar goldens visuales sin aprobación explícita.

## Punto de partida medido

### React

- No existe módulo, página, esquema ni gateway de Actualizar Cronograma.
- `NavegacionLateral.tsx` enlaza `/programa-general-actualizar` como documento externo.
- El router React no reconoce una ruta propia de S06.
- El cliente HTTP común todavía debe adquirir soporte de `FormData`, errores JSON y cancelación en
  T01/S05; S06 lo consume y no crea un segundo cliente.
- La única pieza React observable es la entrada de navegación.

### Legacy

| Pieza | Medición |
|---|---|
| Vista | `views/programa-general-actualizar/programaGeneralActualizar.view.php`, 617 líneas |
| Controlador de página | `ProgramaGeneralActualizarController`, 107 líneas |
| Interacción principal | `hot_actualizar.js`, 1.835 líneas |
| Motor local | `rule_engine.js`, 517 líneas, cargado pero sin caller |
| Bitácora cliente | `decision_logger.js`, 133 líneas |
| Editor compartido | `HandsontableTomSelectEditor.js`, 595 líneas |
| Presentación | `programa-general-actualizar.css`, 764 líneas + adaptador Handsontable |
| Tabla | 10 columnas, primera oculta, sin renderer móvil real |
| Evidencia visual | sólo dark, 1180×820, rol A, red de lista/códigos mockeada |

La vista carga jQuery, jQuery UI, Bootstrap, Handsontable, TomSelect, Font Awesome y varios globals.
La carga y eliminación están escritas inline; la tabla, el autosave y la autoasociación viven en un
IIFE global. El `#mobile-card-view` existe vacío: el CSS esconde la grilla bajo 1180 px, pero ningún
script llena las tarjetas. Por tanto, tablet y móvil pierden la superficie de datos; no existe una
paridad móvil que copiar.

### Columnas y edición observadas

| Columna visible | Propiedad | Legacy | React S06 |
|---|---|---|---|
| Id | `Id` | lectura | lectura |
| Actividad nueva | `Actividad` | lectura, HTML | texto semántico seguro |
| Asociar con… | `programaAnteriorAsociar` | siempre editable en cliente | editable sólo con acción servidor |
| F. Inicio | `Fecha_Inicio` | editable según rol/semana | editable en borrador autorizado |
| F. Fin | `Fecha_Fin` | editable según rol/semana | editable en borrador autorizado |
| Unidad | `unidad` | editable sólo si no está asociada | misma regla, también en servidor |
| Cant. PPTO | `cantidad_ppto` | editable si no asociada y unidad distinta de `%` | misma regla validada |
| Restricciones | `Estado_Restricciones` | ratio de sólo lectura | porcentaje de sólo lectura |
| Ejec. real | `Ejecutado` | cantidad física/porcentaje editable si no asociada | ratio canónico, presentación contextual |

`unique_id` está oculto. El JS declara `codigo_actividad` editable y consulta `/api/general/codigos`,
pero no configura ninguna columna para mostrarlo: es código muerto, no una capacidad visible. S06
no agrega un selector de código; la asociación hereda el código y S05 permite editarlo.

Unidades observadas: `ml`, `m2`, `m3`, `un`, `gl`, `kg`, `%`, `Niveles`; vacío entra como alias de
`%`. Valores desconocidos recibidos se muestran en sólo lectura hasta corregirse en S05.

### Comportamiento observable

- La barra ofrece Cargar desde Excel, Eliminar Actualización, Pendientes/Programa completo,
  Auto-Asociar y un chip de Auto-Guardado.
- Pendientes es el filtro inicial: asociación `null`, vacía o `*No Asociada*`.
- La importación acepta `.xlsx`; en el primer proyecto solicita fecha inicial y crea semana 1.
- Importaciones posteriores crean o reemplazan una semana borrador y disparan autoasociación tras
  recargar.
- El autosave agrupa cambios de una fila durante 800 ms e intenta enviarlos antes de abandonar.
- Seleccionar una asociación hereda responsable, subcontratista, observaciones, código, unidad,
  cantidad, avance y restricciones de la actividad base.
- La autoasociación aplica idénticas/alta confianza y abre revisión para confianza media.
- La revisión muestra cuatro conteos, tabs Pendientes/Procesadas, cinco candidatos como máximo,
  aceptación, Sin coincidencia, Cambiar y un guardado en lote aparente.
- El guardado de revisión en realidad lanza una mutación individual por decisión; no es atómico.
- La eliminación borra físicamente una semana superior a la máxima activa y hace soft reset si la
  semana es activa, aunque la etiqueta dice “Eliminar Actualización”.
- El primer import exitoso presenta un modal y redirige a Programa General; los demás permanecen en
  S06 para mapear.

## Inventario HTTP auditado

| Método | Ruta actual | Contrato/uso actual | Disposición S06 |
|---|---|---|---|
| GET | `/programa-general-actualizar` | VIEW-33; sólo `requireAuth()` | piloto React, luego canónica con permiso de vista |
| GET | `/api/general/list` | base/objetivo; acepta `db`, `semana_objetivo`, `exclude_chapters` | reutilizar GET con `semana`, sin `db`; adaptar filas |
| GET | `/api/general/codigos` | consultado pero no existe columna visible | no consumir en S06 |
| POST | `/api/general/update` | autosave general y herencia mediante flags | S05 lo conserva; S06 usa mutación dedicada |
| POST | `/api/general/import` | una fase, muta; `{respuesta,0,semana_base}` | alias legacy durante piloto; sustituir por preview/confirm |
| POST | `/api/general/delete-update` | borra draft o resetea activa | alias legacy; nueva ruta sólo permite draft |
| POST | `/api/general/auto-associate` | aplica idénticas/altas y devuelve medias | alias legacy; refactor detrás del contrato S06 |
| POST | `/api/general/decision-log` | acepta proyecto/usuario implícito desde cliente | retirar del cliente; bitácora interna server-side |
| POST | `/api/pg/breadcrumb-preview` | sin caller encontrado | no pertenece a S06; barrido final |
| POST | `/api/pg/breadcrumb-estandarizar` | sin caller encontrado; muta | no pertenece a S06; barrido final |
| GET | `/archivosBase/actualizacionCronogramaLPS.xlsx` | plantilla descargable | conservar como asset versionado |
| POST | `/context/week` | cambia semana activa del shell | no se usa para calcular base/objetivo S06 |

### Contratos de importación existentes

`detectImportColumnMap()` reconoce, tras normalizar acentos, caso y separadores:

| Campo | Encabezados aceptados |
|---|---|
| esquema | `numero de esquema`, `esquema`, `wbs`, `edt`, `id` |
| actividad | `nombre de tarea`, `actividad`, `tarea`, `nombre` |
| unique ID opcional | `unique id`, `unique_id`, `id unico`, `identificador unico` |
| resumen | `resumen`, `titulo`, `summary` |
| comienzo | `comienzo`, `fecha inicio`, `fecha de inicio`, `inicio`, `start` |
| fin | `fin`, `fecha fin`, `fecha de fin`, `finish` |
| crítica | `tareas criticas`, `ruta critica`, `critica`, `critical` |

Las seis columnas no opcionales son obligatorias. Filas sin esquema se omiten. Banderas verdaderas:
`1`, `s`, `si`, `yes`, `true`. Fechas admiten objetos/seriales Excel, `YYYY-M-D`, `YYYY/M/D`,
`D-M-YYYY`, `D/M/YYYY`; se normalizan a `YYYY-MM-DD`. Un serial debe producir año 1900..2200.

La importación calcula identificador estable con esta prioridad: Unique ID importado no repetido,
WBS ya conocido, ordinal conocido y, por último, bloque reservado de IDs del proyecto. Conserva el
formato jerárquico legacy en almacenamiento, pero React nunca usa `dangerouslySetInnerHTML` para
mostrarlo.

### Importación y persistencia observadas

La semana destino actual se resuelve así:

1. si `MAX(programa_consolidado.Semana)=0`, destino 1;
2. si `maxPrograma > maxActiva`, se reemplaza ese borrador;
3. en otro caso, destino `maxActiva + 1`.

En una transacción, el legacy actualiza `programa`, elimina filas maestras no referenciadas, crea la
semana activa 1 si corresponde, reemplaza `programa_consolidado` del destino e invoca el recalculo
legacy. Para semanas posteriores no crea `semanas_activas`: el destino permanece borrador hasta el
flujo de creación/activación de semana.

El parser no limita tamaño, extensión real o MIME y carga el workbook antes de dar errores
accionables. S06 adopta el patrón ya vigente de Plan de Compras: máximo 10 MiB, extensión `.xlsx`,
MIME permitido, parser encapsulado, mensajes de vendor no expuestos y preview sin persistencia.

## Permisos y capacidades

### Matriz efectiva por fallback vigente

| Rol | Ver Actualizar | Editar Actualizar | Ver PG compartido | Resultado S06 |
|---|---:|---:|---:|---|
| A, D | Sí | Sí | Sí | lectura e importación/mapeo/borrado |
| R, DCV | Sí | Sí | Sí | lectura e importación/mapeo/borrado del draft |
| OT, V | Sí | No | Sí | sólo lectura |
| G, S, SG | No | No | Sí | 403 S06, aunque el sidebar legacy pueda mostrar enlace |
| C | No | No | No | 403 y sin navegación |

La tabla sólo describe fallbacks actuales. Overrides configurados pueden cambiar el resultado. El
servidor entrega acciones efectivas; React no compara roles ni `canManageGeneralProgram`.

### Contradicciones que se cierran

1. La página PHP sólo exige autenticación; React/canónica exige
   `lps.programa_general_actualizar.ver`.
2. Importar y eliminar exigen hoy `lps.programa_general.editar`; autoasociar y bitácora exigen
   `lps.programa_general_actualizar.editar`. Todas las mutaciones propias de S06 usan esta última.
3. La columna de asociación ignora `_canEditGlobal` y confirmación. React usa `actions.editRows`.
4. El cliente calcula permisos desde A/D/R/DCV y semana. React no recibe rol como autoridad.
5. `decision-log` acepta `proyecto_id` del navegador y obtiene un usuario inexistente de
   `getSessionVars()['user']`. S06 deriva prefijo/proyecto y usuario exclusivamente de sesión/scope.
6. Base/objetivo de la vista dependen a veces de la semana de sesión, pero importación usa máximos
   reales. S06 usa un resolver único para contexto y todas las mutaciones.
7. El botón Eliminar puede resetear una semana activa. El contrato React sólo elimina el borrador
   que el contexto declara.

No se modifica el catálogo, los fallbacks, RLS ni `ProjectScope`. Se corrige el punto de consumo.

## Decisiones de arquitectura

### S06-R1 — Resolver único de base y objetivo

`ProgramaGeneralUpdateContextResolver` calcula, dentro del proyecto activo:

```text
maxActive  = MAX(semanas_activas.Semana) o 0
maxProgram = MAX(programa_consolidado.Semana) o 0
baseWeek   = maxActive
targetWeek = maxProgram si maxProgram > maxActive; en otro caso maxActive + 1
hasDraft   = maxProgram > maxActive y el target tiene filas
mode       = initial si maxActive=0 y no hay filas target
             draft si target tiene filas y target > maxActive
             next si target=maxActive+1 y aún no tiene filas
```

Una anomalía con filas programadas pero sin semana activa se clasifica como `draft`, base 0, y sólo
permite reemplazar/eliminar tras pasar las mismas guardas. El resolver no escribe `$_SESSION`.

La semana seleccionada en el shell se muestra como contexto general, pero no altera la versión de
Project que S06 prepara. Esto alinea pantalla, importación, mapeo, autoasociación y borrado.

### S06-R2 — Preview y confirmación de XLSX

La carga se divide en dos operaciones:

1. preview valida archivo, columnas, filas, fechas, IDs y contexto sin tocar datos;
2. confirmación consume un token efímero, vuelve a comprobar proyecto/base/objetivo y ejecuta la
   transacción existente.

El token está ligado a sesión, usuario, proyecto, hash del archivo y contexto; vive fuera del web
root, expira en 15 minutos y es de un solo uso. No contiene rutas ni secretos decodificables por el
cliente. Un cambio de proyecto, target o máximos invalida el token con 409.

### S06-R3 — Mutaciones propias, APIs compartidas sólo para lectura

S06 reutiliza la lista de S05 para base/objetivo, pero no vuelve a enviar el payload amplio de
`/api/general/update`. Una mutación dedicada valida el target draft, campos permitidos, asociación
y herencia. Esto evita que un guard de Programa General decida accidentalmente una herramienta con
permiso distinto.

Los aliases `/api/general/import|delete-update|auto-associate|decision-log` permanecen durante el
piloto para rollback de VIEW-33. No son llamados por React y se retiran sólo tras búsqueda de
consumidores y corte aprobado.

### S06-R4 — Autoasociación server-side con umbrales reales

`ActivityMatcherService` sigue siendo la única autoridad. Sus umbrales configurables por defecto
son alta 0,90, media 0,70 y capítulo 0,70; la UI actual usa 0,80/0,50 para colorear, divergencia que
no se conserva. La respuesta incluye los umbrales efectivos y React los usa para etiquetas,
leyenda y barras.

Como el legacy, una ejecución aplica coincidencias idénticas y altas a filas aún no asociadas. Las
medias se revisan y las menores al umbral quedan sin asociación. Toda aplicación automática se
registra en servidor. Repetir la operación es idempotente para asociaciones ya resueltas.

### S06-R5 — Herencia y bitácora son una transacción de servidor

React envía IDs estables, no nombres como autoridad. El servidor verifica que el target pertenece a
la semana objetivo y el source a la semana base; deriva el nombre que se almacena por compatibilidad
y hereda los mismos campos que el legacy. La bitácora obtiene proyecto y usuario de contexto.

El guardado de revisión es un lote real: valida todas las decisiones y aplica todas o ninguna. La
bitácora se escribe dentro de la misma transacción. No se lanzan N mutaciones desde el modal.

### S06-R6 — React nativo y responsive real

No se envuelve Handsontable. Una tabla semántica cubre desktop/tablet; tarjetas cubren móvil. Ambas
consumen el mismo estado y validadores. El breakpoint es 768 px, no el 1180 del CSS legacy que hoy
deja una región vacía.

### S06-R7 — Estrangulación y rollback por ruta

- Piloto: `/app/programa-general-actualizar` sirve React.
- Convivencia: `/programa-general-actualizar` continúa en VIEW-33.
- Corte: GET/HEAD canónico entra al SPA después de gates funcionales/RBAC/responsive/tema/a11y.
- Rollback: revertir el cambio de ruta devuelve VIEW-33 mientras los aliases sigan presentes.
- Retiro: vista, JS y CSS exclusivos salen sólo después del corte estable.

## Contratos backend

### Contexto

```text
GET /api/programa-general-actualizar/context
Permiso: lps.programa_general_actualizar.ver
Cache-Control: no-store
```

Respuesta:

```json
{
  "success": true,
  "data": {
    "project": { "id": 73, "name": "Proyecto", "area": "Construccion" },
    "schedule": {
      "baseWeek": 18,
      "targetWeek": 19,
      "maxActiveWeek": 18,
      "maxProgramWeek": 19,
      "mode": "draft",
      "hasDraft": true,
      "targetRows": 214,
      "unmappedRows": 17
    },
    "actions": {
      "importSchedule": true,
      "deleteDraft": true,
      "editRows": true,
      "autoAssociate": true,
      "saveReview": true
    },
    "csrf": {
      "actualizarCronograma": "0000000000000000000000000000000000000000000000000000000000000000"
    },
    "matching": { "highThreshold": 0.9, "mediumThreshold": 0.7, "chapterThreshold": 0.7 },
    "links": {
      "template": "/archivosBase/actualizacionCronogramaLPS.xlsx",
      "programaGeneral": "/programa-general"
    }
  }
}
```

Los ceros son documentales. El token real conserva el formato de `CsrfTokenManager` y nunca entra
en fixture, log ni captura. `area` admite `Construccion|Pre-Construccion`; `mode`,
`initial|next|draft`. `project.id` sólo detecta una respuesta obsoleta tras cambiar proyecto.

Acciones requieren el permiso efectivo de edición y precondiciones:

- `importSchedule`: editor S06 y contexto válido;
- `deleteDraft`: editor, `hasDraft=true`, target superior a máxima activa;
- `editRows`: editor y draft existente;
- `autoAssociate`: `editRows`, base > 0, filas base y target;
- `saveReview`: mismas precondiciones que autoasociar.

Una cuenta con permiso de vista recibe contexto/filas pero acciones falsas. Sin permiso de vista,
403 JSON. Sin proyecto, 409 `PROJECT_REQUIRED` y el shell redirige al selector.

### Lecturas compartidas

React carga en paralelo, con cancelación por cambio de proyecto:

```text
GET /api/general/list?semana=<targetWeek>
GET /api/general/list?semana=<baseWeek>
```

No envía `db`, `semana_objetivo`, filtros de sesión ni `exclude_chapters`. El adaptador S06 descarta
capítulos de la colección editable, pero conserva conteo/jerarquía necesaria para mostrar contexto.
La lista base produce opciones `{uniqueId,id,name,startDate,chapter}`; el nombre se muestra como
texto, nunca HTML. Si `baseWeek=0`, no se realiza la segunda llamada.

El modelo objetivo mínimo conserva:

```text
unique_id, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin,
unidad, cantidad_ppto, Ejecutado, Estado_Restricciones,
programaAnteriorAsociar
```

Se reutilizan normalización de número, fecha, unidad y ratio de S05. El raw schema puede tolerar
campos adicionales de `SELECT *` únicamente en el adaptador; componentes reciben un objeto cerrado.

### Preview de importación

```text
POST /api/programa-general-actualizar/import/preview
Content-Type: multipart/form-data
Header: X-CSRF-Token
Permiso: lps.programa_general_actualizar.editar
Partes: archivoExcel; firstWeekStart sólo en mode=initial
```

Validaciones antes de parsear:

1. upload completo y archivo realmente subido;
2. máximo 10 MiB;
3. nombre terminado en `.xlsx`;
4. MIME XLSX/ZIP/octet-stream permitido por el patrón del repo;
5. workbook legible; mensajes internos del vendor no salen;
6. columnas requeridas detectadas;
7. al menos una fila con esquema;
8. Unique IDs positivos y sin duplicados dentro del archivo cuando se aportan;
9. fechas reales y, cuando ambas existen, inicio no posterior a fin;
10. en `initial`, `firstWeekStart` ISO real; en otros modos se rechaza/ignora de forma explícita.

El servidor devuelve 422 con errores por fila y no crea token si hay errores. Respuesta exitosa:

```json
{
  "success": true,
  "data": {
    "importToken": "opaque-token",
    "expiresAt": "2026-08-30T18:15:00Z",
    "fileName": "cronograma.xlsx",
    "baseWeek": 18,
    "targetWeek": 19,
    "mode": "draft",
    "rows": 214,
    "chapters": 32,
    "activities": 182,
    "replaceExistingDraft": true,
    "existingDraftRows": 207,
    "warnings": []
  }
}
```

El preview no incluye rutas temporales, prefijo de tabla, project ID autoritativo, HTML ni contenido
completo del archivo. Advertencias son estructuradas `{code,row?,message}`.

### Confirmación de importación

```text
POST /api/programa-general-actualizar/import/confirm
Content-Type: application/json
Header: X-CSRF-Token
Body: { "importToken": "opaque-token" }
Permiso: lps.programa_general_actualizar.editar
```

Precondiciones: token vigente, de un solo uso, misma sesión/usuario/proyecto, mismo hash y mismo
resolver base/target. Un mismatch responde 409 `IMPORT_CONTEXT_CHANGED`; un token vencido/usado,
410 `IMPORT_TOKEN_EXPIRED`; ninguna escritura parcial.

Éxito:

```json
{
  "success": true,
  "data": {
    "baseWeek": 18,
    "targetWeek": 19,
    "result": "draft-replaced",
    "importedRows": 214,
    "activities": 182,
    "chapters": 32,
    "shouldAutoAssociate": true,
    "redirectTo": null
  }
}
```

`result` admite `initial-created|draft-created|draft-replaced`. En `initial-created`,
`redirectTo=/programa-general` y `shouldAutoAssociate=false`; React muestra éxito y permite ir al
programa, sin temporizador que saque al usuario mientras lee. En draft, recarga contexto/listas y,
si se autoriza, inicia autoasociación una sola vez.

La transacción conserva el orden del legacy: IDs, maestro `programa`, semana 1 si corresponde,
reemplazo del consolidado objetivo y recálculo. Proyecto y usuario se derivan de sesión/scope.

### Guardado individual

```text
POST /api/programa-general-actualizar/save
Content-Type: application/json
Header: X-CSRF-Token
Permiso: lps.programa_general_actualizar.editar
```

Payload discriminado:

```json
{
  "targetWeek": 19,
  "uniqueId": 8401,
  "changes": {
    "startDate": "2026-09-07",
    "endDate": "2026-09-18"
  }
}
```

o, exclusivamente para mapeo:

```json
{
  "targetWeek": 19,
  "uniqueId": 8401,
  "changes": { "sourceUniqueId": 6120 }
}
```

`sourceUniqueId:null` marca Sin coincidencia. La mutación de asociación no se combina con otros
campos para evitar ambigüedad entre input y herencia. Campos editables ordinarios:
`startDate`, `endDate`, `unit`, `budgetQuantity`, `progressRatio`.

Reglas:

1. target igual al resolver y superior a máxima activa;
2. fila del proyecto/target, no capítulo;
3. al menos un cambio permitido; claves extra rechazadas;
4. fechas ISO reales y `startDate <= endDate` considerando valor persistido no enviado;
5. unidad del catálogo o valor desconocido sin modificación;
6. cantidad nula/no negativa, un decimal; `%` fuerza `null`;
7. ratio 0..1, seis decimales;
8. fila asociada sólo permite fechas o cambiar/quitar asociación;
9. source, si existe, pertenece al mismo proyecto/base y no es capítulo;
10. elegir source hereda exactamente los campos legacy y devuelve la fila confirmada.

Éxito devuelve `{success:true,data:{row:<TargetRow>,inherited:boolean}}`. React reemplaza la fila
completa; no calcula restricciones o avance heredado. 409 de contexto no ofrece reintento ciego:
recarga y explica que el borrador cambió.

### Autoasociación

```text
POST /api/programa-general-actualizar/auto-associate
Body: { "targetWeek": 19 }
Header: X-CSRF-Token
Permiso: lps.programa_general_actualizar.editar
```

El servicio lee source base y target, ejecuta `ActivityMatcherService`, aplica sólo idénticas/altas
a filas aún no asociadas, hereda y registra. Respuesta:

```json
{
  "success": true,
  "data": {
    "thresholds": { "high": 0.9, "medium": 0.7, "chapter": 0.7 },
    "counts": { "identical": 80, "high": 21, "medium": 14, "none": 7, "updated": 101 },
    "updatedRows": [],
    "review": [
      {
        "target": { "uniqueId": 8401, "name": "Actividad nueva" },
        "alreadyAssociated": false,
        "currentSourceUniqueId": null,
        "candidates": [
          {
            "uniqueId": 6120,
            "id": "3.2.1",
            "name": "Actividad anterior",
            "chapter": "Estructura",
            "startDate": "2026-05-04",
            "confidence": 0.82
          }
        ]
      }
    ]
  }
}
```

Hay máximo cinco candidatos por target, orden determinista. `updatedRows` usa el esquema de fila
confirmada. Source/target vacíos producen conteos cero o `none` sin error. Una segunda ejecución no
sobrescribe asociaciones existentes y `updated=0` para ellas.

### Guardado de revisión

```text
POST /api/programa-general-actualizar/associations/batch
Body: {
  "targetWeek": 19,
  "decisions": [
    { "targetUniqueId": 8401, "decision": "accept", "sourceUniqueId": 6120 },
    { "targetUniqueId": 8402, "decision": "skip" }
  ]
}
```

Cada target aparece una vez. `accept` requiere source válido; `skip` no admite source. `correct` se
deriva server-side cuando se elige un candidato distinto al sugerido; el cliente no falsifica la
bitácora. Todas las decisiones se validan antes de abrir transacción y se aplican todas o ninguna.

`skip` cambia el marcador a Sin coincidencia y, por paridad, no limpia otros valores ya heredados.
La respuesta incluye `accepted`, `skipped`, `corrected` y filas completas. La bitácora usa el prefijo
del proyecto y `$_SESSION['usuario']`; no existe `proyecto_id`, usuario, rol ni confianza aportados
como autoridad por React.

### Eliminar borrador

```text
POST /api/programa-general-actualizar/delete-draft
Body: { "targetWeek": 19 }
Header: X-CSRF-Token
Permiso: lps.programa_general_actualizar.editar
```

El servidor recalcula contexto. Sólo procede si target > máxima activa y contiene filas. Nunca
resetea una semana activa. Éxito devuelve base, target eliminado y filas eliminadas; después React
recarga contexto y muestra el estado inicial de próxima actualización. Sin draft devuelve 409
`SCHEDULE_DRAFT_NOT_FOUND`; target activo, 409 `SCHEDULE_ACTIVE_WEEK_PROTECTED`.

La confirmación visible incluye semana y cantidad de filas; el botón queda deshabilitado durante la
petición. No se repite automáticamente.

### Errores

Todos los endpoints S06 nuevos usan la envoltura:

```json
{
  "success": false,
  "error": {
    "code": "IMPORT_VALIDATION_FAILED",
    "message": "El archivo tiene errores; no se importó nada.",
    "fields": {},
    "details": []
  }
}
```

Mapa mínimo:

| HTTP | Código | Uso |
|---:|---|---|
| 401 | `SESSION_EXPIRED` | shell maneja reingreso |
| 403 | `FORBIDDEN` | permiso de vista/edición |
| 409 | `PROJECT_REQUIRED` | falta proyecto |
| 409 | `SCHEDULE_CONTEXT_CHANGED` | base/target cambió |
| 409 | `SCHEDULE_DRAFT_NOT_FOUND` | no hay draft |
| 409 | `SCHEDULE_ACTIVE_WEEK_PROTECTED` | intento sobre activa |
| 410 | `IMPORT_TOKEN_EXPIRED` | preview vencido/usado |
| 413 | `FILE_TOO_LARGE` | más de 10 MiB |
| 422 | `INVALID_FILE` | upload/extensión/MIME/workbook |
| 422 | `IMPORT_VALIDATION_FAILED` | columnas/filas/fechas |
| 422 | `VALIDATION_FAILED` | save/batch inválido |
| 500 | `INTERNAL_ERROR` | mensaje seguro y trazabilidad servidor |

## Frontera HTTP React

`frontend/src/lib/api/cliente.ts` es el único archivo que llama `fetch`. S06 usa gateways con nombres
de dominio y esquemas Zod `.strict()`:

```text
obtenerContextoActualizacion()
listarSemanaActualizacion(semana, signal)
previsualizarImportacion(formData)
confirmarImportacion(token)
guardarFilaActualizacion(payload)
autoAsociar(targetWeek)
guardarRevisiones(payload)
eliminarBorrador(targetWeek)
```

Los componentes no conocen URLs, headers, token CSRF ni formas legacy. `FormData` no fija
`Content-Type`; JSON sí. `Accept: application/json`, `credentials:same-origin`, `AbortSignal` en
lecturas y cero retry automático en mutaciones.

React jamás envía `db`, `Base_de_Datos`, table prefix, `project_id`, proyecto, usuario, rol, área,
`maxWeek`, permiso, `Semanal_Confirmada`, umbral o flag de autorización.

## Modelo de dominio frontend

Los tipos salen exclusivamente de Zod:

```text
ContextoActualizarCronograma
  project
  schedule
  actions
  matching
  links

ActividadActualizacion
  uniqueId: number
  id: string
  name: string
  chapter: string | null
  startDate: string | null
  endDate: string | null
  unit: ProgramaGeneralUnit | string
  budgetQuantity: number | null
  progressRatio: number | null
  restrictionReleaseRatio: number | null
  sourceAssociation: {
    sourceUniqueId: number | null
    sourceName: string
  } | null
  saveState: clean | dirty | saving | saved | error

CandidatoAsociacion
  uniqueId, id, name, chapter, startDate, confidence

DecisionRevision
  targetUniqueId
  decision: accept | skip
  sourceUniqueId?: number
```

El marcador legacy `*No Asociada*`, vacío y `null` se normalizan a `sourceAssociation=null`. No se
vuelve a mostrar ni enviar la cadena técnica. Los ratios se conservan 0..1; UI convierte a `%` o
cantidad física con funciones compartidas de S05. Fechas permanecen strings ISO para evitar zona
horaria.

El association source ID se obtiene cruzando el nombre legacy con la lista base durante
convivencia. Si hay nombres duplicados o no se encuentra source, se conserva nombre como asociación
de sólo lectura y se exige elegir explícitamente un source antes de cambiarla.

## Estado y flujo de pantalla

### Carga inicial

1. shell valida sesión/proyecto/ruta;
2. S06 solicita contexto;
3. con contexto, carga target y base cancelables;
4. normaliza filas y asociaciones;
5. presenta estado según `mode`, `hasDraft` y acciones.

Una respuesta de proyecto anterior se descarta comparando `project.id`. Cambiar proyecto cancela
lecturas, borra file/preview/revisión/drafts locales y arranca con el nuevo contexto.

### Estados de alto nivel

| Estado | Presentación/acción |
|---|---|
| cargando contexto | skeleton de encabezado/toolbar, sin botones falsamente activos |
| sin proyecto | shell redirige a selector |
| sin actualización | explicación, plantilla y Cargar Excel si autorizado |
| draft cargando | skeleton de filas |
| draft vacío inesperado | CTA de reimportar y diagnóstico seguro |
| draft con pendientes | Pendientes activo, conteos y mapeo |
| draft todo asociado | mensaje de éxito y acceso a Programa completo |
| sólo lectura | tabla/tarjetas y filtros; mutaciones ausentes |
| error de contexto/lista | panel con Reintentar; no sustituye por vacío |
| error parcial base | target visible; mapping/auto deshabilitados con reintento base |
| importando/guardando | progreso y controles en vuelo bloqueados |

## Importación UX

1. `Cargar desde Excel` abre un diálogo con enlace a plantilla, input real de archivo y ayuda.
2. En `mode=initial` aparece fecha de inicio con input `date`; es obligatoria.
3. Seleccionar archivo muestra nombre/tamaño y habilita Previsualizar.
4. Preview muestra destino, filas/capítulos/actividades, si reemplaza draft y advertencias.
5. Confirmar describe el efecto masivo; Cancelar invalida/descarta el preview local.
6. Durante confirmación no se puede doble enviar ni cerrar inadvertidamente.
7. Error de archivo mantiene el diálogo y foco en el primer error; permite reemplazar archivo.
8. `initial-created` muestra éxito y botón Ir a Programa General.
9. Draft recarga filas y ejecuta autoasociación una vez; si falla, el import sigue exitoso y ofrece
   Reintentar autoasociación.

No hay redirección automática por temporizador. Navegar con archivo o preview sin confirmar abre una
confirmación; el archivo nunca se persiste en `localStorage` o `sessionStorage`.

## Filtros, búsqueda y conteos

### Segmento principal

- `Pendientes` es default y significa asociación nula.
- `Programa completo` muestra todas las actividades objetivo.
- El estado se conserva en `?vista=pendientes|completo`; un valor inválido se normaliza sin petición.

### Búsqueda

Una sola búsqueda, sin nueva petición, compara texto normalizado de Id, actividad nueva, capítulo y
actividad asociada. Ignora HTML, acentos y caso. Debounce 150 ms sólo para render; no persiste en
sesión PHP.

### Conteos

Se muestran `pendientes`, `asociadas`, `total` y `visibles`. En revisión se muestran idénticas,
alta, media, sin coincidencia, pendientes y procesadas. Los conteos salen del mismo conjunto
filtrado/estado local y no divergen de las filas visibles.

Cambiar asociación actualiza conteos después de respuesta del servidor; una edición no confirmada
no finge que el pendiente desapareció.

## Tabla, tablet y móvil

### Desktop — 1180 px o más

Tabla semántica con columnas:

```text
Id, Actividad nueva, Asociar con, F. inicio, F. fin,
Unidad, Cant. PPTO, Lib. restricciones, Ejec. real
```

El encabezado permanece visible dentro del scroll de contenido. Actividad es flexible; valores
numéricos se alinean por lectura. No hay scroll horizontal de página. Toolbar y contexto permanecen
fuera del scroll de filas.

### Tablet — 768 a 1179 px

Sigue siendo tabla, con columnas iniciales:

```text
Actividad nueva, Asociar con, F. inicio, F. fin, Ejec. real
```

Un control Detalles por fila revela Id, unidad, cantidad y restricciones dentro de la fila. Mapeo,
fechas y avance siguen editables cuando se autoriza. No se oculta una grilla sin reemplazo.

### Móvil — menos de 768 px

Cada actividad es una tarjeta con:

- nombre, Id y estado Pendiente/Asociada;
- selector accesible de asociación con búsqueda;
- fechas;
- unidad, cantidad, liberación de restricciones y avance;
- acciones Editar/Guardar/Descartar y feedback local.

La tarjeta no dispara autosave por cada pulsación; abre un editor, valida y guarda explícitamente.
El mapeo puede confirmarse desde la tarjeta. Tabla y tarjetas no se montan simultáneamente. Un
estado vacío explica cómo importar, no muestra 700 px en blanco.

Viewports obligatorios: 390×844, 768×1024, 1180×820 y 1440×900.

## Edición y validación

### Asociación

- Selector busca Id/nombre/fecha base.
- Elegir source pide confirmación cuando reemplaza una asociación distinta, porque heredará datos.
- Quitar asociación explica que valores heredados existentes no se borran por paridad.
- Éxito reemplaza la fila con la respuesta server-side.
- Error conserva selección local y ofrece Reintentar/Descartar.

### Fechas, unidad, cantidad y avance

Se reutilizan las reglas S05:

1. fechas ISO reales y ordenadas;
2. cantidad nula o no negativa, un decimal;
3. `%` implica cantidad nula;
4. avance visible no negativo y ratio 0..1;
5. con unidad física/cantidad, visible no supera cantidad;
6. con `%` o sin cantidad, visible 0..100;
7. cambio de contexto preserva ratio y requiere confirmación al perder cantidad.

En filas asociadas, unidad/cantidad/avance son de sólo lectura; cambiar source vuelve a heredarlos.
Fechas permanecen editables. El servidor repite todas las reglas; disabled/readOnly del cliente no
es autorización.

### Feedback y navegación

Sólo una mutación activa por fila. Otras filas pueden editarse. El editor anuncia `Guardando…`,
`Guardado` o error mediante `aria-live`. No hay actualización optimista de datos heredados.

Cambiar proyecto, ruta o recargar con editor dirty solicita confirmación. No se usa un fetch tardío
en `beforeunload` como garantía de persistencia.

## Autoasociación y revisión

La acción muestra primero que idénticas/altas se aplicarán automáticamente. Durante proceso evita
doble envío. Al terminar:

- tabla/tarjetas reciben filas aplicadas;
- se resaltan asociaciones automáticas, revisables y sin coincidencia con texto+icono, no sólo color;
- si hay medias, abre panel/modal de revisión;
- si no hay medias, anuncia conteos y mantiene acceso a Programa completo.

La revisión conserva las capacidades legacy:

- cuatro estadísticas;
- tabs Pendientes y Procesadas con conteos;
- target y hasta cinco candidatos de semana base;
- Id, capítulo, fecha, confianza y barra;
- Aceptar, Sin coincidencia y Cambiar;
- guard de cierre con decisiones sin guardar;
- Guardar cambios con cantidad.

En React, candidatos son radios/controles semánticos; no HTML concatenado. Confianza usa umbrales
del response y se muestra como porcentaje. El batch muestra resultado exacto; errores de validación
se asocian al target y no dejan un éxito falso.

## Eliminar actualización

La acción sólo aparece con `deleteDraft=true`. El diálogo dice:

- semana objetivo;
- cantidad de filas;
- que se eliminará el borrador importado;
- que las semanas activas no cambian;
- que la operación no puede deshacerse desde la pantalla.

Cancelar no hace petición. Confirmar bloquea botón, y éxito vuelve al estado sin draft. La semántica
de `programa` maestro se conserva como limitación conocida del legacy; S06 no promete restauración
histórica inexistente.

## Sistema visual, temas y accesibilidad

- Oscuro es default/fallback; claro ofrece idénticas acciones y estados.
- Todo color, radio, sombra, espaciado y foco proviene de `public/css/tokens.css`.
- No hay hex, `style={{...}}`, `!important`, CSS-in-JS ni variantes locales de componentes base.
- Header, toolbar, botones, fields, dialog, tabs, chips, tabla, cards, alertas y skeletons usan el DS.
- Focus visible de 4 px, targets táctiles de al menos 44×44 y orden DOM lógico.
- Upload admite teclado; input tiene label y `accept=.xlsx`, sin dropzone-only.
- Diálogos atrapan/restauran foco, Escape respeta cambios sin guardar y confirmaciones destructivas.
- Tabs usan roles/aria-controls; candidato seleccionado se anuncia.
- Estados de match incluyen etiqueta/icono además de color.
- Errores de fila/campo usan `aria-describedby`; progreso usa `role=status`.
- Tabla tiene caption accesible y headers asociados; detalles tablet anuncian expansión.
- A 200% zoom y 320 CSS px no se pierde acción ni aparece overflow horizontal de página.
- `prefers-reduced-motion` elimina transiciones no esenciales.

## Estructura esperada

```text
frontend/src/
  lib/api/
    cliente.ts
    esquemas/actualizar-cronograma.ts
    actualizar-cronograma.ts
  modules/actualizar-cronograma/
    ActualizarCronogramaPage.tsx
    useActualizarCronograma.ts
    dominio/
      normalizarActualizacion.ts
      contextoActualizacion.ts
      validarActualizacion.ts
      filtrarActualizacion.ts
      revisionAsociaciones.ts
    componentes/
      ContextoActualizacion.tsx
      ToolbarActualizacion.tsx
      DialogoImportarCronograma.tsx
      ResumenImportacion.tsx
      FiltrosActualizacion.tsx
      TablaActualizacion.tsx
      TarjetasActualizacion.tsx
      EditorActualizacion.tsx
      RevisionAsociaciones.tsx
      DialogoEliminarBorrador.tsx
      LeyendaActualizacion.tsx
    actualizar-cronograma.css
```

En PHP se espera un controlador API delgado, un resolver de contexto, parser/preview de importación,
servicio transaccional, policy pura y repositorio scopeado. No se introduce una capa genérica sin
segundo consumidor; se extraen seams sobre lógica actual.

## Pruebas y evidencia

### PHP/puras obligatorias

- Resolver base/target: inicial, próxima, draft existente, anomalía sin activa y proyecto aislado.
- Policy de acciones con permisos efectivos, draft/base y filas.
- Contrato de contexto: éxito, sólo lectura, 403, sin proyecto y response no-store.
- Parser XLSX puro: alias, flags, IDs, jerarquía, fechas/seriales, filas vacías y errores.
- Preview: tamaño, extensión, MIME, primera fecha, token y ausencia de persistencia mediante fakes.
- Confirmación: token vinculado/expirado/usado/contexto cambiado y response normalizado con store fake.
- Save: campos permitidos, fechas, unidad/cantidad/ratio, fila asociada, source/base y herencia fake.
- Matcher: umbrales configurados, orden, vacíos, identical/high/medium/none e idempotencia pura.
- Autoassociate y batch: permiso, CSRF, scope, atomicidad fake y bitácora derivada de sesión.
- Delete draft: sólo target > maxActive; activa protegida; store fake.
- Route contract: GET/HEAD SPA sólo al corte y `/api/*` nunca capturada.

Cada endpoint nuevo tiene su prueba PHP de contrato antes de implementación. Ninguna prueba de esta
entrega ejecuta SQL mutante. No se ejecutan `tests/test_schedule_update_draft_import.php` ni
`tests/test_preconstruction_import_global_ids.php`: ambos crean/eliminan proyectos y escriben
programa/semanas. Su comportamiento se caracteriza mediante servicios y fakes.

### Frontend

- Esquemas Zod estrictos para contexto, filas, preview/confirm, save, match, batch, delete y error.
- Gateway prueba método, URL, body, CSRF, FormData sin content type, cancelación y ausencia de `db`.
- Normalización de asociaciones, HTML, ratios, unidades y nombres duplicados.
- Resolver de filtro/query, búsqueda sin acentos y conteos.
- Validación compartida y payloads discriminados.
- Máquina de importación: archivo → preview → confirm → success/error/expired.
- Máquina de revisión: pendientes/procesadas/cambiar/dirty/batch/error.
- Hook: cancelación, proyecto obsoleto, reload, save por fila y dirty navigation.
- Componentes: sólo lectura/escritura, tabla/tarjetas, estados vacíos/error/loading y dialogs.
- No `fetch(` fuera de `cliente.ts` mediante contrato estático.

### Navegador con red interceptada

1. A/D: estado sin draft, template, preview, confirm y draft cargado.
2. R/DCV: mismas acciones sobre draft autorizado.
3. OT/V: lectura, filtros y retorno; cero mutaciones visibles.
4. G/S/SG/C: navegación ausente y 403 si deep link.
5. Import inicial solicita fecha, éxito no auto-redirige y botón llega a S05.
6. Reemplazo de draft muestra impacto y no doble envía.
7. Pendientes/completo, búsqueda, conteos y vacío “todo asociado”.
8. Mapeo manual hereda response; validación/error/retry/descartar.
9. Autoasociación y revisión completa, cierre dirty y batch real interceptado.
10. Eliminar draft confirma; activa protegida presenta error.
11. Cambio de proyecto cancela y descarta estado anterior.
12. 390/768/1180/1440, oscuro/claro, teclado, lector, 200% zoom y axe.
13. Todas las peticiones operativas se interceptan; ninguna llega a DML real.
14. Payloads no contienen `db`, `project_id`, usuario, rol, permiso ni umbrales autoritativos.

Las capturas visuales son candidatas no versionadas hasta aprobación explícita. No se actualiza el
golden legacy para “hacer verde” el corte.

## Requisitos UX trazables

- S06-UX-01: la pantalla nombra semana base y objetivo antes de cualquier acción.
- S06-UX-02: contexto no depende de la semana de sesión ni la modifica.
- S06-UX-03: un vacío explica cómo se llena y prioriza Cargar Excel.
- S06-UX-04: Eliminar no aparece sin draft y nunca resetea activa.
- S06-UX-05: template y upload `.xlsx` son accesibles por teclado.
- S06-UX-06: primer proyecto exige fecha inicial real.
- S06-UX-07: importación siempre tiene preview antes de persistir.
- S06-UX-08: preview declara semana, filas y reemplazo.
- S06-UX-09: confirmación/import no admite doble envío.
- S06-UX-10: éxito inicial no usa redirección por temporizador.
- S06-UX-11: Pendientes es default y Programa completo está a un control.
- S06-UX-12: búsqueda y conteos coinciden con visibles.
- S06-UX-13: asociación manual identifica source por ID estable.
- S06-UX-14: herencia se confirma desde response servidor.
- S06-UX-15: filas asociadas bloquean unidad/cantidad/avance también en servidor.
- S06-UX-16: fechas permanecen editables en draft asociado.
- S06-UX-17: autoasociación usa y explica umbrales efectivos.
- S06-UX-18: estados de match no dependen sólo de color.
- S06-UX-19: revisión ofrece estadísticas, pendientes/procesadas y cinco candidatos.
- S06-UX-20: cierre dirty no pierde decisiones sin advertir.
- S06-UX-21: guardado de revisión es transaccional y reporta conteos.
- S06-UX-22: errores conservan borrador local y ofrecen recuperación.
- S06-UX-23: tabla funciona en desktop/tablet; tarjetas editables en móvil.
- S06-UX-24: dark default y light completo usan tokens.
- S06-UX-25: sólo lectura conserva toda consulta/filtro y oculta mutaciones.
- S06-UX-26: cambio de proyecto no mezcla archivos, filas ni decisiones.
- S06-UX-27: retorno a Programa General usa router del shell.
- S06-UX-28: ninguna petición cliente aporta alcance/autorización.

## Criterios de aceptación

- S06-AC-01: `/app/programa-general-actualizar` renderiza React sin VIEW-33 durante piloto.
- S06-AC-02: contexto nuevo exige vista, `no-store` y esquema Zod/PHP.
- S06-AC-03: resolver produce base=max activa y target=draft o max activa+1 en todos los casos.
- S06-AC-04: resolver no escribe sesión.
- S06-AC-05: acciones salen del servidor y overrides no se reinterpretan en React.
- S06-AC-06: A/D/R/DCV pueden mutar; OT/V leen; G/S/SG/C reciben el resultado efectivo vigente.
- S06-AC-07: listas base/target derivan proyecto de scope y no aceptan alcance cliente.
- S06-AC-08: S06 no consume códigos ni migra el `rule_engine.js` sin caller.
- S06-AC-09: archivo >10 MiB, no XLSX/MIME inválido y workbook corrupto fallan sin persistir.
- S06-AC-10: encabezados y fechas aceptadas/rechazadas coinciden con el contrato documentado.
- S06-AC-11: primera importación requiere fecha y produce semana 1 como el legacy.
- S06-AC-12: preview no muta y token está ligado a usuario/proyecto/contexto/hash.
- S06-AC-13: confirmación revalida contexto y es transaccional/single-use.
- S06-AC-14: draft nuevo/reemplazado devuelve conteos y dispara autoassociate una vez.
- S06-AC-15: initial-created ofrece retorno explícito a S05 sin temporizador.
- S06-AC-16: Pendientes/completo, búsqueda y cuatro conteos tienen pruebas puras/componentes.
- S06-AC-17: tabla muestra nueve columnas contractuales en desktop y detalles en tablet.
- S06-AC-18: móvil muestra tarjetas editables; no monta tabla oculta.
- S06-AC-19: mapeo usa source ID validado y almacena nombre derivado por compatibilidad.
- S06-AC-20: asociación aplica herencia legacy y devuelve fila completa.
- S06-AC-21: fechas/cantidad/unidad/avance pasan validación cliente y servidor.
- S06-AC-22: fila asociada rechaza cambios ordinarios salvo fechas/asociación.
- S06-AC-23: autoassociate conserva algoritmo y umbrales configurados, no 0,80/0,50 locales.
- S06-AC-24: idénticas/altas se aplican sólo a filas libres e idempotentemente.
- S06-AC-25: revisión conserva estadísticas, tabs, candidatos, aceptar/skip/cambiar y dirty guard.
- S06-AC-26: batch valida todo, aplica todo o nada y retorna filas/conteos.
- S06-AC-27: bitácora deriva proyecto/usuario/sugerencia/confianza en servidor.
- S06-AC-28: delete sólo borra draft superior a máxima activa; activa devuelve 409 sin escritura.
- S06-AC-29: loading, vacío, error, partial base, sólo lectura, saving y success son distinguibles.
- S06-AC-30: no hay fetch directo en componentes, retry automático de mutaciones ni payload de scope.
- S06-AC-31: 390/768/1180/1440 en oscuro/claro no pierden capacidad ni tienen overflow de página.
- S06-AC-32: teclado, foco, dialogs, tabs, live regions, 200% zoom y axe pasan con red interceptada.
- S06-AC-33: cada endpoint nuevo tiene contrato PHP y esquema Zod fallando antes de código productivo.
- S06-AC-34: verificación S06 no ejecuta DDL/DML ni suites existentes que escriben.
- S06-AC-35: canonical cut sucede sólo tras gates y conserva rollback por reversión de ruta.
- S06-AC-36: retiro busca consumidores antes de borrar aliases/assets compartidos.
- S06-AC-37: RLS, schema, grants, usuarios, credenciales y `/admin/` no cambian.

## Entregas verticales

### Entrega 1 — Contexto y lectura responsive

- Resolver único base/target y policy de acciones.
- Contexto tipado, lecturas base/target y normalización.
- Empty/loading/error/read-only.
- Pendientes/completo, búsqueda, conteos.
- Tabla desktop/tablet y tarjetas móviles de sólo lectura.

**Gate:** un viewer autorizado entiende qué borrador existe y puede inspeccionarlo en cuatro
viewports/ambos temas, sin petición mutante.

### Entrega 2 — Preview y confirmación XLSX

- Parser extraído y caracterizado.
- Upload seguro 10 MiB y preview.
- Token efímero vinculado.
- Confirmación transaccional detrás de seam fake.
- Flujos initial/draft y feedback.

**Gate:** con red/fakes, import inicial/nuevo/reemplazo y todos los errores son demostrables sin
ejecutar DML en verificación.

### Entrega 3 — Edición y mapeo manual

- Mutación dedicada.
- Selector base por ID estable y herencia.
- Fechas/unidad/cantidad/avance.
- Tabla/tarjetas editables, save state y navegación dirty.

**Gate:** una fila puede mapearse/editarse y recuperarse de error con autorización efectiva.

### Entrega 4 — Autoasociación, revisión y eliminación

- Matcher service/response tipado con umbrales reales.
- Aplicación automática y bitácora servidor.
- Revisión pending/processed y batch atómico.
- Delete draft protegido.

**Gate:** todo flujo de reconciliación y descarte está cubierto por contratos/fakes/intercepts.

### Entrega 5 — Corte y retiro legacy

- A11y/RBAC/responsive/tema y visual candidate aprobado.
- GET/HEAD canónico a React.
- Búsqueda de consumidores y retiro de VIEW-33/JS/CSS/aliases exclusivos.
- Conservación de template, lista S05 y editor compartido si otro módulo lo usa.

**Gate:** ruta canónica React, rollback verificable, tests verdes, diff acotado y aprobación visual
explícita antes de tocar goldens.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| importación masiva y destructiva | preview, token, confirmación, contexto revalidado y transacción |
| base/target distintos entre UI y API | resolver único consumido por todas las operaciones |
| fuga entre proyectos | scope servidor; cliente no envía prefijo/project ID; contratos negativos |
| permiso equivocado por endpoint compartido | mutaciones S06 dedicadas con `actualizar.editar` |
| nombres duplicados como asociación | source ID estable; nombre sólo derivado para almacenamiento |
| umbrales UI/backend divergentes | thresholds en response, un solo servicio |
| N mutaciones y bitácora incompleta | lote atómico y logger servidor |
| doble importación | controles en vuelo + token single-use |
| archivo hostil/grande | 10 MiB, extensión/MIME, parser encapsulado, error seguro |
| perder borradores locales | estado dirty, guard y recovery explícito |
| tablet/móvil vacíos como legacy | tabla 768+ y tarjetas <768 con pruebas |
| borrar semana activa | action y guard servidor draft-only |
| romper rollback | aliases/vista sobreviven al piloto; corte separado |
| baseline visual engañoso | fixtures con filas/estados; aprobación antes de versionar |
| efecto de maestro `programa` al borrar | limitación legacy documentada; no prometer rollback inexistente |

## Decisiones descartadas

- Envolver VIEW-33 o Handsontable en iframe/componente: conserva globals y no resuelve responsive.
- Reutilizar `/api/general/update` con flags: mezcla permisos y payload amplio.
- Mantener `db`/`proyecto_id` “por compatibilidad”: scope cliente no es autorización.
- Portar `rule_engine.js`: está cargado pero no tiene caller; duplicaría matcher servidor.
- Ejecutar autoasociación en TypeScript: duplicaría umbrales/algoritmo y trazabilidad.
- Hacer importación en una sola llamada: no permite explicar impacto ni evitar doble envío.
- Guardar revisión con N requests: no es lote ni garantiza bitácora completa.
- Conservar breakpoint 1180 para tarjetas: el legacy no genera tarjetas y deja la vista vacía.
- Mostrar código de actividad: el legacy no expone columna; S05 es el editor canónico.
- Soft reset de semana activa desde Eliminar Actualización: contradice la etiqueta y aumenta alcance.
- Regenerar goldens antes del gate: requeriría aprobación explícita.

## Decisiones pendientes

No hay decisiones de negocio, producto, estrategia o PM pendientes para implementar la paridad v0 de
S06. La posible restauración de `programa` maestro al eliminar un draft es una decisión de dominio
fuera del corte React y no bloquea; el v0 conserva el comportamiento existente y lo documenta.

## Siguiente gate

Invocar `superpowers:writing-plans` para producir
`docs/superpowers/plans/2026-08-30-s06-actualizar-cronograma-react.md`, con TDD, archivos exactos,
contratos sin DML, checkpoints verticales, gate visual explícito, corte reversible y retiro por
búsqueda de consumidores. No implementar antes de cerrar y autorrevisar ese plan.
