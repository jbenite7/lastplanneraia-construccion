---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-19-estados-severidad-contrato.md
resumen: Que cada canal visual de las tablas de estado codifique un solo eje — el color dice qué estado es, el filete dice cuán grave, el orden desempata — empezando…
---

# Estados, severidad y color — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cada canal visual de las tablas de estado codifique un solo eje — el color dice qué estado es, el filete dice cuán grave, el orden desempata — empezando por Programación Intermedia.

**Architecture:** El contrato (`state-semantics.json`) gana una regla explícita de un-canal-un-eje y corrige el nivel de **cuatro** de los ocho estados de Programación Intermedia. La capa de componentes gana una primitiva de filete con cuatro escalones. Las hojas de módulo dejan de mapear estado→peldaño de una escalera ordinal y pasan a consumir matiz para el fondo y nivel para el filete. El orden es un botón de barra que reordena los datos, no una capacidad de la rejilla.

**Tech Stack:** PHP 8.3 sin framework, Handsontable, CSS con `@layer`, Node test runner, Playwright, Docker Compose.

**Spec:** `docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md`

## Global Constraints

- **Los hex de `--ds-state-tint-*` no se tocan.** Ocho anclas exactas, fijadas por `tests/design-system/state-tint-ladder.test.mjs`.
- **Ningún golden se regenera sin aprobación visual explícita del usuario**, pedida por esa captura y por su nombre. Bloqueo incondicional.
- **Ningún test se ablanda para que pase.** Si un test debe cambiar porque el contrato cambió, se cambia declarando qué mide ahora, en el mismo commit que el cambio de contrato.
- **Tema `dark`, viewport `1180x820`** es la validación canónica. Por debajo de 1180 px rige el mínimo táctil de 44×44 px.
- **Color computado contra color computado.** Nunca comparar un valor declarado en la hoja con uno computado en el navegador.
- **Sesión local siempre por la puerta de servicio**: `http://localhost:8081/dev/entrar?u=test.R`. Nunca `/login`.
- **Sin dependencias nuevas.**
- **Verificación del frente:** `bash scripts/publicar.sh --solo-verificar`.
- **El contenedor debe servir tu worktree antes de verificar:** `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, y devolverlo a la raíz al terminar.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `docs/design-system/state-semantics.json` | Contrato: la regla un-canal-un-eje, niveles y matices por módulo |
| `public/css/tokens.css` | Tokens nuevos del filete (`--ds-severity-rail-*`) |
| `public/css/design-system/components/severity-rail.css` | **Nuevo.** La primitiva del filete, cuatro escalones |
| `public/css/design-system/components/states-feedback.css` | Sin cambios de fondo; sigue traduciendo matiz a tinte |
| `public/css/styles.css:3664-3725` | Deja de mapear estado→peldaño ordinal; el fondo pasa a matiz |
| `public/js/modules/programacion_intermedia/hot.js` | `statePresentation` (niveles) + atributo de nivel en la fila + botón de orden |
| `views/programacion-intermedia/programacion_intermedia.view.php` | El botón de agrupar en la barra |
| `docs/design-system/component-catalog.json` | Ficha de la primitiva `severity-rail` |
| `tests/design-system/severity-rail.test.mjs` | **Nuevo.** Guard estático de la primitiva y de la regla del contrato |

---

### Task 1: La regla del contrato y los cuatro niveles corregidos

**Files:**
- Modify: `docs/design-system/state-semantics.json`
- Modify: `public/js/modules/programacion_intermedia/hot.js:549-568` (`statePresentation`)
- Create: `tests/design-system/severity-rail.test.mjs`

**Interfaces:**
- Produces: en `state-semantics.json`, la clave `axisRules` (array de strings) y los `level` corregidos de **cuatro** estados del módulo `programacion-intermedia`: `blocked-due`, `alert-1-week`, `alert-4-6-weeks` y `execution-blocked`. Los otros cuatro no se tocan.

- [x] **Step 1: Escribe el test que falla**

Crea `tests/design-system/severity-rail.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// La regla que faltaba y que este frente existe para escribir: un canal, un eje.
// Vive en el contrato y no en la prosa de una hoja, porque es lo que se rompio:
// el fondo intentaba decir identidad Y gravedad, y acabo sin decir ninguna.
test('el contrato declara que ningun canal codifica dos ejes', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  assert.ok(Array.isArray(semantics.axisRules), 'state-semantics.json no declara axisRules');
  const texto = semantics.axisRules.join(' ').toLowerCase();
  assert.match(texto, /fondo/, 'axisRules no dice que hace el fondo');
  assert.match(texto, /filete/, 'axisRules no dice que hace el filete');
  assert.match(texto, /orden/, 'axisRules no dice que hace el orden');
});

