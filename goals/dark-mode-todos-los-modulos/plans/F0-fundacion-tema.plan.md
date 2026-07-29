# F0 · Fundación de tema — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dejar un único mecanismo de aplicación de tema con dark como default de la cascada, retirar el tema `linen` y el tema muerto, y poner `admin/` bajo el audit del design system.

**Architecture:** El tema se resuelve hoy con cuatro señalizadores redundantes y con `:root` sirviendo valores claros. Se reduce a uno (`html[data-aia-theme]` + su clase espejo `html.aia-theme-dark`), se invierte `:root` a dark, se elimina la rama `linen` completa, y se amplía `scanRoots` del audit para que `admin/` deje de ser punto ciego. Cada tarea es un commit verificable y reversible.

**Tech Stack:** PHP 8.3 sobre Docker Compose, CSS con `@layer` nativo, Node 20 (`node --test`), Playwright, esquemas JSON de contrato en `docs/design-system/`.

**Spec:** `goals/dark-mode-todos-los-modulos/specs/F0-fundacion-tema.md`
**Facts vinculantes:** `goals/dark-mode-todos-los-modulos/facts.md`

## Global Constraints

- **Alcance visual:** desktop de al menos 1180 px, **dark únicamente**. Viewport canónico `1180x820`. Prohibido generar cambios, pruebas o evidencia para mobile, tablet o el tema `linen` (AGENTS.md).
- **Runtime:** todo PHP, Composer y PHPStan se ejecutan dentro del contenedor `app`. Nunca un PHP del host.
- **Publicación:** commits locales únicamente. Ningún `push`, `deploy` ni `gh pr` sin petición explícita del usuario (AGENTS.md).
- **Equivalencia de entrypoints:** `public/css/aia-design-system.css` (agregador) y `public/css/design-system/entrypoints/theme-overrides.css` (entrypoint segmentado) contienen hoy **bloques `@layer theme` idénticos**. Toda modificación a uno se aplica igual al otro, o el gate `scripts/design-system-entrypoint-partition.mjs` falla.
- **Cascada de capas fija:** `reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides` (DS-006). El audit valida el orden declarado en `aia-design-system.css`.
- **Versión del design system:** `1.0.0` (`docs/design-system/version.json`). Las excepciones con `expiresAtVersion` menor o igual fallan el audit.
- **Baselines y goldens:** prohibido regenerarlos para forzar verde. `audit-baseline.json` está protegido por hash y exige un archivo de aprobación en `docs/design-system/baseline-approvals/`.
- **Conservar el worktree:** hay cambios ajenos a este plan sin registrar. No revertirlos, no limpiarlos, no incluirlos en ningún commit (AGENTS.md).

## Precondición: `DESIGN.md` tiene cambios sin registrar

Al escribir este plan (2026-07-25), `git status` mostraba `M DESIGN.md` con **404 inserciones y
79 borrados sin commitear**, ajenos a este goal, más los directorios sin seguimiento
`.impeccable/` y `.superpowers/`.

**Task 9 edita `DESIGN.md`.** Antes de empezar hay que resolverlo, porque si no el commit de esa
tarea arrastraría trabajo ajeno:

1. Comprobar el estado: `git status --short` y `git diff DESIGN.md`.
2. Si esos cambios pertenecen a otro trabajo en curso, **preguntar al usuario** si los commitea
   aparte, los guarda en un stash propio, o prefiere que Task 9 se salte `DESIGN.md` y quede
   como tarea de cierre manual.
3. No decidir por cuenta propia. No hacer `git checkout`, `git stash drop` ni `git clean` sobre
   nada de esto.

Los directorios `.impeccable/` y `.superpowers/` no los toca ninguna tarea; se dejan como están
y nunca se incluyen en un `git add -A` sin filtrar. **Los pasos de commit que usan `git add -A`
deben revisarse con `git status --short` antes de ejecutarse**, y sustituirse por rutas
explícitas si aparece cualquier archivo ajeno al plan.

---

### Task 1: Cerrar el rojo vivo del audit

`node scripts/design-system-audit.mjs` falla hoy en `main` (`8a13ad4`) con
`programacion-semanal embedded-style-block: 1 > path budget 0`. **Ninguna otra tarea empieza
hasta que esta cierre**: con CI rojo de partida no se distingue lo que rompe este plan de lo que
ya estaba roto.

**Files:**
- Modify: `views/programacion-semanal/programacion_semanal.view.php:43-51` (eliminar el bloque `<style>`)
- Modify: `public/css/programacion-semanal.css` (recibir la regla, dentro de `@layer components`)

**Interfaces:**
- Consumes: nada.
- Produces: audit en verde, punto de partida limpio para las tareas 2–11.

- [ ] **Step 1: Ejecutar el audit para ver el fallo actual**

```bash
node scripts/design-system-audit.mjs
```

Expected: FALLA con `- programacion-semanal embedded-style-block: 1 > path budget 0`

- [ ] **Step 2: Añadir la regla a la hoja del módulo**

En `public/css/programacion-semanal.css`, dentro del bloque `@layer components` que empieza en la línea 2382, añadir al final:

```css
  /* Shell sidebar (DS-027): la altura del HOT ya no resta el navbar legacy
     (380px). #encabezado solo aloja inputs ocultos (0px); el único elemento
     con altura real entre el body y este contenedor es la context-bar sticky
     del shell (#shellContextBar), medida en runtime a 1180x820 dark = 49px
     (chip de semana 2rem + padding-block 2*0.5rem + borde 1px). Ver
     goals/sidebar-todos-modulos/reports/task-6-report.md. */
  body.aia-shell--sidebar #hot-container {
    height: calc(100vh - 49px);
  }
```

- [ ] **Step 3: Eliminar el bloque `<style>` de la vista**

En `views/programacion-semanal/programacion_semanal.view.php`, borrar íntegras las líneas 43 a 51 (desde `    <style>` hasta `    </style>` inclusive). La línea siguiente debe quedar siendo `</head>`.

- [ ] **Step 4: Verificar que el audit pasa**

```bash
node scripts/design-system-audit.mjs
```

Expected: `Design system audit passed against baseline.`

- [ ] **Step 5: Verificar la altura del HOT en navegador**

Levantar el stack y comprobar en `1180x820` dark que la grilla de `/programacion-semanal` conserva su altura y no aparece scroll vertical del documento:

```bash
docker compose up -d db app
```

Navegar a `http://localhost:8081/programacion-semanal` y confirmar con la consola del navegador:

```js
getComputedStyle(document.querySelector('#hot-container')).height
```

Expected: `771px` (820 − 49), o el valor equivalente al viewport real menos 49 px.

- [ ] **Step 6: Commit**

```bash
git add views/programacion-semanal/programacion_semanal.view.php public/css/programacion-semanal.css
git commit -m "fix(design-system): mover el <style> de programacion-semanal a su hoja de modulo"
```

---

### Task 2: Borrar el tema muerto

`src/View/Components/NavbarComponent.php` está huérfano: ninguna vista ni ruta lo instancia.
Arrastra dos hojas que sólo él cargaba. Verificado el 2026-07-25: las demás menciones de
`navbar.css` en el repositorio son comentarios históricos.

Se hace pronto porque **baja** los contadores del audit (el gate por regla sólo falla al subir),
lo que despeja ruido antes de los cambios grandes.

**Files:**
- Delete: `src/View/Components/NavbarComponent.php`
- Delete: `public/css/dark-mode.css`
- Delete: `public/css/navbar.css`
- Modify: `docs/design-system/exceptions.json` (presupuesto `foundation-shell`: retirar del array `paths` las dos rutas borradas)
- Create: `tests/design-system/dead-theme-removal.test.mjs`

**Trampa descubierta al ejecutar (2026-07-25):** el presupuesto de ruta `foundation-shell` de
`docs/design-system/exceptions.json` lista `public/css/navbar.css` y
`src/View/Components/NavbarComponent.php`. El audit **falla en duro ante rutas configuradas que
no existen** (`scripts/design-system-audit.mjs:352`), con independencia de los contadores. Borrar
los archivos sin sacarlos de ahí deja el audit rojo.

`exceptions.json` **no** está protegido por hash —esa protección es sólo de
`audit-baseline.json`—, así que retirar las dos rutas no requiere aprobación humana. Se retiran
únicamente esas dos entradas del array `paths`. **`maxViolations` se deja intacto**: son techos,
no cuentas exactas, y ajustarlos es una decisión aparte que no pertenece a una tarea de borrado.

**Interfaces:**
- Consumes: audit en verde (Task 1).
- Produces: repositorio sin el vocabulario de tema `--surface-bg` / `--text-main`. Task 3 asume que `dark-mode.css` ya no existe.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/design-system/dead-theme-removal.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const repositoryRoot = new URL('../..', import.meta.url);
const exists = (path) => existsSync(new URL(path, repositoryRoot));
const read = (path) => readFile(new URL(path, repositoryRoot), 'utf8');

