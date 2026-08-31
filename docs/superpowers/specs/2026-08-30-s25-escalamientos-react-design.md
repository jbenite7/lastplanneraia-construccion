---
capa: fuente
tipo: spec
estado: vigente
id: S25
fecha: 2026-08-31
superficie: escalamientos
rutas:
  - "/dashboard/escalamientos"
  - "/api/lps/escalamientos"
  - "/api/lps/comments"
  - "/api/lps/comments/add"
  - "/api/lps/crisis/register"
  - "/api/lps/crisis/close"
depende_de: [T01, T02, S05, S07, S08]
views: [VIEW-12, VIEW-28]
areas: [lps, rbac, design-system]
fuente: "auditoria de public/index.php, DashboardController, LpsApiController, LpsService, RbacCatalog, ProjectScope, VIEW-12, VIEW-28, escalamientos.css, lps_drawer.js, notifications.js, NotificationController, contratos SQL versionados, pruebas browser/PHP/design-system, T01, S05, S07, S08 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S25 del tablero de Escalamientos a React: lectura global de alertas activas por proyecto, jerarquia y conteos, drawer dirigido por alerta y su semana real, comentarios, aviso/SOS, cierre, responsive y oscuro/claro, sin activar autoescalamiento ni modificar RLS/schema/datos."
---

# S25 — Escalamientos en React

> **Estado:** diseño técnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementación,
> commits, DDL/DML, cambios RLS, cambios de capacidades, datos, deploy, publicación ni trabajo en
> `/admin/`. Su plan se escribe a continuación con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S25 migra VIEW-12 y consume el drawer compartido VIEW-28. No vuelve a diseñar el shell ni crea un
segundo drawer:

- T01 posee sesión, proyecto, AppShell, sidebar, temas, routing, errores globales y cliente HTTP;
- T02 posee el modelo contextual LPS, drawer, comentarios, crisis y entrada de notificaciones;
- S05, S07 y S08 producen alertas desde PG, PI y PS y entregan el mismo target contextual;
- S25 aporta la lectura corporativa de alertas activas y permite continuar las acciones ya
  autorizadas sobre una alerta;
- S27 conserva `/dashboard` como redirect y no se confunde con esta ruta.

VIEW-12 se retira con S25. VIEW-28 sólo puede retirarse cuando PG, PI, PS y Escalamientos hayan
cortado y el caller census de T02 llegue a cero. Los aliases HTTP compartidos siguen la misma regla.

## Resultado buscado

`/dashboard/escalamientos` entra en la SPA principal y:

1. muestra todas las crisis activas del proyecto autorizado, sin filtrarlas por la semana del shell;
2. conserva las cuatro estaciones jerárquicas y sus conteos;
3. distingue nivel actual de estación de atención;
4. abre cada crisis mediante un control nativo y un deep link recuperable;
5. resuelve el drawer por alerta, usando la semana, actividad y módulo guardados en esa alerta;
6. permite leer/comentar el hilo, avisar o registrar SOS y cerrar sólo según acciones del servidor;
7. explica cuando una identidad puede leer pero no escribir por la FK histórica;
8. recarga el estado autoritativo después de una acción, sin mover tarjetas de forma optimista;
9. conserva la entrada única de notificaciones del shell sin duplicar campana ni polling;
10. funciona en desktop, tablet y móvil, oscuro y claro, teclado, touch, zoom y lector de pantalla.

Paridad significa conservar datos, acciones, permisos, orden y resultados observables. No significa
copiar jQuery, Bootstrap, Handsontable, el documento PHP aislado ni cuatro columnas comprimidas en
móvil.

## Alcance

### Incluido

- `GET /dashboard/escalamientos` como ruta SPA canónica al corte.
- Nuevo `GET /api/lps/escalamientos` con snapshot tipado y scoped por proyecto.
- Todas las alertas `Activo` del proyecto, de cualquier semana.
- Cuatro estaciones: Director, Coordinación, Gerencia de Construcción y Gerencia General.
- Mapeo explícito de niveles 1/2 a atención del Director.
- Conteo global y por estación, orden estable y recarga manual.
- Tarjetas con semana, identificador, actividad, bloqueo, responsable, trigger, módulo y nivel.
- Deep link `?alerta=<id>` como estado de selección, nunca como autoridad.
- Drawer T02 dirigido por `alertId` y contexto resuelto server-side.
- Lectura de comentarios, raíces, respuestas y metadatos de menciones.
- Comentario/respuesta, aviso/SOS y cierre cuando estén autorizados.
- CSRF, errores tipados, perfil requerido, stale target y recuperación.
- Sidebar en grupo `obra`, después de Programación Semanal.
- Retorno seguro a Programación Semanal.
- Tablero 4 columnas desktop, 2×2 tablet y un nivel a la vez en móvil.
- Oscuro/claro, tokens, accesibilidad, contratos PHP/Zod y pruebas sin DML.
- Convivencia, corte, caller census, rollback y retiro de VIEW-12.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, `ProjectScope`, `ProjectSqlGuard`, runtime boundary, grants, usuarios, credenciales,
  membresías, roles, capacidades o aliases RBAC.
- Cambiar schema, tabla, columna, índice, trigger, FK, migración, vista SQL, dato, seed o fixture
  persistente.
- Ejecutar DDL/DML durante este frente, incluso con rollback.
- Arreglar la FK histórica mediante migración, backfill o asociación por nombre/correo.
- Elegir un nuevo modelo de actor para comentarios y cierres.
- Activar, programar o llamar `LpsService::escalarAlertasActivas()`.
- Crear un historial de crisis cerradas.
- Cambiar la regla de siete días o los niveles jerárquicos.
- Crear filtros por semana, búsqueda, drag-and-drop, orden manual, batch, exportación o descarga:
  legacy no ofrece esas funciones.
- Inventar email, teléfono, contacto de WhatsApp o un join de contactos.
- Afirmar que una mención, copia o aviso fue entregado a una persona.
- Añadir envío real de correo, WhatsApp, push o notificación.
- Duplicar campana, polling o endpoints de `system_notifications`.
- Reemplazar T02 con un drawer local S25.
- Cargar jQuery, jQuery UI, Bootstrap, Handsontable o `lps_drawer.js` en React.
- Regenerar goldens/baselines sin autorización explícita.
- Retirar VIEW-28 o aliases antes de que T02 confirme cero consumidores.

## Fuentes y precedencia

1. Código, esquema vigente y respuestas reales del worktree.
2. Frontera RLS ya cerrada y capacidades resueltas por `RbacService`.
3. T01 y T02.
4. S05, S07 y S08 para los targets que originan crisis.
5. Esta spec para el tablero S25.
6. Legacy como caracterización y compatibilidad, nunca como autoridad de seguridad.

Cuando legacy contradice la seguridad o su propio dato, se conserva la intención funcional y se
cierra la contradicción. En particular, la semana del shell no puede sustituir la semana de una
alerta histórica.

## Punto de partida medido

### React

- `SpaRouter::RUTAS_MIGRADAS` sólo contiene `/app`.
- `frontend/src` contiene shell, login, proyecto y tema; no existe módulo Escalamientos.
- No hay ruta, schema Zod, gateway, hook, store, tablero, tarjeta ni prueba React S25.
- La sidebar actual usa grupos Información, Obra y Compras, pero aún filtra por roles hardcodeados.
- `frontend/src/lib/api/cliente.ts` es la única frontera HTTP permitida.

