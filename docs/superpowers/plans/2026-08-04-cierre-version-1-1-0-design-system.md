---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-04
areas: [design-system]
fuente: docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md
resumen: Publicar la versión 1.1.0 del design system pagando o re-venciendo con evidencia las 39 excepciones que expiran en ella, con la suite estática en 8/8.
---

# Cierre de la versión 1.1.0 del design system — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publicar la versión 1.1.0 del design system pagando o re-venciendo con evidencia las 39 excepciones que expiran en ella, con la suite estática en 8/8.

**Architecture:** App PHP 8.3 sin framework; CSS por capas con tokens `--ds-*` en `public/css/`; el estado de versión vive en `docs/design-system/version.json` y lo custodian `scripts/design-system-contracts.mjs`, `design-system-closeout-contract.mjs`, `design-system-activation-git.mjs` y `design-system-audit.mjs` (este último cobra `expiresAtVersion` de `docs/design-system/exceptions.json`).

**Tech Stack:** PHP 8.3 (Docker `app`), CSS con `@layer` y tokens, Node (`npm run test:design-system:static`), Playwright, navegador integrado.

## Global Constraints

- **Spec fuente:** `docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md` (decisiones D1–D4 del usuario, 2026-08-04).
- **NO arrancar hasta que la campaña dark mode (plan `2026-08-04-cierre-dark-mode-campana-decisiones.md`) haya terminado (D4).** Al arrancar: `git log --oneline -5` y comprobar si la campaña tocó `public/css/project-selector.css`, `views/core/project_selector.view.php` o `docs/design-system/exceptions.json`; si sí, re-medir antes de editar.
- Desktop ≥1180 px, dark only; viewport canónico 1180×820. Nada de mobile/tablet/`linen` (AGENTS.md).
- Sesión SIEMPRE por `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E` (o sin `p` para aterrizar en `/proyectos`). Nunca `/login`.
- Colores solo con tokens `--ds-*`; sin hex ni siquiera en comentarios (el audit los cuenta ahí).
- `npm run test:design-system:static` debe dar **8/8** al cerrar cada task (salvo donde el paso diga lo contrario: las tasks 5–6 dejan el gate en rojo transitorio y la task 7 lo cierra).
- Sin cambios de comportamiento, datos, rutas ni permisos. Proyectos con datos reales: solo lectura.
- Commits atómicos por task, mensaje honesto. No push sin petición explícita.
- Trampa de contexto: `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md` — leerla antes de la task 5.
- Ledger: `.superpowers/sdd/2026-08-XX-cierre-1-1-0/progress.md` (crear al arrancar).

---

### Task 1: Línea base y censo verificado

**Files:**
- Create: ledger `.superpowers/sdd/2026-08-XX-cierre-1-1-0/progress.md`
- Read: `docs/design-system/exceptions.json`, `public/css/project-selector.css`, `views/core/project_selector.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: en el ledger, la tabla censo de las 39 excepciones `expiresAtVersion: "1.1.0"` con columnas `# | file | selector | rule | grupo (selector/hex/handsontable/capas)`; las tasks 2–4 la consumen.

- [ ] **Step 1: Confirmar el terreno.** Run: `git log --oneline -5` y `git status --short`. Comprobar la constraint global de la campaña (arriba). Anotar en el ledger el commit de arranque.
- [ ] **Step 2: Censo desde el JSON, no de memoria.** Run:
```bash
node -e 'const d=require("./docs/design-system/exceptions.json");const e=d.exceptions.map((x,i)=>({i,...x})).filter(x=>x.expiresAtVersion==="1.1.0");console.log(e.length);for(const x of e)console.log([x.i,x.file,x.rule,(x.selector||"").slice(0,60)].join(" | "))'
```
Expected: 39 filas. Clasificarlas en el ledger: grupo A = `file: public/css/project-selector.css` (~15), grupo B = hex `--primary` en `public/css/styles.css` (1), grupo C = adaptadores Handsontable (`handsontable-module.css`, `design-system/adapters/*.css`) (~21), grupo D = `@layer states`/`@layer responsive` en `theme-overrides.css`/`aia-design-system.css` (2). Si los números difieren del spec, anotar y seguir con los reales.
- [ ] **Step 3: Línea base de la suite.** Run: `npm run test:design-system:static 2>&1 | tail -12`. Expected: 8/8 (si no, STOP: el terreno no está como lo dejó la campaña — reportar).
- [ ] **Step 4: Captura de referencia de `/proyectos`.** Dev door sin `p`, viewport 1180×820 dark, captura completa al ledger (es el «antes» de la task 2).

