---
capa: fuente
tipo: plan
estado: abierto
id: P-CI-CARRILES
fecha: 2026-09-28
areas: [proceso, design-system]
fuente: docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md
ejecutor: claude
aprobacion: "Felipe, en el chat, 2026-09-28: plan aprobado; ejecuta Claude en esta sesión (subagent-driven) en vez del ejecutor por defecto. El sello formal `/aprobar`, si la compuerta lo exige, es de su mano."
resumen: "Seis tareas para que el CI de PR corra solo los gates que el diff alcanza: un selector propio con tests, un job `cambios`, `if:` por paso y un resumen que dice qué se omitió."
---

# CI por carriles — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que en `pull_request` el CI corra solo los gates que el diff puede haber roto, que `main` corra todo sin cancelarse, y que el resumen diga qué gate se omitió y por qué.

**Architecture:** Un script Node sin dependencias (`scripts/ci-carriles.mjs`) clasifica las rutas del diff en carriles y traduce carriles a banderas de gate. Un job `cambios` lo ejecuta y publica las banderas como `outputs`; los jobs existentes las leen con `if:` **por paso** (los contratos exigen que los pasos de runtime sigan dentro del job `design-system-runtime`). Ruta sin dueño → todo corre.

**Tech Stack:** GitHub Actions, Node 22 (`node:test`, sin paquetes), bash.

**Spec:** `docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md` (v1.1, aprobada por Felipe el 2026-09-28). Los IDs R1–R8 y D1–D5 son los de esa spec.

## Global Constraints

- `ci.yml`: dos espacios por nivel; pasos como `      - clave:` con propiedades a ocho espacios (lo exige `tests/design-system/workflow-contract-parser.mjs`, que además falla ante claves duplicadas dentro de un paso: si un paso ya tiene `if:`, se combina con `&&` en esa misma clave).
- No aparecen en `ci.yml` las palabras `deploy`, `production` ni `pull_request_target`, ni en comentarios (`visual-ci-contract.test.mjs:153`, sin distinguir mayúsculas).
- No se renombra ningún paso ni `id` citado por los contratos: `runtime-provenance`, `runtime-grants`, `php-suite`, `php-admin-db`, `blocking-runtime`, `keyboard-reflow-evidence`, `Enforce PHPStan baseline`, `Verify isolated runtime target`, `Start isolated runtime`, `Stop isolated runtime`, `Run Programa General persistence and RBAC gate`, `Preserve non-blocking evidence failures`, `Preserve runtime logs`. Los `run:` de `runtime-grants`, `php-suite` y `php-admin-db` quedan idénticos, y ningún paso fuera de `php-admin-db` menciona `DB_USER`, `DB_PASS` ni `LPS_ADMIN_DB_LANE`.
- `blocking-runtime` sigue **sin** `continue-on-error`. Las acciones siguen fijadas por SHA de 40 caracteres con `# vX.Y.Z`. Los checkout llevan `persist-credentials: false`.
- Los datos del evento (SHAs, ramas) entran a los scripts por `env:`, nunca interpolados en `run:` (zizmor).
- No se toca `paths-ignore`, goldens, baselines ni presupuestos (D4, fuera de alcance). Un cambio que no sea `if:`, `needs`, `concurrency`, `strategy.matrix` o el resumen queda fuera del plan.
- Comentarios y mensajes en español de Colombia con tuteo. Commits atómicos por tarea. **Sin push ni merge**: son de Felipe.
- Un test nuevo en `tests/design-system/*.test.mjs` entra solo en `npm run test:design-system:static` (glob en `scripts/design-system-static-suite.mjs`); no se registra en ningún índice.

## Review Focus

1. **Clave de salida mal escrita:** un `needs.cambios.outputs.<clave>` que el script no emite deja el gate omitido en silencio y la corrida en verde. Lo fija el test de contrato de la Tarea 3.
2. **Rutas con espacios, acentos o comillas, y renombrados:** un archivo movido entre carriles debe activar ambos. Lo fijan los tests de la Tarea 2 (`-z` y `--no-renames`).
3. **`push` con SHA anterior en ceros o inexistente** (rama nueva, force-push): el diff no se puede calcular y debe correr todo. Tarea 2.
4. **Diff vacío o solo borrados:** un diff vacío corre todo (no hay evidencia de que sea seguro omitir); un archivo borrado cuenta como ruta. Tareas 1 y 2.
5. **Ráfaga de merges a `main`:** con `cancel-in-progress: false` y un grupo por rama, GitHub descarta la corrida pendiente más vieja, así que «no se cancela» exige grupo por SHA en `push`. Tarea 3.

