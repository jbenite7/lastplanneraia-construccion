---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-21
areas: [design-system, lps]
fuente: docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna.md
resumen: "Plan de once tareas para fundir las restricciones de Programación Intermedia en una columna de cuadritos con globo de liberación, y alinear los contadores de leyenda con el color de su estado"
---

# Habilitación en una columna — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development
> (recomendado) o superpowers:executing-plans para ejecutar este plan tarea por tarea. Los pasos
> usan casillas (`- [ ]`) para el seguimiento.

**Goal:** que `/programacion-intermedia` quepa a 1100 px sin barra horizontal y sin esconder texto,
fundiendo las columnas de restricción en una sola de cuadritos que se liberan desde un globo anclado
a la fila.

**Architecture:** una columna nueva `__habilitacion` reemplaza las N columnas de restricción y la de
`Estado_Restricciones`. Su renderer dibuja un cuadrito por restricción, alimentado por un módulo
puro y testeable (`readiness-cell.js`) que decide relleno, umbral y `N/A`. La edición se mueve a un
globo en el top-layer (Popover API), reutilizando la maquinaria ya probada de `state-tooltip.js` de
este mismo frente. El guardado, el cálculo y el modal de restricción compartida **no se tocan**:
esta obra mueve dónde se editan las restricciones, no qué significan.

**Tech Stack:** Handsontable (renderers, `columnMinWidths`/`Floor`/`Max`, `filters`), Popover API +
CSS anchor positioning, tokens `--ds-*` en `@layer`, `node --test` para las pruebas estáticas y
Playwright para las de navegador.

**Spec:** `docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design.md` (v2, aprobada
por Felipe el 2026-08-21). El plan argumenta desde ella; quien ejecute lee las dos.

## Global Constraints

- **No tocar los hex de `--ds-state-tint-*`.** Esta obra cambia **qué familia consume** la leyenda,
  no los valores de los tintes.
- **No regenerar goldens sin aprobación visual explícita de Felipe**, pedida por su nombre.
- **No cambiar lo que mide una prueba para que pase.** Si una prueba estorba, se declara qué mide
  ahora y por qué.
- **Dark es el tema por defecto y el único que se valida.** Viewport canónico `1180x820`; el
  presupuesto de ancho se mide además a **1100**.
- **Cero hex, cero estilos inline y cero variantes locales** en el CSS nuevo: tokens `--ds-*` dentro
  de `@layer module` o de la capa canónica que corresponda. Dos `@media` fuera de `@layer` bastan
  para que la hoja entera se entregue sin capa — lo caza `unlayered-delivery`.
- **Ancho disponible a 1100: 1040 px.** Presupuesto de la columna nueva: **130 px** (114 útiles).
- **Contraste mínimo 4.5:1** en texto y **3:1** en no-texto, sobre valores **computados** en el
  navegador, nunca sobre el CSS declarado.
- **La prueba la escribe un paso distinto del que implementa** (regla del repo, medida: la detección
  de fallos cae del 25 % al 14 % cuando el mismo modelo hace ambas). En este plan eso significa que
  el paso «escribe la prueba» y el paso «implementa» no se funden aunque parezca más rápido.
- **Sesión local siempre por `/dev/entrar`**, nunca tecleando credenciales en `/login`.
- **El contenedor debe montar el árbol que se verifica** (`LPS_CODE_ROOT="$(pwd)" docker compose up
  -d app`) **y devolverse a la raíz al terminar**. Ver
  `memoria/trampas/env-enlazado-se-rompe-dentro-del-contenedor.md`: en un worktree hace falta además
  un `.env` legible **dentro** del árbol montado, y `composer install` dentro del contenedor.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `public/js/design-system/readiness-cell.js` (crear) | Módulo **puro**: dado el valor de una restricción y su umbral, devuelve `{ relleno, cumple, esNoAplica }`. Sin DOM, sin Handsontable. Es lo que hace testeable la regla del color. |
| `public/css/design-system/components/readiness-squares.css` (crear) | La primitiva visual del cuadrito: relleno, visto, tachado, `+N`. Sin nada de Intermedia. |
| `public/css/design-system/components/readiness-popover.css` (crear) | El globo: caja, anclaje, volteo, grupos, fila de error. |
| `public/js/design-system/readiness-popover.js` (crear) | El globo: apertura, cierre, foco, teclado, flechas, render de su contenido. Consume `readiness-cell.js`. |
| `public/js/modules/programacion_intermedia/hot.js` (modificar) | Columnas, renderer de la celda, anchos, filtro de cabecera, enganche del globo. |
| `views/programacion-intermedia/programacion_intermedia.view.php` (modificar) | Nada del markup de leyenda cambia; solo se verifica que no sobre nada. |
| `public/css/styles.css` (modificar, `3780-3810`) | Los items de leyenda de PI pasan de tinte a sólido. |
| `public/css/programa-general.css` (modificar) | Lo mismo para PG. |
| `tests/design-system/readiness-cell.test.mjs` (crear) | Reglas del cuadrito: umbral, relleno, `N/A`. |
| `tests/design-system/legend-solid-contract.test.mjs` (crear) | La leyenda consume la familia sólida, no la de tintes. |
| `tests/browser/pi-ancho-presupuesto.mjs` (crear) | El guardián del ancho a 1100. |

---

### Task 1: los contadores de la leyenda toman el color de su estado

Va primera porque es **independiente de todo lo demás** y entrega valor sola: si el resto del plan
se detuviera aquí, la queja original queda resuelta.

**Files:**
- Modify: `public/css/styles.css:3775-3812`
- Modify: `public/css/programa-general.css`
- Test: `tests/design-system/legend-solid-contract.test.mjs` (crear)

