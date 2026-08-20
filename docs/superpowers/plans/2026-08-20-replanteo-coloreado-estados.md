---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados.md
resumen: Que el chip sólido sea el portador fuerte de identidad de estado, las filas bajen a tintes sutiles, y el filete de severidad exista y se lea en los TRES…
---

# Replanteo de coloreado de estados (dirección B) — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el chip sólido sea el portador fuerte de identidad de estado, las filas bajen a tintes sutiles, y el filete de severidad exista y se lea en los TRES módulos (Intermedia, Programa General, Semanal), sin cambiar estados ni matices.

**Architecture:** Se añaden tres familias de tokens nuevas (`--ds-state-solid-*`, `--ds-state-solid-*-text`, `--ds-state-row-*`) sin tocar los hex de `--ds-state-tint-*` (los consume el PDC y la posture del frente anterior los protege). La primitiva `states-feedback.css` pasa a pintar chips con sólidos; los bloques `pi-state-*`/`pg-state-*`/`ps-state-*` pasan a tintes de fila; PG estrena la aplicación del atributo `data-aia-severity-rail` copiando el mecanismo de PI. La excepción crítica del chip (2026-08-11) se retira porque el chip deja de codificar nivel: la gravedad vive completa en el filete.

**Tech Stack:** CSS por capas (`@layer components/module`), tokens en `public/css/tokens.css`, contrato en `docs/design-system/state-semantics.json`, guards Node (`node --test`), sondas Playwright, Handsontable hot.js por módulo.

## Global Constraints

- **NO tocar los hex de `--ds-state-tint-*`** (tokens.css:333-344): los consume `--pdc-*-bg` (tokens.css:401-419) y `handsontable-module.css`.
- **Mismos estados, mismos matices**: el vocabulario de `state-semantics.json` y `ds-f1a-escala-estado.json` no se toca; ningún estado cambia de familia de color.
- **Dark, 1180×820** es el viewport y tema de validación (AGENTS.md §Routing UI).
- **Goldens solo con aprobación visual explícita de Felipe, pedida por su nombre** (posture heredada del frente ds-f1a-estados-severidad).
- **Ningún test se ablanda**: si un guard cambia, declara en su comentario qué mide ahora.
- **Sin dependencias nuevas.**
- Decisión de dirección: Felipe, 2026-08-20 (opción B del widget; paleta anclada al manual AIA con rojo custom por AA).

## Contrato de paleta (auditado WCAG AA + manual AIA, 2026-08-20)

| hue | chip sólido | texto chip | ratio | tinte fila | procedencia |
|---|---|---|---|---|---|
| red | `#e15a52` | `#3c0a06` | 4.67 | `#2b1b1a` | custom — el `#e53935` del manual falla AA (4.01) |
| orange | `#e87722` | `#3a2004` | 5.11 | `#2b211a` | manual: Construcción principal |
| amber | `#ffca28` | `#3a2c04` | 8.90 | `#2a2718` | manual: Advertencias alto |
| violet | `#9485d6` | `#26113f` | 5.33 | `#251f2d` | manual: Arquitectura medio |
| teal | `#5ec9bd` | `#062f29` | 7.30 | `#192825` | manual: Inmobiliario medio |
| blue | `#5f9fdd` | `#07223c` | 5.75 | `#1b2330` | desviación registrada: AIA no tiene azul |
| green | `#57b083` | `#06281a` | 5.99 | `#1b2a20` | interpolado rampa Corporativo |
| neutral | `#9aa8a0` | `#1c2420` | 6.41 | `#222724` | neutro del sistema |

Filete: urgent `#ff7a6e` / 6px · attention `#ffd23f` / 4px · **ready `#7ee2a8` / 3px** (decisión de Felipe 2026-08-20: marcador positivo SOLO en lo activamente listo — `liberated-control` en PI, `prog-lista-para-confirmar` y `cal-cumplida-control` en PS; PG no tiene estado con esa semántica. NO es un cuarto nivel de gravedad: es un marcador escaso dentro de Controlado, declarado por estado en `statePresentation` con `rail: 'ready'`, nunca derivado de nivel+matiz — `actividad-futura` es green+healthy y NO lo lleva). Texto sobre tinte de fila: `--ds-active-text-primary` (14–16:1).