---

### Task 1: Selector puro de carriles y gates

**Files:**
- Create: `scripts/ci-carriles.mjs`
- Create: `tests/design-system/ci-carriles.test.mjs`
- Create: `goals/ci-por-carriles/goal.md` (declara el frente, regla 1 de `docs/coordinacion-sesiones.md`; copia el formato de `goals/adopcion-logo-construccion/goal.md`: frontmatter `tipo: goal-doc`, objetivo, condición de hecho = R1–R8 de la spec, y el pie «Archivos de este goal» con enlaces a la spec, este plan y `memoria/goals/estado`)

**Interfaces:**
- Produces (exports de `scripts/ci-carriles.mjs`):
  - `GATE_KEYS: readonly ['static','frontend','runtime','php_runtime','phpstan_pdc','css_minify','e2e','lab','pilot']`
  - `CARRILES: readonly ['docs','php','ds-core','ds-lab','ds-modulo','front-src','front-bundle','apps','todo']`
  - `clasificarRuta(ruta: string): Carril` — primera regla que coincide; sin coincidencia → `'todo'`.
  - `clasificar(rutas: string[]): Set<Carril>` — unión de las rutas; lista vacía → `Set{'todo'}`.
  - `gatesPara(carriles: Set<Carril>, opciones?: { completo?: boolean }): { gates: Record<GateKey, boolean>, temas: ('light'|'dark')[] }`

- [ ] **Step 1: Escribir el test que falla.** `ci-carriles.test.mjs` con dos tablas y estas aserciones:
  - Tabla `clasificarRuta` (ruta → carril esperado): `docs/superpowers/specs/x.md`→docs; `goals/algo/goal.md`→docs; `memoria/index.md`→docs; `decisiones/0001.md`→docs; `ROADMAP.md`→docs; `docs/design-system/README.md`→ds-core; `docs/security/rls-runtime-boundary.md`→php; `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md`→php; `goals/design-system-nucleo-gobernanza/x.md`→ds-modulo; `DESIGN.md`, `GEMINI.md`, `README.md`, `AGENTS.md`, `CLAUDE.md`→ds-modulo; `public/css/tokens.css`, `public/css/aia-design-system.css`, `public/css/design-system/core.css`, `public/js/modules/aia_ui/theme-toggle.js`, `src/View/Components/DesignSystemHeadComponent.php`→ds-core; `views/design-system/lab.view.php` y la ruta real del `DesignSystemLabController` →ds-lab; `public/css/profesionales.css`, `public/js/x.js`, `views/profesionales/profesionales.view.php`, `public/dist-css/x.css`→ds-modulo; `views/plan-compras/app.view.php`, `pdc-app/src/main.tsx`, `ct-app/src/x.ts`, `public/pdc-app/x.js`→apps; `frontend/src/shell/rutas.tsx`→front-src; `public/app/assets/index-C767lz-p.js`→front-bundle; `src/Services/Pdc/X.php`, `admin/src/Core/Router.php`, `database/fixtures/x.sql`, `composer.lock`, `phpstan.neon`→php; `.github/workflows/ci.yml`, `scripts/ci-carriles.mjs`, `tests/test_x.php`, `package.json`, `docker/php/Dockerfile`, `e2e/x.mjs`, `carpeta-nueva/x`→todo.
  - `clasificar([])` es `Set{'todo'}`; `clasificar(['docs/a.md','src/x.php'])` es `{docs, php}`.
  - Tabla `gatesPara` (carriles → banderas en `true`, el resto `false`; `temas`): `{docs}`→ninguna, `['light']`; `{front-src}`→static, frontend; `{ds-modulo}`→static, runtime, php_runtime, css_minify, e2e; `{ds-lab}`→lo de ds-modulo + lab, temas `['light','dark']`; `{ds-core}`→static, runtime, php_runtime, css_minify, e2e, lab, pilot, temas ambos; `{php}`→static, runtime, php_runtime, phpstan_pdc, e2e; `{front-bundle}`→static, frontend, runtime, php_runtime, e2e, pilot, temas ambos; `{apps}`→static, runtime, phpstan_pdc; `{todo}` y `{completo:true}`→las nueve en `true`, temas ambos.
  - Invariante: `runtime` es siempre el OR de `php_runtime, phpstan_pdc, css_minify, e2e, lab, pilot`; `temas` contiene `dark` si y solo si `lab` o `pilot`.
