# F1 · Seis modales huérfanos a dark — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Homologar al tema dark los seis modales que el tramo 5c (`c4fe9a4`) dejó combinando superficie oscura con tinta oscura, arreglando cada uno en el archivo que realmente gana la cascada.

**Architecture:** No se inventa ningún color. Los seis fallos son el mismo defecto repetido —piel anclada al eje de tokens **fijo** (`--ds-color-*`) bajo un shell que ya migró al eje **activo** (`--ds-active-*`)— y el arreglo es una conmutación de eje. Donde la piel vive en utilidades de Bootstrap o en `style=` del markup, se retira del markup (no hay forma de ganarles desde CSS) y se sustituye por clases semánticas del módulo en un archivo CSS nuevo declarado en `@layer module`.

**Tech Stack:** CSS con `@layer` y custom properties; PHP (vistas planas); Playwright + Chrome DevTools Protocol para la medición; Docker Compose (`app` en `http://localhost:8081`).

**Spec:** `goals/dark-mode-todos-los-modulos/specs/F1-modales-huerfanos.md`

## Global Constraints

- **Alcance visual: desktop ≥1180px y dark ÚNICAMENTE.** Viewport canónico `1180x820`. Prohibido trabajar, probar o generar evidencia para mobile, tablet o el tema `linen`.
- **PHP, Composer y PHPStan siempre dentro del contenedor `app`.** Nunca un PHP del host.
- **No se regeneran baselines ni goldens.** `docs/design-system/audit-baseline.json` está protegido por hash y no se toca.
- **Presupuesto de `public/css/programacion-semanal.css`: cero absoluto** (`hardcoded-hex`, `hardcoded-color-function`, `inline-style`, `embedded-style-block`, `forbidden-font-roboto`, `hardcoded-radius`). Solo tokens y `color-mix()` sobre `var()`.
- **Presupuesto de `public/css/pdc.css`:** `hardcoded-hex 0`, `inline-style 0`, `hardcoded-radius 0`, `embedded-style-block 0`. `hardcoded-color-function` sí está permitido, pero no se añaden nuevos.
- **El worktree tiene cambios ajenos de otras sesiones** (`src/View/Components/DesignSystemComponent.php`, `tests/browser/design-system-lab-sidebar.mjs`, `tests/browser/design-system-lab.performance.mjs`). **Staging selectivo siempre; nunca `git add -A`, nunca `git add .`, nunca `git checkout --`.**
- **`tests/browser/` está en `.gitignore` con allowlist.** Un archivo nuevo ahí no se commitea si no se añade su línea `!` en el mismo commit. `tests/browser/support/*.mjs` ya está allowlistado.
- Credenciales de prueba: usuario `test.A`, contraseña `aia2026`, proyecto **Da Porto**.

### Tabla de conmutación de eje (se aplica en todas las tareas)

| Fijo (claro) | → Activo |
|---|---|
| `--ds-color-bg-parchment`, `--ds-color-surface` | `--ds-active-surface` |
| `--ds-color-surface-tint`, `--ds-color-bg-page` (pies, `thead`) | `--ds-active-surface-raised` |
| `--ds-color-surface-subtle`, `--ds-color-surface-hover` | `--ds-active-surface-glass` |
| `--ds-color-text-primary` | `--ds-active-text-primary` |
| `--ds-color-text-secondary`, `--ds-color-text-tertiary` | `--ds-active-text-secondary` |
| `--ds-color-border-default` / `-subtle` / `-strong` | `--ds-active-border` |
| `--ds-color-border-focus` | `--ds-active-focus-ring` |
| `--ds-color-brand-primary` **como tinta sobre superficie** | `--ds-active-action-primary` |

**Nunca se conmutan** `--ds-color-text-inverse` ni `--ds-color-brand-primary*` cuando pintan **sobre el degradado verde de cabecera** o forman un par cerrado (fondo claro + tinta oscura declarados en la misma regla, p. ej. una píldora sobre la cabecera): ese verde es idéntico en los dos temas y el tramo 5c lo dejó intacto en los 48 modales del shell.

---

## Task 1: Botones outline de los pies de modal

Es el más urgente (el `Cancelar` de tres diálogos de borrado queda hoy casi invisible) y el de mayor alcance: la regla cubre los pies de los **58** modales `.aia-modal`. Va primero para que el resto de tareas ya lo tengan resuelto.

**Files:**
- Create: `tests/browser/support/contrast.mjs`
- Create: `tests/browser/modales-dark-homologacion.mjs`
- Modify: `.gitignore` (allowlist del test nuevo)
- Modify: `public/css/styles.css:1630-1641` y `:1655-1661`

**Interfaces:**
- Produces: `tests/browser/support/contrast.mjs` exporta cinco funciones y una constante. Las tareas 2-5 las consumen tal cual.
  - `VIEWPORT: { width: 1180, height: 820 }`
  - `installContrastProbe(page): Promise<void>` — inyecta el `initScript` de medición. Basta llamarla **una vez** tras el login: `addInitScript` persiste en las navegaciones posteriores.
  - `openModal(page, modalId: string): Promise<void>` — abre por jQuery y espera alto > 0.
  - `closeModal(page, modalId: string): Promise<void>`
  - `measure(page, selector: string): Promise<{ ratio: number, fg: string, bg: string } | null>` — `null` si el selector no existe.
  - `matchedRuleFor(page, selector: string, property: string): Promise<{ selector, layers, file, line, value, important } | null>` — quién gana de verdad la cascada, vía CDP.

**Credenciales:** **no** se declara una constante propia. `tests/browser/fixtures/projects.mjs` ya exporta `CREDENTIALS` (`test.A` / `aia2026`) con override por `E2E_APP_USERNAME`/`E2E_APP_PASSWORD`; hardcodearlas rompería ese override. La firma real del helper es `loginAndSelectProject(page, project, credentials = CREDENTIALS)` — **el proyecto va segundo**, así que se llama `await loginAndSelectProject(page, project)`.

