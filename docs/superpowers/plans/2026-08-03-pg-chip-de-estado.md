---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-03-pg-chip-de-estado.md
resumen: Que /programa-general distinga en pantalla los siete estados que su contrato declara, dándole el chip de estado que Programación Intermedia y Programación…
---

# Chip de estado de Programa General — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que `/programa-general` distinga en pantalla los siete estados que su contrato declara, dándole el chip de estado que Programación Intermedia y Programación Semanal ya tienen.

**Architecture:** No hay mecanismo nuevo. El sistema tiene dos canales ortogonales —el **nivel** se pinta en el fondo de fila, el **matiz** desempata dentro de un mismo nivel— y `states-feedback.css` ya traduce `[data-aia-hue][data-aia-severity]` a fondo por matiz con texto pareado. PI y PS lo consumen desde un `<span class="ops-state-chip">` dentro de la celda de estado. PG nunca recibió ese chip: su columna Estado es texto plano, así que *Actividad Futura* y *En Curso* —que comparten nivel `healthy`— se ven idénticas. Este plan replica el patrón de PI en PG.

**Tech Stack:** JavaScript ES5 en `public/js/modules/programa_general/hot.js` (el módulo usa `var` y funciones anónimas; respetar el estilo), Handsontable, CSS con `@layer` en `public/css/`, Node test runner para los guards estáticos, Playwright para navegador.

## Global Constraints