test('el componente de navbar legacy no existe', () => {
  assert.equal(exists('src/View/Components/NavbarComponent.php'), false);
});

test('las hojas del tema legacy no existen', () => {
  assert.equal(exists('public/css/dark-mode.css'), false);
  assert.equal(exists('public/css/navbar.css'), false);
});

test('ningun entrypoint importa las hojas del tema legacy', async () => {
  for (const entrypoint of [
    'public/css/aia-design-system.css',
    'public/css/design-system/entrypoints/core.css',
  ]) {
    const css = await read(entrypoint);
    assert.equal(css.includes('dark-mode.css'), false, `${entrypoint} importa dark-mode.css`);
    assert.equal(css.includes('navbar.css'), false, `${entrypoint} importa navbar.css`);
  }
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
```

Expected: FALLA en los dos primeros tests (`expected false to equal true` — los archivos existen).

- [ ] **Step 3: Confirmar por grep que nada vivo los referencia**

```bash
grep -rn "NavbarComponent" --include="*.php" views src admin public
grep -rn "dark-mode\.css\|navbar\.css" --include="*.php" --include="*.css" --include="*.json" --include="*.mjs" views src admin public scripts docs/design-system/manifests
```

Expected: la primera no devuelve nada fuera de `src/View/Components/NavbarComponent.php`. La segunda sólo devuelve comentarios en `public/css/handsontable-module.css:336`, `public/css/styles.css:208`, `public/css/design-system/adapters/shell-sidebar.css:36` y las autorreferencias de los archivos a borrar. **Si aparece cualquier consumidor real, detenerse y escalar al usuario.**

- [ ] **Step 4: Borrar los tres archivos**

```bash
git rm src/View/Components/NavbarComponent.php public/css/dark-mode.css public/css/navbar.css
```

- [ ] **Step 5: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
```

Expected: PASS, 3 tests.

- [ ] **Step 6: Verificar audit y análisis estático**

```bash
node scripts/design-system-audit.mjs
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: audit `passed against baseline` (los contadores bajan, nunca suben). PHPStan sin errores nuevos.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore(design-system): borrar el tema muerto (NavbarComponent, dark-mode.css, navbar.css)"
```

---

### Task 3: Eliminar el señalizador `body.dark-mode`

Cuarto señalizador de tema, redundante con `html.aia-theme-dark`. `theme.js` lo aplica y **99
selectores vivos** dependen de él, repartidos en 7 archivos.

**Corrección del 2026-07-25, detectada al ejecutar.** La primera redacción de esta tarea contaba
61 selectores en 4 archivos porque buscaba el literal `body\.dark-mode`. Eso **se salta los
selectores con una clase de página intercalada**, que son 37 más:

| Forma del selector | Apariciones |
|---|---|
| `body.dark-mode` (directa) | 62 |
| `body.ps-page.dark-mode` | 26 |
| `body.contratos-page.dark-mode` | 8 |
| `body.pi-page.dark-mode` | 2 |
| `.pg-page.dark-mode` | 1 |

El riesgo que esto creaba era el peor posible: el test de la primera redacción, el audit y la
suite estática **habrían pasado en verde** mientras el cambio mataba en silencio el color de
estado en Programación Semanal, Programación Intermedia, Contratos y **Programa General**.

**Files:**
- Modify: `public/css/listado-actividades.css` (37 apariciones; la de la línea 22 es la única sin prefijo `html.aia-theme-dark`)
- Modify: `public/css/contratos.css` (27: 19 directas + 8 con `.contratos-page`)
- Modify: `public/css/programacion-semanal.css` (26 con `.ps-page`; ojo con la línea 1383, sin prefijo)
- Modify: `public/css/design-system/adapters/legacy-bridge.css` (5 apariciones, líneas 83–98)
- Modify: `public/css/programacion-intermedia.css` (2 con `.pi-page`)
- Modify: `public/css/programa-general.css:28` (1 con `.pg-page`) — **archivo del piloto protegido, ver aviso abajo**
- Modify: `public/css/design-system/components/navigation.css:272` (comentario que cita `dark-mode.css`, ya borrado en Task 2)
- Modify: `public/js/modules/aia_ui/theme.js:26,31` (dejar de aplicar la clase)
- Modify: `public/js/modules/aia_ui/design_system_lab.js:7` (dejar de aplicar la clase)
- Modify: `tests/design-system/dead-theme-removal.test.mjs`

**Aviso sobre el piloto.** `DESIGN.md` prohíbe modificar Programa General «desde la migración de
otra superficie». Esta tarea **no** es una migración de otra superficie: retira un señalizador de
tema global, y `programa-general.css:28` lo usa. No hay alternativa —dejarlo intacto rompería
justo el piloto—, pero por eso esta tarea exige evidencia visual de `/programa-general` antes y
después, archivada en `goals/dark-mode-todos-los-modulos/evidence/F0/`.

**Regla de transformación.** Para cada selector que contenga `.dark-mode`:

1. Eliminar el segmento `.dark-mode`, conservando la clase de página si la hay
   (`body.ps-page.dark-mode` → `body.ps-page`).
2. Si el selector resultante **no** empieza ya por `html.aia-theme-dark`, prefijarlo con
   `html.aia-theme-dark ` para que siga siendo condicional al tema.

El paso 2 es el que evita que estas reglas pasen a aplicarse incondicionalmente. Los selectores
sin prefijo conocidos son `listado-actividades.css:22` y `programacion-semanal.css:1383`;
verificar que no hay más antes de dar por buena la transformación.

**Interfaces:**
- Consumes: `dark-mode.css` ya borrado (Task 2).
- Produces: `html.aia-theme-dark` como único selector de tema en CSS. Tasks 5–9 asumen que no queda `body.dark-mode`.

- [ ] **Step 1: Escribir el test que falla**

Añadir al final de `tests/design-system/dead-theme-removal.test.mjs`:

```javascript
import { readdir } from 'node:fs/promises';

const cssFiles = async (dir) => {
  const entries = await readdir(new URL(dir, repositoryRoot), { withFileTypes: true, recursive: true });
  return entries
    .filter((entry) => entry.isFile() && entry.name.endsWith('.css'))
    .map((entry) => `${entry.parentPath ?? entry.path}/${entry.name}`);
};

// El regex busca la CLASE, no `body.dark-mode`: los selectores con una clase de
// pagina intercalada (body.ps-page.dark-mode, .pg-page.dark-mode) tambien cuentan.
test('ninguna hoja de estilo depende de la clase legacy dark-mode', async () => {
  const offenders = [];
  for (const file of await cssFiles('public/css')) {
    const css = await readFile(file, 'utf8');
    if (/\.dark-mode\b/.test(css)) offenders.push(file);
  }
  assert.deepEqual(offenders, [], `hojas con .dark-mode: ${offenders.join(', ')}`);
});

test('el runtime de tema no aplica la clase legacy dark-mode', async () => {
  for (const script of [
    'public/js/modules/aia_ui/theme.js',
    'public/js/modules/aia_ui/design_system_lab.js',
  ]) {
    const source = await read(script);
    assert.equal(/["']dark-mode["']/.test(source), false, `${script} aplica la clase dark-mode`);
  }
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
```

Expected: FALLA con `hojas con body.dark-mode: …listado-actividades.css, …contratos.css, …legacy-bridge.css` y con `theme.js aplica la clase dark-mode`.

- [ ] **Step 3: Reescribir los selectores que ya llevan prefijo**

Retirar el segmento `.dark-mode` conservando la clase de página. Dos sustituciones, en este orden — la específica primero, si no la genérica se come el caso con clase de página:

```bash
FILES="public/css/listado-actividades.css \
  public/css/contratos.css \
  public/css/programacion-semanal.css \
  public/css/programacion-intermedia.css \
  public/css/programa-general.css \
  public/css/design-system/adapters/legacy-bridge.css"

# 1) body.<pagina>.dark-mode  ->  body.<pagina>     y   .<pagina>.dark-mode -> .<pagina>
sed -i '' -E 's/(\.(ps|pi|pg|contratos)-page)\.dark-mode/\1/g' $FILES

# 2) body.dark-mode  ->  body     (CONSERVAR `body`: en muchas reglas es el SUJETO)
sed -i '' -E 's/html\.aia-theme-dark body\.dark-mode/html.aia-theme-dark body/g' $FILES
```

**No borres `body` en el paso 2.** La primera redacción de este `sed` sustituía
`html.aia-theme-dark body.dark-mode` por `html.aia-theme-dark` a secas, y eso rompió
`contratos.css:167` y `listado-actividades.css:730`, donde `body.dark-mode` no es un ancestro
sino **el sujeto** de la regla:

```css
html.aia-theme-dark body.dark-mode { background: …; color: …; }
```

Al perder `body`, la regla pasa a pintar `<html>` —donde `foundation.css` ya fija lo mismo, así
que es inerte— y `<body>` cae al `--surface-bg: #f5f5f7` de `styles.css`. Resultado medido:
fondo `rgb(245,245,247)` y texto `rgb(29,29,31)` en `/contratos` y `/listado-actividades`, en el
único tema soportado. Ningún gate lo detecta.

Contar antes y después para reconciliar las 99 apariciones:

```bash
grep -rc "\.dark-mode" --include="*.css" public/css | grep -v ':0$'
```

- [ ] **Step 4: Corregir a mano los selectores sin prefijo de tema**

Tras el Step 3 quedan los que no empezaban por `html.aia-theme-dark` y que ahora se aplicarían **incondicionalmente**. Localizarlos:

```bash
grep -rn "\.dark-mode" --include="*.css" public/css
```

Los dos conocidos:

En `public/css/listado-actividades.css:22`, sustituir:

```css
body.dark-mode #encabezado nav.navbar.navbar-aia,
```

por:

```css
html.aia-theme-dark #encabezado nav.navbar.navbar-aia,
```

En `public/css/programacion-semanal.css:1383`, el selector `body.ps-page.dark-mode .text-danger` quedó como `body.ps-page .text-danger` tras el Step 3, sin condición de tema. Prefijarlo:

```css
html.aia-theme-dark body.ps-page .text-danger
```

Si el `grep` revela más selectores sin prefijo, aplicarles la misma regla y anotarlos en el reporte.

- [ ] **Step 5: Dejar de aplicar la clase en el runtime de tema**

En `public/js/modules/aia_ui/theme.js`, sustituir el bloque de las líneas 25 a 35:

```javascript
    if (document.body) {
      document.body.classList.toggle("dark-mode", nextTheme === "dark");
    } else {
      document.addEventListener(
        "DOMContentLoaded",
        () => {
          document.body.classList.toggle("dark-mode", nextTheme === "dark");
        },
        { once: true },
      );
    }
    document.dispatchEvent(new CustomEvent("aia-theme-change", { detail: { theme: nextTheme } }));
```

por:

```javascript
    document.dispatchEvent(new CustomEvent("aia-theme-change", { detail: { theme: nextTheme } }));
```

En `public/js/modules/aia_ui/design_system_lab.js:7`, eliminar la línea:

```javascript
    if (document.body) document.body.classList.add("dark-mode");
```

- [ ] **Step 6: Actualizar el comentario obsoleto de navigation.css**

En `public/css/design-system/components/navigation.css:272`, el comentario cita `dark-mode.css`, borrado en Task 2. Sustituir la referencia por: `que antes ganaba desde la hoja legacy de tema, ya retirada (F0/Task 2).`

- [ ] **Step 7: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
```

Expected: PASS, 5 tests.

- [ ] **Step 8: Verificar en navegador las cinco superficies afectadas**

En `1180x820` dark, contra el contenedor servido, comprobar que conservan su aspecto — con atención al **color de estado**, que es lo que estos selectores gobiernan (badges, leyendas, celdas de Handsontable, `.text-danger`):

| Ruta | Qué mirar |
|---|---|
| `/programa-general` | **Piloto protegido.** Captura antes y después a `evidence/F0/`. Colores de estado de la grilla |
| `/programacion-semanal` | Badges y leyenda de estado, cabeceras DataTables, `.text-danger` |
| `/programacion-intermedia` | Colores de estado de la grilla |
| `/contratos` | Cabeceras de Handsontable y celdas |
| `/listado-actividades` | Cabeceras de grilla, navegación de información, tarjetas |

Consola sin errores en las cinco.

```bash
npm run test:design-system:static
npm run test:design-system:runtime
node scripts/design-system-audit.mjs
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Expected: todos en verde.

**Comprobación específica de no-regresión de color.** Un `.dark-mode` mal transformado no rompe ningún gate — por eso esta comprobación es manual y obligatoria. En cada ruta, en la consola del navegador:

```js
getComputedStyle(document.body).getPropertyValue('--ps-critical-bg')  // ajustar al token de cada pagina
```

Expected: resuelve al valor oscuro, no a vacío. Si resuelve vacío, la regla que lo define perdió su selector.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "refactor(design-system): unificar el senalizador de tema en html.aia-theme-dark"
```

---

### Task 4: Unificar el arranque de tema en BI y laboratorio

Dos superficies fijan el tema por su cuenta en vez de usar `theme-bootstrap.js`: BI con un IIFE
inline y el laboratorio con el atributo escrito a mano en `<html>`. `plan-compras` queda
deliberadamente fuera: su contrato completo es F5.

**Files:**
- Modify: `views/bi/_layout.php:2` y `:7-15`
- Modify: `views/design-system/lab.view.php:2`
- Modify: `src/View/Components/DesignSystemHeadComponent.php:26-32` (`renderLaboratory`)
- Modify: `tests/design-system/dead-theme-removal.test.mjs`

**Interfaces:**
- Consumes: `DesignSystemHeadComponent::renderScript(string $url): string` (ya existente).
- Produces: `renderLaboratory()` pasa a emitir `theme-bootstrap.js` antes de sus hojas, igual que `render()` y `renderForModule()`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/design-system/dead-theme-removal.test.mjs`:

```javascript
test('ninguna vista fija el tema por su cuenta salvo plan-compras (F5)', async () => {
  for (const view of ['views/bi/_layout.php', 'views/design-system/lab.view.php']) {
    const source = await read(view);
    assert.equal(
      /setAttribute\(\s*['"]data-aia-theme['"]/.test(source), false,
      `${view} fija el tema con script inline`,
    );
    assert.equal(
      /<html[^>]*data-aia-theme/.test(source), false,
      `${view} fija el tema con atributo escrito a mano`,
    );
  }
});

test('renderLaboratory emite el bootstrap de tema', async () => {
  const source = await read('src/View/Components/DesignSystemHeadComponent.php');
  const body = source.slice(source.indexOf('function renderLaboratory'));
  assert.match(body.slice(0, 400), /theme-bootstrap\.js/);
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
```

Expected: FALLA con `views/bi/_layout.php fija el tema con script inline` y `renderLaboratory` sin `theme-bootstrap.js`.

- [ ] **Step 3: Hacer que `renderLaboratory` emita el bootstrap**

En `src/View/Components/DesignSystemHeadComponent.php`, sustituir el método completo:

```php
    public static function renderLaboratory(): string
    {
        return implode("\n", array_map([self::class, 'renderStylesheet'], [
            '/css/tokens.css',
            '/css/design-system/lab-entrypoint.css',
        ]));
    }
```

por:

```php
    public static function renderLaboratory(): string
    {
        return implode("\n", array_merge(
            [self::renderScript('/js/modules/aia_ui/theme-bootstrap.js')],
            array_map([self::class, 'renderStylesheet'], [
                '/css/tokens.css',
                '/css/design-system/lab-entrypoint.css',
            ]),
        ));
    }
```

- [ ] **Step 4: Sustituir el script inline de BI**

En `views/bi/_layout.php`, borrar íntegro el bloque de las líneas 7 a 15 (desde `    <script>` hasta `    </script>` del IIFE que fija `data-aia-theme`) y poner en su lugar:

```php
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme-bootstrap.js') ?>
```

- [ ] **Step 5: Quitar el atributo escrito a mano del laboratorio**

En `views/design-system/lab.view.php:2`, sustituir:

```html
<html lang="es" data-aia-theme="dark" class="aia-theme-dark">
```

por:

```html
<html lang="es">
```

- [ ] **Step 6: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/dead-theme-removal.test.mjs
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: tests PASS. PHPStan sin errores nuevos.

- [ ] **Step 7: Verificar en navegador que no hay destello de tema**

En `1180x820` dark: `http://localhost:8081/bi/control-tower` y `http://localhost:8081/internal/design-system`. Ambas deben cargar oscuras **sin flash claro** en el primer pintado, con los charts y el laboratorio operativos. Consola y red sin errores.

```bash
npx playwright test tests/browser/bi_control_tower.spec.mjs --workers=1
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "refactor(design-system): BI y laboratorio arrancan el tema con theme-bootstrap.js"
```

---

### Task 5: Invertir el default de la cascada a dark

Hoy `:root` comparte bloque con `linen`, así que el default de la cascada es claro y toda
superficie sin `theme-bootstrap.js` cae en claro. Se invierte: `:root` sirve dark y `linen`
queda como override explícito.

**Esta tarea es deliberadamente transitoria.** Tasks 6–9 retiran `linen` por completo. Se hace
en dos movimientos para tener un estado intermedio funcional y verificable: si la retirada de
`linen` se detuviera, el default dark ya estaría entregado.

**Files:**
- Modify: `public/css/aia-design-system.css:36-89` (bloque `@layer theme`)
- Modify: `public/css/design-system/entrypoints/theme-overrides.css:1-54` (bloque `@layer theme`, **idéntico al anterior**)
- Create: `tests/design-system/theme-default.test.mjs`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `:root` con valores dark. Tasks 6–9 borran el bloque `linen` que esta tarea deja aislado.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/design-system/theme-default.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const THEME_ENTRYPOINTS = [
  'public/css/aia-design-system.css',
  'public/css/design-system/entrypoints/theme-overrides.css',
];

const themeBlock = (css, selector) => {
  const index = css.indexOf(selector);
  assert.notEqual(index, -1, `no se encontro el bloque ${selector}`);
  return css.slice(index, css.indexOf('}', index));
};

for (const entrypoint of THEME_ENTRYPOINTS) {
  test(`${entrypoint}: :root sirve los valores dark`, async () => {
    const css = await read(entrypoint);
    const root = themeBlock(css, ':root,');
    assert.match(root, /color-scheme:\s*dark/);
    assert.match(root, /--ds-active-bg-canvas:\s*var\(--ds-color-bg-canvas-dark\)/);
    assert.match(root, /--ds-active-text-primary:\s*var\(--ds-color-text-primary-dark\)/);
  });

  test(`${entrypoint}: :root se agrupa con el selector dark, no con linen`, async () => {
    const css = await read(entrypoint);
    const root = css.slice(css.indexOf(':root,'), css.indexOf('{', css.indexOf(':root,')));
    assert.match(root, /\[data-aia-theme="dark"\]/);
    assert.equal(/linen/.test(root), false, ':root sigue agrupado con linen');
  });
}

test('los dos entrypoints declaran bloques de tema equivalentes', async () => {
  const [aggregator, segmented] = await Promise.all(THEME_ENTRYPOINTS.map(read));
  const extract = (css) => css.slice(css.indexOf('@layer theme'), css.indexOf('@layer legacy-overrides'));
  assert.equal(extract(aggregator).trim(), extract(segmented).trim());
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/theme-default.test.mjs
```

Expected: FALLA con `:root sigue agrupado con linen` y sin `color-scheme: dark`.

- [ ] **Step 3: Invertir el bloque de tema en el agregador**

En `public/css/aia-design-system.css`, sustituir el contenido completo de `@layer theme { … }` (líneas 36 a 89) por:

```css
@layer theme {
  :root,
  [data-aia-theme="dark"],
  .aia-theme-dark {
    color-scheme: dark;
    --ds-active-bg-canvas: var(--ds-color-bg-canvas-dark);
    --ds-active-bg-page: var(--ds-color-bg-page-dark);
    --ds-active-surface: var(--ds-color-surface-dark);
    --ds-active-surface-raised: var(--ds-color-surface-raised-dark);
    --ds-active-surface-glass: var(--ds-color-surface-glass-dark);
    --ds-active-text-primary: var(--ds-color-text-primary-dark);
    --ds-active-text-secondary: var(--ds-color-text-secondary-dark);
    --ds-active-border: var(--ds-color-border-dark);
    --ds-active-focus-ring: var(--ds-color-focus-ring-dark);
    --ds-active-action-primary: var(--ds-color-domain-corporate-on-dark);
    --ds-active-action-primary-hover: var(--aia-green-light);
    --ds-active-action-text: var(--ds-color-text-on-domain-dark);
    --ds-active-domain-construction: var(--ds-color-domain-construction-on-dark);
    --ds-active-domain-construction-text: var(--ds-color-text-on-domain-dark);
    --ds-active-data-plan: var(--ds-color-domain-real-estate-on-dark);
    --ds-active-data-executed: var(--ds-color-domain-corporate-on-dark);
    --ds-active-nav-bg: var(--ds-nav-bg-dark);
    --ds-active-nav-border: var(--ds-nav-border-color-dark);
    --ds-active-nav-text: var(--ds-active-text-primary);
    --ds-active-nav-text-muted: var(--ds-active-text-secondary);
    --ds-active-nav-mark-filter: invert(1) brightness(1.15);
  }

  /* Override explicito; se retira por completo en F0/Task 7. */
  [data-aia-theme="linen"],
  .aia-theme-linen {
    color-scheme: light;
    --ds-active-bg-canvas: var(--ds-color-bg-canvas);
    --ds-active-bg-page: var(--ds-color-bg-page);
    --ds-active-surface: var(--ds-color-surface);
    --ds-active-surface-raised: var(--ds-color-surface-raised);
    --ds-active-surface-glass: var(--ds-color-surface-glass);
    --ds-active-text-primary: var(--ds-color-text-primary);
    --ds-active-text-secondary: var(--ds-color-text-secondary);
    --ds-active-border: var(--ds-color-border-default);
    --ds-active-focus-ring: var(--ds-color-focus-ring-light);
    --ds-active-action-primary: var(--ds-color-domain-corporate);
    --ds-active-action-primary-hover: var(--ds-color-brand-primary-dark);
    --ds-active-action-text: var(--ds-color-text-inverse);
    --ds-active-domain-construction: var(--ds-color-domain-construction);
    --ds-active-domain-construction-text: var(--ds-color-text-inverse);
    --ds-active-data-plan: var(--ds-color-domain-real-estate);
    --ds-active-data-executed: var(--ds-color-domain-corporate);
    --ds-active-nav-bg: var(--ds-color-surface-raised);
    --ds-active-nav-border: var(--ds-color-border-default);
    --ds-active-nav-text: var(--ds-color-text-primary);
    --ds-active-nav-text-muted: var(--ds-color-text-secondary);
    --ds-active-nav-mark-filter: none;
  }
}
```

El orden importa: `:root` primero, `linen` después. Ambos selectores tienen la misma
especificidad, así que gana el último y `linen` sigue funcionando cuando se declara.

- [ ] **Step 4: Aplicar el mismo bloque al entrypoint segmentado**

Sustituir el `@layer theme { … }` de `public/css/design-system/entrypoints/theme-overrides.css` (líneas 1 a 54) por **exactamente el mismo bloque** del paso anterior. Los dos archivos deben quedar idénticos en esa sección.

- [ ] **Step 5: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/theme-default.test.mjs
```

Expected: PASS, 5 tests.

- [ ] **Step 6: Verificar el default sin JavaScript**

Comprobar que una superficie sin `data-aia-theme` cae en oscuro. Con el stack levantado, en la consola del navegador sobre cualquier página:

```js
document.documentElement.removeAttribute('data-aia-theme');
document.documentElement.classList.remove('aia-theme-dark', 'aia-theme-linen');
getComputedStyle(document.documentElement).getPropertyValue('--ds-active-bg-canvas');
```

Expected: resuelve al canvas oscuro (`#0b100d` o su equivalente OKLCH renderizado), no al claro.

- [ ] **Step 7: Verificar las superficies con manifiesto**

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
npm run test:design-system:runtime
```

Expected: todos en verde. En navegador, `1180x820` dark: `/login`, `/proyectos`, `/programa-general`, `/programacion-intermedia`, `/programacion-semanal`, `/internal/design-system` sin cambio visual respecto al estado anterior. **Archivar captura de `/programa-general` en `goals/dark-mode-todos-los-modulos/evidence/F0/`**: es el piloto protegido por `DESIGN.md`.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(design-system): :root sirve dark; linen pasa a override explicito"
```

---

### Task 6: Retirar `linen` — esquemas y datos de contrato

`linen` vive en cuatro esquemas JSON, en los 89 grupos de `ui-groups-inventory.json` y en tres
archivos de contrato. Se empieza por los datos porque los gates de Tasks 7–9 los leen.

**Files:**
- Modify: `docs/design-system/module-manifest.schema.json`
- Modify: `docs/design-system/ui-groups-inventory.schema.json`
- Modify: `docs/design-system/runtime-budget.schema.json`
- Modify: `docs/design-system/family-approvals.schema.json`
- Modify: `docs/design-system/ui-groups-inventory.json` (89 grupos, campo `themes`)
- Modify: `docs/design-system/family-approvals.json`
- Modify: `docs/design-system/homologation.json`
- Modify: `docs/design-system/component-catalog.json`
- Create: `tests/design-system/linen-removal.test.mjs`

**Interfaces:**
- Consumes: `:root` ya en dark (Task 5).
- Produces: `themes` sólo admite `["dark"]`. Tasks 7–9 asumen que los esquemas ya lo rechazan.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/design-system/linen-removal.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

const SCHEMAS = [
  'docs/design-system/module-manifest.schema.json',
  'docs/design-system/ui-groups-inventory.schema.json',
  'docs/design-system/runtime-budget.schema.json',
  'docs/design-system/family-approvals.schema.json',
];

for (const schema of SCHEMAS) {
  test(`${schema} no admite el tema linen`, async () => {
    assert.equal(/linen/i.test(await read(schema)), false);
  });
}

test('todos los grupos de UI declaran unicamente el tema dark', async () => {
  const inventory = await readJson('docs/design-system/ui-groups-inventory.json');
  for (const group of inventory.groups) {
    assert.deepEqual(group.themes, ['dark'], `grupo ${group.id} declara ${JSON.stringify(group.themes)}`);
  }
});

test('los contratos de familia y catalogo no mencionan linen', async () => {
  for (const contract of [
    'docs/design-system/family-approvals.json',
    'docs/design-system/homologation.json',
    'docs/design-system/component-catalog.json',
  ]) {
    assert.equal(/linen/i.test(await read(contract)), false, `${contract} menciona linen`);
  }
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/linen-removal.test.mjs
```

Expected: FALLA en los 4 esquemas, en los 89 grupos y en los 3 contratos.

- [ ] **Step 3: Retirar `linen` de los cuatro esquemas**

En cada uno de los cuatro `*.schema.json`, localizar el `enum` que lista los temas y dejar únicamente `"dark"`:

```json
"themes": {
  "type": "array",
  "items": { "enum": ["dark"] },
  "minItems": 1,
  "uniqueItems": true
}
```

Conservar el resto de la definición (`type`, `minItems`, `uniqueItems`) tal como esté en cada esquema; sólo cambia el `enum`.

- [ ] **Step 4: Actualizar los 89 grupos del inventario**

```bash
python3 - <<'PY'
import json
path = 'docs/design-system/ui-groups-inventory.json'
data = json.load(open(path))
for group in data['groups']:
    group['themes'] = ['dark']
json.dump(data, open(path, 'w'), ensure_ascii=False, indent=2)
open(path, 'a').write('\n')
PY
```

- [ ] **Step 5: Retirar `linen` de los tres contratos**

Editar `family-approvals.json`, `homologation.json` y `component-catalog.json` eliminando toda entrada, campo o valor `linen`. Donde `linen` aparezca como elemento de una lista de temas, dejar la lista con `"dark"` únicamente; donde aparezca como clave de un objeto por tema, eliminar esa clave conservando la de `dark`.

- [ ] **Step 6: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/linen-removal.test.mjs
node --test tests/design-system/ui-groups-inventory.test.mjs
```

Expected: PASS en ambos.

- [ ] **Step 7: Commit**

```bash
git add docs/design-system
git commit -m "chore(design-system): retirar linen de esquemas y contratos"
```

---

### Task 7: Retirar `linen` — CSS

**Files:**
- Modify: `public/css/aia-design-system.css` (bloque `linen` introducido en Task 5)
- Modify: `public/css/design-system/entrypoints/theme-overrides.css` (idéntico)
- Modify: `public/css/tokens.css`
- Modify: `public/css/handsontable-module.css`
- Modify: `public/css/pdc.css`
- Modify: `public/css/programacion-semanal.css`
- Modify: `public/css/programacion-intermedia.css`
- Modify: `public/css/design-system/adapters/lps-drawer.css`
- Modify: `public/css/design-system/adapters/shell-sidebar.css`
- Modify: `tests/design-system/linen-removal.test.mjs`

**Interfaces:**
- Consumes: esquemas ya sin `linen` (Task 6).
- Produces: ninguna hoja de estilo contiene ramas de tema claro. Task 8 asume que el CSS ya no las tiene.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/design-system/linen-removal.test.mjs`:

```javascript
import { readdir } from 'node:fs/promises';

const repositoryRoot = new URL('../..', import.meta.url);

test('ninguna hoja de estilo declara el tema linen', async () => {
  const entries = await readdir(new URL('public/css', repositoryRoot), {
    withFileTypes: true, recursive: true,
  });
  const offenders = [];
  for (const entry of entries) {
    if (!entry.isFile() || !entry.name.endsWith('.css')) continue;
    const file = `${entry.parentPath ?? entry.path}/${entry.name}`;
    if (/linen/i.test(await readFile(file, 'utf8'))) offenders.push(entry.name);
  }
  assert.deepEqual(offenders, [], `hojas con linen: ${offenders.join(', ')}`);
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/linen-removal.test.mjs
```

Expected: FALLA listando las nueve hojas.

- [ ] **Step 3: Borrar el bloque `linen` de los dos entrypoints**

En `public/css/aia-design-system.css` y en `public/css/design-system/entrypoints/theme-overrides.css`, borrar íntegro el bloque introducido en Task 5:

```css
  /* Override explicito; se retira por completo en F0/Task 7. */
  [data-aia-theme="linen"],
  .aia-theme-linen {
    …
  }
```

El `@layer theme` queda con un único bloque: `:root, [data-aia-theme="dark"], .aia-theme-dark`.

- [ ] **Step 4: Simplificar los selectores de tema en las siete hojas restantes**

En `tokens.css`, `handsontable-module.css`, `pdc.css`, `programacion-semanal.css`, `programacion-intermedia.css`, `adapters/lps-drawer.css` y `adapters/shell-sidebar.css`:

- Si una regla está bajo `[data-aia-theme="linen"]` o `.aia-theme-linen`, **borrar la regla completa**.
- Si un selector agrupa ambos temas (por ejemplo `.aia-theme-dark .x, .aia-theme-linen .x`), dejar sólo la rama dark.
- Si `linen` sólo aparece en un comentario, actualizar el comentario para que no prometa un tema que ya no existe.

Localizar cada aparición con:

```bash
grep -rn "linen" public/css
```

- [ ] **Step 5: Ejecutar el test para verificar que pasa**

```bash
node --test tests/design-system/linen-removal.test.mjs
node --test tests/design-system/theme-default.test.mjs
```

Expected: PASS en ambos.

- [ ] **Step 6: Verificar audit, partición y runtime**

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
npm run test:design-system:runtime
```

Expected: todos en verde. El audit debe **bajar** contadores, nunca subirlos.

- [ ] **Step 7: Commit**

```bash
git add public/css tests/design-system
git commit -m "chore(design-system): retirar las ramas CSS del tema linen"
```

---

### Task 8: Retirar `linen` — runtime y markup del conmutador

**Files:**
- Modify: `public/js/modules/aia_ui/theme.js` (reducir a aplicar dark y respetar `prefers-reduced-motion`)
- Modify: `public/js/modules/aia_ui/theme-bootstrap.js`
- Modify: `public/js/modules/aia_ui/design_system_lab.js:12-14` (sobrescribe `setTheme`/`toggleTheme` con `enforceDarkTheme`; deja de ser necesario)
- Delete: `views/auth/partials/auth-theme-switch.php`
- Modify: `views/auth/login.view.php`, `views/auth/password-forgot.view.php`, `views/auth/password-reset.view.php` (retirar la inclusión del parcial)
- Modify: `views/partials/shell_sidebar.php:119` (ítem `['label' => 'Cambiar tema', 'themeToggle' => true, …]` y la rama del renderizador que lo pinta)
- Modify: `views/core/project_selector.view.php:38` (mismo ítem `Cambiar tema`)
- Modify: `views/design-system/families/shell-navigation.php:55` (el laboratorio documenta el ítem; retirarlo de la familia)
- Modify: `public/js/cargarDatosGeneralesPagina2.js:175-205,297-310` (construye dos botones `.aia-theme-switch` y llama a `bindThemeSwitches`)
- Modify: `public/css/login-brand-unified.css` (reglas `.auth-theme-tools` / `.auth-theme-switch`, sin markup tras esta tarea)
- Modify: `tests/browser/shell-sidebar-rollout.mjs` (assert de regresión, ver Step 9b)
- Modify: `tests/design-system/linen-removal.test.mjs`

**Contexto de deuda previa:** la review final del sub-goal Control Tower dejó registrada esta
misma tarea como deuda *shell-wide* (`.superpowers/sdd/progress.md`, sección `CT-Final`):
`theme.js` ejecuta `applyTheme(readTheme())` **después** del inline dark, así que un
`localStorage.aia-theme = "linen"` heredado devuelve la página a claro en **las 19 rutas del
shell**. Esta tarea la cierra, y el assert del Step 9b es el que aquella review pidió.

**Interfaces:**
- Consumes: CSS ya sin `linen` (Task 7).
- Produces: `window.AiaDesignSystem` conserva `getTheme()` y pierde `setTheme()`, `toggleTheme()` y `bindThemeSwitches()`. Cualquier consumidor de esas funciones debe haberse retirado en esta tarea.

- [ ] **Step 1: Localizar todos los consumidores de la API de tema**

```bash
grep -rn "setTheme\|toggleTheme\|bindThemeSwitches\|aia-theme-switch" --include="*.js" --include="*.php" views public/js src admin
```

Anotar la lista completa: cada aparición debe quedar resuelta al terminar la tarea. **Si aparece un consumidor fuera de los archivos listados arriba, añadirlo a la tarea antes de continuar.**

- [ ] **Step 2: Escribir el test que falla**

Añadir a `tests/design-system/linen-removal.test.mjs`:

```javascript
test('el runtime de tema no ofrece conmutacion', async () => {
  const source = await read('public/js/modules/aia_ui/theme.js');
  for (const symbol of ['toggleTheme', 'bindThemeSwitches', 'setTheme']) {
    assert.equal(source.includes(symbol), false, `theme.js aun expone ${symbol}`);
  }
});

test('ninguna vista incluye el conmutador de tema', async () => {
  const entries = await readdir(new URL('views', repositoryRoot), {
    withFileTypes: true, recursive: true,
  });
  const offenders = [];
  for (const entry of entries) {
    if (!entry.isFile() || !entry.name.endsWith('.php')) continue;
    const file = `${entry.parentPath ?? entry.path}/${entry.name}`;
    if (/aia-theme-switch|auth-theme-switch/.test(await readFile(file, 'utf8'))) {
      offenders.push(entry.name);
    }
  }
  assert.deepEqual(offenders, [], `vistas con conmutador: ${offenders.join(', ')}`);
});
```

- [ ] **Step 3: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/linen-removal.test.mjs
```

Expected: FALLA con `theme.js aun expone toggleTheme` y con la lista de vistas.

- [ ] **Step 4: Reducir `theme-bootstrap.js`**

Sustituir el contenido completo de `public/js/modules/aia_ui/theme-bootstrap.js` por:

```javascript
(() => {
  var root = document.documentElement;
  root.setAttribute("data-aia-theme", "dark");
  root.classList.add("aia-theme-dark");
})();
```

La clave `aia-theme` de `localStorage` queda obsoleta: no se lee ni se borra del navegador del usuario.

- [ ] **Step 5: Reducir `theme.js`**

Sustituir el contenido completo de `public/js/modules/aia_ui/theme.js` por:

```javascript
(() => {
  function applyTheme() {
    document.documentElement.setAttribute("data-aia-theme", "dark");
    document.documentElement.classList.add("aia-theme-dark");
    document.dispatchEvent(new CustomEvent("aia-theme-change", { detail: { theme: "dark" } }));
  }

  window.AiaDesignSystem = window.AiaDesignSystem || {};
  window.AiaDesignSystem.getTheme = () => "dark";
  document.dispatchEvent(new CustomEvent("aia-theme-ready"));

  applyTheme();

  if (window.matchMedia) {
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
  }
})();
```

`getTheme()` se conserva porque otros módulos lo consultan; devuelve siempre `"dark"`.

- [ ] **Step 6: Retirar la rama linen del laboratorio**

En `public/js/modules/aia_ui/design_system_lab.js`, eliminar toda lógica de conmutación de tema y sus referencias a `linen`, dejando el laboratorio siempre en dark.

- [ ] **Step 7: Borrar el conmutador y todos sus consumidores**

```bash
git rm views/auth/partials/auth-theme-switch.php
```

Y a continuación, en este orden:

1. `views/auth/login.view.php`, `views/auth/password-forgot.view.php`, `views/auth/password-reset.view.php`: retirar la línea que incluye el parcial.
2. `views/partials/shell_sidebar.php:119`: borrar el elemento `['label' => 'Cambiar tema', 'themeToggle' => true, 'icon' => 'theme'],` del array del menú de cuenta, **y** la rama del renderizador de ese partial que trata `themeToggle`, que queda muerta.
3. `views/core/project_selector.view.php:38`: borrar el elemento `['label' => 'Cambiar tema', 'icon' => 'theme', 'themeToggle' => true],`.
4. `views/design-system/families/shell-navigation.php:55`: borrar `['label' => 'Cambiar tema'],` de la familia documentada en el laboratorio.
5. `public/js/cargarDatosGeneralesPagina2.js`: borrar los dos bloques que construyen botones `.aia-theme-switch` (líneas 175-180 y 203-207 aproximadamente, dentro de las plantillas del menú de cuenta y del botón isla) y la función `bindThemeSwitchesWhenReady()` completa con su llamada (líneas 297-310 aproximadamente).
6. `public/css/login-brand-unified.css`: retirar las reglas de `.auth-theme-tools` y `.auth-theme-switch`, sin markup a partir de aquí.

Tras este paso, `grep -rn "aia-theme-switch\|themeToggle\|bindThemeSwitches" views public/js src` no debe devolver nada.

- [ ] **Step 8: Ejecutar los tests para verificar que pasan**

```bash
node --test tests/design-system/linen-removal.test.mjs
npm run check:frontend
```

Expected: tests PASS. Biome sin errores en `public/js` y `public/css`.

- [ ] **Step 9: Verificar en navegador**

En `1180x820` dark: `/login` sin el conmutador y con el formulario intacto; `/programa-general` con la sidebar operativa y su menú de cuenta sin el ítem «Cambiar tema»; `/proyectos` igual; `/internal/design-system` cargando el laboratorio. Consola sin errores de `AiaDesignSystem` indefinido.

- [ ] **Step 9b: Añadir el assert de regresión que pidió la review de Control Tower**

Un `localStorage.aia-theme = "linen"` heredado de antes de esta tarea no debe poder devolver ninguna ruta a claro. En `tests/browser/shell-sidebar-rollout.mjs`, añadir al recorrido de rutas una comprobación que siembre la clave obsoleta antes de navegar y verifique que el documento sigue en dark:

```javascript
await page.addInitScript(() => {
  try { window.localStorage.setItem('aia-theme', 'linen'); } catch { /* modo privado */ }
});
await page.goto(url, { waitUntil: 'domcontentloaded' });
const theme = await page.evaluate(() => document.documentElement.getAttribute('data-aia-theme'));
assert.equal(theme, 'dark', `${url} volvio a claro con aia-theme=linen en localStorage`);
```

Ejecutar el harness completo:

```bash
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Expected: PASS en ambos. El harness cubría 98 asserts sobre 19 rutas antes de esta tarea; el número sube con el assert nuevo por ruta.

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(design-system): retirar el conmutador de tema; dark es el unico tema"
```

---

### Task 9: Retirar `linen` — gates, pruebas y documentación

**Files:**
- Modify: `scripts/design-system-contracts.mjs`
- Modify: `scripts/design-system-runtime-budget.mjs`
- Modify: `scripts/design-system-runtime-budget-provenance.mjs`
- Modify: los archivos de `tests/browser/` y `tests/design-system/` que iteren sobre dos temas
- Modify: `DESIGN.md` (sección 6, viñeta de deuda consciente sobre `linen`)
- Modify: `docs/design-system/CHANGELOG.md` y `docs/design-system/decisions.md`

**Interfaces:**
- Consumes: CSS, runtime y markup ya sin `linen` (Tasks 7–8).
- Produces: `grep -rin "linen"` sólo devuelve historial. Es el criterio de cierre 4 del spec.

- [ ] **Step 1: Inventariar lo que queda**

```bash
grep -rln "linen" scripts tests DESIGN.md docs/design-system --include="*.mjs" --include="*.md" --include="*.json"
```

Los archivos que mencionaban `linen` en la medición del 2026-07-25, además de los tres gates, eran:

| Bajo `tests/browser/` | Bajo `tests/design-system/` |
|---|---|
| `listado-actividades-handsontable.mjs` | `contracts.test.mjs` |
| `project-selector-sidebar.spec.mjs` | `foundation.test.mjs` |
| `design-system-foundation-shell.mjs` | `shell-navigation.test.mjs` |
| `design-system-reflow.mjs` | `ui-groups-inventory.test.mjs` |
| `design-system-compliance.mjs` | `design-doc-wiring.test.mjs` |
| `programacion-semanal-dark-density.mjs` | `visual-ci-contract.test.mjs` |
| `programacion-semanal-subviews.mjs` | |
| `bi_control_tower.spec.mjs` | |
| `support/handsontableGoalMatrix.mjs` | |

Contrastar el `grep` con esta tabla: si aparece un archivo que no está aquí, añadirlo antes de continuar. Si falta alguno de la tabla, es que una tarea anterior ya lo resolvió.

**El `grep` no basta, y esto se descubrió ejecutando Task 6.** Dos suites derivan la expectativa
de dos temas de forma dinámica y **no contienen el literal `linen`**, así que ningún `grep` las
encuentra:

- `tests/design-system/accessibility.test.mjs`
- `tests/design-system/closeout-receipts.test.mjs` (vía `scripts/design-system-contracts.mjs`)

Además, Task 6 dejó en rojo por diseño estas cinco, que son precisamente las que esta tarea
cierra: `contracts.test.mjs`, `shell-navigation.test.mjs`, `ui-groups-inventory.test.mjs`, más las
dos de arriba.

Por eso el inventario de esta tarea se hace con **la suite completa**, no con el `grep`:

```bash
npm run test:design-system:static
```

El `grep` sirve para encontrar menciones textuales; la suite, para encontrar expectativas. Task 9
no cierra hasta que la suite esté verde salvo rojos ajenos documentados.

- [ ] **Step 2: Escribir el test que falla**

Añadir a `tests/design-system/linen-removal.test.mjs`:

```javascript
test('los gates no iteran sobre dos temas', async () => {
  for (const script of [
    'scripts/design-system-contracts.mjs',
    'scripts/design-system-runtime-budget.mjs',
    'scripts/design-system-runtime-budget-provenance.mjs',
  ]) {
    assert.equal(/linen/i.test(await read(script)), false, `${script} menciona linen`);
  }
});

test('el contrato de consumo no promete un tema retirado', async () => {
  const design = await read('DESIGN.md');
  assert.equal(/tema `linen`/.test(design), false, 'DESIGN.md sigue prometiendo linen');
});
```

- [ ] **Step 3: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/linen-removal.test.mjs
```

Expected: FALLA en los tres gates y en `DESIGN.md`.

- [ ] **Step 4: Simplificar los tres gates**

En cada uno, retirar `linen` de las listas de temas sobre las que iteran y dejar `['dark']`. Donde una aserción compare resultados entre temas, dejar la comprobación de dark únicamente.

- [ ] **Step 5: Actualizar los tests que iteran sobre dos temas**

Para cada archivo del inventario del paso 1 bajo `tests/`: retirar el escenario `linen`, dejar el de `dark`, y borrar los datos de fixture asociados a `linen`. **No borrar el test completo**: se elimina el escenario, no la cobertura.

**Deuda que Tasks 7 y 8 aparcaron explícitamente para aquí:**

- **7 archivos de Playwright + 3 helpers de Handsontable** que iteran `['dark','linen']`. Task 8 los dejó a propósito porque reconciliar esos bucles es diseño de test, no una sustitución mecánica. Hoy **lanzan `TypeError`**: llamaban a `setTheme`, que ya no existe. Y no están «a salvo» por no tener script de npm: `playwright.config.mjs:4-6` usa `testDir: './tests/browser'` con `testMatch: '*.mjs'`, así que un `npx playwright test` a secas los recoge todos.
- **Bucles vestigiales de un solo elemento** tras la retirada: `design-system-lab.mjs:429` y `programa-general-design-system.mjs:16` iteran `['dark']`; `design-system-lab.visual.mjs:53` conserva un `freezeTheme()` que ya no congela nada. Simplificar o retirar según cada caso.
- **`docs/design-system/ui-groups-inventory.json:306-311`**: el grupo `theme-switcher` tiene como única entrada de `sources` el parcial que Task 8 borró. Ningún gate valida existencia de `sources` (`scripts/design-system-contracts.mjs:222-231` y `ui-groups-inventory.test.mjs:13-19` comprueban `themes`, `catalogIds` y `styleApi`), así que no está en rojo — pero el grupo entero describe un control que ya no existe. Decidir si se retira el grupo o se reapunta, y dejarlo consistente con `component-catalog.json`.
- **Dos eventos que quedaron inertes:** `theme.js` sigue emitiendo `aia-theme-change`, pero sus únicos oyentes (`bi_chart_theme.js:113`, `bi-spa.js:2823`) se cargan **después** en `views/bi/_layout.php:97-99`, así que nunca lo reciben; y `aia-theme-ready` se quedó sin ningún oyente en el repositorio. Ambos están fijados por `tests/test_foundation_shell_contract.mjs:72-73`. Con un solo tema no hay cambio de tema que anunciar: retirar ambos y su aserción, salvo que encuentres un consumidor vivo — en cuyo caso documenta cuál.

- [ ] **Step 5b: Limpiar las referencias obsoletas que dejó Task 7**

Task 7 renombró el token `--aia-bg-linen` / `--ds-color-bg-linen` a `--aia-bg-parchment` / `--ds-color-bg-parchment` (mismo valor `oklch(96% 0.01 80)`), porque su nombre citaba un tema retirado. El rename es coherente dentro de `public/css`, pero dejó tres referencias al nombre viejo fuera de ahí:

- `docs/design-system/tokens.md:17` — documenta `--ds-color-bg-linen`.
- `DESIGN.md:47` — la clave `bg-linen` del frontmatter (el Step 6 ya la retira).
- `admin/public/css/tokens.css:66` — `--aia-bg-linen`. **No lo toques**: `admin/` mantiene su propio set de tokens hasta que F4 lo unifique (T4.2). Anótalo y sigue.

- [ ] **Step 6: Actualizar `DESIGN.md`**

En la sección **6. Do's and Don'ts**, sustituir la viñeta que hoy dice que `linen` se envía y ningún gate lo valida, por:

```markdown
- **Nunca** trabajes, generes cambios, pruebas o evidencia para **mobile o tablet**: fuera del alcance visual vigente. El tema `linen` fue **retirado del producto** en F0 del goal `dark-mode-todos-los-modulos`; dark es el único tema y no existe conmutador.
```

Actualizar también la sección 2, donde la paleta describe el «Lienzo Light» y la «Tinta Light» como alcance secundario enviado, y el bloque de frontmatter `colors:`, retirando las claves del tema claro (`bg-canvas`, `bg-page`, `text-primary`, `text-secondary`, `text-tertiary`, `bg-linen`).

- [ ] **Step 7: Registrar la decisión**

Añadir una entrada a `docs/design-system/CHANGELOG.md` y a `docs/design-system/decisions.md` con la fecha (2026-07-25), la decisión (retirada del tema `linen`), su motivo (un solo tema reduce a la mitad la superficie de tokens, gates y evidencia; coherente con el alcance desktop-dark de AGENTS.md) y el enlace a `goals/dark-mode-todos-los-modulos/facts.md`.

- [ ] **Step 8: Verificar el cierre completo**

```bash
node --test tests/design-system/linen-removal.test.mjs
grep -rin "linen" public admin views src scripts tests docs/design-system DESIGN.md
```

Expected: tests PASS. El `grep` sólo devuelve las entradas de `CHANGELOG.md` y `decisions.md` que documentan la retirada.

- [ ] **Step 9: Verificar la suite completa**

```bash
node scripts/design-system-audit.mjs
npm run test:design-system:static
npm run test:design-system:runtime
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: todos en verde.

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "chore(design-system): retirar linen de gates, pruebas y documentacion"
```

---

### Task 10: Poner `admin/` bajo el audit

`admin/` no está en `scanRoots`, así que es punto ciego. Medición del 2026-07-25: incluirlo
añade **272 hallazgos**, concentrados en `admin/public/css/admin-custom.css` (144),
`admin/public/css/tokens.css` (42), `admin/public/css/utilities.css` (30) y
`admin/views/pages/users/index.php` (23).

**El gate por regla ya existe** (`scripts/design-system-audit.mjs:337-341`): cualquier regla que
supere su valor en `audit-baseline.json` falla. Incluir `admin/` sube todos los contadores, así
que **exige un baseline nuevo, y el baseline está protegido por hash con aprobación humana
obligatoria**.

**Files:**
- Modify: `scripts/design-system-audit.mjs:19` (`scanRoots`)
- Modify: `docs/design-system/audit-baseline.json`
- Create: `docs/design-system/baseline-approvals/1.0.0-admin-scan.json`
- Modify: `docs/design-system/manifests/inventory.json`
- Modify: `docs/design-system/module-manifest.schema.json` (estado `observed-frozen`)

**Interfaces:**
- Consumes: audit en verde tras Task 9.
- Produces: `admin` en `scanRoots` y en `inventory.json` con estado `observed-frozen`. F4 baja este baseline.

- [ ] **Step 1: Medir el estado actual antes de tocar nada**

```bash
node scripts/design-system-audit.mjs > /tmp/audit-before.json
shasum -a 256 docs/design-system/audit-baseline.json
```

Anotar el total y el hash. El hash de partida al escribir este plan era `0789445c…ada2`; habrá cambiado si Tasks 2–9 modificaron el baseline (no deberían: sólo bajan contadores, y el gate no exige bajarlo).

- [ ] **Step 2: Añadir `admin` a `scanRoots`**

En `scripts/design-system-audit.mjs:19`, sustituir:

```javascript
const scanRoots = ['views', 'public/js', 'public/css', 'src/View/Components'];
```

por:

```javascript
const scanRoots = ['views', 'public/js', 'public/css', 'src/View/Components', 'admin'];
```

- [ ] **Step 3: Ejecutar el audit para ver el fallo esperado**

```bash
node scripts/design-system-audit.mjs
```

Expected: FALLA con varias líneas del tipo `<regla>: <n> > baseline <m>`, porque los 272 hallazgos de `admin/` superan cada contador.

- [ ] **Step 4: Generar el baseline nuevo**

```bash
node scripts/design-system-audit.mjs > /tmp/audit-admin.json 2>/dev/null
python3 - <<'PY'
import json
raw = open('/tmp/audit-admin.json').read()
report, _ = json.JSONDecoder().raw_decode(raw[raw.find('{'):])
baseline = json.load(open('docs/design-system/audit-baseline.json'))
baseline['totals'] = {rule: data['total'] for rule, data in report['summary'].items()}
baseline['generatedAt'] = report['generatedAt']
baseline['note'] = ('Baseline de deuda visual legacy, incluye admin/ desde F0 del goal '
                    'dark-mode-todos-los-modulos. Reducir por modulo migrado; no aumentarlo '
                    'sin justificacion.')
json.dump(baseline, open('docs/design-system/audit-baseline.json', 'w'), ensure_ascii=False, indent=2)
open('docs/design-system/audit-baseline.json', 'a').write('\n')
print('nuevo total por regla:', baseline['totals'])
PY
```

- [ ] **Step 5: DETENERSE — solicitar aprobación humana del baseline**

`AGENTS.md` prohíbe regenerar baselines para forzar verde, y el audit exige un archivo de
aprobación con `afterHash` coincidente. **No continuar sin respuesta del usuario.**

Presentarle:
- el total antes y después,
- el desglose por regla del delta,
- la lista de los cuatro archivos de `admin/` que concentran la deuda,
- la confirmación de que ninguna regla sube por causa distinta a la inclusión de `admin/`.

Preguntar explícitamente si aprueba el baseline nuevo.

- [ ] **Step 6: Registrar la aprobación**

Sólo tras el "sí" del usuario. Calcular el hash y crear el archivo:

```bash
shasum -a 256 docs/design-system/audit-baseline.json
```

Crear `docs/design-system/baseline-approvals/1.0.0-admin-scan.json`, sustituyendo `<HASH_ANTERIOR>` por el hash del paso 1 y `<HASH_NUEVO>` por el recién calculado:

```json
{
  "schemaVersion": 1,
  "designSystemVersion": "1.0.0",
  "baseline": "docs/design-system/audit-baseline.json",
  "beforeHash": "<HASH_ANTERIOR>",
  "afterHash": "<HASH_NUEVO>",
  "approvedBy": "user",
  "approvalRef": "goals/dark-mode-todos-los-modulos/plans/F0-fundacion-tema.plan.md Task 10",
  "recordedAt": "2026-07-25",
  "counts": {}
}
```

Rellenar `counts` con el desglose por regla del paso 4.

- [ ] **Step 7: Verificar que el audit pasa**

```bash
node scripts/design-system-audit.mjs
```

Expected: `Design system audit passed against baseline.` y `scannedRoots` incluyendo `admin`.

- [ ] **Step 8: Registrar `admin` en el inventario**

En `docs/design-system/manifests/inventory.json`, añadir al array `modules`:

```json
    {
      "moduleId": "admin",
      "status": "observed-frozen",
      "manifest": null
    }
```

Añadir `"observed-frozen"` al `enum` de `status` en `docs/design-system/module-manifest.schema.json`, con la semántica: *bajo observación del audit con baseline congelado; sin manifiesto ni presupuesto de ruta; el contador sólo puede bajar.*

- [ ] **Step 9: Verificar los contratos**

```bash
npm run test:design-system:static
node --test tests/design-system/contracts.test.mjs
```

Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(design-system): incluir admin/ en el audit con baseline congelado aprobado"
```

---

### Task 11: Gate monotónico del total

El audit ya impide que **cada regla** suba (líneas 337–341), lo que es estrictamente más
estricto que un tope sobre el total. Falta la comprobación del agregado, que es lo que el
usuario pidió (decisión 16) y lo que hace visible el progreso del goal completo.

**Files:**
- Modify: `scripts/design-system-audit.mjs` (añadir la comprobación del total)
- Modify: `docs/design-system/audit-baseline.json` (campo `totalViolations`)
- Create: `docs/design-system/baseline-approvals/1.0.0-total-gate.json`
- Create: `tests/design-system/audit-total-gate.test.mjs`

**Interfaces:**
- Consumes: `admin` ya en `scanRoots` y baseline aprobado (Task 10).
- Produces: `audit-baseline.json` gana el campo `totalViolations`. F1–F6 lo bajan; ninguna fase puede subirlo sin aprobación.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/design-system/audit-total-gate.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('el baseline declara un tope para el total de hallazgos', async () => {
  const baseline = JSON.parse(await read('docs/design-system/audit-baseline.json'));
  assert.equal(typeof baseline.totalViolations, 'number');
  assert.ok(baseline.totalViolations > 0);
});

test('el audit compara el total contra el baseline', async () => {
  const source = await read('scripts/design-system-audit.mjs');
  assert.match(source, /baseline\.totalViolations/);
  assert.match(source, /total de hallazgos/);
});
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
node --test tests/design-system/audit-total-gate.test.mjs
```

Expected: FALLA con `typeof baseline.totalViolations` siendo `undefined`.

- [ ] **Step 3: Añadir la comprobación al audit**

En `scripts/design-system-audit.mjs`, inmediatamente después del bucle que compara cada regla contra el baseline (el que termina en la línea 342), insertar:

```javascript
const allowedTotal = Number(baseline.totalViolations ?? Number.POSITIVE_INFINITY);
if (violations.length > allowedTotal) {
  failures.push(
    `total de hallazgos: ${violations.length} > baseline ${allowedTotal}. `
    + 'Bajar la deuda o registrar la excepcion en docs/design-system/exceptions.json.',
  );
}
```

Usar `Number.POSITIVE_INFINITY` como valor por defecto hace que el campo sea opcional: un baseline sin él no rompe, sólo no aporta el gate.

- [ ] **Step 4: Fijar el total en el baseline**

```bash
node scripts/design-system-audit.mjs > /tmp/audit-total.json 2>/dev/null
python3 - <<'PY'
import json
raw = open('/tmp/audit-total.json').read()
report, _ = json.JSONDecoder().raw_decode(raw[raw.find('{'):])
baseline = json.load(open('docs/design-system/audit-baseline.json'))
baseline['totalViolations'] = report['totalViolations']
json.dump(baseline, open('docs/design-system/audit-baseline.json', 'w'), ensure_ascii=False, indent=2)
open('docs/design-system/audit-baseline.json', 'a').write('\n')
print('total fijado en', baseline['totalViolations'])
PY
```

- [ ] **Step 5: Registrar la aprobación del baseline**

El baseline vuelve a cambiar de hash, así que necesita su propio archivo de aprobación. Este cambio **no relaja nada** —sólo añade un tope— así que no requiere una decisión visual, pero sí el registro:

```bash
shasum -a 256 docs/design-system/audit-baseline.json
```

Crear `docs/design-system/baseline-approvals/1.0.0-total-gate.json` con el mismo formato de Task 10, `beforeHash` el de Task 10, `afterHash` el recién calculado, y `approvalRef` apuntando a esta tarea.

- [ ] **Step 6: Verificar que el gate funciona en ambos sentidos**

```bash
node scripts/design-system-audit.mjs
node --test tests/design-system/audit-total-gate.test.mjs
```

Expected: audit `passed`, tests PASS.

Comprobar que el gate **detecta** una subida, bajando temporalmente el tope:

```bash
python3 -c "
import json
p='docs/design-system/audit-baseline.json'
d=json.load(open(p)); d['totalViolations'] -= 1
json.dump(d, open(p,'w'), ensure_ascii=False, indent=2); open(p,'a').write('\n')
"
node scripts/design-system-audit.mjs
```

Expected: FALLA con `total de hallazgos: <n> > baseline <n-1>`. Restaurar con `git checkout docs/design-system/audit-baseline.json` y volver a aplicar el paso 4.

- [ ] **Step 7: Documentar el override**

Añadir a `docs/design-system/exceptions.json`, en el array `notes`, una entrada que explique el procedimiento: para subir `totalViolations` hace falta justificación escrita, aprobación del usuario y un archivo nuevo en `baseline-approvals/`. Las fases del goal sólo lo bajan.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(design-system): gate monotonico sobre el total de hallazgos del audit"
```

---

## Cierre de F0

Al terminar las once tareas, verificar los seis criterios de cierre del spec:

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
npm run test:design-system:runtime
npm run check:frontend
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
docker compose exec app php tests/test_global_table_safety.php
```

Y las comprobaciones que no son un comando:

- [ ] `grep -rn "dark-mode" public/css public/js views src admin` sólo devuelve comentarios históricos.
- [ ] `grep -rin "linen" public admin views src scripts tests docs/design-system DESIGN.md` sólo devuelve `CHANGELOG.md` y `decisions.md`.
- [ ] Una superficie de cada grupo verificada en navegador a `1180x820` dark, sin caída a claro ni destello al cargar: `/login` (A), `/contratos` (B), `/bi/control-tower` (C), `/admin` (D).
- [ ] Evidencia archivada en `goals/dark-mode-todos-los-modulos/evidence/F0/`, incluida la captura del piloto `/programa-general` de Task 5.
- [ ] `goals/dark-mode-todos-los-modulos/validation-log.md` actualizada con el cierre de F0 y sin entradas abiertas.

**Nota sobre `/admin` en dark:** tras F0, `admin/` heredará el `:root` oscuro pero AdminLTE
seguirá pintando sus propios fondos claros, así que quedará **mezclado**. Es el estado esperado
—no un fallo de F0— y lo resuelve F4.
