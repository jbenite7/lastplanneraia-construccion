---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-06
areas: [design-system]
fuente: docs/superpowers/plans/2026-09-06-bloqueo-tema-claro.md
resumen: "Levantar el bloqueo del tema claro: theme.js deja de forzar el oscuro en 19 pantallas, la hoja clara entra a los entrypoints con el claro como default, el laboratorio rinde en claro y sus goldens claros entran al CI con visto de Felipe."
---

# Bloqueo del tema claro — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las 19 pantallas PHP y el laboratorio del design system honren el tema que
`theme-bootstrap.js` decide (claro de entrada, preferencia por aparato), y que el carril visual
del CI mida el laboratorio en claro con goldens aprobados.

**Architecture:** Dos causas medidas, dos arreglos. (1) `public/js/modules/aia_ui/theme.js`
conserva su ruta —19 vistas y tres contratos la nombran— pero queda reducido al movimiento
reducido: ya no escribe `data-aia-theme` ni publica `window.AiaDesignSystem`. (2) La hoja clara
`public/css/design-system/theme-claro.css` pasa a ser el default del sistema con el selector
`:root:not([data-aia-theme="dark"]):not(.aia-theme-dark)` y los tres entrypoints la importan;
los bloques oscuros dejan de atarse a `:root`. Ese selector negado hace que el orden entre la
hoja clara y el bloque oscuro no importe (nunca compiten sobre el mismo `<html>`), así que los
dos enlaces explícitos que hoy existen no se rompen. Los tests que daban el oscuro por sentado
materializan el tema que afirman. Los goldens claros del laboratorio se generan, Felipe los
aprueba sobre una galería, y solo entonces se commitean.

**Tech Stack:** CSS custom properties en `@layer theme`, JS vanilla, Node test runner
(`node --test tests/design-system/*.test.mjs`), Playwright contra la pila aislada de CI
(`docker-compose.ci.yml`, puerto 18081), PHP 8.3 en Docker.

**Spec:** `docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design.md` (D12,
D14, D16, D18, D19) y el goal `goals/bloqueo-tema-claro/goal.md`, que trae la medición.

## Global Constraints

- **D12:** el claro es el tema de entrada; la elección manual persiste y gana. **D14:** la
  preferencia vive en `localStorage['aia-theme']`, por aparato, sin backend.
- **D16:** el CI corre ambos temas; el censo de `visual-ci-contract.test.mjs` se declara por
  módulo y tema (`{ dark: 10 }` → `{ dark: 10, light: 10 }` para el laboratorio).
- **D18:** la penumbra no se recalibra. **Ningún golden oscuro cambia**; si uno cambia, es
  hallazgo y se detiene la tarea.
- `AGENTS.md`: «no regeneres snapshots ni baselines para forzar un resultado verde; los cambios
  visuales requieren aprobación explícita». Los goldens claros solo se commitean con el visto de
  Felipe sobre la galería.
- `docs/coordinacion-sesiones.md` reglas 4, 5, 7: no reapuntar `LPS_CODE_ROOT` del contenedor
  compartido; lo CLI corre en contenedor efímero; la verificación en navegador corre en la pila
  aislada (`docker-compose.ci.yml`) y siempre se comprueba qué árbol monta.
- `docker-compose.ci.yml` sirve en `http://127.0.0.1:18081`; los specs leen `E2E_BASE_URL`.
- Viewport canónico 1180×820; el laboratorio homologa además 1440×900.
- Todo CSS editado bajo `public/css/` exige regenerar el espejo sin comentarios
  (`public/dist-css/`), que el CI **verifica** con `npm run css:minify:check` y no genera.
- `biome check public/js public/css` (`npm run check:frontend`) tiene que quedar en verde.
- Nada de este plan toca `admin/`, `ct-app/`, `pdc-app/` ni `frontend/`.

---

## Pila aislada para verificar en navegador (se usa en las tareas 3 y 4)

Levantarla una vez por sesión, desde el worktree de la rama, con estas variables exactas
(son las que `.github/workflows/ci.yml:182-200` fabrica; aquí se fabrican a mano con un
`CI_RUN_ID` local):