- [ ] **Step 1: Escribir el helper de medición**

Crear `tests/browser/support/contrast.mjs`:

```js
// Sonda de contraste para modales. Compone alpha sobre los ancestros hasta la
// primera capa opaca y convierte cualquier notacion de color (color-mix, oklch,
// color(srgb ...)) via canvas: un parser de rgb() las descartaria en silencio y
// devolveria ceros falsos. Mismo metodo que la sonda del tramo 5c.

export const VIEWPORT = { width: 1180, height: 820 };

const PROBE = () => {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });

  const parseColor = (value) => {
    if (!value || value === 'transparent' || value === 'none') return [0, 0, 0, 0];
    ctx.globalCompositeOperation = 'copy';
    ctx.fillStyle = '#000000';
    ctx.fillStyle = value;
    ctx.fillRect(0, 0, 1, 1);
    const d = ctx.getImageData(0, 0, 1, 1).data;
    return [d[0], d[1], d[2], d[3] / 255];
  };

  const over = (fg, bg) => {
    const a = fg[3] + bg[3] * (1 - fg[3]);
    if (a === 0) return [0, 0, 0, 0];
    const mix = (i) => (fg[i] * fg[3] + bg[i] * bg[3] * (1 - fg[3])) / a;
    return [mix(0), mix(1), mix(2), a];
  };

  const effectiveBackground = (el) => {
    let acc = [0, 0, 0, 0];
    for (let node = el; node; node = node.parentElement) {
      acc = over(acc, parseColor(getComputedStyle(node).backgroundColor));
      if (acc[3] >= 0.999) return acc;
    }
    return over(acc, [255, 255, 255, 1]);
  };

  const luminance = ([r, g, b]) => {
    const f = (c) => {
      const s = c / 255;
      return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
  };

  const fmt = (c) => `rgb(${c.slice(0, 3).map((v) => Math.round(v)).join(', ')})`;

  window.__aiaContrast = (selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;
    const bg = effectiveBackground(el);
    const fg = over(parseColor(getComputedStyle(el).color), bg);
    const l1 = luminance(fg);
    const l2 = luminance(bg);
    const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    return { ratio: Math.round(ratio * 100) / 100, fg: fmt(fg), bg: fmt(bg) };
  };
};

export async function installContrastProbe(page) {
  await page.addInitScript(PROBE);
  await page.evaluate(PROBE);
}

export async function openModal(page, modalId) {
  await page.evaluate((id) => window.jQuery(`#${id}`).modal('show'), modalId);
  await page.waitForFunction((id) => {
    const el = document.getElementById(id);
    if (!el) return false;
    return el.getBoundingClientRect().height > 0 && getComputedStyle(el).display !== 'none';
  }, modalId);
  // Las transiciones del shell corren a 0.15s; 450ms las cubre con margen.
  await page.waitForTimeout(450);
}

export async function closeModal(page, modalId) {
  await page.evaluate((id) => window.jQuery(`#${id}`).modal('hide'), modalId);
  await page.waitForTimeout(350);
}

export async function measure(page, selector) {
  return page.evaluate((sel) => window.__aiaContrast(sel), selector);
}

// Quien gana de verdad la cascada. Sin esto, un cambio puede "funcionar" por una
// regla heredada mientras la que se escribio queda inerte — que es exactamente lo
// que le paso al shell dentro de #modalContrato.
export async function matchedRuleFor(page, selector, property) {
  const cdp = await page.context().newCDPSession(page);
  const sheets = new Map();
  // El listener va ANTES de CSS.enable: enable reemite styleSheetAdded de todas
  // las hojas ya cargadas, y es la unica forma de mapear styleSheetId -> archivo.
  cdp.on('CSS.styleSheetAdded', ({ header }) => sheets.set(header.styleSheetId, header.sourceURL));
  await cdp.send('DOM.enable');
  await cdp.send('CSS.enable');

  const { root } = await cdp.send('DOM.getDocument', { depth: -1 });
  const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
  if (!nodeId) {
    await cdp.detach();
    return null;
  }

  const matched = await cdp.send('CSS.getMatchedStylesForNode', { nodeId });
  const hits = [];
  for (const entry of matched.matchedCSSRules ?? []) {
    const rule = entry.rule;
    const decls = (rule.style?.cssProperties ?? []).filter(
      (p) => p.name === property && !p.disabled && p.text,
    );
    if (decls.length === 0) continue;
    const last = decls[decls.length - 1];
    hits.push({
      selector: rule.selectorList?.text ?? '',
      layers: (rule.layers ?? []).map((l) => l.text).join('.') || '(sin capa)',
      file: sheets.get(rule.styleSheetId) ?? '(inline)',
      line: (last.range?.startLine ?? rule.style?.range?.startLine ?? -1) + 1,
      value: last.value,
      important: last.important === true,
    });
  }
  await cdp.detach();
  // matchedCSSRules llega en orden ascendente de prioridad: el ultimo gana.
  return hits.length > 0 ? hits[hits.length - 1] : null;
}
```

- [ ] **Step 2: Escribir el test que falla**

Crear `tests/browser/modales-dark-homologacion.mjs`:

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';
import { VIEWPORT, installContrastProbe, openModal, closeModal, measure }
  from './support/contrast.mjs';

// Guarda de regresion de las siete regresiones medidas en
// .superpowers/sdd/task-5c-report.md 5.7: el shell .aia-modal paso a oscuro y
// la piel concreta de seis modales seguia en el eje de tokens fijo (claro).
// Ninguna suite existente lo habria detectado: design-system-body-canvas-dark
// mide el `body` de 10 rutas, no el interior de un modal.

const project = PROJECTS.find(({ key }) => key === 'construction');
const AA = 4.5;

test.use({ viewport: VIEWPORT });

test('modales de /listado-actividades, /pdc y /control-cambios: boton Cancelar del pie legible', async ({ page }) => {
  await loginAndSelectProject(page, project);
  // Una sola vez: addInitScript persiste en las navegaciones siguientes.
  await installContrastProbe(page);

  for (const [route, modalId, selector] of [
    ['/listado-actividades', 'modalEliminar', '#modalEliminar .modal-footer .btn.btn-default'],
    ['/pdc', 'modalEliminar', '#modalEliminar .modal-footer .btn.btn-default'],
    ['/control-cambios', 'modalEliminar', '#btn_cancelar_eliminar'],
  ]) {
    await page.goto(route);
    await openModal(page, modalId);
    const result = await measure(page, selector);
    expect(result, `${route} ${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${route} ${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
    await closeModal(page, modalId);
  }
});
```

- [ ] **Step 3: Añadir el test a la allowlist de `.gitignore`**

Insertar después de la línea `!tests/browser/pdc-chips-dark.mjs`:

```
!tests/browser/modales-dark-homologacion.mjs
```

Verificar que se ve para git:

```bash
git check-ignore -v tests/browser/modales-dark-homologacion.mjs; echo "exit=$?"
```

Expected: `exit=1` (no ignorado). Si sale `exit=0` con una línea de regla, la allowlist no está bien puesta.

- [ ] **Step 4: Ejecutar el test y verificar que FALLA**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
```

