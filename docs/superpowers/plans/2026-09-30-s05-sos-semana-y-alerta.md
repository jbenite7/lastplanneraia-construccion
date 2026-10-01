---
capa: fuente
tipo: plan
estado: abierto
fecha: 2026-09-30
areas: [lps, datos, qa]
ejecutor: claude
aprobado_por: felipe
aprobado_el: 2026-10-01
sello: "ninguno — aprobado en el chat de Claude («Apruebo el plan 2026-09-30-s05-sos-semana-y-alerta»); el repo no usa P-NNN ni .flujo.json"
spec: docs/superpowers/specs/2026-09-30-s05-sos-semana-y-alerta-design.md
fuente: docs/superpowers/plans/2026-09-30-s05-sos-semana-y-alerta.md
resumen: "Plan del arreglo de los dos fallos críticos del SOS: la semana viaja desde la pantalla y el servidor la verifica, y el id de alertas y comentarios sale de Database::insertedId() en vez de lastInsertId()."
---

# S05-SOS — La semana y el id de la alerta — Plan de implementación

> **Para agentes:** SUB-SKILL OBLIGATORIA: `superpowers:subagent-driven-development` o
> `superpowers:executing-plans`; `superpowers:test-driven-development` en cada tarea de código;
> `superpowers:systematic-debugging` ante cualquier falla; `superpowers:requesting-code-review`
> para el revisor independiente; `superpowers:verification-before-completion` y
> `superpowers:finishing-a-development-branch` al cierre. Pasos con casillas (`- [ ]`).

**Objetivo:** que un SOS o un comentario hecho viendo la semana N quede en la semana N, y que un
SOS registrado se muestre como registrado.

**Arquitectura:** el cliente propone la semana y el servidor la **verifica** contra la tabla del
módulo; nunca la adivina. `Database` expone el id que su capa asignó por proyecto
(`insertedId()`), y los tres escritores de alertas y comentarios lo usan. La pantalla distingue la
sesión vencida de una respuesta con forma inválida.

**Tech stack:** PHP 8.3 y PHPUnit 12 en Docker, MySQL 8.0, React 19 + TypeScript + Zod + Vitest,
JS legado del cajón, Playwright.

**Spec:** `docs/superpowers/specs/2026-09-30-s05-sos-semana-y-alerta-design.md` (versión 1.2,
aprobada por Felipe el 2026-09-30, sin sello). Léela entera antes de la tarea 0.

## Decisiones de Felipe (2026-09-30, en el chat)

- **Llamada sin semana: se rechaza, salvo que traiga alerta.** Responde `VALIDATION_FAILED` con el
  campo `semana`. Si trae `escalamiento_id`, la semana sale de la alerta guardada. Por qué: la
  spec actualiza a todos los llamadores conocidos y adivinar es justo lo que causó el fallo. La
  excepción existe por S25 (tablero de escalamientos): su `semana_PHP` es la de la sesión y no la
  de la crisis (`views/dashboard/escalamientos.php:117`), así que para S25 la alerta es la única
  semana correcta.
- **Ejecutor: Claude, en una sesión de Claude Code en terminal.** Es una excepción al reparto
  vigente, que asigna a Codex. `scripts/mensaje_sprint.py` y D14 solo conocen `codex` y
  `antigravity`, así que el mensaje de sprint se arma a mano. El revisor de la tarea 7 sigue
  siendo independiente: otra sesión o agente, con contexto limpio.

## Decisiones de código (tomadas aquí, anotadas)

- La interfaz de adaptadores cambia `resolveWeek()` por `existsInWeek()`. Así el `LIMIT 1` sin
  `ORDER BY` desaparece en vez de convivir.
- Fuente de la semana en el cajón legado: `escalamiento_id` si la fila trae alerta y, si no,
  `semana_PHP` cuando es mayor que 0. Nunca se manda un 0 de relleno: `getContext()` ya exige una
  semana de sesión mayor que 0 (`LpsApiController.php:43`).
- `registerCrisis` también lee `escalamiento_id`, que hoy pasa `null`, para que S25 registre
  sobre la semana de la alerta.
- La sesión vencida del guardia legado (200 con `respuesta: 'ERROR'`) se reconoce en `crisis.ts`
  por esquema, con el código `SESION_LEGADO`. No se interpreta el texto de un mensaje.

## Restricciones globales

