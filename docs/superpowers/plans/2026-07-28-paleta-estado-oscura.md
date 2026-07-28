# Invertir la paleta de estado del design system a oscuro — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que los cuatro colores de estado del design system dejen de ser los del tema claro y pasen a los valores oscuros que /pdc ya tiene medidos, para que las superficies del sistema nazcan oscuras y los módulos dejen de parchearlas.

**Architecture:** Cuatro pares `--ds-color-state-{critical,warning,success,info}-{bg,text}` se invierten en `public/css/tokens.css`: el fondo pasa de pastel claro a tinte oscuro y el texto de tono oscuro a claro tintado. Antes se clasifican los 39 sitios que hoy usan medio par y quedarían descompensados; después se retiran, módulo por módulo, los `background` que las hojas de módulo declaran encima de los chips y que dejaban inerte la primitiva del design system.

**Tech Stack:** CSS con `@layer` y OKLCH · Playwright contra Docker · `node --test` para los gates estáticos · Handsontable en los módulos operativos.

## Global Constraints

- Desktop ≥1180 px y **solo dark**. Viewport canónico 1180×820, proyecto «Da Porto», credenciales `test.A` / `aia2026`, app en `http://localhost:8081`. Prohibido trabajar, probar o generar evidencia para mobile, tablet o el tema `linen` (`AGENTS.md`).
- **No regenerar baselines visuales.** Varias ya están rojas por un reflow ajeno: `/programa-general` 1180×820 en 43.979 px y `/programacion-intermedia` 1180×820 en 30.845 px con el árbol limpio, y todas las de `design-system-lab.visual.mjs`. El cambio propio se mide comparando el número de píxeles ANTES y DESPUÉS, nunca regenerando.
- **No regenerar `docs/design-system/a11y-baseline.json` de golpe.** Se revisa hallazgo por hallazgo.
- **No stagear ni revertir** los archivos que otras sesiones tengan sucios en el worktree. Nada de `git add -A`: stagear archivo por archivo.
- `tests/browser/*` está gitignorado con allowlist: un test nuevo necesita su línea `!tests/browser/<archivo>` en `.gitignore` o no se commitea. `tests/design-system/*.test.mjs` no lo necesita.
- Rojos preexistentes que **no** son regresión: `contracts.test.mjs › activation: worktree and index must be clean` (ambiental, por los archivos ajenos sucios), 879 errores de formato de biome, y seis fallos en `programacion-semanal-{dark-density,roles-phases}`.
- Los golden `states-feedback-dark-*.png` **no se comparan**: el test retorna antes de `toHaveScreenshot`.
- Mensajes de commit en español, explicando el porqué.

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `public/css/tokens.css` | Los cuatro pares invertidos y los cuatro claros conservados con nombre de documento. Único sitio donde cambian los valores. |
| `docs/design-system/state-token-exceptions.json` | **Nuevo.** Inventario de los sitios que usan medio par a propósito, con su razón. Es el contrato que el guard comprueba. |
| `tests/design-system/state-token-pairing.test.mjs` | **Nuevo.** Guard que exige que todo uso descompensado esté declarado como excepción. |
| `public/css/programacion-semanal.css`, `listado-actividades.css`, `contratos.css`, `bi-control-tower.css`, `bi-filter-drawer.css`, `login-brand-unified.css`, `design-system/components/navigation.css` | Los 39 sitios a clasificar. |
| `public/css/pdc.css`, `programacion-intermedia.css`, `programacion-semanal.css`, `programa-general.css` | Los `background` de módulo a retirar, una tarea por módulo. |

---

### Task 1: Clasificar los 39 usos descompensados

Ningún color cambia en esta tarea. Deja el terreno preparado para que cualquier cosa rara en la Task 2 sea atribuible al cambio de token.

**Files:**
- Create: `docs/design-system/state-token-exceptions.json`
- Create: `tests/design-system/state-token-pairing.test.mjs`
- Modify: los archivos que el guard señale (emparejar los que deban emparejarse)

