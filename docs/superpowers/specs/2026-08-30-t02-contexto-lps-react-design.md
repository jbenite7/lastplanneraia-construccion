---
capa: fuente
tipo: spec
estado: vigente
id: T02
fecha: 2026-08-31
superficie: contexto-lps-compartido
rutas:
  - "/api/lps/comments"
  - "/api/lps/comments/add"
  - "/api/lps/crisis"
  - "/api/lps/crisis/register"
  - "/api/lps/crisis/close"
  - "/api/notifications/unread"
  - "/api/notifications/read"
depende_de: [T01]
consumido_por: [S05, S07, S08, S25, S26]
views: [VIEW-28]
areas: [arquitectura, lps, rbac, design-system]
fuente: "auditoria de VIEW-28, lps_drawer.js, adapters CSS, LpsApiController, LpsService, NotificationController, NotificationService, NotificationType, ProjectScope, RbacCatalog, RestrictionConfigResolver, rutas, tests, matriz de severidad, T01, S05, S07, S08 y S25 en shell-minimo-react, 2026-08-31"
resumen: "Contrato transversal T02 para un único drawer contextual LPS React y una única bandeja de notificaciones del shell: contexto tipado, severidad e ITR compartidos, hilos, menciones, simulación/SOS, crisis, digest, targets server-authoritative, oscuro/claro y responsive, sin cambiar RLS ni datos."
---

# T02 — Contexto LPS, drawer y notificaciones compartidas

> **Estado:** diseño técnico autorrevisado y decision-complete. Esta spec no autoriza
> implementación, commit, push, PR, publicación, deploy, DDL/DML, cambios de RLS, schema,
> capacidades, usuarios, credenciales ni trabajo en `/admin/`. Su plan se escribe a continuación
> con `superpowers:writing-plans`.

## Relación con el programa

T02 no es una página. Es el contrato compartido que impide que cuatro migraciones de producto creen
cuatro versiones incompatibles del Cajón Contextual LPS. S26 consume sus primitivas y censo en el
laboratorio, sin convertirse en un quinto flujo operativo.

| Consumidor | Superficie | Target |
|---|---|---|
| S05 | Programa General | actividad PG de proyecto/semana autorizados |
| S07 | Programación Intermedia | actividad PI de proyecto/semana autorizados |
| S08 | Programación Semanal | actividad PS y fase semanal autorizadas |
| S25 | Escalamientos | alerta activa con semana/módulo persistidos |

T02 posee VIEW-28. S05/S07/S08/S25 poseen sus filas, tarjetas, filtros y rutas. T01 posee AppShell,
sesión, proyecto, semana, tema y cliente HTTP; reserva un slot para el provider y otro para la
bandeja de avisos. T03 posee filtros/drawers BI y no reutiliza las reglas de crisis LPS.

La bandeja de `system_notifications` se incluye porque T01 la reserva expresamente para T02 y porque
legacy la monta junto al shell. No convierte menciones en mensajes ni hace de S25 un segundo inbox.

## Resultado buscado

La aplicación debe tener:

1. un provider y un drawer React para los cuatro consumidores;
2. un modelo de actividad/alerta que no dependa de Handsontable;
3. una sola matriz de severidad e ITR para Construcción y Pre‑Construcción;
4. diagnóstico, restricciones, comentarios raíz, respuestas y menciones;
5. registro/aviso SOS, simulación, cierre y digest con semántica honesta;
6. targets resueltos por servidor y acciones derivadas de capacidades reales;
7. una bandeja de notificaciones de identidad integrada al shell;
8. transporte exclusivo por `cliente.ts` y Zod;
9. foco, borradores, oscuro/claro y composición responsive;
10. coexistencia segura hasta que el cuarto consumidor legacy corte.

## Alcance

### Incluido

- VIEW-28 y `public/js/modules/lps_drawer.js` como runtime a sustituir;
- adaptadores CSS exclusivos del drawer después del censo cero;
- provider, trigger, dialog/sheet, diagnóstico, ITR y restricciones;
- severidad PG, PI y PS conforme a la matriz vigente;
- comentarios, respuestas de un nivel y metadata de menciones;
- modo simulación, texto SOS, portapapeles y handoff WhatsApp/correo;
- registro y cierre de crisis;
- digest local sobre dataset autorizado suministrado por el consumidor;
- enlace BI sólo cuando T01 entrega navegación autorizada;
- siete rutas HTTP existentes de LPS/notificaciones;
- target por actividad y target por alerta;
- elegibilidad del actor sin migración de identidad;
- bandeja, badge, actualización única y marcar leída;
- compatibilidad legacy y gate de retiro de cuatro consumidores;
- pruebas puras, PHP, frontend y navegador interceptado.

### Excluido

- filas/tablas/tarjetas de S05, S07, S08 o S25;
- tablero de Escalamientos;
- activar o programar `escalarAlertasActivas()`;
- enviar WhatsApp, email, push o una notificación por servidor;
- inventar contactos para el dashboard;
- transformar menciones en entregas;
- historial de crisis cerradas;
- más de un nivel de reply;
- preferencias, borrar o marcar todos los avisos;
- nuevas capacidades `lps.drawer.*`;
- cambiar la identidad persistida de comentarios/cierre;
- cambiar schema, FKs, RLS o datos;
- `/admin/`.

## Hallazgos de auditoría

### VIEW-28 y JavaScript

VIEW-28 tiene trigger, dialog, enlace BI, diagnóstico, ITR, SOS, preview, comentarios, cierre,
digest y modo simulación. `lps_drawer.js` tiene 1.588 líneas y mezcla:

- lectura del DOM y aliases de columnas;
- cálculo de ratios, restricciones y severidad;
- adaptación de tres Handsontable y una superficie sin grid;
- estado remoto y abort de comentarios;
- form-data/CSRF y cuatro llamadas `fetch` directas;
- render con `innerHTML`;
- foco, inert y responsive;
- clipboard/WhatsApp/mailto;
- digest sobre `getSourceData()`.

El target elimina esas mezclas: dominio puro, gateway HTTP, provider React y adapters de consumidor
se prueban por separado.

### Brecha de ITR

La biblia `docs/matriz-severidad-cajon-contextual-lps.md` declara que las restricciones blandas no
bloquean inicio. El JS actual llama `getAllRestrictions()` y usa duras más blandas para
`isComplete` y el denominador. `LpsService::calculateLiveITR()` también usa todas.

T02 fija dos resultados:

- `enabling`: sólo duras, decide habilitación/severidad;
- `informational`: blandas, se muestra sin bloquear.

No cambia la fórmula operacional guardada por otros módulos; cambia el diagnóstico del drawer para
obedecer su autoridad documental y los state machines ya existentes.

### Contexto y alcance

`LpsApiController::getContext()` exige una semana positiva, busca proyecto por nombre y filtra
`Area='Construccion'`. Eso falla para Pre‑Construcción y para S25, que abre alertas históricas de
cualquier semana. El nuevo resolver usa `ProjectScope`:

- target de alerta → la alerta aporta semana, actividad y módulo;
- target de actividad → el proyecto/sesión y adapter del módulo validan la actividad;
- no acepta proyecto, db, actor o semana autoritativa del cliente.

### Actor

El código pasa un ID de `general_usuarios` a columnas cuya compatibilidad real puede depender de un
perfil profesional. La falla es genérica. T02 adopta el contrato ya cerrado por S25:
`eligible|profile_required|forbidden`. No crea perfiles, no busca por nombre y no repara FKs.

### Notificaciones

`notifications.js` monta dos listas DOM, llama `fetch` directamente, interpola HTML, actualiza cada
120 segundos y marca leída sin CSRF. `NotificationService` sí filtra por `user_id`, pero
`markAsRead()` devuelve el resultado de `execute()` aunque no haya fila propia afectada.

El target conserva la semántica de inbox personal y el intervalo observable, añade CSRF, usa una
sola lista React y mantiene respuesta idempotente/no enumerativa.

## Decisiones cerradas

### D-T02-01 — Provider único, adapters pequeños

`LpsDrawerProvider` vive una vez en AppShell. Los módulos llaman una API React:

```ts
openLpsContext({
  target,
  module,
  activity,
  state,
  restrictions,
  contacts,
  actions,
  digest,
  returnFocus,
})
```

Cada consumidor normaliza sus datos. T02 no recibe una instancia de tabla ni conoce columnas
legacy. S25 abre por `alertId` y deja que el hilo devuelva su target completo.

### D-T02-02 — Target discriminado y server-authoritative

```ts
type LpsTarget =
  | { kind: 'activity'; activityId: number; module: 'PG' | 'PI' | 'PS' }
  | { kind: 'alert'; alertId: number }
```

No viajan proyecto, prefijo, usuario o semana como autoridad. La semana visible puede formar parte
del contexto para explicar la actividad, pero el servidor la vuelve a derivar.

### D-T02-03 — Capacidad existente, no rol cliente

- leer hilo: `lps.programacion_semanal.ver`;
- comentar, registrar/avisar y cerrar: `lps.programacion_semanal.editar`;
- actor incompatible puede reducir comentario/cierre;
- terminal jerárquico puede reducir aviso;
- overrides RBAC prevalecen.

PHP devuelve acciones efectivas. React no contiene matrices por rol.

### D-T02-04 — Restricciones duras deciden

La severidad consume un `RestrictionConfig` validado del proyecto. Duras deciden `isReady` e ITR;
blandas son evidencia informativa. No existe fallback construction silencioso si el contrato falta:
la sección muestra un error parcial y conserva el resto del drawer.

### D-T02-05 — Una sola matriz de severidad

Orden:

1. SOS/alerta activa;
2. header;
3. matriz PS cuando módulo PS;
4. matriz PG/PI por estado, horizonte, ruta crítica, avance y duras;
5. normal/neutral.

La primera regla aplicable gana. Rojo sólo para crisis; futuro 4–6 semanas nunca es rojo por estar
incompleto.

### D-T02-06 — Hilo de un nivel

Legacy muestra roots y replies, pero no renderiza nietos. T02 valida que `parent_id` sea una raíz del
mismo target y rechaza encadenar reply sobre reply. Evita persistir contenido invisible sin ampliar
producto.

### D-T02-07 — Menciones no son entrega

El cliente puede extraer roles canónicos de `@TOKEN` y enviarlos como
`{"roles":["D","OT"]}`. El servidor valida/deduplica. Tokens desconocidos quedan como texto.
`menciones` continúa siendo metadata JSON; no llama `NotificationService`.

### D-T02-08 — Compatibilidad aditiva de endpoints

Las rutas se mantienen. Mientras exista legacy:

- éxito conserva `respuesta:"OK"` y las claves leídas por `lps_drawer.js`;
- error conserva `respuesta:"ERROR"` y `mensaje`;
- se añaden `ok`, target, acciones, error tipado y meta;
- HTTP usa el status correcto;
- el gateway React normaliza a modelos camelCase.

No se necesita una API paralela ni content negotiation. Los aliases POST esperan el censo cero.

### D-T02-09 — Mutaciones autoritativas, sin optimistic fake

Comentario, registro y cierre tienen CSRF, no retry automático y recargan el recurso. La UI conserva
el borrador en error. Sólo el snapshot/hilo actualizado retira un comentario pendiente o una alerta.

### D-T02-10 — Simulación primero y lenguaje honesto