Expected: FAIL. Los tres ratios entre 1,14 y 1,63 (verde corporativo oscuro sobre pie oscuro). Anotar los tres valores: son la línea base del «antes».

- [ ] **Step 5: Conmutar el bloque de botones**

En `public/css/styles.css`, el bloque que empieza en `:1630` y cuyas declaraciones están en `:1638-1640`:

```css
.aia-modal .btn.aia-btn-secondary,
.aia-modal .btn.aia-btn-danger,
.aia-modal .modal-footer .btn.btn-secondary,
.aia-modal .modal-footer .btn.btn-outline-secondary,
.aia-modal .modal-footer .btn.btn-danger,
.aia-modal .modal-footer .btn.btn-default,
.aia-modal__actions .btn.btn-danger,
.aia-modal__actions .btn.btn-default {
  background: transparent;
  border: 1px solid var(--ds-active-border);
  color: var(--ds-active-text-primary);
}
```

Y las declaraciones del bloque `:hover`/`:focus` en `:1659-1660`:

```css
  background: var(--ds-active-surface-glass);
  color: var(--ds-active-text-primary);
}
```

Es la receta canónica que ya existe en `public/css/design-system/core.css:113` para `.aia-btn--secondary`, con `background: transparent` en vez de `surface-raised` para conservar el aspecto outline sobre un pie que ya es `surface-raised`.

**No** se le da tratamiento propio a `.btn-danger`: hoy pinta un botón destructivo de verde corporativo, que es un defecto semántico preexistente y arreglarlo es rediseño, no homologación a dark.

- [ ] **Step 6: Ejecutar el test y verificar que PASA**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
```

Expected: PASS, los tres ratios ≥ 4,5.

- [ ] **Step 7: Confirmar por CDP que la regla que gana es la escrita**

Añadir al final de `tests/browser/modales-dark-homologacion.mjs`:

```js
test('la regla ganadora del color de los botones del pie es la de styles.css', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await installContrastProbe(page);
  await page.goto('/listado-actividades');
  await openModal(page, 'modalEliminar');

  const rule = await matchedRuleFor(page, '#modalEliminar .modal-footer .btn.btn-default', 'color');
  expect(rule, 'ninguna regla declara color en ese boton').not.toBeNull();
  expect(rule.file, `gana ${rule.file}:${rule.line} (${rule.selector})`).toContain('/css/styles.css');
  expect(rule.value).toBe('var(--ds-active-text-primary)');
  expect(rule.layers, 'la regla debe seguir en la capa module').toContain('module');
});
```

Añadir `matchedRuleFor` al `import` de `./support/contrast.mjs`.

Ejecutar:

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "regla ganadora"
```

Expected: PASS. El mensaje de fallo, si lo hubiera, imprime archivo, línea y selector de quien gane en su lugar. Registrar la salida en el reporte de la tarea.

- [ ] **Step 8: Verificar que biome y el audit no empeoran**

```bash
npx biome check public/css/styles.css
```

Expected: los mismos **4 errores** y ~523 warnings que antes del cambio (el rojo de la `}` huérfana está aplazado al tramo 5j).

```bash
node scripts/design-system-audit.mjs
```

Expected: el total **baja** (se retira un `hardcoded-color-function` y un `raw-token-in-module`). Único fallo admisible: `programa-general hardcoded-hex: 1 > 0`, preexistente y ajeno.

- [ ] **Step 9: Commit**

```bash
git add public/css/styles.css tests/browser/support/contrast.mjs tests/browser/modales-dark-homologacion.mjs .gitignore
git commit -m "fix(design-system): que los botones outline del pie de modal se lean sobre oscuro"
```

---

## Task 2: `/control-cambios` `#modalordenDeCambio`

16 etiquetas del formulario a 1,00:1. Bootstrap fija `bg-light`/`bg-white`/bordes con `!important` desde `@layer vendor`; como el orden de capas se **invierte** para `!important`, ninguna `@layer` posterior puede tocarlos. No hay arreglo desde CSS: se retiran del markup.

**Files:**
- Create: `public/css/control-cambios.css`
- Modify: `views/control-cambios/controlCambios.view.php:143-572` (markup) y `:6` (link)
- Modify: `tests/browser/modales-dark-homologacion.mjs`