```bash
export CI_RUN_ID=run-local-tema-claro-01
export COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-${CI_RUN_ID}
export COMPOSE_FILE=docker-compose.yml:docker-compose.ci.yml
eval "$(node scripts/design-system-ci-preflight.mjs --print-provenance | sed 's/^/export /')"
docker build -f docker/php/Dockerfile --build-arg COMPOSER_INSTALL_FLAGS= -t "lps-aia-design-system-ci:${CI_RUN_ID}" .
docker compose build db
docker compose up -d db app
for _ in $(seq 1 60); do curl -fsS http://127.0.0.1:18081/login >/dev/null && break; sleep 2; done
until docker compose exec -T db mysql -uroot -p"${MYSQL_ROOT_PASSWORD:-ci-admin-only-password}" -N -e \
  "SELECT COUNT(*) FROM lastplanneraia_ci.general_usuarios;" 2>/dev/null | grep -qE '^[1-9]'; do sleep 2; done
docker inspect "$(docker compose ps -q app)" --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'
```

La última línea es el **paso 0** de coordinación: la imagen de CI copia el árbol al construir
(no lo monta), así que la evidencia vale para el sha con que se construyó la imagen. Anotar ese
sha (`git rev-parse HEAD`) junto a cada evidencia; **reconstruir la imagen tras cada tarea que
cambie código** antes de volver a medir. Si la contraseña de root difiere, está en
`docker-compose.ci.yml` (servicio `db`). Al terminar la sesión:
`docker compose down -v` (solo este proyecto; no toca `last-planner-aia`).

Los specs se invocan así (la variable de tema la usa cada spec para elegir subconjunto):

```bash
E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=light npx playwright test tests/browser/<spec>.mjs --workers=1
E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=dark  npx playwright test tests/browser/<spec>.mjs --workers=1
```

---

### Task 1: `theme.js` deja de forzar el tema y de publicar el global

**Files:**
- Modify: `public/js/modules/aia_ui/theme.js` (archivo completo)
- Modify: `public/js/modules/aia_ui/design_system_lab.js:1-8`
- Modify: `views/plan-compras/app.view.php:40-43`
- Modify: `tests/browser/shell-sidebar-rollout.mjs:99-113`
- Modify: `docs/design-system/README.md:77-79`
- Test: `tests/design-system/theme-default-claro.test.mjs`

**Interfaces:**
- Consumes: `theme-bootstrap.js` (ya aplica `data-aia-theme` y alterna `.aia-theme-dark`).
- Produces: `theme.js` = solo `aia-no-motion`; ninguna pieza del producto publica
  `window.AiaDesignSystem`. Las tareas 2–4 asumen que el atributo que `theme-bootstrap.js`
  escribe sobrevive hasta el final de la carga.

- [ ] **Step 1: Escribir los tests que fallan**

Añadir al final de `tests/design-system/theme-default-claro.test.mjs`:

```js
const themeRuntime = readFileSync('public/js/modules/aia_ui/theme.js', 'utf8');
const labRuntime = readFileSync('public/js/modules/aia_ui/design_system_lab.js', 'utf8');

test('D12: theme.js ya no escribe data-aia-theme ni la clase de tema (el bootstrap manda)', () => {
  assert.doesNotMatch(themeRuntime, /setAttribute\(\s*["']data-aia-theme["']/,
    'theme.js vuelve a fijar el tema a pelo: pisa la decisión de theme-bootstrap.js');
  assert.doesNotMatch(themeRuntime, /aia-theme-dark/,
    'theme.js vuelve a tocar la clase de tema');
});

test('ningún runtime del producto publica window.AiaDesignSystem (global sin consumidores)', () => {
  for (const [name, source] of [['theme.js', themeRuntime], ['design_system_lab.js', labRuntime]]) {
    assert.doesNotMatch(source, /AiaDesignSystem/, `${name} publica AiaDesignSystem`);
  }
});

test('theme.js conserva el movimiento reducido', () => {
  assert.match(themeRuntime, /prefers-reduced-motion: reduce/);
  assert.match(themeRuntime, /aia-no-motion/);
});
```

- [ ] **Step 2: Correrlos y verlos fallar**

Run: `node --test tests/design-system/theme-default-claro.test.mjs`
Expected: 2 fallos (`setAttribute("data-aia-theme"` presente; `AiaDesignSystem` presente en los
dos archivos). El tercero pasa ya.

- [ ] **Step 3: Reescribir `theme.js` completo**

```js
// Movimiento reducido: la única responsabilidad que le queda a este archivo.
// El tema lo decide theme-bootstrap.js (D12 claro de entrada, D14 preferencia por
// aparato, spec temas 2026-08-28). Hasta el 2026-09-06 este guion lo pisaba con
// "dark" sin condición en 19 pantallas —7 lo cargan a mano y 12 vía
// linksComunesHead2.js— y publicaba window.AiaDesignSystem.getTheme(), un global
// sin consumidores en el producto (solo un test lo esperaba). Conserva la ruta
// porque esas vistas y tres contratos la nombran; su contenido ya no toca
// data-aia-theme ni la clase aia-theme-dark.
(() => {
  if (!window.matchMedia) return;
  const motion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const applyMotion = () => {
    document.documentElement.classList.toggle("aia-no-motion", motion.matches);
  };
  applyMotion();
  if (motion.addEventListener) {
    motion.addEventListener("change", applyMotion);
  } else if (motion.addListener) {
    motion.addListener(applyMotion);
  }
})();
```