- **Alcance visual: desktop ≥1180 px y dark mode exclusivamente.** Viewport canónico: **1180×820**. Prohibido producir cambios, pruebas o evidencia para mobile, tablet o el tema `linen`. Si una tarea parece pedirlo, no se hace y se dice.
- **La sesión local se abre solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=test.A&p=Da%20Porto`. **Nunca teclear credenciales en `/login`** ni pedirle a una persona que entre.
- **Todo PHP y herramientas del proyecto se ejecutan dentro del contenedor `app`.** Nunca un PHP del host.
- **No se regeneran snapshots ni baselines para forzar verde.** La recaptura de goldens exige aprobación explícita del usuario, con el antes/después a la vista.
- **No tocar `programacion_intermedia` ni `programacion_semanal`.** No tienen defecto: su fondo compartido por nivel es el canal de nivel funcionando como se diseñó.
- **No cambiar qué nivel ni qué matiz le corresponde a cada estado.** Eso lo fija `docs/design-system/state-semantics.json` y es contrato.
- **No escribir una sonda de contraste propia.** Usar `tests/browser/support/contrast.mjs`, que rasteriza por canvas (obligatorio con `oklch()` y `color-mix()`) y compone alfa sobre los ancestros. Dos sesiones ya escribieron copias y tuvieron que sustituirlas. **Reserva conocida:** puede devolver 1:1 en elementos **en línea**; medir sobre elementos de bloque.
- **En comentarios de CSS, describir los colores con palabras, nunca con el literal.** `scripts/design-system-audit.mjs` cuenta hex y `rgba()` dentro de comentarios y rompe el presupuesto de la ruta (`memoria/trampas/audit-ve-color-en-comentarios.md`).
- **Estado de partida:** `npm run test:design-system:static` está en **363/363**. Cualquier rojo nuevo es de este trabajo. `npx playwright test tests/browser/programa-general.visual.mjs` **sí está en rojo a propósito** desde `96d9fd3`, y la Tarea 5 es quien lo cierra.

---

### Task 1: Tabla de presentación y guard de contrato

Añade a PG el mapa `estado → { level, hue }` y el emisor de atributos, y el guard que comprueba que no se desvíe del contrato. El guard se escribe **primero**: en la línea A de este mismo reparto, escribir el guard antes que el arreglo corrigió el censo por su cuenta.

**Files:**
- Modify: `tests/design-system/ops-state-contract.test.mjs` (añadir dos tests al final, espejo de los de Intermedia en las líneas 27-73)
- Modify: `public/js/modules/programa_general/hot.js` (añadir cerca de `classifyPGRow`, que empieza en la línea 751)

**Interfaces:**
- Consumes: `docs/design-system/state-semantics.json`, módulo `programa-general`, que ya declara los siete estados con `key`, `level` y `hue`. No se modifica.
- Produces: `statePresentation` (objeto ES5 en el scope del módulo) y `stateChipAttrs(state)` → `String` con los tres atributos HTML precedidos de espacio, o `''` si el estado no está en el mapa. La Tarea 2 llama a `stateChipAttrs`.

- [ ] **Step 1: Leer el guard existente para copiar su forma**

Lee `tests/design-system/ops-state-contract.test.mjs` líneas 1-73 completas. Fíjate en `parsePresentation()`, que extrae el objeto `statePresentation` del texto del `hot.js`. Tu test de PG debe usar esa misma función.

- [ ] **Step 2: Escribir los dos tests que fallan**

Añade al final de `tests/design-system/ops-state-contract.test.mjs`:

```javascript
test('la tabla de presentación de Programa General proyecta el contrato', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const module = semantics.moduleMappings.find((m) => m.module === 'programa-general');
  const presentation = parsePresentation(
    await read('public/js/modules/programa_general/hot.js'),
  );

  for (const state of module.states) {
    assert.ok(state.key, `el contrato no declara \`key\` para «${state.label}»`);
    const declared = presentation[state.key];
    assert.ok(declared, `el módulo no presenta el estado \`${state.key}\` del contrato`);
    assert.deepEqual(
      declared,
      { level: state.level, hue: state.hue },
      `\`${state.key}\` («${state.label}») difiere entre el módulo y el contrato`,
    );
  }

  const extra = Object.keys(presentation)
    .filter((key) => key !== 'neutral' && !module.states.some((s) => s.key === key));
  assert.deepEqual(extra, [], `el módulo presenta estados que el contrato no declara: ${extra}`);
});
```

Y a continuación el segundo, que es el que impide recaer. El de PI existe porque ese módulo ya
cometió el error de declarar `background` en su propia hoja y dejar inerte la primitiva:

```javascript
test('la hoja de Programa General no vuelve a pintar el chip por nombre de estado', async () => {
  const css = await read('public/css/design-system/components/ops-state-chip.css');

  // El fondo del chip es del design system. Si alguien lo declara otra vez en la
  // forma del chip, la primitiva que pinta por matiz se vuelve inerte.
  const chipBlock = css.match(/\.ops-state-chip \{([^}]*)\}/)?.[1] ?? '';
  assert.ok(chipBlock, 'no se encontró la regla base de .ops-state-chip');
  assert.doesNotMatch(
    chipBlock,
    /(^|[\s;])background(-color)?\s*:/,
    '.ops-state-chip volvió a declarar `background`; eso tapa la primitiva del DS',
  );

  const modulo = await read('public/css/programa-general.css');
  const perState = modulo.match(/\.pg-state-[\w-]+ \.ops-state-chip \{[^}]*(background|color|border-color)[^}]*\}/g) ?? [];
  assert.deepEqual(
    perState,
    [],
    `${perState.length} regla(s) vuelven a colorear el chip por nombre de estado`,
  );
});
```

**Este segundo test depende de archivos que la Tarea 3 crea**, así que fallará por archivo
inexistente hasta entonces. Es correcto: el orden es escribir el guard, verlo fallar, y que la
implementación lo apague.

- [ ] **Step 3: Correr los tests para verificar que fallan**

Run: `node --test tests/design-system/ops-state-contract.test.mjs`
Expected: FAIL. `parsePresentation` no encuentra `statePresentation` en el `hot.js` de PG, así que `presentation[state.key]` es `undefined` y salta el primer `assert.ok(declared, ...)` con «el módulo no presenta el estado `actividad-futura` del contrato».

- [ ] **Step 4: Añadir la tabla de presentación y el emisor**

En `public/js/modules/programa_general/hot.js`, inmediatamente **antes** de `function classifyPGRow(data) {` (línea 751), inserta:

```javascript
  // Guard de que esta tabla no se desvie del contrato:
  // tests/design-system/ops-state-contract.test.mjs
  var LEVEL_ATTRS = {
    neutral: { severity: 'none', urgency: 'none' },
    healthy: { severity: 'low', urgency: 'none' },
    attention: { severity: 'medium', urgency: 'soon' },
    urgent: { severity: 'high', urgency: 'now' },
  };

  // Siete estados, siete matices, sin repetir. Los valores salen de
  // docs/design-system/state-semantics.json y no se eligen aqui: el matiz es el
  // eje que desempata dentro de un mismo nivel. `actividad-futura` y `en-curso`
  // comparten nivel `healthy`, asi que sin matiz se pintan identicas -que es el
  // defecto que este chip viene a corregir-.
  var statePresentation = {
    'actividad-futura': { level: 'healthy', hue: 'green' },
    'en-curso': { level: 'healthy', hue: 'blue' },
    terminada: { level: 'healthy', hue: 'neutral' },
    'con-alerta-restricciones': { level: 'attention', hue: 'amber' },
    'debe-iniciar': { level: 'attention', hue: 'orange' },
    atrasada: { level: 'urgent', hue: 'red' },
    'sin-datos': { level: 'neutral', hue: 'violet' },
  };

  function stateChipAttrs(state) {
    var presentation = statePresentation[state];
    if (!presentation) {
      return '';
    }
    var pair = LEVEL_ATTRS[presentation.level];
    return ' data-aia-hue="' + presentation.hue + '"'
      + ' data-aia-severity="' + pair.severity + '"'
      + ' data-aia-urgency="' + pair.urgency + '"';
  }
```

- [ ] **Step 5: Correr los tests**

Run: `node --test tests/design-system/ops-state-contract.test.mjs`
Expected: el **primero PASA**. El segundo **sigue fallando** por archivo inexistente, y lo apaga la Tarea 3. No lo silencies ni lo borres.

- [ ] **Step 6: Correr la suite estática entera**

Run: `npm run test:design-system:static`
Expected: **1 fail** — el segundo test de la Tarea 1, a propósito, hasta que la Tarea 3 cree el componente. Cualquier **otro** rojo sí es tuyo.

- [ ] **Step 7: Commit**

```bash
git add tests/design-system/ops-state-contract.test.mjs public/js/modules/programa_general/hot.js
git commit -m "feat(programa-general): tabla de presentacion de estado proyectada del contrato

Los siete estados de PG declaran nivel y matiz, con el mismo guard que ya
vigila Intermedia y Semanal. El matiz es el eje que desempata dentro de un
mismo nivel: actividad-futura y en-curso son ambos healthy, asi que sin el se
pintan identicos.

Sin efecto visible todavia: la Tarea 2 es quien lo consume."
```

---

### Task 2: El chip en la celda de estado

Sustituye el texto plano de la columna Estado por el chip que declara los dos ejes.

**Files:**
- Modify: `public/js/modules/programa_general/hot.js` (registro de renderers cerca de la línea 1569; definición de columna en la línea 2855)

**Interfaces:**
- Consumes: `stateChipAttrs(state)` de la Tarea 1, y `classifyPGRow(rowData)` que ya existe (línea 751) y devuelve `{ key, baseKey, rowClass, isCritical, restrictionAlertKey }`.
- Produces: renderer `pgStateChipRenderer` registrado en Handsontable. La Tarea 3 le da estilo y la Tarea 4 lo mide.

- [ ] **Step 1: Entender la precedencia que ya existe**

Lee `createMobileStateBadge` en `public/js/modules/programa_general/hot.js:1792`. Fíjate en `displayState`: **si `classification.restrictionAlertKey` tiene valor, el estado mostrado es «Con Alerta Restricciones»; si no, el propio `Estado` de la fila.** El chip debe respetar exactamente esa precedencia, o la grilla y la leyenda dirán cosas distintas.

Esa función es de mobile y **no se toca** — está fuera del alcance visual del repositorio. Se lee solo para copiar la regla de precedencia.

- [ ] **Step 2: Registrar el renderer del chip**

En `public/js/modules/programa_general/hot.js`, justo después del bloque de `pgGenericTextRenderer` (línea 1569-1571), inserta:

```javascript
    Handsontable.renderers.registerRenderer('pgStateChipRenderer', function (instance, td, row, col, prop, value, cellProperties) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      var rowData = instance.getSourceDataAtRow(instance.toPhysicalRow(row));
      var classification = rowData ? classifyPGRow(rowData) : null;
      if (!classification) {
        return;
      }
      // Misma precedencia que la insignia de estado: una fila con alerta de
      // restricciones se anuncia como tal, no por su estado de avance.
      var stateKey = classification.restrictionAlertKey
        ? 'con-alerta-restricciones'
        : classification.key;
      var attrs = stateChipAttrs(stateKey);
      if (!attrs) {
        return;
      }
      var label = classification.restrictionAlertKey
        ? 'Con Alerta Restricciones'
        : (value === null || value === undefined || value === '' ? 'Sin datos' : String(value));
      td.innerHTML = '<span class="ops-state-chip"' + attrs + '>' + escapeHtml(label) + '</span>';
      td.classList.add('ops-state-td');
    });