// Los tres niveles que este frente corrige. Se asiertan por su valor exacto y no
// por "es distinto de antes": un test que solo comprueba el cambio pasa igual si
// alguien lo mueve otra vez.
const NIVELES_PI = {
  'blocked-overdue-critical': 'urgent',
  'blocked-overdue': 'urgent',
  'blocked-due': 'urgent',
  'alert-1-week': 'attention',
  'alert-2-3-weeks': 'attention',
  'alert-4-6-weeks': 'healthy',
  'execution-blocked': 'urgent',
  'liberated-control': 'healthy',
};

test('los ocho estados de programacion-intermedia llevan el nivel decidido', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const pi = semantics.moduleMappings.find((m) => m.module === 'programacion-intermedia');
  const real = Object.fromEntries(pi.states.map(({ key, level }) => [key, level]));
  assert.deepEqual(real, NIVELES_PI);
});

// El modulo no puede desviarse del contrato: `statePresentation` en hot.js es una
// proyeccion, no una segunda fuente.
test('statePresentation de hot.js declara los mismos niveles que el contrato', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  const bloque = js.match(/var statePresentation = \{([\s\S]*?)\};/)[1];
  const real = {};
  for (const [, key, level] of bloque.matchAll(/'?([\w-]+)'?:\s*\{\s*level:\s*'(\w+)'/g)) {
    if (key !== 'neutral') real[key] = level;
  }
  assert.deepEqual(real, NIVELES_PI);
});
```

- [x] **Step 2: Corre el test y comprueba que falla**

```bash
docker compose exec -T app true 2>/dev/null; node --test tests/design-system/severity-rail.test.mjs
```

Esperado: FAIL — `state-semantics.json no declara axisRules`, y los dos `deepEqual` en rojo por los cuatro niveles viejos.

- [x] **Step 3: Añade `axisRules` al contrato**

En `docs/design-system/state-semantics.json`, después de `"dimensions"`, añade:

```json
  "axisRules": [
    "Un canal, un eje: ningun canal visual codifica dos cosas a la vez. Es lo que se rompio antes de este contrato — el fondo decia identidad y gravedad a la vez y acabo sin decir ninguna de las dos.",
    "El fondo de la fila y del chip codifica IDENTIDAD: que estado es. Se pinta con el matiz, un tinte por matiz.",
    "El filete del borde inicial codifica GRAVEDAD: el nivel, en cuatro escalones de grosor y brillo.",
    "El orden de las filas DESEMPATA dentro de un mismo nivel. Es opcional y arranca apagado: la secuencia del programa es informacion y no se pierde por defecto.",
    "El significado nunca depende solo del color: cada estado muestra texto completo."
  ],
```

- [x] **Step 4: Corrige los cuatro niveles en el contrato**

En el módulo `programacion-intermedia` de `moduleMappings`, cambia **solo** el campo `level` de estos cuatro, dejando `label`, `key` y `hue` intactos:

```
  "key": "blocked-due"        →  "level": "urgent"      (era "attention")
  "key": "alert-1-week"       →  "level": "attention"   (era "urgent")
  "key": "alert-4-6-weeks"    →  "level": "healthy"     (era "attention")
  "key": "execution-blocked"  →  "level": "urgent"      (era "attention")