`lps_simulated_mode` sigue en localStorage y default `true`. En simulación se prepara/copia, sin
mutación. En modo operativo se registra el aviso y se abre un canal externo si existe contacto.
Ninguna respuesta se llama “enviado”; el producto no controla la entrega.

### D-T02-11 — Digest in-memory, no grid

El consumidor entrega la lista visible ya autorizada. Una función pura agrupa bloqueos críticos. No
se monta Handsontable oculto, no se crea endpoint y no se envían filas.

### D-T02-12 — Inbox de identidad, no de proyecto

Las notificaciones pertenecen al usuario autenticado y pueden abarcar proyectos. `project_id` es
compatibilidad de agrupación con db-prefix y no viaja a React. La selección de proyecto no filtra el
inbox. Cerrar sesión sí lo vacía.

### D-T02-13 — Un solo ciclo de actualización

El provider de notificaciones consulta al entrar y cada 120 segundos sólo si la pestaña está
visible. Usa `X-AIA-Idle-Refresh: 0`, AbortSignal y retry manual. No hay timers por módulo.

### D-T02-14 — Dos gates de entrega

- **T02-A, plataforma:** dominio, backend compartido, React provider, bandeja, harness y
  compatibilidad legacy. Se puede publicar antes de migrar los cuatro consumidores.
- **T02-R, retiro:** el último consumidor corta; un censo exacto llega a cero; se retiran VIEW-28,
  JS y adapters exclusivos.

La presencia deliberada de compatibilidad después de T02-A no es trabajo incompleto ni bloquea S05.

## Modelo frontend

### LpsActivityContext

| Campo | Tipo | Autoridad |
|---|---|---|
| `target` | union discriminada | consumidor + validación server |
| `module` | PG/PI/PS/ESC | consumidor |
| `activity.id` | int positivo | snapshot módulo |
| `activity.label` | texto plano | snapshot módulo |
| `activity.state` | key/label/phase/actions | state machine propietaria |
| `activity.progress` | ratio/display | snapshot módulo |
| `activity.critical` | bool | snapshot módulo |
| `activity.isHeader` | bool | snapshot módulo |
| `restrictions.config` | hard/soft/thresholds | servidor |
| `restrictions.values` | record tipado | snapshot módulo |
| `crisis` | alertId/active/level opcional | servidor |
| `contacts` | phone/email opcionales | dataset autorizado |
| `actions` | booleanos efectivos | servidor |
| `digest` | proveedor opcional | consumidor |
| `hiddenByFilters` | bool | consumidor |
| `returnFocus` | ref/fallback | consumidor |

No se conserva la fila cruda ni un setter de grilla.

### Estado del provider

```ts
type DrawerState =
  | { status: 'closed' }
  | { status: 'opening'; context: LpsActivityContext }
  | { status: 'loading'; context: LpsActivityContext }
  | { status: 'ready'; context: LpsActivityContext; thread: LpsThread }
  | { status: 'refreshing'; context: LpsActivityContext; thread: LpsThread }
  | { status: 'partial-error'; context: LpsActivityContext; thread?: LpsThread; error: ApiError }
```

Mutaciones tienen subestado propio para no bloquear todo el panel.

## Contratos HTTP

### Target de lectura

React usa una de estas formas:

```text
GET /api/lps/comments?alerta_id=901
GET /api/lps/comments?consecutivo=4102&modulo=PG
```

Durante convivencia se admite `consecutivo` sin `modulo` sólo para el JS legacy y se resuelve por
proyecto/semana scoped. `alerta_id` no puede combinarse con `consecutivo`.

### GET /api/lps/comments

Respuesta aditiva compatible:

```json
{
  "respuesta": "OK",
  "ok": true,
  "data": [
    {
      "id": 81,
      "comentario": "Texto de prueba",
      "created_at": "2026-08-31 09:00:00",
      "autor_nombre": "Profesional AIA",
      "autor_cargo": "Residente",
      "menciones": {"roles": ["D"]},
      "respuestas": []
    }
  ],
  "target": {
    "kind": "alert",
    "alertId": 901,
    "activityId": 4102,
    "week": 14,
    "module": "PS"
  },
  "actions": {
    "read": true,
    "comment": true,
    "notifyNext": true,
    "close": true,
    "actorWriteBlock": "none"
  },
  "crisisAlert": {
    "id": 901,
    "active": true,
    "level": 1,
    "nextLevelLabel": "Director de Obra"
  },
  "meta": {"requestId": "opaque"}
}
```

La rama legacy puede seguir recibiendo columnas adicionales dentro de `data` mientras exista. El
presenter React las elimina antes del modelo. Los errores nunca devuelven excepciones crudas.

### POST /api/lps/comments/add y alias

`application/x-www-form-urlencoded`:

| Campo | Target actividad | Target alerta |
|---|---:|---:|
| `consecutivo` | requerido | omitido |
| `modulo` | requerido en React | omitido |
| `alerta_id` | omitido | requerido |
| `comentario` | requerido | requerido |
| `parent_id` | opcional | opcional |
| `menciones` | JSON opcional | JSON opcional |
| `_csrf_token` | requerido | requerido |

Éxito conserva `comment_id` para legacy y añade `data.commentId`/target.

### POST /api/lps/crisis/register y alias

- Target actividad: `consecutivo + modulo`.
- Target alerta: `alerta_id`.
- `trigger` enum.
- CSRF drawer.

El servidor crea/asegura una alerta activa y banderas, pero no cambia nivel. Devuelve el target
autoritativo y si la alerta ya existía.

### POST /api/lps/crisis/close

- `alerta_id` positivo;
- `justificacion` trim de al menos 100;
- CSRF drawer.

El target debe seguir activo. Éxito no obliga a una mutación local: React recarga.

### Errores LPS

