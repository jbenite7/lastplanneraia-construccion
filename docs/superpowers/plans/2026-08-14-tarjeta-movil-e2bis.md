---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-14
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-14-tarjeta-movil-e2bis.md
resumen: Que las tarjetas móviles de Programación Semanal e Intermedia adopten el modelo decidido —resumen accionable arriba, detalle plegado— bajando de 562 y 380 px a…
---

# Tarjeta móvil E2-bis: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las tarjetas móviles de Programación Semanal e Intermedia adopten el modelo decidido —resumen accionable arriba, detalle plegado— bajando de 562 y 380 px a ≈325 y ≈275, y que Intermedia pueda por fin liberar restricciones desde el teléfono.

**Architecture:** Las dos tarjetas comparten forma pero no código: cada módulo conserva su propio renderizador (`renderMobileCard` en Semanal, `createMobileCard` en Intermedia) porque uno emite HTML por concatenación y el otro construye nodos del DOM, y unificarlos es un refactor aparte. Lo que sí se comparte es el contrato visual y las clases CSS. El plegado usa `<details>`/`<summary>` nativo —patrón que el repo ya usa (`views/bi/control-tower.php:649`)— para no escribir JS de apertura ni gestionar `aria-expanded` a mano.

**Tech Stack:** JavaScript de navegador sin transpilar, CSS con tokens `--ds-*`, Playwright para navegador, Node test runner para lo puro.

**Spec:** [`2026-08-07-f2a-piloto-movil-programacion-design.md`](../specs/2026-08-07-f2a-piloto-movil-programacion-design.md), adenda del 2026-08-14: decisiones `E2-bis-a` … `E2-bis-e`.

## Global Constraints

- **La cara visible lleva cinco elementos** (`E2-bis-a`): código, actividad, chip, avance y Responsable AIA. En Semanal, además, el campo editable de la fase.
- **En Semanal la edición vive en la cara visible** (`E2-bis-b`), no dentro del desplegable.
- **El chip cuenta las cinco restricciones duras**, en los dos módulos (`E2-bis-e`). Las dos blandas siguen editables en el desplegable pero **no cuentan como bloqueo**.
- **El capítulo se separa del título** con el patrón de `ActivityMatcherService::extractChapter()`, no se trunca (`E2-bis-d`).
- **Las frases de «qué falta» salen de `focus`**, nunca se inventan (`E2-bis-d`).
- **Cero regresión de escritorio.** Por encima de 1180 px no se toca nada: los goldens `1180x820` y `1440x900` de ambos módulos no deben moverse.
- **La red de habilitación no se toca para que pase.** Si una prueba de `programacion-semanal-enablement.mjs` o `programacion-intermedia-enablement.mjs` se pone roja, es regresión del código.
- **Sesión de pruebas por la puerta de desarrollo** (`/dev/entrar?u=test.A`), nunca `/login`. `AGENTS.md` §Seguridad.
- **Un commit por tarea.** El stack se levanta con `docker compose up -d --build db app adminer`.

## Estado medido del código (2026-08-14, re-medir antes de tocar)

| Qué | Dónde |
|---|---|
| Tarjeta de Semanal | `programacion_semanal/hot.js:3441-3462` (`renderMobileCard`) |
| Su chip y contador | `:3435-3438` (`renderMobileStateButton`), `:976-996` (`getOperationalStateSummary`) |
| `focus`, hoy solo para lector de pantalla | `:1020` lo mete en el `aria-label`; `:3438` pinta solo el número |
| Restricciones que cuenta Semanal | `:633-650` (`getConfigRestrictions`) — lee `hardRestrictions` |
| Tarjeta de Intermedia | `programacion_intermedia/hot.js:4328-4366` (`createMobileCard`), `:4311-4326` (`appendMobileField`) |
| Estado «listo» de Intermedia | `programacion_intermedia/stateMachine.js:150-159` (`isReadyToCommit`) — **solo duras** |
| Reglas de edición ya extraídas | `aia_ui/enablement-rules.js` → `crearReglasIntermedia().puedeEditarCelda()` |
| CSS de la tarjeta de Semanal | `public/css/programacion-semanal.css:1109-1170` |
| CSS de la tarjeta de Intermedia | `public/css/programacion-intermedia.css:1070+` |

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `public/js/modules/aia_ui/card-title.js` | **Nuevo.** `separarCapitulo(actividad)` → `{ titulo, capitulo }`. Única pieza compartida: los dos módulos la consumen y es lógica pura, testeable sin navegador. |
| `tests/design-system/card-title.test.mjs` | **Nuevo.** Sus pruebas puras. |
| `public/js/modules/programacion_semanal/hot.js` | Reestructura `renderMobileCard` a cara visible + `<details>`; el chip pasa a mostrar `focus`. |
| `public/js/modules/programacion_intermedia/hot.js` | Reestructura `createMobileCard` igual, y añade las siete restricciones editables en el desplegable. |
| `public/css/programacion-semanal.css`, `programacion-intermedia.css` | Estilo de la cara visible, el capítulo, la línea de foco y el `<details>`. |
| `tests/browser/programacion-movil-tarjeta.mjs` | **Nuevo.** Las pruebas de navegador del modelo, para los dos módulos. |