---

### Task 1: Frente declarado y contención

**Files:**
- Create: `goals/replanteo-coloreado-estados/goal.md`

**Interfaces:**
- Produces: el slug `replanteo-coloreado-estados` que citan los commits y el cierre.

- [ ] **Step 1: Medir contención** (ya medida en sesión el 2026-08-20; re-ejecutar para dejar salida fresca)

```bash
git fetch origin && git log origin/main --since="2026-08-20" --oneline -- docs/design-system/ public/css/ public/js/modules/ tests/design-system/
cat /Users/felipebenitez/Developer/lps-aia/.claude/sesiones.md
```

Expected: ningún frente vivo sobre esos globs (registro del 19-ago está caduco; los frentes de bold-neumann cerraron).

- [ ] **Step 2: Escribir goal.md** con objetivo (este plan §Goal), condición de hecho («los tres módulos pintan chips sólidos, filas sutiles y filete homogéneo, medido computado-contra-computado a 1180×820 dark, suite `bash scripts/publicar.sh --solo-verificar` en RC=0, goldens regenerados con aprobación de Felipe»), posture (las Global Constraints), y referencia a este plan. Frontmatter wiki v2 como en `goals/semanal-fondo-por-matiz/goal.md`, con sección final «Archivos de este goal».

- [ ] **Step 3: Commit**

```bash
git add goals/replanteo-coloreado-estados/goal.md
git commit -m "docs(frente): abre replanteo-coloreado-estados con la contencion medida"
```

---

### Task 2: Tokens nuevos + contrato + guard de contraste (TDD)

**Files:**
- Create: `tests/design-system/state-solid-contract.test.mjs`
- Modify: `public/css/tokens.css` (junto al bloque `--ds-state-tint-*`, líneas ~333-344, y el bloque rail ~361-364)
- Modify: `docs/design-system/state-semantics.json` (catálogo `hues`, línea ~79)

**Interfaces:**
- Produces: tokens `--ds-state-solid-<hue>`, `--ds-state-solid-<hue>-text`, `--ds-state-row-<hue>` (8 hues: red, orange, amber, violet, teal, blue, green, neutral); `--ds-severity-rail-color-urgent: #ff7a6e`, `--ds-severity-rail-color-attention: #ffd23f`. En el contrato JSON, cada entrada de `hues` gana `solid`, `solidText`, `row`.

- [ ] **Step 1: Escribir el guard (falla primero).** El test lee el contrato y los tokens, y hace la matemática WCAG de verdad — es el guard que protege el margen del rojo (4.67):

```js
// tests/design-system/state-solid-contract.test.mjs
// MIDE: (1) que tokens.css declare exactamente los hex del catalogo `hues` del
// contrato para solid/solidText/row; (2) WCAG computado desde el contrato:
// solid vs solidText >= 4.5, solid vs row >= 3, rail vs cada row >= 3.
// Computado contra el contrato, no una declaracion contra si misma: si un hex
// cambia en un solo lado, esto se pone rojo.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const contrato = JSON.parse(readFileSync('docs/design-system/state-semantics.json', 'utf8'));
const tokensCss = readFileSync('public/css/tokens.css', 'utf8');
const HUES = ['red', 'orange', 'amber', 'violet', 'teal', 'blue', 'green', 'neutral'];
const RAIL = { urgent: '#ff7a6e', attention: '#ffd23f', ready: '#7ee2a8' };

const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const ratio = (a, b) => {
  const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
  return (x + 0.05) / (y + 0.05);
};
const tokenValue = (name) => {
  const m = tokensCss.match(new RegExp(`${name}\\s*:\\s*(#[0-9a-fA-F]{6})`));
  assert.ok(m, `token ${name} no declarado como hex literal en tokens.css`);
  return m[1].toLowerCase();
};
const hueEntry = (hue) => {
  const e = (contrato.hues || []).find((h) => h.id === hue || h.hue === hue || h.name === hue);
  assert.ok(e && e.solid && e.solidText && e.row, `contrato sin solid/solidText/row para ${hue}`);
  return e;
};