**Interfaces:**
- Produces: `docs/design-system/state-token-exceptions.json` con la forma `{ "version": "1.0.0", "exceptions": [{ "file", "selector", "token", "reason" }] }`. La Task 2 asume que este archivo existe y que el guard está verde.

- [ ] **Step 1: Escribir el guard que falla**

Crear `tests/design-system/state-token-pairing.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { join, relative } from 'node:path';
import test from 'node:test';

const CSS_ROOT = new URL('../../public/css/', import.meta.url).pathname;

// Los cuatro tokens de estado se consumen en pareja: el fondo trae su texto.
// Un bloque que use solo uno de los dos queda descompensado en cuanto el par se
// invierta -fondo oscuro con texto oscuro, o al reves-, asi que cada caso tiene
// que estar emparejado o declarado como excepcion con su razon.
async function cssFiles(dir) {
  const found = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) found.push(...await cssFiles(full));
    else if (entry.name.endsWith('.css')) found.push(full);
  }
  return found;
}

// Se recorre bloque a bloque `{ ... }` porque la pareja tiene sentido dentro de
// una misma regla: un `-bg` en una regla y su `-text` en otra no garantiza que
// se apliquen al mismo elemento.
function unpairedUses(css) {
  const found = [];
  for (const [, block] of css.matchAll(/\{([^{}]*)\}/g)) {
    const bg = new Set([...block.matchAll(/background[^;:]*:\s*[^;]*--ds-color-state-(\w+)-bg/g)].map((m) => m[1]));
    const text = new Set([...block.matchAll(/(?<!background)color\s*:\s*[^;]*--ds-color-state-(\w+)-text/g)].map((m) => m[1]));
    for (const family of bg) if (!text.has(family)) found.push(`--ds-color-state-${family}-bg`);
    for (const family of text) if (!bg.has(family)) found.push(`--ds-color-state-${family}-text`);
  }
  return found;
}

test('todo uso descompensado de los tokens de estado está declarado', async () => {
  const exceptions = JSON.parse(
    await readFile(new URL('../../docs/design-system/state-token-exceptions.json', import.meta.url), 'utf8'),
  );
  const declared = new Map();
  for (const e of exceptions.exceptions) {
    assert.ok(e.reason && e.reason.length > 20, `la excepción de ${e.file} necesita una razón real`);
    declared.set(`${e.file}|${e.token}`, (declared.get(`${e.file}|${e.token}`) ?? 0) + 1);
  }

  const files = await cssFiles(CSS_ROOT);
  assert.ok(files.length > 20, `se esperaban más de 20 hojas y se encontraron ${files.length}`);

  const undeclared = [];
  for (const file of files) {
    const rel = `public/css/${relative(CSS_ROOT, file)}`;
    const counts = new Map();
    for (const token of unpairedUses(await readFile(file, 'utf8'))) {
      counts.set(token, (counts.get(token) ?? 0) + 1);
    }
    for (const [token, count] of counts) {
      const allowed = declared.get(`${rel}|${token}`) ?? 0;
      if (count > allowed) undeclared.push(`${rel}: ${token} ×${count} (declaradas ${allowed})`);
    }
  }

  assert.deepEqual(undeclared, [], `usos descompensados sin declarar:\n  ${undeclared.join('\n  ')}`);
});
```

Crear `docs/design-system/state-token-exceptions.json` con el esqueleto vacío:

```json
{
  "version": "1.0.0",
  "purpose": "Sitios que consumen la mitad de un par de estado a proposito. Al invertir los cuatro pares a oscuro, un bloque con solo -bg o solo -text queda descompensado; los que aparecen aqui es porque su intencion lo justifica.",
  "exceptions": []
}
```

- [ ] **Step 2: Correr el guard y verificar que falla**

