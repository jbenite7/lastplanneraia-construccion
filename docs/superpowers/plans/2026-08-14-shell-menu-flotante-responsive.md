# Menú flotante del shell bajo 1180 px: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que por debajo de 1180 px la navegación deje de ocupar 240 px de ancho y pase a abrirse por botón sobre el contenido, sin tocar el comportamiento de escritorio ni la preferencia guardada del usuario.

**Architecture:** Una pieza nueva y canónica, `shell-drawer.js`, que solo sabe convertir un contenedor en flotante bajo un umbral y gestionar foco, `Escape` y accesibilidad. `sidebar_navigation.js` la consume y aporta lo único que es suyo: cuándo persistir. El CSS del modo flotante vive en el adaptador del shell, y el marcado gana un disparador propio porque el actual se ocultaría con el menú.

**Tech Stack:** JavaScript de navegador sin transpilar, CSS con `@layer` y tokens `--ds-*`, PHP para el marcado del shell, Node test runner para lo puro, Playwright para navegador.

**Spec:** [`2026-08-14-shell-menu-flotante-responsive-design.md`](../specs/2026-08-14-shell-menu-flotante-responsive-design.md), decisiones D1–D4.

## Global Constraints

- **El umbral es 1180 px**, el mismo de las tarjetas. Por debajo, flotante; a partir de 1180, comportamiento actual.
- **Por encima del umbral nada cambia.** Si un golden de escritorio se mueve, es una regresión, no un efecto esperado.
- **Por debajo del umbral no se escribe `localStorage`.** Es la decisión D3 y se cumple por construcción: `shell-drawer.js` no conoce la clave.
- **La sesión de pruebas se abre por la puerta de desarrollo** (`/dev/entrar?u=test.A`), nunca por `/login`. `AGENTS.md` §Seguridad.
- **Un commit por tarea**, con staging selectivo, nunca `.env` ni evidencia local.
- El stack se levanta con `docker compose up -d --build db app adminer`; la app se sirve en `http://localhost:8081`.
- **No se toca `design_system_lab.js`.** Su migración a la pieza nueva es deuda anotada, no trabajo de este plan.

## Estado medido del código (2026-08-13/14, re-medir antes de tocar)

| Qué | Dónde |
|---|---|
| El `aside` y su toggle interno | `src/View/Components/DesignSystemComponent.php:428-441` (`sidebarMarkup`) |
| El shell persistente y la barra de contexto | `views/partials/shell_sidebar.php:116` (`<div data-sidebar-persist>`) y `:141` (`<div class="context-bar" id="shellContextBar">`) |
| Estado, persistencia y toggle | `public/js/modules/aia_ui/sidebar_navigation.js:41-119` |
| El `padding-left` del que salen los 240 px | `public/css/design-system/adapters/shell-sidebar.css:13` y `:18` |
| Anchos por token | `public/css/tokens.css:580-581` — `--ds-sidebar-width-expanded: 15rem`, `--ds-sidebar-width-collapsed: 4rem` |
| Drawer ya existente (patrón `adaptive`, **no** se toca) | `DesignSystemComponent.php:493-495`, `design_system_lab.js:17-19` |

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `public/js/modules/aia_ui/shell-drawer.js` | **Nuevo, canónico.** `debeSerFlotante(ancho, umbral)` y `crearShellDrawer({ contenedor, disparador, umbral })`. No consulta `localStorage`. |
| `tests/design-system/shell-drawer.test.mjs` | **Nuevo.** Prueba pura del borde del umbral. |
| `public/js/modules/aia_ui/sidebar_navigation.js` | Consume la pieza y decide cuándo persistir. |
| `public/css/design-system/adapters/shell-sidebar.css` | Modo flotante: `aside` fuera de flujo, velo, `body` sin `padding-left`. |
| `views/partials/shell_sidebar.php` | Emite el disparador «Menú» en la barra de contexto. |
| `tests/browser/shell-menu-flotante.mjs` | **Nuevo.** Las pruebas de navegador de la condición de hecho. |