---

### Task 1: `separarCapitulo`, la única pieza compartida

**Files:**
- Create: `public/js/modules/aia_ui/card-title.js`
- Create: `tests/design-system/card-title.test.mjs`

**Interfaces:**
- Produces: `separarCapitulo(actividad)` → `{ titulo: string, capitulo: string|null }`. Las Tasks 2 y 4 la consumen.

- [ ] **Step 1: Escribir las pruebas**

Los casos salen del patrón real de `ActivityMatcherService::extractChapter()` (`src/Services/ActivityMatcherService.php:104-120`), que ya trata las tres formas: con `<small>`, sin él, y con espacios sueltos.

```javascript
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { separarCapitulo } from '../../public/js/modules/aia_ui/card-title.js';

test('separa el capitulo envuelto en small', () => {
  const r = separarCapitulo('Contrato estudio de suelos <small>[Capítulo: CONTRATOS, PRECONSTRUCCIÓN DA PORTO]</small>');
  assert.equal(r.titulo, 'Contrato estudio de suelos');
  assert.equal(r.capitulo, 'CONTRATOS, PRECONSTRUCCIÓN DA PORTO');
});

test('separa el capitulo sin small y con espacios', () => {
  const r = separarCapitulo('Excavación manual  [ Capítulo :  PRELIMINARES ] ');
  assert.equal(r.titulo, 'Excavación manual');
  assert.equal(r.capitulo, 'PRELIMINARES');
});

test('acepta Capitulo sin tilde', () => {
  assert.equal(separarCapitulo('Muro [Capitulo: ESTRUCTURA]').capitulo, 'ESTRUCTURA');
});

test('sin capitulo devuelve null y el titulo intacto', () => {
  const r = separarCapitulo('Vaciado de placa');
  assert.equal(r.titulo, 'Vaciado de placa');
  assert.equal(r.capitulo, null);
});

test('entrada vacia o nula no revienta', () => {
  assert.deepEqual(separarCapitulo(''), { titulo: '', capitulo: null });
  assert.deepEqual(separarCapitulo(null), { titulo: '', capitulo: null });
});

test('quita etiquetas HTML del titulo, como hace getPlainActivityLabel', () => {
  assert.equal(separarCapitulo('<b>Muro</b> en bloque').titulo, 'Muro en bloque');
});
```

- [ ] **Step 2: Correr y ver el rojo**

```bash
node --test tests/design-system/card-title.test.mjs
```

Esperado: FAIL, «Cannot find module .../card-title.js».

- [ ] **Step 3: Escribir la pieza**

```javascript
const PATRON_CAPITULO = /<small>\s*\[\s*Cap[ií]tulo\s*:\s*([^\]]+?)\s*\]\s*<\/small>|\[\s*Cap[ií]tulo\s*:\s*([^\]]+?)\s*\]/iu;

function quitarEtiquetas(valor) {
  return String(valor).replace(/<[^>]*>/g, '');
}

export function separarCapitulo(actividad) {
  if (actividad === null || actividad === undefined) return { titulo: '', capitulo: null };
  const bruto = String(actividad);
  const coincidencia = bruto.match(PATRON_CAPITULO);
  const capitulo = coincidencia ? (coincidencia[1] || coincidencia[2] || '').trim() : null;
  const sinCapitulo = coincidencia ? bruto.replace(coincidencia[0], '') : bruto;
  return {
    titulo: quitarEtiquetas(sinCapitulo).replace(/\s+/g, ' ').trim(),
    capitulo: capitulo || null,
  };
}

if (typeof window !== 'undefined') {
  window.AIACardTitle = { separarCapitulo };
}
```

- [ ] **Step 4: Correr y ver el verde**

```bash
node --test tests/design-system/card-title.test.mjs
```

Esperado: 6 pruebas en verde.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/aia_ui/card-title.js tests/design-system/card-title.test.mjs
git commit -m "feat(aia-ui): separar el capitulo del titulo de actividad"
```

---

### Task 2: La tarjeta de Semanal adopta el modelo

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:3441-3462`
- Modify: `views/programacion-semanal/programacion_semanal.view.php` (cargar `card-title.js`)
- Modify: `public/css/programacion-semanal.css`

**Interfaces:**
- Consumes: `window.AIACardTitle.separarCapitulo` de la Task 1.
- Produces: la estructura `.ps-mobile-card > header + .ps-mobile-foco + .ps-mobile-avance + .ps-mobile-responsable + .ps-mobile-edicion + details.ps-mobile-detalle`, que la Task 6 verifica.

- [ ] **Step 1: Cargar `card-title.js` en la vista**

Junto a los otros dos módulos, con su propio `filemtime` (mismo patrón que `enablement-rules.js` y `view-switch.js` en esa vista):