- [ ] **Step 4: Retirar el global del laboratorio**

En `public/js/modules/aia_ui/design_system_lab.js`, reemplazar las líneas 2–8 (el comentario y
las dos líneas de `window.AiaDesignSystem`) por:

```js
  // El tema lo aplica theme-bootstrap.js en <head>; este módulo no publica ningún
  // global de tema desde el 2026-09-06 (no tenía consumidores).
```

- [ ] **Step 5: Corregir el comentario de la vista del Plan de Compras**

En `views/plan-compras/app.view.php`, reemplazar las líneas 40–42 por:

```php
	<?php /* theme.js solo aplica el movimiento reducido; el tema lo decide theme-bootstrap.js en
	         <head>. El global window.AiaDesignSystem que este comentario citaba se retiró el
	         2026-09-06: no lo consumía nadie. */ ?>
```

- [ ] **Step 6: El test del sidebar espera el atributo, no el global, y afirma el claro (D12)**

En `tests/browser/shell-sidebar-rollout.mjs`, reemplazar las líneas 99–113 por:

```js
  // 0) Regresión CT-Final: un `aia-theme=linen` heredado no es un tema válido y cae al
  // default. Hasta el 2026-09-06 el default era el oscuro que theme.js fijaba a pelo y
  // este check esperaba `dark`; desde D12 (spec temas 2026-08-28) el default es el
  // claro que theme-bootstrap.js aplica en <head>, de forma síncrona, así que a
  // domcontentloaded el atributo ya está escrito y no hay global que esperar.
  await page.waitForFunction(() => document.documentElement.getAttribute('data-aia-theme') !== null);
  const themeWithStaleLinen = await page.evaluate(() => document.documentElement.getAttribute('data-aia-theme'));
  check(`[${r.label}] aia-theme=linen heredado cae al default claro (D12)`,
    themeWithStaleLinen === 'light', `data-aia-theme=${themeWithStaleLinen}`);
```

- [ ] **Step 7: Actualizar la línea del README del design system**

En `docs/design-system/README.md`, reemplazar las líneas 77–79 por:

```markdown
- `public/js/modules/aia_ui/theme.js`: solo aplica reduced motion. Hasta el 2026-09-06 forzaba
  `data-aia-theme="dark"` sin condición en 19 vistas (7 a mano, 12 vía `linksComunesHead2.js`);
  conserva la ruta porque esas vistas y tres contratos la nombran.
```

- [ ] **Step 8: Verificar**

Run:
```bash
node --test tests/design-system/theme-default-claro.test.mjs tests/design-system/linen-removal.test.mjs tests/design-system/dead-theme-removal.test.mjs
node tests/test_foundation_shell_contract.mjs
npm run check:frontend
```
Expected: todo en verde. `test_foundation_shell_contract.mjs` sigue exigiendo que
`linksComunesHead2.js`, `login.view.php` y `project_selector.view.php` carguen `theme.js`
(líneas 81, 85, 86): por eso el archivo conserva su ruta.

- [ ] **Step 9: Commit**

```bash
git add public/js/modules/aia_ui/theme.js public/js/modules/aia_ui/design_system_lab.js views/plan-compras/app.view.php tests/browser/shell-sidebar-rollout.mjs tests/design-system/theme-default-claro.test.mjs docs/design-system/README.md
git commit -m "fix(design-system): theme.js deja de forzar el oscuro y de publicar un global sin consumidores"
```

---

### Task 2: la hoja clara es el default y los tres entrypoints la importan

**Files:**
- Modify: `public/css/design-system/theme-claro.css:15-17`
- Modify: `public/css/design-system/entrypoints/theme-overrides.css:1-4`
- Modify: `public/css/aia-design-system.css:14` (import) y `:53-56` (selector)
- Modify: `public/css/design-system/lab-entrypoint.css:4-5`
- Modify: `public/css/design-system/laboratory-foundation.css:1-4`
- Modify: `views/plan-compras/app.view.php:17`
- Modify: `views/bi/control-tower-piloto.php:16-24,28,37`
- Modify: `public/dist-css/**` (espejo regenerado)
- Test: `tests/design-system/theme-default.test.mjs`

