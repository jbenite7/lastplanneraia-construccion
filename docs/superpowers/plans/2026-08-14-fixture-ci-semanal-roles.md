# Plan: el fixture aislado alcanza para `programacion-semanal-roles-phases`

Diseño: `docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design.md`
Fecha: 2026-08-14 · Base: `ebf09954`

## Condición de hecho

En el stack aislado, `npx playwright test tests/browser/programacion-semanal-roles-phases.mjs
--workers=1` da **14 en verde y 0 `skip`**. En desarrollo, los cuatro que escriben siguen
saltándose por el candado, y los otros diez siguen verdes. El paso está cableado en el workflow con
su recibo, y `npm run test:design-system:static` está verde.

## Estado real al 2026-08-18 — este plan está cerrado

Contrastado tarea por tarea contra el código. **No lo ejecutes de nuevo: las siete tareas están
hechas.**

| Tarea | Estado | Dónde se ve |
|---|---|---|
| 0 · medir la línea base | hecha | `memoria/log.md` |
| 1 · semanas confirmadas de JMC | hecha | `database/fixtures/design-system-ci.sql` |
| 2 · accesos `test.R` y `test.D` | hecha | `database/fixtures/design-system-ci.sql` |
| 3 · semana vacía de Da Porto | hecha | `resolveEmptyWeek()` en el spec |
| 4 · las cuatro pruebas que escriben | hecha, por otra vía | ver abajo |
| 5 · gate propio en CI | hecha | `semanal-roles-phases`, noveno gate del índice |
| 6 · cierre e ingest | hecha | trampa marcada `estado: derogada` |

**La Task 4 no se hizo como estaba escrita, y está bien así.** El plan pedía borrar los
`test.skip(!MUTACION_HABILITADA, …)` y a la vez que en desarrollo esas cuatro siguieran saltándose:
se contradecía. El código conservó el guard, que es lo único que cumple las dos mitades — borrarlo
no las habría habilitado en desarrollo, las habría dejado fallar ahí.

**La condición de hecho decía «14 en verde y 0 `skip`»; la real es 15 y 15.** El spec declara hoy
quince casos: cuatro del bucle de roles (`ROLE_CASES`) más once sueltos. Los dos de tabla en tablet
llegaron a estar saltados en firme por el retiro de esa tabla, y volvieron al gate cuando E3 cerró
ese hueco. Hoy no queda ninguno saltado.

**Aviso a quien lea esta sección por el historial:** entre el 14 y el 18 de agosto este bloque llegó
a decir que las Tasks 4 y 5 estaban descartadas y que lo alcanzable era «13 verdes y 2 saltadas».
Era cierto cuando se escribió y dejó de serlo mientras se escribía, porque otra sesión cerró la
Task 5 y devolvió los dos casos de tablet en paralelo. Lo detectó la re-verificación posterior a
integrar `origin/main`, que es exactamente para lo que existe ese paso del gate de cierre.

---

## Antes de empezar: levantar el stack aislado

El plan entero se verifica ahí, así que esto se hace una vez y se deja arriba. Las cuatro variables
de consentimiento van en **todas** las corridas de Playwright de este plan.

```bash
export CI_RUN_ID=run-local-$(git rev-parse --short HEAD)
export COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-${CI_RUN_ID}
export COMPOSE_FILE=docker-compose.yml:docker-compose.ci.yml
eval "$(node scripts/design-system-ci-preflight.mjs --print-provenance | sed 's/^/export /')"
docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml up -d --build db app
export APP_URL=http://127.0.0.1:18081 E2E_BASE_URL=http://127.0.0.1:18081
export E2E_REQUIRE_ISOLATED_DB=1 E2E_ALLOW_DB_MUTATION=design-system-ci
```

**Esperar a la base, no a la app.** `curl /login` responde 200 sin tocar la base, y el healthcheck
de `db` se pone verde contra el servidor temporal con el que MySQL carga las semillas: hay una
ventana de ~8 s en la que `app` ya está arriba y la base no. Medido el 2026-08-18 — el primer
recibo del gate salió rojo con 13 de 13 casos caídos en 8.072 ms, y el segundo pasó sin cambiar
nada. Antes de medir, preguntarle a la base por un dato que solo existe tras las semillas
(ver [[el-healthcheck-de-db-responde-al-servidor-temporal]]).