**Interfaces:**
- Consumes: `installContrastProbe`, `openModal`, `closeModal`, `measure`, `LOGIN`, `VIEWPORT` de `tests/browser/support/contrast.mjs` (Task 1).
- Produces: clases `.cc-field-row`, `.cc-field-label`, `.cc-field-value`, `.cc-field-counter` en `public/css/control-cambios.css`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/browser/modales-dark-homologacion.mjs`:

```js
test('/control-cambios #modalordenDeCambio: etiquetas del formulario legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/control-cambios');
  await installContrastProbe(page);
  await openModal(page, 'modalordenDeCambio');

  for (const selector of [
    '#modalordenDeCambio label[for="inputJustificacion"]',
    '#modalordenDeCambio label[for="inputDescripcion"]',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // Ninguna utilidad de vendor con color puede sobrevivir dentro del modal.
  const leftovers = await page.evaluate(() => {
    const modal = document.getElementById('modalordenDeCambio');
    const banned = ['bg-light', 'bg-white', 'border', 'border-right', 'border-top', 'border-bottom', 'text-muted'];
    return [...modal.querySelectorAll('*')]
      .flatMap((el) => banned.filter((c) => el.classList.contains(c)))
      .length;
  });
  expect(leftovers, 'quedan utilidades bg-*/border-*/text-muted de Bootstrap').toBe(0);
});
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "control-cambios"
```

Expected: FAIL. Ratios en 1,00 y `leftovers` en 85.

- [ ] **Step 3: Crear la hoja del módulo**

Crear `public/css/control-cambios.css`:

```css
/* Piel del modal de orden de cambio. Va en @layer module para sentarse junto a
   styles.css: entrar sin capa (como pdc.css) le ganaria a todo el design system,
   que es justo el defecto que este tramo corrige. */
@layer module {
  .cc-field-row {
    border: 1px solid var(--ds-active-border);
  }

  .cc-field-label {
    background: var(--ds-active-surface-raised);
    color: var(--ds-active-text-primary);
    border-right: 1px solid var(--ds-active-border);
  }

  .cc-field-value {
    background: var(--ds-active-surface);
    color: var(--ds-active-text-primary);
  }

  .cc-field-counter {
    border-top: 1px solid var(--ds-active-border);
    color: var(--ds-active-text-secondary);
  }
}
```

La polaridad (etiqueta más clara que el valor) invierte la del markup actual **a propósito**: reproduce la decisión ya tomada en `.aia-modal .table thead` por el tramo 5c y hace que la rejilla se lea como la tabla que en realidad es.

- [ ] **Step 4: Enlazar la hoja**

En `views/control-cambios/controlCambios.view.php`, justo después de la línea 6 (`DesignSystemHeadComponent::render()`), para que `@layer module` ya esté establecida por el agregador:

```php
	<link rel="stylesheet" href="/css/control-cambios.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/control-cambios.css') ?: 'cc1')) ?>" />
```

- [ ] **Step 5: Migrar el markup**

En `views/control-cambios/controlCambios.view.php` entre las líneas 143 y 572, sustituir en **las 85 ocurrencias con color** (`border-0`, 17 ocurrencias, **no lleva color y se queda**):

| Antes | Después |
|---|---|
| `class="row m-0 mb-3 border rounded shadow-sm"` | `class="row m-0 mb-3 cc-field-row rounded shadow-sm"` |
| `class="col-sm-3 p-2 bg-light d-flex align-items-center justify-content-center border-right"` | `class="col-sm-3 p-2 cc-field-label d-flex align-items-center justify-content-center"` |
| `class="col-sm-5 bg-light p-2 border-right d-flex align-items-center"` | `class="col-sm-5 cc-field-label p-2 d-flex align-items-center"` |
| `class="col-sm-9 p-0 d-flex flex-column bg-white"` | `class="col-sm-9 p-0 d-flex flex-column cc-field-value"` |
| `class="d-flex justify-content-end px-2 py-1 border-top"` | `class="d-flex justify-content-end px-2 py-1 cc-field-counter"` |
| `class="mb-0 small text-muted"` | `class="mb-0 small"` |

Los `border-bottom` restantes (11) pasan a una regla hermana dentro de `.cc-field-row`; añadir a `public/css/control-cambios.css` dentro del mismo `@layer module`:

```css
  .cc-field-divider {
    border-bottom: 1px solid var(--ds-active-border);
  }
```

y sustituir en el markup `border-bottom` por `cc-field-divider`.

Recuento de control antes de dar el paso por terminado:

```bash
sed -n '143,572p' views/control-cambios/controlCambios.view.php \
  | grep -oE 'class="[^"]*"' | tr ' ' '\n' | tr -d '"' | sed 's/class=//' \
  | grep -cE '^(bg-light|bg-white|border|border-right|border-top|border-bottom|text-muted)$'
```

Expected: `0`.

- [ ] **Step 6: Ejecutar y verificar que pasa**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "control-cambios"
```

Expected: PASS. Ratios ≥ 4,5 y `leftovers` en 0.

- [ ] **Step 7: Confirmar por CDP y revisar consola**

Con el modal abierto, comprobar por `CSS.getMatchedStylesForNode` sobre una `.cc-field-label` que el `background-color` ganador viene de `control-cambios.css` en `@layer module`, y que **no** aparece ninguna regla de `@layer vendor` con `!important` compitiendo. Revisar además que la consola del navegador no registre errores nuevos (la hoja nueva podría dar 404 si el `<link>` está mal).

- [ ] **Step 8: Verificar biome y audit**

```bash
npx biome check public/css/control-cambios.css
node scripts/design-system-audit.mjs
```

Expected: biome limpio en el archivo nuevo. El audit **baja** el total (se retiran 85 usos de utilidades de vendor, aunque no todas cuentan como violación) y no rompe ningún presupuesto nuevo.

- [ ] **Step 9: Commit**

```bash
git add public/css/control-cambios.css views/control-cambios/controlCambios.view.php tests/browser/modales-dark-homologacion.mjs
git commit -m "fix(design-system): que el modal de orden de cambio use superficies del sistema"
```

---

## Task 3: `/pdc` `#modalContrato`

Cuerpo a 1,37:1 y `.form-control` a 2,02:1. `pdc.css` entra por `<link>` **sin capa** desde `views/pdc/pdc.view.php` y gana a cualquier `@layer`: la regla `.aia-modal .modal-content` del shell es **inerte** aquí. Se edita in situ, así que no hay riesgo de cascada.