**Interfaces:**
- Consumes: nada de la tarea 1 más allá de que el atributo del bootstrap sobrevive.
- Produces: en cualquier documento que cargue `aia-design-system.css`, `entrypoints/core.css` o
  `lab-entrypoint.css`, sin atributo o con `data-aia-theme="light"` los `--ds-active-*`
  resuelven a los tokens `-light`; con `data-aia-theme="dark"` o `.aia-theme-dark`, a los
  `-dark`. Las tareas 3 y 4 dependen de esto.

- [ ] **Step 1: Reescribir el contrato en `theme-default.test.mjs` (fallará)**

Reemplazar el bloque `for (const entrypoint of THEME_ENTRYPOINTS) { … }` (líneas 81–169; el
test final «los dos entrypoints declaran bloques de tema equivalentes» se conserva tal cual) por:

```js
const DARK_SELECTOR = '[data-aia-theme="dark"],';
const LIGHT_SHEET = 'public/css/design-system/theme-claro.css';
const LIGHT_IMPORT = /@import url\("\/css\/design-system\/theme-claro\.css\?v=[0-9.]+"\);/;
const LIGHT_DEFAULT_SELECTOR = ':root:not([data-aia-theme="dark"]):not(.aia-theme-dark),';

for (const entrypoint of THEME_ENTRYPOINTS) {
  test(`${entrypoint}: el grupo dark mapea cada --ds-active-* a su token dark (mapeo completo)`, async () => {
    const css = await read(entrypoint);
    assertFullMapping(blockBody(css, DARK_SELECTOR, entrypoint), EXPECTED_DARK_DECLARATIONS, entrypoint);
  });

  // D12 (2026-09-06): el default del sistema es el claro. `:root` ya no forma parte del
  // grupo dark; el dark solo aplica cuando el documento lo pide por atributo o clase.
  test(`${entrypoint}: :root no está atado al grupo dark`, async () => {
    const css = await read(entrypoint);
    const body = layerThemeBody(css, entrypoint);
    assert.equal(/:root\s*,/.test(body), false, `${entrypoint}: @layer theme ata :root al oscuro`);
    assert.match(body, /\[data-aia-theme="dark"\],\s*\.aia-theme-dark\s*\{/);
  });

  test(`${entrypoint}: importa theme-claro.css antes de declarar @layer theme`, async () => {
    const css = await read(entrypoint);
    const importIndex = css.search(LIGHT_IMPORT);
    assert.notEqual(importIndex, -1, `${entrypoint}: no importa theme-claro.css`);
    assert.ok(importIndex < css.indexOf('@layer theme'), `${entrypoint}: importa theme-claro.css después del bloque dark`);
  });

  test(`${entrypoint}: @layer theme contiene exactamente un grupo de selectores`, async () => {
    const css = await read(entrypoint);
    const body = layerThemeBody(css, entrypoint);
    const groupCount = (body.match(/{/g) || []).length;
    assert.equal(groupCount, 1, `${entrypoint}: @layer theme declara ${groupCount} grupos; el claro vive en theme-claro.css importada, no aquí`);
  });
}

test('theme-claro.css declara el claro como default con el selector negado (D12)', async () => {
  const css = await read(LIGHT_SHEET);
  const body = blockBody(css, LIGHT_DEFAULT_SELECTOR, LIGHT_SHEET);
  assert.match(body, /color-scheme:\s*light;/);
  assert.match(css, /\[data-aia-theme="light"\],\s*\.aia-theme-light\s*\{/);
});

test('el laboratorio no ata dark a :root e importa theme-claro.css antes de su fundación', async () => {
  const foundation = await read('public/css/design-system/laboratory-foundation.css');
  assert.equal(/:root\s*,/.test(layerThemeBody(foundation, 'laboratory-foundation.css')), false);
  const entry = await read('public/css/design-system/lab-entrypoint.css');
  const importIndex = entry.search(LIGHT_IMPORT);
  assert.notEqual(importIndex, -1, 'lab-entrypoint.css no importa theme-claro.css');
  assert.ok(importIndex < entry.indexOf('laboratory-foundation.css'), 'theme-claro.css debe importarse antes que laboratory-foundation.css');
});
```

Y borrar la constante `DARK_SELECTOR = ':root,'` original de la línea 12 (queda la nueva).

- [ ] **Step 2: Correr y ver fallar**

Run: `node --test tests/design-system/theme-default.test.mjs`
Expected: fallan los tests de «:root no está atado», «importa theme-claro.css», «selector
negado» y «laboratorio». Los de mapeo completo pasan (el cuerpo del grupo dark no cambia).