- [ ] **Step 2: Correr y ver el fallo.** `node --test tests/design-system/ci-carriles.test.mjs` → FALLA por módulo inexistente.
- [ ] **Step 3: Implementar el módulo** con las firmas de arriba. Reglas como lista ordenada `[RegExp, Carril]` con primera coincidencia gana (el orden de la tabla del test es el orden de evaluación: excepciones de `docs/` y `goals/` antes del comodín, `ds-core` y `ds-lab` antes de `ds-modulo`, `apps` y `front-*` antes de `php`); la matriz gate × carril es un objeto declarativo, la de la spec §3. Antes de fijar la regla de `ds-lab`, ubicar el controlador real con `git ls-files | grep DesignSystemLab` y usar esa ruta en la regla y en el test. Crear el `goal.md`.
- [ ] **Step 4: Correr y ver el verde.** `node --test tests/design-system/ci-carriles.test.mjs` → todos pasan, código de salida 0 leído en línea propia.
- [ ] **Step 5: Commit** (`scripts/ci-carriles.mjs`, el test y `goals/ci-por-carriles/goal.md`): `feat(ci): selector de carriles por ruta con matriz gate × carril`.

### Task 2: CLI del selector con fallo hacia «todo»

**Files:**
- Modify: `scripts/ci-carriles.mjs`
- Modify: `tests/design-system/ci-carriles.test.mjs`

**Interfaces:**
- Consumes: `clasificar`, `gatesPara`, `GATE_KEYS` de la Tarea 1.
- Produces:
  - `rutasDelCambio(entorno: { EVENT_NAME?: string, BASE_SHA?: string, BEFORE_SHA?: string, HEAD_SHA?: string }, git?: (args: string[]) => string): string[] | null` — `null` si el diff no se puede calcular. En `pull_request` diffea `BASE_SHA` contra `HEAD_SHA`; en `push`, `BEFORE_SHA` contra `HEAD_SHA`; usa `git diff --name-only --no-renames -z` y separa por NUL.
  - `calcularSalida(entorno, git?): { gates: Record<GateKey, boolean>, temas: string[], carriles: string[] }` — nunca lanza. `pull_request` con diff calculable → por carriles; **cualquier otro evento** (`push`, `workflow_dispatch`) o `null` o excepción → `completo`.
  - `formatearSalida(salida): string` — una línea `clave=true|false` por cada `GATE_KEYS`, más `temas=["light"]` (JSON, nunca vacío) y `carriles=php,ds-core` (o `completo`).
- Sin argumentos, `node scripts/ci-carriles.mjs` lee `process.env`, escribe `formatearSalida` en stdout y un resumen legible en stderr, y sale con 0.

- [ ] **Step 1: Escribir los tests que fallan**, con un `git` simulado por función y uno real sobre un repositorio temporal (`mkdtemp` + `git init` + dos commits):
  - `pull_request` con diff `docs/a.md\0docs/b.md\0` → todas las banderas `false`.
  - `push` (evento distinto de `pull_request`) → las nueve `true`, `carriles=completo`.
  - `BEFORE_SHA` de 40 ceros y SHA inexistente → `null` y salida `completo`.
  - El `git` simulado lanza → salida `completo`, sin propagar.
  - Ruta `docs/con espacio/año "raro".md` sobre el repositorio temporal real → aparece íntegra y clasifica como `docs`.
  - Renombrado `src/a.php` → `docs/a.md` en el repositorio temporal → los carriles incluyen `php` y `docs`.
  - Archivo borrado (`src/x.php` eliminado) → cuenta como ruta y da `php`.
  - `formatearSalida` emite exactamente una línea por clave de `GATE_KEYS` y `temas` es JSON válido con al menos `light`.