La imagen de `db` **se construye desde el fixture** (`database/fixtures/design-system-ci.Dockerfile`),
así que cada vez que cambie el `.sql` hay que reconstruirla: `docker compose ... up -d --build db`.
Editar el `.sql` y reusar el contenedor es la vía rápida a medir el fixture viejo — el mismo error
de [[exec-en-contenedor-vivo-corre-el-repo-ajeno]] un piso más abajo.

Al terminar, bajar el stack con su volumen: `docker compose -p "$COMPOSE_PROJECT_NAME" -f
docker-compose.yml -f docker-compose.ci.yml down -v`. El stack de desarrollo no se toca en ningún
momento.

## Task 0 — Medir la línea base, no suponerla

Correr el spec en el stack aislado **antes** de tocar nada y guardar la salida. Sin esto, cualquier
rojo posterior es de atribución dudosa.

Expected: los 4 casos de escritura **corren** (el candado ya está satisfecho) y fallan por dato
ausente; varios de los otros diez fallan también. Anotar exactamente cuáles y por qué. Si alguno
falla por un motivo que no está en el spec, **parar y consultar** antes de seguir: el spec se
escribió sobre seis huecos y un séptimo cambia el plan.

## Task 1 — Sembrar las semanas confirmadas de JMC

En `database/fixtures/design-system-ci.sql`:

- `semanas_activas`: añadir las semanas 1, 2, 3 y 4 del proyecto 68 con `Semanal_Confirmada = 1`,
  fechas coherentes y anteriores a la de la semana 5, e `Id` que no choque con los existentes.
  **La semana 5 no se toca**: sigue con `Semanal_Confirmada = 0` y sigue siendo la máxima.
- `programacion_semanal`: al menos una fila por semana nueva con `Sub_Contratista`,
  `Responsable_AIA` y `Compromiso > 0` — es la precondición que `pickWeeklyRow` exige —, y en la
  semana 4 una fila más con `Activa = 0`, que es la que `CnpApiController::list` devuelve como CNP.
  `row_id` y `Consecutivo` sin colisión con la fila 3002 ya sembrada.
- Reconstruir `db` y comprobar en la base que `Max_Semana` sigue siendo 5, que la histórica (3) está
  confirmada y que la 4 es la primera en fase de calificación.

Verificación: los tres casos de API (`avance móvil…`, `API semanal rechaza fase…`, `API CNP no
reprograma…`) dejan de fallar por dato. El caso histórico todavía puede fallar por permisos: es la
Task 2. Y `npm run test:design-system:static`.

## Task 2 — Sembrar los accesos que faltan

- `project_members`: `test.R` (user_id 2) en el proyecto 68 con rol `R`.
- `usuarios`: `test.D` con `cargo` de Director de Obra, el mismo hash que los demás y `activo = 1`;
  más su membresía en el proyecto 73 con rol `D`.
- Revisar si `DEV_DOOR_USERS` de `docker-compose.ci.yml` necesita a `test.D`. **Solo si el spec lo
  usa por la puerta de servicio**: el spec entra por `login()`, así que probablemente no — se
  comprueba, no se añade por si acaso.

Verificación: los cuatro casos de roles y el caso histórico en verde. Y la estática.

## Task 3 — La semana vacía de Da Porto

Opción (a) del diseño, y en este orden:

1. Fixture: una semana nueva de Da Porto **sin ninguna fila** en `programacion_semanal`. Si se añade
   como semana 2, `Max_Semana` de Da Porto pasa a 2 — comprobar antes que ningún gate ni golden dé
   por hecho que es 1 (`grep` por `maxWeek`, y correr `full-app-flow` y la estática después).