### Rutas y controladores legacy

| Método | Ruta | Autoridad actual | Disposición S25 |
|---|---|---|---|
| GET | `/dashboard/escalamientos` | `DashboardController::escalamientos` | host React al corte; exigir capacidad de lectura |
| GET | `/api/lps/escalamientos` | inexistente | snapshot canónico nuevo; Zod + contrato PHP |
| GET | `/api/lps/comments` | `LpsApiController::comments` | conservar; añadir target canónico por alerta |
| POST | `/api/lps/comments` | alias de alta | conservar sólo por convivencia |
| POST | `/api/lps/comments/add` | alta canónica legacy | conservar; aceptar target por alerta |
| POST | `/api/lps/crisis` | alias de registro | conservar sólo por convivencia |
| POST | `/api/lps/crisis/register` | registro canónico legacy | conservar; resolver alerta/module/week en servidor |
| POST | `/api/lps/crisis/close` | cierre canónico | conservar; target por alerta ya existe |
| GET | `/api/notifications/unread` | `NotificationController` | propiedad T01/T02; S25 no llama directamente |
| POST | `/api/notifications/read` | `NotificationController` | propiedad T01/T02; S25 no llama directamente |

La página legacy valida sesión y formato de `db`, busca proyecto por nombre y área, pero no exige
`lps.programacion_semanal.ver`. Los endpoints de comentarios sí exigen lectura y las mutaciones
exigen edición. Esa divergencia se cierra: página y snapshot usan la misma capacidad de lectura.

### Tablero legacy

VIEW-12 construye un documento HTML independiente, sin AppShell ni sidebar. Incluye jQuery,
jQuery UI, Bootstrap, CSS del módulo, VIEW-28 y `lps_drawer.js`. Su cabecera ofrece “Volver a
Planificación” con href fijo a `/programa-general`.

El tablero:

- define cuatro columnas con niveles visuales 2, 3, 4 y 5;
- coloca una alerta nivel 1 en la columna 2;
- ordena por `nivel_actual DESC, fecha_detonacion ASC`;
- muestra semana, `unique_id` o consecutivo, actividad, observación/bloqueo, subcontratista y trigger;
- limpia etiquetas HTML del nombre de actividad antes de imprimir;
- muestra “Sin crisis en este nivel” por columna;
- abre una tarjeta con `div onclick` y luego adapta sus campos al drawer;
- usa un adapter sin grid que recarga toda la página cuando se cierra una crisis.

No tiene búsqueda, filtros, selector de semana, drag-and-drop, exportación, batch, historial ni
paginación. Sus cuatro columnas se fuerzan también en anchos donde dejan de ser legibles.

### Lectura y modelo persistido

`LpsService::getActiveCrisisByProject()` une `lps_escalamientos` con `programa_consolidado` por
actividad y semana, filtra `proyecto_id` y `estado='Activo'`, y devuelve:

- identidad de alerta y actividad;
- semana;
- módulo `PG|PI|PS`;
- trigger;
- nivel 1–5;
- fechas de detonación y último escalamiento;
- nombre de actividad;
- subcontratista;
- `Observaciones` como descripción de bloqueo.

La consulta actual usa `queryWithProject`. Un probe read-only sin alcance fue rechazado por
`MissingProjectScope`, confirmando que la frontera runtime está activa. La caracterización
autorizada de proyectos de prueba no encontró alertas/comentarios y no modificó datos.

### Drawer, comentarios y crisis

`LpsApiController::getContext()` exige hoy una semana positiva de sesión y resuelve proyecto por
nombre. Esto sirve para una página semanal, pero no para el dashboard global.

La brecha medible es:

1. el tablero incluye alertas de cualquier semana;
2. al abrir una tarjeta, el JavaScript no transmite su semana al endpoint de comentarios;
3. el endpoint usa `$_SESSION['semana']`;
4. un hilo histórico puede leerse vacío o recibir un comentario en la semana equivocada.

El target S25 se resuelve por `alertId`. El servidor comprueba proyecto y obtiene de la alerta su
`unique_id`, semana y módulo. El cliente no vuelve a ser autoridad de esas claves.

Los contratos legacy responden `200` incluso para varios errores de validación y usan
`respuesta:"OK"|"ERROR"`. T02 normaliza éxito/errores en el borde sin romper aliases.

### Elegibilidad histórica del actor

El schema vigente relaciona:

- `lps_drawer_comentarios.usuario_id` con `profesionales(project_id,id)`;
- `lps_escalamientos.usuario_cierre_id` con `profesionales(project_id,id)`.

El controlador pasa el `Id` de `general_usuarios` y la lectura de autor vuelve a unir contra
`general_usuarios`. Para algunos usuarios no existe una fila profesional con ese mismo ID en el
proyecto; comentario o cierre pueden fallar de forma genérica.

S25 no puede cambiar esa semántica sin decidir y migrar identidad/datos. El comportamiento seguro
es verificar la compatibilidad exacta del ID antes de ofrecer escritura:

- si el mismo ID satisface la FK actual, se conserva el actor legacy;
- si no, `PROFILE_REQUIRED` bloquea comentario y cierre, que son las escrituras ligadas a esa FK;
- aviso/SOS conserva su política RBAC propia porque no persiste el actor en esas columnas;
- el tablero y el hilo siguen en lectura;
- no se busca por nombre, email ni cargo;
- un nuevo modelo de actor queda para una decisión independiente.

### Aviso, simulación y contactos

`lps_drawer.js` genera acciones jerárquicas y puede registrar `MANUAL` o `SOS-*`. En modo
simulación —persistido hoy en localStorage y activo por defecto— copia el texto y no registra. En
modo operativo, registrar sobre una alerta ya activa no incrementa `nivel_actual`: el servicio
detecta la alerta y sólo asegura banderas de crisis.

El dashboard no tiene un contacto canónico. Sus filas no incluyen teléfono ni email. El fallback
real es copiar texto; S25 lo etiqueta así. No se mostrará “enviado” ni se moverá la tarjeta.

`LpsService::escalarAlertasActivas()` sí incrementa cada siete días, pero no tiene caller en el
repositorio. La migración de UI no lo activa ni lo agenda.

### Notificaciones del shell

`public/js/components/notifications.js` consulta no leídas y marca leída. Es una preocupación de
cuenta/shell, no una crisis del tablero. T01 ya reserva `NotificationEntry` para T02. S25:

- conserva esa entrada al renderizar dentro de AppShell;
- no vuelve a consultar `/api/notifications/*`;
- no transforma menciones del drawer en notificaciones;
- no duplica timers, polling ni estado leído.

### Permisos efectivos

Matriz fallback observada, únicamente explicativa:

| Rol canónico | Ver tablero/hilo | Comentar/avisar/cerrar | Notificaciones LPS fallback |
|---|---:|---:|---:|
| A, D, R, DCV | sí | sí | sí |
| OT | sí | no | sí |
| G, S, SG | sí | no | no |
| V | sí | no | sí |
| C | no | no | no |