```

En `execution-blocked`, **sustituye** el campo `note` por:

```json
"note": "Nivel urgent desde 2026-08-18, por decision del usuario. Revierte a sabiendas la ratificacion del 2026-08-03 que fijaba attention/blue: es el unico estado donde el dano se esta produciendo -hay avance sobre restricciones sin liberar- en vez de anticiparse. Procedencia y argumento en goals/bug-coloreado-severidad/respuestas-ds-f1.md."
```

- [x] **Step 5: Corrige `statePresentation` en `hot.js`**

En `public/js/modules/programacion_intermedia/hot.js`, dentro de `var statePresentation`:

```javascript
  var statePresentation = {
    'blocked-overdue-critical': { level: 'urgent', hue: 'red' },
    'blocked-overdue': { level: 'urgent', hue: 'orange' },
    'blocked-due': { level: 'urgent', hue: 'violet' },
    'alert-1-week': { level: 'attention', hue: 'amber' },
    'alert-2-3-weeks': { level: 'attention', hue: 'teal' },
    'alert-4-6-weeks': { level: 'healthy', hue: 'neutral' },
    'execution-blocked': { level: 'urgent', hue: 'blue' },
    'liberated-control': { level: 'healthy', hue: 'green' },
    neutral: { level: 'neutral', hue: 'neutral' },
  };
```

- [x] **Step 6: Corre los tests y comprueba que pasan**

```bash
node --test tests/design-system/severity-rail.test.mjs
```

Esperado: PASS, 3/3.

- [x] **Step 7: Corre los dos guards que leen el contrato**

```bash
node --test tests/design-system/ops-state-contract.test.mjs tests/design-system/state-tint-ladder.test.mjs
```

Esperado: PASS. Si `ops-state-contract` falla, es que `hot.js` y el contrato divergen — corrige el que esté mal, **nunca el test**.

- [x] **Step 8: Commit**

```bash
git add docs/design-system/state-semantics.json public/js/modules/programacion_intermedia/hot.js tests/design-system/severity-rail.test.mjs
git commit -m "feat(contrato): un canal un eje, y tres estados de PI cambian de nivel"
```

---

### Task 2: La primitiva del filete

**Files:**
- Modify: `public/css/tokens.css` (bloque `html.aia-theme-dark`, junto a `--ds-state-tint-*`)
- Create: `public/css/design-system/components/severity-rail.css`
- Modify: `docs/design-system/component-catalog.json`
- Modify: `tests/design-system/severity-rail.test.mjs`

**Interfaces:**
- Consumes: los `level` del contrato de la Task 1.
- Produces: el atributo `data-aia-severity-rail="urgent|attention|healthy|neutral"`, los tokens `--ds-severity-rail-width-{urgent,attention,healthy,neutral}` y `--ds-severity-rail-color-{…}`, y la ficha `severity-rail` en el catálogo.

- [x] **Step 1: Escribe los tests que fallan**

Añade al final de `tests/design-system/severity-rail.test.mjs`:

```javascript
const NIVELES = ['urgent', 'attention', 'healthy', 'neutral'];

test('los cuatro escalones del filete existen y son grosores distintos', async () => {
  const tokens = await read('public/css/tokens.css');
  const anchos = NIVELES.map((n) => {
    const v = tokens.match(new RegExp(`--ds-severity-rail-width-${n}:\\s*([^;]+);`))?.[1]?.trim();
    assert.ok(v, `falta --ds-severity-rail-width-${n}`);
    return parseFloat(v);
  });
  assert.equal(new Set(anchos).size, 4, `los cuatro escalones deben ser distintos: ${anchos}`);
  // Monotono descendente: mas grave, mas grueso. Es el eje ordinal entero.
  assert.deepEqual(anchos, [...anchos].sort((a, b) => b - a), `los grosores no bajan con la gravedad: ${anchos}`);
  // El escalon mas bajo sigue siendo visible: un filete de 0 no es un escalon, es ausencia.
  assert.ok(anchos[3] > 0, 'el escalon `neutral` no puede medir 0');
});

test('la primitiva traduce cada nivel a su grosor y su color', async () => {
  const css = await read('public/css/design-system/components/severity-rail.css');
  for (const n of NIVELES) {
    const regla = css.match(new RegExp(`\\[data-aia-severity-rail="${n}"\\][^{]*\\{([^}]*)\\}`))?.[1];
    assert.ok(regla, `severity-rail.css no traduce [data-aia-severity-rail="${n}"]`);
    assert.match(regla, new RegExp(`--ds-severity-rail-width-${n}`), `${n} no usa su token de grosor`);
    assert.match(regla, new RegExp(`--ds-severity-rail-color-${n}`), `${n} no usa su token de color`);
  }
});

