# F2a-2b-2 — Extracción de reglas, umbral único y montaje condicional: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sacar las reglas de habilitación de dentro de la configuración de Handsontable a un módulo propio y probado, unificar en 1180px los cuatro umbrales que hoy conviven, y dejar de instanciar la grilla por debajo de ese umbral.

**Architecture:** Tres cambios encadenados sobre los mismos dos módulos, en el orden que impone E8 (la red de pruebas ya está puesta: `programacion-semanal-enablement.mjs` y `programacion-intermedia-enablement.mjs`). Primero la extracción, que **no debe cambiar comportamiento** y por eso se valida con esa red en verde sin tocarla. Después el umbral, que sí cambia lo que ven usuarios de tablet. Por último el montaje condicional, que es el que produce el ahorro que justificaba E4.

**Tech Stack:** JavaScript de navegador sin transpilar (IIFE + jQuery, `public/js/modules/`), Node test runner para las pruebas puras, Playwright para las de navegador, Docker Compose para servir la aplicación.

**Spec:** [`2026-08-07-f2a-piloto-movil-programacion-design.md`](../specs/2026-08-07-f2a-piloto-movil-programacion-design.md), decisiones E3, E4, E7 y la corrección de premisa del 2026-08-08.

**Plan previo, ya ejecutado:** [`2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion.md`](2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion.md). Su informe de cierre trae la tabla de cobertura de las 22 reglas y tres hallazgos que este plan hereda.

## Global Constraints

- **La red de habilitación no se toca para que pase.** `tests/browser/programacion-semanal-enablement.mjs` (8 pruebas) y `tests/browser/programacion-intermedia-enablement.mjs` (6) son el contrato de este plan. Si una se pone roja, es una regresión del código, no una prueba desactualizada — salvo en la Task 4, donde el cambio de umbral las obliga a declarar viewport y eso se hace explícitamente.
- **La extracción (Tasks 1–3) no cambia ni una decisión.** Mismo resultado para las mismas entradas. Si algo parece un bug, se conserva el bug y se anota.
- **Cero cambio visual hasta la Task 4.** No se tocan `public/css`, ni goldens, ni manifiestos, en las tres primeras tareas.
- **Sesión de pruebas siempre por la puerta de desarrollo** (`/dev/entrar?u=test.A`), nunca por `/login`, nunca tecleando credenciales. `AGENTS.md` §Seguridad.
- **Un commit por tarea**, con staging selectivo. Nunca `.env` ni evidencia local.
- **El stack se levanta con** `docker compose up -d --build db app adminer`; la aplicación se sirve en `http://localhost:8081`.
- **Proyecto de pruebas:** las suites eligen el primero disponible entre `Preconstrucción Da Porto`, `Optimización Aeropuerto JMC`, `Da Porto`, `Prueba`. En la base local el que rinde filas es `Preconstrucción Da Porto`.

## Estado medido del código (2026-08-13, re-medir antes de tocar)

**Los umbrales son cuatro, no tres como decía la spec:**

| # | Dónde | Valor | Qué gobierna |
|---|---|---|---|
| U1 | `public/js/modules/programacion_semanal/legacyCards.js:4` | `matchMedia('(max-width: 767px)')` | Cards de CNP, CNC y CIC (tablas HTML planas + DataTables) |
| U2 | `public/js/modules/programacion_intermedia/hot.js:4371` | `matchMedia('(max-width: 768px)')` | Rama móvil de Intermedia |
| U3 | `public/css/handsontable-module.css:324` y `:343` | `max-width: 768px` / `min-width: 769px` | Oculta `#hot-container`, muestra `#mobile-card-view`. **Genérico: afecta a los dos módulos** |
| U4 | `public/css/programacion-semanal.css:936` y `public/css/programacion-intermedia.css:1070` | `max-width: 768px` | Estilo de las cards de cada módulo |

**Puntos de montaje de Handsontable:**

| Módulo | Constructor | Entrada |
|---|---|---|
| Semanal | `programacion_semanal/hot.js:2671` dentro de `updateOrInitHot()` (`:2621`) | `applyFiltersAndRender()` la llama en `:3072`, justo antes de `renderMobileCards(filtered)` en `:3073` |
| Intermedia | `programacion_intermedia/hot.js:3946` | dentro de `init()` |

**Reglas a extraer** (ubicaciones reales, las del plan anterior estaban corridas):

| Módulo | Función | Líneas |
|---|---|---|
| Semanal | `getPermiso`, `isDirectorRole`, `isSemanalEditorRole`, `getMaxSemana`, `getSemanalConfirmada` | `hot.js:279-301` |
| Semanal | `isUserAllowedToEdit`, `canManageToolbarActions`, `isPropReadOnly` | `hot.js:395-429` |
| Semanal | `editableProps` | `hot.js:37-47` |
| Intermedia | `getPermiso`, `isDirectorRole`, `isIntermediaEditorRole`, `getMaxSemana`, `getSemanalConfirmada`, `isUserAllowedToEdit` | `hot.js:596-633` |
| Intermedia | `buildPICellProperties` | `hot.js:955-980` |