### Task 2: Migración de `/proyectos` a primitivas `aia-*` (D1 — paga el grupo A)

**Files:**
- Modify: `public/css/project-selector.css` (retirar los bloques verbatim con `!important`), `views/core/project_selector.view.php` (clases: sustituir las legacy por primitivas `aia-*` donde el CSS retirado las vestía)
- Modify: `docs/design-system/exceptions.json` (eliminar las entradas del grupo A)
- Test: `npm run test:design-system:static` + verificación visual

**Interfaces:**
- Consumes: censo grupo A (Task 1).
- Produces: `/proyectos` sin `!important` verbatim; excepciones del grupo A eliminadas del JSON.

- [ ] **Step 1: Mapa antes de tocar.** Por cada selector del grupo A (`.card`/`.project-card`/`.aia-card`, `.modal-content`, `.dropdown-menu`, `.btn*`, `.form-control`/`.select2*`/`.ts-*`, `.input-group*`, `.badge*`, `.progress*`): localizar en `project-selector.css` el bloque que lo viste (grep por el selector) y en la vista PHP qué elementos llevan esas clases. Anotar en el ledger el par bloque-CSS → elementos.
- [ ] **Step 2: Leer el contrato antes de editar.** Leer `DESIGN.md` (flujo obligatorio para superficies) y en `docs/design-system/component-catalog.json` qué primitivas existen (`aia-card`, `aia-btn`, `aia-input`, `aia-select`, `aia-chip`, `aia-modal-surface`, `aia-panel` ya aparecen en los propios selectores de las excepciones). NO inventar primitivas nuevas: si un elemento no tiene primitiva equivalente, vestirlo con tokens en la hoja del módulo, sin `!important`.
- [ ] **Step 3: Migrar bloque a bloque.** Por cada par del mapa: (a) aplicar la clase primitiva en la vista PHP o sustituir el bloque verbatim por reglas con tokens `--ds-*` sin `!important` en `project-selector.css`; (b) recargar `/proyectos` (dev door sin `p`) y comprobar que la superficie no se rompe ANTES de pasar al siguiente bloque. Regla de decisión: si un `!important` resulta imprescindible porque un vendor (select2/tom-select) lo pelea con estilos inline, ese caso concreto NO se paga — se re-vence a `1.2.0` con la razón medida (misma mecánica que Task 4) y se anota en el ledger.
- [ ] **Step 4: Eliminar del JSON las excepciones pagadas.** Borrar del array `exceptions` las entradas del grupo A cuyo bloque se migró (dejar las re-vencidas del step 3 si las hubo, ya con `1.2.0`).
- [ ] **Step 5: Verificar.** Run: `npm run test:design-system:static 2>&1 | tail -12`. Expected: 8/8 (el audit ya no encuentra los `!important` retirados; si marca hallazgos nuevos en `project-selector.css`, corregirlos — son reglas recién escritas). Sonda funcional: buscar un proyecto en el input, abrir el dropdown, entrar a un proyecto — los tres flujos operan igual que en la captura del «antes».
- [ ] **Step 6: Ciclo triple sobre `/proyectos`.** `/impeccable audit` → `/ux-heuristics` → `/refactoring-ui`, a 1180×820 dark. Hallazgos que sean de esta migración: corregirlos aquí; ajenos: chip. Resultado al ledger con captura del «después».
- [ ] **Step 7: Commit.**
```bash
git add public/css/project-selector.css views/core/project_selector.view.php docs/design-system/exceptions.json
git commit -m "feat(proyectos): el selector migra a primitivas aia-* y paga sus 15 excepciones verbatim (D1)"
```