```php
    <?php $psCardTitleVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/aia_ui/card-title.js') ?: 'ct1'; ?>
    <script type="module" src="/js/modules/aia_ui/card-title.js?v=<?php echo urlencode((string) $psCardTitleVersion); ?>"></script>
```

- [ ] **Step 2: Reescribir `renderMobileCard`**

La cara visible lleva los cinco elementos más la edición de la fase; los siete campos de lectura bajan al `<details>`. **El campo editable cambia con la fase** y eso ya lo decide el código existente: en calificación es `Ejecutado_Real`, en programación `Compromiso`.

```javascript
  function renderMobileCard(row, rowIndex) {
    var alertClass = getAlertClassForRow(row);
    var unidad = isBlank(row.Unidad) ? '' : String(row.Unidad);
    var partes = window.AIACardTitle
      ? window.AIACardTitle.separarCapitulo(row.Actividad)
      : { titulo: getPlainActivityLabel(row.Actividad), capitulo: null };
    var view = getStateView(row);
    var summary = getOperationalStateSummary(view);

    var html = '<article class="ps-mobile-card ps-row-state ' + escapeHtml(alertClass) + '" data-mobile-row="' + rowIndex + '">';

    html += '<header><div class="ps-mobile-identidad">'
      + '<span class="ps-mobile-id">' + escapeHtml(row.Id || row.Consecutivo || '') + '</span>'
      + '<h3>' + escapeHtml(partes.titulo) + '</h3>'
      + (partes.capitulo ? '<p class="ps-mobile-capitulo">' + escapeHtml(partes.capitulo) + '</p>' : '')
      + '</div>' + renderMobileStateButton(row, rowIndex) + '</header>';

    if (summary.status !== 'ready') {
      html += '<p class="ps-mobile-foco">' + escapeHtml(summary.focus) + '</p>';
    }

    html += renderMobileProgressMetric('Avance', row, 'Ejecutado');
    html += '<p class="ps-mobile-responsable">' + escapeHtml(isBlank(row.Responsable_AIA) ? 'Sin responsable' : row.Responsable_AIA) + '</p>';

    html += '<div class="ps-mobile-edicion">';
    if (weeklyPhaseKey === 'calificacion') {
      html += renderMobileRealMetric(row, rowIndex);
    } else {
      html += renderMobileEditableMetric('Compromiso', 'Compromiso', row, rowIndex);
    }
    html += '</div>';

    html += '<details class="ps-mobile-detalle"><summary>Ver fechas y presupuesto</summary><div class="ps-mobile-metrics">';
    html += renderMobileMetric('Subcontratista', row.Sub_Contratista);
    html += renderMobileMetric('Unidad', unidad || row.Unidad);
    html += renderMobileMetric('Sugerida', formatWeeklyQuantity(row.cantidad_sugerida_auto, unidad));
    html += renderMobileProgressMetric('Ej. fin semana', row, 'Ejecutado_Fin_Semana');
    if (weeklyPhaseKey === 'calificacion') {
      html += renderMobileEditableMetric('Compromiso', 'Compromiso', row, rowIndex);
    }
    html += '</div></details>';

    html += '</article>';
    return html;
  }
```

**Ojo con lo que este cambio conserva a propósito:** `renderMobileEditableMetric` y `renderMobileRealMetric` **no se tocan**. Ya consultan `isPropReadOnly` y por tanto respetan las reglas S1–S4; moverlas de sitio no cambia quién puede editar. Es lo que la prueba S13 de la red vigila.

- [ ] **Step 3: El CSS de la cara visible**

En `public/css/programacion-semanal.css`, dentro del bloque `@media (max-width: 1179px)` que ya existe:

```css
    .ps-page .ps-mobile-capitulo {
      margin: var(--ds-space-1) 0 0;
      font-size: var(--ds-type-size-xs);
      color: var(--ds-active-text-secondary);
    }

    .ps-page .ps-mobile-foco {
      margin: 0 0 var(--ds-space-3);
      font-size: var(--ds-type-size-xs);
      color: var(--ds-active-text-warning, var(--ds-active-text-secondary));
      line-height: 1.5;
    }

    .ps-page .ps-mobile-responsable {
      margin: 0 0 var(--ds-space-3);
      font-size: var(--ds-type-size-sm);
      color: var(--ds-active-text-primary);
    }

    .ps-page .ps-mobile-detalle > summary {
      cursor: pointer;
      padding: var(--ds-space-2) 0;
      font-size: var(--ds-type-size-sm);
      color: var(--ds-active-text-secondary);
      min-height: var(--ds-target-min);
      display: flex;
      align-items: center;
      justify-content: center;
    }
```

**Si `--ds-type-size-xs` o `--ds-active-text-warning` no existen**, compruébalo con `grep -n "type-size-xs\|active-text-warning" public/css/tokens.css` y usa el token real que sí exista; **no introduzcas un valor literal**.

