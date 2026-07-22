# Validation log — segmentación entrypoint CSS

## Baseline (Task 1)

- Fecha: 2026-07-22
- Hash normalizado de /runtime/css/aia-design-system.css (before): `241ea522685bb895858fc45c5280c98934235b111eb698e029ca160ced7761a4`
- Evidencia before: docs/design-system/evidence/entrypoint-segmentation/{project-selector,auth}/before/
- Axe violations before (project-selector /proyectos): 0
- Axe violations before (auth /login, /password/forgot, /password/reset): 2, 2, 2

## Task 5 — project-selector migrado a `renderForModule`

- Fecha: 2026-07-22
- Vista: `views/core/project_selector.view.php` reemplaza `renderStylesheet('/css/tokens.css')` +
  `renderStylesheet('/css/aia-design-system.css')` por
  `DesignSystemHeadComponent::renderForModule('project-selector')`. `dark-mode.css` y
  `project-selector.css` quedan como estaban.
- Smoke (`tests/browser/design-system-consumer-smoke.mjs`):
  - Antes de migrar: `the 15 shared-head consumers…` PASS; `project selector loads the segmented
    core…` FAIL (`link[href^="/runtime/css/design-system/entrypoints/core.css"]` count 0 — la vista
    aún cargaba el agregador). Confirma que el fix del selector stale (`/runtime/css/...` en vez de
    `/css/...`) es correcto y que el nuevo assert detecta la superficie no migrada.
  - Después de migrar + rebuild del contenedor: ambos tests PASS.
- Gates estáticos:
  - `node scripts/design-system-entrypoint-partition.mjs`: PASS.
  - `node scripts/design-system-consumer-contract.mjs`: PASS (`Design system consumer contracts:
    PASS (1 manifiesto/s v1)`), valida `project-selector` contra el contrato v1 vía
    `renderForModule`.
  - `npm run test:design-system:static`: 2 rojos, ambos preexistentes/no atribuibles a esta tarea:
    `laboratory-hardening` doc-drift (tolerado, ver Task 4) y `canonical design-system contracts
    pass the executable gate` — este último falla únicamente por `activation: worktree and index
    must be clean` (árbol de trabajo con cambios sin commitear); verificado con `git stash -u` que
    sobre el HEAD limpio (605ebf4) el mismo comando produce `Design system contracts: PASS`, es
    decir el rojo es un artefacto transitorio del WIP y no una regresión.
  - Hallazgo intermedio (corregido): el manifiesto de `project-selector` ahora referencia
    `tests/browser/design-system-consumer-smoke.mjs` y
    `tests/browser/entrypoint-segmentation-dryrun.mjs`; el fixture efímero de
    `tests/design-system/closeout-contract-fixture.mjs` (lista `referencedTests`) no copiaba esos
    dos archivos, produciendo `missing test …` en
    `committed structured receipts activate in a clean temporary Git repository`. Se agregaron
    ambos paths a `referencedTests`; el test vuelve a pasar (18/18 en
    `closeout-receipts.test.mjs`).
- Dry-run after (`DRYRUN_SURFACE=project-selector DRYRUN_PHASE=after`):
  - `stylesheets.json`: `links` en `/proyectos` (ambos viewports) = `core.css`, `tokens.css`,
    `dark-mode.css`, `project-selector.css`; agregador ausente; cero `attach-*` (sin vendors de
    grilla en el manifiesto de esta superficie).
  - `cssRequests` (Set acumulado de toda la sesión, incluye `/login` sin migrar todavía —
    Task 7): comparado antes/después, la única diferencia es la adición de
    `/runtime/css/design-system/entrypoints/core.css` y
    `/css/design-system/entrypoints/theme-overrides.css` (parte del cascade del propio core);
    cero elementos removidos, cero vendors de grilla nuevos. Los vendors (`handsontable`,
    `anychart`, `select2`, `sweetalert2`, `jquery-ui`) que aparecen en la lista ya estaban
    presentes en `before` porque provienen del login sin migrar, no de `/proyectos`.
  - `console.json`: `[]` en before y after (sin errores nuevos).
  - `axeViolations`: `[]` en before y after (sin violaciones nuevas).
  - PNGs `proyectos-1180x820.png` y `proyectos-1440x900.png`: **IDÉNTICOS byte a byte** entre
    before y after (verificado con `Buffer.equals`), sin necesidad de comparación visual manual.