**Specs de navegador que fijan un ancho entre 551 y 787** y que el cambio de umbral afecta:

| Spec | Anchos | Qué afirma en ese ancho |
|---|---|---|
| `programacion-semanal-subviews.mjs` | 768, 787 | Layout de **tabla** en tablet: barra en una fila, acciones dentro de su columna, superficies dark de `table.dataTable` |
| `programacion-semanal-cnp-lifecycle.mjs` | 767, 787 | Ciclo de vida de CNP cerca del umbral |
| `programacion-semanal-roles-phases.mjs` | 551, 787 | Roles y fases en anchos intermedios |

**Esto es lo que hace que la Task 4 no sea un retoque:** a 787px, con el umbral en 1180, esas vistas pasan a mostrar cards, así que las pruebas que verifican layout de tabla ahí dejan de tener objeto. Ver «Decisión pendiente» al final.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `public/js/modules/aia_ui/enablement-rules.js` | **Nuevo.** Funciones puras de decisión, sin DOM y sin Handsontable. Exporta `crearReglasSemanal(contexto)` y `crearReglasIntermedia(contexto)`, donde `contexto` es un objeto plano ya leído. |
| `public/js/modules/aia_ui/view-switch.js` | **Nuevo.** `shouldRenderCards(ancho)` contra el umbral único, y el aviso de recarga al cruzarlo en caliente con grilla montada. |
| `tests/design-system/enablement-rules.test.mjs` | **Nuevo.** Pruebas puras (`node --test`) de las dos fábricas de reglas y del umbral. |
| `public/js/modules/programacion_semanal/hot.js` | Deja de decidir: lee el contexto del DOM y delega en `enablement-rules`. |
| `public/js/modules/programacion_intermedia/hot.js` | Igual. |
| `public/js/modules/programacion_semanal/legacyCards.js` | Su `matchMedia` pasa a consumir el umbral único. |
| `public/css/handsontable-module.css`, `programacion-semanal.css`, `programacion-intermedia.css` | Los tres `768/769` pasan a `1180/1181`. |

**La frontera que importa:** `enablement-rules.js` no lee el DOM. Recibe `{ permiso, semana, maxSemana, semanalConfirmada }` ya resuelto y devuelve decisiones. Eso es lo que la hace probable sin navegador, y lo que permite que la card y la grilla consuman **la misma** función en vez de dos copias — que es el desincronizado contra el que S13 monta guardia.

---

### Task 1: Las reglas de Semanal, extraídas sin cambiar comportamiento

**Files:**
- Create: `public/js/modules/aia_ui/enablement-rules.js`
- Create: `tests/design-system/enablement-rules.test.mjs`
- Modify: `public/js/modules/programacion_semanal/hot.js:37-47`, `:395-429`
- Modify: `views/programacion-semanal/programacion_semanal.view.php` (cargar el módulo nuevo antes de `hot.js`)

**Interfaces:**
- Produces: `window.AIAEnablementRules.crearReglasSemanal(contexto)` → `{ isUserAllowedToEdit(), isPropReadOnly(prop), canManageToolbarActions(), editableProps }`. El `contexto` es `{ permiso, semana, maxSemana, semanalConfirmada }` con `permiso` ya normalizado. La Task 2 consume la misma forma para Intermedia; la Task 5 consume `isPropReadOnly` desde la card.

- [ ] **Step 1: Escribir las pruebas puras que fijan las cuatro cláusulas**

Crear `tests/design-system/enablement-rules.test.mjs`. Las cláusulas y su orden salen de `hot.js:411-429` — **el orden importa**: la de `Ejecutado_Real` se evalúa antes que la de semana histórica, y eso es deliberado (ver el informe de F2a-2b-1, sección «Seguimiento del hallazgo 1»).