Run: `node --test tests/design-system/state-token-pairing.test.mjs`
Expected: FAIL, con una lista de unos 39 usos repartidos en 6-7 archivos. Si la lista sale vacía, el regex no está encontrando nada: comprueba `unpairedUses` contra `public/css/programacion-semanal.css`, que debe dar 16.

- [ ] **Step 3: Clasificar cada uso**

Abrir cada sitio que el guard señale y decidir entre tres salidas:

1. **Emparejarlo** — el bloque debería haber llevado el par completo. Añadir la declaración que falta.
2. **Declararlo excepción** — el sitio usa medio par a propósito. Añadir la entrada a `state-token-exceptions.json` con una razón concreta de por qué (más de 20 caracteres; el guard lo exige).
3. **Corregirlo** — el uso ya estaba mal antes de este trabajo. Arreglarlo y decirlo en el commit.

Empezar por `public/css/programacion-semanal.css`, que concentra 16 de los 39.

Caso conocido que va a la excepción: la cabecera del modal de reabrir semana (`.ps-reopen-header`) es un aviso **deliberadamente claro**; `public/css/styles.css` ya lo advierte en un comentario. Su razón: es un aviso que debe destacar sobre el resto de la interfaz y perdería el énfasis en oscuro.

- [ ] **Step 4: Correr el guard y verificar que pasa**

Run: `node --test tests/design-system/state-token-pairing.test.mjs`
Expected: PASS, 1/1.

- [ ] **Step 5: Verificar que ningún color cambió**

Run: `npx playwright test tests/browser/state-tint-ladder.mjs tests/browser/pdc-chips-dark.mjs --workers=1`
Expected: PASS. Esta tarea empareja declaraciones pero no toca valores; si algo cambia de color aquí, es un error.

- [ ] **Step 6: Commit**

```bash
git add docs/design-system/state-token-exceptions.json tests/design-system/state-token-pairing.test.mjs
git add public/css/programacion-semanal.css public/css/listado-actividades.css public/css/contratos.css
git commit -m "chore(design-system): clasificar los usos descompensados de los tokens de estado"
```

---

### Task 2: Invertir los cuatro pares

**Files:**
- Modify: `public/css/tokens.css:182-189`
- Modify: `tests/browser/state-tint-ladder.mjs` (añadir la aserción del par invertido)

**Interfaces:**
- Consumes: el guard de la Task 1 en verde.
- Produces: `--ds-color-state-{critical,warning,success,info}-bg` con tinte oscuro y `-text` con tono claro; `--ds-color-doc-state-*` con los cuatro valores claros conservados.

- [ ] **Step 1: Derivar y medir el texto de `info`**

/pdc aporta tres textos tintados (`#ffcdc8` rojo, `#f2e79c` ámbar, `#b7e8c6` verde) pero ninguno teal. Hay que derivarlo con la misma receta.

Medir la luminosidad OKLCH de los tres conocidos y usar su media sobre el matiz del ancla teal (`#134841`). En el navegador, con la app abierta:

```javascript
// Pegar en la consola de /pdc o ejecutar con page.evaluate
const probe = (hex) => {
  const d = document.createElement('div');
  d.style.cssText = `position:absolute;left:-9999px;color:oklch(from ${hex} l c h)`;
  document.body.appendChild(d);
  const v = getComputedStyle(d).color;
  d.remove();
  return v;
};
['#ffcdc8', '#f2e79c', '#b7e8c6', '#134841'].map(probe);
```

Tomar la `l` media de los tres textos y aplicarla al ancla teal conservando su croma y matiz:
`oklch(from #134841 <L_media> c h)`. Fijar el hex resultante y medir su contraste contra `#134841`.

**Criterio de aceptación:** ≥7:1, que es el mínimo que cumplen los otros tres (8,88 el peor).

- [ ] **Step 2: Escribir la aserción que falla**

Añadir a `tests/browser/state-tint-ladder.mjs`:

```javascript
// Los cuatro pares de NIVEL viven invertidos respecto a como nacieron: el fondo
// es el tinte oscuro y el texto el tono claro. Nacieron al reves -pastel claro
// con texto oscuro- porque eran los valores de un tema claro que el producto ya
// no tiene, y esa inversion es la razon por la que cada modulo los tapaba.
const LEVEL_PAIRS = {
  critical: { bg: '#431414', text: '#ffcdc8' },
  warning: { bg: '#3a3a0f', text: '#f2e79c' },
  success: { bg: '#173d26', text: '#b7e8c6' },
  info: { bg: '#134841', text: '<el hex medido en el Step 1>' },
};

test('los cuatro pares de nivel son oscuros con texto claro', async ({ page }) => {
  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project);
  await page.goto('/pdc', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pdc-legend .pdc-legend-item').first()).toBeVisible({ timeout: 45000 });

  for (const [family, expected] of Object.entries(LEVEL_PAIRS)) {
    const bg = await resolveTint(page, `--ds-color-state-${family}-bg`);
    const text = await resolveTint(page, `--ds-color-state-${family}-text`);
    expect(toHex(bg), `--ds-color-state-${family}-bg`).toBe(expected.bg);
    expect(toHex(text), `--ds-color-state-${family}-text`).toBe(expected.text);
    // El fondo tiene que ser el oscuro de la pareja, no al reves.
    expect(
      relativeLuminance(bg),
      `${family}: el fondo deberia ser mas oscuro que su texto`,
    ).toBeLessThan(relativeLuminance(text));
    expect(contrastRatio(bg, text), `contraste de ${family}`).toBeGreaterThanOrEqual(7);
  }
});
```

Si `relativeLuminance` o `contrastRatio` no existen en ese archivo, copiarlas de `tests/browser/pdc-chips-dark.mjs:36-47`.

- [ ] **Step 3: Correr y verificar que falla**

Run: `npx playwright test tests/browser/state-tint-ladder.mjs --workers=1 -g "cuatro pares de nivel"`
Expected: FAIL con `--ds-color-state-critical-bg` valiendo `#fdecec` en vez de `#431414`.

- [ ] **Step 4: Invertir los cuatro pares**

En `public/css/tokens.css`, sustituir las líneas 182-189 por:

```css
    /* Los cuatro colores de NIVEL. Nacieron como los valores de un tema claro
       -pastel de fondo, tono oscuro de texto- y se quedaron asi cuando el
       producto retiro `linen` y paso a ser solo oscuro. A diferencia de las
       superficies, el texto y los bordes, los estados no tienen indireccion de
       tema: se consumen en crudo. Por eso cada modulo los venia tapando, y de
       ahi salen el puente que duplica las reglas de nivel, los `background` que
       las hojas declaran encima de los chips y los siete hex propios que /pdc
       mantuvo durante meses.
       Los valores son los que /pdc eligio y midio: 10,99:1 el rojo, 9,31:1 el
       ambar, 8,88:1 el verde. El teal se derivo con la misma receta.
       Guard: tests/browser/state-tint-ladder.mjs. */
    --ds-color-state-success-bg: #173d26;
    --ds-color-state-success-text: #b7e8c6;
    --ds-color-state-warning-bg: #3a3a0f;
    --ds-color-state-warning-text: #f2e79c;
    --ds-color-state-critical-bg: #431414;
    --ds-color-state-critical-text: #ffcdc8;
    --ds-color-state-info-bg: #134841;
    --ds-color-state-info-text: <el hex medido en el Step 1>;

    /* Los cuatro claros no se borran: el export XLSX es un documento BLANCO y
       los necesita. Hoy los tiene duplicados a mano en
       src/Controllers/Gestion/ReportController.php. No son colores de pantalla:
       no los uses para nada que se vea en la app. */
    --ds-color-doc-state-success-bg: #ddefe6;
    --ds-color-doc-state-success-text: #1a5633;
    --ds-color-doc-state-warning-bg: #fff8e1;
    --ds-color-doc-state-warning-text: #5d4200;
    --ds-color-doc-state-critical-bg: #fdecec;
    --ds-color-doc-state-critical-text: #8f1d1d;
    --ds-color-doc-state-info-bg: #e3f9f7;
    --ds-color-doc-state-info-text: #006d66;
```

