# Colapsado del sidebar como primitiva canónica — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mover el pulido del rail colapsado del scope del shell (`body.aia-shell--sidebar` en shell-sidebar.css) al componente canónico (`navigation.css`), y adoptarlo en el laboratorio del design system con un fixture fiel al shell real de Programación Intermedia.

**Architecture:** Los selectores de primitiva pierden el prefijo `body.aia-shell--sidebar` y viven en `navigation.css` (`.aia-navigation--sidebar[data-sidebar-state="collapsed"] …`), que el lab, el shell y el project-selector cargan. Los flyouts de gestión de semanas y el push-layout del body se quedan en shell-sidebar.css. El fixture del lab se actualiza a 13 ítems colapsados; los goldens se regeneran con aprobación humana.

**Tech Stack:** CSS `@layer components` (navigation.css) / `@layer legacy-overrides` + `components` (shell-sidebar.css); componente PHP `DesignSystemComponent::navigation`; fixture PHP del lab; Playwright para probes y goldens; contratos PHP/node.

## Global Constraints

- Desktop ≥1180px, dark (linen y 390 se regeneran por coherencia de árbol, no se rediseñan). Viewport canónico 1180×820.
- Ruta del repo con espacio: citar `cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lab-colapsado"` siempre.
- PHP en el contenedor del worktree: `docker exec lps-aia-lab php …`. App servida en `http://localhost:8085`.
- Probes Playwright con `E2E_BASE_URL=http://localhost:8085`.
- Tokens `--ds-*`/`--aia-*`, sin hex nuevos. Paddings que venzan el reset global `* {padding:0}` de styles.css llevan `!important` (patrón de navigation.css:138).
- El componente `navigation.css` está todo en `@layer components`; las primitivas promovidas van ahí (no en legacy-overrides).
- **NO regenerar goldens sin aprobación humana explícita** (AGENTS.md). Rama actual `worktree-lab-colapsado`; commitear ahí.
- Al declarar "hecho": salida real de comandos de esta sesión.

## Setup del entorno (una vez, antes de Task 1)

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lab-colapsado"
ln -sf "/Volumes/Crucial X6/Developer/lps-aia/node_modules" node_modules
cp "/Volumes/Crucial X6/Developer/lps-aia/.env" .env
sed -i '' 's|^APP_URL=.*|APP_URL=http://localhost:8085|' .env
envval() { grep "^$1=" .env | cut -d= -f2- | tr -d '"'; }
docker rm -f lps-aia-lab 2>/dev/null
docker run -d --name lps-aia-lab --network last-planner-aia_default -p 8085:80 \
  -e "DB_HOST=$(envval DB_HOST)" -e "DB_NAME=$(envval DB_NAME)" -e "DB_USER=$(envval DB_USER)" \
  -e "DB_PASS=$(envval DB_PASS)" -e USE_GLOBAL_TABLES=true \
  -v "$PWD:/var/www/html" -v "/Volumes/Crucial X6/Developer/lps-aia/vendor:/var/www/html/vendor:ro" \
  last-planner-aia-app