- **Felipe aprobó el plan en el chat el 2026-10-01**, sin sello. Autoriza ejecutar las tareas 0 a 7
  tal como están escritas. Si algo de fondo no cumple la spec, se vuelve al paso 03 y no se
  parchea hacia adelante.

- **Cambio deliberado del contrato T02.** D-T02-02 decía que la semana nunca viene del cliente.
  La spec 1.2 lo cambia: el cliente la propone y el servidor la verifica. Se actualizan a
  propósito el docblock de `LpsTargetResolver`, el de `LpsTarget` y
  `tests/test_lps_api_contract.php:189`. No es una regresión que haya que «arreglar» volviendo atrás.
- No cambia el esquema ni los datos. `lastInsertId()` no cambia.
- RBAC, CSRF, `ProjectScope` y la política de acciones (`LpsActionPolicy`) no se tocan.
- Fuera de alcance: los `lastInsertId()` que la spec deja como deuda en `TASKS.md`, y la
  concurrencia de `MAX(id)+1`.
- Worktree propio desde `origin/main`, con `.env` enlazado (no copiado). PHP solo dentro de `app`.
  Para ver la rama en el navegador: `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, y
  devolverlo a la raíz al terminar.
- Sin push ni merge: eso es de Felipe.

## Review Focus

1. **Semana 0 (Pre-Construcción) enviada:** es válida si la actividad existe en la semana 0. Un
   parámetro ausente no es 0. Pruebas en las tareas 3 y 4.
2. **Cajón legado sin `semana_PHP`, o con 0:** no manda `semana`, nunca un 0 de relleno, y el
   servidor rechaza con el campo `semana`. Prueba en la tarea 5.
3. **Crisis de S25 de una semana distinta a la de la sesión:** manda `escalamiento_id`, que fija
   la semana. Si además llega una `semana` distinta, la respuesta es `TARGET_NOT_FOUND`. Pruebas en
   las tareas 3 y 5.
4. **INSERT por `prepare()` después de uno reescrito:** `insertedId()` no devuelve el id viejo.
   Prueba en la tarea 1.
5. **`semana` no numérica o negativa (`"2a"`, `"-1"`):** `VALIDATION_FAILED` con el campo
   `semana`, sin llegar al resolvedor. Prueba en la tarea 4.

---

### Tarea 0: Preparación y línea base

- [ ] Rama `fix/s05-sos-semana-y-alerta` desde `origin/main`, en un worktree propio, con `.env`
  enlazado.
- [ ] Línea base, con cada código de salida leído en su propia línea:
  `docker compose exec app php scripts/run-php-tests.php --nivel=puro`,
  `docker compose exec app php scripts/run-php-tests.php --nivel=http`, y en `frontend/` el
  `npm test` y el `tsc` del proyecto. Anota el SHA y los resultados. Un rojo previo se reporta,
  no se arregla aquí.

### Tarea 1: `Database::insertedId()`

**Archivos:** modificar `src/Core/Database.php`; crear `tests/unit/DatabaseInsertedIdTest.php`
con `#[Group('db')]`.

**Produce:** `Database::insertedId(): int`.

- [ ] **Prueba que falla.** La clase abre `ProjectScope(73, 'test.A', 'A')` y una transacción en
  `setUp`, y hace `rollBack()` y `clear()` en `tearDown`, como `DatabaseWrapperTest`. Métodos, uno
  por cláusula del contrato de la spec:
  - `testDevuelveElIdQueAsignoRewriteInsert`: un INSERT en `lps_escalamientos` sin columna `id`;
    `insertedId()` es mayor que 0 e igual al `MAX(id)` del proyecto.
  - `testUnSelectIntermedioNoBorraElId`: el mismo INSERT, un `SELECT` y luego `insertedId()`
    devuelve el mismo valor.
  - `testInsertConIdPropioCaeALastInsertId`: un INSERT con `id` explícito; `insertedId()` es igual
    a `(int) lastInsertId()`.
  - `testInsertPorPrepareBorraElIdAnterior`: un INSERT reescrito y luego un INSERT por `prepare()`;
    `insertedId()` ya no devuelve el id reescrito.
  - `testCaeALastInsertIdEnTablaConAutoIncrement`: un INSERT en `pdc_subpaquete` (tiene
    `AUTO_INCREMENT` en `id`, verificado en local el 2026-09-30); `insertedId()` es mayor que 0 e
    igual a `lastInsertId()`. Las columnas obligatorias se leen del esquema.
  - `testInsertIgnoreSinFilasNoDejaId`: si ninguna tabla del mapa tiene una clave única secundaria
    con la que forzar un `INSERT IGNORE` sin filas, el ejecutor aísla la promoción en un método
    puro y lo prueba con `rowCount() = 0`. Anota cuál de las dos vías usó.