**Interfaces:**
- Consumes: los tokens `--ds-state-solid-*` y `--ds-state-solid-*-text` ya definidos en
  `public/css/tokens.css:353-370`.
- Produces: nada que otras tareas consuman.

**El dato del diagnóstico, para que quien ejecute no lo repita:** los items de leyenda de PI se
colorean con variables locales `--pi-*-bg` (`styles.css:3780+`) que a su vez apuntan a
`--ds-state-tint-*` (`programacion-intermedia.css:94-100`). Los chips de estado de la tabla usan
`--ds-state-solid-*`. Por eso la leyenda se ve apagada y no se parece a lo que describe. Semanal ya
lo resolvió en `programacion-semanal.css:3683`.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/design-system/legend-solid-contract.test.mjs`:

```js
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// La leyenda existe para describir la tabla. Si consume otra familia de tokens
// que los chips que describe, deja de describirla: ese fue el defecto que
// reporto el usuario con captura el 2026-08-21.
const ESTADOS_PI = [
  'blocked-overdue-critical', 'blocked-overdue', 'blocked-due',
  'alert-1-week', 'alert-2-3-weeks', 'alert-4-6-weeks',
  'execution-blocked', 'liberated-control',
];

test('cada item de leyenda de PI declara un fondo de la familia solida', async () => {
  const css = await read('public/css/styles.css');
  for (const estado of ESTADOS_PI) {
    const regla = new RegExp(
      `\\.pi-legend \\.pdc-legend-item\\.${estado}\\s*\\{[^}]*\\}`, 'm');
    const bloque = css.match(regla);
    assert.ok(bloque, `no hay regla de leyenda para ${estado}`);
    assert.match(bloque[0], /--ds-state-solid-/,
      `la leyenda de ${estado} no usa la familia solida`);
    assert.doesNotMatch(bloque[0], /--ds-state-tint-/,
      `la leyenda de ${estado} sigue usando la familia de tintes`);
  }
});
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node --test tests/design-system/legend-solid-contract.test.mjs
```

Esperado: FAIL en el primer estado, con «la leyenda de blocked-overdue-critical no usa la familia
solida».

- [ ] **Step 3: Implementa el cambio en PI**

En `public/css/styles.css`, cada uno de los ocho bloques
`.pi-page .pi-legend .pdc-legend-item.<estado>` pasa a nombrar directamente el token sólido del hue
que le corresponde según `docs/design-system/state-semantics.json`. Ejemplo del primero:

```css
.pi-page .pi-legend .pdc-legend-item.blocked-overdue-critical {
  background-color: var(--ds-state-solid-red);
  color: var(--ds-state-solid-red-text);
  border-color: var(--ds-state-solid-red);
}
```

Los hues salen del contrato, no de la vista: leer `moduleMappings` de
`docs/design-system/state-semantics.json` para saber qué hue le toca a cada estado. **No inventar
la correspondencia** — si un estado no está mapeado, el contrato es lo que hay que arreglar primero.

- [ ] **Step 4: Córrela y confirma que pasa**

```bash
node --test tests/design-system/legend-solid-contract.test.mjs
```

Esperado: PASS.

- [ ] **Step 5: Lo mismo en Programa General**

Repetir en `public/css/programa-general.css` para los estados de PG, y **añadir su bloque de
aserciones al mismo archivo de prueba** con la lista de estados de PG leída de `moduleMappings`.
Vuelve a correr la prueba: PASS.

- [ ] **Step 6: Verifica en pantalla que el contraste computado cumple**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

Con la sonda `goals/replanteo-coloreado-estados/evidence/diag-contadores-2.mjs`, comprobar sobre
valores **computados** que cada contador tiene el mismo fondo que el chip de su estado y que el par
fondo/texto pasa 4.5:1. Si algún par no llega, **no se ajusta el hex**: se reporta, porque los
sólidos ya están auditados y un fallo ahí es un defecto del contrato.

- [ ] **Step 7: Commit**

```bash
git add public/css/styles.css public/css/programa-general.css tests/design-system/legend-solid-contract.test.mjs
git commit -m "fix(leyenda): los contadores toman el color solido de su estado"
```

---

### Task 2: el módulo puro que decide qué muestra un cuadrito

**Files:**
- Create: `public/js/design-system/readiness-cell.js`
- Test: `tests/design-system/readiness-cell.test.mjs`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `leerRestriccion(valor, umbralRatio)` → `{ relleno: number (0..1), cumple: boolean, esNoAplica: boolean }`
  - `MAX_CUADRITOS_VISIBLES = 7`
  - `repartirCuadritos(lista)` → `{ visibles: Array, sobrantes: number }`
  - El mismo objeto publicado como **`window.AIAReadiness`**, porque `hot.js` no es un módulo ES.
    Ese nombre exacto es el que consume la Task 4.

**Por qué existe este módulo:** la regla del color («cumple su propio umbral») y la del relleno
(«cuánto lleva») son dos preguntas distintas sobre el mismo dato, y son la parte que se puede
equivocar en silencio. Sacarlas del renderer las vuelve testeables sin navegador. `hot.js` ya
calcula el ratio agregado en `calculateRestrictionStateRatio` (`hot.js:812`) usando
`hardRestrictionThresholds[prop]`, que guarda el umbral **ya convertido a ratio** (`hot.js:237-243`);
este módulo trabaja con ese mismo contrato para no introducir una segunda convención.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/design-system/readiness-cell.test.mjs`:

```js
import assert from 'node:assert/strict';
import test from 'node:test';
import { leerRestriccion, repartirCuadritos, MAX_CUADRITOS_VISIBLES }
  from '../../public/js/design-system/readiness-cell.js';

test('N/A no cuenta y no se lee como liberada', () => {
  const r = leerRestriccion('N/A', 1);
  assert.equal(r.esNoAplica, true);
  assert.equal(r.cumple, false);
  assert.equal(r.relleno, 0);
});

test('vacio se trata como cero, no como N/A', () => {
  const r = leerRestriccion('', 1);
  assert.equal(r.esNoAplica, false);
  assert.equal(r.cumple, false);
  assert.equal(r.relleno, 0);
});

test('el relleno refleja el porcentaje crudo, sea cual sea la escala', () => {
  assert.equal(leerRestriccion('33%', 1).relleno, 0.33);
  assert.equal(leerRestriccion('66%', 1).relleno, 0.66);
  assert.equal(leerRestriccion('50%', 0.5).relleno, 0.5);
});

test('cumple se decide contra el umbral propio, no contra el 100', () => {
  assert.equal(leerRestriccion('50%', 0.5).cumple, true);
  assert.equal(leerRestriccion('50%', 1).cumple, false);
  assert.equal(leerRestriccion('100%', 1).cumple, true);
});

test('siete caben enteras', () => {
  const { visibles, sobrantes } = repartirCuadritos([1, 2, 3, 4, 5, 6, 7]);
  assert.equal(visibles.length, 7);
  assert.equal(sobrantes, 0);
});

test('con mas de siete se muestran seis y el resto se cuenta', () => {
  const { visibles, sobrantes } = repartirCuadritos([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
  assert.equal(visibles.length, 6);
  assert.equal(sobrantes, 4);
});

test('el tope declarado es siete, no ocho', () => {
  assert.equal(MAX_CUADRITOS_VISIBLES, 7);
});
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node --test tests/design-system/readiness-cell.test.mjs
```

Esperado: FAIL con «Cannot find module .../readiness-cell.js».

- [ ] **Step 3: Implementa el módulo**

Crear `public/js/design-system/readiness-cell.js`:

```js
export const MAX_CUADRITOS_VISIBLES = 7;

export function leerRestriccion(valor, umbralRatio) {
  const texto = String(valor == null ? '' : valor).trim();
  if (texto.toUpperCase() === 'N/A') {
    return { relleno: 0, cumple: false, esNoAplica: true };
  }
  const numero = Number.parseFloat(texto.replace('%', ''));
  const relleno = Number.isFinite(numero) ? Math.min(Math.max(numero / 100, 0), 1) : 0;
  const umbral = Number.isFinite(umbralRatio) && umbralRatio > 0 ? umbralRatio : 1;
  return { relleno, cumple: relleno >= umbral, esNoAplica: false };
}

export function repartirCuadritos(lista) {
  const items = Array.isArray(lista) ? lista : [];
  if (items.length <= MAX_CUADRITOS_VISIBLES) {
    return { visibles: items, sobrantes: 0 };
  }
  const tope = MAX_CUADRITOS_VISIBLES - 1;
  return { visibles: items.slice(0, tope), sobrantes: items.length - tope };
}
```

- [ ] **Step 4: Córrela y confirma que pasa**

```bash
node --test tests/design-system/readiness-cell.test.mjs
```

Esperado: PASS, 7 tests.

- [ ] **Step 5: Commit**

```bash
git add public/js/design-system/readiness-cell.js tests/design-system/readiness-cell.test.mjs
git commit -m "feat(ds): modulo puro de lectura de restricciones para la celda de habilitacion"
```

---

### Task 3: la primitiva visual del cuadrito

**Files:**
- Create: `public/css/design-system/components/readiness-squares.css`
- Modify: `public/css/tokens.css` (añadir los tokens de la primitiva)
- Test: `tests/design-system/readiness-cell.test.mjs` (ampliar con el contrato de la hoja)

**Interfaces:**
- Consumes: `--ds-state-solid-green`, `--ds-state-solid-amber`, `--ds-state-solid-red`,
  `--ds-state-row-neutral`, `--ds-active-text-secondary`.
- Produces: las clases `.aia-readiness`, `.aia-readiness__box`, `.aia-readiness__fill`,
  `.aia-readiness__check`, `.aia-readiness__box--na`, `.aia-readiness__more`.

**Medidas cerradas por la spec, no negociables aquí:** caja `14 × 18 px`, hueco `2 px`, siete cajas
= `110 px` de `114` útiles; seis cajas más `+N` = `113`. Si al medirlas en pantalla no cuadran, se
avisa y se recalcula el ancho — **no se encoge el visto**, que es la señal que sostiene el criterio
de no depender del color.

- [ ] **Step 1: Escribe la prueba que falla**

Añadir a `tests/design-system/readiness-cell.test.mjs`:

```js
import { readFile } from 'node:fs/promises';
const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

test('la primitiva del cuadrito vive en una capa y sin hex', async () => {
  const css = await read('public/css/design-system/components/readiness-squares.css');
  assert.match(css, /@layer\s+components/, 'la hoja no declara su capa');
  assert.doesNotMatch(css, /#[0-9a-fA-F]{3,8}\b/, 'la hoja trae hex literales');
});

test('el cuadrito lleva tres senales y no solo el color', async () => {
  const css = await read('public/css/design-system/components/readiness-squares.css');
  assert.match(css, /\.aia-readiness__fill/, 'falta el relleno');
  assert.match(css, /\.aia-readiness__check/, 'falta la marca de visto');
  assert.match(css, /\.aia-readiness__box--na/, 'falta el estado no aplica');
});
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node --test tests/design-system/readiness-cell.test.mjs
```

Esperado: FAIL con ENOENT sobre `readiness-squares.css`.

- [ ] **Step 3: Añade los tokens**