2. Test: el caso «semana sin actividades no fabrica filas ni tarjetas» deriva la semana en vez de
   fijar la 1 — recorre las semanas y toma la primera cuya lista viene vacía; si ninguna lo está,
   falla diciéndolo. El comentario que hoy justifica el literal se reescribe: ya no es cierto.

Verificación: ese caso en verde **en los dos entornos** (aislado y desarrollo), más
`full-operational-cycle.spec.mjs` y la estática.

## Task 4 — Retirar los cuatro `test.skip`

Quitar las cuatro líneas `test.skip(!MUTACION_HABILITADA, …)` y, si queda sin uso,
`MUTACION_HABILITADA` con su constante de motivo. **El candado de `dbSnapshot.mjs` no se toca.**

Ojo con lo que se borra: el comentario largo de `MUTACION_HABILITADA` documenta por qué el candado
existe y por qué el fixture no alcanzaba. Eso segundo deja de ser cierto y se va; lo primero se
conserva, resumido, donde se explique el candado.

Verificación: los 14 en verde sin `skip` en el aislado. Y en desarrollo, `10 passed, 4 skipped` —
que los cuatro sigan saltándose fuera del stack aislado es parte de la condición de hecho, no un
residuo.

## Task 5 — Cablear el gate en el CI

Dos piezas, y la primera es la que se olvida:

1. `docs/design-system/closeout-evidence.json`: una entrada de gate nueva con su `command`.
   `gate-receipt.mjs` lee el comando **de ahí** para que el recibo no pueda medir algo distinto de
   lo que el índice declara; sin la entrada, el script sale con «gate desconocido». Mirar antes qué
   exigen `tests/design-system/closeout-evidence.test.mjs`,
   `closeout-executable-contract.test.mjs`, `gate-receipt-content.test.mjs` y
   `release-governance.test.mjs` sobre la forma de esa entrada.
2. `.github/workflows/design-system.yml`: un paso propio **después** de que la app aislada esté
   arriba, con `APP_URL`, `E2E_BASE_URL`, `E2E_REQUIRE_ISOLATED_DB` y `E2E_ALLOW_DB_MUTATION`,
   copiando el patrón del paso de `full-app-flow`:
   - `node scripts/design-system/gate-receipt.mjs <gateId>` para levantar acta;
   - la línea que lee el recibo y propaga su `exitCode` — `gate-receipt.mjs` **siempre sale 0** a
     propósito, así que sin esa línea el paso saldría verde con el gate en rojo;
   - subida del recibo como artefacto y `git checkout --` del recibo después, porque escribe dentro
     del árbol y `runtime-budgets` se niega a medir sobre un árbol sucio.
   - `E2E_PROJECT_KEYS` no hace falta: este spec no itera `PROJECTS`, nombra sus proyectos.

Verificación: `npm run test:design-system:static` verde (es la suite que comprueba el workflow y el
índice), más una corrida local del comando exacto que declara el índice.

## Task 6 — Cerrar

`ingest` en `memoria/log.md`; corregir
`memoria/trampas/el-dato-esta-en-desarrollo-y-el-permiso-en-el-stack-aislado.md`, que queda
**derogada en su conclusión** aunque su diagnóstico siga siendo cierto: se marca el cambio en el
cuerpo y en el `resumen`, no se borra. Y el gate de publicación de `AGENTS.md` §Publicación, con la
re-verificación **después** de integrar `origin/main`.

## Riesgos y cómo se vigilan

| Riesgo | Vigilancia |
|---|---|
| El fixture es contrato anclado por `CI_FIXTURE_SHA256` | la estática de design-system corre tras **cada** task que lo toca, no al final |
| Medir el fixture viejo por no reconstruir `db` | reconstruir con `--build db` y comprobar el dato en la base antes de correr el spec |
| Sembrar semanas cambia la fase de la 5 de JMC | `Max_Semana` sigue en 5; se comprueba en la base y con `full-operational-cycle` |
| La semana nueva de Da Porto mueve su `Max_Semana` | Task 3 lo comprueba antes de sembrar y corre `full-app-flow` después |
| Un gate nuevo rojo bloquea el workflow | el paso se añade al final del plan, cuando el spec ya está verde en local |
