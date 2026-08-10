import { test, expect } from '@playwright/test';
import { CREDENTIALS, PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const PRIORITY_GRID_ROUTES = [
  { path: '/programa-general', label: 'Programa General' },
  { path: '/programacion-intermedia', label: 'Programacion Intermedia' },
  { path: '/programacion-semanal', label: 'Programacion Semanal' },
];

// 2026-08-04: el usuario deprecó el PDC V1 completo — los módulos `pdc/`,
// `contratos/` y `listado-actividades/`. Esta lista los nombraba a los tres y
// se quedó sin sustento:
//   - `/contratos` y `/listado-actividades` respondían 404 y no existen en
//     `public/index.php`; sus selectores `#ct-mobile-card-list` y
//     `#la-mobile-card-list` no aparecen en ningún archivo del repo. El test no
//     llegaba a ejecutarlos —moría antes en `/pdc`— así que dos tercios de esta
//     cobertura llevaban tiempo siendo decorativos.
//   - `/pdc` sigue sirviéndose, pero vigilar un módulo deprecado no es donde
//     este contrato rinde.
// El sucesor vivo es `/plan-compras`, la SPA V2 (`pdc-app/`, React + AG Grid),
// que ya declara su manifiesto `plan-compras-v2.json` y consume el AGREGADOR
// (`views/plan-compras/app.view.php` enlaza `/css/aia-design-system.css`), de
// ahí que DESIGN_SYSTEM_ENTRYPOINTS deba seguir aceptando las dos vías.
//
// El contrato NO se aflojó al mudarse: lo que cambia es el runtime de grilla
// (AG Grid en vez de Handsontable/DataTables), así que las aserciones de
// entrypoint, tema, tokens, overflow y "la grilla llena su shell" se conservan
// una a una sobre los selectores de AG Grid, y se mantiene la que exige que no
// se cuele runtime legado en la V2.
const PRIORITY_OPERATIONAL_TABLE_ROUTES = [
  { path: '/plan-compras', label: 'Plan de Compras v2' },
];

// AGENTS.md fija el alcance visual en desktop >=1180px y dark unicamente.
// La prohibicion de 390x844 (mobile) que regia aqui se retiro el 2026-08-07:
// el viewport quedo permitido pero no exigido, sin evidencia todavia para
// ninguna familia (ver tests/design-system/mobile-viewport-scope.test.mjs).
// Esta lista se mantiene en los dos viewports requeridos porque son los
// unicos con cobertura real hoy: 1180x820 el canonico, 1440x900 el
// secundario.
const RESPONSIVE_VIEWPORTS = [
  { name: 'desktop', width: 1180, height: 820 },
  { name: 'desktop-wide', width: 1440, height: 900 },
];

// Fuente unica del detector de entrypoint, inyectada en page.evaluate().
//
// Por que dos nombres y no uno: desde la segmentacion del entrypoint conviven
// DOS vias legitimas de cargar el sistema de diseno, y las rutas que cubren
// readGridContract/readTableContract estan repartidas entre ambas.
//   - `DesignSystemHeadComponent::render()` emite el AGREGADOR
//     /css/aia-design-system.css. Lo usan hoy /programa-general y
//     /programacion-intermedia.
//   - `renderForModule()` emite el entrypoint SEGMENTADO
//     /css/design-system/entrypoints/core.css mas sus attach-*. Lo usan hoy
//     /programacion-semanal y /pdc (y otras 15 vistas).
// Ambos se sirven bajo /runtime/ para que el ?v= arrastre el mtime real de
// cada @import. La equivalencia entre las dos vias no es una promesa: la
// verifica `scripts/design-system-entrypoint-partition.mjs`, que exige
// igualdad exacta entre el agregador y core.css + adjuntos.
//
// Los tests de /login y /proyectos ya se habian corregido a core.css cuando
// esas vistas migraron; estos dos helpers no, y por eso daban rojo desde
// entonces. Aceptar cualquiera de los dos NO afloja la asercion: se acompana
// de resolvedCanvas, que corrobora el EFECTO. Un entrypoint renombrado, roto
// o servido con 404 deja de aplicar reglas y resolvedCanvas queda vacio, asi
// que el detector no puede quedarse ciego por un cambio de nombre de archivo.
const DESIGN_SYSTEM_ENTRYPOINTS = [
  '/css/aia-design-system.css',
  '/css/design-system/entrypoints/core.css',
];

async function waitForDesignSystemGrid(page) {
  await page.waitForSelector('#hot-container', { state: 'attached', timeout: 30000 });
  await page.waitForFunction(() => {
    const container = document.querySelector('#hot-container');
    const mobile = document.querySelector('#mobile-card-view');
    return Boolean(container && (container.querySelector('table.htCore') || mobile));
  }, null, { timeout: 30000 });
  await page.waitForTimeout(500);
}

async function readGridContract(page) {
  return page.evaluate((entrypoints) => {
    const html = document.documentElement;
    const container = document.querySelector('#hot-container');
    const table = document.querySelector('#hot-container .ht_master table.htCore') || document.querySelector('#hot-container table.htCore');
    const mobile = document.querySelector('#mobile-card-view');
    const hotStyle = container ? getComputedStyle(container) : null;
    const mobileStyle = mobile ? getComputedStyle(mobile) : null;
    const containerWidth = container ? Math.round(container.getBoundingClientRect().width) : 0;
    const tableWidth = table ? Math.round(table.getBoundingClientRect().width) : 0;

    return {
      hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
      // Ver DESIGN_SYSTEM_ENTRYPOINTS: agregador o entrypoint segmentado.
      hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && entrypoints.some((name) => sheet.href.includes(name)))),
      // Corrobora el efecto, no solo la presencia del archivo.
      resolvedCanvas: getComputedStyle(html).getPropertyValue('--ds-active-bg-canvas').trim(),
      // F0/Task 8 retiro la API interactiva de tema (setTheme); dark se aplica
      // sin conmutacion, asi que el contrato ahora lee el estado en vez de fijarlo.
      appliedTheme: html.getAttribute('data-aia-theme'),
      horizontalOverflow: html.scrollWidth - html.clientWidth,
      hotDisplay: hotStyle ? hotStyle.display : '',
      mobileDisplay: mobileStyle ? mobileStyle.display : '',
      hasTable: Boolean(table),
      containerWidth,
      tableWidth,
      tableFillsDesktop: tableWidth >= Math.max(1, containerWidth - 32),
      fontBody: getComputedStyle(html).getPropertyValue('--ds-font-body').trim(),
      minTarget: getComputedStyle(html).getPropertyValue('--ds-target-min').trim(),
    };
  }, DESIGN_SYSTEM_ENTRYPOINTS);
}