- [ ] **Step 4: Verificar a 390 px**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs --workers=1
```

Esperado: 8 passed. **S13 es la que importa**: comprueba que la card ofrece campo editable exactamente cuando la regla lo permite. Si se pone roja, la reestructuración movió la edición fuera de donde la regla la autoriza.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programacion_semanal/hot.js views/programacion-semanal/programacion_semanal.view.php public/css/programacion-semanal.css
git commit -m "feat(programacion-semanal): la tarjeta movil adopta el modelo E2-bis"
```

---

### Task 3: El chip de Semanal dice qué falta, no solo cuántos

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:3435-3438`

**Interfaces:**
- Consumes: `getOperationalStateSummary(view)` de la Task 2, ya existente.

- [ ] **Step 1: Comprobar qué pinta hoy**

Lee `renderMobileStateButton` (`:3435-3438`). Hoy emite `summary.countText` («2 pend.») y descarta `summary.focus`, que **sí llega al `aria-label`** en `:1020`. Esta tarea solo publica en pantalla lo que ya se calcula.

- [ ] **Step 2: Nada que cambiar en el botón**

`renderMobileStateButton` se queda como está: sigue mostrando el contador y abriendo el detalle operativo. La línea de foco la pinta la Task 2 como elemento aparte (`.ps-mobile-foco`), no dentro del botón — meterla dentro convertiría un control en un párrafo y rompería su área táctil.

**Esta tarea es de verificación, no de código.** Comprueba que la línea de foco de la Task 2 muestra el mismo texto que el `aria-label` del botón:

```bash
npx playwright test tests/browser/programacion-movil-tarjeta.mjs --workers=1 --grep "foco"
```

(La prueba la crea la Task 6; si aún no existe, esta tarea se cierra al terminar la 6.)

- [ ] **Step 3: Sin commit propio**

Si no hubo cambio de código, no hay commit. Es un control.

---

### Task 4: La tarjeta de Intermedia adopta el modelo y libera restricciones

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js:4328-4366`
- Modify: `views/programacion-intermedia/programacion_intermedia.view.php` (cargar `card-title.js`)
- Modify: `public/css/programacion-intermedia.css`

**Interfaces:**
- Consumes: `separarCapitulo` (Task 1); `crearReglasIntermedia().puedeEditarCelda()` de `enablement-rules.js`; `hardRestrictionProps` y `restrictionProps`, ya en el módulo.
- Produces: `.pi-mobile-card__restriccion` con `<select data-pi-restriccion="<key>" data-row-index="<i>">`, que la Task 5 conecta y la Task 6 verifica.

- [ ] **Step 1: Cargar `card-title.js` en la vista de Intermedia**

```php
    <?php $piCardTitleVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/aia_ui/card-title.js') ?: 'ct1'; ?>
    <script type="module" src="/js/modules/aia_ui/card-title.js?v=<?php echo urlencode((string) $piCardTitleVersion); ?>"></script>
```

- [ ] **Step 2: Un helper para el contador de duras**

Añádelo junto a `createMobileCard`. **Cuenta solo las duras** (`E2-bis-e`), usando el mismo umbral que `isReadyToCommit`:

```javascript
  function contarDurasLiberadas(row) {
    var duras = Array.isArray(hardRestrictionProps) ? hardRestrictionProps : [];
    var liberadas = 0;
    var faltantes = [];
    for (var i = 0; i < duras.length; i++) {
      var clave = duras[i];
      var entrada = null;
      for (var j = 0; j < _activeRestrictions.length; j++) {
        if (_activeRestrictions[j].key === clave) { entrada = _activeRestrictions[j]; break; }
      }
      var umbral = (entrada && entrada.threshold ? entrada.threshold : 100) / 100;
      var bruto = String((row && row[clave]) === null || (row && row[clave]) === undefined ? '' : row[clave]).trim().toUpperCase();
      // `N/A` cuenta como LIBERADA, no como faltante: es lo que hace
      // restrictionMeets() (stateMachine.js:114-116), del que depende
      // isReadyToCommit(). Si el contador lo tratara como pendiente, diria
      // "4 de 5" en una actividad que su propio chip da por lista — el mismo
      // desajuste que E2-bis-e vino a corregir.
      if (bruto === 'N/A' || bruto === 'NO APLICA') {
        liberadas += 1;
        continue;
      }
      var valor = normalizePercentRatio(row && row[clave]);
      if (valor !== null && valor + 0.0001 >= umbral) {
        liberadas += 1;
      } else {
        faltantes.push((entrada && entrada.label) ? entrada.label : clave);
      }
    }
    return { liberadas: liberadas, total: duras.length, faltantes: faltantes };
  }
```

**El nombre correcto es `normalizePercentRatio`** (`programacion_intermedia/hot.js:681`), no
`normalizeRestrictionRatio` — ese es el de Semanal y **no existe en este módulo**. Lo verificó la
auto-revisión de este plan. Devuelve `null` tanto para vacío como para `N/A`, que **no** es lo que el
contador necesita: hay que distinguirlos. El código de arriba ya lo hace —`N/A` suma como liberada
antes de llamar a la normalización— siguiendo a `restrictionMeets()`
(`stateMachine.js:114-116`), que es de quien depende `isReadyToCommit()`.