`pdc.css` tiene **cero** referencias `--ds-active-*` y 60 fijas: la página `/pdc` entera sigue en claro y su migración es un tramo posterior. Esta tarea toca **solo** `#modalContrato`, cuyo shell ya es oscuro y por tanto ya está roto hoy.

**Files:**
- Modify: `public/css/pdc.css` (líneas indicadas abajo)
- Modify: `tests/browser/modales-dark-homologacion.mjs`

**Interfaces:**
- Consumes: helpers de `tests/browser/support/contrast.mjs` (Task 1).

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/browser/modales-dark-homologacion.mjs`:

```js
test('/pdc #modalContrato: cuerpo y campos legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/pdc');
  await installContrastProbe(page);
  await openModal(page, 'modalContrato');

  for (const selector of [
    '#modalContrato .modal-body',
    '#modalContrato .pdc-contract-section__title',
    '#modalContrato input.form-control',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }
});
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "pdc #modalContrato"
```

Expected: FAIL. Cuerpo ≈ 1,37; `.form-control` ≈ 2,02.

- [ ] **Step 3: Conmutar las declaraciones que sí cambian**

En `public/css/pdc.css`, aplicar exactamente esta lista. **Cada línea, su token destino:**

| Línea | Selector | Antes | Después |
|---|---|---|---|
| 272 | `#modalContrato` | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 284 | `.modal-content` | `--ds-color-bg-parchment` | `--ds-active-surface` |
| 377 | `.pdc-contract-section__title` | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 385 | `.pdc-contract-section__hint` | `--ds-color-text-tertiary` | `--ds-active-text-secondary` |
| 418 | `.labelFormularioSeguimientoContrato` | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 440 | `.pdc-modal-field` | `--ds-color-surface` | `--ds-active-surface` |
| 451 | `textarea/input/select.form-control` | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 452 | `textarea/input/select.form-control` | `--ds-color-surface` | `--ds-active-surface` |
| 504 | `.labelFormularioContratos` | gradiente `surface-tint → bg-parchment` | `background: var(--ds-active-surface-raised)` (plano, se retira el gradiente) |
| 514 | `.labelFormularioContratos` | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 540 | `.inputFormularioContratos` | `--ds-color-surface` | `--ds-active-surface` |
| 553 | `.inputFormularioContratos input:read-only` | `--ds-color-surface` | `--ds-active-surface` |
| 587 | `.inputFormularioContratos i` | `--ds-color-brand-primary` | `--ds-active-action-primary` |
| 591 | `.pdc-bg-muted` | gradiente `bg-parchment → bg-parchment` | `background: var(--ds-active-surface-raised)` |
| 606 | `.seguimientoContrato` | `var(--ds-color-surface)dfa` | `var(--ds-active-surface)` — **ver Step 4** |
| 620 | `.pdc-row-center h5` | `--ds-color-brand-primary-dark` | `--ds-active-text-primary` |
| 641 | `.pdc-provider-locked::placeholder` | `--ds-color-text-tertiary` | `--ds-active-text-secondary` |
| 653 | `.pdc-provider-lock-badge` | `--ds-color-brand-primary-dark` | `--ds-active-action-primary` |
| 705 | `#btn_cancelar_editar` | `--ds-color-brand-primary` + `rgba(26,86,51,.35)` | `color: var(--ds-active-text-primary)`; `border: 1px solid var(--ds-active-border)` |
| 711 | `#btn_cancelar_editar:hover/:focus` | `--ds-color-brand-primary-dark` + `rgba(26,86,51,.08)` | `color: var(--ds-active-text-primary)`; `background: var(--ds-active-surface-glass)` |
| 718 | (pie del bloque) | `--ds-color-text-tertiary` | `--ds-active-text-secondary` |

**Se dejan intactas** (par cerrado o cabecera verde, idéntica en los dos temas): `291` (degradado de cabecera), `305` y `342` (`text-inverse` sobre el verde), `326` (píldora con fondo claro y tinta oscura declarados juntos), `523`/`529` (fila de encabezado verde + `text-inverse`), `691-692`/`698-699` (botón primario verde, igual que en los otros 57 modales).

- [ ] **Step 4: Arreglar el error de sintaxis de `pdc.css:606`**

La declaración actual es:

```css
	background: var(--ds-color-surface)dfa;
```

El sufijo `dfa` la hace inválida y el parser la **descarta entera**: `.seguimientoContrato` no tiene fondo hoy. Es un bug preexistente que este tramo destapa. Queda:

```css
	background: var(--ds-active-surface);
```

- [ ] **Step 5: Reescribir los campos de solo lectura**

Las tres reglas que hoy pintan solo-lectura con un gradiente verde pálido (`:562-564`, `:579`, `:634-635`) pasan a la receta oscura. Sobre oscuro ese verde pálido se convertiría en el elemento **más claro** del modal, justo el que menos debe llamar la atención. El borde `dashed` ya transportaba el significado por un canal no cromático y pasa a ser el principal.

`:562-564` queda:

```css
	border-color: var(--ds-active-border) !important;
	border-style: dashed !important;
	background-color: var(--ds-active-surface-glass) !important;
	background-image: none !important;
	color: var(--ds-active-text-secondary);
```

`:579` queda:

```css
	background: var(--ds-active-surface-glass);
```

`:634-635` queda:

```css
	background: var(--ds-active-surface-glass) !important;
	color: var(--ds-active-text-secondary) !important;
```

- [ ] **Step 6: Ejecutar y verificar que pasa**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "pdc #modalContrato"
```

Expected: PASS, los tres ratios ≥ 4,5.

- [ ] **Step 7: Verificar que no queda eje fijo de superficie/tinta/borde en el modal**

```bash
awk 'NR>=271 && NR<=760 && /--ds-color-(surface|bg-parchment|text-primary|text-secondary|text-tertiary|border-default|border-subtle|border-strong|surface-tint)/ {print NR": "$0}' public/css/pdc.css
```

Expected: sin salida.

- [ ] **Step 8: Confirmar por CDP, revisar consola y red**

Con `#modalContrato` abierto, comprobar por `CSS.getMatchedStylesForNode` sobre `#modalContrato .modal-body` que la regla ganadora del fondo es la de `pdc.css` (sin capa) y no la del shell, y que ahora resuelve a la superficie oscura. Revisar consola y red del modal (carga datos por HTTP).