En `public/css/tokens.css`, junto a los `--ds-state-solid-*`:

```css
    --ds-readiness-box-w: 14px;
    --ds-readiness-box-h: 18px;
    --ds-readiness-gap: 2px;
    --ds-readiness-empty: var(--ds-state-row-neutral);
    --ds-readiness-partial: var(--ds-state-solid-amber);
    --ds-readiness-met: var(--ds-state-solid-green);
    --ds-readiness-met-ink: var(--ds-state-solid-green-text);
```

- [ ] **Step 4: Escribe la hoja**

Crear `public/css/design-system/components/readiness-squares.css`:

```css
@layer components {
  .aia-readiness {
    display: flex;
    align-items: center;
    gap: var(--ds-readiness-gap);
  }

  .aia-readiness__box {
    position: relative;
    inline-size: var(--ds-readiness-box-w);
    block-size: var(--ds-readiness-box-h);
    border-radius: 3px;
    background-color: var(--ds-readiness-empty);
    overflow: hidden;
    flex: 0 0 auto;
  }

  .aia-readiness__fill {
    position: absolute;
    inset-inline: 0;
    inset-block-end: 0;
    background-color: var(--ds-readiness-partial);
  }

  .aia-readiness__box--met { background-color: var(--ds-readiness-met); }
  .aia-readiness__box--met .aia-readiness__fill { display: none; }

  .aia-readiness__check {
    position: absolute;
    inset-block-start: 0;
    inset-inline-end: 0;
    color: var(--ds-readiness-met-ink);
    line-height: 1;
    pointer-events: none;
  }

  .aia-readiness__box--na { background-color: var(--ds-readiness-empty); }

  .aia-readiness__box--na::after {
    content: '';
    position: absolute;
    inset-inline: 2px;
    inset-block-start: 50%;
    block-size: 1px;
    background-color: var(--ds-active-text-secondary);
    transform: rotate(-40deg);
  }

  .aia-readiness__more {
    font-size: var(--ds-font-size-xs);
    color: var(--ds-active-text-secondary);
    margin-inline-start: 1px;
  }
}
```

- [ ] **Step 5: Córrela y confirma que pasa**

```bash
node --test tests/design-system/readiness-cell.test.mjs
```

Esperado: PASS.

- [ ] **Step 6: Comprueba que la hoja entra al bundle y con capa**

```bash
npm run test:design-system:static
```

Esperado: 8/8. Si `unlayered-delivery` sale en rojo, la hoja tiene algo fuera de `@layer` — se
envuelve, no se añade a la lista de excepciones.

- [ ] **Step 7: Commit**

```bash
git add public/css/design-system/components/readiness-squares.css public/css/tokens.css tests/design-system/readiness-cell.test.mjs
git commit -m "feat(ds): primitiva de cuadritos de habilitacion, con relleno visto y no-aplica"
```

---

### Task 4: la columna «Habilitación» en la tabla

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js:342-370` (definición de columnas)
- Modify: `public/js/modules/programacion_intermedia/hot.js:3237` (registro de renderers)
- Modify: `public/js/modules/programacion_intermedia/hot.js:324` (`buildColumnHeaders`)

**Interfaces:**
- Consumes: `leerRestriccion`, `repartirCuadritos` de `readiness-cell.js`;
  `hardRestrictionThresholds` y `restrictionProps` de `hot.js`.
- Produces: la columna `{ data: '__habilitacion', renderer: 'piHabilitacionRenderer' }` y el atributo
  `data-restriccion` en cada cuadrito, del que dependen las Tasks 6 y 9.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/design-system/pi-columna-habilitacion.test.mjs`:

```js
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

test('la tabla declara una columna de habilitacion y ninguna de restriccion suelta', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  assert.match(js, /data:\s*'__habilitacion'/, 'no existe la columna de habilitacion');
  assert.doesNotMatch(js, /renderer:\s*'piRestrictionRenderer'/,
    'sigue habiendo columnas de restriccion sueltas');
});

test('Estado_Restricciones ya no es una columna de la tabla', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  assert.doesNotMatch(js, /\{\s*data:\s*'Estado_Restricciones'/,
    'el % Liberacion sigue ocupando una columna; la spec lo muda al globo');
});
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node --test tests/design-system/pi-columna-habilitacion.test.mjs
```

Esperado: FAIL con «no existe la columna de habilitacion».

- [ ] **Step 3: Sustituye el bucle de columnas de restricción**

En `hot.js`, el bucle `for (var i = 0; i < restrictions.length; i++)` que empuja una columna por
restricción se reemplaza por **una sola**:

```js
    cols.push({
      data: '__habilitacion',
      readOnly: true,
      renderer: 'piHabilitacionRenderer',
      className: 'htLeft htMiddle pi-habilitacion-cell',
    });
```

Y del bloque final se retira `{ data: 'Estado_Restricciones', ... }`. **`Estado_Restricciones`
sigue existiendo en los datos y sigue calculándose** (`hot.js:1224`, `2720`, `3063`): lo que
desaparece es su columna. Quitar el cálculo rompería el estado operativo.

- [ ] **Step 4: Registra el renderer**

Junto a los demás `registerRenderer` (`hot.js:3237`):