---

### Task 1: Localizar la regla que fuerza el ancho en Programa General

**Bloqueante y primera.** Si el modo flotante se implementa sobre un ancho que no obedece al estado, en esa pantalla no se vería el efecto. Medido el 2026-08-13: en `/programa-general`, `--aia-sidebar-width` computa `15rem`, `data-sidebar-state` vale `expanded`, no hay estilo inline, y `getComputedStyle(aside).width` da **64px**.

**Files:**
- Investigación; posible modificación de la hoja que resulte responsable.

**Interfaces:**
- Produces: la decisión escrita —qué regla es, si se retira o se respeta— que las Tasks 3 y 4 asumen.

- [ ] **Step 1: Reproducir la contradicción**

Levanta el stack, abre sesión por la puerta de desarrollo y ve a `/programa-general` con el viewport en 1180 o menos. En la consola:

```js
const aside = document.querySelector('.aia-navigation--sidebar');
({
  varAncho: getComputedStyle(aside).getPropertyValue('--aia-sidebar-width'),
  width: getComputedStyle(aside).width,
  estado: aside.dataset.sidebarState,
  inline: aside.getAttribute('style'),
})
```

Esperado: variable `15rem`, `width` `64px`, estado `expanded`, sin inline. Si **no** reproduce, para y repórtalo: la premisa de esta tarea habría caducado.

- [ ] **Step 2: Localizar la regla ganadora**

Un barrido de `document.styleSheets` **ya se intentó y devolvió lista vacía**, así que no repitas esa vía. Usa el panel de estilos del navegador sobre el `aside` y busca la declaración de `width`/`min-width` que gana, con su archivo y línea. Alternativa si el panel no basta: bisección — ir deshabilitando hojas con `document.styleSheets[i].disabled = true` hasta que el ancho salte a 240 px.

- [ ] **Step 3: Decidir y escribir la decisión**

Con la regla localizada, decide entre:
- **Retirarla**, si resulta ser un resto sin dueño. Comprueba antes qué depende de ella: `grep -rn "<selector>" public/css/ views/`.
- **Respetarla**, si es intencionada, y entonces el modo flotante tendrá que actuar sobre ese mismo mecanismo en vez de sobre el estado.

Escribe la decisión en el informe con su porqué. **No la retires a ciegas.**

- [ ] **Step 4: Commit (solo si hubo cambio de código)**

```bash
git add public/css/
git commit -m "fix(shell): el ancho del sidebar vuelve a obedecer a su estado"
```

Si la decisión fue respetarla, no hay commit: es una tarea de diagnóstico y su salida es la decisión escrita.

---

### Task 2: La pieza `shell-drawer.js` y su prueba pura

**Files:**
- Create: `public/js/modules/aia_ui/shell-drawer.js`
- Create: `tests/design-system/shell-drawer.test.mjs`

**Interfaces:**
- Produces: `debeSerFlotante(ancho, umbral)` → booleano, y `crearShellDrawer({ contenedor, disparador, umbral })` → `{ abrir(), cerrar(), estaAbierto(), destruir() }`. La Task 4 consume ambas.

- [ ] **Step 1: Escribir la prueba del borde**

```javascript
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { debeSerFlotante, UMBRAL_FLOTANTE } from '../../public/js/modules/aia_ui/shell-drawer.js';

test('el umbral por defecto es 1180 y el borde cae del lado fijo', () => {
  assert.equal(UMBRAL_FLOTANTE, 1180);
  assert.equal(debeSerFlotante(1179), true);
  assert.equal(debeSerFlotante(1180), false);
  assert.equal(debeSerFlotante(390), true);
  assert.equal(debeSerFlotante(1440), false);
});

test('un ancho no numerico no vuelve flotante la navegacion', () => {
  assert.equal(debeSerFlotante(undefined), false);
  assert.equal(debeSerFlotante(NaN), false);
});
```