```

- [ ] **Step 3: Comprobar que `escapeHtml` existe en este módulo**

Run: `grep -n "function escapeHtml" public/js/modules/programa_general/hot.js`
Expected: una línea con la definición. **Si no existe**, añade antes del renderer:

```javascript
    function escapeHtml(text) {
      return String(text).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
      });
    }
```

- [ ] **Step 4: Apuntar la columna Estado al renderer nuevo**

En `public/js/modules/programa_general/hot.js:2855`, cambia:

```javascript
        { data: 'Estado', readOnly: true, renderer: 'pgGenericTextRenderer', className: 'htCenter htMiddle force-wrap' },
```

por:

```javascript
        { data: 'Estado', readOnly: true, renderer: 'pgStateChipRenderer', className: 'htCenter htMiddle force-wrap' },
```

- [ ] **Step 5: Verificar en navegador que los atributos llegan al DOM**

Abre `http://localhost:8081/dev/entrar?u=test.A&p=Da%20Porto`, redimensiona a **1180×820 dark**, navega a `/programa-general` y ejecuta:

```javascript
(function(){
  const chips = [...document.querySelectorAll('#hot-container .ops-state-chip')];
  const porMatiz = {};
  chips.forEach(ch => {
    const h = ch.getAttribute('data-aia-hue');
    porMatiz[h] = (porMatiz[h] || 0) + 1;
  });
  return JSON.stringify({ chips: chips.length, porMatiz }, null, 1);
})()
```