Los overrides persistidos pueden cambiar esa tabla. `RbacService` resuelve la decisión real y el
servidor devuelve acciones; React nunca implementa la matriz.

### Pruebas existentes

- `tests/test_csrf_lps_api.php` cubre CSRF de las tres mutaciones con payload inválido y sin escribir.
- `tests/browser/lps-drawer-design-system.mjs` cubre foco, inert, Axe y golden desktop dark.
- `tests/browser/lps-drawer-fetch-lifecycle.mjs` cubre abort, stale, pagehide, red y HTTP 500.
- `tests/browser/escalamientos-acciones.spec.mjs` siembra y borra una alerta: no puede ejecutarse
  bajo la prohibición de DML vigente.
- `tests/browser/escalamientos-sin-errores.spec.mjs` tiene falsos verdes: si la puerta dev redirige
  a login, las aserciones de “sin error” y “digest oculto” pueden pasar sin tablero.
- En la auditoría, ese archivo produjo 2 casos verdes y 1 rojo; la causa fue `/dev/entrar`
  redirigiendo a `/login`. No se cambió código ni dato.

La implementación debe hacer que todo browser test afirme primero sesión, proyecto, URL, h1 y
tablero. Una puerta dev cerrada se reporta explícitamente; la pantalla Login nunca cuenta como
evidencia de Escalamientos.

## Decisiones de arquitectura

### ESC-R1 — Snapshot global por proyecto

El tablero no usa la semana activa. Su grano es una alerta activa del proyecto. La semana viaja en
cada ítem y gobierna su hilo. El header explica “Todas las semanas con crisis activas”.

No se oculta el selector global del shell por hacks del módulo; simplemente no se lo presenta como
filtro S25 ni se invalida el tablero al cambiar semana. Un cambio de proyecto sí invalida todo.

### ESC-R2 — Una capacidad existente, dos modos

No se agrega `lps.escalamientos.*`:

- `lps.programacion_semanal.ver` admite ruta, snapshot y hilo;
- `lps.programacion_semanal.editar` admite comentario, aviso/registro y cierre;
- elegibilidad del actor puede restringir escritura sin retirar lectura.

Página y API comparten una política. Ocultar navegación no sustituye la guarda.

### ESC-R3 — Nuevo GET, servicios compartidos para mutaciones

`GET /api/lps/escalamientos` es nuevo porque legacy sólo incrusta filas en HTML. Devuelve un
snapshot cohesivo y no depende de una semana de sesión.

Los endpoints compartidos de comentario/crisis permanecen. T02 añade un target por alerta y hace que
legacy y React deleguen en el mismo resolver/política/servicio. No se crean cuatro endpoints S25
paralelos.

### ESC-R4 — Alerta como identidad contextual

El navegador puede enviar `alerta_id`. No puede enviar como autoridad la semana, módulo, actividad,
proyecto o actor. `EscalationTargetResolver`:

1. toma ProjectScope activo;
2. carga la alerta por `project_id + id`;
3. valida que esté activa cuando la acción lo exige;
4. extrae `unique_id`, semana, módulo y nivel;
5. produce un target inmutable para hilo/mutación.

Los aliases por consecutivo siguen durante convivencia y pasan por la misma validación.

### ESC-R5 — Jerarquía explícita, no “kanban” editable

La UI parece un board, pero las tarjetas no se arrastran ni cambian nivel desde React. Las estaciones
son atención jerárquica:

| Nivel persistido | Nivel actual | Estación visual | Siguiente |
|---:|---|---|---|
| 1 | Residente | Atención: Director de Obra | Director de Obra |
| 2 | Director de Obra | Atención: Director de Obra | Coordinación de Integración |
| 3 | Coordinación de Integración | Atención: Coordinación de Integración | Gerencia de Construcción |
| 4 | Gerencia de Construcción | Atención: Gerencia de Construcción | Gerencia General |
| 5 | Gerencia General | Atención: Gerencia General | ninguno |

Separar “nivel actual” de “estación” evita afirmar que una alerta nivel 1 ya es nivel 2.

### ESC-R6 — Perfil requerido, sin migración encubierta

`LpsActorEligibility` comprueba la exacta compatibilidad que requiere el schema actual. Si no existe:

- `actions.comment/close=false`;
- `actions.notifyNext` conserva la decisión RBAC/jerarquía porque no guarda ese actor;
- `actorWriteBlock="profile_required"`;
- el drawer explica que el perfil profesional debe ser resuelto fuera de esta migración;
- el servidor devuelve `PROFILE_REQUIRED` si un cliente stale intenta escribir.

No hay autocreación de profesional, mapping por texto, reparación ni bypass de FK.

### ESC-R7 — Avisar no equivale a escalar ni entregar

La acción se presenta según resultado real:

- simulación: “Copiar aviso”; no llama mutación;
- operativo: “Registrar aviso/SOS”; conserva `registerCrisis`;
- éxito: “Registro aceptado; verifica el canal externo”;
- la tarjeta no cambia de columna hasta que el snapshot cambie;
- nivel 5 no ofrece siguiente nivel.

`escalarAlertasActivas()` sigue dormido. Activarlo sería una decisión operacional separada.

### ESC-R8 — Notificaciones siguen en el shell

La campana, no leídas y marcar leída pertenecen a T01/T02. S25 no consume esos endpoints. Las
menciones siguen siendo metadata JSON del hilo. Esta frontera evita dos pollers y evita prometer una
entrega inexistente.

### ESC-R9 — Ruta, sidebar y regreso

T01 recibe esta declaración:

| Campo | Valor |
|---|---|
| id | `escalamientos` |
| label | `Escalamientos` |
| group | `obra` |
| order | después de `programacion-semanal` |
| href | `/dashboard/escalamientos` |
| capability | `lps.programacion_semanal.ver` |

El regreso usa history cuando la entrada anterior pertenece a la SPA y fallback
`/programacion-semanal`, que comparte capacidad de lectura. No se usa referrer externo ni el href
legacy fijo a Programa General.

### ESC-R10 — Responsive por composición

- 1180–1440: cuatro estaciones simultáneas;
- 768–1179: dos columnas por dos filas;
- <768: una estación montada a la vez, elegida por tabs/segmented control accesible;
- 320 y zoom 200%: una columna, sin scroll horizontal de página.

Los conteos de todas las estaciones permanecen visibles en móvil. No se montan simultáneamente una
versión desktop y otra mobile ocultas por CSS.

## Modelo canónico

### EscalationBoardSnapshot

`EscalationBoardSnapshot` contiene:

- `scope: "active_project"`;
- nombre del proyecto ya autorizado;
- acciones globales;
- cuatro `levels` fijos;
- lista plana `items` en orden canónico;
- total y conteos;
- links seguros;
- metadatos de generación/request.

La lista plana evita duplicar un ítem entre payload y columnas. React agrupa con una función pura
usando `displayLevel` ya resuelto.

### EscalationBoardItem