- [ ] **Step 3: `theme-claro.css` pasa a ser el default**

Reemplazar las líneas 15–17 por:

```css
@layer theme {
  /* D12 (2026-09-06): el claro es el default del sistema. El primer selector aplica a
     todo documento que no haya pedido el oscuro por atributo o clase; por eso el orden
     entre esta hoja y el bloque dark de los entrypoints no importa: nunca compiten
     sobre el mismo <html>. Los otros dos selectores conservan el gancho explícito. */
  :root:not([data-aia-theme="dark"]):not(.aia-theme-dark),
  [data-aia-theme="light"],
  .aia-theme-light {
```

- [ ] **Step 4: Los tres bloques oscuros sueltan `:root`**

En `public/css/design-system/entrypoints/theme-overrides.css`, `public/css/aia-design-system.css`
(línea 54) y `public/css/design-system/laboratory-foundation.css`, borrar la línea `  :root,` que
precede a `  [data-aia-theme="dark"],`. Los tres bloques quedan:

```css
@layer theme {
  [data-aia-theme="dark"],
  .aia-theme-dark {
    color-scheme: dark;
```

- [ ] **Step 5: Los tres entrypoints importan la hoja clara**

- `theme-overrides.css`: insertar como **primera línea del archivo**
  `@import url("/css/design-system/theme-claro.css?v=1.1.0");` (los `@import` van antes de
  cualquier regla; `entrypoints/core.css:31` importa este archivo, y los imports anidados
  son válidos).
- `aia-design-system.css`: insertar la misma línea justo después de la línea 14
  (`@import url("/css/design-system/core.css?v=1.1.0");`).
- `lab-entrypoint.css`: insertar la misma línea entre la 4 (`core.css`) y la 5
  (`laboratory-foundation.css`).

- [ ] **Step 6: Las dos vistas que fijaban el oscuro en el servidor**

- `views/plan-compras/app.view.php:17`: `<html lang="es" data-aia-theme="dark">` →
  `<html lang="es">`.
- `views/bi/control-tower-piloto.php:28`: igual. Borrar la línea 37 (el `<link>` a
  `theme-claro.css`: ya viaja dentro de `aia-design-system.css`) y reemplazar el bloque de
  comentario de las líneas 16–24 por:

```php
 * Tema claro: desde el 2026-09-06 `theme-claro.css` viaja dentro de `aia-design-system.css`
 * como default del sistema (D12) y su selector negado hace irrelevante el orden con el bloque
 * dark; el enlace explícito que esta vista llevaba se retiró. `theme-bootstrap.js` escribe el
 * atributo antes de la primera hoja; el `<html>` ya no lo fija en el servidor.
```

- [ ] **Step 7: Regenerar el espejo sin comentarios y verificar**

Run:
```bash
grep -n '"css:minify' package.json
npm run css:minify
npm run css:minify:check
node --test tests/design-system/theme-default.test.mjs tests/design-system/theme-claro-tokens.test.mjs tests/design-system/theme-default-claro.test.mjs tests/design-system/dead-theme-removal.test.mjs tests/design-system/linen-removal.test.mjs
npm run check:frontend
```
Expected: `css:minify:check` en verde tras regenerar; los cinco archivos de test en verde;
biome en verde. Si `css:minify` no existe con ese nombre, el `grep` de la primera línea dice
cuál es; no se edita `public/dist-css/` a mano.

- [ ] **Step 8: Comprobación en navegador, árbol de la rama (pila aislada)**

Reconstruir la imagen (ver receta arriba) y, con el navegador integrado sobre
`http://127.0.0.1:18081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, en `/programa-general`,
`/proyectos`, `/plan-compras` y `/bi` (rol A para `/internal/design-system`) ejecutar:

```js
({attr: document.documentElement.getAttribute('data-aia-theme'),
  bg: getComputedStyle(document.body).backgroundColor,
  page: getComputedStyle(document.documentElement).getPropertyValue('--ds-active-bg-page').trim(),
  scheme: getComputedStyle(document.documentElement).colorScheme})