test('el catalogo publica la ficha del filete', async () => {
  const catalogo = JSON.parse(await read('docs/design-system/component-catalog.json'));
  const arr = Array.isArray(catalogo) ? catalogo : catalogo.components;
  const ficha = arr.find((c) => c.id === 'severity-rail');
  assert.ok(ficha, 'component-catalog.json no publica la ficha `severity-rail`');
  assert.equal(ficha.family, 'states-feedback');
  assert.equal(ficha.maturity, 'candidate');
});
```

- [x] **Step 2: Corre los tests y comprueba que fallan**

```bash
node --test tests/design-system/severity-rail.test.mjs
```

Esperado: FAIL — `falta --ds-severity-rail-width-urgent`.

- [x] **Step 3: Añade los tokens**

En `public/css/tokens.css`, dentro del mismo bloque `html.aia-theme-dark` donde viven los `--ds-state-tint-*`:

```css
    /* Filete de gravedad. El eje ordinal del sistema: el color no lo tiene
       -las ocho anclas son equiluminantes a proposito- asi que la gravedad se
       lee en el GROSOR, que si escala. Cuatro escalones sobre una fila de 24 px
       (la excepcion de densidad de PRODUCT.md): 6/4/2/1 deja el mas bajo visible
       sin que parezca un borde suelto. */
    --ds-severity-rail-width-urgent: 6px;
    --ds-severity-rail-width-attention: 4px;
    --ds-severity-rail-width-healthy: 2px;
    --ds-severity-rail-width-neutral: 1px;
    --ds-severity-rail-color-urgent: var(--ds-color-state-critical-text);
    --ds-severity-rail-color-attention: var(--ds-color-state-warning-text);
    --ds-severity-rail-color-healthy: var(--ds-color-state-success-text);
    --ds-severity-rail-color-neutral: var(--ds-active-border);
```

- [x] **Step 4: Crea la primitiva**

Crea `public/css/design-system/components/severity-rail.css`:

```css
@layer components {
  /* Filete de gravedad. Va en el borde INICIAL (logico, no izquierdo) para que
     el dia que exista un locale RTL no haya que reescribir la primitiva.
     `box-shadow: inset` y no `border-left`: el borde participa del box model y
     desplazaria el contenido de la celda; la sombra interior no ocupa sitio, que
     en una fila de 24 px es la diferencia entre caber y no caber. */
  [data-aia-severity-rail] {
    position: relative;
  }

  [data-aia-severity-rail="urgent"] {
    box-shadow: inset var(--ds-severity-rail-width-urgent) 0 0 0 var(--ds-severity-rail-color-urgent);
  }

  [data-aia-severity-rail="attention"] {
    box-shadow: inset var(--ds-severity-rail-width-attention) 0 0 0 var(--ds-severity-rail-color-attention);
  }

  [data-aia-severity-rail="healthy"] {
    box-shadow: inset var(--ds-severity-rail-width-healthy) 0 0 0 var(--ds-severity-rail-color-healthy);
  }

  [data-aia-severity-rail="neutral"] {
    box-shadow: inset var(--ds-severity-rail-width-neutral) 0 0 0 var(--ds-severity-rail-color-neutral);
  }
}
```

- [x] **Step 5: Publica la ficha en el catálogo**

En `docs/design-system/component-catalog.json`, añade después de la ficha `state`:

```json
  {
    "id": "severity-rail",
    "family": "states-feedback",
    "kind": "canonical",
    "purpose": "Communicate severity as an ordinal step, independent of hue",
    "doNotUseFor": "State identity — that is the tint",
    "api": ["[data-aia-severity-rail]"],
    "markup": "inset rail on the row's inline start",
    "variants": ["urgent", "attention", "healthy", "neutral"],
    "states": ["normal"],
    "densities": ["compact"],
    "tokens": ["--ds-severity-rail-width-urgent", "--ds-severity-rail-color-urgent"],
    "responsive": ["desktop", "wide"],
    "accessibility": ["text-meaning"],
    "testSelector": "[data-aia-severity-rail]",
    "consumers": ["programacion-intermedia"],
    "replacement": null,
    "golden": null,
    "maturity": "candidate",
    "visualApproval": null
  },