test('tokens.css y el contrato declaran los mismos solidos, textos y tintes de fila', () => {
  for (const hue of HUES) {
    const e = hueEntry(hue);
    assert.equal(tokenValue(`--ds-state-solid-${hue}`), e.solid.toLowerCase());
    assert.equal(tokenValue(`--ds-state-solid-${hue}-text`), e.solidText.toLowerCase());
    assert.equal(tokenValue(`--ds-state-row-${hue}`), e.row.toLowerCase());
  }
  assert.equal(tokenValue('--ds-severity-rail-color-urgent'), RAIL.urgent);
  assert.equal(tokenValue('--ds-severity-rail-color-attention'), RAIL.attention);
});

test('WCAG desde el contrato: chip>=4.5 con su texto, >=3 con su fila; filete >=3 en toda fila', () => {
  for (const hue of HUES) {
    const e = hueEntry(hue);
    assert.ok(ratio(e.solid, e.solidText) >= 4.5, `${hue}: chip vs texto ${ratio(e.solid, e.solidText).toFixed(2)} < 4.5`);
    assert.ok(ratio(e.solid, e.row) >= 3, `${hue}: chip vs fila ${ratio(e.solid, e.row).toFixed(2)} < 3`);
    for (const nivel of Object.keys(RAIL)) {
      assert.ok(ratio(RAIL[nivel], e.row) >= 3, `filete ${nivel} vs fila ${hue} < 3`);
    }
  }
});
```

- [ ] **Step 2: Correrlo y verlo fallar**

```bash
node --test tests/design-system/state-solid-contract.test.mjs
```

Expected: FAIL — «contrato sin solid/solidText/row para red».

- [ ] **Step 3: Añadir al contrato.** En `state-semantics.json`, a cada entrada del catálogo `hues` añadirle `solid`, `solidText`, `row` con los valores de la tabla del encabezado, y una nota `"brandAudit": "2026-08-20: anclado al manual AIA; rojo custom porque #e53935 falla AA (4.01); azul sin familia AIA, desviación registrada"`.

- [ ] **Step 4: Añadir los tokens.** En `tokens.css`, inmediatamente después del bloque `--ds-state-tint-*` (misma regla `:root`), como hex literales:

```css
    /* Replanteo 2026-08-20 (direccion B): el chip solido es el portador fuerte
       de identidad; la fila baja a un tinte sutil. Los --ds-state-tint-* de
       arriba NO se tocan: los consume el PDC. Contrato y auditoria (WCAG AA +
       manual AIA): docs/design-system/state-semantics.json (hues[].solid). */
    --ds-state-solid-red: #e15a52;
    --ds-state-solid-red-text: #3c0a06;
    --ds-state-solid-orange: #e87722;
    --ds-state-solid-orange-text: #3a2004;
    --ds-state-solid-amber: #ffca28;
    --ds-state-solid-amber-text: #3a2c04;
    --ds-state-solid-violet: #9485d6;
    --ds-state-solid-violet-text: #26113f;
    --ds-state-solid-teal: #5ec9bd;
    --ds-state-solid-teal-text: #062f29;
    --ds-state-solid-blue: #5f9fdd;
    --ds-state-solid-blue-text: #07223c;
    --ds-state-solid-green: #57b083;
    --ds-state-solid-green-text: #06281a;
    --ds-state-solid-neutral: #9aa8a0;
    --ds-state-solid-neutral-text: #1c2420;
    --ds-state-row-red: #2b1b1a;
    --ds-state-row-orange: #2b211a;
    --ds-state-row-amber: #2a2718;
    --ds-state-row-violet: #251f2d;
    --ds-state-row-teal: #192825;
    --ds-state-row-blue: #1b2330;
    --ds-state-row-green: #1b2a20;
    --ds-state-row-neutral: #222724;
```

Y en el bloque del rail (líneas ~363-364) sustituir los alias por hex literales, dejando dicho por qué:

```css
    /* Avivados el 2026-08-20: los alias a --ds-color-state-*-text eran pastel
       y el filete se perdia en obra y proyector. El guard state-solid-contract
       exige >=3:1 contra todo tinte de fila. `ready` es el marcador positivo
       escaso (decision de Felipe 2026-08-20): solo estados declarados con
       rail:'ready', nunca todo Controlado. */
    --ds-severity-rail-color-urgent: #ff7a6e;
    --ds-severity-rail-color-attention: #ffd23f;
    --ds-severity-rail-width-ready: 3px;
    --ds-severity-rail-color-ready: #7ee2a8;