- [ ] **Step 2: Correr y ver el fallo.** `node --test tests/design-system/ci-carriles.test.mjs`.
- [ ] **Step 3: Implementar** `rutasDelCambio`, `calcularSalida`, `formatearSalida` y el punto de entrada (ejecutar solo si el archivo es el módulo principal, para que `import` desde el test no dispare el CLI). El `git` por defecto usa `execFileSync('git', args)` (sin shell).
- [ ] **Step 4: Correr y ver el verde.** El mismo comando, todos pasan.
- [ ] **Step 5: Commit:** `feat(ci): CLI del selector con fallo hacia la corrida completa`.

### Task 3: Job `cambios`, concurrencia y matriz de temas

**Files:**
- Modify: `.github/workflows/ci.yml` (`concurrency`, nuevo job `cambios` antes de `design-system-static`, `needs` e `if` de los dos jobs existentes, `strategy.matrix.theme`)
- Create: `tests/design-system/ci-carriles-workflow.test.mjs`

**Interfaces:**
- Consumes: `GATE_KEYS` y la salida de `formatearSalida` de la Tarea 2.
- Produces: job `cambios` con `outputs` para cada clave de `GATE_KEYS` más `temas` y `carriles`; las Tareas 4 y 5 leen `needs.cambios.outputs.<clave>`.

- [ ] **Step 1: Escribir el test de contrato que falla** (`ci-carriles-workflow.test.mjs`, lee `.github/workflows/ci.yml` como texto y usa `parseJobSteps`):
  - Toda expresión `needs.cambios.outputs.<x>` del archivo tiene `<x>` en `GATE_KEYS` o es `temas` o `carriles`, y el job `cambios` declara un `output` por cada clave de `GATE_KEYS` (Review Focus 1).
  - El grupo de `concurrency` distingue el evento: para `pull_request` usa `github.ref`, para los demás usa `github.sha`, y `cancel-in-progress` vale `${{ github.event_name == 'pull_request' }}` (Review Focus 5).
  - `cambios` hace checkout con `fetch-depth: 0` y `persist-credentials: false`, y pasa `EVENT_NAME`, `BASE_SHA`, `BEFORE_SHA` y `HEAD_SHA` por `env:`; el `run` no contiene `${{`.
  - `design-system-static` y `design-system-runtime` declaran `cambios` en su `needs`; el `if` del job runtime exige `needs.cambios.outputs.runtime == 'true'`, tolera que `design-system-static` haya quedado `skipped` y **no** corre si falló.
  - `strategy.matrix.theme` sale de `fromJSON(needs.cambios.outputs.temas || '["light","dark"]')`.