- [ ] **Step 5: Correr y verificar que pasa**

Run: `npx playwright test tests/browser/state-tint-ladder.mjs --workers=1`
Expected: PASS, todos los tests del archivo.

- [ ] **Step 6: Medir el delta de las baselines, sin regenerarlas**

Run: `npx playwright test tests/browser/programa-general.visual.mjs tests/browser/programacion-intermedia.visual.mjs --workers=1`

Anotar el número de píxeles distintos de cada una y compararlo con los de la constante global de este plan (43.979 y 30.845). **No regenerar ninguna.** El delta esperado es grande: este commit cambia el color de toda la app.

- [ ] **Step 7: Revisar la accesibilidad hallazgo por hallazgo**

Run: `npm run test:a11y:lab`

Si falla, leer cada `fingerprint` nuevo y decidir si es una mejora que la baseline aún no conoce o una regresión real. **No regenerar la baseline en bloque.** Los pares nuevos miden 8,88–10,99:1 frente a los actuales, así que lo esperable es que mejore.

- [ ] **Step 8: Commit**

```bash
git add public/css/tokens.css tests/browser/state-tint-ladder.mjs
git commit -m "feat(design-system): invertir los cuatro pares de estado a fondo oscuro"
```

---

### Task 3: Retirar el parche de fondo de /pdc

**Files:**
- Modify: `public/css/pdc.css`, `public/css/styles.css` (los bloques de `.pdc-legend-item`)
- Modify: `tests/design-system/ops-state-contract.test.mjs`

**Interfaces:**
- Consumes: los cuatro pares invertidos de la Task 2.

- [ ] **Step 1: Escribir el guard que falla**

Añadir a `tests/design-system/ops-state-contract.test.mjs`:

```javascript
// Mismo motivo que en Intermedia y Semanal: `@layer module` va despues de
// `components`, asi que mientras la hoja del modulo declare `background` sobre
// el chip, la primitiva del design system no puede pintarlo por mucha
// especificidad que se le ponga.
test('la hoja de /pdc no declara el fondo del chip de leyenda', async () => {
  const css = await read('public/css/pdc.css');
  const chipBlock = css.match(/\.pdc-legend-item \{([^}]*)\}/g) ?? [];
  assert.ok(chipBlock.length > 0, 'no se encontró la regla base de .pdc-legend-item');
  const painting = chipBlock.filter((block) => /(^|[\s;])background(-color)?\s*:/.test(block));
  assert.deepEqual(painting, [], 'pdc.css volvió a declarar `background` sobre el chip');
});
```

- [ ] **Step 2: Correr y verificar que falla**

Run: `node --test tests/design-system/ops-state-contract.test.mjs`
Expected: FAIL si `pdc.css` declara fondo. Si pasa a la primera, el chip de /pdc ya lo recibe de otro sitio: buscar con `grep -n "pdc-legend-item" public/css/*.css` antes de continuar.

- [ ] **Step 3: Medir el color actual de los siete chips**

```bash
npx playwright test tests/browser/pdc-chips-dark.mjs --workers=1
```

Anotar los siete fondos antes de tocar nada.

- [ ] **Step 4: Retirar la declaración de fondo**

Quitar el `background` de la regla base de `.pdc-legend-item` en `pdc.css`, dejando layout, borde y tipografía. Añadir un comentario explicando que el fondo lo pinta la capa de componentes desde `data-aia-hue`.

- [ ] **Step 5: Correr y verificar que pasa**

Run: `node --test tests/design-system/ops-state-contract.test.mjs && npx playwright test tests/browser/pdc-chips-dark.mjs --workers=1`
Expected: PASS los dos. Los siete chips deben conservar su color: /pdc ya declara su matiz.