```

- [ ] **Step 5: Verificar verde**

```bash
node --test tests/design-system/state-solid-contract.test.mjs
```

Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add tests/design-system/state-solid-contract.test.mjs public/css/tokens.css docs/design-system/state-semantics.json
git commit -m "feat(ds): tokens del replanteo B — chip solido, tinte de fila y filete avivado, con guard WCAG contra el contrato"
```

---

### Task 3: El chip pasa a sólido en la primitiva (y se retira la excepción crítica)

**Files:**
- Modify: `public/css/design-system/components/states-feedback.css` (bloque de matices, líneas ~108-147, y la excepción crítica ~160-166)
- Modify: `tests/design-system/state-tint-ladder.test.mjs` (las expectativas de traducción del chip)

**Interfaces:**
- Consumes: tokens `--ds-state-solid-*` de Task 2.
- Produces: todo elemento `[data-aia-hue][data-aia-severity]` pinta fondo sólido y texto oscuro de su familia. Sin excepciones por nivel.

- [ ] **Step 1: Actualizar el guard primero.** En `state-tint-ladder.test.mjs`, cambiar las expectativas del chip: cada `data-aia-hue="X"` debe traducir a `var(--ds-state-solid-X)` + `var(--ds-state-solid-X-text)`, **incluido `high`+`now`**. Añadir al comentario de cabecera: «MIDE desde 2026-08-20: la traducción matiz→sólido del chip. La excepción crítica del 2026-08-11 se retiró: el chip ya no codifica nivel (eso descargaba dos ejes en un canal); la gravedad vive completa en el filete, que ahora existe en los tres módulos». Correr y ver FAIL.

```bash
node --test tests/design-system/state-tint-ladder.test.mjs
```

- [ ] **Step 2: Reescribir las 8 reglas de matiz.** Mismo selector y peso (0,2,0), nuevo destino. Patrón (repetir para las 8 hues):

```css
  [data-aia-hue="red"][data-aia-severity] {
    background: var(--ds-state-solid-red);
    color: var(--ds-state-solid-red-text);
  }
```

- [ ] **Step 3: Retirar la excepción crítica** (`[data-aia-hue][data-aia-severity="high"][data-aia-urgency="now"]`), sustituyendo la regla por el comentario que explica el retiro (la razón de 2026-08-11 era que el matiz tapaba el nivel — con el nivel viviendo en el filete y no en el chip, la razón desapareció; decisión implícita en la dirección B de Felipe, 2026-08-20). Esto resuelve además la D-2 de `DECISIONES_PENDIENTES`.

- [ ] **Step 3bis: El marcador `ready` en la primitiva del filete.** En `severity-rail.css`, antes del bloque healthy/neutral, con su porqué y su variante RTL:

```css
  /* Marcador positivo, decision de Felipe 2026-08-20: filete verde fino SOLO
     en lo activamente listo (rail:'ready' declarado por estado, nunca derivado
     de nivel+matiz). No es un cuarto nivel de gravedad: Controlado sigue sin
     barra por defecto y la escasez del canal se conserva. */
  [data-aia-severity-rail="ready"] {
    box-shadow: inset var(--ds-severity-rail-width-ready) 0 0 0 var(--ds-severity-rail-color-ready);
  }

  [dir="rtl"] [data-aia-severity-rail="ready"] {
    box-shadow: inset calc(-1 * var(--ds-severity-rail-width-ready)) 0 0 0 var(--ds-severity-rail-color-ready);
  }
```

Y en los hot.js de PI y PS: `statePresentation` gana `rail: 'ready'` en `liberated-control` (PI), `prog-lista-para-confirmar` y `cal-cumplida-control` (PS); el aplicador del atributo pasa `presentation.rail || presentation.level`.

- [ ] **Step 4: Verificar los guards del área**