| Campo | Tipo | Regla |
|---|---|---|
| `alertId` | int positivo | identidad de alerta y deep link |
| `activityId` | int positivo | `unique_id`; consecutivo sólo adapter legacy |
| `week` | int positivo | valor persistido en alerta |
| `module` | `PG|PI|PS` | origen persistido |
| `trigger` | string no vacío | valor persistido, presentado como dato |
| `currentLevel` | 1..5 | nivel real |
| `currentLevelLabel` | enum visible | etiqueta real |
| `displayLevel` | 2..5 | estación de atención |
| `displayLevelLabel` | enum visible | etiqueta de columna |
| `nextLevel` | 2..5 o null | null en terminal |
| `nextLevelLabel` | enum o null | null en terminal |
| `terminal` | bool | sólo nivel 5 |
| `activity` | string no vacío | texto plano con fallback |
| `blockage` | string o null | Observaciones; null se explica |
| `subcontractor` | string o null | null se presenta como AIA/no registrado según adapter |
| `detonatedAt` | datetime ISO/null | orden secundario |
| `lastEscalatedAt` | datetime ISO/null | contexto |
| `actions` | object | readThread/comment/notifyNext/close server-side |

No viajan `db`, prefijo, proyecto interno, usuario de cierre, justificación, HTML ni contacto.

### Orden y conteos

1. estaciones 2, 3, 4, 5;
2. dentro de cada estación, `currentLevel DESC`;
3. después `detonatedAt ASC`, con null al final;
4. finalmente `alertId ASC`;
5. total = suma exacta de estaciones.

El servidor entrega el orden; la función pura frontend lo preserva al agrupar.

### Fallbacks visibles

- actividad ausente → “Actividad sin nombre”;
- bloqueo ausente → “Sin descripción de bloqueo”;
- subcontratista ausente → “AIA / no registrado”;
- fecha ausente → “Fecha no disponible”;
- trigger desconocido → texto plano “Origen no reconocido”, conservando el valor sólo en detalle.

No se convierte ausencia en “sin restricciones” ni en estado saludable.

## Contrato HTTP objetivo

### GET /api/lps/escalamientos

Sin query requerida. Cabeceras/cookies de sesión resuelven el proyecto. La respuesta exitosa:

```json
{
  "ok": true,
  "data": {
    "scope": "active_project",
    "project": {"name": "Proyecto de prueba"},
    "actions": {
      "readBoard": true,
      "readThread": true,
      "comment": true,
      "notifyNext": true,
      "close": true,
      "actorWriteBlock": "none"
    },
    "levels": [
      {
        "id": "director",
        "displayLevel": 2,
        "label": "Director de Obra",
        "includesCurrentLevels": [1, 2],
        "count": 1
      }
    ],
    "items": [
      {
        "alertId": 901,
        "activityId": 4102,
        "week": 14,
        "module": "PS",
        "trigger": "SOS-RES",
        "currentLevel": 1,
        "currentLevelLabel": "Residente",
        "displayLevel": 2,
        "displayLevelLabel": "Director de Obra",
        "nextLevel": 2,
        "nextLevelLabel": "Director de Obra",
        "terminal": false,
        "activity": "Actividad de prueba",
        "blockage": "Restricción de prueba",
        "subcontractor": "Contratista de prueba",
        "detonatedAt": "2026-08-20T14:30:00Z",
        "lastEscalatedAt": null,
        "actions": {
          "readThread": true,
          "comment": true,
          "notifyNext": true,
          "close": true
        }
      }
    ],
    "totals": {
      "active": 1,
      "byDisplayLevel": {"2": 1, "3": 0, "4": 0, "5": 0}
    },
    "links": {"returnHref": "/programacion-semanal"}
  },
  "meta": {
    "requestId": "opaque",
    "generatedAt": "2026-08-31T12:00:00Z"
  }
}
```

La respuesta real siempre trae los cuatro niveles, aun vacíos. `actorWriteBlock` es
`none|forbidden|profile_required`. No se agrega ETag/concurrencia optimista en v0.

### GET /api/lps/comments

Contrato React:

```text
GET /api/lps/comments?alerta_id=<int positivo>
```

El server resuelve el target completo. El adapter legacy conserva
`consecutivo + escalamiento_id?` mientras existan consumidores; nunca permite que ese camino cruce
proyecto.

La salida canónica de T02 normaliza:

```json
{
  "ok": true,
  "data": {
    "target": {"alertId": 901, "activityId": 4102, "week": 14, "module": "PS"},
    "comments": []
  },
  "meta": {"requestId": "opaque"}
}
```

Cada comentario conserva ID, texto, fecha, autor visible, menciones y respuestas. No expone IDs de
actor ni correo/usuario.

### POST /api/lps/comments/add

`application/x-www-form-urlencoded` mediante `cliente.ts`:

| Campo | Regla |
|---|---|
| `alerta_id` | int positivo, target autoritativo |
| `comentario` | trim, no vacío |
| `parent_id` | int positivo opcional, mismo hilo |
| `menciones` | JSON opcional, schema T02 |
| CSRF | `lps_drawer` |

El éxito devuelve `commentId` positivo y target. React vuelve a leer el hilo. No inserta
optimistamente un comentario ficticio.

### POST /api/lps/crisis/register

Para S25:

| Campo | Regla |
|---|---|
| `alerta_id` | int positivo |
| `trigger` | enum `MANUAL|SOS-RES|SOS-DIR|SOS-COO|SOS-GER` |
| CSRF | `lps_drawer` |

El servidor deriva actividad, semana y módulo. Durante convivencia acepta `consecutivo + modulo`
legacy, pero los valida contra ProjectScope. Éxito significa registro/flags aceptados, no aumento de
nivel ni entrega de mensaje.

### POST /api/lps/crisis/close

| Campo | Regla |
|---|---|
| `alerta_id` | int positivo y activo en proyecto |
| `justificacion` | trim, mínimo 100 caracteres |
| CSRF | `lps_drawer` |

El servicio actual cierra alerta y limpia banderas en consolidado/semanal. React no actualiza
localmente; vuelve a leer y la tarjeta desaparece sólo cuando el snapshot lo confirma.

### Errores

| HTTP | code | UI |
|---:|---|---|
| 401 | `SESSION_REQUIRED` | T01 limpia contexto y muestra Login |
| 403 | `CAPABILITY_REQUIRED` | acceso denegado/read-only según operación |
| 403 | `CSRF_INVALID` | conserva texto, pide reintento manual |
| 409 | `ESCALATION_NOT_ACTIVE` | cierra acción, recarga snapshot |
| 409 | `PROFILE_REQUIRED` | conserva lectura y explica perfil |
| 404 | `ESCALATION_NOT_FOUND` | deep link no disponible |
| 422 | `VALIDATION_FAILED` | campos asociados |
| 500 | `ESCALATION_READ_FAILED` | error recuperable con requestId |
| 503 | `SERVICE_UNAVAILABLE` | conserva último snapshot y reintenta manual |

Los aliases pueden conservar temporalmente `respuesta`, pero el gateway T02 los normaliza. Los
errores no incluyen SQL, prefijos, rutas internas ni mensajes de excepción sin filtrar.

## Arquitectura backend

### EscalationBoardReadService

Responsabilidades:

1. recibir ProjectScope y viewer efectivo;
2. autorizar lectura;
3. consultar alertas activas con scope;
4. normalizar/sanitizar campos;
5. resolver jerarquía, orden, conteos y fallbacks;
6. calcular acciones globales/por ítem;
7. presentar snapshot sin datos internos.

No inicia transacciones ni llama autoescalamiento.