- [ ] **Step 2: Correr y ver el rojo**

```bash
node --test tests/design-system/shell-drawer.test.mjs
```

Esperado: FAIL, «Cannot find module .../shell-drawer.js».

- [ ] **Step 3: Escribir la pieza**

```javascript
export const UMBRAL_FLOTANTE = 1180;

export function debeSerFlotante(ancho, umbral = UMBRAL_FLOTANTE) {
  const medido = Number(ancho);
  if (!Number.isFinite(medido)) return false;
  return medido < umbral;
}

const FOCUSABLES = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

export function crearShellDrawer({ contenedor, disparador, umbral = UMBRAL_FLOTANTE, velo }) {
  if (!contenedor || !disparador) throw new Error('shell-drawer necesita contenedor y disparador');
  let abierto = false;

  function focoDentro(evento) {
    if (!abierto || evento.key !== 'Tab') return;
    const focusables = [...contenedor.querySelectorAll(FOCUSABLES)].filter((el) => el.offsetParent !== null);
    if (focusables.length === 0) return;
    const primero = focusables[0];
    const ultimo = focusables[focusables.length - 1];
    if (evento.shiftKey && document.activeElement === primero) {
      evento.preventDefault();
      ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
      evento.preventDefault();
      primero.focus();
    }
  }

  function abrir() {
    if (abierto) return;
    abierto = true;
    contenedor.dataset.shellDrawerOpen = 'true';
    disparador.setAttribute('aria-expanded', 'true');
    if (velo) velo.hidden = false;
    const primero = contenedor.querySelector(FOCUSABLES);
    if (primero) primero.focus();
  }

  function cerrar({ devolverFoco = true } = {}) {
    if (!abierto) return;
    abierto = false;
    delete contenedor.dataset.shellDrawerOpen;
    disparador.setAttribute('aria-expanded', 'false');
    if (velo) velo.hidden = true;
    if (devolverFoco) disparador.focus();
  }

  function sincronizarModo() {
    const flotante = debeSerFlotante(window.innerWidth, umbral);
    contenedor.dataset.shellDrawerMode = flotante ? 'flotante' : 'fijo';
    disparador.hidden = !flotante;
    if (!flotante) cerrar({ devolverFoco: false });
  }

  disparador.addEventListener('click', () => (abierto ? cerrar() : abrir()));
  if (velo) velo.addEventListener('click', () => cerrar());
  contenedor.addEventListener('click', (evento) => {
    if (evento.target.closest('a[href]')) cerrar({ devolverFoco: false });
  });
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && abierto) {
      evento.preventDefault();
      cerrar();
    }
    focoDentro(evento);
  });
  window.addEventListener('resize', sincronizarModo);
  sincronizarModo();

  return { abrir, cerrar, estaAbierto: () => abierto, sincronizarModo };
}

if (typeof window !== 'undefined') {
  window.AIAShellDrawer = { UMBRAL_FLOTANTE, debeSerFlotante, crearShellDrawer };
}
```

- [ ] **Step 4: Correr y ver el verde**

```bash
node --test tests/design-system/shell-drawer.test.mjs
```

Esperado: 2 pruebas en verde.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/aia_ui/shell-drawer.js tests/design-system/shell-drawer.test.mjs
git commit -m "feat(shell): pieza canonica de menu flotante, con su prueba del umbral"
```

---

### Task 3: El disparador en el marcado y el CSS del modo flotante

**Files:**
- Modify: `views/partials/shell_sidebar.php:141` (barra de contexto)
- Modify: `public/css/design-system/adapters/shell-sidebar.css`

**Interfaces:**
- Consumes: nada de la Task 2 todavía; esto es marcado y estilo.
- Produces: `#shellMenuTrigger` y `#shellMenuVelo`, que la Task 4 conecta.

- [ ] **Step 1: Añadir el disparador y el velo**

En `views/partials/shell_sidebar.php`, dentro de `<div class="context-bar" id="shellContextBar">` y **como primer hijo**, antes de `#ctxProyecto`:

```php
  <button type="button" class="aia-btn aia-btn--secondary shell-menu-trigger" id="shellMenuTrigger"
    aria-controls="app-shell" aria-expanded="false" aria-label="Abrir menú de navegación" hidden>
    <i class="fas fa-bars" aria-hidden="true"></i><span>Menú</span>
  </button>
```

Y justo después del `</div>` que cierra `data-sidebar-persist` (línea 139 aproximadamente, re-medir):

```php
<div class="shell-menu-velo" id="shellMenuVelo" hidden></div>
```

`aria-controls="app-shell"` apunta al `id` que `shell_sidebar.php` ya pasa al componente (`'id' => 'app-shell'`). Verifica ese valor antes de escribirlo: si cambió, usa el real.

- [ ] **Step 2: El CSS del modo flotante**

En `public/css/design-system/adapters/shell-sidebar.css`, dentro de `@layer legacy-overrides`:

```css
  /* Bajo el umbral la navegacion deja de ocupar columna: el body pierde el
     padding-left del que salen los 240px y el aside pasa a flotar sobre el
     contenido. El umbral es el mismo que separa tabla de tarjetas (D2). */
  @media (max-width: 1179px) {
    body.aia-shell--sidebar {
      padding-left: 0;
    }

    body.aia-shell--sidebar .aia-navigation--sidebar[data-shell-pattern="sidebar"] {
      transform: translateX(-100%);
      transition: transform var(--ds-motion-standard);
      box-shadow: none;
    }

    body.aia-shell--sidebar .aia-navigation--sidebar[data-shell-drawer-open="true"] {
      transform: translateX(0);
      z-index: var(--ds-z-shell-overlay);
    }

    .shell-menu-velo {
      position: fixed;
      inset: 0;
      /* Mismo patron que el backdrop del dialogo del sistema
         (`components/dialog.css:38-40`), no un hex nuevo. */
      background: color-mix(in srgb, var(--ds-color-text-primary) 60%, transparent);
      z-index: calc(var(--ds-z-shell-overlay) - 1);
    }

    .shell-menu-velo[hidden] {
      display: none;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    body.aia-shell--sidebar .aia-navigation--sidebar[data-shell-pattern="sidebar"] {
      transition: none;
    }
  }
```

**Los dos valores de arriba están medidos, no supuestos** (2026-08-14): no existe ningún token `--ds-active-scrim` —`tokens.css` solo trae `--ds-z-overlay: 300`, `--ds-z-modal: 400` y `--ds-z-shell-overlay: 2100`—, así que el velo reutiliza el `color-mix` con el que el diálogo del sistema pinta su `::backdrop`, y el apilado usa `--ds-z-shell-overlay`, que es el que existe para overlays del shell. No introduzcas un hex ni un token nuevo.

- [ ] **Step 3: Comprobar a ojo, antes de conectar el JS**

Levanta el stack, entra por la puerta de desarrollo y mira `/programa-general` a 390 px. Esperado en este punto: el contenido ocupa el ancho completo y **el menú no se ve** (está desplazado fuera). El botón sigue oculto porque nadie lo ha activado todavía: eso llega en la Task 4.

- [ ] **Step 4: Commit**

```bash
git add views/partials/shell_sidebar.php public/css/design-system/adapters/shell-sidebar.css
git commit -m "feat(shell): disparador y estilo del menu flotante bajo 1180"
```

---

### Task 4: Conectar la pieza y respetar la preferencia

**Files:**
- Modify: `public/js/modules/aia_ui/sidebar_navigation.js`
- Modify: la vista o partial que carga los scripts del shell, para servir `shell-drawer.js`

**Interfaces:**
- Consumes: `crearShellDrawer` de la Task 2; `#shellMenuTrigger` y `#shellMenuVelo` de la Task 3.

- [ ] **Step 1: Cargar la pieza antes que el script del sidebar**