```bash
node --test tests/design-system/state-tint-ladder.test.mjs tests/design-system/state-solid-contract.test.mjs
```

Expected: PASS.

- [ ] **Step 5: Revisar que ningún módulo pise el fondo del chip**

```bash
grep -n "ops-state-chip" public/css/programacion-semanal.css public/css/programacion-intermedia.css public/css/buttons.css | grep -i "background\|color"
```

Expected: solo geometría/tipografía. Si algo pinta fondo, retirarlo aquí declarándolo en el commit.

- [ ] **Step 6: Commit**

```bash
git add public/css/design-system/components/states-feedback.css tests/design-system/state-tint-ladder.test.mjs
git commit -m "feat(ds): el chip pinta solido con texto de su familia — la excepcion critica se retira, la gravedad ya vive en el filete"
```

---

### Task 4: Las filas bajan a tinte sutil en los tres módulos

**Files:**
- Modify: `public/css/styles.css` — bloque `pi-state-*` (~3702-3760, 8 reglas) y bloque `pg-state-*` (~3365-3405 y swatches de leyenda)
- Modify: `public/css/programacion-semanal.css` — reglas `ps-state-*` de identidad (localizarlas con `grep -n "ps-state-" public/css/programacion-semanal.css`; las introdujo el commit `34c82488`)

**Interfaces:**
- Consumes: `--ds-state-row-*` y `--ds-state-solid-*` de Task 2.
- Produces: toda regla de fondo de fila de estado apunta a `--ds-state-row-<hue>`; los swatches de leyenda de PG apuntan a `--ds-state-solid-<hue>` (la leyenda muestra lo que el ojo busca: el chip).

- [ ] **Step 1: PI.** En cada una de las 8 reglas `td.pi-state-<estado>` sustituir `var(--ds-state-tint-<hue>)` por `var(--ds-state-row-<hue>)` conservando hue por estado (red, orange, violet, amber, teal, verde en liberated, neutral en 4-6-weeks y neutral). `color` y `border-color` no cambian.
- [ ] **Step 2: PG.** Igual en las reglas `td.pg-state-*` (7 estados). En `.pg-legend-modal-swatch.pg-state-*` cambiar el fondo a `var(--ds-state-solid-<hue>)`.
- [ ] **Step 3: PS.** Igual en las reglas `ps-state-*` de las dos fases. **El sistema de cubos `ps-alert-*` y sus `--ps-*-chip/bg` (líneas 40-53) NO se tocan**: son otro eje (alertas), decidido en su propio frente.
- [ ] **Step 4: Verificar que el tinte viejo no quede en filas.**

```bash
grep -n "state-tint" public/css/styles.css public/css/programacion-semanal.css | grep -v "pdc\|ps-alert\|--ps-"
```

Expected: cero filas de estado apuntando a `--ds-state-tint-*` (los usos de PDC y cubos quedan).

- [ ] **Step 5: Suite estática**

```bash
npm run test:design-system:static
```

Expected: RC=0. Si un guard existente se pone rojo por el cambio de destino, actualizarlo declarando qué mide ahora — nunca tolerarlo con baseline.

- [ ] **Step 6: Commit**

```bash
git add public/css/styles.css public/css/programacion-semanal.css
git commit -m "feat(ui): las filas bajan a tinte sutil en los tres modulos — la identidad fuerte ya la lleva el chip"
```

---

### Task 5: Programa General estrena el filete (TDD)

**Files:**
- Modify: `public/js/modules/programa_general/hot.js` (statePresentation ~812; aplicación de rowClass ~899-935)
- Modify o Create: guard en `tests/design-system/` que exige el rail en PG (si `ds-f1a-*.test.mjs` ya recorre superficies, extenderlo; si no, crear `tests/design-system/pg-severity-rail.test.mjs` con el patrón del paso 1)

**Interfaces:**
- Consumes: `statePresentation[estado].level` (ya existe: atrasada=urgent, debe-iniciar=attention, resto healthy/neutral) y la primitiva `severity-rail.css` (atributo `data-aia-severity-rail` en `<tr>` y cada `<td>`, igual que PI en `applyPIRowSeverityAttr`, `programacion_intermedia/hot.js:1046-1057`).
- Produces: `applyPGRowSeverityAttr(element, level)` y su invocación en el mismo punto donde PG replica `rowClass` en tr y celdas.