### EscalationTargetResolver

Es la única entrada de alerta a hilo/mutaciones. Se comparte con T02 y evita que cada endpoint
reconstruya project/week/activity/module desde sesión o body.

### LpsActorEligibility

Comprueba la semántica FK existente antes de comentar o cerrar. Su resultado es
`eligible|profile_required|forbidden`. No repara ni crea identidad.

### Presenter y compatibilidad

El controller produce HTTP/envelope. El servicio produce dominio. El presenter legacy transforma el
mismo resultado sólo mientras VIEW-12/PG/PI/PS lo necesiten. No existen dos queries ni dos políticas.

### Integración propuesta

Crear:

- `src/Services/Lps/EscalationBoardReadService.php`;
- `src/Services/Lps/EscalationBoardRepository.php`;
- `src/Services/Lps/ScopedEscalationBoardRepository.php`;
- `src/Services/Lps/EscalationHierarchy.php`;
- `src/Services/Lps/EscalationActionPolicy.php`;
- `src/Services/Lps/EscalationTarget.php`;
- `src/Services/Lps/EscalationTargetResolver.php`;
- `src/Services/Lps/LpsActorEligibility.php`;
- `src/Services/Lps/EscalationBoardPresenter.php`.

Modificar:

- `src/Controllers/Api/LpsApiController.php`;
- `src/Controllers/Core/DashboardController.php` sólo durante convivencia/corte;
- `src/Services/LpsService.php` mediante delegación mínima;
- `public/index.php`;
- seams T01/T02 de sesión/navegación/drawer.

No se modifica `src/Core/Database.php`, ProjectScope, TableResolver, schema ni SQL versionado.

## Arquitectura frontend

### Estructura propuesta

```text
frontend/src/modules/escalamientos/
├── api/
│   ├── esquemas.ts
│   ├── escalamientos.ts
│   └── escalamientos.test.ts
├── dominio/
│   ├── jerarquia.ts
│   ├── agrupar.ts
│   └── agrupar.test.ts
├── estado/
│   ├── useEscalamientos.ts
│   └── useEscalamientoSeleccionado.ts
├── componentes/
│   ├── CabeceraEscalamientos.tsx
│   ├── ResumenEscalamientos.tsx
│   ├── SelectorNivelEscalamiento.tsx
│   ├── TableroEscalamientos.tsx
│   ├── ColumnaEscalamiento.tsx
│   ├── TarjetaEscalamiento.tsx
│   ├── EstadoEscalamientos.tsx
│   └── LeyendaEscalamientos.tsx
├── PaginaEscalamientos.tsx
├── PaginaEscalamientos.test.tsx
└── escalamientos.css
```

El drawer vive bajo el seam T02 compartido; S25 no crea `DrawerEscalamientos.tsx`.

### Estado remoto

`useEscalamientos` mantiene:

- `status: idle|loading|success|refreshing|error`;
- último snapshot validado;
- error tipado;
- AbortController/request generation;
- acción en curso por alerta;
- selección derivada de `?alerta`.

Cambiar proyecto o sesión descarta todo. Cambiar semana no dispara fetch S25. Recargar o completar
una mutación sí.

### Composición

1. h1 y descripción global;
2. proyecto y “todas las semanas”;
3. total, recargar y leyenda;
4. selector de nivel sólo móvil;
5. tablero/estado global;
6. drawer T02 cuando existe target válido.

No hay filtro o búsqueda decorativos.

### Drawer y URL

Activar tarjeta escribe `?alerta=<id>` con navegación SPA y abre T02. Back cierra selección antes de
abandonar página. Refresh profundo vuelve a cargar snapshot y abre sólo si la alerta pertenece a él.

El drawer:

- muestra alerta, actividad, semana, módulo, nivel real y siguiente;
- carga hilo por alerta;
- presenta acciones server-side;
- conserva texto tras error;
- trampa foco, cierra con Escape/botón y devuelve foco;
- anuncia cuando la tarjeta desapareció después del cierre.

### Leyenda

Explica:

- diferencia entre nivel actual y estación;
- niveles 1/2 compartiendo atención del Director;
- significado del trigger como origen, no gravedad;
- simulación = copiar, no enviar;
- perfil requerido = lectura sin escritura;
- recarga autoritativa después de acciones.

## Estados de producto

### Carga y recarga

Carga inicial usa cuatro skeletons de columna sin contenido ficticio. Refresh conserva datos,
deshabilita sólo la acción incompatible y anuncia resultado. No bloquea toda la página por un hilo.

### Vacíos

- global: “No hay crisis activas en este proyecto”;
- estación: “Sin crisis activas en este nivel de atención”;
- hilo: “Aún no hay comentarios”;
- deep link: “La alerta ya no está disponible” con volver al tablero;
- perfil: lectura disponible, acciones requieren perfil profesional compatible.

### Errores

Un error del hilo no elimina el tablero. Un error de una mutación no cierra drawer ni borra texto.
Un error de snapshot con datos anteriores conserva la última lectura marcada como desactualizada.

## Responsive, tema y design system

- tokens exclusivos de `public/css/tokens.css`;
- oscuro inicial y claro equivalente;
- sin color literal, `!important`, style de color ni tokens `--esc-*` locales;
- los niveles usan componentes/tokens categóricos aprobados y texto visible;
- tarjeta completa nativa, foco visible, 44 px;
- sin hover-only ni emoji como único significado;
- transición reducida con `prefers-reduced-motion`;
- no se carga CSS de Handsontable para obtener geometría del drawer.

VIEW-12 puede conservar `public/css/escalamientos.css` durante piloto. Tras corte y censo cero se
retira si es exclusivo. Los estilos compartidos del drawer esperan T02.

## Accesibilidad

- un `h1`; estaciones `h2`; títulos de tarjeta `h3`;
- `main` pertenece a AppShell; drawer es `aside/dialog` según T02;
- selector móvil usa tabs o radio-group con nombre, selección y panel asociado;
- tarjeta es `button type="button"`;
- conteos tienen texto accesible;
- live regions separan carga, refresh y mutación;
- focus trap/return y background inert;
- Escape no descarta texto sin confirmación cuando existe borrador;
- zoom 200%, 320 px, teclado y lector no pierden acciones;
- Axe serious/critical cero en ambos temas.

## Seguridad, privacidad y RLS

- ProjectScope activo antes de cualquier query operacional.
- Project ID se inserta como predicado por la capa existente.
- RbacService normaliza rol y decide capacidades.
- Alert ID siempre se combina con proyecto.
- Parent, thread y escalation IDs se validan dentro del mismo target.
- CSRF en cada mutación.
- Prepared statements; sin SQL construido con input.
- Texto se escapa al renderizar; no `dangerouslySetInnerHTML`.
- No contactos, emails, usernames ni IDs de actor en snapshot.
- No logs/evidencia con nombres o comentarios reales.
- No RLS/schema/data change.

## Coexistencia, corte y rollback

### Piloto

La misma página React responde primero en `/app/dashboard/escalamientos` si el router de piloto lo
requiere. `/dashboard/escalamientos` permanece PHP hasta pasar gates. No se crea ruta pública
`/legacy/escalamientos`.

### Corte