### Task 3: El acento `--primary` deja de ser hex suelto (D-spec §2 — paga el grupo B)

**Files:**
- Modify: `public/css/styles.css` (la declaración `--primary` en `:root`), `public/css/design-system/tokens.css` (si se declara token oficial)
- Modify: `docs/design-system/exceptions.json` (eliminar la entrada del grupo B)

**Interfaces:**
- Consumes: censo grupo B (Task 1); premisa `memoria/decisiones/inspiracion-apple-en-dark-aia.md`.
- Produces: cero `hardcoded-hex` de `--primary`; consumidores de `--primary` intactos.

- [ ] **Step 1: Censar consumidores vivos.** Run: `grep -rn "var(--primary)" public/css views public/js admin | grep -v node_modules`. La propia excepción documenta que conserva consumidores en `styles.css`. Listarlos en el ledger.
- [ ] **Step 2: Decidir por regla, no por gusto.** (a) Si existe un token `--ds-*` cuyo valor resuelto coincide o cumple el mismo papel de acento (leer la sección de acentos de `tokens.css`): `--primary: var(--ds-<token>)`. (b) Si no existe: declarar en `tokens.css` el token de acento con el valor actual (sin hex en `styles.css`: `--primary: var(--ds-color-accent-apple)` o el nombre que siga la convención del bloque donde se declare) — la decisión de marca ya está tomada en la premisa Apple-en-dark: el acento se queda, tokenizado.
- [ ] **Step 3: Verificar que nada cambió de color.** Sonda en una vista consumidora: `getComputedStyle(document.documentElement).getPropertyValue('--primary')` antes y después — mismo valor resuelto. Eliminar la entrada del grupo B del JSON.
- [ ] **Step 4: Suite + commit.** Run: `npm run test:design-system:static` → 8/8.
```bash
git add public/css/styles.css public/css/design-system/tokens.css docs/design-system/exceptions.json
git commit -m "fix(tokens): el acento --primary se tokeniza y paga su excepcion de hex (spec 1.1.0)"
```

### Task 4: Revisión una a una de los grupos C y D (D3)

**Files:**
- Modify: `docs/design-system/exceptions.json` (pagar o re-vencer cada entrada), los CSS de adaptadores cuyo `!important` se pague (`public/css/handsontable-module.css`, `public/css/design-system/adapters/*.css`, `public/css/design-system/entrypoints/theme-overrides.css`, `public/css/aia-design-system.css`)
- Test: `npm run test:design-system:static`, visuales de PG/PI si un pago mueve píxeles

**Interfaces:**
- Consumes: censo grupos C y D (Task 1).
- Produces: informe una-a-una en el ledger (pagadas N, re-vencidas M, cada una con evidencia); cero entradas `expiresAtVersion: "1.1.0"` en el JSON.