```javascript
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { crearReglasSemanal } from '../../public/js/modules/aia_ui/enablement-rules.js';

const ctx = (over = {}) => ({
  permiso: 'A', semana: 5, maxSemana: 5, semanalConfirmada: 0, ...over,
});

test('S1: una prop fuera de editableProps es readOnly siempre', () => {
  const reglas = crearReglasSemanal(ctx());
  assert.equal(reglas.isPropReadOnly('Actividad'), true);
});

test('S2: en semana histórica solo A y D editan', () => {
  const historica = { semana: 3, maxSemana: 5 };
  assert.equal(crearReglasSemanal(ctx({ ...historica, permiso: 'A' })).isPropReadOnly('Ubicacion'), false);
  assert.equal(crearReglasSemanal(ctx({ ...historica, permiso: 'R' })).isPropReadOnly('Ubicacion'), true);
});

test('S3: Ejecutado_Real solo en fase de calificación, y para roles editores', () => {
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 0 })).isPropReadOnly('Ejecutado_Real'), true);
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 1 })).isPropReadOnly('Ejecutado_Real'), false);
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 1, permiso: 'V' })).isPropReadOnly('Ejecutado_Real'), true);
});

test('S3 antes que S2: Ejecutado_Real ignora la semana histórica, y es deliberado', () => {
  const reglas = crearReglasSemanal(ctx({ permiso: 'R', semana: 3, maxSemana: 5, semanalConfirmada: 1 }));
  assert.equal(reglas.isPropReadOnly('Ubicacion'), true);
  assert.equal(reglas.isPropReadOnly('Ejecutado_Real'), false);
});

test('S4: confirmada bloquea compromiso y responsables', () => {
  const reglas = crearReglasSemanal(ctx({ semanalConfirmada: 1 }));
  for (const prop of ['Compromiso', 'Sub_Contratista', 'Responsable_AIA']) {
    assert.equal(reglas.isPropReadOnly(prop), true, prop);
  }
});

test('el alias de permiso se respeta: P es D, U es V', () => {
  assert.equal(crearReglasSemanal(ctx({ permiso: 'P', semana: 3, maxSemana: 5 })).isPropReadOnly('Ubicacion'), false);
  assert.equal(crearReglasSemanal(ctx({ permiso: 'U' })).isPropReadOnly('Ubicacion'), true);
});
```

- [ ] **Step 2: Correr las pruebas y ver que fallan por módulo inexistente**

```bash
node --test tests/design-system/enablement-rules.test.mjs
```

Esperado: FAIL, «Cannot find module .../enablement-rules.js».

- [ ] **Step 3: Escribir el módulo copiando las cláusulas, sin reordenarlas**

Crear `public/js/modules/aia_ui/enablement-rules.js`. **Copia la lógica tal cual está en `hot.js:411-429`**; no la «mejores» ni cambies el orden de los `if`, que es justo lo que la prueba del paso 1 vigila. El módulo debe funcionar en dos mundos: `import` desde Node para las pruebas, y `<script>` clásico en el navegador.

```javascript
const ALIAS_PERMISO = { P: 'D', U: 'V' };
const EDITABLE_PROPS_SEMANAL = {
  Descripcion: true, Ubicacion: true, Sub_Contratista: true, Responsable_AIA: true,
  Compromiso: true, Ejecutado_Real: true, Categoria_CNC: true, CNC: true, Observaciones_CNC: true,
};

function normalizarPermiso(valor) {
  const permiso = String(valor || '').trim().toUpperCase();
  return ALIAS_PERMISO[permiso] || permiso;
}

const esDirector = (p) => p === 'A' || p === 'D';
const esEditorSemanal = (p) => ['A', 'D', 'R', 'DCV'].includes(p);

export function crearReglasSemanal(contexto) {
  const permiso = normalizarPermiso(contexto.permiso);
  const semana = parseInt(contexto.semana, 10);
  const maxSemana = parseInt(contexto.maxSemana, 10);
  const confirmada = parseInt(contexto.semanalConfirmada, 10) || 0;

  function isUserAllowedToEdit() {
    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return esDirector(permiso);
    }
    return esEditorSemanal(permiso);
  }

  function isPropReadOnly(prop) {
    if (!EDITABLE_PROPS_SEMANAL[prop]) return true;
    // El orden de estas cuatro cláusulas es contrato, no estilo: Ejecutado_Real
    // se resuelve ANTES que la semana histórica a propósito, porque calificar
    // una semana ya cerrada está permitido (el servidor hace lo mismo en
    // LpsWeekEditPolicy::allows con $qualification = true).
    if (prop === 'Ejecutado_Real') {
      return confirmada !== 1 || !esEditorSemanal(permiso);
    }
    if (!isUserAllowedToEdit()) return true;
    if (['Compromiso', 'Sub_Contratista', 'Responsable_AIA'].includes(prop) && confirmada === 1) {
      return true;
    }
    return false;
  }

  return {
    isUserAllowedToEdit,
    isPropReadOnly,
    canManageToolbarActions: isUserAllowedToEdit,
    editableProps: EDITABLE_PROPS_SEMANAL,
  };
}

if (typeof window !== 'undefined') {
  window.AIAEnablementRules = Object.assign(window.AIAEnablementRules || {}, { crearReglasSemanal });
}
```

- [ ] **Step 4: Correr las pruebas y verlas pasar**