| HTTP | code | Uso |
|---:|---|---|
| 401 | `SESSION_REQUIRED` | sesión inválida |
| 403 | `CAPABILITY_REQUIRED` | acción no autorizada |
| 403 | `CSRF_INVALID` | token inválido |
| 404 | `LPS_TARGET_NOT_FOUND` | target ajeno/inexistente indistinguible |
| 409 | `LPS_TARGET_STALE` | alerta cerrada/cambio de contexto |
| 409 | `PROFILE_REQUIRED` | actor incompatible |
| 422 | `VALIDATION_FAILED` | input inválido |
| 500 | `LPS_READ_FAILED` | lectura controlada |
| 503 | `SERVICE_UNAVAILABLE` | recuperación manual |

Forma aditiva:

```json
{
  "respuesta": "ERROR",
  "ok": false,
  "mensaje": "No fue posible completar la acción.",
  "error": {
    "code": "PROFILE_REQUIRED",
    "message": "La bitácora queda disponible en modo lectura.",
    "fields": {}
  },
  "meta": {"requestId": "opaque"}
}
```

### Notificaciones

`GET /api/notifications/unread` conserva `success` y snake_case durante convivencia; Zod transforma:

```json
{
  "success": true,
  "ok": true,
  "data": [
    {
      "id": 31,
      "type": "pi_restriction_lowered",
      "title": "Restricción bajó de nivel",
      "message": "Mensaje de prueba",
      "item_count": 2,
      "created_at": "2026-08-31 09:00:00"
    }
  ]
}
```

No viaja `project_id`. `POST /api/notifications/read` recibe JSON
`{"id":31,"_csrf_token":"..."}` o token por header normalizado de `cliente.ts`. La respuesta es
idempotente y no revela si un ID pertenece a otra cuenta.

## Arquitectura backend

### Crear

- `src/Services/Lps/LpsTarget.php`.
- `src/Services/Lps/LpsTargetResolver.php`.
- `src/Services/Lps/LpsActivityTargetAdapter.php`.
- `src/Services/Lps/LpsActionPolicy.php`.
- `src/Services/Lps/LpsActorEligibility.php`.
- `src/Services/Lps/LpsThreadService.php`.
- `src/Services/Lps/LpsThreadPresenter.php`.
- `src/Services/Lps/LpsApiError.php`.

### Modificar por delegación

- `LpsApiController`: transporte, target, política y presenter;
- `LpsService`: compatibilidad hacia servicios pequeños;
- `NotificationController`: sesión, CSRF, status y forma aditiva;
- `NotificationService`: interfaces inyectables y operaciones de identidad no enumerativas;
- `public/index.php` sólo si hace falta documentar aliases, no para crear rutas paralelas.

No se modifica DataScope, TableResolver, schema o `NotificationType` salvo un bug de contrato
demostrado por test.

## Arquitectura frontend

```text
frontend/src/shared/lps/
├── api/
│   ├── esquemas.ts
│   ├── hilo.ts
│   ├── crisis.ts
│   ├── notificaciones.ts
│   └── *.test.ts
├── dominio/
│   ├── contexto.ts
│   ├── restricciones.ts
│   ├── severidad.ts
│   ├── diagnostico.ts
│   ├── digest.ts
│   └── *.test.ts
├── estado/
│   ├── LpsDrawerProvider.tsx
│   ├── useLpsDrawer.ts
│   ├── useHiloLps.ts
│   └── useNotificaciones.ts
├── componentes/
│   ├── CajonContextualLps.tsx
│   ├── DisparadorLps.tsx
│   ├── DiagnosticoLps.tsx
│   ├── IndicadorRestricciones.tsx
│   ├── HiloLps.tsx
│   ├── FormularioComentario.tsx
│   ├── AccionesSos.tsx
│   ├── CierreCrisis.tsx
│   ├── DigestLps.tsx
│   └── BandejaNotificaciones.tsx
└── lps-contexto.css
```

Los componentes reciben estado/acciones. Sólo los gateways llaman `pedir`/extensiones de
`cliente.ts`.

## Experiencia y composición

### Contenido

1. encabezado de actividad, estado y severidad;
2. contexto de alerta/nivel cuando existe;
3. diagnóstico;
4. restricciones duras e informativas;
5. enlace BI autorizado;
6. hilo y reply;
7. acciones SOS/preview;
8. cierre;
9. digest cuando existe;
10. modo simulación y explicación.

El orden puede compactarse responsive, pero ninguna función desaparece.

### Responsive

- **1180+**: AppShell cambia a grid `minmax(0,1fr) + panel`; el módulo refluye. No hay
  `body.padding-right`.
- **768–1179**: side-sheet modal superpuesto, ancho máximo por tokens.
- **<768**: sheet de ancho completo y alto útil con `dvh`/safe-area; header/footer sticky y body
  scrollable.
- **320/200%**: una columna, sin overflow horizontal y acciones apiladas.

No se montan dos drawers.

### Accesibilidad

- dialog con nombre/descripción;
- inert y focus trap;
- foco inicial y retorno;
- protección de borradores;
- botones nativos;
- estados y conteos anunciados;
- textarea con label/errores;
- reply con relación visible;
- contraste y focus visible en ambos temas;
- reduced motion;
- emoji siempre decorativo o acompañado de texto.

## Seguridad, privacidad, RLS y datos

- `ProjectScope` antes de tablas de actividad, comentarios o crisis.
- `project_id` en cada query operacional.
- target IDs combinados con el proyecto.
- parent combinado con target.
- usuario/rol de sesión, nunca body.
- capabilities resueltas por PHP.
- CSRF en cuatro mutaciones.
- prepared statements y TableResolver existente.
- escape por React; sin HTML de comentario.
- notificaciones aisladas por `user_id`.
- sin project/db prefix en cliente.
- sin contactos/comentarios reales en logs, screenshots o fixtures.
- sin cambios RLS/schema/datos.