- [ ] **Step 1: Guard primero.** El test estático que exige que PG aplique el atributo (mismo estilo de los guards ds-f1a — leer el fuente y afirmar el mecanismo, declarándolo en el comentario):

```js
// tests/design-system/pg-severity-rail.test.mjs
// MIDE: que programa_general/hot.js aplique data-aia-severity-rail en tr y td,
// igual que Intermedia. Nacio del replanteo 2026-08-20: PG declaraba niveles en
// statePresentation pero ninguna fila los dibujaba — el filete era de dos
// modulos y el contrato dice tres.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const src = readFileSync('public/js/modules/programa_general/hot.js', 'utf8');

test('PG define y aplica el atributo del filete', () => {
  assert.match(src, /function applyPGRowSeverityAttr\s*\(/);
  assert.match(src, /setAttribute\('data-aia-severity-rail'/);
  assert.match(src, /removeAttribute\('data-aia-severity-rail'\)/);
});
```

Correr: `node --test tests/design-system/pg-severity-rail.test.mjs` → FAIL.

- [ ] **Step 2: Implementar.** Junto a `statePresentation`, la traducción nivel→atributo y el aplicador (calcado de PI):

```js
  // El fondo (pg-state-*) dice QUE estado es; este atributo dice CUAN grave, y
  // lo traduce la primitiva compartida severity-rail.css. Igual que Intermedia:
  // el atributo va en el <tr> y en cada <td> porque la primitiva no tiene
  // selector de descendiente. Niveles: statePresentation (atrasada=urgent,
  // debe-iniciar=attention; healthy/neutral no dibujan).
  function applyPGRowSeverityAttr(element, level) {
    if (!element) {
      return;
    }
    if (level) {
      element.setAttribute('data-aia-severity-rail', level);
    } else {
      element.removeAttribute('data-aia-severity-rail');
    }
  }
```

Invocarla donde PG aplica `rowClass` al `<tr>` y a las celdas (el punto que puebla `rowClassMap`/`pg-state-*`), pasando `statePresentation[stateKey] ? statePresentation[stateKey].level : null`.

- [ ] **Step 3: Verificar guard y sonda**

```bash
node --test tests/design-system/pg-severity-rail.test.mjs
node goals/ds-f1a-estados-severidad/evidence/sonda-pg-postfix.mjs
```

Expected: guard PASS; en la sonda, `railNivel` = `urgent` en atrasada y `attention` en debe-iniciar, `none`/null en el resto.

- [ ] **Step 4: Commit**

```bash
git add public/js/modules/programa_general/hot.js tests/design-system/pg-severity-rail.test.mjs
git commit -m "feat(pg): Programa General estrena el filete — atrasada y debe-iniciar lo dibujan, el contrato ya cubre los tres modulos"
```

---

### Task 6: Verificación en pantalla, aprobación visual y goldens

**Files:**
- Create: `goals/replanteo-coloreado-estados/evidence/` (sondas adaptadas de `goals/ds-f1a-estados-severidad/evidence/sonda-postfix.mjs` y `sonda-pg-postfix.mjs`, más la de Semanal `goals/semanal-fondo-por-matiz/evidence/sonda-ps.mjs`)
- Modify (solo tras aprobación): `tests/browser/__screenshots__/**` de los specs afectados