```bash
node --test tests/design-system/enablement-rules.test.mjs
```

Esperado: 6 pruebas en verde.

- [ ] **Step 5: Hacer que `hot.js` delegue, conservando sus nombres**

En `programacion_semanal/hot.js`, sustituir los cuerpos de `isUserAllowedToEdit`, `isPropReadOnly` y `canManageToolbarActions` por una delegación que lee el contexto del DOM en cada llamada — **eso no cambia: las reglas se siguen evaluando por celda y leyendo el estado actual**.

```javascript
  function reglasActuales() {
    return window.AIAEnablementRules.crearReglasSemanal({
      permiso: $('#permiso_canonico').val(),
      semana: getSemana(),
      maxSemana: getMaxSemana(),
      semanalConfirmada: getSemanalConfirmada(),
    });
  }

  function isUserAllowedToEdit() { return reglasActuales().isUserAllowedToEdit(); }
  function canManageToolbarActions() { return reglasActuales().canManageToolbarActions(); }
  function isPropReadOnly(prop) { return reglasActuales().isPropReadOnly(prop); }
```

Deja `editableProps` donde está por ahora: lo consumen `beforeChange` (`:2869`) y `renderMobileEditableMetric` (`:3395`), y moverlo es la Task 5.

- [ ] **Step 6: Cargar el módulo en la vista, antes de `hot.js`**

En `views/programacion-semanal/programacion_semanal.view.php`, añadir el `<script>` de `enablement-rules.js` **antes** del de `hot.js`. Sigue el patrón de carga que ya usan los otros módulos de esa vista; no inventes uno nuevo.

- [ ] **Step 7: La red de habilitación en verde, sin tocarla**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs --workers=1
```

Esperado: 8 passed. **Si alguna falla, la extracción cambió comportamiento**: arregla el módulo, nunca la prueba.

- [ ] **Step 8: Commit**

```bash
git add public/js/modules/aia_ui/enablement-rules.js tests/design-system/enablement-rules.test.mjs public/js/modules/programacion_semanal/hot.js views/programacion-semanal/programacion_semanal.view.php
git commit -m "refactor(programacion-semanal): las reglas de habilitacion salen a un modulo probado"
```

---

### Task 2: Las reglas de Intermedia, extraídas igual

**Files:**
- Modify: `public/js/modules/aia_ui/enablement-rules.js` (añadir `crearReglasIntermedia`)
- Modify: `tests/design-system/enablement-rules.test.mjs`
- Modify: `public/js/modules/programacion_intermedia/hot.js:596-633`, `:955-980`
- Modify: `views/programacion-intermedia/programacion_intermedia.view.php`

**Interfaces:**
- Consumes: el archivo y el patrón de la Task 1.
- Produces: `crearReglasIntermedia(contexto)` → `{ isUserAllowedToEdit(), puedeEditarCelda({ prop, esHeader, tieneResponsable, esRestriccion }) }`. Devuelve **solo el booleano de decisión**; las clases CSS las sigue componiendo `hot.js`, porque `pi-cell-locked-resp` la consume el renderer y moverla es otro cambio.

- [ ] **Step 1: Escribir las pruebas puras de las reglas de Intermedia**

Añadir a `tests/design-system/enablement-rules.test.mjs`. Las cláusulas salen de `hot.js:619-633` y `:955-965`. **Dos diferencias con Semanal que no hay que perder**: aquí `semanalConfirmada === 1` bloquea todo sin excepción de rol, y `__shared_selected` ignora rol y fase.

```javascript
import { crearReglasIntermedia } from '../../public/js/modules/aia_ui/enablement-rules.js';

const ctxPI = (over = {}) => ({
  permiso: 'A', semana: 5, maxSemana: 5, semanalConfirmada: 0,
  editableProps: { Observaciones: true, Sub_Contratista: true, Responsable_AIA: true, D_y_E: true },
  ...over,
});
const celda = (over = {}) => ({
  prop: 'Observaciones', esHeader: false, tieneResponsable: true, esRestriccion: false, ...over,
});

test('I2: confirmada bloquea todo, sin excepción de rol', () => {
  const reglas = crearReglasIntermedia(ctxPI({ semanalConfirmada: 1 }));
  assert.equal(reglas.puedeEditarCelda(celda()), false);
});

test('I2: en histórica solo A y D', () => {
  const historica = { semana: 3, maxSemana: 5 };
  assert.equal(crearReglasIntermedia(ctxPI({ ...historica, permiso: 'D' })).puedeEditarCelda(celda()), true);
  assert.equal(crearReglasIntermedia(ctxPI({ ...historica, permiso: 'R' })).puedeEditarCelda(celda()), false);
});