```js
    Handsontable.renderers.registerRenderer('piHabilitacionRenderer', function (instance, td, row) {
      Handsontable.renderers.TextRenderer.apply(this, arguments);
      td.textContent = '';

      var rowData = getSourceRowDataByVisualRow(instance, row) || {};
      var physicalRow = getPhysicalRowFromVisualRow(instance, row);
      var meta = getPIRowMeta(physicalRow, rowData);
      if (meta.isHeader) { return; }

      var lista = [];
      for (var i = 0; i < restrictionProps.length; i++) {
        var prop = restrictionProps[i];
        var lectura = AIAReadiness.leerRestriccion(
          rowData[prop], hardRestrictionThresholds[prop] || 1);
        lista.push({ prop: prop, lectura: lectura });
      }

      var reparto = AIAReadiness.repartirCuadritos(lista);
      var caja = document.createElement('div');
      caja.className = 'aia-readiness';

      for (var v = 0; v < reparto.visibles.length; v++) {
        caja.appendChild(construirCuadrito(reparto.visibles[v]));
      }
      if (reparto.sobrantes > 0) {
        var mas = document.createElement('span');
        mas.className = 'aia-readiness__more';
        mas.textContent = '+' + reparto.sobrantes;
        caja.appendChild(mas);
      }

      td.appendChild(caja);
      td.setAttribute('aria-label', describirHabilitacion(rowData, lista));
    });
```

Con los dos ayudantes, en el mismo archivo:

```js
  function construirCuadrito(item) {
    var box = document.createElement('span');
    box.className = 'aia-readiness__box';
    box.setAttribute('data-restriccion', item.prop);

    if (item.lectura.esNoAplica) {
      box.classList.add('aia-readiness__box--na');
      return box;
    }
    if (item.lectura.cumple) {
      box.classList.add('aia-readiness__box--met');
      var check = document.createElement('span');
      check.className = 'aia-readiness__check';
      check.textContent = '✓';
      box.appendChild(check);
      return box;
    }
    var fill = document.createElement('span');
    fill.className = 'aia-readiness__fill';
    fill.style.height = Math.round(item.lectura.relleno * 100) + '%';
    box.appendChild(fill);
    return box;
  }

  function describirHabilitacion(rowData, lista) {
    var faltan = 0;
    for (var i = 0; i < lista.length; i++) {
      if (!lista[i].lectura.cumple && !lista[i].lectura.esNoAplica) { faltan += 1; }
    }
    var pct = Math.round((rowData.Estado_Restricciones || 0) * 100);
    return (rowData.Actividad || 'Actividad') + ': ' + faltan +
      ' restricciones por liberar, ' + pct + ' por ciento habilitada.';
  }
```

**Nota para quien implemente:** `fill.style.height` es el **único** estilo inline permitido en esta
obra, porque es un dato de la fila y no una decisión de diseño. Todo lo demás va por clase.

- [ ] **Step 5: Expón el módulo puro al `hot.js`**

`hot.js` no es un módulo ES. Cargar `readiness-cell.js` como `<script type="module">` en la vista y
publicarlo en `window.AIAReadiness`, igual que hace `state-tooltip.js` en este mismo frente. Mirar
cómo se enganchó allí y **seguir ese patrón**, no inventar uno nuevo.

- [ ] **Step 6: Córrela y confirma que pasa**

```bash
node --test tests/design-system/pi-columna-habilitacion.test.mjs
```

Esperado: PASS, 2 tests.

- [ ] **Step 7: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js views/programacion-intermedia/programacion_intermedia.view.php tests/design-system/pi-columna-habilitacion.test.mjs
git commit -m "feat(pi): una columna de habilitacion en vez de siete de restriccion"
```

---

### Task 5: los anchos y el guardián del presupuesto

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js:443-467`
- Create: `tests/browser/pi-ancho-presupuesto.mjs`

**Interfaces:**
- Consumes: la columna `__habilitacion` de la Task 4.
- Produces: nada.

**Las cuentas, ya cerradas en la spec:** fijas iniciales `580` de piso, Estado Operativo más
Observaciones `248`, Habilitación `130` → **958 contra 1040 disponibles**, 82 px de holgura.

- [ ] **Step 1: Escribe el guardián que falla**

Crear `tests/browser/pi-ancho-presupuesto.mjs`:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const ANCHO = 1100;

const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: ANCHO, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('#hot-container .handsontable', { timeout: 30000 });
await pagina.waitForTimeout(2500);

const medida = await pagina.evaluate(() => {
  const holder = document.querySelector('#hot-container .ht_master .wtHolder');
  return { scroll: holder.scrollWidth, cliente: holder.clientWidth };
});

assert.ok(medida.scroll <= medida.cliente,
  `la tabla desborda: ${medida.scroll} px de contenido en ${medida.cliente} px de caja`);

const escondidas = await pagina.evaluate(() => {
  const celdas = [...document.querySelectorAll('#hot-container td, #hot-container th')];
  return celdas.filter((c) => c.scrollWidth > c.clientWidth + 1
    || c.scrollHeight > c.clientHeight + 1).length;
});

assert.equal(escondidas, 0, `${escondidas} celdas esconden su contenido`);

console.log(`OK: ${medida.scroll} px en ${medida.cliente}, 0 celdas recortadas`);
await navegador.close();
```

- [ ] **Step 2: Córrelo y confirma que falla**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
node tests/browser/pi-ancho-presupuesto.mjs
```

Esperado: FAIL — la tabla todavía reparte el ancho con los arrays viejos.

- [ ] **Step 3: Ajusta los arrays de ancho**

En `hot.js:443-467`: `fixedTrailing` pierde su primera entrada (la de `Estado_Restricciones`), el
bloque `restrictionMin`/`Floor`/`Max` desaparece, y en su lugar la columna de habilitación entra con
`min`, `floor` y `max` **todos en 130** — no se estira ni se encoge, porque su contenido tiene un
ancho fijo conocido y estirarla solo le robaría píxeles a las columnas de texto.

```js
    var fixedTrailing = {
      min:   [150, 180],
      floor: [118, 130],
      max:   [240, 380],
      ratio: [0.096, 0.088],
    };

    var habilitacion = { min: 130, floor: 130, max: 130, ratio: 0.118 };
```