```

- [x] **Step 6: Asegúrate de que la hoja se entrega**

```bash
grep -rn "states-feedback.css" public/css/aia-design-system.css scripts/ | head
```

Añade `severity-rail.css` al mismo punto de entrada donde ya se importa `states-feedback.css`. Después:

```bash
npm run test:design-system:static
```

Esperado: PASS en `unlayered-delivery` y `entrypoint-partition`. Si `unlayered-delivery` protesta, la hoja no está declarando su `@layer` o no está en el punto de entrada.

- [x] **Step 7: Corre los tests y comprueba que pasan**

```bash
node --test tests/design-system/severity-rail.test.mjs
```

Esperado: PASS, 6/6.

- [x] **Step 8: Commit**

```bash
git add public/css/tokens.css public/css/design-system/components/severity-rail.css docs/design-system/component-catalog.json tests/design-system/severity-rail.test.mjs public/css/aia-design-system.css
git commit -m "feat(ds): la primitiva del filete de gravedad, cuatro escalones"
```

---

### Task 3: Intermedia — el fondo pasa a identidad y la fila gana filete

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js:865-875` (clases de fila)
- Modify: `public/css/styles.css:3664-3725` (los ocho bloques `td.pi-state-*`)
- Modify: `public/css/styles.css:3886-3925` (los muestrarios de la leyenda)

**Interfaces:**
- Consumes: `data-aia-severity-rail` de la Task 2 y los `level`/`hue` de la Task 1.
- Produces: en cada `<tr>` de la rejilla, las clases `pi-row-state pi-state-<estado>` (ya existían) y el atributo `data-aia-severity-rail="<nivel>"`.

- [x] **Step 1: Escribe la sonda que falla**

Copia `goals/bug-coloreado-severidad/evidence/sonda-severidad.mjs` a
`goals/ds-f1a-estados-severidad/evidence/sonda-despues.mjs` y cambia solo el bloque final para que
además lea el filete:

```javascript
      railGrosor: getComputedStyle(celdaTexto).boxShadow,
      railNivel: tr.getAttribute('data-aia-severity-rail'),
```

- [x] **Step 2: Córrela y guarda el ANTES**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
node goals/ds-f1a-estados-severidad/evidence/sonda-despues.mjs
```

Esperado: `railNivel` es `null` en las nueve filas, y los fondos siguen siendo los cinco peldaños viejos. **Guarda esta salida**: es el antes contra el que se compara, computado contra computado.

- [x] **Step 3: Pon el atributo de nivel en la fila**

En `public/js/modules/programacion_intermedia/hot.js`, donde hoy se compone `rowClass` (~línea 870):

```javascript
    var rowStateClass = isHeader ? 'pdc-header' : ('pi-state-' + resolvedState);
    var rowClass = 'pi-row-state ' + rowStateClass;
    // El nivel viaja como atributo y no como clase: es lo que consume la
    // primitiva compartida, y una clase obligaria a cada modulo a inventar su
    // propio nombre para el mismo eje.
    var rowSeverity = (statePresentation[resolvedState] || {}).level || 'neutral';
```

y aplícalo con el resto de atributos de fila que el módulo ya escribe.

- [x] **Step 4: Cambia el fondo de estado→peldaño por estado→matiz**

En `public/css/styles.css`, sustituye los ocho bloques de `3664-3725` por uno solo que delegue en el matiz que la fila ya declara. Ejemplo para dos, repite el patrón para los ocho:

```css
/* El fondo dice QUE estado es, no cuan grave. Cada estado toma su matiz del
   catalogo; la gravedad la lleva el filete. Antes esto mapeaba los ocho estados
   a cinco peldanos de --ds-cell-state-*, una escalera ordinal que ningun
   contrato gobernaba: tres estados pintaban identico y «En Ejecucion Pendiente»
   salia en el verde de «controlado» siendo P1. */
.pi-page .handsontable td.pi-state-blocked-overdue-critical,
.pi-page #hot-container .handsontable td.pi-state-blocked-overdue-critical {
  background-color: var(--ds-state-tint-red) !important;
  color: var(--ds-active-text-primary) !important;
  border-color: var(--ds-active-border) !important;
}