Expected: `chips` > 0 y al menos dos matices distintos en `porMatiz`. **Si `chips` es 0**, comprueba antes que `window.innerWidth` sea 1180 y no 0: un panel colapsado hace que `#hot-container` quede en `display:none` y no pinte ninguna celda.

- [ ] **Step 6: Commit**

```bash
git add public/js/modules/programa_general/hot.js
git commit -m "feat(programa-general): la celda de estado pinta un chip con sus dos ejes

La columna Estado pasa de texto plano a un chip que declara matiz, severidad y
urgencia, como ya hacen Intermedia y Semanal. Respeta la precedencia que ya
usaba la insignia de estado: una fila con alerta de restricciones se anuncia
como tal y no por su estado de avance.

Sin estilo todavia: la Tarea 3 le da la forma."
```

---

### Task 3: El estilo del chip, como componente compartido

**Files:**
- Create: `public/css/design-system/components/ops-state-chip.css`
- Modify: `public/css/design-system/entrypoints/core.css` (añadir el `@import` junto a los demás componentes)

**Interfaces:**
- Consumes: el marcado `<span class="ops-state-chip">` de la Tarea 2.
- Produces: la clase `.ops-state-chip` disponible en cualquier superficie que cargue el núcleo del design system.

**Por qué componente compartido y no una tercera copia:** hoy `.ops-state-chip` está duplicado bajo `.pi-page` (`programacion-intermedia.css:249`) y `.ps-page`. Una tercera copia bajo `.pg-page` haría tres. El componente compartido no toca a PI ni a PS —sus reglas, más específicas, siguen ganando sobre sus propias páginas— así que PG lo consume sin riesgo para ellos, y queda el camino abierto para que suelten su copia en otro trabajo.