Los `ratio` de todas las columnas deben volver a sumar `1.0` — el comentario de `hot.js:632` lo
exige y el reparto responsivo depende de ello. Recalcular, no aproximar.

- [ ] **Step 4: Córrelo y confirma que pasa**

```bash
node tests/browser/pi-ancho-presupuesto.mjs
```

Esperado: `OK: <=1040 px en <caja>, 0 celdas recortadas`.

- [ ] **Step 5: Engancha el guardián a la suite**

Añadirlo a `package.json` en el script que corre las pruebas de navegador, para que **falle solo**
cuando alguien vuelva a ensanchar. Un guardián que hay que acordarse de correr no es un guardián.

- [ ] **Step 6: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js tests/browser/pi-ancho-presupuesto.mjs package.json
git commit -m "feat(pi): la tabla cabe a 1100 y un guardian lo vigila"
```

---

### Task 6: el globo — abrir, cerrar, foco y teclado

**Files:**
- Create: `public/js/design-system/readiness-popover.js`
- Create: `public/css/design-system/components/readiness-popover.css`
- Modify: `public/js/modules/programacion_intermedia/hot.js` (enganche de apertura)

**Interfaces:**
- Consumes: `leerRestriccion` de la Task 2; el atributo `data-restriccion` de la Task 4.
- Produces: `AIAReadinessPopover.abrir(celda, datosFila)`, `.cerrar()`, `.irA(direccion)`.

**Reutiliza, no reinventa:** la maquinaria de top-layer, anclaje y volteo ya está resuelta y probada
en `public/js/design-system/state-tooltip.js` y su hoja, de este mismo frente. Leerla primero. El
fondo **debe ser opaco** por la misma razón que allí: sobre una tabla, un fondo translúcido deja el
texto de las filas asomando bajo el contenido del globo.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/browser/pi-globo-teclado.mjs`:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

await pagina.locator('.pi-habilitacion-cell').first().focus();
await pagina.keyboard.press('Enter');

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'Enter no abrio el globo');
assert.ok(await pagina.evaluate(() =>
  !!document.activeElement.closest('.aia-readiness-popover')),
  'el foco no entro al globo');

await pagina.keyboard.press('Escape');

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 0,
  'Escape no cerro el globo');
assert.ok(await pagina.evaluate(() =>
  document.activeElement.classList.contains('pi-habilitacion-cell')),
  'el foco no volvio a la celda que abrio el globo');

console.log('OK: abre, enfoca, cierra y devuelve el foco');
await navegador.close();
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node tests/browser/pi-globo-teclado.mjs
```

Esperado: FAIL en la primera aserción — no hay globo.

- [ ] **Step 3: Escribe la hoja del globo**

`readiness-popover.css`, dentro de `@layer components`, con el fondo opaco resuelto igual que en
`state-tooltip.css`:

```css
    background-color: var(--ds-active-bg-page);
    background-image: linear-gradient(var(--ds-active-surface-raised), var(--ds-active-surface-raised));
```

Y el volteo por `position-try-fallbacks`, como el tooltip.

- [ ] **Step 4: Escribe el módulo del globo**

`readiness-popover.js` con `popover="manual"`, `anchor-name` sobre la celda, foco al abrir, trampa
de foco mientras está abierto, `Escape` para cerrar y devolución del foco a la celda de origen.

- [ ] **Step 5: Engancha la apertura**

En `hot.js`, un listener sobre `.pi-habilitacion-cell` para clic y para `Enter`/`Espacio`.

- [ ] **Step 6: Córrela y confirma que pasa**

```bash
node tests/browser/pi-globo-teclado.mjs
```

Esperado: PASS, las cuatro aserciones.

- [ ] **Step 7: Commit**

```bash
git add public/js/design-system/readiness-popover.js public/css/design-system/components/readiness-popover.css public/js/modules/programacion_intermedia/hot.js tests/browser/pi-globo-teclado.mjs
git commit -m "feat(pi): globo de habilitacion con foco y teclado completos"
```

---

### Task 7: el globo — contenido, edición y guardado

**Files:**
- Modify: `public/js/design-system/readiness-popover.js`
- Modify: `public/js/modules/programacion_intermedia/hot.js`

**Interfaces:**
- Consumes: el globo de la Task 6.
- Produces: nada nuevo hacia fuera.

**La regla que gobierna esta tarea:** el selector, las opciones, la validación y el endpoint son
**los de hoy**. Si el implementador se encuentra escribiendo un editor nuevo, se ha salido de la
spec. Lo único que cambia es dónde se dibuja.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/browser/pi-globo-guardado.mjs`. El punto de la prueba es que el guardado sea **el
mismo** que ya existe, así que se afirma sobre la petición de red, no sobre la pantalla:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

const peticiones = [];
pagina.on('request', (r) => {
  if (r.method() === 'POST') peticiones.push({ url: r.url(), cuerpo: r.postData() });
});

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

await pagina.locator('.pi-habilitacion-cell').first().click();
await pagina.waitForSelector('.aia-readiness-popover:popover-open');

const antes = await pagina.locator('.aia-readiness-popover__avance').innerText();

await pagina.locator('.aia-readiness-popover select').first().selectOption('100%');
await pagina.waitForTimeout(1200);

assert.equal(peticiones.length, 1,
  `se esperaba una sola peticion de guardado, salieron ${peticiones.length}`);
assert.match(peticiones[0].url, /programacion-intermedia|restriccion/i,
  `el globo guarda contra otro endpoint: ${peticiones[0].url}`);

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'el globo se cerro solo al guardar');

const despues = await pagina.locator('.aia-readiness-popover__avance').innerText();
assert.notEqual(antes, despues, 'el marcador de avance no se movio al liberar');