.pi-page .handsontable td.pi-state-blocked-overdue,
.pi-page #hot-container .handsontable td.pi-state-blocked-overdue {
  background-color: var(--ds-state-tint-orange) !important;
  color: var(--ds-active-text-primary) !important;
  border-color: var(--ds-active-border) !important;
}
```

Los ocho matices, del contrato: `red`, `orange`, `violet`, `amber`, `teal`, `neutral`, `blue`, `green` para `blocked-overdue-critical`, `blocked-overdue`, `blocked-due`, `alert-1-week`, `alert-2-3-weeks`, `alert-4-6-weeks`, `execution-blocked`, `liberated-control`.

- [x] **Step 5: Alinea los muestrarios de la leyenda**

En `public/css/styles.css:3886-3925`, cambia cada `.pi-legend-modal-swatch.pi-state-*` para que use el **mismo** `--ds-state-tint-*` que su fila. Si no, la leyenda deja de describir la tabla — que es el defecto F0-012.

- [x] **Step 6: Mide el DESPUÉS y compáralo con el ANTES**

```bash
node goals/ds-f1a-estados-severidad/evidence/sonda-despues.mjs
```

Esperado, y compruébalo entrada por entrada:
- los ocho estados dan **ocho fondos distintos** (`PARES IDENTICOS (celda): ninguno`),
- `railNivel` es el nivel del contrato en las nueve filas,
- la leyenda da **ocho colores**, no cinco.

- [x] **Step 7: Corre la suite estática y anota qué goldens se movieron**

```bash
npm run test:design-system:static
npx playwright test tests/browser/programacion-intermedia.visual.mjs --workers=1
```

El golden **va a fallar**: el color cambió a propósito. **NO lo regeneres.** Anota el diff, guarda la captura nueva y **pídele al usuario aprobación visual por su nombre** antes de tocar el baseline.

- [x] **Step 8: Commit (sin el golden)**

```bash
git add public/js/modules/programacion_intermedia/hot.js public/css/styles.css goals/ds-f1a-estados-severidad/evidence
git commit -m "feat(pi): el fondo dice que estado es y el filete cuan grave"
```

---

### Task 4: El botón de agrupar por gravedad

**Files:**
- Modify: `views/programacion-intermedia/programacion_intermedia.view.php:47` (barra)
- Modify: `public/js/modules/programacion_intermedia/hot.js`

**Interfaces:**
- Consumes: `statePresentation[...].level` de la Task 1.
- Produces: `window.PIOrden.agrupar(datos)` — recibe el array de filas y devuelve **una copia nueva** ordenada por nivel descendente, estable dentro de cada nivel.

- [x] **Step 1: Escribe el test que falla**

Crea `tests/design-system/orden-gravedad.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import test from 'node:test';

const PESO = { urgent: 3, attention: 2, healthy: 1, neutral: 0 };

// Copia de la funcion que hot.js expone, para poder probarla sin navegador.
const agrupar = (filas, nivelDe) => [...filas]
  .map((fila, i) => ({ fila, i }))
  .sort((a, b) => (PESO[nivelDe(b.fila)] - PESO[nivelDe(a.fila)]) || (a.i - b.i))
  .map(({ fila }) => fila);

test('sube lo grave y conserva el orden del programa dentro de cada nivel', () => {
  const filas = [
    { id: 1, n: 'healthy' }, { id: 2, n: 'urgent' },
    { id: 3, n: 'healthy' }, { id: 4, n: 'urgent' },
  ];
  const r = agrupar(filas, (f) => f.n);
  assert.deepEqual(r.map((f) => f.id), [2, 4, 1, 3]);
});