**Interfaces:**
- Consumes: todo lo anterior servido por el contenedor apuntando al worktree (`LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, regla #4 de coordinación; devolverlo a la raíz al terminar).

- [ ] **Step 1:** Correr las tres sondas (PI, PG, PS) a 1180×820 dark vía `/dev/entrar`; guardar mediciones y capturas en `evidence/`. Verificar consola limpia.
- [ ] **Step 2:** Comparar computado contra contrato: fondos de fila = `row`, chips = `solid`, filete presente solo en urgent/attention. Cero pares idénticos de chip dentro de un módulo.
- [ ] **Step 3 (GATE, bloqueante):** Enviar las capturas a **Felipe** y pedirle **por su nombre** la aprobación visual explícita. **Sin su sí, no se regenera ningún golden y el frente no avanza.** Si a su ojo satura o falla algo, se ajusta y se vuelve a este paso.
- [ ] **Step 4 (solo tras el sí):** regenerar los goldens de los specs visuales afectados (PI/PG/PS) con `--update-snapshots` acotado a esos specs, y correr la suite runtime.

```bash
npm run test:design-system:runtime
```

Expected: RC=0.

- [ ] **Step 5: Commit** (goldens + evidencia versionable; los .png de goals/ los ignora git)

```bash
git add tests/browser/__screenshots__ goals/replanteo-coloreado-estados/evidence/*.mjs goals/replanteo-coloreado-estados/evidence/*.json
git commit -m "test(visual): goldens del replanteo B regenerados con aprobacion visual de Felipe del <fecha>"
```

---

### Task 7: Documentación, revisión y cierre por el gate

**Files:**
- Modify: `DESIGN.md` (los tokens nuevos y cuándo usar solid vs row), `CHANGELOG.md`, `TASKS.md`, `DECISIONES_PENDIENTES.md` (D-2 resuelta por retiro de la excepción), `goals/replanteo-coloreado-estados/goal.md` (cierre), `memoria/log.md` (ingest)

- [ ] **Step 1:** Actualizar los seis documentos. En DESIGN.md: «chip = identidad fuerte (`--ds-state-solid-*`), fila = identidad sutil (`--ds-state-row-*`), filete = gravedad; `--ds-state-tint-*` queda para PDC y no se usa en filas de estado nuevas».
- [ ] **Step 2:** Revisión pre-cierre: `requesting-code-review` sobre el diff del frente (revisor + impeccable audit por pantalla ya corrido en Task 6).
- [ ] **Step 3:** Gate estándar completo:

```bash
bash scripts/publicar.sh --solo-verificar
```

Luego commitear lo suelto, `git fetch origin`, integrar si hay divergencia, **re-verificar**, y publicar:

```bash
bash scripts/publicar.sh
```

- [ ] **Step 4:** Confirmar `git rev-parse origin/main` == sha verificado; anotar cierre en goal.md y TASKS.md; devolver el contenedor a la raíz (`docker compose up -d app` sin `LPS_CODE_ROOT`).

---

## Self-review (hecho al escribir)

- Cobertura: dirección B (Tasks 2-4), homogeneización del filete (Task 5), contraste auditado como guard permanente (Task 2), goldens con aprobación (Task 6), D-2 resuelta (Task 3), docs y gate (Task 7). El cubo de alertas de Semanal queda explícitamente fuera.
- Tipos/nombres consistentes: `--ds-state-solid-<hue>[-text]`, `--ds-state-row-<hue>`, `applyPGRowSeverityAttr` — usados igual en todas las tareas.
- Sin placeholders: cada paso lleva código o comando ejecutable.

---

## Revisión de la Task 6 (2026-08-20, rondas 3-4 del gate visual)

Felipe rechazó dos rondas y sus críticas rediseñaron Semanal y parieron una primitiva nueva:

1. **Ronda 3** — botones de acciones al contrato secondary (superficie elevada + borde activo,
   acción en el color del icono) y el widget de Estado Operativo aquietado.
2. **Ronda 4 (decisión de Felipe)** — el Estado Operativo se sustituye por **chip sencillo +
   tooltip**, replicado en los TRES módulos: primitiva nueva
   `public/css/design-system/components/state-tooltip.css` +
   `public/js/design-system/state-tooltip.js` (WCAG 1.4.13: hover Y foco, Escape lo descarta,
   volteo arriba/abajo). El chip lleva sufijo `· N` solo con pendientes; en PS/PI el clic conserva
   el drawer (cubre móvil/tablet); en PG el chip es focuseable por teclado. Deroga para la celda
   densa de PS la paridad «uniforme 900» del 2026-08-03 (600/0.72 una línea con elipsis).
3. **Lección medida del ciclo**: dos lecturas visuales del asistente sobre miniaturas de 1180
   alucinaron tarjetas claras (56/60 píxeles muestreados eran oscuros) — la evidencia de detalle
   va a 2x; y las sondas esperan el tema estable antes de capturar.
