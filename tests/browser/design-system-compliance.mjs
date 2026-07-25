import { test, expect } from '@playwright/test';
import { CREDENTIALS, PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const PRIORITY_GRID_ROUTES = [
  { path: '/programa-general', label: 'Programa General' },
  { path: '/programacion-intermedia', label: 'Programacion Intermedia' },
  { path: '/programacion-semanal', label: 'Programacion Semanal' },
];

const PRIORITY_OPERATIONAL_TABLE_ROUTES = [
  { path: '/pdc', label: 'PDC', type: 'handsontable', mobileCards: '#pdc-mobile-card-view' },
  { path: '/contratos', label: 'Contratos', type: 'handsontable', mobileCards: '#ct-mobile-card-list' },
  { path: '/listado-actividades', label: 'Listado de Actividades', type: 'handsontable', mobileCards: '#la-mobile-card-list' },
];

const RESPONSIVE_VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet-horizontal', width: 1180, height: 820 },
  { name: 'desktop', width: 1440, height: 900 },
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
  return page.evaluate(() => {
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
      hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/aia-design-system.css'))),
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
  });
}

async function waitForDesignSystemTable(page) {
  await page.waitForFunction(() => {
    const hot = document.querySelector('#hot-container');
    const pdc = document.querySelector('#dt_cliente');
    return Boolean(
      hot?.querySelector('table.htCore') ||
      pdc?.querySelector('table.htCore'),
    );
  }, null, { timeout: 30000 });
  await page.waitForTimeout(500);
}

async function readTableContract(page) {
  return page.evaluate(() => {
    const html = document.documentElement;
    const hot = document.querySelector('#hot-container');
    const hotTable = document.querySelector('#hot-container .ht_master table.htCore')
      || document.querySelector('#hot-container table.htCore')
      || document.querySelector('#dt_cliente .ht_master table.htCore');
    const pdcGrid = document.querySelector('#dt_cliente');
    const dataTableRuntime = document.querySelector('.dataTables_wrapper, .dataTables_scrollBody, table.dataTable');
    const mobileCards = document.querySelector('#ct-mobile-card-list, #la-mobile-card-list, #pdc-mobile-card-view');
    const grid = hot || pdcGrid;
    const table = hotTable;
    const gridStyle = grid ? getComputedStyle(grid) : null;
    const mobileCardsStyle = mobileCards ? getComputedStyle(mobileCards) : null;
    const gridWidth = grid ? Math.round(grid.getBoundingClientRect().width) : 0;
    const tableWidth = table ? Math.round(table.getBoundingClientRect().width) : 0;
    const mobileCardsWidth = mobileCards ? Math.round(mobileCards.getBoundingClientRect().width) : 0;

    return {
      hasTokens: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/tokens.css'))),
      hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/aia-design-system.css'))),
      // F0/Task 8 retiro la API interactiva de tema (setTheme); dark se aplica
      // sin conmutacion, asi que el contrato ahora lee el estado en vez de fijarlo.
      appliedTheme: html.getAttribute('data-aia-theme'),
      horizontalOverflow: html.scrollWidth - html.clientWidth,
      gridDisplay: gridStyle ? gridStyle.display : '',
      gridWidth,
      mobileCardsDisplay: mobileCardsStyle ? mobileCardsStyle.display : '',
      mobileCardsWidth,
      mobileCardCount: mobileCards ? mobileCards.children.length : 0,
      tableWidth,
      hasHotTable: Boolean(hotTable),
      hasDataTableRuntime: Boolean(dataTableRuntime),
      hasRenderedTable: Boolean(table),
      fillsDesktopShell: tableWidth >= Math.max(1, gridWidth - 32),
      fontBody: getComputedStyle(html).getPropertyValue('--ds-font-body').trim(),
      minTarget: getComputedStyle(html).getPropertyValue('--ds-target-min').trim(),
    };
  });
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
      { width: 390, height: 844 },
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
          hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/aia-design-system.css'))),
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
      { width: 390, height: 844 },
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
          hasDesignSystem: Boolean([...document.styleSheets].some((sheet) => sheet.href && sheet.href.includes('/css/aia-design-system.css'))),
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

  test('priority planning grids keep design system contract in desktop and mobile', async ({ page }) => {
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
          expect(state.appliedTheme, `${route.label} must apply ${theme} theme`).toBe(theme);
          expect(state.horizontalOverflow, `${route.label} must not create page overflow on ${viewport.name}`).toBeLessThanOrEqual(1);
          expect(state.hasTable, `${route.label} must render a Handsontable instance`).toBe(true);
          expect(state.fontBody, `${route.label} must use Inter body token`).toContain('Inter');
          expect(state.minTarget, `${route.label} must preserve 44px target token`).toBe('44px');

          if (viewport.name !== 'mobile') {
            expect(state.hotDisplay, `${route.label} ${viewport.name} grid must be visible`).not.toBe('none');
            expect(state.tableFillsDesktop, `${route.label} ${viewport.name} table must fill its grid shell`).toBe(true);
          } else {
            expect(
              state.hotDisplay !== 'none' || state.mobileDisplay !== 'none',
              `${route.label} mobile must expose either grid or mobile fallback`,
            ).toBe(true);
          }
          }
        });
      }
    }
  });

  test('PDC, Contratos and Listado keep design system table contract in desktop and mobile', async ({ page }) => {
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
          expect(state.appliedTheme, `${route.label} must apply ${theme} theme`).toBe(theme);
          expect(state.horizontalOverflow, `${route.label} must not create page overflow on ${viewport.name}`).toBeLessThanOrEqual(1);
          expect(state.fontBody, `${route.label} must use Inter body token`).toContain('Inter');
          expect(state.minTarget, `${route.label} must preserve 44px target token`).toBe('44px');

          if (viewport.name === 'mobile' && route.mobileCards) {
            expect(state.mobileCardsDisplay, `${route.label} cards must be visible on mobile`).not.toBe('none');
            expect(state.mobileCardsWidth, `${route.label} cards must have width on mobile`).toBeGreaterThan(0);
            expect(state.mobileCardCount, `${route.label} must render mobile cards or an empty state`).toBeGreaterThan(0);
          } else {
            expect(state.gridDisplay, `${route.label} table shell must be visible on ${viewport.name}`).not.toBe('none');
            expect(state.gridWidth, `${route.label} table shell must have width on ${viewport.name}`).toBeGreaterThan(0);
          }

          if (route.type === 'handsontable') {
            expect(state.hasHotTable, `${route.label} must render Handsontable`).toBe(true);
            expect(state.hasDataTableRuntime, `${route.label} must not render DataTables runtime`).toBe(false);
            if (viewport.name !== 'mobile') {
              expect(state.tableWidth, `${route.label} Handsontable must be visible on ${viewport.name}`).toBeGreaterThan(0);
            }
          }

          if (viewport.name !== 'mobile') {
            expect(state.hasRenderedTable, `${route.label} ${viewport.name} table must render`).toBe(true);
            expect(state.fillsDesktopShell, `${route.label} ${viewport.name} table must fill its shell`).toBe(true);
          }
          }
        });
      }
    }
  });
});