1. dependencias T01/T02 disponibles;
2. snapshot y target contracts verdes;
3. capacidad página/API alineada;
4. drawer y acciones interceptadas verdes;
5. responsive, temas, teclado, Axe y lifecycle verdes;
6. caller census documentado;
7. ruta añadida a SpaRouter;
8. refresh/deep link verificados;
9. VIEW-12 y assets exclusivos retirados sólo en el commit de corte;
10. VIEW-28/aliases permanecen si T02 aún tiene consumidores.

### Rollback

Retirar ruta del mapa SPA y restaurar VIEW-12 por revert de código. No hay migración ni dato que
revertir. El nuevo GET puede permanecer sin efecto si conserva autorización y contrato.

## Estrategia de pruebas

### PHP y dominio

- jerarquía 1→2, niveles 2–5, terminal y labels;
- scope y query activa;
- sanitización/fallbacks;
- orden y conteos;
- RBAC read/edit y overrides;
- target por alerta y semana propia;
- parent/menciones/aliases;
- elegibilidad/profile required;
- errores HTTP/envelopes;
- invariante no DML/no autoescalamiento.

Todo con repositorios fake, call logs o SELECT-only. No seed, update, delete ni restore.

### Frontend

- Zod estricto y errores;
- agrupación y orden;
- carga/refresh/empty/error/offline;
- vista read-only/profile required/edit;
- URL selection/stale;
- tarjetas/selector responsive;
- tema/foco/teclado/announcements;
- cliente único y cancelación stale.

### Navegador interceptado

Matrices:

- acciones permitidas;
- lectura sin edición;
- profile required;
- cuatro niveles y global empty;
- 390, 480, 768, 1180, 1440;
- oscuro/claro;
- comment success/error;
- simulation/live copy;
- close success/stale;
- network/500/contract error;
- deep link/back/focus;
- Axe y consola/red.

Cada test intercepta APIs y falla ante URL/método inesperado. Antes de la primera aserción funcional
comprueba sesión, proyecto, URL y h1. No usa datos reales ni ejecuta DML.

## Criterios de aceptación