console.log('OK: una peticion, mismo endpoint, avance en vivo y globo abierto');
await navegador.close();
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node tests/browser/pi-globo-guardado.mjs
```

- [ ] **Step 3: Construye el contenido del globo**

Cabecera con nombre de la actividad, semana y responsable; **marcador de avance** con el
`% Liberación` y el chip de estado operativo; grupo «obligatorias» y grupo «de seguimiento»,
rotulados —hoy esa distinción solo vive en la clase CSS `pi-soft-restriction-cell`—; una fila por
restricción con su cuadrito, su nombre y su selector.

- [ ] **Step 4: Conecta el guardado**

Reusar la función que ya guarda desde la celda. Al volver la respuesta, recalcular con
`recalculateRestrictionStateRatio` y actualizar el marcador **sin cerrar el globo**.

- [ ] **Step 5: El fallo se muestra donde ocurrió**

Si el guardado falla, la fila de esa restricción se marca y muestra el texto que ya existe
(`hot.js:3204-3219`) más un botón de reintentar, **dentro del globo**. Hoy ese aviso sale en la
barra de la tabla y el globo lo taparía: el usuario vería su marca deshacerse sola.

- [ ] **Step 6: Modo lectura**

Sin permiso de edición (`canEdit` falso, `hot.js:1015`), el globo abre igual, los selectores van
desactivados y una línea dice por qué.

- [ ] **Step 7: Deshacer con Ctrl+Z sigue funcionando**

La spec promete que equivocarse marcando se deshace igual que hoy. Handsontable lleva su propia
pila de deshacer alimentada por los cambios de celda; si el globo escribe el valor **sin pasar por
`setDataAtRowProp`**, la pila no se entera y `Ctrl+Z` deja de funcionar sin que nada falle en rojo.
Escribir la aserción y **verla fallar** antes de conectar nada:

```js
await pagina.locator('.aia-readiness-popover select').first().selectOption('100%');
await pagina.waitForTimeout(800);
await pagina.keyboard.press('Control+z');
await pagina.waitForTimeout(800);

const valor = await pagina.locator('.aia-readiness-popover select').first().inputValue();
assert.notEqual(valor, '100%', 'Ctrl+Z no deshizo la liberacion hecha desde el globo');
```

- [ ] **Step 8: Córrela y confirma que pasa**

```bash
node tests/browser/pi-globo-guardado.mjs
```

- [ ] **Step 9: Commit**

```bash
git add public/js/design-system/readiness-popover.js public/js/modules/programacion_intermedia/hot.js tests/browser/pi-globo-guardado.mjs
git commit -m "feat(pi): liberar desde el globo, con avance en vivo y el fallo donde ocurrio"
```

---

### Task 8: recorrer actividades sin cerrar el globo

**Files:**
- Modify: `public/js/design-system/readiness-popover.js`

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/browser/pi-globo-recorrido.mjs`:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

await pagina.locator('.pi-habilitacion-cell').first().click();
await pagina.waitForSelector('.aia-readiness-popover:popover-open');

const titulo = () => pagina.locator('.aia-readiness-popover__titulo').innerText();
const primera = await titulo();

await pagina.locator('.aia-readiness-popover__siguiente').click();
await pagina.waitForTimeout(400);

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'la flecha cerro el globo en vez de saltar');
const segunda = await titulo();
assert.notEqual(primera, segunda, 'el globo no cambio de actividad');
assert.ok(segunda.trim().length > 0, 'el globo quedo en blanco tras saltar');

// El ultimo salto no debe dejar el globo vacio ni saltar a una fila de capitulo.
for (let i = 0; i < 60; i += 1) {
  await pagina.locator('.aia-readiness-popover__siguiente').click();
}
await pagina.waitForTimeout(400);
assert.ok((await titulo()).trim().length > 0,
  'al llegar al final el globo se quedo sin contenido');

console.log('OK: recorre sin cerrarse y no se vacia al final');
await navegador.close();
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node tests/browser/pi-globo-recorrido.mjs
```

- [ ] **Step 3: Implementa `irA(direccion)`**

Salta a la fila anterior o siguiente **saltándose las filas de capítulo**, que no son actividades.
Botones y teclado. El globo **no** sigue al ratón: el contenido cambiaría bajo el cursor y se
marcaría la restricción equivocada.

- [ ] **Step 4: Córrela y confirma que pasa**

```bash
node tests/browser/pi-globo-recorrido.mjs
```

- [ ] **Step 5: Commit**

```bash
git add public/js/design-system/readiness-popover.js tests/browser/pi-globo-recorrido.mjs
git commit -m "feat(pi): flechas para recorrer actividades sin cerrar el globo"
```

---

### Task 9: reponer el filtro por restricción

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js` (opciones de `filters`)

**Por qué es obligatoria y no un extra:** hoy cada columna de restricción trae su embudo
(`filters: true`, `hot.js:4226-4237`). Fundirlas sin reponer el filtro **quita una función que el
equipo usa hoy**, y eso convierte una mejora en una regresión.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/browser/pi-filtro-restriccion.mjs`:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

const contarFilas = () => pagina.locator('#hot-container .ht_master tbody tr').count();
const antes = await contarFilas();

await pagina.locator('.pi-habilitacion-filtro').click();
await pagina.locator('.pi-habilitacion-filtro__opcion[data-restriccion="Materiales"]').click();
await pagina.waitForTimeout(600);

const despues = await contarFilas();
assert.ok(despues < antes,
  `el filtro no redujo las filas: ${antes} antes, ${despues} despues`);

const todasPendientes = await pagina.evaluate(() =>
  [...document.querySelectorAll('.pi-habilitacion-cell')].every((celda) => {
    const caja = celda.querySelector('[data-restriccion="Materiales"]');
    return !caja || !caja.classList.contains('aia-readiness__box--met');
  }));
assert.ok(todasPendientes,
  'quedaron filas con esa restriccion ya liberada');

console.log(`OK: ${antes} -> ${despues} filas, todas con la restriccion pendiente`);
await navegador.close();
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node tests/browser/pi-filtro-restriccion.mjs
```