## Estrategia de pruebas sin DML

### PHP

Repositorios fake y spies verifican:

- target actividad/alerta y scope;
- parent same-thread;
- actor eligibility;
- acciones/capacidades;
- CSRF antes de servicio;
- statuses/envelopes;
- identidad de notificación;
- ninguna llamada mutante ante validación/denegación.

No se ejecutan comentarios, crisis o notificaciones reales.

### Frontend

Vitest/Testing Library cubre:

- ratios, thresholds e ITR;
- matriz PG/PI/PS;
- provider y stale responses;
- hilo/reply/menciones;
- borradores y errores;
- simulación/operativo;
- digest;
- inbox, timer visible y mark-read;
- dark/light y responsive semántico.

### Navegador interceptado

Escenarios 390×844, 768×1024, 1180×820 y 1440×900 en ambos temas:

- apertura/cierre/foco;
- target que cambia;
- loading/empty/error;
- comentario/reply;
- perfil requerido;
- simulación/copia;
- registro/cierre interceptados;
- digest;
- notificaciones;
- Axe y consola.

Todas las mutaciones se interceptan.

## Corte, convivencia y retiro

### T02-A

Publicar provider, APIs aditivas, notification entry y adapters documentados. VIEW-28/JS siguen
sirviendo páginas legacy. Cada S integra el provider al cortar.

### T02-R

Después de S05, S07, S08 y S25:

1. generar censo exacto de includes/scripts/globals/endpoints;
2. exigir cero consumers productivos de VIEW-28/`lps_drawer.js`;
3. ejecutar matrices React de los cuatro módulos;
4. retirar partial, JS y reglas CSS exclusivas;
5. mantener aliases POST sólo si una decisión de compatibilidad externa lo exige;
6. actualizar S26 caller census;
7. re-verificar oscuro/claro, responsive, Axe, consola y red;
8. rollback sólo de código/assets.

## Criterios de aceptación