- [ ] **Step 9: Verificar biome y presupuesto**

```bash
npx biome check public/css/pdc.css
node scripts/design-system-audit.mjs
```

Expected: el presupuesto `pdc` sigue en `hardcoded-hex 0`, `inline-style 0`, `hardcoded-radius 0`, `embedded-style-block 0`. No se han añadido `rgba()` nuevos (se han **retirado** dos, en `:704` y `:710`).

- [ ] **Step 10: Commit**

```bash
git add public/css/pdc.css tests/browser/modales-dark-homologacion.mjs
git commit -m "fix(design-system): llevar a oscuro la piel del modal de contrato de /pdc"
```

---

## Task 4: `/programa-general-actualizar` — `#modalAutoAsociar` y `#modalImportacionExitosa`

Cuerpo 1,07:1 y pie 1,01:1 el primero; `h3` 1,28:1 y `p` 1,77:1 el segundo. Los `style=` del markup ganan a todo; se retiran.

**Files:**
- Create: `public/css/programa-general-actualizar.css`
- Modify: `views/programa-general-actualizar/programaGeneralActualizar.view.php` — `:9` (link), `:630-648`, `:717-786`
- Modify: `tests/browser/modales-dark-homologacion.mjs`

**Interfaces:**
- Consumes: helpers de `tests/browser/support/contrast.mjs` (Task 1).
- Produces: clases `.pga-success-badge` y `.pga-success-copy` en `public/css/programa-general-actualizar.css`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/browser/modales-dark-homologacion.mjs`:

```js
test('/programa-general-actualizar: modales de auto-asociacion y exito legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/programa-general-actualizar');
  await installContrastProbe(page);

  await openModal(page, 'modalAutoAsociar');
  for (const selector of ['#modalAutoAsociar .modal-body', '#modalAutoAsociar .modal-footer']) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }
  await closeModal(page, 'modalAutoAsociar');

  await openModal(page, 'modalImportacionExitosa');
  for (const selector of ['#modalImportacionExitosa h3', '#modalImportacionExitosa p']) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }

  // Ningun style= de color puede sobrevivir en los dos modales.
  const inlineColors = await page.evaluate(() => {
    const ids = ['modalAutoAsociar', 'modalImportacionExitosa'];
    return ids.flatMap((id) => [...document.getElementById(id).querySelectorAll('[style]')]
      .map((el) => el.getAttribute('style'))
      .filter((s) => /(^|;)\s*(background|color)\s*:/i.test(s))).length;
  });
  expect(inlineColors, 'quedan style= de color en el markup').toBe(0);
});
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "programa-general-actualizar"
```

Expected: FAIL con los cuatro ratios entre 1,01 y 1,77 e `inlineColors` > 0.

- [ ] **Step 3: Crear la hoja del módulo**

Crear `public/css/programa-general-actualizar.css`:

```css
/* Piel de los dos modales cuyos estilos vivian en style= del markup.
   En @layer module, junto a styles.css: no debe ganarle al design system. */
@layer module {
  /* Sello de exito: par de estado semantico, constante entre temas por DESIGN.md.
     Conserva exactamente el aspecto y el ratio que ya median bien (8,7:1). */
  .pga-success-badge {
    background: var(--ds-color-state-success-bg);
  }

  .pga-success-badge i {
    color: var(--ds-color-state-success-text);
  }

  .pga-success-copy {
    color: var(--ds-active-text-secondary);
  }
}
```

- [ ] **Step 4: Enlazar la hoja**

En `views/programa-general-actualizar/programaGeneralActualizar.view.php`, justo después de la línea 9 (`DesignSystemHeadComponent::render()`):

```php
	<link rel="stylesheet" href="/css/programa-general-actualizar.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/programa-general-actualizar.css') ?: 'pga1')) ?>" />
```

- [ ] **Step 5: Retirar los `style=` de `#modalAutoAsociar`**

Líneas 720-729 y 779. Quedan:

```php
					<div class="modal-header">
						<div class="modal-title" id="modalAutoAsociarLabel">
							<div class="aia-modal__eyebrow">AIA Corporativo</div>
							<h5>Resultados de Auto-Asociación</h5>
						</div>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
```

y

```php
					<div class="modal-footer">
```

El `display: flex; justify-content: space-between; align-items: center` del pie **sí es layout, no color**, y el shell ya lo aporta: `.aia-modal .modal-footer` es flex. Verificar en el navegador que los dos grupos de botones siguen separados; si no, añadir a `programa-general-actualizar.css` dentro del `@layer module`:

```css
  #modalAutoAsociar .modal-footer {
    justify-content: space-between;
  }
```

**Consecuencia aceptada:** la cabecera pasa de verde plano a degradado, como los otros 48 modales del shell. Es el propósito de tener un shell, y debe aparecer en la evidencia visual.

Las tintas `color: white` y `rgba(255,255,255,0.7)` desaparecen sin sustituto: `.aia-modal .modal-header`, `.modal-title` y `.aia-modal__eyebrow` del shell ya las aportan tokenizadas, medidas en 8,25:1.

- [ ] **Step 6: Retirar los `style=` de `#modalImportacionExitosa`**

Líneas 634-638. Quedan:

```php
		        <div class="pga-success-badge" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
		          <i class="fas fa-check" style="font-size: 40px;"></i>
		        </div>
		        <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700; margin-bottom: 10px;">¡Carga Exitosa!</h3>
		        <p class="pga-success-copy" style="font-family: 'Inter', sans-serif; font-size: 16px; margin-bottom: 25px;">
```

El `h3` pierde su `color` y hereda `--ds-active-text-primary` de `.aia-modal`. Los `style=` que quedan son **geometría y tipografía, no color**: siguen siendo violaciones `inline-style` preexistentes del audit, pero están fuera del encargo (que es de contraste) y retirarlos es el refactor del `<style>` de 405 líneas, aplazado a su propio tramo.