sleep 3 && curl -s -o /dev/null -w "8085: %{http_code}\n" http://localhost:8085/login   # espera 200
```

El lab (`/internal/design-system`) NO exige login. `programacion-intermedia` sí; credenciales E2E `test.A`/`aia2026`, proyecto «Da Porto».

---

### Task 1: Promover las primitivas del colapsado a navigation.css

**Files:**
- Modify: `public/css/design-system/components/navigation.css` (añadir bloque al final del último `@layer components`)
- Modify: `public/css/design-system/adapters/shell-sidebar.css` (eliminar los bloques promovidos; partir el bloque mixto de reduced-motion)
- Test: `tests/browser/__probe-promocion.mjs` (temporal; se borra al final del task)

**Interfaces:**
- Produces: en `navigation.css`, todos los selectores `.aia-navigation--sidebar[data-sidebar-state="collapsed"] …` que antes vivían en shell-sidebar.css (sin-scroll, paddings, separador, iconos 20px, colores, píldora de label + caret + reveal + account panel). En `shell-sidebar.css` quedan solo: push-layout del body, context-bar, `.shell-has-week-menu`, `.shell-week-flyout*`.

- [ ] **Step 1: Probe de línea base del shell (captura los valores ACTUALES de PI colapsado)**

Crear `tests/browser/__probe-promocion.mjs`:

```js
import { chromium } from 'playwright';
import { BASE_URL, CREDENTIALS } from './fixtures/projects.mjs';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });
await page.goto(`${BASE_URL}/login`);
await page.locator('#usuario').fill(CREDENTIALS.username);
await page.locator('#password').fill(CREDENTIALS.password);
await Promise.all([ page.waitForURL((u) => u.pathname === '/proyectos', { timeout: 45000 }), page.locator('button[type="submit"]').click() ]);
await page.locator('.project-item').first().waitFor({ timeout: 45000 });
await page.locator('.project-item button[type="submit"], .project-item .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });
await page.goto(`${BASE_URL}/programacion-intermedia`);
await page.waitForSelector('[data-shell-pattern="sidebar"]', { timeout: 20000 });
await page.evaluate(() => { const s = document.querySelector('[data-shell-pattern="sidebar"]'); if (s.dataset.sidebarState !== 'collapsed') document.querySelector('[data-sidebar-toggle]').click(); });
await page.waitForTimeout(400);
const m = await page.evaluate(() => {
  const shell = document.querySelector('[data-shell-pattern="sidebar"]');
  const nav = shell.querySelector('.aia-sidebar__nav');
  const link = shell.querySelector('.aia-sidebar__link');
  const glyph = shell.querySelector('.aia-sidebar__nav .aia-icon__glyph');
  const active = shell.querySelector('[aria-current="page"]');
  return {
    navOverflowY: getComputedStyle(nav).overflowY,
    navNoScroll: nav.scrollHeight <= nav.clientHeight + 1,
    glyphW: getComputedStyle(glyph).width,
    iconColor: getComputedStyle(link.querySelector('.aia-icon')).color,
    activeShadow: getComputedStyle(active).boxShadow !== 'none',
    labelPos: getComputedStyle(link.querySelector('.aia-sidebar__label')).position,
  };
});
console.log(JSON.stringify(m));
const ok = m.navOverflowY === 'visible' && m.navNoScroll && m.glyphW === '20px' && m.activeShadow && m.labelPos === 'absolute';
console.log(ok ? 'BASELINE OK' : 'BASELINE FAIL');
await browser.close();
process.exit(ok ? 0 : 1);
```

Run: `E2E_BASE_URL=http://localhost:8085 node tests/browser/__probe-promocion.mjs`
Expected: `BASELINE OK` (glyphW=20px, navOverflowY=visible, sin scroll, label absolute). Anota el JSON — es el objetivo tras la promoción.

- [ ] **Step 2: Añadir las primitivas a `navigation.css`**

Al final del archivo, ANTES del último `}` que cierra el `@layer components` final (localízalo con `tail -30 public/css/design-system/components/navigation.css`), pegar el bloque de primitivas. Es EXACTAMENTE el contenido de shell-sidebar.css líneas 241-424 (rango del `@layer components`, EXCLUYENDO los `.shell-*` y `.context-*`), con `body.aia-shell--sidebar ` eliminado de cada selector. Reglas de extracción:

- **INCLUIR** (promover): overflow del aside (241-243); nav overflow/padding/gap (245-249); header/footer padding (251-254); group ul gap (257-259); context/h3 display:none (265-268); separador hairline (271-276); iconos 20px (279-282); color de icono reposo/hover/activo (284-296); anillo activo (298-300); position relative de triggers (305-309); píldora de label (311-341); caret ::after (345-365); reveal label (367-377); reveal caret (379-388); supresión de píldora con account abierto (394-402); account panel positioning (411-424).
- **EXCLUIR** (dejar en shell-sidebar.css): `.shell-has-week-menu` supresión de píldora (426-431); todo `.shell-week-flyout*` (432-580).
- Los `!important` existentes en paddings (247, 253) se conservan. El padding de la píldora (línea 320, `padding: var(--ds-space-3) var(--ds-space-6)`) SÍ necesita `!important` al vivir en navigation.css (styles.css lo colapsaría): cámbialo a `padding: var(--ds-space-3) var(--ds-space-6) !important;`.

Encabeza el bloque con un comentario:
```css
  /* ===== Rail colapsado: primitivas canónicas =====
     Pulido del estado colapsado (sin-scroll, píldora de label instantánea,
     separadores, iconos 20px, jerarquía de color). Promovido desde el adapter
     del shell (shell-sidebar.css) para que el laboratorio lo versione y todos
     los consumidores (.aia-navigation--sidebar) lo hereden. Los flyouts de
     gestión de semanas siguen siendo del shell (shell-sidebar.css). */
```

- [ ] **Step 3: Partir el bloque mixto de reduced-motion**

En shell-sidebar.css, el `@media (prefers-reduced-motion: reduce)` (aprox. líneas 581-590) mezcla selectores de primitiva (`.aia-sidebar__link .aia-icon`, `.aia-sidebar__label`, `.aia-sidebar__toggle-label`) con `.shell-week-flyout`. Léelo (`sed -n '580,595p'`). En `navigation.css`, dentro del bloque de primitivas recién añadido, agrega la parte de primitiva:

```css
  @media (prefers-reduced-motion: reduce) {
    .aia-navigation--sidebar[data-sidebar-state="collapsed"] .aia-sidebar__link .aia-icon,
    .aia-navigation--sidebar[data-sidebar-state="collapsed"] .aia-sidebar__link .aia-sidebar__label,
    .aia-navigation--sidebar[data-sidebar-state="collapsed"] .aia-sidebar__utility .aia-sidebar__label,
    .aia-navigation--sidebar[data-sidebar-state="collapsed"] .aia-sidebar__toggle .aia-sidebar__toggle-label {
      transition: none;
    }
  }
```

En shell-sidebar.css, ese `@media` conserva SOLO la línea de `.shell-week-flyout` (y su `transition: none`). Si tras quitar las líneas de primitiva el `@media` quedara con un único selector `.shell-week-flyout`, déjalo así.

- [ ] **Step 4: Eliminar de shell-sidebar.css los bloques promovidos**

Borrar de shell-sidebar.css las reglas de los rangos 241-424 que se movieron (todo lo marcado INCLUIR en Step 2). Conservar intactos: `@layer legacy-overrides` completo (push-layout body 12-33, context-bar 37-134, y los paddings de `.shell-week-flyout*` 148-179; el bloque 141-145 de padding de la píldora en legacy-overrides SE VA porque la píldora ahora vive en navigation.css con su padding !important); `.shell-has-week-menu` (426-431); `.shell-week-flyout*` (432-580); la parte shell del `@media` reduced-motion.

Verificación de que no quedó ninguna primitiva huérfana:
Run: `grep -c 'aia-navigation--sidebar\[data-sidebar-state="collapsed"\] .aia-sidebar__label\|aia-icon__glyph\|__group + .aia-sidebar__group::before' public/css/design-system/adapters/shell-sidebar.css`
Expected: `0`

- [ ] **Step 5: Gates de sintaxis y no-regresión del shell**

```bash
npx biome check public/css/design-system/components/navigation.css public/css/design-system/adapters/shell-sidebar.css   # sin rojos nuevos vs baseline (hay 1 preexistente de formato en shell-sidebar.css)
E2E_BASE_URL=http://localhost:8085 node tests/browser/__probe-promocion.mjs   # BASELINE OK (idéntico al Step 1)
E2E_BASE_URL=http://localhost:8085 node tests/browser/shell-week-admin.mjs    # 11/11 (flyouts de gestión intactos)
```
Si el probe no da `BASELINE OK` con los MISMOS valores del Step 1, la promoción rompió algo — revisar qué selector quedó sin promover o mal transformado. No continuar hasta paridad.

- [ ] **Step 6: Borrar el probe temporal y commit**

```bash
rm tests/browser/__probe-promocion.mjs
git add public/css/design-system/components/navigation.css public/css/design-system/adapters/shell-sidebar.css
git commit -m "refactor(design-system): colapsado del sidebar como primitiva canónica en navigation.css

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Fixture del laboratorio a 13 módulos colapsado + asserts de conteo

**Files:**
- Modify: `views/design-system/families/shell-navigation.php`
- Modify: `tests/browser/design-system-lab-sidebar.mjs`

**Interfaces:**
- Consumes: iconos del componente (`gauge`, `change`, `sync`, `company`, `unlock`, `week-commit`, `hierarchy`, `contract`) ya existentes; las primitivas de Task 1.
- Produces: fixture con 13 ítems (Información 6, Obra 4, Compras 3), `initialState => 'collapsed'`.

- [ ] **Step 1: Actualizar `views/design-system/families/shell-navigation.php`**

Reemplazar el array de `navigation([...])` para que espeje el shell real (grupos y iconos de `views/partials/shell_sidebar.php`):
- `'initialState' => 'expanded'` → `'collapsed'`.
- Grupo **information** (6 ítems, en orden): control-tower `chart`; project-weeks "Semanas del Proyecto" `calendar` (quitar `'state' => 'disabled'`, dejar `href => '#project-weeks'`); professionals `user`; subcontractors `company` (era `contract`); **indicators** "Indicadores LPS" `gauge` `href="/indicadores"`; **change-control** "Control de Cambios" `change` `href="/control-cambios"`.
- Grupo **obra** (4 ítems): programa-general `program`; programacion-intermedia `unlock` (era `tasks`); programacion-semanal `week-commit` (era `calendar`); **update-schedule** "Actualizar Cronograma" `sync` `href="/programa-general-actualizar"`.
- Grupo **compras** (3 ítems): activity-families `hierarchy` (era `list`); contracts `contract` (sin cambio); pdc `clipboard`.
- `active` sigue `'programa-general'`. `utilities` sin cambios.

Guardas del contrato (no romper): todos los labels de `shell-navigation.test.mjs` presentes; ningún label "Integración"; 3 grupos Información/Obra/Compras.

- [ ] **Step 2: Verificar el contrato de la familia (no debe romperse)**

Run: `docker exec lps-aia-lab php -l views/design-system/families/shell-navigation.php`
Run: `node --test tests/design-system/shell-navigation.test.mjs` → `pass 2, fail 0`.
Run: `docker exec lps-aia-lab php tests/test_design_system_components.php` → `PASS`.

- [ ] **Step 3: Ajustar asserts de conteo en `design-system-lab-sidebar.mjs`**

Leer el test (`grep -n 'information\|obra\|compras\|querySelectorAll\|data-sidebar-state\|expanded\|collapsed' tests/browser/design-system-lab-sidebar.mjs`). Actualizar:
- Información: de 4 a **6** ítems (añade Indicadores LPS y Control de Cambios); sigue conteniendo Control Tower/Profesionales/Subcontratistas y NO "Integración".
- Obra: de 3 a **4** (añade Actualizar Cronograma).
- Compras: sigue **3**.
- Si algún assert asume `data-sidebar-state="expanded"` de arranque, cambiarlo a `collapsed` (el toggle sigue alternando; el test de geometría de colapso/expansión se mantiene, solo invierte el estado inicial).

- [ ] **Step 4: Ejecutar el test del lab sidebar**

Run: `E2E_BASE_URL=http://localhost:8085 node tests/browser/design-system-lab-sidebar.mjs`
Expected: todos los checks OK (conteos nuevos, geometría de colapso por token, estados, targets 44px).

- [ ] **Step 5: Commit**

```bash
git add views/design-system/families/shell-navigation.php tests/browser/design-system-lab-sidebar.mjs
git commit -m "feat(design-system): fixture del lab a 13 módulos colapsado con iconos remapeados

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Regenerar goldens con aprobación humana + sellar manifiestos

**Files:**
- Regenerate: `tests/browser/__screenshots__/design-system-lab.visual.mjs/shell-navigation-*.png` (6)
- Regenerate: `tests/browser/__screenshots__/project-selector/project-selector-dark-*.png` (2)
- Modify: `docs/design-system/manifests/laboratory.json` (sha256 de los 2 escenarios shell-navigation)
- Modify (si declara sha del project-selector): `docs/design-system/manifests/project-selector.json`
- Modify: `docs/design-system/decisions.md`

**Interfaces:**
- Consumes: fixture de Task 2, primitivas de Task 1.

- [ ] **Step 1: Confirmar qué goldens cambian (dry-run sin reescribir)**

Run: `npx playwright test tests/browser/design-system-lab.visual.mjs --grep shell-navigation --workers=1` (contra el server del worktree; si el runner usa baseURL propio, exportar `E2E_BASE_URL=http://localhost:8085`).
Expected: FALLA en los escenarios shell-navigation (diff visual: el rail ahora colapsado). Igual para `npx playwright test tests/browser/project-selector*.spec.mjs` si compara golden — anota cuáles fallan.

- [ ] **Step 2: GATE DE APROBACIÓN HUMANA — generar capturas y mostrarlas**

Regenerar en un directorio temporal para revisión (NO sobre los goldens todavía). Método simple: script Playwright que navega a `/internal/design-system`, colapsa el candidato shell-navigation, y guarda PNG a `/tmp/.../lab-colapsado-preview/` en dark 1180×820 y 1440×900; ídem project-selector colapsado. El controlador del SDD abre esas PNG (o el navegador integrado) y **pide aprobación explícita del usuario** antes del Step 3.

Este paso NO se auto-aprueba. Si el usuario pide ajustes visuales, volver a Task 1/2.

- [ ] **Step 3: Regenerar los goldens declarados (solo tras aprobación)**

Run: `npx playwright test tests/browser/design-system-lab.visual.mjs --grep shell-navigation --update-snapshots=all --workers=1`
Run (si aplica): `npx playwright test tests/browser/project-selector-sidebar.spec.mjs --update-snapshots=all --workers=1`
(El default `changed` NO reescribe si el diff cae dentro de tolerancia — usar `all`, ver pitfall del proyecto.)

- [ ] **Step 4: Recalcular y sellar los sha256 de los manifiestos**

Los sha256 de `laboratory.json` (escenarios `shell-navigation-dark-1180x820`, `-1440x900`) se recalculan del PNG nuevo. Método: `shasum -a 256 <png>` y pegar en el manifiesto. Ídem project-selector si su manifiesto los declara.
Run: `node scripts/design-system-contracts.mjs` (con árbol limpio: `git stash -u` → correr → `git stash pop`) → `Design system contracts: PASS`.

- [ ] **Step 5: Registrar la decisión**

En `docs/design-system/decisions.md`, añadir fila:
```
| DS-029 | Colapsado canónico | El estado colapsado pulido del sidebar (sin-scroll, píldora de label, separadores, iconos 20px) es primitiva de navigation.css versionada por el laboratorio; el lab captura el rail colapsado por defecto y todos los consumidores lo heredan | approved |
```

- [ ] **Step 6: Commit**

```bash
git add tests/browser/__screenshots__/design-system-lab.visual.mjs/shell-navigation-*.png \
        tests/browser/__screenshots__/project-selector/*.png \
        docs/design-system/manifests/laboratory.json docs/design-system/manifests/project-selector.json \
        docs/design-system/decisions.md
git commit -m "test(design-system): goldens del lab y project-selector con el colapsado canónico (aprobado)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Verificación integral y evidencia

**Files:** ninguno nuevo (ajustes menores si algo falla)

- [ ] **Step 1: Gates completos**

```bash
node tests/test_foundation_shell_contract.mjs && echo "shell-contract exit $?"
node --test tests/design-system/shell-navigation.test.mjs tests/design-system/contracts.test.mjs   # pass, 0 fail
docker exec lps-aia-lab php tests/test_design_system_components.php   # PASS
E2E_BASE_URL=http://localhost:8085 node tests/browser/shell-week-admin.mjs   # 11/11
E2E_BASE_URL=http://localhost:8085 node tests/browser/design-system-lab-sidebar.mjs   # OK
git stash -u >/dev/null 2>&1; node scripts/design-system-contracts.mjs; git stash pop >/dev/null 2>&1   # PASS
```

- [ ] **Step 2: Evidencia visual de paridad (navegador integrado)**

`/internal/design-system` (lab, candidato shell-navigation colapsado) + `/programacion-intermedia` colapsado + project-selector colapsado, a 1180×820 dark. Screenshots para el usuario confirmando que el lab muestra el mismo rail pulido que PI. Consola sin errores nuevos.

- [ ] **Step 3: Reporte al usuario**

Qué se verificó (comandos + resultados), goldens regenerados y aprobados, y opciones de cierre de rama (merge/push a main como en las features anteriores). Rama actual del worktree.