`shell-drawer.js` usa `export`, así que va como `<script type="module">`. **Ojo con el orden**: los módulos son diferidos y se ejecutan después de los scripts clásicos, así que el consumidor debe tolerar que aún no esté — igual que se resolvió en `programacion_semanal/hot.js`. Usa el mismo patrón de `filemtime` propio para el cache-busting que ya usan las dos vistas de programación.

- [ ] **Step 2: Consumirla desde `sidebar_navigation.js`**

Dentro de `init(shell)`, después de que el toggle esté enganchado:

```javascript
    const disparador = document.getElementById('shellMenuTrigger');
    const velo = document.getElementById('shellMenuVelo');
    if (disparador && global.AIAShellDrawer) {
      global.AIAShellDrawer.crearShellDrawer({ contenedor: shell, disparador, velo });
    }
```

- [ ] **Step 3: Que por debajo del umbral no se escriba la preferencia**

Es la decisión D3 y es el punto que más fácil se pierde. En `setCollapsed`, la escritura a `localStorage` pasa a estar condicionada:

```javascript
    const flotante = global.AIAShellDrawer
      && global.AIAShellDrawer.debeSerFlotante(global.innerWidth);
    if (shouldPersist(shell) && !flotante) {
      try {
        global.localStorage.setItem(storageKey, collapsed ? "collapsed" : "expanded");
      } catch (_error) {
        // El estado sigue aplicando en la página actual.
      }
    }
```

Y en `init`, el estado persistido solo se aplica cuando **no** estamos en modo flotante: por debajo del umbral el menú arranca siempre cerrado, sea cual sea la preferencia.

- [ ] **Step 4: Escribir las pruebas de navegador**

Crear `tests/browser/shell-menu-flotante.mjs`. Registrar el archivo en `.gitignore` con su excepción (`!tests/browser/shell-menu-flotante.mjs`), o no viajará al clon.

```javascript
import { test, expect } from '@playwright/test';
import { login, selectProject } from './support/session.mjs';

const CANDIDATOS = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

async function abrir(page, ancho) {
  await page.setViewportSize({ width: ancho, height: 844 });
  await login(page);
  for (const name of CANDIDATOS) {
    const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (await card.count()) { await card.locator('button[type="submit"], .btn-enter').click(); break; }
  }
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
  await page.goto('/programa-general');
}

test('en 390 la navegacion no ocupa columna y el contenido usa el ancho completo', async ({ page }) => {
  await abrir(page, 390);
  const padding = await page.evaluate(() => getComputedStyle(document.body).paddingLeft);
  expect(padding).toBe('0px');
  await expect(page.locator('#shellMenuTrigger')).toBeVisible();
});

test('el menu se abre, se cierra con Escape y devuelve el foco al boton', async ({ page }) => {
  await abrir(page, 390);
  await page.locator('#shellMenuTrigger').click();
  await expect(page.locator('.aia-navigation--sidebar')).toHaveAttribute('data-shell-drawer-open', 'true');
  await page.keyboard.press('Escape');
  await expect(page.locator('.aia-navigation--sidebar')).not.toHaveAttribute('data-shell-drawer-open', 'true');
  await expect(page.locator('#shellMenuTrigger')).toBeFocused();
});

test('abrir el menu en movil NO toca la preferencia guardada', async ({ page }) => {
  await abrir(page, 390);
  const antes = await page.evaluate(() => localStorage.getItem('aia-sidebar-state'));
  await page.locator('#shellMenuTrigger').click();
  await page.locator('#shellMenuTrigger').click();
  const despues = await page.evaluate(() => localStorage.getItem('aia-sidebar-state'));
  expect(despues).toBe(antes);
});

test('en 1440 el comportamiento es el de siempre y el disparador no se ve', async ({ page }) => {
  await abrir(page, 1440);
  await expect(page.locator('#shellMenuTrigger')).toBeHidden();
  const padding = await page.evaluate(() => getComputedStyle(document.body).paddingLeft);
  expect(padding).not.toBe('0px');
});
```