- [ ] **Step 1: Por cada excepción del grupo C (una a la vez):** (a) leer su `reason` y localizar el CSS legacy que la obliga — el selector móvil global (`grep -rn "display: block" public/css/styles.css public/css/*.css | grep -i "handsontable\|table"`), la piel del vendor (`node_modules/handsontable/dist/*.css`), o estilos inline del vendor (inspección en vivo en `/programa-general`); (b) veredicto: **el opresor ya no existe** → quitar el `!important` de la regla protegida, verificar la superficie en vivo (PG con datos reales en solo lectura, 1180×820) y borrar la excepción; **sigue vivo** → editar la entrada: `"expiresAtVersion": "1.2.0"` y añadir al final de `reason`: `" Re-vencida 1.1.0→1.2.0: la paga el retiro del puente legacy movil/vendor."`; (c) línea en el ledger con el veredicto y su evidencia (grep o captura).
- [ ] **Step 2: Si un pago rompe la superficie** (celdas descolocadas, tarjetas blancas): revertir ese `!important`, re-vencer la excepción y anotar por qué — regla del spec: ante duda, re-vencer, no forzar el pago.
- [ ] **Step 3: Grupo D (las 2 capas).** `@layer states` y `@layer responsive`: comprobar si sus reglas siguen existiendo (`grep -n "@layer states\|@layer responsive" public/css -r`) y si alguna regla interna sigue teniendo efecto a 1180 px dark. Mismas dos salidas que el step 1. Nota: el informe de dobles capas de la Task 27 de la campaña (ledger de la campaña) puede tener ya el diagnóstico — leerlo antes de medir de cero.
- [ ] **Step 4: Verificación de cierre de task.** Run:
```bash
node -e 'const d=require("./docs/design-system/exceptions.json");console.log("quedan en 1.1.0:",d.exceptions.filter(x=>x.expiresAtVersion==="1.1.0").length)'
```
Expected: `quedan en 1.1.0: 0`. Suite 8/8. Goldens de PG/PI re-corridos si algún pago movió píxel (`npx playwright test tests/browser/programa-general.visual.mjs tests/browser/programacion-intermedia.visual.mjs --workers=1`).
- [ ] **Step 5: Commit.**
```bash
git add docs/design-system/exceptions.json public/css
git commit -m "chore(ds): revision una a una de las 23 excepciones legacy — pagadas las huerfanas, re-vencidas a 1.2.0 las que el puente aun obliga (D3)"
```

### Task 5: Gates a «al menos 1.0.0» (D2)

**Files:**
- Modify: `scripts/design-system-closeout-contract.mjs:121-124`, `scripts/design-system-activation-git.mjs:55`
- Test: `npm run test:design-system:static` (queda verde con versión 1.0.0; la prueba real llega con la task 7)

**Interfaces:**
- Consumes: nada de tasks previas (independiente; se hace antes del bump para que la task 7 sea solo datos).
- Produces: los gates aceptan cualquier versión SemVer con major ≥1 + `status: stable`.

- [ ] **Step 1: Leer la trampa.** `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md` — el patrón exacto ya se probó el 2026-08-04.
- [ ] **Step 2: Editar `design-system-closeout-contract.mjs`.** Sustituir las líneas 121-124:
```js
  const activatedVersion = /^([1-9]\d*)\.\d+\.\d+$/.test(versionDocument?.version ?? '');
  const versionActivated = activatedVersion
    && versionDocument?.status === 'stable';
  const versionPartiallyActivated = activatedVersion
    || versionDocument?.status === 'stable';
```
- [ ] **Step 3: Editar `design-system-activation-git.mjs:55`.** La condición `version?.version !== '1.0.0'` pasa a `!/^([1-9]\d*)\.\d+\.\d+$/.test(version?.version ?? '')`.
- [ ] **Step 4: Verificar sin bump.** Run: `npm run test:design-system:static` → 8/8 (con 1.0.0 los gates siguen pasando: el cambio es compatible hacia atrás). `tests/design-system/closeout-evidence.test.mjs` no se toca (su rama `stable` cubre cualquier versión).
- [ ] **Step 5: Commit.**
```bash
git add scripts/design-system-closeout-contract.mjs scripts/design-system-activation-git.mjs
git commit -m "feat(ds): los gates de activacion aceptan versiones posteriores a 1.0.0 — el hito fue unico (D2)"
```

### Task 6: Sincronización de manifiestos y bump de versión

**Files:**
- Modify: `docs/design-system/version.json` y todos los manifiestos con `designSystemVersion` que el gate exija (lista canónica: la salida del propio gate — incluye `component-catalog.json`, `stable-api-1.0.0.json`, `ui-groups-inventory.json`, `state-semantics.json`, `vendors.json`, `legacy-aliases.json`, `manifests/inventory.json`, `closeout-evidence.json`, `a11y-baseline.json`, `a11y-exceptions.json`, `exceptions.json`)

**Interfaces:**
- Consumes: tasks 2–5 completadas (cero excepciones en 1.1.0; gates flexibles).
- Produces: `version.json` en `1.1.0/stable` y manifiestos sincronizados. **El gate queda en rojo transitorio por «version.json must match HEAD» hasta el commit de la task 7.**