- [ ] **Step 1: Crear el componente**

Crea `public/css/design-system/components/ops-state-chip.css`:

```css
@layer components {
  /* Chip de estado operativo: el marcado declara su matiz y su nivel, y la
     primitiva de states-feedback.css los traduce a fondo y texto. Aqui va solo
     la forma.

     SIN `background` A PROPOSITO. El fondo lo pinta la capa de componentes
     desde `data-aia-hue`. Si este bloque lo declarara, ganaria siempre —`module`
     va despues de `components` en el orden de capas— y la primitiva quedaria
     inerte. Es el error que Intermedia ya cometio y corrigio. */
  .ops-state-chip {
    display: block;
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: var(--ds-space-1) var(--ds-space-2);
    border: 1px solid var(--ds-active-border);
    border-radius: var(--ds-radius-md);
    color: inherit;
    font-weight: 900;
    font-size: 0.78rem;
    line-height: 1.18;
    overflow: visible;
    overflow-wrap: anywhere;
    white-space: normal;
    word-break: normal;
  }
}
```

- [ ] **Step 2: Enlazarlo desde el núcleo**

En `public/css/design-system/entrypoints/core.css`, después de la línea 11
(`states-feedback.css`, que es la primitiva que pinta este chip) y antes de la 12
(`data-display.css`), inserta:

```css
@import url("/css/design-system/components/ops-state-chip.css?v=1.0.0");
```

El orden importa: no es alfabético sino de dependencia, y el chip va después de la primitiva que
le da color. El `?v=1.0.0` es cache-busting y todas las líneas de ese bloque lo llevan.

- [ ] **Step 3: Verificar en navegador que el chip toma color por matiz**

Recarga `/programa-general` a 1180×820 dark y ejecuta:

```javascript
(function(){
  const c = document.createElement('canvas'); c.width = c.height = 1;
  const cx = c.getContext('2d', { willReadFrequently: true });
  const rgb = (v) => { cx.clearRect(0,0,1,1); cx.fillStyle='#000'; cx.fillStyle=v; cx.fillRect(0,0,1,1);
    const d = cx.getImageData(0,0,1,1).data; return `${d[0]},${d[1]},${d[2]}`; };
  const porMatiz = {};
  document.querySelectorAll('#hot-container .ops-state-chip').forEach(ch => {
    porMatiz[ch.getAttribute('data-aia-hue')] = rgb(getComputedStyle(ch).backgroundColor);
  });
  return JSON.stringify(porMatiz, null, 1);
})()
```

Expected: cada matiz con un fondo **distinto**. `green` debe dar `23,61,38`. Si todos salen iguales o transparentes, el `@import` no entró o algo declara `background` sobre `.ops-state-chip`.

- [ ] **Step 4: Apagar el rojo que dejó la Tarea 1**

Run: `node --test tests/design-system/ops-state-contract.test.mjs`
Expected: **los dos tests de PG en verde**. El segundo fallaba por archivo inexistente y ahora
encuentra `ops-state-chip.css`. Si sigue rojo por `background`, es que declaraste fondo en el
componente — quítalo: lo pinta la primitiva.

- [ ] **Step 5: Correr los gates de CSS**

Run: `npm run test:design-system:static`
Expected: **0 fail**. Aquí sí vuelve al verde completo.

Run: `node scripts/design-system-entrypoint-partition.mjs`
Expected: verde. Si se queja, el `@import` está en el entrypoint equivocado.

- [ ] **Step 6: Commit**

```bash
git add public/css/design-system/components/ops-state-chip.css public/css/design-system/entrypoints/core.css
git commit -m "feat(design-system): el chip de estado operativo pasa a componente compartido

Estaba duplicado bajo .pi-page y .ps-page. En vez de una tercera copia para
Programa General, la forma sube a componente del nucleo. No toca a Intermedia ni
a Semanal: sus reglas mas especificas siguen ganando sobre sus paginas, y queda
el camino abierto para que suelten su copia.

Sin background a proposito: lo pinta la primitiva desde data-aia-hue."
```