test('no muta el array original', () => {
  const filas = [{ id: 1, n: 'healthy' }, { id: 2, n: 'urgent' }];
  const copia = JSON.parse(JSON.stringify(filas));
  agrupar(filas, (f) => f.n);
  assert.deepEqual(filas, copia);
});
```

- [x] **Step 2: Corre el test y comprueba que pasa el modelo**

```bash
node --test tests/design-system/orden-gravedad.test.mjs
```

Esperado: PASS, 2/2. (Este test fija el **contrato del orden**; el paso siguiente lo implementa igual en `hot.js`.)

- [x] **Step 3: Añade el botón a la barra**

En `views/programacion-intermedia/programacion_intermedia.view.php`, junto a los otros `aia-btn`:

```php
<button id="btn-agrupar-gravedad" type="button" class="aia-btn aia-btn--secondary" aria-pressed="false">Agrupar por gravedad <i class="fas fa-sort-amount-down ml-1"></i></button>
```

- [x] **Step 4: Implementa el orden en `hot.js`**

```javascript
  var PESO_NIVEL = { urgent: 3, attention: 2, healthy: 1, neutral: 0 };
  var agrupadoPorGravedad = false;

  // Devuelve una COPIA. La rejilla no ordena por si sola (columnSorting: false),
  // asi que el orden es del dato, y mutar el array original perderia la
  // secuencia del programa sin poder volver.
  function agruparPorGravedad(filas) {
    return filas
      .map(function (fila, i) { return { fila: fila, i: i }; })
      .sort(function (a, b) {
        // `getState` es la del modulo de reglas ya cargado
        // (public/js/modules/programacion_intermedia/stateMachine.js), la misma
        // que usa la linea 868 para resolver la clase de la fila. No se
        // introduce ninguna funcion nueva.
        var pa = PESO_NIVEL[(statePresentation[getState(a.fila)] || {}).level] || 0;
        var pb = PESO_NIVEL[(statePresentation[getState(b.fila)] || {}).level] || 0;
        return (pb - pa) || (a.i - b.i);
      })
      .map(function (x) { return x.fila; });
  }
```

y engancha el botón para alternar entre `agruparPorGravedad(datosOriginales)` y `datosOriginales`, actualizando `aria-pressed`.

- [x] **Step 5: Compruébalo en el navegador**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

Abre `http://localhost:8081/dev/entrar?u=test.R&p=<proyecto>`, ve a `/programacion-intermedia`, pulsa el botón y comprueba: sube lo grave, `aria-pressed` pasa a `true`, y al pulsar otra vez vuelve el orden del programa **exactamente** como estaba.

- [x] **Step 6: Commit**

```bash
git add views/programacion-intermedia/programacion_intermedia.view.php public/js/modules/programacion_intermedia/hot.js tests/design-system/orden-gravedad.test.mjs
git commit -m "feat(pi): boton para agrupar por gravedad, apagado por defecto"
```

---

### Task 5: Conciliar el golden de Intermedia

- [x] **Step 1: Genera la captura nueva y enséñasela al usuario**

```bash
npx playwright test tests/browser/programacion-intermedia.visual.mjs --workers=1
```

Guarda la imagen de `test-output/` y **enséñale al usuario el antes y el después a tamaño real**, sin reescalar.

- [x] **Step 2: Pide aprobación visual por su nombre**

Literal: «¿Apruebo el golden `<nombre exacto>` de `programacion-intermedia.visual.mjs`?». **Sin un sí explícito, para aquí.**

- [x] **Step 3: Solo con el sí, actualiza el baseline y commitea**

```bash
npx playwright test tests/browser/programacion-intermedia.visual.mjs --workers=1 --update-snapshots
git add tests/browser/__screenshots__ docs/design-system/manifests/programacion-intermedia.json
git commit -m "chore(golden): concilia el retrato de PI con el modelo de tres canales"
```

Si el manifiesto ancla el golden por `sha256`, actualízalo en el mismo commit.

---

### Task 6: Programa General y Plan de Compras

**Files:**
- Modify: `public/css/programa-general.css`, `public/css/design-system/adapters/programa-general-handsontable.css`
- Modify: `public/js/modules/programa_general/hot.js` (atributo de fila)
- Modify: `public/css/tokens.css` — solo si `/plan-compras` necesita alias nuevos, que no debería
- Test: `tests/browser/programa-general.visual.mjs`, `tests/browser/pdc-*.spec.mjs`

**Interfaces:**
- Consumes: `data-aia-severity-rail` (Task 2) y los `level`/`hue` del contrato para los módulos
  `programa-general` y `pdc`.

- [ ] **Step 1: Pon el atributo de nivel en la fila de `/programa-general`**

Mismo mecanismo que la Task 3, Step 3: se lee el nivel del estado resuelto y se escribe
`data-aia-severity-rail="<nivel>"` en el `<tr>`. Los siete estados y sus niveles salen del módulo
`programa-general` de `state-semantics.json`; no se inventa ninguno.

- [ ] **Step 2: Cambia el fondo a matiz en `/programa-general`**