- [ ] **Step 6: Commit**

```bash
git add public/css/pdc.css tests/design-system/ops-state-contract.test.mjs
git commit -m "refactor(design-system): que /pdc deje de declarar el fondo de su chip"
```

---

### Task 4: Retirar el parche de fondo de /programacion-intermedia

Idéntico en forma a la Task 3, sobre `public/css/programacion-intermedia.css`. El guard ya existe (`la hoja del módulo no vuelve a pintar el chip por nombre de estado`) y hoy está verde: comprobar que sigue verde tras la Task 2 y que los ocho chips conservan su matiz.

- [ ] **Step 1: Medir los ocho chips antes**

Run: `npx playwright test tests/browser/ops-state-chip-hue.mjs --workers=1 -g "Intermedia"`

- [ ] **Step 2: Comprobar si queda algún `background` en la hoja**

Run: `grep -n "background" public/css/programacion-intermedia.css | grep -i "state-chip\|legend-item"`
Expected: vacío. Si sale algo, retirarlo.

- [ ] **Step 3: Correr los guards**

Run: `node --test tests/design-system/ops-state-contract.test.mjs && npx playwright test tests/browser/ops-state-chip-hue.mjs --workers=1`
Expected: PASS.

- [ ] **Step 4: Commit (si hubo cambios)**

```bash
git add public/css/programacion-intermedia.css
git commit -m "refactor(design-system): retirar el ultimo fondo de modulo de Intermedia"
```

---

### Task 5: Retirar el parche de fondo de /programacion-semanal

**Files:**
- Modify: `public/css/programacion-semanal.css`

Semanal es el caso más cargado: sus cinco tintes de chip se derivan hoy con una mezcla al 88 % hacia la superficie (`#3f1615` donde el ancla roja es `#431414`), así que **no** son las anclas.

- [ ] **Step 1: Medir los cinco chips antes**

Run: `npx playwright test tests/browser/programacion-semanal-legend-honesty.mjs --workers=1`

Anotar los cinco fondos.

- [ ] **Step 2: Hacer que el chip consuma el ancla**

Sustituir los `--ps-*-chip` por el matiz que el contrato asigna a cada clase, de modo que el chip de leyenda use el ancla y no una mezcla propia. El tinte tenue de la FILA se conserva: es una decisión de diseño registrada, porque aquí se tiñe la fila completa en una grilla densa.

- [ ] **Step 3: Correr los guards**

Run: `npx playwright test tests/browser/programacion-semanal-legend-honesty.mjs --workers=1`
Expected: PASS. Los dos tests siguen exigiendo que la leyenda comparta familia con su fila y que las muestras de la fase se distingan entre sí.

- [ ] **Step 4: Commit**

```bash
git add public/css/programacion-semanal.css
git commit -m "refactor(design-system): que el chip de Semanal use el ancla en vez de su mezcla"
```

---

### Task 6: Retirar el parche de fondo de /programa-general

**Files:**
- Modify: `public/css/programa-general.css`
- Modify: `tests/browser/programa-general-legend-hue.mjs`

General es el único cuyo chip **no lleva matiz**: los siete fondos son el mismo gris y el color vive solo en el punto.

- [ ] **Step 1: Reescribir el guard, que hoy no puede fallar**

`tests/browser/programa-general-legend-hue.mjs` asierta el punto contra una banda de matiz derivada del **nivel**, y a `sin-datos` lo declara acromático. Hoy **pasa en verde con cuatro desajustes presentes**. Reescribirlo para que compare el fondo del chip contra el matiz que el contrato asigna a ese estado, igual que hace `ops-state-chip-hue.mjs`.

- [ ] **Step 2: Correr y verificar que falla**

Run: `npx playwright test tests/browser/programa-general-legend-hue.mjs --workers=1`
Expected: FAIL — los siete chips pintan `#202c26` y el contrato les asigna siete matices distintos.

- [ ] **Step 3: Añadir `hue` a los siete estados de General en el contrato**