- [ ] **Step 3: Implementa el filtro de cabecera**

Un menú propio en la cabecera de Habilitación que lista las restricciones y filtra por el estado de
cada una. Reusar el mecanismo de filtros de Handsontable donde se pueda.

- [ ] **Step 4: Córrela y confirma que pasa**

```bash
node tests/browser/pi-filtro-restriccion.mjs
```

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js tests/browser/pi-filtro-restriccion.mjs
git commit -m "feat(pi): el filtro por restriccion vuelve, ahora en la cabecera de habilitacion"
```

---

### Task 10: móvil comparte la pieza

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js:4370-4500` (`construirDetalleRestricciones`)

**El dato de partida:** en móvil la tabla **ya no es tabla**, son tarjetas (`pi-mobile-card`) que ya
listan cada restricción con su nombre y su valor. El panel por fila ya existe ahí y funciona.

- [ ] **Step 1: Escribe la prueba que falla**

Crear `tests/browser/pi-movil-misma-pieza.mjs`:

```js
import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 390, height: 844 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-mobile-card', { timeout: 30000 });

await pagina.locator('.pi-mobile-card details').first().click();
await pagina.waitForTimeout(400);

const orden = await pagina.evaluate(() =>
  [...document.querySelectorAll('.pi-mobile-card .aia-readiness__box')]
    .map((b) => b.getAttribute('data-restriccion')));

assert.ok(orden.length > 0,
  'la tarjeta movil no usa la primitiva compartida: no hay ningun .aia-readiness__box');
assert.deepEqual(orden, [...orden].filter(Boolean),
  'algun cuadrito de la tarjeta no declara su restriccion');

console.log(`OK: la tarjeta usa la misma pieza, ${orden.length} cuadritos en orden`);
await navegador.close();
```

- [ ] **Step 2: Córrela y confirma que falla**

```bash
node tests/browser/pi-movil-misma-pieza.mjs
```

- [ ] **Step 3: Extrae el contenido a una función compartida**

El cuerpo del globo pasa a una función que devuelve un fragmento; el globo lo mete en su caja
flotante y la tarjeta móvil lo mete dentro de su `<details>`. Cambia el envase, no la pieza.

- [ ] **Step 4: Córrela y confirma que pasa**

```bash
node tests/browser/pi-movil-misma-pieza.mjs
```

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js public/js/design-system/readiness-popover.js tests/browser/pi-movil-misma-pieza.mjs
git commit -m "refactor(pi): el globo y la tarjeta movil comparten la misma pieza"
```

---

### Task 11: verificación completa y cierre

**Files:** ninguno nuevo.

- [ ] **Step 1: Rol permitido y rol denegado**

Con `/dev/entrar?u=test.R` y `u=test.V`, comprobar que el residente libera y el visualizador ve el
globo completo sin poder cambiar nada. Lo exige el routing de RBAC de AGENTS.md para todo cambio que
toque capacidades.

- [ ] **Step 2: Escribir, recargar, recuperar**

Liberar una restricción, recargar la página y comprobar que el valor está. La spec mueve dónde se
edita; si el dato no sobrevive a una recarga, no se movió: se perdió.

- [ ] **Step 3: La suite completa**

```bash
npm run test:design-system:static
docker compose exec app php scripts/run-php-tests.php --nivel=puro
node tests/browser/pi-ancho-presupuesto.mjs
```

Esperado: 8/8, PHP en verde, y el guardián del ancho en `OK`.

- [ ] **Step 4: Escala de grises**

Con la pantalla en escala de grises, comprobar que los cuatro casos del cuadrito siguen siendo
distinguibles. Si el visto no se distingue a 14 px, **no se encoge el problema**: se sube el tamaño
del cuadrito y se recalcula el ancho de la columna con la Task 5.

- [ ] **Step 5: Pedir la aprobación visual a Felipe, por su nombre**

Capturas a 2x de la tabla, el globo abierto y la tarjeta móvil. **No regenerar los goldens hasta
tener su sí explícito.**

- [ ] **Step 6: Regenerar goldens y publicar**

Solo con la aprobación en mano:

```bash
bash scripts/publicar.sh -v "la tabla cabe a 1100 y se libera desde el globo" -m "feat(pi): habilitacion en una columna"
```

- [ ] **Step 7: Anotar el cierre**

En el `goal.md` del frente, en `CHANGELOG.md` y en `TASKS.md`, en el mismo turno.

---

## Fuera de este plan

**Programación Semanal.** Comparte las mismas cinco restricciones duras
(`programacion_semanal/hot.js:570`) y hereda la pieza, pero en la **ola siguiente**, con lo de
Intermedia ya rodado una semana en obra. Hacer las dos a la vez duplicaría el riesgo de la primera
versión y obligaría a deshacer en dos pantallas.

**Concurrencia entre dos personas en la misma actividad.** Hoy existe un 409 que avisa si la semana
activa cambió en otra sesión (`hot.js:3214`), pero nada vigila la misma celda. El globo no lo empeora
ni lo arregla.

**Programa General** y el rediseño del modal de Restricción Compartida.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** readiness-cell.js, readiness-popover.js, readiness-squares.css; columna __habilitacion en programacion_intermedia/hot.js

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