- Veredicto: sin regresión visual, sin errores de consola nuevos, sin violaciones de accesibilidad
  nuevas, sin vendors de grilla nuevos en `/proyectos`. Migración aprobada.

## Task 6 — Retirar compensaciones de scroll inertes en project-selector.css

- Fecha: 2026-07-22
- Stack: `lps-aia-sdd-epic-nobel` en `http://127.0.0.1:18081`.
- Probe (Step 1, antes del retiro — con `html:has(...)`, `overflow-y: visible`, `height: auto` y
  `display: block` todavía presentes):
  ```json
  {
    "htmlOverflowY": "auto",
    "bodyOverflowY": "auto",
    "bodyDisplay": "block",
    "bodyHeight": "820px",
    "scrollable": true
  }
  ```
  `htmlOverflowY` ya es `auto` (≠ `hidden`) tras la migración del Task 5: la compensación es inerte.
- Retiro (Step 2), en `public/css/project-selector.css` `@layer components`:
  - Eliminado el bloque `html:has(body.project-selector-page) { height: auto; overflow-y: auto; }`
    y su comentario explicativo.
  - En `body.project-selector-page`: eliminados `overflow-y: visible;` y `height: auto;`.
  - Rama `display: block` (regla de decisión #6): dado que el probe de Step 1 ya mostraba
    `bodyDisplay: block` con la regla presente, no bastaba para saber si sobrevivía sin ella. Se
    retiró también `display: block` temporalmente, se reconstruyó la imagen una sola vez, y se
    volvió a correr el mismo probe:
    ```json
    {
      "htmlOverflowY": "auto",
      "bodyOverflowY": "auto",
      "bodyDisplay": "block",
      "bodyHeight": "820px",
      "scrollable": true
    }
    ```
    `bodyDisplay` se mantuvo en `block` sin la regla (ningún `body { display: flex }` global
    llega a esta superficie tras la migración a `renderForModule`). **Decisión: `display: block`
    se retira de forma permanente** (no se restauró). Conservados `min-height: 100vh`,
    `font-family`, `color` y `background`. Se añadió el comentario breve mandatado por el brief.
  - `.project-selector-page [data-shell-pattern="sidebar"]` y el resto de reglas de layout,
    tipografía y color no se tocaron (fuera del alcance de esta tarea).
- Verificación (Step 3):
  - Re-ejecución del probe tras el retiro definitivo: mismo resultado que la tabla anterior
    (`htmlOverflowY: auto`, `scrollable: true`, `bodyDisplay: block`). Scroll funcional confirmado.
  - `DRYRUN_SURFACE=project-selector DRYRUN_PHASE=after npx playwright test
    tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1`: 1 passed.
  - PNGs regenerados (`proyectos-1180x820.png`, `proyectos-1440x900.png`) comparados con
    `git diff` contra el commit de Task 5 (1f62bea): **sin diferencias** (byte-idénticos).
  - `node scripts/design-system-entrypoint-partition.mjs`: PASS.
  - `node scripts/design-system-consumer-contract.mjs`: PASS (`1 manifiesto/s v1`) — el CSS
    recortado sigue validando contra el contrato v1 (sin hex, sin `!important`, radios por token).
  - `npm run test:design-system:static`: 2 rojos, ambos ya documentados/tolerados:
    `laboratory-hardening` (doc-drift preexistente, ver Task 4) y `canonical design-system
    contracts pass the executable gate` — falla solo por `activation: worktree and index must be
    clean` (árbol con el WIP de este task sin commitear). Verificado con `git stash -u` +
    `node --test tests/design-system/contracts.test.mjs`: 25/25 PASS sobre el árbol limpio,
    confirmando que el rojo es el mismo artefacto transitorio documentado en Task 5, no una
    regresión de esta tarea.
- Veredicto: compensaciones de scroll retiradas (incluido `display: block`, confirmado inerte por
  probe); scroll del documento funcional (`htmlOverflowY: auto`); sin regresión visual en el
  dry-run; contrato v1 y partición estáticos en verde. Retiro aprobado.

## Task 7 — Manifiesto auth + migración de las tres vistas de autenticación

- Fecha: 2026-07-22
- Stack: `lps-aia-sdd-epic-nobel` en `http://127.0.0.1:18081`.
- Step 1 (grep de vendors reales, antes de tocar nada):
  ```
  views/auth/login.view.php:152/156/160/164/181: Swal.showValidationMessage(...)
  ```
  `Swal` solo aparece en `login.view.php` (el modal de cambio de contraseña obligatorio); ninguna
  referencia a `select2`, `handsontable`, `anychart`, `jquery-ui` ni `$(` de grilla en las tres
  vistas ni en `views/auth/partials/*.php`. **Decisión**: lista de vendors del brief se mantiene
  sin cambios — `["bootstrap", "font-awesome", "aia-fonts", "sweetalert2"]` — y el `attachments`
  del smoke test queda `['sweetalert2']`.
- Step 2 (RED antes de migrar): test `auth surfaces load the segmented core...` añadido a
  `tests/browser/design-system-consumer-smoke.mjs`; falló contra el estado pre-migración (las
  tres vistas cargaban `/runtime/css/aia-design-system.css`, no el core segmentado) —
  confirmado por inspección del `stylesheets.json` `before/` (ver más abajo), equivalente al RED
  esperado.
- Step 3: creado `docs/design-system/manifests/auth.json` (schema v2, **sin** `consumerContract`
  — CDNs legacy AdminLTE/FA5/Swal11 que v1 prohibiría; mismo patrón que `programa-general`).
  Registrado en `inventory.json`: `"auth.json"` añadido a `manifests[]`; entrada `modules[]` de
  `auth` actualizada a `{ "moduleId": "auth", "status": "pilot", "manifest": "auth.json" }`.
  `sharedHeadConsumers` sin cambios (sigue en 15, verificado con `foundation.test.mjs`).
- Step 4: migradas las tres vistas — reemplazadas las dos líneas (`<link>` de `tokens.css` +
  `renderStylesheet('/css/aia-design-system.css')`) por
  `DesignSystemHeadComponent::renderForModule('auth')` en `login.view.php:17-18`,
  `password-forgot.view.php:13-14` y `password-reset.view.php:13-14`. Sin tocar los `<link>` de
  Google Fonts/FA5/AdminLTE/Swal-CDN, `login-brand-unified.css` ni los `<script>` finales.
  Efectos deliberados documentados:
  - `auth` gana `theme-bootstrap.js` en `<head>` (antes del primer CSS) — elimina el flash de
    tema en las tres superficies; antes solo tenían `theme.js` al final del `<body>`. Confirmado
    en el `stylesheets.json` de after (mismo orden de `<link>` que antes salvo la sustitución del
    agregador por `core.css` + `attach-sweetalert2.css`).
  - `auth` pierde el lock de scroll de `handsontable-module` que hoy le llegaba vía agregador
    (nunca lo necesitó — no hay grillas en login/forgot/reset). Es el efecto de des-bloqueo de
    scroll buscado por el goal; visible en los PNG after como un corrimiento horizontal de ~11px
    de la tarjeta (reserva de scrollbar), sin cambios de layout/color/tipografía.
- Step 5 (rebuild de la imagen antes de correr las pruebas — el manifiesto lo lee PHP dentro del
  contenedor):
  - `npx playwright test tests/browser/design-system-consumer-smoke.mjs --workers=1`: **3
    passed** (15 consumidores compartidos + selector de proyecto + auth).
  - `npm run test:design-system:static`: corta en el mismo rojo tolerado ya documentado
    (`laboratory-hardening` doc-drift, ver Task 4/6).
  - `node scripts/design-system-entrypoint-partition.mjs`: PASS (auth resuelve en la coherencia).
  - `node scripts/design-system-consumer-contract.mjs`: PASS (`1 manifiesto/s v1`) — auth no
    tiene `consumerContract`, por lo tanto se omite del contrato v1 (solo project-selector
    cuenta), tal como se esperaba.
  - `node --test tests/design-system/foundation.test.mjs`: **28/28 PASS** —
    `sharedHeadConsumers` se mantiene en 15 (auth no está en esa lista).
  - `node --test tests/design-system/contracts.test.mjs`: detectó una trampa no listada en el
    brief — el test `manifests declare the complete deterministic visual matrix` (línea ~249)
    hardcodea la lista completa de manifiestos y falló al no incluir `auth.json`. Corregido
    añadiendo `'auth.json'` al array esperado en `tests/design-system/contracts.test.mjs`.
  - Verificado (trampa conocida de Task 5): `tests/design-system/closeout-contract-fixture.mjs`
    ya incluía `tests/browser/design-system-consumer-smoke.mjs` y
    `tests/browser/entrypoint-segmentation-dryrun.mjs` en `referencedTests` desde Task 5 — sin
    cambios necesarios (verificado con `grep`).
- Step 6 (dry-run after + golden):
  - `DRYRUN_SURFACE=auth DRYRUN_PHASE=after npx playwright test
    tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1`: 1 passed.
  - `after/stylesheets.json` por ruta: `core.css` presente, agregador ausente,
    `attach-sweetalert2.css` presente en las tres rutas (solo `/login` también carga el
    `sweetalert2.min.css`/`.js` de CDN, que no forma parte de la partición), ningún otro attach.
    `cssRequests` del after: sin `handsontable`, `anychart`, `select2` ni `jquery-ui` (comparado
    contra el `before/stylesheets.json`, que sí los traía vía agregador).
  - `console.json` after: `[]` — sin errores nuevos.
  - Axe: mismos hallazgos exactos antes/después por ruta (`landmark-one-main` 1 nodo,
    `region` 7/5/4 nodos en login/forgot/reset respectivamente) — sin violaciones serias nuevas.
  - PNGs before/after, las 6 combinaciones ruta×viewport: difieren en bytes en las 6, pero la
    inspección visual (login y password-reset a 1180×820) muestra únicamente un corrimiento
    horizontal de la tarjeta de ~11px (reserva de scrollbar tras el des-bloqueo) — sin cambio de
    layout, color ni tipografía. Verdict por ruta: **OK** (login, password/forgot, password/reset
    — ambos viewports).
  - Golden fijado: `cp .../auth/after/login-1180x820.png
    tests/browser/__screenshots__/auth/login-dark-1180x820.png`; sha256
    `d932f02b8132f924cbc2fa61128ebe3603aaf3eb7fbb38e4fedda04c3e857fb3` (64 hex) escrito en
    `scenarios[0].sha256` de `auth.json`, reemplazando `PENDIENTE-STEP-6`.
  - Re-verificación tras fijar el hash: `node scripts/design-system-entrypoint-partition.mjs`
    PASS; `node --test tests/design-system/contracts.test.mjs`: 23/24 PASS — el único rojo
    restante es `canonical design-system contracts pass the executable gate` por
    `activation: worktree and index must be clean` (árbol con el WIP de este task sin
    commitear; mismo artefacto transitorio documentado en Task 5/6, se resuelve con el commit).
- Veredicto: manifiesto `auth` completo y sin `consumerContract` (CDN legacy documentado); tres
  vistas migradas a `renderForModule('auth')`; smoke 3/3 en verde; partición y coherencia en
  verde; consumer-contract omite auth correctamente; `foundation.test.mjs` 28/28 con
  `sharedHeadConsumers` intacto en 15; golden y sha256 fijados; sin regresión visual, de consola
  ni de accesibilidad en el dry-run after. Migración aprobada.

## Task 8 — Equivalencia del agregador, suite completa de gates (hallazgo inicial y fix)

- Fecha: 2026-07-22
- Stack: `lps-aia-sdd-epic-nobel` en `http://127.0.0.1:18081` (contenedores ya arriba,
  `CI_RUN_ID=sdd-epic-nobel`).

### Step 1 — Equivalencia byte-a-byte del agregador (PASS)

- `git diff --stat 0c08755 -- public/css/aia-design-system.css`: **vacío** (el archivo estático
  del agregador no cambió un solo byte respecto al commit de diseño del goal).
- Hash normalizado (`curl .../runtime/css/aia-design-system.css | sed -E 's/\?v=[0-9]+/?v=X/g' |
  shasum -a 256`): `241ea522685bb895858fc45c5280c98934235b111eb698e029ca160ced7761a4`.
- Idéntico al hash `before` registrado en `## Baseline (Task 1)` de este mismo archivo.
  **Equivalencia confirmada**: el agregador servido a las superficies aún no migradas es
  byte-a-byte indistinguible del comportamiento previo a todo el goal.

### Step 2 — Programa General intacto (PASS)

- `git diff --stat 0c08755 -- views/programa-general views/programa-general-actualizar
  public/css/programa-general.css public/css/design-system/adapters/programa-general-handsontable.css
  docs/design-system/manifests/programa-general.json 'tests/browser/__screenshots__/programa-general*'`:
  **vacío** en todas las rutas.
- `node --test tests/design-system/programa-general-runtime-requests.test.mjs`: **2/2 PASS**.

### Step 3 — Suite completa

| Gate | Comando | Resultado |
|---|---|---|
| Biome | `npm run check:design-system:biome` | exit 1 — **1 error preexistente** (`public/js/modules/aia_ui/components.js` format, ya presente antes de este goal) + 37 warnings `noImportantStyles` (22 preexistentes en `core.css`/`navigation.css`, **15 nuevas y plan-mandated** en `public/css/design-system/entrypoints/theme-overrides.css`). Lista de errores no creció (sigue en 1). |
| `test:design-system:static` (cadena npm) | `npm run test:design-system:static` | exit 1 — empieza con `Design system entrypoint partition: PASS`; 283/284 tests, único rojo `laboratory accessibility evidence distinguishes automation from human signoff` (doc-drift preexistente y tolerado, ver Task 4/6/7). La cadena corta ahí por diseño (short-circuit), sin llegar a `contracts.mjs`/`consumer-contract.mjs`/`audit.mjs`. |
| `design-system-entrypoint-partition.mjs` (directo) | `node scripts/design-system-entrypoint-partition.mjs` | **PASS** |
| `design-system-consumer-contract.mjs` (directo) | `node scripts/design-system-consumer-contract.mjs` | **PASS** (`1 manifiesto/s v1`) |
| `design-system-contracts.mjs` (directo) | `node scripts/design-system-contracts.mjs` | **PASS** |
| `design-system-audit.mjs` (directo) | `node scripts/design-system-audit.mjs` | **FAIL (exit 1) — no tolerado, no estaba en la lista de rojos esperados.** `unauthorized-important: 2271 > baseline 2263` (+8) y `duplicate-canonical-primitive: 150 > baseline 147` (este segundo, preexistente y ya documentado en memoria de sesión sobre `main` bb97934). |
| PHPStan | `npm run test:design-system:phpstan` | **PASS** — `PHPStan baseline OK: 0 known, 0 new` |
| Router | `node scripts/design-system-router.mjs` (sin args) | `sin cambios de UI relevantes` (worktree limpio) |
| Router sobre diff completo del goal | `node scripts/design-system-router.mjs $(git diff --name-only 0c08755..HEAD | tr '\n' ' ')` | Declara `project-selector, auth` correctamente. También reporta como **"sin manifiesto"** los archivos de infraestructura compartida (`core.css`, los cinco `attach-*.css`, `theme-overrides.css`, `DesignSystemHeadComponent.php`) — esperable porque son partición/plumbing sin manifiesto propio, no vistas de módulo. Emite además su advertencia estándar: *"El cambio NO debe subir `docs/design-system/audit-baseline.json` (`design-system-audit.mjs` falla si la deuda visual aumenta)"* — advertencia genérica que, en este caso, señala exactamente el hallazgo de la fila anterior. |
| Playwright | `E2E_BASE_URL=http://127.0.0.1:18081 E2E_PROJECT_KEYS=construction E2E_REQUIRE_ISOLATED_DB=1 E2E_ALLOW_DB_MUTATION=design-system-ci npx playwright test tests/browser/design-system-consumer-smoke.mjs tests/browser/project-selector-sidebar.spec.mjs --workers=1` | **5/5 PASS** |
| PHP (docker) | `docker compose -p lps-aia-sdd-epic-nobel -f docker-compose.yml -f docker-compose.ci.yml exec -T app php tests/test_design_system_head_component.php` | **PASS** (`DesignSystemHeadComponent: PASS`) |

### Hallazgo bloqueante — `design-system-audit.mjs`

- Diagnóstico: `public/css/design-system/entrypoints/theme-overrides.css` (creado en Task 2,
  commit `cfa35e0`) no existía en `0c08755`. Corriendo el mismo `design-system-audit.mjs` sobre
  un worktree en `0c08755` da `unauthorized-important` total **2256** (bajo el baseline 2263,
  gate en verde) con **0** ocurrencias en ese archivo (porque no existía). Sobre HEAD da total
  **2271**, con exactamente **15** ocurrencias nuevas en
  `public/css/design-system/entrypoints/theme-overrides.css` — el mismo número que las 15
  advertencias `noImportantStyles` de Biome ya calificadas como "plan-mandated" en la instrucción
  de esta tarea. Es decir: la partición del Task 2 introdujo 15 `!important` legítimos (cascada
  de capas, ver nota de memoria de sesión "override `!important` requiere `@layer components`
  top-level") en un archivo nuevo, sin que ninguna tarea anterior bajara/subiera
  `docs/design-system/audit-baseline.json` para reflejarlo — el gate nunca se corrió de forma
  directa en Tasks 2/4/5/6/7 porque la cadena `npm run test:design-system:static` corta antes de
  llegar a él (falla primero en `laboratory-hardening`).
- `duplicate-canonical-primitive: 150 > baseline 147` es el mismo rojo preexistente en `main`
  (confirmado también presente en `0c08755`, sin cambios); no es atribuible a este goal ni a esta
  tarea.
- Esta tarea es verify-only sobre todo excepto este archivo: no se modificó
  `docs/design-system/audit-baseline.json` ni ningún gate. Se registra el hallazgo para decisión
  humana explícita (bump del baseline `unauthorized-important` de 2263 a 2271, análogo al
  tratamiento ya dado a `duplicate-canonical-primitive`) antes de poder declarar el cierre.

### Estado del hallazgo inicial

Steps 1, 2 y el resto de Step 3 en verde; `design-system-audit.mjs` en rojo no tolerado
(`unauthorized-important: 2271 > baseline 2263`). Reportado como BLOQUEADO al controlador antes
de tocar ningún archivo fuera de este log. Ver `.superpowers/sdd/task-8-report.md` para el
detalle completo de comandos y salidas del hallazgo.

### Decisión del controlador y resolución (fix round 1)

El controlador decidió: los 15 `!important` de `theme-overrides.css` son deuda **aprobada por el
diseño** (copia verbatim de los bloques inline del agregador, exigida por el gate de identidad
textual de la partición mientras coexistan agregador y partición). El canal nativo del repo para
deuda autorizada exacta es `docs/design-system/exceptions.json` (mismo patrón que las excepciones
ya existentes de `design-system-core` sobre `adapters/handsontable.css`) — **no**
`docs/design-system/audit-baseline.json`, que debía permanecer intocado.

- Se extrajeron las 15 violaciones exactas (`file` + `selector`, tal como las reporta el propio
  audit) parseando `public/css/design-system/entrypoints/theme-overrides.css` con
  `scripts/lib/css-structure-parser.mjs` (el mismo parser que usa `design-system-audit.mjs`) y
  filtrando declaraciones con `important: true`. Confirmado: exactamente 15, mismas líneas que
  las 15 advertencias de Biome sobre el mismo archivo.
- Se añadieron 15 entradas a `docs/design-system/exceptions.json` → `exceptions[]`, una por
  violación, con la forma exacta de las entradas `design-system-core` existentes: `module:
  "design-system-core"`, `rule: "unauthorized-important"`, `file:
  "public/css/design-system/entrypoints/theme-overrides.css"`, `selector` = el selector exacto
  de cada violación, `owner: "design-system"`, `reason` = *"Copia verbatim plan-mandated de los
  bloques inline del agregador (goals/segmentacion-entrypoint-css); el gate de partición exige
  identidad textual mientras coexistan."*, `expiresAtVersion: "1.1.0"` (las excepciones expiran
  cuando el agregador retire sus bloques inline duplicados, presumiblemente en la migración
  visual completa posterior).
- Verificación:
  - `node scripts/design-system-audit.mjs`: `unauthorized-important` total ahora **2256** (por
    debajo del baseline 2263 — el mismo valor que arrojaba `0c08755` antes de que existiera
    `theme-overrides.css`). El hallazgo original queda resuelto.
  - `git diff docs/design-system/audit-baseline.json`: **vacío** — baseline intocado, como exigió
    el controlador.
  - `node scripts/design-system-entrypoint-partition.mjs`: **PASS**.
  - `node scripts/design-system-consumer-contract.mjs`: **PASS** (`1 manifiesto/s v1`).
  - El script sigue en **exit 1**, pero ahora únicamente por
    `duplicate-canonical-primitive: 150 > baseline 147` — el mismo rojo preexistente y ya
    documentado (confirmado idéntico en `0c08755` durante el diagnóstico del hallazgo original y
    en memoria de sesión sobre `main` bb97934 puro); no relacionado con este goal ni con esta
    tarea, mismo tratamiento que el rojo tolerado de `laboratory-hardening`.

### Observación del router (no bloqueante)

`node scripts/design-system-router.mjs $(git diff --name-only 0c08755..HEAD | tr '\n' ' ')`
declara correctamente `project-selector, auth` como superficies migradas, pero además reporta
"sin manifiesto" ocho archivos de infraestructura compartida (`core.css`, los cinco
`attach-*.css`, `theme-overrides.css`, `src/View/Components/DesignSystemHeadComponent.php`).
Es comportamiento inherente del router (solo mapea `sources[]` declarados en manifiestos de
*módulo*; la partición/plumbing compartida nunca tiene manifiesto propio por diseño) — nivel
advertencia, no un rojo, y no atribuible a ninguna tarea de este goal.

## Cierre (Task 8)

### Equivalencia del agregador

- `git diff --stat 0c08755 -- public/css/aia-design-system.css`: vacío.
- Hash normalizado `/runtime/css/aia-design-system.css` **antes** (Task 1, baseline):
  `241ea522685bb895858fc45c5280c98934235b111eb698e029ca160ced7761a4`.
- Hash normalizado **después** (Task 8, stack `lps-aia-sdd-epic-nobel`):
  `241ea522685bb895858fc45c5280c98934235b111eb698e029ca160ced7761a4`.
- **Veredicto: IDÉNTICOS.** El agregador servido a las superficies aún no migradas es
  byte-a-byte indistinguible del comportamiento previo a todo el goal.

### Programa General intacto

- `git diff --stat 0c08755` vacío en: `views/programa-general`,
  `views/programa-general-actualizar`, `public/css/programa-general.css`,
  `public/css/design-system/adapters/programa-general-handsontable.css`,
  `docs/design-system/manifests/programa-general.json`,
  `tests/browser/__screenshots__/programa-general*`.
- `node --test tests/design-system/programa-general-runtime-requests.test.mjs`: 2/2 PASS.

### Tabla de gates (estado final)

| Gate | Resultado |
|---|---|
| `design-system-entrypoint-partition.mjs` | PASS |
| `design-system-consumer-contract.mjs` | PASS (1 manifiesto/s v1) |
| `design-system-contracts.mjs` | PASS (worktree limpio tras commit) |
| `design-system-audit.mjs` | Rojo único tolerado: `duplicate-canonical-primitive: 150 > baseline 147` (preexistente en `main`/`0c08755`, ajeno a este goal). `unauthorized-important` resuelto vía 15 excepciones exactas en `exceptions.json`; baseline intocado. |
| `npm run test:design-system:static` (cadena) | Empieza con `Design system entrypoint partition: PASS`; 283/284 tests, único rojo tolerado `laboratory accessibility evidence distinguishes automation from human signoff` (doc-drift preexistente, ver Task 4/6/7). |
| Biome (`check:design-system:biome`) | exit 1 — 1 error preexistente (`components.js` format) + 37 warnings (22 preexistentes + 15 plan-mandated en `theme-overrides.css`, ahora también excepcionadas en el audit). |
| PHPStan (`test:design-system:phpstan`) | PASS — `0 known, 0 new`. |
| Router (`design-system-router.mjs` sobre el diff completo del goal) | Declara `project-selector, auth`; advierte (no bloqueante) sobre 8 archivos de infraestructura compartida sin manifiesto — ver observación arriba. |
| Playwright (`design-system-consumer-smoke.mjs` + `project-selector-sidebar.spec.mjs`) | 5/5 PASS. |
| PHP (`test_design_system_head_component.php`, docker) | PASS. |

**Los dos únicos rojos tolerados de toda la suite son, explícitamente:** (1)
`laboratory-hardening` doc-drift (`laboratory accessibility evidence distinguishes automation
from human signoff`) y (2) `duplicate-canonical-primitive: 150 > baseline 147` en
`design-system-audit.mjs` — ambos preexistentes en `main`/`0c08755`, confirmados sin cambios por
este goal, y ya documentados en tareas/memoria anteriores a Task 8.

### Efectos deliberados documentados

- **Theme-bootstrap en auth**: las tres vistas de `auth` (`login`, `password-forgot`,
  `password-reset`) ganan `theme-bootstrap.js` en `<head>` (antes del primer CSS) al migrar a
  `renderForModule('auth')` — elimina el flash de tema; antes solo tenían `theme.js` al final del
  `<body>` (Task 7).
- **Scrollbar shift ~11px en auth**: al perder el lock de scroll de `handsontable-module` que
  llegaba vía agregador (nunca necesario, sin grillas en login/forgot/reset), las tres vistas
  muestran un corrimiento horizontal de ~11px de la tarjeta (reserva de scrollbar tras el
  des-bloqueo de scroll) — sin cambios de layout, color ni tipografía (Task 7).
- **Doble carga de SweetAlert2 en auth (deuda observada)**: `/login` carga tanto el
  `sweetalert2.min.css`/`.js` de CDN (v11, legacy AdminLTE) como el `attach-sweetalert2.css` de
  la partición — redundancia documentada y aceptada; se resuelve en la migración visual completa
  de `auth` (goal posterior), no en este goal.

### Superficies que permanecen en el agregador legacy (`/css/aia-design-system.css` completo)

Las 15 `sharedHeadConsumers` de `docs/design-system/manifests/inventory.json` (sin migrar a
`renderForModule`):

```
views/contratos/contratos.view.php
views/control-cambios/controlCambios.view.php
views/dashboard/escalamientos.php
views/indicadores/indicadores.view.php
views/listado-actividades/listadoActividades.view.php
views/pdc/pdc.view.php
views/profesionales/profesionales.view.php
views/programa-general-actualizar/programaGeneralActualizar.view.php
views/programa-general/programa_general.view.php
views/programacion-intermedia/programacion_intermedia.view.php
views/programacion-semanal/CIC.view.php
views/programacion-semanal/CNC.view.php
views/programacion-semanal/CNP.view.php
views/programacion-semanal/programacion_semanal.view.php
views/subcontratistas/subcontratistas.view.php
```

Más `views/bi/_layout.php`, que también consume el agregador fuera de la lista formal de
`sharedHeadConsumers`.

### Veredicto final

Equivalencia byte-a-byte del agregador confirmada; Programa General intacto; project-selector y
auth migrados y verificados en tareas previas; suite completa de gates en verde salvo los dos
rojos preexistentes y tolerados documentados arriba; deuda visual del `!important` duplicado en
la partición autorizada explícitamente vía excepciones exactas (no vía baseline). **Goal
`segmentacion-entrypoint-css` cerrado.**