// La SPA V2 monta AG Grid: `.ag-root-wrapper` es la raiz de la grilla y
// `[role="grid"]` el viewport que pinta las celdas. Se espera al viewport, no
// solo al wrapper, para no medir una grilla a medio montar.
async function waitForDesignSystemTable(page) {
  await page.waitForFunction(() => {
    const grid = document.querySelector('.ag-root-wrapper');
    return Boolean(grid && grid.querySelector('[role="grid"]'));
  }, null, { timeout: 30000 });
  await page.waitForTimeout(500);
}

async function readTableContract(page) {
  return page.evaluate((entrypoints) => {
    const html = document.documentElement;
    const grid = document.querySelector('.ag-root-wrapper');
    const viewport = grid ? grid.querySelector('[role="grid"]') : null;
    // Runtime legado que NO debe colarse en la V2: Handsontable y DataTables
    // son de los modulos deprecados del PDC V1.
    const legacyRuntime = document.querySelector('.dataTables_wrapper, .dataTables_scrollBody, table.dataTable, table.htCore');
    // AG Grid envuelve la grilla en varios `.ag-styled-root` de ancho 0 que
    // solo cuelgan las variables de tema; el shell real es el primer ancestro
    // con ancho propio.
    let shell = grid ? grid.parentElement : null;
    while (shell && Math.round(shell.getBoundingClientRect().width) === 0) {
      shell = shell.parentElement;
    }
    const gridStyle = grid ? getComputedStyle(grid) : null;
    const shellWidth = shell ? Math.round(shell.getBoundingClientRect().width) : 0;
    const gridWidth = grid ? Math.round(grid.getBoundingClientRect().width) : 0;
    const viewportWidth = viewport ? Math.round(viewport.getBoundingClientRect().width) : 0;

    return {
      hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
      // Ver DESIGN_SYSTEM_ENTRYPOINTS: agregador o entrypoint segmentado.
      hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && entrypoints.some((name) => sheet.href.includes(name)))),
      // Corrobora el efecto, no solo la presencia del archivo.
      resolvedCanvas: getComputedStyle(html).getPropertyValue('--ds-active-bg-canvas').trim(),
      // F0/Task 8 retiro la API interactiva de tema (setTheme); dark se aplica
      // sin conmutacion, asi que el contrato ahora lee el estado en vez de fijarlo.
      appliedTheme: html.getAttribute('data-aia-theme'),
      horizontalOverflow: html.scrollWidth - html.clientWidth,
      gridDisplay: gridStyle ? gridStyle.display : '',
      gridWidth,
      shellWidth,
      viewportWidth,
      hasRenderedTable: Boolean(viewport),
      hasLegacyGridRuntime: Boolean(legacyRuntime),
      fillsDesktopShell: gridWidth >= Math.max(1, shellWidth - 32),
      fontBody: getComputedStyle(html).getPropertyValue('--ds-font-body').trim(),
      minTarget: getComputedStyle(html).getPropertyValue('--ds-target-min').trim(),
    };
  }, DESIGN_SYSTEM_ENTRYPOINTS);
}