- [ ] **Step 3: Reescribir `createMobileCard`**

```javascript
  function createMobileCard(row, index) {
    var view = getStateView(row || {});
    var partes = window.AIACardTitle
      ? window.AIACardTitle.separarCapitulo(row && row.Actividad)
      : { titulo: view.activity, capitulo: null };
    var conteo = contarDurasLiberadas(row || {});

    var card = document.createElement('article');
    card.className = 'pi-mobile-card';
    card.dataset.rowIndex = String(index);

    var header = document.createElement('header');
    header.className = 'pi-mobile-card__header';
    var identity = document.createElement('div');
    identity.className = 'pi-mobile-card__identity';

    var id = document.createElement('span');
    id.className = 'pi-mobile-card__id';
    id.textContent = row && row.Id ? 'ID ' + row.Id : 'Actividad';
    identity.appendChild(id);

    var title = document.createElement('h3');
    title.className = 'pi-mobile-card__title';
    title.textContent = partes.titulo || 'Actividad sin nombre';
    identity.appendChild(title);

    if (partes.capitulo) {
      var cap = document.createElement('p');
      cap.className = 'pi-mobile-card__capitulo';
      cap.textContent = partes.capitulo;
      identity.appendChild(cap);
    }

    var state = document.createElement('span');
    state.className = 'pi-mobile-card__state';
    state.textContent = conteo.liberadas + ' de ' + conteo.total;

    header.appendChild(identity);
    header.appendChild(state);
    card.appendChild(header);

    var barra = document.createElement('div');
    barra.className = 'pi-mobile-card__barra';
    for (var b = 0; b < conteo.total; b++) {
      var seg = document.createElement('span');
      seg.className = b < conteo.liberadas ? 'is-liberada' : 'is-pendiente';
      barra.appendChild(seg);
    }
    card.appendChild(barra);

    if (conteo.faltantes.length) {
      var foco = document.createElement('p');
      foco.className = 'pi-mobile-card__foco';
      foco.textContent = 'Faltan ' + conteo.faltantes.join(', ');
      card.appendChild(foco);
    }

    var resp = document.createElement('p');
    resp.className = 'pi-mobile-card__responsable';
    resp.textContent = (row && row.Responsable_AIA) ? row.Responsable_AIA : 'Sin responsable';
    card.appendChild(resp);

    card.appendChild(construirDetalleRestricciones(row, index));
    return card;
  }
```

- [ ] **Step 4: El desplegable con las siete restricciones**

**Las siete**, duras y blandas: el contador cuenta cinco pero se editan todas (`E2-bis-e`). Cada control respeta las reglas ya extraídas.

```javascript
  function construirDetalleRestricciones(row, index) {
    var detalle = document.createElement('details');
    detalle.className = 'pi-mobile-card__detalle';

    var resumen = document.createElement('summary');
    resumen.textContent = 'Liberar restricciones';
    detalle.appendChild(resumen);

    var meta = getPIRowMeta(getPhysicalRowFromVisualRow(hot, index), row || {});
    var reglas = window.AIAEnablementRules.crearReglasIntermedia({
      permiso: $('#permiso_canonico').val(),
      semana: getSemana(),
      maxSemana: getMaxSemana(),
      semanalConfirmada: getSemanalConfirmada(),
      editableProps: editableProps,
    });

    for (var i = 0; i < restrictionProps.length; i++) {
      var clave = restrictionProps[i];
      var entrada = null;
      for (var j = 0; j < _activeRestrictions.length; j++) {
        if (_activeRestrictions[j].key === clave) { entrada = _activeRestrictions[j]; break; }
      }
      var puedeEditar = reglas.puedeEditarCelda({
        prop: clave,
        esHeader: meta.isHeader,
        tieneResponsable: meta.hasResponsable,
        esRestriccion: true,
      });

      var fila = document.createElement('div');
      fila.className = 'pi-mobile-card__restriccion';

      var etiqueta = document.createElement('label');
      etiqueta.className = 'pi-mobile-card__restriccion-label';
      etiqueta.textContent = (entrada && entrada.label) ? entrada.label : clave;
      etiqueta.setAttribute('for', 'pi-restr-' + index + '-' + clave);
      fila.appendChild(etiqueta);

      var control = document.createElement('select');
      control.id = 'pi-restr-' + index + '-' + clave;
      control.dataset.piRestriccion = clave;
      control.dataset.rowIndex = String(index);
      control.disabled = !puedeEditar;
      var opciones = (entrada && Array.isArray(entrada.options)) ? [''].concat(entrada.options) : [''];
      for (var k = 0; k < opciones.length; k++) {
        var opt = document.createElement('option');
        opt.value = opciones[k];
        opt.textContent = opciones[k] === '' ? '—' : opciones[k];
        if (String(row && row[clave] || '') === opciones[k]) opt.selected = true;
        control.appendChild(opt);
      }
      fila.appendChild(control);
      detalle.appendChild(fila);
    }

    if (!meta.hasResponsable) {
      var aviso = document.createElement('p');
      aviso.className = 'pi-mobile-card__aviso';
      aviso.textContent = 'Asigna un Responsable AIA para liberar restricciones.';
      detalle.appendChild(aviso);
    }

    var pie = document.createElement('p');
    pie.className = 'pi-mobile-card__pie';
    pie.textContent = ((row && row.Sub_Contratista) ? row.Sub_Contratista : 'Sin sub-contratista')
      + ' · Inicio ' + ((row && row.Semanas_Inicio !== undefined && row.Semanas_Inicio !== null) ? row.Semanas_Inicio : '—');
    detalle.appendChild(pie);

    return detalle;
  }
```