1. **S25-AC-001:** Página y endpoint exigen sesión autenticada y un ProjectScope válido.
2. **S25-AC-002:** La ruta y la lectura requieren la capacidad efectiva lps.programacion_semanal.ver.
3. **S25-AC-003:** Comentar, avisar/registrar y cerrar requieren lps.programacion_semanal.editar.
4. **S25-AC-004:** React no compara códigos de rol ni mantiene una matriz de permisos.
5. **S25-AC-005:** Overrides RBAC del proyecto prevalecen sobre la matriz fallback documentada.
6. **S25-AC-006:** Un proyecto ajeno no produce filas, conteos, IDs ni metadatos de crisis.
7. **S25-AC-007:** Ningún project_id, db, prefijo, proyecto, usuario o rol del navegador concede alcance.
8. **S25-AC-008:** El tablero contiene todas las alertas activas del proyecto, incluidas semanas anteriores.
9. **S25-AC-009:** La semana activa del shell no filtra ni reescribe el tablero.
10. **S25-AC-010:** Cada tarjeta conserva la semana autoritativa de su alerta.
11. **S25-AC-011:** No se crea una capacidad nueva específica de Escalamientos.
12. **S25-AC-012:** El manifiesto autorizado declara el ítem escalamientos en el grupo obra.
13. **S25-AC-013:** Escalamientos aparece después de Programación Semanal en la sidebar.
14. **S25-AC-014:** El ítem usa id escalamientos, etiqueta Escalamientos y href /dashboard/escalamientos.
15. **S25-AC-015:** Ruta, aria-current, breadcrumb y título activo derivan del mismo manifiesto.
16. **S25-AC-016:** Una URL sin permiso falla cerrada aunque el ítem no viaje en navegación.
17. **S25-AC-017:** Página y API aplican la misma guarda y no divergen.
18. **S25-AC-018:** Cambiar proyecto invalida tablero, selección, drawer, hilos y errores previos.
19. **S25-AC-019:** Logout o expiración cancela lecturas y limpia todo contexto S25.
20. **S25-AC-020:** Todo /admin/ permanece fuera del frente.
21. **S25-AC-021:** GET /api/lps/escalamientos es el snapshot canónico nuevo del tablero.
22. **S25-AC-022:** La lectura incluye sólo estado Activo del proyecto autorizado.
23. **S25-AC-023:** Toda consulta operativa usa queryWithProject o frontera equivalente con ProjectScope.
24. **S25-AC-024:** El join de alerta y actividad conserva project_id + unique_id + semana.
25. **S25-AC-025:** Cada ítem entrega alerta, actividad, semana, módulo, trigger, nivel y fechas normalizados.
26. **S25-AC-026:** El nombre de actividad se convierte a texto plano en servidor.
27. **S25-AC-027:** HTML entities y espacios repetidos se normalizan sin insertar HTML.
28. **S25-AC-028:** Actividad, bloqueo y subcontratista aplican fallbacks explícitos y no engañosos.
29. **S25-AC-029:** module acepta exclusivamente PG, PI o PS.
30. **S25-AC-030:** currentLevel acepta únicamente enteros 1 a 5.
31. **S25-AC-031:** Los niveles 1 y 2 se muestran en la columna de atención del Director.
32. **S25-AC-032:** Nivel actual, columna de atención y siguiente nivel son campos distintos.
33. **S25-AC-033:** Los cuatro niveles de atención tienen metadatos, orden y conteos estables.
34. **S25-AC-034:** totals.active coincide con la suma exacta de conteos por columna.
35. **S25-AC-035:** Orden canónico: nivel descendente, detonación ascendente y alertId ascendente como desempate.
36. **S25-AC-036:** alertId y activityId son enteros positivos y no se intercambian.
37. **S25-AC-037:** detonatedAt y lastEscalatedAt son ISO-8601 o null.
38. **S25-AC-038:** terminal es true sólo en currentLevel 5.
39. **S25-AC-039:** nextLevel y nextLevelLabel son null en terminal y válidos en los demás niveles.
40. **S25-AC-040:** El snapshot no expone justificación de cierre, usuario de cierre ni datos de alertas cerradas.
41. **S25-AC-041:** El JSON no expone db, prefijos de tabla, proyecto_id interno ni IDs de membresía.
42. **S25-AC-042:** El JSON no expone HTML crudo procedente de actividad u observaciones.
43. **S25-AC-043:** El vacío global se distingue de una columna vacía dentro de un tablero con crisis.
44. **S25-AC-044:** Una lectura GET no ejecuta INSERT, UPDATE, DELETE, DDL, cron ni recomputación.
45. **S25-AC-045:** El endpoint del tablero funciona sin que sesión tenga una semana positiva.
46. **S25-AC-046:** El envelope canónico usa ok/data/meta y requestId sin mezclar respuesta legacy.
47. **S25-AC-047:** El esquema Zod del snapshot es strict y los tipos salen de z.infer.
48. **S25-AC-048:** Ningún componente S25 llama fetch; todo transporte pasa por cliente.ts.
49. **S25-AC-049:** 401, 403, 404, 409, 422, 500 y contrato inválido producen errores tipados.
50. **S25-AC-050:** Cache y claves remotas se particionan por sesión y proyecto, nunca sólo por URL.
51. **S25-AC-051:** Una petición reemplazada se aborta y una respuesta obsoleta se ignora.
52. **S25-AC-052:** Recargar es una acción manual visible que anuncia inicio y resultado.
53. **S25-AC-053:** Toda mutación exitosa vuelve a leer snapshot y target autoritativos.
54. **S25-AC-054:** alerta en la query de página es estado de vista, no autoridad de acceso.
55. **S25-AC-055:** Un deep link ausente o ya cerrado muestra estado recuperable sin seleccionar otra tarjeta.
56. **S25-AC-056:** El target canónico del drawer se resuelve por alertId en servidor.
57. **S25-AC-057:** El alertId debe pertenecer al proyecto activo antes de consultar o mutar.
58. **S25-AC-058:** Semana y activityId del drawer se derivan de la alerta, no del shell ni del body.
59. **S25-AC-059:** El módulo para registrar/avisar se deriva de la alerta y no se confía al cliente React.
60. **S25-AC-060:** GET /api/lps/comments acepta alerta_id como contrato canónico S25.
61. **S25-AC-061:** POST /api/lps/comments/add acepta alerta_id y comentario como target canónico.
62. **S25-AC-062:** parent_id se valida dentro del mismo proyecto, alerta, actividad y semana.
63. **S25-AC-063:** escalamiento_id legacy se valida contra la misma alerta cuando se usa en compatibilidad.
64. **S25-AC-064:** menciones acepta sólo una forma JSON documentada y roles canónicos permitidos.
65. **S25-AC-065:** Las menciones se presentan como metadatos y nunca se afirman enviadas.
66. **S25-AC-066:** POST /api/lps/comments y POST /api/lps/crisis sobreviven como aliases mientras haya consumidores.
67. **S25-AC-067:** Aliases y contrato React delegan al mismo servicio, política y presenter.
68. **S25-AC-068:** Las tres mutaciones conservan CSRF lps_drawer o el token compartido equivalente de T02.
69. **S25-AC-069:** Cliente y servidor no reintentan automáticamente comentario, aviso ni cierre.
70. **S25-AC-070:** Leer el hilo depende de capacidad de lectura, no de capacidad de edición.
71. **S25-AC-071:** Comentar depende de edición semanal efectiva.
72. **S25-AC-072:** Avisar/registrar SOS depende de edición semanal efectiva.
73. **S25-AC-073:** Cerrar crisis depende de edición semanal efectiva.
74. **S25-AC-074:** Las acciones viajan resueltas por servidor en el snapshot y en el target.
75. **S25-AC-075:** El servidor calcula elegibilidad del actor antes de ofrecer comentario o cierre.
76. **S25-AC-076:** La elegibilidad conserva el ID numérico que usa legacy y verifica su FK exacta en el proyecto.
77. **S25-AC-077:** Un actor incompatible recibe code PROFILE_REQUIRED y no una falla genérica.
78. **S25-AC-078:** PROFILE_REQUIRED conserva tablero, drawer e hilo en modo lectura.
79. **S25-AC-079:** No se resuelve actor por coincidencia de nombre, correo o cargo.
80. **S25-AC-080:** Cambiar el modelo de identidad del actor exige un frente de datos/producto separado.
81. **S25-AC-081:** El comentario se recorta y debe seguir siendo no vacío.
82. **S25-AC-082:** El hilo conserva raíces y respuestas en orden cronológico.
83. **S25-AC-083:** El autor mostrado se limita a nombre/cargo disponibles; no expone correo, usuario ni ID.
84. **S25-AC-084:** El éxito de comentario devuelve commentId positivo y el hilo autoritativo se recarga.
85. **S25-AC-085:** Registrar sobre una alerta activa no afirma cambiar nivel jerárquico.
86. **S25-AC-086:** Después de avisar no hay movimiento optimista de tarjeta.
87. **S25-AC-087:** trigger sólo usa MANUAL, SOS-RES, SOS-DIR, SOS-COO o SOS-GER.
88. **S25-AC-088:** Modo simulación se etiqueta como copiar solamente y no ejecuta la mutación.
89. **S25-AC-089:** Modo operativo distingue registro aceptado de entrega externa no comprobada.
90. **S25-AC-090:** Una alerta en nivel 5 no ofrece siguiente nivel.
91. **S25-AC-091:** Sin contacto canónico, WhatsApp/email caen explícitamente a copiar texto.
92. **S25-AC-092:** No se inventan teléfonos, correos ni joins de contacto.
93. **S25-AC-093:** Cerrar exige justificación recortada de al menos 100 caracteres.
94. **S25-AC-094:** Cerrar una alerta ausente o cerrada devuelve conflicto tipado y fuerza recarga.
95. **S25-AC-095:** El h1 Escalamientos vive dentro de AppShell y no crea otro documento/shell.
96. **S25-AC-096:** Proyecto activo y alcance todas las semanas son visibles.
97. **S25-AC-097:** El selector semanal del shell no aparece como filtro local de S25.
98. **S25-AC-098:** Total activo y cuatro conteos por nivel son visibles y coherentes.
99. **S25-AC-099:** Las columnas representan atención de Director, Coordinación, Gerencia de Construcción y Gerencia General.
100. **S25-AC-100:** Cada tarjeta muestra semana, actividad, ID visible, bloqueo, responsable, trigger y nivel actual.
101. **S25-AC-101:** Cada tarjeta completa es un button nativo con nombre accesible.
102. **S25-AC-102:** Una sola activación de tarjeta abre el drawer; no requiere un segundo trigger oculto.
103. **S25-AC-103:** Al cerrar drawer, foco vuelve a la tarjeta si aún existe o al encabezado del nivel.
104. **S25-AC-104:** En 1180 y 1440 px se ven cuatro columnas sin comprimir texto de forma ilegible.
105. **S25-AC-105:** Entre 768 y 1179 px el tablero usa una composición 2×2 sin overflow de página.
106. **S25-AC-106:** Por debajo de 768 px se monta una vista de un nivel a la vez con selector accesible.
107. **S25-AC-107:** En móvil siguen visibles los cuatro conteos y el total.
108. **S25-AC-108:** 390, 480, 768, 1180 y 1440 px no pierden acciones ni contenido.
109. **S25-AC-109:** 320 px y zoom 200% no producen overflow horizontal de página.
110. **S25-AC-110:** Oscuro es tema inicial/fallback.
111. **S25-AC-111:** Claro conserva la misma información, estados, foco y acciones.
112. **S25-AC-112:** Todos los estilos consumen public/css/tokens.css.
113. **S25-AC-113:** No hay colores literales, estilos inline de color, !important ni familia local de tokens.
114. **S25-AC-114:** Loading usa skeletons sin crisis ficticias.
115. **S25-AC-115:** Refreshing conserva el snapshot y marca el contenido como en actualización.
116. **S25-AC-116:** Vacío global explica que no hay crisis activas sin afirmar que nunca existieron.
117. **S25-AC-117:** Cada nivel vacío usa un estado compacto sin ocultar los otros niveles.
118. **S25-AC-118:** Red/offline, 401, 403, 404/409, 422 y 5xx tienen recuperación distinta.
119. **S25-AC-119:** El error de red conserva el último snapshot válido y ofrece reintento explícito.
120. **S25-AC-120:** Conteos, recarga, mutaciones y cambios del drawer se anuncian con aria-live.
121. **S25-AC-121:** Existe un solo h1 y una jerarquía h2/h3 válida.
122. **S25-AC-122:** AppShell conserva nav/main/aside/dialog landmarks sin duplicarlos.
123. **S25-AC-123:** Ningún nivel, trigger, error o acción depende sólo del color o emoji.
124. **S25-AC-124:** Controles touch miden al menos 44×44 px.
125. **S25-AC-125:** Tab, Shift+Tab, Enter, Space y Escape cubren tablero y drawer.
126. **S25-AC-126:** prefers-reduced-motion desactiva desplazamientos no esenciales.
127. **S25-AC-127:** Axe serious/critical es cero en tablero y drawer.
128. **S25-AC-128:** document.title identifica Escalamientos y el proyecto sin exponer IDs.
129. **S25-AC-129:** Atrás usa historial SPA seguro y cae a /programacion-semanal cuando no hay entrada interna.
130. **S25-AC-130:** El frente no modifica RLS, ProjectSqlGuard ni runtime-boundary.
131. **S25-AC-131:** El frente no modifica schema, migraciones, tablas, columnas, índices, triggers ni FKs.
132. **S25-AC-132:** El frente no modifica datos, seeds, fixtures persistentes, grants, usuarios ni credenciales.
133. **S25-AC-133:** escalarAlertasActivas no se invoca, programa ni expone desde S25.
134. **S25-AC-134:** La campana y sus endpoints siguen siendo propiedad única de T01/T02.
135. **S25-AC-135:** S25 conserva la entrada de notificaciones del shell sin duplicar UI.
136. **S25-AC-136:** S25 no crea polling ni marca notificaciones como leídas.
137. **S25-AC-137:** Una prueba de caracterización fija rutas, campos, orden, fallbacks y ausencia de filtros/export.
138. **S25-AC-138:** Una prueba PHP contractual cubre GET /api/lps/escalamientos y sus errores.
139. **S25-AC-139:** Pruebas PHP cubren target por alerta, aliases, CSRF, RBAC y PROFILE_REQUIRED sin DML.
140. **S25-AC-140:** Pruebas puras cubren agrupación 1→2, niveles, orden, terminal y conteos.
141. **S25-AC-141:** Vitest cubre Zod, gateway, stale/abort y contrato de query.
142. **S25-AC-142:** Testing Library cubre loading, vacío, error, lectura, acciones y foco.
143. **S25-AC-143:** Playwright afirma sesión/proyecto, URL, h1 y tablero antes de probar acciones.
144. **S25-AC-144:** Si la puerta dev está cerrada, la prueba falla/omite explícitamente; login no cuenta como verde.
145. **S25-AC-145:** Los escenarios de navegador usan respuestas interceptadas y prohíben mutaciones reales.
146. **S25-AC-146:** escalamientos-acciones.spec.mjs no se ejecuta mientras siembre o borre alertas sin autorización.
147. **S25-AC-147:** El route cut exige contratos, paridad, temas, responsive, a11y y caller census verdes.
148. **S25-AC-148:** VIEW-12 se retira tras el corte; VIEW-28 y aliases esperan el gate transversal T02.
149. **S25-AC-149:** Rollback cambia sólo mapa de rutas/código y el cierre registra SHA, pruebas y cero DDL/DML.
150. **S25-AC-150:** La leyenda explica niveles, trigger, simulación, perfil requerido y recarga autoritativa.