test.describe('Design system foundation', () => {
  test('canonical assets are served', async ({ page }) => {
    for (const asset of [
      '/css/tokens.css?v=test',
      '/css/aia-design-system.css?v=test',
      '/public/js/modules/aia_ui/theme.js?v=test',
    ]) {
      const response = await page.request.get(asset);
      expect(response.ok(), `${asset} must be available`).toBe(true);
      expect((await response.text()).length, `${asset} must not be empty`).toBeGreaterThan(200);
    }
  });

  test('authenticated shell loads AIA design system in dark', async ({ page }) => {
    const project = PROJECTS[0];
    await loginAndSelectProject(page, project);
    await page.goto('/programa-general', { waitUntil: 'networkidle', timeout: 30000 });

    const state = await page.evaluate(() => ({
      hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
      hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/aia-design-system.css'))),
      initialTheme: document.documentElement.getAttribute('data-aia-theme'),
      minTarget: getComputedStyle(document.documentElement).getPropertyValue('--ds-target-min').trim(),
      fontBody: getComputedStyle(document.documentElement).getPropertyValue('--ds-font-body').trim(),
    }));

    expect(state.hasTokens).toBe(true);
    expect(state.hasDesignSystem).toBe(true);
    expect(state.initialTheme).toBe('dark');
    expect(state.minTarget).toBe('44px');
    expect(state.fontBody).toContain('Inter');
  });

  test('login follows migrated design system contract in dark', async ({ page }) => {
    for (const viewport of [
      { width: 1180, height: 820 },
      { width: 1440, height: 900 },
    ]) {
      await page.setViewportSize(viewport);
      await page.goto('/login', { waitUntil: 'networkidle', timeout: 30000 });

      const state = await page.evaluate(() => {
        const submit = document.querySelector('#loginForm button[type="submit"]');
        const userInput = document.querySelector('#usuario');
        const card = document.querySelector('.card-login');
        const body = document.body;
        const html = document.documentElement;
        const submitStyle = submit ? getComputedStyle(submit) : null;
        const inputStyle = userInput ? getComputedStyle(userInput) : null;

        return {
          hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
          // /login consume renderForModule('auth'), que emite el entrypoint
          // SEGMENTADO (entrypoints/core.css, servido por /runtime/). El
          // agregador aia-design-system.css es para superficies NO migradas:
          // asertarlo aqui daba falso rojo desde la segmentacion del entrypoint.
          hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/design-system/entrypoints/core.css'))),
          // Corrobora el efecto, no solo la presencia del archivo.
          resolvedCanvas: getComputedStyle(document.documentElement).getPropertyValue('--ds-active-bg-canvas').trim(),
          hasShellClass: body.classList.contains('aia-shell'),
          hasCard: Boolean(card),
          // F0/Task 8 retiro setTheme; dark se aplica sin conmutacion.
          appliedTheme: html.getAttribute('data-aia-theme'),
          horizontalOverflow: html.scrollWidth - html.clientWidth,
          submitMinHeight: submitStyle ? parseFloat(submitStyle.minHeight) : 0,
          inputMinHeight: inputStyle ? parseFloat(inputStyle.minHeight) : 0,
          submitRadius: submitStyle ? submitStyle.borderRadius : '',
          fontBody: getComputedStyle(html).getPropertyValue('--ds-font-body').trim(),
          fontDisplay: getComputedStyle(html).getPropertyValue('--ds-font-display').trim(),
        };
      });

      expect(state.hasTokens).toBe(true);
      expect(state.hasDesignSystem).toBe(true);
      expect(state.resolvedCanvas).not.toBe('');
      expect(state.hasShellClass).toBe(true);
      expect(state.hasCard).toBe(true);
      expect(state.appliedTheme).toBe('dark');
      expect(state.horizontalOverflow).toBeLessThanOrEqual(1);
      expect(state.submitMinHeight).toBeGreaterThanOrEqual(44);
      expect(state.inputMinHeight).toBeGreaterThanOrEqual(44);
      expect(state.submitRadius).not.toBe('0px');
      expect(state.fontBody).toContain('Inter');
      expect(state.fontDisplay).toContain('Montserrat');
    }
  });

  test('project selector follows migrated design system contract in dark', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'networkidle', timeout: 30000 });
    await page.locator('#usuario').fill(CREDENTIALS.username);
    await page.locator('#password').fill(CREDENTIALS.password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL('**/proyectos', { timeout: 45000 });

    for (const viewport of [
      { width: 1180, height: 820 },
      { width: 1440, height: 900 },
    ]) {
      await page.setViewportSize(viewport);
      await page.goto('/proyectos', { waitUntil: 'networkidle', timeout: 30000 });

      const state = await page.evaluate(() => {
        const html = document.documentElement;
        const firstCard = document.querySelector('.project-card');
        const firstButton = document.querySelector('.btn-enter');
        const search = document.querySelector('#projectSearch');
        const buttonStyle = firstButton ? getComputedStyle(firstButton) : null;
        const searchStyle = search ? getComputedStyle(search) : null;

        return {
          hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
          // /proyectos consume renderForModule('project-selector'): entrypoint
          // SEGMENTADO, no el agregador. Ver la nota del test de /login.
          hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/design-system/entrypoints/core.css'))),
          resolvedCanvas: getComputedStyle(html).getPropertyValue('--ds-active-bg-canvas').trim(),
          hasProjectCss: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/project-selector.css'))),
          hasShellClass: document.body.classList.contains('aia-shell'),
          hasProjectPageClass: document.body.classList.contains('project-selector-page'),
          hasCard: Boolean(firstCard),
          // F0/Task 8 retiro setTheme; dark se aplica sin conmutacion.
          appliedTheme: html.getAttribute('data-aia-theme'),
          horizontalOverflow: html.scrollWidth - html.clientWidth,
          buttonMinHeight: buttonStyle ? parseFloat(buttonStyle.minHeight) : 0,
          searchMinHeight: searchStyle ? parseFloat(searchStyle.minHeight) : 0,
          fontBody: getComputedStyle(html).getPropertyValue('--ds-font-body').trim(),
          fontDisplay: getComputedStyle(html).getPropertyValue('--ds-font-display').trim(),
        };
      });

      expect(state.hasTokens).toBe(true);
      expect(state.hasDesignSystem).toBe(true);
      expect(state.resolvedCanvas).not.toBe('');
      expect(state.hasProjectCss).toBe(true);
      expect(state.hasShellClass).toBe(true);
      expect(state.hasProjectPageClass).toBe(true);
      expect(state.hasCard).toBe(true);
      expect(state.appliedTheme).toBe('dark');
      expect(state.horizontalOverflow).toBeLessThanOrEqual(1);
      expect(state.buttonMinHeight).toBeGreaterThanOrEqual(44);
      expect(state.searchMinHeight).toBeGreaterThanOrEqual(44);
      expect(state.fontBody).toContain('Inter');
      expect(state.fontDisplay).toContain('Montserrat');
    }
  });

  test('priority planning grids keep design system contract in desktop', async ({ page }) => {
    await loginAndSelectProject(page, PROJECTS[0]);

    for (const viewport of RESPONSIVE_VIEWPORTS) {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });

      for (const route of PRIORITY_GRID_ROUTES) {
        await test.step(`${route.label} ${viewport.name}`, async () => {
          await page.goto(route.path, { waitUntil: 'domcontentloaded', timeout: 30000 });
          await waitForDesignSystemGrid(page);

          for (const theme of ['dark']) {
            const state = await readGridContract(page);

          expect(state.hasTokens, `${route.label} must load tokens`).toBe(true);
          expect(state.hasDesignSystem, `${route.label} must load AIA design system CSS`).toBe(true);
          expect(state.resolvedCanvas, `${route.label} design system CSS must actually apply`).not.toBe('');
          expect(state.appliedTheme, `${route.label} must apply ${theme} theme`).toBe(theme);
          expect(state.horizontalOverflow, `${route.label} must not create page overflow on ${viewport.name}`).toBeLessThanOrEqual(1);
          expect(state.hasTable, `${route.label} must render a Handsontable instance`).toBe(true);
          expect(state.fontBody, `${route.label} must use Inter body token`).toContain('Inter');
          expect(state.minTarget, `${route.label} must preserve 44px target token`).toBe('44px');

          // La rama mobile no se ejercita aqui: RESPONSIVE_VIEWPORTS solo cubre
          // los dos viewports requeridos. 390x844 quedo permitido pero no
          // exigido desde el 2026-08-07, sin evidencia todavia (ver
          // tests/design-system/mobile-viewport-scope.test.mjs). En desktop la
          // grilla siempre debe verse.
          expect(state.hotDisplay, `${route.label} ${viewport.name} grid must be visible`).not.toBe('none');
          expect(state.tableFillsDesktop, `${route.label} ${viewport.name} table must fill its grid shell`).toBe(true);
          }
        });
      }
    }
  });

  test('Plan de Compras v2 keeps design system table contract in desktop', async ({ page }) => {
    await loginAndSelectProject(page, PROJECTS[0]);

    for (const viewport of RESPONSIVE_VIEWPORTS) {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });

      for (const route of PRIORITY_OPERATIONAL_TABLE_ROUTES) {
        await test.step(`${route.label} ${viewport.name}`, async () => {
          await page.goto(route.path, { waitUntil: 'domcontentloaded', timeout: 30000 });
          await waitForDesignSystemTable(page);

          for (const theme of ['dark']) {
            const state = await readTableContract(page);

          expect(state.hasTokens, `${route.label} must load tokens`).toBe(true);
          expect(state.hasDesignSystem, `${route.label} must load AIA design system CSS`).toBe(true);
          expect(state.resolvedCanvas, `${route.label} design system CSS must actually apply`).not.toBe('');
          expect(state.appliedTheme, `${route.label} must apply ${theme} theme`).toBe(theme);
          expect(state.horizontalOverflow, `${route.label} must not create page overflow on ${viewport.name}`).toBeLessThanOrEqual(1);
          expect(state.fontBody, `${route.label} must use Inter body token`).toContain('Inter');
          expect(state.minTarget, `${route.label} must preserve 44px target token`).toBe('44px');

          // Las aserciones de tarjetas moviles no se ejercitan aqui: solo se
          // cubren los dos viewports requeridos. 390x844 quedo permitido pero
          // no exigido desde el 2026-08-07, sin evidencia todavia (ver
          // tests/design-system/mobile-viewport-scope.test.mjs).
          expect(state.gridDisplay, `${route.label} table shell must be visible on ${viewport.name}`).not.toBe('none');
          expect(state.gridWidth, `${route.label} table shell must have width on ${viewport.name}`).toBeGreaterThan(0);

          // La V2 pinta con AG Grid: ni Handsontable ni DataTables deben
          // aparecer aqui. Es la heredera de la vieja asercion
          // `must not render DataTables runtime`, ampliada al runtime completo
          // del PDC V1 ahora que ese modulo esta deprecado.
          expect(state.hasLegacyGridRuntime, `${route.label} must not render legacy grid runtime`).toBe(false);
          expect(state.viewportWidth, `${route.label} grid viewport must be visible on ${viewport.name}`).toBeGreaterThan(0);

          expect(state.hasRenderedTable, `${route.label} ${viewport.name} table must render`).toBe(true);
          expect(state.fillsDesktopShell, `${route.label} ${viewport.name} table must fill its shell`).toBe(true);
          }
        });
      }
    }
  });
});