- [ ] **Step 2: Correr y ver el fallo.** `node --test tests/design-system/ci-carriles-workflow.test.mjs`.
- [ ] **Step 3: Editar `ci.yml`.** `cambios`: `runs-on: ubuntu-latest`, `timeout-minutes: 5`, `permissions: contents: read`, checkout y `setup-node` con los mismos SHA que el resto, un paso `id: carriles` que corre `node scripts/ci-carriles.mjs >> "$GITHUB_OUTPUT"`, y `outputs` que mapean cada clave a `steps.carriles.outputs.<clave>`. Job `design-system-static`: `needs: cambios` e `if: needs.cambios.outputs.static == 'true'`. Job `design-system-runtime`: `needs: [cambios, design-system-static]` e `if: ${{ !cancelled() && needs.cambios.result == 'success' && needs.cambios.outputs.runtime == 'true' && (needs.design-system-static.result == 'success' || needs.design-system-static.result == 'skipped') }}`. Sin las palabras vetadas en comentarios.
- [ ] **Step 4: Verificar.** En línea propia cada uno: `node --test tests/design-system/ci-carriles-workflow.test.mjs`; `node --test tests/design-system/visual-ci-contract.test.mjs tests/design-system/ci-workflow-provenance.test.mjs tests/design-system/phpstan-baseline.test.mjs`; `actionlint .github/workflows/ci.yml` (si falta, `brew install actionlint`, permiso ya concedido); y el contrato PHP: enlazar `.env` como indica `CLAUDE.md`, `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, y `docker compose exec app php tests/test_runtime_boundary_ci_contract.php`. Antes del PHP, confirmar qué árbol monta el contenedor (regla 7 de `docs/coordinacion-sesiones.md`). Esperado: RC 0 en los cinco.
- [ ] **Step 5: Commit:** `ci: job cambios, concurrencia por SHA en push y matriz de temas dinámica`.

### Task 4: `if:` por paso en los jobs static y runtime

**Files:**
- Modify: `.github/workflows/ci.yml`
- Modify: `tests/design-system/ci-carriles-workflow.test.mjs`

**Interfaces:**
- Consumes: los `outputs` del job `cambios` (Tarea 3).
- Produces: cada gate cuelga de su bandera, y los gates que no dependen del tema corren solo en la pata `light`.

Condiciones por paso (`B` = `needs.cambios.outputs`; «luz» = `matrix.theme == 'light'`):

| Paso (nombre o `id`) | `if:` |
|---|---|
| job static: `npm ci --prefix frontend`, «Comprobar tipos del frontend», «Correr las pruebas del frontend» | `B.frontend == 'true'` |
| job static: el resto (imagen, PHP `puro`, static, contrato de PG, secreto en bundle) | sin `if` (siguen `foundation.test.mjs`, que ejecuta PHP con la imagen construida: `ci.yml:32-46`) |
| runtime: «Verify the comment-free CSS matches its source» | luz && `B.css_minify` |
| `phpstan-baseline`, `runtime-grants`, `php-suite`, `php-admin-db` | luz && `B.php_runtime` |
| `phpstan-pdc` | luz && `B.phpstan_pdc` |
| `full-app-flow`, `semanal-roles-phases`, `pg-persistence-rbac` | luz && `B.e2e` |
| `runtime-budget-measure`, `runtime-budget-check` | luz && `B.pilot` |
| `blocking-runtime`, `keyboard-reflow-evidence` | `B.lab` (las dos patas) |
| `pilot-lab-gates` | `B.pilot` (las dos patas) |
| los tres `upload-artifact` de recibos (`ci.yml:327,369,416`) | `always() && steps.<id del gate>.outcome != 'skipped'` |

- [ ] **Step 1: Ampliar el test que falla:** para cada fila de la tabla, el paso existe y su `if` contiene su bandera (y «luz» donde se indica); ningún paso tiene la clave `if` duplicada; los tres `upload-artifact` con `if-no-files-found: error` (Review R8) condicionan sobre el `outcome` de su gate; `blocking-runtime` no tiene `continue-on-error`; el `if` de `Preserve non-blocking evidence failures` sigue siendo `steps.keyboard-reflow-evidence.outcome == 'failure'`.
- [ ] **Step 2: Correr y ver el fallo.** `node --test tests/design-system/ci-carriles-workflow.test.mjs`.
- [ ] **Step 3: Editar `ci.yml`** añadiendo o combinando con `&&` cada `if:` de la tabla, sin alterar `run`, `env`, `id` ni nombres.
- [ ] **Step 4: Verificar** los mismos cinco comandos de la Tarea 3 más `npm run test:design-system:static` (su salida real, RC leído aparte). Esperado: RC 0 en todos.
- [ ] **Step 5: Commit:** `ci: gates condicionados por carril; los independientes del tema corren una vez`.

### Task 5: Resumen que dice qué se omitió

**Files:**
- Modify: `.github/workflows/ci.yml` (paso «Summarize gate results»)
- Modify: `tests/design-system/ci-carriles-workflow.test.mjs`

**Interfaces:**
- Consumes: `needs.cambios.outputs.carriles` y los `steps.<id>.outcome` existentes.
- Produces: filas de la tabla con `omitido (carril no tocado)` cuando el `outcome` es `skipped`, y una línea con los carriles detectados.

- [ ] **Step 1: Ampliar el test que falla:** el `run` de «Summarize gate results» define una función que traduce `skipped` a `omitido (carril no tocado)` y la usa en las trece filas de la tabla; sigue definiendo `G_RUNTIME_GRANTS: ${{ steps.runtime-grants.outcome }}` y referenciando `$G_RUNTIME_GRANTS`; el bucle de veredicto solo trata `failure` como rojo (un omitido no es rojo, pero tampoco se imprime como `success`); `CARRILES: ${{ needs.cambios.outputs.carriles }}` entra por `env:` y se imprime al inicio del resumen.
- [ ] **Step 2: Correr y ver el fallo.** `node --test tests/design-system/ci-carriles-workflow.test.mjs`.
- [ ] **Step 3: Editar el paso** conservando el orden y el texto de cada fila, el bucle `for outcome in …` y el mensaje de error final. Cambia solo cómo se imprime el resultado y la línea de carriles.
- [ ] **Step 4: Verificar** los mismos comandos de la Tarea 4. Esperado: RC 0 en todos.
- [ ] **Step 5: Commit:** `ci: el resumen distingue omitido de verde y lista los carriles`.

### Task 6: Prueba en corridas reales y cierre del frente

**Files:**
- Modify: `goals/ci-por-carriles/goal.md` (evidencia de R1–R8)
- Modify: `TASKS.md` (pendientes que salgan)

**Interfaces:**
- Consumes: todo lo anterior. Requiere **push y PR, que son de Felipe**: esta tarea se detiene en `BLOCKED` hasta que él los autorice en el chat.

- [ ] **Step 1: Pedir a Felipe** el push de `ci/ci-por-carriles` y la apertura del PR. El PR toca `.github/**` y `scripts/**` → carril `todo` → corre todo: es la primera prueba de R2 y de R6 en el runner real.
- [ ] **Step 2: Declarar la condición de hecho en el cuerpo del PR antes de que corra el CI** (regla de `AGENTS.md`): R1–R8 y el resultado esperado del resumen. Comprobar antes que el workflow corre para ese diff.
- [ ] **Step 3: Tres PR de prueba, con autorización de Felipe, cerrados sin merge:** uno que solo toque `docs/superpowers/`, uno que solo toque `frontend/src/`, uno que solo toque un CSS de módulo (`public/css/profesionales.css`). Anotar duración, banderas y filas «omitido» de cada resumen frente a la línea base de ~15 min (R7, V5). Confirmar que los tres `upload-artifact` de recibos no fallan (R8).
- [ ] **Step 4: Leer el verde de las variables `G_*`** del resumen, no del color del tablero, y comprobar que tras el merge la corrida de `main` corre todo y no se cancela (R3).
- [ ] **Step 5: Registrar la evidencia** en `goals/ci-por-carriles/goal.md`, mover los pendientes a `TASKS.md` (por ejemplo: PHP `puro` innecesario para `front-src`, celdas † de la matriz por afinar con los datos medidos, `apps` que arranca el runtime solo por PHPStan del PDC) y commitear: `docs(ci): evidencia del frente ci-por-carriles`.

---

## Autorrevisión

- **Cobertura de la spec:** R1 → T1–T2; R2 → T1–T2; R3 → T3 y T6; R4 → T4; R5 → T5; R6 → T3–T5; R7 → T6; R8 → T4 y T6. D1 (`main` completo) → T2 (`completo` en `push`) y T3; D2 → T1–T3; D3 → reglas de T1; D4 y D5 → Global Constraints.
- **Consistencia de tipos:** las nueve claves de `GATE_KEYS` de la Tarea 1 son las mismas de la tabla de la Tarea 4 y de los `outputs` de la Tarea 3; no hay bandera aparte para la imagen: `foundation.test.mjs` ejecuta PHP con docker compose dentro de `static`, así que la imagen y PHP `puro` corren siempre que corre `static` (la fila de la spec quedó corregida en consecuencia).
- **Proporción:** las decisiones que el ejecutor no puede tomar solo (rutas, banderas, nombres, condiciones por paso) están escritas; los cuerpos de funciones y el YAML completo no.

<!-- prueba descartable del carril docs, no se mergea -->