## Entregas verticales

### Entrega 1 — Frontera y lectura útil

- capacidad de página/API alineada;
- snapshot global scoped;
- jerarquía, orden, conteos y fallbacks;
- contrato PHP y Zod.

### Entrega 2 — Tablero React

- gateway/estado;
- ruta, sidebar y regreso;
- tablero desktop/tablet/móvil;
- loading, vacío, error, recarga, oscuro/claro y accesibilidad.

### Entrega 3 — Target y drawer

- target por alerta;
- semana/módulo/actividad autoritativos;
- hilo, respuestas, menciones y perfil requerido;
- foco, deep link y errores parciales.

### Entrega 4 — Acciones, corte y retiro

- copiar/avisar sin prometer entrega;
- cierre con 100 caracteres y stale handling;
- browser interceptado;
- caller census, route cut, VIEW-12, rollback y evidencia sin DML.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| semana del shell contamina hilo histórico | target por alerta y semana server-side |
| página filtra menos que API | política única read para ambas |
| nivel 1 parece nivel 2 | nivel actual y estación separados |
| aviso se interpreta como escalamiento | copy exacto y refetch autoritativo |
| mención se interpreta como notificación | frontera T02 y leyenda explícita |
| actor rompe FK | elegibilidad previa + PROFILE_REQUIRED |
| se intenta reparar actor por texto | prohibición y test de no mapping |
| deep link cruza proyecto | alertId + ProjectScope siempre |
| cuatro columnas colapsan móvil | 2×2 tablet y un nivel móvil |
| falso verde en login | precondiciones de sesión/URL/h1/tablero |
| test existente escribe | no ejecutar; reemplazar por fake/interceptado |
| VIEW-28 se retira prematuramente | caller census transversal T02 |
| autoescalamiento se activa por accidente | invariant de cero callers nuevos |
| datos reales entran evidencia | fixtures sintéticos e interceptados |

## Alternativas descartadas

- Reutilizar VIEW-12 dentro de iframe: conserva documento, jQuery y frontera rota.
- Filtrar por semana activa: pierde crisis históricas que legacy muestra.
- Enviar semana desde tarjeta y confiarla: el server ya posee el dato.
- Crear capacidad lps.escalamientos: no hace falta y ampliaría RBAC.
- Transformar el tablero en kanban editable: no existe esa operación.
- Mover tarjeta tras SOS: registerCrisis no cambia nivel.
- Activar escalarAlertasActivas desde GET o UI: side effect y decisión operacional.
- Arreglar FK en esta migración: requiere datos y decisión de actor.
- Resolver profesional por nombre/email: ambiguo y altera semántica.
- Mostrar contactos desde joins heurísticos: no existe fuente canónica.
- Duplicar la campana dentro de S25: dos owners y dos pollers.
- Añadir filtros/export “por completar”: no son paridad y amplían alcance.
- Ejecutar el browser test que siembra: viola la prohibición de DML.
- Mantener cuatro columnas en 390 px: contenido ilegible.
- Crear un segundo cliente HTTP: contradice T01.

## Decisiones pendientes

Ninguna para la migración S25. Cambiar identidad de actor, activar autoescalamiento, conectar
contactos o convertir menciones en notificaciones son decisiones independientes de producto/datos
y no bloquean esta entrega porque el spec conserva el comportamiento seguro y observable actual.

## Autor revisión

- Rutas, controladores, servicio, vista, CSS, drawer, notificaciones, RBAC, schema y tests
  contrastados.
- Brecha de semana y brecha FK resueltas sin DDL/DML ni ampliación de permisos.
- Sidebar, claro/oscuro y frontera RLS incluidos.
- 150 criterios numerados, únicos y trazables.
- Sin implementación, datos, RLS, /admin/, commit ni publicación.