El círculo de éxito conserva su color exacto (`#d5e5db` + `#1a5633`), ahora tokenizado a los estados fijos: mismo aspecto, mismo ratio de 8,7:1, dos `hardcoded-hex` menos.

- [ ] **Step 7: Ejecutar y verificar que pasa**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "programa-general-actualizar"
```

Expected: PASS, los cuatro ratios ≥ 4,5 e `inlineColors` en 0.

- [ ] **Step 8: Confirmar por CDP y capturar la cabecera**

Comprobar por `CSS.getMatchedStylesForNode` sobre `#modalAutoAsociar .modal-body` que ahora gana `.aia-modal .modal-body` de `styles.css` y que no hay regla de `style` attribute. Capturar `#modalAutoAsociar` abierto: la cabecera cambia de aspecto (plano → degradado) y ese cambio debe quedar en la evidencia.

- [ ] **Step 9: Verificar biome y audit**

```bash
npx biome check public/css/programa-general-actualizar.css
node scripts/design-system-audit.mjs
```

Expected: biome limpio. El audit **baja**: −2 `hardcoded-hex` (el círculo), −4 `inline-style` de color en `#modalAutoAsociar` y −2 en `#modalImportacionExitosa`.

- [ ] **Step 10: Commit**

```bash
git add public/css/programa-general-actualizar.css views/programa-general-actualizar/programaGeneralActualizar.view.php tests/browser/modales-dark-homologacion.mjs
git commit -m "fix(design-system): sacar del markup la piel clara de los modales de actualizacion"
```

---

## Task 5: `/programacion-semanal` `#modal_change_monitor`

Cuerpo 1,07:1 y pie 1,03:1.

**El informe de origen atribuye este modal a `public/css/change-monitor.css` y es falso.** Ese archivo existe (127 líneas, cargado desde `views/programacion-semanal/programacion_semanal.view.php:42`) y **no contiene ni una regla `.cm-modal`**. El dueño real es `public/css/programacion-semanal.css:2890-3130`.

**Este archivo tiene presupuesto cero absoluto**, incluido `hardcoded-color-function`: solo tokens y `color-mix()` sobre `var()`. Que `color-mix` es admisible lo prueba `.cm-total-badge` (`:2945`), que ya lo usa en este archivo bajo el mismo presupuesto.

**Files:**
- Modify: `public/css/programacion-semanal.css:2890-3130`
- Modify: `tests/browser/modales-dark-homologacion.mjs`

**Interfaces:**
- Consumes: helpers de `tests/browser/support/contrast.mjs` (Task 1).

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/browser/modales-dark-homologacion.mjs`:

```js
test('/programacion-semanal #modal_change_monitor: cuerpo y pie legibles', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/programacion-semanal');
  await installContrastProbe(page);
  await openModal(page, 'modal_change_monitor');

  for (const selector of [
    '#modal_change_monitor .cm-modal-body',
    '#modal_change_monitor .cm-modal-footer',
    '#modal_change_monitor .cm-close-btn',
  ]) {
    const result = await measure(page, selector);
    expect(result, `${selector} no existe`).not.toBeNull();
    expect.soft(result.ratio, `${selector} — ${result.fg} sobre ${result.bg}`)
      .toBeGreaterThanOrEqual(AA);
  }
});
```

**Aviso de entorno:** `/programacion-semanal` auto-dispara `save`/`auto-program` al cargar. Esperar a que la red se calme antes de abrir el modal (`await page.waitForLoadState('networkidle')`) para no medir durante un repintado.

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "change_monitor"
```

Expected: FAIL. Cuerpo ≈ 1,07; pie ≈ 1,03.

- [ ] **Step 3: Conmutar el bloque**

En `public/css/programacion-semanal.css`, aplicar la tabla de conmutación a las 31 referencias fijas de `2890-3130`:

| Líneas | Antes | Después |
|---|---|---|
| 2981 | `--ds-color-bg-parchment` | `--ds-active-surface` |
| 2993, 3026 | `--ds-color-text-inverse` como **fondo** | `--ds-active-surface-raised` |
| 2994, 3093, 3100, 3110 | `--ds-color-border-default` | `--ds-active-border` |
| 2995, 3013, 3039, 3102 | `--ds-color-text-secondary` | `--ds-active-text-secondary` |
| 3004, 3014 | `--ds-color-surface-tint` | `--ds-active-surface-raised` |
| 3005 | `--ds-color-border-strong` | `--ds-active-border` |
| 3029, 3044 | `--ds-color-border-subtle` | `--ds-active-border` |
| 3038, 3061, 3092 | `--ds-color-bg-page` | `--ds-active-surface-raised` |
| 3050 | `--ds-color-text-primary` | `--ds-active-text-primary` |
| 3052 | `--ds-color-bg-page` (borde) | `--ds-active-border` |
| 3086 | `--ds-color-text-tertiary` | `--ds-active-text-secondary` |

**Se dejan intactas:** `2902`/`2903`, `2938`, `2945`, `2946`, `2963` — cabecera verde y su `text-inverse`, idénticos en los dos temas.

**Cuidado con `3100` y `3110`:** `--ds-color-border-default` se usa ahí como **fondo** del botón `.cm-close-btn`, no como borde. Ese uso pasa a `--ds-active-surface-raised`, no a `--ds-active-border` (un borde translúcido al 22% no sirve de fondo de botón).

**Riesgo 1 del spec — la escala activa tiene menos escalones.** La tabla de arriba colapsa `border-subtle`, `border-default` y `border-strong` en un único `--ds-active-border`, lo que aplanaría la jerarquía de la tabla del change monitor (tres pesos de línea pasarían a uno). Los dos escalones que faltan se derivan del token activo, sin literales:

```css
  /* separador tenue, donde antes iba border-subtle */
  border-bottom: 1px solid color-mix(in srgb, var(--ds-active-border) 55%, transparent);
```