- [ ] **Step 1: Bump provisional para obtener la lista canónica.** Editar `version.json` → `"version": "1.1.0"`. Run: `npm run test:design-system:static 2>&1 | grep "designSystemVersion must equal"`. La lista de archivos que salga es LA lista (no la del plan, si difieren).
- [ ] **Step 2: Sincronizar cada archivo listado:** `"designSystemVersion": "1.1.0"`. Solo ese campo; no tocar nada más de cada manifiesto.
- [ ] **Step 3: Verificar que solo queda el rojo esperado.** Run: `npm run test:design-system:static 2>&1 | tail -20`. Expected: los únicos fallos restantes son `activation: version.json must match HEAD exactly` (y su eco), que exigen el commit — cualquier otro fallo se corrige aquí.
- [ ] **Step 4: NO commitear todavía** — el commit va con el changelog en la task 7 para que la activación sea atómica (lección del commit `58b850e7`, que ya lo hizo así con 1.0.0).

### Task 7: Changelog, commit de activación y verificación final

**Files:**
- Modify: `docs/design-system/CHANGELOG.md` (retitular la entrada), + todo lo staged de la task 6

**Interfaces:**
- Consumes: task 6 (worktree con bump + manifiestos).
- Produces: condición de hecho del spec cumplida.

- [ ] **Step 1: Retitular la entrada.** `## Sin publicar (candidato a 1.1.0)` → `## 1.1.0 - <fecha de hoy>`. Retirar el blockquote de aviso («La versión viva sigue siendo 1.0.0…») y en su lugar una línea: `> Activada el <fecha> junto con la revisión una a una de las 39 excepciones que vencían en esta versión (pagadas N, re-vencidas M a 1.2.0).` — con los N y M reales del ledger.
- [ ] **Step 2: Commit de activación atómico.**
```bash
git add docs/design-system/version.json docs/design-system/*.json docs/design-system/manifests/inventory.json docs/design-system/CHANGELOG.md
git status --short   # revisar que SOLO entra lo de esta activación
git commit -m "docs(design-system): activate 1.1.0 — manifiestos sincronizados y changelog al dia"
```
- [ ] **Step 3: Verificación final (condición de hecho).** Run: `npm run test:design-system:static 2>&1 | tail -12`. Expected: **8/8 con `version.json` en `1.1.0`**. Si `activation-git` exige HEAD limpio y algo quedó fuera, corregir y amend ANTES de cualquier otra cosa.
- [ ] **Step 4: Ingest a la wiki** (respetando `docs/wiki-operacion.md`): línea en `memoria/log.md`; marcar `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md` con el desenlace (la trampa sigue vigente para futuros bumps: 1.2.0 cobrará las re-vencidas); actualizar el mapa `memoria/mapas/design-system.md` si cita la versión.
- [ ] **Step 5: Resumen final al usuario:** verificado, comandos, N pagadas / M re-vencidas, y el recordatorio de que 1.2.0 cobrará las re-vencidas cuando se retire el puente legacy.

---

## Self-review

- **Cobertura del spec:** §1 migración → Task 2; §2 acento → Task 3; §3 una-a-una → Task 4; §4 gates → Task 5; §5 publicación → Tasks 6–7; condición de hecho → Task 7 step 3; riesgos (campaña, pagos que rompen, manifiestos) → constraint global, Task 4 step 2, Task 6 step 1.
- **Placeholders:** los pasos de edición citan archivo, selector o línea y regla de decisión; el código de la Task 5 es literal (ya probado). Los N/M del changelog se toman del ledger, no son TBD: la Task 4 los produce.
- **Consistencia:** `expiresAtVersion` (Tasks 2–4), regex `^([1-9]\d*)\.\d+\.\d+$` (Task 5 = trampa), lista de manifiestos tomada del gate (Task 6 manda sobre la enumeración del plan).

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** docs/design-system/version.json:2 «1.1.0 stable»; CHANGELOG.md:13 «## 1.1.0 - 2026-08-07»; cero excepciones con expiresAtVersion 1.1.0

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