---

### Task 4: El guard que cruza contrato y píxel

El guard estático de la Tarea 1 compara dos archivos. Este comprueba que el **color resuelto en el navegador** sea el que el contrato declara — que es lo que ningún guard hacía, y por eso el defecto vivió sin que nadie lo viera.

**Files:**
- Create: `tests/browser/programa-general-state-hue.mjs`
- Modify: `.gitignore` (registrar el test en la lista blanca de `tests/browser/`)
- Modify: `package.json` (añadirlo a `test:design-system:runtime`)

**Interfaces:**
- Consumes: `installContrastProbe` y `measure` de `tests/browser/support/contrast.mjs`; `loginAndSelectProject` y `changeWeek` de `tests/browser/support/session.mjs`; `PROJECTS` de `tests/browser/fixtures/projects.mjs`.
- Produces: nada que otra tarea consuma.

- [ ] **Step 1: Escribir el test**

Crea `tests/browser/programa-general-state-hue.mjs`:

```javascript
// Cruza el contrato con el PIXEL, que es lo que ningun guard hacia.
//
// `state-tint-ladder.test.mjs:170` comprueba que ningun modulo repita matiz
// recorriendo `semantics.moduleMappings` — es decir, leyendo el JSON contra si
// mismo. Una declaracion validandose a si misma esta verde por construccion, y
// por eso «Actividad Futura» y «En Curso» llevaron dias pintandose identicas
// mientras el contrato declaraba matices distintos.
//
// Necesita filas reales: sobre una grilla vacia este test se quedaria verde sin
// haber medido nada, que es exactamente el fallo que viene a corregir.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { changeWeek, loginAndSelectProject, logout } from './support/session.mjs';
import { installContrastProbe, measure } from './support/contrast.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const AA_MIN = 4.5;

const SEMANTICS = JSON.parse(readFileSync(
  fileURLToPath(new URL('../../docs/design-system/state-semantics.json', import.meta.url)),
  'utf8',
));
const PG_STATES = SEMANTICS.moduleMappings.find((m) => m.module === 'programa-general').states;

// Las mismas siete filas del mock de la prueba visual, para que ambas midan lo
// mismo. Sin `Consecutivo` ni `Id` la fila cae a `sin-datos`; `Titulo` distinto
// de 0 se clasificaria como cabecera.
const FILAS = [
  { Id: 1, Consecutivo: 1, Titulo: 0, Actividad: 'Cimentacion eje 4', Estado: 'Terminada', Ruta_Critica: '0' },
  { Id: 2, Consecutivo: 2, Titulo: 0, Actividad: 'Muros nivel 2', Estado: 'En curso', Ruta_Critica: '0' },
  { Id: 3, Consecutivo: 3, Titulo: 0, Actividad: 'Redes', Estado: 'Actividad futura', Ruta_Critica: '0' },
  { Id: 4, Consecutivo: 4, Titulo: 0, Actividad: 'Electrica', Estado: 'Debe iniciar', Ruta_Critica: '0' },
  { Id: 5, Consecutivo: 5, Titulo: 0, Actividad: 'Losa nivel 3', Estado: 'Atrasada', Ruta_Critica: '0' },
  { Titulo: 0, Actividad: 'Cubierta', Estado: '', Ruta_Critica: '0' },
];

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('cada matiz declarado se pinta distinto y legible', async ({ page }) => {
  await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":false}' }));
  await page.route('**/api/general/codigos**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":true,"data":[]}' }));
  await page.route('**/programa-general/filtros', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":true,"data":{}}' }));
  await page.route('**/api/general/list**', (r) => r.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }),
  }));

  await installContrastProbe(page);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await changeWeek(page, PROJECTS[0].maxWeek, '/programa-general');
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => document.querySelectorAll('#hot-container .ops-state-chip').length > 0, null, { timeout: 20000 });

  const fondos = await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 1; canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const srgb = (value) => {
      ctx.clearRect(0, 0, 1, 1); ctx.fillStyle = '#000'; ctx.fillStyle = value; ctx.fillRect(0, 0, 1, 1);
      const d = ctx.getImageData(0, 0, 1, 1).data;
      return `${d[0]},${d[1]},${d[2]}`;
    };
    const out = {};
    for (const chip of document.querySelectorAll('#hot-container .ops-state-chip')) {
      out[chip.getAttribute('data-aia-hue')] = srgb(getComputedStyle(chip).backgroundColor);
    }
    return out;
  });

  const matices = Object.keys(fondos);
  expect(matices.length, 'no se pinto ningun chip: revisa que la grilla traiga filas').toBeGreaterThan(1);

  // Dos matices distintos no pueden resolver al mismo pixel: ese es el defecto.
  const colisiones = [];
  for (const a of matices) {
    for (const b of matices) {
      if (a < b && fondos[a] === fondos[b]) colisiones.push(`${a} y ${b} pintan ${fondos[a]}`);
    }
  }
  expect(colisiones, `matices distintos con el mismo color:\n${colisiones.join('\n')}`).toEqual([]);

  // Y cada matiz visible tiene que estar declarado en el contrato.
  const declarados = new Set(PG_STATES.map((s) => s.hue));
  const intrusos = matices.filter((h) => !declarados.has(h));
  expect(intrusos, `matices que el contrato no declara: ${intrusos}`).toEqual([]);

  const bajos = [];
  for (const hue of matices) {
    const medida = await measure(page, `#hot-container .ops-state-chip[data-aia-hue="${hue}"]`);
    if (!medida || typeof medida.ratio !== 'number') { bajos.push(`${hue}: la sonda no pudo medir`); continue; }
    if (medida.ratio < AA_MIN) bajos.push(`${hue}: ${medida.ratio.toFixed(2)}:1`);
  }
  expect(bajos, `chips bajo AA:\n${bajos.join('\n')}`).toEqual([]);

  await logout(page);
});
```

- [ ] **Step 2: Registrar el test en la lista blanca**

`tests/browser/*` está ignorado con lista blanca: **un test nuevo ahí no se commitea si no lo registras** (`memoria/trampas/tests-browser-allowlist.md`).

Añade en `.gitignore`, junto a las demás excepciones de `tests/browser/`:

```
!tests/browser/programa-general-state-hue.mjs
```

Run: `git check-ignore -v tests/browser/programa-general-state-hue.mjs`
Expected: imprime la línea de la **excepción** (la que empieza por `!`), no una de exclusión.

- [ ] **Step 3: Correr el test**

Run: `npx playwright test tests/browser/programa-general-state-hue.mjs --workers=1`
Expected: PASS.

**Si falla por colisión**, no lo silencies: significa que dos estados con matices distintos siguen pintándose igual, y es el defecto original sin corregir. Vuelve a la Tarea 2 o 3.

- [ ] **Step 4: Enrutarlo**

En `package.json`, añade el archivo a la lista de `test:design-system:runtime`, junto a `design-system-table-contract.runtime.mjs`.

Run: `node -e "console.log(require('./package.json').scripts['test:design-system:runtime'].includes('programa-general-state-hue'))"`
Expected: `true`.

- [ ] **Step 5: Commit**

```bash
git add tests/browser/programa-general-state-hue.mjs .gitignore package.json
git commit -m "test(programa-general): guard que cruza el contrato con el pixel

state-tint-ladder.test.mjs comprueba que ningun modulo repita matiz recorriendo
state-semantics.json contra si mismo. Una declaracion validandose a si misma
esta verde por construccion, y por eso «Actividad Futura» y «En Curso» llevaron
dias pintandose identicas mientras el contrato les declaraba matices distintos.

Este mide el color RESUELTO en navegador, con filas reales: sobre una grilla
vacia se quedaria verde sin haber medido nada."
```

---

### Task 5: Cerrar el rojo visual

`test:visual:pilot` está en rojo desde `96d9fd3` esperando este momento.

**Files:**
- Modify: `tests/browser/__screenshots__/programa-general.visual.mjs/*.png` (solo si el usuario aprueba)

- [ ] **Step 1: Generar el después sin recapturar**

Run: `npx playwright test tests/browser/programa-general.visual.mjs --workers=1`
Expected: FAIL en `1180x820` con un porcentaje de píxeles distintos. Playwright deja la imagen nueva en `test-output/.../programa-general-dark-1180x820-actual.png`.

**Ojo con el de 1440×900:** puede pasar en verde aunque el cambio sea real. La tolerancia es del 3 % y el fondo oscuro domina la imagen — ya ocurrió con este mismo cambio. Que pase no prueba que esté al día.

- [ ] **Step 2: Enseñar el antes y el después al usuario**

Copia el golden actual y la imagen nueva a un directorio de trabajo y muéstraselos. **Espera su aprobación explícita.** No sigas sin ella: `DESIGN.md` lo exige y el usuario ya rechazó una recaptura este mismo día por consagrar una regresión.

- [ ] **Step 3: Recapturar, solo con el sí**

Run: `npx playwright test tests/browser/programa-general.visual.mjs --workers=1 --update-snapshots=all`

**`all` y no el modo por defecto:** `changed` no reescribe si el diff cae dentro de la tolerancia, así que dejaría desactualizado el de 1440×900 (`memoria/trampas/gate-visual-tolerancia-enganosa.md`).

- [ ] **Step 4: Comprobar qué PNG cambiaron y que el delta sea el tuyo**

Run: `git status --short tests/browser/__screenshots__/`

Mira cada imagen antes de quedártela. Si cambió algo que no es el chip —el ancho del rail, el alto de las filas—, **revierte con `git checkout -- tests/browser/__screenshots__/` y averigua qué se coló.** Recapturar con `all` hornea en la baseline cualquier deriva ajena con tu firma encima.

- [ ] **Step 5: Actualizar los sha256 del manifiesto**

Los goldens llevan `sha256` en `docs/design-system/manifests/programa-general.json` y hay que recalcularlos, o el gate de contratos falla (`memoria/trampas/manifiesto-ds-exige-golden.md`).

Run: `node scripts/design-system-contracts.mjs`
Expected: verde. Si reporta `hash mismatch`, recalcula el sha256 del PNG afectado y actualízalo en el manifiesto.

- [ ] **Step 6: Verificación final**

Run: `npm run test:design-system:static`
Expected: **363 pass, 0 fail** (o más, con los tests nuevos).

Run: `npx playwright test tests/browser/programa-general.visual.mjs tests/browser/programa-general-state-hue.mjs --workers=1`
Expected: todo PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/browser/__screenshots__/ docs/design-system/manifests/programa-general.json
git commit -m "test(visual): los goldens de Programa General retratan los siete estados

Cierra el rojo deliberado de 96d9fd3. Las baselines pasan de una grilla vacia a
siete filas con sus siete matices distintos, aprobado por el usuario con el
antes/despues delante.

Recapturado con --update-snapshots=all: el modo por defecto no reescribe si el
diff cae dentro del 3% de tolerancia, y el golden de 1440x900 se colaba por ahi."
```

---

## Verificación de cierre

Contra la condición de hecho de `goals/pg-chip-de-estado/goal.md`:

1. **Los siete estados se distinguen** — Tarea 4, assert de colisiones.
2. **El matiz coincide con el contrato** — Tarea 1 (estático) y Tarea 4 (píxel).
3. **Cada chip cumple AA** — Tarea 4, con la sonda compartida.
4. **Leyenda y grilla concuerdan** — Tarea 2, precedencia de `restrictionAlertKey`.
5. **Goldens recapturados con aprobación** — Tarea 5.

## Lo que este plan NO hace

- No toca PI ni PS. No tienen defecto.
- No cambia la asignación de nivel ni matiz de ningún estado: la fija el contrato.
- No retira las copias de `.ops-state-chip` de `.pi-page` y `.ps-page`. Queda como trabajo aparte.
- No toca `createMobileStateBadge` ni nada de mobile.