Los siete matices del contrato son `green`, `blue`, `neutral`, `amber`, `orange`, `red`, `violet`
para `actividad-futura`, `en-curso`, `terminada`, `con-alerta-restricciones`, `debe-iniciar`,
`atrasada` y `sin-datos`. Cada regla de celda de estado consume `var(--ds-state-tint-<matiz>)` con
`color: var(--ds-active-text-primary)`.

- [ ] **Step 3: Mide antes y después con la sonda**

```bash
node goals/ds-f1a-estados-severidad/evidence/sonda-despues.mjs --ruta /programa-general
```

Esperado: siete fondos distintos, `PARES IDENTICOS (celda): ninguno`, y `railNivel` poblado en todas
las filas.

- [ ] **Step 4: Pide aprobación visual del golden de PG por su nombre. Commit.**

- [ ] **Step 5: `/plan-compras` — solo el filete**

Sus siete fondos **ya** consumen la paleta a través de `--pdc-*-bg`, que `state-tint-ladder.test.mjs`
verifica. Aquí no se toca el fondo: solo se añade el atributo de nivel en la fila y se comprueba que
ningún par de sus siete estados comparte matiz.

- [ ] **Step 6: Mide, pide aprobación del golden por su nombre, commit.**

---

### Task 7: Programación Semanal — desempatar dos parejas

**Files:**
- Modify: `docs/design-system/state-semantics.json` (módulo `programacion-semanal`)
- Modify: `tests/design-system/state-tint-ladder.test.mjs` (`KNOWN_HUE_COLLISIONS`)
- Modify: `public/css/programacion-semanal.css`

- [x] **Step 1: Asigna un matiz libre a cada pareja empatada**

Fase `programacion` usa hoy `red, orange, amber, amber, green` → libres: `violet`, `blue`, `teal`, `neutral`.
Fase `calificacion` usa hoy `red, amber, amber, green, blue` → libres: `violet`, `orange`, `teal`, `neutral`.

Propuesta a confirmar con el usuario antes de escribirla: «Por Comprometer» → `violet` (no puede comprometerse con lo que tiene, mismo gesto que `/pdc`), y «Sin Calificar» → `neutral` (ausencia de dato, no problema).

- [x] **Step 2: Reescribe la excepción, no la borres**

En `tests/design-system/state-tint-ladder.test.mjs`, `KNOWN_HUE_COLLISIONS` pasa a declarar que las repeticiones que quedan son **entre fases** y por tanto inocuas, con el porqué escrito: `stateMachine.js:58` resuelve una fase u otra según `semanalConfirmada`, así que las dos mitades nunca conviven.

- [ ] **Step 3: Aplica fondo por matiz y filete por nivel**, igual que la Task 3.

- [ ] **Step 4: Mide, pide aprobación del golden por su nombre, commit.**

---

### Task 8: Cierre del frente

- [ ] **Step 1: Verifica sobre el árbol correcto**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
bash scripts/publicar.sh --solo-verificar
```

- [ ] **Step 2: Anota qué queda huérfano**

`--ds-cell-state-*` deja de tener consumidor en estas cuatro superficies. **No lo borres en este frente:** busca quién más lo usa y anótalo.

```bash
grep -rn 'ds-cell-state-' public/css admin/public/css views | grep -v tokens.css
```

- [ ] **Step 3: Escribe `## Publicaciones` y `## Cierre` en `goals/ds-f1a-estados-severidad/goal.md`.**

- [ ] **Step 4: Publica**

```bash
bash scripts/publicar.sh
```

Y devuelve el contenedor a la raíz:

```bash
cd /Users/felipebenitez/Developer/lps-aia && LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

---

## Dónde parar

**Al terminar la Task 5, Programación Intermedia está completa y verificable por sí sola.** Las Tasks 6 y 7 extienden el modelo a las otras tres superficies con las primitivas ya hechas. Si el usuario ve Intermedia y no le convence, se para ahí y se revierte un frente de cinco tasks, no de ocho.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** severity-rail.css; data-aia-severity-rail en programa_general/hot.js; axisRules en state-semantics.json. Tasks 6-8 sustituidas bajo el contrato de ds-f1a, con constancia en goals/ds-f1a-estados-severidad/goal.md:163

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