- [ ] Correr `docker compose exec app vendor/bin/phpunit --group db --filter DatabaseInsertedIdTest`:
  falla porque el método no existe.
- [ ] **Implementar.** `rewriteInsert()` guarda el id que asigna como pendiente. `query()` lo
  promueve tras un `execute()` con `rowCount() > 0`; cualquier otro INSERT lo borra, incluido
  `INSERT … SELECT`. `prepare()` borra el valor si la sentencia es un INSERT. Un `SELECT`,
  `UPDATE` o `DELETE` no lo toca. `insertedId()` devuelve el valor promovido o
  `(int) $this->pdo->lastInsertId()`.
- [ ] Correr la prueba: pasa. Correr `--nivel=puro`: sigue verde.
- [ ] Commit `feat(db): insertedId() devuelve el id asignado por proyecto`.

### Tarea 2: Los tres escritores usan `insertedId()` y el registro revierte con id 0

**Archivos:** `src/Services/Lps/LpsLegacyCrisisRepository.php:82`,
`src/Services/Lps/LpsLegacyThreadRepository.php:121`, `src/Services/LpsService.php:224`,
`src/Services/Lps/LpsCrisisService.php`; pruebas en `tests/unit/LpsCrisisServiceTest.php` (nivel
`puro`) y en `tests/unit/LpsLegacyInsertedIdTest.php` (nivel `db`, creada aquí).

**Consume:** `Database::insertedId(): int` (tarea 1).

- [ ] **Pruebas que fallan:**
  - `LpsCrisisServiceTest::testRegistroConIdNoPositivoRevierteYLanza`: un repositorio falso cuyo
    `insertAlert` devuelve 0. `register()` lanza `LpsTargetException` con el código de
    `LpsApiError::serviceUnavailable()`, llama a `rollBack()` y no llama a `commit()`.
  - `LpsLegacyInsertedIdTest`, con transacción y `rollBack` como en la tarea 1:
    `testInsertAlertDevuelveElIdDeLaFila`, `testInsertComentarioDevuelveElIdDeLaFila` y
    `testAddActivityCommentDevuelveElIdDeLaFila`. Cada una inserta y comprueba que el retorno es
    mayor que 0 y que existe una fila con ese `id` en el proyecto.
- [ ] Correr las pruebas: fallan (devuelven 0 o hacen commit).
- [ ] **Implementar.** Los tres reemplazan `lastInsertId()` por `insertedId()`. En
  `LpsCrisisService::register`, si `$alertId <= 0` cuando no `$wasActive`, se lanza dentro del
  `try`, así que el `catch` existente revierte.
- [ ] Correr: pasan. `--nivel=puro` sigue verde.
- [ ] Commit `fix(lps): alertas y comentarios devuelven el id asignado y revierten con 0`.

### Tarea 3: El resolvedor verifica la semana

**Archivos:** `src/Services/Lps/LpsTargetRequest.php`, `LpsActivityTargetAdapter.php`, los tres
`LpsLegacy*ActivityAdapter.php`, `LpsTargetResolver.php`, el docblock de `LpsTarget.php`, y
`tests/unit/LpsTargetResolverTest.php`.

**Produce:**
- `LpsTargetRequest::__construct(?int $activityId = null, ?string $module = null, ?int $alertId = null, ?int $escalamientoId = null, ?int $week = null)`.
- `LpsActivityTargetAdapter::existsInWeek(int $projectId, int $activityId, int $week): bool`,
  que reemplaza a `resolveWeek()`.