test('I3: una fila cabecera no edita ninguna columna', () => {
  const reglas = crearReglasIntermedia(ctxPI());
  assert.equal(reglas.puedeEditarCelda(celda({ esHeader: true })), false);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: '__shared_selected', esHeader: true })), false);
});

test('I4: una restricción sin responsable queda bloqueada', () => {
  const reglas = crearReglasIntermedia(ctxPI());
  assert.equal(reglas.puedeEditarCelda(celda({ prop: 'D_y_E', esRestriccion: true, tieneResponsable: true })), true);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: 'D_y_E', esRestriccion: true, tieneResponsable: false })), false);
});

test('I5: __shared_selected ignora rol y fase en fila normal', () => {
  const reglas = crearReglasIntermedia(ctxPI({ permiso: 'V', semanalConfirmada: 1 }));
  assert.equal(reglas.puedeEditarCelda(celda()), false);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: '__shared_selected' })), true);
});
```

- [ ] **Step 2: Correr y ver el rojo**

```bash
node --test tests/design-system/enablement-rules.test.mjs
```

Esperado: las 6 de Semanal en verde, las 5 nuevas en rojo por `crearReglasIntermedia` no exportada.

- [ ] **Step 3: Implementar `crearReglasIntermedia`**

`editableProps` llega por contexto porque en Intermedia es **dinámico**: se construye desde `/api/general/restriction-config` (`hot.js:205-230`). El módulo no hace peticiones.

```javascript
const esEditorIntermedia = (p) => ['A', 'D', 'R', 'DCV'].includes(p);