```
Expected sin preferencia guardada: `attr: "light"`, `page: "#ffffff"`, `scheme: "light"`.
Después `localStorage.setItem('aia-theme','dark')` + recarga: `attr: "dark"`,
`page: "#111a15"` en PG. Anotar las salidas en `goals/bloqueo-tema-claro/goal.md`.

- [ ] **Step 9: Commit**

```bash
git add public/css/design-system/theme-claro.css public/css/design-system/entrypoints/theme-overrides.css public/css/aia-design-system.css public/css/design-system/lab-entrypoint.css public/css/design-system/laboratory-foundation.css public/dist-css views/plan-compras/app.view.php views/bi/control-tower-piloto.php tests/design-system/theme-default.test.mjs
git commit -m "feat(design-system): el claro es el default del sistema y los entrypoints importan la hoja clara (D12)"
```

---

### Task 3: los tests que daban el oscuro por sentado materializan su tema

**Files:**
- Modify: `tests/browser/design-system-body-canvas-dark.mjs` (dentro del `test`, antes del `for`)
- Modify: `tests/browser/operational-fixtures.mjs:15-62`
- Modify: `tests/browser/design-system-compliance.mjs:199,248,303,323,359`

**Interfaces:**
- Consumes: el default claro de la tarea 2.
- Produces: los cuatro specs pasan en las dos patas de la matriz (`E2E_THEME=light|dark`).

- [ ] **Step 1: Correrlos contra la pila aislada y ver el rojo**

Run (pila levantada con la imagen de la tarea 2):
```bash
E2E_BASE_URL=http://127.0.0.1:18081 npx playwright test tests/browser/design-system-body-canvas-dark.mjs tests/browser/operational-fixtures.mjs tests/browser/design-system-compliance.mjs --workers=1
```
Expected: fallan los asserts `toBe('dark')` (ahora leen `light`) y los fondos oscuros de
`body-canvas-dark`. Es el rojo correcto: afirman un tema que no materializaron.

- [ ] **Step 2: `body-canvas-dark` pide el oscuro antes de navegar**

Dentro del `test('el body de cada ruta de la Tarea 3 usa su fondo oscuro…')`, como primera
línea del cuerpo (antes del `for (const [route, expectedBackground] …`):

```js
  // Este spec mide el OSCURO; desde D12 (2026-09-06) el default es el claro, así que
  // materializa el tema que afirma en vez de darlo por hecho (misma vía que theme-bootstrap.js).
  await page.addInitScript(() => {
    try { localStorage.setItem('aia-theme', 'dark'); } catch (_) { /* privado/bloqueado */ }
  });
```

- [ ] **Step 3: `operational-fixtures` afirma el tema de la corrida**

Añadir tras los `import` de `tests/browser/operational-fixtures.mjs`:

```js
// D16: la matriz corre ambos temas; el laboratorio materializa el de la corrida antes de medir.
const THEME = process.env.E2E_THEME === 'dark' ? 'dark' : 'light';
const materializarTema = (page) => page.addInitScript((t) => {
  try { localStorage.setItem('aia-theme', t); } catch (_) { /* privado/bloqueado */ }
}, THEME);
```

En los tres tests, insertar `await materializarTema(page);` justo después de
`await page.setViewportSize({ width: 1180, height: 820 });`, y cambiar las tres aserciones
`toHaveAttribute('data-aia-theme', 'dark')` por `toHaveAttribute('data-aia-theme', THEME)`.
Renombrar el primer test a `'P1 and P2 operational fixtures stay contained in the desktop laboratory, in the run theme'`.

- [ ] **Step 4: `design-system-compliance` distingue default de tema materializado**

- Línea 199: `expect(state.initialTheme).toBe('dark');` → `expect(state.initialTheme).toBe('light'); // D12: claro de entrada`.
- Líneas 248 y 303: `expect(state.appliedTheme).toBe('dark');` → `expect(state.appliedTheme).toBe('light'); // D12`.
- Líneas 323 y 359: justo después de `for (const theme of ['dark']) {` insertar:

```js
            await page.evaluate((t) => {
              document.documentElement.setAttribute('data-aia-theme', t);
              document.documentElement.classList.toggle('aia-theme-dark', t === 'dark');
            }, theme);
```
(El bucle sigue midiendo solo `dark`: extenderlo al claro es trabajo del plan de cada
módulo, no de este.)

- [ ] **Step 5: Verificar en las dos patas**

Run:
```bash
for t in light dark; do E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=$t npx playwright test tests/browser/design-system-body-canvas-dark.mjs tests/browser/operational-fixtures.mjs tests/browser/design-system-compliance.mjs tests/browser/design-system-lab.mjs --workers=1; echo "RC_$t=$?"; done
```
Expected: `RC_light=0` y `RC_dark=0`.

- [ ] **Step 6: Commit**

```bash
git add tests/browser/design-system-body-canvas-dark.mjs tests/browser/operational-fixtures.mjs tests/browser/design-system-compliance.mjs
git commit -m "test(design-system): los specs materializan el tema que afirman; el default es el claro (D12)"
```

---

### Task 4: el laboratorio en claro — escenarios, goldens con visto, censo

**Files:**
- Modify: `docs/design-system/manifests/laboratory.json` (+20 escenarios `light`)
- Create: `tests/browser/__screenshots__/design-system-lab.visual.mjs/*-light-*.png` (20)
- Modify: `tests/design-system/visual-ci-contract.test.mjs:58-66,80`

**Interfaces:**
- Consumes: el laboratorio rindiendo en claro (tarea 2) y `freezeTheme` del spec visual.
- Produces: 20 escenarios `light` con `golden` y `sha256`; censo `{ dark: 10, light: 10 }`.

- [ ] **Step 1: Declarar los 20 escenarios claros sin golden todavía**

```bash
node - <<'EOF'
const fs = require('node:fs');
const p = 'docs/design-system/manifests/laboratory.json';
const m = JSON.parse(fs.readFileSync(p, 'utf8'));
const dark = m.scenarios.filter((s) => s.theme === 'dark');
const light = dark.map(({ goldenPlatforms, ...s }) => ({
  ...s,
  id: s.id.replace('-dark-', '-light-'),
  theme: 'light',
  golden: s.golden.replace('-dark-', '-light-'),
  sha256: null,
}));
m.scenarios = [...dark, ...light];
fs.writeFileSync(p, `${JSON.stringify(m, null, 2)}\n`);
console.log(`${light.length} escenarios light declarados`);
EOF
```
Expected: `20 escenarios light declarados`. (`goldenPlatforms` no se declara: el gemelo Linux
se fija después, desde la corrida real de Actions, como manda la nota del 2026-08-28.)

- [ ] **Step 2: Generar las capturas claras contra la pila aislada**

```bash
E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=light npx playwright test tests/browser/design-system-lab.visual.mjs --workers=1 --update-snapshots
ls tests/browser/__screenshots__/design-system-lab.visual.mjs/*-light-*.png | wc -l
```
Expected: 20 PNG nuevos. Verificar que ningún golden oscuro cambió:
`git status --porcelain tests/browser/__screenshots__ | grep -- '-dark-'` debe estar vacío.

- [ ] **Step 3: Galería para el visto de Felipe**

Componer un HTML en el scratchpad con las 20 capturas claras al lado de su gemela oscura
(una fila por escenario, `<img>` con `max-width: 48%`), enviarlo con `SendUserFile` y
**detener la tarea hasta el visto**. Sin visto no se commitea ningún golden. Si Felipe objeta
una familia, la objeción va al plan del módulo dueño de esa familia; los goldens objetados no
entran y su escenario se retira del manifiesto hasta entonces.

- [ ] **Step 4: Con el visto, fijar los `sha256`**

```bash
node - <<'EOF'
const fs = require('node:fs');
const crypto = require('node:crypto');
const p = 'docs/design-system/manifests/laboratory.json';
const m = JSON.parse(fs.readFileSync(p, 'utf8'));
for (const s of m.scenarios) {
  if (s.theme !== 'light') continue;
  s.sha256 = crypto.createHash('sha256').update(fs.readFileSync(s.golden)).digest('hex');
}
fs.writeFileSync(p, `${JSON.stringify(m, null, 2)}\n`);
console.log(m.scenarios.filter((s) => !s.sha256).length, 'sin sha256');
EOF
```
Expected: `0 sin sha256`.

- [ ] **Step 5: El censo exige el claro**

En `tests/design-system/visual-ci-contract.test.mjs`:
- línea 80: `const LABORATORY_SCENARIOS_PER_VIEWPORT = { dark: 10 };` →
  `const LABORATORY_SCENARIOS_PER_VIEWPORT = { dark: 10, light: 10 };`
- en el comentario de las líneas 55–66, sustituir el párrafo del LABORATORIO por:
  `//   · LABORATORIO: bloqueo levantado el 2026-09-06 (theme-claro.css importada en lab-entrypoint.css y laboratory-foundation.css sin :root); los 20 goldens claros los aprobó Felipe sobre la galería de esa fecha.`
- el párrafo de PROGRAMA GENERAL se conserva: PG sigue `dark: 1` hasta su propio plan.

- [ ] **Step 6: Verificar los contratos estáticos y la pata clara**

```bash
node --test tests/design-system/visual-ci-contract.test.mjs
node scripts/design-system-contracts.mjs
E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=light npx playwright test tests/browser/design-system-lab.visual.mjs --workers=1
E2E_BASE_URL=http://127.0.0.1:18081 E2E_THEME=dark  npx playwright test tests/browser/design-system-lab.visual.mjs --workers=1
```
Expected: todo en verde; la pata oscura no reporta ni un golden cambiado.

- [ ] **Step 7: Commit**

```bash
git add docs/design-system/manifests/laboratory.json tests/browser/__screenshots__/design-system-lab.visual.mjs/*-light-*.png tests/design-system/visual-ci-contract.test.mjs
git commit -m "feat(design-system): el laboratorio mide el claro en el CI; 20 goldens claros aprobados por Felipe"
```

- [ ] **Step 8: Tras la primera corrida en Actions, fijar los gemelos Linux**

Cuando el PR corra `design-system-runtime (light)`: si falla solo por capturas Linux ausentes,
bajar el artefacto de capturas de esa corrida (`gh api repos/{owner}/{repo}/actions/runs/<id>/artifacts --jq '.artifacts[].name'`, luego `gh run download <id> -n <nombre>`), copiar los 20 PNG a
`tests/browser/__screenshots__/design-system-lab.visual.mjs/linux/`, declararlos en
`goldenPlatforms.linux` de cada escenario `light` con su `sha256` (mismo script del paso 4,
apuntando a la ruta `linux/`), y commitear. Es el mismo procedimiento que fijó los gemelos
oscuros; no se inventa ninguna captura.

---

### Task 5: cierre — tareas, registro de cambios, wiki y Pull Request

**Files:**
- Modify: `TASKS.md` (§Bloqueantes, entradas del 2026-08-28 en las líneas 669–723)
- Modify: `CHANGELOG.md` (`## [Sin publicar]`)
- Modify: `goals/bloqueo-tema-claro/goal.md` (`## Cierre`)
- Modify: `memoria/log.md` (línea `ingest`)

- [ ] **Step 1: Anotar el cierre donde vive el bloqueo**

En `TASKS.md`, encima de la entrada `**2026-08-28 — \`theme.js\` deshace el claro de entrada…`
añadir un párrafo `**Resuelto el <fecha> (frente \`bloqueo-tema-claro\`, PR #<n>).**` con:
las dos causas medidas (19 pantallas, no 7; la hoja clara sin entrypoint), el arreglo
(theme.js solo movimiento; `:root:not(...)`; imports), los tests cambiados con intención, y la
salida de la condición de hecho del goal. El texto original se conserva debajo.

- [ ] **Step 2: Registro de cambios**

En `CHANGELOG.md`, bajo `## [Sin publicar]`, entrada `### Arreglado: el tema claro llega a las
pantallas PHP y al laboratorio (<fecha>)` con los mismos tres puntos en prosa corta.

- [ ] **Step 3: Cierre del goal y bitácora de la wiki**

`## Cierre` en `goals/bloqueo-tema-claro/goal.md` con comandos y salidas de los cinco puntos de
la condición de hecho; una línea `- <fecha> · ingest · **…**` en `memoria/log.md`; y
`npm run test:wiki` en `RC=0`.

- [ ] **Step 4: Pull Request y gate**

```bash
git push -u origin fix/bloqueo-tema-claro
gh pr create --base main --title "fix(design-system): el tema claro llega a las pantallas PHP y al laboratorio" --body-file <cuerpo con lo verificado>
```
Merge solo con `design-system-static`, `design-system-runtime (light)` y `(dark)` en verde.

---

## Self-review (ejecutado al escribir)

**Cobertura del goal:** condición 1 → tareas 1, 2 (paso 8); condición 2 → tareas 1–3
(comandos listados); condición 3 → tarea 4; condición 4 y 5 → tarea 5. D12/D14 → tareas 1–2;
D16 → tarea 4; D18 → paso 2 de la tarea 4 (ningún golden oscuro cambia).

**Placeholders:** ninguno. Los únicos `<…>` son valores que solo existen al ejecutar (número de
PR, id de corrida, fecha), y cada paso dice cómo obtenerlos.

**Consistencia:** el selector negado se escribe igual en `theme-claro.css` (tarea 2, paso 3) y en
el test (`LIGHT_DEFAULT_SELECTOR`); `THEME`/`materializarTema` solo viven en
`operational-fixtures.mjs`; los ids de escenario claro se derivan con el mismo `replace` en los
pasos 1 y 4 de la tarea 4.

**Lo que este plan deja fuera a propósito:** migrar cualquier módulo al claro (PG es el
primero, D23, con plan propio); `admin/` (D24); `ct-app/src/lib/theme.ts` y su clave
`ct-piloto-theme` (se anota como pendiente del cierre de la Torre); el conmutador D13 en las
vistas (`theme-toggle.js` existe y nadie lo carga: tarea del plan de fase de estreno); extender
los bucles de `design-system-compliance` al claro.