**`I4` se hace visible por primera vez:** el aviso explica *por qué* están bloqueadas, en vez de solo desactivarlas. Sin él, una fila sin responsable ofrece siete controles muertos sin explicación.

- [ ] **Step 5: El CSS**

En `public/css/programacion-intermedia.css`, dentro del `@media (max-width: 1179px)` existente:

```css
        .pi-page .pi-mobile-card__capitulo {
            margin: var(--ds-space-1) 0 0;
            font-size: var(--ds-type-size-xs);
            color: var(--ds-active-text-secondary);
        }

        .pi-page .pi-mobile-card__barra {
            display: flex;
            gap: var(--ds-space-1);
            margin-block: var(--ds-space-2);
        }

        .pi-page .pi-mobile-card__barra span {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: var(--ds-active-border);
        }

        .pi-page .pi-mobile-card__barra span.is-liberada {
            background: var(--ds-color-state-success-fg, var(--ds-active-text-primary));
        }

        .pi-page .pi-mobile-card__foco {
            margin: 0 0 var(--ds-space-2);
            font-size: var(--ds-type-size-xs);
            color: var(--ds-active-text-secondary);
        }

        .pi-page .pi-mobile-card__restriccion {
            display: flex;
            align-items: center;
            gap: var(--ds-space-2);
            padding-block: var(--ds-space-1);
        }

        .pi-page .pi-mobile-card__restriccion-label {
            flex: 1;
            font-size: var(--ds-type-size-sm);
        }

        .pi-page .pi-mobile-card__restriccion select {
            flex: none;
            min-height: var(--ds-target-min);
            min-width: 5.5rem;
        }

        .pi-page .pi-mobile-card__detalle > summary {
            cursor: pointer;
            min-height: var(--ds-target-min);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--ds-type-size-sm);
            color: var(--ds-active-text-secondary);
        }
```

Comprueba los tokens con `grep -n "type-size-xs\|state-success-fg\|target-min" public/css/tokens.css` antes de usarlos; si alguno no existe, usa el real. **Ningún valor literal de color.**

- [ ] **Step 6: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js views/programacion-intermedia/programacion_intermedia.view.php public/css/programacion-intermedia.css
git commit -m "feat(programacion-intermedia): la tarjeta movil libera restricciones"
```

---

### Task 5: Guardar la restricción desde la tarjeta

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js` (`renderMobileCards`, y el guardado)

**Interfaces:**
- Consumes: `[data-pi-restriccion]` de la Task 4; `saveRow(visualRow, prop, oldValue)` (`:3030`), ya existente.

- [ ] **Step 1: Enganchar el cambio**

Al final de `renderMobileCards`, tras insertar la lista:

```javascript
    container.addEventListener('change', function (evento) {
      var control = evento.target.closest('[data-pi-restriccion]');
      if (!control || control.disabled) return;
      var visualRow = Number(control.dataset.rowIndex);
      var prop = control.dataset.piRestriccion;
      if (!Number.isInteger(visualRow) || !prop) return;
      var fila = visibleRows[visualRow];
      if (!fila) return;
      var anterior = fila[prop];
      fila[prop] = control.value;
      saveRow(visualRow, prop, anterior);
    }, false);
```

**Un solo listener en el contenedor**, no uno por control: `renderMobileCards` se llama en cada filtro y por cada tarjeta, y engancharlo por control multiplicaría los listeners en cada repintado — el mismo fallo que `PSLegacyCards` ya tuvo y que su prueba de ciclo de vida vigila.

- [ ] **Step 2: Comprobar que `saveRow` funciona sin grilla**

Bajo el umbral, `hot` es null (decisión E4, ya implementada). Lee `saveRow` (`:3030`) y comprueba si usa `hot.` sin protección, como pasaba en Semanal. Si lo hace, protégelo con el mismo patrón: resolver la fila desde `visibleRows[visualRow]` cuando `hot` no exista. **Repórtalo en el informe**: es el mismo fallo que la Task 5 del plan anterior encontró en el módulo hermano.