- [ ] **Step 5: Correr las pruebas**

```bash
npx playwright test tests/browser/shell-menu-flotante.mjs --workers=1
```

Esperado: 4 passed. Córrelo **tres veces**; si alguna es intermitente, averigua la causa antes de seguir — una prueba intermitente enseña a ignorar el rojo.

- [ ] **Step 6: Commit**

```bash
git add public/js/modules/aia_ui/sidebar_navigation.js tests/browser/shell-menu-flotante.mjs .gitignore views/
git commit -m "feat(shell): el menu flota bajo 1180 y no ensucia la preferencia de escritorio"
```

---

### Task 5: Regresión de escritorio y cierre

**Files:** ninguno nuevo.

- [ ] **Step 1: Los módulos de la cascada en 390 px**

Recorre `/programa-general`, `/programacion-intermedia` y `/programacion-semanal` a 390 px y comprueba en cada uno: `body` sin `padding-left`, sin desbordamiento horizontal (`document.documentElement.scrollWidth === window.innerWidth`) y el menú alcanzable. Anota lo que veas, no solo lo que esperas ver.

- [ ] **Step 2: Que escritorio no se haya movido**

```bash
npx playwright test tests/browser/programa-general.visual.mjs --workers=1
npm run test:design-system:static
```

Esperado: los goldens de escritorio sin cambios y las ocho puertas en verde. **Si un golden se mueve, es una regresión**: diagnostícala, no la recaptures.

- [ ] **Step 3: Las redes de habilitación, que comparten shell**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

Esperado: 14 passed.

- [ ] **Step 4: Anotar la deuda con dueño**

En `docs/EXPERIMENTS.md`, una tarjeta: migrar `design_system_lab.js` a `shell-drawer.js` para que no convivan dos implementaciones. Con su ICE y sin dueño asignado si no lo hay — pero escrita, no intencionada.

- [ ] **Step 5: Commit**

```bash
git add docs/EXPERIMENTS.md
git commit -m "docs(experiments): la migracion del laboratorio al menu flotante canonico"
```

---

## Condición de hecho

1. En `390x844` el contenido dispone del ancho completo (`body` sin `padding-left`) y el menú es alcanzable y cerrable con ratón y con teclado.
2. Abrir el menú por debajo del umbral **no modifica** `aia-sidebar-state`, comprobado leyendo la clave antes y después.
3. Por encima de 1180 px el comportamiento es indistinguible del actual: disparador oculto, `padding-left` intacto, goldens de escritorio sin cambios.
4. La regla que fuerza el ancho en `/programa-general` está localizada y resuelta, con su decisión escrita.
5. `npm run test:design-system:static` en sus ocho puertas y las dos redes de habilitación en 14/14.
6. Las cuatro pruebas nuevas pasan tres veces seguidas sin intermitencias.

## Fuera de alcance

Migrar el laboratorio (deuda anotada en la Task 5). El umbral de las tarjetas y el montaje condicional de Handsontable, que son las Tasks 4 y 5 del plan `f2a-2b-2`. Los hallazgos `V-4`, `V-5` y `V-7` de la auditoría móvil.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| La regla fantasma de `/programa-general` resulta intencionada y algo depende de ella. | Task 1 la localiza **antes** de tocar nada y su salida es una decisión escrita. No se retira a ciegas. |
| El menú flotante se abre y no hay forma de salir en un teléfono. | Tres vías de cierre —velo, `Escape` y elegir destino— y la prueba de teclado forma parte de la condición de hecho. |
| Quitar el `padding-left` descoloca módulos que asumían ese offset. | Task 5 Step 1 recorre los tres módulos de la cascada a 390 px, y Step 2 comprueba que escritorio no se movió. |
| El módulo diferido aún no está cuando el script clásico lo busca. | Mismo patrón ya resuelto en `programacion_semanal/hot.js`: el consumidor tolera su ausencia en vez de lanzar. |