```css
  /* linea de enfasis, donde antes iba border-strong */
  border-color: color-mix(in srgb, var(--ds-active-border) 100%, var(--ds-active-text-secondary) 25%);
```

Aplicar el escalón tenue a `:3029` y `:3044`, y el de énfasis a `:3005`. `:2994`, `:3093` y `:3052` se quedan en `--ds-active-border` a secas. `color-mix()` sobre `var()` no cuenta como `hardcoded-color-function` (lo prueba `.cm-total-badge` en `:2945`, bajo el mismo presupuesto cero).

- [ ] **Step 4: Reescribir los tres tintes de fila**

`:3067`, `:3071` y `:3075` mezclan hoy la **mitad oscura** del par de estado al 10%:

```css
    background: color-mix(in srgb, var(--ds-color-state-success-text) 10%, transparent);
```

Un 10% de tinta oscura sobre superficie oscura es invisible, y estos tres tintes son el único canal que distingue una fila conforme de una crítica. Pasan a mezclar la **mitad clara**:

```css
    background: color-mix(in srgb, var(--ds-color-state-success-bg) 12%, transparent);
```

```css
    background: color-mix(in srgb, var(--ds-color-state-critical-bg) 12%, transparent);
```

```css
    background: color-mix(in srgb, var(--ds-color-state-warning-bg) 12%, transparent);
```

El `12%` es un punto de partida, **no un valor final**. Medir el fondo compuesto de cada fila contra el de una fila sin tinte y subir el escalón de 2% hasta que la diferencia de luminancia relativa sea perceptible (≥ 1,15:1 entre fila teñida y fila neutra). Registrar en el reporte de la tarea el porcentaje final medido de los tres.

- [ ] **Step 5: Ejecutar y verificar que pasa**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1 -g "change_monitor"
```

Expected: PASS, los tres ratios ≥ 4,5.

- [ ] **Step 6: Verificar el presupuesto cero**

```bash
node scripts/design-system-audit.mjs 2>&1 | grep -i "programacion-semanal"
```

Expected: sin fallo. Cualquier `rgba()` o hex que se haya colado lo rompe.

```bash
awk 'NR>=2890 && NR<=3130 && /#[0-9a-fA-F]{3,8}\b|rgba?\(|hsla?\(/ {print NR": "$0}' public/css/programacion-semanal.css
```

Expected: sin salida.

- [ ] **Step 7: Confirmar por CDP**

Con `#modal_change_monitor` abierto, comprobar por `CSS.getMatchedStylesForNode` que el fondo de `.cm-modal-body` lo gana `programacion-semanal.css` y no el shell, y que resuelve a la superficie oscura.

- [ ] **Step 8: Verificar biome**

```bash
npx biome check public/css/programacion-semanal.css
```

Expected: sin errores nuevos frente a la línea base del archivo.

- [ ] **Step 9: Commit**

```bash
git add public/css/programacion-semanal.css tests/browser/modales-dark-homologacion.mjs
git commit -m "fix(design-system): llevar a oscuro el modal del change monitor"
```

---

## Task 6: Cierre — barrido completo y evidencia

**Files:**
- Create: `.superpowers/sdd/f1-modales-huerfanos-report.md`
- Modify: `goals/dark-mode-todos-los-modulos/validation-log.md`

- [ ] **Step 1: Ejecutar la suite completa del test nuevo**

```bash
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
```

Expected: PASS en los cinco bloques.

- [ ] **Step 2: Verificar que no queda eje fijo en ninguno de los seis modales**

```bash
node scripts/design-system-audit.mjs
```

Expected: total por debajo del de `HEAD~5`. Único fallo admisible: `programa-general hardcoded-hex: 1 > 0`, preexistente y ajeno a este tramo.

- [ ] **Step 3: Ejecutar las suites que este cambio puede haber tocado**

```bash
npm run test:design-system:static
```

Expected: mismos rojos preexistentes que antes de empezar (`contracts.test.mjs` exige árbol limpio; con cambios ajenos en el worktree seguirá rojo). Ningún rojo **nuevo**.

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
```

Expected: el mismo resultado que antes del tramo (test 1 verde en las 10 rutas; test 2 rojo deliberado en `/profesionales` y `/subcontratistas`).

- [ ] **Step 4: Verificación visual de cierre de sprint**

Abrir el navegador integrado contra el contenedor y recorrer las cinco rutas afectadas con sus modales abiertos: `/control-cambios`, `/pdc`, `/programa-general-actualizar`, `/programacion-semanal`, `/listado-actividades`. Viewport 1180×820, dark. Revisar consola y red en cada una.

- [ ] **Step 5: Escribir el reporte**

Crear `.superpowers/sdd/f1-modales-huerfanos-report.md` con: tabla de ratio antes/después de los siete defectos de §5.7, la confirmación por CDP de qué regla gana en cada rol, el porcentaje final medido de los tres tintes de fila, el delta del audit, y la lista de lo que se decidió **no** hacer.

- [ ] **Step 6: Commit**

```bash
git add .superpowers/sdd/f1-modales-huerfanos-report.md goals/dark-mode-todos-los-modulos/validation-log.md
git commit -m "docs(design-system): registrar la homologacion a oscuro de los seis modales"
```

---

## Fuera de alcance

- Filas 8 y 9 de §5.7 (`span.text-danger` a 3,11:1; borde de `.aia-tipo-pill--oc.is-checked` a 1,22:1): preexistentes, y la segunda exige mover un hex de marca que el goal protege.
- El defecto de especificidad del hover de las pills (§5.6).
- La migración de `/pdc` fuera de `#modalContrato` y el `<style>` de 405 líneas de `programa-general-actualizar`: ambos, tramo propio.
- `.ps-dropdown-item` y `.btn-dropdown-trigger` (`programacion-semanal.css:2853-2887`), que usan `--ds-color-brand-architecture` como tinta: están fuera del change monitor y fuera de este tramo.
- Cualquier trabajo en mobile, tablet o tema `linen`.
