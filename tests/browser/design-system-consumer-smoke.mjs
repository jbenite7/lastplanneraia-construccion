import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const routes = [
  '/control-cambios', '/dashboard/escalamientos', '/indicadores',
  '/profesionales', '/programa-general-actualizar',
  '/programa-general', '/programacion-intermedia', '/programacion-semanal/cic',
  '/programacion-semanal/cnc', '/programacion-semanal/cnp',
  '/programacion-semanal', '/subcontratistas',
];

const AGGREGATOR = 'link[href^="/runtime/css/aia-design-system.css"]';
const CORE = 'link[href^="/runtime/css/design-system/entrypoints/core.css"]';

// Superficies migradas al design system segmentado: core + adjuntos declarados, nunca el
// agregador ni CSS de grilla. «Migrada» aquí es al entrypoint segmentado, no a React.

async function expectSegmentedHead(page, { attachments }) {
  await expect(page.locator(CORE)).toHaveCount(1);
  await expect(page.locator(AGGREGATOR)).toHaveCount(0);
  for (const vendor of ['jquery-ui', 'anychart', 'select2', 'sweetalert2', 'handsontable']) {
    const locator = page.locator(`link[href^="/runtime/css/design-system/entrypoints/attach-${vendor}.css"]`);
    await expect(locator, `attach-${vendor}`).toHaveCount(attachments.includes(vendor) ? 1 : 0);
  }
  await expect(page.locator('link[href*="handsontable-module.css"]')).toHaveCount(0);
}

test('the 15 shared-head consumers load the canonical entrypoint', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const route of routes) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
      await expect(
        page.locator(AGGREGATOR),
        `${route} must load the canonical entrypoint once`,
      ).toHaveCount(1);
      await expect(page.locator(CORE), `${route} must not load the segmented core`).toHaveCount(0);
      expect(await page.locator('body').innerText()).not.toContain('Fatal error');
    }
  } finally {
    await logout(page).catch(() => {});
  }
});

test('project selector loads the segmented core without grid vendors', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    await page.goto('/proyectos', { waitUntil: 'domcontentloaded' });
    await expectSegmentedHead(page, { attachments: [] });
  } finally {
    await logout(page).catch(() => {});
  }
});

// Las tres superficies de acceso (`/login`, `/password/forgot`, `/password/reset`) ya no las
// sirve una vista PHP: las sirve el shell React desde `App\Core\SpaHostRenderer`, con el head
// fijo de `public/app/index.html`. No queda ninguna ruta GET de acceso en PHP — `views/auth/
// login.view.php` solo atiende el POST legado —, así que este spec no conserva un caso PHP de
// acceso; el head segmentado sigue cubierto por el test de `/proyectos`.
const AUTH_REACT_ROUTES = ['/login', '/password/forgot', '/password/reset'];

async function expectReactShellHead(page) {
  for (const href of ['/css/tokens.css', '/css/aia-design-system.css', '/css/auth-react.css']) {
    await expect(page.locator(`link[href^="${href}"]`), href).toHaveCount(1);
  }
  // El tema lo fija el HTML construido (hoy `theme-claro.css`, el fallback del shell).
  await expect(page.locator('link[href^="/css/design-system/theme-"]')).toHaveCount(1);
  await expect(page.locator('link[href^="/css/design-system/theme-claro.css"]')).toHaveCount(1);
  await expect(page.locator('link[href^="/app/assets/index-"][href$=".css"]')).toHaveCount(1);
  await expect(page.locator('script[type="module"][src^="/app/assets/index-"]')).toHaveCount(1);

  // Nada del serving runtime segmentado ni de los adjuntos de vendor.
  await expect(page.locator(CORE)).toHaveCount(0);
  await expect(page.locator(AGGREGATOR)).toHaveCount(0);
  for (const vendor of ['jquery-ui', 'anychart', 'select2', 'sweetalert2', 'handsontable']) {
    const locator = page.locator(`link[href^="/runtime/css/design-system/entrypoints/attach-${vendor}.css"]`);
    await expect(locator, `attach-${vendor}`).toHaveCount(0);
  }
  await expect(page.locator('link[href*="handsontable-module.css"]')).toHaveCount(0);
}

test('auth surfaces load the React shell head', async ({ page }) => {
  for (const route of AUTH_REACT_ROUTES) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
    expect(response?.status(), `${route} must respond`).toBeLessThan(400);
    await expectReactShellHead(page);
    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  }
});