export function crearReglasIntermedia(contexto) {
  const permiso = normalizarPermiso(contexto.permiso);
  const semana = parseInt(contexto.semana, 10);
  const maxSemana = parseInt(contexto.maxSemana, 10);
  const confirmada = parseInt(contexto.semanalConfirmada, 10) || 0;
  const editableProps = contexto.editableProps || {};

  function isUserAllowedToEdit() {
    if (confirmada === 1) return false;
    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return esDirector(permiso);
    }
    return esEditorIntermedia(permiso);
  }

  function puedeEditarCelda({ prop, esHeader, tieneResponsable, esRestriccion }) {
    if (prop === '__shared_selected') return !esHeader;
    const bloqueadaPorResponsable = Boolean(esRestriccion) && !esHeader && tieneResponsable === false;
    return Boolean(editableProps[prop]) && !esHeader && isUserAllowedToEdit() && !bloqueadaPorResponsable;
  }

  return { isUserAllowedToEdit, puedeEditarCelda };
}
```

Añadir `crearReglasIntermedia` al `window.AIAEnablementRules` del final del archivo.

- [ ] **Step 4: Correr y ver las 11 en verde**

```bash
node --test tests/design-system/enablement-rules.test.mjs
```

- [ ] **Step 5: Hacer que `buildPICellProperties` delegue**

En `programacion_intermedia/hot.js:955-980`, la decisión (`canEdit`) pasa a venir del módulo; **la composición de clases se queda donde está**, porque `pi-cell-locked-resp` la lee el renderer (`:3147`) y `pi-cell-dropdown` depende de `dropdownProps`.

```javascript
  function buildPICellProperties(baseClass, prop, meta) {
    var isSharedSelector = prop === '__shared_selected';
    var isRestrictionCell = restrictionProps.indexOf(prop) > -1 && !meta.isHeader;
    var isLockedByResponsable = isRestrictionCell && meta.hasResponsable === false;
    var canEdit = window.AIAEnablementRules.crearReglasIntermedia({
      permiso: $('#permiso_canonico').val(),
      semana: getSemana(),
      maxSemana: getMaxSemana(),
      semanalConfirmada: getSemanalConfirmada(),
      editableProps: editableProps,
    }).puedeEditarCelda({
      prop: prop,
      esHeader: meta.isHeader,
      tieneResponsable: meta.hasResponsable,
      esRestriccion: restrictionProps.indexOf(prop) > -1,
    });
    // ... el resto del cuerpo actual, sin cambios
```

**Ojo con `_canEditGlobal`:** hoy se fija en `buildRowClassCache()` (`:1085`) y `buildPICellProperties` lo lee. Al delegar, la decisión se recalcula por celda. Eso **puede cambiar el rendimiento**, no el resultado. Si la Task 4 detecta lentitud, se cachea el objeto de reglas por pasada de render, no se vuelve atrás.

- [ ] **Step 6: Cargar el módulo en la vista de Intermedia**

Igual que en la Task 1, `<script>` antes de `hot.js`.

- [ ] **Step 7: La red de Intermedia en verde, sin tocarla**

```bash
npx playwright test tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

Esperado: 6 passed.

- [ ] **Step 8: Commit**

```bash
git add public/js/modules/aia_ui/enablement-rules.js tests/design-system/enablement-rules.test.mjs public/js/modules/programacion_intermedia/hot.js views/programacion-intermedia/programacion_intermedia.view.php
git commit -m "refactor(programacion-intermedia): las reglas de habilitacion salen al modulo compartido"
```

---

### Task 3: Las dos redes juntas y la suite estática, antes de tocar nada visual

Punto de control: la extracción está completa y **el producto se ve y se comporta igual**. Es la última oportunidad de detectar una regresión antes de que el cambio de umbral empiece a mover cosas visibles y enturbie el diagnóstico.

**Files:** ninguno nuevo.

- [ ] **Step 1: Las dos redes, tres corridas seguidas**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

Esperado: 14 passed, tres veces seguidas sin intermitencias.

- [ ] **Step 2: Las suites vecinas de los dos módulos, que no deben moverse**

```bash
npx playwright test tests/browser/programacion-semanal-sprint.mjs tests/browser/programacion-semanal-roles-phases.mjs tests/browser/programacion-semanal-cnp-lifecycle.mjs --workers=1
```

Esperado: el mismo resultado que antes de la Task 1. **Mídelo antes de empezar la Task 1 y guarda la salida**, porque si alguna ya estaba roja, saberlo después no sirve de nada.

- [ ] **Step 3: La suite estática**

```bash
npm run test:design-system:static
```

Esperado: 8/8.

- [ ] **Step 4: Commit solo si hubo arreglos**

Si los pasos anteriores no exigieron cambios, no hay commit: es un control, no una entrega.

---

### Task 4: Umbral único a 1180

**Aquí empieza el cambio visible.** Por debajo de 1180 se ven cards; por encima, grilla. **La tablet pasa a recibir cards**, que es el efecto buscado por E3 y la razón por la que existe este piloto: la grilla no es usable bajo 1180.

**Files:**
- Create: `public/js/modules/aia_ui/view-switch.js`
- Modify: `tests/design-system/enablement-rules.test.mjs` (o un archivo hermano para el umbral)
- Modify: `public/js/modules/programacion_semanal/legacyCards.js:4`
- Modify: `public/js/modules/programacion_intermedia/hot.js:4371`
- Modify: `public/css/handsontable-module.css:324`, `:343`
- Modify: `public/css/programacion-semanal.css:936`
- Modify: `public/css/programacion-intermedia.css:1070`

**Interfaces:**
- Produces: `window.AIAViewSwitch.shouldRenderCards(ancho)` → booleano, y la constante `UMBRAL_CARDS = 1180`. La Task 5 la consume para decidir el montaje.

- [ ] **Step 1: Escribir la prueba del borde**

```javascript
import { shouldRenderCards, UMBRAL_CARDS } from '../../public/js/modules/aia_ui/view-switch.js';

test('el umbral es 1180 y el borde cae del lado de la grilla', () => {
  assert.equal(UMBRAL_CARDS, 1180);
  assert.equal(shouldRenderCards(1179), true);
  assert.equal(shouldRenderCards(1180), false);
  assert.equal(shouldRenderCards(390), true);
  assert.equal(shouldRenderCards(1440), false);
});
```

- [ ] **Step 2: Correr y ver el rojo**

```bash
node --test tests/design-system/enablement-rules.test.mjs
```

- [ ] **Step 3: Escribir `view-switch.js`**

```javascript
export const UMBRAL_CARDS = 1180;

export function shouldRenderCards(ancho) {
  const medido = Number(ancho);
  if (!Number.isFinite(medido)) return false;
  return medido < UMBRAL_CARDS;
}

if (typeof window !== 'undefined') {
  window.AIAViewSwitch = { UMBRAL_CARDS, shouldRenderCards };
}
```

- [ ] **Step 4: Correr y ver el verde**

- [ ] **Step 5: Mover los cuatro umbrales**

- `legacyCards.js:4`: `matchMedia('(max-width: 1179px)')`.
- `programacion_intermedia/hot.js:4371`: `matchMedia('(max-width: 1179px)')`.
- `handsontable-module.css`: `max-width: 768px` → `max-width: 1179px`; `min-width: 769px` → `min-width: 1180px`.
- `programacion-semanal.css:936` y `programacion-intermedia.css:1070`: `max-width: 768px` → `max-width: 1179px`.

**Usa 1179/1180, no 767/768 desplazados:** el borde es «menor que 1180 son cards», y `max-width: 1179px` es su traducción exacta en CSS.

- [ ] **Step 6: Revisar una a una las specs que fijan anchos intermedios**

Las tres del censo (`programacion-semanal-subviews.mjs` en 768 y 787, `programacion-semanal-cnp-lifecycle.mjs` en 767 y 787, `programacion-semanal-roles-phases.mjs` en 551 y 787). Para cada prueba, decide con este criterio y **escribe la decisión en el commit**:

- Si lo que verifica es **el layout de escritorio** y usaba 787 solo porque «no era móvil»: sube el viewport a 1180 o más. Se actualiza el ancho, **nunca la aserción**.
- Si lo que verifica es **el comportamiento en tablet con tabla** (las de `subviews` sobre `table.dataTable`, la barra en una fila, las acciones en su columna): ese escenario **deja de existir en el producto**. No la adaptes para que pase: márcala con `test.skip` y un comentario que diga que E3 retiró la tabla en tablet, o reescríbela contra las cards — pero eso último es trabajo de la Task 5, así que aquí basta el skip documentado.

- [ ] **Step 7: Correr las suites afectadas y la red**

```bash
npx playwright test tests/browser/programacion-semanal-subviews.mjs tests/browser/programacion-semanal-cnp-lifecycle.mjs tests/browser/programacion-semanal-roles-phases.mjs --workers=1
npx playwright test tests/browser/programacion-semanal-enablement.mjs tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

La red debe seguir en 14/14: sus pruebas de grilla corren en el viewport por defecto de Playwright, y la de S13 fija 390 explícitamente.

- [ ] **Step 8: Commit**

```bash
git add public/js/modules/aia_ui/view-switch.js tests/design-system/enablement-rules.test.mjs public/js/modules/programacion_semanal/legacyCards.js public/js/modules/programacion_intermedia/hot.js public/css/handsontable-module.css public/css/programacion-semanal.css public/css/programacion-intermedia.css tests/browser/
git commit -m "feat(programacion): umbral unico de 1180 para cards, la tablet recibe cards"
```

---

### Task 5: Dejar de montar Handsontable bajo el umbral

El ahorro que justificaba E4. Hoy las cards se pintan siempre y el CSS esconde la grilla, así que **Handsontable se instancia igual en el celular**: unos cuantos miles de nodos que nadie ve.

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:2621-2680`, `:3072-3073`
- Modify: `public/js/modules/programacion_intermedia/hot.js:3946`
- Create: `tests/browser/programacion-movil-sin-grilla.mjs`
- Modify: `.gitignore` (excepción para el archivo nuevo, ver más abajo)

**Interfaces:**
- Consumes: `window.AIAViewSwitch.shouldRenderCards` de la Task 4.

- [ ] **Step 1: Escribir la prueba que sostiene el ahorro**

Sin esta prueba, «no se instancia» es una afirmación sin respaldo. Crear `tests/browser/programacion-movil-sin-grilla.mjs`:

```javascript
import { test, expect } from '@playwright/test';
import { login, selectProject } from './support/session.mjs';

const PROJECT_CANDIDATES = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

async function abrir(page, ruta) {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  for (const name of PROJECT_CANDIDATES) {
    const card = page.locator('.project-item').filter({
      has: page.getByRole('heading', { name, exact: true }),
    });
    if (await card.count()) {
      await card.locator('button[type="submit"], .btn-enter').click();
      await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
      break;
    }
  }
  await page.goto(ruta);
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
}

for (const [modulo, ruta] of [['semanal', '/programacion-semanal'], ['intermedia', '/programacion-intermedia']]) {
  test(`${modulo}: en 390x844 no existe ni un nodo de Handsontable`, async ({ page }) => {
    await abrir(page, ruta);
    const nodos = await page.locator('#hot-container .handsontable').count();
    expect(nodos, 'Handsontable se montó bajo el umbral').toBe(0);
    const cards = await page.locator('#mobile-card-view').count();
    expect(cards, 'Sin grilla y sin cards, la vista quedaría vacía').toBeGreaterThan(0);
  });
}
```

- [ ] **Step 2: Registrar el archivo en `.gitignore`**

`tests/browser/*` está ignorado con lista blanca. Sin esta línea el archivo no viaja a un clon fresco:

```
!tests/browser/programacion-movil-sin-grilla.mjs
```

- [ ] **Step 3: Correr y ver el rojo**

```bash
npx playwright test tests/browser/programacion-movil-sin-grilla.mjs --workers=1
```

Esperado: FAIL en los dos módulos, porque hoy la grilla se monta siempre.

- [ ] **Step 4: No instanciar en Semanal**

En `applyFiltersAndRender()` (`hot.js:3072`), condicionar el montaje y dejar siempre el pintado de cards:

```javascript
    if (!window.AIAViewSwitch.shouldRenderCards(window.innerWidth)) {
      updateOrInitHot(filtered);
    }
    renderMobileCards(filtered);
```

**Cuidado con lo que se rompe:** hay código que asume `hot` existe (`getSourceRowDataByVisualRow`, `commitMobileCardValue` en `:3465`, `getFilteredRows`). Recorre los usos de `hot.` que corren en la rama móvil y protégelos. `commitMobileCardValue` es el más importante: es cómo se guarda desde la card, y hoy hace `getSourceRowDataByVisualRow(hot, rowIndex)`. Si `hot` es nulo, la card debe seguir guardando — usando `masterData` por índice, que es la misma fuente que alimenta a la grilla.

- [ ] **Step 5: No instanciar en Intermedia**

Mismo tratamiento en `programacion_intermedia/hot.js:3946`, dentro de `init()`.

- [ ] **Step 6: Correr la prueba nueva y verla pasar**

```bash
npx playwright test tests/browser/programacion-movil-sin-grilla.mjs --workers=1
```

Esperado: 2 passed.

- [ ] **Step 7: Comprobar que la card sigue guardando sin grilla**

```bash
npx playwright test tests/browser/programacion-semanal-sprint.mjs --workers=1
```

Esa suite ya ejercita el guardado móvil a 390x844. Si falla, es la regresión del paso 4, no un fallo de la suite.

- [ ] **Step 8: La red completa y la estática**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs tests/browser/programacion-intermedia-enablement.mjs --workers=1
npm run test:design-system:static
```

**S13 es la que importa aquí:** comprueba que la card ofrece input exactamente cuando la grilla lo haría. Sin grilla montada, la regla la sigue dando `enablement-rules`, así que debe seguir en verde. Si se pone roja, la card se desincronizó de la regla — que es justo lo que la red existía para detectar.

- [ ] **Step 9: Commit**

```bash
git add public/js/modules/programacion_semanal/hot.js public/js/modules/programacion_intermedia/hot.js tests/browser/programacion-movil-sin-grilla.mjs .gitignore
git commit -m "perf(programacion): la grilla no se instancia bajo el umbral, las cards se bastan"
```

---

## Condición de hecho

1. `node --test tests/design-system/enablement-rules.test.mjs` en verde, cubriendo las cláusulas de los dos módulos y el borde del umbral.
2. Las dos redes de habilitación en 14/14, **sin haber editado sus aserciones** — salvo los viewports que la Task 4 declare y justifique.
3. En `390x844`, ninguna de las dos vistas instancia Handsontable, comprobado contando nodos en el DOM.
4. `npm run test:design-system:static` en 8/8.
5. Las specs de anchos intermedios, resueltas una a una con su decisión escrita: viewport actualizado, o `skip` documentado por retirada del escenario. **Ninguna aserción debilitada para pasar.**
6. Los goldens de escritorio de ambos módulos, sin cambios.

## Fuera de alcance

La evidencia móvil y los goldens `390x844` de los dos módulos (necesitan aprobación visual explícita y van en su propia tanda). Que las cards de Intermedia editen. Los otros 13 módulos. El tema claro (F3). La SPA del Plan de Compras.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| La extracción cambia una decisión sin que nadie lo note. | Las Tasks 1–3 no tocan CSS ni goldens, y la red de 14 pruebas corre después de cada una. Es exactamente para esto que se construyó primero. |
| Al dejar de montar la grilla, algo que asumía `hot` presente revienta en móvil. | El paso 4 de la Task 5 lo dice explícitamente y nombra el caso crítico (`commitMobileCardValue`). La suite `programacion-semanal-sprint.mjs` ya ejercita el guardado móvil. |
| Recalcular las reglas por celda en Intermedia (antes cacheadas en `_canEditGlobal`) cuesta rendimiento. | Se mide en la Task 4; si duele, se cachea el objeto de reglas por pasada de render. No se revierte la extracción. |
| Tres pruebas de tablet quedan sin objeto. | Se declara en el commit y se decide entre `skip` documentado y reescritura contra cards. No se adaptan para que pasen. |

## Decisión pendiente, para el gate

**Las pruebas de tablet de CNP/CNC/CIC verifican layout de tabla a 787px, y con el umbral en 1180 ese escenario desaparece del producto.** Son al menos tres en `programacion-semanal-subviews.mjs` («reúne la barra en una fila», «mantiene todas las superficies dark», «contiene las acciones dentro de su columna»). Las opciones, con su coste:

- **(a) `skip` documentado ahora, reescritura contra cards en la tanda de evidencia móvil.** Barato, y deja la deuda visible en el propio archivo. Es lo que el plan asume por defecto.
- **(b) Reescribirlas contra las cards dentro de este plan.** Más caro y mezcla dos trabajos: la Task 4 pasaría a tocar aserciones visuales, que es justo lo que las tres primeras tareas evitan a propósito.
- **(c) Conservar la tabla en tablet** (umbral distinto para las subvistas de DataTables). Contradice E3 y reintroduce el segundo umbral que este plan viene a eliminar.