`docs/design-system/state-semantics.json`, módulo `programa-general`. Ya tienen `level`; falta `hue` y `key`.

- [ ] **Step 4: Hacer que el chip consuma `data-aia-hue`**

Emitir el atributo desde `views/programa-general/programa_general.view.php` y retirar el `background` de `.pg-page #pgLegend .pg-filter-chip`. El activo y el inactivo se distinguen con la clase `.inactive-filter`, que ya existe en el módulo.

- [ ] **Step 5: Correr y verificar que pasa**

Run: `npx playwright test tests/browser/programa-general-legend-hue.mjs --workers=1`
Expected: PASS.

- [ ] **Step 6: Medir el delta de la baseline y pedir aprobación visual**

Run: `npx playwright test tests/browser/programa-general.visual.mjs --workers=1`

Este módulo es el piloto protegido por `DESIGN.md`. Anotar el delta y **parar a pedir aprobación visual antes de continuar**. No regenerar.

- [ ] **Step 7: Commit**

```bash
git add public/css/programa-general.css docs/design-system/state-semantics.json
git add views/programa-general/programa_general.view.php tests/browser/programa-general-legend-hue.mjs
git commit -m "feat(design-system): que el chip de Programa General muestre su matiz"
```

---

### Task 7: Verificar login, BI y el resto de superficies

**Files:** ninguno por defecto. Solo se modifica lo que la verificación destape.

- [ ] **Step 1: Verificar el login con captura**

Es la única pantalla que se ve antes de autenticarse y es fácil que se rompa sin que nadie lo note.

```bash
npx playwright screenshot --viewport-size=1180,820 --color-scheme=dark \
  http://localhost:8081/login /tmp/login-tras-inversion.png
```

Revisar la captura. Comprobar que no hay parches de pastel claro.

- [ ] **Step 2: Verificar Control Tower**

Abrir `/bi/control-tower` a 1180×820 en dark y revisar sus superficies de estado. Sus tres sitios de riesgo se clasificaron en la Task 1; aquí se comprueba el resultado.

- [ ] **Step 3: Recorrer las cuatro rutas operativas**

`/pdc`, `/programacion-intermedia`, `/programacion-semanal` y `/programa-general`, a 1180×820 en dark con el proyecto Da Porto. Comprobar chips, filas y mensajes de `.aia-feedback`.

- [ ] **Step 4: Correr la suite completa**

```bash
npm run test:design-system:static
npm run test:design-system:runtime
```

Expected: los rojos preexistentes de la sección de constantes globales, y nada más.

- [ ] **Step 5: Commit de lo que la verificación destape**

Un commit por hallazgo, con su medición.

---

## Self-Review

**Cobertura de la especificación:**

| Requisito | Tarea |
|---|---|
| Clasificar los 39 descompensados | Task 1 |
| Superficies claras deliberadas como excepción declarada | Task 1, Step 3 |
| Derivar y medir el texto de `info` | Task 2, Step 1 |
| Invertir los cuatro pares | Task 2, Step 4 |
| Conservar los claros con nombre de documento | Task 2, Step 4 |
| Retirar los parches de módulo, uno por commit | Tasks 3–6 |
| Baseline de accesibilidad revisada, no regenerada | Task 2, Step 7 |
| Verificar login con captura | Task 7, Step 1 |
| BI en la verificación, no en el rediseño | Task 7, Step 2 |
| No regenerar baselines visuales | Constantes globales + Tasks 2 y 6 |

**Fuera de alcance, coherente con la especificación:** la convergencia del componente de chip (va después, como refactor que no debe mover un píxel) y la paleta de marca `--aia-*`.

**Un valor que este plan no puede fijar de antemano:** el texto de `info`. Aparece como `<el hex medido en el Step 1>` en dos sitios de la Task 2 a propósito — se deriva y se mide con el método que el Step 1 detalla, y se sustituye en ambos. Es una medición, no un placeholder.