- [ ] **Step 3: Verificar en navegador**

```bash
npx playwright test tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

Esperado: 6 passed. Las reglas I1–I7 no deben moverse.

- [ ] **Step 4: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js
git commit -m "feat(programacion-intermedia): guardar restricciones desde la tarjeta movil"
```

---

### Task 6: Las pruebas del modelo, y la gemela de S13 para Intermedia

**Files:**
- Create: `tests/browser/programacion-movil-tarjeta.mjs`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: la estructura de las Tasks 2 y 4.

- [ ] **Step 1: Registrar el archivo en `.gitignore`**

`tests/browser/*` está ignorado con lista blanca; sin esto el archivo no viaja a un clon:

```
!tests/browser/programacion-movil-tarjeta.mjs
```

- [ ] **Step 2: Escribir las pruebas**

```javascript
import { test, expect } from '@playwright/test';
import { login } from './support/session.mjs';

const CANDIDATOS = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

async function abrir(page, ruta) {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  for (const name of CANDIDATOS) {
    const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (await card.count()) {
      await card.locator('button[type="submit"], .btn-enter').click();
      break;
    }
  }
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
  await page.goto(ruta);
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 }).catch(() => {});
  await page.waitForTimeout(1200);
}

test('semanal: la tarjeta cerrada no pasa de 380px', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const alto = await page.locator('.ps-mobile-card').first().evaluate((el) => el.getBoundingClientRect().height);
  expect(alto, `La tarjeta mide ${Math.round(alto)}px cerrada`).toBeLessThan(380);
});

test('semanal: el detalle esta plegado y el compromiso visible sin desplegar', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const tarjeta = page.locator('.ps-mobile-card').first();
  await expect(tarjeta.locator('details.ps-mobile-detalle')).not.toHaveAttribute('open', '');
  await expect(tarjeta.locator('.ps-mobile-edicion')).toBeVisible();
});

test('semanal: el capitulo no va dentro del titulo', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const titulo = await page.locator('.ps-mobile-card h3').first().textContent();
  expect(titulo, 'El capitulo sigue dentro del titulo').not.toContain('Capítulo');
});

test('semanal: la linea de foco dice lo mismo que el aria del chip', async ({ page }) => {
  await abrir(page, '/programacion-semanal');
  const datos = await page.evaluate(() => {
    const card = document.querySelector('.ps-mobile-card');
    const foco = card.querySelector('.ps-mobile-foco');
    const boton = card.querySelector('.ops-state-zoom');
    return { foco: foco ? foco.textContent.trim() : null, aria: boton ? boton.getAttribute('aria-label') : '' };
  });
  if (datos.foco) {
    expect(datos.aria, 'El aria del chip no contiene el foco que muestra la tarjeta').toContain(datos.foco);
  }
});

test('intermedia: el contador cuenta duras y coincide con los segmentos', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const datos = await page.evaluate(() => {
    const card = document.querySelector('.pi-mobile-card');
    const chip = card.querySelector('.pi-mobile-card__state').textContent.trim();
    const total = card.querySelectorAll('.pi-mobile-card__barra span').length;
    const liberadas = card.querySelectorAll('.pi-mobile-card__barra span.is-liberada').length;
    return { chip, total, liberadas };
  });
  expect(datos.chip).toBe(`${datos.liberadas} de ${datos.total}`);
  expect(datos.total, 'El contador debe contar las duras, no las siete').toBeLessThanOrEqual(5);
});

test('intermedia: el chip nunca contradice al estado de su propia tarjeta', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const filas = await page.evaluate(() => {
    const modulo = window.PIHotModule && window.PIHotModule.getHotInstance;
    return [...document.querySelectorAll('.pi-mobile-card')].slice(0, 20).map((card) => {
      const chip = card.querySelector('.pi-mobile-card__state').textContent.trim();
      const [liberadas, total] = chip.split(' de ').map(Number);
      return { completo: liberadas === total, clase: card.className };
    });
  });
  // Una tarjeta con todas las duras liberadas no puede estar pintada como
  // bloqueada, y una con alguna pendiente no puede estar pintada como lista:
  // el contador y el estado salen de la misma regla (E2-bis-e).
  for (const fila of filas) {
    if (fila.completo) {
      expect(fila.clase, 'Todas las duras liberadas pero pintada como bloqueada').not.toContain('execution-blocked');
    }
  }
});

test('intermedia: al desplegar aparecen las siete restricciones', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const tarjeta = page.locator('.pi-mobile-card').first();
  await tarjeta.locator('details.pi-mobile-card__detalle > summary').click();
  const controles = await tarjeta.locator('[data-pi-restriccion]').count();
  expect(controles, 'Se editan las siete, aunque el contador cuente cinco').toBeGreaterThan(5);
});

test('intermedia: sin responsable, las restricciones se bloquean Y se explica', async ({ page }) => {
  await abrir(page, '/programacion-intermedia');
  const hallazgo = await page.evaluate(() => {
    const cards = [...document.querySelectorAll('.pi-mobile-card')];
    for (const card of cards) {
      const resp = card.querySelector('.pi-mobile-card__responsable');
      if (resp && resp.textContent.trim() === 'Sin responsable') {
        const control = card.querySelector('[data-pi-restriccion]');
        return { bloqueado: control ? control.disabled : null, aviso: Boolean(card.querySelector('.pi-mobile-card__aviso')) };
      }
    }
    return null;
  });
  if (hallazgo === null) {
    test.skip(true, 'El proyecto sembrado no tiene ninguna fila sin responsable');
  }
  expect(hallazgo.bloqueado, 'Sin responsable las restricciones deben estar bloqueadas (I4)').toBe(true);
  expect(hallazgo.aviso, 'Se bloquean sin decir por que').toBe(true);
});
```