- T02-AC-001: T02 posee exactamente VIEW-28, `views/partials/drawer_unificado.php`.
- T02-AC-002: Los consumidores productivos de VIEW-28 son S05, S07, S08 y S25.
- T02-AC-003: T02 no posee una ruta de pantalla ni añade una entrada de sidebar para el drawer.
- T02-AC-004: Existe una sola instancia de `LpsDrawerProvider` dentro del AppShell autenticado.
- T02-AC-005: Ningún módulo crea un drawer LPS local ni copia su estado, API o CSS.
- T02-AC-006: El componente React no importa Handsontable, jQuery, Bootstrap, Font Awesome ni globals legacy.
- T02-AC-007: El drawer recibe un contexto canónico; no inspecciona celdas, DOM oculto ni aliases de columnas.
- T02-AC-008: Programa General entrega módulo `PG`; Intermedia `PI`; Semanal `PS`; Escalamientos usa target de alerta.
- T02-AC-009: Un capítulo/header produce diagnóstico neutral y no habilita acciones de actividad.
- T02-AC-010: Una actividad inexistente o no seleccionada mantiene cerrado el drawer y un trigger no engañoso.
- T02-AC-011: Un target de actividad exige identificador entero positivo y módulo PG, PI o PS.
- T02-AC-012: Un target de alerta exige `alerta_id` entero positivo.
- T02-AC-013: Target de alerta y target de actividad son variantes mutuamente excluyentes.
- T02-AC-014: El navegador nunca es autoridad de proyecto, prefijo, usuario, rol o actor.
- T02-AC-015: El navegador no puede fijar una semana autoritativa en comentarios o crisis.
- T02-AC-016: El servidor resuelve el proyecto desde `ProjectScope` y la semana desde el target autorizado.
- T02-AC-017: Un target por alerta deriva actividad, semana y módulo de la alerta persistida.
- T02-AC-018: Un target por actividad valida que la actividad pertenece al proyecto y módulo actuales.
- T02-AC-019: El camino legacy por `consecutivo` sigue scoped y existe sólo durante convivencia.
- T02-AC-020: Un `escalamiento_id` legacy opcional debe pertenecer al mismo proyecto, actividad y semana.
- T02-AC-021: El target se limpia al cambiar sesión o proyecto.
- T02-AC-022: El target de PG, PI o PS se limpia al cambiar la semana que gobierna ese módulo.
- T02-AC-023: El target de S25 no se invalida por cambiar la semana del shell porque la alerta porta su semana.
- T02-AC-024: Un cambio de dataset limpia target, hilo, errores y acciones previas.
- T02-AC-025: Si un filtro oculta la actividad seleccionada, el drawer conserva el target y anuncia `Oculta por los filtros`.
- T02-AC-026: Si la actividad deja de existir tras recarga, el drawer anuncia indisponibilidad y cierra acciones.
- T02-AC-027: Back cierra primero un drawer abierto por deep link antes de abandonar la ruta.
- T02-AC-028: Refresh profundo de S25 reabre sólo una alerta que el snapshot autorizado contiene.
- T02-AC-029: Cada apertura conserva referencia al trigger real para devolución de foco.
- T02-AC-030: Si el trigger desaparece, el foco vuelve al encabezado del módulo o estación.
- T02-AC-031: La lectura del hilo se cancela al cambiar target, proyecto, sesión o desmontar.
- T02-AC-032: Una respuesta tardía nunca sustituye el hilo del target actual.
- T02-AC-033: El drawer distingue `closed`, `opening`, `loading`, `ready`, `refreshing`, `empty`, `partial-error` y `mutation`.
- T02-AC-034: Un error de hilo no elimina diagnóstico, restricciones ni dataset del módulo.
- T02-AC-035: Una recarga de hilo conserva el contenido anterior marcado como actualizándose.
- T02-AC-036: La configuración de restricciones proviene del contrato tipado del módulo/servidor, no de defaults silenciosos en React.
- T02-AC-037: Construcción reconoce duras D_y_E, Materiales, MdeO, Equipos y Predecesora.
- T02-AC-038: Construcción reconoce Pdto_Cons y Modelo como restricciones blandas.
- T02-AC-039: Pre‑Construcción reconoce `restriccion_pc_1` como dura y PC2–PC4 como blandas.
- T02-AC-040: Predecesora se considera liberada desde 50%.
- T02-AC-041: Las demás restricciones duras de Construcción se consideran liberadas desde 100%.
- T02-AC-042: `restriccion_pc_1` usa el umbral server-authoritative del resolver, actualmente 50%.
- T02-AC-043: Valores `100%`, `100`, `1`, `1.0` y `1,0` se normalizan de forma equivalente.
- T02-AC-044: Valores `33%`, `33`, `0.33` y `0,33` se normalizan de forma equivalente.
- T02-AC-045: Los ratios se acotan a 0..1 sólo para presentación; entradas inválidas no se convierten en éxito.
- T02-AC-046: `N/A`, `NA` y `NO APLICA` se excluyen del denominador.
- T02-AC-047: Un campo duro existente pero vacío cuenta como pendiente 0%.
- T02-AC-048: Un campo inexistente en el módulo no penaliza el indicador.
- T02-AC-049: El ITR habilitante se calcula sólo con restricciones duras aplicables.
- T02-AC-050: Las restricciones blandas se muestran aparte y nunca bloquean inicio ni compromiso.
- T02-AC-051: `isReady` es verdadero sólo cuando todas las duras aplicables alcanzan su umbral.
- T02-AC-052: Sin restricciones duras aplicables, el estado es neutral/no evaluable; no se afirma liberación ficticia.
- T02-AC-053: `DeepGap` significa dura bajo 66%, salvo Predecesora bajo 50%.
- T02-AC-054: La severidad se resuelve en una única función pura compartida.
- T02-AC-055: `alerta_crisis=1` o SOS activo gana y produce `critical`.
- T02-AC-056: Una fila header produce `neutral` antes de cualquier cálculo de módulo.
- T02-AC-057: Programación Semanal no usa `Semanas_Inicio` para severidad.
- T02-AC-058: `prog-bloqueo-critico-sin-compromiso` y `cal-incumplida-critica` producen `critical`.
- T02-AC-059: `prog-ejecucion-con-restricciones` es crítica sólo en ruta crítica; de lo contrario es `attention`.
- T02-AC-060: `cal-incumplida`, `cal-sin-calificar`, `prog-condiciones-pendientes` y `prog-sin-compromiso` producen `attention`.
- T02-AC-061: `prog-lista-para-confirmar` y `cal-cumplida-control` producen `normal`.
- T02-AC-062: `ps-no-activa` produce `neutral`.
- T02-AC-063: PG/PI bloqueada actual o vencida en ruta crítica produce `critical`.
- T02-AC-064: PG/PI bloqueada actual o vencida sin ruta crítica produce `attention`.
- T02-AC-065: PG/PI a una semana con restricción pendiente produce `attention`, no crisis.
- T02-AC-066: PG/PI a dos o tres semanas con brecha profunda produce `attention`.
- T02-AC-067: PG/PI a dos o tres semanas sin brecha profunda produce seguimiento `normal`.
- T02-AC-068: PG/PI a cuatro a seis semanas incompleta produce `info`, no crisis.
- T02-AC-069: Una actividad terminada o liberada sin acción abierta produce `normal`.
- T02-AC-070: Sin evidencia suficiente para escalar, la severidad es `neutral`.
- T02-AC-071: Rojo/pulso sólo representa `critical`; ámbar representa `attention` sin pulso de crisis.
- T02-AC-072: La severidad siempre tiene etiqueta textual y no depende sólo de color, icono o emoji.
- T02-AC-073: El diagnóstico explica estado, brechas, progreso y siguiente acción sin insertar HTML no confiable.
- T02-AC-074: El indicador muestra liberadas/aplicables y porcentaje con una alternativa textual.
- T02-AC-075: GET `/api/lps/comments` conserva la ruta existente.
- T02-AC-076: GET de React acepta exactamente target de alerta o target de actividad canónico.
- T02-AC-077: El GET legacy por `consecutivo` permanece sólo mientras exista un consumidor legacy.
- T02-AC-078: Leer hilo exige sesión, ProjectScope válido y `lps.programacion_semanal.ver`.
- T02-AC-079: Una alerta ajena o actividad ajena no produce existencia, comentarios ni metadatos.
- T02-AC-080: La respuesta GET conserva `respuesta` y `data` para legacy y añade campos tipados sin romperlo.
- T02-AC-081: El gateway React normaliza la forma aditiva a `target`, `comments`, `actions`, `crisisAlert` y `meta`.
- T02-AC-082: Cada comentario cliente expone ID, texto, fecha, autor visible, cargo visible, menciones y respuestas.
- T02-AC-083: El payload React no expone username, correo, `usuario_id`, `project_id`, prefijo ni SQL.
- T02-AC-084: Comentarios raíz aparecen por fecha ascendente.
- T02-AC-085: Respuestas aparecen bajo su raíz por fecha ascendente.
- T02-AC-086: T02 conserva un solo nivel observable de respuesta.
- T02-AC-087: Un `parent_id` debe identificar una raíz del mismo target.
- T02-AC-088: Un parent de otro proyecto, actividad, semana o alerta se rechaza.
- T02-AC-089: Una respuesta a otra respuesta se rechaza para no crear contenido invisible.
- T02-AC-090: Las menciones usan `{roles:string[]}` con roles canónicos deduplicados.
- T02-AC-091: Un `@TOKEN` desconocido permanece texto y no se convierte en destinatario.
- T02-AC-092: Las menciones son metadata; T02 no crea, envía ni promete una notificación por mención.
- T02-AC-093: POST `/api/lps/comments/add` permanece como mutación canónica.
- T02-AC-094: POST `/api/lps/comments` permanece como alias temporal de la misma acción.
- T02-AC-095: Comentar exige `lps.programacion_semanal.editar` y CSRF `lps_drawer`.
- T02-AC-096: Comentario se recorta y debe ser no vacío.
- T02-AC-097: T02 no impone un límite nuevo que recorte silenciosamente contenido legacy válido.
- T02-AC-098: El actor de comentario se resuelve en servidor.
- T02-AC-099: La elegibilidad del actor comprueba la compatibilidad FK existente sin crear perfiles.
- T02-AC-100: `PROFILE_REQUIRED` mantiene diagnóstico e hilo en lectura y bloquea sólo escrituras ligadas al actor.
- T02-AC-101: Un comentario exitoso devuelve `comment_id` positivo y React vuelve a leer el hilo.
- T02-AC-102: React no inserta optimistamente un comentario ficticio.
- T02-AC-103: Error de comentario conserva borrador, target y hilo.
- T02-AC-104: Cliente y servidor no reintentan automáticamente comentarios.
- T02-AC-105: POST `/api/lps/crisis/register` permanece como mutación canónica.
- T02-AC-106: POST `/api/lps/crisis` permanece como alias temporal.
- T02-AC-107: Registrar crisis exige edición semanal efectiva y CSRF `lps_drawer`.
- T02-AC-108: `modulo` sólo puede resolver a PG, PI o PS.
- T02-AC-109: `trigger` sólo admite MANUAL, SOS-RES, SOS-DIR, SOS-COO o SOS-GER.
- T02-AC-110: El servidor deriva o valida módulo, actividad y semana contra el target.
- T02-AC-111: Registrar una alerta ya activa es idempotente y no incrementa el nivel.
- T02-AC-112: Éxito de registro significa alerta/banderas aceptadas; no significa entrega de mensaje ni escalamiento jerárquico.
- T02-AC-113: `escalarAlertasActivas()` permanece sin scheduler ni caller nuevo.
- T02-AC-114: El modo simulación usa `lps_simulated_mode` y es verdadero por defecto.
- T02-AC-115: En simulación, SOS sólo prepara/copia el texto y no llama la mutación.
- T02-AC-116: En modo operativo, la UI registra primero el aviso/SOS y luego prepara el canal externo.
- T02-AC-117: Si el canal no tiene contacto, la UI ofrece copiar y explica la ausencia.
- T02-AC-118: WhatsApp y correo son handoff del navegador; T02 nunca afirma `enviado`.
- T02-AC-119: Un fallo de portapapeles conserva texto seleccionable y feedback recuperable.
- T02-AC-120: Contactos opcionales provienen del dataset ya autorizado del módulo y no se registran en logs/evidencia.
- T02-AC-121: Nivel terminal sin superior no ofrece acción `notifyNext`.
- T02-AC-122: POST `/api/lps/crisis/close` permanece canónico.
- T02-AC-123: Cerrar exige alerta activa del mismo proyecto, edición semanal, actor elegible y CSRF.
- T02-AC-124: La justificación se recorta y exige al menos 100 caracteres en cliente y servidor.
- T02-AC-125: El contador de cierre anuncia longitud y estado de validación.
- T02-AC-126: Error de cierre conserva justificación y drawer abierto.
- T02-AC-127: Éxito de cierre recarga hilo/snapshot/dataset autoritativo antes de retirar la alerta visual.
- T02-AC-128: React no limpia localmente banderas de crisis como fuente de verdad.
- T02-AC-129: Cliente y servidor no reintentan automáticamente registro o cierre.
- T02-AC-130: El digest existe sólo cuando el consumidor entrega una colección autorizada visible.
- T02-AC-131: El digest no necesita una grilla ni una versión desktop oculta.
- T02-AC-132: El digest agrupa bloqueos críticos por responsable/subcontratista con texto plano.
- T02-AC-133: El digest no envía filas al servidor o a terceros.
- T02-AC-134: Copiar digest conserva feedback de éxito/error y texto seleccionable.
- T02-AC-135: El enlace BI aparece sólo si T01 entrega un href autorizado.
- T02-AC-136: El drawer nunca calcula acceso BI desde rol.
- T02-AC-137: GET `/api/notifications/unread` permanece como bandeja de identidad del usuario autenticado.
- T02-AC-138: POST `/api/notifications/read` permanece como marca individual e idempotente.
- T02-AC-139: Marcar leída exige token CSRF del shell durante y después de convivencia.
- T02-AC-140: Una notificación sólo puede marcarse usando ID positivo y el usuario de sesión como predicado.
- T02-AC-141: Un ID ajeno o ya leído no revela existencia y produce una respuesta idempotente segura.
- T02-AC-142: La respuesta de notificaciones no expone el `project_id`/db-prefix interno.
- T02-AC-143: Cada aviso expone ID, tipo, título, mensaje, item_count y fecha.
- T02-AC-144: El badge cuenta grupos no leídos; `item_count` se muestra dentro del grupo.
- T02-AC-145: No se añade `mark all read`, preferencias, enlaces o borrado sin otra spec.
- T02-AC-146: Existe una sola bandeja React para desktop y móvil, no dos DOM sincronizados.
- T02-AC-147: El shell carga avisos al entrar y como máximo cada 120 segundos mientras el documento está visible.
- T02-AC-148: El polling usa `X-AIA-Idle-Refresh: 0` y no prolonga artificialmente la sesión.
- T02-AC-149: Ocultar la pestaña, cerrar sesión o desmontar aborta el request/timer.
- T02-AC-150: Un error conserva los avisos anteriores como desactualizados y ofrece reintento manual.
- T02-AC-151: Marcar leída sólo retira el ítem después de éxito.
- T02-AC-152: Las notificaciones usan `cliente.ts`; ningún componente llama `fetch`.
- T02-AC-153: Todos los endpoints LPS/notificaciones tienen esquemas Zod estrictos y pruebas de contrato PHP.
- T02-AC-154: El transporte form-urlencoded, JSON, CSRF, AbortSignal y errores pasa por `cliente.ts`/gateways, no por componentes.
- T02-AC-155: El trigger del drawer es un botón nativo con nombre visible o accesible y estado de severidad textual.
- T02-AC-156: El trigger nunca tapa la última fila; cada consumidor lo ubica en toolbar/tarjeta según su layout.
- T02-AC-157: El drawer usa `role=dialog`, nombre accesible y `aria-modal=true` cuando bloquea el fondo.
- T02-AC-158: Al abrir, el foco entra en el encabezado/cierre o primer control útil.
- T02-AC-159: Tab y Shift+Tab permanecen dentro del drawer modal.
- T02-AC-160: Escape cierra si no hay borrador; con borrador pide confirmación antes de descartar.
- T02-AC-161: Cerrar por botón u overlay aplica la misma protección de borrador.
- T02-AC-162: El fondo queda inert mientras el drawer modal está abierto.
- T02-AC-163: Al cerrar, el foco vuelve al trigger o fallback definido.
- T02-AC-164: Estados de carga, vacío, error, guardado y cierre se anuncian con live regions separadas.
- T02-AC-165: Comentarios, respuestas y acciones son operables por teclado y touch.
- T02-AC-166: Targets interactivos miden al menos 44×44 CSS px.
- T02-AC-167: El contenido funciona a 320 px y zoom 200% sin scroll horizontal de página.
- T02-AC-168: En desktop 1180+ el AppShell reserva una columna de panel sin hacks de padding en body.
- T02-AC-169: En tablet el drawer es un side-sheet superpuesto con ancho acotado.
- T02-AC-170: En móvil el drawer es un bottom/full sheet con `dvh`, safe areas y scroll interno.
- T02-AC-171: Una sola instancia se compone por breakpoint; no se duplican versiones ocultas.
- T02-AC-172: `prefers-reduced-motion` elimina deslizamientos/pulsos sin ocultar estados.
- T02-AC-173: Oscuro es el tema inicial y claro ofrece función, contraste y estados equivalentes.
- T02-AC-174: Todos los colores, espacios, radios, sombras, capas y motion provienen de `public/css/tokens.css`.
- T02-AC-175: Los archivos nuevos T02 tienen cero colores literales, estilos inline, CSS-in-JS y `!important`.
- T02-AC-176: El texto de comentario se renderiza escapado; no se usa `dangerouslySetInnerHTML`.
- T02-AC-177: URLs WhatsApp/mailto se construyen con encoding y ventanas externas usan aislamiento adecuado.
- T02-AC-178: Cada lectura/escritura operacional mantiene ProjectScope y predicado `project_id`.
- T02-AC-179: `system_notifications` conserva aislamiento Identity por `user_id`; no se fuerza a ProjectScope.
- T02-AC-180: T02 no modifica RLS, DataScope, schema, migraciones, grants, usuarios, credenciales o datos.
- T02-AC-181: Las pruebas de contrato usan repositorios/fakes/spies y no ejecutan DDL/DML.
- T02-AC-182: Las pruebas browser interceptan comentarios, crisis y notificaciones; no crean alertas ni comentarios reales.
- T02-AC-183: Una matriz pura cubre Construcción, Pre‑Construcción, PG, PI y PS.
- T02-AC-184: Una matriz de permisos cubre lectura, edición, perfil requerido, CSRF y target ajeno.
- T02-AC-185: Axe serious/critical es cero en oscuro/claro y desktop/tablet/móvil.
- T02-AC-186: No hay errores inesperados de consola ni requests sin abortar en cambios de target/ruta.
- T02-AC-187: El caller census mantiene VIEW-28, `lps_drawer.js` y adapters mientras quede uno de los cuatro consumidores legacy.
- T02-AC-188: La plataforma T02 puede publicarse con compatibilidad legacy explícita; eso no autoriza retirar VIEW-28.
- T02-AC-189: El último consumidor ejecuta el gate T02-R: cero callers productivos, pruebas verdes y retiro atómico.
- T02-AC-190: Los aliases POST se retiran sólo con censo cero y una decisión de compatibilidad registrada.
- T02-AC-191: El rollback de plataforma restaura código/rutas/assets, nunca datos.
- T02-AC-192: El rollback de retiro vuelve a habilitar el adapter legacy sin revertir comentarios, crisis o notificaciones.
- T02-AC-193: El cierre confirma diff vacío bajo `admin/`, RLS y database.
- T02-AC-194: No quedan decisiones de producto, negocio, estrategia o PM abiertas en T02.

## Trazabilidad

El plan `docs/superpowers/plans/2026-08-30-t02-contexto-lps-react.md` debe contener exactamente una
fila por cada criterio T02-AC, con tarea y evidencia ejecutable. T02-A y T02-R se distinguen en el
plan para evitar el ciclo “el drawer debe cerrar antes que sus consumidores y sus consumidores
dependen del drawer”.

## Decisiones pendientes

Ninguna. Envío server-side real, más niveles de reply, historial cerrado, preferencias de
notificación, autoescalamiento o una nueva identidad de actor requieren specs y autorización
separadas.