- [ ] **Pruebas que fallan.** El falso de la línea 51 pasa a `module => [activityId => list<week>]`.
  - `testSemanaEnviadaQueExisteResuelveEsaSemana`: actividad con semanas `[1, 2]`; con `week: 2`,
    `target->week` es 2.
  - `testSemanaEnviadaSinFilaEsTargetNotFound`.
  - `testSinSemanaNiEscalamientoEsValidationFailedConCampoSemana`.
  - `testEscalamientoFijaLaSemanaDeLaAlerta`: sin `week`, `target->week` es la de la alerta.
  - `testEscalamientoConSemanaDistintaEsTargetNotFound`.
  - `testSemanaCeroEnviadaEsValidaSiExiste`.
  - `testLegadoSinModuloConSemanaEligeElModuloDondeExiste`.
  - Las pruebas actuales que resolvían sin semana (líneas 118, 129, 138, 254–270, 288 y 313) se
    actualizan para mandar `week`. Se conserva lo que afirman; no se borran.
- [ ] Correr `--filter LpsTargetResolverTest`: falla.
- [ ] **Implementar**, en este orden dentro de `resolveActivityTarget`:
  1. Con `escalamientoId`: se carga la alerta del proyecto. Si no existe o su actividad es otra,
     `TARGET_NOT_FOUND`. La semana es la de la alerta; si el módulo no vino, también se toma de
     ella. Si llegó una `week` distinta de la de la alerta, `TARGET_NOT_FOUND`.
  2. Si no, con `week`: `existsInWeek` en el adaptador del módulo o, en el camino legado sin
     módulo, el primero de PG, PI y PS donde exista. Si no existe, `TARGET_NOT_FOUND`.
  3. Si no hay ninguna de las dos: `VALIDATION_FAILED` con
     `['semana' => 'Requerida: la semana que se está viendo.']`.
  Los adaptadores consultan `… AND Semana = ? LIMIT 1` (PG y PI conservan `Titulo = 0`).
  Los docblocks dicen: «el cliente propone la semana y el servidor la verifica (S05-SOS 1.2)».
- [ ] Correr: pasa. `--nivel=puro` sigue verde.
- [ ] Commit `fix(lps): la semana se verifica, no se adivina`.

### Tarea 4: El controlador lee `semana` y `escalamiento_id`

**Archivos:** `src/Controllers/Api/LpsApiController.php` (`comments`, `addComment`,
`registerCrisis`, `buildTargetRequest`), `tests/test_lps_api_contract.php`.

**Consume:** el `LpsTargetRequest` con `week` de la tarea 3.

- [ ] **Pruebas que fallan** en `test_lps_api_contract.php` (nivel `http`), con la siembra que
  ya usa (`unique_id = 3`, `Semana = 1`):
  - La línea 189 manda `semana=1` y afirma `target.week === 1`.
  - Con `semana=2` responde `TARGET_NOT_FOUND`.
  - Sin `semana` ni `escalamiento_id` responde `VALIDATION_FAILED` con `semana` en sus campos.
  - Con `semana=2a` y con `semana=-1` responde `VALIDATION_FAILED` con `semana`.
  - `registerCrisis` con `escalamiento_id` de una alerta sembrada registra sobre la semana de esa
    alerta. Si la siembra no tiene alerta, se crea en la misma prueba y se borra al final.
- [ ] Correr `docker compose exec app php tests/test_lps_api_contract.php`: falla.
- [ ] **Implementar** `private function parseSemana(mixed $raw): int|false|null`. Devuelve `null`
  si el valor no viene o viene vacío, `false` si no es un entero mayor o igual que 0, y el entero
  en otro caso. Si devuelve `false`, el controlador responde `VALIDATION_FAILED` con `semana`
  antes de resolver. `buildTargetRequest` recibe la semana. `registerCrisis` lee `escalamiento_id`
  igual que `addComment`. Nunca se usa `!empty()`, porque trata el 0 como ausente.
- [ ] Correr: pasa. `--nivel=http` sigue verde.
- [ ] Commit `fix(lps): la API recibe la semana y la valida`.

### Tarea 5: El cajón legado manda la semana

**Archivos:** `public/js/modules/lps_drawer.js` (líneas 883, 1061 y 1213),
`tests/browser/lps-drawer-fetch-lifecycle.mjs`.

- [ ] **Prueba que falla**, en la suite de la propia prueba de navegador, interceptando las
  peticiones:
  - En PI y PS, la lectura, el comentario y el registro de crisis llevan `semana` igual a
    `semana_PHP`.
  - Con una fila que trae `alerta_id` (como las de S25), llevan `escalamiento_id` y no `semana`.
  - Si `semana_PHP` falta o vale 0, no llevan `semana`.
