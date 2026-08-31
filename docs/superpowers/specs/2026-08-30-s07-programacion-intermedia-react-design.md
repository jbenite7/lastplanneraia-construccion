---
capa: fuente
tipo: spec
estado: vigente
id: S07
fecha: 2026-08-30
superficie: programacion-intermedia
rutas: ["/programacion-intermedia"]
depende_de: [T01, T02, S04, S05]
views: [VIEW-35]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramacionIntermediaController, guardar_programacion_intermedia.php, RestrictionConfigResolver, GeneralApiController, ReportController, LpsApiController, VIEW-35, hot.js, stateMachine.js, CSS, pruebas, RBAC, S05 y frontend actual en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S07 de Programación Intermedia a React: look-ahead de seis semanas, ocho estados operativos, restricciones dinámicas, asignaciones, edición individual y compartida, filtros, CSV/XLSX, drawer, tabla y tarjetas accesibles en oscuro/claro, sin modificar RLS ni datos durante la fase documental."
---

# S07 — Programación Intermedia en React

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
- [[docs/superpowers/specs/2026-08-30-s05-programa-general-react-design|S05 — Programa General React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

T01 posee sesión, proyecto, semana, sidebar, tema y navegación. S04 posee el cambio de proyecto. S05
posee el adaptador base de actividad y el contrato inicial del drawer contextual. T02 será dueño del
componente compartido definitivo del drawer, no S07. Programación Intermedia sólo posee su universo
de look-ahead, restricciones, asignaciones, estados, operaciones y la integración contextual de una
actividad seleccionada.

VIEW-35 es la única vista propiedad de S07. `views/partials/drawer_unificado.php` es VIEW-28 y
pertenece a T02; no se elimina al cortar S07 mientras Programa General, Programación Semanal o
Escalamientos conserven consumidores legacy.

## Resultado buscado

`/programacion-intermedia` será una superficie React que conserva, como mínimo, toda capacidad útil
y comportamiento observable del módulo PHP/JS actual:

1. usa el proyecto y la semana activos del shell y se reinicia de forma segura cuando cambian;
2. carga el look-ahead de actividades no terminadas con fechas válidas;
3. muestra por defecto la ventana de hasta seis semanas y permite “ver todas” cuando la acción
   efectiva lo autoriza;
4. explica y filtra los ocho estados operativos con conteos de la ventana de seis semanas;
5. representa las restricciones duras y blandas configuradas para Construcción o Preconstrucción;
6. permite buscar y filtrar por actividad, estado, semanas, habilitación, asignaciones y restricción
   pendiente;
7. permite agrupar por gravedad sin alterar la identidad ni el orden estable de actividades iguales;
8. edita subcontratista, Responsable AIA, observaciones y restricciones cuando corresponda;
9. impide restricciones sin Responsable AIA y explica cómo desbloquear la actividad;
10. valida porcentajes, cantidades discretas, valores `N/A`, semana y contexto antes de guardar;
11. guarda una actividad y devuelve su estado completo recalculado por el servidor;
12. selecciona actividades y previsualiza/aplica restricciones o asignaciones compartidas en lote;
13. permite recargar datos y catálogos, exportar CSV y descargar el corte XLSX autorizado;
14. integra diagnóstico, comentarios, respuestas, menciones, SOS y crisis del drawer compartido;
15. usa tabla semántica responsive en desktop/tablet y tarjetas plenamente editables en móvil;
16. distingue carga, vacío, filtros sin coincidencias, sólo lectura, confirmación, error y recuperación;
17. ofrece capacidad equivalente en tema oscuro y claro y es operable con teclado, zoom y lector de
   pantalla.

Paridad no obliga a conservar Handsontable, jQuery, Bootstrap, TomSelect, Toastr, Font Awesome,
HTML inyectado, filtros mutantes por GET ni defectos del legacy. React conserva intención, dato,
autorización y resultado; puede mejorar seguridad, claridad, accesibilidad y recuperación.

## Alcance

### Incluido

- Ruta piloto y ruta canónica React de Programación Intermedia.
- Contexto cohesivo de proyecto/semana, configuración, catálogos, acciones, enlaces y CSRF.
- Lista normalizada de actividades reales; un resultado vacío es `[]`, nunca una fila ficticia.
- Ventana de seis semanas, modo ver todas, conteos y ocho estados.
- Restricciones dinámicas para Construcción y Preconstrucción desde un resolver único.
- Búsqueda, filtros combinables, agrupación por gravedad, selección y conteo visible.
- Edición individual de asignaciones, observaciones y restricciones.
- Política efectiva de semana confirmada, semana histórica y Responsable AIA.
- Preview y aplicación transaccional de restricciones/asignaciones compartidas.
- Recarga ordinaria, recarga explícita de catálogos, CSV local y reporte XLSX.
- Tabla desktop/tablet y tarjetas móviles con capacidad de edición equivalente.
- Integración del drawer compartido con `modulo="PI"` y digest local autorizado.
- Tema oscuro/claro, teclado, foco, live regions, reduced motion y zoom 200 %.
- Contratos PHP, Zod, pruebas puras, componentes y navegador con red interceptada.
- Convivencia con VIEW-35 durante piloto y retiro de piezas exclusivas tras el corte.

### Fuera de alcance

- `/admin/` y cualquier vista, permiso, dependencia o ruta administrativa.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, índices, grants, usuarios, credenciales,
  roles, membresías o datos.
- Ejecutar DDL/DML durante esta fase documental o durante las verificaciones descritas en el plan.
- Cambiar el significado de semana activa/confirmada; T01 gobierna selección y el backend vigente
  conserva el lock de compromisos.
- Migrar Programa General, Programación Semanal, Profesionales, Subcontratistas, BI o Escalamientos
  dentro de S07.
- Crear o editar catálogos desde S07. Sus enlaces sólo navegan a S13/S14 o al legacy mientras esos
  módulos no hayan cortado.
- Agregar columnas, locks optimistas o tablas de auditoría nuevas.
- Rediseñar las notificaciones/escalamientos del lote más allá de extraer una frontera comprobable.
- Convertir `pi_shared_constraints` o sus enlaces en un módulo de gestión; BI conserva su dominio.
- Retirar el drawer compartido, sus endpoints o aliases mientras existan otros consumidores.
- Regenerar goldens visuales sin aprobación explícita.

## Punto de partida medido

### React

- No existe página, módulo, esquema Zod, gateway ni dominio de Programación Intermedia.
- La sidebar enlaza `/programacion-intermedia` como documento legacy y calcula visibilidad con una
  matriz local de roles, que oculta indebidamente el módulo a perfiles con lectura efectiva.
- El router autenticado sólo compone shell/proyecto; no reconoce una ruta S07.
- `frontend/src/lib/api/cliente.ts` es la única frontera autorizada para HTTP y S07 debe consumir las
  extensiones de errores, formulario y cancelación definidas en T01/S05.

### Legacy

| Pieza | Medición |
|---|---|
| Vista | `views/programacion-intermedia/programacion_intermedia.view.php`, 373 líneas |
| Controlador | `ProgramacionIntermediaController`, 1.194 líneas |
| Guardado individual | `src/Legacy/guardar_programacion_intermedia.php` |
| Interacción | `public/js/modules/programacion_intermedia/hot.js`, 5.687 líneas |
| Estado cliente | `stateMachine.js`, resolver global duplicado |
| Presentación | `public/css/programacion-intermedia.css`, 1.818 líneas |
| Grid | Handsontable con diez columnas visibles y popover de habilitación |
| Responsive | tabla a partir de 1180 px; tarjetas bajo 1180 px |
| Evidencia visual | dark 1180×820 y 1440×900; no existe golden light aprobado |

La vista carga jQuery, Bootstrap, Handsontable, TomSelect, Font Awesome y globals del shell/drawer.
El estado se deriva tanto en PHP legacy como en JavaScript. El controlador selecciona `*`, mezcla
lectura, sesión, SQL, batch, tracking y notificaciones, y devuelve errores operativos con HTTP 200.

### Datos incluidos actualmente

La lista selecciona actividades de la semana activa que cumplen todas estas condiciones:

```text
Fecha_Inicio IS NOT NULL
Fecha_Fin IS NOT NULL
Ejecutado < 1
Titulo = 0
```

En el modo predeterminado agrega `Semanas_Inicio <= 6`; “ver todas” elimina sólo esa barrera. El
orden inicial es `Semanas_Inicio ASC`. No se incluyen capítulos ni actividades terminadas. Un error
de consulta se registra y se presenta como lista vacía; una lista vacía real se sustituye por una
fila de strings vacíos. S07 distingue ambos casos y elimina la fila ficticia.

### Columnas y acciones observadas

| Columna/acción | Legacy | S07 React |
|---|---|---|
| Id | lectura | lectura |
| Lote | checkbox local | selección accesible por fila/tarjeta |
| Actividad | lectura | lectura con búsqueda y texto seguro |
| Subcontratista | multi-select editable | selector tipado; creación sólo por enlace autorizado |
| Responsable AIA | single-select editable | selector tipado y guard de restricciones |
| Semanas para inicio | lectura | lectura y filtro por bandas |
| Ejecutado | lectura | ratio/porcentaje normalizado |
| Habilitación | popover con restricciones | resumen + editor de todas las restricciones aplicables |
| Estado operativo | celda/detalle local | chip, explicación y entrada al drawer contextual |
| Observaciones | texto editable | textarea con validación y estado de guardado |

El popover de habilitación permite anterior/siguiente, filtrar una restricción pendiente, editar
restricciones y deshacer una sola operación local. S07 conserva navegación entre actividades,
mantiene el editor abierto después de guardar y reemplaza el undo opaco por edición local cancelable:
antes de guardar se revierte localmente; después de guardar, una reversión es otra mutación explícita.

La barra actual ofrece Leyenda, Descargar corte, Exportar CSV, Recargar, Agrupar por gravedad, Ver
todas, Restricción compartida, Listas, Seleccionar visibles, Limpiar selección, enlace BI, conteo de
seleccionadas y estado de guardado. Todas permanecen cuando la acción efectiva corresponde.

### Responsive observado y corrección de paridad

El legacy cambia a tarjetas bajo 1180 px. Las tarjetas muestran identificación, actividad, capítulo,
restricciones duras, Responsable, subcontratista y semanas; sólo permiten editar restricciones. No
permiten editar subcontratista, Responsable ni observaciones, aunque esas capacidades existen en la
tabla. La petición aprobada exige tabla responsive en desktop/tablet y tarjetas editables en móvil.

S07 fija:

- `>=1180`: tabla amplia con todas las columnas;
- `768..1179`: tabla semántica dentro de un contenedor propio, columnas prioritarias y detalle por
  fila; puede desplazarse internamente, pero la página no tiene overflow horizontal;
- `<768`: tarjetas, sin tabla/Handsontable ocultos, con asignaciones, observaciones, restricciones,
  lote y drawer disponibles.

La prueba `programacion-intermedia-mobile-runtime.mjs` que exige una instancia HOT escondida quedó
superada por `programacion-movil-sin-grilla.mjs`, que exige cero nodos de grilla en móvil. React adopta
la intención más reciente y no monta una tabla oculta.

## Inventario HTTP auditado

| Método | Ruta actual | Contrato/uso actual | Disposición S07 |
|---|---|---|---|
| GET | `/programacion-intermedia` | VIEW-35; sólo `requireAuth()` | piloto React, luego canónica con permiso PI de lectura |
| GET | `/api/pi/list` | `{data:[SELECT *]}`; scope/semana/filtros desde cliente/sesión | alias legacy; nueva lista tipada y scoped |
| POST | `/api/pi/save` | formulario amplio; delega a script legacy | alias legacy; mutación estrecha JSON/CSRF |
| POST | `/programacion-intermedia/filtros` | conteos de seis semanas y flags de sesión | no consumir; conteos en nueva lista |
| GET | `/programacion-intermedia/set-filtro` | muta sesión sin permiso de vista | retirar con VIEW-35; filtros React no mutan servidor |
| GET | `/programacion-intermedia/set-view-all` | muta sesión; exige edición; sin CSRF | alias legacy; sustituir por POST/CSRF |
| POST | `/programacion-intermedia/shared-constraints/preview` | preview con `db`, semana y payload flexible | alias legacy; nueva ruta strict/scoped |
| POST | `/programacion-intermedia/shared-constraints/apply` | batch, tracking y notificaciones | alias legacy; servicio transaccional detrás de contrato |
| GET | `/api/general/restriction-config` | configuración por área; sólo autenticación | resolver compartido; contexto S07 exige lectura PI |
| GET/POST | `/api/profesionales/list` | catálogo; GET ejecuta sync con DML oculto | contexto lee; refresh explícito concentra sync |
| GET/POST | `/api/subcontratistas/list` | catálogo scoped | contexto lee; refresh explícito devuelve ambos catálogos |
| POST | `/reportes/restricciones` | genera XLSX y devuelve `{url}` | alias; wrapper S07 scoped, CSRF y respuesta estricta |
| GET | `/api/lps/comments` | comentarios de actividad | conservar; T02 tipa y comparte |
| POST | `/api/lps/comments/add` | comentario/respuesta/menciones | conservar; T02 tipa y comparte |
| POST | `/api/lps/crisis/register` | crisis/SOS | conservar; S07 envía `modulo=PI` |
| POST | `/api/lps/crisis/close` | cierre con justificación mínima | conservar; T02 comparte |
| POST | `/api/lps/comments`, `/api/lps/crisis` | aliases legacy | no consumir; T02 retira sólo tras todos los callers |
| GET | `/api/session` | shell, navegación, proyecto y semana | propiedad T01; S07 consume |
| POST | `/context/week` | cambia semana activa | propiedad T01; S07 reacciona al resultado |
| GET | `/bi/programacion-intermedia` | enlace BI contextual | sólo navegación si servidor autoriza |

## Permisos y capacidades

### Matriz fallback observada

| Perfil canónico | Ver PI | Editar PI | Reporte | Resultado S07 |
|---|---:|---:|---:|---|
| A, D | Sí | Sí | Sí | edición actual e histórica autorizada |
| R, DCV | Sí | Sí | Sí | edición actual; histórica bloqueada |
| OT | Sí | No | Sí | lectura, CSV y XLSX |
| G, S, SG | Sí | No | No | lectura y CSV; hoy la sidebar React puede ocultarlos |
| V | Sí | No | Sí | lectura, CSV y XLSX |
| C | No | No | No | sin navegación y 403 |

La matriz documenta fallbacks actuales, no reemplaza RBAC. Overrides configurables pueden cambiar
el resultado. PHP entrega acciones efectivas; React no compara roles, cargos ni aliases.

### Contradicciones que S07 cierra

1. La página sólo exige autenticación; la ruta canónica exige `lps.programacion_intermedia.ver`.
2. La sidebar React usa roles y oculta PI a G/S/SG pese a su lectura; T01 sólo envía navegación
   autorizada por servidor.
3. El cliente permite editar a A/D/R/DCV y bloquea semanas históricas a R/DCV, pero el endpoint no
   aplica esa segunda regla; la política server-side la hace autoritativa.
4. El guard de semana confirmada existe en mutaciones, pero la UI también deduce el lock; React usa
   acciones y maneja un `409 WEEK_CONFIRMED` si el contexto cambió.
5. Guardar restricciones sin Responsable se bloquea en servidor, pero asignaciones/observaciones sí
   pueden guardar; la respuesta de acciones por fila lo expresa.
6. `setFilter()` muta sesión sin permiso y no tiene caller actual; React no lo usa.
7. `setViewAll()` muta por GET sin CSRF. React usa POST, conserva el requisito de edición y no
   amplía la función a lectores.
8. `restrictionConfig()` sólo exige sesión. El contexto S07 exige lectura PI.
9. La lista calcula estado con campos hardcodeados de Construcción y diverge en Preconstrucción;
   S07 usa `RestrictionConfigResolver` para lista, preview, guardado y batch.
10. El drawer aplica permisos de Programación Semanal y busca a veces sólo área Construcción. T02
    resuelve acciones del drawer y el proyecto activo para ambas áreas; S07 sólo consume el contrato.
11. El reporte acepta `db`/semana del navegador. El wrapper S07 deriva ambos del scope/sesión.
12. El catálogo de profesionales muta durante GET. S07 separa lectura inicial de refresh explícito.

### Acciones efectivas

El contexto expone, como booleanos ya resueltos:

```text
editAssignments     subcontratista, Responsable y observaciones
editRestrictions    restricciones de una fila con Responsable
selectBatch         selección local; siempre true para un viewer
applyBatch          preview/aplicación compartida
toggleViewAll       persistir ver todas
refreshCatalogs     sincronizar/recargar catálogos
exportCsv           exportar filas visibles localmente
downloadReport      generar corte XLSX
openBi              navegar al BI
readDrawer          abrir diagnóstico/comentarios
writeDrawer         comentar, SOS y cerrar crisis
```

`editAssignments`, `applyBatch` y `toggleViewAll` requieren la capacidad efectiva de edición y una
semana no confirmada. `editRestrictions` agrega Responsable AIA por fila. `downloadReport` depende
de `lps.reportes.generar`. `readDrawer`/`writeDrawer` pertenecen a T02 y conservan las capacidades
efectivas del backend, aunque no coincidan con edición PI. `selectBatch` no muta y puede permanecer
disponible a lectores; `applyBatch` decide si la selección puede operar.

Para una semana histórica, sólo A/D conservan mutaciones según el comportamiento observable del
cliente. La policy recibe el rol normalizado del servidor únicamente para aplicar esta excepción
vigente; ese dato no viaja a React.

## Semántica de restricciones

### Configuración única

`RestrictionConfigResolver` se convierte en la fuente única para lectura, cálculo y validación.

Construcción:

| Clave | Tipo | Umbral | Valores permitidos |
|---|---|---:|---|
| `D_y_E` | dura | 100 % | 0, 33, 66, 100, N/A |
| `Materiales` | dura | 100 % | 0, 33, 66, 100, N/A |
| `MdeO` | dura | 100 % | 0, 33, 66, 100, N/A |
| `Equipos` | dura | 100 % | 0, 33, 66, 100, N/A |
| `Predecesora` | dura | 50 % | 0, 50, 100, N/A |
| `Pdto_Cons` | blanda | 100 % | 0, 50, 100, N/A |
| `Modelo` | blanda | 100 % | 0, 50, 100, N/A |

Preconstrucción:

| Clave | Tipo | Umbral | Valores permitidos |
|---|---|---:|---|
| `restriccion_pc_1` | dura | 50 % | 0, 50, 100, N/A |
| `restriccion_pc_2` | blanda si tiene nombre | 100 % | 0, 50, 100, N/A |
| `restriccion_pc_3` | blanda si tiene nombre | 100 % | 0, 50, 100, N/A |
| `restriccion_pc_4` | blanda si tiene nombre | 100 % | 0, 50, 100, N/A |

Las etiquetas de PC 2–4 provienen del proyecto. Una restricción sin nombre no se expone ni acepta.
El navegador envía la clave pública exacta del contexto; el servidor rechaza claves, valores o
tipos que no pertenezcan al área activa. No acepta columnas SQL arbitrarias.

`N/A` satisface una restricción individual para habilitación. La habilitación usa sólo restricciones
duras y sus umbrales. `Estado_Restricciones` es un agregado informativo de duras y blandas, excluye
`N/A` del denominador y vale 1 si ninguna aplica. Las blandas nunca bloquean el estado “Listo para
Comprometer”.

### Ocho estados operativos

El servidor calcula un `state.id`, severidad y detalle; React no reclasifica filas para autorizar.

| ID | Etiqueta | Regla resumida | Severidad |
|---|---|---|---|
| `blocked-overdue-critical` | RC inicio vencido | sin iniciar, SI < 0, no habilitada, ruta crítica | urgent |
| `blocked-overdue` | Inicio vencido | sin iniciar, SI < 0, no habilitada | urgent |
| `blocked-due` | Inicio por Habilitar | sin iniciar, SI = 0, no habilitada | urgent |
| `alert-1-week` | Alistamiento Urgente | sin iniciar, SI = 1, no habilitada | attention |
| `alert-2-3-weeks` | Alistamiento en Riesgo | sin iniciar, SI 2..3, no habilitada | attention |
| `alert-4-6-weeks` | Alistamiento Pendiente | sin iniciar, SI 4..6, no habilitada | healthy |
| `execution-blocked` | En Ejecución Pendiente | 0 < ejecutado < 1 y no habilitada | urgent |
| `liberated-control` | Listo para Comprometer | iniciada o no, todas las duras cumplen | healthy |

`neutral` queda como fallback para actividades de “ver todas” fuera de la ventana u otra combinación
no operativa; no aparece como chip principal de los ocho conteos. `header` no entra porque S07
filtra `Titulo=0` en origen. La presentación consume los tokens semánticos registrados en
`docs/design-system/state-semantics.json`; no crea colores por estado en CSS local.

Agrupar por gravedad produce `urgent > attention > healthy > neutral` y conserva orden estable por
`Semanas_Inicio` dentro de cada nivel.

## Decisiones de arquitectura

### PI-R1 — Frontera scoped y contratos dedicados

S07 agrega ocho endpoints:

```text
GET  /api/programacion-intermedia/context
GET  /api/programacion-intermedia/activities
POST /api/programacion-intermedia/activity
POST /api/programacion-intermedia/view
POST /api/programacion-intermedia/shared/preview
POST /api/programacion-intermedia/shared/apply
POST /api/programacion-intermedia/catalogs/refresh
POST /api/programacion-intermedia/report
```

El navegador nunca envía `db`, prefijo, tabla, `project_id`, proyecto, área, rol, capacidad, usuario
ni semana máxima. PHP deriva alcance de la sesión y `ProjectScope`. `week` puede viajar sólo como
guard de respuesta obsoleta: debe coincidir con la semana activa o falla `409 STALE_CONTEXT`.

Los aliases legacy sobreviven durante el piloto. Ningún endpoint nuevo llama el controlador grande
como si fuera servicio; la lógica se extrae detrás de stores/servicios comprobables.

### PI-R2 — Lectura normalizada y una sola fuente de estado

La lista deja de devolver `SELECT *`. Un `ProgramacionIntermediaStateResolver` puro recibe la fila y
la configuración de `RestrictionConfigResolver`, calcula habilitación/agregado/estado y produce un
DTO estable. Guardado y batch devuelven el mismo DTO. Fixtures PHP/TS compartidos prueban ambas áreas
y todas las fronteras de semanas/avance; TypeScript presenta el estado recibido y sólo calcula
filtros/orden local.

### PI-R3 — Mutación estrecha con merge servidor

El guardado individual recibe `activityId`, `week` y `changes`. El servidor lee la fila vigente,
valida claves permitidas, aplica las guardas, mezcla sólo el cambio autorizado, recalcula y devuelve
la fila completa. No se reenvía una fila completa obsoleta ni se sobrescriben campos que el usuario
no editó.

No se agrega versión de fila. Las mutaciones son secuenciales por actividad; mientras una está en
vuelo, sólo esa actividad queda bloqueada. No hay retry automático. Un error restaura el último DTO
confirmado y permite reintentar explícitamente.

### PI-R4 — Preview informativo y aplicación siempre revalidada

El preview no persiste ni reserva filas. Devuelve actividades encontradas/faltantes, valores
actuales, conflictos, filas sin Responsable y resultado previsto. Al confirmar, el servidor vuelve
a leer todas las filas, configuración, permisos, semana y Responsable dentro de la operación. La
aplicación es todo-o-nada para las actualizaciones de programa; el tracking legacy puede estar no
disponible y, en ese caso, el resultado incluye una advertencia visible `trackingEnabled=false`.

No se inventa un token de preview ni una nueva tabla. Si el contexto cambió, apply falla y exige un
nuevo preview.

### PI-R5 — Filtros de pantalla, vista persistida

Los filtros ordinarios viven en URL/estado de pantalla y nunca mutan sesión. `viewAll` sí conserva la
preferencia legacy en sesión mediante POST/CSRF porque altera el universo cargado. Al cambiar
proyecto o semana, React cancela requests, limpia selección, drawer y filtros transitorios, y carga
el nuevo contexto; la preferencia `viewAll` sólo permanece si el servidor la devuelve.

### PI-R6 — Drawer compartido, no fork local

S07 consume el contrato T02/S05. Al seleccionar una actividad entrega un contexto normalizado con
`module="PI"`, identificadores, semana, estado, restricciones, responsables, contactos y acciones
efectivas. El drawer conserva comentario, respuesta, menciones, SOS, crisis, cierre de 100 caracteres,
simulación/copia y navegación BI. El digest semanal se calcula localmente sobre el dataset PI ya
autorizado; no hace una consulta de alcance adicional.

## Contratos backend

### Contexto

```text
GET /api/programacion-intermedia/context
Permiso: lps.programacion_intermedia.ver
Cache-Control: no-store
```

Respuesta mínima:

```json
{
  "success": true,
  "data": {
    "project": {"id": 73, "name": "Proyecto", "area": "Construccion"},
    "week": {"number": 18, "max": 20, "confirmed": false, "historical": true},
    "view": {"all": false, "windowWeeks": 6},
    "actions": {
      "editAssignments": true,
      "editRestrictions": true,
      "selectBatch": true,
      "applyBatch": true,
      "toggleViewAll": true,
      "refreshCatalogs": true,
      "exportCsv": true,
      "downloadReport": true,
      "openBi": true,
      "readDrawer": true,
      "writeDrawer": true
    },
    "restrictions": [],
    "catalogs": {"subcontractors": [], "professionals": []},
    "csrf": {"programacionIntermedia": "<token>", "drawer": "<token>"},
    "links": {
      "bi": "/bi/programacion-intermedia",
      "subcontractors": "/subcontratistas",
      "professionals": "/profesionales"
    }
  }
}
```

Los tokens anteriores son marcadores documentales; fixtures/snapshots nunca contienen tokens
operativos. Catálogos exponen sólo identificador público/nombre/estado necesario para el selector.
Los enlaces son `null` si la navegación efectiva no los autoriza. Si no hay semana válida, `number=0`,
catálogos/configuración pueden cargar, la lista es vacía y todas las acciones mutantes son falsas.

### Lista y conteos

```text
GET /api/programacion-intermedia/activities?week=<int>
Permiso: lps.programacion_intermedia.ver
```

Respuesta:

```json
{
  "success": true,
  "data": {
    "week": 18,
    "viewAll": false,
    "items": [],
    "counts": {
      "windowTotal": 0,
      "byState": {
        "blocked-overdue-critical": 0,
        "blocked-overdue": 0,
        "blocked-due": 0,
        "alert-1-week": 0,
        "alert-2-3-weeks": 0,
        "alert-4-6-weeks": 0,
        "execution-blocked": 0,
        "liberated-control": 0
      }
    }
  }
}
```

`counts` siempre se calcula sobre el universo de seis semanas antes de filtros React, aun con
`viewAll=true`. La UI muestra aparte `visibleCount` calculado sobre `items` filtrados y la etiqueta
“Conteos: ventana 6 semanas” cuando se ven actividades futuras.

Fila normalizada:

```json
{
  "activityId": 812,
  "programId": "A-104",
  "activity": "Instalar refuerzo",
  "chapter": "Estructura",
  "weeksToStart": 2,
  "startDate": "2026-09-14",
  "finishDate": "2026-09-18",
  "critical": true,
  "executedRatio": 0,
  "restrictionRatio": 0.44,
  "ready": false,
  "subcontractors": ["Proveedor Uno"],
  "responsible": "Profesional Uno",
  "observations": "",
  "restrictions": [{"key": "D_y_E", "value": 0.66, "met": false}],
  "state": {"id": "alert-2-3-weeks", "severity": "attention", "label": "Alistamiento en Riesgo"},
  "actions": {"editAssignments": true, "editRestrictions": true, "selectBatch": true}
}
```

`activityId` es el identificador estable de persistencia (`unique_id` con fallback legacy resuelto
en servidor); `programId` es el Id legible. La salida nunca incluye prefijo, tabla o `project_id`.
Fechas son ISO o `null`; ratios son 0..1; una restricción `N/A` usa `value="NA"`.

### Guardado individual

```text
POST /api/programacion-intermedia/activity
Content-Type: application/json
X-CSRF-Token: contexto.csrf.programacionIntermedia
Permiso: lps.programacion_intermedia.editar + policy de fila
```

```json
{
  "week": 18,
  "activityId": 812,
  "changes": {
    "subcontractors": ["Proveedor Uno"],
    "responsible": "Profesional Uno",
    "observations": "Texto",
    "restrictions": [{"key": "D_y_E", "value": 1}]
  }
}
```

`changes` exige al menos una clave. Sólo admite las cuatro anteriores; nombres se validan contra los
catálogos activos o se preservan cuando ya estaban almacenados y no se modifican. Observaciones se
recortan y limitan a 2.000 caracteres. Subcontratistas se normalizan como lista única no vacía; el
adaptador legacy serializa al formato persistido existente. Responsable vacío se representa `null`.
Restricciones aceptan sólo claves/valores del contexto.

Si `changes.restrictions` no está vacío y la fila resultante carece de Responsable, responde
`422 RESPONSIBLE_REQUIRED`. Un mismo request puede asignar Responsable y restricciones y sí pasa.
Cambiar sólo asignaciones u observaciones no queda bloqueado por esa regla. La respuesta exitosa es
la fila completa normalizada y un mensaje de guardado.

### Ver todas

```text
POST /api/programacion-intermedia/view
Payload: {"week":18,"viewAll":true}
CSRF + lps.programacion_intermedia.editar + semana no confirmada
```

Actualiza sólo `$_SESSION['pi_view_all']` y devuelve `{success:true,data:{viewAll:true}}`. No escribe
base de datos. React limpia selección y recarga lista/conteos. Los lectores conservan la vista
predeterminada de seis semanas; S07 no amplía el permiso legacy.

### Catálogos

```text
POST /api/programacion-intermedia/catalogs/refresh
Payload: {"week":18}
CSRF + acción efectiva refreshCatalogs
```

La lectura inicial del contexto no sincroniza ni escribe. El refresh explícito invoca la frontera
existente de sincronización de profesionales y vuelve a leer ambos catálogos scoped. La respuesta
puede marcar `professionals.status="error"` o `subcontractors.status="error"` de forma independiente;
la lista PI no desaparece por un error parcial. El contrato PHP usa un sincronizador fake y no
ejecuta DML.

### Preview compartido

```text
POST /api/programacion-intermedia/shared/preview
CSRF + lps.programacion_intermedia.ver
```

```json
{
  "week": 18,
  "activityIds": [812, 813],
  "applyRestrictions": true,
  "restrictions": [{"key": "D_y_E", "value": 1}],
  "applyAssignments": true,
  "subcontractors": ["Proveedor Uno"],
  "responsible": "Profesional Uno",
  "note": "Liberación conjunta"
}
```

`activityIds` contiene 2..500 enteros positivos únicos. Al menos una de `applyRestrictions` o
`applyAssignments` debe ser verdadera. Cada grupo aplicado debe contener un cambio. `note` se
recorta y limita a 1.000 caracteres. Preview devuelve:

- IDs solicitados, encontrados y faltantes;
- valor actual y previsto por actividad;
- conflictos de valores/asignaciones;
- actividades que seguirían sin Responsable;
- restricciones normalizadas y estado previsto;
- `canApply` y razones bloqueantes.

Preview no escribe, no crea tracking, no emite notificaciones y no concede permiso de apply.

### Aplicación compartida

```text
POST /api/programacion-intermedia/shared/apply
Mismo payload canónico del preview
CSRF + lps.programacion_intermedia.editar + action applyBatch
```

Apply vuelve a validar todos los campos y filas. Si falta una actividad, cambió la semana, la semana
se confirmó, cambió la configuración, la policy niega o una fila queda sin Responsable al escribir
restricciones, no actualiza ninguna fila. En éxito devuelve filas completas, IDs actualizados,
registros compartidos creados, `trackingEnabled`, advertencias y resumen de notificaciones.

La transacción cubre las actualizaciones operativas y los registros compartidos cuando las tablas
scoped están disponibles. La disponibilidad se consulta en el catálogo de scope; S07 no crea tablas.
Si el tracking legacy no está disponible pero las actualizaciones pueden conservar su semántica
vigente, se confirma con advertencia explícita. Una falla de actualización siempre hace rollback.
Proyecto, creador y destinatarios se derivan del servidor.

### Reporte

```text
POST /api/programacion-intermedia/report
Payload: {"week":18}
CSRF + lps.reportes.generar
```

El wrapper usa proyecto/semana activos, conserva el contenido XLSX de restricciones, etiquetas
dinámicas y leyenda, e inyecta el generador en pruebas. Respuesta estricta:

```json
{"success":true,"data":{"url":"/public/storage/cortesRestricciones/corte.xlsx","filename":"corte.xlsx"}}
```

Sólo se acepta URL relativa del mismo origen, bajo el directorio autorizado y con sufijo `.xlsx`.
No se escribe un archivo real en las pruebas de contrato.

### Drawer

S07 consume, sin fork, los endpoints canónicos de T02/S05:

```text
GET  /api/lps/comments?consecutivo=<id>&escalamiento_id=<id?>
POST /api/lps/comments/add
POST /api/lps/crisis/register
POST /api/lps/crisis/close
```

Comentarios/respuestas/menciones conservan el esquema recursivo. Crisis usa `modulo="PI"`; triggers
admitidos permanecen `MANUAL`, `SOS-RES`, `SOS-DIR`, `SOS-COO`, `SOS-GER`; cerrar exige al menos 100
caracteres. T02 resuelve ambas áreas y acciones efectivas. S07 entrega estado/restricciones/contactos
de la actividad y no envía alcance de proyecto.

### Errores estables

Los ocho endpoints nuevos usan:

```json
{"success":false,"error":{"code":"STALE_CONTEXT","message":"La semana cambió. Recarga para continuar.","details":{}}}
```

Códigos mínimos: `AUTH_REQUIRED` 401, `FORBIDDEN` 403, `CSRF_INVALID` 403,
`STALE_CONTEXT` 409, `WEEK_CONFIRMED` 409, `ACTIVITY_NOT_FOUND` 404,
`RESPONSIBLE_REQUIRED` 422, `INVALID_RESTRICTION` 422, `INVALID_CATALOG_VALUE` 422,
`BATCH_CONFLICT` 409, `REPORT_FAILED` 500 y `INTERNAL_ERROR` 500. Mensajes internos, SQL, prefijos,
rutas físicas y excepciones no salen al cliente.

## Arquitectura React

### Módulo

```text
frontend/src/modules/programacion-intermedia/
  dominio/
    normalizarProgramacionIntermedia.ts
    filtrarProgramacionIntermedia.ts
    exportarProgramacionIntermediaCsv.ts
  componentes/
    ToolbarProgramacionIntermedia.tsx
    FiltrosProgramacionIntermedia.tsx
    LeyendaProgramacionIntermedia.tsx
    TablaProgramacionIntermedia.tsx
    TarjetasProgramacionIntermedia.tsx
    EditorHabilitacion.tsx
    EditorActividadIntermedia.tsx
    DialogoRestriccionCompartida.tsx
  useProgramacionIntermedia.ts
  ProgramacionIntermediaPage.tsx
  programacion-intermedia.css
```

Los esquemas viven en `frontend/src/lib/api/esquemas/programacion-intermedia.ts`; el gateway en
`frontend/src/lib/api/programacion-intermedia.ts`. Sólo `cliente.ts` llama `fetch`.

### Estado de pantalla

El hook posee:

- snapshot confirmado de contexto/lista;
- filtros/search/agrupación;
- IDs seleccionados;
- editor abierto y borrador local por una sola actividad;
- request activo y `AbortController` por carga;
- estado de mutación por actividad;
- preview/borrador del batch;
- actividad elegida para drawer.

No copia catálogos o acciones a estados divergentes. Cambio de proyecto/semana aborta cargas,
descarta borradores con confirmación si están sucios y limpia selección/drawer. Respuestas con
identidad pública distinta se descartan aunque lleguen 200.

### Filtros

Filtros combinables:

- búsqueda unificada por Id/actividad/capítulo;
- uno o varios de los ocho estados;
- semanas: vencida, 0, 1, 2–3, 4–6, >6;
- habilitada/no habilitada;
- subcontratista;
- Responsable AIA, incluido “sin responsable”;
- restricción pendiente;
- agrupación por gravedad;
- limpiar filtros.

Los chips de estado permiten selección múltiple sin depender de Ctrl/Cmd. Cada control tiene nombre
visible, contador y botón de limpiar. “Seleccionar visibles” opera sobre el resultado filtrado, no
sobre filas ocultas. Al cambiar el universo o desaparecer IDs, la selección se intersecta con las
filas presentes y anuncia cuántas se retiraron.

### CSV

CSV se genera desde las filas visibles normalizadas en cualquier breakpoint, no desde una instancia
de tabla. Usa UTF-8 con BOM, RFC 4180, CRLF, encabezados/etiquetas visibles y escapa comillas, saltos
y separadores. Incluye Id, actividad, fechas, semanas, ejecutado, habilitación, estado, asignaciones,
observaciones y una columna por restricción configurada. No incluye scope ni campos internos.

### Edición

Tabla y tarjeta comparten `EditorActividadIntermedia`; no duplican validación. El editor:

- indica qué campos son editables y por qué un campo está bloqueado;
- permite asignar Responsable antes de restricciones dentro del mismo borrador;
- presenta todas las restricciones del área con valor, umbral, tipo y `N/A`;
- ofrece anterior/siguiente sobre la lista visible conservando foco;
- valida antes de enviar y muestra errores junto al campo;
- bloquea doble envío y anuncia guardando/guardado/error;
- restaura snapshot confirmado ante error y conserva el borrador para reintento.

El editor móvil incluye asignaciones, observaciones y restricciones; no exige cambiar a desktop.

## Estados de experiencia

La página distingue:

1. carga inicial de contexto;
2. carga de actividades;
3. semana/proyecto sin datos;
4. universo válido vacío;
5. filtros sin coincidencias;
6. sólo lectura;
7. catálogos parciales o fallidos;
8. guardado individual pendiente/exitoso/fallido;
9. preview batch pendiente/listo/bloqueado;
10. apply pendiente/exitoso/con advertencia/fallido;
11. contexto obsoleto;
12. semana confirmada durante la edición;
13. reporte pendiente/listo/fallido;
14. drawer sin selección/cargando/vacío/error;
15. error general con reintento.

Un error de lista nunca se presenta como “No hay actividades”. Un error de catálogos no borra las
filas. Un error de drawer no bloquea edición PI. Recargar no dispara mutaciones ni sincronización de
catálogos; “Actualizar listas” es una acción separada y explícita.

## Accesibilidad, tema y design system

- Dark es default/fallback y light conserva capacidad, jerarquía y contraste.
- Todo color, radio, sombra, espaciado y estado sale de `public/css/tokens.css` y contratos semánticos.
- No hay colores literales, estilos inline, Bootstrap, Handsontable, TomSelect, CSS-in-JS ni
  `!important` local.
- La tabla usa `table`, encabezados, caption accesible y controles reales; no emula celdas con divs.
- Tarjetas usan headings, listas descriptivas y botones/inputs con labels.
- Checkboxes tienen nombre de actividad; selección visible anuncia cantidad.
- Leyenda es dialog accesible, atrapa/restaura foco y se cierra con Escape.
- Editor y batch mantienen orden de foco, errores asociados y confirmación no basada sólo en color.
- Drawer y dialogs no compiten por foco; sólo un overlay modal puede estar activo.
- Estados se expresan por texto/icono/estructura además de color.
- Targets táctiles mínimos 44 px, zoom 200 %, reduced motion y orientación móvil/tablet.
- Viewports requeridos: 390×844, 768×1024, 1180×820 y 1440×900, ambos temas, sin overflow de página.

## Seguridad y RLS

S07 no cambia RLS. Respeta la frontera existente:

- `ProjectScope` activo y sesión son autoridad;
- toda consulta/actualización incluye `project_id` explícito o usa la capa scoped equivalente;
- ningún dato del navegador selecciona tabla/proyecto/usuario/rol/área;
- RBAC y policy se ejecutan antes de acceder al servicio;
- mutaciones exigen CSRF y no aceptan GET;
- queries usan prepared statements;
- errores no exponen SQL/excepciones;
- no hay retry automático de mutaciones;
- el tracking compartido no ejecuta DDL; sólo consulta el catálogo scoped;
- pruebas de servicios usan stores/generadores/sincronizadores fake;
- Playwright intercepta todas las mutaciones antes de navegar.

## Estrategia de pruebas

### PHP sin DML

- resolver puro de restricciones/estado para ambas áreas;
- policy de acciones: viewer/editor, actual/histórica, confirmada, Responsable;
- contexto/lista con stores en memoria y respuesta exacta;
- guardado individual con fake store, merge estrecho, valores válidos/inválidos y rollback lógico;
- view preference con sesión aislada;
- refresh de catálogos con sincronizador fake;
- preview sin llamadas write;
- apply atómico con fake transaccional y tracking disponible/no disponible;
- reporte con generador fake y URL segura;
- rutas, método, RBAC, CSRF, scope y forma de error.

`tests/test_pi_responsable_aia_gate.php` permanece como caracterización. Pruebas que invocan el
script legacy, reportes reales, catálogos reales o endpoints POST contra Docker no se ejecutan sin
autorización de datos.

### TypeScript/Vitest

- esquemas strict de ocho endpoints y rechazos por campos/tipos inesperados;
- gateway exacto, headers, query permitida, cancelación y errores;
- normalización de ratios, `NA`, fechas y catálogos;
- filtros combinados, conteo visible, selección y orden estable;
- CSV BOM/RFC4180 en tabla y tarjetas;
- tabla/tarjetas con mismas acciones;
- editor, validación, missing Responsable, save/revert/retry;
- batch preview/apply/conflictos/advertencia;
- estados de carga/vacío/error/parcial/readonly;
- cambio de proyecto/semana y descarte de respuesta obsoleta.

### Playwright interceptado

- viewer permitido, denegado y editor efectivo;
- semana actual/histórica/confirmada;
- Construcción y Preconstrucción;
- ocho estados, neutral futuro y conteos de seis semanas;
- búsqueda/filtros/gravedad/viewAll/selección;
- edición individual desktop/tablet/móvil;
- batch preview/apply/error/tracking warning;
- CSV en móvil, reporte y recarga;
- drawer lectura/escritura/bloqueo;
- teclado, foco, Axe, zoom 200 %, reduced motion, no overflow;
- dark/light en cuatro viewports;
- candidatos visuales; ningún golden se modifica sin aprobación.

## Estrategia strangler y retiro

1. `/app/programacion-intermedia` sirve el piloto React y la ruta canónica mantiene VIEW-35.
2. Los ocho endpoints nuevos conviven con aliases legacy.
3. Se completan gates funcional, contrato, RBAC, responsive, a11y y candidatos visuales.
4. Felipe aprueba explícitamente el cambio visual/goldens aplicables.
5. GET/HEAD `/programacion-intermedia` pasa al SPA router; POST legacy permanece sólo mientras el
   rollback de la vista lo necesite.
6. Una búsqueda de consumidores prueba qué piezas son exclusivas.
7. Se retiran VIEW-35, controlador/JS/CSS/stateMachine exclusivos y pruebas legacy reemplazadas.

No se eliminan `views/partials/drawer_unificado.php`, `lps_drawer.js`, endpoints LPS, reportes
compartidos, `RestrictionConfigResolver`, catálogos, tokens ni assets/vendor usados por otras
superficies. El rollback es revertir el commit de corte/ruta; no se mantienen dos UIs indefinidamente.

## Criterios de aceptación

- S07-AC-01: `/programacion-intermedia` exige lectura efectiva y no depende de rol cliente.
- S07-AC-02: la sidebar proviene de navegación server-side y no oculta lectores autorizados.
- S07-AC-03: React nunca envía scope/autorización y no llama fetch fuera de `cliente.ts`.
- S07-AC-04: contexto/lista se invalidan al cambiar proyecto o semana.
- S07-AC-05: lista incluye sólo actividades con fechas, no terminadas y no capítulos.
- S07-AC-06: vacío real usa `items=[]`; error no se disfraza de vacío.
- S07-AC-07: default aplica `Semanas_Inicio<=6`; viewAll sólo lo altera con acción/CSRF.
- S07-AC-08: conteos siempre representan la ventana de seis semanas y visibleCount los filtros.
- S07-AC-09: los ocho estados y sus etiquetas/reglas coinciden con el contrato.
- S07-AC-10: `neutral` cubre sólo fallback/futuro y no contamina ocho conteos.
- S07-AC-11: habilitación usa restricciones duras/umbrales del área; blandas no bloquean.
- S07-AC-12: `N/A` satisface la restricción y se excluye del agregado aplicable.
- S07-AC-13: Construcción y Preconstrucción usan el mismo resolver backend.
- S07-AC-14: filtros combinan búsqueda, estado, semanas, readiness, asignaciones y pendiente.
- S07-AC-15: gravedad ordena stable urgent/attention/healthy/neutral.
- S07-AC-16: selección visible/limpieza/conteo son accesibles y coherentes con filtros.
- S07-AC-17: tabla muestra todas las operaciones en desktop y detalle accesible en tablet.
- S07-AC-18: móvil muestra tarjetas plenamente editables y no monta una tabla oculta.
- S07-AC-19: asignaciones, observaciones y restricciones se validan en cliente y servidor.
- S07-AC-20: restricciones sin Responsable fallan; asignarlo en la misma mutación permite guardar.
- S07-AC-21: semana confirmada bloquea mutaciones y una confirmación concurrente devuelve 409.
- S07-AC-22: histórica permite mutar sólo según policy A/D vigente y servidor la impone.
- S07-AC-23: save recibe changes estrechos y devuelve fila completa recalculada.
- S07-AC-24: error de save restaura snapshot y conserva reintento explícito; no auto-retry.
- S07-AC-25: preview batch no escribe y explica faltantes/conflictos/sin Responsable.
- S07-AC-26: apply revalida contexto/filas/configuración y actualiza todo o nada.
- S07-AC-27: tracking no crea tablas y su indisponibilidad se muestra como advertencia.
- S07-AC-28: catálogos cargan sin DML oculto; refresh es explícito y testeado con fake.
- S07-AC-29: CSV funciona desde filas visibles en todos los layouts y cumple BOM/RFC4180.
- S07-AC-30: reporte deriva scope/semana, exige permiso/CSRF y valida URL XLSX.
- S07-AC-31: leyenda explica estados, restricciones duras/blandas, umbrales y acciones.
- S07-AC-32: drawer recibe contexto PI y conserva comentarios, menciones, SOS y crisis autorizados.
- S07-AC-33: carga, vacío, no-match, parcial, readonly, saving, stale y error son distinguibles.
- S07-AC-34: oscuro/claro y 390/768/1180/1440 tienen capacidad equivalente y sin overflow.
- S07-AC-35: teclado, foco, zoom, targets, live regions, reduced motion y Axe pasan.
- S07-AC-36: cada endpoint nuevo tiene contrato PHP y esquema Zod antes del código productivo.
- S07-AC-37: pruebas S07 no ejecutan DDL/DML, login real, reportes reales ni POST contra Docker.
- S07-AC-38: corte canónico sucede sólo tras gates y aprobación visual explícita.
- S07-AC-39: retiro prueba cero callers y conserva drawer/reportes/catálogos compartidos.
- S07-AC-40: RLS, schema, grants, usuarios, credenciales, permisos y `/admin/` no cambian.

## Entregas verticales

### Entrega 1 — Núcleo de lectura responsive

- Resolver de restricciones/estado y policy efectiva.
- Contexto/lista tipados con conteos reales.
- Búsqueda, filtros, gravedad, leyenda, recarga y CSV.
- Tabla desktop/tablet y tarjetas móviles de sólo lectura.

**Gate:** viewer autorizado entiende el look-ahead en ambas áreas, cuatro viewports y ambos temas,
sin petición mutante.

### Entrega 2 — Edición individual

- Servicio de mutación estrecha y guards server-side.
- Asignaciones, observaciones y restricciones.
- Responsable, confirmado, histórica y stale context.
- Editor compartido tabla/tarjeta con recovery.

**Gate:** una actividad se edita con permisos/validaciones idénticos en desktop y móvil mediante
stores e intercepts, sin DML real.

### Entrega 3 — Operaciones compartidas

- ViewAll POST/CSRF.
- Refresh explícito de catálogos.
- Selección y batch preview/apply atómico.
- CSV/XLSX, warnings y feedback.

**Gate:** operaciones individuales y lote están cubiertas por contratos/fakes y no pierden estado.

### Entrega 4 — Drawer, accesibilidad y calidad visual

- Integración T02 con selección PI/digest.
- RBAC permitido/denegado/read-only.
- A11y, oscuro/claro, responsive y visual candidates.

**Gate:** flujos observables y overlays pasan con red interceptada; aprobación explícita antes de
versionar cualquier golden.

### Entrega 5 — Corte y retiro legacy

- Ruta canónica React.
- Manifiestos/design-system actualizados.
- Búsqueda de consumidores y retiro de VIEW-35/piezas exclusivas.
- Rollback comprobable por reversión del commit de corte.

**Gate:** ruta React canónica, suite enfocada/post-corte verde, diff acotado y compartidos intactos.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| estado distinto entre PHP/JS | resolver PHP único y fixtures compartidos |
| PC calculada como Construcción | configuración por área en lista/save/batch |
| edición histórica no protegida | policy server-side y casos negativos |
| overwrite de fila obsoleta | payload changes + merge servidor + stale week guard |
| restricciones sin Responsable | acción por fila y 422 autoritativo |
| batch parcial | revalidación y transacción todo-o-nada |
| tracking silenciosamente degradado | warning tipado y visible; sin DDL |
| fuga entre proyectos | scope servidor y pruebas negativas sin IDs cliente |
| DML oculto en catálogo GET | lectura pura + refresh POST explícito |
| conteos confusos en viewAll | etiqueta fija de ventana y visibleCount separado |
| CSV roto en móvil | exportador sobre dominio, no tabla |
| tablet pierde funciones | tabla semántica 768+ con detalle y pruebas |
| drawer duplicado | dependencia T02, no fork S07 |
| borrar shared assets | búsqueda de callers y corte por exclusividad |
| baseline visual engañoso | fixtures de ocho estados y aprobación previa |

## Decisiones descartadas

- Envolver VIEW-35/Handsontable: conserva globals, doble estado y paridad móvil incompleta.
- Mantener estado calculado en TypeScript: duplica autoridad y vuelve a divergir en PC.
- Reutilizar `/api/pi/save` con fila completa: permite overwrite obsoleto y scope cliente.
- Conservar filtros de sesión por GET: carece de CSRF/permiso y no tiene caller útil.
- Ampliar viewAll a lectores: cambia permisos; v0 conserva el gate de edición.
- Crear tablas de tracking al vuelo: viola RLS/schema/no-DDL.
- Hacer preview vinculante con una tabla/token nuevo: no es necesario si apply revalida.
- Ocultar warning de tracking: el éxito parcial observable debe explicarse.
- Exportar desde Handsontable: falla en móvil y acopla dominio a presentación.
- Mantener tarjetas sólo de restricciones: pierde asignaciones/observaciones.
- Migrar drawer dentro de S07: duplicaría VIEW-28/T02 y sus cuatro consumidores.
- Regenerar goldens antes del gate: requiere aprobación explícita.

## Decisiones pendientes

No hay decisiones de negocio, producto, estrategia o PM pendientes para implementar la paridad v0
de S07. “Ver todas” conserva su permiso legacy de edición; el tracking puede degradarse con una
advertencia explícita; ambas decisiones evitan ampliar alcance sin bloquear el corte React.

## Siguiente gate

Invocar `superpowers:writing-plans` para producir
`docs/superpowers/plans/2026-08-30-s07-programacion-intermedia-react.md`, con TDD, archivos exactos,
contratos sin DML, checkpoints verticales, dependencia T02, gate visual explícito, corte reversible
y retiro por búsqueda de consumidores. No implementar antes de cerrar y autorrevisar ese plan.