- [ ] **Step 3: Correr, tres veces**

```bash
npx playwright test tests/browser/programacion-movil-tarjeta.mjs --workers=1
```

Esperado: 8 passed, tres corridas seguidas. Si alguna es intermitente, averigua la causa antes de seguir; una prueba intermitente enseña a ignorar el rojo.

- [ ] **Step 4: Commit**

```bash
git add tests/browser/programacion-movil-tarjeta.mjs .gitignore
git commit -m "test(programacion): el modelo de tarjeta movil y el candado por responsable"
```

---

### Task 7: Regresión de escritorio y medición del resultado

**Files:** ninguno nuevo.

- [ ] **Step 1: Los goldens de escritorio no se mueven**

```bash
npx playwright test tests/browser/programacion-intermedia.visual.mjs --workers=1
npx playwright test tests/browser/programa-general.visual.mjs --workers=1
```

Esperado: sin cambios. **Si un golden se mueve, es una regresión**: la reestructuración tocó algo por encima de 1180 px. Diagnostícala, no la recaptures.

- [ ] **Step 2: Las redes y la estática**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs tests/browser/programacion-intermedia-enablement.mjs --workers=1
npm run test:design-system:static
```

Esperado: 14/14 y 8/8.

- [ ] **Step 3: Medir la altura real y compararla con la estimación**

La spec estimó **≈325 px** (Semanal) y **≈275 px** (Intermedia) por composición, y dejó dicho que si se desvía se reporta. Mídelo:

```bash
npx playwright test tests/browser/programacion-movil-tarjeta.mjs --workers=1 --grep "no pasa de 380px"
```

Escribe en el informe **la cifra real** de las dos tarjetas cerradas, contra 562 y 380 de partida, y contra las estimaciones. Si se desvía más de un 20 %, dilo explícitamente en vez de dejar la estimación en pie.

- [ ] **Step 4: Commit del cierre**

```bash
git add docs/
git commit -m "docs(f2a): la tarjeta movil E2-bis, medida contra su estimacion"
```

---

## Condición de hecho

1. `node --test tests/design-system/card-title.test.mjs` en verde.
2. Las ocho pruebas de `programacion-movil-tarjeta.mjs` pasan **tres veces seguidas**.
3. Las dos redes de habilitación en 14/14, **sin editar sus aserciones**.
4. `npm run test:design-system:static` en 8/8.
5. Los goldens de escritorio de Intermedia y Programa General, sin cambios.
6. La altura real de las dos tarjetas cerradas está **medida y comparada** con la estimación de la spec, y la desviación reportada si la hay.
7. Una fila sin Responsable AIA muestra sus restricciones bloqueadas **y el aviso que lo explica** (I4 visible por primera vez en móvil).

## Fuera de alcance

Unificar los dos renderizadores en una primitiva común (son HTML por concatenación contra nodos del DOM: refactor aparte). Los contadores y filtros ausentes en móvil (`M-A4`, `M-A5`). El ajuste de la escala tipográfica (`DET-2`), que se hace sobre esta tarjeta ya construida. La discrepancia entre `GLOSARIO.md` y `isReadyToCommit()` sobre si bloquean cinco o siete restricciones, que es decisión de negocio.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Mover la edición de sitio la desacopla de las reglas que la autorizan. | `renderMobileEditableMetric` y `renderMobileRealMetric` no se tocan, solo cambian de posición; S13 lo vigila y corre en la Task 2. |
| El listener de restricciones se multiplica en cada repintado. | Un solo listener delegado en el contenedor, no uno por control. Es el fallo que `PSLegacyCards` ya tuvo. |
| `saveRow` de Intermedia revienta sin grilla montada. | La Task 5 Step 2 lo comprueba explícitamente contra el mismo patrón que apareció en Semanal. |
| La tarjeta desplegada de Intermedia queda enorme con siete controles. | Es aceptado por `E2-bis-c` y solo una está abierta a la vez; la Task 7 mide la cerrada, que es la que multiplica por 78. |
| Los tokens CSS citados no existen. | Cada bloque de CSS lleva su `grep` de comprobación y la instrucción de no introducir literales. |