- [ ] Correr la prueba: falla.
- [ ] **Implementar** `function appendTargetWeek(params, rowData)`. Si la fila trae
  `escalamiento_id` o `alerta_id`, agrega `escalamiento_id`; si no, agrega `semana` cuando
  `getSessionContext().semana > 0`. Se usa en las tres llamadas: en el `GET` sobre sus
  `URLSearchParams` y en los `POST` sobre su `FormData`. Un `escalamiento_id` que ya se manda no
  se duplica.
- [ ] Correr: pasa. `tests/test_t02_lps_caller_census.mjs` sigue verde.
- [ ] Commit `fix(lps-drawer): el cajón legado manda la semana o la alerta`.

### Tarea 6: React manda la semana y no disfraza el error de contrato

**Archivos:** `frontend/src/shared/lps/api/esquemas.ts`, `crisis.ts`,
`frontend/src/modules/programa-general/api/programaGeneralApi.ts`, `ProgramaGeneralPage.tsx:263`,
`frontend/src/modules/programa-general/domain/erroresSos.ts`, los fixtures de
`frontend/src/shared/lps/testing/`, y sus `*.test.ts` vecinos.

**Produce:** `TargetHiloParams = { consecutivo: number; modulo: Modulo; semana: number } | { alertaId: number }`,
y el código de `ApiError` `'SESION_LEGADO'`.

- [ ] **Pruebas que fallan:**
  - `esquemas.test.ts`: `queryDeTarget` con una actividad pone `semana`; con una alerta, no.
  - `crisis.test.ts`: el cuerpo de `registrarCrisis` lleva `semana`; una respuesta 200
    `{respuesta:'ERROR', mensaje}` lanza `ApiError` con `codigo === 'SESION_LEGADO'`.
  - `erroresSos.test.ts`: con `SESION_LEGADO`, el texto de sesión vencida. Con `forma_invalida` y
    status 200, el texto exacto «Error del sistema: la respuesta del servidor no se pudo leer.
    Recarga la página para ver si la crisis quedó registrada antes de repetirla.»
- [ ] Correr `npm test` en `frontend/`: falla.
- [ ] **Implementar.** `semana` obligatoria en la variante de actividad, así `tsc` lista a cada
  llamador. `DeclararSosPayload` suma `semana: number`, y `handleDeclararSos` pasa
  `contexto.semana.numero`. `crisis.ts` valida contra la unión del esquema actual y
  `{ respuesta: 'ERROR', mensaje: string }`, y en el segundo caso lanza el `ApiError`
  (`tipo: 'http'`, `status: 200`, `codigo: 'SESION_LEGADO'`). En `mensajeErrorSos`, la rama de
  `SESION_LEGADO` va **antes** de la rama `http` que devuelve el mensaje del servidor. Una
  `forma_invalida` con 200 ya no se mapea a sesión vencida.
- [ ] Correr `npm test`, `tsc` y el lint del frontend: verdes.
- [ ] Commit `fix(pg): el SOS manda la semana y distingue sesión vencida de error del sistema`.

### Tarea 7: Verificación integral y cierre

- [ ] Suites completas tras la última tarea, cada código de salida en su propia línea:
  `--nivel=puro`, `--nivel=http`, `npm test` y `tsc` en `frontend/`,
  `npx playwright test tests/browser/lps-drawer-fetch-lifecycle.mjs --workers=1`, y
  `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`.
- [ ] **Prueba en navegador**, en la copia local de Da Porto con `test.R`. Escribe en la base
  local, así que requiere respaldo previo, `/visto-prod` de Felipe y restauración al final, como
  en la prueba del 2026-09-30. Viendo la semana 2:
  - un SOS y un comentario quedan en la semana 2 (`SELECT` de solo lectura para comprobarlo);
  - la pantalla confirma el registro;
  - consola sin errores.
  Además, desde PI (cajón legado), un comentario queda en su semana.
- [ ] Revisión independiente de la rama completa contra la spec (`requesting-code-review`, con un
  revisor que no sea quien ejecutó).
- [ ] Wiki: ingest en `memoria/trampas/lastinsertid-en-tablas-con-id-por-proyecto.md` (el
  arreglo del SOS ya usa `insertedId()`) y en la ficha de Programa General, con su línea en
  `memoria/log.md`. `npm run test:wiki` sin hallazgos de forma.
- [ ] Informe de cierre con salidas reales, SHA verificado y pendientes con fecha. Sin push ni
  merge.
